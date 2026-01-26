# 🏗️ ARQUITECTURA COMPLETA: SEPARACIÓN DOM ↔ BACKEND

## Problema Identificado

❌ **Antes (PERDIDA DE IMÁGENES):**
```javascript
// ❌ INCORRECTO: JSON.stringify no puede serializar File objects
const formData = new FormData();
formData.append('pedido', JSON.stringify({
    cliente: "Acme",
    prendas: [{
        imagenes: [File object]  // ← SE PIERDE AL STRINGIFY
    }]
}));
```

```log
[LOG] "imagenes": [{}]  ← Array vacío, File se perdió
[ERROR] $request->allFiles(); // vacío
```

---

## ✅ Solución: Separación Clara de Modelos

```
┌─────────────────────────────────────────────────────────────┐
│                     USUARIO EN NAVEGADOR                    │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│         DOM PEDIDO MODEL (Editable, con File objects)       │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ {                                                     │  │
│  │   cliente: "Acme Corp",                               │  │
│  │   prendas: [{                                         │  │
│  │     uid: "uuid-1",                                    │  │
│  │     nombre_prenda: "Camisa",                          │  │
│  │     imagenes: [{                                      │  │
│  │       uid: "img-uuid-1",                              │  │
│  │       file: File { ... },    ← ✅ File object aquí   │  │
│  │       preview: "data:image...",  ← ✅ Para mostar    │  │
│  │       nombre_archivo: "camisa.jpg"                    │  │
│  │     }]                                                │  │
│  │   }]                                                  │  │
│  │ }                                                     │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                          ↓ SOLO METADATA
┌─────────────────────────────────────────────────────────────┐
│     BACKEND PEDIDO MODEL (JSON Serializable, sin Files)     │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ {                                                     │  │
│  │   cliente: "Acme Corp",                               │  │
│  │   prendas: [{                                         │  │
│  │     uid: "uuid-1",                                    │  │
│  │     nombre_prenda: "Camisa",                          │  │
│  │     imagenes: [{                                      │  │
│  │       uid: "img-uuid-1",     ← SOLO UID para mapear   │  │
│  │       nombre_archivo: "camisa.jpg"                    │  │
│  │     }]                                                │  │
│  │   }]                                                  │  │
│  │ }                                                     │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                          ↓ 
┌─────────────────────────────────────────────────────────────┐
│          FormData (JSON + Archivos separados)                │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ pedido: "{ JSON del Backend Model }"                  │  │
│  │ prendas.0.imagenes.0: File { camisa.jpg }   ← Archivo│  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                          ↓ HTTP POST
                      BACKEND (Laravel)
                          ↓
┌─────────────────────────────────────────────────────────────┐
│        Normalizar + Resolver Referencias                    │
│  1. Extraer JSON → PedidoNormalizadorDTO                     │
│  2. Procesar archivos → ResolutorImagenesService            │
│     - Guardar en storage/                                   │
│     - Mapear UID → ruta final                               │
│  3. Crear en BD → MapeoImagenesService                      │
│     - Prendas → PrendaProduccion                            │
│     - Telas → PrendaPedidoColorTela                         │
│     - Procesos → ProcesoPrendaDetalle                       │
│  4. Asignar fotos usando UID                                │
│     - PrendaFotoPedido                                      │
│     - PrendaFotoTelaPedido                                  │
│     - ProcesoPrendaFoto                                     │
└─────────────────────────────────────────────────────────────┘
```

---

## 🚀 Implementación Paso a Paso

### PASO 1️⃣: JAVASCRIPT - Construir y Editar el Pedido (DOM)

```javascript
import { DOMPedidoModel } from './arquitectura-pedidos/DOMPedidoModel.js';

// Inicializar modelo editable
const pedidoDOM = new DOMPedidoModel();
pedidoDOM.cliente = "Acme Corp";
pedidoDOM.asesora = "María";
pedidoDOM.forma_de_pago = "Contado";

// Agregar prenda
const prenda = {
    nombre_prenda: "Camisa Corporativa",
    cantidad_talla: { dama: { S: 10, M: 5 } },
    variaciones: { tipo_manga: "Larga" },
    telas: [],
    procesos: [],
    imagenes: []
};
pedidoDOM.agregarPrenda(prenda);

// Usuario sube una imagen de prenda
document.getElementById('input-imagenes-prenda-0').addEventListener('change', (e) => {
    const archivo = e.target.files[0];
    if (archivo) {
        const imagenDom = pedidoDOM.agregarImagenPrenda(0, archivo);
        
        // Mostrar preview en HTML
        const img = document.createElement('img');
        img.src = imagenDom.preview;
        img.alt = imagenDom.nombre_archivo;
        document.getElementById('previews-prendas-0').appendChild(img);
    }
});
```

