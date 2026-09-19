<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * История оборудования (lab_instrument_events): ремонты, обслуживание (ТО),
 * калибровка/поверка, замена компонентов, примечания.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_instrument_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('InstrumentID')->index();
            $table->string('EventType')->nullable(); // repair|maintenance|calibration|verification|replacement|note
            $table->date('EventDate')->nullable();
            $table->text('Description')->nullable();
            $table->string('PerformedBy')->nullable();
            $table->date('NextDate')->nullable();     // следующая дата (для калибровки/ТО)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_instrument_events');
    }
};
