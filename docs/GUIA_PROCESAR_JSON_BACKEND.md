# 📘 GUÍA: PROCESAR JSON + FormData EN BACKEND

**Fecha:** Enero 16, 2026  
**Audiencia:** Backend engineers  
**Propósito:** Entender la estructura del flujo JSON → FormData  

---

##  ESTRUCTURA RECIBIDA EN BACKEND

### FormData Recibido

```
POST /api/pedidos/guardar-desde-json

FormData {
    pedido_produccion_id: "1",
    prendas: '{"prendas":[...]}'              // ← JSON limpio
    
    prenda_0_foto_0: <File>,                  // ← Foto de prenda
    prenda_0_tela_0: <File>,                  // ← Foto de tela
    
    prenda_0_proceso_0_img_0: <File>,        // ← Imagen de proceso
    prenda_0_proceso_1_img_0: <File>,        // ← Otra imagen de otro proceso
}
```

---

##  DESCIFRANDO LA ESTRUCTURA

### JSON String

```javascript
// Valor de FormData['prendas']
{
  "pedido_produccion_id": 1,
  "prendas": [
    {
      "nombre_prenda": "Polo",
      "descripcion": "Polo premium",
      "genero": "M",
      "de_bodega": false,
      
      "variantes": [
        {
          "talla": "M",
          "cantidad": 10,
          "color_id": 1,
          "tela_id": 2,
          "tipo_manga_id": 1,
          "tipo_broche_boton_id": null,
          "tiene_bolsillos": true,
          "bolsillos_obs": ""
        }
      ],
      
      "fotos_prenda": [
        {
          "nombre": "frente.jpg",
          "observaciones": "Vista frontal"
        }
      ],
      
      "fotos_tela": [
        {
          "nombre": "tela_azul.jpg",
          "color": "Azul",
          "observaciones": "Tela importada"
        }
      ],
      
      "procesos": [
        {
          "tipo_proceso_id": 2,
          "ubicaciones": ["pecho", "espalda"],
          "observaciones": "Bordado personalizado"
        }
      ]
    }
  ]
}
```

### FormData Files

| Key | Significado | Correlación JSON |
|-----|-------------|-----------------|
| `prenda_0_foto_0` | Foto de prenda #0, índice 0 | `prendas[0].fotos_prenda[0]` |
| `prenda_0_tela_0` | Foto de tela de prenda #0, índice 0 | `prendas[0].fotos_tela[0]` |
| `prenda_0_proceso_0_img_0` | Imagen de proceso #0, índice 0 | `prendas[0].procesos[0]` |
| `prenda_0_proceso_1_img_0` | Imagen de proceso #1, índice 0 | `prendas[0].procesos[1]` |

---

##  PSEUDOCÓDIGO PARA PROCESAR

### Paso 1: Extraer y validar JSON

```php
// Laravel
Route::post('/api/pedidos/guardar-desde-json', function (Request $request) {
    //  Obtener JSON string
    $prendasJson = $request->input('prendas');
    
    //  Parsear a array
    $prendas = json_decode($prendasJson, true);
    
    //  Validar que JSON es válido
    if (json_last_error() !== JSON_ERROR_NONE) {
        return response()->json([
            'success' => false,
            'message' => 'JSON inválido: ' . json_last_error_msg()
        ], 400);
    }
    
    //  Validar estructura esperada
    if (!is_array($prendas)) {
        return response()->json([
            'success' => false,
            'message' => 'Prendas debe ser un array'
        ], 400);
    }
});
```

### Paso 2: Procesar cada prenda

