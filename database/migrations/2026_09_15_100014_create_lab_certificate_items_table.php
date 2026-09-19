<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Строки паспорта качества (lab_certificate_items):
 * показатель → фактическое значение, норма по ТУ, соответствие, методика.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_certificate_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('CertificateID')->index();
            $table->unsignedBigInteger('AnalyteID')->nullable()->index();
            $table->unsignedBigInteger('MethodID')->nullable();
            $table->string('ResultValue')->nullable();   // фактическое значение
            $table->string('Unit')->nullable();
            $table->string('NormText')->nullable();       // норма по ТУ (напр. "≥ 99.99")
            $table->boolean('Conforms')->nullable();      // соответствие ТУ
            $table->integer('OrderNumber')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_certificate_items');
    }
};
