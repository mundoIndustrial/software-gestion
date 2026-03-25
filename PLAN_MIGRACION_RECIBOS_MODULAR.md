# Plan de Migración: Recibos de Costura a Arquitectura Modular DDD

## 📋 Estado Actual (24-03-2026)

###  COMPLETADO
- **PHASE 0 Backend**: API endpoints funcionales
  - GET `/api/recibos-costura` - Lista recibos con paginación
  - GET `/api/recibos-costura/filter-options` - Opciones de filtro
  
- **PHASE 1 Frontend Modular**: Todos los módulos creados
  - Domain Layer: 4 Value Objects (EstadoRecibo, AreaRecibo, DiasTranscurridos, EncargadoProceso)
  - Infrastructure Layer: ReciboCosturaAPI + RecibosState
  - Presentation Layer: RecibosTableController
  - Entry Point: RecibosCostruaModule

- **PHASE 2 Bundle**: Compilación única
  - `bundle.js`: Contiene toda la arquitectura modular
  - Auto-inicializa en DOMContentLoaded
  - Renderiza tabla con datos en tiempo real
  - Sistema de dropdowns funcional

### 🔄 EN PROGRESO
**Compatibilidad Dual de Funciones**: Blade mantiene fallbacks, bundle sobrescribe si carga

---

## 🎯 Plan de Migración Método por Método

### FASE A: FUNCIONES DE DROPDOWN Y MODALES (Prioridad: ALTA)
**Estado**: 60% migrado al bundle.js

| Función | Ubicación Bundle | Ubicación Blade | Estado | Acción |
|---------|------------------|-----------------|--------|--------|
| `setupTableEventListeners()` | bundle.js:864 | blade.php:880* | Delegada |  Ya en bundle (versión mejorada) |
| `crearDropdownRecibos()` | bundle.js:906 | blade.php:1402* | Delegada |  Ya en bundle |
| `closeDropdownRecibos()` | bundle.js:985 | blade.php:1389* | Delegada |  Ya en bundle |
| `posicionarDropdown()` | bundle.js:984 | blade.php:1470* | Delegada |  Ya en bundle |
| `openOrderDetailModal()` | bundle.js:997 | blade.php:1028* | Delegada |  Ya en bundle |
| `window.closeModalOverlay()` | bundle.js:1017 | blade.php:1361* | Delegada |  Ya en bundle |
| `openOrderDetailModalDirect()` | bundle.js:991 | (nuevo) | Delegada |  En bundle |
| `openOrderTrackingDirect()` | bundle.js:1004 | (nuevo) | Delegada |  En bundle |
| `openNovedadesModal()` | bundle.js:1015 | (nuevo) | Delegada |  En bundle |

**Proxima acción para Fase A**:
1. Esperar confirmación que todos los dropdowns/modales funcionan
2. Comentar versiones en blade.php como "DEPRECATED - usar bundle.js"
3. Eliminarlos gradualmente según pruebas

---

### FASE B: FUNCIONES DE FILTRO (Prioridad: MEDIA)
**Estado**: No migrado (0%)

| Función | Ubicación Blade | Complejidad | Estado | Acción |
|---------|-----------------|-------------|--------|--------|
| `openFilterModal()` | blade.php:340 | Media | No migrado | Mantener por ahora |
| `loadFilterOptions()` | blade.php:362 | Alta | No migrado | Mantener (usa DOM) |
| `getDynamicFilterOptions()` | blade.php:387 | Alta | No migrado | Mantener (análisis tabla) |
| `getColumnIndex()` | blade.php:413 | Baja | No migrado | Mantener |
| `closeFilterModal()` | blade.php:435 | Baja | No migrado | Mantener |
| `resetFilters()` | blade.php:442 | Media | No migrado | Mantener |
| `applyFilters()` | blade.php:458 | Alta | No migrado | Mantener |
| `selectAllCheckboxFilters()` | blade.php:507 | Baja | No migrado | Mantener |
| `filterCheckboxOptions()` | blade.php:514 | Baja | No migrado | Mantener |

**Acción Fase B**:
- Estas funciones están bien donde están por ahora
- Son complejas de migrar, requieren testing exhaustivo
- Aprioridad: post-validación de Fase A

---

### FASE C: FUNCIONES DE MODALES ADICIONALES (Prioridad: MEDIA-BAJA)
**Estado**: No migrado (0%)

| Función | Ubicación Blade | Uso | Status |
|---------|-----------------|-----|--------|
| `verDetallesRecibo()` | blade.php:700 | Iniciar modal detalles | Legacy |
| `abrirModalSeguimiento()` | blade.php:755 | Abrir seguimiento | Legacy |
| `abrirModalSeguimientoDirecto()` | blade.php:812 | Abrir seguimiento directo | Legacy |
| `abrirModalAgregarProcesoDesdeArea()` | blade.php:938 | Abrir agregar proceso | Legacy |

