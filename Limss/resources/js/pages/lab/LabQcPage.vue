<template>
  <div class="lims-page">
    <div class="lims-head">
      <div>
        <h1 class="lims-title">{{ L('Внутрилабораторный контроль', 'Ichki laboratoriya nazorati', 'Internal quality control') }}</h1>
        <p class="lims-sub">{{ L('Контрольные измерения СО и карты Шухарта', 'SN nazorat o\'lchovlari', 'Reference-standard control & Shewhart charts') }}</p>
      </div>
    </div>

    <!-- Панель выбора и регистрации -->
    <div class="lims-toolbar">
      <VaSelect v-model="sel.StandardID" :options="opt.standards" text-by="text" value-by="value"
        :label="L('Станд. образец', 'Standart namuna', 'Reference standard')" class="w-56" @update:model-value="onContextChange" />
      <VaSelect v-model="sel.AnalyteID" :options="opt.analytes" text-by="text" value-by="value"
        :label="L('Показатель', 'Ko\'rsatkich', 'Analyte')" class="w-44" @update:model-value="onContextChange" />
      <VaSelect v-model="sel.MethodID" :options="opt.methods" text-by="text" value-by="value"
        :label="L('Методика', 'Metodika', 'Method')" class="w-44" clearable />
      <VaInput v-model="form.MeasuredValue" :label="L('Измеренное', 'O\'lchangan', 'Measured')" class="w-32" />
      <button v-if="canCreate" class="lims-btn" @click="saveMeasurement" :disabled="!sel.StandardID || !sel.AnalyteID || form.MeasuredValue === ''">
        <span class="material-icons">save</span>{{ L('Записать', 'Yozish', 'Record') }}
      </button>
      <span v-if="refVal.certified !== null && refVal.certified !== undefined" class="text-sm ml-2" style="color:var(--lims-muted)">
        {{ L('Аттест.:', 'Attest.:', 'Certified:') }} <b style="color:var(--lims-text)">{{ refVal.certified }}</b>
        <span v-if="refVal.tolerance !== null && refVal.tolerance !== undefined"> ± {{ refVal.tolerance }}</span>
      </span>
    </div>

    <!-- Карта Шухарта -->
    <div v-if="sel.StandardID && sel.AnalyteID" class="lims-panel lims-panel-pad mb-4">
      <div class="panel-title" style="font-weight:700;margin-bottom:10px">{{ L('Контрольная карта Шухарта', 'Shewhart nazorat kartasi', 'Shewhart control chart') }}</div>
      <canvas ref="chartCanvas" height="90"></canvas>
      <div v-if="chart.center !== null" class="text-xs mt-1" style="color:var(--lims-muted)">
        {{ L('Центр', 'Markaz', 'Center') }}: {{ round(chart.center) }} · UCL: {{ round(chart.ucl) }} · LCL: {{ round(chart.lcl) }}
      </div>
    </div>

    <!-- Список измерений -->
    <div class="lims-panel" style="overflow-x:auto">
      <table class="lims-table">
        <thead>
          <tr>
            <th style="width:44px">№</th>
            <th style="width:150px">{{ L('Дата', 'Sana', 'Date') }}</th>
            <th>{{ L('Образец', 'Namuna', 'Standard') }}</th>
            <th>{{ L('Показатель', 'Ko\'rsatkich', 'Analyte') }}</th>
            <th style="width:90px">{{ L('Измер.', 'O\'lchov', 'Meas.') }}</th>
            <th style="width:90px">{{ L('Аттест.', 'Attest.', 'Cert.') }}</th>
            <th style="width:90px">{{ L('Откл.', 'Chetl.', 'Dev.') }}</th>
            <th style="width:100px">{{ L('В допуске', 'Ruxsatda', 'In tol.') }}</th>
            <th style="width:56px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(m, i) in measurements" :key="m.id">
            <td>{{ i + 1 }}</td>
            <td>{{ fmt(m.MeasuredAt) }}</td>
            <td>{{ isRu ? (m.StandardNameRus || m.StandardName) : m.StandardName }}</td>
            <td><b>{{ m.AnalyteSymbol }}</b></td>
            <td style="font-weight:700">{{ m.MeasuredValue }}</td>
            <td>{{ m.CertifiedValue ?? '—' }}</td>
            <td>{{ m.Deviation !== null ? round(m.Deviation) : '—' }}</td>
            <td>
              <span v-if="m.InTolerance !== null" class="chip" :class="Number(m.InTolerance) ? 'chip-tested' : 'chip-reject'">
                {{ Number(m.InTolerance) ? L('да', 'ha', 'yes') : L('нет', 'yo\'q', 'no') }}
              </span>
              <span v-else>—</span>
            </td>
            <td>
              <button v-if="canDelete" class="lims-iconbtn danger" @click="removeMeasurement(m)"><span class="material-icons">delete</span></button>
            </td>
          </tr>
          <tr v-if="!measurements.length"><td colspan="9" class="lims-empty">{{ L('Нет измерений', "O'lchov yo'q", 'No measurements') }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from "vue";
import axios from "axios";
import Chart from "chart.js/auto";
import "vuestic-ui/dist/vuestic-ui.css";
import { VaInput, VaSelect, VaButton, VaBadge, useToast } from "vuestic-ui";
import { useStore } from "vuex";
import { useLang } from "../../composables/useLang.js";

const { L, isRu, locale } = useLang();
const store = useStore();
const { init } = useToast();

const userRole = computed(() => store.state.user?.roles?.find((r) => r.name === "menu.lab_qc"));
const hasPermission = (perm) => Number(userRole.value?.pivot?.[perm]) === 1;
const canCreate = computed(() => hasPermission("create"));
const canDelete = computed(() => hasPermission("delete"));

const opt = reactive({ standards: [], analytes: [], methods: [] });
const sel = reactive({ StandardID: null, AnalyteID: null, MethodID: null });
const form = reactive({ MeasuredValue: "" });
const refVal = reactive({ certified: null, tolerance: null });

const measurements = ref([]);
const chart = reactive({ center: null, ucl: null, lcl: null, uwl: null, lwl: null });
const chartCanvas = ref(null);
let chartInstance = null;

const round = (v) => (v === null || v === undefined ? "—" : Math.round(v * 1000) / 1000);
const fmt = (v) => (v ? String(v).replace("T", " ").slice(0, 16) : "—");

const mapOpts = (arr) =>
  (Array.isArray(arr) ? arr : []).map((o) => ({
    value: o.id,
    text: (isRu.value ? o.NameRus || o.Name : o.Name) ?? String(o.id),
  }));

const loadOptions = async () => {
  const [s, a, m] = await Promise.all([
    axios.get("/lab/standards").catch(() => ({ data: [] })),
    axios.get("/lab/analytes").catch(() => ({ data: [] })),
    axios.get("/lab/methods").catch(() => ({ data: [] })),
  ]);
  opt.standards = (Array.isArray(s.data) ? s.data : []).map((o) => ({ value: o.id, text: o.Code || (isRu.value ? o.NameRus || o.Name : o.Name) }));
  opt.analytes = mapOpts(a.data);
  opt.methods = mapOpts(m.data);
};

const loadRef = async () => {
  refVal.certified = null;
  refVal.tolerance = null;
  if (!sel.StandardID || !sel.AnalyteID) return;
  try {
    const { data } = await axios.get(`/lab/standards/${sel.StandardID}/values`);
    const v = (data || []).find((x) => x.AnalyteID === sel.AnalyteID);
    if (v) {
      refVal.certified = v.CertifiedValue;
      refVal.tolerance = v.Uncertainty;
    }
  } catch (e) {
    /* ignore */
  }
};

const fetchMeasurements = async () => {
  try {
    const params = {};
    if (sel.StandardID) params.StandardID = sel.StandardID;
    if (sel.AnalyteID) params.AnalyteID = sel.AnalyteID;
    const { data } = await axios.get("/lab/qc", { params });
    measurements.value = Array.isArray(data) ? data : [];
  } catch (e) {
    console.error(e);
  }
};

const drawChart = async () => {
  if (!sel.StandardID || !sel.AnalyteID) return;
  let d;
  try {
    ({ data: d } = await axios.get("/lab/qc/chart", { params: { StandardID: sel.StandardID, AnalyteID: sel.AnalyteID } }));
  } catch (e) {
    return;
  }
  Object.assign(chart, { center: d.center, ucl: d.ucl, lcl: d.lcl, uwl: d.uwl, lwl: d.lwl });

  await nextTick();
  if (!chartCanvas.value) return;
  const tickColor = "#94a3b8";
  const gridColor = "rgba(148,163,184,0.15)";
  const labels = d.points.map((p) => p.x);
  const line = (val, color, dash) => ({
    label: "",
    data: labels.map(() => val),
    borderColor: color,
    borderDash: dash || [],
    borderWidth: 1,
    pointRadius: 0,
    fill: false,
  });
  const datasets = [
    {
      label: L("Измерено", "O'lchov", "Measured"),
      data: d.points.map((p) => p.y),
      borderColor: "#154EC1",
      backgroundColor: d.points.map((p) => (p.inTolerance === false ? "#dc2626" : "#154EC1")),
      pointBackgroundColor: d.points.map((p) => (p.inTolerance === false ? "#dc2626" : "#154EC1")),
      pointRadius: 4,
      tension: 0.1,
      fill: false,
    },
  ];
  if (d.center !== null) datasets.push({ ...line(d.center, "#16a34a"), label: L("Центр", "Markaz", "Center") });
  if (d.ucl !== null) datasets.push(line(d.ucl, "#dc2626"));
  if (d.lcl !== null) datasets.push(line(d.lcl, "#dc2626"));
  if (d.uwl !== null) datasets.push(line(d.uwl, "#f59e0b", [5, 5]));
  if (d.lwl !== null) datasets.push(line(d.lwl, "#f59e0b", [5, 5]));

  if (chartInstance) chartInstance.destroy();
  chartInstance = new Chart(chartCanvas.value, {
    type: "line",
    data: { labels, datasets },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { maxRotation: 45, minRotation: 0, color: tickColor }, grid: { color: gridColor } },
        y: { ticks: { color: tickColor }, grid: { color: gridColor } },
      },
    },
  });
};

const onContextChange = async () => {
  await loadRef();
  await fetchMeasurements();
  await drawChart();
};

const saveMeasurement = async () => {
  try {
    const { data } = await axios.post("/lab/qc/register", {
      StandardID: sel.StandardID,
      AnalyteID: sel.AnalyteID,
      MethodID: sel.MethodID,
      MeasuredValue: form.MeasuredValue,
    });
    if (data.status === 200) {
      form.MeasuredValue = "";
      init({ message: L("Записано", "Yozildi", "Recorded"), color: "success" });
      await onContextChange();
    }
  } catch (e) {
    init({ message: isRu.value ? "Ошибка" : "Xatolik", color: "danger" });
    console.error(e);
  }
};

const removeMeasurement = async (m) => {
  try {
    await axios.delete(`/lab/qc/${m.id}`);
    await onContextChange();
  } catch (e) {
    console.error(e);
  }
};

onMounted(() => {
  const savedLocale = localStorage.getItem("locale");
  if (savedLocale) locale.value = savedLocale;
  loadOptions();
  fetchMeasurements();
});
</script>
