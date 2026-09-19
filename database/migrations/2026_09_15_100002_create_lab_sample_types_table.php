<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Типы проб (lab_sample_types): пульпа, продуктивный/маточный раствор, кек,
 * уголь, катодный осадок, сплав Доре, готовая продукция и т.д.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_sample_types', function (Blueprint $table) {
            $table->id();
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
        Schema::dropIfExists('lab_sample_types');
    }
};
