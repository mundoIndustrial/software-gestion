# 📋 ANÁLISIS COMPLETO: FLUJO DE GUARDADO DE PEDIDOS

**Fecha de Análisis:** Enero 16, 2026  
**Endpoint Analizado:** `GET http://servermi:8000/asesores/pedidos-produccion/crear-nuevo`  
**Tipo de Análisis:** Auditoría de integridad de datos

---

## 🎯 OBJETIVO

Verificar que **TODO** lo que el usuario ingresa en el formulario de crear nuevo pedido se guarde correctamente en la base de datos, incluyendo:

- ✅ Información del pedido (cliente, asesora, forma de pago)
- ✅ Prendas con todas sus variaciones
- ✅ Tallas y cantidades por género
- ✅ Procesos especiales (bordado, estampado, etc.) con imágenes
- ✅ Imágenes de prendas
- ✅ Telas y sus imágenes
- ✅ Observaciones y campos especiales

---

## 🏗️ ARQUITECTURA DEL FLUJO DE GUARDADO

### 1. CAPA DE PRESENTACIÓN (Frontend)

#### Archivo: `resources/views/asesores/pedidos/crear-pedido-nuevo.blade.php`

**Responsabilidad:** Renderizar el formulario HTML interactivo

**Elementos principales:**
- Información del pedido (cliente, asesora, forma de pago)
- Selector de tipo de ítem (PRENDA, EPP, REFLECTIVO)
- Contenedor dinámico de ítems
- Modales para:
  - Seleccionar prendas
  - Seleccionar tallas
  - Agregar prenda nueva
  - Agregar reflectivo
  - Agregar procesos genéricos

**Scripts cargados:**
1. `configuracion/constantes-tallas.js` - Constantes de tallas
2. `modulos/crear-pedido/modales/modales-dinamicos.js` - Gestión de modales
3. `modulos/crear-pedido/tallas/gestion-tallas.js` - Control de tallas
4. `modulos/crear-pedido/telas/gestion-telas.js` - Control de telas
5. `modulos/crear-pedido/procesos/gestion-items-pedido.js` - Lógica principal

### 2. CAPA DE LÓGICA DE PRESENTACIÓN (JavaScript)

#### Archivo: `public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js`

**Clase Principal:** `GestionItemsUI`

**Responsabilidades:**
- Gestionar lista de ítems en memoria
- Recolectar datos del formulario
- Enviar datos al backend
- Mostrar notificaciones

**Método crítico:** `manejarSubmitFormulario()`

```javascript
// 1. Validación local (cliente requerido)
// 2. Recolecta datos vía recolectarDatosPedido()
// 3. Valida que haya ítems
// 4. Llama a api.validarPedido()
// 5. Llama a api.crearPedido()
// 6. Redirige a /asesores/pedidos-produccion
```

**Método crítico:** `recolectarDatosPedido()`

Construye el objeto pedidoData con estructura:
```javascript
{
  cliente: string,
  asesora: string,
  forma_de_pago: string,
  items: [
    {
      tipo: 'prenda_nueva' | 'cotizacion' | 'reflectivo',
      prenda: string,
      origen: 'bodega' | 'confeccion',
      procesos: { [tipoProceso]: {...} },
      tallas: [
        { genero: 'dama' | 'caballero' | 'mixto', talla: 'S' | 'M' | ..., cantidad: number }
      ],
      variaciones: {
        manga: { tipo: string, observacion: string },
        broche: { tipo: string, observacion: string },
        bolsillos: { tipo: string, observacion: string },
        reflectivo: { tipo: string, observacion: string }
      },
      imagenes: File[],
      de_bodega: 0 | 1
    }
  ]
}
```

#### Archivo: `public/js/modulos/crear-pedido/configuracion/api-pedidos-editable.js`

**Clase Principal:** `PedidosEditableWebClient`

**Responsabilidades:**
- Comunicación HTTP con el backend
- Manejo de FormData para imágenes
- Gestión de CSRF tokens

