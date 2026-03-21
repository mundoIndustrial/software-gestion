# 🎯 Plan de Refactorización DDD - RegistroOrdenController

**Archivo**: `app/Infrastructure/Http/Controllers/Pedidos/RegistroOrdenController.php`  
**Líneas Actuales**: 2254 líneas  
**Complejidad**: 🔴 CRÍTICA  
**Prioridad**: ⭐ MÁXIMA  

---

## 📊 Análisis Actual

### Responsabilidades Identificadas

#### 1. **Gestión de Órdenes** (CORE BUSINESS)
```php
- getNextPedido()              // Obtener próximo número
- validatePedido()             // Validar número
- store()                      // Crear nueva orden
- update()                     // Actualizar orden
- destroy()                    // Eliminar orden
- updatePedido()               // Actualizar número de pedido
```
**Impacto**: 🔴 CRÍTICO - Core del negocio

#### 2. **Búsqueda y Filtrado** (QUERY PATTERN)
```php
- searchOrders()               // Búsqueda en tiempo real
- filterOrders()               // Filtrado avanzado
- getColumnFilterOptions()     // Opciones de columna
- getFilterOptions()           // Opciones globales
```
**Impacto**: 🟠 ALTO - Consultas complejas

#### 3. **Prendas** (COMPOSICIÓN)
```php
- getRegistrosPorOrden()       // Obtener prendas de orden
- editFullOrder()              // Editar orden + prendas
- updateDescripcionPrendas()   // Actualizar descripción
- generarDescripcionPrenda()   // Generar descripción detallada
```
**Impacto**: 🟠 ALTO - Lógica de composición

#### 4. **Novedades** (DOMAIN EVENTS)
```php
- updateNovedades()            // Reemplazar novedades
- addNovedad()                 // Agregar novedad
```
**Impacto**: 🟡 MEDIO - Comentarios de auditoría

#### 5. **Entregas** (QUERY)
```php
- getEntregas()                // Obtener entregas
```
**Impacto**: 🟡 MEDIO - Consulta simple

#### 6. **Recibos** (BUSINESS LOGIC)
```php
- recibosCostura()             // Recibos de costura con filtros
- recibosReflectivo()          // Recibos de reflectivo
- getReciboJson()              // JSON de recibo
- getReciboReflectivoJson()    // JSON de reflectivo
- aplicarFiltros()             // Helper de filtros
```
**Impacto**: 🔴 CRÍTICO - Lógica compleja de recibos

#### 7. **Procesos & Área** (WORKFLOW)
```php
- obtenerAreaProcesoMasReciente()    // Obtener área del proceso
- getAreaReciente()                  // API de área reciente
- contarRecibosEjecutandoCostura()  // Conteo en ejecución
- marcarReciboVistoCostura()        // Marcar como visto
```
**Impacto**: 🟠 ALTO - Gestión de flujo

#### 8. **Cálculos** (BUSINESS RULES)
```php
- saveDiaEntrega()                   // Guardar día + calcular fecha
- calcularFechaEstimadaConDiasHabiles() // Cálculo de fecha
- invalidarCacheDias()               // Invalidar cache
```
**Impacto**: 🟠 ALTO - Reglas de negocio

#### 9. **Varias** (METADATA)
```php
- show()                       // Obtener detalles de orden
```
**Impacto**: 🟡 MEDIO - Consulta simple

---

## 🏗️ Propuesta DDD

### Estructura de Directorios

