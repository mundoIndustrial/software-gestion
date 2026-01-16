# Análisis: Por qué NO se guardan variaciones y observaciones de prendas

## 🔴 PROBLEMA IDENTIFICADO

Las variaciones (manga, broche, bolsillos, reflectivo) y sus observaciones no se guardan en la BD cuando se crea una prenda sin cotización.

## 📊 FLUJO DE DATOS

### 1️⃣ FRONTEND: gestion-items-pedido.js (líneas 1049-1164)

**Lo que se PREPARA:**
```javascript
const itemSinCot = {
    tipo: 'prenda_nueva',
    prenda: 'ertre',
    descripcion: 'tertert',
    genero: 'dama',
    cantidad_talla: {dama-S: 20, dama-M: 20},
    tallas: ['dama-S', 'dama-M'],
    variaciones: variaciones,  // ✅ INCLUYE VARIACIONES
    obs_manga: obs_manga,      // ✅ INCLUYE OBSERVACIONES
    obs_bolsillos: obs_bolsillos,
    obs_broche: obs_broche,
    obs_reflectivo: obs_reflectivo,
    origen: 'bodega',
    de_bodega: 1,
    procesos: procesosParaEnviar
};
```

**Estado de `variaciones`:**
```
{
    tipo_manga: 'No aplica',
    obs_manga: '',
    tipo_broche: 'No aplica',
    obs_broche: '',
    tiene_bolsillos: false,
    obs_bolsillos: '',
    tiene_reflectivo: false,
    obs_reflectivo: ''
}
```

✅ Frontend ENVÍA esto en JSON a recolectarDatosPedido() → api-pedidos-editable.js

---

### 2️⃣ FRONTEND: api-pedidos-editable.js (línea 132)

**Lo que se ENVÍA:**
```javascript
{
    cliente: 'rtytryrt',
    asesora: 'yus2',
    forma_de_pago: 'yrtytr',
    items: [
        {
            tipo: 'prenda_nueva',
            prenda: 'ertre',
            descripcion: 'tertert',
            genero: 'dama',
            cantidad_talla: {...},
            tallas: [...],
            variaciones: {...},     // ✅ INCLUIDA EN JSON
            obs_manga: '',
            obs_bolsillos: '',
            obs_broche: '',
            obs_reflectivo: '',
            procesos: {...},
            imagenes: [...]
        }
    ]
}
```

✅ Se envía como JSON al endpoint: `/asesores/pedidos-editable/crear`

---

### 3️⃣ BACKEND: CrearPedidoEditableController.php → crearPedido()

**¿QUE RECIBE?**
```php
$validated = $request->validate([
    'cliente' => 'required|string',
    'asesora' => 'required|string',
    'forma_de_pago' => 'nullable|string',
    'items' => 'required|array',
]);

// $validated['items'] contiene el array de items
```

**¿QUE HACE CON VARIACIONES?** (línea 302)
```php
foreach ($validated['items'] as $item) {
    // Procesar observaciones de variaciones
    // ✅ Busca: $item['variaciones'] ← CORRECTO
    if (isset($item['variaciones']) && is_array($item['variaciones'])) {
        foreach ($item['variaciones'] as $varTipo => $variacion) {
            // Extrae tipo (manga, broche, etc.)
            if (isset($variacion['tipo'])) {
                $prendaData[$varTipo] = $variacion['tipo'];
            }
            // Extrae observación
            if (isset($variacion['observacion'])) {
                $prendaData['obs_' . $varTipo] = $variacion['observacion'];
            }
        }
    }
    
    // Luego pasa $prendaData a guardarPrendasEnPedido()
    $prendasParaGuardar[] = $prendaData;
}
```

⚠️ **PROBLEMA 1:** El backend busca `$variacion['observacion']` pero el frontend envía `obs_manga`, `obs_bolsillos`, etc. directamente al nivel superior, NO dentro de variaciones.

---

### 4️⃣ BACKEND: PedidoPrendaService.php → guardarPrenda()

**¿QUE RECIBE?**
```php
$prendaData = [
    'nombre_producto' => 'ertre',
    'descripcion' => 'tertert',
    'variaciones' => [
        'tipo_manga' => 'No aplica',
        'obs_manga' => '',
        'tipo_broche' => 'No aplica',
        'obs_broche' => '',
        'tiene_bolsillos' => false,
        'obs_bolsillos' => '',
        'tiene_reflectivo' => false,
        'obs_reflectivo' => ''
    ],
    'cantidad_talla' => {...},
    'procesos' => {...},
    // FALTA: obs_manga, obs_bolsillos, obs_broche, obs_reflectivo
    // FALTA: tipo_manga, tipo_broche, tiene_bolsillos, tiene_reflectivo
]
```

**¿QUE INTENTA GUARDAR?** (línea 178-224)
```php
$prenda = PrendaPedido::create([
    'numero_pedido' => $pedido->numero_pedido,
    'nombre_prenda' => $prendaData['nombre_producto'],
    'descripcion' => $descripcionFinal,
    'cantidad_talla' => json_encode($cantidadTallaFinal),
    'descripcion_variaciones' => $this->armarDescripcionVariaciones($prendaData),
    
    // CAMPOS QUE DEBERÍA GUARDAR:
    'tipo_manga_id' => $prendaData['tipo_manga_id'] ?? null,  // ← BuscaID pero recibe STRING
    'tipo_broche_id' => $prendaData['tipo_broche_id'] ?? null,
    'tiene_bolsillos' => $prendaData['tiene_bolsillos'] ?? false,
    'tiene_reflectivo' => $prendaData['tiene_reflectivo'] ?? false,
    
    // CAMPOS DE OBSERVACIONES:
    'manga_obs' => $prendaData['obs_manga'] ?? $prendaData['manga_obs'] ?? '',
    'bolsillos_obs' => $prendaData['obs_bolsillos'] ?? $prendaData['bolsillos_obs'] ?? '',
    'broche_obs' => $prendaData['obs_broche'] ?? $prendaData['broche_obs'] ?? '',
    'reflectivo_obs' => $prendaData['obs_reflectivo'] ?? $prendaData['reflectivo_obs'] ?? '',
]);
```

