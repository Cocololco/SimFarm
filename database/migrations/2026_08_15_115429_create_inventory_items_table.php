<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('item_key');
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique(['farm_id', 'item_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
