<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machinery_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('icon');
            $table->string('description');
            $table->decimal('price', 10, 2);
            $table->string('effect_type'); // yield_boost | growth_speed | feed_discount
            $table->decimal('effect_value', 5, 2); // e.g. 0.20 = +20%
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machinery_types');
    }
};
