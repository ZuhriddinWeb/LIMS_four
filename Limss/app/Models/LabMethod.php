<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabMethod extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_methods';

    protected $fillable = [
        'Code',
        'Name',
        'NameRus',
        'ShortName',
        'ShortNameRus',
        'StandardDoc',
        'LOD',
        'LOQ',
        'Uncertainty',
        'DecimalPlaces',
        'Comment',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];

    public function analytes()
    {
        return $this->hasMany(LabMethodAnalyte::class, 'MethodID');
    }
}
