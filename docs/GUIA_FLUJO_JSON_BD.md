# 🎯 GUÍA DE IMPLEMENTACIÓN: FLUJO JSON → BD (REFACTORIZADO)

**Versión:** 1.0  
**Fecha:** Enero 16, 2026  
**Patrón:** CQRS + Domain-Driven Design + Transacciones  

---

##  RESUMEN EJECUTIVO

Se ha implementado una arquitectura profesional que respeta el flujo correcto:

```
FRONTEND (JSON temporal) → BACKEND (Descomposición) → BD (Tablas normalizadas)
```

###  Lo que se ha hecho:

1. **Servicio de Dominio** (`GuardarPedidoDesdeJSONService`)
   - Recibe JSON del frontend
   - Descompone en tablas relacionales
   - Maneja transacciones automáticamente
   - Procesa imágenes (conversión a WebP)

2. **Validador** (`PedidoJSONValidator`)
   - Valida estructura del JSON
   - Mensajes de error descriptivos
   - Reglas exhaustivas

3. **Controlador Refactorizado** (`GuardarPedidoJSONController`)
   - Solo responsable de HTTP
   - Delega lógica al servicio
   - Manejo de errores robusto
   - Logging completo

4. **Modelos Eloquent**
   - `PedidosProcesosPrendaDetalle` - Procesos productivos
   - `PedidosProcessImagenes` - Imágenes de procesos
   - `TipoProceso` - Catálogo de procesos

5. **Rutas API**
   - `POST /api/pedidos/guardar-desde-json` - Guardar
   - `POST /api/pedidos/validar-json` - Solo validar

---

## 🏗️ ARQUITECTURA

### CAPA 1: CONTROLADOR HTTP

**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/GuardarPedidoJSONController.php`

**Responsabilidades:**
-  Recibir request HTTP
-  Extraer datos del body
-  Llamar al validador
-  Delegar al servicio
-  Retornar respuesta HTTP

**No hace:**
-  Lógica de negocio
-  Acceso a BD
-  Transacciones
-  Transformación de datos

### CAPA 2: VALIDADOR

**Archivo:** `app/Domain/PedidoProduccion/Validators/PedidoJSONValidator.php`

**Responsabilidades:**
-  Validar estructura del JSON
-  Validar tipos de datos
-  Validar relaciones (FK)
-  Validar tamaños de archivos

**Reglas clave:**
```php
'pedido_produccion_id' => 'required|integer|min:1',
'prendas' => 'required|array|min:1',
'prendas.*.nombre_prenda' => 'required|string|max:255',
'prendas.*.variantes' => 'required|array|min:1',
'prendas.*.variantes.*.talla' => 'required|string|max:50',
'prendas.*.variantes.*.cantidad' => 'required|integer|min:1',
'prendas.*.procesos.*.tipo_proceso_id' => 'required|integer',
```

### CAPA 3: SERVICIO DE DOMINIO (TRANSACCIONAL)

**Archivo:** `app/Domain/PedidoProduccion/Services/GuardarPedidoDesdeJSONService.php`

**Responsabilidades:**
-  Recibir JSON validado
-  Crear transacción DB
-  Descomponer JSON
-  Guardar en tablas relacionales
-  Procesar imágenes
-  Actualizar cantidad_total
-  Rollback automático en errores

**Métodos principales:**

```php
guardar(int $pedidoId, array $prendas): array
  ├─ DB::transaction()
  │  ├─ Para cada prenda:
  │  │  ├─ guardarPrenda()
  │  │  │  ├─ crearPrendaPedido()
  │  │  │  ├─ guardarFotosPrenda()
  │  │  │  ├─ guardarFotosTelas()
  │  │  │  ├─ guardarVariantes()
  │  │  │  └─ guardarProcesos()
  │  │  │     └─ guardarImagenesProceso()
  │  └─ UPDATE pedido.cantidad_total
  └─ return resultado
