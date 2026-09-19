<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Двухступенчатое утверждение результатов + электронная подпись (ISO/IEC 17025 §7.8):
 * ввод (аналитик) → проверка (руководитель группы) → утверждение (руководитель).
 * После утверждения результат блокируется от изменения (нужен обоснованный «reopen»).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_sample_determinations', function (Blueprint $table) {
            $table->string('ResultStatus')->nullable()->index()->after('Status'); // entered | reviewed | approved
            $table->string('ReviewedBy')->nullable()->after('ResultStatus');
            $table->dateTime('ReviewedAt')->nullable()->after('ReviewedBy');
            $table->string('ApprovedBy')->nullable()->after('ReviewedAt');
            $table->dateTime('ApprovedAt')->nullable()->after('ApprovedBy');
        });

        // Бэкофилл: уже введённые результаты помечаем как 'entered'.
        DB::table('lab_sample_determinations')->where('Status', 'done')->whereNull('ResultStatus')->update(['ResultStatus' => 'entered']);
    }

    public function down(): void
    {
        Schema::table('lab_sample_determinations', function (Blueprint $table) {
            $table->dropColumn(['ResultStatus', 'ReviewedBy', 'ReviewedAt', 'ApprovedBy', 'ApprovedAt']);
        });
    }
};
