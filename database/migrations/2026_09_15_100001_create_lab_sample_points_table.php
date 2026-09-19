<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Точки отбора проб (lab_sample_points).
 * Привязка к существующей структуре: FactoryStructureID (цех) и BlogID (участок).
 * Ссылки целочисленные без жёстких FK — как в остальной схеме приложения.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_sample_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('FactoryStructureID')->nullable()->index(); // цех
            $table->unsignedBigInteger('BlogID')->nullable()->index();              // участок
            $table->string('Code')->nullable();                                     // шифр точки
            $table->string('Name');
            $table->string('NameRus')->nullable();
            $table->string('ShortName')->nullable();
            $table->string('ShortNameRus')->nullable();
            $table->string('Environment')->nullable(); // среда: pulp/solution/solid/product
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
        Schema::dropIfExists('lab_sample_points');
    }
};
