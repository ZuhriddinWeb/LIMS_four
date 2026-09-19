<template>
  <div class="lims-page">
    <div class="lims-head">
      <div>
        <h1 class="lims-title">{{ L('Паспорта качества', 'Sifat pasportlari', 'Quality certificates') }}</h1>
        <p class="lims-sub">{{ L('Паспорта на продукцию по результатам анализа', 'Mahsulot pasportlari', 'Product quality passports') }}</p>
      </div>
      <button v-if="canCreate" class="lims-btn" @click="openCreate"><span class="material-icons">add</span>{{ L('Создать паспорт', 'Pasport yaratish', 'New certificate') }}</button>
    </div>

    <div class="lims-toolbar">
      <VaInput v-model.number="filters.year" type="number" :label="L('Год', 'Yil', 'Year')" class="w-24" />
      <VaSelect v-model="filters.status" :options="statusOptions" text-by="text" value-by="value" :label="L('Статус', 'Holat', 'Status')" class="w-44" clearable />
      <VaInput v-model="filters.q" :label="L('Поиск (№)', 'Qidiruv (№)', 'Search (№)')" class="w-52" />
      <button class="lims-btn ghost" @click="fetchCerts"><span class="material-icons">search</span>{{ L('Показать', "Ko'rsatish", 'Show') }}</button>
    </div>

    <div class="lims-count">{{ L('Паспортов', 'Pasportlar', 'Certificates') }}: {{ certs.length }}</div>

    <div class="lims-panel" style="overflow-x:auto">
      <table class="lims-table">
        <thead>
          <tr>
            <th style="width:44px">№</th>
            <th>{{ L('№ паспорта', 'Pasport №', 'Certificate №') }}</th>
            <th>{{ L('Продукция', 'Mahsulot', 'Product') }}</th>
            <th>{{ L('Партия', 'Partiya', 'Batch') }}</th>
            <th style="width:120px">{{ L('Дата', 'Sana', 'Date') }}</th>
            <th>{{ L('Проба', 'Namuna', 'Sample') }}</th>
            <th style="width:120px">{{ L('Статус', 'Holat', 'Status') }}</th>
            <th style="width:140px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(c, i) in certs" :key="c.id">
            <td>{{ i + 1 }}</td>
            <td class="mono">{{ c.CertNumber }}</td>
            <td>{{ isRu ? (c.ProductNameRus || c.ProductName) : c.ProductName }}</td>
            <td>{{ c.Batch }}</td>
            <td>{{ (c.CertDate || '').slice(0, 10) }}</td>
            <td class="mono">{{ c.SampleCode }}</td>
            <td><span class="chip" :class="'chip-' + c.Status">{{ statusLabel(c.Status) }}</span></td>
            <td style="white-space:nowrap">
              <button class="lims-iconbtn" :title="L('Изменить', 'Tahrirlash', 'Edit')" @click="openEdit(c.id)"><span class="material-icons">edit</span></button>
              <button class="lims-iconbtn" :title="L('Паспорт (печать/PDF)', 'Pasport (chop/PDF)', 'Certificate (print/PDF)')" @click="printCert(c.id)"><span class="material-icons">picture_as_pdf</span></button>
              <button v-if="canDelete" class="lims-iconbtn danger" :title="L('Удалить', 'O\'chirish', 'Delete')" @click="removeCert(c)"><span class="material-icons">delete</span></button>
            </td>
          </tr>
          <tr v-if="!certs.length"><td colspan="8" class="lims-empty">{{ L('Паспортов нет', "Pasport yo'q", 'No certificates') }}</td></tr>
        </tbody>
      </table>
    </div>

    <LabCertificateForm v-model="showForm" :cert-id="activeCertId" @saved="fetchCerts" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import axios from "axios";
import "vuestic-ui/dist/vuestic-ui.css";
import { VaInput, VaSelect, useToast } from "vuestic-ui";
import { useStore } from "vuex";
import { useLang } from "../../composables/useLang.js";
import { usePlant } from "../../composables/usePlant.js";
import LabCertificateForm from "../../components/LabComponent/LabCertificateForm.vue";

const { L, isRu, locale } = useLang();
const plant = usePlant();
const store = useStore();
const { init } = useToast();

const userRole = computed(() => store.state.user?.roles?.find((r) => r.name === "menu.lab_certificates"));
const hasPermission = (perm) => Number(userRole.value?.pivot?.[perm]) === 1;
const canCreate = computed(() => hasPermission("create"));
const canDelete = computed(() => hasPermission("delete"));

