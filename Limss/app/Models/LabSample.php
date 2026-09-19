<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabSample extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_samples';

    protected array $auditLabelKeys = ['SampleCode'];

    protected $fillable = [
        'SampleCode',
        'DocumentNumber',
        'DepartmentID',
        'CustomerLaboratoryID',
        'CustomerGroupID',
        'SampleTypeID',
        'PhysicalState',
        'Category',
        'RegisteredAt',
        'RegisteredBy',
        'PeriodYear',
        'PeriodMonth',
        'Status',
        'StorageLocation',
        'StorageLocationID',
        'Quantity',
        'Comment',
        'Batch',
        'Description',
        'ChemicalComposition',
        'StorageConditions',
        'TransportConditions',
        'StorageUntil',
        'DisposalAct',
        'DisposalDate',
        'DisposalBy',
        'DisposalReason',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];

    public function determinations()
    {
        return $this->hasMany(LabSampleDetermination::class, 'SampleID');
    }

    public function files()
    {
        return $this->hasMany(LabSampleFile::class, 'SampleID');
    }

    public function storageLocation()
    {
        return $this->belongsTo(LabStorageLocation::class, 'StorageLocationID');
    }
}