**Ventajas:**
- ✅ File objects intactos (para previews)
- ✅ Edición en tiempo real
- ✅ Nunca se serializa a JSON

---

### PASO 2️⃣: JAVASCRIPT - Convertir a Modelo Backend

```javascript
import { BackendPedidoModel } from './arquitectura-pedidos/BackendPedidoModel.js';

// Convertir modelo DOM a modelo Backend (SOLO metadata)
const pedidoBackend = BackendPedidoModel.fromDOMPedido(pedidoDOM);

// ✅ Resultado: JSON serializable
console.log(JSON.stringify(pedidoBackend, null, 2));
// {
//   "cliente": "Acme Corp",
//   "prendas": [{
//     "uid": "1738000000-abc123",
//     "nombre_prenda": "Camisa",
//     "imagenes": [{
//       "uid": "1738000001-def456",
//       "nombre_archivo": "camisa.jpg"
//     }]
//   }]
// }
```

**Ventajas:**
- ✅ No contiene File objects
- ✅ Contiene UIDs únicos para resolver referencias
- ✅ 100% JSON serializable

---

### PASO 3️⃣: JAVASCRIPT - Construir FormData

```javascript
import { PedidoFormDataBuilder } from './arquitectura-pedidos/PedidoFormDataBuilder.js';

// Construir FormData
const formDataBuilder = new PedidoFormDataBuilder(pedidoDOM);
const formData = formDataBuilder
    .agregarPedidoJSON(pedidoBackend)  // Agregar JSON metadata
    .agregarTodasLasImagenes()         // Agregar archivos automáticamente
    .construir();

// ✅ FormData ahora contiene:
// - pedido: JSON string (metadata)
// - prendas.0.imagenes.0: File object
// - prendas.0.telas.0.imagenes.0: File object
// - prendas.0.procesos.0.imagenes.0: File object

console.log('FormData keys:', Array.from(formData.keys()));
// ['pedido', 'prendas.0.imagenes.0', 'prendas.0.telas.0.imagenes.0', ...]
```

**Ventajas:**
- ✅ Archivos y metadata juntos
- ✅ Rutas de FormData respetan estructura
- ✅ Backend puede resolver fácilmente

---

### PASO 4️⃣: JAVASCRIPT - Enviar al Backend

```javascript
import { PedidoService } from './arquitectura-pedidos/PedidoService.js';

async function guardarPedido() {
    const service = new PedidoService('/asesores/pedidos-editable/crear');
    
    try {
        const resultado = await service.crearPedido({
            cliente: pedidoDOM.cliente,
            asesora: pedidoDOM.asesora,
            forma_de_pago: pedidoDOM.forma_de_pago,
            prendas: pedidoDOM.prendas,
            epps: pedidoDOM.epps
        });
        
        console.log('✅ Pedido creado:', resultado);
        console.log('ID:', resultado.pedido_id);
        console.log('Número:', resultado.numero_pedido);
        
    } catch (error) {
        console.error('❌ Error:', error.message);
    }
}
```

---

### PASO 5️⃣: LARAVEL - Normalizar y Resolver Referencias

