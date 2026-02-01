#!/usr/bin/env node

/**
 * QUICK START - Integración de Origen Automático de Prendas
 * 
 * Guía rápida para implementar en 5 minutos
 */

console.log(`
╔═══════════════════════════════════════════════════════════════════╗
║   Implementación: Origen Automático de Prendas desde Cotización   ║
║                         QUICK START                               ║
╚═══════════════════════════════════════════════════════════════════╝

📁 ARCHIVOS GENERADOS:
─────────────────────────────────────────────────────────────────────
1. cotizacion-prenda-handler.js
   └─ Clase principal con la lógica

2. cotizacion-prenda-config.js
   └─ Configuración y sincronización con API

3. cotizacion-prenda-handler-ejemplos.js
   └─ Ejemplos de uso e integración

4. Documentación:
   ├─ GUIA_ORIGEN_AUTOMATICO_PRENDAS.md (documentación completa)
   └─ API_TIPOS_COTIZACION.md (estructura API backend)


🚀 PASOS DE IMPLEMENTACIÓN:
─────────────────────────────────────────────────────────────────────

PASO 1: Incluir scripts en HTML
─────────────────────────────────
En tu archivo blade o HTML, antes de </body>:

<script src="/js/modulos/crear-pedido/procesos/services/cotizacion-prenda-handler.js"></script>
<script src="/js/modulos/crear-pedido/procesos/services/cotizacion-prenda-config.js"></script>

PASO 2: Inicializar en el DOMContentLoaded
────────────────────────────────────────
<script>
document.addEventListener('DOMContentLoaded', async () => {
    // Opción A: Desde API (recomendado)
    await CotizacionPrendaConfig.inicializarDesdeAPI();

    // O Opción B: Desde localStorage (usa caché)
    await CotizacionPrendaConfig.inicializarConRetroalimentacion();

    // Iniciar sincronización automática
    CotizacionPrendaConfig.iniciarSincronizacionAutomatica(300000);
});
</script>

PASO 3: Usar en tu código
─────────────────────────
Cuando agregues una prenda desde una cotización:

const prenda = { nombre: 'Camiseta', talla: 'M' };
const cotizacion = { tipo_cotizacion_id: 'Reflectivo' };

// Aplicar origen automático
CotizacionPrendaHandler.prepararPrendaParaEdicion(prenda, cotizacion);

console.log(prenda.origen); // "bodega" ✓


💡 CASOS DE USO PRINCIPALES:
─────────────────────────────────────────────────────────────────────

1. CARGAR PRENDAS DE COTIZACIÓN
   └─ Cuando el usuario selecciona una cotización en el dropdown

2. EDITAR PRENDA EXISTENTE
   └─ Mantener el origen automático si viene de cotización

3. AGREGAR PRENDA MANUALMENTE
   └─ Ignorar lógica automática (solo si viene de cotización)


⚙️  CONFIGURACIÓN:
─────────────────────────────────────────────────────────────────────

Tipos que requieren BODEGA (por defecto):
  • Reflectivo → origen = "bodega"
  • Logo → origen = "bodega"

Otros tipos mantienen → origen = "confeccion"

Para agregar más tipos:
CotizacionPrendaHandler.registrarTipoBodega('ID', 'Nombre Tipo');


✅ CHECKLIST DE IMPLEMENTACIÓN:
─────────────────────────────────────────────────────────────────────

[ ] 1. Copiar archivos JS a:
      public/js/modulos/crear-pedido/procesos/services/

[ ] 2. Incluir <script> en HTML

[ ] 3. Llamar CotizacionPrendaConfig.inicializarDesdeAPI()

[ ] 4. Usar CotizacionPrendaHandler.prepararPrendaParaEdicion()
      donde se cargan prendas desde cotización

[ ] 5. Probar en consola:
      testearOrigenAutomatico()

[ ] 6. Verificar origen en prendas cargadas:
      CotizacionPrendaConfig.mostrarEstado()


🧪 TESTING RÁPIDO:
─────────────────────────────────────────────────────────────────────

En consola del navegador (F12):

// Ver tipos registrados
CotizacionPrendaHandler.obtenerTiposBodega()
// → ["Reflectivo", "Logo"]

// Probar lógica
const test = CotizacionPrendaHandler.prepararPrendaParaEdicion(
    { nombre: 'Test' },
    { tipo_cotizacion_id: 'Reflectivo' }
);
console.log(test.origen); // "bodega" ✓

// Suite completa de tests
testearOrigenAutomatico()
// Muestra todos los casos en la consola


📊 IMPACTO EN LA BD:
─────────────────────────────────────────────────────────────────────

Antes:  Prendas de cotización "Reflectivo" con origen = "confeccion"
        ❌ Comportamiento incorrecto

Después: Prendas de cotización "Reflectivo" con origen = "bodega"
         ✅ Comportamiento correcto

La asignación ocurre en el FRONTEND antes de guardar en BD.


🔄 FLUJO DE DATOS:
─────────────────────────────────────────────────────────────────────

Usuario selecciona cotización
    ↓
API retorna tipos_cotizacion con requiere_bodega
    ↓
CotizacionPrendaConfig.inicializarDesdeAPI()
    ↓
Se registran tipos en TIPOS_COTIZACION_BODEGA
    ↓
Usuario carga prendas de la cotización
    ↓
CotizacionPrendaHandler.prepararPrendaParaEdicion()
    ↓
Se verifica tipo_cotizacion_id
    ↓
Se asigna origen = "bodega" si aplica
    ↓
Modal abre con origen correcto
    ↓
Usuario guarda pedido


❓ TROUBLESHOOTING:
─────────────────────────────────────────────────────────────────────

Problema: "CotizacionPrendaHandler is not defined"
Solución: Verificar que el script está incluido antes de usarlo

Problema: Tipos no se registran desde API
Solución: Verificar endpoint /api/tipos-cotizacion retorna JSON
          Revisar en Network tab del navegador

Problema: Origen sigue siendo "confeccion" para Reflectivo
Solución: Verificar que CotizacionPrendaConfig.inicializarDesdeAPI()
          se ejecutó correctamente (revisar console.log)

Problema: localStorage lleno
Solución: Limpiar: localStorage.removeItem('tipos-cotizacion-bodega')


📚 DOCUMENTACIÓN COMPLETA:
─────────────────────────────────────────────────────────────────────

Archivo: GUIA_ORIGEN_AUTOMATICO_PRENDAS.md
├─ Descripción general
├─ Arquitectura
├─ Instalación detallada
├─ API completa
├─ Casos de uso avanzados
└─ FAQ

Archivo: API_TIPOS_COTIZACION.md
├─ Estructura de respuesta JSON
├─ Ejemplo controlador Laravel
├─ Migración DB
├─ Queries SQL de testing
└─ Inicialización desde HTML


🎯 PRÓXIMOS PASOS:
─────────────────────────────────────────────────────────────────────

1. Implementar endpoint /api/tipos-cotizacion (backend)
2. Incluir scripts en HTML
3. Probar en navegador
4. Integrar en PrendaEditor.abrirModal()
5. Validar en producción


📞 NOTAS IMPORTANTES:
─────────────────────────────────────────────────────────────────────

✓ La lógica es agnóstica a la BD - funciona en frontend puro
✓ Compatible con cualquier framework (Laravel, Vue, React, etc.)
✓ Sin dependencias externas
✓ Totalmente escalable - agregar tipos fácilmente
✓ Robusto con fallback a valores por defecto
✓ Logging detallado para debugging


═══════════════════════════════════════════════════════════════════════
¿Listo para empezar? Sigue el PASO 1 arriba ↑
═══════════════════════════════════════════════════════════════════════
`);
