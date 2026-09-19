<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Пробы / объекты испытания (lab_samples).
 * SampleCode — уникальный шифр, присваивается автоматически при регистрации.
 * Заказчик — лаборатория/группа, зарегистрировавшая пробу (ТЛ и т.д.).
 * Разбивка по месяцам — через PeriodYear/PeriodMonth.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_samples', function (Blueprint $table) {
            $table->id();
            $table->string('SampleCode')->unique();          // шифр
            $table->string('DocumentNumber')->nullable();    // № документа откуда получена
            $table->unsignedBigInteger('DepartmentID')->nullable()->index();        // подразделение-источник
            $table->unsignedBigInteger('CustomerLaboratoryID')->nullable()->index();// лаборатория-заказчик
            $table->unsignedBigInteger('CustomerGroupID')->nullable()->index();     // группа-заказчик
            $table->unsignedBigInteger('SampleTypeID')->nullable()->index();        // тип пробы
            $table->string('PhysicalState')->nullable();     // liquid | solid
            $table->string('Category')->nullable();          // сырьё/продукция/вода/воздух/биоматериал
            $table->dateTime('RegisteredAt')->nullable();    // дата регистрации (авто)
            $table->string('RegisteredBy')->nullable();
            $table->integer('PeriodYear')->nullable()->index();
            $table->integer('PeriodMonth')->nullable()->index();
            $table->string('Status')->default('new');        // new/in_progress/tested/reject/utilized
            $table->string('StorageLocation')->nullable();
            $table->string('Quantity')->nullable();          // масса/объём
            $table->text('Comment')->nullable();
            $table->dateTime('Created')->nullable();
            $table->string('Creator')->nullable();
            $table->dateTime('Changed')->nullable();
            $table->string('Changer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_samples');
    }
};
