<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('icon');
            $table->decimal('seed_price', 10, 2);
            $table->decimal('sell_price', 10, 2);
            $table->unsignedInteger('growth_days');
            $table->unsignedInteger('yield_amount')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_types');
    }
};
