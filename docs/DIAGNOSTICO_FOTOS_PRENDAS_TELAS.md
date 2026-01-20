#  DIAGNÓSTICO: Fotos de Prendas y Telas No Se Guardan

##  PROBLEMA IDENTIFICADO

Las fotos de **prendas** y **telas** NO se están guardando en:
- `prenda_fotos_pedido` (columnas `ruta_original` y `ruta_webp` vacías)
- `prenda_fotos_tela_pedido` (columnas `ruta_original` y `ruta_webp` vacías)

En cambio, las fotos de **procesos** (reflectivo, etc.) SÍ se guardan correctamente.

### Estado del Log del Usuario (16/01/2026)
```
📸 Fotos encontradas en prenda.imagenes: 1
 Telas encontradas: 1
📷 Agregado imagen tela: 0/0 = CODIGO DE TELA.png
📷 Agregado archivo: reflectivo/0 = CAMISA DRILL.png
```

Nota: Se agregó imagen de **tela** y **proceso**, pero las fotos de **prenda** no generaron registro (`fotos: []`)

---

## 🔎 ANÁLISIS: ¿Por qué procesos sí funciona pero prendas no?

### PROCESOS ( FUNCIONA)
```php
// CrearPedidoEditableController.php - Línea 368
$imagenesFormDataKey = "prendas.{$itemIndex}.procesos.{$tipoProceso}.imagenes";
$imagenesUploadedFiles = $request->file($imagenesFormDataKey) ?? [];  //  Request->file()
$datosProceso['imagenes'] = array_filter($imagenesUploadedFiles, 
    fn($img) => $img instanceof \Illuminate\Http\UploadedFile
);
```

Las imágenes de procesos se obtienen correctamente con `$request->file()` que las convierte a `UploadedFile`.

### PRENDAS Y TELAS ( NO FUNCIONABA)
```php
// ANTES - CrearPedidoEditableController.php - Línea 557
$prendaData = [
    'fotos' => $item['imagenes'] ?? [],  //  Tomaba de $item array, no de FormData
    // ...
];
```

Las imágenes de prendas se tomaban directamente de `$item` (datos parseados del FormData como strings/objetos, no como UploadedFile).

---

##  SOLUCIÓN IMPLEMENTADA

### 1. Procesar Fotos de Prenda desde FormData (Líneas 551-571 en CrearPedidoEditableController.php)

```php
//  OBTENER IMÁGENES DE PRENDA DESDE FormData
$fotosFormDataKey = "prendas.{$itemIndex}.imagenes";
$fotosUploadedFiles = $request->file($fotosFormDataKey) ?? [];

// Asegurar que es array
if (!is_array($fotosUploadedFiles)) {
    $fotosUploadedFiles = [$fotosUploadedFiles];
}

$fotosFiltered = array_filter($fotosUploadedFiles, function($foto) {
    return $foto instanceof \Illuminate\Http\UploadedFile;
});

// Ahora $fotosFiltered contiene UploadedFile válidos
```

### 2. Procesar Fotos de Telas desde FormData (Líneas 573-609 en CrearPedidoEditableController.php)

```php
//  OBTENER IMÁGENES DE TELAS DESDE FormData y FUSIONAR con datos existentes
$telasFormDataKey = "prendas.{$itemIndex}.telas";
$telasConImagenes = [];

// Primero, copiar datos de telas existentes del item si los hay
if (!empty($item['telas']) && is_array($item['telas'])) {
    foreach ($item['telas'] as $telaIdx => $telaDatos) {
        $telasConImagenes[$telaIdx] = is_array($telaDatos) ? $telaDatos : [];
        if (!isset($telasConImagenes[$telaIdx]['fotos'])) {
            $telasConImagenes[$telaIdx]['fotos'] = [];
        }
    }
}

// Obtener imágenes desde FormData
$telaFiles = $request->file($telasFormDataKey) ?? [];
if (is_array($telaFiles)) {
    foreach ($telaFiles as $telaIdx => $telaData) {
        if (!isset($telasConImagenes[$telaIdx])) {
            $telasConImagenes[$telaIdx] = ['fotos' => []];
        }
        
        // Obtener imagenes de esta tela específica
        $imagenesTela = $request->file($telasFormDataKey . ".{$telaIdx}.imagenes") ?? [];
        if (!is_array($imagenesTela)) {
            $imagenesTela = [$imagenesTela];
        }
        
        $imagenesTelaFiltered = array_filter($imagenesTela, 
            fn($img) => $img instanceof \Illuminate\Http\UploadedFile
        );
        
        if (!empty($imagenesTelaFiltered)) {
            $telasConImagenes[$telaIdx]['fotos'] = array_values($imagenesTelaFiltered);
        }
    }
}
```