```
app/
├─ Domain/
│  ├─ Pedidos/
│  │  ├─ Entities/
│  │  │  ├─ Orden.php                           # Entity raíz agregado
│  │  │  └─ Prenda.php                          # Entity dependiente
│  │  ├─ ValueObjects/
│  │  │  ├─ NumeroPedido.php                    # ValueObject para número
│  │  │  ├─ DiasEntrega.php                     # ValueObject para días
│  │  │  ├─ FechaEstimada.php                   # ValueObject para fecha
│  │  │  └─ Novedad.php                         # ValueObject para novedades
│  │  ├─ Repositories/
│  │  │  └─ OrdenRepositoryInterface.php         # Interface repositorio
│  │  ├─ Services/
│  │  │  ├─ CalculoDiasEntregaService.php       # Cálculos de días
│  │  │  ├─ GeneradorDescripcionService.php     # Generación de descripciones
│  │  │  └─ ValidadorNumeroPedidoService.php    # Validación números
│  │  └─ Events/
│  │     ├─ OrdenCreada.php
│  │     ├─ OrdenActualizada.php
│  │     └─ NovedadAgregada.php
│  │
│  └─ Recibos/
│     ├─ Entities/
│     │  ├─ ReciboCostura.php
│     │  └─ ReciboReflectivo.php
│     ├─ ValueObjects/
│     │  ├─ EstadoRecibo.php
│     │  └─ NumeroRecibo.php
│     ├─ Services/
│     │  └─ FiltroReciboService.php
│     └─ Repositories/
│        └─ ReciboRepositoryInterface.php
│
├─ Application/
│  └─ UseCases/
│     ├─ Pedidos/
│     │  ├─ CrearOrdenUseCase.php                # Crear orden
│     │  ├─ ActualizarOrdenUseCase.php           # Actualizar orden
│     │  ├─ EliminarOrdenUseCase.php             # Eliminar orden
│     │  ├─ ObtenerDetallesOrdenUseCase.php      # Obtener detalles
│     │  ├─ BuscarOrdenesUseCase.php             # Búsqueda avanzada
│     │  ├─ ActualizarPrendasUseCase.php         # Actualizar prendas
│     │  ├─ ActualizarNovedadesUseCase.php       # Agregar novedad
│     │  ├─ GuardarDiaEntregaUseCase.php         # Guardar día entrega
│     │  └─ DTOs/ (Input/Output)
│     │
│     └─ Recibos/
│        ├─ ObtenerRecibosCoStratergiaUseCase.php
│        ├─ ObtenerRecibosReflectivoUseCase.php
│        ├─ FiltrarRecibosUseCase.php
│        └─ DTOs/
│
└─ Infrastructure/
   ├─ Persistence/
   │  └─ Eloquent/
   │     ├─ OrdenEloquentRepository.php          # Implementación Ordem
   │     ├─ ReciboEloquentRepository.php         # Implementación Recibo
   │     └─ Models/
   │        ├─ PedidoProduccion.php
   │        └─ ...
   │
   └─ Http/
      ├─ Controllers/
      │  └─ Pedidos/
      │     ├─ RegistroOrdenController.php       # Controllers REFACTORIZADO
      │     └─ RegistroReciboController.php      # Nuevo controller Recibos
      │
      └─ Requests/
         ├─ CrearOrdenRequest.php
         ├─ ActualizarOrdenRequest.php
         └─ ...
```

---

## 🚀 Fases de Refactorización

### **FASE 1: Casos de Uso Core - Gestión de Órdenes**
**Objetivo**: Extraer lógica de CRUD de órdenes  
**Duración Estimada**: 2-3 horas

#### Archivos a Crear:
1. `CrearOrdenUseCase.php` (280 líneas)
2. `ActualizarOrdenUseCase.php` (250 líneas)
3. `EliminarOrdenUseCase.php` (120 líneas)
4. `ObtenerDetallesOrdenUseCase.php` (150 líneas)
5. DTOs: `CrearOrdenInput.php`, `CrearOrdenOutput.php`, etc.

#### Métodos Extraídos:
- `store()` → CrearOrdenUseCase
- `update()` → ActualizarOrdenUseCase
- `destroy()` → EliminarOrdenUseCase
- `show()` → ObtenerDetallesOrdenUseCase
- `updatePedido()` → ActualizarNumeroPedidoUseCase

#### Servicios Necesarios:
- `RegistroOrdenValidationService` (ya existe)
- `RegistroOrdenCreationService` (ya existe)
- `RegistroOrdenUpdateService` (ya existe)
- `RegistroOrdenDeletionService` (ya existe)

---

### **FASE 2: Búsqueda y Filtrado**
**Objetivo**: Extraer lógica de query/búsqueda  
**Duración Estimada**: 2 horas

#### Archivos a Crear:
1. `BuscarOrdenesUseCase.php` (200 líneas)
2. `ObtenerOpcionesColumnaUseCase.php` (100 líneas)
3. `app/Domain/Pedidos/Services/FiltroOrdenService.php` (300 líneas)

#### Métodos Extraídos:
- `searchOrders()` → BuscarOrdenesUseCase
- `filterOrders()` → FiltroOrdenService
- `getColumnFilterOptions()` → ObtenerOpcionesColumnaUseCase
- `getFilterOptions()` → ObtenerOpcionesColumnaUseCase
- `aplicarFiltros()` → FiltroOrdenService

---

### **FASE 3: Gestión de Prendas**
**Objetivo**: Extraer lógica de composición de prendas  
**Duración Estimada**: 2.5 horas

#### Archivos a Crear:
1. `ActualizarPrendasUseCase.php` (200 líneas)
2. `ActualizarDescripcionUseCase.php` (150 líneas)
3. ValueObjects: `Prenda.php`, `DescripcionPrenda.php`
4. `GeneradorDescripcionService.php` (refactorizar método actual)

#### Métodos Extraídos:
- `getRegistrosPorOrden()` → ActualizarPrendasUseCase
- `editFullOrder()` → ActualizarPrendasUseCase
- `updateDescripcionPrendas()` → ActualizarDescripcionUseCase
- `generarDescripcionPrenda()` → GeneradorDescripcionService

---

### **FASE 4: Novedades y Auditoría**
**Objetivo**: Extraer gestión de novedades como Domain Event  
**Duración Estimada**: 1.5 horas

#### Archivos a Crear:
1. `ActualizarNovedadesUseCase.php` (180 líneas)
2. `AgregarNovedadUseCase.php` (180 líneas)
3. ValueObject: `Novedad.php`
4. Event: `NovedadAgreada.php`

