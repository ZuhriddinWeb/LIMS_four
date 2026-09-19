<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Определения по пробе (lab_sample_determinations):
 * заказанные элементы/показатели по каждой пробе + результат.
 * ExecutorGroupID — группа-исполнитель (Спектральная/Химическая), которой
 * передана проба для определения данного элемента.
 * Результат вводится по каждому элементу отдельно; ResultAt проставляется
 * автоматически при вводе результата.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_sample_determinations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('SampleID')->index();
            $table->unsignedBigInteger('AnalyteID')->nullable()->index();   // определяемый элемент
            $table->unsignedBigInteger('ExecutorGroupID')->nullable()->index(); // группа-исполнитель
            $table->unsignedBigInteger('MethodID')->nullable()->index();
            $table->unsignedBigInteger('InstrumentID')->nullable();
            $table->string('Unit')->nullable();
            $table->string('ResultValue')->nullable();       // значение (строка: число или "<0.1")
            $table->boolean('InTolerance')->nullable();
            $table->string('AnalystUser')->nullable();
            $table->dateTime('ResultAt')->nullable();        // дата выхода результата (авто)
            $table->string('Status')->default('pending');    // pending | done
            $table->text('Comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_sample_determinations');
    }
};