⚠️ **PROBLEMA 2:** Los datos de variaciones vienen ANIDADOS en `$prendaData['variaciones']` pero PedidoPrendaService.php busca en nivel superior:
- Busca: `$prendaData['obs_manga']` 
- Pero recibe: `$prendaData['variaciones']['obs_manga']`

---

## 🔴 RAÍCES DEL PROBLEMA

### Problema 1: Mapeo de observaciones en CrearPedidoEditableController

**Línea 302-322:**
```php
if (isset($item['variaciones']) && is_array($item['variaciones'])) {
    foreach ($item['variaciones'] as $varTipo => $variacion) {
        // El backend asume estructura anidada:
        // variaciones: { manga: { tipo: "...", observacion: "..." } }
        
        // PERO el frontend envía estructura plana:
        // variaciones: { tipo_manga: "...", obs_manga: "..." }
        // obs_manga: "..." (nivel superior también)
    }
}
```

**SOLUCIÓN:** Extraer observaciones directamente del item, no de variaciones:
```php
// También buscar en nivel superior del item
$prendaData['obs_manga'] = $item['obs_manga'] ?? '';
$prendaData['obs_bolsillos'] = $item['obs_bolsillos'] ?? '';
$prendaData['obs_broche'] = $item['obs_broche'] ?? '';
$prendaData['obs_reflectivo'] = $item['obs_reflectivo'] ?? '';
```

### Problema 2: Acceso a datos anidados en PedidoPrendaService

**Línea 181-196:**
```php
// Busca en nivel superior
$prendaData['tipo_manga_id'] ?? null  // NO EXISTE

// Pero debería buscar en variaciones
$prendaData['variaciones']['tipo_manga'] ?? null  // EXISTE
```

**SOLUCIÓN:** Extraer datos de variaciones si existe:
```php
// Procesar variaciones si vienen anidadas
if (isset($prendaData['variaciones']) && is_array($prendaData['variaciones'])) {
    // Fusionar variaciones al nivel superior para que funcione la lógica existente
    $prendaData = array_merge($prendaData, $prendaData['variaciones']);
}
```

---

## 🎯 CAMPOS QUE SE PIERDEN

| Campo | Ubicación Frontend | Ubicación Backend | Estado |
|-------|-------------------|------------------|--------|
| tipo_manga | variaciones.tipo_manga | variaciones.tipo_manga | ❌ NO se procesa |
| obs_manga | obs_manga (nivel superior) | obs_manga | ❌ NO se extrae |
| tipo_broche | variaciones.tipo_broche | variaciones.tipo_broche | ❌ NO se procesa |
| obs_broche | obs_broche | obs_broche | ❌ NO se extrae |
| tiene_bolsillos | variaciones.tiene_bolsillos | variaciones.tiene_bolsillos | ❌ NO se procesa |
| obs_bolsillos | obs_bolsillos | obs_bolsillos | ❌ NO se extrae |
| tiene_reflectivo | variaciones.tiene_reflectivo | variaciones.tiene_reflectivo | ❌ NO se procesa |
| obs_reflectivo | obs_reflectivo | obs_reflectivo | ❌ NO se extrae |

---

## ✅ SOLUCIÓN RECOMENDADA

### 1. En CrearPedidoEditableController.php (línea ~310)

ANTES DE pasar a PedidoPrendaService, normalizar la estructura:

```php
// Agregar observaciones de nivel superior
$prendaData['obs_manga'] = $item['obs_manga'] ?? '';
$prendaData['obs_bolsillos'] = $item['obs_bolsillos'] ?? '';
$prendaData['obs_broche'] = $item['obs_broche'] ?? '';
$prendaData['obs_reflectivo'] = $item['obs_reflectivo'] ?? '';

// Si variaciones vienen anidadas, extraerlas
if (isset($item['variaciones'])) {
    $prendaData['variaciones'] = $item['variaciones'];
}
```

### 2. En PedidoPrendaService.php (línea ~170)

ANTES DE crear PrendaPedido, extraer datos de variaciones:

```php
// Si variaciones vienen anidadas, fusionarlas al nivel superior
if (isset($prendaData['variaciones']) && is_array($prendaData['variaciones'])) {
    foreach ($prendaData['variaciones'] as $key => $value) {
        // Solo fusionar si no existe en nivel superior
        if (!isset($prendaData[$key])) {
            $prendaData[$key] = $value;
        }
    }
}
```

---

## 📋 CHECKLIST DE VERIFICACIÓN

- [ ] ¿Se envía `variaciones` desde frontend? ✅ SÍ
- [ ] ¿Se envían observaciones al nivel superior? ✅ SÍ
- [ ] ¿CrearPedidoEditableController extrae observaciones? ❌ NO
- [ ] ¿PedidoPrendaService busca variaciones anidadas? ❌ NO
- [ ] ¿Se guardan en BD? ❌ NO
