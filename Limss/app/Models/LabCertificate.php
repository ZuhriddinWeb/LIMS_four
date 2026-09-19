<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class LabCertificate extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_certificates';

    protected array $auditLabelKeys = ['CertNumber'];

    protected $fillable = [
        'CertNumber',
        'ProductID',
        'Batch',
        'CertDate',
        'SampleID',
        'Quantity',
        'Status',
        'IssuedBy',
        'Comment',
        'PeriodYear',
        'PeriodMonth',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];

    public function items()
    {
        return $this->hasMany(LabCertificateItem::class, 'CertificateID');
    }
}
