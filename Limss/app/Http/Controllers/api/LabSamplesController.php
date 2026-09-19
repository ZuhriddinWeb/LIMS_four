<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LabSample;
use App\Models\LabSampleDetermination;
use App\Models\LabDepartment;
use App\Models\LabLaboratory;
use App\Models\LabGroup;
use App\Models\LabSampleType;
use App\Models\LabAnalyte;
use App\Models\LabMethod;
use App\Models\LabSampleFile;
use App\Models\LabStorageLocation;
use App\Support\LabAudit;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LabSamplesController extends Controller
{
    /**
     * Регистрация пробы: генерирует шифр, создаёт пробу и заказанные определения.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'DocumentNumber' => 'nullable|string|max:255',
            'DepartmentID' => 'nullable|integer',
            'CustomerLaboratoryID' => 'nullable|integer',
            'CustomerGroupID' => 'nullable|integer',
            'SampleTypeID' => 'nullable|integer',
            'PhysicalState' => 'nullable|string|max:50',
            'Category' => 'nullable|string|max:255',
            'Batch' => 'nullable|string|max:255',
            'Description' => 'nullable|string|max:2000',
            'ChemicalComposition' => 'nullable|string|max:2000',
            'StorageLocation' => 'nullable|string|max:255',
            'StorageLocationID' => 'nullable|integer',
            'StorageConditions' => 'nullable|string|max:255',
            'TransportConditions' => 'nullable|string|max:255',
            'StorageUntil' => 'nullable|date',
            'Quantity' => 'nullable|string|max:255',
            'Comment' => 'nullable|string|max:1000',
            'determinations' => 'array',
            'determinations.*.AnalyteID' => 'nullable|integer',
            'determinations.*.ExecutorGroupID' => 'nullable|integer',
            'determinations.*.MethodID' => 'nullable|integer',
            'determinations.*.Unit' => 'nullable|string|max:50',
        ]);

        $now = Carbon::now();

        $sample = DB::transaction(function () use ($data, $request, $now) {
            // Генерация уникального шифра с несколькими попытками на случай гонки.
            $code = null;
            for ($i = 0; $i < 5; $i++) {
                $candidate = $this->generateSampleCode($now);
                if (!LabSample::where('SampleCode', $candidate)->exists()) {
                    $code = $candidate;
                    break;
                }
            }
            if ($code === null) {
                $code = $this->generateSampleCode($now) . '-' . uniqid();
            }

            $sample = LabSample::create([
                'SampleCode' => $code,
                'DocumentNumber' => $data['DocumentNumber'] ?? null,
                'DepartmentID' => $data['DepartmentID'] ?? null,
                'CustomerLaboratoryID' => $data['CustomerLaboratoryID'] ?? null,
                'CustomerGroupID' => $data['CustomerGroupID'] ?? null,
                'SampleTypeID' => $data['SampleTypeID'] ?? null,
                'PhysicalState' => $data['PhysicalState'] ?? null,
                'Category' => $data['Category'] ?? null,
                'Batch' => $data['Batch'] ?? null,
                'Description' => $data['Description'] ?? null,
                'ChemicalComposition' => $data['ChemicalComposition'] ?? null,
                'RegisteredAt' => $now,
                'RegisteredBy' => optional(LabAudit::resolveUser())->name,
                'PeriodYear' => (int) $now->format('Y'),
                'PeriodMonth' => (int) $now->format('n'),
                'Status' => 'new',
                'StorageLocation' => $data['StorageLocation'] ?? null,
                'StorageLocationID' => $data['StorageLocationID'] ?? null,
                'StorageConditions' => $data['StorageConditions'] ?? null,
                'TransportConditions' => $data['TransportConditions'] ?? null,
                'StorageUntil' => $data['StorageUntil'] ?? null,
                'Quantity' => $data['Quantity'] ?? null,
                'Comment' => $data['Comment'] ?? null,
            ]);

            foreach (($data['determinations'] ?? []) as $d) {
                if (empty($d['AnalyteID'])) {
                    continue;
                }
                LabSampleDetermination::create([
                    'SampleID' => $sample->id,
                    'AnalyteID' => $d['AnalyteID'],
                    'ExecutorGroupID' => $d['ExecutorGroupID'] ?? null,
                    'MethodID' => $d['MethodID'] ?? null,
                    'Unit' => $d['Unit'] ?? null,
                    'Status' => 'pending',
                ]);
            }

            return $sample;
        });

        return response()->json([
            'status' => 200,
            'message' => "Namuna ro'yxatga olindi",
            'unit' => $sample->load('determinations'),
            'SampleCode' => $sample->SampleCode,
        ]);
    }

    /**
     * Собирает шифр из шаблона config('lims.sample_code').
     */
    private function generateSampleCode(Carbon $now): string
    {
        $cfg = config('lims.sample_code');
        $prefix = $cfg['prefix'] ?? 'TL';
        $pad = (int) ($cfg['seq_pad'] ?? 4);
        $reset = $cfg['reset'] ?? 'month';
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');

        $q = LabSample::query();
        if ($reset === 'month') {
            $q->where('PeriodYear', $year)->where('PeriodMonth', $month);
        } elseif ($reset === 'year') {
            $q->where('PeriodYear', $year);
        }
        $seq = $q->count() + 1;

        $replacements = [
            '{prefix}' => $prefix,
            '{year}' => $year,
            '{month}' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
            '{seq}' => str_pad((string) $seq, $pad, '0', STR_PAD_LEFT),
        ];

        return strtr($cfg['format'] ?? '{prefix}-{year}-{month}-{seq}', $replacements);
    }

    /**
     * Список проб с фильтрами: год, месяц, статус, поиск по шифру/№ документа.
     */
    public function index(Request $request)
    {
        $q = LabSample::query()->orderByDesc('RegisteredAt')->orderByDesc('id');

        if ($request->filled('year')) {
            $q->where('PeriodYear', (int) $request->year);
        }
        if ($request->filled('month')) {
            $q->where('PeriodMonth', (int) $request->month);
        }
        if ($request->filled('status')) {
            $q->where('Status', $request->status);
        }
        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $q->where(function ($w) use ($term) {
                $w->where('SampleCode', 'like', $term)
                    ->orWhere('DocumentNumber', 'like', $term);
            });
        }

        $samples = $q->limit(1000)->get();

        // Прикладываем читаемые имена справочников.
        $deps = LabDepartment::all()->keyBy('id');
        $types = LabSampleType::all()->keyBy('id');
        $groups = LabGroup::all()->keyBy('id');
        $counts = LabSampleDetermination::select('SampleID')
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when Status = 'done' then 1 else 0 end) as done")
            ->groupBy('SampleID')->get()->keyBy('SampleID');

        $samples->transform(function ($s) use ($deps, $types, $groups, $counts) {
            $s->DepartmentName = optional($deps->get($s->DepartmentID))->Name;
            $s->DepartmentNameRus = optional($deps->get($s->DepartmentID))->NameRus;
            $s->SampleTypeName = optional($types->get($s->SampleTypeID))->Name;
            $s->SampleTypeNameRus = optional($types->get($s->SampleTypeID))->NameRus;
            $s->CustomerGroupName = optional($groups->get($s->CustomerGroupID))->Name;
            $s->CustomerGroupNameRus = optional($groups->get($s->CustomerGroupID))->NameRus;
            $c = $counts->get($s->id);
            $s->DeterminationsTotal = $c ? (int) $c->total : 0;
            $s->DeterminationsDone = $c ? (int) $c->done : 0;
            return $s;
        });

        return response()->json($samples);
    }

    /**
     * Карточка пробы с определениями (с именами элементов/групп/методик).
     */
    public function show($id)
    {
        $sample = LabSample::find($id);
        if (!$sample) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $analytes = LabAnalyte::all()->keyBy('id');
        $groups = LabGroup::all()->keyBy('id');
        $methods = LabMethod::all()->keyBy('id');
        $deps = LabDepartment::all()->keyBy('id');
        $labs = LabLaboratory::all()->keyBy('id');
        $types = LabSampleType::all()->keyBy('id');

        // Имена справочников для шапки протокола.
        $sample->DepartmentName = optional($deps->get($sample->DepartmentID))->Name;
        $sample->DepartmentNameRus = optional($deps->get($sample->DepartmentID))->NameRus;
        $sample->CustomerLaboratoryName = optional($labs->get($sample->CustomerLaboratoryID))->Name;
        $sample->CustomerLaboratoryNameRus = optional($labs->get($sample->CustomerLaboratoryID))->NameRus;
        $sample->CustomerGroupName = optional($groups->get($sample->CustomerGroupID))->Name;
        $sample->CustomerGroupNameRus = optional($groups->get($sample->CustomerGroupID))->NameRus;
        $sample->SampleTypeName = optional($types->get($sample->SampleTypeID))->Name;
        $sample->SampleTypeNameRus = optional($types->get($sample->SampleTypeID))->NameRus;

        // Место хранения (справочник) + сопроводительные файлы.
        if ($sample->StorageLocationID) {
            $loc = LabStorageLocation::find($sample->StorageLocationID);
            $sample->StorageLocationName = optional($loc)->Name;
            $sample->StorageLocationNameRus = optional($loc)->NameRus;
        }
        $sample->files = LabSampleFile::where('SampleID', $id)->orderByDesc('id')->get();

        $dets = LabSampleDetermination::where('SampleID', $id)->get()->map(function ($d) use ($analytes, $groups, $methods) {
            $a = $analytes->get($d->AnalyteID);
            $d->AnalyteName = optional($a)->Name;
            $d->AnalyteNameRus = optional($a)->NameRus;
            $d->AnalyteSymbol = optional($a)->Symbol;
            $d->ExecutorGroupName = optional($groups->get($d->ExecutorGroupID))->Name;
            $d->ExecutorGroupNameRus = optional($groups->get($d->ExecutorGroupID))->NameRus;
            $m = $methods->get($d->MethodID);
            $d->MethodName = optional($m)->Name;
            $d->MethodNameRus = optional($m)->NameRus;
            // Метрология методики — для отображения неопределённости и LOD/LOQ.
            $d->MethodLOD = optional($m)->LOD;
            $d->MethodLOQ = optional($m)->LOQ;
            $d->MethodUncertainty = optional($m)->Uncertainty;
            $d->MethodDecimalPlaces = optional($m)->DecimalPlaces;
            return $d;
        });

        $sample->determinations = $dets;
        return response()->json($sample);
    }

    /**
     * Изменение статуса пробы.
     */
    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'Status' => 'required|string|max:50',
        ]);
        $sample = LabSample::findOrFail($id);
        $sample->update(['Status' => $data['Status']]);
        return response()->json(['status' => 200, 'message' => 'Status updated', 'unit' => $sample]);
    }

    /**
     * Ввод результата по одному определению (элементу). ResultAt — авто.
     */
    public function saveResult(Request $request, $determinationId)
    {
        $data = $request->validate([
            'ResultValue' => 'nullable|string|max:255',
            'InTolerance' => 'nullable|boolean',
            'AnalystUser' => 'nullable|string|max:255',
            'InstrumentID' => 'nullable|integer',
            'MethodID' => 'nullable|integer',
            'Unit' => 'nullable|string|max:50',
            'Comment' => 'nullable|string|max:1000',
        ]);

        $det = LabSampleDetermination::findOrFail($determinationId);

        // Утверждённый результат заблокирован от изменения (нужен обоснованный reopen).
        if ($det->ResultStatus === 'approved') {
            return response()->json([
                'status' => 423,
                'message' => 'Результат утверждён и заблокирован. Для правки выполните «Переоткрыть».',
            ], 423);
        }

        $det->update(array_merge($data, [
            'ResultAt' => Carbon::now(),
            'Status' => 'done',
            'ResultStatus' => 'entered',   // правка сбрасывает результат в «введён» (нужна повторная проверка)
            'ReviewedBy' => null,
            'ReviewedAt' => null,
            'AnalystUser' => $data['AnalystUser'] ?? optional(LabAudit::resolveUser())->name,
        ]));

        $this->recomputeSampleStatus($det->SampleID);

        return response()->json(['status' => 200, 'message' => 'Natija saqlandi', 'unit' => $det]);
    }

    /**
     * Проверка результата (2-й этап). Требует введённого результата.
     */
    public function review($determinationId)
    {
        $det = LabSampleDetermination::findOrFail($determinationId);
        if (empty($det->ResultValue) && $det->Status !== 'done') {
            return response()->json(['status' => 422, 'message' => 'Нет результата для проверки'], 422);
        }
        if ($det->ResultStatus === 'approved') {
            return response()->json(['status' => 423, 'message' => 'Результат уже утверждён'], 423);
        }
        $user = LabAudit::resolveUser();
        $old = $det->ResultStatus;
        $det->ResultStatus = 'reviewed';
        $det->ReviewedBy = $user->name ?? 'system';
        $det->ReviewedAt = Carbon::now();
        $det->saveQuietly();
        LabAudit::record('LabSampleDetermination', $det->id, 'result_reviewed',
            ['ResultStatus' => ['old' => $old, 'new' => 'reviewed'], 'ReviewedBy' => ['old' => null, 'new' => $det->ReviewedBy]],
            'det#' . $det->id);
        $this->recomputeSampleStatus($det->SampleID);
        return response()->json(['status' => 200, 'message' => 'Проверено', 'unit' => $det]);
    }

    /**
     * Утверждение результата (3-й этап, эл. подпись). Требует предварительной проверки.
     */
    public function approve($determinationId)
    {
        $det = LabSampleDetermination::findOrFail($determinationId);
        if ($det->ResultStatus !== 'reviewed') {
            return response()->json(['status' => 422, 'message' => 'Сначала необходимо проверить результат'], 422);
        }
        $user = LabAudit::resolveUser();
        $det->ResultStatus = 'approved';
        $det->ApprovedBy = $user->name ?? 'system';
        $det->ApprovedAt = Carbon::now();
        $det->saveQuietly();
        LabAudit::record('LabSampleDetermination', $det->id, 'result_approved',
            ['ResultStatus' => ['old' => 'reviewed', 'new' => 'approved'], 'ApprovedBy' => ['old' => null, 'new' => $det->ApprovedBy]],
            'det#' . $det->id);
        $this->recomputeSampleStatus($det->SampleID);
        return response()->json(['status' => 200, 'message' => 'Утверждено', 'unit' => $det]);
    }

    /**
     * Переоткрытие утверждённого/проверенного результата с обязательной причиной (аудируется).
     */
    public function reopen(Request $request, $determinationId)
    {
        $data = $request->validate(['reason' => 'required|string|max:500']);
        $det = LabSampleDetermination::findOrFail($determinationId);
        $old = $det->ResultStatus;
        $det->ResultStatus = 'entered';
        $det->ReviewedBy = null;
        $det->ReviewedAt = null;
        $det->ApprovedBy = null;
        $det->ApprovedAt = null;
        $det->saveQuietly();
        LabAudit::record('LabSampleDetermination', $det->id, 'reopened',
            ['ResultStatus' => ['old' => $old, 'new' => 'entered'], 'reason' => ['old' => null, 'new' => $data['reason']]],
            'det#' . $det->id);
        $this->recomputeSampleStatus($det->SampleID);
        return response()->json(['status' => 200, 'message' => 'Переоткрыто', 'unit' => $det]);
    }

    /**
     * Пересчёт статуса пробы: approved (все утверждены) > tested (все введены) > in_progress > new.
     */
    private function recomputeSampleStatus($sampleId): void
    {
        $sample = LabSample::find($sampleId);
        if (!$sample) {
            return;
        }
        $total = LabSampleDetermination::where('SampleID', $sampleId)->count();
        if ($total === 0) {
            return;
        }
        $done = LabSampleDetermination::where('SampleID', $sampleId)->where('Status', 'done')->count();
        $approved = LabSampleDetermination::where('SampleID', $sampleId)->where('ResultStatus', 'approved')->count();

        if ($approved >= $total) {
            $status = 'approved';
        } elseif ($done >= $total) {
            $status = 'tested';
        } elseif ($done > 0) {
            $status = 'in_progress';
        } else {
            $status = 'new';
        }
        if ($sample->Status !== $status && !in_array($sample->Status, ['reject', 'utilized'], true)) {
            $sample->update(['Status' => $status]);
        }
    }

    public function destroy($id)
    {
        LabSampleDetermination::where('SampleID', $id)->delete();
        LabSample::where('id', $id)->delete();
        return response()->json(['status' => 200, 'message' => 'Deleted']);
    }

    /**
     * Полная история пробы: события самой пробы + всех её определений (аудит).
     */
    public function history($id)
    {
        $detIds = LabSampleDetermination::withTrashed()->where('SampleID', $id)->pluck('id')->all();

        $rows = \App\Models\LabAuditLog::query()
            ->where(function ($q) use ($id) {
                $q->where('EntityType', 'LabSample')->where('EntityID', (int) $id);
            })
            ->orWhere(function ($q) use ($detIds) {
                if (count($detIds)) {
                    $q->where('EntityType', 'LabSampleDetermination')->whereIn('EntityID', $detIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return response()->json($rows);
    }

    /**
     * Загрузка сопроводительного документа пробы (ТЗ 2.8).
     */
    public function uploadFile(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:20480',
            'FileType' => 'nullable|string|max:50',
        ]);
        LabSample::findOrFail($id);
        $file = $request->file('file');
        $path = $file->store("lab_samples/{$id}");
        $rec = LabSampleFile::create([
            'SampleID' => $id,
            'FileType' => $request->input('FileType', 'other'),
            'OriginalName' => $file->getClientOriginalName(),
            'Path' => $path,
            'Size' => $file->getSize(),
            'UploadedBy' => optional(LabAudit::resolveUser())->name,
        ]);
        return response()->json(['status' => 200, 'message' => 'Uploaded', 'unit' => $rec]);
    }

    public function downloadFile($fileId)
    {
        $f = LabSampleFile::findOrFail($fileId);
        if (!Storage::exists($f->Path)) {
            return response()->json(['message' => 'File missing'], 404);
        }
        return Storage::download($f->Path, $f->OriginalName);
    }

    public function deleteFile($fileId)
    {
        $f = LabSampleFile::findOrFail($fileId);
        Storage::delete($f->Path);
        $f->delete();
        return response()->json(['status' => 200, 'message' => 'Deleted']);
    }

    /**
     * Оформление утилизации объекта испытания (ТЗ 2.14): акт, дата, кто, причина.
     * Ставит статус 'utilized' и фиксирует реквизиты (аудируется).
     */
    public function dispose(Request $request, $id)
    {
        $data = $request->validate([
            'DisposalAct' => 'nullable|string|max:255',
            'DisposalDate' => 'nullable|date',
            'DisposalReason' => 'nullable|string|max:500',
        ]);
        $sample = LabSample::findOrFail($id);
        $sample->update([
            'Status' => 'utilized',
            'DisposalAct' => $data['DisposalAct'] ?? null,
            'DisposalDate' => $data['DisposalDate'] ?? Carbon::now()->toDateString(),
            'DisposalBy' => optional(LabAudit::resolveUser())->name,
            'DisposalReason' => $data['DisposalReason'] ?? null,
        ]);
        return response()->json(['status' => 200, 'message' => 'Utilizatsiya rasmiylashtirildi', 'unit' => $sample]);
    }
}
