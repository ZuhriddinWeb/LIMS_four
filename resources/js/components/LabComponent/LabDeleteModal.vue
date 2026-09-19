<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="delete" preset="primary" class="mt-1" @click="show = true" />
    <VaModal
      v-model="show"
      :ok-text="t('modals.apply')"
      :cancel-text="t('modals.cancel')"
      @close="show = false"
      @ok="onSubmit"
      close-button
    >
      <h3 class="va-h3">{{ t('modals.title') }}</h3>
      <p>{{ t('modals.message') }}</p>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, inject } from "vue";
import axios from "axios";
import { useI18n } from "vue-i18n";
import { VaButton, VaModal, useToast } from "vuestic-ui";

const props = defineProps(["params"]);
const { t } = useI18n();
const { init } = useToast();
const ondeleted = inject("ondeleted");

const endpoint = props.params.endpoint;
const show = ref(false);

const onSubmit = async () => {
  try {
    const { data } = await axios.delete(`${endpoint}/${props.params.data.id}`);
    if (data.status === 200) {
      ondeleted(props.params.data);
      show.value = false;
      init({ message: t('login.successMessage'), color: "success" });
    } else {
      console.error("Error deleting:", data.message);
    }
  } catch (e) {
    console.error("Error deleting:", e);
  }
};
</script>
