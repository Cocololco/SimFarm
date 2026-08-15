<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->foreignId('previous_crop_type_id')->nullable()->after('crop_type_id')
                ->constrained('crop_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->dropConstrainedForeignId('previous_crop_type_id');
        });
    }
};
