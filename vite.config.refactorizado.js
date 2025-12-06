import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Agregar módulos JavaScript
                'resources/js/modules/CotizacionRepository.js',
                'resources/js/modules/CotizacionSearchUIController.js',
                'resources/js/modules/PrendasUIController.js',
                'resources/js/modules/FormularioPedidoController.js',
                'resources/js/modules/FormInfoUpdater.js',
                'resources/js/modules/CotizacionDataLoader.js',
                'resources/js/modules/CrearPedidoApp.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        outDir: 'public/build',
        manifest: 'manifest.json',
        // Opciones para optimizar módulos ES6
        rollupOptions: {
            output: {
                // Preservar estructura de módulos
                preserveModules: false,
                // Genera chunk separados para mejor caché
                manualChunks: {
                    'pedidos-app': [
                        'resources/js/modules/CrearPedidoApp.js',
                        'resources/js/modules/CotizacionRepository.js',
                        'resources/js/modules/CotizacionSearchUIController.js',
                        'resources/js/modules/PrendasUIController.js',
                        'resources/js/modules/FormularioPedidoController.js',
                        'resources/js/modules/FormInfoUpdater.js',
                        'resources/js/modules/CotizacionDataLoader.js',
                    ],
                },
            },
        },
    },
    server: {
        // Para desarrollo local
        hmr: {
            host: 'localhost',
            port: 5173,
        },
    },
});
