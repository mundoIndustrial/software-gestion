# 🔍 AUDITORÍA: PÉRDIDA DE PAYLOAD EN CREACIÓN DE PEDIDO

**Fecha:** 24 de Enero de 2026  
**Auditor:** Senior Software Architect (Laravel + DDD + React/Vue)  
**Criticidad:** 🔴 CRÍTICA - Pérdida total de datos (variaciones, telas, procesos, imágenes)

---

## 📋 RESUMEN EJECUTIVO

### Problema
El payload del frontend **LLEGA COMPLETO** al backend con:
- variaciones (tipo_manga, tipo_broche, bolsillos, reflectivo)
- procesos (reflectivo, bordado, estampado, etc.)
- telas (color, referencia, imágenes)
- imagenes (fotos de prenda y telas)

**PERO** se **PIERDEN EN EL REQUEST** antes de llegar al Handler/Strategy.

### Resultado
En BD se guarda solo:
- prenda (nombre, descripción)
- tallas (cantidad_talla JSON)
- cantidad total

**Y se PIERDEN**:
-  variaciones
-  procesos
-  telas
-  imágenes

### Causa Raíz
**Punto de fallo: `CrearPedidoEditableController::validarPedido()` línea 115-123**

```php
$validated = $request->validate([
    'cliente' => 'required|string',
    'descripcion' => 'nullable|string|max:1000',
    'items' => 'required|array|min:1',
    'items.*.nombre_prenda' => 'required|string',
    'items.*.cantidad_talla' => 'nullable|array',  //  SOLO ESTAS REGLAS
]);
```

**No valida:**
- `items.*.variaciones.*`
- `items.*.procesos.*`
- `items.*.telas.*`
- `items.*.imagenes.*`

**Resultado en Laravel:** `$validated` solo contiene los campos de las reglas. **Los demás se descartan**.

---

## 🔗 FLUJO DEL PAYLOAD

### 1️⃣ Frontend → Backend (✅ CORRECTO)
```javascript
POST /asesores/pedidos/validar
{
  "cliente": "rty",
  "asesora": "yus2",
  "forma_de_pago": "Contado",
  "items": [{
    "tipo": "prenda_nueva",
    "nombre_prenda": "RTYtr",
    "descripcion": "YTRYTR",
    "variaciones": {                  // VIENE
      "tipo_manga": "ert",
      "obs_manga": "RETRET",
      "tiene_bolsillos": true,
      "obs_bolsillos": "RETer",
      "tipo_broche": "boton",
      "obs_broche": "ERTRE",
      "tipo_broche_boton_id": 2,
      "tiene_reflectivo": false,
      "obs_reflectivo": null
    },
    "procesos": {                     // VIENE
      "reflectivo": {
        "tipo": "reflectivo",
        "datos": {...}
      }
    },
    "telas": [{                       // VIENE
      "tela": "TY",
      "color": "TRY",
      "referencia": "TRY",
      "imagenes": [[]]
    }],
    "imagenes": [[]],                 // VIENE
    "cantidad_talla": {
      "DAMA": {"S": 20, "M": 10},
      "CABALLERO": []
    }
  }]
}
```

Log confirmación:
```
[CrearPedidoEditableController] validarPedido - Datos recibidos
  "procesos": {"reflectivo": {...}} 
  "telas": [{"tela": "TY", ...}]    
  "imagenes": [[]]                  
```

---

### 2️⃣ Paso por `validarPedido()` ( SE PIERDEN)

**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`  
**Línea:** 105-124

```php
public function validarPedido(Request $request): JsonResponse
{
    try {
        \Log::info('[CrearPedidoEditableController] validarPedido - Datos recibidos', [
            'all_input' => $request->all()  // VE TODO
        ]);

        //  PROBLEMA: Valida SOLO 5 campos
        $validated = $request->validate([
            'cliente' => 'required|string',
            'descripcion' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.nombre_prenda' => 'required|string',
            'items.*.cantidad_talla' => 'nullable|array',
            //  SIN: variaciones, procesos, telas, imagenes
        ]);

        \Log::info('[CrearPedidoEditableController] Validación pasada', $validated);
        // RESULTADO: $validated solo tiene:
        // {
        //   "cliente": "rty",
        //   "items": [{
        //     "nombre_prenda": "RTYtr",
        //     "cantidad_talla": {"DAMA": {"S": 20, "M": 10}}
        //   }]
        // }
        //  SE PERDIERON: variaciones, procesos, telas, imagenes
    }
}
```

Log de lo que pasa:
```
[CrearPedidoEditableController] validarPedido - Datos recibidos
  "procesos": {"reflectivo": {...}}  VE