```php
<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Domain\Pedidos\DTOs\PedidoNormalizadorDTO;
use App\Domain\Pedidos\Services\ResolutorImagenesService;
use App\Domain\Pedidos\Services\MapeoImagenesService;

class CrearPedidoEditableController extends Controller
{
    public function __construct(
        private PedidoWebService $pedidoWebService,
        private ResolutorImagenesService $resolutorImagenes,
        private MapeoImagenesService $mapeoImagenes,
        private ImageUploadService $imageUploadService,
        private ColorTelaService $colorTelaService
    ) {}

    /**
     * POST /asesores/pedidos-editable/crear
     * 
     * Ahora completamente refactorizado para manejar referencias correctamente
     */
    public function crearPedido(Request $request): JsonResponse
    {
        $pedidoId = null;

        try {
            // ====== PASO 1: Decodificar JSON del frontend ======
            $pedidoJSON = $request->input('pedido');
            if (!$pedidoJSON) {
                throw new \Exception('Campo "pedido" JSON requerido');
            }

            $datosFrontend = json_decode($pedidoJSON, true);
            if (!$datosFrontend) {
                throw new \Exception('JSON inválido en campo "pedido"');
            }

            // ====== PASO 2: Obtener/crear cliente ======
            $clienteNombre = trim($datosFrontend['cliente'] ?? '');
            $cliente = $this->obtenerOCrearCliente($clienteNombre);

            // ====== PASO 3: Normalizar usando DTO ======
            $dtoPedido = PedidoNormalizadorDTO::fromFrontendJSON(
                $datosFrontend,
                $cliente->id
            );

            Log::info('[CrearPedidoEditableController] DTO Normalizado', [
                'cliente_id' => $cliente->id,
                'prendas' => count($dtoPedido->prendas),
                'estructura_correcta' => true
            ]);

            // ====== PASO 4: Iniciar transacción ======
            DB::beginTransaction();

            // ====== PASO 5: Crear pedido base ======
            $pedido = $this->pedidoWebService->crearPedidoCompleto(
                (array)$dtoPedido,
                Auth::id()
            );

            $pedidoId = $pedido->id;

            Log::info('[CrearPedidoEditableController] Pedido base creado', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido
            ]);

            // ====== PASO 6: Crear carpetas ======
            $this->crearCarpetasPedido($pedidoId);

            // ====== PASO 7: CRÍTICO - Mapear y procesar imágenes ======
            $this->mapeoImagenes->mapearYCrearFotos(
                $dtoPedido,      // DTO con referencias
                $pedidoId,       // ID del pedido creado
                $request         // Request con archivos
            );

            Log::info('[CrearPedidoEditableController] Imágenes mapeadas', [
                'pedido_id' => $pedidoId,
                'imagenes_mapeadas' => count($dtoPedido->imagen_uid_a_ruta)
            ]);

            // ====== PASO 8: Calcular cantidades y commit ======
            $cantidadTotal = $this->calcularCantidadTotal($pedidoId);
            $pedido->update(['cantidad_total' => $cantidadTotal]);

            DB::commit();

            Log::info('[CrearPedidoEditableController] TRANSACCIÓN EXITOSA', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('[CrearPedidoEditableController] ERROR', [
                'error' => $e->getMessage(),
                'pedido_id' => $pedidoId
            ]);

            // Limpiar carpeta si se creó
            if ($pedidoId) {
                Storage::disk('public')->deleteDirectory("pedidos/{$pedidoId}");
            }

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

---

## ❌ QUÉ NO HACER (Antipatterns)

### ❌ 1. NO serializar File objects a JSON
```javascript
// ❌ INCORRECTO
JSON.stringify({ imagenes: [File object] })  // Se pierde
```

### ❌ 2. NO clonar profundamente estructuras con Files
```javascript
// ❌ INCORRECTO
const copia = JSON.parse(JSON.stringify(pedido));  // File se pierde
```

### ❌ 3. NO enviar archivos en FormData sin estructura clara
```javascript
// ❌ INCORRECTO
const formData = new FormData();
formData.append('archivos[]', file1);
formData.append('archivos[]', file2);
// Backend no sabe a qué prenda/tela/proceso pertenecen
```

### ❌ 4. NO reutilizar mismos objetos entre DOM y Backend
```javascript
// ❌ INCORRECTO
const pedido = construirPedido();  // Con File objects
const json = JSON.stringify(pedido);  // Falla
const formData = new FormData();
formData.append('pedido', json);
```

### ❌ 5. NO ignorar UIDs únicos
```javascript
// ❌ INCORRECTO
{ imagenes: [{ nombre: "foto.jpg" }] }  // ¿Cómo mapear luego?
// ✅ CORRECTO
{ imagenes: [{ uid: "uuid-1", nombre: "foto.jpg" }] }
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

### Frontend (JavaScript)

- [ ] Copiar archivos a `public/js/arquitectura-pedidos/`:
  - [ ] `ImageReference.js`
  - [ ] `DOMPedidoModel.js`
  - [ ] `BackendPedidoModel.js`
  - [ ] `PedidoFormDataBuilder.js`
  - [ ] `PedidoService.js`