#### Métodos Extraídos:
- `updateNovedades()` → ActualizarNovedadesUseCase
- `addNovedad()` → AgregarNovedadUseCase

---

### **FASE 5: Cálculos y Entregas**
**Objetivo**: Extraer lógica de dominio de cálculos  
**Duración Estimada**: 2 horas

#### Archivos a Crear:
1. `GuardarDiaEntregaUseCase.php` (150 líneas)
2. `ObtenerEntregasUseCase.php` (100 líneas)
3. DomainService: `CalculoDiasEntregaService.php` (refactorizar método actual)
4. ValueObjects: `DiasEntrega.php`, `FechaEstimada.php`

#### Métodos Extraídos:
- `saveDiaEntrega()` → GuardarDiaEntregaUseCase
- `calcularFechaEstimadaConDiasHabiles()` → CalculoDiasEntregaService
- `getEntregas()` → ObtenerEntregasUseCase

---

### **FASE 6: Recibos - Query Model**
**Objetivo**: Extraer lógica compleja de recibos  
**Duración Estimada**: 3 horas

#### Archivos a Crear:
1. `ObtenerRecibosCoStratergiaUseCase.php` (250 líneas)
2. `ObtenerRecibosReflectivoUseCase.php` (200 líneas)
3. `FiltrarRecibosUseCase.php` (150 líneas)
4. DomainService: `FiltroReciboService.php` (refactorizar método actual)
5. ValueObjects: `EstadoRecibo.php`, `NumeroRecibo.php`

#### Métodos Extraídos:
- `recibosCostura()` → ObtenerRecibosCoStratergiaUseCase
- `recibosReflectivo()` → ObtenerRecibosReflectivoUseCase
- `getReciboJson()` → ObtenerRecibosCoStratergiaUseCase
- `getReciboReflectivoJson()` → ObtenerRecibosReflectivoUseCase
- `aplicarFiltros()` → FiltroReciboService

---

### **FASE 7: Procesos y Workflow**
**Objetivo**: Extraer lógica de procesos  
**Duración Estimada**: 1.5 horas

#### Archivos a Crear:
1. `ObtenerAreaProcesoUseCase.php` (150 líneas)
2. `ContarRecibosEnEjecucionUseCase.php` (120 líneas)
3. `MarcarReciboVistoUseCase.php` (100 líneas)
4. DomainService: `GestorProcesoService.php` (refactorizar métodos actuales)

#### Métodos Extraídos:
- `obtenerAreaProcesoMasReciente()` → GestorProcesoService
- `getAreaReciente()` → ObtenerAreaProcesoUseCase
- `contarRecibosEjecutandoCostura()` → ContarRecibosEnEjecucionUseCase
- `marcarReciboVistoCostura()` → MarcarReciboVistoUseCase

---

### **FASE 8: Refactorización Final del Controller**
**Objetivo**: Dejar controller minimal (< 500 líneas)  
**Duración Estimada**: 2 horas

#### Responsabilidad Final del Controller:
```php
✅ Recibir HTTP Request
✅ Validar Request
✅ Invocar UseCase correcta
✅ Retornar Response HTTP
❌ Lógica de negocio
❌ Persistencia
❌ Cálculos
```

#### Estructura Esperada:
```php
class RegistroOrdenController extends Controller {
    // Inyecciones de UseCases
    
    public function store(Request $request) {
        $input = CrearOrdenInput::fromRequest($request);
        $output = $this->crearOrdenUseCase->execute($input);
        return response()->json($output->toResponse());
    }
    
    // Similar para otros métodos...
}
```

---

## ✅ Checklist de Validación

- [ ] Sintaxis PHP válida en todos los archivos
- [ ] Todas las inyecciones de dependencia funcionan
- [ ] Tests unitarios para UseCases
- [ ] Tests de integración para flujos CRUD
- [ ] 0 regresiones en endpoints
- [ ] Logging en todos los casos críticos
- [ ] Documentación de API actualizada
- [ ] Broadcasting de eventos funcionando
- [ ] Cache invalidado correctamente

---

## 📈 Métricas de Éxito

| Métrica | Antes | Después | ✅ |
|---------|-------|---------|-----|
| Líneas Controller | 2254 | ~300 | ✅ |
| # Responsabilidades | 9 | 1 | ✅ |
| # UseCase Classes | 0 | 12+ | ✅ |
| Branch Complexity | 🔴 ALTA | 🟢 BAJA | ✅ |
| Testabilidad | 🟠 MEDIA | 🟢 ALTA | ✅ |
| Reusabilidad de Lógica | 🔴 BAJA | 🟢 ALTA | ✅ |

---

## 🔗 Referencias

- [x] Arquitectura similar en CrearPedidoEditableController (FASE_6_REFACTORING_COMPLETE.md)
- [x] Patrón DDD de Insumos/Demoras (VALIDACION_ARQUITECTURA_DDD.md)
- [x] Patrón de FiltraloService en otros módulos

