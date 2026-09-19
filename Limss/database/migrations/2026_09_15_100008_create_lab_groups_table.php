<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Группы внутри лаборатории (lab_groups): напр. Гравитация, Цианирование
 * (ТЛ); Спектральная, Химическая (Аналитическая); вода/воздух/радиация.
 * LaboratoryID — ссылка на lab_laboratories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('LaboratoryID')->nullable()->index();
            $table->string('Code')->nullable();
            $table->string('Name');
            $table->string('NameRus')->nullable();
            $table->string('ShortName')->nullable();
            $table->string('ShortNameRus')->nullable();
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
        Schema::dropIfExists('lab_groups');
    }
};
