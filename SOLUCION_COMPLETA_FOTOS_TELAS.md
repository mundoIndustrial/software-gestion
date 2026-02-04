# 🎉 SOLUCIÓN COMPLETA: Guardar Imágenes de Telas al Editar Prendas

## 📌 Problema Original
**"¿PORQUE CUANDO EDITO LA IMAGEN DE LA TELA NO SE GUARDA?"**

Las imágenes de telas nuevas no se guardaban cuando se editaba una prenda existente en el modal de actualización.

---

## 🔍 Análisis Progresivo (8 sesiones)

### Sesión 1-3: Problemas de Compatibilidad Campo Nombres
**Síntoma**: Backend rechazaba `fotosTelas` (camelCase del frontend)
**Causa**: Servidor esperaba `fotos_telas` (snake_case)
**Solución**: DTO acepta ambos: `fotos_telas` y `fotosTelas`

### Sesión 4-5: Error SQL Ambigüedad
**Síntoma**: `Column 'id' in where clause is ambiguous`
**Causa**: `prenda_fotos_tela_pedido` y otros joins tenían ambigüedad en el ID
**Solución**: Calificar columna: `prenda_fotos_tela_pedido.id`

### Sesión 6-7: Falta de FK en Frontend
**Síntoma**: Las fotos existentes no encontraban su relación
**Causa**: Frontend no enviaba `prenda_pedido_colores_telas_id` para fotos existentes
**Solución**: Agregar FK a los datos del modal: `prenda_pedido_colores_telas_id: tela.id`

### Sesión 8: Relación HasManyThrough
**Síntoma**: `create()` no funcionaba en HasManyThrough
**Causa**: HasManyThrough es solo lectura, necesita acceso directo al modelo
**Solución**: Usar `PrendaFotoTelaPedido::create($datos)` directamente

### Sesión 9-10: Imágenes No Detectadas
**Síntoma**: Imágenes nuevas se guardaban en JS pero no se enviaban
**Causa**: Frontend guardaba imágenes como `{file, nombre, tamaño}` pero código buscaba `instanceof File`
**Solución**: Detectar ambos: `const fileObject = img instanceof File ? img : (img.file instanceof File ? img.file : null)`

### Sesión 11 (ACTUAL): Rutas No Procesadas
**Síntoma**: Archivos llegaban al backend pero se ignoraban: "Foto ignorada (sin color_tela_id o ruta)"
**Causa**: Backend recibía archivos en `fotos_tela[0]` pero `fotosTelas` no tenía `ruta_original`
**Solución**: 
1. Procesar archivos en Controller con `TelaFotoService`
2. Pasar rutas procesadas al DTO
3. UseCase inyecta rutas en metadata de fotos nuevas antes de crear

---

## ✅ SOLUCIÓN FINAL IMPLEMENTADA

### Cambio 1: Controller Procesa Archivos
**Archivo**: `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php`
**Líneas**: ~860-895

```php
// Nuevo: Procesar imágenes de telas nuevas (fotos_tela[0], fotos_tela[1], etc.)
$fotosTelasProcesadas = [];
foreach ($allFiles as $key => $value) {
    if (strpos($key, 'fotos_tela[') === 0 && strpos($key, ']') !== false) {
        if ($value && $value->isValid()) {
            try {
                $telaFotoService = new \App\Domain\Pedidos\Services\TelaFotoService();
                $rutas = $telaFotoService->procesarFoto($value);
                // Extraer índice: fotos_tela[0] => 0
                preg_match('/fotos_tela\[(\d+)\]/', $key, $matches);
                $indice = isset($matches[1]) ? (int)$matches[1] : count($fotosTelasProcesadas);
                $fotosTelasProcesadas[$indice] = $rutas;
                // Log detallado
            } catch (\Exception $e) {
                // Manejo de errores
            }
        }
    }
}
```

**Qué hace**:
- Busca archivos con patrón `fotos_tela[N]`
- Procesa cada archivo con `TelaFotoService`
- Extrae índice del key
- Almacena resultado en array indexado: `{0 => {ruta_original, ruta_webp}, 1 => {...}}`

