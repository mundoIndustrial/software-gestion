# Implementación: Sistema de Estados para Cotizaciones y Pedidos con Colas

## 📋 RESUMEN DE IMPLEMENTACIÓN COMPLETADA

Se ha implementado completamente el sistema de gestión de estados para cotizaciones y pedidos con procesamiento mediante colas. La implementación incluye validación, auditoría completa y asignación automática de números.

---

## ✅ COMPONENTES IMPLEMENTADOS

### 1. MIGRACIONES (4 archivos)

#### 2025_12_04_000001_add_estado_to_cotizaciones.php
- Agrega enum `estado` a tabla `cotizaciones`
- Agrega campos de timestamp: `aprobada_por_contador_en`, `aprobada_por_aprobador_en`
- Agrega campo `numero_cotizacion` UNIQUE NULLABLE

#### 2025_12_04_000002_add_estado_to_pedidos_produccion.php
- Agrega enum `estado` a tabla `pedidos_produccion`
- Agrega campo `numero_pedido` UNIQUE NULLABLE
- Agrega campo `aprobado_por_supervisor_en`

#### 2025_12_04_000003_create_historial_cambios_cotizaciones_table.php
- Tabla de auditoría completa para cotizaciones
- Campos: usuario, rol, IP, user agent, razón del cambio, datos adicionales
- Índices optimizados para búsquedas rápidas

#### 2025_12_04_000004_create_historial_cambios_pedidos_table.php
- Tabla de auditoría completa para pedidos
- Misma estructura que historial de cotizaciones
- Relacionada a `pedidos_produccion`

### 2. ENUMS (2 archivos)

#### app/Enums/EstadoCotizacion.php
```php
- BORRADOR
- ENVIADA_CONTADOR
- APROBADA_CONTADOR
- APROBADA_COTIZACIONES
- CONVERTIDA_PEDIDO
- FINALIZADA
```

Métodos incluidos:
- `label()` - Nombre legible
- `color()` - Color para UI
- `icon()` - Icono para UI
- `transicionesPermitidas()` - Estados válidos siguientes
- `puedePasar()` - Validar transición

#### app/Enums/EstadoPedido.php
```php
- PENDIENTE_SUPERVISOR
- APROBADO_SUPERVISOR
- EN_PRODUCCION
- FINALIZADO
```

Mismos métodos que EstadoCotizacion

### 3. MODELOS (2 archivos)

#### app/Models/HistorialCambiosCotizacion.php
- Modelo para auditoría de cotizaciones
- Relaciones: `cotizacion()`, `usuario()`
- Sin timestamps (solo `created_at`)

#### app/Models/HistorialCambiosPedido.php
- Modelo para auditoría de pedidos
- Relaciones: `pedido()`, `usuario()`
- Sin timestamps (solo `created_at`)

### 4. SERVICIOS (2 archivos)

#### app/Services/CotizacionEstadoService.php
Métodos principales:
- `enviarACOntador()` - BORRADOR → ENVIADA_CONTADOR
- `aprobarComoContador()` - ENVIADA_CONTADOR → APROBADA_CONTADOR
- `aprobarComoAprobador()` - APROBADA_CONTADOR → APROBADA_COTIZACIONES
- `marcarComoConvertidaAPedido()` - APROBADA_COTIZACIONES → CONVERTIDA_PEDIDO
- `marcarComoFinalizada()` - CONVERTIDA_PEDIDO → FINALIZADA
- `obtenerEstadoActual()` - Estado actual
- `obtenerHistorial()` - Historial de cambios
- `validarTransicion()` - Valida si transición es permitida
- `asignarNumeroCotizacion()` - Asigna número autoincrement
- `obtenerSiguienteNumeroCotizacion()` - Calcula siguiente número

#### app/Services/PedidoEstadoService.php
Métodos principales:
- `aprobarComoSupervisor()` - PENDIENTE_SUPERVISOR → APROBADO_SUPERVISOR
- `enviarAProduccion()` - APROBADO_SUPERVISOR → EN_PRODUCCION
- `marcarComoFinalizado()` - EN_PRODUCCION → FINALIZADO
- `obtenerEstadoActual()` - Estado actual
- `obtenerHistorial()` - Historial de cambios
- `validarTransicion()` - Valida si transición es permitida
- `asignarNumeroPedido()` - Asigna número autoincrement
- `obtenerSiguienteNumeroPedido()` - Calcula siguiente número

### 5. JOBS (4 archivos)

#### app/Jobs/EnviarCotizacionAContadorJob.php
- Dispatchable cuando Asesor envía cotización
- Notifica a Contador (placeholder para implementar)
- Retries: 3 intentos

#### app/Jobs/AsignarNumeroCotizacionJob.php
- Asigna número_cotizacion automáticamente
- Dispara `EnviarCotizacionAAprobadorJob` después
- Retries: 3 intentos

#### app/Jobs/EnviarCotizacionAAprobadorJob.php
- Cambia estado a APROBADA_COTIZACIONES
- Notifica a Aprobador_Cotizaciones (placeholder)
- Retries: 3 intentos

