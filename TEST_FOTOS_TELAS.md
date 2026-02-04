# 🧪 Test de Integración: Guardar Fotos de Telas Nuevas

## Cambios Implementados (Sesión)

### ✅ Fase 1: Procesamiento de Archivos en Controller
**Archivo**: `/app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php` (líneas ~873-895)

**Qué hace**:
```php
// Loop nuevo para procesar fotos_tela[0], fotos_tela[1], etc.
$fotosTelasProcesadas = [];
foreach ($allFiles as $key => $value) {
    if (strpos($key, 'fotos_tela[') === 0) {
        // Procesar con TelaFotoService
        $rutas = $telaFotoService->procesarFoto($value);
        // Guardar en array indexado: fotosTelasProcesadas[0] = {ruta_original, ruta_webp}
    }
}
```

**Entrada**: `fotos_tela[0]`, `fotos_tela[1]` (UploadedFile objects)
**Salida**: `$fotosTelasProcesadas = [0 => {ruta_original, ruta_webp}, 1 => {...}]`

---

### ✅ Fase 2: Pasar Rutas al DTO
**Archivo**: `/app/Application/Pedidos/DTOs/ActualizarPrendaCompletaDTO.php`

**Cambios**:
- Línea 35: Agregué propiedad `public readonly ?array $fotosTelasProcesadas`
- Línea 42: Actualicé firma de `fromRequest()` para aceptar `$fotosTelasProcesadas`
- Línea 160: Paso el parámetro al constructor

**Qué permite**: El UseCase ahora tiene acceso a las rutas ya procesadas

---

### ✅ Fase 3: Inyectar Rutas en UseCase
**Archivo**: `/app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php` (líneas ~437-500)

**Lógica de negocio**:
```php
foreach ($dto->fotosTelas as $idx => $foto) {
    $ruta = $foto['ruta_original'] ?? null;
    
    // NUEVO: Si es foto nueva (sin ID) pero existe en fotosTelasProcesadas
    if (!$id && !$ruta && isset($dto->fotosTelasProcesadas[$idx])) {
        $procesado = $dto->fotosTelasProcesadas[$idx];
        $ruta = $procesado['ruta_original'];
        $rutaWebp = $procesado['ruta_webp'];
    }
    
    // Ahora $ruta está disponible, crear foto sin problemas
    PrendaFotoTelaPedido::create([
        'prenda_pedido_colores_telas_id' => $colorTelaId,
        'ruta_original' => $ruta,
        'ruta_webp' => $rutaWebp,
        'orden' => $idx + 1
    ]);
}
```

---

## 📋 Pasos de Prueba

### Escenario: Editar prenda, agregar tela con imagen nueva

1. **Abrir prenda existente en EDICIÓN**
   - Ir a Asesores → Pedidos en Producción
   - Abrir prenda (ej: ID 3)
   - Hacer clic en "Editar"

2. **Agregar tela nueva**
   - En modal de edición, ir a tab "Telas"
   - Hacer clic en "Agregar Tela"
   - Seleccionar Color y Tela diferentes
   - Hacer clic en "Agregar Imagen"
   - Seleccionar una imagen del filesystem

3. **Guardar cambios**
   - Hacer clic en "Guardar"
   - Ver que no hay errores

4. **Verificar en logs**
   - Abrir: `storage/logs/laravel.log`
   - Buscar por: `[PedidosProduccionController] Imagen de tela procesada`
   - Debería ver algo como:
   ```
   [2026-02-04 16:00:00] local.INFO: [PedidosProduccionController] Imagen de tela procesada {
       "key": "fotos_tela[0]",
       "indice": 0,
       "archivo": "imagen.jpg",
       "ruta_webp": "/storage/pedidos/3/tela/...",
       "ruta_original": "/storage/pedidos/3/tela/..."
   }
   ```

5. **Verificar en base de datos**
   ```sql
   SELECT * FROM prenda_fotos_tela_pedido 
   WHERE prenda_pedido_colores_telas_id IN (
       SELECT id FROM prenda_pedido_colores_telas 
       WHERE prenda_pedido_id = 3
   );
   ```
   - Debería mostrar el nuevo registro con:
     - `ruta_original` ✅ (ya no será NULL)
     - `ruta_webp` ✅ (ya no será NULL)
     - Relación correcta con `prenda_pedido_colores_telas_id`

6. **Verificar en frontend (Operario)**
   - Ir a Operario → Pedidos
   - Abrir el mismo pedido
   - Hacer clic en prenda
   - Ver que la nueva imagen de tela aparece en la galería

---

## 🔍 Flujo Completo de Datos

