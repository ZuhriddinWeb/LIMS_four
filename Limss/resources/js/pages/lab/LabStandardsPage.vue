<template>
  <div class="lims-page">
    <div class="lims-head">
      <div>
        <h1 class="lims-title">{{ L('Стандартные образцы', 'Standart namunalar', 'Reference standards') }}</h1>
        <p class="lims-sub">{{ L('ГСО / СОП с аттестованными значениями', 'GSO / SOP', 'CRM / SOP with certified values') }}</p>
      </div>
      <button v-if="canCreate" class="lims-btn" @click="openCreate"><span class="material-icons">add</span>{{ L('Добавить СО', "SN qo'shish", 'Add standard') }}</button>
    </div>

    <div class="lims-panel" style="overflow-x:auto">
      <table class="lims-table">
        <thead>
          <tr>
            <th style="width:44px">№</th>
            <th>{{ L('Код', 'Kod', 'Code') }}</th>
            <th>{{ L('Наименование', 'Nomi', 'Name') }}</th>
            <th style="width:100px">{{ L('Тип', 'Turi', 'Type') }}</th>
            <th style="width:130px">{{ L('Годен до', 'Muddati', 'Valid until') }}</th>
            <th style="width:120px">{{ L('Показателей', "Ko'rsatkich", 'Analytes') }}</th>
            <th style="width:90px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(s, i) in standards" :key="s.id">
            <td>{{ i + 1 }}</td>
            <td class="mono">{{ s.Code }}</td>
            <td>{{ isRu ? (s.NameRus || s.Name) : s.Name }}</td>
            <td>{{ s.StandardType }}</td>
            <td>{{ (s.ValidUntil || '').slice(0, 10) }}</td>
            <td>{{ s.ValuesCount }}</td>
            <td style="white-space:nowrap">
              <button class="lims-iconbtn" :title="L('Изменить', 'Tahrirlash', 'Edit')" @click="openEdit(s.id)"><span class="material-icons">edit</span></button>
              <button v-if="canDelete" class="lims-iconbtn danger" :title="L('Удалить', 'O\'chirish', 'Delete')" @click="removeStandard(s)"><span class="material-icons">delete</span></button>
            </td>
          </tr>
          <tr v-if="!standards.length"><td colspan="7" class="lims-empty">{{ L('Нет образцов', "Namuna yo'q", 'No standards') }}</td></tr>
        </tbody>
      </table>
    </div>

    <LabStandardForm v-model="showForm" :standard-id="activeId" @saved="fetchStandards" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import "vuestic-ui/dist/vuestic-ui.css";
import { useToast } from "vuestic-ui";
import { useStore } from "vuex";
import { useLang } from "../../composables/useLang.js";
import LabStandardForm from "../../components/LabComponent/LabStandardForm.vue";

const { L, isRu, locale } = useLang();
const store = useStore();
const { init } = useToast();

const userRole = computed(() => store.state.user?.roles?.find((r) => r.name === "menu.lab_standards"));
const hasPermission = (perm) => Number(userRole.value?.pivot?.[perm]) === 1;
const canCreate = computed(() => hasPermission("create"));
const canDelete = computed(() => hasPermission("delete"));

const standards = ref([]);
const showForm = ref(false);
const activeId = ref(null);

const fetchStandards = async () => {
  try {
    const { data } = await axios.get("/lab/standards");
    standards.value = Array.isArray(data) ? data : [];
  } catch (e) { console.error(e); }
};

const openCreate = () => { activeId.value = null; showForm.value = true; };
const openEdit = (id) => { activeId.value = id; showForm.value = true; };
const removeStandard = async (s) => {
  if (!confirm(L("Удалить СО ", "SN o'chirish ", "Delete standard ") + (s.Code || s.Name) + "?")) return;
  try {
    await axios.delete(`/lab/standards/${s.id}`);
    init({ message: L("Удалено", "O'chirildi", "Deleted"), color: "success" });
    fetchStandards();
  } catch (e) { console.error(e); }
};

onMounted(() => {
  const savedLocale = localStorage.getItem("locale");
  if (savedLocale) locale.value = savedLocale;
  fetchStandards();
});
</script>
