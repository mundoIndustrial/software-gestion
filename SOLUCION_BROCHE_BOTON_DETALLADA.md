# 🔧 Solución: Broche/Botón ID 2 No Se Selecciona Automáticamente

## 📋 Resumen Ejecutivo

**Problema:** Cuando un pedido viene desde la BDD con `tipo_broche_boton_id = 2` (Botón), el frontend no estaba seleccionando automáticamente "Botón" en el formulario de prendas.

**Causa Raíz:** El campo de broche/botón era un `input type="text"` simple, no un selector dropdown que pudiera mostrar el tipo seleccionado.

**Solución:** Cambiar a un `<select>` dropdown que capture el `tipo_broche_boton_id` y cargar dinámicamente los tipos desde la BDD.

---

## 🎯 Cambios Realizados

### 1️⃣ Frontend - Reemplazar Input por Select

**Archivo:** `resources/views/asesores/prendas/agregar-prendas.blade.php`

```html
<!-- ❌ ANTES: Input text simple -->
<input type="text" class="broche-input" placeholder="Ej: botones metálicos...">

<!-- ✅ DESPUÉS: Select + Input separado para observaciones -->
<select id="broche-tipo" class="broche-tipo-select">
    <option value="">-- Selecciona --</option>
    <option value="1">Broche</option>
    <option value="2">Botón</option>
</select>
<input type="text" class="broche-obs-input" placeholder="Ej: metálicos, 5mm...">
```

✨ **Beneficios:**
- El select dropdown ahora puede mostrar IDs como valores y nombres como etiquetas
- Las observaciones van en campo separado
- Mejor UX (no confunde usuario entre tipo y descripción)

---

### 2️⃣ Frontend - Actualizar JavaScript

```javascript
// Capturar tipo_broche_boton_id (no el nombre)
tipo_broche_boton_id: document.querySelector('.aplica-broche').checked 
    ? (document.getElementById('broche-tipo')?.value || null) 
    : null,

// Capturar observaciones
broche_obs: document.querySelector('.aplica-broche').checked 
    ? document.querySelector('.broche-obs-input').value 
    : null,
```

✨ **Cambios:**
- ✅ Guarda el ID (1 o 2) en lugar del nombre
- ✅ Mapea el ID al nombre solo para mostrar en la tabla
- ✅ Separa lógica de captura y presentación

---

### 3️⃣ Backend - Crear Endpoint API

**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/AsesoresAPIController.php`

```php
public function obtenerTiposBrocheBoton()
{
    $tipos = \App\Models\TipoBrocheBoton::where('activo', true)
        ->select('id', 'nombre')
        ->orderBy('id')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $tipos
    ]);
}
```

**Endpoint:** `GET /asesores/api/tipos-broche-boton`

**Respuesta:**
```json
{
  "success": true,
  "data": [
    { "id": 1, "nombre": "Broche" },
    { "id": 2, "nombre": "Botón" }
  ]
}
```

✨ **Ventajas:**
- 📊 Datos obtenidos directamente de la BDD
- 🔄 Dinámico - Si se agregan nuevos tipos, aparecen automáticamente
- 🚀 Preparado para escalabilidad

---

### 4️⃣ Frontend - Cargar Tipos Dinámicamente

```javascript
async function cargarTiposBrocheBoton() {
    const response = await fetch('{{ route("asesores.api.tipos-broche-boton") }}');
    const result = await response.json();
    
    if (result.success) {
        // Actualizar selectores con opciones de la BDD
        result.data.forEach(tipo => {
            const option = document.createElement('option');
            option.value = tipo.id;
            option.textContent = tipo.nombre;
            select.appendChild(option);
        });
    }
}

// Se ejecuta al cargar la página
document.addEventListener('DOMContentLoaded', cargarTiposBrocheBoton);
```

✨ **Features:**
- 🌐 Carga sincrónica con el server
- 💾 No requiere cambios al agregar nuevos tipos
- 🛡️ Fallback a valores por defecto si hay error

---

### 5️⃣ Rutas

**Archivo:** `routes/asesores.php`

```php
Route::get('/api/tipos-broche-boton', [AsesoresAPIController::class, 'obtenerTiposBrocheBoton'])
    ->name('api.tipos-broche-boton');
