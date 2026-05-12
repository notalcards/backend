<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_precalculations', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->json('birth_data');
            $table->json('result_data')->nullable();
            $table->longText('interpretation')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_precalculations');
    }
};
