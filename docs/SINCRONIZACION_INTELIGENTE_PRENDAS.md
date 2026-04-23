# 🔄 SINCRONIZACIÓN INTELIGENTE DE PRENDAS - ARQUITECTURA DE EDICIÓN SEGURA

## Problema

Al editar un borrador con 14+ prendas y agregar imágenes nuevas, las imágenes existentes desaparecen. Esto ocurre porque:

1. El frontend serializa TODAS las imágenes como "nuevas" (File multipart)
2. El backend recibe solo Files sin referencia a las imágenes existentes en BD
3. No hay merge → las viejas imágenes se pierden

## Solución: Distinción Clara entre 3 tipos de imágenes

```javascript
// Lo que debe enviarse:
{
  imagenes_nuevas: [File, File, ...],           // ← Solo uploads nuevos (multipart)
  imagenes_existentes: [
    { id: 1, ruta_original: '/storage/...' },
    { id: 2, ruta_webp: '/storage/...' },
    ...
  ],                                             // ← URLs de BD (JSON)
  imagenes_a_eliminar: [1, 3, 5]                // ← IDs a borrar (JSON)
}
```

## 📋 Flujo de Actualización

```
ESCENARIO: Borrador con 14 prendas + agregar 2 prendas nuevas + editar 3 existentes

GET /api/asesores/pedidos/100
└─ Carga 14 prendas con sus imágenes de BD

USUARIO:
├─ Abre prenda #5 (existente)
│  ├─ Carga 3 imágenes existentes
│  ├─ Agrega 2 imágenes nuevas
│  └─ Marca 1 imagen para eliminar
├─ Abre prenda #10 (existente)
│  ├─ Carga 4 imágenes existentes
│  ├─ Sin cambios
│  └─ Cierra sin editar
└─ Click "Guardar" → PUT /api/asesores/pedidos/100/borrador

SERIALIZACIÓN (Frontend):
┌─ prenda #5 (EXISTENTE EDITADA):
│  {
│    "id": 5,                              // ← prenda_pedido_id
│    "nombre_prenda": "Camisa",
│    "imagenes_existentes": [              // ← Lo QUE SE MANTIENE
│      { "id": 1, "ruta_original": "..." },
│      { "id": 2, "ruta_webp": "..." },
│      { "id": 3, "ruta_webp": "..." }
│    ],
│    "imagenes_a_eliminar": [1],           // ← Lo QUE SE BORRA
│    "imagenes_nuevas": [File, File]       // ← Lo QUE SE AGREGA
│  }
├─ prenda #10 (EXISTENTE SIN CAMBIOS):
│  {
│    "id": 10,
│    "nombre_prenda": "Pantalón",
│    "imagenes_existentes": [              // ← Se preservan automáticamente
│      { "id": 10, "ruta_original": "..." },
│      { "id": 11, "ruta_webp": "..." },
│      { "id": 12, "ruta_webp": "..." },
│      { "id": 13, "ruta_webp": "..." }
│    ]
│    // No hay imagenes_a_eliminar ni imagenes_nuevas
│  }
└─ prenda #15 (NUEVA):
   {
     "id": null,
     "nombre_prenda": "Corbata",
     "imagenes_nuevas": [File]              // ← Solo Files nuevas
   }

BACKEND (ActualizarBorradorUseCase):
├─ Prenda #5:
│  ├─ Actualizar datos (nombre_prenda, etc.)
│  ├─ Eliminar imágenes con IDs [1]
│  ├─ Mantener imágenes [2, 3]
│  └─ Agregar 2 nuevas imágenes
├─ Prenda #10:
│  ├─ Actualizar datos
│  ├─ imagenesExistentes: [10, 11, 12, 13] → preservadas automáticamente
│  └─ Sin cambios en imágenes
└─ Prenda #15:
   ├─ Crear nueva prenda
   └─ Guardar 1 imagen nueva

RESULTADO:
Prenda #5:  3 - 1 + 2 = 4 imágenes ✅
Prenda #10: 4 imágenes (sin cambios) ✅
Prenda #15: 1 imagen ✅
```

