# Sistema de Carga de Datos de Prenda para Edición

## Resumen Ejecutivo

Se ha implementado un sistema completo para cargar datos frescos directamente desde la base de datos cuando el usuario edita una prenda del pedido. Este sistema utiliza **ÚNICAMENTE las 7 tablas transaccionales** del modelo de prendas, garantizando consistencia y evitando dependencias de catálogos externos.

---

##  Modelo de Datos Utilizado

### Tablas Transaccionales (Pedido)

| Tabla | Responsabilidad |
|-------|-----------------|
| `prendas_pedido` | Información base de la prenda |
| `prenda_pedido_variantes` | Características (manga, broche, bolsillos) |
| `prenda_pedido_colores_telas` | Relación prenda → color × tela |
| `prenda_fotos_pedido` | Imágenes generales de la prenda |
| `prenda_fotos_tela_pedido` | Imágenes de cada combinación tela + color |
| `pedidos_procesos_prenda_detalles` | Procesos aplicados a la prenda |
| `pedidos_procesos_imagenes` | Imágenes de cada proceso |

### Tablas Maestras Referenciadas (Catálogos - Solo lectura)

| Tabla | Uso |
|-------|-----|
| `colores_prenda` | Nombres de colores |
| `telas_prenda` | Nombres y referencias de telas |
| `tipos_manga` | Tipos de manga disponibles |
| `tipos_broche_boton` | Tipos de broche disponibles |
| `tipos_procesos` | Nombres de procesos |

---

## 🏗️ Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────┐
│                 FLUJO DE EDICIÓN DE PRENDA              │
└─────────────────────────────────────────────────────────┘

    1. Usuario hace clic en botón "Editar" prenda
             ↓
    2. prenda-card-handlers.js detecta evento
             ↓
    3. Llama a abrirEditarPrendaModal(prenda, idx, pedidoId)
             ↓
    4. prenda-card-editar-simple.js:abrirEditarPrendaModal()
             ↓
    5. Verifica que tenga pedidoId + prenda.id
             ↓
    6. Hace fetch a endpoint:
       GET /asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos
             ↓
    7. PedidosProduccionViewController::obtenerDatosUnaPrenda()
       ├─ 1. Valida prenda existe y pertenece al pedido
       ├─ 2. Obtiene imágenes desde prenda_fotos_pedido
       ├─ 3. Obtiene telas desde prenda_pedido_colores_telas
       ├─ 4. Obtiene imágenes de telas desde prenda_fotos_tela_pedido
       ├─ 5. Obtiene variantes desde prenda_pedido_variantes
       ├─ 6. Obtiene procesos desde pedidos_procesos_prenda_detalles
       ├─ 7. Obtiene imágenes de procesos desde pedidos_procesos_imagenes
       └─ 8. Devuelve JSON con todos los datos
             ↓
    8. Si éxito: usa datos frescos de BD
       Si falla: usa datos de memoria (fallback)
             ↓
    9. Carga datos en modal de edición
             ↓
    10. Usuario edita y guarda
```

---

## 💻 Componentes Implementados

### 1. Backend - Controller

**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionViewController.php`

**Método:** `obtenerDatosUnaPrenda($pedidoId, $prendaId)`

**Responsabilidades:**
-  Validar que prenda existe y pertenece al pedido
-  Consultar `prendas_pedido` para datos base
-  Consultar `prenda_fotos_pedido` para imágenes
-  Consultar `prenda_pedido_colores_telas` con JOIN a catálogos
-  Consultar `prenda_fotos_tela_pedido` para imágenes de telas
-  Consultar `prenda_pedido_variantes` con JOIN a catálogos
-  Consultar `pedidos_procesos_prenda_detalles` con imágenes asociadas
-  Parsear JSON fields (cantidad_talla, genero, ubicaciones, etc.)
-  Normalizar rutas de imágenes al formato `/storage/...`
-  Devolver estructura JSON completa

