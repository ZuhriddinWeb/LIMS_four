<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabSampleFile extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_sample_files';

    protected $fillable = [
        'SampleID',
        'FileType',
        'OriginalName',
        'Path',
        'Size',
        'UploadedBy',
    ];
}
