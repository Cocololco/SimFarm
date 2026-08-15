<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machinery_types', function (Blueprint $table) {
            $table->unsignedInteger('required_level')->default(1)->after('key');
        });
    }

    public function down(): void
    {
        Schema::table('machinery_types', function (Blueprint $table) {
            $table->dropColumn('required_level');
        });
    }
};
