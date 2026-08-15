<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crop_types', function (Blueprint $table) {
            // null = grows fine in any season. Otherwise one of
            // spring/summer/fall/winter, matched against Farm::currentSeason()
            // for a yield bonus.
            $table->string('season')->nullable()->after('key');
        });
    }

    public function down(): void
    {
        Schema::table('crop_types', function (Blueprint $table) {
            $table->dropColumn('season');
        });
    }
};
