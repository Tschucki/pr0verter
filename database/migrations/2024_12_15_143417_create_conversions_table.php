<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversions', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('job_batch_id');
            $table->foreignUuid('conversion_presets')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('file_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('status')->default('pending');
            $table->boolean('downloadable')->default(false);
            $table->timestamps();

            $table->foreign('job_batch_id')->cascadeOnUpdate()->cascadeOnDelete()->references('id')->on('job_batches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};
