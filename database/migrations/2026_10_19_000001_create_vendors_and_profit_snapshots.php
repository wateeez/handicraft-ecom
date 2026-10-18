<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('cost_price_pct', 7, 4);
            $table->decimal('commission_pct', 7, 4);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('finance_rate_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('distributor_id')->constrained('vendors')->nullOnDelete();
        });

        Schema::create('order_finance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version');

            // Inputs, including every rate in force at calculation time
            $table->string('vendor_name')->nullable();
            $table->decimal('product_price_usd', 16, 2);
            $table->decimal('shipping_usd', 16, 2);
            $table->decimal('forex_rate', 14, 4);
            $table->decimal('actual_shipping_cost_npr', 16, 2)->default(0);
            $table->decimal('vendor_cost_price_pct', 7, 4);
            $table->decimal('vendor_commission_pct', 7, 4);
            $table->decimal('gateway_fee_pct', 7, 4);
            $table->decimal('csr_pct', 7, 4);
            $table->decimal('data_entry_pct', 7, 4);
            $table->decimal('de_fixed_rate', 14, 4);
            $table->decimal('de_threshold_rate', 14, 4);
            $table->boolean('include_shipping_in_commission');

            // Outputs
            $table->decimal('selling_price_usd', 16, 2);
            $table->decimal('gateway_fee_usd', 16, 2);
            $table->decimal('received_usd', 16, 2);
            $table->decimal('received_npr', 16, 2);
            $table->decimal('mrp_npr', 16, 2);
            $table->decimal('cost_price_npr', 16, 2);
            $table->decimal('vendor_commission_npr', 16, 2);
            $table->decimal('vendor_total_npr', 16, 2);
            $table->decimal('balance_after_vendor_npr', 16, 2);
            $table->decimal('commission_base_usd', 16, 2);
            $table->decimal('csr_usd', 16, 2);
            $table->decimal('csr_npr', 16, 2);
            $table->decimal('data_entry_usd', 16, 2);
            $table->decimal('data_entry_rate_used', 14, 4);
            $table->decimal('data_entry_npr', 16, 2);
            $table->decimal('leftover_npr', 16, 2);
            $table->decimal('net_profit_npr', 16, 2);

            $table->timestamps();
            $table->unique(['order_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_finance_snapshots');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
        });
        Schema::dropIfExists('finance_rate_settings');
        Schema::dropIfExists('vendors');
    }
};
