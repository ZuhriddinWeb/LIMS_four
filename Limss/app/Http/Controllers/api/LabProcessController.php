<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\LabProcessMeasurement;
use App\Models\LabSamplePoint;
use App\Models\LabAnalyte;
use App\Support\LabAudit;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Технологический контроль по сменам (вариант A): лабораторные измерения проб
 * техпроцесса как результаты, представленные сменным журналом.
 */
class LabProcessController extends Controller
{
    /** Измерения за день по точке — для сетки журнала. */
    public function index(Request $request)
    {
        $data = $request->validate([
            'PointID' => 'nullable|integer',
            'MeasureDate' => 'nullable|date',
        ]);

        $q = LabProcessMeasurement::query();
        if (!empty($data['PointID'])) $q->where('PointID', $data['PointID']);
        if (!empty($data['MeasureDate'])) $q->whereDate('MeasureDate', $data['MeasureDate']);
        $rows = $q->orderBy('TimeSlot')->limit(2000)->get();

        return response()->json($rows);
    }

    /** Массовое сохранение сетки журнала (точка+дата+смена, строки: показатель×время×значение). */
    public function bulkSave(Request $request)
    {
        $data = $request->validate([
            'PointID' => 'required|integer',
            'MeasureDate' => 'required|date',
            'ShiftNo' => 'nullable|integer',
            'rows' => 'required|array',
            'rows.*.AnalyteID' => 'required|integer',
            'rows.*.TimeSlot' => 'nullable|string|max:20',
            'rows.*.Value' => 'nullable',
            'rows.*.Unit' => 'nullable|string|max:50',
        ]);

        $date = Carbon::parse($data['MeasureDate']);
        $user = optional(LabAudit::resolveUser())->name;
        $saved = 0;

        foreach ($data['rows'] as $r) {
            $val = ($r['Value'] === '' || $r['Value'] === null) ? null : (float) str_replace(',', '.', (string) $r['Value']);
            $slot = $r['TimeSlot'] ?? null;
            $measuredAt = null;
            if ($slot && preg_match('/^(\d{1,2})[-:](\d{2})$/', $slot, $m)) {
                $measuredAt = $date->copy()->setTime((int) $m[1] % 24, (int) $m[2]);
            }
            $rec = LabProcessMeasurement::firstOrNew([
                'PointID' => $data['PointID'],
                'AnalyteID' => $r['AnalyteID'],
                'MeasureDate' => $date->toDateString(),
                'TimeSlot' => $slot,
            ]);
            // Пустое значение по существующей записи — удаляем (очистка ячейки).
            if ($val === null) {
                if ($rec->exists) { $rec->delete(); }
                continue;
            }
            $rec->fill([
                'ShiftNo' => $data['ShiftNo'] ?? $rec->ShiftNo,
                'MeasuredAt' => $measuredAt,
                'Value' => $val,
                'Unit' => $r['Unit'] ?? $rec->Unit,
                'AnalystUser' => $user,
                'PeriodYear' => (int) $date->format('Y'),
                'PeriodMonth' => (int) $date->format('n'),
            ]);
            $rec->save();
            $saved++;
        }

        return response()->json(['status' => 200, 'message' => 'Сохранено', 'saved' => $saved]);
    }

    /** Месячный журнал: суточные средние по показателям для точки. */
    public function journal(Request $request)
    {
        $data = $request->validate([
            'PointID' => 'required|integer',
            'year' => 'required|integer',
            'month' => 'required|integer',
        ]);
        $rows = LabProcessMeasurement::where('PointID', $data['PointID'])
            ->where('PeriodYear', $data['year'])->where('PeriodMonth', $data['month'])
            ->get();

        $analytes = LabAnalyte::all()->keyBy('id');
        // группируем по дате × показатель → среднее
        $byDay = [];
        foreach ($rows as $r) {
            $d = (string) $r->MeasureDate;
            $byDay[$d][$r->AnalyteID][] = (float) $r->Value;
        }
        $out = [];
        foreach ($byDay as $d => $byAnalyte) {
            $item = ['date' => substr($d, 0, 10), 'values' => []];
            foreach ($byAnalyte as $aid => $vals) {
                $avg = count($vals) ? round(array_sum($vals) / count($vals), 3) : null;
                $item['values'][$aid] = ['avg' => $avg, 'n' => count($vals),
                    'symbol' => optional($analytes->get($aid))->Symbol,
                    'name' => optional($analytes->get($aid))->NameRus ?: optional($analytes->get($aid))->Name];
            }
            $out[] = $item;
        }
        usort($out, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return response()->json(['days' => $out]);
    }

    public function destroy($id)
    {
        $rec = LabProcessMeasurement::findOrFail($id);
        $rec->delete();
        return response()->json(['status' => 200, 'message' => 'Deleted']);
    }
}
