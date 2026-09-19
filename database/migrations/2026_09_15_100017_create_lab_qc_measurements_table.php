<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Контрольные измерения ВЛК (lab_qc_measurements):
 * измерение стандартного образца — фактическое значение против аттестованного,
 * отклонение, допуск, «в допуске». Основа для карт Шухарта.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_qc_measurements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('StandardID')->nullable()->index();
            $table->unsignedBigInteger('AnalyteID')->nullable()->index();
            $table->unsignedBigInteger('MethodID')->nullable();
            $table->unsignedBigInteger('InstrumentID')->nullable();
            $table->unsignedBigInteger('ExecutorGroupID')->nullable();
            $table->double('MeasuredValue')->nullable();
            $table->double('CertifiedValue')->nullable();
            $table->double('Deviation')->nullable();      // measured - certified
            $table->double('Tolerance')->nullable();      // допуск (±)
            $table->boolean('InTolerance')->nullable();
            $table->string('AnalystUser')->nullable();
            $table->dateTime('MeasuredAt')->nullable();
            $table->integer('PeriodYear')->nullable()->index();
            $table->integer('PeriodMonth')->nullable()->index();
            $table->text('Comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_qc_measurements');
    }
};
