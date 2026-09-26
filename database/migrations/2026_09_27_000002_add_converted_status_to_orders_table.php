<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds the 'converted' status (an inquiry that was checked out and paid as
     * a real order) to the orders.status enum.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('unprocessed', 'quotation_sent', 'processed', 'dispatched', 'delivered', 'cancelled', 'converted') NOT NULL DEFAULT 'unprocessed'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('orders')->where('status', 'converted')->update(['status' => 'quotation_sent']);
            DB::statement("ALTER TABLE orders MODIFY status ENUM('unprocessed', 'quotation_sent', 'processed', 'dispatched', 'delivered', 'cancelled') NOT NULL DEFAULT 'unprocessed'");
        }
    }
};