const filters = reactive({ year: new Date().getFullYear(), status: null, q: "" });
const statusOptions = computed(() => [
  { value: "draft", text: L("Черновик", "Qoralama", "Draft") },
  { value: "approved", text: L("Утверждён", "Tasdiqlangan", "Approved") },
]);
const statusLabel = (s) => (s === "approved" ? L("Утверждён", "Tasdiqlangan", "Approved") : L("Черновик", "Qoralama", "Draft"));

const certs = ref([]);
const showForm = ref(false);
const activeCertId = ref(null);

const fetchCerts = async () => {
  try {
    const params = {};
    if (filters.year) params.year = filters.year;
    if (filters.status) params.status = filters.status;
    if (filters.q) params.q = filters.q;
    const { data } = await axios.get("/lab/certificates", { params });
    certs.value = Array.isArray(data) ? data : [];
  } catch (e) { console.error(e); }
};

const openCreate = () => { activeCertId.value = null; showForm.value = true; };
const openEdit = (id) => { activeCertId.value = id; showForm.value = true; };

const removeCert = async (c) => {
  if (!confirm(L("Удалить паспорт ", "Pasportni o'chirish ", "Delete certificate ") + c.CertNumber + "?")) return;
  try {
    await axios.delete(`/lab/certificates/${c.id}`);
    init({ message: L("Удалено", "O'chirildi", "Deleted"), color: "success" });
    fetchCerts();
  } catch (e) { console.error(e); }
};

