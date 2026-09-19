<template>
  <VaForm ref="formRef" class="flex flex-col items-baseline gap-2 w-full">
    <template v-for="f in fields" :key="f.key">
      <!-- select -->
      <VaSelect
        v-if="f.type === 'select'"
        class="w-full"
        v-model="model[f.key]"
        :label="label(f)"
        :options="options[f.key] || []"
        :text-by="optionText"
        :value-by="'id'"
        clearable
      />
      <!-- textarea -->
      <VaTextarea
        v-else-if="f.type === 'textarea'"
        class="w-full"
        v-model="model[f.key]"
        :max-length="1000"
        :label="label(f)"
      />
      <!-- date -->
      <VaInput
        v-else-if="f.type === 'date'"
        class="w-full"
        type="date"
        v-model="model[f.key]"
        :label="label(f)"
      />
      <!-- text (default) -->
      <VaInput
        v-else
        class="w-full"
        v-model="model[f.key]"
        :label="label(f)"
        :rules="f.required ? [(v) => (!!v && String(v).length > 0) || t('validation.requiredField')] : []"
      />
    </template>
  </VaForm>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import axios from "axios";
import { useI18n } from "vue-i18n";
import { VaForm, VaInput, VaTextarea, VaSelect } from "vuestic-ui";

const props = defineProps({
  fields: { type: Array, required: true },
  model: { type: Object, required: true },
});

const { locale, t } = useI18n();
const options = reactive({});

const label = (f) =>
  f.label ? (f.label[locale.value] || f.label.ru || f.label.uz) : f.key;

// Отображение опции select: по локали Name/NameRus, с запасными вариантами.
const optionText = (opt) => {
  if (opt == null) return "";
  const ru = opt.NameRus || opt.Name || opt.name;
  const uz = opt.Name || opt.NameRus || opt.name;
  return (locale.value === "uz" ? uz : ru) ?? String(opt.id ?? "");
};

const loadOptions = async () => {
  for (const f of props.fields) {
    if (f.type === "select" && f.optionsEndpoint) {
      try {
        const { data } = await axios.get(f.optionsEndpoint);
        options[f.key] = Array.isArray(data) ? data : data.items || [];
      } catch (e) {
        options[f.key] = [];
      }
    }
  }
};

onMounted(loadOptions);
</script>
