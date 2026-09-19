<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabProcessMeasurement extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_process_measurements';

    protected $fillable = [
        'PointID', 'AnalyteID', 'MethodID', 'MeasureDate', 'ShiftNo', 'TimeSlot',
        'MeasuredAt', 'Value', 'Unit', 'InTolerance', 'AnalystUser',
        'PeriodYear', 'PeriodMonth', 'Comment',
    ];
}
