<template>
  <VaModal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    size="large"
    :ok-text="L('Сохранить', 'Saqlash', 'Save')"
    :cancel-text="L('Отмена', 'Bekor qilish', 'Cancel')"
    @ok="onSubmit"
    @close="$emit('update:modelValue', false)"
    close-button
  >
    <h3 class="va-h3 mb-3">
      {{ form.id ? L('Паспорт качества', 'Sifat pasporti', 'Quality certificate') : L('Новый паспорт качества', 'Yangi sifat pasporti', 'New quality certificate') }}
      <span v-if="form.CertNumber" class="opacity-70">— {{ form.CertNumber }}</span>
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <VaSelect v-model="form.ProductID" :label="L('Продукция', 'Mahsulot', 'Product')"
        :options="opt.products" text-by="text" value-by="value" clearable />
      <VaInput v-model="form.Batch" :label="L('Партия / серия', 'Partiya / seriya', 'Batch / lot')" />
      <VaInput v-model="form.CertDate" type="date" :label="L('Дата паспорта', 'Pasport sanasi', 'Certificate date')" />
      <VaInput v-model="form.Quantity" :label="L('Масса / количество', 'Massa / miqdor', 'Mass / quantity')" />
      <div class="flex items-end gap-2">
        <VaSelect v-model="form.SampleID" :label="L('Проба (источник результатов)', 'Namuna (natijalar manbai)', 'Sample (results source)')"
          :options="opt.samples" text-by="text" value-by="value" clearable class="flex-grow" />
        <VaButton size="small" @click="pullFromSample" :disabled="!form.SampleID">
          {{ L('Подтянуть', 'Yuklash', 'Pull') }}
        </VaButton>
      </div>
      <VaInput v-model="form.IssuedBy" :label="L('Выдал', 'Bergan', 'Issued by')" />
    </div>

    <div class="mt-4">
      <div class="flex justify-between items-center mb-2">
        <span class="va-h6">{{ L('Показатели качества', "Sifat ko'rsatkichlari", 'Quality indicators') }}</span>
        <VaButton size="small" icon="add" @click="addRow">{{ L('Добавить', "Qo'shish", 'Add') }}</VaButton>
      </div>

      <table class="lims-table">
        <thead>
          <tr>
            <th>{{ L('Показатель', "Ko'rsatkich", 'Analyte') }}</th>
            <th style="width:110px">{{ L('Значение', 'Qiymat', 'Value') }}</th>
            <th style="width:70px">{{ L('Ед.', 'Birlik', 'Unit') }}</th>
            <th style="width:120px">{{ L('Норма ТУ', 'TSh normasi', 'Spec norm') }}</th>
            <th>{{ L('Методика', 'Metodika', 'Method') }}</th>
            <th style="width:60px">{{ L('Соотв.', 'Mos', 'Conf.') }}</th>
            <th style="width:36px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(it, i) in form.items" :key="i">
            <td><VaSelect v-model="it.AnalyteID" :options="opt.analytes" text-by="text" value-by="value" clearable /></td>
            <td><VaInput v-model="it.ResultValue" size="small" /></td>
            <td><VaInput v-model="it.Unit" size="small" /></td>
            <td><VaInput v-model="it.NormText" size="small" /></td>
            <td><VaSelect v-model="it.MethodID" :options="opt.methods" text-by="text" value-by="value" clearable /></td>
            <td class="text-center"><VaCheckbox v-model="it.Conforms" /></td>
            <td><VaButton preset="plain" icon="close" color="danger" @click="removeRow(i)" /></td>
          </tr>
          <tr v-if="!form.items.length">
            <td colspan="7" class="lims-empty">
              {{ L('Добавьте показатели или подтяните из пробы.', "Ko'rsatkich qo'shing yoki namunadan yuklang.", 'Add indicators or pull from a sample.') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </VaModal>
</template>

<script setup>
import { reactive, watch } from "vue";
import axios from "axios";
import { VaModal, VaInput, VaSelect, VaButton, VaCheckbox, useToast } from "vuestic-ui";
import { useLang } from "../../composables/useLang.js";

const props = defineProps({ modelValue: Boolean, certId: [Number, String, null] });
const emit = defineEmits(["update:modelValue", "saved"]);

const { L, isRu } = useLang();
const { init } = useToast();

const blank = () => ({
  id: null,
  CertNumber: "",
  ProductID: null,
  Batch: "",
  CertDate: new Date().toISOString().slice(0, 10),
  SampleID: null,
  Quantity: "",
  IssuedBy: "",
  items: [],
});
const form = reactive(blank());
const opt = reactive({ products: [], samples: [], analytes: [], methods: [] });

const mapOpts = (arr, textKey) =>
  (Array.isArray(arr) ? arr : []).map((o) => ({
    value: o.id,
    text: textKey ? o[textKey] : (isRu.value ? o.NameRus || o.Name : o.Name) ?? String(o.id),
  }));

const loadOptions = async () => {
  const [p, s, a, m] = await Promise.all([
    axios.get("/lab/products").catch(() => ({ data: [] })),
    axios.get("/lab/samples").catch(() => ({ data: [] })),
    axios.get("/lab/analytes").catch(() => ({ data: [] })),
    axios.get("/lab/methods").catch(() => ({ data: [] })),
  ]);
  opt.products = mapOpts(p.data);
  opt.samples = mapOpts(s.data, "SampleCode");
  opt.analytes = mapOpts(a.data);
  opt.methods = mapOpts(m.data);
};

const loadCert = async (id) => {
  const { data } = await axios.get(`/lab/certificates/${id}`);
  Object.assign(form, {
    id: data.id,
    CertNumber: data.CertNumber,
    ProductID: data.ProductID,
    Batch: data.Batch,
    CertDate: data.CertDate ? String(data.CertDate).slice(0, 10) : "",
    SampleID: data.SampleID,
    Quantity: data.Quantity,
    IssuedBy: data.IssuedBy,
    items: (data.items || []).map((it) => ({
      AnalyteID: it.AnalyteID,
      ResultValue: it.ResultValue,
      Unit: it.Unit,
      NormText: it.NormText,
      MethodID: it.MethodID,
      Conforms: it.Conforms === null ? false : !!Number(it.Conforms),
    })),
  });
};

const pullFromSample = async () => {
  if (!form.SampleID) return;
  try {
    const { data } = await axios.get(`/lab/certificates/from-sample/${form.SampleID}`);
    form.items = (data || []).map((d) => ({
      AnalyteID: d.AnalyteID,
      ResultValue: d.ResultValue,
      Unit: d.Unit,
      NormText: "",
      MethodID: d.MethodID,
      Conforms: false,
    }));
    init({ message: L("Показатели загружены из пробы", "Ko'rsatkichlar yuklandi", "Loaded from sample"), color: "success" });
  } catch (e) {
    console.error(e);
  }
};

const addRow = () =>
  form.items.push({ AnalyteID: null, ResultValue: "", Unit: "", NormText: "", MethodID: null, Conforms: false });
const removeRow = (i) => form.items.splice(i, 1);

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      Object.assign(form, blank());
      await loadOptions();
      if (props.certId) await loadCert(props.certId);
    }
  }
);

const onSubmit = async () => {
  try {
    const payload = { ...form, items: form.items.filter((it) => it.AnalyteID || it.ResultValue || it.NormText) };
    let data;
    if (form.id) {
      ({ data } = await axios.put(`/lab/certificates/${form.id}`, payload));
    } else {
      ({ data } = await axios.post("/lab/certificates/register", payload));
    }
    if (data.status === 200) {
      init({ message: L("Паспорт: ", "Pasport: ", "Certificate: ") + (data.CertNumber || form.CertNumber), color: "success" });
      emit("saved");
      emit("update:modelValue", false);
    }
  } catch (e) {
    init({ message: L("Ошибка сохранения", "Xatolik", "Save error"), color: "danger" });
    console.error(e);
  }
};
</script>
