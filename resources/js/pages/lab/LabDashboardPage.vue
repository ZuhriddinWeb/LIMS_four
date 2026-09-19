<template>
  <div class="dash">
    <div class="dash-head">
      <div>
        <h1 class="dash-title">{{ L('Панель ЛИМС', 'LIMS paneli', 'LIMS Dashboard') }}</h1>
        <p class="dash-sub">{{ L('Лаборатория · обзор', 'Laboratoriya · umumiy ko\'rinish', 'Laboratory · overview') }}</p>
      </div>
      <div class="dash-clock">{{ clock }}</div>
    </div>

    <!-- KPI -->
    <div class="kpi-grid">
      <div v-for="k in kpis" :key="k.key" class="kpi-card" :style="{ '--accent': k.color }">
        <div class="kpi-icon"><span class="material-icons">{{ k.icon }}</span></div>
        <div class="kpi-body">
          <div class="kpi-value">{{ k.value }}</div>
          <div class="kpi-label">{{ k.label }}</div>
          <div v-if="k.sub" class="kpi-sub">{{ k.sub }}</div>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="dash-row">
      <div class="panel panel-2">
        <div class="panel-title">{{ L('Пробы по месяцам', 'Oylar bo\'yicha namunalar', 'Samples by month') }}</div>
        <canvas ref="monthCanvas" height="120"></canvas>
      </div>
      <div class="panel">
        <div class="panel-title">{{ L('Статусы проб', 'Namuna holatlari', 'Sample statuses') }}</div>
        <canvas ref="statusCanvas" height="120"></canvas>
      </div>
    </div>

    <!-- Recent -->
    <div class="panel">
      <div class="panel-title">{{ L('Последние пробы', 'Oxirgi namunalar', 'Recent samples') }}</div>
      <table class="dash-table">
        <thead>
          <tr>
            <th>{{ L('Шифр', 'Shifr', 'Code') }}</th>
            <th>{{ L('Дата', 'Sana', 'Date') }}</th>
            <th>{{ L('Подразделение', 'Bo\'linma', 'Department') }}</th>
            <th>{{ L('Статус', 'Holat', 'Status') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(r, i) in recent" :key="i">
            <td class="mono">{{ r.SampleCode }}</td>
            <td>{{ r.RegisteredAt }}</td>
            <td>{{ isRu ? (r.DepartmentNameRus || r.DepartmentName) : r.DepartmentName }}</td>
            <td><span class="chip" :class="'chip-' + r.Status">{{ statusLabel(r.Status) }}</span></td>
          </tr>
          <tr v-if="!recent.length"><td colspan="4" class="empty">—</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Recent activity (audit) -->
    <div class="panel" style="margin-top:16px">
      <div class="panel-title">{{ L('Недавняя активность', "So'nggi faoliyat", 'Recent activity') }}</div>
      <table class="dash-table">
        <tbody>
          <tr v-for="(a, i) in recentAudit" :key="i">
            <td class="mono" style="width:140px">{{ a.CreatedAt }}</td>
            <td style="width:130px">{{ a.UserName }}</td>
            <td>
              <span class="act-badge">{{ auditActionLabel(a.Action) }}</span>
              {{ auditEntityLabel(a.EntityType) }}
              <span v-if="a.EntityLabel" class="mono">{{ a.EntityLabel }}</span>
            </td>
          </tr>
          <tr v-if="!recentAudit.length"><td colspan="3" class="empty">—</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from "vue";
import axios from "axios";
import Chart from "chart.js/auto";
import { useLang } from "../../composables/useLang.js";

const { L, isRu } = useLang();

const kpi = reactive({});
const recent = ref([]);
const recentAudit = ref([]);
const monthCanvas = ref(null);
const statusCanvas = ref(null);
let monthChart = null, statusChart = null;

const clock = ref("");
let clockTimer = null;
const tick = () => (clock.value = new Date().toLocaleString());

const statuses = {
  new: { ru: "Новый", uz: "Yangi", en: "New" },
  in_progress: { ru: "В работе", uz: "Jarayonda", en: "In progress" },
  tested: { ru: "Испытан", uz: "Sinaldi", en: "Tested" },
  reject: { ru: "Брак", uz: "Brak", en: "Reject" },
  utilized: { ru: "Утилизирован", uz: "Utilizatsiya", en: "Utilized" },
};
const statusLabel = (s) => (statuses[s] ? L(statuses[s].ru, statuses[s].uz, statuses[s].en) : s);

// Метки для ленты аудита.
const auditEntities = {
  LabSample: { ru: "Проба", uz: "Namuna", en: "Sample" },
  LabSampleDetermination: { ru: "Определение", uz: "Aniqlash", en: "Determination" },
  LabCertificate: { ru: "Паспорт", uz: "Pasport", en: "Certificate" },
  LabQcMeasurement: { ru: "ВЛК", uz: "IQN", en: "QC" },
  LabInstrument: { ru: "Прибор", uz: "Asbob", en: "Instrument" },
  LabStandard: { ru: "Ст. образец", uz: "SN", en: "Standard" },
  LabMethod: { ru: "Методика", uz: "Metodika", en: "Method" },
};
const auditEntityLabel = (t) => (auditEntities[t] ? L(auditEntities[t].ru, auditEntities[t].uz, auditEntities[t].en) : t);
const auditActions = {
  created: { ru: "создание", uz: "yaratish", en: "created" },
  updated: { ru: "изменение", uz: "o'zgartirish", en: "updated" },
  deleted: { ru: "удаление", uz: "o'chirish", en: "deleted" },
  result_reviewed: { ru: "проверка", uz: "tekshirish", en: "reviewed" },
  result_approved: { ru: "утверждение", uz: "tasdiqlash", en: "approved" },
  reopened: { ru: "переоткрытие", uz: "qayta ochish", en: "reopened" },
  status_changed: { ru: "смена статуса", uz: "holat", en: "status change" },
};
const auditActionLabel = (a) => (auditActions[a] ? L(auditActions[a].ru, auditActions[a].uz, auditActions[a].en) : a);

// KPI — computed, реактивны к языку и данным.
const kpis = computed(() => [
  { key: 'sm', icon: 'science', color: '#22d3ee', value: kpi.samplesMonth ?? 0, label: L('Пробы за месяц', 'Oylik namunalar', 'Samples this month') },
  { key: 'ip', icon: 'sync', color: '#f59e0b', value: kpi.inProgress ?? 0, label: L('В работе', 'Jarayonda', 'In progress') },
  { key: 'ts', icon: 'task_alt', color: '#34d399', value: kpi.testedMonth ?? 0, label: L('Испытано (месяц)', 'Sinaldi (oy)', 'Tested (month)') },
  { key: 'pd', icon: 'pending_actions', color: '#a78bfa', value: kpi.pendingDet ?? 0, label: L('Ожидают анализа', 'Tahlil kutmoqda', 'Pending analyses') },
  { key: 'pa', icon: 'fact_check', color: kpi.pendingApproval ? '#f59e0b' : '#34d399', value: kpi.pendingApproval ?? 0, label: L('Ожидают утверждения', 'Tasdiqlash kutmoqda', 'Awaiting approval') },
  { key: 'ct', icon: 'verified', color: '#60a5fa', value: kpi.certsYear ?? 0, label: L('Паспортов (год)', 'Pasportlar (yil)', 'Certificates (year)') },
  { key: 'eq', icon: 'handyman', color: kpi.equipBlocked ? '#f43f5e' : '#38bdf8', value: `${kpi.equipTotal ?? 0}`, label: L('Оборудование', 'Jihozlar', 'Equipment'), sub: kpi.equipBlocked ? L(`Заблокировано: ${kpi.equipBlocked}`, `Bloklangan: ${kpi.equipBlocked}`, `Blocked: ${kpi.equipBlocked}`) : L('Все в норме', 'Hammasi normada', 'All OK') },
  { key: 'qc', icon: 'insights', color: kpi.qcOut ? '#f43f5e' : '#34d399', value: kpi.qcOut ?? 0, label: L('ВЛК вне допуска', 'Ruxsatdan tashqari', 'QC out of tolerance'), sub: L('за месяц', 'oylik', 'this month') },
]);

const monthNames = { ru: ["Янв","Фев","Мар","Апр","Май","Июн","Июл","Авг","Сен","Окт","Ноя","Дек"], uz: ["Yan","Fev","Mar","Apr","May","Iyn","Iyl","Avg","Sen","Okt","Noy","Dek"], en: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"] };
const fmtMonth = (label) => {
  const [y, m] = label.split('-');
  const arr = L(monthNames.ru, monthNames.uz, monthNames.en);
  return `${arr[parseInt(m, 10) - 1]} ${y.slice(2)}`;
};

const drawCharts = async (data) => {
  await nextTick();
  const dark = document.documentElement.classList.contains('dark-theme');
  const gridColor = dark ? 'rgba(148,163,184,0.15)' : 'rgba(100,116,139,0.18)';
  const tickColor = dark ? '#94a3b8' : '#475569';
  if (monthCanvas.value) {
    if (monthChart) monthChart.destroy();
    const g = monthCanvas.value.getContext('2d').createLinearGradient(0, 0, 0, 220);
    g.addColorStop(0, 'rgba(34,211,238,0.55)');
    g.addColorStop(1, 'rgba(34,211,238,0.03)');
    monthChart = new Chart(monthCanvas.value, {
      type: 'bar',
      data: { labels: data.byMonth.map(x => fmtMonth(x.label)), datasets: [{ data: data.byMonth.map(x => x.count), backgroundColor: g, borderColor: '#22d3ee', borderWidth: 1, borderRadius: 6, maxBarThickness: 46 }] },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { color: tickColor } }, y: { grid: { color: gridColor }, ticks: { color: tickColor, precision: 0 }, beginAtZero: true } } },
    });
  }
  if (statusCanvas.value) {
    if (statusChart) statusChart.destroy();
    const s = data.byStatus;
    statusChart = new Chart(statusCanvas.value, {
      type: 'doughnut',
      data: {
        labels: [statusLabel('new'), statusLabel('in_progress'), statusLabel('tested'), statusLabel('reject'), statusLabel('utilized')],
        datasets: [{ data: [s.new, s.in_progress, s.tested, s.reject, s.utilized], backgroundColor: ['#38bdf8', '#f59e0b', '#34d399', '#f43f5e', '#94a3b8'], borderColor: 'transparent', borderWidth: 0 }],
      },
      options: { responsive: true, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { color: tickColor, boxWidth: 12, padding: 10 } } } },
    });
  }
};

