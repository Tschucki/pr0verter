<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_operations', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('class');
            $table->string('category')->comment('Can be something like "video", "audio", "image" etc.');
            $table->unsignedInteger('priority')->default(0)->comment('The higher the number, the higher the priority.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_operations');
    }
};
