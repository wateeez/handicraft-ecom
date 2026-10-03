<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('unprocessed', 'quotation_sent', 'processed', 'dispatched', 'delivered', 'returned', 'refunded', 'cancelled', 'converted') NOT NULL DEFAULT 'unprocessed'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('orders')->whereIn('status', ['returned', 'refunded'])->update(['status' => 'delivered']);
            DB::statement("ALTER TABLE orders MODIFY status ENUM('unprocessed', 'quotation_sent', 'processed', 'dispatched', 'delivered', 'cancelled', 'converted') NOT NULL DEFAULT 'unprocessed'");
        }
    }
};