## 🛠️ Implementación Frontend

### 1. Modificar Draft Serializer

**Ubicación:** `public/js/modulos/crear-pedido/edicion/draft-pedido-serializer.js`

**Cambio clave (línea 507-521):**

```javascript
// ❌ ANTES (confunde nuevas con existentes)
const imagenesExistentes = [];
const archivosYaAgregados = new Set();
(Array.isArray(prenda.imagenes) ? prenda.imagenes : []).forEach((img) => {
    const file = img instanceof File ? img : null;
    if (file) {
        agregarArchivo('imagenes[]', file);  // ← Agrega Files
        return;
    }
    if (img?.url?.startsWith('/') || img?.ruta) {
        imagenesExistentes.push({ id: img.id, url: img.url });  // ← Pero JSON va aquí
    }
});
// PROBLEMA: Files y URLs mezcladas

// ✅ DESPUÉS (distinción clara)
const imagenesNuevas = [];      // Files multipart
const imagenesExistentes = [];  // JSON con ID+URL
const archivosYaAgregados = new Set();

(Array.isArray(prenda.imagenes) ? prenda.imagenes : []).forEach((img) => {
    // 1️⃣ ¿Es un File nuevo?
    const file = img instanceof File ? img : (img?.file instanceof File ? img.file : null);
    if (file) {
        agregarArchivo('imagenes[]', file);
        imagenesNuevas.push(file);
        archivosYaAgregados.add(file);
        return;
    }
    
    // 2️⃣ ¿Es URL de BD? (tiene id + url/ruta válida)
    if ((img?.id || img?.urlDesdeDB) && (img?.url?.startsWith('/') || img?.ruta || img?.ruta_webp)) {
        imagenesExistentes.push({
            id: img.id,
            ruta_original: img.ruta_original || img.ruta || img.url,
            ruta_webp: img.ruta_webp || null,
            urlDesdeDB: true  // ← Marcador de "viene de BD"
        });
        return;
    }
    
    // 3️⃣ Si no es ni File ni URL válida, ignorar
});
```

### 2. Enviar datos estructurados

```javascript
// En el payload final de la prenda existente:
const prendaPayload = {
    id: prenda.prenda_pedido_id,
    nombre_prenda: prenda.nombre_producto,
    descripcion: prenda.descripcion,
    
    // ← IMPORTANTE: Estos 3 campos separados
    imagenes_nuevas: imagenesNuevas.length > 0 ? imagenesNuevas : undefined,
    imagenes_existentes: imagenesExistentes.length > 0 ? JSON.stringify(imagenesExistentes) : undefined,
    imagenes_a_eliminar: prenda.imagenes_a_eliminar?.length > 0 ? JSON.stringify(prenda.imagenes_a_eliminar) : undefined,
    
    // Resto de datos
    ...otrosCampos
};
```

## 🔧 Validación Backend

El `ActualizarPrendaCompletaDTO` (línea 221-236) ya tiene la lógica correcta:

```php
fotos: (function() use ($imagenes, $imagenesExistentes, $imagenesAEliminar, $data) {
    $tieneNuevas = !empty($imagenes);
    $tieneExistentes = !empty($imagenesExistentes);
    $tieneEliminaciones = !empty($imagenesAEliminar);
    
    if ($tieneNuevas || $tieneExistentes) {
        // ✅ MERGE: Mantiene existentes + agrega nuevas
        return array_merge($imagenesExistentes ?? [], $imagenes ?? []);
    }
    if ($tieneEliminaciones) {
        // ✅ Usuario eliminó pero no agregó → array vacío
        return [];
    }
    // ✅ Sin cambios → null = NO TOCAR
    return null;
})(),
```

## 📊 Ejemplo Completo

### Frontend (serializar prenda #5 con cambios de imágenes)

