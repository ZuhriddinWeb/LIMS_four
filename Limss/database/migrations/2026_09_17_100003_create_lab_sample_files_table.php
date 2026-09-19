<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Сопроводительные документы пробы (ТЗ 2.8): накладные, акты приёмки и т.п.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_sample_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('SampleID')->index();
            $table->string('FileType')->nullable();   // waybill|acceptance_act|other
            $table->string('OriginalName')->nullable();
            $table->string('Path');
            $table->unsignedBigInteger('Size')->nullable();
            $table->string('UploadedBy')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_sample_files');
    }
};