---

### Cambio 2: DTO Recibe Rutas Procesadas
**Archivo**: `app/Application/Pedidos/DTOs/ActualizarPrendaCompletaDTO.php`
**Líneas**: 35, 39, 171

```php
// Propiedad agregada
public readonly ?array $fotosTelasProcesadas = null,

// Método fromRequest actualizado
public static function fromRequest(int|string $prendaId, array $data, ?array $imagenes = null, 
                                   ?array $imagenesExistentes = null, 
                                   ?array $fotosTelasProcesadas = null): self

// Paso al constructor
fotosTelasProcesadas: $fotosTelasProcesadas,
```

**Qué hace**:
- Acepta rutas procesadas del controller
- Las pasa al UseCase para inyección en UseCase

---

### Cambio 3: UseCase Inyecta Rutas en Fotos Nuevas
**Archivo**: `app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php`
**Líneas**: ~437-540

```php
private function actualizarFotosTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
{
    foreach ($dto->fotosTelas as $idx => $foto) {
        $id = $foto['id'] ?? null;
        $ruta = $foto['ruta_original'] ?? $foto['path'] ?? null;
        
        // NUEVO: Si es foto nueva (sin ID) pero existe en fotosTelasProcesadas
        if (!$id && !$ruta && isset($idx) && is_array($dto->fotosTelasProcesadas) && 
            isset($dto->fotosTelasProcesadas[$idx])) {
            
            $procesado = $dto->fotosTelasProcesadas[$idx];
            $ruta = $procesado['ruta_original'] ?? null;
            $rutaWebp = $procesado['ruta_webp'] ?? null;
            
            \Log::debug('[ActualizarPrendaCompletaUseCase] Usando ruta procesada para foto nueva', [
                'indice' => $idx,
                'ruta_original' => $ruta,
                'ruta_webp' => $rutaWebp
            ]);
        }
        
        // ... resto de lógica ...
        
        // Ahora $ruta está disponible, crear foto sin problemas
        $fotoCreada = \App\Models\PrendaFotoTelaPedido::create([
            'prenda_pedido_colores_telas_id' => $colorTelaId,
            'ruta_original' => $ruta,
            'ruta_webp' => $rutaWebp,
            'orden' => $idx + 1
        ]);
    }
}
```

**Qué hace**:
- Detecta fotos nuevas (sin ID y sin ruta)
- Busca la ruta en `fotosTelasProcesadas[$idx]`
- Inyecta ruta en metadata
- Crea foto con todos los datos completos

---

## 🎯 Flujo de Datos Completo