```

---

## 💾 FLUJO DE GUARDADO EN DETALLE

### Paso 1: Pedido Base
```php
$prendaPedido = $pedido->prendas()->create([
    'nombre_prenda' => 'Polo',
    'descripcion' => 'Polo con bordado frontal',
    'genero' => 'dama',
    'de_bodega' => true,
]);
// Inserta en: prendas_pedido
```

### Paso 2: Variantes (Tallas)
```php
$prendaPedido->variantes()->create([
    'talla' => 'M',
    'cantidad' => 50,
    'color_id' => 1,
    'tela_id' => 5,
    'tipo_manga_id' => 2,
    'manga_obs' => 'Manga corta',
    'tipo_broche_boton_id' => 3,
    'broche_boton_obs' => 'Botón blanco',
    'tiene_bolsillos' => true,
    'bolsillos_obs' => 'Bolsillos laterales',
]);
// Inserta en: prenda_variantes
// Crea 1 variante por talla × color × tela × etc.
```

### Paso 3: Fotos de Prenda
```php
$prendaPedido->fotos()->create([
    'ruta_original' => 'storage/prendas/1/imagen_original.png',
    'ruta_webp' => 'storage/prendas/1/imagen_original.webp',
    'orden' => 1,
]);
// Inserta en: prenda_fotos_pedido
```

### Paso 4: Fotos de Telas
```php
$prendaPedido->fotosTelas()->create([
    'tela_id' => 5,
    'color_id' => 1,
    'ruta_original' => 'storage/telas/1/tela_blanca.png',
    'ruta_webp' => 'storage/telas/1/tela_blanca.webp',
    'tamaño' => 2048576,
    'observaciones' => 'Tela algodon 100%',
]);
// Inserta en: prenda_fotos_tela_pedido
```

### Paso 5: Procesos
```php
$proceso = $prendaPedido->procesos()->create([
    'tipo_proceso_id' => 3, // Bordado
    'ubicaciones' => json_encode(['Frente', 'Espalda']),
    'observaciones' => 'Bordado en punto de cruz',
    'tallas_dama' => json_encode(['S', 'M', 'L']),
    'tallas_caballero' => null,
    'estado' => 'PENDIENTE',
]);
// Inserta en: pedidos_procesos_prenda_detalles
```

### Paso 6: Imágenes de Procesos
```php
$proceso->imagenes()->create([
    'ruta_original' => 'storage/procesos/1/bordado_muestra.png',
    'ruta_webp' => 'storage/procesos/1/bordado_muestra.webp',
    'orden' => 1,
    'es_principal' => true,
]);
// Inserta en: pedidos_procesos_imagenes
```

### Paso 7: Cantidad Total
```php
$pedido->update([
    'cantidad_total' => 150, // Suma de todas las variantes
]);
```

---

## 🔄 JSON FRONTEND vs BD

### ¿QUÉ LLEGA DEL FRONTEND? (JSON)

```javascript
{
  pedido_produccion_id: 1,
  prendas: [
    {
      nombre_prenda: "Polo",
      descripcion: "Polo con bordado",
      genero: "dama",
      de_bodega: true,
      
      // ARCHIVOS (FormData)
      fotos_prenda: [File, File, ...],
      fotos_tela: [
        {
          tela_id: 5,
          color_id: 1,
          archivo: File,
          observaciones: "Algodon"
        }
      ],
      
      // VARIANTES (NO ARCHIVOS)
      variantes: [
        {
          talla: "S",
          cantidad: 30,
          color_id: 1,
          tela_id: 5,
          tipo_manga_id: 2,
          manga_obs: "Corta",
          tipo_broche_boton_id: 3,
          broche_boton_obs: "Blanco",
          tiene_bolsillos: true,
          bolsillos_obs: "Laterales"
        },
        {
          talla: "M",
          cantidad: 50,
          // ...
        }
      ],
      
      // PROCESOS
      procesos: [
        {
          tipo_proceso_id: 3, // Bordado
          ubicaciones: ["Frente", "Espalda"],
          observaciones: "Punto de cruz",
          tallas_dama: ["S", "M", "L"],
          tallas_caballero: null,
          imagenes: [File, File, ...]
        }
      ]
    }
  ]
}
```

### ¿QUÉ SE GUARDA EN BD? (Tablas relacionales)

| Tabla | Registros | Descripción |
|-------|-----------|---|
| `prendas_pedido` | 1 por prenda | Datos base de prenda |
| `prenda_variantes` | N (tallas × colores × telas) | Combinaciones reales |
| `prenda_fotos_pedido` | M (fotos de prenda) | Imágenes de referencia |
| `prenda_fotos_tela_pedido` | K (fotos de telas) | Imágenes de telas |
| `pedidos_procesos_prenda_detalles` | L (procesos) | Bordados, estampados, etc. |
| `pedidos_procesos_imagenes` | P (imágenes de procesos) | Fotos de procesos |

**NO se guarda JSON directamente.**

---

## 🚀 CÓMO USAR

### 1. FRONTEND ENVIA JSON

```javascript
const datosJSON = {
  pedido_produccion_id: 1,
  prendas: [...]
};

