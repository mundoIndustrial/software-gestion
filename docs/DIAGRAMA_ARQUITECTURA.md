# 🏗️ Diagrama de Arquitectura - Crear Pedido desde Cotización

## Flujo Completo de Datos

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                          USER INTERACTION LAYER                              │
│                   (Vista Blade + JavaScript Modules)                         │
└────────────────────┬─────────────────────────────────────────────────────────┘
                     │
                     ↓
        ┌─────────────────────────────┐
        │   PRESENTATION LAYER        │
        │                             │
        │  crear-desde-cotizacion.    │
        │  blade.php                  │
        │  + 3 componentes Blade      │
        │                             │
        │  CSS + Estilos              │
        └────────────┬────────────────┘
                     │
                     ↓
    ┌────────────────────────────────────────┐
    │   JAVASCRIPT MODULES (ES6)             │
    │                                        │
    │  ┌──────────────────────────────────┐  │
    │  │ CrearPedidoApp (FACADE)          │  │
    │  │ - Orquesta todo                  │  │
    │  │ - Punto de entrada único         │  │
    │  └────────┬─────────────────────────┘  │
    │           │                            │
    │      ┌────┴─────────────────────┐      │
    │      │                          │      │
    │      ↓                          ↓      │
    │  ┌──────────────────┐    ┌──────────────┐
    │  │ CotizacionSearch │    │ PrendasUI    │
    │  │ UIController     │    │ Controller   │
    │  │ - Maneja input   │    │ - Carga      │
    │  │ - Dropdown       │    │ - Tallas     │
    │  └─────┬────────────┘    │ - Cantidades │
    │        │                 └──┬───────────┘
    │        │                    │
    │        └─────────┬──────────┘
    │                  │
    │      ┌───────────┴───────────┐
    │      ↓                       ↓
    │  ┌──────────────┐   ┌─────────────────┐
    │  │ Repository   │   │ FormularioPedido│
    │  │ (Datos Local)│   │ Controller      │
    │  └──────────────┘   │ - Valida        │
    │                     │ - Envía POST    │
    │                     │ - Muestra UI    │
    │                     └────────┬────────┘
    │                              │
    └──────────────────────────────┼──────────────────┘
                                   │
                                   ↓ AJAX POST
┌──────────────────────────────────────────────────────────────────────────────┐
│                          HTTP REQUEST LAYER                                   │
└────────────────────┬─────────────────────────────────────────────────────────┘
                     │
                     ↓
    ┌─────────────────────────────────────────┐
    │  CONTROLLER LAYER                       │
    │  PedidoProduccionController              │
    │                                         │
    │  - crearDesdeCotzacion()                │
    │    • Valida Request                     │
    │    • Crea DTOs                          │
    │    • Llama a Services                   │
    │    • Retorna JSON Response              │
    └────────────────┬────────────────────────┘
                     │
                     ↓
    ┌────────────────────────────────────────────┐
    │    BUSINESS LOGIC LAYER (SERVICES)         │
    │                                            │
    │  ┌──────────────────────────────────────┐  │
    │  │ PedidoProduccionCreatorService       │  │
    │  │ - crear(dto, asesorId)               │  │
    │  │ - obtenerProximoNumero()             │  │
    │  └────────────┬─────────────────────────┘  │
    │              │                             │
    │              ↓                             │
    │  ┌──────────────────────────────────────┐  │
    │  │ PrendaProcessorService (DIP)         │  │
    │  │ - procesar(prenda)                   │  │
    │  │ - procesarCantidades()               │  │
    │  │ - normalizarString()                 │  │
    │  └──────────────────────────────────────┘  │
    │                                            │
    │  ┌──────────────────────────────────────┐  │
    │  │ CotizacionSearchService              │  │
    │  │ - obtenerTodas()                     │  │
    │  │ - obtenerPorAsesor()                 │  │
    │  │ - filtrarPorTermino()                │  │
    │  └──────────────────────────────────────┘  │
    │                                            │
    └────────────────┬──────────────────────────┘
                     │
                     ↓
    ┌────────────────────────────────────────┐
    │    DATA TRANSFER LAYER (DTOs)          │
    │                                        │
    │  CotizacionSearchDTO                   │
    │  ├─ id (readonly)                      │
    │  ├─ numero                             │
    │  ├─ cliente                            │
    │  ├─ asesora                            │
    │  ├─ formaPago                          │
    │  └─ prendasCount                       │
    │                                        │
    │  PrendaCreacionDTO                     │
    │  ├─ index                              │
    │  ├─ nombreProducto                     │
    │  ├─ descripcion                        │
    │  ├─ especificaciones {...}             │
    │  ├─ cantidades {...}                   │
    │  ├─ esValido()                         │
    │  └─ cantidadTotal()                    │
    │                                        │
    │  CrearPedidoProduccionDTO              │
    │  ├─ cotizacionId                       │
    │  ├─ prendasData                        │
    │  ├─ esValido()                         │
    │  └─ prendasValidas()                   │
    │                                        │
    └────────────────┬──────────────────────┘
                     │
                     ↓
    ┌────────────────────────────────────────┐
    │   DATA ACCESS LAYER (Eloquent)         │
    │                                        │
    │  Cotizacion Model                      │
    │  PedidoProduccion Model                │
    │  PrendaCotizacion Model                │
    │                                        │
    │  Database Queries                      │
    └────────────────┬──────────────────────┘
                     │
                     ↓
    ┌────────────────────────────────────────┐
    │   DATABASE                             │
    │                                        │
    │  cotizaciones (tabla)                  │
    │  pedidos_produccion (tabla)            │
    │  prendas_cotizaciones (tabla)          │
    │  ...                                   │
    │                                        │
    └────────────────────────────────────────┘
