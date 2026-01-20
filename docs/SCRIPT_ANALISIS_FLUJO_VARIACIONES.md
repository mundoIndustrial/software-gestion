#  Script de Análisis: ¿Por qué NO se guardan variaciones y observaciones?

##  Resumen Ejecutivo

Cuando envías un pedido con prendas que tienen **variaciones** (manga, broche, bolsillos, reflectivo) y **observaciones**, los datos **DEBEN** fluir a través de estos puntos:

1. **Frontend**: `gestion-items-pedido.js` → Prepara objeto con `variaciones`
2. **Frontend**: `api-pedidos-editable.js` → Envía JSON al backend
3. **Backend**: `CrearPedidoEditableController` → Recibe y extrae datos
4. **Backend**: `PedidoPrendaService` → Guarda en BD
5. **Database**: `prendas_pedido` → Columnas: `tipo_manga_id`, `tipo_broche_id`, `tiene_bolsillos`, `tiene_reflectivo`, `manga_obs`, `bolsillos_obs`, `broche_obs`, `reflectivo_obs`

---

## 🚀 PASO 1: Verificar que el FRONTEND envía las variaciones

### 📍 Archivo: `resources/js/components/gestion-items-pedido.js` (líneas 1049-1164)

```javascript
// El objeto que se prepara tiene esta estructura:
const item = {
    prenda: "nombre de la prenda",
    descripcion: "detalles",
    telas: [ /* array de telas */ ],
    variaciones: {
        manga: {tipo: "corta", observacion: "con puño"},
        broche: {tipo: "cierre", observacion: ""},
        bolsillos: {tipo: true, observacion: "bolsillos de pecho"},
        reflectivo: {tipo: true, observacion: "3 franjas"}
    },
    //  PROBLEMA 1: Se envían también a nivel superior
    obs_manga: "con puño",
    obs_bolsillos: "bolsillos de pecho", 
    obs_broche: "",
    obs_reflectivo: "3 franjas"
}
```

** VERIFICACIÓN:**
```javascript
// En consola del navegador, después de preparar item:
console.log("Variaciones enviadas:", item.variaciones);
console.log("Observaciones nivel superior:", {
    obs_manga: item.obs_manga,
    obs_bolsillos: item.obs_bolsillos,
    obs_broche: item.obs_broche,
    obs_reflectivo: item.obs_reflectivo
});
```

---

## 🚀 PASO 2: Verificar que se ENVÍA al backend correctamente

### 📍 Archivo: `resources/js/components/api-pedidos-editable.js` (línea ~132)

```javascript
fetch(`/asesores/pedidos-editable/crear`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        items: [
            {
                prenda: "...",
                variaciones: { /* object con manga, broche, etc */ },
                obs_manga: "...",
                obs_bolsillos: "...",
                // etc
            }
        ]
    })
})
```

** VERIFICACIÓN:**
Abrir DevTools → Network → Buscar `/asesores/pedidos-editable/crear` → Ver request body en `Request` tab:

```json
{
    "items": [
        {
            "prenda": "...",
            "variaciones": {
                "manga": {"tipo": "...", "observacion": "..."},
                "broche": {...},
                "bolsillos": {...},
                "reflectivo": {...}
            },
            "obs_manga": "...",
            "obs_bolsillos": "...",
            "obs_broche": "...",
            "obs_reflectivo": "..."
        }
    ]
}
```

---

## 🚀 PASO 3: Verificar que el BACKEND RECIBE las variaciones

### 📍 Archivo: `app/Http/Controllers/Asesores/CrearPedidoEditableController.php` (línea 302+)

**El controlador debe extraer así:**

```php
// En CrearPedidoEditableController::crearPedido()
foreach ($validated['items'] as $item) {
    //  EXTRACCIÓN 1: Observaciones a nivel superior
    $prendaData = [
        'nombre_producto' => $item['prenda'],
        'descripcion' => $item['descripcion'] ?? '',
        'variaciones' => $item['variaciones'] ?? [],
        
        //  EXTRAER observaciones del nivel superior
        'obs_manga' => $item['obs_manga'] ?? '',
        'obs_bolsillos' => $item['obs_bolsillos'] ?? '',
        'obs_broche' => $item['obs_broche'] ?? '',
        'obs_reflectivo' => $item['obs_reflectivo'] ?? '',
    ];
    
    //  EXTRACCIÓN 2: Si vienen anidadas en variaciones, también extraer
    if (isset($item['variaciones']) && is_array($item['variaciones'])) {
        foreach ($item['variaciones'] as $varTipo => $variacion) {
            if (is_array($variacion)) {
                // Extraer tipo: manga, broche, etc.
                if (isset($variacion['tipo'])) {
                    $prendaData[$varTipo] = $variacion['tipo'];
                }
                // Extraer observación
                if (isset($variacion['observacion'])) {
                    $prendaData['obs_' . $varTipo] = $variacion['observacion'];
                }
            }
        }
    }
    
    // Pasar a servicio
    $this->guardarPrendasEnPedido($pedido, [$prendaData]);
}
```

** VERIFICACIÓN (en logs):**