const chartData = ref(null);
const load = async () => {
  try {
    const { data } = await axios.get('/lab/dashboard');
    Object.assign(kpi, data.kpi);
    recent.value = data.recent || [];
    recentAudit.value = data.recentAudit || [];
    chartData.value = data;
    await drawCharts(data);
  } catch (e) {
    console.error(e);
  }
};

// Перерисовать графики при смене языка (подписи месяцев/статусов).
watch(() => L('ru', 'uz', 'en'), () => {
  if (chartData.value) drawCharts(chartData.value);
});

// Перерисовать графики при переключении темы (цвета осей/сетки).
let themeObserver = null;

onMounted(() => {
  tick();
  clockTimer = setInterval(tick, 1000);
  load();
  themeObserver = new MutationObserver(() => {
    if (chartData.value) drawCharts(chartData.value);
  });
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });
});
onBeforeUnmount(() => {
  if (clockTimer) clearInterval(clockTimer);
  if (themeObserver) themeObserver.disconnect();
  if (monthChart) monthChart.destroy();
  if (statusChart) statusChart.destroy();
});
</script>

<style scoped>
.dash {
  /* Светлая тема по умолчанию */
  --bg: #eef2f7;
  --card: #ffffff;
  --card2: #f8fafc;
  --line: #e2e8f0;
  --text: #0f172a;
  --muted: #64748b;
  min-height: 100%;
  padding: 82px 22px 40px;
  background:
    radial-gradient(900px 500px at 15% -10%, rgba(34,211,238,0.10), transparent 60%),
    radial-gradient(800px 500px at 100% 0%, rgba(96,165,250,0.10), transparent 55%),
    var(--bg);
  color: var(--text);
}
.dash-head { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 18px; gap: 12px; flex-wrap: wrap; }
.dash-title { font-size: 1.55rem; font-weight: 800; letter-spacing: -0.02em; margin: 0; background: linear-gradient(90deg,#0f172a,#0284c7); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
.dash-sub { margin: 2px 0 0; color: var(--muted); font-size: 0.85rem; }
.dash-clock { color: var(--muted); font-variant-numeric: tabular-nums; font-size: 0.85rem; padding-top: 6px; }

.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 16px; }
.kpi-card {
  position: relative; display: flex; align-items: center; gap: 14px;
  padding: 16px; border-radius: 16px;
  background: linear-gradient(180deg, var(--card2), var(--card));
  border: 1px solid var(--line);
  box-shadow: 0 10px 30px -18px rgba(0,0,0,0.7);
  overflow: hidden;
}
.kpi-card::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 4px; background: var(--accent); box-shadow: 0 0 18px 2px var(--accent); opacity: 0.9; }
.kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; color: var(--accent); background: color-mix(in srgb, var(--accent) 16%, transparent); }
.kpi-icon .material-icons { font-size: 26px; }
.kpi-value { font-size: 1.7rem; font-weight: 800; line-height: 1.1; }
.kpi-label { color: var(--muted); font-size: 0.8rem; margin-top: 2px; }
.kpi-sub { color: var(--accent); font-size: 0.72rem; margin-top: 3px; opacity: 0.9; }

