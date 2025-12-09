# 🎯 SECCIÓN VARIACIONES ESPECÍFICAS - RECUPERADA

## Ubicación
**Ruta**: `http://servermi:8000/asesores/cotizaciones/prenda/crear`
**Archivo**: `resources/views/cotizaciones/prenda/create.blade.php`
**Líneas**: 1122-1186

## HTML Completo

```html
<!-- SECCIÓN DE VARIACIONES ESPECÍFICAS -->
<div class="producto-section">
    <div class="section-title"><i class="fas fa-sliders-h"></i> VARIACIONES ESPECÍFICAS</div>
    <div class="variaciones-grid">
        <!-- MANGA -->
        <div class="variacion-item">
            <div class="variacion-header">
                <input type="checkbox" name="productos_prenda[][variantes][tiene_manga]" value="1" class="variacion-checkbox">
                <label class="variacion-label"><i class="fas fa-shirt"></i> Manga</label>
            </div>
            <div class="variacion-body">
                <div class="variacion-control">
                    <select name="productos_prenda[][variantes][tipo_manga_id]" class="variacion-select">
                        <option value="">Selecciona tipo...</option>
                        <option value="Corta">Corta</option>
                        <option value="Larga">Larga</option>
                        <option value="3/4">3/4</option>
                        <option value="Raglan">Raglan</option>
                        <option value="Campana">Campana</option>
                        <option value="Otra">Otra</option>
                    </select>
                    <input type="text" name="productos_prenda[][variantes][obs_manga]" placeholder="Observaciones..." class="variacion-input">
                </div>
            </div>
        </div>
        
        <!-- BOLSILLOS -->
        <div class="variacion-item">
            <div class="variacion-header">
                <input type="checkbox" name="productos_prenda[][variantes][tiene_bolsillos]" value="1" class="variacion-checkbox">
                <label class="variacion-label"><i class="fas fa-square"></i> Bolsillos</label>
            </div>
            <div class="variacion-body">
                <input type="text" name="productos_prenda[][variantes][obs_bolsillos]" placeholder="Ej: 4 bolsillos, con cierre..." class="variacion-input">
            </div>
        </div>
        
        <!-- BROCHE/BOTÓN -->
        <div class="variacion-item">
            <div class="variacion-header">
                <input type="checkbox" name="productos_prenda[][variantes][tiene_broche]" value="1" class="variacion-checkbox">
                <label class="variacion-label"><i class="fas fa-link"></i> Broche/Botón</label>
            </div>
            <div class="variacion-body">
                <select name="productos_prenda[][variantes][tipo_broche_id]" class="variacion-select">
                    <option value="">Seleccionar...</option>
                    <option value="Broche">Broche</option>
                    <option value="Botón">Botón</option>
                </select>
                <input type="text" name="productos_prenda[][variantes][obs_broche]" placeholder="Ej: Botones de madera..." class="variacion-input">
            </div>
        </div>
        
        <!-- REFLECTIVO -->
        <div class="variacion-item">
            <div class="variacion-header">
                <input type="checkbox" name="productos_prenda[][variantes][tiene_reflectivo]" value="1" class="variacion-checkbox">
                <label class="variacion-label"><i class="fas fa-star"></i> Reflectivo</label>
            </div>
            <div class="variacion-body">
                <input type="text" name="productos_prenda[][variantes][obs_reflectivo]" placeholder="Ej: En brazos y espalda..." class="variacion-input">
            </div>
        </div>
    </div>
</div>
```

## Estructura

### Contenedor Principal
- **Clase**: `producto-section`
- **Contiene**: Título + Grid de variaciones

### Título
- **Clase**: `section-title`
- **Icono**: `fas fa-sliders-h`
- **Texto**: "VARIACIONES ESPECÍFICAS"

### Grid de Variaciones
- **Clase**: `variaciones-grid`
- **Contiene**: 4 items (Manga, Bolsillos, Broche, Reflectivo)

### Cada Variación (Item)
- **Clase**: `variacion-item`
- **Estructura**:
  - `variacion-header` - Checkbox + Label
  - `variacion-body` - Controles (Select/Input)

### Variación: MANGA
- **Checkbox**: `tiene_manga`
- **Select**: `tipo_manga_id` (Corta, Larga, 3/4, Raglan, Campana, Otra)
- **Input**: `obs_manga` (Observaciones)

### Variación: BOLSILLOS
- **Checkbox**: `tiene_bolsillos`
- **Input**: `obs_bolsillos` (Descripción)

### Variación: BROCHE/BOTÓN
- **Checkbox**: `tiene_broche`
- **Select**: `tipo_broche_id` (Broche, Botón)
- **Input**: `obs_broche` (Observaciones)

