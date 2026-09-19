<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Расширение объекта испытания (пробы) по ТЗ СевРУ, раздел 2:
 *  2.3 партия/серия; 2.4 описание + химический состав; 2.7 условия хранения/транспортировки;
 *  2.11 место хранения (ссылка на справочник); 2.12 срок хранения;
 *  2.14 оформление утилизации (акт, дата, кто, причина).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_samples', function (Blueprint $table) {
            $table->string('Batch')->nullable()->after('DocumentNumber');                 // 2.3 партия/серия
            $table->text('Description')->nullable()->after('Category');                    // 2.4 физ. описание
            $table->text('ChemicalComposition')->nullable()->after('Description');         // 2.4 химический состав
            $table->string('StorageConditions')->nullable()->after('StorageLocation');     // 2.7 условия хранения
            $table->string('TransportConditions')->nullable()->after('StorageConditions'); // 2.7 условия транспортировки
            $table->unsignedBigInteger('StorageLocationID')->nullable()->index()->after('TransportConditions'); // 2.11
            $table->date('StorageUntil')->nullable()->after('StorageLocationID');          // 2.12 срок хранения
            // 2.14 утилизация
            $table->string('DisposalAct')->nullable()->after('StorageUntil');
            $table->date('DisposalDate')->nullable()->after('DisposalAct');
            $table->string('DisposalBy')->nullable()->after('DisposalDate');
            $table->string('DisposalReason')->nullable()->after('DisposalBy');
        });
    }

    public function down(): void
    {
        Schema::table('lab_samples', function (Blueprint $table) {
            $table->dropColumn([
                'Batch', 'Description', 'ChemicalComposition', 'StorageConditions',
                'TransportConditions', 'StorageLocationID', 'StorageUntil',
                'DisposalAct', 'DisposalDate', 'DisposalBy', 'DisposalReason',
            ]);
        });
    }
};
