<template>
  <div class="lims-page ref-page">
    <div class="lims-head">
      <div>
        <h1 class="lims-title">{{ title }}</h1>
        <p class="lims-sub">{{ L('Справочник', 'Ma\'lumotnoma', 'Reference') }}</p>
      </div>
      <button v-if="canCreate" class="lims-btn" @click="openAdd"><span class="material-icons">add</span>{{ L('Добавить', "Qo'shish", 'Add') }}</button>
    </div>

    <div class="lims-panel ref-grid-wrap">
      <ag-grid-vue
        :rowData="rowData"
        :columnDefs="columnDefs"
        :defaultColDef="defaultColDef"
        animateRows="true"
        class="ag-theme-material"
        style="height:100%;width:100%"
        @gridReady="(p) => (gridApi = p.api)"
      ></ag-grid-vue>
    </div>

    <VaModal
      v-model="showAdd"
      :ok-text="L('Сохранить', 'Saqlash', 'Save')"
      :cancel-text="L('Отмена', 'Bekor qilish', 'Cancel')"
      @ok="onAdd"
      close-button
    >
      <h3 class="va-h3 mb-2">{{ title }}</h3>
      <LabFormFields v-if="showAdd" :fields="config.fields" :model="addModel" />
    </VaModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, provide, watch, onMounted } from "vue";
import axios from "axios";
import "vuestic-ui/dist/vuestic-ui.css";
import { VaModal, useToast } from "vuestic-ui";
import { useStore } from "vuex";
import { useLang } from "../../composables/useLang.js";
import { getLabConfig } from "./referenceConfigs.js";
import LabFormFields from "../../components/LabComponent/LabFormFields.vue";
import LabEditModal from "../../components/LabComponent/LabEditModal.vue";
import LabDeleteModal from "../../components/LabComponent/LabDeleteModal.vue";

const props = defineProps({ configKey: { type: String, required: true } });

const { L, locale } = useLang();
const store = useStore();
const { init } = useToast();

const pick = (obj) => (obj ? obj[locale.value] || obj.ru || obj.uz : "");
const config = computed(() => getLabConfig(props.configKey));
const title = computed(() => (config.value ? pick(config.value.title) : ""));

const userRole = computed(() => store.state.user?.roles?.find((r) => r.name === config.value?.role));
const hasPermission = (perm) => Number(userRole.value?.pivot?.[perm]) === 1;
const canCreate = computed(() => hasPermission("create"));
const canUpdate = computed(() => hasPermission("update"));
const canDelete = computed(() => hasPermission("delete"));

const rowData = ref([]);
const gridApi = ref(null);
const showAdd = ref(false);
const addModel = reactive({});

const colLabel = (c) => (c.label ? pick(c.label) : c.key);
const dataName = (row, base) => (locale.value === "uz" ? row?.[base] : row?.[base + "Rus"] || row?.[base]);

const columnDefs = computed(() => {
  if (!config.value) return [];
  const cols = [{ headerName: "№", valueGetter: "node.rowIndex + 1", width: 70 }];
  for (const c of config.value.columns) {
    if (c.key === "__name__") {
      cols.push({ headerName: colLabel(c), valueGetter: (p) => dataName(p.data, "Name"), flex: c.flex || 1, width: c.width });
    } else if (c.localizedBase) {
      cols.push({ headerName: colLabel(c), valueGetter: (p) => dataName(p.data, c.localizedBase), flex: c.flex, width: c.width });
    } else {
      cols.push({ headerName: colLabel(c), field: c.key, flex: c.flex, width: c.width });
    }
  }
  if (canUpdate.value) {
    cols.push({ headerName: "", width: 70, cellClass: ["px-0"], cellRenderer: LabEditModal, cellRendererParams: { endpoint: config.value.endpoint, fields: config.value.fields } });
  }
  if (canDelete.value) {
    cols.push({ headerName: "", width: 70, cellClass: ["px-0"], cellRenderer: LabDeleteModal, cellRendererParams: { endpoint: config.value.endpoint, fields: config.value.fields } });
  }
  return cols;
});

const defaultColDef = { sortable: true, filter: true };

function ondeleted(row) { gridApi.value?.applyTransaction({ remove: [row] }); }
function onupdated(node, data) { node.setData(data); }
provide("ondeleted", ondeleted);
provide("onupdated", onupdated);

const fetchData = async () => {
  if (!config.value) return;
  try {
    const { data } = await axios.get(config.value.endpoint);
    rowData.value = Array.isArray(data) ? data : data.items || [];
  } catch (e) { console.error("Error fetching data:", e); }
};

const openAdd = () => {
  Object.keys(addModel).forEach((k) => delete addModel[k]);
  config.value.fields.forEach((f) => (addModel[f.key] = null));
  showAdd.value = true;
};

const onAdd = async () => {
  try {
    const { data } = await axios.post(config.value.endpoint, addModel);
    if (data.status === 200) {
      showAdd.value = false;
      await fetchData();
      init({ message: L("Сохранено", "Saqlandi", "Saved"), color: "success" });
    }
  } catch (e) { console.error("Error saving:", e); }
};

onMounted(() => {
  const savedLocale = localStorage.getItem("locale");
  if (savedLocale) locale.value = savedLocale;
  fetchData();
});

watch(() => props.configKey, fetchData);
</script>

<style scoped>
.ref-page { display: flex; flex-direction: column; }
.ref-grid-wrap { flex: 1; min-height: 420px; padding: 0; overflow: hidden; }
</style>
<style>
.ag-theme-material .ag-cell { border-right: 1px solid var(--lims-line); }
.ag-theme-material .ag-header-cell { border-right: 1px solid var(--lims-line); }
.ag-theme-material .ag-row { border-bottom: 1px solid var(--lims-line); }
</style>
