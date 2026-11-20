<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices de rendimiento para orders y reservations.
 *
 * Justificación:
 *
 * orders.user_id
 *   - Cada vez que un cliente carga "Mis pedidos" o el admin filtra por cliente
 *     se ejecuta WHERE user_id = ?. Sin índice, MySQL hace full scan de la tabla.
 *
 * orders.status
 *   - El dashboard admin cuenta pedidos pendientes/confirmados/preparando con
 *     WHERE status IN (...). Con volumen alto, el índice evita full scan.
 *
 * orders.payment_status
 *   - Los reportes de ingresos y la revisión de evidencias filtran por
 *     payment_status = 'pending' o 'paid' frecuentemente.
 *
 * reservations.user_id
 *   - El panel del cliente y el admin cargan reservas por usuario con
 *     WHERE user_id = ?. Mismo patrón que orders.user_id.
 *
 * reservations.reservation_date
 *   - El admin lista reservas ordenadas por fecha y el modelo verifica
 *     conflictos de horario con WHERE reservation_date = ?. El índice
 *     acelera ambas consultas.
 *
 * Nota: user_id en orders ya tiene FK implícita en InnoDB, pero Laravel
 * no garantiza que la FK genere un índice utilizable para ORDER BY / GROUP BY,
 * por lo que se declara explícitamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('user_id', 'orders_user_id_idx');
            $table->index('status', 'orders_status_idx');
            $table->index('payment_status', 'orders_payment_status_idx');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->index('user_id', 'reservations_user_id_idx');
            $table->index('reservation_date', 'reservations_reservation_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_id_idx');
            $table->dropIndex('orders_status_idx');
            $table->dropIndex('orders_payment_status_idx');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_user_id_idx');
            $table->dropIndex('reservations_reservation_date_idx');
        });
    }
};
