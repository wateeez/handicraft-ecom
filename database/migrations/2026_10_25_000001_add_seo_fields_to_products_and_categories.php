<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'categories'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('meta_title', 70)->nullable();
                $table->string('meta_description', 200)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['products', 'categories'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['meta_title', 'meta_description']);
            });
        }
    }
};
