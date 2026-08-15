<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('icon');
            $table->decimal('buy_price', 10, 2);
            $table->decimal('feed_cost', 10, 2);
            $table->decimal('sell_price', 10, 2);
            $table->string('produce_key');
            $table->string('produce_name');
            $table->string('produce_icon');
            $table->decimal('produce_sell_price', 10, 2);
            $table->unsignedInteger('produce_interval_days')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_types');
    }
};
