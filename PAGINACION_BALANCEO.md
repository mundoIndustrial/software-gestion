# Paginación en Módulo de Balanceo

## Resumen de Cambios

Se ha implementado paginación en el módulo de balanceo para mejorar el rendimiento y la experiencia de usuario al trabajar con un gran número de prendas.

## Cambios Realizados

### 1. Controlador (BalanceoController.php)

**Archivo:** `app/Http/Controllers/BalanceoController.php`

#### Método `index()` actualizado:
- ✅ Implementación de paginación con 12 prendas por página
- ✅ Búsqueda en el servidor por nombre, referencia y tipo de prenda
- ✅ Ordenamiento por fecha de creación (más recientes primero)
- ✅ Preservación de parámetros de búsqueda en la paginación

```php
public function index(Request $request)
{
    $query = Prenda::with('balanceoActivo')->where('activo', true);
    
    // Aplicar búsqueda si existe
    if ($request->has('search') && $request->search != '') {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('nombre', 'like', '%' . $search . '%')
              ->orWhere('referencia', 'like', '%' . $search . '%')
              ->orWhere('tipo', 'like', '%' . $search . '%');
        });
    }
    
    // Paginación (12 prendas por página)
    $prendas = $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();
    
    return view('balanceo.index', compact('prendas'));
}
```

### 2. Vista (index.blade.php)

**Archivo:** `resources/views/balanceo/index.blade.php`

#### Cambios implementados:

1. **Buscador actualizado:**
   - Cambio de búsqueda en cliente (Alpine.js) a búsqueda en servidor
   - Formulario GET que envía el término de búsqueda al servidor
   - Botón para limpiar búsqueda cuando hay un término activo
   - Auto-submit al cambiar el valor del campo

2. **Grid de prendas:**
   - Eliminación de filtros en cliente con Alpine.js
   - Renderizado directo de prendas paginadas desde el servidor

3. **Controles de paginación:**
   - Implementación de controles de paginación de Laravel
   - Estilos personalizados que coinciden con el diseño de la aplicación
   - Diseño responsive y moderno

4. **Estilos CSS personalizados (basados en tableros.blade.php):**
   - Barra de progreso visual que muestra la página actual
   - Información de registros mostrados (ej: "Mostrando 1-12 de 45 prendas")
   - Botones de paginación con efecto hover
   - Página activa resaltada con gradiente naranja (#f97316 a #fb923c)
   - Transiciones suaves
   - Diseño consistente con el módulo de tableros
   - Fondo oscuro (#1e293b) con bordes redondeados
   - Responsive design para dispositivos móviles

## Características

### Paginación
- **Prendas por página:** 12
- **Navegación:** Botones anterior/siguiente + números de página
- **Preservación de búsqueda:** Los filtros se mantienen al cambiar de página
- **Barra de progreso:** Indicador visual del progreso de navegación
- **Información contextual:** Muestra "Mostrando X-Y de Z prendas"
- **Diseño:** Mismo estilo que el módulo de tableros (fondo oscuro, gradientes naranjas)

### Búsqueda
- **Campos buscables:** nombre, referencia, tipo
- **Tipo:** Búsqueda en servidor (mejor rendimiento)
- **Funcionalidad:** Auto-submit al cambiar el valor
- **Limpieza:** Botón X para limpiar la búsqueda rápidamente

### Rendimiento
- **Carga inicial:** Solo se cargan 12 prendas en lugar de todas
- **Consultas optimizadas:** Uso de `with('balanceoActivo')` para evitar N+1 queries
- **Ordenamiento:** Por fecha de creación descendente

## Ventajas de la Implementación

1. **Mejor rendimiento:** No se cargan todas las prendas de una vez
2. **Experiencia de usuario mejorada:** Navegación más rápida y fluida
3. **Escalabilidad:** Soporta cientos o miles de prendas sin problemas
4. **Búsqueda eficiente:** Filtrado en base de datos en lugar de en cliente
5. **Diseño consistente:** Estilos que coinciden con el resto de la aplicación

## Uso

### Para el usuario final:

1. **Navegar entre páginas:**
   - Usar los botones "Anterior" y "Siguiente"
   - Hacer clic en números de página específicos

2. **Buscar prendas:**
   - Escribir en el campo de búsqueda
   - La búsqueda se ejecuta automáticamente al cambiar el valor
   - Hacer clic en la X para limpiar la búsqueda

3. **Ver resultados:**
   - Se muestran hasta 12 prendas por página
   - Las prendas se ordenan por fecha de creación (más recientes primero)

## Configuración

Para cambiar el número de prendas por página, editar el archivo:
`app/Http/Controllers/BalanceoController.php`

```php
// Cambiar el número 12 por el deseado
$prendas = $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();
```

## Compatibilidad

- ✅ Compatible con Laravel 11.x
- ✅ Funciona con el sistema de temas (modo claro/oscuro)
- ✅ Responsive (se adapta a diferentes tamaños de pantalla)
- ✅ Accesible (navegación por teclado)

## Notas Técnicas

- Se eliminó la dependencia de Alpine.js para el filtrado
- La búsqueda ahora se realiza con SQL LIKE en el servidor
- Los estilos de paginación están inline en la vista para facilitar el mantenimiento
- Se utiliza `withQueryString()` para preservar parámetros de búsqueda en la paginación
