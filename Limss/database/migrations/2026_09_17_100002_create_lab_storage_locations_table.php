<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Справочник мест хранения проб (ТЗ 2.11) — динамический словарь:
 * холодильник, сейф, полка №..., шкаф и т.д.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_storage_locations', function (Blueprint $table) {
            $table->id();
            $table->string('Code')->nullable();
            $table->string('Name');                         // узб.
            $table->string('NameRus')->nullable();          // рус.
            $table->string('LocationType')->nullable();     // fridge|safe|shelf|cabinet|room
            $table->string('Comment')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_storage_locations');
    }
};
