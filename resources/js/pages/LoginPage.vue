<template>
  <main class="login-wrap">
    <!-- Брендовая панель -->
    <section class="login-brand">
      <div class="brand-glow brand-glow-1"></div>
      <div class="brand-glow brand-glow-2"></div>
      <div class="brand-inner">
        <img :src="logo" alt="logo" class="brand-logo" />
        <div class="brand-title">{{ companyShort || t('login.titlePage') }}</div>
        <div class="brand-plant">{{ plantName }}</div>
        <div class="brand-sub">{{ L('Лабораторная информационная система', 'Laboratoriya axborot tizimi', 'Laboratory Information Management System') }}</div>
      </div>
      <div class="brand-foot">LIMS · NGMK</div>
    </section>

    <!-- Форма входа -->
    <section class="login-panel">
      <div class="login-topbar">
        <button class="login-lang" @click="cycleLanguage">
          <span class="material-icons" style="font-size:18px">language</span>
          {{ currentLanguageLabel }}
        </button>
      </div>

      <div class="login-center">
        <VaForm ref="form" @submit.prevent="onSubmit" class="login-card">
          <div class="login-badge"><span class="material-icons">science</span></div>
          <h1 class="login-h1">{{ t('login.title') }}</h1>
          <p class="login-h2">{{ L('Войдите в систему', 'Tizimga kiring', 'Sign in to continue') }}</p>

          <VaInput v-model="result.login"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" class="login-input"
            :label="t('form.login')" type="text" />

          <VaValue v-slot="isPasswordVisible" :default-value="false">
            <VaInput v-model="result.password"
              :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]"
              :type="isPasswordVisible.value ? 'text' : 'password'" class="login-input"
              :label="t('form.password')"
              @keyup.enter="onSubmit"
              @clickAppendInner.stop="isPasswordVisible.value = !isPasswordVisible.value">
              <template #appendInner>
                <VaIcon :name="isPasswordVisible.value ? 'mso-visibility_off' : 'mso-visibility'"
                  class="cursor-pointer" color="secondary" />
              </template>
            </VaInput>
          </VaValue>

          <button type="button" class="login-submit" @click="onSubmit">
            <span class="material-icons" style="font-size:19px">login</span>
            {{ t('login.submitButton') }}
          </button>
        </VaForm>
      </div>

      <div class="login-copyright">© {{ year }} {{ company || 'Navoi Mining & Metallurgical Company' }}</div>
    </section>
  </main>
</template>


<script lang="ts" setup>
import { reactive, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useForm, useToast, VaValue, VaInput, VaForm, VaIcon } from 'vuestic-ui';
import store from '../store';
import { useLang } from '../composables/useLang.js';
import { usePlant } from '../composables/usePlant.js';

const { validate } = useForm('form');
const router = useRouter();

const { init } = useToast();
const { locale, t, L } = useLang();
const { plantName, companyShort, company, logo } = usePlant();

const year = new Date().getFullYear();

const result = reactive({
  login: '',
  password: '',
});

const onSubmit = async () => {
  try {
    const result1 = await store.dispatch('login', result);
    if (result1.success) {
      router.push({ name: 'home' });
    } else {
      console.error('Error:', result1.message || 'Login failed');
      init({ message: t('login.errorMessage'), color: 'danger' });
    }
  } catch (error) {
    console.error('Error during login:', error);
    init({ message: t('login.errorMessage'), color: 'danger' });
  }
};

// Циклическое переключение языков: ru → uz → en → ru
const cycleLanguage = () => {
  locale.value = locale.value === 'ru' ? 'uz' : locale.value === 'uz' ? 'en' : 'ru';
  localStorage.setItem('locale', locale.value);
};

const currentLanguageLabel = computed(() =>
  locale.value === 'uz' ? "O‘zbek" : locale.value === 'en' ? 'English' : 'Русский'
);

onMounted(() => {
  const savedLocale = localStorage.getItem('locale');
  if (savedLocale) locale.value = savedLocale;
});
</script>

