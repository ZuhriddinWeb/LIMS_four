<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Мягкое удаление (deleted_at) для таблиц лабораторного блока.
 * Результаты и записи никогда не удаляются физически — только помечаются,
 * что требуется для целостности данных и прослеживаемости (ISO/IEC 17025).
 */
return new class extends Migration
{
    private array $tables = [
        'lab_samples',
        'lab_sample_determinations',
        'lab_certificates',
        'lab_certificate_items',
        'lab_qc_measurements',
        'lab_standards',
        'lab_standard_values',
        'lab_instruments',
        'lab_instrument_events',
        'lab_instrument_files',
        'lab_laboratories',
        'lab_groups',
        'lab_analytes',
        'lab_methods',
        'lab_method_analytes',
        'lab_products',
        'lab_departments',
        'lab_sample_points',
        'lab_sample_types',
    ];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            if (Schema::hasTable($t) && !Schema::hasColumn($t, 'deleted_at')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'deleted_at')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
