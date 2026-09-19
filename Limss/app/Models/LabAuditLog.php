<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Запись журнала аудита. Только чтение/вставка — не редактируется и не удаляется.
 */
class LabAuditLog extends Model
{
    protected $table = 'lab_audit_log';
    public $timestamps = false;

    protected $fillable = [
        'EntityType', 'EntityID', 'EntityLabel', 'Action', 'Changes',
        'UserID', 'UserName', 'IP', 'UserAgent', 'CreatedAt',
    ];

    protected $casts = [
        'Changes' => 'array',
        'CreatedAt' => 'datetime',
    ];
}