const printCert = async (id) => {
  try {
    const { data: c } = await axios.get(`/lab/certificates/${id}`);
    const b = plant.at(locale.value); // брендинг завода по текущему языку
    const origin = window.location.origin;
    const esc = (s) => String(s ?? "").replace(/[&<>]/g, (m) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;" }[m]));

    const conf = (v) => (v === null || v === undefined ? "—" : Number(v) ? L("соответствует", "mos", "conforms") : L("не соответствует", "mos emas", "not conf."));
    const items = c.items || [];
    const anyJudged = items.some((it) => it.Conforms !== null && it.Conforms !== undefined);
    const allConform = items.every((it) => it.Conforms === null || it.Conforms === undefined || Number(it.Conforms));
    const verdict = !anyJudged
      ? ""
      : allConform
        ? L("Продукция СООТВЕТСТВУЕТ требованиям нормативной документации.", "Mahsulot me'yoriy hujjat talablariga MOS keladi.", "The product CONFORMS to the requirements of the normative documentation.")
        : L("Продукция НЕ СООТВЕТСТВУЕТ требованиям нормативной документации.", "Mahsulot me'yoriy hujjat talablariga MOS KELMAYDI.", "The product does NOT CONFORM to the requirements of the normative documentation.");

    const rows = items
      .map((it, i) => `<tr>
          <td style="text-align:center">${i + 1}</td>
          <td>${esc(it.AnalyteSymbol || "")} ${esc((isRu.value ? it.AnalyteNameRus || it.AnalyteName : it.AnalyteName) || "")}</td>
          <td style="text-align:center"><b>${esc(it.ResultValue ?? "—")}</b></td>
          <td style="text-align:center">${esc(it.Unit || "")}</td>
          <td style="text-align:center">${esc(it.NormText || "—")}</td>
          <td>${esc((isRu.value ? it.MethodNameRus || it.MethodName : it.MethodName) || "")}</td>
          <td style="text-align:center">${conf(it.Conforms)}</td>
        </tr>`).join("");

    const accr = b.accreditation || {};
    const accrLine = accr.number
      ? `<div class="accr">${L("Аттестат аккредитации", "Akkreditatsiya attestati", "Accreditation certificate")}: ${esc(accr.number)}${accr.valid ? ` (${L("действ. до", "amal qiladi", "valid until")} ${esc(accr.valid)})` : ""}${accr.body ? ` · ${esc(accr.body)}` : ""}</div>`
      : "";

    const html = `<!doctype html><html><head><meta charset="utf-8"><title>${esc(c.CertNumber)}</title>
      <style>
        @page { size: A4; margin: 16mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', Georgia, serif; font-size: 12px; color: #111; margin: 0; }
        .hdr { display: flex; align-items: center; gap: 14px; border-bottom: 2px solid #14314f; padding-bottom: 10px; }
        .hdr img { width: 64px; height: auto; }
        .hdr .org { flex: 1; }
        .hdr .company { font-size: 15px; font-weight: 700; }
        .hdr .plant { font-size: 13px; color: #14314f; }
        .hdr .lab { font-size: 12px; color: #444; }
        .accr { font-size: 10.5px; color: #555; margin-top: 3px; }
        h2 { text-align: center; margin: 18px 0 2px; font-size: 17px; letter-spacing: .5px; }
        .num { text-align: center; margin-bottom: 14px; font-size: 13px; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 2px 24px; margin-bottom: 12px; line-height: 1.7; }
        .meta b { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #555; padding: 5px 7px; text-align: left; font-size: 11.5px; }
        th { background: #eef2f7; text-align: center; }
        .verdict { margin: 16px 0 6px; padding: 8px 12px; border-left: 4px solid #14314f; background: #f5f8fb; font-weight: 700; }
        .sign { margin-top: 42px; display: flex; justify-content: space-between; gap: 40px; }
        .sign .box { flex: 1; }
        .sign .line { border-top: 1px solid #333; margin-top: 30px; padding-top: 4px; font-size: 11px; color: #444; text-align: center; }
        .foot { margin-top: 18px; font-size: 10px; color: #888; text-align: center; }
      </style></head><body>
      <div class="hdr">
        <img src="${origin}${esc(b.logo)}" alt="" onerror="this.style.display='none'">
        <div class="org">
          <div class="company">${esc(b.company)}</div>
          <div class="plant">${esc(b.plantName)}</div>
          <div class="lab">${esc(b.labName)}${b.city ? " · " + esc(b.city) : ""}</div>
          ${accrLine}
        </div>
      </div>

      <h2>${L("ПАСПОРТ КАЧЕСТВА", "SIFAT PASPORTI", "QUALITY CERTIFICATE")}</h2>
      <div class="num">№ <b>${esc(c.CertNumber)}</b> ${L("от", "sana", "dated")} ${esc((c.CertDate || "").slice(0, 10))}</div>

      <div class="meta">
        <div>${L("Продукция", "Mahsulot", "Product")}: <b>${esc((isRu.value ? c.ProductNameRus || c.ProductName : c.ProductName) || "—")}</b></div>
        <div>${L("Партия / серия", "Partiya / seriya", "Batch / lot")}: <b>${esc(c.Batch || "—")}</b></div>
        <div>${L("Масса / количество", "Massa / miqdor", "Mass / quantity")}: ${esc(c.Quantity || "—")}</div>
        <div>${L("Шифр пробы", "Namuna shifri", "Sample code")}: ${esc(c.SampleCode || "—")}</div>
      </div>

      <table><thead><tr>
        <th style="width:32px">№</th><th>${L("Показатель", "Ko'rsatkich", "Analyte")}</th>
        <th style="width:70px">${L("Значение", "Qiymat", "Value")}</th><th style="width:50px">${L("Ед.", "Birlik", "Unit")}</th>
        <th style="width:90px">${L("Норма НД", "ND normasi", "Spec norm")}</th><th>${L("Методика", "Metodika", "Method")}</th>
        <th style="width:90px">${L("Соответствие", "Muvofiqlik", "Conformity")}</th>
      </tr></thead><tbody>${rows || `<tr><td colspan="7" style="text-align:center">—</td></tr>`}</tbody></table>

      ${verdict ? `<div class="verdict">${verdict}</div>` : ""}

      <div class="sign">
        <div class="box"><div class="line">${L("Выдал (Ф.И.О., подпись)", "Bergan (F.I.Sh., imzo)", "Issued by (name, signature)")}${c.IssuedBy ? "<br><b>" + esc(c.IssuedBy) + "</b>" : ""}</div></div>
        <div class="box"><div class="line">${L("Зав. лабораторией (Ф.И.О., подпись)", "Laboratoriya mudiri (F.I.Sh., imzo)", "Head of laboratory (name, signature)")}</div></div>
      </div>
      <div class="foot">${esc(b.companyShort || b.company)} · ${L("Паспорт качества", "Sifat pasporti", "Quality certificate")} № ${esc(c.CertNumber)}</div>
      </body></html>`;

    const w = window.open("", "_blank");
    if (!w) { init({ message: L("Разрешите всплывающие окна", "Popup'ga ruxsat bering", "Allow pop-ups"), color: "warning" }); return; }
    w.document.write(html); w.document.close(); w.focus();
    setTimeout(() => w.print(), 400);
  } catch (e) { console.error(e); }
};

onMounted(() => {
  const savedLocale = localStorage.getItem("locale");
  if (savedLocale) locale.value = savedLocale;
  fetchCerts();
});
</script>
