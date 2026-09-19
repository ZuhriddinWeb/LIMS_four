export default [
  {
    path: "/",
    name: "home",
    component: () => import("../pages/lab/LabDashboardPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/login",
    name: "login",
    meta: {
      guard: "guest",
    },
    component: () => import("../pages/LoginPage.vue"),
  },
  {
    path: "/factory",
    name: "factory",
    component: () => import("../pages/FactoriesPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/structure",
    name: "structure",
    component: () => import("../pages/FactoriesStructurePage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/cardPageStatic",
    name: "CardDetailPageStatic",
    component: () => import("../pages/svodka/staticscards/StaticPageCard.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },
  {
  path: "/cardPageStatic/:page/groups",
  name: "CardGroupsPageStatic",
  component: () => import("../pages/svodka/staticscards/GroupsCards.vue"),
  meta: { guard: "auth" },
  props: r => ({ page: Number(r.params.page) }),
},
  // {
  //   path: "/static/:id",
  //   name: "static",
  //   component: () => import("../pages/StaticParametrs.vue"),
  //   meta: {
  //     guard: "auth",
  //   },
  //   props: true
  // },
  {
  path: "/static/:id/:groupId",
  name: "staticByGroup",
  component: () => import("../pages/StaticParametrs.vue"),
  meta: { guard: "auth" },
  props: r => ({ id: Number(r.params.id), groupId: Number(r.params.groupId) }),
},
// {
//   path: "/static/:id/group/:groupId",
//   name: "staticByGroup",
//   component: () => import("../pages/StaticParametrs.vue"),
//   meta: { guard: "auth" },
//   props: r => ({
//     id: Number(r.params.id),
//     groupId: Number(r.params.groupId),
//   }),
// },
{
  path: "/static/:id/group/:groupId?",
  name: "static",
  component: () => import("../pages/StaticParametrs.vue"),
  meta: { guard: "auth" },
  props: r => ({
    id: Number(r.params.id),
    groupId: r.params.groupId != null ? Number(r.params.groupId) : null,
  }),
},

  // {
  // 	path: '/blogs',
  // 	name:'blogs',
  // 	component: () => import('../pages/BlogsPage.vue'),
  // 	meta: {
  // 		guard: 'auth',
  // 	},
  // },
  {
    path: "/blogs",
    name: "blogs",
    component: () => import("../pages/BlogCardPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/blog/:id",
    name: "BlogDetail",
    component: () => import("../pages/BlogsPage.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },
  {
    path: "/units",
    name: "units",
    component: () => import("../pages/UnitsPage.vue"),
    meta: {
      guard: "auth",
    },
  },
    {
    path: "/periodType",
    name: "periodType",
    component: () => import("../pages/PeriodTypePage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/graphics",
    name: "graphics",
    component: () => import("../pages/GraphicsPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  // {
  // 	path: '/graphictimes',
  // 	name:'graphictimes',
  // 	component: () => import('../pages/GraphicTimesPage.vue'),
  // 	meta: {
  // 		guard: 'auth',
  // 	},
  // },
  {
    path: "/graphictimes",
    name: "graphictimes",
    component: () => import("../pages/TimeCardPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/time/:id",
    name: "TimeDetail",
    component: () => import("../pages/GraphicTimesPage.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },

  {
    path: "/graphicterms",
    name: "graphicterms",
    component: () => import("../pages/TermsCardPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/terms/:id",
    name: "TermsDetail",
    component: () => import("../pages/GraphicTermsPage.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },

  {
    path: "/cardPage/:id",
    name: "CardDetailPage",
    component: () => import("../pages/ParameterGraphicsPage.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },

  {
    path: "/card/:id/:page",
    name: "CardDetail",
    component: () => import("../pages/ParameterGraphics.vue"),
    meta: {
      guard: "auth",
    },
    props: (route) => ({
      id: route.params.id,
      page: route.params.page,
    }),
  },

  {
    path: "/paramgraphics",
    name: "paramgraphics",
    component: () => import("../pages/GraphicCardsPage.vue"),
    meta: {
      guard: "auth",
    },
  },

  {
    path: "/groupPage/:id",
    name: "GroupDetailPage",
    component: () => import("../pages/GroupPagesCard.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },
  {
    path: "/group/:id/:page",
    name: "GroupDetail",
    component: () => import("../pages/GroupsPage.vue"),
    meta: {
      guard: "auth",
    },
    props: (route) => ({
      id: route.params.id,
      page: route.params.page,
    }),
  },

  {
    path: "/group",
    name: "groupcard",
    component: () => import("../pages/GroupCardPage.vue"),
    meta: {
      guard: "auth",
    },
  },

  {
    path: "/paramtypes",
    name: "paramtypes",
    component: () => import("../pages/ParametersTypesPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/params",
    name: "params",
    component: () => import("../pages/ParametersPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/formula",
    name: "formula",
    component: () => import("../pages/FormulaPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  // {
  // 	path: '/pages',
  // 	name:'pages',
  // 	component: () => import('../pages/NumberPage.vue'),
  // 	meta: {
  // 		guard: 'auth',
  // 	},
  // },
  {
    path: "/pages",
    name: "pages",
    component: () => import("../pages/NumberPageCard.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/page/:id",
    name: "PageDetail",
    component: () => import("../pages/NumberPage.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },
  {
    path: "/sources",
    name: "sources",
    component: () => import("../pages/SourcesPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/changes",
    name: "changes",
    component: () => import("../pages/ChangesPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  // {
  // 	path: '/vparam/:id',
  // 	name:'vparam',
  // 	component: () => import('../pages/ParametrValue.vue'),
  // 	meta: {
  // 		guard: 'auth',
  // 	},
  // 	props: true,
  // },
  {
    path: "/vparams",
    name: "vparams",
    component: () => import("../pages/ParametrValueCardVertical.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/vparam/:id",
    name: "vparam",
    component: () => import("../pages/ParametrValueVertical.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },

  {
    path: "/vparamsHorizontal",
    name: "vparamsHorizontal",
    component: () => import("../pages/ParametrValueCardHorizontal.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/vparamsHorizontal/:id",
    name: "vparamHorizontal",
    component: () => import("../pages/ParametrValueHorizontal.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },
  {
    path: "/count",
    name: "createDoc",
    component: () => import("../pages/ParametrValueCardVertical.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/opercount",
    name: "createDoc",
    component: () => import("../pages/OperatorsCountInputValueCard.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/operDetail/:id",
    name: "OperatorDetail",
    component: () => import("../pages/OperatorResultPage.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },
  {
    path: "/documents",
    name: "documents",
    component: () => import("../pages/Documents.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/vparamsget",
    name: "vparamsget",
    component: () => import("../pages/ParametrGetValue.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/gmz3",
    name: "gmz3-report",
    component: () => import("../pages/svodka/GMZ3Report.vue"),
    meta: {
      guard: "auth",
    },
  },
    {
    path: "/gmz301",
    name: "gmz3-report301",
    component: () => import("../pages/svodka/GMZ3Report301.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/users",
    name: "users",
    component: () => import("../pages/UsersPage.vue"),
  },
  // Лабораторный блок — пробы (Фаза Л2)
  {
    path: "/lab/samples",
    name: "lab-samples",
    component: () => import("../pages/lab/LabSamplesPage.vue"),
    meta: { guard: "auth" },
  },
  // Лабораторный блок — журнал КХА (Фаза Л3)
  {
    path: "/lab/journal",
    name: "lab-journal",
    component: () => import("../pages/lab/LabJournalPage.vue"),
    meta: { guard: "auth" },
  },
  // Лабораторный блок — ИИ-помощник
  {
    path: "/lab/ai",
    name: "lab-ai",
    component: () => import("../pages/lab/LabAiPage.vue"),
    meta: { guard: "auth" },
  },
  // Лабораторный блок — управление оборудованием
  {
    path: "/lab/equipment",
    name: "lab-equipment",
    component: () => import("../pages/lab/LabEquipmentPage.vue"),
    meta: { guard: "auth" },
  },
  // Лабораторный блок — журнал аудита
  {
    path: "/lab/audit",
    name: "lab-audit",
    component: () => import("../pages/lab/LabAuditPage.vue"),
    meta: { guard: "auth" },
  },
  // Лабораторный блок — центр оповещений
  {
    path: "/lab/alerts",
    name: "lab-alerts",
    component: () => import("../pages/lab/LabAlertsPage.vue"),
    meta: { guard: "auth" },
  },
  // Лабораторный блок — технологический контроль (сменные журналы)
  {
    path: "/lab/process",
    name: "lab-process",
    component: () => import("../pages/lab/LabProcessPage.vue"),
    meta: { guard: "auth" },
  },
  // Лабораторный блок — внутрилаб. контроль ВЛК (Фаза Л5)
  {
    path: "/lab/qc",
    name: "lab-qc",
    component: () => import("../pages/lab/LabQcPage.vue"),
    meta: { guard: "auth" },
  },
  {
    path: "/lab/standards",
    name: "lab-standards",
    component: () => import("../pages/lab/LabStandardsPage.vue"),
    meta: { guard: "auth" },
  },
  // Лабораторный блок — паспорта качества (Фаза Л4)
  {
    path: "/lab/certificates",
    name: "lab-certificates",
    component: () => import("../pages/lab/LabCertificatesPage.vue"),
    meta: { guard: "auth" },
  },
  {
    path: "/lab/products",
    name: "lab-products",
    component: () => import("../pages/lab/LabReferencePage.vue"),
    props: { configKey: "products" },
    meta: { guard: "auth" },
  },
  {
    path: "/lab/storage",
    name: "lab-storage",
    component: () => import("../pages/lab/LabReferencePage.vue"),
    props: { configKey: "storage" },
    meta: { guard: "auth" },
  },
  // Лабораторный блок — справочники (Фаза Л1)
  {
    path: "/lab/laboratories",
    name: "lab-laboratories",
    component: () => import("../pages/lab/LabReferencePage.vue"),
    props: { configKey: "laboratories" },
    meta: { guard: "auth" },
  },
  {
    path: "/lab/groups",
    name: "lab-groups",
    component: () => import("../pages/lab/LabReferencePage.vue"),
    props: { configKey: "groups" },
    meta: { guard: "auth" },
  },
  {
    path: "/lab/departments",
    name: "lab-departments",
    component: () => import("../pages/lab/LabReferencePage.vue"),
    props: { configKey: "departments" },
    meta: { guard: "auth" },
  },
  {
    path: "/lab/points",
    name: "lab-points",
    component: () => import("../pages/lab/LabReferencePage.vue"),
    props: { configKey: "points" },
    meta: { guard: "auth" },
  },
  {
    path: "/lab/types",
    name: "lab-types",
    component: () => import("../pages/lab/LabReferencePage.vue"),
    props: { configKey: "types" },
    meta: { guard: "auth" },
  },
  {
    path: "/lab/analytes",
    name: "lab-analytes",
    component: () => import("../pages/lab/LabReferencePage.vue"),
    props: { configKey: "analytes" },
    meta: { guard: "auth" },
  },
  {
    path: "/lab/methods",
    name: "lab-methods",
    component: () => import("../pages/lab/LabReferencePage.vue"),
    props: { configKey: "methods" },
    meta: { guard: "auth" },
  },
  {
    path: "/lab/instruments",
    name: "lab-instruments",
    component: () => import("../pages/lab/LabReferencePage.vue"),
    props: { configKey: "instruments" },
    meta: { guard: "auth" },
  },
  {
    path: "/:pathMatch(.*)*",
    redirect: "/",
    name: "pathMatch",
    meta: {
      title: "all",
    },
  },
];
