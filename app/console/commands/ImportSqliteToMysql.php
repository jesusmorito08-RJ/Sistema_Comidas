<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use PDOException;
use RuntimeException;

class ImportSqliteToMysql extends Command
{
    protected $signature = 'sqlite:import-mysql
        {--source= : Ruta de la copia SQLite que se va a leer}
        {--dry-run : Validar y mostrar conteos sin escribir}
        {--execute : Importar tras completar todas las validaciones}';

    protected $description = 'Importa registros SQLite a MySQL preservando IDs y valores originales.';

    private const TARGET_DATABASE = 'sistema_comidas';

    private const EXPECTED_COUNTS = [
        'users' => 2,
        'foods' => 19,
        'orders' => 2,
        'order_items' => 2,
        'reservations' => 0,
    ];

    private const TABLE_COLUMNS = [
        'users' => [
            'id', 'name', 'email', 'email_verified_at', 'password',
            'remember_token', 'created_at', 'updated_at', 'role',
            'phone', 'address', 'is_active',
        ],
        'foods' => [
            'id', 'name', 'category', 'description', 'price', 'offer_price',
            'image_path', 'is_available', 'is_featured', 'created_at',
            'updated_at', 'deleted_at',
        ],
        'orders' => [
            'id', 'user_id', 'order_number', 'status', 'payment_method',
            'payment_status', 'total', 'delivery_address', 'notes',
            'created_at', 'updated_at', 'deleted_at',
        ],
        'order_items' => [
            'id', 'order_id', 'food_id', 'food_name', 'unit_price',
            'quantity', 'subtotal', 'created_at', 'updated_at',
        ],
        'reservations' => [
            'id', 'user_id', 'reservation_date', 'reservation_time',
            'guests', 'status', 'notes', 'created_at', 'updated_at',
        ],
    ];

    private const REQUIRED_MYSQL_MIGRATIONS = [
        '0001_01_01_000000_create_users_table',
        '0001_01_01_000001_create_cache_table',
        '0001_01_01_000002_create_jobs_table',
        '2026_11_12_210000_create_food_ordering_tables',
    ];

    private const REQUIRED_SOURCE_MIGRATIONS = [
        '0001_01_01_000000_create_users_table',
        '0001_01_01_000001_create_cache_table',
        '0001_01_01_000002_create_jobs_table',
        '2026_11_12_210000_create_food_ordering_tables',
        '2026_11_16_210652_update_criollo_food_categories_and_add_a_la_carta_dishes',
    ];

    private const DEFERRED_MIGRATION =
        '2026_11_16_210652_update_criollo_food_categories_and_add_a_la_carta_dishes';

    private const DECIMAL_COLUMNS = [
        'foods' => ['price', 'offer_price'],
        'orders' => ['total'],
        'order_items' => ['unit_price', 'subtotal'],
    ];

    private const INTEGER_COLUMNS = [
        'id', 'user_id', 'order_id', 'food_id', 'quantity', 'guests',
        'is_available', 'is_featured',
    ];

