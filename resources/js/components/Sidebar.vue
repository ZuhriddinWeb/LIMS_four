<template>
  <VaLayout :top="{ fixed: true, order: 1 }" :left="{
    fixed: true,
    absolute: breakpoints.smDown,
    order: 2,
    overlay: breakpoints.smDown && isSidebarVisible,
    class: 'custom-sidebar'
  }" @left-overlay-click="isSidebarVisible = false">
    <template #top>
      <VaNavbar>
        <template #left>
          <VaButton preset="secondary" :icon="isSidebarVisible ? 'menu_open' : 'menu'"
            @click="isSidebarVisible = !isSidebarVisible" />
        </template>
        <template #center>
          <div v-if="store.state.user && store.state.user.name" class="flex justify-between font-semibold">
            <div class="flex justify-between items-center">
              <span class="material-icons user-icon text-blue-800">account_circle</span>
              <p class="ml-1">{{ t('table.user') }}: {{ store.state.user.name }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-blue-800">manage_history</span>
              <p class="ml-1">{{ t('table.change') }}: {{ displayValue }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-blue-800">diamond</span>
              <p class="ml-1">{{ t('table.parameters') }}: {{ paramCount }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-green-500">diamond</span>
              <p class="ml-1">{{ t('table.input') }}: {{ store.state.countInputedParams }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-orange-500">diamond</span>
              <p class="ml-1">{{ t('table.output') }}: {{ paramCount - store.state.countInputedParams }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-cyan-800">timelapse</span>
              <p class="ml-1">{{ t('menu.timer') }}: {{ currentTime }} </p>
            </div>
          </div>
        </template>
        <template #right>
          <div class="flex items-center gap-2 mr-8">
            <!-- Оповещения -->
            <button class="nav-bell-btn" @click="goAlerts" :title="t('menu.lab_alerts')">
              <VaIcon name="notifications" size="small" />
              <span v-if="alertCount > 0" class="nav-bell-badge">{{ alertCount > 99 ? '99+' : alertCount }}</span>
            </button>
            <!-- Тема -->
            <button class="nav-theme-btn" @click="toggleTheme" :title="isDark ? 'Светлая тема' : 'Тёмная тема'">
              <VaIcon :name="isDark ? 'light_mode' : 'dark_mode'" size="small" />
            </button>
            <!-- Язык -->
            <div class="nav-lang-switch">
              <button v-for="l in langs" :key="l" class="nav-lang-btn" :class="{ active: locale === l }" @click="setLocale(l)">
                {{ l.toUpperCase() }}
              </button>
            </div>
            <VaButton preset="secondary" @click="handleLogout">
              <VaIcon name="logout" style="margin-right: 0.5rem;" />
              {{ t('menu.logout') }}
            </VaButton>
          </div>
        </template>
      </VaNavbar>
      <VaDivider style="margin: 0" />
    </template>
    <template #left>
      <VaSidebar v-model="isSidebarVisible" class="custom-sidebar">
        <!-- Главная -->
        <MenuItem v-if="homeVisible" :menuItem="homeItem" />

        <!-- Группы со сворачиванием («шторки») -->
        <template v-for="group in visibleGroups" :key="group.key">
          <div class="menu-group-header" @click="toggleGroup(group.key)">
            <span class="menu-group-title">{{ t(group.title) }}</span>
            <VaIcon :name="collapsed[group.key] ? 'expand_more' : 'expand_less'" size="small" />
          </div>
          <template v-if="!collapsed[group.key]">
            <MenuItem v-for="item in group.items" :key="item.title" :menuItem="item" />
          </template>
        </template>
      </VaSidebar>
    </template>
    <template #content>
      <main>
        <article>
          <slot />
        </article>
      </main>
    </template>
  </VaLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useBreakpoint, useColors } from 'vuestic-ui';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import MenuItem from './MenuItem.vue'; // Import the MenuItem component

const breakpoints = useBreakpoint();
const store = useStore();
const router = useRouter();
const { t, locale } = useI18n();
const paramCount = ref(0);
const currentTime = ref('');
const isSidebarVisible = ref(false); // Initialize as false

watch(() => breakpoints.smUp, (newValue) => {
  if (newValue) {
    isSidebarVisible.value = store.state.user ? false : true; // Hide if user is logged in
  } else {
    isSidebarVisible.value = newValue;
  }
});

const currentHour = new Date().getHours();
const displayValue = computed(() => (currentHour >= 8 && currentHour < 20) ? 1 : 2);

const updateCurrentTime = () => {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString();
};

// const fetchParameterCount = async () => {
//   try {
//     const change = (currentHour >= 8 && currentHour < 20) ? 1 : 2;
//     const response = await axios.get(`/get-params-for-user-count/${store.state.user.structure_id}/${change}`);
//     paramCount.value = response.data;
//   } catch (error) {
//     console.error('Error fetching parameters count:', error);
//   }
// };

// Главная — всегда сверху, вне групп.
const homeItem = { title: 'menu.home', icon: 'home', path: '/' };

// Группы меню со сворачиванием («шторки»):
//  - general: разделы первой версии системы (не лаборатория)
//  - lab: оперативные разделы лаборатории
//  - labref: справочники лаборатории
const menuGroups = ref([
  {
    key: 'general',
    title: 'menu.section_general',
    items: [
      { title: 'menu.structure', icon: 'dashboard', path: '/structure' },
      { title: 'menu.blogs', icon: 'account_tree', path: '/blogs' },
      { title: 'menu.units', icon: 'ad_units', path: '/units' },
      { title: 'menu.period', icon: 'hourglass_top', path: '/periodType' },
      { title: 'menu.graphics', icon: 'schedule', path: '/graphics' },
      { title: 'menu.term', icon: 'gavel', path: '/graphicterms' },
      { title: 'menu.graphictimes', icon: 'alarm', path: '/graphictimes' },
      { title: 'menu.pages', icon: 'auto_stories', path: '/pages' },
      { title: 'menu.groups', icon: 'table_view', path: '/group' },
      { title: 'menu.params', icon: 'format_list_bulleted', path: '/params' },
      { title: 'menu.static', icon: 'data_usage', path: '/cardPageStatic' },
      { title: 'menu.formula', icon: 'calculate', path: '/formula' },
      { title: 'menu.paramgraphics', icon: 'schema', path: '/paramgraphics' },
      { title: 'menu.create_document', icon: 'history_edu', path: '/createDoc' },
      { title: 'menu.document', icon: 'file_present', path: '/documents' },
      { title: 'menu.operator_count', icon: 'flag_circle', path: '/opercount' },
      { title: 'menu.vparams', icon: 'diamond', path: '/vparams' },
      { title: 'menu.vparamsHorizontal', icon: 'grid_goldenratio', path: '/vparamsHorizontal' },
      { title: 'menu.users', icon: 'person', path: '/users' },
    ],
  },
  {
    key: 'lab',
    title: 'menu.section_lab',
    items: [
      { title: 'menu.lab_samples', icon: 'assignment', path: '/lab/samples' },
      { title: 'menu.lab_journal', icon: 'menu_book', path: '/lab/journal' },
      { title: 'menu.lab_certificates', icon: 'verified', path: '/lab/certificates' },
      { title: 'menu.lab_process', icon: 'table_chart', path: '/lab/process' },
      { title: 'menu.lab_qc', icon: 'insights', path: '/lab/qc' },
      { title: 'menu.lab_instruments', icon: 'handyman', path: '/lab/equipment' },
      { title: 'menu.lab_ai', icon: 'smart_toy', path: '/lab/ai' },
      { title: 'menu.lab_alerts', icon: 'notifications_active', path: '/lab/alerts' },
      { title: 'menu.lab_audit', icon: 'history', path: '/lab/audit' },
    ],
  },
  {
    key: 'labref',
    title: 'menu.section_lab_ref',
    items: [
      { title: 'menu.lab_products', icon: 'inventory_2', path: '/lab/products' },
      { title: 'menu.lab_standards', icon: 'straighten', path: '/lab/standards' },
      { title: 'menu.lab_laboratories', icon: 'account_balance', path: '/lab/laboratories' },
      { title: 'menu.lab_groups', icon: 'groups', path: '/lab/groups' },
      { title: 'menu.lab_departments', icon: 'apartment', path: '/lab/departments' },
      { title: 'menu.lab_storage', icon: 'inventory', path: '/lab/storage' },
      { title: 'menu.lab_points', icon: 'colorize', path: '/lab/points' },
      { title: 'menu.lab_types', icon: 'science', path: '/lab/types' },
      { title: 'menu.lab_analytes', icon: 'biotech', path: '/lab/analytes' },
      { title: 'menu.lab_methods', icon: 'labs', path: '/lab/methods' },
    ],
  },
]);

// Состояние сворачивания групп (сохраняется в localStorage).
const collapsed = ref({ general: true, lab: false, labref: false });
const toggleGroup = (key) => {
  collapsed.value[key] = !collapsed.value[key];
  try {
    localStorage.setItem('menuCollapsed', JSON.stringify(collapsed.value));
  } catch (e) { /* ignore */ }
};

const handleLogout = async () => {
  try {
    await store.dispatch('logout');
    router.push({ name: 'login' });
    isSidebarVisible.value = false; // Hide sidebar on logout
  } catch (error) {
    console.error('Error during logout:', error);
  }
};

// Оповещения: счётчик в колокольчике, периодически обновляется.
const alertCount = ref(0);
const goAlerts = () => router.push({ name: 'lab-alerts' });
const fetchAlertCount = async () => {
  if (!store.state.user) return;
  try {
    const { data } = await axios.get('/lab/alerts');
    alertCount.value = data?.counts?.total ?? 0;
  } catch (e) { /* тихо игнорируем (напр. не авторизован) */ }
};

// Язык: 3 варианта (узбекский, русский, английский).
const langs = ['uz', 'ru', 'en'];
const setLocale = (l) => {
  locale.value = l;
  try { localStorage.setItem('locale', l); } catch (e) { /* ignore */ }
};

// Тема: светлая / тёмная (пресеты Vuestic + класс на <html> для остального UI).
const { applyPreset, currentPresetName } = useColors();
const isDark = computed(() => currentPresetName.value === 'dark');
const applyTheme = (name) => {
  applyPreset(name);
  document.documentElement.classList.toggle('dark-theme', name === 'dark');
  try { localStorage.setItem('theme', name); } catch (e) { /* ignore */ }
};
const toggleTheme = () => applyTheme(isDark.value ? 'light' : 'dark');

// Доступен ли пункт: есть роль с таким именем и правом просмотра.
const roleAllowed = (title) => {
  const user = store.state.user;
  if (!user) return false;
  return (user.roles || []).some(r => Number(r?.pivot?.view) === 1 && r.name === title);
};

const homeVisible = computed(() => roleAllowed(homeItem.title));

// Группы с отфильтрованными по правам пунктами; пустые группы скрываются.
const visibleGroups = computed(() =>
  menuGroups.value
    .map(g => ({ ...g, items: g.items.filter(it => roleAllowed(it.title)) }))
    .filter(g => g.items.length)
);

onMounted(() => {
  // fetchParameterCount();
  const savedLocale = localStorage.getItem('locale');
  if (savedLocale) {
    locale.value = savedLocale;
  }
  updateCurrentTime();
  setInterval(updateCurrentTime, 1000);

  // Применить тему (по умолчанию тёмная/премиальная).
  applyTheme(localStorage.getItem('theme') === 'light' ? 'light' : 'dark');

  // Восстановить состояние сворачивания групп меню.
  try {
    const saved = JSON.parse(localStorage.getItem('menuCollapsed') || 'null');
    if (saved && typeof saved === 'object') {
      collapsed.value = { ...collapsed.value, ...saved };
    }
  } catch (e) { /* ignore */ }

  // Check if user is logged in and set sidebar visibility
  if (store.state.user) {
    isSidebarVisible.value = false;
  }

  // Счётчик оповещений: сразу и каждые 60 секунд.
  fetchAlertCount();
  setInterval(fetchAlertCount, 60000);
});
</script>


<style>
.custom-sidebar {
  background-color: #0f1729;
}

/* Колокольчик оповещений */
.nav-bell-btn {
  position: relative;
  display: inline-flex; align-items: center; justify-content: center;
  width: 34px; height: 34px; border-radius: 8px;
  color: #cdd8ea; background: transparent; border: none; cursor: pointer;
  transition: background-color .15s;
}
.nav-bell-btn:hover { background-color: rgba(127, 127, 127, 0.3); }
.nav-bell-badge {
  position: absolute; top: -2px; right: -2px;
  min-width: 17px; height: 17px; padding: 0 4px;
  display: flex; align-items: center; justify-content: center;
  background: #f43f5e; color: #fff; font-size: 10px; font-weight: 700;
  border-radius: 999px; line-height: 1;
}

/* Переключатели темы/языка в верхней панели */
.nav-theme-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 30px; height: 28px; border: none; border-radius: 6px; cursor: pointer;
  color: inherit; background-color: rgba(127, 127, 127, 0.16);
}
.nav-theme-btn:hover { background-color: rgba(127, 127, 127, 0.3); }
.nav-lang-switch { display: flex; gap: 2px; }
.nav-lang-btn {
  min-width: 30px; height: 28px; padding: 0 7px; border: none; border-radius: 6px;
  cursor: pointer; font-size: 0.7rem; font-weight: 700; color: inherit;
  background-color: rgba(127, 127, 127, 0.16);
}
.nav-lang-btn:hover { background-color: rgba(127, 127, 127, 0.3); }
.nav-lang-btn.active { background-color: #154EC1; color: #fff; }

/* Заголовок сворачиваемой группы меню («шторка») */
.menu-group-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.55rem 0.9rem;
  margin-top: 0.25rem;
  cursor: pointer;
  user-select: none;
  background-color: #16203a;
  color: #8fa1bf;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-top: 1px solid #24304a;
  border-bottom: 1px solid #24304a;
}
.menu-group-header:hover {
  background-color: #1e2b48;
  color: #c7d3e6;
}
.menu-group-title {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.hover-item:hover {
  background-color: #154EC1;
  color: white;
}

.hover-logout:hover {
  background-color: rgb(220 38 38);
  color: white;
}
</style>