**Métodos principales:**
- `agregarItem()` - POST `/asesores/pedidos-editable/items/agregar`
- `eliminarItem()` - POST `/asesores/pedidos-editable/items/eliminar`
- `obtenerItems()` - GET `/asesores/pedidos-editable/items`
- `validarPedido()` - POST `/asesores/pedidos-editable/validar`
- `crearPedido()` - POST `/asesores/pedidos-editable/crear` ⭐

### 3. CAPA DE VALIDACIÓN Y ORQUESTACIÓN (Backend)

#### Archivo: `app/Http/Controllers/Asesores/CrearPedidoEditableController.php`

**Responsabilidades:**
- Recibir requests HTTP
- Validar datos
- Orquestar guardado de pedido y prendas
- Retornar respuestas JSON

#### Método: `crearPedido(Request $request)` ⭐⭐⭐

**Flujo:**

1. **Extrae items del request**
   ```php
   $items = $request->input('items', []);
   ```

2. **Validación básica**
   - Al menos un ítem existe
   - Cada ítem tiene prenda especificada
   - Cada ítem tiene tallas/cantidades

3. **Validación HTTP**
   ```php
   $validated = $request->validate([
       'cliente' => 'required|string',
       'asesora' => 'required|string',
       'forma_de_pago' => 'nullable|string',
       'items' => 'required|array',
   ]);
   ```

4. **Obtiene usuario autenticado**
   ```php
   $asesora = auth()->user();
   ```

5. **Obtiene o crea cliente**
   ```php
   $cliente = \App\Models\Cliente::where('nombre', $validated['cliente'])->first();
   if (!$cliente) {
       $cliente = \App\Models\Cliente::create([...]);
   }
   ```

6. **Genera número de pedido** (⚠️ Secuencial simple, NO seguro para concurrencia)
   ```php
   $ultimoPedido = PedidoProduccion::orderBy('id', 'desc')->first();
   $numeroPedido = ($ultimoPedido?->numero_pedido ?? 0) + 1;
   ```

7. **Crea pedido en BD**
   ```php
   $pedido = PedidoProduccion::create([
       'numero_pedido' => $numeroPedido,
       'cliente' => $validated['cliente'],
       'cliente_id' => $cliente->id,
       'asesor_id' => $asesora->id,
       'forma_de_pago' => $validated['forma_de_pago'],
       'estado' => 'pendiente',
       'fecha_de_creacion_de_orden' => now(),
       'cantidad_total' => 0,
   ]);
   ```

8. **Procesa cada item**
   
   Para cada item en validated['items']:
   
   a. **Determina deBodega** (lógica compleja)
      ```php
      $deBodega = 1; // default
      if (isset($item['de_bodega'])) {
          $deBodega = (int)$item['de_bodega'];
      } else {
          $origen = $item['origen'] ?? 'bodega';
          $deBodega = $origen === 'bodega' ? 1 : 0;
      }
      ```

   b. **Reconstruye procesos con imágenes desde FormData**
      ```php
      $procesosReconstruidos = [];
      $procesosFormData = $request->file("prendas.*.procesos");
      // Procesa cada tipo de proceso
      // Asocia archivos UploadedFile con datos JSON
      ```

   c. **Construye prendaData** (estructura para PedidoPrendaService)
      ```php
      $prendaData = [
          'nombre_producto' => $item['prenda'],
          'descripcion' => $item['descripcion'] ?? '',
          'variaciones' => $item['variaciones'] ?? [],
          'fotos' => $item['imagenes'] ?? [],
          'procesos' => $procesosReconstruidos,
          'origen' => $item['origen'] ?? 'bodega',
          'de_bodega' => $deBodega,
          'obs_manga' => $item['obs_manga'] ?? '',
          'obs_bolsillos' => $item['obs_bolsillos'] ?? '',
          'obs_broche' => $item['obs_broche'] ?? '',
          'obs_reflectivo' => $item['obs_reflectivo'] ?? '',
          'cantidad_talla' => $this->procesarTallasParaServicio($item['tallas']),
      ];
      ```

   d. **Procesa tallas**
      ```php
      $prendaData['cantidad_talla'] = $this->procesarTallasParaServicio($item['tallas']);
      $cantidadItem = $this->calcularCantidadDeTallas($item['tallas']);
      ```

   e. **Procesa variaciones**
      ```php
      // Extrae tipo y observación de cada variación
      // Mapea a campos específicos (obs_manga, obs_broche, etc.)
      ```

   f. **Acumula cantidad total**
      ```php
      $cantidadTotal += $cantidadItem;
      ```