**Acción Fase C**:
- Mantener por ahora, evaluar si bundle las reemplaza
- Posible: migrar a bundle.js cuando se complete integración

---

### FASE D: SISTEMA DE NOTIFICACIONES (Prioridad: MEDIA-BAJA)
**Estado**: No migrado (0%)

| Función | Ubicación Blade | Uso | Status |
|---------|-----------------|-----|--------|
| `showToast()` | blade.php:1248 | Toast notifications | Core |
| `showSuccess()` | blade.php:1230 | Success toast | Core |
| `showError()` | blade.php:1238 | Error toast | Core |
| `removeToast()` | blade.php:1286 | Remove toast | Core |
| `cargarConteoRecibosCorte()` | blade.php:1620 | Load notification count | Real-time |
| `marcarReciboVisto()` | blade.php:1656 | Mark receipt as seen | Real-time |
| `initializeReciboAprobadoListener()` | blade.php:1753 | Real-time listener | Real-time |
| `showRecibAprobadoNotification()` | blade.php:1768 | Show notification | Real-time |
| `recargarTablaRecibosEnTiempoReal()` | blade.php:1829 | Reload table real-time | Real-time |
| `playNotificationSound()` | blade.php:1868 | Play notification sound | Real-time |

**Acción Fase D**:
- Estas son críticas para sistema en tiempo real
- Mantener por ahora, validar después de Fase A y B

---

## 🚀 Próximos Pasos Inmediatos

### PASO 1: Validación del Bundle (HOY)
- [ ] Recarga el navegador después de cache:clear
- [ ] Verifica en consola: " Bundle.js cargado: SÍ"
- [ ] Prueba click en botón de recibo → debe abrir dropdown
- [ ] Prueba click en "Ver Detalles" → debe abrir modal

### PASO 2: Comentar en Blade (HOYSI TODO FUNCIONA)
Si el bundle funciona:
```javascript
// ⚠️ DEPRECATED - Versión mejorada en bundle.js
// USAR bundle.js en su lugar
function setupTableEventListeners() {
    // FALLBACK SOLAMENTE - Ver bundle.js para versión optimizada
}
```

### PASO 3: Validación por Fase (PRÓXIMA SESIÓN)
- Fase A: Validar dropdowns/modales completamente
- Fase B: Comenzar a migrar filtros
- Fase C: Migrar modales adicionales
- Fase D: Migrar sistema real-time

---

## 📝 Notas Importantes

1. **NO ELIMINAR** funciones del blade hasta que:
   - Bundle.js esté verificado funcionando correctamente
   - Todas las pruebas pasen
   - Se haya esperado al menos 24h sin errores

2. **MANTENER COMPATIBILIDAD** mediante:
   - Dejar fallbacks en blade.php
   - Bundle.js sobrescribe cuando carga
   - Si bundle falla, blade sigue funcionando

3. **ROLLBACK FÁCIL**: Si algo se rompe:
   - Deshabilitar bundle.js en blade
   - Automáticamente caen back a versiones antiguas

4. **MONITOREO**: Revisar console.log de compatibilidad
   ```
   [COMPATIBILIDAD] Bundle.js cargado:  SÍ
   ```

---

## 📊 Checklist de Validación

### Validación Bundle Inicial
- [ ] Bundle.js carga sin errores
- [ ] Tabla renderiza con datos
- [ ] Dropdown aparece al hacer click en botón
- [ ] Modal abre al seleccionar "Ver Detalles"
- [ ] Modales se cierran correctamente
- [ ] Sin errores en consola

### Validación Filtros (Fase B)
- [ ] Abrir filtro por estado
- [ ] Seleccionar opción
- [ ] Aplicar filtro → tabla se filtra
- [ ] Limpiar filtros → tabla vuelve al estado inicial

### Validación Real-time (Fase D)
- [ ] Notificaciones aparecen en tiempo real
- [ ] Sonido de notificación funciona (si activado)
- [ ] Recibos aprobados se reflejan en tabla

---

## 🔗 Archivos Relacionados

- **Bundle**: `public/js/recibos-costura/bundle.js` (1,030 líneas)
- **Blade**: `resources/views/registros/recibos-costura.blade.php` (~1,900 líneas)
- **QueryService**: `app/Services/ReciboCosturaQueryService.php` (API backend)
- **Routes**: `routes/recibos.php` (Endpoints)

---

## 🏁 Meta Final

**Objetivo**: Blade debe tener SOLO:
- Estructura HTML (x-componentes)
- CSS styles
- Funciones legacy que no se pueden/necesitan migrar
- Listeners para compatibilidad

**Bundle debe tener**:
- Toda lógica de negocio (DDD)
- Todos los listeners
- Manipulación de DOM
- Estado centralizado

**Timeline**: 3-5 sesiones de trabajo