```php
foreach ($prendas as $prendaIdx => $prendaData) {
    //  Crear prenda
    $prenda = new Prenda([
        'nombre_prenda' => $prendaData['nombre_prenda'],
        'descripcion' => $prendaData['descripcion'],
        'genero' => $prendaData['genero'],
        'de_bodega' => $prendaData['de_bodega']
    ]);
    $prenda->save();
    
    //  Procesar variantes
    foreach ($prendaData['variantes'] as $varianteData) {
        $variante = new Variante($varianteData);
        $variante->prenda_id = $prenda->id;
        $variante->save();
    }
    
    //  Procesar fotos de prenda
    foreach ($prendaData['fotos_prenda'] as $fotoIdx => $fotoData) {
        // Key en FormData: prenda_0_foto_0
        $fileKey = "prenda_{$prendaIdx}_foto_{$fotoIdx}";
        
        if ($request->hasFile($fileKey)) {
            $file = $request->file($fileKey);
            
            // Guardar archivo
            $path = $file->store("prendas/{$prenda->id}", 'public');
            
            // Crear registro en DB
            FotoPrenda::create([
                'prenda_id' => $prenda->id,
                'archivo' => $path,
                'nombre' => $fotoData['nombre'],
                'observaciones' => $fotoData['observaciones']
            ]);
        }
    }
    
    //  Procesar procesos y sus imágenes
    foreach ($prendaData['procesos'] as $procesoIdx => $procesoData) {
        // Crear proceso
        $proceso = new Proceso([
            'tipo_proceso_id' => $procesoData['tipo_proceso_id'],
            'ubicaciones' => json_encode($procesoData['ubicaciones']),
            'observaciones' => $procesoData['observaciones']
        ]);
        $proceso->prenda_id = $prenda->id;
        $proceso->save();
        
        // Procesar imágenes del proceso
        //  IMPORTANTE: Notar que el JSON NO contiene imagenes
        // Las imagenes vienen SOLO en FormData
        
        // Buscar imágenes de este proceso
        for ($imgIdx = 0; $imgIdx < 100; $imgIdx++) { // Asumir max 100 imagenes
            // Key en FormData: prenda_0_proceso_0_img_0
            $fileKey = "prenda_{$prendaIdx}_proceso_{$procesoIdx}_img_{$imgIdx}";
            
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $path = $file->store("procesos/{$proceso->id}", 'public');
                
                ImagenProceso::create([
                    'proceso_id' => $proceso->id,
                    'archivo' => $path,
                    'nombre' => $file->getClientOriginalName()
                ]);
            } else {
                break; // No hay más imágenes
            }
        }
    }
}
```

---

##  PUNTOS CRÍTICOS

### 1. Índices deben coincidir exactamente

```
JSON: prendas[0].fotos_prenda[0]
FormData: prenda_0_foto_0   Coincide

JSON: prendas[0].procesos[1].imagenes (NO EXISTE EN JSON)
FormData: prenda_0_proceso_1_img_0   Correlación válida
```

### 2. JSON NO contiene File objects

```javascript
//  NUNCA verás esto en el JSON:
{
  "fotos": [{
    "file": {},  //  NO ESTÁ
    "nombre": "x.jpg"
  }]
}

//  SIEMPRE verás esto:
{
  "fotos": [{
    "nombre": "x.jpg"  //  SIN file
  }]
}
```

### 3. Los archivos vienen separados en FormData

```
// Las fotos NUNCA vienen en el JSON
// Siempre están en FormData con su propia key

JSON: { fotos: [{ nombre: "x.jpg" }] }
FormData: prenda_0_foto_0 = <File>   Correlacionable
```

### 4. Metadatos en JSON, archivos en FormData

```
JSON: Estructura, validación, relaciones
FormData: Archivos binarios

Ejemplo:
- JSON → tipo_proceso_id, ubicaciones, observaciones
- FormData → archivo binario de imagen
```

---

## 🧪 CASOS DE VALIDACIÓN

### Validación 1: JSON válido

```php
$json = $request->input('prendas');
if (json_decode($json) === null) {
    throw new InvalidJsonException('JSON inválido');
}
```

### Validación 2: Estructura esperada

```php
$prendas = json_decode($request->input('prendas'), true);

foreach ($prendas as $prenda) {
    //  Verificar campos obligatorios
    Assert::notEmpty($prenda['nombre_prenda']);
    Assert::notEmpty($prenda['genero']);
    
    //  Verificar arrays esperados
    Assert::isArray($prenda['variantes']);
    Assert::isArray($prenda['fotos_prenda']);
    Assert::isArray($prenda['procesos']);
}
```

