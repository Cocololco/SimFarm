<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('plot_number');
            $table->foreignId('crop_type_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('planted_on_day')->nullable();
            $table->timestamps();

            $table->unique(['farm_id', 'plot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
