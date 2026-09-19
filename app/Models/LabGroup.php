<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabGroup extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_groups';

    protected $fillable = [
        'LaboratoryID',
        'Code',
        'Name',
        'NameRus',
        'ShortName',
        'ShortNameRus',
        'Comment',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];

    public function laboratory()
    {
        return $this->belongsTo(LabLaboratory::class, 'LaboratoryID');
    }
}
