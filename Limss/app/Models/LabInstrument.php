<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabInstrument extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_instruments';

    protected array $auditLabelKeys = ['Code', 'NameRus', 'Name'];

    protected $fillable = [
        'LaboratoryID',
        'Code',
        'Name',
        'NameRus',
        'Model',
        'SerialNumber',
        'Location',
        'Supplier',
        'CommissionDate',
        'Status',
        'InventoryNo',
        'VerificationDate',
        'VerificationDue',
        'NextMaintenance',
        'Comment',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];

    public function events()
    {
        return $this->hasMany(LabInstrumentEvent::class, 'InstrumentID');
    }

    public function files()
    {
        return $this->hasMany(LabInstrumentFile::class, 'InstrumentID');
    }
}
