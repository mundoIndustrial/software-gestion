# 📊 ANÁLISIS COMPLETO: DEUDA TÉCNICA EN AsesoresController

**Fecha**: 22 de Enero de 2026  
**Estado del Proyecto**: Refactor DDD Fase 2 - AsesoresController  
**Criticidad**: ⚠️ ALTA - Hay duplicación y servicios legacy mezclados

---

## 🎯 TABLA DE CONTENIDOS

1. [DUPLICACIÓN DE AGREGADOS](#1-duplicación-de-agregados)
2. [SERVICIOS LEGACY: ANÁLISIS DE USO](#2-servicios-legacy-análisis-de-uso)
3. [MÉTODOS POR REFACTORIZAR](#3-métodos-por-refactorizar)
4. [VALIDACIÓN DE REPOSITORIO](#4-validación-de-repositorio)
5. [SERVICE PROVIDERS ANALYSIS](#5-service-providers-analysis)
6. [PLAN DE ACCIÓN](#6-plan-de-acción)

---

## 1. DUPLICACIÓN DE AGREGADOS

### ❌ PROBLEMA IDENTIFICADO

Existen **DOS** implementaciones del mismo agregado:

| Ubicación | Líneas | Namespace | Estado |
|-----------|--------|-----------|--------|
| `app/Domain/PedidoProduccion/Agregado/PedidoProduccionAggregate.php` | 359 | `App\Domain\PedidoProduccion\Agregado` | ⚠️ **LEGACY** |
| `app/Domain/PedidoProduccion/Aggregates/PedidoProduccionAggregate.php` | 212 | `App\Domain\PedidoProduccion\Aggregates` | ✅ **NUEVO DDD** |

### 📋 COMPARATIVA DETALLADA

#### **Agregado Legacy (Agregado/)**
```
Características:
- Factory methods: crear(), restaurarDesdeBD()
- Estados: PENDIENTE, CONFIRMADO, EN_PRODUCCION, COMPLETADO, ANULADO
- Operaciones básicas:
  ✓ confirmar()
  ✓ marcarEnProduccion()
  ✓ marcarCompletado()
  ✓ anular(razon)
  ✓ agregarPrenda()
  ✓ eliminarPrenda()
- Getters: Sí, completos
- Eventos de dominio: NO
- Validadores: Sí, privados
```

#### **Agregado DDD (Aggregates/)**
```
Características:
- Factory method: crear()
- Estados: PENDIENTE_SUPERVISOR, EN_PROCESO, COMPLETADO, CANCELADO
- Operaciones: 
  ✓ agregarCantidad()
  ✓ cambiarEstado()
- Event Sourcing: ✅ SÍ
  - recordEvent()
  - getUncommittedEvents()
  - markEventsAsCommitted()
- Getters: Sí
- Validadores: Mínimos
```

### 🎯 RECOMENDACIÓN: ELIMINAR `Agregado/PedidoProduccionAggregate.php`

**Justificación:**

1. **El agregado DDD es superior**: Implementa Event Sourcing, que es la versión correcta según DDD
2. **Estados más realistas**: PENDIENTE_SUPERVISOR es más específico que PENDIENTE
3. **Separación de carpetas**: `Aggregates/` sigue convención estándar
4. **El legacy no se usa**: Ningún Use Case lo importa
5. **Conflicto de namespace**: Ambos tienen el mismo nombre, puede causar confusión

**Acciones:**
```bash
✓ Eliminar: app/Domain/PedidoProduccion/Agregado/
✓ Mantener: app/Domain/PedidoProduccion/Aggregates/
✓ Verificar imports en: DomainServiceProvider, todos los Use Cases
```

---

## 2. SERVICIOS LEGACY: ANÁLISIS DE USO

### 📊 MATRIZ DE SERVICIOS IMPORTADOS EN AsesoresController

```php
use App\Application\Services\Asesores\DashboardService;
use App\Application\Services\Asesores\NotificacionesService;
use App\Application\Services\Asesores\PerfilService;
use App\Application\Services\Asesores\EliminarPedidoService;
use App\Application\Services\Asesores\ObtenerFotosService;
use App\Application\Services\Asesores\AnularPedidoService;
use App\Application\Services\Asesores\ObtenerPedidosService;
use App\Application\Services\Asesores\ObtenerProximoPedidoService;
use App\Application\Services\Asesores\ObtenerDatosFacturaService;
use App\Application\Services\Asesores\ObtenerDatosRecibosService;
use App\Application\Services\Asesores\ProcesarFotosTelasService;
use App\Application\Services\Asesores\GuardarPedidoLogoService;
use App\Application\Services\Asesores\GuardarPedidoProduccionService;
use App\Application\Services\Asesores\ConfirmarPedidoService;
use App\Application\Services\Asesores\ActualizarPedidoService;
use App\Application\Services\Asesores\ObtenerPedidoDetalleService;
```

### ✅ SERVICIOS REALMENTE USADOS (Con análisis de método)

#### **1. DashboardService** ✅ EN USO
```
Usado en:
  - dashboard() → llamada directa
  - getDashboardData() → llamada directa

Análisis: ✅ NO REFACTORIZADO
  Métodos no refactorizados a Use Case (NO existen en Application/Pedidos/)
  
Disposición: MANTENER (por ahora)
  Podría moverse a UseCase si se necesita reutilización
```

#### **2. NotificacionesService** ✅ EN USO
```
Usado en:
  - getNotificaciones() → $this->notificacionesService->obtenerNotificaciones()
  - getNotifications() → alias directo
  - markAllAsRead() → $this->notificacionesService->marcarTodosLeidosPedidos()
  - markNotificationAsRead() → $this->notificacionesService->marcarNotificacionLeida()

Análisis: ✅ NO REFACTORIZADO
  Notificaciones no tienen Use Case equivalente
  
Disposición: MANTENER
  Es funcionalidad específica de gestión de notificaciones
```

#### **3. PerfilService** ✅ EN USO
```
Usado en:
  - updateProfile() → $this->perfilService->actualizarPerfil()

Análisis: ✅ NO REFACTORIZADO
  Gestión de perfil de usuario, no de pedidos
  
Disposición: MANTENER
  Es concern separado de gestión de pedidos
```

#### **4. ObtenerProximoPedidoService** ✅ EN USO
```
Usado en:
  - getNextPedido() → $this->obtenerProximoPedidoService->obtenerProximo()

Análisis: ✅ NO REFACTORIZADO
  Genera siguiente número de pedido automáticamente
  
Disposición: ⚠️ REFACTORIZAR
  Debería convertirse a Use Case: ObtenerSiguientePedidoNumberUseCase
  O integrar en PrepararCreacionProduccionPedidoUseCase
```

#### **5. AnularPedidoService** ❌ CONFLICTO
```
Usado en:
  - anularPedido() → $this->anularPedidoService->anular()

Análisis: ⚠️ DUPLICADO
  Use Case EXISTE: AnularProduccionPedidoUseCase
  El servicio legacy actualiza:
    - estado → 'Anulada'
    - novedades → concatena con timestamp
  El Use Case debería hacer lo mismo
  
Disposición: ❌ ELIMINAR
  Usar: AnularProduccionPedidoUseCase (ya refactorizado)
  Método destroy() YA usa el Use Case correcto
```

#### **6. ObtenerFotosService** ❌ VERIFICAR
```
Usado en:
  - ??? (grep no encontró uso directo en métodos)

Análisis: 🤔 NO USADO EN MÉTODOS MOSTRADOS
  
Disposición: ❌ ELIMINAR (aparentemente no se usa)
```

#### **7. ObtenerPedidosService** ❌ VERIFICAR
```
Usado en:
  - ??? (grep no encontró uso directo)

Análisis: 🤔 NO USADO EN MÉTODOS MOSTRADOS
  index() usa: ListarProduccionPedidosUseCase (correcto)
  
Disposición: ❌ ELIMINAR (aparentemente no se usa)
```

#### **8. ObtenerDatosFacturaService** ✅ EN USO
```
Usado en:
  - obtenerDatosFactura() → $this->obtenerDatosFacturaService->obtener()

Análisis: ⚠️ DEBERÍA DELEGARSE AL REPOSITORIO
  La lógica está en: PedidoProduccionRepository::obtenerDatosFactura()
  El servicio es solo wrapper que llama al repositorio
  
Disposición: ⚠️ REFACTORIZAR
  Llamar directamente al repositorio desde el controlador
  O crear Use Case: ObtenerDatosFacturaUseCase
```

#### **9. ObtenerDatosRecibosService** ✅ EN USO
```
Usado en:
  - obtenerDatosRecibos() → $this->obtenerDatosRecibosService->obtener()

Análisis: ⚠️ DEBERÍA DELEGARSE AL REPOSITORIO
  La lógica está en: PedidoProduccionRepository::obtenerDatosRecibos()
  El servicio es solo wrapper
  
Disposición: ⚠️ REFACTORIZAR
  Llamar directamente al repositorio
  O crear Use Case: ObtenerDatosRecibosUseCase
```

#### **10. ProcesarFotosTelasService** ✅ EN USO
```
Usado en:
  - store() → $this->procesarFotosTelasService->procesar()
  - store() → $this->procesarFotosTelasService->procesarImagenesLogo()

Análisis: ⚠️ FUNCIONALIDAD DE PROCESAMIENTO
  No es lógica de negocio de pedidos, es procesamiento técnico
  
Disposición: MANTENER
  Refactorizar más adelante si es necesario
  Podría moverse a Infrastructure/Services
```

#### **11. GuardarPedidoLogoService** ✅ EN USO
```
Usado en:
  - store() → $this->guardarPedidoLogoService->guardar()
  - store() → $this->guardarPedidoLogoService->esLogoPedido()

Análisis: ⚠️ LÓGICA DE NEGOCIO DE LOGOS
  Mezcla de concernos: procesamiento + persistencia
  
Disposición: ⚠️ REFACTORIZAR
  Crear Use Case: GuardarPedidoLogoUseCase
  Integrar en CrearProduccionPedidoUseCase para logos
```

#### **12. GuardarPedidoProduccionService** ❌ NO USADO
```
Usado en:
  - ??? (NO ENCONTRADO en métodos analizados)

Análisis: 🤔 NO USADO
  store() ya usa: CrearProduccionPedidoUseCase
  
Disposición: ❌ ELIMINAR
```

#### **13. ConfirmarPedidoService** ❌ DUPLICADO
```
Usado en:
  - ??? (NO ENCONTRADO en método confirm())

Análisis: ⚠️ NO USADO
  confirm() ya usa: ConfirmarProduccionPedidoUseCase
  
Disposición: ❌ ELIMINAR
```

#### **14. ActualizarPedidoService** ❌ DUPLICADO
```
Usado en:
  - ??? (NO ENCONTRADO en método update())

Análisis: ⚠️ NO USADO
  update() ya usa: ActualizarProduccionPedidoUseCase
  
Disposición: ❌ ELIMINAR
```

#### **15. ObtenerPedidoDetalleService** ❌ NO USADO
```
Usado en:
  - ??? (NO ENCONTRADO)

Análisis: 🤔 NO USADO
  show() y edit() ya usan: ObtenerProduccionPedidoUseCase
  
Disposición: ❌ ELIMINAR
```

#### **16. EliminarPedidoService** ❌ NO USADO
```
Usado en:
  - ??? (NO ENCONTRADO)

Análisis: 🤔 NO USADO
  
Disposición: ❌ ELIMINAR
```

---

## 3. MÉTODOS POR REFACTORIZAR

### 📋 MÉTODOS NO REFACTORIZADOS

| Método | Estado | Prioridad | Acción |
|--------|--------|-----------|--------|
| `dashboard()` | ❌ Legacy | 🟡 Media | Crear DashboardUseCase |
| `getDashboardData()` | ❌ Legacy | 🟡 Media | Crear DashboardUseCase |
| `getNotificaciones()` | ❌ Legacy | 🟡 Media | Crear NotificacionesUseCase |
| `markAllAsRead()` | ❌ Legacy | 🟡 Media | Integrar en NotificacionesUseCase |
| `markNotificationAsRead()` | ❌ Legacy | 🟡 Media | Integrar en NotificacionesUseCase |
| `updateProfile()` | ❌ Legacy | 🟢 Baja | Crear PerfilUseCase (separado) |
| `anularPedido()` | ❌ Legacy | 🔴 Alta | Usar AnularProduccionPedidoUseCase |
| `inventarioTelas()` | ⚠️ Delegado | 🟢 Baja | Revisar AsesoresInventarioTelasController |
| `obtenerDatosFactura()` | ❌ Legacy | 🔴 Alta | Crear ObtenerDatosFacturaUseCase |
| `obtenerDatosRecibos()` | ❌ Legacy | 🔴 Alta | Crear ObtenerDatosRecibosUseCase |
| `agregarPrendaSimple()` | ❌ Legacy | 🟡 Media | Usar AgregarItemPedidoUseCase |

### 🔍 MÉTODOS YA REFACTORIZADOS ✅

```
✅ index() - Usa ListarProduccionPedidosUseCase
✅ create() - Usa PrepararCreacionProduccionPedidoUseCase
✅ store() - Usa CrearProduccionPedidoUseCase
✅ confirm() - Usa ConfirmarProduccionPedidoUseCase
✅ show() - Usa ObtenerProduccionPedidoUseCase
✅ edit() - Usa ObtenerProduccionPedidoUseCase
✅ update() - Usa ActualizarProduccionPedidoUseCase
✅ destroy() - Usa AnularProduccionPedidoUseCase
```

---

## 4. VALIDACIÓN DE REPOSITORIO

### ✅ PedidoProduccionRepository ANÁLISIS

**Ubicación**: `app/Domain/PedidoProduccion/Repositories/PedidoProduccionRepository.php`  
**Líneas**: 898  
**Estado**: ✅ COMPLETO Y FUNCIONAL

#### **Métodos Implementados:**

| Método | Retorno | Relaciones | Estado |
|--------|---------|-----------|--------|
| `obtenerPorId(int)` | `?PedidoProduccion` | ✅ Completas (11) | ✅ OK |
| `obtenerPedidosAsesor(array)` | `LengthAwarePaginator` | ✅ Básicas | ✅ OK |
| `perteneceAlAsesor(int, int)` | `bool` | N/A | ✅ OK |
| `actualizarCantidadTotal(string)` | `void` | N/A | ✅ OK |
| `obtenerDatosFactura(int)` | `array` | ✅ Complejas | ✅ OK |
| `obtenerDatosRecibos(int)` | `array` | ✅ Complejas | ✅ OK |

#### **Relaciones Cargadas en obtenerPorId():**

```php
'cotizacion.cliente',
'cotizacion.tipoCotizacion',
'prendas.variantes.tipoManga',
'prendas.variantes.tipoBroche',
'prendas.fotos',
'prendas.fotosTelas',
'prendas.tallas',          // ✅ NUEVA - Tallas relacionales
'prendas.procesos',
'prendas.procesos.tipoProceso',  // ✅ NUEVA
'prendas.procesos.imagenes',
'epps.epp.categoria',      // ✅ NUEVA - EPP
'epps.imagenes',           // ✅ NUEVA
```

#### **Tablas Soportadas:**

| Tabla | Status |
|-------|--------|
| `pedidos_produccion` | ✅ Principal |
| `prendas_pedido` | ✅ Completa |
| `prenda_pedido_tallas` | ✅ Soportada |
| `prenda_pedido_variantes` | ✅ Soportada |
| `prenda_pedido_colores_telas` | ✅ Soportada |
| `prenda_fotos_pedido` | ✅ Soportada |
| `prenda_fotos_tela_pedido` | ✅ Soportada |
| `pedidos_procesos_prenda_detalles` | ✅ Soportada |
| `pedidos_procesos_prenda_tallas` | ✅ Soportada |
| `pedidos_procesos_imagenes` | ✅ Soportada |
| `pedido_epp` | ✅ Soportada |
| `pedido_epp_imagenes` | ✅ Soportada |

#### **QUÉ FALTA:**

```
❌ Métodos faltantes:
  - obtenerTodos()           → Fácil de agregar
  - guardar()                → Fácil de agregar
  - actualizar()             → Fácil de agregar
  - eliminar()               → Fácil de agregar
  - obtenerPorNumero()       → Importante
  - obtenerPorEstado()       → Importante
```

#### **Método obtenerTallas() Pendiente:**

El repositorio usa `obtenerTallas()` pero está en un TRAIT:

```php
use GestionaTallasRelacional;  // ✅ Trait con obtenerTallas()
```

✅ **ESTÁ PRESENTE EN EL TRAIT**

---

## 5. SERVICE PROVIDERS ANALYSIS

### 📊 Providers Registrados

#### **1. DomainServiceProvider** ✅
**Ubicación**: `app/Providers/DomainServiceProvider.php`

**Registra**:
- ✅ PedidoRepository → PedidoRepositoryImpl
- ✅ CrearPedidoUseCase
- ✅ ConfirmarPedidoUseCase
- ✅ ObtenerPedidoUseCase
- ✅ ListarPedidosPorClienteUseCase
- ✅ CancelarPedidoUseCase
- ✅ ActualizarDescripcionPedidoUseCase
- ✅ IniciarProduccionPedidoUseCase
- ✅ CompletarPedidoUseCase
- ✅ AgregarItemPedidoUseCase
- ✅ EliminarItemPedidoUseCase
- ✅ ObtenerItemsPedidoUseCase
- ✅ GuardarPedidoDesdeJSONUseCase
- ✅ ValidarPedidoDesdeJSONUseCase

#### **2. PedidosServiceProvider** ✅
**Ubicación**: `app/Providers/PedidosServiceProvider.php`

**Registra**:
- ✅ PrendaProcessorService
- ✅ PedidoProduccionCreatorService
- ✅ PedidoPrendaService
- ✅ PedidoLogoService
- ✅ CopiarImagenesCotizacionAPedidoService
- ✅ ColorGeneroMangaBrocheService

#### **3. AppServiceProvider** ✅
**Ubicación**: `app/Providers/AppServiceProvider.php`

**Registra**:
- ✅ OperarioRepository
- ✅ TipoProcesoRepository
- ✅ ProcesoPrendaDetalleRepository
- ✅ ProcesoPrendaImagenRepository
- ✅ EppRepositoryInterface
- ✅ PedidoEppRepositoryInterface
- ✅ EppDomainService
- ✅ GenerarNumeroCotizacionService
- ✅ Image manager (Intervention)

#### **4. Infrastructure/Providers** ⚠️
**FALTA Service Provider específico para servicios legacy de Asesores**

```
❌ NO EXISTE: AsesoresServiceProvider
   para registrar:
   - DashboardService
   - NotificacionesService
   - PerfilService
   - ObtenerProximoPedidoService
   - AnularPedidoService
   - ObtenerDatosFacturaService
   - ObtenerDatosRecibosService
   - etc.
```

### ⚠️ PROBLEMA IDENTIFICADO

**Las inyecciones en AsesoresController funciona porque**:
- Constructor inyecta automáticamente los servicios
- Laravel resuelve las dependencias por nombre de clase
- NO hay que registrarlos explícitamente si no tienen dependencias

**PERO esto es un problema porque**:
- 🚫 No hay visibilidad de qué servicios se necesitan
- 🚫 Dificulta pruebas unitarias
- 🚫 Aumenta acoplamiento tácito
- 🚫 Viola explícitness is better than implicitness

---

## 6. PLAN DE ACCIÓN

### 🎯 FASES DE REFACTORIZACIÓN

#### **FASE 1: ELIMINAR DUPLICACIÓN (URGENTE) ⏰ 1-2 horas**

```
1. ELIMINAR agregado legacy:
   ❌ app/Domain/PedidoProduccion/Agregado/
   
2. VERIFICAR imports:
   - DomainServiceProvider
   - CommandHandlers
   - Listeners
   
3. RENOMBRAR carpeta (opcional):
   app/Domain/PedidoProduccion/Aggregates/ → OK (ya está bien)
```

**Commits**:
- `[CLEANUP] Eliminar PedidoProduccionAggregate legacy (duplicado)`
- `[TEST] Verificar que todos los imports apunten a Aggregates/`

---

#### **FASE 2: ELIMINAR SERVICIOS NO USADOS (ALTA) ⏰ 1 hora**

```
❌ ELIMINAR ESTOS SERVICIOS (no se usan):
   - ObtenerFotosService
   - ObtenerPedidosService
   - GuardarPedidoProduccionService
   - ConfirmarPedidoService (en AsesoresController)
   - ActualizarPedidoService (en AsesoresController)
   - ObtenerPedidoDetalleService
   - EliminarPedidoService

ACCIÓN EN CONTROLADOR:
   - Remover imports del constructor
   - Remover properties
   - Remover inyecciones en __construct()
```

**Commits**:
- `[CLEANUP] Eliminar servicios legacy no usados (7 servicios)`
- `[REFACTOR] AsesoresController: remover dependencias muertas`

---

#### **FASE 3: REFACTORIZAR MÉTODOS CRÍTICOS (ALTA) ⏰ 4-6 horas**

```
MÉTODO: anularPedido() 
  ❌ Estado actual: Usa AnularPedidoService (legacy)
  ✅ Cambiar a: AnularProduccionPedidoUseCase (ya existe)
  Archivo: app/Infrastructure/Http/Controllers/Asesores/AsesoresController.php
  Línea: ~635

MÉTODO: obtenerDatosFactura()
  ❌ Estado actual: Usa ObtenerDatosFacturaService
  ✅ Cambiar a: Inyectar PedidoProduccionRepository directamente
  O crear: ObtenerDatosFacturaUseCase
  
MÉTODO: obtenerDatosRecibos()
  ❌ Estado actual: Usa ObtenerDatosRecibosService
  ✅ Cambiar a: Inyectar PedidoProduccionRepository directamente
  O crear: ObtenerDatosRecibosUseCase

MÉTODO: getNextPedido()
  ❌ Estado actual: Usa ObtenerProximoPedidoService
  ✅ Cambiar a: Crear ObtenerSiguientePedidoNumberUseCase
```

**Commits**:
- `[REFACTOR] anularPedido(): usar AnularProduccionPedidoUseCase`
- `[REFACTOR] obtenerDatosFactura(): usar repositorio directamente`
- `[REFACTOR] obtenerDatosRecibos(): usar repositorio directamente`
- `[REFACTOR] getNextPedido(): crear use case`

---

#### **FASE 4: REFACTORIZAR MÉTODOS SECUNDARIOS (MEDIA) ⏰ 3-4 horas**

```
MÉTODO: dashboard(), getDashboardData()
  ⚠️ Estos SÍ necesitan refactorización a Use Case
  Crear: GetDashboardDataUseCase
  
MÉTODO: getNotificaciones(), markAllAsRead(), markNotificationAsRead()
  ⚠️ Estos SÍ necesitan refactorización a Use Cases
  Crear:
    - GetNotificacionesUseCase
    - MarkAllAsReadUseCase
    - MarkNotificationAsReadUseCase
  O integrarlos en un solo: GestionarNotificacionesUseCase
  
MÉTODO: agregarPrendaSimple()
  ⚠️ Usar AgregarItemPedidoUseCase (ya existe)
  Actualmente: Crea prenda directamente en BD
```

**Commits**:
- `[REFACTOR] dashboard(): crear DashboardUseCase`
- `[REFACTOR] notificaciones: crear NotificacionesUseCases`
- `[REFACTOR] agregarPrendaSimple(): usar AgregarItemPedidoUseCase`

---

#### **FASE 5: CREAR SERVICE PROVIDER (MEDIA) ⏰ 1-2 horas**

```
CREAR: app/Infrastructure/Pedidos/Providers/AsesoresServiceProvider.php

Registrar:
  - DashboardService
  - NotificacionesService
  - PerfilService
  - ObtenerProximoPedidoService
  - ObtenerDatosFacturaService
  - ObtenerDatosRecibosService
  - ProcesarFotosTelasService
  - GuardarPedidoLogoService
  
REGISTRAR EN config/app.php:
  \App\Infrastructure\Pedidos\Providers\AsesoresServiceProvider::class
```

**Commits**:
- `[FEATURE] Crear AsesoresServiceProvider para inyección explícita`
- `[CONFIG] Registrar AsesoresServiceProvider en app.php`

---

#### **FASE 6: VALIDAR Y TESTEAR (MEDIA) ⏰ 2-3 horas**

```
VERIFICAR:
  ✓ Todos los imports están correctos
  ✓ Ningún Use Case falta registración en Providers
  ✓ Controllers funcionan con nuevas inyecciones
  ✓ Tests pasan (unit tests existen)

TESTS:
  - AsesoresControllerTest
  - UseCasesTests
  - RepositoryTests
```

**Commits**:
- `[TEST] Verificar refactorización de AsesoresController`
- `[FIX] Corregir imports después de refactorización`

---

### 📋 RESUMEN DE CAMBIOS

#### **Total de cambios necesarios:**

| Tipo | Cantidad | Esfuerzo |
|------|----------|----------|
| Servicios a eliminar | 7 | 1 hora |
| Métodos a refactorizar | 11 | 8 horas |
| Use Cases a crear | 5 | 2 horas |
| Service Providers a crear | 1 | 1 hora |
| Tests a actualizar | ~10 | 2 horas |
| **TOTAL** | **34 cambios** | **~14 horas** |

---

## 📌 CHECKLIST DE IMPLEMENTACIÓN

### Paso 1: Eliminar Duplicación
```
□ Eliminar app/Domain/PedidoProduccion/Agregado/
□ Verificar DomainServiceProvider no importa Agregado/
□ Verificar que todos los tests pasen
□ Commit: "[CLEANUP] Eliminar PedidoProduccionAggregate legacy"
```

### Paso 2: Limpiar Servicios Muertos
```
□ Remover 7 servicios no usados del constructor
□ Remover imports
□ Remover properties
□ Commit: "[CLEANUP] Remover servicios legacy no usados"
```

### Paso 3: Refactorizar Métodos Críticos
```
□ anularPedido() → AnularProduccionPedidoUseCase
□ obtenerDatosFactura() → PedidoProduccionRepository
□ obtenerDatosRecibos() → PedidoProduccionRepository
□ getNextPedido() → ObtenerSiguientePedidoUseCase
□ Commit: "[REFACTOR] Métodos críticos de AsesoresController"
```

### Paso 4: Refactorizar Métodos Secundarios
```
□ dashboard() → DashboardUseCase
□ getDashboardData() → DashboardUseCase
□ getNotificaciones() → NotificacionesUseCases
□ markAllAsRead() → NotificacionesUseCases
□ markNotificationAsRead() → NotificacionesUseCases
□ agregarPrendaSimple() → AgregarItemPedidoUseCase
□ Commit: "[REFACTOR] Métodos secundarios de AsesoresController"
```

### Paso 5: Crear Service Provider
```
□ Crear AsesoresServiceProvider
□ Registrar todos los servicios
□ Agregar a config/app.php
□ Commit: "[FEATURE] Crear AsesoresServiceProvider"
```

### Paso 6: Validar y Testear
```
□ Ejecutar tests
□ Verificar que no hay errores
□ Commit: "[TEST] Validar refactorización completa"
```

---

## 🎯 RECOMENDACIONES FINALES

### ✅ HACER

1. **Eliminar agregado legacy AHORA**
   - Es la causa directa de confusión
   - No se usa en ningún lado
   - Causa dudas sobre cuál usar

2. **Crear Service Provider explícito**
   - Mejora visibilidad de dependencias
   - Facilita testing
   - Documenta el diseño

3. **Usar repositorio directamente**
   - `obtenerDatosFactura()` y `obtenerDatosRecibos()`
   - No necesitan capas intermedias
   - El repositorio ES la abstracción

4. **Crear Use Cases para métodos no refactorizados**
   - Dashboard, Notificaciones, etc.
   - Mantiene consistencia
   - Facilita reutilización

### ❌ NO HACER

1. **Dejar servicios legacy sin refactorizar**
   - Aumenta deuda técnica
   - Dificulta mantenimiento

2. **Mantener ambos agregados**
   - Causa conflictos
   - Genera confusión

3. **Mezclar patrones (Service + UseCase)**
   - Ya se hace en métodos refactorizados
   - Debe ser consistente

---

## 📚 REFERENCIAS

- [DDD Aggregates](https://martinfowler.com/bliki/DDD_Aggregate.html)
- [CQRS Pattern](https://martinfowler.com/bliki/CQRS.html)
- [Event Sourcing](https://martinfowler.com/eaaDev/EventSourcing.html)
- [Dependency Injection Pattern](https://en.wikipedia.org/wiki/Dependency_injection)

---

**Análisis completado**: 22 de Enero de 2026  
**Próxima revisión**: Post-implementación  
**Estado general**: ⚠️ Refactor pendiente (deuda técnica media)
