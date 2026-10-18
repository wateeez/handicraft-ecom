<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Finance is reported in NPR (reporting amounts are order amount x NPR rate), but the
 * setting defaulted to USD, so NPR figures were labelled "USD".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_settings', function ($table) {
            $table->char('reporting_currency', 3)->default('NPR')->change();
        });

        DB::table('finance_settings')->where('reporting_currency', 'USD')->update(['reporting_currency' => 'NPR']);
    }

    public function down(): void
    {
        Schema::table('finance_settings', function ($table) {
            $table->char('reporting_currency', 3)->default('USD')->change();
        });
    }
};
