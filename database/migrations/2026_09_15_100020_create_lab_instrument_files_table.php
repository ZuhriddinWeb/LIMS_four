<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Файлы оборудования (lab_instrument_files): сертификаты калибровки,
 * акты обслуживания, инструкции по эксплуатации.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_instrument_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('InstrumentID')->index();
            $table->string('FileType')->nullable();  // certificate|act|manual|other
            $table->string('OriginalName')->nullable();
            $table->string('Path');
            $table->unsignedBigInteger('Size')->nullable();
            $table->string('UploadedBy')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_instrument_files');
    }
};
