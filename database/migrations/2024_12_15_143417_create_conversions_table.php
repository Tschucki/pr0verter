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
            $table->string('session_id')->unique();
            $table->foreignUuid('file_id')->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
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
            $table->text('url')->nullable();
            $table->timestamps();

            $table->foreign('session_id')
                ->references('id')
                ->on('sessions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};
