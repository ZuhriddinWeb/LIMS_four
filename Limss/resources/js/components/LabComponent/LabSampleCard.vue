<template>
  <VaModal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    size="large"
    hide-default-actions
    close-button
  >
    <template v-if="sample">
      <div class="flex justify-between items-start mb-1">
        <h3 class="va-h3 mono-code">{{ sample.SampleCode }}</h3>
        <div class="flex gap-2">
          <VaButton size="small" preset="secondary" icon="history" @click="toggleHistory">
            {{ L('История', 'Tarix', 'History') }}
          </VaButton>
          <VaButton size="small" preset="secondary" icon="print" @click="printProtocol">
            {{ L('Протокол', 'Protokol', 'Protocol') }}
          </VaButton>
        </div>
      </div>

      <!-- Панель истории (аудит пробы и её определений) -->
      <div v-if="showHistory" class="hist-panel">
        <table class="lims-table">
          <thead>
            <tr>
              <th style="width:140px">{{ L('Дата', 'Sana', 'Date') }}</th>
              <th style="width:120px">{{ L('Пользователь', 'Foydalanuvchi', 'User') }}</th>
              <th style="width:120px">{{ L('Действие', 'Amal', 'Action') }}</th>
              <th>{{ L('Детали', 'Tafsilotlar', 'Details') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="h in history" :key="h.id">
              <td class="mono" style="font-size:11px">{{ (h.CreatedAt || '').replace('T',' ').slice(0,16) }}</td>
              <td>{{ h.UserName }}</td>
              <td><span class="chip" :class="histActionChip(h.Action)">{{ histActionLabel(h.Action) }}</span></td>
              <td style="font-size:12px">
                <span class="opacity-70">{{ histEntityLabel(h.EntityType) }}</span>
                <template v-if="h.Changes"> · <span v-for="(v,f) in h.Changes" :key="f" class="hist-chg">{{ f }}: <s>{{ fmtV(v.old) }}</s>→<b>{{ fmtV(v.new) }}</b></span></template>
              </td>
            </tr>
            <tr v-if="!history.length"><td colspan="4" class="lims-empty">{{ L('Нет записей', "Yozuv yo'q", 'No records') }}</td></tr>
          </tbody>
        </table>
      </div>
      <div class="text-sm opacity-80 mb-2 flex flex-wrap gap-x-4 gap-y-1">
        <span>{{ L('Дата:', 'Sana:', 'Date:') }} {{ fmt(sample.RegisteredAt) }}</span>
        <span>{{ L('Документ:', 'Hujjat:', 'Document:') }} {{ sample.DocumentNumber || '—' }}</span>
        <span v-if="sample.Batch">{{ L('Партия:', 'Partiya:', 'Batch:') }} <b>{{ sample.Batch }}</b></span>
        <span>{{ L('Статус:', 'Holat:', 'Status:') }} <b>{{ statusLabel(sample.Status) }}</b></span>
      </div>

      <!-- Объект испытания: хранение, условия, описание (ТЗ 2.4/2.7/2.11/2.12) -->
      <div v-if="hasObjectInfo" class="obj-info">
        <div v-if="storageLoc || sample.StorageLocation"><span class="oi-k">{{ L('Место хранения', 'Saqlash joyi', 'Storage') }}:</span> {{ storageLoc }}<span v-if="sample.StorageLocation"> · {{ sample.StorageLocation }}</span></div>
        <div v-if="sample.StorageUntil"><span class="oi-k">{{ L('Хранить до', 'Saqlash muddati', 'Store until') }}:</span> <b :style="storageExpired ? 'color:#f43f5e' : ''">{{ (sample.StorageUntil||'').slice(0,10) }}</b><span v-if="storageExpired" style="color:#f43f5e"> ({{ L('срок истёк', 'muddati o\'tgan', 'expired') }})</span></div>
        <div v-if="sample.StorageConditions"><span class="oi-k">{{ L('Условия хранения', 'Saqlash sharoiti', 'Storage cond.') }}:</span> {{ sample.StorageConditions }}</div>
        <div v-if="sample.TransportConditions"><span class="oi-k">{{ L('Условия транспортировки', 'Tashish sharoiti', 'Transport cond.') }}:</span> {{ sample.TransportConditions }}</div>
        <div v-if="sample.Description"><span class="oi-k">{{ L('Описание', 'Tavsif', 'Description') }}:</span> {{ sample.Description }}</div>
        <div v-if="sample.ChemicalComposition"><span class="oi-k">{{ L('Химический состав', 'Kimyoviy tarkib', 'Chem. composition') }}:</span> {{ sample.ChemicalComposition }}</div>
        <div v-if="sample.DisposalDate" class="oi-disp"><span class="oi-k">{{ L('Утилизирован', 'Utilizatsiya', 'Disposed') }}:</span> {{ (sample.DisposalDate||'').slice(0,10) }} · {{ sample.DisposalBy }}<span v-if="sample.DisposalAct"> · {{ L('акт', 'dalolatnoma', 'act') }} {{ sample.DisposalAct }}</span><span v-if="sample.DisposalReason"> · {{ sample.DisposalReason }}</span></div>
      </div>

      <table class="lims-table">
        <thead>
          <tr>
            <th>{{ L('Элемент', 'Element', 'Element') }}</th>
            <th>{{ L('Исполнитель', 'Bajaruvchi', 'Executor') }}</th>
            <th style="width:110px">{{ L('Результат', 'Natija', 'Result') }}</th>
            <th style="width:64px">{{ L('Ед.', 'Birlik', 'Unit') }}</th>
            <th style="width:70px">{{ L('Допуск', 'Ruxsat', 'In tol.') }}</th>
            <th style="width:180px">{{ L('Состояние / подписи', 'Holat / imzolar', 'Status / signatures') }}</th>
            <th style="width:210px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in determinations" :key="d.id">
            <td>
              <b>{{ d.AnalyteSymbol }}</b>
              <span class="opacity-70"> {{ isRu ? (d.AnalyteNameRus || d.AnalyteName) : d.AnalyteName }}</span>
            </td>
            <td>{{ isRu ? (d.ExecutorGroupNameRus || d.ExecutorGroupName) : d.ExecutorGroupName }}</td>
            <td>
              <VaInput v-model="d.ResultValue" size="small" :readonly="isApproved(d)" />
              <div v-if="d.MethodUncertainty" class="metro">± {{ d.MethodUncertainty }}%</div>
              <div v-if="d.MethodLOQ" class="metro">LOQ {{ d.MethodLOQ }}</div>
            </td>
            <td><VaInput v-model="d.Unit" size="small" :readonly="isApproved(d)" /></td>
            <td class="text-center"><VaCheckbox v-model="d.InTolerance" :disabled="isApproved(d)" /></td>
            <td>
              <span v-if="d.ResultStatus" class="chip" :class="rStatusChip(d.ResultStatus)">{{ rStatusLabel(d.ResultStatus) }}</span>
              <span v-else class="chip chip-pending">{{ L('ожидает', 'kutmoqda', 'pending') }}</span>
              <div v-if="d.ReviewedBy" class="sig">✓ {{ d.ReviewedBy }}<span class="sig-dt"> · {{ fmt(d.ReviewedAt) }}</span></div>
              <div v-if="d.ApprovedBy" class="sig sig-appr">✓✓ {{ d.ApprovedBy }}<span class="sig-dt"> · {{ fmt(d.ApprovedAt) }}</span></div>
            </td>
            <td style="white-space:nowrap">
              <VaButton v-if="!isApproved(d)" size="small" preset="primary" icon="save" @click="saveResult(d)" :title="L('Сохранить','Saqlash','Save')" />
              <VaButton v-if="d.ResultStatus === 'entered'" size="small" preset="secondary" icon="fact_check" color="warning" @click="reviewResult(d)" :title="L('Проверить','Tekshirish','Review')" />
              <VaButton v-if="d.ResultStatus === 'reviewed'" size="small" icon="verified" color="success" @click="approveResult(d)" :title="L('Утвердить','Tasdiqlash','Approve')" />
              <VaButton v-if="d.ResultStatus === 'reviewed' || d.ResultStatus === 'approved'" size="small" preset="secondary" icon="lock_open" color="danger" @click="reopenResult(d)" :title="L('Переоткрыть','Qayta ochish','Reopen')" />
            </td>
          </tr>
          <tr v-if="!determinations.length">
            <td colspan="7" class="lims-empty">{{ L('Определений нет.', "Aniqlash yo'q.", 'No determinations.') }}</td>
          </tr>
        </tbody>
      </table>
      <p class="text-xs opacity-60 mt-2">
        {{ L('Порядок: ввод результата → проверка → утверждение. Утверждённый результат заблокирован; для правки — «Переоткрыть» (с указанием причины, фиксируется в аудите).',
             'Tartib: natija → tekshirish → tasdiqlash. Tasdiqlangan natija bloklanadi; tuzatish uchun «Qayta ochish» (sabab bilan, auditda qayd etiladi).',
             'Flow: enter result → review → approve. An approved result is locked; to edit use “Reopen” (with a reason, recorded in the audit trail).') }}
      </p>

      <!-- Сопроводительные документы (ТЗ 2.8) -->
      <div class="mt-4">
        <div class="va-h6 mb-2">{{ L('Сопроводительные документы', 'Hamrohlik hujjatlari', 'Accompanying documents') }}</div>
        <div class="flex flex-wrap items-center gap-2 mb-2">
          <VaSelect v-model="fileType" :options="fileTypes" text-by="text" value-by="value" class="w-52" />
          <input type="file" ref="fileInput" @change="onFilePick" />
        </div>
        <table class="lims-table">
          <tbody>
            <tr v-for="f in files" :key="f.id">
              <td style="width:150px"><span class="chip chip-utilized">{{ fileTypeLabel(f.FileType) }}</span></td>
              <td>{{ f.OriginalName }}</td>
              <td class="mono" style="width:90px">{{ Math.round((f.Size || 0) / 1024) }} KB</td>
              <td style="width:96px;white-space:nowrap">
                <button class="lims-iconbtn" :title="L('Скачать','Yuklab olish','Download')" @click="downloadFile(f)"><span class="material-icons">download</span></button>
                <button class="lims-iconbtn danger" :title="L('Удалить','O\'chirish','Delete')" @click="deleteFile(f)"><span class="material-icons">delete</span></button>
              </td>
            </tr>
            <tr v-if="!files.length"><td colspan="4" class="lims-empty">—</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Оформление утилизации (ТЗ 2.14) -->
      <div v-if="sample.Status !== 'utilized'" class="mt-4">
        <button v-if="!showDispose" class="lims-btn ghost" @click="showDispose = true"><span class="material-icons">delete_sweep</span>{{ L('Оформить утилизацию', 'Utilizatsiyani rasmiylashtirish', 'Formalize disposal') }}</button>
        <div v-else class="lims-panel lims-panel-pad">
          <div class="va-h6 mb-2">{{ L('Утилизация объекта', 'Obyekt utilizatsiyasi', 'Object disposal') }}</div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-2">
            <VaInput v-model="disposal.DisposalAct" :label="L('№ акта', 'Dalolatnoma №', 'Act №')" />
            <VaInput v-model="disposal.DisposalDate" type="date" :label="L('Дата', 'Sana', 'Date')" />
            <VaInput v-model="disposal.DisposalReason" :label="L('Причина', 'Sabab', 'Reason')" />
          </div>
          <div class="flex gap-2">
            <VaButton size="small" color="danger" icon="check" @click="confirmDispose">{{ L('Подтвердить', 'Tasdiqlash', 'Confirm') }}</VaButton>
            <VaButton size="small" preset="secondary" @click="showDispose = false">{{ L('Отмена', 'Bekor', 'Cancel') }}</VaButton>
          </div>
        </div>
      </div>
    </template>
    <div v-else class="p-4 text-center opacity-60">{{ L('Загрузка…', 'Yuklanmoqda…', 'Loading…') }}</div>
  </VaModal>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import axios from "axios";
import { VaModal, VaInput, VaSelect, VaButton, VaCheckbox, useToast } from "vuestic-ui";
import { useLang } from "../../composables/useLang.js";

const props = defineProps({ modelValue: Boolean, sampleId: [Number, String] });
const emit = defineEmits(["update:modelValue", "changed"]);

const { L, isRu } = useLang();
const { init } = useToast();

const sample = ref(null);
const determinations = ref([]);
const showHistory = ref(false);
const history = ref([]);

// Файлы (сопроводительные документы) + утилизация.
const files = ref([]);
const fileType = ref("waybill");
const fileInput = ref(null);
const showDispose = ref(false);
const disposal = ref({ DisposalAct: "", DisposalDate: "", DisposalReason: "" });

const fileTypeMap = {
  waybill: { ru: "Накладная", uz: "Yuk xati", en: "Waybill" },
  acceptance_act: { ru: "Акт приёмки", uz: "Qabul dalolatnomasi", en: "Acceptance act" },
  other: { ru: "Прочее", uz: "Boshqa", en: "Other" },
};
const fileTypes = computed(() => Object.entries(fileTypeMap).map(([k, v]) => ({ value: k, text: L(v.ru, v.uz, v.en) })));
const fileTypeLabel = (k) => (fileTypeMap[k] ? L(fileTypeMap[k].ru, fileTypeMap[k].uz, fileTypeMap[k].en) : k);

const storageLoc = computed(() => (isRu.value ? sample.value?.StorageLocationNameRus || sample.value?.StorageLocationName : sample.value?.StorageLocationName) || "");
const storageExpired = computed(() => {
  const d = sample.value?.StorageUntil;
  return d ? new Date(String(d).slice(0, 10)) < new Date(new Date().toDateString()) : false;
});
const hasObjectInfo = computed(() => {
  const s = sample.value;
  return !!(s && (s.StorageLocationID || s.StorageLocation || s.StorageUntil || s.StorageConditions || s.TransportConditions || s.Description || s.ChemicalComposition || s.DisposalDate));
});

const histActions = {
  created: { ru: "создание", uz: "yaratish", en: "created", chip: "chip-tested" },
  updated: { ru: "изменение", uz: "o'zgartirish", en: "updated", chip: "chip-in_progress" },
  deleted: { ru: "удаление", uz: "o'chirish", en: "deleted", chip: "chip-reject" },
  result_reviewed: { ru: "проверка", uz: "tekshirish", en: "reviewed", chip: "chip-in_progress" },
  result_approved: { ru: "утверждение", uz: "tasdiqlash", en: "approved", chip: "chip-approved" },
  reopened: { ru: "переоткрытие", uz: "qayta ochish", en: "reopened", chip: "chip-reject" },
};
const histActionLabel = (a) => (histActions[a] ? L(histActions[a].ru, histActions[a].uz, histActions[a].en) : a);
const histActionChip = (a) => histActions[a]?.chip || "chip-utilized";
const histEntities = {
  LabSample: { ru: "Проба", uz: "Namuna", en: "Sample" },
  LabSampleDetermination: { ru: "Определение", uz: "Aniqlash", en: "Determination" },
};
const histEntityLabel = (t) => (histEntities[t] ? L(histEntities[t].ru, histEntities[t].uz, histEntities[t].en) : t);
const fmtV = (v) => (v === null || v === undefined || v === "" ? "∅" : String(v).slice(0, 40));

const toggleHistory = async () => {
  showHistory.value = !showHistory.value;
  if (showHistory.value && props.sampleId) {
    try {
      const { data } = await axios.get(`/lab/samples/${props.sampleId}/history`);
      history.value = Array.isArray(data) ? data : [];
    } catch (e) { console.error(e); }
  }
};

const statuses = {
  new: { ru: "Новый", uz: "Yangi", en: "New" },
  in_progress: { ru: "В процессе", uz: "Jarayonda", en: "In progress" },
  tested: { ru: "Испытан", uz: "Sinaldi", en: "Tested" },
  reject: { ru: "Брак", uz: "Brak", en: "Reject" },
  utilized: { ru: "Утилизирован", uz: "Utilizatsiya", en: "Utilized" },
};
const statusLabel = (s) => (statuses[s] ? L(statuses[s].ru, statuses[s].uz, statuses[s].en) : s);
const fmt = (v) => (v ? String(v).replace("T", " ").slice(0, 16) : "—");

// Состояние результата (жизненный цикл ISO 17025).
const rStatuses = {
  entered: { ru: "Введён", uz: "Kiritilgan", en: "Entered", chip: "chip-new" },
  reviewed: { ru: "Проверен", uz: "Tekshirilgan", en: "Reviewed", chip: "chip-in_progress" },
  approved: { ru: "Утверждён", uz: "Tasdiqlangan", en: "Approved", chip: "chip-approved" },
};
const rStatusLabel = (s) => (rStatuses[s] ? L(rStatuses[s].ru, rStatuses[s].uz, rStatuses[s].en) : s);
const rStatusChip = (s) => rStatuses[s]?.chip || "chip-utilized";
const isApproved = (d) => d.ResultStatus === "approved";

const load = async () => {
  if (!props.sampleId) return;
  sample.value = null;
  determinations.value = [];
  showHistory.value = false;
  history.value = [];
  files.value = [];
  showDispose.value = false;
  disposal.value = { DisposalAct: "", DisposalDate: "", DisposalReason: "" };
  try {
    const { data } = await axios.get(`/lab/samples/${props.sampleId}`);
    sample.value = data;
    files.value = data.files || [];
    determinations.value = (data.determinations || []).map((d) => ({
      ...d,
      InTolerance: d.InTolerance === null ? false : !!Number(d.InTolerance),
    }));
  } catch (e) {
    console.error(e);
  }
};

const onFilePick = async (e) => {
  const file = e.target.files?.[0];
  if (!file || !props.sampleId) return;
  const fd = new FormData();
  fd.append("file", file);
  fd.append("FileType", fileType.value);
  try {
    await axios.post(`/lab/samples/${props.sampleId}/file`, fd, { headers: { "Content-Type": "multipart/form-data" } });
    if (fileInput.value) fileInput.value.value = "";
    init({ message: L("Файл загружен", "Fayl yuklandi", "File uploaded"), color: "success" });
    await load();
  } catch (err) {
    init({ message: L("Ошибка загрузки", "Yuklash xatosi", "Upload error"), color: "danger" });
    console.error(err);
  }
};

const downloadFile = (f) => {
  const base = axios.defaults.baseURL || "";
  window.open(`${base}/lab/sample-file/${f.id}/download`, "_blank");
};

const deleteFile = async (f) => {
  try {
    await axios.delete(`/lab/sample-file/${f.id}`);
    await load();
  } catch (e) { console.error(e); }
};

const confirmDispose = async () => {
  try {
    await axios.post(`/lab/samples/${props.sampleId}/dispose`, disposal.value);
    init({ message: L("Утилизация оформлена", "Utilizatsiya rasmiylashtirildi", "Disposal recorded"), color: "success" });
    showDispose.value = false;
    emit("changed");
    await load();
  } catch (e) {
    init({ message: e?.response?.data?.message || L("Ошибка", "Xatolik", "Error"), color: "danger" });
  }
};

watch(() => props.modelValue, (open) => { if (open) load(); });

const nm = (base) => (isRu.value ? sample.value?.[base + "Rus"] || sample.value?.[base] : sample.value?.[base]) || "";

const printProtocol = () => {
  const s = sample.value;
  if (!s) return;
  const uAbs = (d) => {
    const r = parseFloat(d.ResultValue);
    const u = parseFloat(d.MethodUncertainty);
    if (isNaN(r) || isNaN(u)) return d.MethodUncertainty ? `± ${d.MethodUncertainty}%` : "";
    const dp = d.MethodDecimalPlaces != null ? Number(d.MethodDecimalPlaces) : 3;
    return "± " + ((r * u) / 100).toFixed(dp);
  };

  const rows = determinations.value
    .map(
      (d, i) => `<tr>
        <td>${i + 1}</td>
        <td>${d.AnalyteSymbol || ""} ${(isRu.value ? d.AnalyteNameRus || d.AnalyteName : d.AnalyteName) || ""}</td>
        <td>${(isRu.value ? d.MethodNameRus || d.MethodName : d.MethodName) || ""}</td>
        <td style="text-align:center">${d.ResultValue ?? "—"}</td>
        <td style="text-align:center">${uAbs(d) || "—"}</td>
        <td style="text-align:center">${d.Unit || ""}</td>
        <td style="text-align:center">${d.ApprovedBy ? "✓ " + d.ApprovedBy : (d.ResultAt ? fmt(d.ResultAt) : "—")}</td>
      </tr>`
    )
    .join("");

  const html = `<!doctype html><html><head><meta charset="utf-8"><title>${s.SampleCode}</title>
    <style>
      body{font-family:Arial,sans-serif;font-size:13px;color:#111;padding:24px}
      h2{text-align:center;margin:0 0 4px}
      .sub{text-align:center;color:#555;margin-bottom:16px}
      .meta{margin-bottom:12px;line-height:1.6}
      table{width:100%;border-collapse:collapse;margin-top:8px}
      th,td{border:1px solid #999;padding:6px 8px;text-align:left}
      th{background:#f0f0f0}
      .sign{margin-top:40px;display:flex;justify-content:space-between}
    </style></head><body>
    <h2>${L("ПРОТОКОЛ результатов анализа", "Tahlil natijalari BAYONNOMASI", "ANALYSIS REPORT")}</h2>
    <div class="sub">${L("Шифр пробы", "Namuna shifri", "Sample code")}: <b>${s.SampleCode}</b></div>
    <div class="meta">
      <div>${L("Дата регистрации", "Ro'yxat sanasi", "Registered")}: ${fmt(s.RegisteredAt)}</div>
      <div>${L("№ документа", "Hujjat №", "Document №")}: ${s.DocumentNumber || "—"}</div>
      <div>${L("Подразделение", "Bo'linma", "Department")}: ${nm("DepartmentName") || "—"}</div>
      <div>${L("Заказчик", "Buyurtmachi", "Customer")}: ${nm("CustomerLaboratoryName")} ${nm("CustomerGroupName") ? "/ " + nm("CustomerGroupName") : ""}</div>
      <div>${L("Тип пробы", "Namuna turi", "Sample type")}: ${nm("SampleTypeName") || "—"}</div>
    </div>
    <table>
      <thead><tr>
        <th>№</th><th>${L("Показатель", "Ko'rsatkich", "Analyte")}</th>
        <th>${L("Методика", "Metodika", "Method")}</th>
        <th>${L("Результат", "Natija", "Result")}</th>
        <th>${L("Неопределённость (U)", "Noaniqlik (U)", "Uncertainty (U)")}</th>
        <th>${L("Ед.", "Birlik", "Unit")}</th>
        <th>${L("Утвердил / дата", "Tasdiqladi / sana", "Approved / date")}</th>
      </tr></thead>
      <tbody>${rows}</tbody>
    </table>
    <div class="sign">
      <div>${L("Исполнитель ______________", "Bajaruvchi ______________", "Analyst ______________")}</div>
      <div>${L("Зав. лабораторией ______________", "Laboratoriya mudiri ______________", "Head of laboratory ______________")}</div>
    </div>
    </body></html>`;

  const w = window.open("", "_blank");
  if (!w) {
    init({ message: L("Разрешите всплывающие окна", "Popup'ga ruxsat bering", "Allow pop-ups"), color: "warning" });
    return;
  }
  w.document.write(html);
  w.document.close();
  w.focus();
  setTimeout(() => w.print(), 300);
};

const saveResult = async (d) => {
  try {
    const { data } = await axios.post(`/lab/determinations/${d.id}/result`, {
      ResultValue: d.ResultValue,
      Unit: d.Unit,
      InTolerance: d.InTolerance,
    });
    if (data.status === 200) {
      d.ResultAt = data.unit.ResultAt;
      d.Status = "done";
      init({ message: L("Результат сохранён", "Natija saqlandi", "Result saved"), color: "success" });
      emit("changed");
      await load();
    }
  } catch (e) {
    const locked = e?.response?.status === 423;
    init({ message: locked ? L("Результат утверждён и заблокирован", "Natija tasdiqlangan va bloklangan", "Result is approved and locked") : L("Ошибка", "Xatolik", "Error"), color: locked ? "warning" : "danger" });
    console.error(e);
  }
};

const reviewResult = async (d) => {
  try {
    await axios.post(`/lab/determinations/${d.id}/review`);
    init({ message: L("Проверено", "Tekshirildi", "Reviewed"), color: "success" });
    emit("changed");
    await load();
  } catch (e) {
    init({ message: e?.response?.data?.message || L("Ошибка", "Xatolik", "Error"), color: "danger" });
  }
};

const approveResult = async (d) => {
  try {
    await axios.post(`/lab/determinations/${d.id}/approve`);
    init({ message: L("Утверждено", "Tasdiqlandi", "Approved"), color: "success" });
    emit("changed");
    await load();
  } catch (e) {
    init({ message: e?.response?.data?.message || L("Ошибка", "Xatolik", "Error"), color: "danger" });
  }
};

const reopenResult = async (d) => {
  const reason = window.prompt(L("Причина переоткрытия (фиксируется в аудите):", "Qayta ochish sababi (auditda qayd etiladi):", "Reason for reopening (recorded in the audit trail):"));
  if (!reason) return;
  try {
    await axios.post(`/lab/determinations/${d.id}/reopen`, { reason });
    init({ message: L("Переоткрыто", "Qayta ochildi", "Reopened"), color: "success" });
    emit("changed");
    await load();
  } catch (e) {
    init({ message: e?.response?.data?.message || L("Ошибка", "Xatolik", "Error"), color: "danger" });
  }
};
</script>

<style scoped>
.mono-code { font-family: ui-monospace, monospace; }
.sig { font-size: 11px; color: #16a34a; margin-top: 3px; }
.sig-appr { color: #0284c7; font-weight: 700; }
.sig-dt { opacity: .6; font-weight: 400; }
.chip-pending { background: rgba(148,163,184,0.18); color: #64748b; }
.metro { font-size: 11px; color: var(--lims-muted, #94a3b8); margin-top: 2px; }
.obj-info { font-size: 12.5px; line-height: 1.7; margin-bottom: 12px; padding: 8px 12px; border-radius: 8px; background: var(--lims-panel-bg, rgba(148,163,184,0.08)); border: 1px solid var(--lims-line); }
.obj-info .oi-k { color: var(--lims-muted, #94a3b8); }
.obj-info .oi-disp { color: #a78bfa; margin-top: 2px; }
.hist-panel { margin: 6px 0 14px; max-height: 240px; overflow-y: auto; border: 1px solid var(--lims-line); border-radius: 10px; }
.hist-chg { margin-right: 8px; }
.hist-chg s { color: #f43f5e; opacity: .8; }
.hist-chg b { color: #22c55e; }
</style>
