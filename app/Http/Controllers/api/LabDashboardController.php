<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LabSample;
use App\Models\LabSampleDetermination;
use App\Models\LabCertificate;
use App\Models\LabInstrument;
use App\Models\LabQcMeasurement;
use App\Models\LabDepartment;
use App\Models\LabAuditLog;
use Carbon\Carbon;

class LabDashboardController extends Controller
{
    public function summary(Request $request)
    {
        $now = Carbon::now();
        $y = (int) $now->format('Y');
        $m = (int) $now->format('n');

        // KPI
        $samplesMonth = LabSample::where('PeriodYear', $y)->where('PeriodMonth', $m)->count();
        $inProgress = LabSample::where('Status', 'in_progress')->count();
        $testedMonth = LabSample::where('PeriodYear', $y)->where('PeriodMonth', $m)->where('Status', 'tested')->count();
        $pendingDet = LabSampleDetermination::where('Status', 'pending')->count();
        $certsYear = LabCertificate::where('PeriodYear', $y)->count();
        $equipTotal = LabInstrument::count();
        $equipBlocked = LabInstrument::where('Status', 'awaiting_calibration')
            ->orWhere('Status', 'out_of_service')
            ->orWhere(function ($q) use ($now) {
                $q->whereNotNull('VerificationDue')->whereDate('VerificationDue', '<', $now->toDateString());
            })->count();
        $qcMonth = LabQcMeasurement::where('PeriodYear', $y)->where('PeriodMonth', $m)->count();
        $qcOut = LabQcMeasurement::where('PeriodYear', $y)->where('PeriodMonth', $m)->where('InTolerance', false)->count();
        $pendingApproval = LabSampleDetermination::whereIn('ResultStatus', ['entered', 'reviewed'])->count();

        // Пробы по месяцам (последние 6)
        $byMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = $now->copy()->subMonths($i);
            $byMonth[] = [
                'label' => $d->format('Y-m'),
                'count' => LabSample::where('PeriodYear', (int) $d->format('Y'))->where('PeriodMonth', (int) $d->format('n'))->count(),
            ];
        }

        // Пробы по статусам
        $statusRows = LabSample::select('Status', DB::raw('count(*) as c'))->groupBy('Status')->pluck('c', 'Status');
        $byStatus = [
            'new' => (int) ($statusRows['new'] ?? 0),
            'in_progress' => (int) ($statusRows['in_progress'] ?? 0),
            'tested' => (int) ($statusRows['tested'] ?? 0),
            'reject' => (int) ($statusRows['reject'] ?? 0),
            'utilized' => (int) ($statusRows['utilized'] ?? 0),
        ];

        // Последние пробы
        $deps = LabDepartment::all()->keyBy('id');
        $recent = LabSample::orderByDesc('RegisteredAt')->orderByDesc('id')->limit(6)->get()->map(function ($s) use ($deps) {
            return [
                'SampleCode' => $s->SampleCode,
                'RegisteredAt' => optional($s->RegisteredAt)->format('Y-m-d H:i'),
                'Status' => $s->Status,
                'DepartmentName' => optional($deps->get($s->DepartmentID))->Name,
                'DepartmentNameRus' => optional($deps->get($s->DepartmentID))->NameRus,
            ];
        });

        // Последние события аудита (лента активности).
        $recentAudit = LabAuditLog::orderByDesc('id')->limit(8)->get()->map(function ($a) {
            return [
                'EntityType' => $a->EntityType,
                'EntityLabel' => $a->EntityLabel,
                'Action' => $a->Action,
                'UserName' => $a->UserName,
                'CreatedAt' => optional($a->CreatedAt)->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'kpi' => [
                'samplesMonth' => $samplesMonth,
                'inProgress' => $inProgress,
                'testedMonth' => $testedMonth,
                'pendingDet' => $pendingDet,
                'pendingApproval' => $pendingApproval,
                'certsYear' => $certsYear,
                'equipTotal' => $equipTotal,
                'equipBlocked' => $equipBlocked,
                'qcMonth' => $qcMonth,
                'qcOut' => $qcOut,
            ],
            'byMonth' => $byMonth,
            'byStatus' => $byStatus,
            'recent' => $recent,
            'recentAudit' => $recentAudit,
        ]);
    }
}
