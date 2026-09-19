<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LabCertificate;
use App\Models\LabCertificateItem;
use App\Models\LabProduct;
use App\Models\LabSample;
use App\Models\LabSampleDetermination;
use App\Models\LabAnalyte;
use App\Models\LabMethod;
use Carbon\Carbon;

class LabCertificatesController extends Controller
{
    /**
     * Создание паспорта качества. Номер генерируется автоматически, если не задан.
     * Строки: из переданного items[] или подтягиваются из связанной пробы.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'CertNumber' => 'nullable|string|max:255',
            'ProductID' => 'nullable|integer',
            'Batch' => 'nullable|string|max:255',
            'CertDate' => 'nullable|date',
            'SampleID' => 'nullable|integer',
            'Quantity' => 'nullable|string|max:255',
            'IssuedBy' => 'nullable|string|max:255',
            'Comment' => 'nullable|string|max:1000',
            'items' => 'array',
            'items.*.AnalyteID' => 'nullable|integer',
            'items.*.MethodID' => 'nullable|integer',
            'items.*.ResultValue' => 'nullable|string|max:255',
            'items.*.Unit' => 'nullable|string|max:50',
            'items.*.NormText' => 'nullable|string|max:255',
            'items.*.Conforms' => 'nullable|boolean',
        ]);

        $now = Carbon::now();

        $cert = DB::transaction(function () use ($data, $request, $now) {
            $number = $data['CertNumber'] ?? null;
            if (!$number) {
                for ($i = 0; $i < 5; $i++) {
                    $candidate = $this->generateCertNumber($now);
                    if (!LabCertificate::where('CertNumber', $candidate)->exists()) {
                        $number = $candidate;
                        break;
                    }
                }
                $number = $number ?? ('PS-' . uniqid());
            }

            $cert = LabCertificate::create([
                'CertNumber' => $number,
                'ProductID' => $data['ProductID'] ?? null,
                'Batch' => $data['Batch'] ?? null,
                'CertDate' => $data['CertDate'] ?? $now->toDateString(),
                'SampleID' => $data['SampleID'] ?? null,
                'Quantity' => $data['Quantity'] ?? null,
                'Status' => 'draft',
                'IssuedBy' => $data['IssuedBy'] ?? optional($request->user())->name,
                'Comment' => $data['Comment'] ?? null,
                'PeriodYear' => (int) $now->format('Y'),
                'PeriodMonth' => (int) $now->format('n'),
            ]);

            $items = $data['items'] ?? [];
            if (empty($items) && !empty($data['SampleID'])) {
                $items = $this->itemsFromSample($data['SampleID']);
            }
            $this->saveItems($cert->id, $items);

            return $cert;
        });

        return response()->json([
            'status' => 200,
            'message' => 'Passport yaratildi',
            'unit' => $cert,
            'CertNumber' => $cert->CertNumber,
        ]);
    }

    private function generateCertNumber(Carbon $now): string
    {
        $cfg = config('lims.cert_number');
        $prefix = $cfg['prefix'] ?? 'PS';
        $pad = (int) ($cfg['seq_pad'] ?? 4);
        $reset = $cfg['reset'] ?? 'year';
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');

        $q = LabCertificate::query();
        if ($reset === 'month') {
            $q->where('PeriodYear', $year)->where('PeriodMonth', $month);
        } elseif ($reset === 'year') {
            $q->where('PeriodYear', $year);
        }
        $seq = $q->count() + 1;

        return strtr($cfg['format'] ?? '{prefix}-{year}-{seq}', [
            '{prefix}' => $prefix,
            '{year}' => $year,
            '{month}' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
            '{seq}' => str_pad((string) $seq, $pad, '0', STR_PAD_LEFT),
        ]);
    }

    private function saveItems($certId, array $items): void
    {
        LabCertificateItem::where('CertificateID', $certId)->delete();
        $order = 1;
        foreach ($items as $it) {
            if (empty($it['AnalyteID']) && empty($it['ResultValue']) && empty($it['NormText'])) {
                continue;
            }
            LabCertificateItem::create([
                'CertificateID' => $certId,
                'AnalyteID' => $it['AnalyteID'] ?? null,
                'MethodID' => $it['MethodID'] ?? null,
                'ResultValue' => $it['ResultValue'] ?? null,
                'Unit' => $it['Unit'] ?? null,
                'NormText' => $it['NormText'] ?? null,
                'Conforms' => $it['Conforms'] ?? null,
                'OrderNumber' => $order++,
            ]);
        }
    }

    /**
     * Показатели из результатов связанной пробы (для предзаполнения паспорта).
     */
    private function itemsFromSample($sampleId): array
    {
        return LabSampleDetermination::where('SampleID', $sampleId)->get()->map(fn ($d) => [
            'AnalyteID' => $d->AnalyteID,
            'MethodID' => $d->MethodID,
            'ResultValue' => $d->ResultValue,
            'Unit' => $d->Unit,
            'NormText' => null,
            'Conforms' => null,
        ])->toArray();
    }