### 3. Asignar Fotos y Telas a $prendaData (Líneas 621-636)

```php
$prendaData = [
    'nombre_producto' => $item['prenda'],
    'descripcion' => $item['descripcion'] ?? '',
    'variaciones' => $variaciones_data,
    'fotos' => $fotosFiltered,              //  Fotos de prenda como UploadedFile
    'procesos' => $procesosReconstruidos,   //  Procesos con imágenes UploadedFile
    'origen' => $item['origen'] ?? 'bodega',
    'de_bodega' => $deBodega,
    'obs_manga' => $obs_manga,
    'obs_bolsillos' => $obs_bolsillos,
    'obs_broche' => $obs_broche,
    'obs_reflectivo' => $obs_reflectivo,
    'tipo_manga_id' => $tipo_manga_id,
    'tipo_broche_boton_id' => $tipo_broche_boton_id,
    'telas' => $telasConImagenes,          //  Telas con imágenes UploadedFile
];
```

---

##  CAMBIOS EN ARCHIVOS

### [CrearPedidoEditableController.php](app/Http/Controllers/Asesores/CrearPedidoEditableController.php)

**Cambios:**
1.  Agregado procesamiento de fotos de prenda desde FormData (Líneas 551-571)
2.  Agregado procesamiento de fotos de telas desde FormData (Líneas 573-609)
3.  Actualizado `$prendaData` para usar fotos procesadas (Línea 627)
4.  Agregado log de verificación pre-guardado (Líneas 738-753)

### [PedidoPrendaService.php](app/Application/Services/PedidoPrendaService.php)

**Cambios:**
1.  Mejorado log en `guardarFotosPrenda()` para mostrar estructura detallada (Líneas 534-554)
2.  Mejorado log en `guardarFotosTelas()` para mostrar estructura detallada (Líneas 744-760)

---

## 🧪 PRUEBA

### Pasos para Verificar

1. **Crear nuevo pedido** con:
   - 1 prenda nueva
   - Agregar 1+ fotos a la prenda
   - Agregar 1+ telas con fotos

2. **Verificar logs** en `storage/logs/laravel.log`:
   ```
    [CrearPedidoEditableController] Prendas listas para guardar
   - tiene_fotos: 1
   - tiene_telas: 1
   
    [PedidoPrendaService::guardarFotosPrenda] Guardando fotos de prenda
   - cantidad: 1
   - fotos_estructura: [{"index": 0, "es_UploadedFile": true, "nombre": "..."}]
   
    [PedidoPrendaService::guardarFotosTelas] Guardando fotos de telas
   - cantidad_telas: 1
   - telas_estructura: [{"tela_index": 0, "tiene_fotos": 1}]
   ```

3. **Verificar BD**:
   ```sql
   SELECT * FROM prenda_fotos_pedido WHERE prenda_pedido_id = {NUEVA_ID};
   SELECT * FROM prenda_fotos_tela_pedido WHERE prenda_pedido_id = {NUEVA_ID};
   ```
   - Debe haber registros con `ruta_original` y `ruta_webp` llenas
   - Las imágenes deben estar en `storage/app/public/pedidos/{id}/prendas/`

---

##  IMPACTO

| Elemento | Antes | Después |
|----------|-------|---------|
| Fotos de prenda |  No se guardan |  Se guardan |
| Fotos de tela |  No se guardan |  Se guardan |
| Fotos de proceso |  Se guardan |  Se guardan (sin cambios) |

---

## 🔗 REFERENCIAS

- Frontend: [api-pedidos-editable.js](public/js/modulos/crear-pedido/configuracion/api-pedidos-editable.js) - Líneas 358-385 (formulario FormData)
- Backend: [CrearPedidoEditableController.php](app/Http/Controllers/Asesores/CrearPedidoEditableController.php) - Líneas 551-636 (procesamiento)
- Servicio: [PedidoPrendaService.php](app/Application/Services/PedidoPrendaService.php) - Líneas 378-420 (guardado)

---

**Fecha:** 16 de Enero de 2026  
**Estado:**  Implementado y Probado
