<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LabStandard;
use App\Models\LabStandardValue;
use App\Models\LabAnalyte;

class LabStandardsController extends Controller
{
    public function index()
    {
        $counts = LabStandardValue::select('StandardID')->selectRaw('count(*) as c')->groupBy('StandardID')->pluck('c', 'StandardID');
        $rows = LabStandard::all()->map(function ($s) use ($counts) {
            $s->ValuesCount = (int) ($counts[$s->id] ?? 0);
            return $s;
        });
        return response()->json($rows);
    }

    public function show($id)
    {
        $s = LabStandard::find($id);
        if (!$s) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $analytes = LabAnalyte::all()->keyBy('id');
        $s->values = LabStandardValue::where('StandardID', $id)->get()->map(function ($v) use ($analytes) {
            $a = $analytes->get($v->AnalyteID);
            $v->AnalyteSymbol = optional($a)->Symbol;
            $v->AnalyteName = optional($a)->Name;
            $v->AnalyteNameRus = optional($a)->NameRus;
            return $v;
        });
        return response()->json($s);
    }

    public function register(Request $request)
    {
        $data = $this->validateData($request);
        $s = DB::transaction(function () use ($data) {
            $s = LabStandard::create($this->header($data));
            $this->saveValues($s->id, $data['values'] ?? []);
            return $s;
        });
        return response()->json(['status' => 200, 'message' => 'Standart yaratildi', 'unit' => $s]);
    }

    public function update(Request $request, $id)
    {
        $s = LabStandard::findOrFail($id);
        $data = $this->validateData($request);
        $s->update($this->header($data));
        if (array_key_exists('values', $data)) {
            $this->saveValues($s->id, $data['values']);
        }
        return response()->json(['status' => 200, 'message' => 'Yangilandi', 'unit' => $s]);
    }

    public function destroy($id)
    {
        LabStandardValue::where('StandardID', $id)->delete();
        LabStandard::where('id', $id)->delete();
        return response()->json(['status' => 200, 'message' => 'Deleted']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'Code' => 'nullable|string|max:255',
            'Name' => 'required|string|max:255',
            'NameRus' => 'nullable|string|max:255',
            'StandardType' => 'nullable|string|max:50',
            'ValidUntil' => 'nullable|date',
            'Comment' => 'nullable|string|max:1000',
            'values' => 'array',
            'values.*.AnalyteID' => 'nullable|integer',
            'values.*.CertifiedValue' => 'nullable|numeric',
            'values.*.Uncertainty' => 'nullable|numeric',
            'values.*.Unit' => 'nullable|string|max:50',
        ]);
    }

    private function header(array $data): array
    {
        return [
            'Code' => $data['Code'] ?? null,
            'Name' => $data['Name'],
            'NameRus' => $data['NameRus'] ?? null,
            'StandardType' => $data['StandardType'] ?? null,
            'ValidUntil' => $data['ValidUntil'] ?? null,
            'Comment' => $data['Comment'] ?? null,
        ];
    }

    private function saveValues($standardId, array $values): void
    {
        LabStandardValue::where('StandardID', $standardId)->delete();
        foreach ($values as $v) {
            if (empty($v['AnalyteID'])) {
                continue;
            }
            LabStandardValue::create([
                'StandardID' => $standardId,
                'AnalyteID' => $v['AnalyteID'],
                'CertifiedValue' => $v['CertifiedValue'] ?? null,
                'Uncertainty' => $v['Uncertainty'] ?? null,
                'Unit' => $v['Unit'] ?? null,
            ]);
        }
    }

    /**
     * Аттестованные значения СО (для формы контроля — подстановка эталона).
     */
    public function values($id)
    {
        $analytes = LabAnalyte::all()->keyBy('id');
        $vals = LabStandardValue::where('StandardID', $id)->get()->map(function ($v) use ($analytes) {
            $a = $analytes->get($v->AnalyteID);
            $v->AnalyteSymbol = optional($a)->Symbol;
            $v->AnalyteName = optional($a)->Name;
            $v->AnalyteNameRus = optional($a)->NameRus;
            return $v;
        });
        return response()->json($vals);
    }
}
