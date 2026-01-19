import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        vue(),
        react(),
    ],

    build: {
        outDir: 'public/build',
        manifest: true,
        rollupOptions: {
            input: {
                // Main scripts
                app: resolve(__dirname, 'resources/js/app.js'),
                admin: resolve(__dirname, 'resources/js/admin.js'),

                // React entry (if using React)
                // react: resolve(__dirname, 'resources/js/react-app.jsx'),

                // Vue entry (if using Vue)
                // vue: resolve(__dirname, 'resources/js/vue-app.js'),

                // Styles
                style: resolve(__dirname, 'resources/css/app.css'),
                'admin-style': resolve(__dirname, 'resources/css/admin.css'),
            },
            output: {
                entryFileNames: 'js/[name]-[hash].js',
                chunkFileNames: 'js/chunks/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return 'css/[name]-[hash][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },

    server: {
        origin: 'http://localhost:5173',
        cors: true,
    },

    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '~': resolve(__dirname, 'resources'),
        },
    },
});
