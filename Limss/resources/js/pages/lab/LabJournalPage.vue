<template>
  <div class="lims-page">
    <div class="lims-head">
      <div>
        <h1 class="lims-title">{{ L('Журнал КХА', 'KHA jurnali', 'CQA journal') }}</h1>
        <p class="lims-sub">{{ L('Сводный / пометодный реестр определений', "Aniqlashlar reestri", 'Determinations register') }}</p>
      </div>
      <button class="lims-btn ghost" @click="exportExcel"><span class="material-icons">download</span>Excel</button>
    </div>

    <!-- Фильтры -->
    <div class="lims-toolbar">
      <VaInput v-model.number="filters.year" type="number" :label="L('Год', 'Yil', 'Year')" class="w-24" />
      <VaSelect v-model="filters.month" :options="monthOptions" text-by="text" value-by="value" :label="L('Месяц', 'Oy', 'Month')" class="w-40" />
      <VaSelect v-model="filters.methodId" :options="opt.methods" text-by="text" value-by="value" :label="L('Методика', 'Metodika', 'Method')" class="w-48" clearable />
      <VaSelect v-model="filters.executorGroupId" :options="opt.groups" text-by="text" value-by="value" :label="L('Группа-исполнитель', 'Bajaruvchi guruh', 'Executor group')" class="w-48" clearable />
      <VaSelect v-model="filters.status" :options="statusOptions" text-by="text" value-by="value" :label="L('Статус', 'Holat', 'Status')" class="w-40" clearable />
      <VaInput v-model="filters.q" :label="L('Шифр', 'Shifr', 'Code')" class="w-40" />
      <button class="lims-btn ghost" @click="fetchJournal"><span class="material-icons">search</span>{{ L('Показать', "Ko'rsatish", 'Show') }}</button>
    </div>

    <div class="lims-count">{{ L('Записей', 'Yozuvlar', 'Records') }}: {{ rows.length }}</div>

    <div class="lims-panel" style="overflow-x:auto">
      <table class="lims-table">
        <thead>
          <tr>
            <th style="width:44px">№</th>
            <th style="width:140px">{{ L('Дата', 'Sana', 'Date') }}</th>
            <th>{{ L('Шифр', 'Shifr', 'Code') }}</th>
            <th>{{ L('Подразделение', "Bo'linma", 'Department') }}</th>
            <th>{{ L('Элемент', 'Element', 'Element') }}</th>
            <th>{{ L('Методика', 'Metodika', 'Method') }}</th>
            <th>{{ L('Исполнитель', 'Bajaruvchi', 'Executor') }}</th>
            <th style="width:100px">{{ L('Результат', 'Natija', 'Result') }}</th>
            <th style="width:60px">{{ L('Ед.', 'Birlik', 'Unit') }}</th>
            <th style="width:150px">{{ L('Дата рез-та', 'Natija sanasi', 'Result date') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(r, i) in rows" :key="r.id">
            <td>{{ i + 1 }}</td>
            <td>{{ fmt(r.RegisteredAt) }}</td>
            <td class="mono">{{ r.SampleCode }}</td>
            <td>{{ isRu ? (r.DepartmentNameRus || r.DepartmentName) : r.DepartmentName }}</td>
            <td><b>{{ r.AnalyteSymbol }}</b> {{ isRu ? (r.AnalyteNameRus || r.AnalyteName) : r.AnalyteName }}</td>
            <td>{{ isRu ? (r.MethodNameRus || r.MethodName) : r.MethodName }}</td>
            <td>{{ isRu ? (r.ExecutorGroupNameRus || r.ExecutorGroupName) : r.ExecutorGroupName }}</td>
            <td style="font-weight:700">{{ r.ResultValue || '—' }}</td>
            <td>{{ r.Unit }}</td>
            <td>{{ r.ResultAt ? fmt(r.ResultAt) : '—' }}</td>
          </tr>
          <tr v-if="!rows.length"><td colspan="10" class="lims-empty">{{ L('Нет записей', "Yozuv yo'q", 'No records') }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import axios from "axios";
import * as XLSX from "xlsx";
import "vuestic-ui/dist/vuestic-ui.css";
import { VaInput, VaSelect, useToast } from "vuestic-ui";
import { useLang } from "../../composables/useLang.js";

const { L, isRu, locale } = useLang();
const { init } = useToast();

const now = new Date();
const filters = reactive({ year: now.getFullYear(), month: now.getMonth() + 1, methodId: null, executorGroupId: null, status: null, q: "" });

const monthNames = {
  ru: ["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"],
  uz: ["Yanvar","Fevral","Mart","Aprel","May","Iyun","Iyul","Avgust","Sentabr","Oktabr","Noyabr","Dekabr"],
  en: ["January","February","March","April","May","June","July","August","September","October","November","December"],
};
const monthOptions = computed(() => [
  { value: null, text: L("Все месяцы", "Barcha oylar", "All months") },
  ...Array.from({ length: 12 }, (_, i) => ({ value: i + 1, text: L(monthNames.ru, monthNames.uz, monthNames.en)[i] })),
]);
const statusOptions = computed(() => [
  { value: "pending", text: L("Ожидает", "Kutilmoqda", "Pending") },
  { value: "done", text: L("Готово", "Tayyor", "Done") },
]);
const fmt = (v) => (v ? String(v).replace("T", " ").slice(0, 16) : "—");

const opt = reactive({ methods: [], groups: [] });
const rows = ref([]);

const mapOpts = (arr) =>
  (Array.isArray(arr) ? arr : []).map((o) => ({ value: o.id, text: (isRu.value ? o.NameRus || o.Name : o.Name) ?? String(o.id) }));

const loadOptions = async () => {
  const [m, g] = await Promise.all([
    axios.get("/lab/methods").catch(() => ({ data: [] })),
    axios.get("/lab/groups").catch(() => ({ data: [] })),
  ]);
  opt.methods = mapOpts(m.data);
  opt.groups = mapOpts(g.data);
};

const fetchJournal = async () => {
  try {
    const params = {};
    for (const k of ["year", "month", "methodId", "executorGroupId", "status", "q"]) if (filters[k]) params[k] = filters[k];
    const { data } = await axios.get("/lab/journal", { params });
    rows.value = Array.isArray(data) ? data : [];
  } catch (e) { console.error(e); }
};

const exportExcel = () => {
  try {
    const data = rows.value.map((r, i) => ({
      "№": i + 1,
      [L("Дата", "Sana", "Date")]: fmt(r.RegisteredAt),
      [L("Шифр", "Shifr", "Code")]: r.SampleCode,
      [L("Подразделение", "Bo'linma", "Department")]: isRu.value ? r.DepartmentNameRus || r.DepartmentName : r.DepartmentName,
      [L("Элемент", "Element", "Element")]: (r.AnalyteSymbol || "") + " " + (isRu.value ? r.AnalyteNameRus || r.AnalyteName || "" : r.AnalyteName || ""),
      [L("Методика", "Metodika", "Method")]: isRu.value ? r.MethodNameRus || r.MethodName : r.MethodName,
      [L("Исполнитель", "Bajaruvchi", "Executor")]: isRu.value ? r.ExecutorGroupNameRus || r.ExecutorGroupName : r.ExecutorGroupName,
      [L("Результат", "Natija", "Result")]: r.ResultValue,
      [L("Ед.", "Birlik", "Unit")]: r.Unit,
      [L("Дата рез-та", "Natija sanasi", "Result date")]: r.ResultAt ? fmt(r.ResultAt) : "",
    }));
    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "KHA");
    XLSX.writeFile(wb, `KHA_${filters.year}_${filters.month || "all"}.xlsx`);
  } catch (e) {
    init({ message: L("Ошибка экспорта", "Eksport xatosi", "Export error"), color: "danger" });
    console.error(e);
  }
};

onMounted(() => {
  const savedLocale = localStorage.getItem("locale");
  if (savedLocale) locale.value = savedLocale;
  loadOptions();
  fetchJournal();
});
</script>