Busca en `storage/logs/laravel.log`:

```
[2024-XX-XX] local.INFO:  [CrearPedidoEditableController] Procesando item 1
{
    "prenda": "...",
    "obs_manga": "con puño",
    "obs_bolsillos": "bolsillos de pecho",
    "obs_broche": "",
    "obs_reflectivo": "3 franjas",
    "variaciones": {...}
}
```

---

## 🚀 PASO 4: Verificar que el SERVICIO recibe las variaciones

### 📍 Archivo: `app/Application/Services/PedidoPrendaService.php` (línea ~175)

```php
public function guardarPrendasEnPedido(
    PedidoProduccion $pedido,
    array $prendasData,
    ?User $usuario = null
): void {
    foreach ($prendasData as $index => $prendaData) {
        //  VERIFICAR que recibe los datos correctos
        Log::info(' [PedidoPrendaService] Recibida prenda', [
            'index' => $index,
            'obs_manga_recibido' => $prendaData['obs_manga'] ?? 'NO RECIBIDO',
            'obs_bolsillos_recibido' => $prendaData['obs_bolsillos'] ?? 'NO RECIBIDO',
            'obs_broche_recibido' => $prendaData['obs_broche'] ?? 'NO RECIBIDO',
            'obs_reflectivo_recibido' => $prendaData['obs_reflectivo'] ?? 'NO RECIBIDO',
        ]);
        
        //  EXTRACCIÓN adicional de datos anidados en variaciones
        if (isset($prendaData['variaciones']) && is_array($prendaData['variaciones'])) {
            foreach ($prendaData['variaciones'] as $key => $value) {
                if (!isset($prendaData[$key])) {
                    $prendaData[$key] = $value;
                }
            }
        }
        
        // ... resto de procesamiento
    }
}
```

** VERIFICACIÓN (en logs):**

Busca en `storage/logs/laravel.log`:

```
[2024-XX-XX] local.INFO:  [PedidoPrendaService] Recibida prenda
{
    "obs_manga_recibido": "con puño",
    "obs_bolsillos_recibido": "bolsillos de pecho",
    "obs_broche_recibido": "",
    "obs_reflectivo_recibido": "3 franjas"
}
```

Si ves `"NO RECIBIDO"` → **El controlador NO está pasando los datos**

---

## 🚀 PASO 5: Verificar que se GUARDAN en la BD

### 📍 Archivo: `app/Application/Services/PedidoPrendaService.php` (línea ~250)

```php
$prenda = PrendaPedido::create([
    'numero_pedido' => $pedido->numero_pedido,
    'nombre_prenda' => $prendaData['nombre_producto'] ?? 'Sin nombre',
    'descripcion' => $descripcionFinal,
    'cantidad_talla' => json_encode($cantidadTallaFinal),
    'descripcion_variaciones' => $this->armarDescripcionVariaciones($prendaData),
    
    //  VARIACIONES: Tipos
    'tipo_manga_id' => $prendaData['tipo_manga_id'] ?? null,
    'tipo_broche_id' => $prendaData['tipo_broche_id'] ?? null,
    'tiene_bolsillos' => $prendaData['tiene_bolsillos'] ?? false,
    'tiene_reflectivo' => $prendaData['tiene_reflectivo'] ?? false,
    
    //  OBSERVACIONES: Guardadas con ambos prefijos para compatibilidad
    'manga_obs' => $prendaData['obs_manga'] ?? $prendaData['manga_obs'] ?? '',
    'bolsillos_obs' => $prendaData['obs_bolsillos'] ?? $prendaData['bolsillos_obs'] ?? '',
    'broche_obs' => $prendaData['obs_broche'] ?? $prendaData['broche_obs'] ?? '',
    'reflectivo_obs' => $prendaData['obs_reflectivo'] ?? $prendaData['reflectivo_obs'] ?? '',
]);

//  LOG de verificación
Log::info(' [PedidoPrendaService] Prenda guardada', [
    'manga_obs_guardado' => $prenda->manga_obs,
    'bolsillos_obs_guardado' => $prenda->bolsillos_obs,
    'broche_obs_guardado' => $prenda->broche_obs,
    'reflectivo_obs_guardado' => $prenda->reflectivo_obs,
]);
```

** VERIFICACIÓN (en logs):**

Busca en `storage/logs/laravel.log`:

```
[2024-XX-XX] local.INFO:  [PedidoPrendaService] Prenda guardada
{
    "manga_obs_guardado": "con puño",
    "bolsillos_obs_guardado": "bolsillos de pecho",
    "broche_obs_guardado": "",
    "reflectivo_obs_guardado": "3 franjas"
}
```

Si los valores están vacíos → **El servicio no recibió los datos del controlador**

---

## 🚀 PASO 6: Verificar en la BASE DE DATOS

```sql
-- Conectar a la BD
SELECT 
    id,
    nombre_prenda,
    manga_obs,
    bolsillos_obs,
    broche_obs,
    reflectivo_obs,
    tipo_manga_id,
    tipo_broche_id,
    tiene_bolsillos,
    tiene_reflectivo
FROM prendas_pedido
WHERE numero_pedido = 'TU_NUMERO_PEDIDO'
ORDER BY id DESC
LIMIT 1;
```

