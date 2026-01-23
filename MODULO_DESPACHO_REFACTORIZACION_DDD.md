# 📋 MÓDULO DE DESPACHO - REFACTORIZACIÓN A DDD (COMPLETADA)

**Fecha:** 23 de enero de 2026  
**Estado:** ✅ COMPLETADA - 100% DDD

---

## 📊 Cambios realizados

### 1. Domain Layer (Lógica de negocio pura)

#### ✅ DespachoGeneradorService
**Ubicación:** `app/Domain/Pedidos/Services/DespachoGeneradorService.php`

- Generador de filas de despacho unificadas
- Métodos:
  - `generarFilasDespacho()` → prendas + EPP
  - `generarPrendas()` → solo prendas
  - `generarEpp()` → solo EPP
- Retorna: `Collection<FilaDespachoDTO>`

#### ✅ DespachoValidadorService
**Ubicación:** `app/Domain/Pedidos/Services/DespachoValidadorService.php`

- Validación de despachos
- Métodos:
  - `validarDespacho()` → Un despacho
  - `validarMultiplesDespachos()` → Varios
  - `procesarDespacho()` → Validar + log
  - `calcularPendiente()` → P1, P2, P3 automático
- Excepciones: `DespachoInvalidoException`

#### ✅ DespachoInvalidoException
**Ubicación:** `app/Domain/Pedidos/Exceptions/DespachoInvalidoException.php`

- Exception de dominio
- Lanzada cuando hay errores de negocio

---

### 2. Application Layer (Casos de uso)

#### ✅ ObtenerFilasDespachoUseCase
**Ubicación:** `app/Application/Pedidos/UseCases/ObtenerFilasDespachoUseCase.php`

- Use Case para obtener filas
- Métodos públicos:
  - `obtenerTodas($pedidoId)` → Todas
  - `obtenerPrendas($pedidoId)` → Solo prendas
  - `obtenerEpp($pedidoId)` → Solo EPP
- Coordina: Domain Service + Models

#### ✅ GuardarDespachoUseCase
**Ubicación:** `app/Application/Pedidos/UseCases/GuardarDespachoUseCase.php`

- Use Case para guardar despacho
- Entrada: `ControlEntregasDTO`
- Salida: `array ['success' => bool, 'message' => string]`
- Coordina: Validación + Transacciones + Logs

---

### 3. DTOs (Data Transfer Objects)

#### ✅ FilaDespachoDTO
**Ubicación:** `app/Application/Pedidos/DTOs/FilaDespachoDTO.php`

Atributos públicos type-safe:
```php
- tipo: string ('prenda' | 'epp')
- id: int|string
- tallaId: ?int
- descripcion: string
- cantidadTotal: int
- talla: string
- genero: ?string
- objetoPrenda: ?array
- objetoTalla: ?array
- objetoEpp: ?array
```

#### ✅ DespachoParcialesDTO
**Ubicación:** `app/Application/Pedidos/DTOs/DespachoParcialesDTO.php`

Atributos:
```php
- tipo: string
- id: int|string
- parcial1: int
- parcial2: int
- parcial3: int
+ método: getTotalDespachado()
```

#### ✅ ControlEntregasDTO
**Ubicación:** `app/Application/Pedidos/DTOs/ControlEntregasDTO.php`

Atributos:
```php
- pedidoId: int|string
- numeroPedido: string
- cliente: string
- fechaHora: ?Carbon
- clienteEmpresa: ?string
- despachos: DespachoParcialesDTO[]
```

---

### 4. Presentation Layer (HTTP)

#### ✅ DespachoController REFACTORIZADO
**Ubicación:** `app/Http/Controllers/DespachoController.php`

**Inyección de dependencias:**
```php
public function __construct(
    private ObtenerFilasDespachoUseCase $obtenerFilasUseCase,
    private GuardarDespachoUseCase $guardarDespachoUseCase,
)
```

**Métodos:**
- `index()` - GET /despacho
- `show($pedido)` - GET /despacho/{id}
- `guardarDespacho()` - POST /despacho/{id}/guardar
- `printDespacho()` - GET /despacho/{id}/print