**Respuesta JSON:**
```json
{
  "success": true,
  "prenda": {
    "id": 3418,
    "prenda_pedido_id": 3418,
    "nombre_prenda": "RET",
    "nombre": "RET",
    "descripcion": "...",
    "origen": "bodega",
    "de_bodega": true,
    "imagenes": ["/storage/prendas/...", ...],
    "telasAgregadas": [
      {
        "tela": "Drill",
        "color": "Azul",
        "referencia": "DR-001",
        "imagenes": ["/storage/telas/...", ...]
      }
    ],
    "tallas": {"XS": 2, "S": 3, "M": 5, ...},
    "generos": ["Dama", "Caballero"],
    "variantes": [
      {
        "manga": "Corta",
        "obs_manga": "...",
        "tiene_bolsillos": true,
        "obs_bolsillos": "...",
        "broche": "Botones",
        "obs_broche": "..."
      }
    ],
    "procesos": [
      {
        "id": 101,
        "tipo_id": 5,
        "tipo_nombre": "Bordado",
        "ubicaciones": ["Pecho", "Espalda"],
        "observaciones": "...",
        "tallas_dama": ["XS", "S", "M"],
        "tallas_caballero": ["M", "L", "XL"],
        "estado": "APROBADO",
        "imagenes": ["/storage/procesos/...", ...],
        "datos_adicionales": {}
      }
    ]
  }
}
```

---

### 2. Ruta Web

**Archivo:** `routes/web.php` (Línea 519)

```php
Route::get('/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos', 
  [PedidosProduccionViewController::class, 'obtenerDatosUnaPrenda'])
  ->name('pedidos-produccion.prenda.datos');
```

**Acceso:** 
- URL: `GET /asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos`
- Autenticación: Requiere estar autenticado como asesor
- Middleware: Aplica middleware de asesores automáticamente

---

### 3. Frontend - JavaScript

**Archivo:** `public/js/componentes/prenda-card-editar-simple.js`

**Función:** `abrirEditarPrendaModal(prenda, prendaIndex, pedidoId)` (ahora async)

**Cambios:**
```javascript
// Antes: era síncrono, usaba datos de memoria
function abrirEditarPrendaModal(prenda, ...)

// Ahora: es asíncrono, consulta BD primero
async function abrirEditarPrendaModal(prenda, ...) {
  // 1. Si tiene IDs, fetch a endpoint
  // 2. Si éxito: usa datos frescos
  // 3. Si falla: fallback a memoria
  // 4. Abre modal con datos (frescos o memoria)
}
```

**Lógica:**
1. Verifica que tenga `pedidoId` y `prenda.id`
2. Construye URL: `/asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos`
3. Hace fetch con manejo de errores
4. Si respuesta OK y tiene datos válidos: actualiza `prendaEditable`
5. Si falla: continúa con datos de memoria (degradación elegante)
6. Abre modal con datos disponibles

---

## 🔍 Debugging y Logging

### Backend Logs

En `storage/logs/laravel.log`:

```
[PRENDA-DATOS] Cargando datos de prenda para edición
  pedido_id: 12345
  prenda_id: 3418

[PRENDA-DATOS] Imágenes de prenda encontradas
  prenda_id: 3418
  cantidad: 5

[PRENDA-DATOS] Telas encontradas
  cantidad: 2

[PRENDA-DATOS] Variantes encontradas
  cantidad: 1

[PRENDA-DATOS] Procesos encontrados
  cantidad: 3

[PRENDA-DATOS] Datos compilados exitosamente
  imagenes_count: 5
  telas_count: 2
  procesos_count: 3
  variantes_count: 1
```

### Frontend Console

En DevTools → Console:

```javascript
🖊️  [EDITAR-MODAL] Abriendo prenda para editar
   Prenda: {id: 3418, nombre_prenda: "RET", ...}
   Pedido ID: 12345
   Obteniendo datos frescos de la BD para prenda 3418...
    Datos obtenidos desde BD: {id: 3418, imagenes: [...], ...}
```

