<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabInstrumentFile extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_instrument_files';

    protected $fillable = [
        'InstrumentID',
        'FileType',
        'OriginalName',
        'Path',
        'Size',
        'UploadedBy',
    ];
}
