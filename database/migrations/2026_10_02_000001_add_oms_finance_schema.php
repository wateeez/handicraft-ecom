<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
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

        Schema::table('orders', function (Blueprint $table) {
            $table->char('currency', 3)->default('USD')->after('type');
            $table->foreignId('distributor_id')->nullable()->after('client_id')
                ->constrained('distributors')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable()->after('expected_delivery_at');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('source_order_item_id')->nullable()->after('order_id')
                ->constrained('order_items')->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('pdf_sha256', 64)->nullable()->after('pdf_path');
        });

        Schema::table('order_audit_logs', function (Blueprint $table) {
            $table->string('subject_type')->nullable()->after('invoice_id');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::table('order_notifications', function (Blueprint $table) {
            $table->string('provider_message_id')->nullable()->after('status');
            $table->timestamp('failed_at')->nullable()->after('sent_at');
        });

        Schema::create('order_delivery_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('expected_delivery_at');
            $table->string('status')->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'expected_delivery_at']);
        });

        Schema::create('order_merges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('merged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('merged_at');
            $table->timestamps();
        });

        Schema::create('order_merge_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_merge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_order_id')->constrained('orders')->restrictOnDelete();
            $table->string('source_order_number');
            $table->json('financial_snapshot');
            $table->timestamps();
            $table->unique(['order_merge_id', 'source_order_id']);
        });

        Schema::create('finance_settings', function (Blueprint $table) {
            $table->id();
            $table->char('reporting_currency', 3)->default('USD');
            $table->string('default_commission_type')->default('percent');
            $table->decimal('default_commission_value', 15, 2)->default(0);
            $table->string('commission_base')->default('after_discount_excluding_shipping');
            $table->string('commission_earned_trigger')->default('payment');
            $table->string('loyalty_basis')->default('lifetime_spend');
            $table->boolean('payment_reminders_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('finance_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('payment_status')->default('unpaid');
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->timestamps();
            $table->index(['payment_status', 'order_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversal_of_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3);
            $table->decimal('reporting_amount', 15, 2);
            $table->decimal('reporting_exchange_rate', 18, 8);
            $table->string('method');
            $table->string('reference')->nullable();
            $table->date('received_on');
            $table->string('status')->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['order_id', 'status']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3);
            $table->decimal('reporting_amount', 15, 2);
            $table->decimal('reporting_exchange_rate', 18, 8);
            $table->string('status')->default('pending');
            $table->text('reason');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['order_id', 'status']);
        });

        Schema::create('finance_order_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('distributor_id')->nullable()->constrained()->nullOnDelete();
            $table->char('currency', 3);
            $table->decimal('reporting_exchange_rate', 18, 8);
            $table->decimal('distributor_share', 15, 2)->default(0);
            $table->decimal('payment_fee', 15, 2)->default(0);
            $table->decimal('direct_cost', 15, 2)->default(0);
            $table->string('commission_type')->default('percent');
            $table->decimal('commission_value', 15, 2)->default(0);
            $table->string('commission_base')->default('after_discount_excluding_shipping');
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->json('financial_snapshot');
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('distributor_payables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('distributor_id')->constrained()->restrictOnDelete();
            $table->char('currency', 3);
            $table->decimal('original_amount', 15, 2);
            $table->decimal('adjusted_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status')->default('not_due');
            $table->timestamps();
            $table->softDeletes();
            $table->unique('order_id');
            $table->index(['distributor_id', 'status']);
        });

        Schema::create('distributor_payout_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_id')->constrained()->restrictOnDelete();
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

        Schema::create('distributor_payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_payout_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('distributor_payable_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
            $table->unique(['distributor_payout_batch_id', 'distributor_payable_id'], 'distributor_payout_allocation_unique');
        });

        Schema::create('commission_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('payout_details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('commission_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('commission_recipients')->nullOnDelete();
            $table->char('currency', 3);
            $table->decimal('original_amount', 15, 2);
            $table->decimal('adjusted_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->json('basis_snapshot');
            $table->string('status')->default('pending');
            $table->timestamp('earned_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['recipient_id', 'status']);
        });

        Schema::create('commission_payout_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')->constrained('commission_recipients')->restrictOnDelete();
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

        Schema::create('commission_payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_payout_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commission_entry_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
            $table->unique(['commission_payout_batch_id', 'commission_entry_id'], 'commission_payout_allocation_unique');
        });

        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('rank')->unique();
            $table->string('qualification_basis')->default('lifetime_spend');
            $table->decimal('threshold_amount', 15, 2)->default(0);
            $table->unsignedInteger('threshold_order_count')->default(0);
            $table->decimal('suggested_discount_percent', 5, 2)->default(0);
            $table->boolean('is_vip')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('client_loyalty_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('loyalty_tier_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->default('automatic');
            $table->text('reason')->nullable();
            $table->timestamp('effective_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['client_id', 'ended_at']);
        });

        Schema::create('finance_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('action_type');
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['order_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['action_type', 'created_at']);
        });

        Schema::create('financial_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('entry_type');
            $table->char('currency', 3);
            $table->decimal('amount', 15, 2);
            $table->decimal('reporting_amount', 15, 2);
            $table->decimal('reporting_exchange_rate', 18, 8);
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['order_id', 'occurred_at']);
            $table->index(['source_type', 'source_id']);
            $table->index(['entry_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_ledger_entries');
        Schema::dropIfExists('finance_audit_logs');
        Schema::dropIfExists('client_loyalty_assignments');
        Schema::dropIfExists('loyalty_tiers');
        Schema::dropIfExists('commission_payout_items');
        Schema::dropIfExists('commission_payout_batches');
        Schema::dropIfExists('commission_entries');
        Schema::dropIfExists('commission_recipients');
        Schema::dropIfExists('distributor_payout_items');
        Schema::dropIfExists('distributor_payout_batches');
        Schema::dropIfExists('distributor_payables');
        Schema::dropIfExists('finance_order_snapshots');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('finance_invoices');
        Schema::dropIfExists('finance_settings');
        Schema::dropIfExists('order_merge_members');
        Schema::dropIfExists('order_merges');
        Schema::dropIfExists('order_delivery_schedules');

        Schema::table('order_notifications', function (Blueprint $table) {
            $table->dropColumn(['provider_message_id', 'failed_at']);
        });
        Schema::table('order_audit_logs', function (Blueprint $table) {
            $table->dropIndex(['subject_type', 'subject_id']);
            $table->dropColumn(['subject_type', 'subject_id']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('pdf_sha256');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_order_item_id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('distributor_id');
            $table->dropColumn(['currency', 'delivered_at']);
        });
        Schema::dropIfExists('distributors');
    }
};