// Crear FormData para archivos
const formData = new FormData();
formData.append('pedido_produccion_id', datosJSON.pedido_produccion_id);
formData.append('prendas', JSON.stringify(datosJSON.prendas));
// ... agregar archivos a FormData

// Enviar
fetch('/api/pedidos/guardar-desde-json', {
  method: 'POST',
  headers: {
    'X-CSRF-TOKEN': token
  },
  body: formData
}).then(...)
```

### 2. BACKEND RECIBE Y VALIDA

```php
POST /api/pedidos/guardar-desde-json

// El controlador:
$datos = $request->all();
$validacion = PedidoJSONValidator::validar($datos);
if (!$validacion['valid']) {
    return error 422
}
```

### 3. SERVICIO GUARDA EN TRANSACCIÓN

```php
$resultado = $this->guardarService->guardar($pedidoId, $prendas);

// Dentro de DB::transaction():
// - Crea prendas_pedido
// - Crea prenda_variantes
// - Crea fotos
// - Crea procesos
// - Actualiza cantidad_total
// Si error → ROLLBACK automático
```

### 4. RESPUESTA AL FRONTEND

```json
{
  "success": true,
  "message": "Pedido guardado correctamente",
  "pedido_id": 1,
  "numero_pedido": "PED-001",
  "cantidad_prendas": 2,
  "cantidad_items": 150,
  "prendas": [
    {
      "prenda_pedido_id": 5,
      "nombre_prenda": "Polo",
      "cantidad_variantes": 3,
      "cantidad_procesos": 1,
      "cantidad_fotos_prenda": 2,
      "cantidad_fotos_telas": 1
    }
  ]
}
```

---

## ⚠️ MANEJO DE ERRORES

### Validación falla
```json
{
  "success": false,
  "message": "Datos inválidos",
  "errors": {
    "prendas.0.nombre_prenda": ["El nombre de la prenda es requerido"],
    "prendas.0.variantes": ["La prenda debe tener al menos una variante"]
  }
}
→ 422 Unprocessable Entity
```

### Pedido no existe
```json
{
  "success": false,
  "message": "Error al guardar pedido",
  "error": "Pedido con ID 999 no encontrado"
}
→ 500 Internal Server Error
```

### Error de transacción
```json
{
  "success": false,
  "message": "Error al guardar pedido",
  "error": "SQLSTATE[23000]: Integrity constraint..."
}
→ 500 Internal Server Error
```

---

## 🔍 LOGGING

El servicio registra todos los pasos:

```
📥 [GuardarPedidoJSONController] POST /api/pedidos/guardar-desde-json
📦 Datos recibidos: pedido_id=1, cantidad_prendas=2
 Validación exitosa
📝 [Guardado de prenda 1/2] Polo...
  ├─  Creada PrendaPedido ID=5
  ├─  Guardadas 2 fotos de prenda
  ├─  Guardadas 1 foto de tela
  ├─  Creadas 3 variantes
  └─  Creado 1 proceso
 [GuardarPedidoJSONController] Pedido guardado exitosamente
```

---

##  CHECKLIST FINAL

- [x] Servicio de dominio con transacciones
- [x] Validador exhaustivo
- [x] Controlador limpio (solo HTTP)
- [x] Modelos Eloquent con relaciones
- [x] Rutas API configuradas
- [x] Logging completo
- [x] Manejo de errores robusto
- [x] Conversión de imágenes a WebP
- [x] Rollback automático en errores
- [x] SRP y DDD implementados

---

## 📚 REFERENCIAS

### Modelos:
- `PrendaPedido` - Prenda base
- `PrendaVariante` - Variantes (talla, color, tela, etc.)
- `PrendaFotoPedido` - Fotos de prenda
- `PrendaFotoTelasPedido` - Fotos de telas
- `PedidosProcesosPrendaDetalle` - Procesos
- `PedidosProcessImagenes` - Imágenes de procesos

### Servicios:
- `GuardarPedidoDesdeJSONService` - Lógica principal
- `ImagenService` - Conversión a WebP

### Validadores:
- `PedidoJSONValidator` - Validación de estructura

### Controladores:
- `GuardarPedidoJSONController` - HTTP layer

### Rutas:
- `POST /api/pedidos/guardar-desde-json`
- `POST /api/pedidos/validar-json`

---

**Implementación completada:  PROFESIONAL GRADE**

