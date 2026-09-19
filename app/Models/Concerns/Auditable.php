<?php

namespace App\Models\Concerns;

use App\Support\LabAudit;

/**
 * Трейт для моделей лабораторного блока: автоматически пишет в журнал аудита
 * события created / updated / deleted / restored с разницей значений полей.
 *
 * Модель может задать:
 *   protected array $auditLabelKeys = ['SampleCode'];   // чем подписывать запись
 *   protected array $auditExclude   = ['SomeNoisyField'];
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            LabAudit::record($model->auditEntityType(), $model->getKey(), 'created', $model->auditCreatedChanges(), $model->auditLabel());
        });

        static::updated(function ($model) {
            $changes = $model->auditDiff();
            if (empty($changes)) {
                return; // нечего логировать (например, изменились только timestamps)
            }
            LabAudit::record($model->auditEntityType(), $model->getKey(), 'updated', $changes, $model->auditLabel());
        });

        static::deleted(function ($model) {
            $soft = method_exists($model, 'isForceDeleting') && !$model->isForceDeleting();
            LabAudit::record($model->auditEntityType(), $model->getKey(), $soft ? 'deleted' : 'force_deleted', null, $model->auditLabel());
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                LabAudit::record($model->auditEntityType(), $model->getKey(), 'restored', null, $model->auditLabel());
            });
        }
    }

    protected function auditEntityType(): string
    {
        return class_basename($this);
    }

    protected function auditExcludedFields(): array
    {
        $base = ['created_at', 'updated_at', 'deleted_at', 'Created', 'Changed', 'Creator', 'Changer'];
        return array_merge($base, property_exists($this, 'auditExclude') ? $this->auditExclude : []);
    }

    /**
     * Метка записи: первый непустой из auditLabelKeys, иначе — из типовых ключей.
     */
    public function auditLabel(): ?string
    {
        $keys = property_exists($this, 'auditLabelKeys') && is_array($this->auditLabelKeys)
            ? $this->auditLabelKeys
            : ['SampleCode', 'CertificateNumber', 'Code', 'NameRus', 'Name', 'Symbol'];
        foreach ($keys as $k) {
            $v = $this->getAttribute($k);
            if (!empty($v)) {
                return (string) $v;
            }
        }
        return null;
    }

    /**
     * Разница изменённых полей: ['field' => ['old' => ..., 'new' => ...]].
     */
    protected function auditDiff(): array
    {
        $excluded = $this->auditExcludedFields();
        $changes = [];
        foreach ($this->getChanges() as $field => $new) {
            if (in_array($field, $excluded, true)) {
                continue;
            }
            $changes[$field] = ['old' => $this->getOriginal($field), 'new' => $new];
        }
        return $changes;
    }

    /**
     * Значимые поля при создании (без служебных), для читаемого лога.
     */
    protected function auditCreatedChanges(): array
    {
        $excluded = $this->auditExcludedFields();
        $out = [];
        foreach ($this->getAttributes() as $field => $val) {
            if (in_array($field, $excluded, true) || $field === $this->getKeyName()) {
                continue;
            }
            if ($val !== null && $val !== '') {
                $out[$field] = ['old' => null, 'new' => $val];
            }
        }
        return $out;
    }
}
