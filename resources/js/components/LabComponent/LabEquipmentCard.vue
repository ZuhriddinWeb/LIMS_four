<template>
  <VaModal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    size="large"
    :ok-text="t('buttons.save')"
    :cancel-text="t('buttons.cancel')"
    @ok="onSubmit"
    @close="$emit('update:modelValue', false)"
    close-button
  >
    <h3 class="va-h3 mb-3">
      {{ form.id ? L('Карточка прибора', 'Asbob kartasi', 'Instrument card') : L('Новый прибор', 'Yangi asbob', 'New instrument') }}
      <VaBadge v-if="form.id && blocked" :text="L('ЗАБЛОКИРОВАН', 'BLOKLANGAN', 'BLOCKED')" color="danger" class="ml-2" />
    </h3>

    <!-- Основные поля -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <VaInput v-model="form.Name" :label="L('Наименование (uz)', 'Nomi (uz)', 'Name (uz)')" />
      <VaInput v-model="form.NameRus" :label="L('Наименование (ru)', 'Nomi (ru)', 'Name (ru)')" />
      <VaInput v-model="form.Model" :label="L('Модель', 'Model', 'Model')" />
      <VaInput v-model="form.SerialNumber" :label="L('Серийный номер', 'Seriya raqami', 'Serial number')" />
      <VaInput v-model="form.InventoryNo" :label="L('Инвентарный №', 'Inventar №', 'Inventory №')" />
      <VaSelect v-model="form.LaboratoryID" :options="opt.laboratories" text-by="text" value-by="value"
        :label="L('Лаборатория', 'Laboratoriya', 'Laboratory')" clearable />
      <VaInput v-model="form.Location" :label="L('Местоположение', 'Joylashuvi', 'Location')" />
      <VaInput v-model="form.Supplier" :label="L('Поставщик', 'Yetkazib beruvchi', 'Supplier')" />
      <VaInput v-model="form.CommissionDate" type="date" :label="L('Дата ввода', 'Ishga tushirilgan', 'Commission date')" />
      <VaSelect v-model="form.Status" :options="statusOptions" text-by="text" value-by="value"
        :label="L('Статус', 'Holat', 'Status')" />
      <VaInput v-model="form.VerificationDate" type="date" :label="L('Дата поверки/калибровки', 'Kalibrovka sanasi', 'Verification date')" />
      <VaInput v-model="form.VerificationDue" type="date" :label="L('Поверка до', 'Amal muddati', 'Verification due')"
        :class="{ 'overdue-field': overdue.calibration }" />
      <VaInput v-model="form.NextMaintenance" type="date" :label="L('Следующее ТО', 'Keyingi TX', 'Next maintenance')"
        :class="{ 'overdue-field': overdue.maintenance }" />
    </div>
    <div v-if="overdue.calibration || overdue.maintenance" class="text-sm text-red-600 mt-2">
      ⚠
      <span v-if="overdue.calibration">{{ L('Просрочена поверка/калибровка.', 'Kalibrovka muddati o\'tgan.', 'Verification/calibration overdue.') }}</span>
      <span v-if="overdue.maintenance"> {{ L('Просрочено ТО.', 'TX muddati o\'tgan.', 'Maintenance overdue.') }}</span>
    </div>

    <!-- История и файлы (только для сохранённого прибора) -->
    <template v-if="form.id">
      <!-- События -->
      <div class="mt-4">
        <div class="va-h6 mb-2">{{ L('История (ремонты, ТО, калибровка)', 'Tarix (tamir, TX, kalibrovka)', 'History (repairs, maintenance, calibration)') }}</div>
        <div class="flex flex-wrap items-end gap-2 mb-2">
          <VaSelect v-model="newEvent.EventType" :options="eventTypes" text-by="text" value-by="value"
            :label="L('Тип', 'Turi', 'Type')" class="w-40" />
          <VaInput v-model="newEvent.EventDate" type="date" :label="L('Дата', 'Sana', 'Date')" class="w-40" />
          <VaInput v-model="newEvent.NextDate" type="date" :label="L('След. дата', 'Keyingi', 'Next date')" class="w-40" />
          <VaInput v-model="newEvent.Description" :label="L('Описание', 'Tavsif', 'Description')" class="flex-grow" />
          <VaButton size="small" icon="add" @click="addEvent">{{ L('Добавить', "Qo'shish", 'Add') }}</VaButton>
        </div>
        <table class="lims-table">
          <thead>
            <tr><th style="width:112px">{{ L('Дата', 'Sana', 'Date') }}</th><th style="width:128px">{{ L('Тип', 'Turi', 'Type') }}</th><th>{{ L('Описание', 'Tavsif', 'Description') }}</th><th>{{ L('Исполнитель', 'Bajaruvchi', 'Performed by') }}</th></tr>
          </thead>
          <tbody>
            <tr v-for="e in events" :key="e.id">
              <td class="mono">{{ (e.EventDate || '').slice(0, 10) }}</td>
              <td><span class="chip chip-in_progress">{{ eventLabel(e.EventType) }}</span></td>
              <td>{{ e.Description }}</td>
              <td>{{ e.PerformedBy }}</td>
            </tr>
            <tr v-if="!events.length"><td colspan="4" class="lims-empty">—</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Файлы -->
      <div class="mt-4">
        <div class="va-h6 mb-2">{{ L('Файлы (сертификаты, акты, инструкции)', 'Fayllar (sertifikat, dalolatnoma, yo\'riqnoma)', 'Files (certificates, acts, manuals)') }}</div>
        <div class="flex flex-wrap items-center gap-2 mb-2">
          <VaSelect v-model="fileType" :options="fileTypes" text-by="text" value-by="value" class="w-44" />
          <input type="file" ref="fileInput" @change="onFilePick" />
        </div>
        <table class="lims-table">
          <tbody>
            <tr v-for="f in files" :key="f.id">
              <td style="width:130px"><span class="chip chip-utilized">{{ fileLabel(f.FileType) }}</span></td>
              <td>{{ f.OriginalName }}</td>
              <td class="mono" style="width:96px">{{ Math.round((f.Size || 0) / 1024) }} KB</td>
              <td style="width:112px;white-space:nowrap">
                <button class="lims-iconbtn" :title="L('Скачать', 'Yuklab olish', 'Download')" @click="downloadFile(f)"><span class="material-icons">download</span></button>
                <button class="lims-iconbtn danger" :title="L('Удалить', 'O\'chirish', 'Delete')" @click="deleteFile(f)"><span class="material-icons">delete</span></button>
              </td>
            </tr>
            <tr v-if="!files.length"><td colspan="4" class="lims-empty">—</td></tr>
          </tbody>
        </table>
      </div>
    </template>
  </VaModal>
