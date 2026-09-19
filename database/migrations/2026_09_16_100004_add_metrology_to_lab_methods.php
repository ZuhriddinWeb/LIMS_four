<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Метрологические характеристики методики (ISO/IEC 17025 §7.6, §7.8.3):
 * предел обнаружения (LOD), предел количественного определения (LOQ),
 * расширенная неопределённость измерения (%), число значащих знаков.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_methods', function (Blueprint $table) {
            $table->decimal('LOD', 20, 6)->nullable()->after('StandardDoc');           // предел обнаружения
            $table->decimal('LOQ', 20, 6)->nullable()->after('LOD');                    // предел количеств. определения
            $table->decimal('Uncertainty', 10, 3)->nullable()->after('LOQ');            // расшир. неопределённость, %
            $table->unsignedTinyInteger('DecimalPlaces')->nullable()->after('Uncertainty'); // знаков после запятой
        });
    }

    public function down(): void
    {
        Schema::table('lab_methods', function (Blueprint $table) {
            $table->dropColumn(['LOD', 'LOQ', 'Uncertainty', 'DecimalPlaces']);
        });
    }
};