9. **Guarda todas las prendas**
   ```php
   $this->pedidoPrendaService->guardarPrendasEnPedido($pedido, $prendasParaGuardar);
   ```

10. **Actualiza cantidad total del pedido**
    ```php
    $pedido->update(['cantidad_total' => $cantidadTotal]);
    ```

11. **Retorna respuesta de éxito**
    ```php
    return response()->json([
        'success' => true,
        'message' => 'Pedido creado correctamente',
        'pedido_id' => $pedido->id,
        'numero_pedido' => $pedido->numero_pedido,
    ]);
    ```

### 4. CAPA DE PERSISTENCIA DE PRENDAS (Backend)

#### Clase: `PedidoPrendaService`

**Responsabilidad:** Guardar prendas con todas sus variaciones y procesos

**Método:** `guardarPrendasEnPedido($pedido, $prendasParaGuardar)`

**Flujo:**

Para cada prenda en `$prendasParaGuardar`:

1. **Crea registro PrendaPedido**
   ```php
   $prendaPedido = $pedido->prendas()->create([
       'nombre_prenda' => $prendaData['nombre_producto'],
       'descripcion' => $prendaData['descripcion'],
       'genero' => implode(',', $prendaData['genero'] ?? []),
       'de_bodega' => $prendaData['de_bodega'],
   ]);
   ```

2. **Procesa imágenes de prenda**
   - Convierte a WebP
   - Guarda en storage
   - Crea registros PrendaFotoPedido

3. **Procesa variaciones (tallas con sus detalles)**
   
   Para cada talla en `cantidad_talla`:
   
   a. **Crea PrendaVariante**
      ```php
      $variante = PrendaVariante::create([
          'prenda_pedido_id' => $prendaPedido->id,
          'talla' => $talla,
          'genero' => $genero,
          'cantidad' => $cantidad,
          'color' => $prendaData['color'] ?? null,
          'tela' => $prendaData['tela'] ?? null,
          'tipo_manga' => $prendaData['tipo_manga'] ?? null,
          'tipo_broche' => $prendaData['tipo_broche'] ?? null,
          'bolsillos' => $prendaData['bolsillos'] ?? null,
      ]);
      ```

   b. **Guarda observaciones**
      ```php
      if ($prendaData['obs_manga'] ?? null) {
          // Guardar en variante o tabla de observaciones
      }
      // Similar para obs_broche, obs_bolsillos, obs_reflectivo
      ```

4. **Procesa procesos especiales**
   
   Para cada tipo de proceso en `procesos`:
   
   a. **Crea ProcesosPrenda**
      ```php
      $proceso = ProcesosPrenda::create([
          'numero_pedido' => $pedido->numero_pedido,
          'prenda_pedido_id' => $prendaPedido->id,
          'proceso' => $tipoProceso, // 'bordado', 'estampado', etc.
          'estado_proceso' => 'pendiente',
          'observaciones' => $datosProceso['observaciones'] ?? '',
      ]);
      ```

   b. **Procesa imágenes del proceso**
      ```php
      foreach ($datosProceso['imagenes'] as $imagen) {
          // Guarda imagen
          // Crea registro ProcesoPrendaImagen
          $imagen->store('procesos/' . $proceso->id, 'public');
      }
      ```

