import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import fs from 'fs';

// Leer variables del .env.development
let hmrHost = 'localhost';
let hmrPort = 5173;

try {
    const envPath = '.env.development';
    if (fs.existsSync(envPath)) {
        const envContent = fs.readFileSync(envPath, 'utf-8');
        const hmrMatch = envContent.match(/VITE_HMR_HOST=(.+)/);
        if (hmrMatch) {
            hmrHost = hmrMatch[1].trim();
        }
    }
} catch (e) {
    console.log('⚠️  No se pudo leer .env.development, usando localhost');
}

// Fallback a variable de entorno si está disponible
hmrHost = process.env.VITE_HMR_HOST || hmrHost;

const isProduction = process.env.VITE_ENV === 'production' || process.env.NODE_ENV === 'production';

console.log('🔧 Vite Config - HMR:', hmrHost + ':' + hmrPort);

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: false,
        hmr: isProduction ? false : {
            host: hmrHost,
            port: hmrPort,
            protocol: 'http',
        },
        cors: {
            origin: '*',
            credentials: true
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        target: 'esnext',
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: isProduction,
                drop_debugger: isProduction,
                passes: 2,
            },
            format: {
                comments: false,
            }
        },
        // Aggressive code splitting
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    // Vendor chunks
                    if (id.includes('node_modules')) {
                        if (id.includes('alpinejs')) return 'vendor-alpine';
                        if (id.includes('sweetalert')) return 'vendor-alert';
                        return 'vendor-common';
                    }
                },
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
                assetFileNames: ({name}) => {
                    if (/\.(gif|jpe?g|png|svg|webp|webm|mp4)$/.test(name ?? '')) {
                        return 'images/[name]-[hash][extname]';
                    }
                    if (/\.css$/.test(name ?? '')) {
                        return 'css/[name]-[hash][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                }
            }
        },
        chunkSizeWarningLimit: 1000,
        cssCodeSplit: true,
        sourcemap: !isProduction, // Only in dev
        reportCompressedSize: true,
    },
    // Optimize dependencies pre-bundling
    optimizeDeps: {
        include: ['alpinejs'],
        exclude: [] // Removido 'laravel-echo' para que se incluya correctamente
    }
});