```

---

## Inyección de Dependencias

```
┌──────────────────────────────────────────────────┐
│  Service Provider                                │
│  (app/Providers/PedidosServiceProvider.php)      │
└──────────┬───────────────────────────────────────┘
           │
           ├──→ Registra PrendaProcessorService
           │    └─ Singleton (reutilizable)
           │
           ├──→ Registra CotizacionSearchService
           │    └─ Singleton (reutilizable)
           │
           └──→ Registra PedidoProduccionCreatorService
                ├─ Inyecta PrendaProcessorService
                └─ Listo para usar

┌──────────────────────────────────────────────────┐
│  Controller Constructor                          │
│  (Inyección automática por Laravel)              │
└──────────┬───────────────────────────────────────┘
           │
           ├──→ CotizacionSearchService
           ├──→ PedidoProduccionCreatorService
           └──→ PrendaProcessorService
                └─ Todas configuradas y listas
```

---

## Ciclo de Vida: Crear Pedido

```
1. USUARIO FINAL
   └─ Ingresa búsqueda en input
   
2. FRONTEND
   └─ CotizacionSearchUIController.handleSearch()
      └─ Llama a CotizacionRepository.buscar()
         └─ Retorna resultados filtrados
            └─ Renderiza en dropdown

3. USUARIO SELECCIONA COTIZACIÓN
   └─ CotizacionSearchUIController.seleccionar()
      └─ CrearPedidoApp.cargarCotizacion()
         └─ CotizacionDataLoader.cargar()
            └─ AJAX GET /asesores/cotizaciones/{id}

4. BACKEND RECIBE SOLICITUD DE DATOS
   └─ PedidoProduccionController.obtenerDatosCotizacion()
      └─ CotizacionSearchService.obtenerPorId()
         └─ Retorna Cotizacion Model
            └─ JSON con prendas y datos

5. FRONTEND RECIBE DATOS
   └─ PrendasUIController.cargar()
      └─ Renderiza prendas con tallas
         └─ Usuario ve todo disponible

6. USUARIO INGRESA CANTIDADES Y ENVÍA
   └─ FormularioPedidoController.handleSubmit()
      └─ Recolecta datos con PrendasUIController.obtenerDatos()
         └─ AJAX POST /asesores/cotizaciones/{id}/crear-pedido-produccion
            └─ Envía: { cotizacion_id, prendas[] }