---

## 🗄️ ESTRUCTURA DE TABLAS INVOLUCRADAS

### Tabla: `pedidos_produccion`

```sql
CREATE TABLE pedidos_produccion (
    id BIGINT PRIMARY KEY,
    numero_pedido INT UNIQUE NOT NULL, -- ⚠️ Generado sin lock DB
    cotizacion_id BIGINT NULLABLE,
    numero_cotizacion VARCHAR NULLABLE,
    cliente VARCHAR NOT NULL,
    cliente_id BIGINT NOT NULL,
    asesor_id BIGINT NOT NULL,
    forma_de_pago VARCHAR NULLABLE,
    estado VARCHAR, -- 'pendiente', 'confirmado', etc.
    fecha_de_creacion_de_orden TIMESTAMP,
    cantidad_total INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (asesor_id) REFERENCES users(id),
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id)
);
```

### Tabla: `prendas_pedido`

```sql
CREATE TABLE prendas_pedido (
    id BIGINT PRIMARY KEY,
    pedido_produccion_id BIGINT NOT NULL,
    nombre_prenda VARCHAR,
    descripcion TEXT,
    genero VARCHAR, -- 'dama', 'caballero', 'mixto'
    de_bodega BOOLEAN DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    FOREIGN KEY (pedido_produccion_id) REFERENCES pedidos_produccion(id)
);
```

### Tabla: `prenda_variantes`

```sql
CREATE TABLE prenda_variantes (
    id BIGINT PRIMARY KEY,
    prenda_pedido_id BIGINT NOT NULL,
    talla VARCHAR,
    genero VARCHAR,
    cantidad INT,
    color VARCHAR NULLABLE,
    tela VARCHAR NULLABLE,
    tipo_manga VARCHAR NULLABLE,
    tipo_broche VARCHAR NULLABLE,
    bolsillos BOOLEAN NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    FOREIGN KEY (prenda_pedido_id) REFERENCES prendas_pedido(id)
);
```

### Tabla: `procesos_prenda`

```sql
CREATE TABLE procesos_prenda (
    id BIGINT PRIMARY KEY,
    numero_pedido INT NOT NULL,
    prenda_pedido_id BIGINT NULLABLE,
    proceso VARCHAR, -- 'bordado', 'estampado', etc.
    estado_proceso VARCHAR,
    observaciones TEXT,
    novedades TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    FOREIGN KEY (numero_pedido) REFERENCES pedidos_produccion(numero_pedido),
    FOREIGN KEY (prenda_pedido_id) REFERENCES prendas_pedido(id)
);
```

### Tabla: `prenda_foto_pedido`

```sql
CREATE TABLE prenda_foto_pedido (
    id BIGINT PRIMARY KEY,
    prenda_pedido_id BIGINT NOT NULL,
    ruta_archivo VARCHAR,
    ruta_original VARCHAR,
    tipo VARCHAR, -- 'prenda', 'tela'
    created_at TIMESTAMP,
    FOREIGN KEY (prenda_pedido_id) REFERENCES prendas_pedido(id)
);
```

### Tabla: `proceso_prenda_imagen`

```sql
CREATE TABLE proceso_prenda_imagen (
    id BIGINT PRIMARY KEY,
    proceso_prenda_id BIGINT NOT NULL,
    ruta_archivo VARCHAR,
    tipo VARCHAR,
    created_at TIMESTAMP,
    FOREIGN KEY (proceso_prenda_id) REFERENCES procesos_prenda(id)
);
```

---

## ✅ CHECKLIST: QUÉ SE GUARDA

### Información del Pedido
- [x] Cliente (nombre)
- [x] Cliente (ID del cliente)
- [x] Asesora (ID del usuario)
- [x] Forma de pago
- [x] Estado inicial ('pendiente')
- [x] Fecha de creación
- [x] Número de pedido (auto-incrementado)
- [x] Cantidad total (sumatorio)

