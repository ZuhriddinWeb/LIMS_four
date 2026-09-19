<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Паспорта качества (lab_certificates).
 * CertNumber — уникальный номер (авто или вручную). Может ссылаться на пробу,
 * из результатов которой заполняются показатели.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('CertNumber')->unique();
            $table->unsignedBigInteger('ProductID')->nullable()->index();
            $table->string('Batch')->nullable();              // партия / серия
            $table->date('CertDate')->nullable();
            $table->unsignedBigInteger('SampleID')->nullable()->index(); // связанная проба
            $table->string('Quantity')->nullable();           // масса / количество
            $table->string('Status')->default('draft');       // draft | approved
            $table->string('IssuedBy')->nullable();
            $table->text('Comment')->nullable();
            $table->integer('PeriodYear')->nullable()->index();
            $table->integer('PeriodMonth')->nullable()->index();
            $table->dateTime('Created')->nullable();
            $table->string('Creator')->nullable();
            $table->dateTime('Changed')->nullable();
            $table->string('Changer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_certificates');
    }
};
