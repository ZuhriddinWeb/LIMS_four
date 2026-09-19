<template>
  <div class="lims-page">
    <div class="lims-head">
      <div>
        <h1 class="lims-title">
          <span class="material-icons" style="vertical-align:-4px;color:var(--lims-accent)">notifications_active</span>
          {{ L('Центр оповещений', 'Ogohlantirishlar markazi', 'Alerts center') }}
        </h1>
        <p class="lims-sub">{{ L('Что требует внимания прямо сейчас', 'Hozir e\'tibor talab qiladigan narsalar', 'What needs attention right now') }}</p>
      </div>
      <button class="lims-btn ghost" @click="fetchData"><span class="material-icons">refresh</span>{{ L('Обновить', 'Yangilash', 'Refresh') }}</button>
    </div>

    <div class="alert-grid">
      <!-- Ожидают проверки/утверждения -->
      <div class="lims-panel lims-panel-pad alert-card">
        <div class="ac-head">
          <span class="material-icons ac-ico" style="color:#f59e0b">fact_check</span>
          <div class="ac-title">{{ L('Ожидают проверки/утверждения', 'Tekshirish/tasdiqlash kutmoqda', 'Awaiting review/approval') }}</div>
          <span class="chip" :class="data.counts.pending ? 'chip-in_progress' : 'chip-tested'">{{ data.counts.pending }}</span>
        </div>
        <table class="lims-table" v-if="data.pending.length">
          <tbody>
            <tr v-for="p in data.pending" :key="p.id">
              <td class="mono" style="width:150px">{{ p.sampleCode }}</td>
              <td><b>{{ p.analyte }}</b></td>
              <td style="width:130px"><span class="chip" :class="p.stage === 'reviewed' ? 'chip-approved' : 'chip-new'">{{ stageLabel(p.stage) }}</span></td>
            </tr>
          </tbody>
        </table>
        <div v-else class="lims-empty">{{ L('Нет', "Yo'q", 'None') }}</div>
      </div>

      <!-- Поверка приборов -->
      <div class="lims-panel lims-panel-pad alert-card">
        <div class="ac-head">
          <span class="material-icons ac-ico" style="color:#22d3ee">construction</span>
          <div class="ac-title">{{ L('Поверка/калибровка приборов', 'Asboblar tekshiruvi', 'Instrument calibration') }}</div>
          <span class="chip" :class="data.counts.calibration ? 'chip-in_progress' : 'chip-tested'">{{ data.counts.calibration }}</span>
        </div>
        <table class="lims-table" v-if="data.calibration.length">
          <tbody>
            <tr v-for="c in data.calibration" :key="c.id">
              <td>{{ c.name }}</td>
              <td class="mono" style="width:120px">{{ (c.due || '').slice(0,10) }}</td>
              <td style="width:130px">
                <span class="chip" :class="c.overdue ? 'chip-reject' : 'chip-in_progress'">
                  {{ c.overdue ? L('просрочено', 'muddati o\'tgan', 'overdue') : L('через', 'qoldi', 'in') + ' ' + c.daysLeft + ' ' + L('дн.', 'kun', 'd') }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else class="lims-empty">{{ L('Нет', "Yo'q", 'None') }}</div>
      </div>

      <!-- ВЛК вне допуска -->
      <div class="lims-panel lims-panel-pad alert-card">
        <div class="ac-head">
          <span class="material-icons ac-ico" style="color:#f43f5e">warning</span>
          <div class="ac-title">{{ L('ВЛК вне допуска (30 дн.)', 'IQN ruxsatdan tashqari (30 kun)', 'QC out of tolerance (30d)') }}</div>
          <span class="chip" :class="data.counts.qcOut ? 'chip-reject' : 'chip-tested'">{{ data.counts.qcOut }}</span>
        </div>
        <table class="lims-table" v-if="data.qcOut.length">
          <tbody>
            <tr v-for="q in data.qcOut" :key="q.id">
              <td><b>{{ q.analyte }}</b></td>
              <td class="mono">{{ q.measured }} <span style="opacity:.5">/ {{ q.certified }}</span></td>
              <td class="mono" style="width:150px">{{ (q.at || '').replace('T',' ').slice(0,16) }}</td>
            </tr>
          </tbody>
        </table>
        <div v-else class="lims-empty">{{ L('Нет', "Yo'q", 'None') }}</div>
      </div>

      <!-- Истекает срок хранения -->
      <div class="lims-panel lims-panel-pad alert-card">
        <div class="ac-head">
          <span class="material-icons ac-ico" style="color:#38bdf8">inventory_2</span>
          <div class="ac-title">{{ L('Срок хранения проб', 'Namuna saqlash muddati', 'Sample storage expiry') }}</div>
          <span class="chip" :class="data.counts.storageExpiring ? 'chip-in_progress' : 'chip-tested'">{{ data.counts.storageExpiring }}</span>
        </div>
        <table class="lims-table" v-if="data.storageExpiring.length">
          <tbody>
            <tr v-for="s in data.storageExpiring" :key="s.id">
              <td class="mono" style="width:150px">{{ s.sampleCode }}</td>
              <td class="mono">{{ (s.until || '').slice(0,10) }}</td>
              <td style="width:130px">
                <span class="chip" :class="s.expired ? 'chip-reject' : 'chip-in_progress'">
                  {{ s.expired ? L('истёк', 'o\'tgan', 'expired') : L('через', 'qoldi', 'in') + ' ' + s.daysLeft + ' ' + L('дн.', 'kun', 'd') }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else class="lims-empty">{{ L('Нет', "Yo'q", 'None') }}</div>
      </div>

      <!-- Просроченные пробы -->
      <div class="lims-panel lims-panel-pad alert-card">
        <div class="ac-head">
          <span class="material-icons ac-ico" style="color:#a78bfa">schedule</span>
          <div class="ac-title">{{ L('Просроченные пробы (TAT)', 'Muddati o\'tgan namunalar', 'Overdue samples (TAT)') }}</div>
          <span class="chip" :class="data.counts.overdueSamples ? 'chip-reject' : 'chip-tested'">{{ data.counts.overdueSamples }}</span>
        </div>
        <table class="lims-table" v-if="data.overdueSamples.length">
          <tbody>
            <tr v-for="s in data.overdueSamples" :key="s.id">
              <td class="mono" style="width:150px">{{ s.sampleCode }}</td>
              <td>{{ statusLabel(s.status) }}</td>
              <td style="width:110px"><span class="chip chip-reject">{{ s.ageDays }} {{ L('дн.', 'kun', 'd') }}</span></td>
            </tr>
          </tbody>
        </table>
        <div v-else class="lims-empty">{{ L('Нет', "Yo'q", 'None') }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import axios from "axios";
import { useLang } from "../../composables/useLang.js";

const { L, locale } = useLang();

const data = reactive({
  pending: [], calibration: [], qcOut: [], storageExpiring: [], overdueSamples: [],
  counts: { pending: 0, calibration: 0, qcOut: 0, storageExpiring: 0, overdueSamples: 0, total: 0 },
});

const stages = {
  entered: { ru: "нужна проверка", uz: "tekshirish kerak", en: "needs review" },
  reviewed: { ru: "нужно утвердить", uz: "tasdiqlash kerak", en: "needs approval" },
};
const stageLabel = (s) => (stages[s] ? L(stages[s].ru, stages[s].uz, stages[s].en) : s);

const statuses = {
  new: { ru: "Новый", uz: "Yangi", en: "New" },
  in_progress: { ru: "В процессе", uz: "Jarayonda", en: "In progress" },
};
const statusLabel = (s) => (statuses[s] ? L(statuses[s].ru, statuses[s].uz, statuses[s].en) : s);

const fetchData = async () => {
  try {
    const { data: d } = await axios.get("/lab/alerts");
    Object.assign(data, d);
  } catch (e) { console.error(e); }
};

onMounted(() => {
  const savedLocale = localStorage.getItem("locale");
  if (savedLocale) locale.value = savedLocale;
  fetchData();
});
</script>

<style scoped>
.alert-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 900px) { .alert-grid { grid-template-columns: 1fr; } }
.alert-card { display: flex; flex-direction: column; }
.ac-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.ac-ico { font-size: 22px; }
.ac-title { font-weight: 700; font-size: 0.95rem; flex: 1; color: var(--lims-title); }
</style>
