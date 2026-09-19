<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabSamplePoint extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_sample_points';

    protected $fillable = [
        'FactoryStructureID',
        'BlogID',
        'Code',
        'Name',
        'NameRus',
        'ShortName',
        'ShortNameRus',
        'Environment',
        'Comment',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];
}
