<template>
  <div class="lims-page">
    <div class="lims-head">
      <div>
        <h1 class="lims-title">
          <span class="material-icons" style="vertical-align:-4px;color:var(--lims-accent)">smart_toy</span>
          {{ L('ИИ-помощник', 'AI-yordamchi', 'AI assistant') }}
        </h1>
        <p class="lims-sub">{{ L('Тренды воды и воздуха, прогноз и заключение', 'Suv va havo trendlari, prognoz va xulosa', 'Water & air trends, forecast and verdict') }}</p>
      </div>
    </div>

    <!-- Фильтры -->
    <div class="lims-toolbar">
      <VaSelect v-model="sel.AnalyteID" :options="opt.analytes" text-by="text" value-by="value"
        :label="L('Показатель', 'Ko\'rsatkich', 'Analyte')" class="w-44" />
      <VaSelect v-model="sel.DepartmentID" :options="opt.departments" text-by="text" value-by="value"
        :label="L('Источник / место', 'Manba / joy', 'Source / location')" class="w-52" clearable />
      <VaInput v-model="sel.from" type="date" :label="L('С', 'Dan', 'From')" class="w-40" />
      <VaInput v-model="sel.to" type="date" :label="L('По', 'Gacha', 'To')" class="w-40" />
      <VaInput v-model="sel.Norm" :label="L('Норма (ПДК)', 'Norma (PDK)', 'Limit (MPC)')" class="w-28" />
      <button class="lims-btn" @click="analyze(false)" :disabled="!sel.AnalyteID"><span class="material-icons">analytics</span>{{ L('Анализ', 'Tahlil', 'Analyze') }}</button>
      <button class="lims-btn ghost" @click="analyze(true)" :disabled="!sel.AnalyteID"><span class="material-icons">smart_toy</span>{{ L('Спросить ИИ', "AI so'rash", 'Ask AI') }}</button>
    </div>

    <div v-if="!result" class="lims-empty" style="margin-top:32px">
      {{ L('Выберите показатель и нажмите «Анализ».', "Ko'rsatkichni tanlang va «Tahlil» bosing.", 'Pick an analyte and press “Analyze”.') }}
    </div>

    <!-- Результат -->
    <div v-if="result" class="ai-grid">
      <!-- График -->
      <div class="lims-panel lims-panel-pad ai-chart">
        <div class="ai-panel-title">{{ L('Динамика', 'Dinamika', 'Trend') }}</div>
        <canvas ref="chartCanvas" height="120"></canvas>
      </div>

      <!-- Вердикт + статистика -->
      <div class="lims-panel lims-panel-pad">
        <div class="ai-panel-title">{{ L('Оценка', 'Baho', 'Assessment') }}</div>
        <span class="chip" :class="verdictChip" style="margin-bottom:12px;display:inline-block">{{ verdictText }}</span>
        <table class="lims-table ai-stats" v-if="result.stats && result.stats.n">
          <tbody>
            <tr><td>{{ L('Измерений', 'O\'lchov', 'Measurements') }}</td><td class="mono">{{ result.stats.n }}</td></tr>
            <tr><td>{{ L('Последнее', 'Oxirgi', 'Latest') }}</td><td class="mono">{{ result.stats.last }} {{ result.unit }}</td></tr>
            <tr><td>{{ L('Среднее', 'O\'rtacha', 'Mean') }}</td><td class="mono">{{ result.stats.mean }}</td></tr>
            <tr><td>{{ L('Мин / Макс', 'Min / Maks', 'Min / Max') }}</td><td class="mono">{{ result.stats.min }} / {{ result.stats.max }}</td></tr>
            <tr><td>{{ L('Изм. по ряду', 'Ryad o\'zgarishi', 'Series change') }}</td><td class="mono" :style="changeStyle">{{ Math.round(result.stats.rel_change * 100) }}%</td></tr>
            <tr v-if="result.stats.over_norm !== null"><td>{{ L('Выше нормы', 'Normadan yuqori', 'Above limit') }}</td><td class="mono">{{ result.stats.over_norm }}</td></tr>
          </tbody>
        </table>
        <p v-else class="lims-empty">{{ L('Нет данных за период.', "Davr uchun ma'lumot yo'q.", 'No data for the period.') }}</p>
      </div>

      <!-- Заключение ИИ -->
      <div class="lims-panel lims-panel-pad ai-verdict" v-if="aiRequested">
        <div class="ai-panel-title"><span class="material-icons" style="vertical-align:-4px;color:var(--lims-accent);font-size:18px">smart_toy</span> {{ L('Заключение ИИ', 'AI xulosasi', 'AI verdict') }}</div>
        <div v-if="loadingAi" class="ai-loading">{{ L('Запрос к ИИ…', "AI so'rovi…", 'Querying AI…') }}</div>
        <div v-else-if="result.ai && result.ai.ok" class="ai-text">{{ result.ai.text }}</div>
        <div v-else-if="!result.ai_configured" class="ai-note">
          {{ L('ИИ-релей не настроен (LIMS_AI_RELAY_URL). Показана эвристическая оценка. На заводе подключается релей с DeepSeek.', 'AI relay sozlanmagan (LIMS_AI_RELAY_URL). Evristik baho ko\'rsatildi.', 'AI relay not configured (LIMS_AI_RELAY_URL). Heuristic assessment shown. A DeepSeek relay is connected at the plant.') }}
        </div>
        <div v-else class="ai-error">
          {{ L('ИИ недоступен: ', 'AI mavjud emas: ', 'AI unavailable: ') }}{{ result.ai && result.ai.error }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick, watch } from "vue";
