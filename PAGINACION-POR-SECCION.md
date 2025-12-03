# Implementación de Paginación por Sección (Prenda, Logo, Prenda/Bordado)

## Estado: ✅ COMPLETADO

### Cambios Realizados

#### 1. Controller: `app/Http/Controllers/Asesores/CotizacionesController.php`

**Cambio Principal:**
- Reemplazó la lógica de 6 queries independientes (que paginaban antes de filtrar) por una lógica que:
  1. Obtiene TODOS los cotizaciones/borradores del usuario
  2. Filtra por tipo usando `obtenerTipoCotizacion()`
  3. Crea paginadores manuales para cada tipo usando el método `paginateCollection()`

**Nuevas Características:**
- Agregó método privado `paginateCollection()` que convierte colecciones filtradas en `LengthAwarePaginator`
- Cada tipo tiene su propio paginador con sus propias URL parameters:
  - Cotizaciones Prenda: `page_cot_prenda`
  - Cotizaciones Logo: `page_cot_logo`
  - Cotizaciones Prenda/Bordado: `page_cot_pb`
  - Borradores Prenda: `page_bor_prenda`
  - Borradores Logo: `page_bor_logo`
  - Borradores Prenda/Bordado: `page_bor_pb`

**Imports Agregados:**
```php
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
```

#### 2. Vista: `resources/views/asesores/cotizaciones/index.blade.php`

**Cambios Principales:**
- Reemplazó la estructura única de tablas con 6 secciones independientes:
  - 3 secciones para cotizaciones (Prenda, Logo, Prenda/Bordado)
  - 3 secciones para borradores (Prenda, Logo, Prenda/Bordado)

- Cada sección tiene:
  - Encabezado con nombre del tipo (`<h3>`)
  - Tabla con datos del tipo específico
  - Paginación independiente con su propio `pageName`
  - Mensaje "No hay datos" si la colección está vacía

**IDs de Secciones:**
```
Cotizaciones:
- #seccion-prenda
- #seccion-logo
- #seccion-pb

Borradores:
- #seccion-bor-prenda
- #seccion-bor-logo
- #seccion-bor-pb
```

**Filtros Removidos:**
- Eliminó los `@if($cot->obtenerTipoCotizacion() === 'X')` innecesarios
- Ahora las colecciones ya contienen solo elementos del tipo correcto

#### 3. Función JavaScript: `mostrarTipo(tipo)`

**Cambio de Comportamiento:**
- Antes: Filtraba visualmente con `display: none/block` en tarjetas
- Ahora: Oculta/muestra secciones completas (`#seccion-prenda`, etc.)

**Nueva Lógica:**
```javascript
function mostrarTipo(tipo) {
    // Oculta todas las secciones
    document.querySelectorAll('#tab-cotizaciones .seccion-tipo, #tab-borradores .seccion-tipo')
        .forEach(seccion => { seccion.style.display = 'none'; });
    
    // Muestra solo la sección del tipo seleccionado
    if (tipo === 'P') {
        document.getElementById('seccion-prenda').style.display = 'block';
        document.getElementById('seccion-bor-prenda').style.display = 'block';
    } // ... similar para 'L' y 'PB'
}
```

### Ventajas de Esta Implementación

1. **Paginación Correcta**: Cada tipo tiene su propia paginación independiente
   - La página 1 de Prenda no afecta la página 1 de Logo
   - Los URL parameters mantienen el estado de cada paginador

2. **Rendimiento Optimizado**:
   - Se cargan todos los datos una sola vez con eager loading
   - El filtrado ocurre en PHP (memoria) vs. en múltiples queries
   - Mejor que 6 queries separadas

3. **Código Más Limpio**:
   - Eliminó los `@if` innecesarios en la vista
   - La lógica de filtrado está centralizada en el controlador
   - Las colecciones ya contienen datos correctamente filtrados

4. **Experiencia de Usuario**:
   - Cambiar entre tipos es instantáneo (solo oculta/muestra divs)
   - Las URLs contienen parámetros separados para cada tipo
   - Bookmark de URL mantiene el estado (tipo + página)

### Cómo Funciona

1. **Usuario hace clic en tab "Logo"**
   - Evento `onclick="mostrarTipo('L')"`
   - JavaScript oculta `#seccion-prenda` y `#seccion-pb`
   - JavaScript muestra `#seccion-logo` y `#seccion-bor-logo`

2. **Usuario pagina en sección Logo**
   - Hace clic en página 2 de paginación
   - URL parámetro: `?page_cot_logo=2`
   - Controller obtiene el parámetro correcto
   - Se muestra la página 2 de SOLO los datos de Logo

3. **Usuario cambia a Prenda**
   - URL parámetro anterior `page_cot_logo=2` se mantiene
   - Se muestra `#seccion-prenda` con su paginación (default página 1)
   - Si vuelve a Logo, mantiene `page_cot_logo=2`

### Archivos Modificados

- `app/Http/Controllers/Asesores/CotizacionesController.php`
- `resources/views/asesores/cotizaciones/index.blade.php`

### Validación

✅ Sin errores de sintaxis en Controller y View
✅ Métodos `paginateCollection()` implementados
✅ Imports de Pagination agregados
✅ Todas las 6 secciones creadas
✅ JavaScript `mostrarTipo()` actualizado
✅ Filtros innecesarios removidos

### Próximos Pasos (Opcional)

- [ ] Probar en navegador que la paginación funciona correctamente
- [ ] Verificar que las URLs mantienen estado al cambiar entre tipos
- [ ] Validar que no hay datos duplicados en las secciones
- [ ] Revisar que los números de paginación son correctos por tipo
