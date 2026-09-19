<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Расширение lab_instruments до полноценной карточки оборудования (по ТЗ):
 * принадлежность к лаборатории, модель/серийный/поставщик/местоположение,
 * дата ввода, статус, следующее ТО.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_instruments', function (Blueprint $table) {
            $table->unsignedBigInteger('LaboratoryID')->nullable()->index()->after('id');
            $table->string('Model')->nullable()->after('NameRus');
            $table->string('SerialNumber')->nullable()->after('Model');
            $table->string('Location')->nullable()->after('SerialNumber');
            $table->string('Supplier')->nullable()->after('Location');
            $table->date('CommissionDate')->nullable()->after('Supplier');       // дата ввода в эксплуатацию
            $table->string('Status')->default('working')->after('CommissionDate'); // working|maintenance|out_of_service|awaiting_calibration
            $table->date('NextMaintenance')->nullable()->after('VerificationDue'); // следующее ТО
        });
    }

    public function down(): void
    {
        Schema::table('lab_instruments', function (Blueprint $table) {
            $table->dropColumn([
                'LaboratoryID', 'Model', 'SerialNumber', 'Location', 'Supplier',
                'CommissionDate', 'Status', 'NextMaintenance',
            ]);
        });
    }
};
