<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * order_notifications.status was enum('sent','failed') but the app also logs 'queued',
 * which MySQL rejects. A plain string keeps every existing value and allows new states.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE order_notifications MODIFY status VARCHAR(20) NOT NULL DEFAULT 'sent'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE order_notifications SET status = 'sent' WHERE status NOT IN ('sent', 'failed')");
            DB::statement("ALTER TABLE order_notifications MODIFY status ENUM('sent','failed') NOT NULL DEFAULT 'sent'");
        }
    }
};