</template>

<script setup>
import { ref, reactive, watch, computed } from "vue";
import axios from "axios";
import { VaModal, VaInput, VaSelect, VaButton, VaBadge, useToast } from "vuestic-ui";
import { useLang } from "../../composables/useLang.js";

const props = defineProps({ modelValue: Boolean, equipmentId: [Number, String, null] });
const emit = defineEmits(["update:modelValue", "saved"]);

const { L, isRu, t } = useLang();
const { init } = useToast();

const statusMap = {
  working: { ru: "Рабочее", uz: "Ishchi", en: "Working" },
  maintenance: { ru: "На обслуживании", uz: "Xizmatda", en: "Maintenance" },
  out_of_service: { ru: "Вне эксплуатации", uz: "Ishlamaydi", en: "Out of service" },
  awaiting_calibration: { ru: "Ожидает калибровки", uz: "Kalibrovka kutmoqda", en: "Awaiting calibration" },
};
const statusOptions = computed(() => Object.entries(statusMap).map(([k, v]) => ({ value: k, text: L(v.ru, v.uz, v.en) })));
const eventTypeMap = {
  calibration: { ru: "Калибровка", uz: "Kalibrovka", en: "Calibration" },
  verification: { ru: "Поверка", uz: "Tekshiruv", en: "Verification" },
  maintenance: { ru: "ТО", uz: "TX", en: "Maintenance" },
  repair: { ru: "Ремонт", uz: "Tamir", en: "Repair" },
  replacement: { ru: "Замена", uz: "Almashtirish", en: "Replacement" },
  note: { ru: "Примечание", uz: "Izoh", en: "Note" },
};
const eventTypes = computed(() => Object.entries(eventTypeMap).map(([k, v]) => ({ value: k, text: L(v.ru, v.uz, v.en) })));
const eventLabel = (k) => (eventTypeMap[k] ? L(eventTypeMap[k].ru, eventTypeMap[k].uz, eventTypeMap[k].en) : k);
const fileTypeMap = {
  certificate: { ru: "Сертификат", uz: "Sertifikat", en: "Certificate" },
  act: { ru: "Акт", uz: "Dalolatnoma", en: "Act" },
  manual: { ru: "Инструкция", uz: "Yo'riqnoma", en: "Manual" },
  other: { ru: "Прочее", uz: "Boshqa", en: "Other" },
};
const fileTypes = computed(() => Object.entries(fileTypeMap).map(([k, v]) => ({ value: k, text: L(v.ru, v.uz, v.en) })));
const fileLabel = (k) => (fileTypeMap[k] ? L(fileTypeMap[k].ru, fileTypeMap[k].uz, fileTypeMap[k].en) : k);