- [ ] En tu formulario de creación:
  ```javascript
  import { PedidoService } from './arquitectura-pedidos/PedidoService.js';
  ```

- [ ] Cambiar el envío:
  ```javascript
  // ❌ Antes (pierde imágenes)
  // POST con JSON.stringify
  
  // ✅ Después
  const service = new PedidoService();
  const resultado = await service.crearPedido(datosPedido);
  ```

### Backend (Laravel)

- [ ] Copiar DTOs a `app/Domain/Pedidos/DTOs/`:
  - [ ] `PedidoNormalizadorDTO.php`

- [ ] Copiar Services a `app/Domain/Pedidos/Services/`:
  - [ ] `ResolutorImagenesService.php`
  - [ ] `MapeoImagenesService.php`

- [ ] Inyectar en `CrearPedidoEditableController`:
  ```php
  public function __construct(
      private ResolutorImagenesService $resolutorImagenes,
      private MapeoImagenesService $mapeoImagenes,
      // ... otros services
  ) {}
  ```

- [ ] Reemplazar la lógica en `crearPedido()` con el código del paso 5️⃣

- [ ] Asegurar que tus modelos tengan los campos necesarios:
  - [ ] `PrendaFotoPedido.prenda_pedido_id`
  - [ ] `PrendaFotoTelaPedido.prenda_pedido_colores_telas_id`
  - [ ] `ProcesoPrendaFoto.proceso_prenda_detalle_id`

---

## 📊 Diagrama de Flujo Completo

```
┌─────────────────────────────────────────────────────────────────┐
│  USUARIO: Carga imágenes y rellena formulario en Blade HTML     │
└─────────────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────────────┐
│  JavaScript: Construir DOMPedidoModel (con File objects)        │
│  - Agregar prendas, telas, procesos                             │
│  - Agregar imágenes (File + preview)                            │
└─────────────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────────────┐
│  JavaScript: Convertir a BackendPedidoModel                     │
│  - Extraer SOLO metadata (UID + nombre_archivo)                 │
│  - Eliminar File objects                                        │
└─────────────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────────────┐
│  JavaScript: Construir FormData                                 │
│  - Field "pedido": JSON del BackendPedidoModel                  │
│  - Fields "prendas.0.imagenes.0": File objects                  │
│  - Fields "prendas.0.telas.0.imagenes.0": File objects          │
└─────────────────────────────────────────────────────────────────┘
                        ↓
        ┌──────────────────────────────────┐
        │    POST /asesores/pedidos-editable/crear
        │  Content-Type: multipart/form-data
        │  Body: FormData con JSON + archivos
        └──────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────────────┐
│  Laravel: CrearPedidoEditableController::crearPedido()         │
│                                                                 │
│  1. Decodificar JSON → $datosFrontend                           │
│  2. Obtener/crear Cliente                                       │
│  3. Normalizar → PedidoNormalizadorDTO (con UIDs)               │
│  4. Crear Pedido base en BD                                     │
│  5. Crear carpetas/pedidos/{id}/                                │
│  6. MapeoImagenesService::mapearYCrearFotos()                   │
│     ├─ ResolutorImagenesService::extraerYProcesar()            │
│     │  ├─ Extraer archivos de Request                          │
│     │  ├─ Guardar en storage/pedidos/{id}/{tipo}/               │
│     │  ├─ Mapear UID → ruta final                               │
│     │  └─ Registrar en DTO                                      │
│     │                                                            │
│     └─ Crear registros en BD                                    │
│        ├─ PrendaFotoPedido (UID → ID prenda → ruta)            │
│        ├─ PrendaFotoTelaPedido (UID → ID tela → ruta)          │
│        └─ ProcesoPrendaFoto (UID → ID proceso → ruta)          │
│                                                                 │
│  7. Calcular cantidad_total                                     │
│  8. COMMIT transacción                                          │
└─────────────────────────────────────────────────────────────────┘
                        ↓
        ┌──────────────────────────────────┐
        │  Response JSON
        │  {
        │    success: true,
        │    pedido_id: 2722,
        │    numero_pedido: 100008
        │  }
        └──────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────────────┐
│  ✅ IMÁGENES GUARDADAS Y MAPEADAS CORRECTAMENTE                 │
│  - storage/pedidos/2722/prendas/000.webp                        │
│  - storage/pedidos/2722/telas/001.webp                          │
│  - storage/pedidos/2722/procesos/bordado/002.webp               │
│                                                                 │
│  - PrendaFotoPedido.id = 1 → prenda_pedido_id = 3432            │
│  - PrendaFotoTelaPedido.id = 1 → tela_id = 60                   │
│  - ProcesoPrendaFoto.id = 1 → proceso_id = 77                   │
└─────────────────────────────────────────────────────────────────┘
```

