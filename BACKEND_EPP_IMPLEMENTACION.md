# Backend EPP - Implementación según DDD

## Estructura Implementada

### 1. **Migraciones** (`database/migrations/`)
- ✅ `2026_01_17_create_epps_table.php` - Tabla catálogo de EPP
- ✅ `2026_01_17_create_epp_imagenes_table.php` - Tabla de imágenes (referencias, no almacena rutas completas)
- ✅ `2026_01_17_create_pedido_epps_table.php` - Tabla relación Pedido ↔ EPP

### 2. **Capa de Dominio** (`app/Domain/Epp/`)

#### Value Objects
- `ValueObjects/CodigoEpp.php` - Garantiza códigos válidos (EPP-XXX-###)
- `ValueObjects/CategoriaEpp.php` - Enumera categorías válidas (CABEZA, MANOS, PIES, etc.)
- `ValueObjects/UrlImagen.php` - Construye URLs `/storage/epp/{codigo}/{archivo}`

#### Agregados
- `Aggregates/EppAggregate.php` - Raíz de agregado para EPP
  - Encapsula datos y comportamiento del EPP
  - Gestiona imágenes y validaciones de negocio
  - Métodos: activar(), desactivar(), eliminar(), agregarImagen()
  
- `Aggregates/EppImagenValue.php` - Value Object para imágenes
  - Solo referencia nombres de archivo
  - Construye URLs cuando es necesario

#### Repositorios
- `Repositories/EppRepositoryInterface.php` - Contrato para EPP
- `Repositories/EppRepository.php` - Implementación con mapeo Agregado → Modelo
  - obtenerPorId(), obtenerPorCodigo(), buscar()
  - obtenerActivos(), obtenerPorCategoria()
  - guardar(), eliminar()

- `Repositories/PedidoEppRepositoryInterface.php` - Contrato para relación Pedido-EPP
- `Repositories/PedidoEppRepository.php` - Implementación
  - obtenerEppDelPedido(), agregarEppAlPedido()
  - actualizarEppEnPedido(), eliminarEppDelPedido()

#### Servicios de Dominio
- `Services/EppDomainService.php` - Orquesta lógica de EPP
  - buscarEppConImagenes()
  - obtenerEppActivos(), obtenerEppPorCategoria()
  - Formatea respuestas API con URLs construidas

### 3. **Capa de Persistencia** (`app/Models/`)
- `Epp.php` - Modelo Eloquent para tabla `epps`
- `EppImagen.php` - Modelo Eloquent para tabla `epp_imagenes`
- `PedidoEpp.php` - Modelo Eloquent para tabla `pedido_epps`

Todos con scopes y relaciones, sirven como adaptadores entre BD y agregados.

### 4. **DTOs** (`app/DTOs/`)
- `AgregarEppAlPedidoDTO.php` - Validación entrada al agregar EPP
- `EppSearchDTO.php` - Parámetros búsqueda/filtrado

### 5. **Integración en Modelo de Pedido**
PedidoProduccion actualizado con:
- Relación `epps()` - belongsToMany con pivot table
- Relación `pedidoEpps()` - hasMany directo a tabla relación

### 6. **Registro de Dependencias**
`app/Providers/AppServiceProvider.php` actualizado:
- Binding de `EppRepositoryInterface` → `EppRepository`
- Binding de `PedidoEppRepositoryInterface` → `PedidoEppRepository`
- Singleton de `EppDomainService`

---

## Cómo Usar

### Búsqueda de EPP
```php
$eppService = app(\App\Domain\Epp\Services\EppDomainService::class);

// Buscar por término
$resultados = $eppService->buscarEppConImagenes('casco');

// Obtener todos activos
$todos = $eppService->obtenerEppActivos();

// Por categoría
$cabezas = $eppService->obtenerEppPorCategoria('CABEZA');

// Por ID
$epp = $eppService->obtenerEppPorId(1);
```

Respuesta incluye:
```json
{
  "id": 1,
  "codigo": "EPP-CAB-001",
  "nombre": "Casco de Seguridad",
  "categoria": "CABEZA",
  "imagen_principal_url": "/storage/epp/EPP-CAB-001/principal.jpg",
  "imagenes": [...]
}
```

### Agregar EPP a Pedido
```php
$pedidoEppRepo = app(\App\Domain\Epp\Repositories\PedidoEppRepositoryInterface::class);

$pedidoEppRepo->agregarEppAlPedido(
    pedidoId: $pedido->id,
    eppId: $epp->id,
    talla: 'L',
    cantidad: 10,
    observaciones: 'Requerimiento especial'
);
```

### Obtener EPP de un Pedido
```php
$eppDelPedido = $pedidoEppRepo->obtenerEppDelPedido($pedido->id);
```

---

## Rutas Sugeridas

Crear estas rutas en `routes/api.php`:

```php
Route::prefix('epp')->group(function () {
    // Búsqueda y listado
    Route::get('/', [EppController::class, 'index']); // Buscar/listar
    Route::get('/{id}', [EppController::class, 'show']); // Obtener uno
    Route::get('categorias/all', [EppController::class, 'categorias']);
    
    // Gestión en pedidos
    Route::post('pedidos/{pedido}/agregar', [PedidoEppController::class, 'agregar']);
    Route::delete('pedidos/{pedido}/epp/{epp}', [PedidoEppController::class, 'eliminar']);
    Route::get('pedidos/{pedido}', [PedidoEppController::class, 'obtenerDelPedido']);
});
```

---

## Próximos Pasos

1. **Crear Controllers**
   - `Http/Controllers/EppController.php`
   - `Http/Controllers/PedidoEppController.php`

2. **Crear Queries/Handlers** (si usan CQRS)
   - Queries para búsqueda
   - Handlers correspondientes

3. **Seeders**
   - Poblar tabla `epps` y `epp_imagenes` con datos reales

4. **Validadores de Dominio**
   - Si necesitan lógica adicional de validación

---

## Notas Importantes

⚠️ **Estructura de Imágenes**
- NO se crean carpetas automáticamente
- NO se suben imágenes desde backend
- Frontend o script manual debe crear: `storage/app/public/epp/{CODIGO_EPP}/{archivo}`
- Backend SOLO referencia nombres de archivo y construye URLs

✅ **Soft Deletes**
- Tabla `epps` tiene soft deletes
- Consultas automáticamente excluyen eliminados (usa `->onlyTrashed()` si necesita recuperar)

✅ **Relaciones Correctas**
- EPP → Imágenes: 1 a muchos (cascade delete)
- EPP → Pedidos: muchos a muchos (tabla `pedido_epps`)
- PedidoProduccion actualizado con ambas relaciones

✅ **URLs Siempre Construidas**
- Servicio de dominio genera URLs automáticamente
- Formato: `/storage/epp/{codigo}/{archivo}`
- Ejemplo: `/storage/epp/EPP-CAB-001/principal.jpg`
