<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Связка методика × показатель (lab_method_analytes): какие показатели
 * определяет методика, в каком диапазоне, с какой единицей и погрешностью.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_method_analytes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('MethodID')->index();
            $table->unsignedBigInteger('AnalyteID')->index();
            $table->string('Unit')->nullable();
            $table->double('RangeMin')->nullable();
            $table->double('RangeMax')->nullable();
            $table->string('Tolerance')->nullable(); // погрешность / допуск
            $table->text('Comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_method_analytes');
    }
};
