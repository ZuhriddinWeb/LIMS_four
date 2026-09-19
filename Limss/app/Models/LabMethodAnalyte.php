<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabMethodAnalyte extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_method_analytes';

    protected $fillable = [
        'MethodID',
        'AnalyteID',
        'Unit',
        'RangeMin',
        'RangeMax',
        'Tolerance',
        'Comment',
    ];

    public function method()
    {
        return $this->belongsTo(LabMethod::class, 'MethodID');
    }

    public function analyte()
    {
        return $this->belongsTo(LabAnalyte::class, 'AnalyteID');
    }
}
