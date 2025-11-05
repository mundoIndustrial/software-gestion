import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '0.0.0.0', // Permite conexiones desde cualquier IP de la red
        port: 5173,
        strictPort: true,
        hmr: {
            host: process.env.VITE_HMR_HOST || 'localhost',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/tableros.css',
                'resources/js/tableros.js'
            ],
            refresh: true,
        }),
    ],
    build: {
        // Optimize bundle size
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.logs in production
                drop_debugger: true
            }
        },
        // Code splitting
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['alpinejs'],
                },
                // Optimize chunk size
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
                assetFileNames: ({name}) => {
                    if (/\.(gif|jpe?g|png|svg|webp)$/.test(name ?? '')) {
                        return 'images/[name]-[hash][extname]';
                    }
                    if (/\.css$/.test(name ?? '')) {
                        return 'css/[name]-[hash][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                }
            }
        },
        // Increase chunk size warning limit
        chunkSizeWarningLimit: 600,
        // CSS code splitting
        cssCodeSplit: true,
    },
    // Optimize dependencies
    optimizeDeps: {
        include: ['alpinejs']
    }
});
