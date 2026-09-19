<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabProduct extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_products';

    protected $fillable = [
        'Code',
        'Name',
        'NameRus',
        'ShortName',
        'ShortNameRus',
        'ProductType',
        'Comment',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];
}
