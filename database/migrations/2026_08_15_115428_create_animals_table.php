<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_type_id')->constrained();
            $table->string('nickname')->nullable();
            $table->unsignedInteger('fed_on_day')->nullable();
            $table->unsignedInteger('last_produced_day')->nullable();
            $table->unsignedInteger('purchased_on_day');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
