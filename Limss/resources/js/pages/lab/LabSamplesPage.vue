<template>
  <div class="lims-page">
    <div class="lims-head">
      <div>
        <h1 class="lims-title">{{ L('Пробы (журнал)', 'Namunalar (jurnal)', 'Samples (journal)') }}</h1>
        <p class="lims-sub">{{ L('Регистрация и ведение проб по сменам', 'Smenalar bo\'yicha namunalarni ro\'yxatga olish', 'Register and track samples by shift') }}</p>
      </div>
      <button v-if="canCreate" class="lims-btn" @click="showRegister = true">
        <span class="material-icons">add</span>{{ L('Регистрация пробы', "Namuna ro'yxatga olish", 'Register sample') }}
      </button>
    </div>

    <!-- Фильтры -->
    <div class="lims-toolbar">
      <VaInput v-model.number="filters.year" type="number" :label="L('Год', 'Yil', 'Year')" class="w-24" />
      <VaSelect v-model="filters.month" :options="monthOptions" text-by="text" value-by="value" :label="L('Месяц', 'Oy', 'Month')" class="w-40" />
      <VaSelect v-model="filters.status" :options="statusOptions" text-by="text" value-by="value" :label="L('Статус', 'Holat', 'Status')" class="w-48" clearable />
      <VaInput v-model="filters.q" :label="L('Поиск (шифр/документ)', 'Qidiruv', 'Search (code/doc)')" class="w-56" />
      <button class="lims-btn ghost" @click="fetchSamples"><span class="material-icons">search</span>{{ L('Показать', "Ko'rsatish", 'Show') }}</button>
    </div>

    <div class="lims-count">{{ L('Проб', 'Namuna', 'Samples') }}: {{ samples.length }}</div>

    <!-- Таблица -->
    <div class="lims-panel">
      <table class="lims-table">
        <thead>
          <tr>
            <th style="width:44px">№</th>
            <th>{{ L('Шифр', 'Shifr', 'Code') }}</th>
            <th style="width:150px">{{ L('Дата', 'Sana', 'Date') }}</th>
            <th>{{ L('Подразделение', "Bo'linma", 'Department') }}</th>
            <th>{{ L('Тип', 'Turi', 'Type') }}</th>
            <th>{{ L('Заказчик', 'Buyurtmachi', 'Customer') }}</th>
            <th style="width:120px">{{ L('Статус', 'Holat', 'Status') }}</th>
            <th style="width:90px">{{ L('Готово', 'Tayyor', 'Done') }}</th>
            <th style="width:110px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(s, i) in samples" :key="s.id">
            <td>{{ i + 1 }}</td>
            <td class="mono">{{ s.SampleCode }}</td>
            <td>{{ fmt(s.RegisteredAt) }}</td>
            <td>{{ isRu ? (s.DepartmentNameRus || s.DepartmentName) : s.DepartmentName }}</td>
            <td>{{ isRu ? (s.SampleTypeNameRus || s.SampleTypeName) : s.SampleTypeName }}</td>
            <td>{{ isRu ? (s.CustomerGroupNameRus || s.CustomerGroupName) : s.CustomerGroupName }}</td>
            <td><span class="chip" :class="'chip-' + s.Status">{{ statusLabel(s.Status) }}</span></td>
            <td>{{ s.DeterminationsDone }}/{{ s.DeterminationsTotal }}</td>
            <td style="white-space:nowrap">
              <button class="lims-iconbtn" :title="L('Открыть', 'Ochish', 'Open')" @click="openCard(s.id)"><span class="material-icons">visibility</span></button>
              <button v-if="canDelete" class="lims-iconbtn danger" :title="L('Удалить', 'O\'chirish', 'Delete')" @click="removeSample(s)"><span class="material-icons">delete</span></button>
            </td>
          </tr>
          <tr v-if="!samples.length"><td colspan="9" class="lims-empty">{{ L('Проб не найдено', 'Namuna topilmadi', 'No samples found') }}</td></tr>
        </tbody>
      </table>
    </div>

    <LabSampleRegister v-model="showRegister" @saved="fetchSamples" />
    <LabSampleCard v-model="showCard" :sample-id="activeSampleId" @changed="fetchSamples" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import axios from "axios";
import "vuestic-ui/dist/vuestic-ui.css";
import { VaInput, VaSelect, useToast } from "vuestic-ui";
import { useStore } from "vuex";
import { useLang } from "../../composables/useLang.js";
import LabSampleRegister from "../../components/LabComponent/LabSampleRegister.vue";
import LabSampleCard from "../../components/LabComponent/LabSampleCard.vue";

const { L, isRu, locale } = useLang();
const store = useStore();
const { init } = useToast();

const userRole = computed(() => store.state.user?.roles?.find((r) => r.name === "menu.lab_samples"));
const hasPermission = (perm) => Number(userRole.value?.pivot?.[perm]) === 1;
const canCreate = computed(() => hasPermission("create"));
const canDelete = computed(() => hasPermission("delete"));

const now = new Date();
const filters = reactive({ year: now.getFullYear(), month: now.getMonth() + 1, status: null, q: "" });

const monthNames = {
  ru: ["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"],
  uz: ["Yanvar","Fevral","Mart","Aprel","May","Iyun","Iyul","Avgust","Sentabr","Oktabr","Noyabr","Dekabr"],
  en: ["January","February","March","April","May","June","July","August","September","October","November","December"],
};
const monthOptions = computed(() => [
  { value: null, text: L("Все месяцы", "Barcha oylar", "All months") },
  ...Array.from({ length: 12 }, (_, i) => ({ value: i + 1, text: L(monthNames.ru, monthNames.uz, monthNames.en)[i] })),
]);

const statuses = {
  new: { ru: "Новый", uz: "Yangi", en: "New" },
  in_progress: { ru: "В процессе", uz: "Jarayonda", en: "In progress" },
  tested: { ru: "Испытан", uz: "Sinaldi", en: "Tested" },
  reject: { ru: "Брак", uz: "Brak", en: "Reject" },
  utilized: { ru: "Утилизирован", uz: "Utilizatsiya", en: "Utilized" },
};
const statusOptions = computed(() => Object.entries(statuses).map(([k, v]) => ({ value: k, text: L(v.ru, v.uz, v.en) })));
const statusLabel = (s) => (statuses[s] ? L(statuses[s].ru, statuses[s].uz, statuses[s].en) : s);
const fmt = (v) => (v ? String(v).replace("T", " ").slice(0, 16) : "—");

const samples = ref([]);
const showRegister = ref(false);
const showCard = ref(false);
const activeSampleId = ref(null);

const fetchSamples = async () => {
  try {
    const params = {};
    if (filters.year) params.year = filters.year;
    if (filters.month) params.month = filters.month;
    if (filters.status) params.status = filters.status;
    if (filters.q) params.q = filters.q;
    const { data } = await axios.get("/lab/samples", { params });
    samples.value = Array.isArray(data) ? data : [];
  } catch (e) {
    console.error(e);
  }
};

const openCard = (id) => { activeSampleId.value = id; showCard.value = true; };

const removeSample = async (s) => {
  if (!confirm(L("Удалить пробу ", "Namunani o'chirish ", "Delete sample ") + s.SampleCode + "?")) return;
  try {
    await axios.delete(`/lab/samples/${s.id}`);
    init({ message: L("Удалено", "O'chirildi", "Deleted"), color: "success" });
    fetchSamples();
  } catch (e) { console.error(e); }
};

onMounted(() => {
  const savedLocale = localStorage.getItem("locale");
  if (savedLocale) locale.value = savedLocale;
  fetchSamples();
});
</script>
