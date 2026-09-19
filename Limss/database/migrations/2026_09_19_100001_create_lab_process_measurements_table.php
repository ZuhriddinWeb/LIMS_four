<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Технологический контроль по сменам (лабораторные анализы проб техпроцесса).
 * Лёгкая модель измерений: точка отбора × показатель × дата/смена/время × значение.
 * Это лабораторные результаты (вариант A), а не дискретные пробы с шифром —
 * хранятся компактно для сменных журналов и сводок (средние по смене/сутки).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_process_measurements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('PointID')->index();        // точка отбора (lab_sample_points)
            $table->unsignedBigInteger('AnalyteID')->index();      // показатель (lab_analytes)
            $table->unsignedBigInteger('MethodID')->nullable();
            $table->date('MeasureDate')->index();                  // дата
            $table->unsignedTinyInteger('ShiftNo')->nullable();    // смена (1..4)
            $table->string('TimeSlot', 20)->nullable();            // часовая отметка, напр. 08-00
            $table->dateTime('MeasuredAt')->nullable();
            $table->double('Value')->nullable();
            $table->string('Unit', 50)->nullable();
            $table->boolean('InTolerance')->nullable();
            $table->string('AnalystUser')->nullable();
            $table->integer('PeriodYear')->nullable()->index();
            $table->integer('PeriodMonth')->nullable()->index();
            $table->text('Comment')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['PointID', 'MeasureDate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_process_measurements');
    }
};
