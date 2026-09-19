<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LabAnalyte;
use App\Models\LabDepartment;
use Carbon\Carbon;

class LabAiController extends Controller
{
    /**
     * Анализ тренда показателя во времени (мониторинг вода/воздух):
     * временной ряд результатов + эвристическая оценка (норма/ухудшение)
     * + при настроенном релее — заключение ИИ.
     */
    public function trend(Request $request)
    {
        $data = $request->validate([
            'AnalyteID' => 'required|integer',
            'DepartmentID' => 'nullable|integer',
            'Category' => 'nullable|string|max:255',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'Norm' => 'nullable|numeric',
            'ask_ai' => 'nullable|boolean',
        ]);

        $rows = DB::table('lab_sample_determinations as d')
            ->join('lab_samples as s', 's.id', '=', 'd.SampleID')
            ->where('d.AnalyteID', $data['AnalyteID'])
            ->whereNotNull('d.ResultValue')
            ->where('d.ResultValue', '<>', '')
            ->when(!empty($data['DepartmentID']), fn ($q) => $q->where('s.DepartmentID', $data['DepartmentID']))
            ->when(!empty($data['Category']), fn ($q) => $q->where('s.Category', $data['Category']))
            ->when(!empty($data['from']), fn ($q) => $q->whereDate('s.RegisteredAt', '>=', $data['from']))
            ->when(!empty($data['to']), fn ($q) => $q->whereDate('s.RegisteredAt', '<=', $data['to']))
            ->orderBy('s.RegisteredAt')->orderBy('s.id')
            ->select('d.ResultValue', 'd.Unit', 's.SampleCode', 's.RegisteredAt')
            ->limit(2000)->get();

        // Числовой ряд (значения, приводимые к числу).
        $series = [];
        foreach ($rows as $r) {
            $val = is_numeric($r->ResultValue) ? (float) $r->ResultValue : null;
            if ($val === null) {
                continue;
            }
            $series[] = [
                'x' => optional(Carbon::parse($r->RegisteredAt))->format('Y-m-d H:i'),
                'y' => $val,
                'code' => $r->SampleCode,
            ];
        }

        $analyte = LabAnalyte::find($data['AnalyteID']);
        $dept = !empty($data['DepartmentID']) ? LabDepartment::find($data['DepartmentID']) : null;
        $unit = $rows->first()->Unit ?? ($analyte->Unit ?? '');
        $norm = $data['Norm'] ?? null;

        $stats = $this->computeStats($series, $norm);
        $verdict = $this->verdict($stats, $norm);

        $ai = null;
        if (!empty($data['ask_ai'])) {
            $ai = $this->askAi($analyte, $dept, $series, $unit, $norm, $stats, $verdict);
        }

        return response()->json([
            'series' => $series,
            'unit' => $unit,
            'analyte' => $analyte ? ['Symbol' => $analyte->Symbol, 'Name' => $analyte->Name, 'NameRus' => $analyte->NameRus] : null,
            'stats' => $stats,
            'verdict' => $verdict,
            'ai' => $ai,
            'ai_configured' => (bool) config('lims.ai.relay_url'),
        ]);
    }

    private function computeStats(array $series, $norm)
    {
        $n = count($series);
        if ($n === 0) {
            return ['n' => 0];
        }
        $ys = array_column($series, 'y');
        $mean = array_sum($ys) / $n;
        $min = min($ys);
        $max = max($ys);
        $first = $ys[0];
        $last = $ys[$n - 1];

        // Наклон линейной регрессии по индексу (тренд).
        $slope = 0.0;
        if ($n > 1) {
            $xm = ($n - 1) / 2;
            $num = 0.0;
            $den = 0.0;
            foreach ($ys as $i => $y) {
                $num += ($i - $xm) * ($y - $mean);
                $den += ($i - $xm) ** 2;
            }
            $slope = $den != 0 ? $num / $den : 0.0;
        }
        // Относительное изменение по всему ряду.
        $totalChange = $slope * ($n - 1);
        $relChange = $mean != 0 ? $totalChange / abs($mean) : 0.0;

        $overNorm = null;
        if ($norm !== null) {
            $overNorm = count(array_filter($ys, fn ($y) => $y > $norm));
        }

        return [
            'n' => $n,
            'mean' => round($mean, 4),
            'min' => round($min, 4),
            'max' => round($max, 4),
            'first' => round($first, 4),
            'last' => round($last, 4),
            'slope' => round($slope, 6),
            'rel_change' => round($relChange, 4),
            'over_norm' => $overNorm,
        ];
    }