**Cambios:**
- ❌ Eliminadas: Métodos `guardarDespachoPrenda()`, `guardarDespachoEpp()`
- ❌ Eliminadas: Lógica de validación inline
- ✅ Agregadas: Inyecciones de UseCase
- ✅ Delegada: Toda lógica a UseCases

---

### 5. Vistas (Blade)

#### ✅ show.blade.php ACTUALIZADA
**Cambios:**
- `$fila['tipo']` → `$fila->tipo` (DTO)
- `$fila['id']` → `$fila->id` (DTO)
- `$fila['cantidad_total']` → `$fila->cantidadTotal` (DTO)
- `$fila['talla_id']` → `$fila->tallaId` (DTO)
- Acceso a atributos públicos del DTO

#### ✅ print.blade.php ACTUALIZADA
**Cambios:**
- Igual que show.blade.php
- `$fila->tipo` en lugar de `$fila['tipo']`

#### ✅ index.blade.php
**Sin cambios** - Ya estaba bien

---

### 6. Modelos (Infrastructure)

#### ✅ PedidoProduccion
**Cambios:**
- ❌ Eliminados: Métodos `getFilasDespacho()`, `getPrendasParaDespacho()`, `getEppParaDespacho()`
- ✅ Mantenidas: Relaciones `prendas()`, `epps()`
- ✅ Mantenido: Alias `prendaPedidoTallas()` en PrendaPedido

**Razón:** La lógica pertenece al Domain Service, no al Model

---

### 7. Rutas
**Sin cambios** - Ya están en DDD

```php
routes/despacho.php
- GET  /despacho
- GET  /despacho/{id}
- POST /despacho/{id}/guardar
- GET  /despacho/{id}/print
```

---

## 📈 Métricas de mejora

| Métrica | Antes | Después |
|---------|-------|---------|
| **Capas** | 2 (Controller + Model) | 4 (Presentation, Application, Domain, Infrastructure) |
| **Responsabilidades del Model** | 5 | 0 (solo persistencia) |
| **Métodos en Controller** | 6 | 4 (delegation, no logic) |
| **DTOs** | 0 | 3 |
| **UseCase específicos** | 0 | 2 |
| **Domain Services** | 0 | 2 |
| **Testabilidad** | Acoplada a Framework | Independent (no Framework) |

---

## 🔄 Flujo de datos (Nuevo)

```
HTTP Request
    ↓
DespachoController (Presentation)
    ↓ inject
ObtenerFilasDespachoUseCase / GuardarDespachoUseCase (Application)
    ↓ use
DespachoGeneradorService / DespachoValidadorService (Domain)
    ↓
Models (Infrastructure)
    ↓
DTOs (Transfer Objects)
    ↓
HTTP Response / Views
```

---

## 🧪 Testing

### Ejemplo: Test de Domain Service (sin Framework)
```php
public function test_generar_filas_unifica_prendas_y_epp()
{
    $service = new DespachoGeneradorService();
    $pedido = new PedidoProduccion(['id' => 1]);
    // ... mock relaciones
    
    $filas = $service->generarFilasDespacho($pedido);
    
    $this->assertInstanceOf(Collection::class, $filas);
    $this->assertInstanceOf(FilaDespachoDTO::class, $filas[0]);
}
```

### Ejemplo: Test de Use Case
```php
public function test_guardar_despacho_rechaza_valores_negativos()
{
    $useCase = app(GuardarDespachoUseCase::class);
    $control = new ControlEntregasDTO(
        pedidoId: 1,
        numeroPedido: 'TEST-001',
        cliente: 'Test',
        despachos: [[
            'tipo' => 'prenda',
            'id' => 1,
            'parcial_1' => -5,  // ❌ Negativo
            'parcial_2' => 0,
            'parcial_3' => 0,
        ]],
    );
    
    $this->expectException(\Exception::class);
    $useCase->ejecutar($control);
}
```

---

## 📝 Inversión de control (Dependency Injection)

