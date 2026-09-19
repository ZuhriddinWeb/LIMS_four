<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabQcMeasurement extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_qc_measurements';

    protected $fillable = [
        'StandardID',
        'AnalyteID',
        'MethodID',
        'InstrumentID',
        'ExecutorGroupID',
        'MeasuredValue',
        'CertifiedValue',
        'Deviation',
        'Tolerance',
        'InTolerance',
        'AnalystUser',
        'MeasuredAt',
        'PeriodYear',
        'PeriodMonth',
        'Comment',
    ];
}
