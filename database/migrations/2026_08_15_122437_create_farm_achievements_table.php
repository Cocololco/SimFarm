<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('unlocked_on_day');
            $table->timestamps();

            $table->unique(['farm_id', 'achievement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_achievements');
    }
};