#### app/Jobs/AsignarNumeroPedidoJob.php
- Asigna número_pedido automáticamente
- Cambia estado a EN_PRODUCCION
- Retries: 3 intentos

### 6. CONTROLLERS (2 archivos)

#### app/Http/Controllers/CotizacionEstadoController.php
Endpoints:
- `POST /cotizaciones/{id}/enviar` - Enviar a contador
- `POST /cotizaciones/{id}/aprobar-contador` - Aprobar como contador
- `POST /cotizaciones/{id}/aprobar-aprobador` - Aprobar como aprobador
- `GET /cotizaciones/{id}/historial` - Ver historial
- `GET /cotizaciones/{id}/seguimiento` - Ver seguimiento

#### app/Http/Controllers/PedidoEstadoController.php
Endpoints:
- `POST /pedidos/{id}/aprobar-supervisor` - Aprobar como supervisor
- `GET /pedidos/{id}/historial` - Ver historial
- `GET /pedidos/{id}/seguimiento` - Ver seguimiento

### 7. RUTAS
Agregadas a `routes/web.php`:
```php
// Cotizaciones
Route::post('/cotizaciones/{cotizacion}/enviar', ...)
Route::post('/cotizaciones/{cotizacion}/aprobar-contador', ...)
Route::post('/cotizaciones/{cotizacion}/aprobar-aprobador', ...)
Route::get('/cotizaciones/{cotizacion}/historial', ...)
Route::get('/cotizaciones/{cotizacion}/seguimiento', ...)

// Pedidos
Route::post('/pedidos/{pedido}/aprobar-supervisor', ...)
Route::get('/pedidos/{pedido}/historial', ...)
Route::get('/pedidos/{pedido}/seguimiento', ...)
```

### 8. RELACIONES EN MODELOS

#### Cotizacion.php (Actualizado)
- Agregada relación: `historialCambios()`

#### PedidoProduccion.php (Actualizado)
- Agregada relación: `historialCambios()`

---

## 🔄 FLUJO COMPLETO DEL CASO FELIZ

### Fase 1: Cotización Asesor → Contador
```
1. Asesor crea cotización (estado: BORRADOR)
2. Asesor hace click "Enviar"
   ├─ POST /cotizaciones/{id}/enviar
   ├─ Servicio: enviarACOntador()
   ├─ Estado: BORRADOR → ENVIADA_CONTADOR
   ├─ Job: EnviarCotizacionAContadorJob()
   └─ Historial registrado ✓
```

### Fase 2: Contador Revisa y Aprueba
```
3. Contador recibe notificación
4. Contador hace click "Aprobar"
   ├─ POST /cotizaciones/{id}/aprobar-contador
   ├─ Servicio: aprobarComoContador()
   ├─ Estado: ENVIADA_CONTADOR → APROBADA_CONTADOR
   ├─ Job: AsignarNumeroCotizacionJob()
   │  ├─ Asigna numero_cotizacion (autoincrement)
   │  └─ Job: EnviarCotizacionAAprobadorJob()
   └─ Historial registrado ✓
```

### Fase 3: Aprobador Revisa y Aprueba
```
5. Aprobador recibe notificación
6. Aprobador hace click "Aprobar"
   ├─ POST /cotizaciones/{id}/aprobar-aprobador
   ├─ Servicio: aprobarComoAprobador()
   ├─ Estado: APROBADA_CONTADOR → APROBADA_COTIZACIONES
   └─ Historial registrado ✓
```

### Fase 4: Asesor Busca y Crea Pedido
```
7. Asesor busca cotización por cliente o número_cotizacion
   └─ La cotización es APROBADA_COTIZACIONES (visible)
8. Asesor hace click "Crear Pedido"
   ├─ Se crea PedidoProduccion
   ├─ Pedido estado: PENDIENTE_SUPERVISOR
   ├─ Cotización: CONVERTIDA_PEDIDO
   └─ Historial registrado ✓
```

### Fase 5: Supervisor Revisa y Aprueba
```
9. Supervisor recibe notificación
10. Supervisor hace click "Aprobar"
    ├─ POST /pedidos/{id}/aprobar-supervisor
    ├─ Servicio: aprobarComoSupervisor()
    ├─ Estado: PENDIENTE_SUPERVISOR → APROBADO_SUPERVISOR
    ├─ Job: AsignarNumeroPedidoJob()
    │  ├─ Asigna numero_pedido (autoincrement)
    │  └─ Envía a EN_PRODUCCION
    └─ Historial registrado ✓
```

### Fase 6: Producción
```
11. Pedido va a producción
    ├─ Estado: EN_PRODUCCION
    └─ Procesos comienzan
```

---

## 📊 ESTRUCTURA DE AUDITORÍA

