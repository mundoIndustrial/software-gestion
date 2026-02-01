#!/usr/bin/env bash

# ============================================================================
# CHECKLIST DE INSTALACIÓN - Origen Automático de Prendas
# ============================================================================

cat << 'EOF'

╔════════════════════════════════════════════════════════════════════════╗
║                   CHECKLIST DE IMPLEMENTACIÓN                         ║
║            Origen Automático de Prendas desde Cotización              ║
╚════════════════════════════════════════════════════════════════════════╝

FASE 1: PREPARACIÓN
───────────────────────────────────────────────────────────────────────

[ ] 1. Leer el RESUMEN_ORIGEN_AUTOMATICO.md
      └─ Entender el concepto general

[ ] 2. Leer el QUICK_START_ORIGEN_PRENDAS.md
      └─ Familiarizarse con los pasos básicos

[ ] 3. Revisar archivos generados en:
      public/js/modulos/crear-pedido/procesos/services/
      ├── cotizacion-prenda-handler.js
      ├── cotizacion-prenda-config.js
      └── prenda-editor-extension.js


FASE 2: BACKEND (API REST)
───────────────────────────────────────────────────────────────────────

[ ] 4. Leer API_TIPOS_COTIZACION.md
      └─ Comprender estructura de respuesta esperada

[ ] 5. Implementar endpoint GET /api/tipos-cotizacion
      Debe retornar:
      {
          "success": true,
          "data": [
              { "id": 1, "nombre": "Reflectivo", "requiere_bodega": true },
              { "id": 2, "nombre": "Logo", "requiere_bodega": true }
          ]
      }

[ ] 6. Probar endpoint en Postman/Thunder Client
      URL: http://localhost:8000/api/tipos-cotizacion
      Método: GET
      Status esperado: 200 OK

[ ] 7. Verificar que tipos con requiere_bodega: true incluyen:
      ✓ Reflectivo
      ✓ Logo
      (Agregar más según necesidad)


FASE 3: FRONTEND - INCLUIR SCRIPTS
───────────────────────────────────────────────────────────────────────

[ ] 8. Copiar archivos a proyecto
      Origen: /public/js/modulos/crear-pedido/procesos/services/
      Archivos:
      ├── cotizacion-prenda-handler.js
      ├── cotizacion-prenda-config.js
      └── prenda-editor-extension.js

[ ] 9. Incluir scripts en HTML (blade template)
      Agregar antes de </body>:
      
      <script src="/js/modulos/crear-pedido/procesos/services/cotizacion-prenda-handler.js"></script>
      <script src="/js/modulos/crear-pedido/procesos/services/cotizacion-prenda-config.js"></script>
      <script src="/js/modulos/crear-pedido/procesos/services/prenda-editor-extension.js"></script>

[ ] 10. Recargar página en navegador
       Presionar F12 → Console tab
       Verificar que no hay errores de 404


FASE 4: INICIALIZACIÓN
───────────────────────────────────────────────────────────────────────

[ ] 11. En DOMContentLoaded, inicializar configuración
        Agregar en JavaScript (crear-pedido.js o similar):
        
        document.addEventListener('DOMContentLoaded', async () => {
            // Inicializar handlers de origen automático
            await CotizacionPrendaConfig.inicializarConRetroalimentacion();
            
            // Mostrar estado en consola
            CotizacionPrendaConfig.mostrarEstado();
        });

[ ] 12. Recargar página
        Abrir F12 → Console
        Buscar logs: "✓ Tipos cargados desde API"
        Verificar que no hay errores


FASE 5: TESTING INICIAL
───────────────────────────────────────────────────────────────────────

[ ] 13. Ejecutar tests automáticos
        En consola (F12):
        testearOrigenAutomatico()
        
        Resultado esperado: 3 tests verdes ✓

[ ] 14. Verificar tipos registrados
        En consola:
        CotizacionPrendaHandler.obtenerTiposBodega()
        
        Debe mostrar: ["Reflectivo", "Logo"]

[ ] 15. Probar lógica manualmente
        En consola:
        
        const test = CotizacionPrendaHandler.prepararPrendaParaEdicion(
            { nombre: 'Camiseta' },
            { tipo_cotizacion_id: 'Reflectivo' }
        );
        console.log(test.origen); // Debe ser "bodega"


FASE 6: INTEGRACIÓN CON PRENDAEDITOR
───────────────────────────────────────────────────────────────────────

[ ] 16. Inicializar extensión de PrendaEditor
        En mismo DOMContentLoaded:
        
        const prendaEditor = new PrendaEditor({
            notificationService: window.notificationService
        });
        PrendaEditorExtension.inicializar(prendaEditor);

[ ] 17. Identificar dónde se cargan prendas desde cotización
        En tu código, buscar donde:
        - Se selecciona una cotización en dropdown
        - Se hace fetch a /api/cotizaciones/{id}/prendas
        - Se agrega prenda a la lista

[ ] 18. Integrar cargar prendas con extension
        Reemplazar código de carga con:
        
        PrendaEditorExtension.cargarPrendasDesdeCotizacion(
            prendas,
            cotizacion
        );

[ ] 19. Probar seleccionar una cotización "Reflectivo"
        Verificar en:
        - Consola: debe haber logs de prendas cargadas
        - Inspector: prendas en memoria deben tener origen = "bodega"


FASE 7: FLUJO COMPLETO
───────────────────────────────────────────────────────────────────────

[ ] 20. Crear un pedido completo:
        1. Seleccionar cotización "Reflectivo"
        2. Se cargan prendas (con origen = "bodega")
        3. Editar alguna prenda (modal debe mostrar origen)
        4. Guardar el pedido
        5. Verificar en BD que origen = "bodega"

