# Corrección: Guardar Observaciones por Talla-Color en Procesos

## Problema Reportado
Las observaciones y ubicaciones por combinación talla-color **no se guardaban** en la tabla `pedidos_procesos_prenda_talla_colores` cuando se creaban órdenes con procesos en modo específico.

**Ejemplo del log:**
```
"M__AQUA": { "observaciones": "jhgjgh", "ubicaciones": ["hjgh"] }
"L__AZUL ACERO": { "observaciones": "hjghjhg", "ubicaciones": ["ghjhgj"] }
```
Estos datos no llegaban a la BD.

---

## Diagnóstico

### Causa Raíz
Cuando se creaban colores en procesos con:
```php
$tallaProceso->coloresAsignados()->create([
    'color_nombre' => $colorNombre,
    'tela_nombre' => $telaGuardar,
    'cantidad' => (int)$colorCantidad,
    // FALTABAN:
    // 'observaciones' => $observacionesColor,
    // 'ubicaciones' => json_encode($ubicacionesColor),
]);
```

Había 2 problemas:
1. **PedidoWebService.php**: No extraía `observaciones`/`ubicaciones` de `datosExtendidos`
2. **PedidosProcesosPrendaTallaColor.php**: El `$fillable` no permitía estos campos

---

## Solución Implementada

### 1. PedidoWebService.php - crearTallasProceso()

#### Flujo TALLA__COLOR embebido (línea ~1208)
```php
foreach ($data['colores'] as $colorItem) {
    // NUEVO: Extraer observaciones y ubicaciones del datosExtendidos
    $claveDataExtendidos = "{$tallaReal}__{$colorItem['nombre']}";
    $observacionesColor = null;
    $ubicacionesColor = null;
    
    if (!empty($datosExtendidos)) {
        $generoKey = strtolower(trim($generoBD));
        if (isset($datosExtendidos[$generoKey][$claveDataExtendidos])) {
            $datosColor = $datosExtendidos[$generoKey][$claveDataExtendidos];
            $observacionesColor = $datosColor['observaciones'] ?? null;
            $ubicacionesColor = $datosColor['ubicaciones'] ?? null;
        }
    }
    
    $colorCreado = $tallaProceso->coloresAsignados()->create([
        'color_nombre' => $colorItem['nombre'],
        'tela_nombre' => $telaGuardar,
        'cantidad' => (int)$colorItem['cantidad'],
        'observaciones' => $observacionesColor,  // NUEVO
        'ubicaciones' => !empty($ubicacionesColor) ? json_encode($ubicacionesColor) : null,  // NUEVO
    ]);
}
```

#### Flujo normal (línea ~1320)
Se aplicó la misma corrección para extraer observaciones por color específico.

### 2. PedidosProcesosPrendaTallaColor.php

```php
protected $fillable = [
    'pedidos_procesos_prenda_talla_id',
    'color_nombre',
    'tela_nombre',
    'cantidad',
    'observaciones',  // NUEVO
    'ubicaciones',     // NUEVO
];
```

### 3. Logging agregado
Se añadió logging DEBUG para verificar que se guardan:
```php
\Log::debug('[PedidoWebService] Color con observaciones guardado', [
    'color_id' => $colorCreado->id,
    'color_nombre' => $colorNombre,
    'observaciones' => $observacionesColor,
    'ubicaciones' => $ubicacionesColor,
]);
```

---

## Cómo Probar

### 1. Crear nueva orden con proceso específico
- Prenda: CAMISA DRILL
- Proceso: Estampado  
- Modo tallas: Específico
- Agregar tallas: M (AQUA), L (AZUL ACERO)
- Agregar observaciones para cada talla-color

### 2. Verificar logs
Buscar en logs.txt:
```
[PedidoWebService] Color con observaciones guardado
```

### 3. Verificar base de datos
```sql
SELECT 
    id,
    color_nombre,
    observaciones,
    ubicaciones
FROM pedidos_procesos_prenda_talla_colores
WHERE pedidos_procesos_prenda_talla_id IN (
    SELECT id FROM pedidos_procesos_prenda_tallas
    WHERE proceso_prenda_detalle_id = [ID_DEL_PROCESO]
);
```

Deberías ver las observaciones en la columna `observaciones` y las ubicaciones en JSON en `ubicaciones`.

---

## Archivos Modificados
- ✅ `app/Domain/Pedidos/Services/PedidoWebService.php`
- ✅ `app/Models/PedidosProcesosPrendaTallaColor.php`

## Notas
- Los cambios son **retroactivos** solo para nuevas órdenes creadas
- Las órdenes existentes requieren actualización manual si necesitan las observaciones
- El logging DEBUG ayudará a identificar problemas en el future