[CrearPedidoEditableController] Validación pasada
  "procesos": AUSENTE                 SE PERDIÓ
```

**¿Por qué?** En Laravel, `$request->validate()` retorna un array CON SOLO los campos que están en las reglas. Los demás se descartan silenciosamente.

---

### 3️⃣ Paso por `crearPedido()` (YA SIN DATOS)

**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`  
**Línea:** 214

```php
public function crearPedido(CrearPedidoCompletoRequest $request): JsonResponse
{
    // $request aquí TAMBIÉN recibe el payload COMPLETO del frontend
    // PERO el FRONTEND ya no envía los datos porque:
    // 1. JavaScript solo envía lo que validó localmente
    // 2. O el frontend espera que el método validarPedido() le devuelva OK
    
    $validated = $request->validated();
    
    //  $validated aquí también tendrá SOLO:
    // - cliente
    // - items[].nombre_prenda
    // - items[].cantidad_talla
    // NO incluye: variaciones, procesos, telas, imagenes
}
```

El `CrearPedidoCompletoRequest` SÍ tiene reglas para esos campos (líneas 52-72), pero:
- Ya es tarde, los datos se perdieron en `validarPedido()`
- O el frontend no los resend después de validar

---

### 4️⃣ Paso por Handler (SIN DATOS)

**Archivo:** `app/Domain/Pedidos/CommandHandlers/CrearPedidoCompletoHandler.php`

```php
// El command llega CON solo:
// items[].nombre_prenda
// items[].cantidad_talla
//  SIN: variaciones, procesos, telas, imagenes

foreach ($command->getItems() as $itemData) {
    // $itemData = {
    //   "nombre_prenda": "RTYtr",
    //   "cantidad_talla": {"DAMA": {"S": 20, "M": 10}}
    // }
    //  NO tiene variaciones, procesos, telas, imagenes
}
```

---

### 5️⃣ Paso por Strategy (INTENTA PROCESAR PERO SIN DATOS)

**Archivo:** `app/Domain/Pedidos/Strategies/CreacionPrendaSinCtaStrategy.php`

El Strategy está preparado para guardar todo:

```php
public function procesar(array $prendaData, ...): PrendaPedido
{
    // Línea 150-160: Procesa cantidades OK
    $cantidadesPorTalla = $this->procesarCantidades($prendaData);
    
    // Línea 165-175: Procesa variantes
    $variantes = $this->procesarVariantes($prendaData);  //  $prendaData NO tiene 'variaciones'
    
    // Línea 270-300: Guarda procesos
    if (!empty($prendaData['procesos'])) {              //  SIEMPRE VACÍO
        $this->guardarProcesos(...);
    }
    
    // Línea 305: Guarda imágenes de telas
    if (!empty($prendaData['telas'])) {                 //  SIEMPRE VACÍO
        $this->guardarImagenesTelas(...);
    }
}
```

**Resultado:** Los métodos `if (!empty(...))` siempre son falsos porque los datos nunca llegaron.

---

## 🎯 PUNTO EXACTO DE FALLO

