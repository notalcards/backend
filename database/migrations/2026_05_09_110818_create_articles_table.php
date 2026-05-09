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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('excerpt');
            $table->string('emoji')->default('📝');
            $table->string('gradient')->default('linear-gradient(135deg, #2D1B69 0%, #7C3AED 100%)');
            $table->string('category')->default('Астрология');
            $table->string('author')->default('Редакция');
            $table->json('content')->nullable();
            $table->string('read_time')->default('5 мин');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
