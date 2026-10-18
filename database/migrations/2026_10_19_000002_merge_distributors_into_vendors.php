<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vendors replace distributors as the single "who we buy from and pay" entity.
 * The distributor tables were never populated, so nothing is migrated; the
 * migration refuses to run if that assumption turns out to be wrong.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['distributors', 'distributor_payables', 'distributor_payout_batches'] as $table) {
            if (DB::table($table)->count() > 0) {
                throw new RuntimeException("Cannot merge distributors into vendors: {$table} contains rows.");
            }
        }

        Schema::table('vendors', function (Blueprint $table) {
            $table->string('email')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->json('payout_details')->nullable()->after('address');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('distributor_id');
        });

        Schema::table('finance_order_snapshots', function (Blueprint $table) {
            $table->dropForeign(['distributor_id']);
        });
        Schema::table('finance_order_snapshots', function (Blueprint $table) {
            $table->renameColumn('distributor_id', 'vendor_id');
            $table->renameColumn('distributor_share', 'vendor_share');
        });
        Schema::table('finance_order_snapshots', function (Blueprint $table) {
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
        });

        Schema::table('distributor_payout_items', function (Blueprint $table) {
            $table->dropForeign(['distributor_payout_batch_id']);
            $table->dropForeign(['distributor_payable_id']);
        });
        Schema::table('distributor_payables', function (Blueprint $table) {
            $table->dropForeign(['distributor_id']);
        });
        Schema::table('distributor_payout_batches', function (Blueprint $table) {
            $table->dropForeign(['distributor_id']);
        });

        Schema::table('distributor_payout_items', function (Blueprint $table) {
            $table->renameColumn('distributor_payout_batch_id', 'vendor_payout_batch_id');
            $table->renameColumn('distributor_payable_id', 'vendor_payable_id');
        });
        Schema::table('distributor_payables', function (Blueprint $table) {
            $table->renameColumn('distributor_id', 'vendor_id');
        });
        Schema::table('distributor_payout_batches', function (Blueprint $table) {
            $table->renameColumn('distributor_id', 'vendor_id');
        });

        Schema::rename('distributor_payout_items', 'vendor_payout_items');
        Schema::rename('distributor_payables', 'vendor_payables');
        Schema::rename('distributor_payout_batches', 'vendor_payout_batches');

        Schema::table('vendor_payables', function (Blueprint $table) {
            $table->foreign('vendor_id')->references('id')->on('vendors')->restrictOnDelete();
        });
        Schema::table('vendor_payout_batches', function (Blueprint $table) {
            $table->foreign('vendor_id')->references('id')->on('vendors')->restrictOnDelete();
        });
        Schema::table('vendor_payout_items', function (Blueprint $table) {
            $table->foreign('vendor_payout_batch_id')->references('id')->on('vendor_payout_batches')->cascadeOnDelete();
            $table->foreign('vendor_payable_id')->references('id')->on('vendor_payables')->restrictOnDelete();
        });

        Schema::dropIfExists('distributors');
    }

    public function down(): void
    {
        Schema::create('distributors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->json('payout_details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('vendor_payout_items', function (Blueprint $table) {
            $table->dropForeign(['vendor_payout_batch_id']);
            $table->dropForeign(['vendor_payable_id']);
        });
        Schema::table('vendor_payables', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
        });
        Schema::table('vendor_payout_batches', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
        });
        Schema::table('finance_order_snapshots', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
        });

        Schema::table('vendor_payout_items', function (Blueprint $table) {
            $table->renameColumn('vendor_payout_batch_id', 'distributor_payout_batch_id');
            $table->renameColumn('vendor_payable_id', 'distributor_payable_id');
        });
        Schema::table('vendor_payables', function (Blueprint $table) {
            $table->renameColumn('vendor_id', 'distributor_id');
        });
        Schema::table('vendor_payout_batches', function (Blueprint $table) {
            $table->renameColumn('vendor_id', 'distributor_id');
        });
        Schema::table('finance_order_snapshots', function (Blueprint $table) {
            $table->renameColumn('vendor_id', 'distributor_id');
            $table->renameColumn('vendor_share', 'distributor_share');
        });

        Schema::rename('vendor_payout_items', 'distributor_payout_items');
        Schema::rename('vendor_payables', 'distributor_payables');
        Schema::rename('vendor_payout_batches', 'distributor_payout_batches');

        Schema::table('finance_order_snapshots', function (Blueprint $table) {
            $table->foreign('distributor_id')->references('id')->on('distributors')->nullOnDelete();
        });
        Schema::table('distributor_payables', function (Blueprint $table) {
            $table->foreign('distributor_id')->references('id')->on('distributors')->restrictOnDelete();
        });
        Schema::table('distributor_payout_batches', function (Blueprint $table) {
            $table->foreign('distributor_id')->references('id')->on('distributors')->restrictOnDelete();
        });
        Schema::table('distributor_payout_items', function (Blueprint $table) {
            $table->foreign('distributor_payout_batch_id')->references('id')->on('distributor_payout_batches')->cascadeOnDelete();
            $table->foreign('distributor_payable_id')->references('id')->on('distributor_payables')->restrictOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('distributor_id')->nullable()->after('client_id')->constrained('distributors')->nullOnDelete();
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone', 'address', 'payout_details']);
        });
    }
};
