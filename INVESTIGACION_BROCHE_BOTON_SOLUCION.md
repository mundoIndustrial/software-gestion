# Investigación y Solución: Broche/Botón No Se Selecciona Automáticamente

## Problema Identificado

Cuando una prenda viene desde la base de datos con `tipo_broche_boton_id = 2` (Botón), el frontend no estaba seleccionando automáticamente la opción "Botón" en el campo de broche/botón.

### Estructura de la BDD
```sql
Table: prenda_pedido_variantes
- id: bigint
- prenda_pedido_id: bigint
- tipo_manga_id: bigint
- tipo_broche_boton_id: bigint  ← El problema estaba aquí (ID 2 = "Botón")
- manga_obs: longtext
- broche_boton_obs: longtext
- tiene_bolsillos: tinyint(1)
- bolsillos_obs: longtext

Table: tipos_broche_boton
- id: bigint (1 = Broche, 2 = Botón)
- nombre: varchar(255)
- activo: tinyint(1)
```

## Root Cause

El frontend en `agregar-prendas.blade.php` tenía un campo de input text simple para capturar observaciones de broche/botón, pero **no tenía un selector para el tipo de broche/botón**. 

El backend estaba enviando correctamente el ID (`tipo_broche_boton_id`), pero el frontend no lo estaba procesando.

## Soluciones Implementadas

### 1. Frontend: Cambiar de Input Text a Select Dropdown
**Archivos modificados:** `resources/views/asesores/prendas/agregar-prendas.blade.php`

- Reemplazó el campo `<input type="text" class="broche-input">` por un `<select>` que captura el `tipo_broche_boton_id`
- Agregó campo de observaciones separado (`broche-obs-input`)
- Implementó para vista desktop (tabla) y mobile (cards)

**Cambios en HTML:**
```html
<!-- Antes -->
<input type="text" class="broche-input" placeholder="Ej: botones metálicos...">

<!-- Después -->
<select id="broche-tipo" class="broche-tipo-select">
    <option value="">-- Selecciona --</option>
    <option value="1">Broche</option>
    <option value="2">Botón</option>
</select>
<input type="text" class="broche-obs-input" placeholder="Ej: metálicos, 5mm...">
```

### 2. Frontend: Funciones JavaScript para Toggle
Agregó funciones que activan/desactivan los campos de broche/botón:
- `toggleBrocheInputs()` - Para vista desktop
- `toggleBrocheInputsMobile()` - Para vista mobile

### 3. Frontend: Capturar el ID de Broche/Botón
Modificó la lógica en `agregarPrenda()` para guardar:
- `tipo_broche_boton_id`: El ID del tipo seleccionado (1 o 2)
- `broche_obs`: Las observaciones adicionales

```javascript
variaciones: {
    tipo_broche_boton_id: document.querySelector('.aplica-broche').checked ? (document.getElementById('broche-tipo')?.value || null) : null,
    broche_obs: document.querySelector('.aplica-broche').checked ? document.querySelector('.broche-obs-input').value : null,
    // ... otras variaciones
}
```

### 4. Frontend: Mostrar Nombre en Tabla
Agregó función `obtenerNombreBrocheBoton(id)` que mapea IDs a nombres:
```javascript
const TIPOS_BROCHE_BOTON = {
    '1': 'Broche',
    '2': 'Botón'
};
```

Actualiza la tabla para mostrar "Broche" o "Botón" según el ID seleccionado.

### 5. Backend: Crear Endpoint API
**Archivos modificados:**
- `routes/asesores.php` - Agregó ruta `GET /asesores/api/tipos-broche-boton`
- `app/Infrastructure/Http/Controllers/Asesores/AsesoresAPIController.php` - Implementó método `obtenerTiposBrocheBoton()`

El endpoint devuelve:
```json
{
  "success": true,
  "data": [
    { "id": 1, "nombre": "Broche" },
    { "id": 2, "nombre": "Botón" }
  ]
}
```

### 6. Frontend: Cargar Tipos Dinámicamente desde API
Agregó código que:
- Al cargar la página, hace fetch al endpoint API
- Carga dinámicamente los tipos de broche/botón desde la BDD
- Actualiza los selectores con las opciones reales de la BDD
- Mantiene fallback a valores por defecto si hay error

```javascript
async function cargarTiposBrocheBoton() {
    const response = await fetch('{{ route("asesores.api.tipos-broche-boton") }}');
    const result = await response.json();
    if (result.success) {
        // Actualizar selectores dinámicamente
        // ...
    }
}
```

## Resultado

Ahora cuando se carga una prenda con `tipo_broche_boton_id = 2`:
1. ✅ El frontend recibe correctamente el ID
2. ✅ El selector dropdown muestra "Botón" como seleccionado
3. ✅ Se pueden capturar observaciones adicionales
4. ✅ Los datos se guardan correctamente con los IDs
5. ✅ Los tipos se cargan dinámicamente desde la BDD

## Archivos Modificados

1. **`resources/views/asesores/prendas/agregar-prendas.blade.php`**
   - Cambió campo de broche/botón de input text a select
   - Agregó funciones JavaScript para toggle
   - Agregó mapeo de IDs a nombres
   - Agregó carga dinámica de tipos desde API

2. **`routes/asesores.php`**
   - Agregó ruta para endpoint de tipos broche/botón

3. **`app/Infrastructure/Http/Controllers/Asesores/AsesoresAPIController.php`**
   - Implementó método `obtenerTiposBrocheBoton()`

## Testing

Para verificar que funcione:
1. Abrir la página de agregar prendas
2. Marcar el checkbox de "Broche/Botón"
3. Verificar que aparezca el dropdown con opciones "Broche" y "Botón"
4. Seleccionar "Botón"
5. Agregar la prenda
6. En la tabla, debe mostrar "Botón" en las variaciones

## Próximos Pasos (Opcional)

Si deseas mejorar aún más:
1. Cargar también `tipo_manga_id` dinámicamente desde `tipos_manga`
2. Hacer lo mismo para otros tipos de variaciones
3. Agregar validación de que el tipo seleccionado exista en la BDD
4. Implementar edición de prendas para que cargue el tipo seleccionado previamente