```
┌─────────────────────────────────────────────────────────┐
│ FRONTEND: modal-novedad-edicion.js                      │
├─────────────────────────────────────────────────────────┤
│ telas[0] = {                                            │
│   color_id: 42,                                         │
│   tela_id: 4,                                           │
│   imagenes: [{file: File, nombre, tamaño}]             │
│ }                                                       │
│ telas[1] = {                                            │
│   color_id: 98,                                         │
│   tela_id: 47,                                          │
│   imagenes: [{file: File, nombre, tamaño}]             │
│ }                                                       │
└──────────────┬──────────────────────────────────────────┘
               │ Construye FormData
               ↓
┌─────────────────────────────────────────────────────────┐
│ FormData                                                │
├─────────────────────────────────────────────────────────┤
│ fotos_tela[0]: UploadedFile                             │
│ fotos_tela[1]: UploadedFile                             │
│ fotosTelas: JSON "[{color_id:98, tela_id:47, orden:1}]"│
└──────────────┬──────────────────────────────────────────┘
               │ POST /pedidos/{id}/prendas/{prenda_id}
               ↓
┌─────────────────────────────────────────────────────────┐
│ CONTROLLER: PedidosProduccionController                 │
├─────────────────────────────────────────────────────────┤
│ 1. Loop: foreach fotos_tela[0..N]                       │
│    - TelaFotoService::procesarFoto()                    │
│    - Guarda en /storage/pedidos/{id}/tela/             │
│    - Convierte a WebP                                   │
│                                                         │
│ 2. Resultado: $fotosTelasProcesadas[0] = {             │
│      ruta_original: /storage/...jpg,                   │
│      ruta_webp: /storage/...webp                       │
│    }                                                    │
└──────────────┬──────────────────────────────────────────┘
               │ Construye DTO
               ↓
┌─────────────────────────────────────────────────────────┐
│ DTO: ActualizarPrendaCompletaDTO                        │
├─────────────────────────────────────────────────────────┤
│ $dto->fotosTelas = [{                                   │
│   color_id: 98,                                         │
│   tela_id: 47,                                          │
│   orden: 1                                              │
│   // ⚠️ sin ruta_original aún                           │
│ }]                                                      │
│                                                         │
│ $dto->fotosTelasProcesadas = [{                         │
│   ruta_original: /storage/...,                         │
│   ruta_webp: /storage/...                              │
│ }]                                                      │
└──────────────┬──────────────────────────────────────────┘
               │ ejecutar()
               ↓
┌─────────────────────────────────────────────────────────┐
│ USECASE: ActualizarPrendaCompletaUseCase                │
├─────────────────────────────────────────────────────────┤
│ foreach ($dto->fotosTelas as $idx => $foto):            │
│   if (NO tiene $id && NO tiene ruta):                   │
│     $procesado = $dto->fotosTelasProcesadas[$idx]       │
│     $ruta = $procesado['ruta_original']     // ✅       │
│                                                         │
│   Crear prenda_pedido_colores_telas (FK)               │
│   Crear PrendaFotoTelaPedido con ruta               │
│                                                         │
│   LOG: "Foto creada {foto_id, color_tela_id, ruta}"   │
└──────────────┬──────────────────────────────────────────┘
               │ Persiste
               ↓
┌─────────────────────────────────────────────────────────┐
│ DATABASE                                                │
├─────────────────────────────────────────────────────────┤
│ prenda_pedido_colores_telas:                            │
│  id: 10                                                 │
│  prenda_pedido_id: 3                                    │
│  color_id: 98                                           │
│  tela_id: 47                                            │
│                                                         │
│ prenda_fotos_tela_pedido:                               │
│  id: 123                   ✅ NUEVO                     │
│  prenda_pedido_colores_telas_id: 10   ✅ Vinculado    │
│  ruta_original: /storage/...jpg       ✅ Guardada      │
│  ruta_webp: /storage/...webp          ✅ Optimizada    │
│  orden: 1                                               │
└─────────────────────────────────────────────────────────┘
```

---

## 📊 Matriz de Casos de Uso

| Escenario | Entrada | Procesamiento | Salida | Estado |
|-----------|---------|---------------|--------|--------|
| Foto existente sin cambios | `{id:3, ruta:/old.jpg}` | UPDATE | BD actualizada con misma ruta | ✅ |
| Foto existente con cambio | `{id:3, ruta:/new.jpg}` | UPDATE | BD actualizada con nueva ruta | ✅ |
| Foto nueva con imagen | `{color_id:98, tela_id:47, file}` | CREAR + PROCESAR | BD crea registro con ruta procesada | ✅ |
| Foto nueva sin imagen | `{color_id:98, tela_id:47}` | IGNORAR | colorTela creada, foto NO creada | ✅ |
| Tela sin cambios | `{id:4, imagenes:[...]}` | MERGE | Fotos actualizadas | ✅ |
| Tela nueva | `{color_id:98, tela_id:47, imagenes:[]}` | CREAR | colorTela + foto si hay imagen | ✅ |

---

## 🧪 Validación Post-Implementación

### 1. Verificación en Logs
Buscar en `storage/logs/laravel.log`:

