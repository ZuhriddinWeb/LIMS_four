<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabCertificateItem extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_certificate_items';

    protected $fillable = [
        'CertificateID',
        'AnalyteID',
        'MethodID',
        'ResultValue',
        'Unit',
        'NormText',
        'Conforms',
        'OrderNumber',
    ];
}
