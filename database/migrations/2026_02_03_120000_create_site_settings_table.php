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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, image, textarea, etc.
            $table->string('group')->nullable(); // logo, general, social, etc.
            $table->timestamps();
        });

        // Insert default settings
        DB::table('site_settings')->insert([
            [
                'key' => 'site_name',
                'value' => 'Handicraft Nepal',
                'type' => 'text',
                'group' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'navbar_logo',
                'value' => null,
                'type' => 'image',
                'group' => 'logo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'footer_logo',
                'value' => null,
                'type' => 'image',
                'group' => 'logo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'favicon',
                'value' => null,
                'type' => 'image',
                'group' => 'logo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'footer_qr_code',
                'value' => null,
                'type' => 'image',
                'group' => 'footer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
