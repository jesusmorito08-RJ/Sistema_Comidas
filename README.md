# Sazón — pedidos y reservas

Aplicación Laravel 13 de pedidos de comida a domicilio, carrito, ofertas, reservas por horario y administración. La instalación local está configurada para la instancia MariaDB/MySQL incluida en XAMPP.

## Conectar con XAMPP

1. Abre el panel de XAMPP y enciende **MySQL**.
2. La base de datos de la aplicación es `sazon_delivery` (utf8mb4). Si aún no existe, créala desde phpMyAdmin o ejecuta:

   ```sql
   CREATE DATABASE sazon_delivery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. En `.env`, revisa `APP_URL` y los valores `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=sazon_delivery` y las credenciales de tu instalación. Para Apache/XAMPP usa `APP_URL=http://localhost/SISTEMA_COMIDAS/public`; si inicias con `php artisan serve`, usa `APP_URL=http://127.0.0.1:8000`. XAMPP normalmente usa el usuario `root` sin contraseña local.
4. Desde la carpeta del proyecto, ejecuta:

   ```powershell
   php artisan migrate --seed
   php artisan storage:link
   php artisan serve
   ```

5. Con Apache activo, abre `http://localhost/SISTEMA_COMIDAS/public`. XAMPP redirige el proyecto a `public/`, y cada sección conserva su ruta separada (por ejemplo, `/SISTEMA_COMIDAS/public/admin` y `/SISTEMA_COMIDAS/public/carrito`).
   Alternativamente, desde el terminal puedes iniciar `php artisan serve` y abrir `http://127.0.0.1:8000`.

Las migraciones crean las tablas `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `foods`, `orders`, `order_items` y `reservations`. El seeder agrega dos cuentas y 16 comidas de muestra, incluidos platos peruanos. El menú permite filtrar por categorías como entradas peruanas, platos de fondo, anticuchos, sopas, postres y bebidas. No borres la base de datos con `migrate:fresh` si quieres conservar pedidos y clientes.

Para agregar clientes de prueba, configura `SEED_CUSTOMER_EMAILS` en el `.env` con correos separados por comas y ejecuta `php artisan db:seed --class=AdditionalCustomersSeeder`. El nombre inicial se toma del correo y la contraseña se copia de la cuenta demo de Sofía. Usa estas credenciales compartidas solo en desarrollo y reemplázalas antes de usar cuentas reales.

## URLs de la aplicación

| Sección | URL |
| --- | --- |
| Inicio y menú | `/SISTEMA_COMIDAS/public/` |
| Catálogo | `/SISTEMA_COMIDAS/public/comidas` |
| Carrito | `/SISTEMA_COMIDAS/public/carrito` |
| Ingresar | `/SISTEMA_COMIDAS/public/ingresar` |
| Crear cuenta | `/SISTEMA_COMIDAS/public/crear-cuenta` |
| Mis pedidos | `/SISTEMA_COMIDAS/public/mis-pedidos` |
| Reservas del cliente | `/SISTEMA_COMIDAS/public/reservas` |
| Panel administrador | `/SISTEMA_COMIDAS/public/admin` |
| Comidas del administrador | `/SISTEMA_COMIDAS/public/admin/comidas` |
| Pedidos del administrador | `/SISTEMA_COMIDAS/public/admin/pedidos` |
| Reservas del administrador | `/SISTEMA_COMIDAS/public/admin/reservas` |
| Clientes | `/SISTEMA_COMIDAS/public/admin/clientes` |
| Reportes | `/SISTEMA_COMIDAS/public/admin/reportes` |

Cada sección tiene su propia ruta y vista. Las páginas Blade están organizadas en `resources/views/auth`, `resources/views/store`, `resources/views/admin` y `resources/views/layouts`; rutas en `routes/web.php`, controladores en `app/Http/Controllers`, modelos en `app/Models` y migraciones en `database/migrations`.

## Cuentas locales de demostración

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Administrador | `admin@sazon.test` | `admin12345` |
| Cliente | `cliente@sazon.test` | `cliente12345` |

Solo son credenciales para desarrollo; reemplázalas antes de publicar la aplicación. La tarjeta y transferencia se registran como método elegido, pero el proyecto no se conecta a una pasarela ni procesa cobros.

Las fotos que suba el administrador se guardan en `storage/app/public/foods`. Las fotos de muestra usan URLs externas.

## Pruebas

```powershell
php artisan test
```
