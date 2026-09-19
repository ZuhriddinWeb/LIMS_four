
import { createApp } from "vue";
import App from "./App.vue";
import axios from "axios";
import router from "./router";
import store from "./store";
import { createVuestic } from "vuestic-ui";
import "vuestic-ui/css";
// import Swal from "sweetalert2";
// import "sweetalert2/src/sweetalert2.scss";
import "ag-grid-community/styles/ag-grid.css";
import "ag-grid-community/styles/ag-theme-quartz.css";
import "ag-grid-community/styles/ag-theme-material.css";
import 'devextreme/dist/css/dx.material.blue.light.css'; 
// import 'devextreme/dist/css/dx.common.css';
import { Bar } from "vue-chartjs";
import { AgGridVue } from "ag-grid-vue3";
import { createI18n } from "vue-i18n";
import locale from "./locale.js";
import "material-icons/iconfont/material-icons.css";
import { useToast } from "vuestic-ui";
import InputMask from 'vue-input-mask';
import 'material-symbols/outlined.css';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import VueExcelEditor from 'vue3-excel-editor'
import HighchartsVue from 'highcharts-vue';
import "./lims-ui.css";
axios.defaults.baseURL = "/api/";
window.axios = axios;
axios.defaults.withCredentials = true;
// Токен авторизации из localStorage — сразу, до монтирования (иначе первый
// fetch страницы уходит без заголовка → 401 → пустые данные при загрузке).
const savedToken = localStorage.getItem("token");
if (savedToken) {
  axios.defaults.headers.common["Authorization"] = savedToken;
}

// Если токен истёк/отозван — сервер вернёт 401. Мягко выкидываем на вход
// (только когда токен был — иначе стартовый /user без токена вызвал бы петлю).
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error?.response?.status === 401 && localStorage.getItem("token")) {
      localStorage.removeItem("token");
      delete axios.defaults.headers.common["Authorization"];
      if (router.currentRoute.value?.name !== "login") {
        router.push({ name: "login" }).catch(() => {});
      }
    }
    return Promise.reject(error);
  }
);


// window.Swal = Swal;
window.store = store;
window.router = router;
window.router = useToast;
// window.Pusher = Pusher;

// console.log(window.location.hostname);

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
//     wsHost: window.location.hostname,
//     wsPort: 6001,
//     forceTLS: false,
//     disableStats: true,
// });

const i18n = createI18n({
  legacy: false,
  locale: "uz",
  fallbackLocale: "ru",
  messages: locale,
});

window.i18n = i18n;

async function initApp() {
  await store.dispatch("getUser");
  const app = createApp(App)
    .use(createVuestic({
      config: {
        colors: {
          currentPresetName: localStorage.getItem('theme') === 'light' ? 'light' : 'dark',
          presets: {
            light: {
              backgroundPrimary: '#ffffff',
              backgroundSecondary: '#f4f6f8',
              backgroundElement: '#ECF0F1',
              backgroundBorder: '#e5e7eb',
              textPrimary: '#262824',
              primary: '#154EC1',
            },
            dark: {
              backgroundPrimary: '#0f172a',
              backgroundSecondary: '#1e293b',
              backgroundElement: '#1e293b',
              backgroundBorder: '#334155',
              textPrimary: '#e2e8f0',
              primary: '#3b82f6',
            },
          },
        },
      },
    }))
    .component("AgGridVue", AgGridVue)
    .component("Bar", Bar)
    .component('input-mask', InputMask)
    .use(store)
    .use(router)
    .use(i18n)
    .use(useToast)
    .use(HighchartsVue)
    .use(VueExcelEditor)
    .mount("#app");
}

initApp();
