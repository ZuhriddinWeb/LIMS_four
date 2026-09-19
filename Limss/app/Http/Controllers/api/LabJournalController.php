<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LabAnalyte;
use App\Models\LabMethod;
use App\Models\LabGroup;
use App\Models\LabDepartment;

class LabJournalController extends Controller
{
    /**
     * Журнал КХА: плоский реестр определений (проба × элемент) с фильтрами.
     * Без фильтра по методике — сводный реестр; с фильтром — пометодный журнал.
     * Параметры: year, month, methodId, executorGroupId, status, q (шифр).
     */
    public function journal(Request $request)
    {
        $rows = DB::table('lab_sample_determinations as d')
            ->join('lab_samples as s', 's.id', '=', 'd.SampleID')
            ->when($request->filled('year'), fn ($q) => $q->where('s.PeriodYear', (int) $request->year))
            ->when($request->filled('month'), fn ($q) => $q->where('s.PeriodMonth', (int) $request->month))
            ->when($request->filled('methodId'), fn ($q) => $q->where('d.MethodID', (int) $request->methodId))
            ->when($request->filled('executorGroupId'), fn ($q) => $q->where('d.ExecutorGroupID', (int) $request->executorGroupId))
            ->when($request->filled('status'), fn ($q) => $q->where('d.Status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->where('s.SampleCode', 'like', '%' . $request->q . '%'))
            ->select(
                'd.id', 'd.SampleID', 'd.AnalyteID', 'd.ExecutorGroupID', 'd.MethodID',
                'd.Unit', 'd.ResultValue', 'd.InTolerance', 'd.AnalystUser', 'd.ResultAt', 'd.Status',
                's.SampleCode', 's.RegisteredAt', 's.DepartmentID', 's.DocumentNumber'
            )
            ->orderByDesc('s.RegisteredAt')
            ->orderBy('s.id')
            ->limit(5000)
            ->get();

        $analytes = LabAnalyte::all()->keyBy('id');
        $methods = LabMethod::all()->keyBy('id');
        $groups = LabGroup::all()->keyBy('id');
        $deps = LabDepartment::all()->keyBy('id');

        $rows->transform(function ($r) use ($analytes, $methods, $groups, $deps) {
            $a = $analytes->get($r->AnalyteID);
            $r->AnalyteSymbol = optional($a)->Symbol;
            $r->AnalyteName = optional($a)->Name;
            $r->AnalyteNameRus = optional($a)->NameRus;
            $r->MethodName = optional($methods->get($r->MethodID))->Name;
            $r->MethodNameRus = optional($methods->get($r->MethodID))->NameRus;
            $r->ExecutorGroupName = optional($groups->get($r->ExecutorGroupID))->Name;
            $r->ExecutorGroupNameRus = optional($groups->get($r->ExecutorGroupID))->NameRus;
            $r->DepartmentName = optional($deps->get($r->DepartmentID))->Name;
            $r->DepartmentNameRus = optional($deps->get($r->DepartmentID))->NameRus;
            return $r;
        });

        return response()->json($rows);
    }
}