7. BACKEND RECIBE SOLICITUD DE CREACIÓN
   └─ PedidoProduccionController.crearDesdeCotzacion()
      └─ Valida Request
         └─ Crea DTO: CrearPedidoProduccionDTO::fromRequest()
            └─ Valida DTO: $dto->esValido()
               └─ PedidoProduccionCreatorService.crear()
                  └─ PrendaProcessorService.procesar() (para cada prenda)
                     └─ PedidoProduccion::create() (Guarda en BD)
                        └─ Retorna JSON { success: true, redirect }

8. FRONTEND RECIBE RESPUESTA
   └─ FormularioPedidoController.mostrarExito()
      └─ Muestra notificación SweetAlert
         └─ Redirige a /asesores/pedidos-produccion

9. PEDIDO CREADO ✅
   └─ Usuario ve lista actualizada
      └─ Nuevo pedido aparece en tabla
```

---

## Responsabilidades por Capa

```
┌─────────────────────────────────────────┐
│ PRESENTATION LAYER                      │
├─────────────────────────────────────────┤
│ Responsabilidad: Mostrar UI             │
│ - Renderizar HTML                       │
│ - Aplicar estilos CSS                   │
│ - Mostrar/ocultar elementos             │
│ NO: Contiene lógica de negocio          │
└─────────────────────────────────────────┘
            ↓ Depende de
┌─────────────────────────────────────────┐
│ CONTROLLER LAYER                        │
├─────────────────────────────────────────┤
│ Responsabilidad: Coordinar              │
│ - Recibir HTTP requests                 │
│ - Validar input                         │
│ - Orquestar Services                    │
│ - Retornar respuestas (JSON/View)       │
│ NO: Lógica de negocio compleja          │
└─────────────────────────────────────────┘
            ↓ Depende de
┌─────────────────────────────────────────┐
│ SERVICE LAYER                           │
├─────────────────────────────────────────┤
│ Responsabilidad: Lógica de negocio      │
│ - CotizacionSearchService               │
│   └─ Búsqueda y filtrado                │
│ - PrendaProcessorService                │
│   └─ Procesamiento de datos             │
│ - PedidoProduccionCreatorService        │
│   └─ Creación de pedidos                │
│ NO: Acceso directo a BD                 │
└─────────────────────────────────────────┘
            ↓ Depende de
┌─────────────────────────────────────────┐
│ DTO LAYER                               │
├─────────────────────────────────────────┤
│ Responsabilidad: Transferencia tipada   │
│ - Encapsular datos                      │
│ - Validación básica                     │
│ - Conversión de formatos                │
│ - Métodos de factory                    │
│ NO: Lógica de negocio                   │
└─────────────────────────────────────────┘
            ↓ Depende de
┌─────────────────────────────────────────┐
│ MODEL LAYER (Eloquent)                  │
├─────────────────────────────────────────┤
│ Responsabilidad: Acceso a BD            │
│ - Definir tablas                        │
│ - Relaciones                            │
│ - Queries básicas                       │
│ NO: Lógica de negocio compleja          │
└─────────────────────────────────────────┘
            ↓ Depende de
┌─────────────────────────────────────────┐
│ DATABASE                                │
├─────────────────────────────────────────┤
│ - Tablas                                │
│ - Índices                               │
│ - Relaciones                            │
└─────────────────────────────────────────┘
```

---

## Principios SOLID en Acción

### 🅢 SRP - Single Responsibility

```
CotizacionSearchService
  └─ SOLO: Buscar cotizaciones
     ├─ NO procesa prendas
     ├─ NO crea pedidos
     └─ NO actualiza UI

PrendaProcessorService
  └─ SOLO: Procesar prendas
     ├─ NO busca cotizaciones
     ├─ NO crea pedidos
     └─ NO accede a BD

PedidoProduccionCreatorService
  └─ SOLO: Crear pedidos
     ├─ NO busca cotizaciones
     ├─ Delega procesamiento a PrendaProcessorService
     └─ NO maneja UI