    /**
     * Готовые строки из пробы для UI (предпросмотр перед созданием паспорта).
     */
    public function fromSample($sampleId)
    {
        $analytes = LabAnalyte::all()->keyBy('id');
        $methods = LabMethod::all()->keyBy('id');
        $items = collect($this->itemsFromSample($sampleId))->map(function ($it) use ($analytes, $methods) {
            $a = $analytes->get($it['AnalyteID']);
            $it['AnalyteSymbol'] = optional($a)->Symbol;
            $it['AnalyteName'] = optional($a)->Name;
            $it['AnalyteNameRus'] = optional($a)->NameRus;
            $it['MethodName'] = optional($methods->get($it['MethodID']))->Name;
            $it['MethodNameRus'] = optional($methods->get($it['MethodID']))->NameRus;
            return $it;
        });
        return response()->json($items);
    }

    public function index(Request $request)
    {
        $q = LabCertificate::query()->orderByDesc('CertDate')->orderByDesc('id');
        if ($request->filled('year')) {
            $q->where('PeriodYear', (int) $request->year);
        }
        if ($request->filled('status')) {
            $q->where('Status', $request->status);
        }
        if ($request->filled('q')) {
            $q->where('CertNumber', 'like', '%' . $request->q . '%');
        }
        $rows = $q->limit(2000)->get();

        $products = LabProduct::all()->keyBy('id');
        $samples = LabSample::all()->keyBy('id');
        $rows->transform(function ($c) use ($products, $samples) {
            $p = $products->get($c->ProductID);
            $c->ProductName = optional($p)->Name;
            $c->ProductNameRus = optional($p)->NameRus;
            $c->SampleCode = optional($samples->get($c->SampleID))->SampleCode;
            return $c;
        });

        return response()->json($rows);
    }

    public function show($id)
    {
        $cert = LabCertificate::find($id);
        if (!$cert) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $products = LabProduct::all()->keyBy('id');
        $analytes = LabAnalyte::all()->keyBy('id');
        $methods = LabMethod::all()->keyBy('id');
        $samples = LabSample::all()->keyBy('id');

        $p = $products->get($cert->ProductID);
        $cert->ProductName = optional($p)->Name;
        $cert->ProductNameRus = optional($p)->NameRus;
        $cert->SampleCode = optional($samples->get($cert->SampleID))->SampleCode;

        $cert->items = LabCertificateItem::where('CertificateID', $id)->orderBy('OrderNumber')->get()
            ->map(function ($it) use ($analytes, $methods) {
                $a = $analytes->get($it->AnalyteID);
                $it->AnalyteSymbol = optional($a)->Symbol;
                $it->AnalyteName = optional($a)->Name;
                $it->AnalyteNameRus = optional($a)->NameRus;
                $it->MethodName = optional($methods->get($it->MethodID))->Name;
                $it->MethodNameRus = optional($methods->get($it->MethodID))->NameRus;
                return $it;
            });

        return response()->json($cert);
    }

    public function update(Request $request, $id)
    {
        $cert = LabCertificate::findOrFail($id);
        $data = $request->validate([
            'CertNumber' => 'nullable|string|max:255',
            'ProductID' => 'nullable|integer',
            'Batch' => 'nullable|string|max:255',
            'CertDate' => 'nullable|date',
            'SampleID' => 'nullable|integer',
            'Quantity' => 'nullable|string|max:255',
            'Status' => 'nullable|string|max:50',
            'IssuedBy' => 'nullable|string|max:255',
            'Comment' => 'nullable|string|max:1000',
            'items' => 'array',
        ]);

        $cert->update(array_filter([
            'CertNumber' => $data['CertNumber'] ?? $cert->CertNumber,
            'ProductID' => $data['ProductID'] ?? null,
            'Batch' => $data['Batch'] ?? null,
            'CertDate' => $data['CertDate'] ?? null,
            'SampleID' => $data['SampleID'] ?? null,
            'Quantity' => $data['Quantity'] ?? null,
            'Status' => $data['Status'] ?? $cert->Status,
            'IssuedBy' => $data['IssuedBy'] ?? null,
            'Comment' => $data['Comment'] ?? null,
        ], fn ($v) => $v !== null));

        if (array_key_exists('items', $data)) {
            $this->saveItems($cert->id, $data['items']);
        }

        return response()->json(['status' => 200, 'message' => 'Yangilandi', 'unit' => $cert]);
    }

    public function destroy($id)
    {
        LabCertificateItem::where('CertificateID', $id)->delete();
        LabCertificate::where('id', $id)->delete();
        return response()->json(['status' => 200, 'message' => 'Deleted']);
    }
}
