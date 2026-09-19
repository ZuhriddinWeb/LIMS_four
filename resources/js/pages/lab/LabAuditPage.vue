<template>
  <div class="lims-page">
    <div class="lims-head">
      <div>
        <h1 class="lims-title">
          <span class="material-icons" style="vertical-align:-4px;color:var(--lims-accent)">history</span>
          {{ L('Журнал аудита', 'Audit jurnali', 'Audit trail') }}
        </h1>
        <p class="lims-sub">{{ L('Кто, что и когда менял — целостность данных (ISO 17025)', "Kim, nima va qachon o'zgartirdi (ISO 17025)", 'Who changed what and when — data integrity (ISO 17025)') }}</p>
      </div>
      <button class="lims-btn ghost" @click="fetchData"><span class="material-icons">refresh</span>{{ L('Обновить', 'Yangilash', 'Refresh') }}</button>
    </div>

    <div class="lims-toolbar">
      <VaSelect v-model="filters.entity_type" :options="entityOptions" text-by="text" value-by="value" :label="L('Сущность', 'Obyekt', 'Entity')" class="w-52" clearable />
      <VaSelect v-model="filters.action" :options="actionOptions" text-by="text" value-by="value" :label="L('Действие', 'Amal', 'Action')" class="w-44" clearable />
      <VaInput v-model="filters.user" :label="L('Пользователь', 'Foydalanuvchi', 'User')" class="w-40" />
      <VaInput v-model="filters.from" type="date" :label="L('С', 'Dan', 'From')" class="w-40" />
      <VaInput v-model="filters.to" type="date" :label="L('По', 'Gacha', 'To')" class="w-40" />
      <VaInput v-model="filters.q" :label="L('Поиск', 'Qidiruv', 'Search')" class="w-40" />
      <button class="lims-btn" @click="fetchData"><span class="material-icons">search</span>{{ L('Показать', "Ko'rsatish", 'Show') }}</button>
    </div>

    <div class="lims-count">{{ L('Записей', 'Yozuvlar', 'Records') }}: {{ rows.length }}</div>

    <div class="lims-panel" style="overflow-x:auto">
      <table class="lims-table">
        <thead>
          <tr>
            <th style="width:150px">{{ L('Дата/время', 'Sana/vaqt', 'Date/time') }}</th>
            <th style="width:150px">{{ L('Пользователь', 'Foydalanuvchi', 'User') }}</th>
            <th style="width:140px">{{ L('Действие', 'Amal', 'Action') }}</th>
            <th style="width:210px">{{ L('Объект', 'Obyekt', 'Object') }}</th>
            <th>{{ L('Изменения', "O'zgarishlar", 'Changes') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r.id">
            <td class="mono">{{ fmt(r.CreatedAt) }}</td>
            <td>{{ r.UserName || '—' }}<div v-if="r.IP" class="mono" style="font-size:11px;opacity:.5">{{ r.IP }}</div></td>
            <td><span class="chip" :class="actionChip(r.Action)">{{ actionLabel(r.Action) }}</span></td>
            <td>
              <div style="font-weight:600">{{ entityLabel(r.EntityType) }}</div>
              <div class="mono" style="font-size:12px;opacity:.7">{{ r.EntityLabel || ('#' + r.EntityID) }}</div>
            </td>
            <td>
              <div v-if="r.Changes && Object.keys(r.Changes).length" class="chg">
                <div v-for="(v, f) in r.Changes" :key="f" class="chg-row">
                  <span class="chg-field">{{ f }}</span>
                  <span class="chg-old">{{ fmtVal(v.old) }}</span>
                  <span class="material-icons chg-arrow">arrow_forward</span>
                  <span class="chg-new">{{ fmtVal(v.new) }}</span>
                </div>
              </div>
              <span v-else style="opacity:.5">—</span>
            </td>
          </tr>
          <tr v-if="!rows.length"><td colspan="5" class="lims-empty">{{ L('Записей нет', "Yozuvlar yo'q", 'No records') }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import axios from "axios";
import "vuestic-ui/dist/vuestic-ui.css";
import { VaInput, VaSelect } from "vuestic-ui";
import { useLang } from "../../composables/useLang.js";

const { L, locale } = useLang();

const rows = ref([]);
const filters = reactive({ entity_type: null, action: null, user: "", from: "", to: "", q: "" });
const meta = reactive({ entityTypes: [], actions: [] });

// Человекочитаемые названия сущностей.
const entityMap = {
  LabSample: { ru: "Проба", uz: "Namuna", en: "Sample" },
  LabSampleDetermination: { ru: "Определение", uz: "Aniqlash", en: "Determination" },
  LabCertificate: { ru: "Паспорт качества", uz: "Sifat pasporti", en: "Certificate" },
  LabCertificateItem: { ru: "Строка паспорта", uz: "Pasport qatori", en: "Certificate item" },
  LabQcMeasurement: { ru: "Измерение ВЛК", uz: "IQN o'lchovi", en: "QC measurement" },
  LabStandard: { ru: "Стандартный образец", uz: "Standart namuna", en: "Reference standard" },
  LabStandardValue: { ru: "Значение СО", uz: "SN qiymati", en: "Standard value" },
  LabInstrument: { ru: "Прибор", uz: "Asbob", en: "Instrument" },
  LabInstrumentEvent: { ru: "Событие прибора", uz: "Asbob hodisasi", en: "Instrument event" },
  LabInstrumentFile: { ru: "Файл прибора", uz: "Asbob fayli", en: "Instrument file" },
  LabLaboratory: { ru: "Лаборатория", uz: "Laboratoriya", en: "Laboratory" },
  LabGroup: { ru: "Группа", uz: "Guruh", en: "Group" },
  LabAnalyte: { ru: "Показатель", uz: "Ko'rsatkich", en: "Analyte" },
  LabMethod: { ru: "Методика", uz: "Metodika", en: "Method" },
  LabProduct: { ru: "Продукция", uz: "Mahsulot", en: "Product" },
  LabDepartment: { ru: "Подразделение", uz: "Bo'linma", en: "Department" },
  LabSamplePoint: { ru: "Точка отбора", uz: "Namuna nuqtasi", en: "Sampling point" },
  LabSampleType: { ru: "Тип пробы", uz: "Namuna turi", en: "Sample type" },
};
const entityLabel = (t) => (entityMap[t] ? L(entityMap[t].ru, entityMap[t].uz, entityMap[t].en) : t);

// Действия.
const actionMap = {
  created: { ru: "Создание", uz: "Yaratish", en: "Created", chip: "chip-tested" },
  updated: { ru: "Изменение", uz: "O'zgartirish", en: "Updated", chip: "chip-in_progress" },
  deleted: { ru: "Удаление", uz: "O'chirish", en: "Deleted", chip: "chip-reject" },
  force_deleted: { ru: "Физ. удаление", uz: "To'liq o'chirish", en: "Force deleted", chip: "chip-reject" },
  restored: { ru: "Восстановление", uz: "Tiklash", en: "Restored", chip: "chip-new" },
  status_changed: { ru: "Смена статуса", uz: "Holat o'zgardi", en: "Status changed", chip: "chip-in_progress" },
  result_entered: { ru: "Ввод результата", uz: "Natija kiritildi", en: "Result entered", chip: "chip-new" },
  result_reviewed: { ru: "Проверка", uz: "Tekshirildi", en: "Reviewed", chip: "chip-in_progress" },
  result_approved: { ru: "Утверждение", uz: "Tasdiqlandi", en: "Approved", chip: "chip-approved" },
};
const actionLabel = (a) => (actionMap[a] ? L(actionMap[a].ru, actionMap[a].uz, actionMap[a].en) : a);
const actionChip = (a) => actionMap[a]?.chip || "chip-utilized";

const entityOptions = computed(() =>
  (meta.entityTypes || []).map((t) => ({ value: t, text: entityLabel(t) }))
);
const actionOptions = computed(() =>
  (meta.actions || []).map((a) => ({ value: a, text: actionLabel(a) }))
);

const fmt = (v) => (v ? String(v).replace("T", " ").slice(0, 19) : "—");
const fmtVal = (v) => {
  if (v === null || v === undefined || v === "") return "∅";
  const s = String(v);
  return s.length > 60 ? s.slice(0, 60) + "…" : s;
};

const loadMeta = async () => {
  try {
    const { data } = await axios.get("/lab/audit/meta");
    meta.entityTypes = data.entityTypes || [];
    meta.actions = data.actions || [];
  } catch (e) { console.error(e); }
};

const fetchData = async () => {
  try {
    const params = {};
    Object.entries(filters).forEach(([k, v]) => { if (v) params[k] = v; });
    const { data } = await axios.get("/lab/audit", { params });
    rows.value = Array.isArray(data) ? data : [];
  } catch (e) { console.error(e); }
};

onMounted(() => {
  const savedLocale = localStorage.getItem("locale");
  if (savedLocale) locale.value = savedLocale;
  loadMeta();
  fetchData();
});
</script>

<style scoped>
.chg { display: flex; flex-direction: column; gap: 3px; }
.chg-row { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; font-size: 12.5px; }
.chg-field { font-weight: 700; color: var(--lims-title); min-width: 90px; }
.chg-old { color: #f43f5e; text-decoration: line-through; opacity: .8; font-family: ui-monospace, monospace; }
.chg-new { color: #22c55e; font-family: ui-monospace, monospace; }
.chg-arrow { font-size: 14px; opacity: .5; }
</style>
