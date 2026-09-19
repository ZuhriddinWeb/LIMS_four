<template>
    <section class="bg-white h-full w-full flex">
        <!-- <transition name="fade"> -->
        <Sidebar class="no-print !flex-grow-0 !flex-shrink-0" />
        <!-- </transition>         -->
        <router-view class="flex-grow no-print app-scroll" />
    </section>
</template>

<script setup>
import Sidebar from "./components/Sidebar.vue";




</script>
<style>
@media print {
    .print {
        position: fixed;
        top: 0;
        left: 0;
        display: inherit;
    }

    .no-print {
        display: none;
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.ag-header-container {
    background-color: rgb(209 213 219);
}

.ag-header-cell-text {
    color: black;
}

.ag-header-container {
    background-color: rgb(209 213 219);
}

.ag-header-cell-text {
    color: black;
}

/* Прокрутка области контента для высоких страниц (дашборд и т.п.) */
.app-scroll {
    height: 100vh;
    overflow-y: auto;
}

/* ===== Тёмная тема (класс на <html>) ===== */
html.dark-theme,
html.dark-theme body {
    background-color: #0f172a;
    color: #e2e8f0;
}
html.dark-theme section.bg-white,
html.dark-theme .bg-white {
    background-color: #1e293b !important;
    color: #e2e8f0;
}
html.dark-theme .bg-gray-100,
html.dark-theme .bg-gray-50 {
    background-color: #24324a !important;
    color: #e2e8f0;
}
/* Контент-область всегда тёмная (нет белых «дыр» на общих страницах) */
html.dark-theme .app-scroll {
    background-color: #0f172a;
}
html.dark-theme .bg-stone-100,
html.dark-theme .bg-stone-50,
html.dark-theme .bg-slate-50,
html.dark-theme .bg-slate-100,
html.dark-theme .bg-neutral-100,
html.dark-theme .bg-neutral-50 {
    background-color: #0f172a !important;
    color: #e2e8f0;
}
html.dark-theme .bg-slate-200,
html.dark-theme .bg-stone-200 {
    background-color: #1b2740 !important;
}
/* Тёмный текст → светлый для читаемости */
html.dark-theme .text-slate-800,
html.dark-theme .text-slate-700,
html.dark-theme .text-slate-900,
html.dark-theme .text-gray-800,
html.dark-theme .text-gray-700,
html.dark-theme .text-black {
    color: #e2e8f0 !important;
}
html.dark-theme .text-slate-600,
html.dark-theme .text-slate-500,
html.dark-theme .text-gray-600,
html.dark-theme .text-gray-500 {
    color: #94a3b8 !important;
}
html.dark-theme .border-slate-200,
html.dark-theme .border-slate-300,
html.dark-theme .border-slate-400,
html.dark-theme .border-gray-200,
html.dark-theme .border-gray-300 {
    border-color: #334155 !important;
}
html.dark-theme .bg-red-50 {
    background-color: #3b1d1d !important;
}
html.dark-theme .border,
html.dark-theme .border-t,
html.dark-theme .border-b,
html.dark-theme table td,
html.dark-theme table th {
    border-color: #334155 !important;
}
html.dark-theme .hover\:bg-gray-50:hover {
    background-color: #2a3a54 !important;
}
html.dark-theme .rounded,
html.dark-theme .rounded.border {
    background-color: #1e293b;
}
/* ag-Grid в тёмной теме */
html.dark-theme .ag-theme-material,
html.dark-theme .ag-theme-quartz {
    --ag-background-color: #1e293b;
    --ag-foreground-color: #e2e8f0;
    --ag-header-background-color: #24324a;
    --ag-odd-row-background-color: #1b2740;
    --ag-border-color: #334155;
}
html.dark-theme .ag-header-container { background-color: #24324a; }
html.dark-theme .ag-header-cell-text { color: #e2e8f0; }

::-webkit-scrollbar {
    width: 3px;
    height: 3px;
}

::-webkit-scrollbar-track {
    box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
}

::-webkit-scrollbar-thumb {
    background-color: #154EC1;
}

</style>