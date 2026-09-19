<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\LabSample;
use App\Models\LabSampleDetermination;
use App\Models\LabInstrument;
use App\Models\LabQcMeasurement;
use App\Models\LabAnalyte;
use App\Models\LabGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Центр оповещений лаборатории: ожидают проверки/утверждения, поверка приборов,
 * выход ВЛК за допуск, просроченные пробы (TAT). Только чтение (агрегация).
 */
class LabAlertsController extends Controller
{
    public function index(Request $request)
    {
        $overdueDays = (int) config('lims.tat_overdue_days', 3);
        $calibDays = (int) config('lims.calibration_warn_days', 30);
        $qcWindow = 30;

        $now = Carbon::now();

        // 1. Ожидают проверки/утверждения.
        $analytes = LabAnalyte::withTrashed()->get()->keyBy('id');
        $samples = LabSample::withTrashed()->get()->keyBy('id');
        $pending = LabSampleDetermination::whereIn('ResultStatus', ['entered', 'reviewed'])
            ->orderByDesc('ResultAt')->limit(200)->get()
            ->map(function ($d) use ($analytes, $samples) {
                return [
                    'id' => $d->id,
                    'sampleId' => $d->SampleID,
                    'sampleCode' => optional($samples->get($d->SampleID))->SampleCode,
                    'analyte' => optional($analytes->get($d->AnalyteID))->Symbol
                        ?: optional($analytes->get($d->AnalyteID))->NameRus,
                    'stage' => $d->ResultStatus, // entered -> нужна проверка; reviewed -> нужно утверждение
                    'at' => $d->ResultAt,
                ];
            })->values();

        // 2. Поверка/калибровка приборов (просрочена или скоро).
        $calibration = LabInstrument::whereNotNull('VerificationDue')
            ->whereDate('VerificationDue', '<=', $now->copy()->addDays($calibDays))
            ->orderBy('VerificationDue')->get()
            ->map(function ($m) use ($now) {
                $due = Carbon::parse($m->VerificationDue);
                return [
                    'id' => $m->id,
                    'name' => $m->NameRus ?: $m->Name,
                    'due' => $m->VerificationDue,
                    'overdue' => $due->lt($now->copy()->startOfDay()),
                    'daysLeft' => (int) $now->copy()->startOfDay()->diffInDays($due, false),
                ];
            })->values();

        // 3. ВЛК вне допуска за последние 30 дней.
        $qcOut = LabQcMeasurement::where('InTolerance', 0)
            ->where('MeasuredAt', '>=', $now->copy()->subDays($qcWindow))
            ->orderByDesc('MeasuredAt')->limit(100)->get()
            ->map(function ($q) use ($analytes) {
                return [
                    'id' => $q->id,
                    'analyte' => optional($analytes->get($q->AnalyteID))->Symbol
                        ?: optional($analytes->get($q->AnalyteID))->NameRus,
                    'measured' => $q->MeasuredValue,
                    'certified' => $q->CertifiedValue,
                    'at' => $q->MeasuredAt,
                ];
            })->values();

        // 3b. Истекает срок хранения проб (ТЗ 2.12): просрочен или в пределах 7 дней.
        $storageExpiring = LabSample::whereNotNull('StorageUntil')
            ->where('Status', '!=', 'utilized')
            ->whereDate('StorageUntil', '<=', $now->copy()->addDays(7))
            ->orderBy('StorageUntil')->limit(100)->get()
            ->map(function ($s) use ($now) {
                $until = Carbon::parse($s->StorageUntil);
                return [
                    'id' => $s->id,
                    'sampleCode' => $s->SampleCode,
                    'until' => $s->StorageUntil,
                    'expired' => $until->lt($now->copy()->startOfDay()),
                    'daysLeft' => (int) $now->copy()->startOfDay()->diffInDays($until, false),
                ];
            })->values();

        // 4. Просроченные пробы (в работе дольше N дней) — прокси срока (TAT).
        $overdueSamples = LabSample::whereIn('Status', ['new', 'in_progress'])
            ->whereNotNull('RegisteredAt')
            ->where('RegisteredAt', '<', $now->copy()->subDays($overdueDays))
            ->orderBy('RegisteredAt')->limit(100)->get()
            ->map(function ($s) use ($now) {
                return [
                    'id' => $s->id,
                    'sampleCode' => $s->SampleCode,
                    'status' => $s->Status,
                    'registeredAt' => $s->RegisteredAt,
                    'ageDays' => (int) Carbon::parse($s->RegisteredAt)->diffInDays($now),
                ];
            })->values();

        return response()->json([
            'pending' => $pending,
            'calibration' => $calibration,
            'qcOut' => $qcOut,
            'storageExpiring' => $storageExpiring,
            'overdueSamples' => $overdueSamples,
            'counts' => [
                'pending' => $pending->count(),
                'calibration' => $calibration->count(),
                'qcOut' => $qcOut->count(),
                'storageExpiring' => $storageExpiring->count(),
                'overdueSamples' => $overdueSamples->count(),
                'total' => $pending->count() + $calibration->count() + $qcOut->count() + $storageExpiring->count() + $overdueSamples->count(),
            ],
        ]);
    }
}
