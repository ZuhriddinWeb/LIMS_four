import { computed } from "vue";
import { useI18n } from "vue-i18n";

/**
 * Трилингвал-хелпер для страниц с текстом «в компоненте».
 *  - isRu: выбор языкового поля данных (для en данных нет → используем русское).
 *  - L(ru, uz, en): подпись интерфейса по текущему языку; en по умолчанию = ru.
 */
export function useLang() {
  const { locale, t } = useI18n();
  const isRu = computed(() => locale.value !== "uz");
  const isEn = computed(() => locale.value === "en");
  const L = (ru, uz, en) =>
    locale.value === "uz" ? uz : locale.value === "en" ? (en ?? ru) : ru;

  // Значение справочника по локали: en → NameRus (данных на en нет).
  const nameOf = (obj, base = "Name") => {
    if (!obj) return "";
    return locale.value === "uz"
      ? obj[base]
      : obj[base + "Rus"] || obj[base];
  };

  return { locale, t, isRu, isEn, L, nameOf };
}
