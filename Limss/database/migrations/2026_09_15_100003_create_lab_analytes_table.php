<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Определяемые показатели / компоненты (lab_analytes):
 * Au, Ag, Cu, Fe, As, S, CN-, NaOH, pH, влажность и т.д.
 * UnitsID — необязательная ссылка на существующий справочник units;
 * Unit — свободная строка единицы (г/т, %, г/л, мг/л) на случай отсутствия в units.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_analytes', function (Blueprint $table) {
            $table->id();
            $table->string('Symbol')->nullable(); // Au, Ag, Cu, CN...
            $table->string('Name');
            $table->string('NameRus')->nullable();
            $table->unsignedBigInteger('UnitsID')->nullable()->index();
            $table->string('Unit')->nullable();    // отображаемая единица (г/т, %, г/л)
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
        Schema::dropIfExists('lab_analytes');
    }
};