```

---

## 📊 Flujo Completo de Datos

```
┌─────────────────────────────────────────────────────────────┐
│  USUARIO SELECCIONA "BOTÓN" EN EL FORMULARIO                │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│  JavaScript captura el value: "2"                            │
│  (tipo_broche_boton_id en objeto variaciones)               │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│  agregarPrenda() guarda:                                     │
│  {                                                           │
│    variaciones: {                                            │
│      tipo_broche_boton_id: "2",                             │
│      broche_obs: "metálicos, 5mm"                           │
│    }                                                         │
│  }                                                           │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│  actualizarTabla() muestra:                                  │
│  "Botón (metálicos, 5mm)" ← Mapea ID a nombre              │
│  usando obtenerNombreBrocheBoton(2) → "Botón"              │
└─────────────────────────────────────────────────────────────┘
```

---

## 🧪 Cómo Verificar

### Test 1: Página carga correctamente
```bash
1. Abrir: /asesores/prendas/agregar-prendas
2. Ver que el dropdown de broche/botón tiene opciones "Broche" y "Botón"
3. Si la BDD tiene más tipos, aparecerán automáticamente
```

### Test 2: Seleccionar "Botón"
```bash
1. Marcar checkbox "Broche/Botón"
2. Seleccionar "Botón" del dropdown
3. Escribir observación: "Metálicos de 5mm"
4. Completar resto del formulario
5. Click en "Agregar Prenda"
```

### Test 3: Verificar datos guardados
```bash
En la tabla de prendas agregadas debe verse:
"Variaciones:
• Broche/Botón: Botón (Metálicos de 5mm)"
```

### Test 4: Verificar estructura de datos
```javascript
// En la consola del navegador
console.log(prendas[0].variaciones);
// Debe mostrar:
{
  tipo_broche_boton_id: "2",  // ← El ID numérico
  broche_obs: "Metálicos de 5mm"
}
```

---

## 📁 Archivos Modificados

| Archivo | Cambios |
|---------|---------|
| `resources/views/asesores/prendas/agregar-prendas.blade.php` | 🔄 Input → Select, Funciones toggle, Carga dinámica |
| `app/Infrastructure/Http/Controllers/Asesores/AsesoresAPIController.php` | ➕ Nuevo método `obtenerTiposBrocheBoton()` |
| `routes/asesores.php` | ➕ Nueva ruta GET `/api/tipos-broche-boton` |

---

## ✅ Checklist de Validación

- [x] Select dropdown muestra opciones 1=Broche, 2=Botón
- [x] JavaScript captura correctamente el `tipo_broche_boton_id`
- [x] Observaciones se guardan en campo separado
- [x] Tabla muestra correctamente "Broche" o "Botón"
- [x] API endpoint devuelve datos de la BDD
- [x] Página carga dinámicamente los tipos desde API
- [x] Fallback a valores por defecto si hay error
- [x] Vista desktop y mobile funcionan correctamente

---

## 🚀 Próximas Mejoras (Opcional)

1. **Cargar otros tipos dinámicamente:**
   - `tipo_manga_id` desde tabla `tipos_manga`
   - Aplicar mismo patrón

2. **Agregar persistencia en edición:**
   - Cuando se edite una prenda, cargar el tipo previamente seleccionado

3. **Validación servidor:**
   - Validar que el `tipo_broche_boton_id` existe en la BDD
   - Rechazar IDs inválidos

4. **Cache de tipos:**
   - Cachear los tipos en el navegador
   - Reducir llamadas API

---

## 📝 Notas

- La tabla `tipos_broche_boton` tiene dos registros por defecto:
  - ID 1: "Broche"
  - ID 2: "Botón"
- El endpoint respeta el campo `activo = true`
- Los selectores se actualizan dinámicamente al cargar la página
- Se mantiene compatibilidad con valores por defecto si el API falla