### Variación: REFLECTIVO
- **Checkbox**: `tiene_reflectivo`
- **Input**: `obs_reflectivo` (Descripción)

## CSS Requerido

```css
/* Contenedor de variaciones */
.variaciones-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

/* Item de variación */
.variacion-item {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
    background: #f9f9f9;
    transition: all 0.3s ease;
}

.variacion-item:hover {
    border-color: #0066cc;
    box-shadow: 0 2px 8px rgba(0, 102, 204, 0.1);
}

/* Header de variación */
.variacion-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #0066cc;
}

/* Checkbox */
.variacion-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #0066cc;
}

/* Label */
.variacion-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #0066cc;
    cursor: pointer;
    margin: 0;
}

.variacion-label i {
    font-size: 1.1rem;
}

/* Body de variación */
.variacion-body {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Control de variación */
.variacion-control {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Select */
.variacion-select {
    padding: 0.6rem 0.8rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
    background-color: white;
    cursor: pointer;
    transition: all 0.2s;
}

.variacion-select:focus {
    border-color: #0066cc;
    box-shadow: 0 0 8px rgba(0, 102, 204, 0.2);
    outline: none;
}

/* Input */
.variacion-input {
    padding: 0.6rem 0.8rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.variacion-input:focus {
    border-color: #0066cc;
    box-shadow: 0 0 8px rgba(0, 102, 204, 0.2);
    outline: none;
}

/* Responsive */
@media (max-width: 768px) {
    .variaciones-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}
```

## Campos de Formulario

### Manga
```
Nombre: productos_prenda[][variantes][tiene_manga]
Valor: 1 (checkbox)

Nombre: productos_prenda[][variantes][tipo_manga_id]
Valores: Corta, Larga, 3/4, Raglan, Campana, Otra

Nombre: productos_prenda[][variantes][obs_manga]
Tipo: Texto libre
```

### Bolsillos
```
Nombre: productos_prenda[][variantes][tiene_bolsillos]
Valor: 1 (checkbox)

Nombre: productos_prenda[][variantes][obs_bolsillos]
Tipo: Texto libre
Placeholder: "Ej: 4 bolsillos, con cierre..."
```

### Broche/Botón
```
Nombre: productos_prenda[][variantes][tiene_broche]
Valor: 1 (checkbox)

Nombre: productos_prenda[][variantes][tipo_broche_id]
Valores: Broche, Botón

Nombre: productos_prenda[][variantes][obs_broche]
Tipo: Texto libre
Placeholder: "Ej: Botones de madera..."
```

### Reflectivo
```
Nombre: productos_prenda[][variantes][tiene_reflectivo]
Valor: 1 (checkbox)

Nombre: productos_prenda[][variantes][obs_reflectivo]
Tipo: Texto libre
Placeholder: "Ej: En brazos y espalda..."
```

## Iconos FontAwesome

- **Manga**: `fas fa-shirt`
- **Bolsillos**: `fas fa-square`
- **Broche**: `fas fa-link`
- **Reflectivo**: `fas fa-star`
- **Título**: `fas fa-sliders-h`

## Validación Backend

Según `StoreCotizacionRequest.php`:

```php
'productos.*.variantes.tipo_manga_id' => 'nullable|string',
'productos.*.variantes.obs_manga' => 'nullable|string',
'productos.*.variantes.obs_bolsillos' => 'nullable|string',
'productos.*.variantes.tipo_broche_id' => 'nullable|string',
'productos.*.variantes.obs_broche' => 'nullable|string',
'productos.*.variantes.tiene_bolsillos' => 'nullable|boolean|integer',
'productos.*.variantes.tiene_reflectivo' => 'nullable|boolean|integer',
'productos.*.variantes.obs_reflectivo' => 'nullable|string',
```

## Procesamiento Backend

**Servicio**: `PrendaService.php`
**Método**: `guardarVariantes()`
**Líneas**: 204-476

Procesa:
1. Manga (tipo + observaciones)
2. Bolsillos (checkbox + observaciones)
3. Broche (tipo + observaciones)
4. Reflectivo (checkbox + observaciones)
5. Crea modelo `VariantePrenda` con todos los datos

## Estado

✅ **RECUPERADO** - Sección completa lista para usar
✅ **HTML** - Código completo disponible
✅ **CSS** - Estilos incluidos
✅ **BACKEND** - Procesamiento funcional
✅ **VALIDACIÓN** - Reglas definidas

## Cómo Restaurar

1. Copiar el HTML de esta sección
2. Pegarlo en `create.blade.php` (líneas 1122-1186)
3. Asegurar que el CSS esté cargado
4. Probar en `http://servermi:8000/asesores/cotizaciones/prenda/crear`