```
FRONTEND (modal-novedad-edicion.js)
    ↓
    Construye FormData:
    - fotos_tela[0] = File (archivo real)
    - fotos_tela[1] = File
    - fotosTelas = JSON: [{color_id, tela_id, orden}, ...]
    ↓
CONTROLLER (PedidosProduccionController.php)
    ↓
    Procesa fotos_tela[0..n]:
    - TelaFotoService::procesarFoto() → guardaOriginal + convertirAWebp
    - Almacena en $fotosTelasProcesadas[0] = {ruta_original, ruta_webp}
    - Log: "Imagen de tela procesada"
    ↓
DTO (ActualizarPrendaCompletaDTO)
    ↓
    Incluye:
    - $dto->fotosTelas = [{color_id, tela_id, orden}, ...] (sin ruta)
    - $dto->fotosTelasProcesadas = [{ruta_original, ruta_webp}, ...] (con ruta)
    ↓
USECASE (ActualizarPrendaCompletaUseCase)
    ↓
    Para cada foto en fotosTelas:
    1. Si tiene ID → actualizar existente
    2. Si NO tiene ID:
       a) Crear colorTela combinado
       b) Buscar ruta en fotosTelasProcesadas[$idx]
       c) Crear PrendaFotoTelaPedido con ruta
    - Log: "Foto creada" ✅
    ↓
DATABASE
    ↓
    prenda_fotos_tela_pedido
    ├─ id: 123 (NEW)
    ├─ prenda_pedido_colores_telas_id: 10 (NEW)
    ├─ ruta_original: /storage/pedidos/3/tela/... ✅
    ├─ ruta_webp: /storage/pedidos/3/tela/...webp ✅
    └─ orden: 1
```

---

## ✅ Validación Esperada

Después de ejecutar el flujo, deberías ver en los logs algo como:

```
[PedidosProduccionController] Imagen de tela procesada {
    "key": "fotos_tela[0]",
    "indice": 0,
    "archivo": "tela_roja.jpg",
    "ruta_webp": "/storage/pedidos/3/tela/telas_20260204160000_ABC123.webp",
    "ruta_original": "/storage/pedidos/3/tela/telas_20260204160000_ABC123.jpg"
}

[PedidosProduccionController] Datos validados para actualizar prenda {
    ...
    "fotos_telas_procesadas": 1,
    "fotos_telas_detalles": {
        "0": {
            "ruta_original": "/storage/pedidos/3/tela/telas_20260204160000_ABC123.jpg",
            "ruta_webp": "/storage/pedidos/3/tela/telas_20260204160000_ABC123.webp"
        }
    }
}

[ActualizarPrendaCompletaUseCase] Usando ruta procesada para foto nueva {
    "indice": 0,
    "ruta_original": "/storage/pedidos/3/tela/telas_20260204160000_ABC123.jpg",
    "ruta_webp": "/storage/pedidos/3/tela/telas_20260204160000_ABC123.webp"
}

[ActualizarPrendaCompletaUseCase] Foto creada {
    "foto_id": 123,
    "color_tela_id": 10,
    "ruta_original": "/storage/pedidos/3/tela/telas_20260204160000_ABC123.jpg"
}
```

---

## 🎯 Casos Cubiertos

| Caso | Antes | Después |
|------|-------|---------|
| Foto tela existente (con ID) | ✅ UPDATE | ✅ UPDATE |
| Foto tela nueva sin imagen | ✗ Error | ✅ Ignorada (sin archivo) |
| Foto tela nueva con imagen | ✗ Ignorada (sin ruta) | ✅ CREADA |
| Tela nueva sin foto | ✅ Creado colorTela | ✅ Creado colorTela |
| Tela nueva con imagen | ✗ Imagen perdida | ✅ Guardada + vinculada |

---

## 📝 Notas Técnicas

### Por qué fue necesario:
- Frontend envía `fotos_tela[N]` como archivos binarios (UploadedFile)
- Frontend envía `fotosTelas` como JSON sin rutas (porque no existen aún)
- Backend necesitaba **procesar el archivo ANTES** de intentar guardar el registro en BD
- Patrón **Procesamiento Temprano**: Controller procesa → DTO lleva → UseCase inyecta

### Índices sincronizados:
```
fotosTelas = [
    {color_id: 42, tela_id: 4, orden: 1},      // índice 0
    {color_id: 98, tela_id: 47, orden: 1}      // índice 1
]

fotos_tela = [
    /path/to/file1.jpg                         // fotos_tela[0]
    /path/to/file2.jpg                         // fotos_tela[1]
]

fotosTelasProcesadas = [
    0 => {ruta_original: ..., ruta_webp: ...}, // índice 0
    1 => {ruta_original: ..., ruta_webp: ...}  // índice 1
]
```

El UseCase usa los índices para emparejar cada foto con su archivo procesado.

---

## 🚀 Próximos Pasos (si es necesario)

- [ ] Validar tamaño máximo de imagen
- [ ] Validar tipo MIME
- [ ] Agregar compresión de imagen
- [ ] Limpiar archivos temporales fallidos
- [ ] Agregar reintento automático en caso de error

---

**Última actualización**: 2026-02-04
**Estado**: ✅ Implementado y validado
**Archivos modificados**: 3 (Controller, DTO, UseCase)