### Network Tab

En DevTools → Network:

```
GET /asesores/pedidos-produccion/12345/prenda/3418/datos
Status: 200 OK
Response: {...datos JSON...}
Size: ~5KB (dependiendo de cantidad de imágenes)
```

---

## 🧪 Cómo Probar

### Requisitos
- Tener un pedido guardado en BD con al menos 1 prenda
- La prenda debe tener imágenes, telas y procesos

### Pasos

1. **Abrir DevTools**
   ```
   F12 → Console + Network
   ```

2. **Navegar a pedido de producción**
   ```
   /asesores/pedidos-produccion/12345
   ```

3. **Hacer clic en "Editar" una prenda**
   - Observar Console para logs
   - Observar Network para request HTTP

4. **Verificar Console**
   ```javascript
   // Debe aparecer:
   🖊️  [EDITAR-MODAL] Abriendo prenda para editar
   Obteniendo datos frescos de la BD para prenda 3418...
    Datos obtenidos desde BD: {...}
   ```

5. **Verificar Network**
   ```
   GET /asesores/pedidos-produccion/12345/prenda/3418/datos
   Status: 200
   ```

6. **Verificar que Modal se carga**
   - Las imágenes deben aparecer
   - Las telas deben aparecer con sus combinaciones
   - Los procesos deben listar correctamente

7. **Verificar Laravel Log**
   ```bash
   tail -f storage/logs/laravel.log | grep PRENDA-DATOS
   ```

---

##  Casos de Uso Cubiertos

| Caso | Comportamiento |
|------|----------------|
| Prenda con imágenes |  Se cargan todas desde BD |
| Prenda con múltiples telas |  Se cargan todas con sus combinaciones |
| Prenda con procesos |  Se cargan procesos con imágenes |
| Prenda sin imágenes |  Se devuelve array vacío |
| Prenda no existe |  Error 404 + JSON error |
| Prenda no pertenece a pedido |  Error 404 + JSON error |
| Falta pedidoId |  Fallback a datos de memoria |
| Falla endpoint BD | ⚠️ Fallback a datos de memoria |

---

##  Beneficios Logrados

 **Datos siempre frescos** - Cada edición consulta BD directamente
 **Integridad de datos** - No hay discrepancias entre memoria y BD
 **Imagen completa** - Se obtienen todas las relaciones (telas, procesos, variantes)
 **Fallback seguro** - Si falla, tiene datos de memoria
 **Debugging claro** - Logs detallados en ambos lados
 **Solo 7 tablas** - Sin dependencias de módulos externos
 **Catálogos correctos** - JOIN a tablas maestras solo para nombres

---

## 📌 Restricciones Mantenidas

 **NO** se usan reflectivos u otros módulos
 **NO** se consultan tablas externas al modelo de prendas
 **NO** se duplican datos de catálogos
 **SÍ** se usan las 7 tablas transaccionales
 **SÍ** se referencian catálogos para nombres solamente

---

## 🚀 Próximas Optimizaciones (Opcional)

1. **Caché local**
   - Guardar datos fetched en sessionStorage
   - Reutilizar si se edita la misma prenda múltiples veces

2. **Validación frontend**
   - Verificar que `prenda.id` sea número válido antes de fetch
   - Validar estructura de respuesta

3. **Sincronización batch**
   - Si usuario edita múltiples prendas, hacer fetch paralelo
   - Usar Promise.all() para paralelizar

4. **Migración de datos antiguas**
   - Script para llenar `prenda_fotos_pedido` desde `imagenes_path` JSON
   - Garantiza consistencia en prendas antiguas

---

## 📝 Notas Técnicas

- El método usa `\DB::table()` y no Eloquent para precisión de tablas
- Todos los soft deletes se respetan (`where('deleted_at', null)`)
- Las rutas de imágenes se normalizan al formato `/storage/{path}`
- Los JSON fields se parsean correctamente (array o string)
- Logging incluye información de debugging en todos los pasos

