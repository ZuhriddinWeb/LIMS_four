<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Журнал аудита лабораторного блока (ISO/IEC 17025 §7.5/§8.4, принцип ALCOA+).
 * Фиксирует «кто, что, когда, старое→новое» по всем изменениям сущностей лаборатории.
 * Записи журнала неизменяемы (только вставка).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('EntityType')->index();            // напр. LabSample, LabSampleDetermination
            $table->unsignedBigInteger('EntityID')->nullable()->index();
            $table->string('EntityLabel')->nullable();        // человекочитаемая метка (шифр пробы и т.п.)
            $table->string('Action')->index();                // created|updated|deleted|restored|status_changed|result_entered|result_reviewed|result_approved
            $table->longText('Changes')->nullable();          // JSON: { field: { old, new } }
            $table->unsignedBigInteger('UserID')->nullable()->index();
            $table->string('UserName')->nullable();
            $table->string('IP', 64)->nullable();
            $table->string('UserAgent')->nullable();
            $table->timestamp('CreatedAt')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_audit_log');
    }
};
