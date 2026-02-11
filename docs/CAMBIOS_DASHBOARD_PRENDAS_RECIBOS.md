# Cambios Implementados: Dashboard Operario - Prendas y Recibos de Costura

## Fecha: 04/02/2026
## Ruta: `http://localhost:8000/operario/dashboard`

---

## Descripción del Cambio

Se modificó el dashboard del operario para mostrar **prendas con sus recibos de costura** en lugar de **pedidos completos**. Esto permite al operario visualizar de forma más granular el trabajo de costura a nivel de prenda individual.

---

## Archivos Modificados

### 1. **Controlador Principal**
   - Archivo: [`app/Infrastructure/Http/Controllers/Operario/OperarioController.php`](app/Infrastructure/Http/Controllers/Operario/OperarioController.php)
   - Cambios:
     - Inyección del nuevo servicio `ObtenerPrendasRecibosService`
     - Modificación del método `dashboard()` para pasar los datos de prendas con recibos a la vista
     - Se mantiene compatibilidad con los datos existentes de `$operario->pedidos`

### 2. **Nuevo Servicio de Negocio**
   - Archivo: [`app/Application/Operario/Services/ObtenerPrendasRecibosService.php`](app/Application/Operario/Services/ObtenerPrendasRecibosService.php)
   - Funcionalidad:
     - Obtiene todos los recibos activos de tipo `COSTURA` y `COSTURA-BODEGA`
     - Agrupa recibos por `prenda_id`
     - Incluye información del pedido y prenda relacionada
     - Retorna colección con estructura:
       ```php
       [
           'prenda_id' => int,
           'pedido_id' => int,
           'numero_pedido' => string,
           'cliente' => string,
           'nombre_prenda' => string,
           'descripcion' => string,
           'de_bodega' => bool,
           'recibos' => [...],
           'total_recibos' => int,
           'fecha_creacion' => datetime
       ]
       ```

### 3. **Vista (Blade Template)**
   - Archivo: [`resources/views/operario/dashboard.blade.php`](resources/views/operario/dashboard.blade.php)
   - Cambios:
     - Reemplazó sección "MIS ÓRDENES" por "MIS PRENDAS - RECIBOS DE COSTURA"
     - Mostración de prendas individuales con sus recibos
     - Badges de colores por tipo de recibo (COSTURA, COSTURA-BODEGA, ESTAMPADO, etc.)
     - Botón "VER RECIBOS" en lugar de "COMPLETAR PROCESO"
     - Búsqueda ahora incluye prenda, número de pedido y cliente
     - Estilos CSS nuevos para recibos de costura

---

## Cambios en la Vista

### Antes
```blade
<!-- Mostrar PEDIDOS completos -->
@foreach($operario->pedidos as $pedido)
    <div class="orden-card-simple">
        <h4>#{{ $pedido['numero_pedido'] }}</h4>
        <p>{{ $pedido['cantidad'] }} prenda(s): {{ $pedido['descripcion'] }}</p>
        <button>COMPLETAR PROCESO</button>
    </div>
@endforeach
```

### Después
```blade
<!-- Mostrar PRENDAS con RECIBOS -->
@foreach($prendasConRecibos as $prenda)
    <div class="orden-card-simple">
        <h4>#{{ $prenda['numero_pedido'] }}</h4>
        <p><strong>{{ $prenda['nombre_prenda'] }}</strong></p>
        <div class="recibos-lista">
            @foreach($prenda['recibos'] as $recibo)
                <span class="recibo-badge">{{ $recibo['tipo_recibo'] }}</span>
            @endforeach
        </div>
        <button>VER RECIBOS</button>
    </div>
@endforeach
```

---

## Estructura de Datos - Relaciones