### Validación 3: Archivos correlacionados

```php
foreach ($prendas as $prendaIdx => $prenda) {
    // Verificar que cada foto referenciada tiene su archivo
    foreach ($prenda['fotos_prenda'] as $fotoIdx => $foto) {
        $fileKey = "prenda_{$prendaIdx}_foto_{$fotoIdx}";
        
        if (!$request->hasFile($fileKey)) {
            throw new MissingFileException(
                "Archivo faltante: {$fileKey}"
            );
        }
    }
    
    // Similar para procesos e imágenes
    foreach ($prenda['procesos'] as $procesoIdx => $proceso) {
        // Buscar archivos de este proceso
        $imageCount = 0;
        for ($imgIdx = 0; $imgIdx < 100; $imgIdx++) {
            $fileKey = "prenda_{$prendaIdx}_proceso_{$procesoIdx}_img_{$imgIdx}";
            
            if ($request->hasFile($fileKey)) {
                $imageCount++;
            } else {
                break;
            }
        }
        
        // Guardar cantidad de imágenes para validación posterior
        $proceso['_imageCount'] = $imageCount;
    }
}
```

---

## 🔄 FLUJO COMPLETO EN LARAVEL

```php
<?php

namespace App\Http\Controllers;

use App\Models\Prenda;
use App\Models\Variante;
use App\Models\FotoPrenda;
use App\Models\Proceso;
use App\Models\ImagenProceso;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    /**
     * Guardar pedido desde JSON + FormData
     * 
     * Flujo:
     * 1. Validar JSON es correcto
     * 2. Procesar cada prenda
     * 3. Adjuntar archivos en su ubicación correcta
     * 4. Guardar en BD con transacción
     */
    public function guardarDesdeJson(Request $request)
    {
        //  Paso 1: Extraer y validar JSON
        $prendasJson = $request->input('prendas');
        
        if (empty($prendasJson)) {
            return response()->json([
                'success' => false,
                'message' => 'Prendas requeridas'
            ], 400);
        }
        
        $prendas = json_decode($prendasJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'JSON inválido: ' . json_last_error_msg()
            ], 400);
        }
        
        //  Paso 2: Iniciar transacción
        \DB::beginTransaction();
        
        try {
            $pedidoId = $request->input('pedido_produccion_id');
            $createdPrendas = [];
            
            //  Paso 3: Procesar cada prenda
            foreach ($prendas as $prendaIdx => $prendaData) {
                
                // Crear prenda
                $prenda = Prenda::create([
                    'pedido_produccion_id' => $pedidoId,
                    'nombre_prenda' => $prendaData['nombre_prenda'],
                    'descripcion' => $prendaData['descripcion'] ?? '',
                    'genero' => $prendaData['genero'],
                    'de_bodega' => $prendaData['de_bodega'] ?? false
                ]);
                
                //  Procesar variantes (solo metadatos)
                if (!empty($prendaData['variantes'])) {
                    foreach ($prendaData['variantes'] as $varianteData) {
                        Variante::create(
                            array_merge($varianteData, ['prenda_id' => $prenda->id])
                        );
                    }
                }
                
                //  Procesar fotos de prenda (metadata + archivo)
                if (!empty($prendaData['fotos_prenda'])) {
                    foreach ($prendaData['fotos_prenda'] as $fotoIdx => $fotoData) {
                        $fileKey = "prenda_{$prendaIdx}_foto_{$fotoIdx}";
                        
                        if ($request->hasFile($fileKey)) {
                            $file = $request->file($fileKey);
                            $path = $file->store(
                                "prendas/{$prenda->id}/fotos",
                                'public'
                            );
                            
                            FotoPrenda::create([
                                'prenda_id' => $prenda->id,
                                'archivo' => $path,
                                'nombre' => $fotoData['nombre'],
                                'observaciones' => $fotoData['observaciones'] ?? ''
                            ]);
                        }
                    }
                }
                
                //  Procesar procesos
                if (!empty($prendaData['procesos'])) {
                    foreach ($prendaData['procesos'] as $procesoIdx => $procesoData) {
                        
                        $proceso = Proceso::create([
                            'prenda_id' => $prenda->id,
                            'tipo_proceso_id' => $procesoData['tipo_proceso_id'],
                            'ubicaciones' => json_encode(
                                $procesoData['ubicaciones'] ?? []
                            ),
                            'observaciones' => $procesoData['observaciones'] ?? ''
                        ]);
                        
                        //  Procesar imágenes del proceso
                        for ($imgIdx = 0; $imgIdx < 100; $imgIdx++) {
                            $fileKey = "prenda_{$prendaIdx}_proceso_{$procesoIdx}_img_{$imgIdx}";
                            
                            if ($request->hasFile($fileKey)) {
                                $file = $request->file($fileKey);
                                $path = $file->store(
                                    "procesos/{$proceso->id}",
                                    'public'
                                );
                                
                                ImagenProceso::create([
                                    'proceso_id' => $proceso->id,
                                    'archivo' => $path,
                                    'nombre' => $file->getClientOriginalName()
                                ]);
                            } else {
                                break; // No hay más imágenes
                            }
                        }
                    }
                }
                
                $createdPrendas[] = $prenda;
            }
            
            //  Paso 4: Commit de transacción
            \DB::commit();
            
            //  Paso 5: Retornar éxito
            return response()->json([
                'success' => true,
                'message' => 'Pedido guardado correctamente',
                'numero_pedido' => $pedidoId,
                'prendas_creadas' => count($createdPrendas),
                'prendas' => $createdPrendas->map(fn($p) => [
                    'id' => $p->id,
                    'nombre' => $p->nombre_prenda,
                    'variantes' => $p->variantes->count(),
                    'fotos' => $p->fotoPrenda->count(),
                    'procesos' => $p->procesos->count()
                ])
            ]);
            
        } catch (\Exception $e) {
            //  Rollback en caso de error
            \DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

---

## 🔒 GARANTÍAS IMPLEMENTADAS

| Garantía | Cómo se cumple |
|----------|---|
| JSON siempre válido | Frontend lo genera con `transformStateForSubmit()` |
| Índices únicos | Nombrada: `prenda_X_proceso_Y_img_Z` |
| Archivos correlacionables | FormData key → JSON array index |
| Metadatos completos | JSON contiene toda la información necesaria |
| Transacción segura | Rollback automático si falla |
| Validación exhaustiva | Verificación de estructura y archivos |

---

## 🚨 ERRORES COMUNES

###  Error: "JSON inválido"

**Causa:** El frontend no usó `transformStateForSubmit()`  
**Solución:** Verificar que el frontend envía JSON limpio

###  Error: "Archivo no encontrado: prenda_0_foto_0"

**Causa:** Índice en JSON no coincide con FormData  
**Solución:** Verificar nombrado de keys

###  Error: "Imagen duplicada"

**Causa:** Reutilización de variable `pIdx` en bucles anidados  
**Solución:** Usar `procesoIdx` en lugar de `pIdx`

---

##  VERIFICACIÓN EN BACKEND

```php
// Test: Verificar que recibimos estructura correcta
$this->json('POST', '/api/pedidos/guardar-desde-json', [
    'pedido_produccion_id' => 1,
    'prendas' => json_encode([
        [
            'nombre_prenda' => 'Polo',
            'genero' => 'M',
            'variantes' => [
                ['talla' => 'M', 'cantidad' => 10]
            ],
            'fotos_prenda' => [
                ['nombre' => 'frente.jpg', 'observaciones' => '']
            ],
            'procesos' => [
                ['tipo_proceso_id' => 1, 'ubicaciones' => ['pecho']]
            ]
        ]
    ])
])->assertJson([
    'success' => true
]);
```