**Resultado esperado:**
```
| id  | nombre_prenda | manga_obs    | bolsillos_obs       | broche_obs | reflectivo_obs | tipo_manga_id | tipo_broche_id | tiene_bolsillos | tiene_reflectivo |
|-----|---------------|--------------|---------------------|-----------|----------------|---------------|----------------|-----------------|------------------|
| 123 | Uniforme      | con puño     | bolsillos de pecho  |           | 3 franjas      | 1             | 2              | 1               | 1                |
```

Si ves `NULL` o valores vacíos en observaciones → **El dato NO llegó al servicio**

---

##  Diagnóstico Rápido

Copia este código en `dd()` o `dump()` en el controlador:

```php
// En CrearPedidoEditableController::crearPedido() - línea 302
dd([
    'item_recibido' => $item,
    'prendaData_preparado' => $prendaData,
    'obs_manga' => $prendaData['obs_manga'] ?? 'NO DEFINIDO',
    'obs_bolsillos' => $prendaData['obs_bolsillos'] ?? 'NO DEFINIDO',
    'obs_broche' => $prendaData['obs_broche'] ?? 'NO DEFINIDO',
    'obs_reflectivo' => $prendaData['obs_reflectivo'] ?? 'NO DEFINIDO',
]);
```

---

##  Checklist de Verificación

- [ ] **Frontend**: Verificar que `gestion-items-pedido.js` prepara `variaciones` correctamente
- [ ] **Network**: En DevTools, ver que el JSON enviado tiene todos los campos
- [ ] **Controller**: Verificar en logs que `CrearPedidoEditableController` recibe `obs_*` 
- [ ] **Service**: Verificar en logs que `PedidoPrendaService` recibe `obs_*`
- [ ] **Database**: Verificar SQL que la columna tiene datos guardados
- [ ] **Logs**: Buscar errores en `storage/logs/laravel.log` entre create() y el siguiente endpoint

---

## 🐛 Problemas Comunes y Soluciones

### Problema 1: Los valores llegan `NULL` a la BD

**Causa**: El controller no los extrajo o el DTO no los pasó
**Solución**: Verificar línea 288-295 del controller

### Problema 2: Los valores vienen vacíos en variaciones

**Causa**: Frontend envía `variaciones.manga.observacion` pero backend busca `obs_manga`
**Solución**: El backend ya tiene lógica para extraer de ambas fuentes (línea 308-315)

### Problema 3: El log dice "NO RECIBIDO"

**Causa**: El controlador no está pasando los datos a prendaData
**Solución**: Revisar que las líneas 288-295 estén presentes en el foreach

### Problema 4: Los datos llegan al service pero no se guardan

**Causa**: El modelo no tiene los atributos o no están en `$fillable`
**Solución**: Verificar que `PrendaPedido` tenga los campos en `$fillable`:

```php
protected $fillable = [
    'manga_obs',
    'bolsillos_obs', 
    'broche_obs',
    'reflectivo_obs',
    // ... otros campos
];
```

---

##  Mapa del Flujo Completo

```
┌─────────────────────────────────┐
│ Frontend: gestion-items-pedido.js│ Prepara item.variaciones
│ item.obs_manga = "con puño"     │ item.obs_bolsillos = "..."
└────────────────┬────────────────┘
                 │
                 ▼
┌─────────────────────────────────┐
│ Frontend: api-pedidos-editable.js│ Envía JSON
└────────────────┬────────────────┘
                 │
                 ▼
   ┌───────────────────────────┐
   │ Network Request JSON      │
   │ items[0].obs_manga = "..." │
   │ items[0].variaciones = {...}│
   └────────┬──────────────────┘
            │
            ▼
   ┌──────────────────────────────────┐
   │ CrearPedidoEditableController    │ Recibe
   │ Extrae obs_* a prendaData       │ Busca en variaciones también
   └────────┬───────────────────────┘
            │
            ▼
   ┌──────────────────────────────────┐
   │ PedidoPrendaService              │ Recibe prendaData
   │ Busca obs_* en datos             │ Busca en variaciones anidadas
   └────────┬───────────────────────┘
            │
            ▼
   ┌──────────────────────────────────┐
   │ PrendaPedido::create()           │ Guarda
   │ manga_obs = $prendaData['obs_...'] │
   └────────┬───────────────────────┘
            │
            ▼
   ┌──────────────────────────────────┐
   │ MySQL: prendas_pedido table      │  GUARDADO
   │ manga_obs: "con puño"            │
   └──────────────────────────────────┘
```

---

##  Próximos Pasos

1. **Ejecuta los logs**: Crea un pedido de prueba y busca los 4 puntos de verification en logs
2. **Identifica el punto de ruptura**: ¿Dónde se pierden los datos?
3. **Aplica la solución**: Ya está implementada, pero puede necesitar ajustes
4. **Valida en BD**: Consulta directa a MySQL para confirmar que están guardados

---

**Última actualización**: 2024
**Estado**: Script de análisis completo para debug de variaciones
