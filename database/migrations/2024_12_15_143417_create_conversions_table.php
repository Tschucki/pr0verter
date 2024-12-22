<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversions', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversion_presets')->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('file_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('status')->default('pending');
            $table->boolean('downloadable')->default(false);
            $table->boolean('keep_resolution')->default(false);
            $table->boolean('audio')->default(true);
            $table->boolean('auto_crop')->default(false);
            $table->boolean('watermark')->default(false);
            $table->boolean('interpolation')->default(false);
            $table->float('audio_quality');
            $table->unsignedInteger('trim_start')->nullable();
            $table->unsignedInteger('trim_end')->nullable();
            $table->unsignedInteger('max_size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};
