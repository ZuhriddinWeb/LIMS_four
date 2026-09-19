<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Продукция (lab_products): сплав Доре, катодное золото, полупродукты и т.д.
 * На неё оформляются паспорта качества.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_products', function (Blueprint $table) {
            $table->id();
            $table->string('Code')->nullable();
            $table->string('Name');
            $table->string('NameRus')->nullable();
            $table->string('ShortName')->nullable();
            $table->string('ShortNameRus')->nullable();
            $table->string('ProductType')->nullable(); // dore | cathode | semi
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
        Schema::dropIfExists('lab_products');
    }
};
