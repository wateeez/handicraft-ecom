<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Allow sub-categories to be nested under other sub-categories.
     * A null parent_id means the sub-category sits directly under its category.
     */
    public function up(): void
    {
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('category_id')
                ->constrained('sub_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
