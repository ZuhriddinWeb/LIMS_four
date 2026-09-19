<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Аттестованные значения стандартного образца (lab_standard_values):
 * СО × показатель → аттестованное значение + погрешность.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_standard_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('StandardID')->index();
            $table->unsignedBigInteger('AnalyteID')->nullable()->index();
            $table->double('CertifiedValue')->nullable();
            $table->double('Uncertainty')->nullable();  // погрешность / допуск
            $table->string('Unit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_standard_values');
    }
};
