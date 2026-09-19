<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabAnalyte extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_analytes';

    protected $fillable = [
        'Symbol',
        'Name',
        'NameRus',
        'UnitsID',
        'Unit',
        'Comment',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];
}