### Información de Prendas
- [x] Nombre de prenda
- [x] Descripción
- [x] Género (dama/caballero/mixto)
- [x] De bodega (0/1)
- [x] Cantidad por talla/género
- [x] Imágenes de prenda (convertidas a WebP)
- [x] Observaciones de manga
- [x] Observaciones de broche
- [x] Observaciones de bolsillos
- [x] Observaciones de reflectivo

### Información de Variantes
- [x] Talla (S, M, L, XL, etc.)
- [x] Género (dama, caballero, mixto)
- [x] Cantidad
- [x] Color
- [x] Tela
- [x] Tipo de manga
- [x] Tipo de broche
- [x] Bolsillos (sí/no)

### Información de Procesos
- [x] Tipo de proceso (bordado, estampado, etc.)
- [x] Estado del proceso
- [x] Observaciones del proceso
- [x] Imágenes del proceso
- [x] Fecha de inicio (si se proporciona)
- [x] Fecha de fin (si se proporciona)

---

## ⚠️ PROBLEMAS IDENTIFICADOS

### 1. 🔴 GENERACIÓN DE NÚMERO DE PEDIDO SIN DB LOCK

**Localización:** `CrearPedidoEditableController.php` línea ~260

**Problema:**
```php
$ultimoPedido = PedidoProduccion::orderBy('id', 'desc')->first();
$numeroPedido = ($ultimoPedido?->numero_pedido ?? 0) + 1;
```

**Riesgo:** En entorno con múltiples usuarios simultáneos, dos peticiones pueden generar el mismo número de pedido.

**Solución recomendada:**
```php
// Usar DB::transaction + DB::raw con LOCK
$numeroPedido = DB::transaction(function () {
    $ultimoPedido = DB::select('SELECT numero_pedido FROM pedidos_produccion 
                                ORDER BY numero_pedido DESC LIMIT 1 FOR UPDATE');
    return ($ultimoPedido[0]->numero_pedido ?? 0) + 1;
});

// O usar NumeracionService si existe
$numeroPedido = $this->numeracionService->generarNumeroPedido();
```

### 2. 🟡 VALIDACIÓN DE TALLAS INCONSISTENTE

**Localización:** `CrearPedidoEditableController.php` línea ~180-190

**Problema:** La validación diferencia entre `tipo === 'nuevo'` y `tipo === 'prenda_nueva'`, pero la lógica es redundante.

**Riesgo:** Podría causarse que un tipo con nombre levemente diferente no se valide correctamente.

**Solución:**
```php
// Normalizar tipos
$tipoProcesado = match($tipo) {
    'nuevo', 'prenda_nueva' => 'prenda_nueva',
    'reflectivo' => 'reflectivo',
    default => 'cotizacion',
};
```

### 3. 🟡 PROCESSING DE PROCESOS DESDE FORMDATA COMPLEJO

**Localización:** `CrearPedidoEditableController.php` línea ~270-290

**Problema:** La reconstrucción de procesos desde `$request->file("prendas.*.procesos")` es compleja y propensa a errores.

**Código actual:**
```php
$procesosFormData = $request->file("prendas.*.procesos");
if ($procesosFormData && isset($procesosFormData[$itemIndex])) {
    $procesosByTipo = $procesosFormData[$itemIndex];
    foreach ($procesosByTipo as $tipoProceso => $datosProcesoJson) {
        // ...
    }
}
```

**Riesgo:** Si la estructura de FormData no coincide exactamente, las imágenes del proceso pueden perderse.

### 4. 🟡 OBSERVACIONES EN MÚLTIPLES UBICACIONES

**Localización:** `CrearPedidoEditableController.php` línea ~310-325