<style scoped>
.login-wrap {
  position: fixed;
  inset: 0;
  display: grid;
  grid-template-columns: 45% 55%;
  font-family: 'Inter', system-ui, sans-serif;
}

/* Брендовая панель */
.login-brand {
  position: relative;
  overflow: hidden;
  background: radial-gradient(1200px 800px at 20% 10%, #14314f 0%, #0b1220 55%, #070c16 100%);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.brand-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(70px);
  opacity: 0.5;
  pointer-events: none;
}
.brand-glow-1 { width: 380px; height: 380px; background: #22d3ee; top: -80px; left: -60px; opacity: 0.28; }
.brand-glow-2 { width: 460px; height: 460px; background: #3b82f6; bottom: -120px; right: -100px; opacity: 0.22; }
.brand-inner {
  position: relative;
  z-index: 1;
  text-align: center;
  padding: 24px;
  max-width: 460px;
}
.brand-logo { width: 96px; height: auto; margin-bottom: 28px; filter: drop-shadow(0 8px 24px rgba(0,0,0,.5)); }
.brand-title { font-size: 30px; font-weight: 800; letter-spacing: .01em; line-height: 1.15; }
.brand-plant { font-size: 22px; font-weight: 600; margin-top: 10px; color: #7dd3fc; }
.brand-sub { font-size: 14px; margin-top: 20px; color: #9fb2cc; line-height: 1.5; }
.brand-foot {
  position: absolute; bottom: 24px; z-index: 1;
  font-size: 12px; letter-spacing: .28em; color: #4b5f7d; font-weight: 700;
}

/* Панель формы */
.login-panel {
  position: relative;
  background: #0e1626;
  display: flex;
  flex-direction: column;
}
.login-topbar { display: flex; justify-content: flex-end; padding: 20px 24px 0; }
.login-lang {
  display: inline-flex; align-items: center; gap: 6px;
  background: #172136; color: #cdd8ea; border: 1px solid #24304a;
  padding: 8px 14px; border-radius: 10px; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: .15s;
}
.login-lang:hover { background: #1e2c46; border-color: #34507e; }

.login-center { flex: 1; display: flex; align-items: center; justify-content: center; padding: 24px; }
.login-card {
  width: 100%; max-width: 380px;
  display: flex; flex-direction: column; align-items: stretch;
  background: #131c2e; border: 1px solid #24304a; border-radius: 20px;
  padding: 36px 32px 32px; box-shadow: 0 30px 80px rgba(0,0,0,.45);
}
.login-badge {
  align-self: center; width: 56px; height: 56px; border-radius: 16px;
  display: flex; align-items: center; justify-content: center; margin-bottom: 18px;
  background: linear-gradient(135deg, #22d3ee, #3b82f6);
  box-shadow: 0 10px 30px rgba(34,211,238,.35);
}
.login-badge .material-icons { color: #06121f; font-size: 30px; }
.login-h1 { text-align: center; font-size: 24px; font-weight: 800; color: #f1f5f9; margin: 0; }
.login-h2 { text-align: center; font-size: 14px; color: #94a3b8; margin: 6px 0 26px; }
.login-input { width: 100%; margin-bottom: 18px; }
.login-submit {
  margin-top: 8px; width: 100%;
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  padding: 13px 16px; border: none; border-radius: 12px; cursor: pointer;
  font-size: 15px; font-weight: 700; color: #06121f;
  background: linear-gradient(135deg, #22d3ee, #38bdf8);
  box-shadow: 0 10px 26px rgba(34,211,238,.35); transition: .18s;
}
.login-submit:hover { filter: brightness(1.06); transform: translateY(-1px); }
.login-submit:active { transform: translateY(0); }
.login-copyright { text-align: center; font-size: 12px; color: #4b5f7d; padding: 0 24px 22px; }

@media (max-width: 860px) {
  .login-wrap { grid-template-columns: 1fr; }
  .login-brand { display: none; }
}
</style>
