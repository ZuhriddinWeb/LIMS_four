<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\LabAuditLog;
use Illuminate\Http\Request;

class LabAuditController extends Controller
{
    /**
     * Журнал аудита с фильтрами: сущность, действие, пользователь, период, поиск по метке.
     * Отдаёт последние записи (по умолчанию 500), новые сверху.
     */
    public function index(Request $request)
    {
        $q = LabAuditLog::query()->orderByDesc('id');

        if ($request->filled('entity_type')) {
            $q->where('EntityType', $request->entity_type);
        }
        if ($request->filled('entity_id')) {
            $q->where('EntityID', (int) $request->entity_id);
        }
        if ($request->filled('action')) {
            $q->where('Action', $request->action);
        }
        if ($request->filled('user')) {
            $q->where('UserName', 'like', '%' . $request->user . '%');
        }
        if ($request->filled('from')) {
            $q->whereDate('CreatedAt', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate('CreatedAt', '<=', $request->to);
        }
        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $q->where(function ($w) use ($term) {
                $w->where('EntityLabel', 'like', $term)
                    ->orWhere('EntityType', 'like', $term);
            });
        }

        $limit = min((int) ($request->limit ?? 500), 2000);
        $rows = $q->limit($limit)->get();

        return response()->json($rows);
    }

    /**
     * История одной сущности (для «истории изменений» в карточке).
     */
    public function forEntity(Request $request, $entityType, $entityId)
    {
        $rows = LabAuditLog::query()
            ->where('EntityType', $entityType)
            ->where('EntityID', (int) $entityId)
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return response()->json($rows);
    }

    /**
     * Справочник типов сущностей и действий (для выпадающих фильтров).
     */
    public function meta()
    {
        return response()->json([
            'entityTypes' => LabAuditLog::query()->select('EntityType')->distinct()->orderBy('EntityType')->pluck('EntityType'),
            'actions' => LabAuditLog::query()->select('Action')->distinct()->orderBy('Action')->pluck('Action'),
        ]);
    }
}
