<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabStandardValue extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_standard_values';

    protected $fillable = [
        'StandardID',
        'AnalyteID',
        'CertifiedValue',
        'Uncertainty',
        'Unit',
    ];
}
