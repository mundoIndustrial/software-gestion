/**
 * =====================================================
 * BUILD CORE BUNDLES
 * =====================================================
 * Concatena y minifica los archivos DDD core en 2 bundles:
 *   - shared-core.js       → Infraestructura compartida (window.shared)
 *   - sp-core.js           → DDD supervisor-pedidos (window.supervisorPedidos)
 *
 * Uso:
 *   node scripts/build-core.mjs          → builds dev + min
 *   node scripts/build-core.mjs --watch  → rebuild on file changes
 *
 * Requisitos:
 *   - esbuild (ya incluido como dependencia de Vite)
 */

import { readFileSync, writeFileSync, mkdirSync, watchFile } from 'node:fs';
import { join } from 'node:path';
import { transform } from 'esbuild';

const ROOT = process.cwd();
const OUT_DIR = join(ROOT, 'public', 'js', 'bundles');

// =====================================================
// DEFINICIÓN DE BUNDLES
// =====================================================

const BUNDLES = [
    {
        name: 'shared-core',
        files: [
            // Infrastructure - sin dependencias, order matters
            'public/js/shared/infrastructure/HttpClient.js',
            'public/js/shared/infrastructure/NotificationService.js',
            'public/js/shared/infrastructure/ModalManager.js',
            // Domain Interfaces
            'public/js/shared/WebSocketClient.js',
            'public/js/shared/CacheRepository.js',
            // Infrastructure Implementations
            'public/js/shared/infrastructure/EchoReverbWebSocketClient.js',
            'public/js/shared/infrastructure/SessionStorageCacheRepository.js',
            // Bootstrap (sets up window.shared with all above)
            'public/js/shared/bootstrap.js',
        ],
    },
    {
        name: 'sp-core',
        files: [
            'public/js/supervisor-pedidos/core/domain/PedidoRepository.js',
            'public/js/supervisor-pedidos/core/infrastructure/PedidoApiRepository.js',
            'public/js/supervisor-pedidos/core/application/FilterService.js',
            'public/js/supervisor-pedidos/core/application/SelectionService.js',
            'public/js/supervisor-pedidos/core/application/OrderEditService.js',
            'public/js/supervisor-pedidos/core/bootstrap.js',
        ],
    },
];

// =====================================================
// BUILD
// =====================================================

async function buildBundle(bundle) {
    const parts = bundle.files.map(f => {
        const content = readFileSync(join(ROOT, f), 'utf-8');
        return `// --- ${f.split('/').pop()} ---\n${content}`;
    });

    const raw = parts.join('\n\n');

    // Minificar con esbuild
    const { code: minified } = await transform(raw, {
        minify: true,
        target: 'es2020',
    });

    // Escribir versión desarrollo (legible)
    const devFile = join(OUT_DIR, `${bundle.name}.js`);
    writeFileSync(devFile, raw);

    // Escribir versión producción (minificada)
    const minFile = join(OUT_DIR, `${bundle.name}.min.js`);
    writeFileSync(minFile, minified);

    const rawKB = (raw.length / 1024).toFixed(1);
    const minKB = (minified.length / 1024).toFixed(1);
    const savings = (100 - (minified.length / raw.length * 100)).toFixed(0);

    console.log(`   ${bundle.name}.js        ${rawKB} KB`);
    console.log(`   ${bundle.name}.min.js    ${minKB} KB (${savings}% menor)`);
}

async function buildAll() {
    mkdirSync(OUT_DIR, { recursive: true });
    console.log('\n🔨 Building core bundles...\n');

    for (const bundle of BUNDLES) {
        await buildBundle(bundle);
    }

    console.log('\n Build completado\n');
}

// =====================================================
// WATCH MODE
// =====================================================

if (process.argv.includes('--watch')) {
    console.log('👀 Watch mode activado\n');
    await buildAll();

    const allFiles = BUNDLES.flatMap(b => b.files);
    for (const file of allFiles) {
        watchFile(join(ROOT, file), { interval: 500 }, () => {
            console.log(`\n📝 Cambio detectado: ${file}`);
            buildAll().catch(console.error);
        });
    }
} else {
    await buildAll();
}
