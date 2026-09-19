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
      {{ form.id ? L('Стандартный образец', 'Standart namuna', 'Reference standard') : L('Новый стандартный образец', 'Yangi standart namuna', 'New reference standard') }}
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <VaInput v-model="form.Code" :label="L('Код (ГСО/СОП №)', 'Kod (GSO/SOP №)', 'Code (CRM/SOP №)')" />
      <VaInput v-model="form.StandardType" :label="L('Тип (gso/sop)', 'Turi (gso/sop)', 'Type (crm/sop)')" />
      <VaInput v-model="form.Name" :label="L('Наименование (uz)', 'Nomi (uz)', 'Name (uz)')" />
      <VaInput v-model="form.NameRus" :label="L('Наименование (ru)', 'Nomi (ru)', 'Name (ru)')" />
      <VaInput v-model="form.ValidUntil" type="date" :label="L('Годен до', 'Amal qilish muddati', 'Valid until')" />
    </div>

    <div class="mt-4">
      <div class="flex justify-between items-center mb-2">
        <span class="va-h6">{{ L('Аттестованные значения', 'Attestatsiyalangan qiymatlar', 'Certified values') }}</span>
        <VaButton size="small" icon="add" @click="addRow">{{ L('Добавить', "Qo'shish", 'Add') }}</VaButton>
      </div>
      <table class="lims-table">
        <thead>
          <tr>
            <th>{{ L('Показатель', "Ko'rsatkich", 'Analyte') }}</th>
            <th style="width:130px">{{ L('Аттест. значение', 'Attest. qiymat', 'Certified value') }}</th>
            <th style="width:120px">{{ L('Погрешность ±', 'Xatolik ±', 'Uncertainty ±') }}</th>
            <th style="width:80px">{{ L('Ед.', 'Birlik', 'Unit') }}</th>
            <th style="width:36px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(v, i) in form.values" :key="i">
            <td><VaSelect v-model="v.AnalyteID" :options="opt.analytes" text-by="text" value-by="value" clearable /></td>
            <td><VaInput v-model="v.CertifiedValue" size="small" /></td>
            <td><VaInput v-model="v.Uncertainty" size="small" /></td>
            <td><VaInput v-model="v.Unit" size="small" /></td>
            <td><VaButton preset="plain" icon="close" color="danger" @click="removeRow(i)" /></td>
          </tr>
          <tr v-if="!form.values.length">
            <td colspan="5" class="lims-empty">{{ L('Добавьте показатели.', "Ko'rsatkich qo'shing.", 'Add analytes.') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </VaModal>
</template>

<script setup>
import { reactive, watch } from "vue";
import axios from "axios";
import { VaModal, VaInput, VaSelect, VaButton, useToast } from "vuestic-ui";
import { useLang } from "../../composables/useLang.js";

const props = defineProps({ modelValue: Boolean, standardId: [Number, String, null] });
const emit = defineEmits(["update:modelValue", "saved"]);

const { L, isRu } = useLang();
const { init } = useToast();

const blank = () => ({ id: null, Code: "", Name: "", NameRus: "", StandardType: "", ValidUntil: "", values: [] });
const form = reactive(blank());
const opt = reactive({ analytes: [] });

const loadOptions = async () => {
  const { data } = await axios.get("/lab/analytes").catch(() => ({ data: [] }));
  opt.analytes = (Array.isArray(data) ? data : []).map((o) => ({
    value: o.id,
    text: (isRu.value ? o.NameRus || o.Name : o.Name) ?? String(o.id),
  }));
};

const loadStandard = async (id) => {
  const { data } = await axios.get(`/lab/standards/${id}`);
  Object.assign(form, {
    id: data.id,
    Code: data.Code,
    Name: data.Name,
    NameRus: data.NameRus,
    StandardType: data.StandardType,
    ValidUntil: data.ValidUntil ? String(data.ValidUntil).slice(0, 10) : "",
    values: (data.values || []).map((v) => ({
      AnalyteID: v.AnalyteID,
      CertifiedValue: v.CertifiedValue,
      Uncertainty: v.Uncertainty,
      Unit: v.Unit,
    })),
  });
};

const addRow = () => form.values.push({ AnalyteID: null, CertifiedValue: "", Uncertainty: "", Unit: "" });
const removeRow = (i) => form.values.splice(i, 1);

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      Object.assign(form, blank());
      await loadOptions();
      if (props.standardId) await loadStandard(props.standardId);
    }
  }
);

const onSubmit = async () => {
  try {
    const payload = { ...form, values: form.values.filter((v) => v.AnalyteID) };
    let data;
    if (form.id) {
      ({ data } = await axios.put(`/lab/standards/${form.id}`, payload));
    } else {
      ({ data } = await axios.post("/lab/standards/register", payload));
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
</script>