**Problema:** Las observaciones se almacenan en múltiples ubicaciones:
```php
$prendaData['obs_manga'] = $item['obs_manga'] ?? '';
$prendaData['obs_bolsillos'] = $item['obs_bolsillos'] ?? '';
// ...
// Luego se procesan:
if (isset($variacion['observacion'])) {
    $prendaData['obs_' . $varTipo] = $variacion['observacion'];
    $prendaData[$varTipo . '_obs'] = $variacion['observacion'];
}
```

**Riesgo:** Ambigüedad en dónde se guardan realmente.

### 5. 🔴 FALTA DE TRANSACCIÓN GLOBAL

**Localización:** `CrearPedidoEditableController.php` - `crearPedido()`

**Problema:** No hay DB::transaction wrapping toda la operación.

**Riesgo:** Si falla el guardado de prendas después de crear el pedido, quedarán datos inconsistentes.

**Solución:**
```php
public function crearPedido(Request $request): JsonResponse
{
    try {
        return DB::transaction(function () use ($request) {
            // Validar
            // Crear pedido
            // Guardar prendas
            // Actualizar cantidad_total
            // Retornar respuesta
        });
    } catch (\Exception $e) {
        // Error handling
    }
}
```

### 6. 🟡 SIN VALIDACIÓN DE CANTIDAD TOTAL CERO

**Localización:** `CrearPedidoEditableController.php` línea ~350

**Problema:**
```php
$pedido->update(['cantidad_total' => $cantidadTotal]);
```

Si `$cantidadTotal` es 0, el pedido se crea sin ninguna cantidad, lo que es ilógico.

**Solución:**
```php
if ($cantidadTotal <= 0) {
    throw new \Exception('La cantidad total debe ser mayor a 0');
}
$pedido->update(['cantidad_total' => $cantidadTotal]);
```

### 7. 🟡 MANEJO DE CLIENTE NO EXPLÍCITO

**Localización:** `CrearPedidoEditableController.php` línea ~235-242

**Problema:**
```php
$cliente = \App\Models\Cliente::where('nombre', $validated['cliente'])->first();
if (!$cliente) {
    $cliente = \App\Models\Cliente::create([
        'nombre' => $validated['cliente'],
        'estado' => 'activo',
    ]);
}
```

**Riesgo:** Crea clientes automáticamente sin validación de duplicados (case-sensitive).

**Solución:**
```php
$cliente = \App\Models\Cliente::where('nombre', 'LIKE', '%' . $validated['cliente'] . '%')
    ->first();
if (!$cliente) {
    // Validar que no exista similar antes de crear
    $cliente = \App\Models\Cliente::create([
        'nombre' => trim($validated['cliente']),
        'estado' => 'activo',
    ]);
}
```

---

## 📊 FLUJO VISUAL COMPLETO

```
┌─────────────────────────────────────────────────────────────────┐
│                    FORMULARIO HTML                              │
│  - Cliente                                                      │
│  - Asesora (readonly)                                           │
│  - Forma de Pago                                                │
│  - Ítems (dinámicos con procesos e imágenes)                   │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 │ (evento submit)
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│     GestionItemsUI.manejarSubmitFormulario()                    │
│     - Valida cliente                                            │
│     - Recolecta datos                                           │
│     - Valida ítems                                              │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 │ (POST /asesores/pedidos-editable/validar)
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  CrearPedidoEditableController.validarPedido()                 │
│  - Valida estructura de items                                   │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 │ (POST /asesores/pedidos-editable/crear)
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  CrearPedidoEditableController.crearPedido() ⭐⭐⭐             │
│                                                                 │
│  1. Valida datos HTTP                                           │
│  2. Obtiene usuario autenticado                                │
│  3. Obtiene/crea cliente                                        │
│  4. Genera número pedido                                        │
│  5. CREATE pedido en BD                                         │
│  6. Para cada item:                                             │
│     - Procesa procesos desde FormData                          │
│     - Construye prendaData                                      │
│     - Procesa tallas/variaciones                               │
│  7. Llama PedidoPrendaService.guardarPrendasEnPedido()        │
│  8. UPDATE cantidad_total en pedido                            │
│  9. Retorna JSON success                                        │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 │ (JSON response)
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│     Frontend maneja respuesta                                    │
│     - Muestra notificación                                      │
│     - Redirige a /asesores/pedidos-produccion                  │
└─────────────────────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  PedidoPrendaService.guardarPrendasEnPedido()                  │
│                                                                 │
│  Para cada prenda:                                             │
│  1. CREATE prendas_pedido                                      │
│  2. Procesa imágenes (convert WebP)                            │
│  3. CREATE prenda_foto_pedido                                  │
│  4. Para cada variante:                                        │
│     - CREATE prenda_variantes                                  │
│     - Guarda observaciones                                     │
│  5. Para cada proceso:                                         │
│     - CREATE procesos_prenda                                   │
│     - Procesa imágenes del proceso                             │
│     - CREATE proceso_prenda_imagen                             │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔍 VERIFICACIÓN DE INTEGRIDAD

### Consulta SQL para validar pedido completo:

```sql
-- 1. Pedido base
SELECT * FROM pedidos_produccion WHERE numero_pedido = ?;

