<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversions', static function (Blueprint $table) {
            $table->string('subtitle_mode')->default('none')->after('audio_only');
            $table->string('subtitle_status')->nullable()->after('subtitle_mode');
            $table->string('subtitle_path')->nullable()->after('subtitle_status');
        });
    }

    public function down(): void
    {
        Schema::table('conversions', static function (Blueprint $table) {
            $table->dropColumn(['subtitle_mode', 'subtitle_status', 'subtitle_path']);
        });
    }
};
