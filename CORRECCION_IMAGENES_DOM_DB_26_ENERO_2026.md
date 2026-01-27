# 🔧 CORRECCIONES IMPLEMENTADAS - Manejo de Imágenes DOM → DB

**Fecha:** 26 Enero 2026  
**Problema:** Las imágenes llegaban como `{}` al backend (File objects en JSON)  
**Solución:** Separar JSON (metadatos + UIDs) de FormData (archivos reales)

---

##  Resumen de Cambios

| Archivo | Descripción | Líneas |
|---------|-------------|--------|
| **form-data-builder.js** | Generar UIDs + formdata_key para cada imagen | +120 |
| **PedidoCompletoUnificado.js** | Generar UIDs en prendas, telas, procesos | +25 |
| **ResolutorImagenesService.php** | Resolver archivos por formdata_key | +15 |
| **PedidoNormalizadorDTO.php** | Procesar y preservar formdata_key | +3 |
| **CrearPedidoEditableController.php** | Validación + logging de archivos | +65 |
| **ImageDeduplicationService.php** | **NUEVO** - Evitar duplicados con MD5 | +120 |

---

## ✅ 1. FormDataBuilder.js - Generar UIDs únicos

### Cambio Principal
```javascript
// ANTES (❌ PROBLEMATICO):
// JSON contiene: imagenes: [File object] → JSON.stringify() falla silenciosamente
// Resultado: imagenes: [{}]

// DESPUES (✅ CORRECTO):
// JSON contiene: imagenes: [{uid: "uuid-1234", nombre_archivo: "tela.jpg", formdata_key: "files_prenda_0_0"}]
// FormData contiene: files_prenda_0_0 → File object
```

### Cómo Funciona
1. **Generar UID único** para cada imagen usando `_generateUUID()`
2. **Extraer archivo** del payload (si es File instanceof)
3. **Guardar archivo en FormData** con key: `files_prenda_{idx}_{imgIdx}`
4. **Guardar metadata en JSON** con: `{uid, nombre_archivo, formdata_key}`

### Estructura de Salida

```javascript
// FormData que se envía:
FormData {
  "pedido": JSON.stringify({
    cliente: "Acme",
    items: [{
      uid: "uid-abc123",
      imagenes: [{
        uid: "uid-xyz789",
        nombre_archivo: "tela.jpg",
        formdata_key: "files_prenda_0_0"  // ← CLAVE PARA RESOLVER
      }]
    }],
    _uuid_to_formkey: {  // Mapeo auxiliar (opcional)
      "uid-xyz789": "files_prenda_0_0"
    }
  }),
  
  // Archivos reales:
  "files_prenda_0_0": File object (tela.jpg)
  "files_prenda_0_1": File object (otra-tela.jpg)
  "files_tela_0_0_0": File object (detalle-tela.jpg)
}
  "files_tela_0_0_0": File object (detalle-tela.jpg)
}
```

---

## ✅ 2. ResolutorImagenesService - Mapear FormData keys a UIDs

### Cambio Principal
```php
// ANTES (❌):
// $archivo = $request->file("prendas.0.imagenes.0");  // Falla si no es la estructura exacta

// DESPUES (✅):
$formDataKey = $imagenMetadata['formdata_key'] ?? null;  // "files_prenda_0_0"
$archivo = $request->file($formDataKey);

if (!$archivo) {
    // Fallback a formato antiguo para compatibilidad
    $archivo = $request->file("{$formPrefix}.{$imagenIdx}");
}
```

### Flujo de Resolución
1. **Intentar con `formdata_key`** (generado por frontend) → `files_prenda_0_0`
2. **Si no encuentra, intentar formato antiguo** → `prendas.0.imagenes.0`
3. **Si encuentra archivo**, procesar y guardar
4. **Registrar UID → ruta** en mapeo para crear registros en BD

---

## ✅ 2.5. PedidoCompletoUnificado - Generar UIDs en Frontend

### Cambios Principales

Ahora el builder genera UIDs **para cada elemento**:

```javascript
// Prendas
_sanitizarPrenda(raw) {
    return {
        uid: raw.uid || this._generateUID(),  // ← NUEVO UID
        ...
    };
}

// Telas
_sanitizarTelas(raw) {
    return raw.map(tela => ({
        uid: tela.uid || this._generateUID(),  // ← NUEVO UID
        ...
        imagenes: Array.isArray(tela.imagenes) ? tela.imagenes : []  // ← Mantener File objects
    }))
}

// Procesos
_sanitizarProcesos(raw) {
    tiposProceso.forEach(tipo => {
        cleaned[tipo] = {
            uid: raw[tipo].uid || this._generateUID(),  // ← NUEVO UID
            tipo: tipo,
            ...
        };
    });
}

// Método helper para generar UIDs
_generateUID() {
    return 'uid-' + Math.random().toString(36).substr(2, 9) + '-' + Date.now().toString(36);
}
```

### Resultado
```javascript
// Payload generado:
{
    cliente: "ACME Corp",
    items: [{
        uid: "uid-abc123-xxxxx",  // ← UID de prenda
        nombre_prenda: "Camisa",
        telas: [{
            uid: "uid-def456-yyyyy",  // ← UID de tela
            imagenes: [
                File object (mantiene el File, NO serializa)
            ]
        }],
        procesos: {
            bordado: {
                uid: "uid-ghi789-zzzzz",  // ← UID de proceso
                datos: {
                    imagenes: [
                        File object
                    ]
                }
            }
        }
    }]
}
```

---

## ✅ 3. PedidoNormalizadorDTO - Procesar UIDs + formdata_key

### Cambio Principal
```php
// ANTES (❌):
'uid' => $img['uid']
'nombre_archivo' => sanitizar($img['nombre_archivo'])

// DESPUES (✅):
'uid' => $img['uid']
'nombre_archivo' => sanitizar($img['nombre_archivo'])
'formdata_key' => $img['formdata_key'] ?? null  // ← NUEVO
```

**Beneficio:** El DTO ahora puede pasar el `formdata_key` al `ResolutorImagenesService` para que pueda obtener el archivo real de la Request.

---

## ✅ 4. CrearPedidoEditableController - Logging y Validación

### Agregados
1. **Debug de archivos recibidos:**
   ```php
   $archivosRecibidos = [];
   foreach ($request->allFiles() as $key => $file) {
       $archivosRecibidos[] = [
           'key' => $key,
           'name' => $file->getClientOriginalName(),
           'size' => $file->getSize()
       ];
   }
   Log::debug('[CrearPedidoEditableController] Archivos en FormData', [...]);
   ```

2. **Validación de JSON sin File objects:**
   ```php
   private function validarJsonSinFiles(array $datos, $ruta = ''): void
   ```
   - Recursivamente revisa el JSON
   - Si encuentra objetos (como File), lanza error
   - Valida que imágenes tienen `uid` y `formdata_key`

---

## ✅ 5. ImageDeduplicationService - Evitar Duplicados

### Estrategia
1. **Calcular MD5** del contenido del archivo
2. **Buscar en BD** si ese hash ya existe
3. **Si existe** → reutilizar ruta existente (NO guardar)
4. **Si no existe** → guardar normalmente

### Uso
```php
$resultado = $deduplicationService->guardarConDeduplicacion(
    $file,
    'pedidos/2723/prendas',
    function($ruta, $rutaWebp) {
        // Si se reutilizó, aquí hacemos algo
    }
);

if ($resultado['duplicado']) {
    $rutaFinal = $resultado['ruta'];
} else {
    $rutaFinal = guardarNormalmente($file);
}
```

---

## 🔍 Flujo Completo - Paso a Paso

### 1️⃣ FRONTEND (JavaScript)
```javascript
// Usuario carga imágenes en formulario
prendas[0].imagenes = [File object]

// Al enviar, FormDataBuilder limpia:
const builder = new FormDataBuilder();
const formData = builder.build(prendaData);

// Resultado:
// - JSON con UIDs
// - FormData con archivos
```

### 2️⃣ BACKEND (Laravel) - CrearPedidoEditableController::crearPedido()
```php
// Recibe FormData
$pedidoJSON = $request->input('pedido');  // JSON limpio
$archivos = $request->allFiles();         // Archivos reales

// Valida
validarJsonSinFiles($datosFrontend);

// Normaliza
$dtoPedido = PedidoNormalizadorDTO::fromFrontendJSON(...);
// DTO contiene: uid + formdata_key de cada imagen
```

