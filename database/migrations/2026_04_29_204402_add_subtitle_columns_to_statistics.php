<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statistics', static function (Blueprint $table): void {
            $table->string('subtitle_mode')->nullable()->after('audio_only');
            $table->string('subtitle_status')->nullable()->after('subtitle_mode');
        });
    }

    public function down(): void
    {
        Schema::table('statistics', static function (Blueprint $table): void {
            $table->dropColumn(['subtitle_mode', 'subtitle_status']);
        });
    }
};