Cada cambio de estado registra:
```json
{
  "cotizacion_id": 123,
  "estado_anterior": "BORRADOR",
  "estado_nuevo": "ENVIADA_CONTADOR",
  "usuario_id": 45,
  "usuario_nombre": "Juan Asesor",
  "rol_usuario": "asesor",
  "razon_cambio": "Cotización enviada a contador para revisión",
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "datos_adicionales": {
    "numero_cotizacion": 12345,
    "cliente": "Mi Cliente SA"
  },
  "created_at": "2025-12-04 10:30:45"
}
```

---

## 🚀 CARACTERÍSTICAS CLAVE

### ✅ Transiciones Validadas
- Cada cambio de estado valida la transición
- Se previenen cambios de estado inválidos
- Los Enums definen las transiciones permitidas

### ✅ Asignación Automática de Números
- Números se asignan VÍA COLAS (no en request directo)
- Evita race conditions con múltiples asesorAs
- Autoincrement: MAX(numero) + 1

### ✅ Auditoría Completa
- Quién hizo el cambio
- Cuándo se hizo
- Desde qué IP
- Razón del cambio
- Datos contextuales

### ✅ Manejo de Colas
- 3 intentos de reintento
- Backoff: [10s, 30s, 60s]
- Logging detallado
- Timeout: 60 segundos

### ✅ Validaciones de Permisos
- Controllers validan autorización
- Gates para cada rol (TODO: Implementar)
- Solo la asesor dueña puede ver su cotización

### ✅ APIs JSON
- Todos los endpoints retornan JSON
- Incluyenmensajes de error claros
- Datos estructurados para frontend

---

## 📝 TODO: PRÓXIMOS PASOS

### 1. Implementar Gates/Policies
```php
// En app/Providers/AuthServiceProvider.php
Gate::define('isContador', function (User $user) {
    return $user->hasRole('contador');
});

Gate::define('isAprobadorCotizaciones', function (User $user) {
    return $user->hasRole('aprobador_cotizaciones');
});

Gate::define('isSupervisorPedidos', function (User $user) {
    return $user->hasRole('supervisor_pedidos');
});
```

### 2. Implementar Notificaciones
- `CotizacionEnviadaAContadorNotification.php`
- `CotizacionListaParaAprobacionNotification.php`
- `PedidoListoParaAprobacionNotification.php`
- Configurar rutas: email, database, SMS

### 3. Crear Vistas/Componentes
- Botones de acción (Enviar, Aprobar)
- Modal de historial
- Panel de seguimiento
- Indicadores visuales de estado

### 4. Pruebas
- Unit tests para Servicios
- Feature tests para Controllers
- Integration tests para Colas
- Seeders para datos de prueba

### 5. Documentación
- API documentation (Swagger/OpenAPI)
- Guía de uso por rol
- Diagrama de flujo UML

---

## 🔧 CONFIGURACIÓN NECESARIA

### 1. Variables de Entorno (.env)
```env
QUEUE_CONNECTION=database
QUEUE_FAILED_TABLE=failed_jobs
```

### 2. Ejecutar Migraciones
```bash
php artisan migrate
```

### 3. Correr Cola
```bash
php artisan queue:work
# O en background:
php artisan queue:work --daemon
```

### 4. Workers en Producción
Recomendado usar Supervisor para monitorear el worker

---

## 📞 ENDPOINTS RESUMEN

### Cotizaciones
| Método | Endpoint | Descripción | Rol |
|--------|----------|-------------|-----|
| POST | `/cotizaciones/{id}/enviar` | Enviar a contador | Asesor |
| POST | `/cotizaciones/{id}/aprobar-contador` | Aprobar como contador | Contador |
| POST | `/cotizaciones/{id}/aprobar-aprobador` | Aprobar como aprobador | Aprobador |
| GET | `/cotizaciones/{id}/historial` | Ver historial | Todos |
| GET | `/cotizaciones/{id}/seguimiento` | Ver seguimiento | Asesor+ |

### Pedidos
| Método | Endpoint | Descripción | Rol |
|--------|----------|-------------|-----|
| POST | `/pedidos/{id}/aprobar-supervisor` | Aprobar como supervisor | Supervisor |
| GET | `/pedidos/{id}/historial` | Ver historial | Todos |
| GET | `/pedidos/{id}/seguimiento` | Ver seguimiento | Asesor+ |

---

## 🎯 NOTAS IMPORTANTES

1. **Números únicos**: `numero_cotizacion` y `numero_pedido` son UNIQUE para evitar duplicados
2. **Sin número en borrador**: Las cotizaciones en BORRADOR no tienen `numero_cotizacion`
3. **Asignación en cola**: Los números se asignan VÍA JOB para manejar concurrencia
4. **Historial inmutable**: Una vez creado, el historial no se puede modificar (solo lectura)
5. **Estados finales**: FINALIZADO y FINALIZADA son estados finales sin transiciones salientes

---

## ✨ PRÓXIMA TAREA

La próxima tarea será crear los componentes Blade para mostrar:
1. Botones de acción contextuales
2. Modal de historial
3. Panel de seguimiento
4. Indicadores visuales del estado actual
5. Integración con el buscador para cotizaciones aprobadas

¿Deseas proceder con la creación de vistas y componentes?