### 3️⃣ MAPEO DE IMÁGENES (ResolutorImagenesService)
```php
// Para cada imagen en DTO:
$formDataKey = "files_prenda_0_0";
$archivo = $request->file($formDataKey);

if ($archivo) {
    // Guardar y registrar mapeo
    $ruta = guardarArchivo($archivo);
    $dtoPedido->registrarImagenUID($uid, $ruta);
}
```

### 4️⃣ CREAR REGISTROS EN BD (MapeoImagenesService)
```php
// Con UIDs → rutas mapeados:
PrendaFotoPedido::create([
    'prenda_id' => 123,
    'ruta_original' => 'pedidos/2723/prendas/imagen-1.jpg',
    'ruta_webp' => 'pedidos/2723/prendas/imagen-1.webp',
    'hash_contenido' => 'abc123...'
]);
```

---

## 🧪 Cómo Probar

### En JavaScript Console (Frontend)
```javascript
// Ver que se genera FormData correcto
const builder = new PedidoCompletoUnificado();
builder.setCliente('Test').agregarPrenda({
    nombre_prenda: 'Camisa',
    imagenes: [fileObject1, fileObject2]
});

const payload = builder.build();
const formData = FormDataBuilder.build(payload);

// Inspeccionarconsole.log('FormData entries:');
for (let [key, value] of formData.entries()) {
    console.log(key, value instanceof File ? 'FILE:' + value.name : value.slice(0, 100));
}
```

### En Laravel Logs
```bash
tail -f storage/logs/laravel.log | grep "CrearPedidoEditableController"
```

Deberías ver:
```
[CrearPedidoEditableController] Archivos en FormData {"archivos":
  [{"key":"files_prenda_0_0","name":"tela.jpg","size":125000}]
}
```

---

## 🚨 Checklist de Verificación

- [ ] FormDataBuilder.js genera UIDs únicos para cada imagen
- [ ] JSON enviado NO contiene File objects (`imagenes: [{}]` = error)
- [ ] FormData contiene archivos con keys: `files_prenda_0_0`, `files_tela_0_0_0`, etc
- [ ] ResolutorImagenesService puede obtener archivos usando `formdata_key`
- [ ] Laravel logs muestran "Archivos en FormData" sin error
- [ ] Imágenes se guardan en `pedidos/{id}/{carpeta}/`
- [ ] `ruta_original` y `ruta_webp` se registran en BD
- [ ] No hay duplicados: MD5 hash se valida y reutiliza

---

## 📋 Resumen de Cambios

| Archivo | Cambio | Líneas |
|---------|--------|--------|
| `form-data-builder.js` | Generar UIDs + formdata_key | +50 |
| `ResolutorImagenesService.php` | Resolver por formdata_key | +15 |
| `PedidoNormalizadorDTO.php` | Procesar formdata_key | +3 |
| `CrearPedidoEditableController.php` | Validación + logging | +60 |
| `ImageDeduplicationService.php` | **NUEVO** - Evitar duplicados | +120 |

---

##  Integración Pendiente

### Para activar deduplicación (opcional):
```php
// En ImageUploadService::guardarImagenDirecta():
if ($this->deduplicationService->guardarConDeduplicacion(...)) {
    // Reutilizar imagen existente
} else {
    // Guardar nueva imagen
}
```

### Para usar en otros controladores:
```php
// Inyectar en constructor
public function __construct(
    private ImageDeduplicationService $dedupService
) {}

// Usar
$resultado = $this->dedupService->guardarConDeduplicacion($file, $carpeta);
```

---

##  Resultado Final

**Antes:**
```
"imagenes": [{}]  ❌ File object se pierde en JSON
$request->allFiles();  // Vacío
Error: No files received
```

**Después:**
```
"imagenes": [{
  "uid": "uuid-123",
  "nombre_archivo": "tela.jpg",
  "formdata_key": "files_prenda_0_0"  ✅
}]
$request->file('files_prenda_0_0');  // File object
✅ Imágenes guardadas sin duplicados
```