```
[2026-02-04 16:00:00] local.INFO: [PedidosProduccionController] Imagen de tela procesada {
    "key": "fotos_tela[0]",
    "indice": 0,
    "archivo": "tela_roja.jpg",
    "ruta_webp": "/storage/pedidos/3/tela/telas_20260204160000_ABC123.webp",
    "ruta_original": "/storage/pedidos/3/tela/telas_20260204160000_ABC123.jpg"
}

[ActualizarPrendaCompletaUseCase] actualizarFotosTelas - Iniciando {
    "prenda_id": "3",
    "cantidad_fotos": 2,
    "fotos_procesadas_disponibles": 1
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

### 2. Verificación en Base de Datos
```sql
SELECT 
    ppt.id,
    ppt.prenda_pedido_colores_telas_id,
    ppt.ruta_original,
    ppt.ruta_webp,
    ppt.orden
FROM prenda_fotos_tela_pedido ppt
WHERE ppt.prenda_pedido_colores_telas_id IN (
    SELECT id FROM prenda_pedido_colores_telas 
    WHERE prenda_pedido_id = 3
)
ORDER BY ppt.prenda_pedido_colores_telas_id, ppt.orden;
```

Esperado:
- Nueva foto debería aparecer con `ruta_original` ✅ y `ruta_webp` ✅
- FK correcto a `prenda_pedido_colores_telas` ✅

### 3. Verificación en Frontend (Operario)
1. Ir a Operario → Pedidos
2. Abrir pedido → Prenda
3. Ver galería de telas: Debería mostrar la nueva imagen

---

## 🚨 Errores Comunes y Soluciones

| Error | Causa | Solución |
|-------|-------|----------|
| "Foto ignorada (sin ruta)" | `fotosTelasProcesadas` es null | Verificar que Controller está pasando el parámetro al DTO |
| "prenda_pedido_colores_telas_id unknown column" | Typo en campo | Verificar que UseCase usa nombre correcto |
| Imagen no se carga en galería | Ruta incorrecta o permisos de almacenamiento | Verificar permisos de carpeta `/storage/pedidos` |
| WebP no se genera | `ImageManager` no disponible | Verificar que extensión GD está instalada |

---

## 📁 Archivos Modificados

| Archivo | Líneas | Cambio |
|---------|--------|--------|
| `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php` | ~860-895 | Procesar fotos_tela[N] con TelaFotoService |
| `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php` | ~947 | Pasar fotosTelasProcesadas al DTO |
| `app/Application/Pedidos/DTOs/ActualizarPrendaCompletaDTO.php` | 35, 39, 171 | Agregar fotosTelasProcesadas |
| `app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php` | ~437-540 | Inyectar rutas en fotos nuevas |
| `public/js/componentes/modal-novedad-edicion.js` | ~545, 559 | Detectar File wrapped + agregar FK |

---

## ✨ Beneficios de la Solución

1. **End-to-End**: Archivo → Procesamiento → Storage → BD → Frontend
2. **Robusta**: Validaciones en cada paso, logging detallado
3. **Mantenible**: Patrón claro Controller → DTO → UseCase
4. **Escalable**: Mismo patrón funciona para múltiples fotos
5. **Segura**: Archivos procesados antes de guardar en BD

---

## 🎓 Lecciones Aprendidas

- **HasManyThrough**: Es solo lectura, requiere acceso directo al modelo objetivo
- **Índices sincronizados**: Crucial para emparejar archivos con metadata
- **Procesamiento temprano**: Es mejor procesar archivos en controller que en UseCase
- **Inyección de dependencias**: DTO es perfecto para pasar datos complejos

---

## 🚀 Próximas Mejoras (Opcional)

- [ ] Validar dimensiones de imagen (ancho/alto mínimo)
- [ ] Compresión de imagen más agresiva
- [ ] Generar thumbnail para preview
- [ ] Eliminar temporales en caso de fallo
- [ ] Soporte para arrastrar-soltar (drag & drop)

---

**Estado Final**: ✅ FUNCIONANDO
**Fecha**: 2026-02-04
**Duración Total**: 11 sesiones
**Líneas Modificadas**: ~80
**Archivos Afectados**: 5