---

##  Casos de Uso

### Caso 1: Crear pedido con solo prendas (sin telas ni procesos)

```javascript
const pedido = new DOMPedidoModel();
pedido.cliente = "Cliente A";
pedido.agregarPrenda({
    nombre_prenda: "Camiseta",
    imagenes: [File, File, File]  // ← Solo imágenes de prenda
});
await new PedidoService().crearPedido({
    cliente: pedido.cliente,
    prendas: pedido.prendas
});
```

**Backend:**
- ✅ Crea PrendaProduccion
- ✅ Crea PrendaFotoPedido x3 (una por archivo)
- ✅ Mapea usando UIDs

---

### Caso 2: Crear pedido completo (prendas + telas + procesos + imágenes)

```javascript
const pedido = new DOMPedidoModel();
pedido.cliente = "Cliente B";

const prenda = pedido.agregarPrenda({
    nombre_prenda: "Polo Corporativo",
    cantidad_talla: { dama: { S: 10, M: 5 } },
    telas: [{
        tela_id: 64,
        color_id: 50,
        imagenes: [File]  // ← Imagen de tela
    }],
    procesos: [{
        nombre: "bordado",
        ubicaciones: ["pecho"],
        imagenes: [File]  // ← Imagen de proceso
    }],
    imagenes: [File]  // ← Imagen de prenda
});

await new PedidoService().crearPedido({
    cliente: pedido.cliente,
    prendas: pedido.prendas
});
```

**Backend:**
- ✅ Crea PrendaProduccion
- ✅ Crea PrendaPedidoColorTela (la tela)
- ✅ Crea ProcesoPrendaDetalle (el proceso)
- ✅ Mapea imágenes x3 a sus respectivas entidades usando UIDs

---

## 🔍 Debugging

### Ver logs en Laravel
```bash
tail -f storage/logs/laravel.log | grep "\[ResolutorImagenesService\]\|\[MapeoImagenesService\]"
```

### Verificar archivos guardados
```bash
ls -la storage/app/public/pedidos/2722/prendas/
ls -la storage/app/public/pedidos/2722/telas/
ls -la storage/app/public/pedidos/2722/procesos/bordado/
```

### Verificar mapeos en BD
```sql
-- Ver imágenes mapeadas a prenda
SELECT * FROM prenda_foto_pedido WHERE prenda_pedido_id = 3432;

-- Ver imágenes mapeadas a tela
SELECT * FROM prenda_foto_tela_pedido WHERE prenda_pedido_colores_telas_id = 60;

-- Ver imágenes mapeadas a proceso
SELECT * FROM proceso_prenda_foto WHERE proceso_prenda_detalle_id = 77;
```

---

## 📚 Referencias

- [Archivo 1: ImageReference.js](public/js/arquitectura-pedidos/ImageReference.js)
- [Archivo 2: DOMPedidoModel.js](public/js/arquitectura-pedidos/DOMPedidoModel.js)
- [Archivo 3: BackendPedidoModel.js](public/js/arquitectura-pedidos/BackendPedidoModel.js)
- [Archivo 4: PedidoFormDataBuilder.js](public/js/arquitectura-pedidos/PedidoFormDataBuilder.js)
- [Archivo 5: PedidoService.js](public/js/arquitectura-pedidos/PedidoService.js)
- [Archivo 6: PedidoNormalizadorDTO.php](app/Domain/Pedidos/DTOs/PedidoNormalizadorDTO.php)
- [Archivo 7: ResolutorImagenesService.php](app/Domain/Pedidos/Services/ResolutorImagenesService.php)
- [Archivo 8: MapeoImagenesService.php](app/Domain/Pedidos/Services/MapeoImagenesService.php)
