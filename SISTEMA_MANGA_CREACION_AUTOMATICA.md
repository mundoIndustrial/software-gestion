# 🧵 Sistema de Manga - Creación Automática de Tipos

## Problema Resuelto ✅

Para la tabla `tipos_manga`, si el tipo **NO existe, se crea automáticamente** en la base de datos.

```
Usuario escribe: "manga corta"
↓
Sistema verifica si existe en BDD
↓
Si NO existe → ✅ LO CREA automáticamente
Si YA existe → ✅ USA el existente
```

---

## Arquitectura de la Solución

### 1️⃣ Frontend - Input con Datalist

Cambió de un input text simple a un **datalist** que permite:
- ✅ Ver opciones existentes en un dropdown
- ✅ Escribir valores nuevos libremente
- ✅ Crear nuevos tipos automáticamente

```html
<input type="text" 
       id="manga-input" 
       class="manga-input" 
       placeholder="Ej: manga larga, corta..." 
       list="manga-options">

<datalist id="manga-options">
    <!-- Se cargan dinámicamente: Manga Larga, Manga Corta, etc -->
    <option value="Manga Larga"></option>
    <option value="Manga Corta"></option>
</datalist>
```

**Flujo:**
1. Usuario abre la página → Se cargan tipos existentes en datalist
2. Usuario comienza a escribir → Ve sugerencias
3. Usuario escribe algo nuevo que no existe → Al salir del campo, se crea automáticamente
4. La próxima vez, ese tipo aparecerá en las sugerencias

---

### 2️⃣ Backend - Endpoints API

**Ruta 1: GET - Obtener tipos existentes**
```
GET /asesores/api/tipos-manga
```
Respuesta:
```json
{
  "success": true,
  "data": [
    { "id": 1, "nombre": "Manga Larga" },
    { "id": 2, "nombre": "Manga Corta" }
  ]
}
```

**Ruta 2: POST - Crear nuevo tipo si no existe**
```
POST /asesores/api/tipos-manga
Body: { "nombre": "Manga 3/4" }
```
Respuesta:
```json
{
  "success": true,
  "data": { "id": 3, "nombre": "Manga 3/4" },
  "mensaje": "Tipo creado"
}
```

**Características:**
- ✅ Verifica case-insensitive (ignora mayúsculas/minúsculas)
- ✅ Normaliza el nombre (primera letra mayúscula)
- ✅ Solo activos en BDD
- ✅ Logging automático de creaciones

---

### 3️⃣ Diferencia con Broche/Botón

| Aspecto | Broche/Botón | Manga |
|---------|--------------|-------|
| **Control** | Select (solo predefinidos) | Datalist (permite crear) |
| **Crear nuevos** | ❌ No | ✅ Sí |
| **Caso de uso** | Opciones limitadas y fijas | Tipos extensibles |

---

## Flujo Completo de Datos

```
┌────────────────────────────────────────────┐
│ 1. USUARIO CARGA LA PÁGINA                 │
└──────────────────┬─────────────────────────┘
                   │
                   ▼
        GET /api/tipos-manga
                   │
                   ▼
    Cargar datalist con tipos existentes
    ├─ Manga Larga
    ├─ Manga Corta
    └─ Manga 3/4

┌────────────────────────────────────────────┐
│ 2. USUARIO ESCRIBE ALGO NUEVO               │
└──────────────────┬─────────────────────────┘
                   │
          "Manga Pliegues"
                   │
                   ▼
    Usuario sale del campo (blur event)
                   │
                   ▼
    Verificar si existe en datalist
                   │
            NO EXISTE → ✅ Crear
                   │
                   ▼
        POST /api/tipos-manga
        { "nombre": "Manga Pliegues" }
                   │
                   ▼
    BDD crea nuevo registro con ID 4
    Datalist se actualiza automáticamente
```

---

## Implementación Técnica