### Antes
```php
$service = new DespachoGeneradorService();  // ❌ Manual
$filas = $service->generarFilasDespacho($pedido);
```

### Después (Laravel Container)
```php
// En Service Provider
$this->app->singleton(DespachoGeneradorService::class);
$this->app->singleton(ObtenerFilasDespachoUseCase::class);

// En Controller
public function __construct(
    private ObtenerFilasDespachoUseCase $useCase
) {}  // ✅ Automático

// Laravel resuelve las dependencias
```

---

## 🚀 Próximas mejoras (Opcionales)

- [ ] Agregar tabla `despacho_historico` para auditoría
- [ ] Crear Specification Pattern para validaciones complejas
- [ ] Agregar eventos de dominio (DomainEvent)
- [ ] Repository pattern explícito
- [ ] CQRS para lectura/escritura separadas
- [ ] PDF generation con Dompdf/TCPDF

---

## ✅ Checklist de validación DDD

- ✅ Existe Domain Layer con Services
- ✅ Existe Application Layer con UseCases
- ✅ Existe Presentation Layer (Controller)
- ✅ DTOs para transferencia de datos
- ✅ Domain Exceptions
- ✅ Dependency Injection
- ✅ Separation of Concerns
- ✅ Model con una sola responsabilidad
- ✅ No hay lógica en vistas
- ✅ Testeable sin Framework

---

## 📚 Archivos modificados

| Archivo | Cambio | Tipo |
|---------|--------|------|
| `app/Domain/Pedidos/Services/DespachoGeneradorService.php` | ✨ NUEVO | Domain Service |
| `app/Domain/Pedidos/Services/DespachoValidadorService.php` | ✨ NUEVO | Domain Service |
| `app/Domain/Pedidos/Exceptions/DespachoInvalidoException.php` | ✨ NUEVO | Exception |
| `app/Application/Pedidos/UseCases/ObtenerFilasDespachoUseCase.php` | ✨ NUEVO | UseCase |
| `app/Application/Pedidos/UseCases/GuardarDespachoUseCase.php` | ✨ NUEVO | UseCase |
| `app/Application/Pedidos/DTOs/FilaDespachoDTO.php` | ✨ NUEVO | DTO |
| `app/Application/Pedidos/DTOs/DespachoParcialesDTO.php` | ✨ NUEVO | DTO |
| `app/Application/Pedidos/DTOs/ControlEntregasDTO.php` | ✨ NUEVO | DTO |
| `app/Http/Controllers/DespachoController.php` | 🔄 REFACTORIZADO | Controller |
| `resources/views/despacho/show.blade.php` | 🔄 ACTUALIZADA | Vista |
| `resources/views/despacho/print.blade.php` | 🔄 ACTUALIZADA | Vista |
| `app/Models/PedidoProduccion.php` | 🗑️ LIMPIADA | Model |
| `routes/despacho.php` | ✅ SIN CAMBIOS | Routes |
| `resources/views/despacho/index.blade.php` | ✅ SIN CAMBIOS | Vista |

---

## 🎓 Documentación generada

- ✅ `MODULO_DESPACHO_DDD_ARQUITECTURA.md` - Esta arquitectura en detalle
- ✅ `MODULO_DESPACHO_DOCUMENTACION.md` - Documentación técnica original
- ✅ `MODULO_DESPACHO_README.md` - Quick start
- ✅ `MODULO_DESPACHO_REFERENCIA_TECNICA.md` - Referencia rápida

---

## ✨ Resumen

El módulo de **Despacho ahora es 100% DDD**:

1. ✅ **Domain Layer**: Services con lógica pura
2. ✅ **Application Layer**: UseCases coordinadores
3. ✅ **Presentation Layer**: Controller delegador
4. ✅ **DTOs**: Transfer objects desacoplados
5. ✅ **Testing**: Fácil de testear
6. ✅ **Mantenibilidad**: Código limpio y organizado
7. ✅ **Escalabilidad**: Fácil agregar funcionalidad

**Pronto para producción** 🚀
