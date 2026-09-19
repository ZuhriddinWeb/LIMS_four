<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabStandard extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_standards';

    protected array $auditLabelKeys = ['Code', 'NameRus', 'Name'];

    protected $fillable = [
        'Code',
        'Name',
        'NameRus',
        'StandardType',
        'ValidUntil',
        'Comment',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];

    public function values()
    {
        return $this->hasMany(LabStandardValue::class, 'StandardID');
    }
}
