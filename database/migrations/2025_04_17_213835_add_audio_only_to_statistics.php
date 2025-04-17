<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statistics', static function (Blueprint $table) {
            $table->boolean('audio_only')->nullable()->default(false)->after('audio');
        });
    }

    public function down(): void
    {
        Schema::table('statistics', static function (Blueprint $table) {
            $table->dropColumn('audio_only');
        });
    }
};
