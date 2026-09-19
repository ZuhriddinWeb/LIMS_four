import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
            './cptable': 'cptable'
        },
    },
    optimizeDeps: {
        // include: ['xlsx-style', 'cptable']
        exclude: ['xlsx-js-style'], // ⚠️ exclude qilish
        include: ['jquery', 'jquery-mousewheel', 'luckysheet']
        
      },
      ssr: {
        noExternal: ['xlsx-js-style'], // ⚠️ SSR vaqtida tashqaridan yuklamaslik
      },
   server: {
        host: '0.0.0.0',
        cors: {
            origin: [
                'http://192.168.14.82:8009',
                /^https?:\/\/192\.168\.\d+\.\d+(:\d+)?$/, // butun LAN tarmog'iga ruxsat
            ],
        },
        hmr: {
            host: '192.168.14.82',
        },
    },
    
});
