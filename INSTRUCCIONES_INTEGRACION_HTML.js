/**
 * INSTRUCCIONES DE INTEGRACIÓN - HTML
 * 
 * Cómo incluir los scripts en tu página HTML/Blade
 */

/*

╔════════════════════════════════════════════════════════════════════════╗
║             INTEGRACIÓN EN HTML - 3 PASOS SIMPLES                      ║
╚════════════════════════════════════════════════════════════════════════╝


PASO 1: INCLUIR LOS SCRIPTS EN HTML
═══════════════════════════════════════════════════════════════════════

En tu archivo blade o HTML, ANTES de </body>, agregar:

<script src="/js/modulos/crear-pedido/procesos/services/cotizacion-prenda-handler.js"></script>
<script src="/js/modulos/crear-pedido/procesos/services/cotizacion-prenda-config.js"></script>
<script src="/js/modulos/crear-pedido/procesos/services/prenda-editor-extension.js"></script>
<script src="/js/modulos/crear-pedido/procesos/services/inicializador-origen-automatico.js"></script>


OPCIÓN 1 - BLADE TEMPLATE (resources/views/crear-pedido.blade.php):
─────────────────────────────────────────────────────────────────────

<!DOCTYPE html>
<html>
<head>
    <!-- ... otros scripts ... -->
</head>
<body>
    <!-- ... contenido ... -->

    <!-- Scripts de la aplicación -->
    <script src="/js/modulos/crear-pedido/procesos/services/prenda-editor.js"></script>
    
    <!-- 🔴 AGREGAR ESTOS SCRIPTS 🔴 -->
    <script src="/js/modulos/crear-pedido/procesos/services/cotizacion-prenda-handler.js"></script>
    <script src="/js/modulos/crear-pedido/procesos/services/cotizacion-prenda-config.js"></script>
    <script src="/js/modulos/crear-pedido/procesos/services/prenda-editor-extension.js"></script>
    <script src="/js/modulos/crear-pedido/procesos/services/inicializador-origen-automatico.js"></script>
    
    <!-- Script de inicialización específico del proyecto -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            console.log('✅ Página lista - Sistema de origen automático inicializado');
        });
    </script>
</body>
</html>


OPCIÓN 2 - VITE/MIX (resources/js/app.js):
─────────────────────────────────────────────────────────────────────

// Al inicio del archivo app.js

import CotizacionPrendaHandler from './modulos/crear-pedido/procesos/services/cotizacion-prenda-handler.js';
import CotizacionPrendaConfig from './modulos/crear-pedido/procesos/services/cotizacion-prenda-config.js';
import PrendaEditorExtension from './modulos/crear-pedido/procesos/services/prenda-editor-extension.js';

// Exponer globalmente
window.CotizacionPrendaHandler = CotizacionPrendaHandler;
window.CotizacionPrendaConfig = CotizacionPrendaConfig;
window.PrendaEditorExtension = PrendaEditorExtension;

// Inicializador
import './modulos/crear-pedido/procesos/services/inicializador-origen-automatico.js';


PASO 2: CREAR INSTANCIA DE PRENDAEDITOR
═══════════════════════════════════════════════════════════════════════

Cuando crees la instancia de PrendaEditor, ahora soporta cotización:

// CON COTIZACIÓN (nuevo)
const prendaEditor = new PrendaEditor({
    notificationService: window.notificationService,
    cotizacionActual: {
        id: 1,
        numero_cotizacion: 'CZ-001',
        tipo_cotizacion_id: 'Reflectivo'
    }
});

// O SIN COTIZACIÓN (comportamiento normal)
const prendaEditor = new PrendaEditor({
    notificationService: window.notificationService
});


PASO 3: CARGAR PRENDAS DESDE COTIZACIÓN
═══════════════════════════════════════════════════════════════════════

Opción A - Cargar una prenda:
────────────────────────────
const prenda = { 
    nombre_prenda: 'Camiseta', 
    talla: 'M',
    color: 'Azul'
};

const cotizacion = {
    id: 100,
    numero_cotizacion: 'CZ-001',
    tipo_cotizacion_id: 'Reflectivo'
};

prendaEditor.cargarPrendaEnModal(prenda, 0);


Opción B - Cargar múltiples prendas (RECOMENDADO):
────────────────────────────────────────────────
const prendas = [
    { nombre_prenda: 'Camiseta', talla: 'M' },
    { nombre_prenda: 'Pantalón', talla: 'L' }
];

const cotizacion = {
    id: 100,
    numero_cotizacion: 'CZ-001',
    tipo_cotizacion_id: 'Logo'
};

// Cargar prendas con origen automático
const prendasProcesadas = prendaEditor.cargarPrendasDesdeCotizacion(
    prendas, 
    cotizacion
);

// Ahora agregar al pedido
window.prendas = [...(window.prendas || []), ...prendasProcesadas];


EJEMPLO COMPLETO - CARGAR COTIZACIÓN
═══════════════════════════════════════════════════════════════════════

// Cuando el usuario selecciona una cotización
document.getElementById('select-cotizacion').addEventListener('change', async (e) => {
    const cotizacionId = e.target.value;
    
    // Obtener datos de la cotización
    const response = await fetch(`/api/cotizaciones/${cotizacionId}`);
    const data = await response.json();
    
    const cotizacion = data.cotizacion;
    const prendas = data.prendas;
    
    // Cargar en PrendaEditor (aplica origen automático)
    const prendasProcesadas = prendaEditor.cargarPrendasDesdeCotizacion(
        prendas,
        cotizacion
    );
    
    // Agregar a lista del pedido
    window.prendas = [...(window.prendas || []), ...prendasProcesadas];
    
    // Mostrar notificación
    console.log(`✅ ${prendas.length} prendas cargadas`);
    console.log('Estadísticas:', window.obtenerEstadisticasPrendas());
});


VERIFICAR QUE FUNCIONA
═══════════════════════════════════════════════════════════════════════

En la consola del navegador (F12), ejecuta:

// 1. Ver estado del sistema
debugOrigenAutomatico()

// 2. Ejecutar tests
testearOrigenAutomatico()

// 3. Ver tipos registrados
CotizacionPrendaConfig.mostrarEstado()

// 4. Ver estadísticas
window.obtenerEstadisticasPrendas()


API DEL PRENDAEDITOR (NUEVAS FUNCIONES)
═══════════════════════════════════════════════════════════════════════

// Aplicar origen automático a una prenda individual
prendaEditor.aplicarOrigenAutomaticoDesdeCotizacion(prenda)

// Cargar múltiples prendas con origen automático
prendaEditor.cargarPrendasDesdeCotizacion(prendas, cotizacion)

// Abrir modal con cotización (nueva opción)
prendaEditor.abrirModal(false, null, cotizacion)

// Cambiar cotización actual
prendaEditor.cotizacionActual = nuevaCotizacion


TROUBLESHOOTING
═══════════════════════════════════════════════════════════════════════

Error: "CotizacionPrendaHandler is not defined"
Solución: Verificar que los scripts están en el HTML en el orden correcto

Error: "Origen sigue siendo 'confeccion' para Reflectivo"
Solución: Ejecutar CotizacionPrendaConfig.mostrarEstado() para ver si tipos están registrados

Error: "/api/tipos-cotizacion 404"
Solución: Implementar endpoint en backend (ver API_TIPOS_COTIZACION.md)

*/

