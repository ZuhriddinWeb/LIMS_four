<template>
  <VaModal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    :ok-text="L('Сохранить', 'Saqlash', 'Save')"
    :cancel-text="L('Отмена', 'Bekor qilish', 'Cancel')"
    size="large"
    @ok="onSubmit"
    @close="$emit('update:modelValue', false)"
    close-button
  >
    <h3 class="va-h3 mb-3">{{ L('Регистрация пробы', "Namunani ro'yxatga olish", 'Register sample') }}</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <VaInput v-model="form.DocumentNumber" :label="L('№ документа / откуда', 'Hujjat № / qayerdan', 'Document № / source')" />
      <VaInput v-model="form.Batch" :label="L('Партия / серия', 'Partiya / seriya', 'Batch / lot')" />
      <VaSelect v-model="form.DepartmentID" :label="L('Подразделение-источник', 'Manba bo\'linma', 'Source department')"
        :options="opt.departments" text-by="text" value-by="value" clearable />
      <VaSelect v-model="form.CustomerLaboratoryID" :label="L('Лаборатория-заказчик', 'Buyurtmachi laboratoriya', 'Customer laboratory')"
        :options="opt.laboratories" text-by="text" value-by="value" clearable />
      <VaSelect v-model="form.CustomerGroupID" :label="L('Группа-заказчик', 'Buyurtmachi guruh', 'Customer group')"
        :options="opt.groups" text-by="text" value-by="value" clearable />
      <VaSelect v-model="form.SampleTypeID" :label="L('Тип пробы', 'Namuna turi', 'Sample type')"
        :options="opt.types" text-by="text" value-by="value" clearable />
      <VaSelect v-model="form.PhysicalState" :label="L('Состояние', 'Holati', 'State')"
        :options="physicalStates" text-by="text" value-by="value" clearable />
      <VaInput v-model="form.Category" :label="L('Категория (сырьё/вода/воздух…)', 'Toifa (xomashyo/suv/havo…)', 'Category (ore/water/air…)')" />
      <VaInput v-model="form.Quantity" :label="L('Масса / объём', 'Massa / hajm', 'Mass / volume')" />
      <VaSelect v-model="form.StorageLocationID" :label="L('Место хранения', 'Saqlash joyi', 'Storage location')"
        :options="opt.storages" text-by="text" value-by="value" clearable />
      <VaInput v-model="form.StorageLocation" :label="L('Уточнение (№ полки/ячейки)', 'Aniqlik (javon/№)', 'Detail (shelf/cell №)')" />
      <VaInput v-model="form.StorageUntil" type="date" :label="L('Хранить до', 'Saqlash muddati', 'Store until')" />
      <VaInput v-model="form.StorageConditions" :label="L('Условия хранения', 'Saqlash sharoiti', 'Storage conditions')" />
      <VaInput v-model="form.TransportConditions" :label="L('Условия транспортировки', 'Tashish sharoiti', 'Transport conditions')" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
      <VaTextarea v-model="form.Description" :label="L('Описание (физ. характеристики)', 'Tavsif (fizik xususiyatlar)', 'Description (physical properties)')" :max-length="2000" />
      <VaTextarea v-model="form.ChemicalComposition" :label="L('Химический состав', 'Kimyoviy tarkib', 'Chemical composition')" :max-length="2000" />
    </div>

    <div class="mt-4">
      <div class="flex justify-between items-center mb-2">
        <span class="va-h6">{{ L('Определяемые элементы', "Aniqlanadigan elementlar", 'Determined elements') }}</span>
        <VaButton size="small" icon="add" @click="addRow">{{ L('Добавить', "Qo'shish", 'Add') }}</VaButton>
      </div>

      <div v-for="(d, i) in form.determinations" :key="i"
        class="grid grid-cols-[1fr,1fr,1fr,90px,40px] gap-2 items-center mb-2">
        <VaSelect v-model="d.AnalyteID" :label="L('Элемент', 'Element', 'Element')"
          :options="opt.analytes" text-by="text" value-by="value" clearable />
        <VaSelect v-model="d.ExecutorGroupID" :label="L('Группа-исполнитель', 'Bajaruvchi guruh', 'Executor group')"
          :options="opt.groups" text-by="text" value-by="value" clearable />
        <VaSelect v-model="d.MethodID" :label="L('Методика', 'Metodika', 'Method')"
          :options="opt.methods" text-by="text" value-by="value" clearable />
        <VaInput v-model="d.Unit" :label="L('Ед.', 'Birlik', 'Unit')" />
        <VaButton preset="plain" icon="close" color="danger" @click="removeRow(i)" />
      </div>
      <p v-if="!form.determinations.length" class="text-sm opacity-60">
        {{ L('Добавьте хотя бы один элемент.', "Kamida bitta element qo'shing.", 'Add at least one element.') }}
      </p>
    </div>
  </VaModal>