import axios from "axios";
import Chart from "chart.js/auto";
import "vuestic-ui/dist/vuestic-ui.css";
import { VaInput, VaSelect, useToast } from "vuestic-ui";
import { useLang } from "../../composables/useLang.js";

const { L, isRu, locale } = useLang();
const { init } = useToast();

const opt = reactive({ analytes: [], departments: [] });
const sel = reactive({ AnalyteID: null, DepartmentID: null, from: "", to: "", Norm: "" });
const result = ref(null);
const aiRequested = ref(false);
const loadingAi = ref(false);
const chartCanvas = ref(null);
let chartInstance = null;

const verdictText = computed(() => (result.value?.verdict ? L(result.value.verdict.ru, result.value.verdict.uz, result.value.verdict.en || result.value.verdict.ru) : ""));
const verdictChip = computed(() => ({ danger: "chip-reject", warning: "chip-in_progress", success: "chip-tested", none: "chip-utilized" }[result.value?.verdict?.level] || "chip-utilized"));
const changeStyle = computed(() => {
  const c = result.value?.stats?.rel_change;
  if (c == null) return "";
  return c > 0.05 ? "color:#f43f5e;font-weight:700" : c < -0.05 ? "color:#22c55e;font-weight:700" : "";
});

const mapOpts = (arr) =>
  (Array.isArray(arr) ? arr : []).map((o) => ({ value: o.id, text: (isRu.value ? o.NameRus || o.Name : o.Name) ?? String(o.id) }));

const loadOptions = async () => {
  const [a, d] = await Promise.all([
    axios.get("/lab/analytes").catch(() => ({ data: [] })),
    axios.get("/lab/departments").catch(() => ({ data: [] })),
  ]);
  opt.analytes = mapOpts(a.data);
  opt.departments = mapOpts(d.data);
};

