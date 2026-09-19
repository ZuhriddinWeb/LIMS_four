<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LabQcMeasurement;
use App\Models\LabStandard;
use App\Models\LabStandardValue;
use App\Models\LabAnalyte;
use App\Models\LabMethod;
use Carbon\Carbon;

class LabQcController extends Controller
{
    /**
     * Регистрация контрольного измерения СО.
     * Аттестованное значение и допуск подставляются из СО, если не заданы.
     * Отклонение и «в допуске» рассчитываются автоматически.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'StandardID' => 'nullable|integer',
            'AnalyteID' => 'nullable|integer',
            'MethodID' => 'nullable|integer',
            'InstrumentID' => 'nullable|integer',
            'ExecutorGroupID' => 'nullable|integer',
            'MeasuredValue' => 'required|numeric',
            'CertifiedValue' => 'nullable|numeric',
            'Tolerance' => 'nullable|numeric',
            'MeasuredAt' => 'nullable|date',
            'Comment' => 'nullable|string|max:1000',
        ]);

        $certified = $data['CertifiedValue'] ?? null;
        $tolerance = $data['Tolerance'] ?? null;

        // Подстановка эталонных значений из СО.
        if (($certified === null || $tolerance === null) && !empty($data['StandardID']) && !empty($data['AnalyteID'])) {
            $sv = LabStandardValue::where('StandardID', $data['StandardID'])->where('AnalyteID', $data['AnalyteID'])->first();
            if ($sv) {
                if ($certified === null) {
                    $certified = $sv->CertifiedValue;
                }
                if ($tolerance === null) {
                    $tolerance = $sv->Uncertainty;
                }
            }
        }

        $measured = (float) $data['MeasuredValue'];
        $deviation = $certified !== null ? $measured - (float) $certified : null;
        $inTolerance = ($deviation !== null && $tolerance !== null) ? (abs($deviation) <= (float) $tolerance) : null;

        $at = !empty($data['MeasuredAt']) ? Carbon::parse($data['MeasuredAt']) : Carbon::now();

        $m = LabQcMeasurement::create([
            'StandardID' => $data['StandardID'] ?? null,
            'AnalyteID' => $data['AnalyteID'] ?? null,
            'MethodID' => $data['MethodID'] ?? null,
            'InstrumentID' => $data['InstrumentID'] ?? null,
            'ExecutorGroupID' => $data['ExecutorGroupID'] ?? null,
            'MeasuredValue' => $measured,
            'CertifiedValue' => $certified,
            'Deviation' => $deviation,
            'Tolerance' => $tolerance,
            'InTolerance' => $inTolerance,
            'AnalystUser' => optional($request->user())->name,
            'MeasuredAt' => $at,
            'PeriodYear' => (int) $at->format('Y'),
            'PeriodMonth' => (int) $at->format('n'),
            'Comment' => $data['Comment'] ?? null,
        ]);

        return response()->json(['status' => 200, 'message' => "Nazorat o'lchovi saqlandi", 'unit' => $m]);
    }

    public function index(Request $request)
    {
        $q = LabQcMeasurement::query()->orderByDesc('MeasuredAt')->orderByDesc('id');
        foreach (['StandardID' => 'StandardID', 'AnalyteID' => 'AnalyteID', 'MethodID' => 'MethodID'] as $param => $col) {
            if ($request->filled($param)) {
                $q->where($col, (int) $request->$param);
            }
        }
        if ($request->filled('year')) {
            $q->where('PeriodYear', (int) $request->year);
        }
        if ($request->filled('month')) {
            $q->where('PeriodMonth', (int) $request->month);
        }
        $rows = $q->limit(5000)->get();

        $standards = LabStandard::all()->keyBy('id');
        $analytes = LabAnalyte::all()->keyBy('id');
        $methods = LabMethod::all()->keyBy('id');

        $rows->transform(function ($m) use ($standards, $analytes, $methods) {
            $m->StandardName = optional($standards->get($m->StandardID))->Name;
            $m->StandardNameRus = optional($standards->get($m->StandardID))->NameRus;
            $a = $analytes->get($m->AnalyteID);
            $m->AnalyteSymbol = optional($a)->Symbol;
            $m->AnalyteName = optional($a)->Name;
            $m->AnalyteNameRus = optional($a)->NameRus;
            $m->MethodName = optional($methods->get($m->MethodID))->Name;
            $m->MethodNameRus = optional($methods->get($m->MethodID))->NameRus;
            return $m;
        });

        return response()->json($rows);
    }

    /**
     * Данные для карты Шухарта: точки во времени + центральная линия и границы.
     */
    public function chart(Request $request)
    {
        $request->validate([
            'StandardID' => 'required|integer',
            'AnalyteID' => 'required|integer',
        ]);

        $q = LabQcMeasurement::where('StandardID', $request->StandardID)->where('AnalyteID', $request->AnalyteID);
        if ($request->filled('MethodID')) {
            $q->where('MethodID', (int) $request->MethodID);
        }
        $rows = $q->orderBy('MeasuredAt')->orderBy('id')->get();

        $points = $rows->map(fn ($m) => [
            'x' => optional($m->MeasuredAt)->format('Y-m-d H:i') ?? '',
            'y' => (float) $m->MeasuredValue,
            'inTolerance' => $m->InTolerance,
        ]);

        $vals = $rows->pluck('MeasuredValue')->map(fn ($v) => (float) $v);
        $n = $vals->count();
        $mean = $n ? $vals->avg() : null;
        $std = null;
        if ($n > 1) {
            $variance = $vals->reduce(fn ($c, $v) => $c + ($v - $mean) ** 2, 0) / ($n - 1);
            $std = sqrt($variance);
        }

        $sv = LabStandardValue::where('StandardID', $request->StandardID)->where('AnalyteID', $request->AnalyteID)->first();
        $center = $sv && $sv->CertifiedValue !== null ? (float) $sv->CertifiedValue : $mean;

        return response()->json([
            'points' => $points,
            'center' => $center,
            'mean' => $mean,
            'std' => $std,
            'ucl' => $std !== null ? $center + 3 * $std : null,
            'lcl' => $std !== null ? $center - 3 * $std : null,
            'uwl' => $std !== null ? $center + 2 * $std : null,
            'lwl' => $std !== null ? $center - 2 * $std : null,
            'certified' => $sv ? (float) $sv->CertifiedValue : null,
            'tolerance' => $sv ? (float) $sv->Uncertainty : null,
        ]);
    }

    public function destroy($id)
    {
        LabQcMeasurement::where('id', $id)->delete();
        return response()->json(['status' => 200, 'message' => 'Deleted']);
    }
}
