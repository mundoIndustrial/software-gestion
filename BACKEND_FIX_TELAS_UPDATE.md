# Backend Fix: Soportar UPDATE de Telas en Edición

## Cambio Requerido

En `ActualizarPrendaCompletaUseCase.php` línea 267-322:

### ANTES (Actual):
```php
private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
{
    // ... validaciones ...
    
    // PROBLEMA: Solo CREATE, no UPDATE
    foreach ($dto->coloresTelas as $colorTela) {
        // Busca por combinación color_id/tela_id
        // Si no existe, crea
        // Si existe, ignora
    }
}
```

### DESPUÉS (Requerido):
```php
private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
{
    // Patrón SELECTIVO
    if (is_null($dto->coloresTelas)) {
        return;  // No tocar si es null
    }

    if (empty($dto->coloresTelas)) {
        $prenda->coloresTelas()->delete();  // Eliminar si es array vacío
        return;
    }

    // ✅ MERGE PATTERN: UPDATE o CREATE según id
    foreach ($dto->coloresTelas as $colorTela) {
        $colorId = $colorTela['color_id'] ?? null;
        $telaId = $colorTela['tela_id'] ?? null;
        $referencia = $colorTela['referencia'] ?? null;  // NUEVA: referencia del pedido
        $id = $colorTela['id'] ?? null;  // ID de relación existente
        
        // Fallback: buscar por nombres si no hay IDs
        if (isset($colorTela['color_nombre']) && !$colorId) {
            $colorId = $this->obtenerOCrearColor($colorTela['color_nombre']);
        }
        
        if (isset($colorTela['tela_nombre']) && !$telaId) {
            $telaId = $this->obtenerOCrearTela($colorTela['tela_nombre']);
        }
        
        if (!$colorId || !$telaId) {
            continue;
        }
        
        // ✅ UPDATE: Si viene con ID, actualizar relación existente
        if ($id) {
            $colorTelaExistente = $prenda->coloresTelas()->where('id', $id)->first();
            if ($colorTelaExistente) {
                $colorTelaExistente->update([
                    'color_id' => $colorId,
                    'tela_id' => $telaId,
                    'referencia' => $referencia  // Actualizar referencia
                ]);
            }
        } 
        // ✅ CREATE: Si NO viene con ID, crear nueva relación
        else {
            // Verificar si ya existe esta combinación
            $existente = $prenda->coloresTelas()
                ->where('color_id', $colorId)
                ->where('tela_id', $telaId)
                ->first();
            
            if (!$existente) {
                $prenda->coloresTelas()->create([
                    'color_id' => $colorId,
                    'tela_id' => $telaId,
                    'referencia' => $referencia
                ]);
            }
        }
    }
}
```

## Frontend cambios ya implementados:

✅ `tela-processor.js` - Carga `id`, `color_id`, `tela_id` desde BD
✅ `modal-novedad-edicion.js` - Envía esos IDs al backend
✅ `prenda-form-collector.js` - Usa `window.telasCreacion` (separado)

## Resultado:

**Caso 1: Editar tela existente**
```
BD: id=5, color_id=1, tela_id=3 → {rojo, drill}
User modifica a: {azul, poliéster}

Payload:
{ "id": 5, "color_id": 1, "tela_id": 3, "color": "AZUL", "tela": "POLIÉSTER" }

Backend:
- Busca relación id=5
- Busca/crea color "AZUL" → color_id=2
- Busca/crea tela "POLIÉSTER" → tela_id=4
- UPDATE: color_id=2, tela_id=4
```

**Caso 2: Agregar tela nueva**
```
Payload:
{ "color": "VERDE", "tela": "LINO" }

Backend:
- SIN id = CREATE
- Busca/crea color "VERDE" → color_id=3
- Busca/crea tela "LINO" → tela_id=5
- CREATE: nueva relación
```

## Status:
- Frontend: ✅ LISTO
- Backend: ⏳ REQUIERE CAMBIO en ActualizarPrendaCompletaUseCase.php
