<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="edit" preset="primary" class="mt-1" @click="open" />
    <VaModal
      v-model="show"
      :ok-text="t('modals.apply')"
      :cancel-text="t('modals.cancel')"
      @ok="onSubmit"
      @close="show = false"
      close-button
    >
      <h3 class="va-h3 mb-2">{{ t('modals.edit') }}</h3>
      <LabFormFields v-if="show" :fields="fields" :model="model" />
    </VaModal>
  </main>
</template>

<script setup>
import { ref, reactive, inject } from "vue";
import axios from "axios";
import { useI18n } from "vue-i18n";
import { VaButton, VaModal, useToast } from "vuestic-ui";
import LabFormFields from "./LabFormFields.vue";

const props = defineProps(["params"]);
const { t } = useI18n();
const { init } = useToast();
const onupdated = inject("onupdated");

const endpoint = props.params.endpoint;
const fields = props.params.fields || [];

const show = ref(false);
const model = reactive({ id: props.params.data.id });

const open = async () => {
  show.value = true;
  try {
    const { data } = await axios.get(`${endpoint}/${props.params.data.id}`);
    fields.forEach((f) => {
      model[f.key] = data?.[f.key] ?? null;
    });
    model.id = props.params.data.id;
  } catch (e) {
    console.error("Error fetching row:", e);
  }
};

const onSubmit = async () => {
  try {
    const { data } = await axios.put(endpoint, model);
    if (data.status === 200) {
      onupdated(props.params.node, data.unit);
      show.value = false;
      init({ message: t('login.successMessage'), color: "success" });
    } else {
      console.error("Error saving:", data.message);
    }
  } catch (e) {
    console.error("Error saving:", e);
  }
};
</script>