const analyze = async (askAi) => {
  if (!sel.AnalyteID) return;
  aiRequested.value = askAi;
  loadingAi.value = askAi;
  try {
    const params = { AnalyteID: sel.AnalyteID };
    if (sel.DepartmentID) params.DepartmentID = sel.DepartmentID;
    if (sel.from) params.from = sel.from;
    if (sel.to) params.to = sel.to;
    if (sel.Norm !== "" && sel.Norm !== null) params.Norm = sel.Norm;
    if (askAi) params.ask_ai = 1;
    const { data } = await axios.get("/lab/ai/trend", { params });
    result.value = data;
    await drawChart();
  } catch (e) {
    init({ message: L("Ошибка анализа", "Xatolik", "Analysis error"), color: "danger" });
    console.error(e);
  } finally {
    loadingAi.value = false;
  }
};

const drawChart = async () => {
  await nextTick();
  if (!chartCanvas.value || !result.value) return;
  const dark = document.documentElement.classList.contains("dark-theme");
  const grid = dark ? "rgba(148,163,184,0.12)" : "rgba(15,23,42,0.08)";
  const tick = dark ? "#93a4c4" : "#475569";
  const series = result.value.series || [];
  const labels = series.map((p) => p.x);
  const ctx = chartCanvas.value.getContext("2d");
  const grad = ctx.createLinearGradient(0, 0, 0, 260);
  grad.addColorStop(0, "rgba(34,211,238,0.35)");
  grad.addColorStop(1, "rgba(34,211,238,0.02)");
  const datasets = [
    {
      label: L("Значение", "Qiymat", "Value"),
      data: series.map((p) => p.y),
      borderColor: "#22d3ee",
      backgroundColor: grad,
      pointRadius: 3,
      pointBackgroundColor: "#22d3ee",
      borderWidth: 2,
      tension: 0.25,
      fill: true,
    },
  ];
  const norm = sel.Norm !== "" ? parseFloat(sel.Norm) : null;
  if (norm !== null && !isNaN(norm)) {
    datasets.push({
      label: L("Норма", "Norma", "Limit"),
      data: labels.map(() => norm),
      borderColor: "#f43f5e",
      borderDash: [6, 4],
      pointRadius: 0,
      borderWidth: 1.5,
      fill: false,
    });
  }
  if (chartInstance) chartInstance.destroy();
  chartInstance = new Chart(chartCanvas.value, {
    type: "line",
    data: { labels, datasets },
    options: {
      responsive: true,
      plugins: { legend: { display: true, labels: { color: tick, boxWidth: 12 } } },
      scales: {
        x: { ticks: { maxRotation: 45, color: tick }, grid: { color: grid } },
        y: { ticks: { color: tick }, grid: { color: grid } },
      },
    },
  });
};

// Перерисовать график и подписи при смене языка.
watch(() => L("ru", "uz", "en"), () => { if (result.value) drawChart(); });

// Перерисовать график при переключении темы (цвета осей/сетки/градиента).
let themeObserver = null;

onMounted(() => {
  const savedLocale = localStorage.getItem("locale");
  if (savedLocale) locale.value = savedLocale;
  loadOptions();
  themeObserver = new MutationObserver(() => { if (result.value) drawChart(); });
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });
});

onBeforeUnmount(() => {
  if (themeObserver) themeObserver.disconnect();
  if (chartInstance) chartInstance.destroy();
});
</script>

<style scoped>
.ai-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 14px;
}
.ai-verdict { grid-column: 1 / -1; }
@media (max-width: 900px) {
  .ai-grid { grid-template-columns: 1fr; }
}
.ai-panel-title {
  font-size: 13px;
  font-weight: 700;
  letter-spacing: .02em;
  color: var(--lims-title);
  margin-bottom: 10px;
  text-transform: uppercase;
  opacity: .85;
}
.ai-stats td { padding: 6px 8px; }
.ai-stats td:first-child { color: var(--lims-sub-color, #94a3b8); }
.ai-text { font-size: 13px; line-height: 1.6; white-space: pre-line; color: var(--lims-title); }
.ai-loading { font-size: 13px; opacity: .6; }
.ai-note { font-size: 12.5px; color: #f59e0b; line-height: 1.5; }
.ai-error { font-size: 13px; color: #f43f5e; }
</style>
