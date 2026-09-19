<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Проверка прав на бэкенде (RBAC) — защита в глубину поверх auth:sanctum.
 * Использование в маршруте:
 *   ->middleware('perm:menu.lab_samples')          // действие по HTTP-методу
 *   ->middleware('perm:menu.lab_samples,update')   // явное действие
 *
 * Право = у пользователя есть роль с name == <permission> и pivot.<action> == 1
 * (view|create|update|delete). Иначе 403.
 */
class LabPermission
{
    public function handle(Request $request, Closure $next, string $permission, ?string $action = null)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $act = $action ?: match (strtoupper($request->method())) {
            'GET', 'HEAD' => 'view',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'view',
        };

        $role = $user->roles->firstWhere('name', $permission);
        $allowed = $role && (int) ($role->pivot->{$act} ?? 0) === 1;

        if (!$allowed) {
            return response()->json([
                'message' => 'Недостаточно прав для этого действия',
                'permission' => $permission,
                'action' => $act,
            ], 403);
        }

        return $next($request);
    }
}