### Tabla de Referencia
| Tabla | Columna | Tipo | Descripción |
|-------|---------|------|-------------|
| `consecutivos_recibos_pedidos` | `id` | bigint | ID del recibo |
| ↑ | `prenda_id` | bigint | FK a `prendas_pedido.id` |
| ↑ | `tipo_recibo` | enum | COSTURA, ESTAMPADO, BORDADO, etc. |
| ↑ | `activo` | tinyint | 1 = activo, 0 = inactivo |
| `prendas_pedido` | `id` | bigint | ID de la prenda |
| ↑ | `pedido_produccion_id` | bigint | FK a `pedidos_produccion.id` |
| ↑ | `nombre_prenda` | varchar | Nombre de la prenda |
| `pedidos_produccion` | `id` | bigint | ID del pedido |
| ↑ | `numero_pedido` | varchar | # Pedido |
| ↑ | `cliente` | varchar | Cliente |

---

## Flujo de Datos

```
OperarioController.dashboard()
    ↓
ObtenerPrendasRecibosService.obtenerPrendasConRecibos()
    ↓
ConsecutivoReciboPedido::where('activo', 1)
    ↓
with(['prenda', 'prenda.pedidoProduccion'])
    ↓
groupBy('prenda_id')
    ↓
Retorna Collection<PrendaConRecibos>
    ↓
Pasa a vista: $prendasConRecibos
    ↓
dashboard.blade.php renderiza prendas
```

---

## Estilos CSS Nuevos

Se agregaron los siguientes estilos para los recibos:

```css
.recibos-info {}
.recibos-lista {}
.recibo-badge {}
.recibo-costura {}
.recibo-costura_bodega {}
.recibo-estampado {}
.recibo-bordado {}
.recibo-reflectivo {}
.recibo-dtf {}
.recibo-sublimado {}
.btn-ver-recibos {}
```

Cada tipo de recibo tiene un color distintivo:
- **COSTURA**: Azul (#E3F2FD)
- **COSTURA-BODEGA**: Naranja (#FFF3E0)
- **ESTAMPADO**: Púrpura (#F3E5F5)
- **BORDADO**: Verde agua (#E0F2F1)
- **REFLECTIVO**: Rosa (#FCE4EC)
- **DTF**: Verde (#E8F5E9)
- **SUBLIMADO**: Índigo (#EDE7F6)

---

## Funcionalidad JavaScript

### Nueva Función
```javascript
function abrirDetallesRecibos(prendaId, nombrePrenda) {
    abrirModalExito('DETALLES DE RECIBOS', `Prenda: ${nombrePrenda}`);
}
```

### Búsqueda Mejorada
- Ahora busca por:
  - `numero_pedido`
  - `nombre_prenda` (nuevo)
  - `cliente`

---

## Cambios en Variables de Datos

### Variable Antigua
```php
'operario' => $datosOperario  // Contiene: pedidos, totalPedidos, etc.
```

### Variable Nueva (Complementaria)
```php
'prendasConRecibos' => $prendasConRecibos  // Contiene: prendas con recibos agrupadas
```

**Nota**: Se mantiene `$operario` para compatibilidad con otras partes del código.

---

## Verificación de Sintaxis

 `OperarioController.php` - Sin errores  
 `ObtenerPrendasRecibosService.php` - Sin errores  
 `dashboard.blade.php` - Sin errores  

---

## Próximos Pasos (Opcional)

1. **Expandir funcionalidad "VER RECIBOS"**
   - Mostrar detalles completos del recibo
   - Formularios para actualizar consecutivos
   - Historial de cambios

2. **Filtrar por tipo de recibo**
   - Similar a los filtros de costura-reflectivo
   - Badges para filtrar por COSTURA, ESTAMPADO, etc.

3. **Integrar con módulo de producción**
   - Vincular con horas de trabajo
   - Métricas de productividad
   - Historial de estados

---

## Compatibilidad

- Laravel 11+
- PHP 8.1+
- Blade Template Engine
- Eloquent ORM

---

## Autor
Sistema de gestión Mundo Industrial  
Fecha: 04/02/2026