```

### 🅞 OCP - Open/Closed

```
Antes: Modificar PedidoProduccionCreatorService
```php
// ❌ Para agregar caché, había que modificar
class PedidoProduccionCreatorService {
    if (cache()) { ... }
    // Modificar existente
}
```

Después: Extender sin modificar
```php
// ✅ Para agregar caché, solo extender
class PedidoCreatorWithCache extends PedidoProduccionCreatorService {
    public function crear() {
        return cache()->remember(..., fn() => parent::crear());
    }
}
```

### 🅛 LSP - Liskov Substitution

```
DTOs intercambiables:

CotizacionSearchDTO $dto1 = CotizacionSearchDTO::fromModel($model);
CotizacionSearchDTO $dto2 = ... // Otra fuente

// Ambas funcionan igual
$resultado = $service->filtrarPorTermino($todas, $termino);
```

### 🅘 ISP - Interface Segregation

```
Métodos simples y específicos:

CotizacionRepository:
  ├─ obtenerTodas()        // Método simple
  ├─ filtrarPorAsesor()    // Método simple
  ├─ buscar(termino)       // Método simple
  └─ obtenerPorId()        // Método simple

NO: Un método gigante que hace todo
```

### 🅓 DIP - Dependency Inversion

```
Antes: Acoplado (hardcoded)
```php
$processor = new PrendaProcessorService();
$service = new PedidoCreator($processor);
// Difícil cambiar implementación
```

Después: Inyectado
```php
public function __construct(
    private PrendaProcessorService $processor
) {}

// Service Provider configura
$app->bind(PedidoCreator::class, function($app) {
    return new PedidoCreator(
        $app->make(PrendaProcessorService::class)
    );
});
// Fácil cambiar implementación
```

---

## Patrones Visualizados

### Repository Pattern (Frontend)

```
┌─────────────────────┐
│  CotizacionRepository │
├─────────────────────┤
│ Datos en memoria    │
│ Array de DTOs       │
└────────┬────────────┘
         │
         ├─→ buscar()         // Búsqueda local
         ├─→ filtrarPorAsesor() // Filtrado
         ├─→ obtenerPorId()    // Acceso
         └─→ obtenerTodas()    // Lectura

Beneficio:
- Abstrae acceso a datos
- Reutilizable en cualquier UI
- Fácil testear
```

### Service Layer (Backend)

```
┌──────────────────────────────────────┐
│         SERVICE LAYER                │
├──────────────────────────────────────┤
│ CotizacionSearchService              │
│ PrendaProcessorService               │
│ PedidoProduccionCreatorService       │
└────────────────┬─────────────────────┘
                 │
    ┌────────────┼────────────┐
    │            │            │
    ↓            ↓            ↓
  Model1      Model2      Model3

Beneficio:
- Lógica centralizada
- Reutilizable en múltiples contextos
- Fácil testear
- Fácil extender
```

### Factory Pattern (DTOs)

```
Data (Múltiples fuentes)
    ├─→ fromModel()          // Desde Eloquent
    ├─→ fromRequest()        // Desde HTTP Request
    ├─→ fromArray()          // Desde Array
    └─→ fromJson()           // Desde JSON

    ↓

DTO (Tipado y seguro)

    ↓

Lógica de negocio
(Siempre recibe DTO válido)

Beneficio:
- Conversión consistente
- Validación garantizada
- Tipado fuerte
```

---

## Métricas de Calidad

```
Complejidad Ciclomática
═══════════════════════════════════════
Antes:  ████████████████████░  Muy Alta (20+)
Después: █████░                Baja (5)

Acoplamiento
═══════════════════════════════════════
Antes:  ███████████████░      Alto
Después: ██░                   Bajo

Cohesión
═══════════════════════════════════════
Antes:  █░                     Baja
Después: ███████████████░      Alta

Testabilidad
═══════════════════════════════════════
Antes:  ██░                    Difícil
Después: ████████████████░     Fácil

Mantenibilidad
═══════════════════════════════════════
Antes:  ███░                   Difícil
Después: ████████████████░     Excelente
```

---

Conclusión: **¡Arquitectura Limpia, Modular y SOLID! 🎉**
