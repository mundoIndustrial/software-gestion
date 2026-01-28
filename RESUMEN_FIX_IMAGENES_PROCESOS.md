# RESUMEN DE CAMBIOS - IMAGENES PROCESOS

## 🎯 Problema
Las imágenes subidas en el modal de edición de procesos **NO SE GUARDABAN** en la base de datos.

## ✅ Solución Implementada

### 1️⃣ Frontend: FormData en PATCH
**Archivo:** `public/js/componentes/modal-novedad-edicion.js`

```
ANTES:
└─ PATCH /api/prendas-pedido/{prendaId}/procesos/{procesoId}
   └─ Headers: Content-Type: application/json
   └─ Body: JSON.stringify(cambios)  ❌ Sin archivos

DESPUÉS:
└─ PATCH /api/prendas-pedido/{prendaId}/procesos/{procesoId}
   └─ Headers: multipart/form-data (automático)
   └─ Body: FormData con:
      ├─ ubicaciones: JSON string
      ├─ observaciones: string
      ├─ imagenes: JSON string (existentes)
      └─ imagenes_nuevas[0]: File  ✅ Archivo nuevo
         imagenes_nuevas[1]: File  ✅ Archivo nuevo
```

### 2️⃣ Backend: Procesar Archivos en PATCH
**Archivo:** `app/Infrastructure/Http/Controllers/API/PrendaPedidoEditController.php`

**Método:** `actualizarProcesoEspecifico()`

```php
Flujo:
1. Extraer imagenes_nuevas[*] del FormData
   └─ for each File:
      ├─ Validar que es válido
      └─ ProcesoFotoService::procesarFoto() → WebP

2. Obtener rutas nuevas procesadas
   └─ [$ruta1, $ruta2, ...]

3. Decodificar JSON de imágenes existentes
   └─ json_decode($data['imagenes'])

4. Mergear imágenes nuevas + existentes
   └─ array_merge($imagenesJSON, $imagenesNuevasRutas)

5. Guardar en tabla pedidos_procesos_imagenes
   └─ INSERT (como antes, pero con rutas nuevas)
```

### 3️⃣ Corrección: Búsqueda de Archivos POST
**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php`

```
ANTES:
└─ if (strpos($key, 'procesos[') === 0)  ❌ Nunca encontraba nada

DESPUÉS:
└─ if (strpos($key, 'files_proceso_') === 0)  ✅ Busca claves correctas
```

## 📊 Flujo Completo (AHORA)

```
┌─────────────────────┐
│  Usuario en Modal   │
│  (edicion proceso)  │
└────────┬────────────┘
         │
         ├─ Carga 2 imágenes nuevas
         │  └─ window.imagenesProcesoActual = [File1, File2]
         │
         ├─ Edita ubicaciones
         ├─ Edita observaciones
         │
         └─ Click "Guardar cambios"
            │
            ▼
┌──────────────────────────────────┐
│  Frontend: construye FormData    │
│  - ubicaciones: JSON string      │
│  - observaciones: string         │
│  - imagenes: JSON string (URLs)  │
│  - imagenes_nuevas[0]: File1     │
│  - imagenes_nuevas[1]: File2     │
└────────┬─────────────────────────┘
         │
         ├─ PATCH /api/prendas-pedido/3472/procesos/113
         │
         ▼
┌──────────────────────────────────────┐
│  Backend: actualizarProcesoEspecifico│
│  1. Extrae imagenes_nuevas[*]       │
│  2. ProcesoFotoService::procesarFoto │
│     ├─ Valida archivo               │
│     ├─ Convierte a WebP             │
│     └─ Retorna ruta_webp            │
│  3. Decod ifica imagenes JSON        │
│  4. Mergea rutas nuevas             │
│  5. Actualiza procesos_imagenes     │
└────────┬──────────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│  BD: pedidos_procesos_imagenes  │
│  INSERT proceso_id=113          │
│  ├─ ruta_webp: procesos/...1   │
│  ├─ ruta_webp: procesos/...2   │
│  └─ updated_at: now()           │
└─────────────────────────────────┘
```

## 🔍 Cómo Verificar

### En Console (Frontend):
```javascript
// En el modal
console.log(window.imagenesProcesoActual);
// Debería mostrar: [File, File, null] o similar

// En Network tab
// Buscar PATCH request
// Verificar que Content-Type es multipart/form-data
// Verificar que lleva imagenes_nuevas[0], imagenes_nuevas[1]
```

### En Log (Backend):
```
[PROCESOS-ACTUALIZAR] Imagen nueva de proceso procesada
[PROCESOS-ACTUALIZAR] Procesando imágenes: {"total_recibidas":2}
[PROCESOS-ACTUALIZAR] Imágenes agregadas: {"cantidad":2}
```

### En BD:
```sql
SELECT * FROM pedidos_procesos_imagenes 
WHERE proceso_prenda_detalle_id = 113
-- Debería mostrar 2 registros nuevos
```

## 📝 Archivos Cambiados

| Archivo | Cambios |
|---------|---------|
| `public/js/componentes/modal-novedad-edicion.js` | Convertir PATCH a FormData, agregar files |
| `app/Infrastructure/Http/Controllers/API/PrendaPedidoEditController.php` | Procesar imagenes_nuevas[*], mergear rutas |
| `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php` | Corregir búsqueda: files_proceso_ |

## ✨ Resultados

- ✅ Imágenes nuevas se guardan en BD
- ✅ Imágenes existentes se preservan (merge)
- ✅ Conversión automática a WebP
- ✅ Log detallado para auditoría
- ✅ Aparecen en recibo/factura correctamente
