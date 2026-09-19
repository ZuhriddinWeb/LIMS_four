<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Приборы лаборатории (lab_instruments): весы пробирные, ААС-спектрометр,
 * титратор, муфельная печь и т.д. С данными о поверке.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_instruments', function (Blueprint $table) {
            $table->id();
            $table->string('Code')->nullable();
            $table->string('Name');
            $table->string('NameRus')->nullable();
            $table->string('InventoryNo')->nullable();       // инвентарный номер
            $table->date('VerificationDate')->nullable();    // дата поверки
            $table->date('VerificationDue')->nullable();     // поверка действительна до
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
        Schema::dropIfExists('lab_instruments');
    }
};