    /** Эвристический вердикт: норма/приближение/превышение + направление тренда. */
    private function verdict(array $stats, $norm)
    {
        if (($stats['n'] ?? 0) === 0) {
            return ['level' => 'none', 'ru' => 'Недостаточно данных', 'uz' => "Ma'lumot yetarli emas", 'en' => 'Not enough data'];
        }
        $rel = $stats['rel_change'] ?? 0;
        $rising = $rel > 0.05;
        $falling = $rel < -0.05;

        if ($norm !== null && ($stats['last'] > $norm || ($stats['over_norm'] ?? 0) > 0)) {
            return ['level' => 'danger', 'ru' => 'Превышение нормы' . ($rising ? ', тренд роста' : ''), 'uz' => 'Norma oshgan', 'en' => 'Above limit' . ($rising ? ', rising trend' : '')];
        }
        if ($norm !== null && $stats['last'] > 0.9 * $norm) {
            return ['level' => 'warning', 'ru' => 'Приближается к норме' . ($rising ? ' (рост)' : ''), 'uz' => 'Normaga yaqinlashmoqda', 'en' => 'Approaching limit' . ($rising ? ' (rising)' : '')];
        }
        if ($rising) {
            return ['level' => 'warning', 'ru' => 'Ухудшающийся тренд (рост показателя)', 'uz' => "Yomonlashish trendi", 'en' => 'Worsening trend (value rising)'];
        }
        if ($falling) {
            return ['level' => 'success', 'ru' => 'Улучшение (снижение показателя)', 'uz' => 'Yaxshilanish', 'en' => 'Improving (value falling)'];
        }
        return ['level' => 'success', 'ru' => 'Стабильно, в норме', 'uz' => "Barqaror, normada", 'en' => 'Stable, within limit'];
    }

    /** Вызов ИИ через релей (OpenAI-совместимый). Возвращает текст или ошибку. */
    private function askAi($analyte, $dept, array $series, $unit, $norm, array $stats, array $verdict)
    {
        $url = config('lims.ai.relay_url');
        if (!$url) {
            return ['ok' => false, 'error' => 'ai_not_configured'];
        }

        $name = $analyte ? ($analyte->NameRus ?: $analyte->Name) : '—';
        $place = $dept ? ($dept->NameRus ?: $dept->Name) : 'все источники';
        $points = array_map(fn ($p) => $p['x'] . ' = ' . $p['y'], $series);
        $seriesText = implode('; ', array_slice($points, -40));

        $prompt = "Ты — эколог-аналитик лаборатории. Показатель: {$name} ({$unit}). "
            . "Место/источник: {$place}. Норма (ПДК): " . ($norm !== null ? $norm : 'не задана') . ". "
            . "Ряд измерений во времени: {$seriesText}. "
            . "Статистика: n={$stats['n']}, среднее={$stats['mean']}, мин={$stats['min']}, макс={$stats['max']}, "
            . "первое={$stats['first']}, последнее={$stats['last']}, относит.изменение={$stats['rel_change']}. "
            . "Дай краткое заключение (3-5 предложений) на русском: в норме или есть ухудшение, характер тренда, "
            . "риски и рекомендация. Без вводных фраз.";

        try {
            $payload = [
                'model' => config('lims.ai.model'),
                'messages' => [
                    ['role' => 'system', 'content' => 'Кратко, по делу, на русском языке.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
            ];
            $headers = ['Content-Type' => 'application/json'];
            if (config('lims.ai.api_key')) {
                $headers['Authorization'] = 'Bearer ' . config('lims.ai.api_key');
            }
            $client = new \GuzzleHttp\Client(['timeout' => (int) config('lims.ai.timeout', 30)]);
            $resp = $client->post($url, ['headers' => $headers, 'json' => $payload]);
            $body = json_decode((string) $resp->getBody(), true);
            $text = $body['choices'][0]['message']['content'] ?? null;
            if ($text === null) {
                return ['ok' => false, 'error' => 'bad_response'];
            }
            return ['ok' => true, 'text' => trim($text)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'relay_unavailable', 'message' => $e->getMessage()];
        }
    }
}
