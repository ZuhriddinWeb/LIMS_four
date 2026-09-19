<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Методики анализа (lab_methods): пробирный, атомно-абсорбционный (ААС),
 * титриметрический, гравиметрический, потенциометрический, гран.состав и т.д.
 * StandardDoc — нормативный документ (ГОСТ / МВИ / СТ предприятия).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_methods', function (Blueprint $table) {
            $table->id();
            $table->string('Code')->nullable();
            $table->string('Name');
            $table->string('NameRus')->nullable();
            $table->string('ShortName')->nullable();
            $table->string('ShortNameRus')->nullable();
            $table->string('StandardDoc')->nullable(); // ГОСТ / МВИ
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
        Schema::dropIfExists('lab_methods');
    }
};
