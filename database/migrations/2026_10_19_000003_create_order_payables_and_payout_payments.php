<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the vendor-only payable/batch tables with one payable per payee
 * type (vendor, CSR, data entry) per order, and individual payments against it.
 * Everything is in NPR. The replaced tables were never populated.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['vendor_payables', 'vendor_payout_batches', 'vendor_payout_items'] as $table) {
            if (DB::table($table)->count() > 0) {
                throw new RuntimeException("Cannot replace {$table}: it contains rows.");
            }
        }

        Schema::dropIfExists('vendor_payout_items');
        Schema::dropIfExists('vendor_payout_batches');
        Schema::dropIfExists('vendor_payables');

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('payout_details');
        });

        Schema::create('order_payables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('payee_type', 20); // vendor | csr | data_entry
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->restrictOnDelete();
            $table->decimal('amount', 16, 2);
            $table->decimal('paid_amount', 16, 2)->default(0);
            $table->string('status', 20)->default('unpaid'); // unpaid | partial | paid
            $table->timestamps();
            $table->unique(['order_id', 'payee_type']);
            $table->index(['payee_type', 'status']);
        });

        Schema::create('payout_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_payable_id')->constrained('order_payables')->cascadeOnDelete();
            $table->decimal('amount', 16, 2);
            $table->date('paid_on');
            $table->string('method', 100);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_payments');
        Schema::dropIfExists('order_payables');

        Schema::table('vendors', function (Blueprint $table) {
            $table->json('payout_details')->nullable()->after('address');
        });

        Schema::create('vendor_payables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->char('currency', 3);
            $table->decimal('original_amount', 15, 2);
            $table->decimal('adjusted_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status')->default('not_due');
            $table->timestamps();
            $table->softDeletes();
            $table->unique('order_id');
            $table->index(['vendor_id', 'status']);
        });
        Schema::create('vendor_payout_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('paid_on');
            $table->char('currency', 3);
            $table->decimal('amount', 15, 2);
            $table->string('method');
            $table->string('reference')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('vendor_payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_payout_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_payable_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
            $table->unique(['vendor_payout_batch_id', 'vendor_payable_id'], 'distributor_payout_allocation_unique');
        });
    }
};
