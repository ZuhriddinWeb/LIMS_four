<template>
  <div class="lims-page">
    <div class="lims-head">
      <div>
        <h1 class="lims-title">
          <span class="material-icons" style="vertical-align:-4px;color:var(--lims-accent)">table_chart</span>
          {{ L('Технологический контроль', 'Texnologik nazorat', 'Process control') }}
        </h1>
        <p class="lims-sub">{{ L('Сменный журнал: анализы проб техпроцесса по точкам и времени', 'Smenali jurnal: nuqta va vaqt bo\'yicha', 'Shift journal: process-sample analyses by point and time') }}</p>
      </div>
      <button v-if="canUpdate" class="lims-btn" @click="save" :disabled="!filters.PointID || !columns.length"><span class="material-icons">save</span>{{ L('Сохранить журнал', 'Saqlash', 'Save journal') }}</button>
    </div>

    <div class="lims-toolbar">
      <VaSelect v-model="filters.PointID" :options="opt.points" text-by="text" value-by="value" :label="L('Точка отбора', 'Namuna nuqtasi', 'Sampling point')" class="w-56" @update:model-value="fetchGrid" />
      <VaInput v-model="filters.MeasureDate" type="date" :label="L('Дата', 'Sana', 'Date')" class="w-40" @update:model-value="fetchGrid" />
      <VaSelect v-model="filters.ShiftNo" :options="shiftOptions" text-by="text" value-by="value" :label="L('Смена', 'Smena', 'Shift')" class="w-32" clearable />
      <div class="flex items-end gap-2">
        <VaSelect v-model="pickAnalyte" :options="availableAnalytes" text-by="text" value-by="value" :label="L('Добавить показатель', 'Ko\'rsatkich qo\'shish', 'Add analyte')" class="w-52" clearable />
        <button class="lims-btn ghost" @click="addColumn" :disabled="!pickAnalyte"><span class="material-icons">add</span>{{ L('Столбец', 'Ustun', 'Column') }}</button>
      </div>
    </div>

    <div v-if="!filters.PointID" class="lims-empty" style="margin-top:24px">{{ L('Выберите точку отбора и дату.', 'Nuqta va sanani tanlang.', 'Select a sampling point and date.') }}</div>
    <div v-else-if="!columns.length" class="lims-empty" style="margin-top:24px">{{ L('Добавьте показатели (столбцы) для ввода.', 'Ko\'rsatkichlar (ustunlar) qo\'shing.', 'Add analytes (columns) to enter data.') }}</div>

    <div v-else class="lims-panel" style="overflow-x:auto">
      <table class="lims-table">
        <thead>
          <tr>
            <th style="width:110px">{{ L('Время', 'Vaqt', 'Time') }}</th>
            <th v-for="c in columns" :key="c.AnalyteID" style="min-width:110px">
              <div style="display:flex;align-items:center;gap:6px;justify-content:space-between">
                <span><b>{{ c.symbol || c.name }}</b><span v-if="c.unit" class="opacity-60">, {{ c.unit }}</span></span>
                <button class="lims-iconbtn danger" :title="L('Убрать столбец', 'Ustunni olib tashlash', 'Remove column')" @click="removeColumn(c.AnalyteID)"><span class="material-icons" style="font-size:16px">close</span></button>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, ri) in rows" :key="ri">
            <td><input class="proc-in time" v-model="row.slot" placeholder="08-00" /></td>
            <td v-for="c in columns" :key="c.AnalyteID">
              <input class="proc-in" v-model="row.values[c.AnalyteID]" inputmode="decimal" />
            </td>
          </tr>
          <tr>
            <td colspan="99" style="padding:6px 8px">
              <button class="lims-btn ghost" @click="addSlot"><span class="material-icons">add</span>{{ L('Добавить время', 'Vaqt qo\'shish', 'Add time') }}</button>
            </td>
          </tr>
          <tr class="avg-row">
            <td><b>{{ L('Среднее', 'O\'rtacha', 'Average') }}</b></td>
            <td v-for="c in columns" :key="c.AnalyteID" class="mono"><b>{{ avg(c.AnalyteID) }}</b></td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-if="columns.length" class="text-xs opacity-60 mt-2">
      {{ L('Пустая ячейка очищает значение при сохранении. Среднее считается по заполненным ячейкам столбца.',
           'Bo\'sh katak saqlashda qiymatni tozalaydi. O\'rtacha to\'ldirilgan kataklar bo\'yicha.',
           'An empty cell clears the value on save. The average is over filled cells of the column.') }}
    </p>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import axios from "axios";
import "vuestic-ui/dist/vuestic-ui.css";
import { VaInput, VaSelect, useToast } from "vuestic-ui";
import { useStore } from "vuex";
import { useLang } from "../../composables/useLang.js";

const { L, isRu, locale } = useLang();
const store = useStore();
const { init } = useToast();

const userRole = computed(() => store.state.user?.roles?.find((r) => r.name === "menu.lab_process"));
const canUpdate = computed(() => Number(userRole.value?.pivot?.update) === 1);

