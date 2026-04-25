<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('spiritual_option')->nullable()->after('product_id');
            $table->decimal('option_price', 12, 2)->default(0)->after('unit_price');
            // purchase_type can be added if needed, but assuming it already exists elsewhere.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['spiritual_option', 'option_price']);
        });
    }
};
?>