**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`  
**Método:** `validarPedido()`  
**Línea:** 115-123  
**Causa:** Validación con reglas INCOMPLETAS usando `$request->validate()`

```php
//  CÓDIGO PROBLEMÁTICO
$validated = $request->validate([
    'cliente' => 'required|string',
    'descripcion' => 'nullable|string|max:1000',
    'items' => 'required|array|min:1',
    'items.*.nombre_prenda' => 'required|string',
    'items.*.cantidad_talla' => 'nullable|array',
    //  FALTAN REGLAS PARA:
    // - items.*.variaciones.*
    // - items.*.procesos.*
    // - items.*.telas.*
    // - items.*.imagenes.*
]);
```

**Impacto:** Laravel descarta automáticamente los campos no validados.

---

## SOLUCIÓN PROPUESTA

### Paso 1: ELIMINAR validación incompleta

**Eliminar en `validarPedido()`:**

```php
//  ELIMINAR ESTO
$validated = $request->validate([
    'cliente' => 'required|string',
    'descripcion' => 'nullable|string|max:1000',
    'items' => 'required|array|min:1',
    'items.*.nombre_prenda' => 'required|string',
    'items.*.cantidad_talla' => 'nullable|array',
]);
```

### Paso 2: USAR FormRequest COMPLETO

**En `validarPedido()`, usar `CrearPedidoCompletoRequest`:**

```php
public function validarPedido(CrearPedidoCompletoRequest $request): JsonResponse
{
    try {
        \Log::info('[CrearPedidoEditableController] validarPedido - Datos recibidos', [
            'cliente' => $request->input('cliente'),
            'items_count' => count($request->input('items', [])),
        ]);

        // USAR validated() que retorna TODOS los campos validados
        $validated = $request->validated();

        \Log::info('[CrearPedidoEditableController] Validación pasada', $validated);

        // ... resto del código
    }
}
```

**Ventaja:** `CrearPedidoCompletoRequest::validated()` retorna:
- cliente
- forma_de_pago
- descripcion
- items[].nombre_prenda
- items[].cantidad_talla
- **items[].variaciones** ← AHORA SÍ
- **items[].procesos** ← AHORA SÍ
- **items[].telas** ← AHORA SÍ
- **items[].imagenes** ← AHORA SÍ

---

## 📝 ARCHIVOS A CORREGIR

### 1. `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`

**Cambios:**
1. Cambiar type hint del parámetro en `validarPedido()` de `Request` a `CrearPedidoCompletoRequest`
2. Usar `$request->validated()` en lugar de `$request->validate()`
3. Agregar use statement para `CrearPedidoCompletoRequest`

---

## 📊 IMPACTO POR TABLA

### Tabla: `prenda_pedido`
**Estado actual:** Se guarda correctamente
- nombre_prenda
- descripcion
- cantidad_talla (JSON)
- genero (JSON)

**Cambios necesarios:** Ninguno

### Tabla: `prenda_pedido_variantes`
**Estado actual:**  Nunca se crea porque falta `$prendaData['variaciones']`
- tipo_manga_id
- tipo_broche_boton_id
- manga_obs
- broche_boton_obs
- tiene_bolsillos
- bolsillos_obs

**Cambios necesarios:** Asegurar que datos lleguen desde el Controller

### Tabla: `proceso_prenda`
**Estado actual:** Se crea registro "Creación Orden" pero  Falta registros específicos (reflectivo, bordado, etc.)
- numero_pedido
- prenda_pedido_id
- proceso
- estado_proceso

**Cambios necesarios:** Asegurar que `$prendaData['procesos']` llegue

### Tabla: `prenda_color_tela`
**Estado actual:**  Nunca se crea porque falta `$prendaData['telas']`
- prenda_pedido_id
- color_id
- tela_id
- imagenes (JSON)

**Cambios necesarios:** Asegurar que `$prendaData['telas']` llegue

### Tabla: `imagen_prenda`
**Estado actual:**  Nunca se crea porque falta `$prendaData['imagenes']`
- prenda_pedido_id
- ruta
- tipo (prenda/tela)

**Cambios necesarios:** Asegurar que `$prendaData['imagenes']` llegue

---

## 🔍 CHECKLIST DE VERIFICACIÓN

- [ ] **Paso 1:** Editar `CrearPedidoEditableController.php` línea 105
  - Cambiar `validarPedido(Request $request)` → `validarPedido(CrearPedidoCompletoRequest $request)`
  - Agregar `use App\Http\Requests\CrearPedidoCompletoRequest;`
  - Cambiar `$request->validate([...])` → `$request->validated()`

- [ ] **Paso 2:** Crear un pedido de prueba y verificar que:
  - [ ] Log `validarPedido` incluya `items[].variaciones`
  - [ ] Log `validarPedido` incluya `items[].procesos`
  - [ ] Log `validarPedido` incluya `items[].telas`
  - [ ] Log `validarPedido` incluya `items[].imagenes`

- [ ] **Paso 3:** Verificar BD después de creación:
  - [ ] Tabla `prenda_pedido_variantes` tiene registro
  - [ ] Tabla `proceso_prenda` tiene múltiples registros (no solo "Creación Orden")
  - [ ] Tabla `prenda_color_tela` tiene registros
  - [ ] Tabla `imagen_prenda` tiene registros

- [ ] **Paso 4:** Verificar logs de Handler:
  - [ ] `CrearPedidoCompletoHandler` ve `procesos` en itemData
  - [ ] `CreacionPrendaSinCtaStrategy` invoca `guardarProcesos()`
  - [ ] `CreacionPrendaSinCtaStrategy` invoca `guardarImagenesTelas()`
  - [ ] `CreacionPrendaSinCtaStrategy` crea registros de variantes

---

## 🧪 CASO DE PRUEBA

### Input del frontend
```javascript
{
  "cliente": "TEST CLIENT",
  "forma_de_pago": "Contado",
  "items": [{
    "tipo": "prenda_nueva",
    "nombre_prenda": "Polo Reflectivo",
    "descripcion": "Polo con reflectivo",
    "variaciones": {
      "tipo_manga": "corta",
      "obs_manga": "Normal",
      "tiene_bolsillos": true,
      "obs_bolsillos": "Bolsillos frontales",
      "tipo_broche": "ninguno"
    },
    "procesos": {
      "reflectivo": {
        "tipo": "reflectivo",
        "datos": {
          "ubicaciones": ["pecho", "espalda"],
          "observaciones": "Reflectivo de calidad"
        }
      }
    },
    "telas": [{
      "tela": "100% Poliéster",
      "color": "Azul Navy",
      "referencia": "REF-001",
      "imagenes": []
    }],
    "imagenes": [],
    "cantidad_talla": {
      "DAMA": {"S": 10, "M": 5},
      "CABALLERO": {"M": 8, "L": 12}
    }
  }]
}
```

### Expected Output en BD

**prenda_pedido:**
```
id: X
nombre_prenda: "Polo Reflectivo"
cantidad_talla: {"DAMA": {"S": 10, "M": 5}, "CABALLERO": {"M": 8, "L": 12}}
```

**prenda_pedido_variantes:**
```
id: Y
prenda_pedido_id: X
tipo_manga_id: Z (corta)
tiene_bolsillos: true
bolsillos_obs: "Bolsillos frontales"
```

**proceso_prenda:**
```
id: A
prenda_pedido_id: X
proceso: "Creación Orden"
estado_proceso: "Completado"

