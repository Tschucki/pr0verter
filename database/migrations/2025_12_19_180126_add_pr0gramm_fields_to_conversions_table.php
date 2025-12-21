<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversions', static function (Blueprint $table) {
            $table->string('quality_tier')->nullable()->after('audio_quality');
            $table->dropColumn('keep_resolution');
        });
    }

    public function down(): void
    {
        Schema::table('conversions', static function (Blueprint $table) {
            $table->dropColumn([
                'quality_tier',
            ]);
            $table->boolean('keep_resolution')->default(false);
        });
    }
};
