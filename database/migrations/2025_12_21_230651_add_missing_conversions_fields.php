<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversions', static function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('max_size');
        });
    }

    public function down(): void
    {
        Schema::table('conversions', static function (Blueprint $table) {
            $table->dropColumn(['metadata']);
        });
    }
};