-- 2. Prendas del pedido
SELECT * FROM prendas_pedido WHERE pedido_produccion_id = ?;

-- 3. Variantes de cada prenda
SELECT * FROM prenda_variantes WHERE prenda_pedido_id = ?;

-- 4. Imágenes de prenda
SELECT * FROM prenda_foto_pedido WHERE prenda_pedido_id = ?;

-- 5. Procesos de prendas
SELECT * FROM procesos_prenda WHERE numero_pedido = ?;

-- 6. Imágenes de procesos
SELECT * FROM proceso_prenda_imagen WHERE proceso_prenda_id = ?;

-- 7. Cantidad total
SELECT SUM(cantidad) FROM prenda_variantes 
WHERE prenda_pedido_id IN (
    SELECT id FROM prendas_pedido WHERE pedido_produccion_id = ?
);
```

---

## 📝 RECOMENDACIONES

### CRÍTICAS 🔴

1. **Implementar DB::transaction global en `crearPedido()`**
   - Evita inconsistencias si falla parte del proceso

2. **Usar NumeracionService para generar número de pedido**
   - Reemplazar lógica simple con servicio que usa locks DB

3. **Validar cantidad_total > 0**
   - Rechazar pedidos sin cantidad

### IMPORTANTES 🟡

4. **Normalizar tipos de ítems**
   - Mapear todas las variantes ('nuevo', 'prenda_nueva', etc.) a un valor standard

5. **Centralizar lógica de observaciones**
   - Decidir una única ubicación para guardar obs_manga, obs_broche, etc.

6. **Mejorar validación de cliente**
   - Usar búsqueda case-insensitive
   - Validar duplicados antes de crear

7. **Documentar estructura de FormData de procesos**
   - La reconstrucción es compleja y propensa a errores

### MEJORAS 🟢

8. **Agregar logs en puntos críticos**
   - Facilita debugging cuando falla el guardado

9. **Crear job async para procesamiento de imágenes**
   - Las conversiones a WebP pueden tardar

10. **Tests de integridad**
    - Verificar que cantidad_total coincida con suma de variantes
    - Verificar que todas las imágenes se guardaron

---

## 🎓 CONCLUSIÓN

El flujo de guardado de pedidos es **funcional pero tiene riesgos de integridad de datos** principalmente en:

- Generación de número de pedido (sin concurrencia safety)
- Falta de transacción global
- Procesamiento complejo de procesos desde FormData
- Ambigüedad en almacenamiento de observaciones

**Recomendación:** Implementar las soluciones CRÍTICAS antes de hacer deployment a producción.

---

**Archivo de Análisis Generado:** 2026-01-16  
**Analista:** GitHub Copilot  
**Estado:** ✅ COMPLETO