[ ] 21. Crear un pedido con cotización "Logo":
        Verificar que también obtiene origen = "bodega"

[ ] 22. Crear un pedido con cotización "Estándar":
        Verificar que mantiene origen = "confeccion"


FASE 8: VALIDACIÓN EN PRODUCCIÓN
───────────────────────────────────────────────────────────────────────

[ ] 23. Limpiar localStorage (opcional)
        localStorage.removeItem('tipos-cotizacion-bodega');

[ ] 24. Desactivar syncronización automática en tests
        Para que no haya llamadas continuas a API:
        // const syncId = CotizacionPrendaConfig.iniciarSincronizacionAutomatica();

[ ] 25. Hacer deploy a producción

[ ] 26. Verificar en producción:
        1. Abrir página en incógnito (sin caché)
        2. F12 → Console
        3. Verificar logs de inicialización
        4. Probar flujo completo con datos reales


FASE 9: MONITOREO
───────────────────────────────────────────────────────────────────────

[ ] 27. Configurar alertas de error
        Agregar en console.error catching:
        
        window.addEventListener('error', (e) => {
            if (e.message.includes('CotizacionPrenda')) {
                // Alertar a equipo
                fetch('/api/errors/log', { 
                    method: 'POST',
                    body: JSON.stringify({ error: e.message })
                });
            }
        });

[ ] 28. Monitorear estadísticas
        En cada carga de cotización:
        PrendaEditorExtension.mostrarReporte();

[ ] 29. Revisar logs en BD
        SELECT COUNT(*) FROM prendas WHERE origen = 'bodega';
        Compararar con cantidad de cotizaciones Reflectivo/Logo


FASE 10: OPTIMIZACIÓN Y MANTENIMIENTO
───────────────────────────────────────────────────────────────────────

[ ] 30. Si todo funciona correctamente:
        ✓ Quitar logs de debug (cambiar console.debug a comentarios)
        ✓ Reducir intervalo de sincronización si es necesario
        ✓ Documentar en README del proyecto


TROUBLESHOOTING
───────────────────────────────────────────────────────────────────────

PROBLEMA: "CotizacionPrendaHandler is not defined"
SOLUCIÓN: Verificar que el script está en HTML (paso 9)
          Verificar que el archivo existe en proyecto
          Recargar página (Ctrl+Shift+R para limpia caché)

PROBLEMA: "GET /api/tipos-cotizacion 404"
SOLUCIÓN: Verificar que endpoint existe en backend (paso 5-6)
          Verificar URL en código
          Ver Network tab del navegador

PROBLEMA: "Tipos no se registran"
SOLUCIÓN: Ejecutar en consola:
          CotizacionPrendaConfig.mostrarEstado()
          Revisar que API retorna requiere_bodega: true

PROBLEMA: "Prendas no cambian origen"
SOLUCIÓN: Verificar que:
          1. Tipos están registrados (paso 14)
          2. Cotización tiene tipo_cotizacion_id correcto
          3. Ejecutar: testearOrigenAutomatico()

PROBLEMA: "Origen se asigna para todos"
SOLUCIÓN: Revisar que cotización tiene tipo_cotizacion_id correcto
          Ejecutar: CotizacionPrendaHandler.requiereBodega(tipoId)


MÉTRICAS DE ÉXITO
───────────────────────────────────────────────────────────────────────

✓ API endpoint responde correctamente
✓ Scripts se cargan sin errores en consola
✓ Tipos se registran correctamente
✓ testearOrigenAutomatico() pasa todos los tests
✓ Prendas de "Reflectivo" obtienen origen = "bodega"
✓ Prendas de otros tipos obtienen origen = "confeccion"
✓ Prendas manuales no son afectadas
✓ BD guarda origen correcto para cada prenda
✓ No hay errores en consola durante operación normal


DOCUMENTOS DE REFERENCIA
───────────────────────────────────────────────────────────────────────

Necesito saber cómo...
├─ Iniciar rápido?
│  └─ QUICK_START_ORIGEN_PRENDAS.md

├─ Entender la arquitectura?
│  └─ RESUMEN_ORIGEN_AUTOMATICO.md

├─ Configurar el backend?
│  └─ API_TIPOS_COTIZACION.md

├─ Usar la clase principal?
│  └─ GUIA_ORIGEN_AUTOMATICO_PRENDAS.md

├─ Ver ejemplos de código?
│  └─ cotizacion-prenda-handler-ejemplos.js

└─ Integrar con PrendaEditor?
   └─ prenda-editor-extension.js (con comentarios)


PRÓXIMOS PASOS DESPUÉS DE IMPLEMENTACIÓN
───────────────────────────────────────────────────────────────────────

[ ] Agregar más tipos de cotización dinámicamente
[ ] Implementar UI para mostrar origen de prendas
[ ] Agregar filtro de "agrupar por origen"
[ ] Crear reporte de prendas por origen
[ ] Sincronizar cambios en tiempo real con websockets
[ ] Agregar validaciones en backend para origen


═════════════════════════════════════════════════════════════════════════

Cuando completes todos los checks, el sistema estará listo para producción.

Última revisión: Revisar que:
  1. No hay errores en consola
  2. Logs muestran inicialización correcta
  3. Flujo completo funciona sin problemas
  4. BD guarda datos correctamente
  5. Usuarios reportan funcionamiento correcto

═════════════════════════════════════════════════════════════════════════

EOF

echo ""
echo "✅ Checklist completado - Sistema listo para implementación"
echo ""