const blank = () => ({
  id: null, LaboratoryID: null, Code: "", Name: "", NameRus: "", Model: "", SerialNumber: "",
  Location: "", Supplier: "", InventoryNo: "", CommissionDate: "", Status: "working",
  VerificationDate: "", VerificationDue: "", NextMaintenance: "", Comment: "",
});
const form = reactive(blank());
const opt = reactive({ laboratories: [] });
const events = ref([]);
const files = ref([]);
const overdue = reactive({ calibration: false, maintenance: false });
const blocked = ref(false);
const newEvent = reactive({ EventType: "calibration", EventDate: "", NextDate: "", Description: "" });
const fileType = ref("certificate");
const fileInput = ref(null);

const loadOptions = async () => {
  const { data } = await axios.get("/lab/laboratories").catch(() => ({ data: [] }));
  opt.laboratories = (Array.isArray(data) ? data : []).map((o) => ({ value: o.id, text: isRu.value ? o.NameRus || o.Name : o.Name }));
};

const loadCard = async (id) => {
  const { data } = await axios.get(`/lab/equipment/${id}`);
  Object.keys(blank()).forEach((k) => (form[k] = data[k] != null ? (String(k).includes("Date") || k === "VerificationDue" ? String(data[k]).slice(0, 10) : data[k]) : blank()[k]));
  form.id = data.id;
  overdue.calibration = !!data.CalibrationOverdue;
  overdue.maintenance = !!data.MaintenanceOverdue;
  blocked.value = !!data.Blocked;
  events.value = data.events || [];
  files.value = data.files || [];
};

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      Object.assign(form, blank());
      events.value = [];
      files.value = [];
      overdue.calibration = overdue.maintenance = false;
      blocked.value = false;
      await loadOptions();
      if (props.equipmentId) await loadCard(props.equipmentId);
    }
  }
);

const onSubmit = async () => {
  try {
    let data;
    if (form.id) {
      ({ data } = await axios.put(`/lab/equipment/${form.id}`, form));
    } else {
      ({ data } = await axios.post("/lab/equipment/register", form));
    }
    if (data.status === 200) {
      init({ message: L("Сохранено", "Saqlandi", "Saved"), color: "success" });
      emit("saved");
      emit("update:modelValue", false);
    }
  } catch (e) {
    init({ message: L("Ошибка", "Xatolik", "Error"), color: "danger" });
    console.error(e);
  }
};

const addEvent = async () => {
  if (!form.id) return;
  try {
    await axios.post(`/lab/equipment/${form.id}/event`, { ...newEvent });
    Object.assign(newEvent, { EventType: "calibration", EventDate: "", NextDate: "", Description: "" });
    await loadCard(form.id);
    emit("saved");
    init({ message: L("Событие добавлено", "Qo'shildi", "Event added"), color: "success" });
  } catch (e) {
    console.error(e);
  }
};

const onFilePick = async (e) => {
  const file = e.target.files?.[0];
  if (!file || !form.id) return;
  const fd = new FormData();
  fd.append("file", file);
  fd.append("FileType", fileType.value);
  try {
    await axios.post(`/lab/equipment/${form.id}/file`, fd, { headers: { "Content-Type": "multipart/form-data" } });
    if (fileInput.value) fileInput.value.value = "";
    await loadCard(form.id);
    init({ message: L("Файл загружен", "Fayl yuklandi", "File uploaded"), color: "success" });
  } catch (err) {
    init({ message: L("Ошибка загрузки", "Yuklash xatosi", "Upload error"), color: "danger" });
    console.error(err);
  }
};

const downloadFile = (f) => {
  const base = axios.defaults.baseURL || "";
  window.open(`${base}/lab/equipment-file/${f.id}/download`, "_blank");
};

const deleteFile = async (f) => {
  try {
    await axios.delete(`/lab/equipment-file/${f.id}`);
    await loadCard(form.id);
  } catch (e) {
    console.error(e);
  }
};
</script>

<style scoped>
.overdue-field :deep(.va-input-wrapper__field) {
  border-color: #dc2626;
}
</style>
