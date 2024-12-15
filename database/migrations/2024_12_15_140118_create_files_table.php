<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ip_hash')->unique();
            $table->string('filename');
            $table->string('mime_type');
            $table->string('extension');
            $table->bigInteger('size');
            $table->string('disk');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
