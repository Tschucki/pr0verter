<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', static function (Blueprint $table) {
            $table->after('disk', function (Blueprint $table) {
                $table->boolean('public')->default(false);
            });
        });
    }

    public function down(): void
    {
        Schema::table('files', static function (Blueprint $table) {
            $table->dropColumn('public');
        });
    }
};
