<template>
  <div class="lims-page">
    <div class="lims-head">
      <div>
        <h1 class="lims-title">{{ L('Оборудование', 'Jihozlar', 'Equipment') }}</h1>
        <p class="lims-sub">{{ L('Приборы, статусы, поверка/ТО', 'Asboblar, holatlar, tekshiruv', 'Instruments, status, calibration/maintenance') }}</p>
      </div>
      <button v-if="canCreate" class="lims-btn" @click="openCreate"><span class="material-icons">add</span>{{ L('Добавить прибор', "Asbob qo'shish", 'Add instrument') }}</button>
    </div>

    <div class="lims-toolbar">
      <VaSelect v-model="filters.status" :options="statusOptions" text-by="text" value-by="value" :label="L('Статус', 'Holat', 'Status')" class="w-48" clearable />
      <VaSelect v-model="filters.LaboratoryID" :options="opt.laboratories" text-by="text" value-by="value" :label="L('Лаборатория', 'Laboratoriya', 'Laboratory')" class="w-52" clearable />
      <VaInput v-model="filters.q" :label="L('Поиск', 'Qidiruv', 'Search')" class="w-48" />
      <button class="lims-btn ghost" @click="fetchList"><span class="material-icons">search</span>{{ L('Показать', "Ko'rsatish", 'Show') }}</button>
    </div>

    <div class="lims-count">{{ L('Приборов', 'Asboblar', 'Instruments') }}: {{ items.length }}</div>

    <div class="lims-panel" style="overflow-x:auto">
      <table class="lims-table">
        <thead>
          <tr>
            <th style="width:44px">№</th>
            <th>{{ L('Прибор', 'Asbob', 'Instrument') }}</th>
            <th>{{ L('Модель / сер.№', 'Model / seriya', 'Model / serial') }}</th>
            <th>{{ L('Лаборатория', 'Laboratoriya', 'Laboratory') }}</th>
            <th style="width:170px">{{ L('Статус', 'Holat', 'Status') }}</th>
            <th style="width:130px">{{ L('Поверка до', 'Muddati', 'Verif. due') }}</th>
            <th style="width:90px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(m, i) in items" :key="m.id">
            <td>{{ i + 1 }}</td>
            <td>
              <span style="font-weight:600">{{ isRu ? (m.NameRus || m.Name) : m.Name }}</span>
              <span v-if="m.Blocked" class="chip chip-reject" style="margin-left:6px">{{ L('заблок.', 'blok', 'blocked') }}</span>
            </td>
            <td>{{ m.Model }} <span class="opacity-60 mono">{{ m.SerialNumber }}</span></td>
            <td>{{ isRu ? (m.LaboratoryNameRus || m.LaboratoryName) : m.LaboratoryName }}</td>
            <td><span class="chip" :class="statusChip(m.Status)">{{ statusLabel(m.Status) }}</span></td>
            <td :style="m.CalibrationOverdue ? 'color:#f43f5e;font-weight:700' : (m.CalibrationDueSoon ? 'color:#f59e0b;font-weight:700' : '')">
              {{ (m.VerificationDue || '').slice(0, 10) || '—' }}
              <div v-if="m.CalibrationOverdue" style="font-size:10px;color:#f43f5e">{{ L('просрочено', 'muddati o\'tgan', 'overdue') }}</div>
              <div v-else-if="m.CalibrationDueSoon" style="font-size:10px;color:#f59e0b">{{ L('через', 'qoldi', 'in') }} {{ m.DaysToVerification }} {{ L('дн.', 'kun', 'd') }}</div>
            </td>
            <td style="white-space:nowrap">
              <button class="lims-iconbtn" :title="L('Открыть', 'Ochish', 'Open')" @click="openEdit(m.id)"><span class="material-icons">visibility</span></button>
              <button v-if="canDelete" class="lims-iconbtn danger" :title="L('Удалить', 'O\'chirish', 'Delete')" @click="remove(m)"><span class="material-icons">delete</span></button>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="7" class="lims-empty">{{ L('Приборов нет', "Asbob yo'q", 'No instruments') }}</td></tr>
        </tbody>
      </table>
    </div>

    <LabEquipmentCard v-model="showCard" :equipment-id="activeId" @saved="fetchList" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import axios from "axios";
import "vuestic-ui/dist/vuestic-ui.css";
import { VaInput, VaSelect, useToast } from "vuestic-ui";
import { useStore } from "vuex";
import { useLang } from "../../composables/useLang.js";
import LabEquipmentCard from "../../components/LabComponent/LabEquipmentCard.vue";

const { L, isRu, locale } = useLang();
const store = useStore();
const { init } = useToast();

const userRole = computed(() => store.state.user?.roles?.find((r) => r.name === "menu.lab_instruments"));
const hasPermission = (perm) => Number(userRole.value?.pivot?.[perm]) === 1;
const canCreate = computed(() => hasPermission("create"));
const canDelete = computed(() => hasPermission("delete"));

const statusMap = {
  working: { ru: "Рабочее", uz: "Ishchi", en: "Working", chip: "chip-tested" },
  maintenance: { ru: "На обслуживании", uz: "Xizmatda", en: "Maintenance", chip: "chip-in_progress" },
  out_of_service: { ru: "Вне эксплуатации", uz: "Ishlamaydi", en: "Out of service", chip: "chip-reject" },
  awaiting_calibration: { ru: "Ожидает калибровки", uz: "Kalibrovka kutmoqda", en: "Awaiting calibration", chip: "chip-in_progress" },
};
const statusOptions = computed(() => Object.entries(statusMap).map(([k, v]) => ({ value: k, text: L(v.ru, v.uz, v.en) })));
const statusLabel = (s) => (statusMap[s] ? L(statusMap[s].ru, statusMap[s].uz, statusMap[s].en) : s);
const statusChip = (s) => statusMap[s]?.chip || "chip-utilized";

const filters = reactive({ status: null, LaboratoryID: null, q: "" });
const opt = reactive({ laboratories: [] });
const items = ref([]);
const showCard = ref(false);
const activeId = ref(null);

const loadOptions = async () => {
  const { data } = await axios.get("/lab/laboratories").catch(() => ({ data: [] }));
  opt.laboratories = (Array.isArray(data) ? data : []).map((o) => ({ value: o.id, text: isRu.value ? o.NameRus || o.Name : o.Name }));
};

const fetchList = async () => {
  try {
    const params = {};
    if (filters.status) params.status = filters.status;
    if (filters.LaboratoryID) params.LaboratoryID = filters.LaboratoryID;
    if (filters.q) params.q = filters.q;
    const { data } = await axios.get("/lab/equipment", { params });
    items.value = Array.isArray(data) ? data : [];
  } catch (e) { console.error(e); }
};

const openCreate = () => { activeId.value = null; showCard.value = true; };
const openEdit = (id) => { activeId.value = id; showCard.value = true; };
const remove = async (m) => {
  if (!confirm(L("Удалить прибор ", "Asbobni o'chirish ", "Delete instrument ") + (m.NameRus || m.Name) + "?")) return;
  try {
    await axios.delete(`/lab/equipment/${m.id}`);
    init({ message: L("Удалено", "O'chirildi", "Deleted"), color: "success" });
    fetchList();
  } catch (e) { console.error(e); }
};

onMounted(() => {
  const savedLocale = localStorage.getItem("locale");
  if (savedLocale) locale.value = savedLocale;
  loadOptions();
  fetchList();
});
</script>
