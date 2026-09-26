<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->enum('return_status', ['none', 'partially_returned', 'returned'])
                ->default('none')
                ->after('line_total');
            $table->unsignedInteger('returned_quantity')->default(0)->after('return_status');
            $table->text('return_reason')->nullable()->after('returned_quantity');
            $table->timestamp('returned_at')->nullable()->after('return_reason');
            $table->foreignId('returned_by')->nullable()->after('returned_at')
                ->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('returned_by');
            $table->dropColumn(['return_status', 'returned_quantity', 'return_reason', 'returned_at']);
        });
    }
};
