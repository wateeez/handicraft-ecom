<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add spiritual option fields to products table
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_spiritual_options')->default(false);
            $table->decimal('price_filling_only', 8, 2)->nullable();
            $table->decimal('price_blessing_only', 8, 2)->nullable();
            $table->decimal('price_both', 8, 2)->nullable();
        });

        // Add spiritual option fields to order_items table
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('spiritual_option')->nullable()->comment('filling, blessing, both, or null');
            $table->decimal('option_price', 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['has_spiritual_options', 'price_filling_only', 'price_blessing_only', 'price_both']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['spiritual_option', 'option_price']);
        });
    }
};
