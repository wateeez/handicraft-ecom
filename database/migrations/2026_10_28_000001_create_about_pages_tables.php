<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_pages', function (Blueprint $table) {
            $table->id();
            $table->string('meta_title');
            $table->string('meta_description');
            $table->string('hero_heading');
            $table->text('hero_intro');
            $table->string('hero_image_path')->nullable();
            $table->string('hero_image_alt')->nullable();
            $table->timestamps();
        });

        Schema::create('about_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('about_page_id')->constrained()->cascadeOnDelete();
            $table->string('key')->nullable();
            $table->string('heading');
            $table->text('body')->nullable();
            $table->json('items')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['about_page_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_sections');
        Schema::dropIfExists('about_pages');
    }
};