const today = new Date().toISOString().slice(0, 10);
const filters = reactive({ PointID: null, MeasureDate: today, ShiftNo: null });
const opt = reactive({ points: [], analytes: [] });
const columns = ref([]); // [{AnalyteID, symbol, name, unit}]
const rows = ref([]);     // [{slot, values:{analyteId:value}}]
const pickAnalyte = ref(null);

const shiftOptions = computed(() => [1, 2, 3, 4].map((n) => ({ value: n, text: L("Смена ", "Smena ", "Shift ") + n })));
const defaultSlots = ["08-00", "12-00", "16-00", "20-00", "00-00", "04-00"];

const analyteMap = computed(() => Object.fromEntries(opt.analytes.map((a) => [a.value, a])));
const availableAnalytes = computed(() => opt.analytes.filter((a) => !columns.value.some((c) => c.AnalyteID === a.value)));

const mapOpts = (arr, extra) => (Array.isArray(arr) ? arr : []).map((o) => ({
  value: o.id, text: (isRu.value ? o.NameRus || o.Name : o.Name) ?? String(o.id), ...extra(o),
}));

const loadOptions = async () => {
  const [p, a] = await Promise.all([
    axios.get("/lab/points").catch(() => ({ data: [] })),
    axios.get("/lab/analytes").catch(() => ({ data: [] })),
  ]);
  opt.points = mapOpts(p.data, () => ({}));
  opt.analytes = mapOpts(a.data, (o) => ({ symbol: o.Symbol, unit: o.Unit }));
};

const blankRows = () => defaultSlots.map((s) => ({ slot: s, values: {} }));

const addColumn = () => {
  const a = analyteMap.value[pickAnalyte.value];
  if (!a) return;
  columns.value.push({ AnalyteID: a.value, symbol: a.symbol, name: a.text, unit: a.unit || "" });
  pickAnalyte.value = null;
};
const removeColumn = (aid) => { columns.value = columns.value.filter((c) => c.AnalyteID !== aid); };
const addSlot = () => rows.value.push({ slot: "", values: {} });

const avg = (aid) => {
  const nums = rows.value.map((r) => parseFloat(String(r.values[aid] ?? "").replace(",", "."))).filter((v) => !isNaN(v));
  if (!nums.length) return "—";
  return Math.round((nums.reduce((s, v) => s + v, 0) / nums.length) * 1000) / 1000;
};

const fetchGrid = async () => {
  if (!filters.PointID || !filters.MeasureDate) return;
  try {
    const { data } = await axios.get("/lab/process", { params: { PointID: filters.PointID, MeasureDate: filters.MeasureDate } });
    const recs = Array.isArray(data) ? data : [];
    // столбцы — из встреченных показателей (сохраняем уже добавленные вручную)
    const present = [...new Set(recs.map((r) => r.AnalyteID))];
    present.forEach((aid) => {
      if (!columns.value.some((c) => c.AnalyteID === aid)) {
        const a = analyteMap.value[aid];
        if (a) columns.value.push({ AnalyteID: aid, symbol: a.symbol, name: a.text, unit: a.unit || "" });
      }
    });
    // строки — объединяем стандартные слоты и встреченные
    const slots = [...new Set([...defaultSlots, ...recs.map((r) => r.TimeSlot).filter(Boolean)])];
    rows.value = slots.map((s) => ({ slot: s, values: {} }));
    recs.forEach((r) => {
      let row = rows.value.find((x) => x.slot === r.TimeSlot);
      if (!row) { row = { slot: r.TimeSlot || "", values: {} }; rows.value.push(row); }
      row.values[r.AnalyteID] = r.Value;
    });
  } catch (e) { console.error(e); }
};

const save = async () => {
  const payloadRows = [];
  rows.value.forEach((r) => {
    if (!r.slot) return;
    columns.value.forEach((c) => {
      payloadRows.push({ AnalyteID: c.AnalyteID, TimeSlot: r.slot, Value: r.values[c.AnalyteID] ?? "", Unit: c.unit });
    });
  });
  try {
    const { data } = await axios.post("/lab/process/bulk", {
      PointID: filters.PointID, MeasureDate: filters.MeasureDate, ShiftNo: filters.ShiftNo, rows: payloadRows,
    });
    init({ message: L("Журнал сохранён", "Jurnal saqlandi", "Journal saved") + " (" + (data.saved ?? 0) + ")", color: "success" });
    await fetchGrid();
  } catch (e) {
    init({ message: e?.response?.data?.message || L("Ошибка", "Xatolik", "Error"), color: "danger" });
  }
};

onMounted(() => {
  const savedLocale = localStorage.getItem("locale");
  if (savedLocale) locale.value = savedLocale;
  loadOptions();
  rows.value = blankRows();
});
</script>

<style scoped>
.proc-in { width: 100%; border: 1px solid var(--lims-line); border-radius: 6px; padding: 5px 7px; background: transparent; color: var(--lims-text); font-size: 13px; }
.proc-in:focus { outline: none; border-color: var(--lims-accent); }
.proc-in.time { font-family: ui-monospace, monospace; text-align: center; }
.avg-row td { background: rgba(34,211,238,0.08); }
</style>
