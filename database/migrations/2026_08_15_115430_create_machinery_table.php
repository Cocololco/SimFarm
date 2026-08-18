<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machinery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('machinery_type_id')->constrained();
            $table->unsignedInteger('purchased_on_day');
            $table->timestamps();

            $table->unique(['farm_id', 'machinery_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machinery');
    }
};
