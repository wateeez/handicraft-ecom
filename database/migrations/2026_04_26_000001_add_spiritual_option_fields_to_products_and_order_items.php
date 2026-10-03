<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'has_spiritual_options')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('has_spiritual_options')->default(false)->after('secondary_image');
                $table->decimal('price_filling_only', 10, 2)->nullable()->after('has_spiritual_options');
                $table->decimal('price_blessing_only', 10, 2)->nullable()->after('price_filling_only');
                $table->decimal('price_both', 10, 2)->nullable()->after('price_blessing_only');
            });
        }

        if (!Schema::hasColumn('order_items', 'purchase_type')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('purchase_type', 20)->default('normal')->after('product_snapshot');
                $table->string('spiritual_option', 30)->nullable()->after('purchase_type');
                $table->decimal('option_price', 12, 2)->default(0)->after('spiritual_option');
            });
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['purchase_type', 'spiritual_option', 'option_price']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'has_spiritual_options',
                'price_filling_only',
                'price_blessing_only',
                'price_both',
            ]);
        });
    }
};