</template>

<script setup>
import { reactive, computed, watch } from "vue";
import axios from "axios";
import { VaModal, VaInput, VaTextarea, VaSelect, VaButton, useToast } from "vuestic-ui";
import { useLang } from "../../composables/useLang.js";

const props = defineProps({ modelValue: Boolean });
const emit = defineEmits(["update:modelValue", "saved"]);

const { L, isRu } = useLang();
const { init } = useToast();

const physicalStates = computed(() => [
  { value: "solid", text: L("Твёрдая", "Qattiq", "Solid") },
  { value: "liquid", text: L("Жидкая", "Suyuq", "Liquid") },
]);

const blankForm = () => ({
  DocumentNumber: "",
  Batch: "",
  DepartmentID: null,
  CustomerLaboratoryID: null,
  CustomerGroupID: null,
  SampleTypeID: null,
  PhysicalState: null,
  Category: "",
  Quantity: "",
  StorageLocationID: null,
  StorageLocation: "",
  StorageUntil: "",
  StorageConditions: "",
  TransportConditions: "",
  Description: "",
  ChemicalComposition: "",
  determinations: [{ AnalyteID: null, ExecutorGroupID: null, MethodID: null, Unit: "" }],
});
const form = reactive(blankForm());

const opt = reactive({ departments: [], laboratories: [], groups: [], types: [], analytes: [], methods: [], storages: [] });

const mapOpts = (arr) =>
  (Array.isArray(arr) ? arr : []).map((o) => ({
    value: o.id,
    text: (isRu.value ? o.NameRus || o.Name : o.Name) ?? String(o.id),
  }));

const loadOptions = async () => {
  const [dep, lab, grp, typ, ana, met, sto] = await Promise.all([
    axios.get("/lab/departments").catch(() => ({ data: [] })),
    axios.get("/lab/laboratories").catch(() => ({ data: [] })),
    axios.get("/lab/groups").catch(() => ({ data: [] })),
    axios.get("/lab/types").catch(() => ({ data: [] })),
    axios.get("/lab/analytes").catch(() => ({ data: [] })),
    axios.get("/lab/methods").catch(() => ({ data: [] })),
    axios.get("/lab/storage-locations").catch(() => ({ data: [] })),
  ]);
  opt.departments = mapOpts(dep.data);
  opt.laboratories = mapOpts(lab.data);
  opt.groups = mapOpts(grp.data);
  opt.types = mapOpts(typ.data);
  opt.analytes = mapOpts(ana.data);
  opt.methods = mapOpts(met.data);
  opt.storages = mapOpts(sto.data);
};

const addRow = () =>
  form.determinations.push({ AnalyteID: null, ExecutorGroupID: null, MethodID: null, Unit: "" });
const removeRow = (i) => form.determinations.splice(i, 1);

const reset = () => Object.assign(form, blankForm());

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      reset();
      loadOptions();
    }
  }
);

const onSubmit = async () => {
  try {
    const payload = {
      ...form,
      determinations: form.determinations.filter((d) => d.AnalyteID),
    };
    const { data } = await axios.post("/lab/samples/register", payload);
    if (data.status === 200) {
      init({ message: L("Проба зарегистрирована: ", "Namuna: ", "Sample registered: ") + data.SampleCode, color: "success" });
      emit("saved", data);
      emit("update:modelValue", false);
    }
  } catch (e) {
    init({ message: L("Ошибка регистрации", "Xatolik", "Registration error"), color: "danger" });
    console.error(e);
  }
};
</script>
