import { computed } from "vue";
import { useLang } from "./useLang.js";

// Брендинг завода из window.__LIMS__ (инъекция из blade, config/lims.php → plant).
// Значения по локали; безопасные запасные варианты, если конфиг не задан.
const RAW = (typeof window !== "undefined" && window.__LIMS__) || {};

export function usePlant() {
  const { L } = useLang();

  const pick = (obj, fallback = "") => {
    if (!obj) return fallback;
    return L(obj.ru ?? fallback, obj.uz ?? obj.ru ?? fallback, obj.en ?? obj.ru ?? fallback);
  };

  const plantName = computed(() => pick(RAW.name, "LIMS"));
  const company = computed(() => pick(RAW.company, ""));
  const companyShort = computed(() => pick(RAW.company_short, ""));
  const labName = computed(() => pick(RAW.lab_name, ""));
  const code = computed(() => RAW.code || "");
  const city = computed(() => RAW.city || "");
  const logo = computed(() => RAW.logo || "/ngmk.png");
  const accreditation = RAW.accreditation || { number: "", valid: "", body: "" };

  // Синхронный доступ по языку (для не-Vue контекстов, напр. генерации HTML COA).
  const at = (locale) => {
    const g = (o, f = "") => (o ? (o[locale] ?? o.ru ?? f) : f);
    return {
      plantName: g(RAW.name, "LIMS"),
      company: g(RAW.company),
      companyShort: g(RAW.company_short),
      labName: g(RAW.lab_name),
      code: RAW.code || "",
      city: RAW.city || "",
      logo: RAW.logo || "/ngmk.png",
      accreditation: RAW.accreditation || { number: "", valid: "", body: "" },
    };
  };

  return { plantName, company, companyShort, labName, code, city, logo, accreditation, raw: RAW, at };
}
