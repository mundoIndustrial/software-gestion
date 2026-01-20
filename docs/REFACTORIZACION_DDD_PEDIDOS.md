# Refactorización DDD - Pedidos de Producción

##  Resumen

Se ha iniciado la refactorización del controlador `PedidosProduccionController.php` (3091 líneas) aplicando principios de **Domain-Driven Design (DDD)** y **SOLID**.

##  Problemas Identificados

1. **God Object Controller**: 3091 líneas, 30+ métodos, múltiples responsabilidades
2. **Lógica de negocio en controlador**: Cálculos, validaciones, transformaciones
3. **Acceso directo a modelos**: Sin capa de repositorio
4. **Sin servicios de dominio**: Lógica dispersa y duplicada
5. **Transacciones DB en controlador**: Debería estar en servicios

##  Arquitectura DDD Implementada

```
app/Domain/PedidoProduccion/
├── Services/
│   ├── NumeracionService.php           Generación de números únicos
│   ├── DescripcionService.php          Construcción de descripciones
│   ├── ImagenService.php               Procesamiento de imágenes
│   └── PedidoProduccionService.php     Lógica de negocio principal
├── Repositories/
│   └── CotizacionRepository.php        Acceso a datos de cotizaciones
├── DTOs/                              🔄 Pendiente
└── ValueObjects/                      🔄 Pendiente
```

##  Servicios Creados

### 1. NumeracionService
**Responsabilidad**: Generar números secuenciales únicos para pedidos

```php
// Uso
$numeroPedido = $this->numeracionService->generarNumeroPedido();
$numeroLogo = $this->numeracionService->generarNumeroLogoPedido();
```

**Características**:
-  Usa DB locks para prevenir race conditions
-  Maneja secuencias separadas para pedidos y logos
-  Formato específico para cada tipo

### 2. DescripcionService
**Responsabilidad**: Construir descripciones formateadas de prendas

```php
// Uso
$descripcion = $this->descripcionService->construirDescripcionPrenda(
    $numeroPrenda,
    $producto,
    $cantidadesPorTalla
);
```

**Métodos**:
- `construirDescripcionPrenda()` - Para prendas de cotización
- `construirDescripcionPrendaSinCotizacion()` - Para prendas nuevas
- `construirDescripcionReflectivoSinCotizacion()` - Para reflectivos

### 3. ImagenService
**Responsabilidad**: Procesar y guardar imágenes en formato WebP

```php
// Uso
$ruta = $this->imagenService->guardarImagenComoWebp(
    $file,
    $numeroPedido,
    'prendas' // o 'logos', 'telas'
);
```

**Características**:
-  Convierte automáticamente a WebP
-  Calidad optimizada (85%)
-  Nombres únicos con timestamp
-  Validación de archivos

### 4. PedidoProduccionService
**Responsabilidad**: Orquestar la creación y gestión de pedidos

```php
// Uso
$pedido = $this->pedidoService->crearDesdeCotizacion($cotizacionId);
$pedidos = $this->pedidoService->obtenerPedidosAsesor($filtros);
```

**Métodos**:
- `crearDesdeCotizacion()` - Crear pedido desde cotización
- `obtenerPedidosAsesor()` - Listar pedidos con filtros
- `actualizarEstado()` - Cambiar estado de pedido

### 5. CotizacionRepository
**Responsabilidad**: Encapsular consultas a la base de datos

```php
// Uso
$cotizaciones = $this->cotizacionRepository->obtenerCotizacionesAprobadas();
$cotizacion = $this->cotizacionRepository->obtenerCotizacionCompleta($id);
```

**Métodos**:
- `obtenerCotizacionesAprobadas()` - Cotizaciones del asesor
- `obtenerCotizacionCompleta()` - Con todas las relaciones
- `esCotizacionLogo()` - Verificar tipo
- `esCotizacionReflectivo()` - Verificar tipo

## 🔄 Controlador Refactorizado

### Antes (Violaba SOLID)
```php
public function crearFormEditable()
{
    // Lógica de acceso a datos directamente en controlador
    $cotizaciones = Cotizacion::where('asesor_id', Auth::id())
        ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
        ->with([...])
        ->orderBy('created_at', 'desc')
        ->get();
    
    return view(...);
}
```

### Después (Sigue DDD)
```php
public function __construct(
    private PedidoProduccionService $pedidoService,
    private CotizacionRepository $cotizacionRepository
) {}

public function crearFormEditable()
{
    // Delegar al repositorio
    $cotizaciones = $this->cotizacionRepository->obtenerCotizacionesAprobadas();
    
    return view(...);
}
```

## 📈 Beneficios de la Refactorización

###  Ventajas Inmediatas
1. **Separación de Responsabilidades**: Cada clase tiene una única responsabilidad
2. **Testeable**: Los servicios pueden testearse independientemente
3. **Reutilizable**: Los servicios se pueden usar en otros controladores
4. **Mantenible**: Cambios en lógica de negocio no afectan al controlador
5. **Escalable**: Fácil agregar nuevas funcionalidades

###  Principios SOLID Aplicados
- **S**ingle Responsibility: Cada servicio tiene una responsabilidad
- **O**pen/Closed: Servicios abiertos a extensión, cerrados a modificación
- **L**iskov Substitution: Interfaces consistentes
- **I**nterface Segregation: Interfaces específicas
- **D**ependency Inversion: Controlador depende de abstracciones

## 🔄 Estado de la Refactorización

###  Completado
- [x] NumeracionService
- [x] DescripcionService
- [x] ImagenService
- [x] PedidoProduccionService (básico)
- [x] CotizacionRepository
- [x] Inyección de dependencias en controlador
- [x] Refactorización de métodos principales

### 🔄 En Progreso
- [ ] Migrar todos los métodos del controlador a servicios
- [ ] Crear DTOs para transferencia de datos
- [ ] Crear Value Objects para conceptos de dominio
- [ ] Tests unitarios para servicios

###  Pendiente
- [ ] LogoPedidoService (lógica específica de logos)
- [ ] ReflectivoPedidoService (lógica específica de reflectivos)
- [ ] PrendaRepository
- [ ] PedidoProduccionRepository
- [ ] Event Sourcing para auditoría
- [ ] Documentación completa de API

## 🚀 Próximos Pasos

1. **Continuar migración incremental**: Mover métodos del controlador a servicios
2. **Crear tests**: Asegurar que la funcionalidad se mantiene
3. **Crear DTOs**: Para validación y transferencia de datos
4. **Documentar**: PHPDoc completo en todos los servicios

##  Notas Importantes

- **No romper funcionalidad existente**: La refactorización es incremental
- **Mantener compatibilidad**: Los métodos legacy se marcan como deprecated
- **Testear cada cambio**: Verificar que todo sigue funcionando
- **Documentar decisiones**: Registrar el por qué de cada cambio

## 🔗 Referencias

- [Domain-Driven Design](https://martinfowler.com/bliki/DomainDrivenDesign.html)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Repository Pattern](https://martinfowler.com/eaaCatalog/repository.html)
- [Service Layer](https://martinfowler.com/eaaCatalog/serviceLayer.html)
