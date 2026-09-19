<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabSampleDetermination extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_sample_determinations';

    protected $fillable = [
        'SampleID',
        'AnalyteID',
        'ExecutorGroupID',
        'MethodID',
        'InstrumentID',
        'Unit',
        'ResultValue',
        'InTolerance',
        'AnalystUser',
        'ResultAt',
        'Status',
        'ResultStatus',
        'ReviewedBy',
        'ReviewedAt',
        'ApprovedBy',
        'ApprovedAt',
        'Comment',
    ];

    public function sample()
    {
        return $this->belongsTo(LabSample::class, 'SampleID');
    }

    public function analyte()
    {
        return $this->belongsTo(LabAnalyte::class, 'AnalyteID');
    }
}
