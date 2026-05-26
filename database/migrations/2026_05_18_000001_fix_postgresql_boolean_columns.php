<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Casts all boolean-intent columns to true PostgreSQL BOOLEAN type.
     * Safe to run if columns are already boolean — the USING clause is a no-op
     * when the source type is already boolean.
     *
     * This is needed when the database was originally created with MySQL
     * (which stores booleans as tinyint(1)) and was later migrated to
     * PostgreSQL, leaving the columns as smallint/integer instead of boolean.
     */
    public function up(): void
    {
        // Only run on PostgreSQL — MySQL/SQLite handle booleans differently
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $alterations = [
            // products table
            'products' => [
                'is_order_now_enabled',
                'is_new_arrival',
                'is_featured',
                'is_recommended',
                'is_on_sale',
            ],
            // orders table
            'orders' => [
                'is_paid',
                'is_merged',
            ],
            // blog_posts table
            'blog_posts' => [
                'is_published',
            ],
            // users table
            'users' => [
                'is_active',
            ],
            // payment_methods table
            'payment_methods' => [
                'is_active',
            ],
            // shipping_providers table
            'shipping_providers' => [
                'is_active',
            ],
        ];

        foreach ($alterations as $table => $columns) {
            foreach ($columns as $column) {
                // Check if the column exists before attempting the cast
                $exists = DB::select("
                    SELECT 1
                    FROM information_schema.columns
                    WHERE table_name = ?
                    AND column_name = ?
                    AND table_schema = 'public'
                ", [$table, $column]);

                if (empty($exists)) {
                    continue;
                }

                // Check current data type — skip if already boolean
                $type = DB::select("
                    SELECT data_type
                    FROM information_schema.columns
                    WHERE table_name = ?
                    AND column_name = ?
                    AND table_schema = 'public'
                ", [$table, $column]);

                if (!empty($type) && $type[0]->data_type === 'boolean') {
                    continue; // Already correct, nothing to do
                }

                DB::statement("
                    ALTER TABLE \"{$table}\"
                    ALTER COLUMN \"{$column}\" TYPE boolean
                    USING \"{$column}\"::boolean
                ");
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Casts boolean columns back to smallint (PostgreSQL equivalent of
     * MySQL's tinyint(1)). Data is preserved: true → 1, false → 0.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $alterations = [
            'products' => [
                'is_order_now_enabled',
                'is_new_arrival',
                'is_featured',
                'is_recommended',
                'is_on_sale',
            ],
            'orders' => [
                'is_paid',
                'is_merged',
            ],
            'blog_posts' => [
                'is_published',
            ],
            'users' => [
                'is_active',
            ],
            'payment_methods' => [
                'is_active',
            ],
            'shipping_providers' => [
                'is_active',
            ],
        ];

        foreach ($alterations as $table => $columns) {
            foreach ($columns as $column) {
                $exists = DB::select("
                    SELECT 1
                    FROM information_schema.columns
                    WHERE table_name = ?
                    AND column_name = ?
                    AND table_schema = 'public'
                ", [$table, $column]);

                if (empty($exists)) {
                    continue;
                }

                DB::statement("
                    ALTER TABLE \"{$table}\"
                    ALTER COLUMN \"{$column}\" TYPE smallint
                    USING \"{$column}\"::int
                ");
            }
        }
    }
};
