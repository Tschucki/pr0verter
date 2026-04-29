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
            $table->string('thumbnail_path')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('conversions', static function (Blueprint $table) {
            $table->dropColumn('thumbnail_path');
        });
    }
};
