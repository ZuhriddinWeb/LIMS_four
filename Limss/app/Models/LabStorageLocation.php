<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabStorageLocation extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_storage_locations';

    protected array $auditLabelKeys = ['Code', 'NameRus', 'Name'];

    protected $fillable = [
        'Code',
        'Name',
        'NameRus',
        'LocationType',
        'Comment',
    ];
}
