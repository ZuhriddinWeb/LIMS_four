<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabInstrumentEvent extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_instrument_events';

    protected $fillable = [
        'InstrumentID',
        'EventType',
        'EventDate',
        'Description',
        'PerformedBy',
        'NextDate',
    ];
}
