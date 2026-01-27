# 🔍 ANÁLISIS: Problema de Imágenes Marcadas como Deleted_at al Actualizar Prendas

## 📋 Resumen del Problema
Cuando un usuario agrega fotos nuevas a una prenda que **ya existe en la BD**, las fotos **antiguas se marcan como deleted_at (soft delete)** en lugar de preservarse.

---

## 🎯 Archivos y Lógica Problemática Encontrada

### 1. **RUTA DE API**
**Archivo:** [routes/api.php](routes/api.php)

```php
// Línea ~90 (aproximada)
Route::post('prendas/{id}/actualizar', [PedidosProduccionController::class, 'actualizarPrendaCompleta'])
```

---

### 2. **CONTROLADOR PRINCIPAL**
**Archivo:** [app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php](app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php)

**Método:** `actualizarPrendaCompleta()` (líneas ~750-900)

**Lógica:**
1. Valida los datos de entrada
2. Procesa nuevas imágenes desde `prendas[0][imagenes]`
3. Obtiene `imagenes_existentes` desde JSON
4. Llama al Use Case `ActualizarPrendaCompletaUseCase::ejecutar()`

```php
// Línea ~825
$rutas = $prendaFotoService->procesarFoto($imagen);
$imagenesGuardadas[] = $rutas;

// Línea ~884
$dto = ActualizarPrendaCompletaDTO::fromRequest(
    $validated['prenda_id'], 
    $validated, 
    $imagenesGuardadas,      // ← Nuevas imágenes
    $imagenesExistentes      // ← Imágenes existentes a preservar
);

// Línea ~885
$prenda = $this->actualizarPrendaCompletaUseCase->ejecutar($dto);
```

---

### 3. ⚠️ **USO CASE - LÓGICA PROBLEMÁTICA**
**Archivo:** [app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php](app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php)

#### **Problema en: `actualizarColoresTelas()`** (líneas 267-330)

```php
private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
{
    // PROBLEMA: Si es null, no toca. Si es VACIO, BORRA TODO!
    if (is_null($dto->coloresTelas)) {
        return;
    }

    // ⚠️ AQUI ESTA EL BUG
    if (empty($dto->coloresTelas)) {
        // Si viene array vacío, es intención explícita de eliminar TODO
        $prenda->coloresTelas()->delete();  // ← SOFT DELETE de todas las relaciones color-tela
        return;
    }
    
    // ... resto de lógica
}
```

#### **Cascada en Base de Datos** (línea 190 en migración)
**Archivo:** [database/migrations/2026_01_28_add_foreign_keys_cascade_and_indexes.php](database/migrations/2026_01_28_add_foreign_keys_cascade_and_indexes.php)

```php
// Línea 187-190
$table->foreign('prenda_pedido_colores_telas_id')
    ->references('id')
    ->on('prenda_pedido_colores_telas')
    ->onDelete('cascade');  // ← CASCADA: Si se borra color-tela, borra sus fotos
```

---

## 🔗 **FLUJO CAUSAL DEL BUG**

```
1. Usuario agrega fotos nuevas a prenda existente
                    ↓
2. Frontend envía: { imagenes_nuevas: [...], imagenes_existentes: [...] }
                    ↓
3. PedidosProduccionController::actualizarPrendaCompleta()
   - Procesa imagenes_nuevas ✓
   - Obtiene imagenes_existentes ✓
   - Llama al UseCase ✓
                    ↓
4. ActualizarPrendaCompletaUseCase::ejecutar()
   - Llama a actualizarColoresTelas($prenda, $dto)
                    ↓
5. actualizarColoresTelas() RECIBE $dto->coloresTelas = NULL o []
   - SI NULL: retorna, no toca ✓
   - SI []: EJECUTA $prenda->coloresTelas()->delete() ⚠️
                    ↓
6. Base de datos ejecuta:
   DELETE FROM prenda_pedido_colores_telas 
   WHERE prenda_pedido_id = X
                    ↓
7. CASCADA automática (onDelete('cascade')):
   UPDATE prenda_fotos_tela_pedido 
   SET deleted_at = NOW()
   WHERE prenda_pedido_colores_telas_id IN (...)
                    ↓
8. RESULTADO: ❌ Todas las fotos antiguas = SOFT DELETED
```

---

## 🧪 Modelos con Soft Delete

### Modelos afectados:
- [app/Models/PrendaFotoPed.php](app/Models/PrendaFotoPed.php) - usa `SoftDeletes`
- [app/Models/PrendaFotoTelaPedido.php](app/Models/PrendaFotoTelaPedido.php) - usa `SoftDeletes`
- [app/Models/PrendaFotoPedido.php](app/Models/PrendaFotoPedido.php) - usa `SoftDeletes`
- [app/Models/PrendaFotoLogoPedido.php](app/Models/PrendaFotoLogoPedido.php) - usa `SoftDeletes`

### Modelo relación:
- [app/Models/PrendaPedidoColorTela.php](app/Models/PrendaPedidoColorTela.php)
  - Línea 47-49: `fotos()` relationship con `PrendaFotoTelaPedido`

---

## 📊 Comparación de Dos Use Cases

