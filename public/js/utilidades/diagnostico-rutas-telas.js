/**
 * Diagnóstico de Rutas de Telas
 * Verifica que las URLs de imágenes de telas sean accesibles
 */

globalThis.diagnosticarRutasTelas = async function() {
    console.log(' [DIAGNÓSTICO-RUTAS] Iniciando verificación de rutas de telas...');
    
    // Obtener telas disponibles
    const telas = globalThis.telasAgregadas || globalThis.telasEdicion || globalThis.telasCreacion || [];
    console.log(`[DIAGNÓSTICO-RUTAS] Telas a verificar: ${telas.length}`);
    
    if (telas.length === 0) {
        console.warn('[DIAGNÓSTICO-RUTAS] No hay telas para diagnosticar');
        return;
    }
    
    // Verificar cada tela
    for (let i = 0; i < telas.length; i++) {
        const tela = telas[i];
        console.log(`\n[DIAGNÓSTICO-RUTAS]  Tela ${i}: ${tela.nombre_tela || 'SIN NOMBRE'}`);
        
        if (!tela.imagenes || tela.imagenes.length === 0) {
            console.warn(`[DIAGNÓSTICO-RUTAS]    Sin imágenes`);
            continue;
        }
        
        // Verificar cada imagen
        for (let j = 0; j < tela.imagenes.length; j++) {
            const img = tela.imagenes[j];
            console.log(`[DIAGNÓSTICO-RUTAS]    Imagen ${j}:`, {
                ruta: img.ruta || 'NULL',
                ruta_webp: img.ruta_webp || 'NULL',
                previewUrl: img.previewUrl || 'NULL',
                url: img.url || 'NULL'
            });
            
            // Determinar cuál es la URL a usar
            const urlAVerificar = img.ruta || img.ruta_webp || img.url || img.previewUrl;
            
            if (!urlAVerificar) {
                console.error(`[DIAGNÓSTICO-RUTAS]      Ninguna URL válida encontrada`);
                continue;
            }
            
            // Probar si la URL es accesible
            try {
                const response = await fetch(urlAVerificar, { method: 'HEAD' });
                if (response.ok) {
                    console.log(`[DIAGNÓSTICO-RUTAS]      URL accesible: ${urlAVerificar}`);
                } else {
                    console.error(`[DIAGNÓSTICO-RUTAS]      Error ${response.status} en URL: ${urlAVerificar}`);
                }
            } catch (error) {
                console.error(`[DIAGNÓSTICO-RUTAS]      Error de red: ${error.message}`);
                console.error(`[DIAGNÓSTICO-RUTAS]     URL intentada: ${urlAVerificar}`);
            }
        }
    }
    
    console.log('\n[DIAGNÓSTICO-RUTAS]  Diagnóstico completado');
};

// Exponer globalmente
globalThis.runDiagnosticoTelas = globalThis.diagnosticarRutasTelas;

console.log(' [diagnostico-rutas-telas.js] Cargado - usa globalThis.diagnosticarRutasTelas() para diagnosticar');
