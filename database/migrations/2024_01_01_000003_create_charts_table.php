<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['natal', 'solar', 'transit', 'monthly', 'progressions', 'venus', 'synastry']);
            $table->json('input_data');
            $table->json('result_data')->nullable();
            $table->text('interpretation')->nullable();
            $table->integer('credits_spent')->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charts');
    }
};