### Use Case MALO ⚠️
**Archivo:** [app/Application/Pedidos/UseCases/ActualizarPrendaPedidoUseCase.php](app/Application/Pedidos/UseCases/ActualizarPrendaPedidoUseCase.php)

```php
// Línea 120, 124 - Siempre borra coloresTelas aunque esté vacío
private function actualizarColoresTelas(...) {
    if (is_null($dto->coloresTelas)) {
        return;
    }
    
    if (empty($dto->coloresTelas)) {
        $prenda->coloresTelas()->delete();  // ⚠️ BUG IGUAL
        return;
    }
    
    $prenda->coloresTelas()->delete();  // ⚠️ BUG: Borra antes de crear
    // ...
}
```

---

### Use Case MEJOR (ActualizarPrendaCompletaUseCase) ✅
**Archivo:** [app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php](app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php)

Tiene intención de MERGE (línea 285):
```php
// ✅ MERGE PATTERN: UPDATE o CREATE según id
foreach ($dto->coloresTelas as $colorTela) {
    // ... lógica selectiva
}
```

PERO el problema está en línea 276:
```php
if (empty($dto->coloresTelas)) {
    $prenda->coloresTelas()->delete();  // ⚠️ BUG: Borra todo si array vacío
    return;
}
```

---

## 🔴 RAÍZ DEL PROBLEMA

| Punto | Problema | Línea |
|-------|----------|-------|
| 1️⃣ Frontend | NO envía `coloresTelas` cuando agrega solo fotos nuevas | - |
| 2️⃣ DTO | Recibe `coloresTelas = NULL` pero también acepta `[]` vacío | ActualizarPrendaCompletaDTO |
| 3️⃣ Use Case | Trata `[]` como intención de "eliminar todo" | Línea 276 |
| 4️⃣ Base de datos | Cascada automática elimina fotos relacionadas | Migración línea 190 |

---

## 🛠️ SOLUCIONES RECOMENDADAS

### Opción A: Cambiar la lógica del Use Case (RECOMENDADO)
**Archivo:** [app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php](app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php)

**Cambio en `actualizarColoresTelas()` línea 267-330:**

```php
private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
{
    // ✅ PATRÓN SELECTIVO: Si es null, NO tocar (es actualización parcial)
    if (is_null($dto->coloresTelas)) {
        return;  // ← No toca los colores/telas existentes
    }

    // ⚠️ PROBLEMA: Un array vacío [] se interpreta como "eliminar todo"
    // PERO el frontend NUNCA envía datos de coloresTelas si solo agrega fotos
    // Entonces esto nunca debería ejecutarse

    // OPCIÓN 1: Solo si es EXPLÍCITAMENTE array vacío (menos probable)
    if (empty($dto->coloresTelas)) {
        // Verificar que el usuario REALMENTE quiere eliminar
        // (agregar flag: $dto->deleteAllColorsTelas = true?)
        // POR AHORA: NO HACER NADA
        return;
    }

    // ... resto sin cambios
}
```

### Opción B: Cambiar cómo el Frontend envía datos

El frontend debería enviar explícitamente los colores/telas existentes cuando actualiza solo fotos:

```javascript
// Antes (BUG):
POST /api/pedidos/1/prendas/5/actualizar {
  imagenes_nuevas: [...],
  // falta: coloresTelas
}

// Después (CORRECTO):
POST /api/pedidos/1/prendas/5/actualizar {
  imagenes_nuevas: [...],
  coloresTelas: [  // Enviar aunque solo hay fotos nuevas
    { id: 1, color_id: 5, tela_id: 10 },
    { id: 2, color_id: 6, tela_id: 11 }
  ]
}
```

---

## 📝 Archivos a revisar para contexto completo:

1. **Rutas:**
   - [routes/api.php](routes/api.php) - Definición de endpoints

2. **Controllers:**
   - [app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php](app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php)

3. **Use Cases:**
   - [app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php](app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php) ⚠️
   - [app/Application/Pedidos/UseCases/ActualizarPrendaPedidoUseCase.php](app/Application/Pedidos/UseCases/ActualizarPrendaPedidoUseCase.php) ⚠️

4. **DTOs:**
   - [app/Application/Pedidos/DTOs/ActualizarPrendaCompletaDTO.php](app/Application/Pedidos/DTOs/ActualizarPrendaCompletaDTO.php)

5. **Modelos:**
   - [app/Models/PrendaFotoTelaPedido.php](app/Models/PrendaFotoTelaPedido.php)
   - [app/Models/PrendaPedidoColorTela.php](app/Models/PrendaPedidoColorTela.php)

6. **Migraciones:**
   - [database/migrations/2026_01_28_add_foreign_keys_cascade_and_indexes.php](database/migrations/2026_01_28_add_foreign_keys_cascade_and_indexes.php)

---

## 🎓 Resumen Técnico

El problema es una **mala interpretación de la semántica de array vacío**:

- `null` = "No envío datos, no toques esto" ✅
- `[]` = "Envío datos vacío, elimina todo" ❌ (pero el frontend nunca lo envía así)

**Resultado:** El backend asume que `[]` significa "elimina todo", pero el frontend nunca envía ese escenario al actualizar solo fotos.
