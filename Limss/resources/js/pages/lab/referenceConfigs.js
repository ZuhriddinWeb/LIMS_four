// Конфигурация справочников лабораторного блока.
// Одна generic-страница LabReferencePage.vue рендерит любой справочник по его ключу.
// Метки трёхязычные {uz, ru, en} — выбираются по текущей локали (en→ru при отсутствии).
// Типы полей формы: text | textarea | date | select (select грузит options с optionsEndpoint).

const C = { uz: "Kod", ru: "Код", en: "Code" };
const NAME = { uz: "Nomi", ru: "Наименование", en: "Name" };
const CMT = { uz: "Izoh", ru: "Комментарий", en: "Comment" };
const NAME_UZ = { uz: "Nomi (uz)", ru: "Наименование (uz)", en: "Name (uz)" };
const NAME_RU = { uz: "Nomi (ru)", ru: "Наименование (ru)", en: "Name (ru)" };
const SHORT_UZ = { uz: "Qisqa (uz)", ru: "Кратко (uz)", en: "Short (uz)" };
const SHORT_RU = { uz: "Qisqa (ru)", ru: "Кратко (ru)", en: "Short (ru)" };

export const labConfigs = {
  laboratories: {
    endpoint: "/lab/laboratories",
    role: "menu.lab_laboratories",
    title: { uz: "Laboratoriyalar", ru: "Лаборатории", en: "Laboratories" },
    columns: [
      { key: "Code", label: C, width: 110 },
      { key: "__name__", label: NAME, flex: 1 },
      { key: "Comment", label: CMT, flex: 1 },
    ],
    fields: [
      { key: "Code", label: C },
      { key: "Name", label: NAME_UZ, required: true },
      { key: "NameRus", label: NAME_RU },
      { key: "ShortName", label: SHORT_UZ },
      { key: "ShortNameRus", label: SHORT_RU },
      { key: "Comment", label: CMT, type: "textarea" },
    ],
  },

  groups: {
    endpoint: "/lab/groups",
    role: "menu.lab_groups",
    title: { uz: "Guruhlar", ru: "Группы", en: "Groups" },
    columns: [
      { key: "Code", label: C, width: 110 },
      { key: "__name__", label: NAME, flex: 1 },
      { key: "Laboratory", localizedBase: "LaboratoryName", label: { uz: "Laboratoriya", ru: "Лаборатория", en: "Laboratory" }, flex: 1 },
    ],
    fields: [
      { key: "LaboratoryID", label: { uz: "Laboratoriya", ru: "Лаборатория", en: "Laboratory" }, type: "select", optionsEndpoint: "/lab/laboratories", required: true },
      { key: "Code", label: C },
      { key: "Name", label: NAME_UZ, required: true },
      { key: "NameRus", label: NAME_RU },
      { key: "Comment", label: CMT, type: "textarea" },
    ],
  },

  products: {
    endpoint: "/lab/products",
    role: "menu.lab_products",
    title: { uz: "Mahsulotlar", ru: "Продукция", en: "Products" },
    columns: [
      { key: "Code", label: C, width: 110 },
      { key: "__name__", label: NAME, flex: 1 },
      { key: "ProductType", label: { uz: "Turi", ru: "Тип", en: "Type" }, width: 120 },
      { key: "Comment", label: CMT, flex: 1 },
    ],
    fields: [
      { key: "Code", label: C },
      { key: "Name", label: NAME_UZ, required: true },
      { key: "NameRus", label: NAME_RU },
      { key: "ProductType", label: { uz: "Turi (dore/cathode/semi)", ru: "Тип (dore/cathode/semi)", en: "Type (dore/cathode/semi)" } },
      { key: "Comment", label: CMT, type: "textarea" },
    ],
  },

  departments: {
    endpoint: "/lab/departments",
    role: "menu.lab_departments",
    title: { uz: "Bo'linmalar (manbalar)", ru: "Подразделения (источники)", en: "Departments (sources)" },
    columns: [
      { key: "Code", label: C, width: 110 },
      { key: "__name__", label: NAME, flex: 1 },
      { key: "Comment", label: CMT, flex: 1 },
    ],
    fields: [
      { key: "Code", label: C },
      { key: "Name", label: NAME_UZ, required: true },
      { key: "NameRus", label: NAME_RU },
      { key: "ShortName", label: SHORT_UZ },
      { key: "ShortNameRus", label: SHORT_RU },
      { key: "Comment", label: CMT, type: "textarea" },
    ],
  },

  points: {
    endpoint: "/lab/points",
    role: "menu.lab_points",
    title: { uz: "Namuna olish nuqtalari", ru: "Точки отбора проб", en: "Sampling points" },
    columns: [
      { key: "Code", label: C, width: 110 },
      { key: "__name__", label: NAME, flex: 1 },
      { key: "Environment", label: { uz: "Muhit", ru: "Среда", en: "Environment" }, width: 140 },
      { key: "Comment", label: CMT, flex: 1 },
    ],
    fields: [
      { key: "Code", label: C },
      { key: "Name", label: NAME_UZ, required: true },
      { key: "NameRus", label: NAME_RU },
      { key: "FactoryStructureID", label: { uz: "Sex", ru: "Цех", en: "Shop" }, type: "select", optionsEndpoint: "/structure" },
      { key: "BlogID", label: { uz: "Uchastka", ru: "Участок", en: "Section" }, type: "select", optionsEndpoint: "/blogs" },
      { key: "Environment", label: { uz: "Muhit (pulpa/eritma/qattiq/mahsulot)", ru: "Среда (пульпа/раствор/твёрдое/продукт)", en: "Environment (pulp/solution/solid/product)" } },
      { key: "Comment", label: CMT, type: "textarea" },
    ],
  },

  types: {
    endpoint: "/lab/types",
    role: "menu.lab_types",
    title: { uz: "Namuna turlari", ru: "Типы проб", en: "Sample types" },
    columns: [
      { key: "Code", label: C, width: 110 },
      { key: "__name__", label: NAME, flex: 1 },
      { key: "Comment", label: CMT, flex: 1 },
    ],
    fields: [
      { key: "Code", label: C },
      { key: "Name", label: NAME_UZ, required: true },
      { key: "NameRus", label: NAME_RU },
      { key: "ShortName", label: SHORT_UZ },
      { key: "ShortNameRus", label: SHORT_RU },
      { key: "Comment", label: CMT, type: "textarea" },
    ],
  },

  analytes: {
    endpoint: "/lab/analytes",
    role: "menu.lab_analytes",
    title: { uz: "Aniqlanadigan ko'rsatkichlar", ru: "Определяемые показатели", en: "Analytes" },
    columns: [
      { key: "Symbol", label: { uz: "Belgi", ru: "Символ", en: "Symbol" }, width: 110 },
      { key: "__name__", label: NAME, flex: 1 },
      { key: "Unit", label: { uz: "Birlik", ru: "Ед.", en: "Unit" }, width: 100 },
      { key: "Comment", label: CMT, flex: 1 },
    ],
    fields: [
      { key: "Symbol", label: { uz: "Belgi (Au, Ag, Cu...)", ru: "Символ (Au, Ag, Cu...)", en: "Symbol (Au, Ag, Cu...)" } },
      { key: "Name", label: NAME_UZ, required: true },
      { key: "NameRus", label: NAME_RU },
      { key: "UnitsID", label: { uz: "O'lchov birligi", ru: "Единица измерения", en: "Unit of measure" }, type: "select", optionsEndpoint: "/units" },
      { key: "Unit", label: { uz: "Birlik (matn: g/t, %, g/l)", ru: "Единица (текст: г/т, %, г/л)", en: "Unit (text: g/t, %, g/l)" } },
      { key: "Comment", label: CMT, type: "textarea" },
    ],
  },

  methods: {
    endpoint: "/lab/methods",
    role: "menu.lab_methods",
    title: { uz: "Tahlil metodikalari", ru: "Методики анализа", en: "Analysis methods" },
    columns: [
      { key: "Code", label: C, width: 110 },
      { key: "__name__", label: NAME, flex: 1 },
      { key: "StandardDoc", label: { uz: "Normativ hujjat", ru: "Норм. документ", en: "Standard doc" }, width: 160 },
      { key: "LOD", label: { uz: "LOD", ru: "LOD", en: "LOD" }, width: 90 },
      { key: "LOQ", label: { uz: "LOQ", ru: "LOQ", en: "LOQ" }, width: 90 },
      { key: "Uncertainty", label: { uz: "U, %", ru: "U, %", en: "U, %" }, width: 90 },
    ],
    fields: [
      { key: "Code", label: C },
      { key: "Name", label: NAME_UZ, required: true },
      { key: "NameRus", label: NAME_RU },
      { key: "StandardDoc", label: { uz: "Normativ hujjat (GOST/MVI)", ru: "Норм. документ (ГОСТ/МВИ)", en: "Standard doc (GOST/MVI)" } },
      { key: "LOD", label: { uz: "Aniqlash chegarasi (LOD)", ru: "Предел обнаружения (LOD)", en: "Limit of detection (LOD)" } },
      { key: "LOQ", label: { uz: "Miqdoriy aniqlash chegarasi (LOQ)", ru: "Предел количеств. опред. (LOQ)", en: "Limit of quantification (LOQ)" } },
      { key: "Uncertainty", label: { uz: "Kengaytirilgan noaniqlik U, %", ru: "Расширенная неопределённость U, %", en: "Expanded uncertainty U, %" } },
      { key: "DecimalPlaces", label: { uz: "Kasrdan keyingi belgilar", ru: "Знаков после запятой", en: "Decimal places" } },
      { key: "Comment", label: CMT, type: "textarea" },
    ],
  },

  storage: {
    endpoint: "/lab/storage-locations",
    role: "menu.lab_storage",
    title: { uz: "Saqlash joylari", ru: "Места хранения", en: "Storage locations" },
    columns: [
      { key: "Code", label: C, width: 110 },
      { key: "__name__", label: NAME, flex: 1 },
      { key: "LocationType", label: { uz: "Turi", ru: "Тип", en: "Type" }, width: 140 },
      { key: "Comment", label: CMT, flex: 1 },
    ],
    fields: [
      { key: "Code", label: C },
      { key: "Name", label: NAME_UZ, required: true },
      { key: "NameRus", label: NAME_RU },
      { key: "LocationType", label: { uz: "Turi (muzlatgich/seyf/javon/xona)", ru: "Тип (холодильник/сейф/полка/комната)", en: "Type (fridge/safe/shelf/room)" } },
      { key: "Comment", label: CMT, type: "textarea" },
    ],
  },

  instruments: {
    endpoint: "/lab/instruments",
    role: "menu.lab_instruments",
    title: { uz: "Asboblar", ru: "Приборы", en: "Instruments" },
    columns: [
      { key: "Code", label: C, width: 110 },
      { key: "__name__", label: NAME, flex: 1 },
      { key: "InventoryNo", label: { uz: "Inv. raqam", ru: "Инв. №", en: "Inv. №" }, width: 140 },
      { key: "VerificationDue", label: { uz: "Tekshiruv muddati", ru: "Поверка до", en: "Verification due" }, width: 150 },
    ],
    fields: [
      { key: "Code", label: C },
      { key: "Name", label: NAME_UZ, required: true },
      { key: "NameRus", label: NAME_RU },
      { key: "InventoryNo", label: { uz: "Inventar raqami", ru: "Инвентарный номер", en: "Inventory number" } },
      { key: "VerificationDate", label: { uz: "Tekshiruv sanasi", ru: "Дата поверки", en: "Verification date" }, type: "date" },
      { key: "VerificationDue", label: { uz: "Amal qilish muddati", ru: "Поверка действительна до", en: "Verification due" }, type: "date" },
      { key: "Comment", label: CMT, type: "textarea" },
    ],
  },
};

export function getLabConfig(key) {
  return labConfigs[key] || null;
}
