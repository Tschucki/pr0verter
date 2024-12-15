<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_operation_conversion_preset', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('media_operation_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('conversion_preset_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_operation_conversion_preset');
    }
};
