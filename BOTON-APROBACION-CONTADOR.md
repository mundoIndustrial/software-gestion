# Implementación: Botón de Aprobación para Contador

## 🎯 Objetivo
Crear un botón en la interfaz de Contador que permita aprobar una cotización y enviarla automáticamente al área de Aprobación de Cotizaciones.

## 📋 Estado
✅ **COMPLETADO**

Fecha: 2025-12-04
Usuario: GitHub Copilot

## 🔧 Cambios Realizados

### 1. Modificación de Vistas (resources/views/contador/index.blade.php)

**Cambio:** Se agregó un `modal-footer` con botones de acción al modal de detalle de cotización.

```blade
<div class="modal-footer" style="padding: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 1rem; background: #f9fafb;">
    <button type="button" class="btn-secondary" onclick="closeCotizacionModal()" style="...">
        Cancelar
    </button>
    <button type="button" id="btnAprobarContador" class="btn-primary" onclick="aprobarCotizacionComoContador()" style="...display: none;">
        <span class="material-symbols-rounded">check_circle</span>
        Aprobar y Enviar a Aprobador
    </button>
</div>
```

**Detalles:**
- Botón "Cancelar": Cierra el modal
- Botón "Aprobar y Enviar a Aprobador": 
  - Solo visible cuando estado = "Enviada a Contador"
  - Color verde (#10b981) para indicar acción positiva
  - Ícono de check_circle
  - Se muestra/oculta dinámicamente según el estado

### 2. Modificación de JavaScript (public/js/contador/cotizacion.js)

**Cambio 1: Variable Global para Guardar el ID de Cotización**

```javascript
// Variable global para guardar el ID de la cotización actual
let cotizacionIdActual = null;
```

**Cambio 2: Modificación de `openCotizacionModal()` para Detectar Estado**

```javascript
// Guardar el ID de cotización actual
cotizacionIdActual = cotizacionId;

// Obtener el estado de la cotización (quinta columna)
const estado = cells[4]?.textContent.trim() || '';

// Mostrar/ocultar el botón de aprobación según el estado
const btnAprobar = document.getElementById('btnAprobarContador');
if (estado === 'Enviada a Contador') {
    btnAprobar.style.display = 'inline-block';
} else {
    btnAprobar.style.display = 'none';
}
```

**Cambio 3: Nueva Función `aprobarCotizacionComoContador()`**

```javascript
/**
 * Aprueba la cotización como contador y la envía a aprobador
 */
function aprobarCotizacionComoContador() {
    // 1. Validar que cotizacionIdActual esté definido
    // 2. Mostrar confirmación con SweetAlert
    // 3. Enviar POST a /cotizaciones/{id}/aprobar-contador
    // 4. Manejar respuesta de éxito o error
    // 5. Recargar la página si es exitoso
}
```

## 🔄 Flujo de Aprobación

```
┌─────────────────────────────────────────────────────────────┐
│ Contador abre cotización en el modal                         │
│ (Estado: ENVIADA_CONTADOR)                                  │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ Se detecta estado y se muestra botón "Aprobar"              │
│ (JavaScript verifica estado de la 5ta columna)              │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ Contador hace clic en "Aprobar y Enviar a Aprobador"        │
│ (onclick="aprobarCotizacionComoContador()")                 │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ SweetAlert muestra confirmación                             │
│ (usuario confirma la acción)                                │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ Se envía POST a /cotizaciones/{id}/aprobar-contador         │
│ (CotizacionEstadoController::aprobarContador)               │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ Backend procesa la aprobación:                              │
│ - Transición de estado: ENVIADA_CONTADOR → APROBADA_CONTADOR│
│ - Registro en historial de cambios                          │
│ - Dispara Job: AsignarNumeroCotizacionJob                   │
│   • Asigna número_cotizacion                                │
│   • Dispara: EnviarCotizacionAAprobadorJob                  │
│     * Transición: APROBADA_CONTADOR → APROBADA_COTIZACIONES │
│     * Envía notificación: CotizacionListaParaAprobacionNot. │
│       - Email a aprobador_cotizaciones                      │
│       - Registro en BD (notifications table)                │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ SweetAlert muestra éxito                                     │
│ Se cierra modal y se recarga la página                      │
│ (Estado de la cotización ahora es "Aprobada por Contador")  │
└─────────────────────────────────────────────────────────────┘
```

## 📡 Endpoints Involucrados

- **POST** `/cotizaciones/{id}/aprobar-contador`
  - Controlador: `CotizacionEstadoController@aprobarContador`
  - Middleware: `auth`, `verified`
  - Respuesta: JSON con `success`, `message`, `data`

## 🔐 Seguridad

- Validación en JavaScript: Solo se habilita el botón si estado = "Enviada a Contador"
- Validación en Backend: El servicio valida la transición de estado
- CSRF Token: Se incluye en el header `X-CSRF-TOKEN`
- Autenticación: Solo usuarios autenticados y verificados
- Log de Cambios: Se registra en `historial_cambios_cotizaciones`

## ✅ Testing Manual

### Casos de Prueba

1. **Caso 1: Cotización en estado ENVIADA_CONTADOR**
   - ✅ Abre cotización en modal
   - ✅ Botón "Aprobar" se muestra
   - ✅ Hace clic y confirma
   - ✅ Se aprueba exitosamente
   - ✅ Notificación enviada a aprobador
   - ✅ Se recarga la página con nuevo estado

2. **Caso 2: Cotización en otro estado**
   - ✅ Abre cotización en modal
   - ✅ Botón "Aprobar" está oculto
   - ✅ No se puede hacer clic

3. **Caso 3: Cancelación**
   - ✅ Abre cotización
   - ✅ Hace clic en "Cancelar"
   - ✅ Modal se cierra sin cambios

## 📊 Integración con Sistema Existente

### Componentes Relacionados

1. **Estado de Cotización**
   - Enum: `EstadoCotizacion::ENVIADA_CONTADOR`
   - Enum: `EstadoCotizacion::APROBADA_CONTADOR`
   - Transición: Validada en `CotizacionEstadoService`

2. **Service Layer**
   - Método: `CotizacionEstadoService::aprobarComoContador()`
   - Lógica: Valida estado, registra historial, dispara jobs

3. **Queue Jobs**
   - `AsignarNumeroCotizacionJob`: Genera número de cotización
   - `EnviarCotizacionAAprobadorJob`: Envía a aprobadores

4. **Notificaciones**
   - `CotizacionListaParaAprobacionNotification`
   - Canales: Mail + Database

5. **Base de Datos**
   - Tabla: `cotizaciones` (campo `estado`)
   - Tabla: `historial_cambios_cotizaciones` (auditoría)

## 🚀 Funcionalidades Habilitadas

1. ✅ Botón de aprobación visible en modal de contador
2. ✅ Confirmación antes de aprobar
3. ✅ Transición automática de estado
4. ✅ Generación automática de número de cotización
5. ✅ Envío automático a aprobador
6. ✅ Notificación a aprobador (mail + BD)
7. ✅ Registro de auditoría

## 📝 Notas Importantes

- El botón solo se muestra cuando el estado de la cotización es exactamente "Enviada a Contador"
- La confirmación da contexto de que se enviará al área de Aprobación
- El sistema automáticamente dispara los jobs de notificación
- La página se recarga automáticamente para reflejar el nuevo estado
- El flujo respeta la arquitectura existente con Service + Jobs + Notifications

## 🔍 Verificación de Funcionamiento

Para verificar que todo funciona correctamente:

1. Acceder como usuario "Contador" (rol: contador)
2. Ver tabla de cotizaciones
3. Clickear en una cotización con estado "Enviada a Contador"
4. Verificar que aparece botón "Aprobar y Enviar a Aprobador"
5. Hacer clic en botón
6. Confirmar en SweetAlert
7. Esperar notificación de éxito
8. Verificar que estado cambió en la tabla
9. Verificar que aprobador recibió notificación por email

---

**Implementación completada exitosamente** ✅

