<?php

namespace App\Support;

use App\Models\LabAuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Центральная точка записи в журнал аудита лаборатории.
 * Пользователя определяет через guard 'sanctum' (токен приходит в заголовке
 * Authorization даже на открытых маршрутах), с запасным вариантом — web/default.
 */
class LabAudit
{
    /**
     * Записать событие аудита.
     *
     * @param string      $entityType короткое имя модели (напр. 'LabSample')
     * @param int|null    $entityId
     * @param string      $action     created|updated|deleted|restored|status_changed|result_entered|result_reviewed|result_approved
     * @param array|null  $changes    ['field' => ['old' => ..., 'new' => ...]]
     * @param string|null $label      человекочитаемая метка (шифр пробы, № паспорта)
     */
    public static function record(string $entityType, ?int $entityId, string $action, ?array $changes = null, ?string $label = null): void
    {
        try {
            $user = self::resolveUser();
            $request = request();

            LabAuditLog::create([
                'EntityType' => $entityType,
                'EntityID' => $entityId,
                'EntityLabel' => $label,
                'Action' => $action,
                'Changes' => $changes && count($changes) ? $changes : null,
                'UserID' => $user->id ?? null,
                'UserName' => $user->name ?? 'system',
                'IP' => $request ? $request->ip() : null,
                'UserAgent' => $request ? substr((string) $request->userAgent(), 0, 255) : null,
                'CreatedAt' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            // Аудит не должен ломать основную операцию.
            report($e);
        }
    }

    /**
     * Текущий пользователь: сначала пробуем sanctum (bearer-токен), затем дефолтный guard.
     */
    public static function resolveUser()
    {
        try {
            if ($u = Auth::guard('sanctum')->user()) {
                return $u;
            }
        } catch (\Throwable $e) {
            // guard может быть не сконфигурирован в некоторых окружениях
        }
        return Auth::user();
    }
}