    private const FOREIGN_KEYS = [
        ['table' => 'orders', 'column' => 'user_id', 'parent' => 'users'],
        ['table' => 'order_items', 'column' => 'order_id', 'parent' => 'orders'],
        ['table' => 'order_items', 'column' => 'food_id', 'parent' => 'foods'],
        ['table' => 'reservations', 'column' => 'user_id', 'parent' => 'users'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $execute = (bool) $this->option('execute');

        if ($dryRun === $execute) {
            $this->error('Indica exactamente una opción: --dry-run o --execute.');

            return self::FAILURE;
        }

        try {
            $sourcePath = $this->resolveSourcePath();
            $sqlite = $this->openSource($sourcePath);
            $mysql = DB::connection('mysql');

            $sourceCounts = $this->validateSource($sqlite);
            $nullableColumns = $this->validateSchemas($sqlite, $mysql);
            $this->validateDestination($mysql);

            if ($dryRun) {
                $this->info('DRY RUN: validaciones correctas; no se escribieron datos.');
                $this->printCounts($sourceCounts, 'SQLite');
                $this->info('MySQL: las cinco tablas de destino están vacías.');

                return self::SUCCESS;
            }

            $mysql->transaction(function () use (
                $sqlite,
                $mysql,
                $nullableColumns
            ): void {
                $this->assertDestinationTablesEmpty($mysql);

                foreach (array_keys(self::EXPECTED_COUNTS) as $table) {
                    foreach ($this->readRows($sqlite, $table) as $row) {
                        $mysql->table($table)->insert($row);
                    }
                }

                $this->assertTargetCounts($mysql);
                $this->assertImportedRowsMatchSource($sqlite, $mysql);
                $this->assertNoOrphanRows($mysql, $nullableColumns);
                $this->assertAutoIncrementReady($mysql);
            });

            $this->info('Importación completada y verificada antes del commit.');
            $this->printCounts(self::EXPECTED_COUNTS, 'MySQL');

            return self::SUCCESS;
        } catch (QueryException) {
            $this->error(
                'MySQL rechazó una validación o escritura. '
                .'Si ocurrió durante la importación, la transacción fue revertida. '
                .'No se muestran valores de registros.'
            );

            return self::FAILURE;
        } catch (PDOException) {
            $this->error('No se pudo leer la copia SQLite en modo de solo lectura.');

            return self::FAILURE;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolveSourcePath(): string
    {
        $providedPath = $this->option('source');

        if (! is_string($providedPath) || trim($providedPath) === '') {
            throw new RuntimeException('Debes indicar --source con la ruta de la copia SQLite.');
        }

        $resolvedPath = realpath($providedPath);

        if ($resolvedPath === false || ! is_file($resolvedPath)) {
            throw new RuntimeException('El archivo indicado en --source no existe.');
        }

        $originalPath = realpath(database_path('database.sqlite'));

        if ($originalPath !== false && $this->normalizePath($resolvedPath) === $this->normalizePath($originalPath)) {
            throw new RuntimeException(
                'La ruta indicada es el SQLite original; usa la copia de seguridad.'
            );
        }

        return $resolvedPath;
    }

    private function normalizePath(string $path): string
    {
        return strtolower(str_replace('\\', '/', $path));
    }

    private function openSource(string $path): PDO
    {
        $sqlite = new PDO('sqlite:'.$path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES => true,
            PDO::SQLITE_ATTR_OPEN_FLAGS => PDO::SQLITE_OPEN_READONLY,
        ]);

        $sqlite->exec('PRAGMA query_only = ON');

        if ($sqlite->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
            throw new RuntimeException('La comprobación de integridad de SQLite falló.');
        }

        return $sqlite;
    }

    /**
     * @return array<string, int>
     */
    private function validateSource(PDO $sqlite): array
    {
        $migrations = $sqlite
            ->query('SELECT migration FROM migrations')
            ->fetchAll(PDO::FETCH_COLUMN);

        foreach (self::REQUIRED_SOURCE_MIGRATIONS as $migration) {
            if (! in_array($migration, $migrations, true)) {
                throw new RuntimeException(
                    'La copia SQLite no tiene las migraciones esperadas.'
                );
            }
        }

        if ($sqlite->query('PRAGMA foreign_key_check')->fetchAll() !== []) {
            throw new RuntimeException('SQLite contiene referencias foráneas inválidas.');
        }

        if ((int) $sqlite->query(
            'SELECT COUNT(*) - COUNT(DISTINCT lower(email)) FROM users'
        )->fetchColumn() !== 0) {
            throw new RuntimeException('SQLite contiene correos duplicados.');
        }

        if ((int) $sqlite->query(
            'SELECT COUNT(*) - COUNT(DISTINCT lower(order_number)) FROM orders'
        )->fetchColumn() !== 0) {
            throw new RuntimeException('SQLite contiene números de pedido duplicados.');
        }

        foreach (self::EXPECTED_COUNTS as $table => $expectedCount) {
            $actualCount = (int) $sqlite
                ->query('SELECT COUNT(*) FROM '.$table)
                ->fetchColumn();

            if ($actualCount !== $expectedCount) {
                throw new RuntimeException(
                    "El conteo de SQLite para {$table} no coincide con el esperado."
                );
            }

            if ((int) $sqlite->query(
                'SELECT COUNT(*) FROM '.$table.' WHERE id <= 0'
            )->fetchColumn() !== 0) {
                throw new RuntimeException(
                    "La tabla SQLite {$table} contiene IDs no válidos."
                );
            }

            $this->assertSourceColumns($sqlite, $table);
        }

        $this->assertSourceDecimals($sqlite);

        return self::EXPECTED_COUNTS;
    }

    private function assertSourceDecimals(PDO $sqlite): void
    {
        foreach (self::DECIMAL_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $sql = 'SELECT COUNT(*) FROM "'.$table.'" '
                    .'WHERE "'.$column.'" IS NOT NULL '
                    .'AND (ABS("'.$column.'") > 99999999.99 '
                    .'OR ABS("'.$column.'" - ROUND("'.$column.'", 2)) > 0.00000001)';

                if ((int) $sqlite->query($sql)->fetchColumn() !== 0) {
                    throw new RuntimeException(
                        "La tabla SQLite {$table} tiene importes incompatibles."
                    );
                }
            }
        }
    }

    /**
     * Compara columnas y nulabilidad entre SQLite y MySQL.
     *
     * @return array<string, array<string, bool>>
     */
    private function validateSchemas(PDO $sqlite, Connection $mysql): array
    {
        $nullableColumns = [];
        $database = $mysql->getDatabaseName();

        foreach (self::TABLE_COLUMNS as $table => $expectedColumns) {
            $sourceMetadata = $this->sourceColumnMetadata($sqlite, $table);
            $targetMetadata = $this->mysqlColumnMetadata($mysql, $database, $table);

            $expected = $this->sortedColumns($expectedColumns);
            $sourceNames = $this->sortedColumns(array_keys($sourceMetadata));
            $targetNames = $this->sortedColumns(array_keys($targetMetadata));

            if ($sourceNames !== $expected || $targetNames !== $expected) {
                throw new RuntimeException(
                    "Las columnas de {$table} no coinciden entre origen y destino."
                );
            }

            foreach ($expectedColumns as $column) {
                if ($sourceMetadata[$column] !== $targetMetadata[$column]) {
                    throw new RuntimeException(
                        "La nulabilidad de {$table}.{$column} difiere entre origen y destino."
                    );
                }

                $nullableColumns[$table][$column] = $sourceMetadata[$column];
            }

            $this->assertRequiredSourceColumnsNotNull($sqlite, $table, $sourceMetadata);
        }

        return $nullableColumns;
    }

    /**
     * @return array<string, bool>
     */
    private function sourceColumnMetadata(PDO $sqlite, string $table): array
    {
        $metadata = [];

        foreach ($sqlite->query('PRAGMA table_info("'.$table.'")') as $column) {
            $metadata[$column['name']] =
                (int) $column['notnull'] === 0 && (int) $column['pk'] === 0;
        }

        return $metadata;
    }

    /**
     * @return array<string, bool>
     */
    private function mysqlColumnMetadata(
        Connection $mysql,
        string $database,
        string $table
    ): array {
        $metadata = [];

        foreach ($mysql->select(
            'SELECT COLUMN_NAME, IS_NULLABLE FROM information_schema.COLUMNS '
            .'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$database, $table]
        ) as $column) {
            $metadata[$column->COLUMN_NAME] = $column->IS_NULLABLE === 'YES';
        }

        return $metadata;
    }

    /**
     * @param  array<string, bool>  $metadata
     */
    private function assertRequiredSourceColumnsNotNull(
        PDO $sqlite,
        string $table,
        array $metadata
    ): void {
        foreach ($metadata as $column => $nullable) {
            if ($nullable) {
                continue;
            }

            $sql = 'SELECT COUNT(*) FROM "'.$table.'" WHERE "'.$column.'" IS NULL';

            if ((int) $sqlite->query($sql)->fetchColumn() !== 0) {
                throw new RuntimeException(
                    "La tabla SQLite {$table} contiene NULL en una columna obligatoria."
                );
            }
        }
    }

    private function validateDestination(Connection $mysql): void
    {
        $config = $mysql->getConfig();
        $host = strtolower((string) ($config['host'] ?? ''));
        $port = (string) ($config['port'] ?? '');

        if (
            $mysql->getDriverName() !== 'mysql'
            || $mysql->getDatabaseName() !== self::TARGET_DATABASE
            || ! in_array($host, ['127.0.0.1', 'localhost'], true)
            || $port !== '3306'
        ) {
            throw new RuntimeException(
                'La conexión mysql no coincide con el destino local esperado.'
            );
        }

        $schema = Schema::connection('mysql');

        if (! $schema->hasTable('migrations')) {
            throw new RuntimeException('No se encontró la tabla migrations en MySQL.');
        }

        $applied = $mysql->table('migrations')->pluck('migration')->all();

        foreach (self::REQUIRED_MYSQL_MIGRATIONS as $migration) {
            if (! in_array($migration, $applied, true)) {
                throw new RuntimeException(
                    'MySQL no tiene aplicadas las primeras cuatro migraciones.'
                );
            }
        }

        if (in_array(self::DEFERRED_MIGRATION, $applied, true)) {
            throw new RuntimeException(
                'La quinta migración ya figura como aplicada en MySQL.'
            );
        }

        foreach (array_keys(self::EXPECTED_COUNTS) as $table) {
            if (! $schema->hasTable($table)) {
                throw new RuntimeException("Falta la tabla MySQL {$table}.");
            }
        }

        $this->assertInnoDbTables($mysql);
        $this->assertDestinationTablesEmpty($mysql);
    }

    private function assertInnoDbTables(Connection $mysql): void
    {
        $tables = array_keys(self::EXPECTED_COUNTS);
        $placeholders = implode(', ', array_fill(0, count($tables), '?'));

        $engines = $mysql->select(
            'SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES '
            .'WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ('.$placeholders.')',
            array_merge([$mysql->getDatabaseName()], $tables)
        );

        $engineByTable = [];

        foreach ($engines as $row) {
            $engineByTable[strtolower($row->TABLE_NAME)] =
                strtoupper((string) $row->ENGINE);
        }

        foreach ($tables as $table) {
            if (($engineByTable[strtolower($table)] ?? null) !== 'INNODB') {
                throw new RuntimeException(
                    "La tabla {$table} no usa InnoDB; importación cancelada."
                );
            }
        }
    }

    private function assertDestinationTablesEmpty(Connection $mysql): void
    {
        foreach (array_keys(self::EXPECTED_COUNTS) as $table) {
            if ($mysql->table($table)->exists()) {
                throw new RuntimeException(
                    "La tabla MySQL {$table} no está vacía; no se importó nada."
                );
            }
        }
    }

    private function assertSourceColumns(PDO $sqlite, string $table): void
    {
        $sourceColumns = $this->sourceColumnMetadata($sqlite, $table);
        $expectedColumns = $this->sortedColumns(self::TABLE_COLUMNS[$table]);

        if ($this->sortedColumns(array_keys($sourceColumns)) !== $expectedColumns) {
            throw new RuntimeException(
                "Las columnas de SQLite para {$table} no son las esperadas."
            );
        }
    }

    /**
     * @param  list<string>  $columns
     * @return list<string>
     */
    private function sortedColumns(array $columns): array
    {
        sort($columns);

        return $columns;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readRows(PDO $sqlite, string $table): array
    {
        $columns = implode(
            ', ',
            array_map(
                static fn (string $column): string => '"'.$column.'"',
                self::TABLE_COLUMNS[$table]
            )
        );

        return $sqlite
            ->query('SELECT '.$columns.' FROM "'.$table.'" ORDER BY "id"')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function assertTargetCounts(Connection $mysql): void
    {
        foreach (self::EXPECTED_COUNTS as $table => $expectedCount) {
            if ((int) $mysql->table($table)->count() !== $expectedCount) {
                throw new RuntimeException(
                    "El conteo MySQL de {$table} no coincide; importación cancelada."
                );
            }
        }
    }

    private function assertImportedRowsMatchSource(PDO $sqlite, Connection $mysql): void
    {
        foreach (array_keys(self::EXPECTED_COUNTS) as $table) {
            foreach ($this->readRows($sqlite, $table) as $sourceRow) {
                $targetRow = $mysql->table($table)
                    ->where('id', $sourceRow['id'])
                    ->first();

                if ($targetRow === null) {
                    throw new RuntimeException(
                        "Falta un registro importado en {$table}; transacción cancelada."
                    );
                }

                if (
                    $this->normalizeRow($table, $sourceRow)
                    !== $this->normalizeRow($table, (array) $targetRow)
                ) {
                    throw new RuntimeException(
                        "Los datos importados de {$table} difieren del origen."
                    );
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, int|string|null>
     */
    private function normalizeRow(string $table, array $row): array
    {
        $normalized = [];

        foreach (self::TABLE_COLUMNS[$table] as $column) {
            $value = $row[$column];

            if ($value === null) {
                $normalized[$column] = null;

                continue;
            }

            if (in_array($column, self::DECIMAL_COLUMNS[$table] ?? [], true)) {
                $normalized[$column] = number_format((float) $value, 2, '.', '');

                continue;
            }

            if (in_array($column, self::INTEGER_COLUMNS, true)) {
                $normalized[$column] = (string) ((int) $value);

                continue;
            }

            $normalized[$column] = (string) $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, array<string, bool>>  $nullableColumns
     */
    private function assertNoOrphanRows(
        Connection $mysql,
        array $nullableColumns
    ): void {
        foreach (self::FOREIGN_KEYS as $foreignKey) {
            $table = $foreignKey['table'];
            $column = $foreignKey['column'];
            $parent = $foreignKey['parent'];

            $query = $mysql->table($table.' as child')
                ->leftJoin($parent.' as parent', 'parent.id', '=', 'child.'.$column)
                ->whereNull('parent.id');

            if ($nullableColumns[$table][$column]) {
                $query->whereNotNull('child.'.$column);
            }

            if ($query->count() !== 0) {
                throw new RuntimeException(
                    "La verificación de relaciones falló para {$table}; transacción cancelada."
                );
            }
        }
    }

    private function assertAutoIncrementReady(Connection $mysql): void
    {
        foreach (array_keys(self::EXPECTED_COUNTS) as $table) {
            $row = $mysql->selectOne(
                'SELECT AUTO_INCREMENT FROM information_schema.TABLES '
                .'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [$mysql->getDatabaseName(), $table]
            );

            $nextId = isset($row->AUTO_INCREMENT) ? (int) $row->AUTO_INCREMENT : 0;
            $maxId = (int) ($mysql->table($table)->max('id') ?? 0);

            if ($nextId < max(1, $maxId + 1)) {
                throw new RuntimeException(
                    "El AUTO_INCREMENT de {$table} no quedó por encima del ID máximo."
                );
            }
        }
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function printCounts(array $counts, string $label): void
    {
        foreach ($counts as $table => $count) {
            $this->line("{$label} {$table}: {$count}");
        }
    }
}
