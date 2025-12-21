<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statistics', static function (Blueprint $table) {
            $table->dropColumn('keep_resolution');
        });
    }

    public function down(): void
    {
        Schema::table('statistics', static function (Blueprint $table) {
            $table->boolean('keep_resolution')->default(false)->after('status');
        });
    }
};
