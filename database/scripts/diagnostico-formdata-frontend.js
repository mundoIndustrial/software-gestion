/**
 * SCRIPT DE DIAGNÓSTICO - FormData que se envía al servidor
 * 
 * Copia y pega esto en la consola del navegador ANTES de enviar el formulario
 * para ver exactamente qué se está enviando
 */

console.log(' INICIANDO DIAGNÓSTICO DE FormData');

// Interceptar fetch
const originalFetch = window.fetch;
window.fetch = function(...args) {
    const [resource, config] = args;
    
    if (resource && typeof resource === 'string' && 
        (resource.includes('crear-pedido') || resource.includes('pedidos'))) {
        
        console.log('\n╔════════════════════════════════════════════════════════════════╗');
        console.log('║                INTERCEPTADA SOLICITUD AL SERVIDOR              ║');
        console.log('╚════════════════════════════════════════════════════════════════╝\n');
        
        console.log('📍 URL:', resource);
        console.log('📤 Método:', config?.method || 'GET');
        
        if (config?.body instanceof FormData) {
            console.log('\n FormData CAPTURADO:\n');
            
            let formDataAnalisis = {
                campos: {},
                archivos: {},
                imagenesContadas: 0,
                tamaño: 0
            };
            
            for (let [key, value] of config.body.entries()) {
                if (value instanceof File) {
                    if (!formDataAnalisis.archivos[key]) {
                        formDataAnalisis.archivos[key] = [];
                    }
                    formDataAnalisis.archivos[key].push({
                        nombre: value.name,
                        tipo: value.type,
                        tamaño: value.size,
                        tamañoKB: (value.size / 1024).toFixed(2)
                    });
                    formDataAnalisis.imagenesContadas++;
                    formDataAnalisis.tamaño += value.size;
                } else if (typeof value === 'string' && value.length > 100) {
                    formDataAnalisis.campos[key] = value.substring(0, 100) + '...';
                } else {
                    formDataAnalisis.campos[key] = value;
                }
            }
            
            console.log(' RESUMEN:');
            console.log('   • Imágenes/Archivos: ' + formDataAnalisis.imagenesContadas);
            console.log('   • Tamaño total: ' + (formDataAnalisis.tamaño / 1024 / 1024).toFixed(2) + ' MB');
            
            console.log('\n CAMPOS DE TEXTO:');
            Object.entries(formDataAnalisis.campos).forEach(([key, value]) => {
                console.log('   ✓ ' + key + ': ' + (typeof value === 'object' ? JSON.stringify(value).substring(0, 50) : value));
            });
            
            console.log('\n📸 ARCHIVOS:');
            Object.entries(formDataAnalisis.archivos).forEach(([key, files]) => {
                console.log('   📁 ' + key + ': ' + files.length + ' archivo(s)');
                files.forEach((file, idx) => {
                    console.log(`      [${idx}] ${file.nombre} (${file.tipo}) - ${file.tamañoKB} KB`);
                });
            });
            
            console.log('\n DETALLES DE PROCESOS (si existen):');
            
            // Analizar campos de procesos
            const procesosFields = [];
            for (let [key, value] of config.body.entries()) {
                if (key.includes('procesos')) {
                    procesosFields.push({ key, isFile: value instanceof File });
                }
            }
            
            if (procesosFields.length > 0) {
                console.log('   Encontrados ' + procesosFields.length + ' campos de procesos:');
                procesosFields.forEach(p => {
                    console.log('   ✓ ' + p.key + ' → ' + (p.isFile ? '📷 ARCHIVO' : ' TEXTO'));
                });
            } else {
                console.log('     NO SE ENCONTRARON CAMPOS DE PROCESOS');
            }
            
        } else if (config?.body) {
            console.log(' Body (no es FormData):', config.body.substring(0, 200));
        }
        
        console.log('\n Diagnóstico completado. El servidor recibirá esta información.\n');
    }
    
    // Llamar al fetch original
    return originalFetch.apply(this, args);
};

console.log(' Diagnóstico activado. Ahora haz clic en "Guardar Pedido"');
console.log(' Se mostrará el análisis del FormData que se envía\n');

// Función auxiliar para monitorear errores de red
window.addEventListener('error', function(event) {
    if (event.message.includes('fetch') || event.message.includes('Network')) {
        console.error(' Error de red detectado:', event.message);
    }
});

// Monitorear respuestas fallidas
document.addEventListener('error', function(event) {
    console.error(' Error en documento:', event);
}, true);