```javascript
const prenda = {
    id: 5,
    prenda_pedido_id: 5,
    nombre_producto: "Camisa Azul",
    imagenes: [
        // Existentes (de BD):
        { id: 101, ruta_original: '/storage/prendas/101.jpg', ruta_webp: '/storage/prendas/101.webp' },
        { id: 102, ruta_original: '/storage/prendas/102.jpg', ruta_webp: '/storage/prendas/102.webp' },
        { id: 103, ruta_original: '/storage/prendas/103.jpg', ruta_webp: '/storage/prendas/103.webp' },
        // Nueva (File object):
        new File(['...'], 'camisa-detalle.jpg', { type: 'image/jpeg' }),
        new File(['...'], 'camisa-frente.jpg', { type: 'image/jpeg' })
    ],
    imagenes_a_eliminar: [101]  // Marcar imagen para eliminar
};

// Serializar:
serializarPrendaExistenteParaBorrador(prenda, 0, formData);

// Resultado en FormData:
//
// prenda_existente_0_id = 5
// prenda_existente_0_nombre_prenda = "Camisa Azul"
// prenda_existente_0_imagenes[] = File (camisa-detalle.jpg)
// prenda_existente_0_imagenes[] = File (camisa-frente.jpg)
// prenda_existente_0_imagenes_existentes = JSON.stringify([
//    { id: 102, ruta_original: '/storage/...', ruta_webp: '/storage/...' },
//    { id: 103, ruta_original: '/storage/...', ruta_webp: '/storage/...' }
// ])
// prenda_existente_0_imagenes_a_eliminar = JSON.stringify([101])
```

### Backend (actualizar)

```php
// ActualizarBorradorUseCase::actualizarPrendasExistentes()
$imagenesProcesadas = $this->procesarImagenesPrendaService->procesarParaActualizar($subRequest, $pedidoId);

// $imagenesProcesadas contiene:
// [
//     'imagenes_guardadas' => [              // Files nuevos procesados
//         ['ruta_original' => '...', 'ruta_webp' => '...'],
//         ['ruta_original' => '...', 'ruta_webp' => '...']
//     ],
//     'imagenes_existentes' => [             // JSON decodificado
//         ['id' => 102, 'ruta_original' => '...'],
//         ['id' => 103, 'ruta_webp' => '...']
//     ],
//     'imagenes_a_eliminar' => [101]         // IDs a eliminar
// ]

$dto = ActualizarPrendaCompletaDTO::fromRequest(
    $prendaId,
    $prendaPayload,
    $imagenesProcesadas['imagenes_guardadas'],
    $imagenesProcesadas['imagenes_existentes'],
    // ... otros parámetros
);

// En el DTO (línea 221):
// fotos: merge($imagenesExistentes, $imagenes_guardadas)
// = merge([102, 103], [nuevas]) = [102, 103, nuevas]
//
// Luego se eliminan las que tienen ID en imagenes_a_eliminar [101]
```

## ✅ Garantías

Con esta arquitectura:

✅ **14+ prendas:** Cada una preserva sus imágenes existentes  
✅ **Múltiples ediciones:** El merge es idempotente  
✅ **Agregar imágenes:** New images + existentes = total  
✅ **Eliminar imágenes:** Se eliminan solo las marcadas  
✅ **Sin cambios:** Si no toca imágenes → null → sin cambios  
✅ **Transacción atómica:** Todo falla o todo se guarda  

## 🧪 Testing

```bash
# Test: Editar prenda con 3 imágenes existentes + agregar 2 nuevas + eliminar 1
php artisan test tests/Feature/Pedidos/ActualizarBorradorImagenesTest.php::test_actualizar_prenda_merge_inteligente_imagenes

# Test: 14 prendas, editar 3, cada una con cambios diferentes
php artisan test tests/Feature/Pedidos/ActualizarBorradorImagenesTest.php::test_actualizar_14_prendas_cambios_mixtos
```

---

**Última actualización:** 2026-04-23  
**Versión:** 1.0  
**Estado:** Implementación en progreso
