<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statistics', static function (Blueprint $table) {
            $table->comment('Each row represents a single conversion');

            $table->uuid('id')->primary();
            $table->foreignUuid('conversion_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->string('mime_type');
            $table->string('extension');
            $table->string('status');
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
            $table->bigInteger('size');
            $table->dateTime('conversion_started_at')->nullable();
            $table->dateTime('conversion_ended_at')->nullable();
            // create a new generated column for the time difference between conversion_started_at and conversion_ended_at
            $table->integer('conversion_time')->virtualAs('TIMESTAMPDIFF(SECOND, conversion_started_at, conversion_ended_at)');
            // virtualAs Column for
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistics');
    }
};
