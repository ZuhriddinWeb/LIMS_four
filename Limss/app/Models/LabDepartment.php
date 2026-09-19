<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabDepartment extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_departments';

    protected $fillable = [
        'Code',
        'Name',
        'NameRus',
        'ShortName',
        'ShortNameRus',
        'Comment',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];
}