.dash-row { display: grid; grid-template-columns: 2fr 1fr; gap: 14px; margin-bottom: 16px; }
.panel { background: linear-gradient(180deg, var(--card2), var(--card)); border: 1px solid var(--line); border-radius: 16px; padding: 16px; box-shadow: 0 10px 30px -20px rgba(0,0,0,0.7); }
.panel-title { font-weight: 700; font-size: 0.92rem; margin-bottom: 12px; color: var(--text); }

.dash-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.dash-table th { text-align: left; color: var(--muted); font-weight: 600; padding: 8px 10px; border-bottom: 1px solid var(--line); }
.dash-table td { padding: 9px 10px; border-bottom: 1px solid var(--line); color: var(--text); }
.dash-table tr:last-child td { border-bottom: none; }
.mono { font-family: ui-monospace, monospace; font-weight: 700; color: #0284c7; }
.empty { text-align: center; color: var(--muted); }
.act-badge { display: inline-block; padding: 1px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; background: rgba(34,211,238,0.14); color: #0891b2; margin-right: 6px; }

.chip { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; }
.chip-new { background: rgba(56,189,248,0.16); color: #0284c7; }
.chip-in_progress { background: rgba(245,158,11,0.16); color: #b45309; }
.chip-tested { background: rgba(52,211,153,0.16); color: #047857; }
.chip-reject { background: rgba(244,63,94,0.16); color: #be123c; }
.chip-utilized { background: rgba(148,163,184,0.16); color: #475569; }

@media (max-width: 900px) {
  .dash-row { grid-template-columns: 1fr; }
}
</style>

<!-- Тёмная тема: non-scoped, чтобы html.dark-theme перекрывал переменные .dash -->
<style>
html.dark-theme .dash {
  --bg: #0b1220;
  --card: #131c2e;
  --card2: #172236;
  --line: #24304a;
  --text: #e6edf7;
  --muted: #93a1b8;
}
html.dark-theme .dash-title { background: linear-gradient(90deg,#e6edf7,#7dd3fc); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
html.dark-theme .dash .mono { color: #7dd3fc; }
html.dark-theme .dash .chip-new { color: #7dd3fc; }
html.dark-theme .dash .chip-in_progress { color: #fbbf24; }
html.dark-theme .dash .chip-tested { color: #6ee7b7; }
html.dark-theme .dash .chip-reject { color: #fda4af; }
html.dark-theme .dash .chip-utilized { color: #cbd5e1; }
</style>