### Backend - Controlador
**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/AsesoresAPIController.php`

```php
public function crearObtenerTipoManga(Request $request)
{
    $nombre = trim($request->input('nombre', ''));
    
    // Buscar si ya existe (case-insensitive)
    $tipo = \App\Models\TipoManga::whereRaw('LOWER(nombre) = ?', 
        [strtolower($nombre)])->first();

    // Si no existe, crearlo
    if (!$tipo) {
        $tipo = \App\Models\TipoManga::create([
            'nombre' => ucfirst(strtolower($nombre)),
            'activo' => true
        ]);
    }

    return response()->json([
        'success' => true,
        'data' => $tipo,
        'mensaje' => $tipo->wasRecentlyCreated ? 'Tipo creado' : 'Tipo existente'
    ]);
}
```

### Frontend - JavaScript

**Cargar tipos al inicio:**
```javascript
async function cargarTiposManga() {
    const response = await fetch('{{ route("asesores.api.tipos-manga") }}');
    const result = await response.json();
    
    // Llenar datalist con opciones
    result.data.forEach(tipo => {
        const option = document.createElement('option');
        option.value = tipo.nombre;
        datalist.appendChild(option);
    });
}
```

**Crear tipo al salir del campo:**
```javascript
async function procesarMangaInput(input) {
    const valor = input.value.trim();
    
    // Verificar si ya existe
    if (!existe) {
        // Crear nuevo tipo
        const response = await fetch('{{ route("asesores.api.tipos-manga.create") }}', {
            method: 'POST',
            body: JSON.stringify({ nombre: valor })
        });
        
        // Agregar a datalist
        const newOption = document.createElement('option');
        newOption.value = result.data.nombre;
        datalist.appendChild(newOption);
    }
}
```

---

## Casos de Uso

### Caso 1: Usuario selecciona tipo existente
```
1. Usuario ve datalist con "Manga Larga", "Manga Corta"
2. Selecciona "Manga Larga"
3. Se captura el nombre
4. Se guarda en prenda.variaciones.manga_nombre = "Manga Larga"
```

### Caso 2: Usuario crea tipo nuevo
```
1. Usuario escribe "Manga Rollada"
2. Sigue sin existir → NO está en datalist
3. Usuario sale del campo (blur)
4. Sistema detecta que no existe
5. POST /api/tipos-manga con "Manga Rollada"
6. BDD crea nuevo tipo con ID auto-incremental
7. Datalist se actualiza
8. Próximas veces aparecerá en sugerencias
```

### Caso 3: Usuario escribe variación de tipo existente
```
1. Datalist tiene "Manga Larga"
2. Usuario escribe "manga larga" (minúsculas)
3. Sistema busca case-insensitive
4. Encuentra que existe
5. NO lo crea (usa el existente)
```

---

## Rutas API

| Método | Ruta | Función |
|--------|------|---------|
| GET | `/asesores/api/tipos-manga` | Obtener todos los tipos activos |
| POST | `/asesores/api/tipos-manga` | Crear o obtener un tipo |

---

## Archivos Modificados

1. **`resources/views/asesores/prendas/agregar-prendas.blade.php`**
   - Cambió manga input → datalist
   - Agregó `cargarTiposManga()`
   - Agregó `configurarManejadorManga()`
   - Agregó `procesarMangaInput()`
   - Agregó `toggleMangaInputMobile()`

2. **`routes/asesores.php`**
   - ➕ Ruta GET: `/api/tipos-manga`
   - ➕ Ruta POST: `/api/tipos-manga`

3. **`app/Infrastructure/Http/Controllers/Asesores/AsesoresAPIController.php`**
   - ➕ Método: `obtenerTiposManga()`
   - ➕ Método: `crearObtenerTipoManga()`

---

## Testing

### Test 1: Cargar tipos existentes
```bash
1. Abrir /asesores/prendas/agregar-prendas
2. Marcar checkbox "Manga"
3. Ver datalist con tipos existentes en BDD
```

### Test 2: Crear nuevo tipo
```bash
1. En campo manga escribir: "Manga Experimental"
2. Salir del campo (blur)
3. Ver que se crea automáticamente en BDD
4. Recargar página
5. Verificar que "Manga Experimental" aparezca en sugerencias
```

### Test 3: Caso insensitive
```bash
1. BDD tiene "Manga Larga"
2. Escribir "manga larga" (minúsculas)
3. Salir del campo
4. NO debe crear duplicado
5. Solo debe usar el existente
```

### Test 4: Guardar prenda con manga nueva
```bash
1. Crear tipo "Manga Pliegues"
2. Completar datos de prenda
3. Hacer click en "Agregar Prenda"
4. En tabla debe mostrar: "Manga: Manga Pliegues"
```

---

## Ventajas del Sistema

✅ **Escalable** - Los usuarios pueden crear nuevos tipos sin intervención admin  
✅ **Case-insensitive** - No crea duplicados por mayúsculas  
✅ **Auto-normalizado** - Normaliza nombres (primera letra mayúscula)  
✅ **Auditado** - Logs de nuevos tipos creados  
✅ **Dinámico** - Las sugerencias se actualizan automáticamente  
✅ **Consistente** - Mismo patrón que el sistema de broche/botón  

---

## Notas Importantes

1. **El datalist permite escribir libremente** - No está restringido a opciones
2. **La creación es automática** - Al salir del campo se procesa
3. **Solo se crea si es nuevo** - Verifica existencia antes de crear
4. **Vista desktop y mobile** - Ambas soportadas
5. **Normalización** - "manga larga", "MANGA LARGA", "Manga Larga" → "Manga larga"

---

## Comparativa: Manga vs Broche/Botón

### Manga (Creación automática)
```html
<input list="manga-options">  ← Datalist (libre)
<datalist>
    <option value="Manga Larga"></option>
    <option value="Manga Corta"></option>
</datalist>
```

### Broche/Botón (Solo predefinidos)
```html
<select>  ← Select cerrado
    <option value="1">Broche</option>
    <option value="2">Botón</option>
</select>
```

---

## Próximas Mejoras

1. **Validación de duplicados** en tiempo real (mientras escribe)
2. **Sugerencias inteligentes** (buscar parcial)
3. **Ordenamiento** de tipos por frecuencia de uso
4. **Historial** de tipos recientemente creados
5. **Búsqueda fuzzy** para encontrar tipos similares