// ============================================================================
// CHECKLIST RÁPIDO DE INCLUSIÓN
// ============================================================================

/**
 * Usar este checklist para verificar que todo está bien incluido
 */
window.verificarIntegracion = function() {
    console.group('✅ VERIFICACIÓN DE INTEGRACIÓN');
    
    const checks = {
        '1. CotizacionPrendaHandler': typeof CotizacionPrendaHandler !== 'undefined',
        '2. CotizacionPrendaConfig': typeof CotizacionPrendaConfig !== 'undefined',
        '3. PrendaEditor': typeof PrendaEditor !== 'undefined',
        '4. PrendaEditor.cargarPrendasDesdeCotizacion': 
            typeof PrendaEditor !== 'undefined' && 
            typeof PrendaEditor.prototype.cargarPrendasDesdeCotizacion === 'function',
        '5. Función debugOrigenAutomatico': typeof debugOrigenAutomatico !== 'undefined',
        '6. API /api/tipos-cotizacion': 'Verificar manualmente en Network tab'
    };
    
    let todoBien = true;
    Object.entries(checks).forEach(([check, resultado]) => {
        const symbol = resultado === true ? '✅' : (resultado === false ? '❌' : '⚠️');
        console.log(`${symbol} ${check}`);
        if (resultado === false) todoBien = false;
    });
    
    if (todoBien) {
        console.log('\n✅ ¡INTEGRACIÓN COMPLETA! Sistema listo para usar');
    } else {
        console.log('\n❌ Faltan elementos. Revisar incluir todos los scripts en HTML');
    }
    
    console.groupEnd();
};

// Verificar automáticamente al cargar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', verificarIntegracion);
} else {
    verificarIntegracion();
}