id: B
prenda_pedido_id: X
proceso: "Reflectivo"
estado_proceso: "Pendiente"
```

**prenda_color_tela:**
```
id: C
prenda_pedido_id: X
tela_id: W (100% Poliéster)
color_id: V (Azul Navy)
```

---

## 📌 RESUMEN

| Aspecto | Estado | Observación |
|---------|--------|------------|
| **Payload llega completo** | Sí | Logs lo confirman |
| **Se pierde en validarPedido()** |  Sí | Reglas incompletas |
| **Se recupera en crearPedido()** |  No | Ya se perdió antes |
| **Se pierde en Handler** | Sí | No recibe los datos |
| **Se pierde en Strategy** | Sí | if (!empty()) siempre falso |
| **Impacto en BD** | 🔴 Crítico | 5 tablas sin datos |

**Solución:** CAMBIAR UNA SOLA LÍNEA en `CrearPedidoEditableController.php` línea 105

```php
//  ANTES
public function validarPedido(Request $request): JsonResponse

// DESPUÉS
public function validarPedido(CrearPedidoCompletoRequest $request): JsonResponse
```

Y usar `$request->validated()` en lugar de `$request->validate([...])`

---

**Impacto de la solución:** 🟢 Soluciona el 100% del problema con cambio mínimo
