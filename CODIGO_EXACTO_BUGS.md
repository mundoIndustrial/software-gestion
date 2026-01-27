# 🎯 CÓDIGO EXACTO - Lugares del Bug

## BUG #1: ActualizarPrendaCompletaUseCase - actualizarColoresTelas()

**Archivo:** `app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php`

**Línea 276 - EL PUNTO CRÍTICO:**

```php
270     if (is_null($dto->coloresTelas)) {
271         return;
272     }
273
274     if (empty($dto->coloresTelas)) {
275         // Si viene array vacío, es intención explícita de eliminar TODO
276         $prenda->coloresTelas()->delete();  // ⚠️⚠️⚠️ AQUI ESTA
277         return;
278     }
```

**¿Qué hace?**
- Línea 276 ejecuta un `delete()` en la relación `coloresTelas`
- Esto dispara soft delete en `prenda_pedido_colores_telas` table
- Que a su vez dispara cascada en `prenda_fotos_tela_pedido`

**Contexto completo de la función:**

```php
267  private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
268  {
269      // Patrón SELECTIVO: Si es null, NO tocar (es actualización parcial)
270      if (is_null($dto->coloresTelas)) {
271          return;
272      }
273
274      if (empty($dto->coloresTelas)) {
275          // Si viene array vacío, es intención explícita de eliminar TODO
276          $prenda->coloresTelas()->delete();  // ⚠️⚠️⚠️ BUG AQUI
277          return;
278      }
279
280      // ✅ MERGE PATTERN: UPDATE o CREATE según id
281      foreach ($dto->coloresTelas as $colorTela) {
282          $colorId = $colorTela['color_id'] ?? null;
283          $telaId = $colorTela['tela_id'] ?? null;
284          $referencia = $colorTela['referencia'] ?? null;
285          $id = $colorTela['id'] ?? null;  // ID de relación existente
286          
287          // ... resto del MERGE logic
308      }
309  }
```

---

## BUG #2: ActualizarPrendaPedidoUseCase - actualizarColoresTelas()

**Archivo:** `app/Application/Pedidos/UseCases/ActualizarPrendaPedidoUseCase.php`

**Línea 121 - PRIMER BUG:**

```php
114  private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaPedidoDTO $dto): void
115  {
116      if (is_null($dto->coloresTelas)) {
117          return;
118      }
119
120      if (empty($dto->coloresTelas)) {
121          $prenda->coloresTelas()->delete();  // ⚠️⚠️⚠️ BUG #1
122          return;
123      }
124
125      $prenda->coloresTelas()->delete();  // ⚠️⚠️⚠️ BUG #2 (siempre borra)
126      foreach ($dto->coloresTelas as $colorTela) {
127          $prenda->coloresTelas()->create([
128              'color_id' => $colorTela['color_id'] ?? null,
129              'tela_id' => $colorTela['tela_id'] ?? null,
129          ]);
130      }
131  }
```

**¿Por qué es peor?**
- Línea 121: Borra si array vacío
- Línea 125: **SIEMPRE borra antes de crear** (incluso con datos válidos)
- Esto es un patrón DESTRUCTIVO, no MERGE

---

## BUG #3: Cascada en Migración

**Archivo:** `database/migrations/2026_01_28_add_foreign_keys_cascade_and_indexes.php`

**Línea 190 - LA CASCADA:**

```php
177  if (Schema::hasTable('prenda_foto_tela_pedido') && Schema::hasTable('prenda_pedido_colores_telas')) {
178      Schema::table('prenda_foto_tela_pedido', function (Blueprint $table) {
179          $keyExists = DB::selectOne("
180              SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
181              WHERE TABLE_NAME = 'prenda_foto_tela_pedido' 
182              AND COLUMN_NAME = 'prenda_pedido_colores_telas_id' 
183              AND REFERENCED_TABLE_NAME = 'prenda_pedido_colores_telas'
184          ") !== null;
185
186          if (!$keyExists) {
187              $table->foreign('prenda_pedido_colores_telas_id')
188                  ->references('id')
189                  ->on('prenda_pedido_colores_telas')
190                  ->onDelete('cascade');  // ⚠️⚠️⚠️ CASCADA AQUI
191          }
192
193          if (!Schema::hasColumn('prenda_foto_tela_pedido', 'idx_tela_id')) {
194              $table->index('prenda_pedido_colores_telas_id');
195          }
196      });
197  }
```

**¿Qué hace?**
- Cuando se ejecuta `DELETE FROM prenda_pedido_colores_telas WHERE id = X`
- La BD automáticamente ejecuta: `UPDATE prenda_fotos_tela_pedido SET deleted_at = NOW() WHERE prenda_pedido_colores_telas_id = X`

---

## 📋 Resumen de Cambios Necesarios

### OPCIÓN 1: Fix rápido en ActualizarPrendaCompletaUseCase (RECOMENDADO)

**Cambiar línea 274-277 de:**
```php
if (empty($dto->coloresTelas)) {
    // Si viene array vacío, es intención explícita de eliminar TODO
    $prenda->coloresTelas()->delete();
    return;
}
```

**A:**
```php
if (empty($dto->coloresTelas)) {
    // Si viene array vacío, NO hacer nada (es actualización parcial sin datos de colores)
    // El frontend nunca envía esto, así que es seguro ignorarlo
    return;
}
```

---

### OPCIÓN 2: Fix más seguro (conservador)

**Cambiar línea 267-278 completamente de:**
```php
private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
{
    // Patrón SELECTIVO: Si es null, NO tocar (es actualización parcial)
    if (is_null($dto->coloresTelas)) {
        return;
    }

    if (empty($dto->coloresTelas)) {
        // Si viene array vacío, es intención explícita de eliminar TODO
        $prenda->coloresTelas()->delete();
        return;
    }
```

**A:**
```php
private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
{
    // Patrón SELECTIVO: Si es null o vacío, NO tocar (es actualización parcial)
    if (is_null($dto->coloresTelas) || empty($dto->coloresTelas)) {
        return;  // ✅ Preservar colores existentes
    }
```

---

### OPCIÓN 3: Fix en ActualizarPrendaPedidoUseCase (CRÍTICO)

**Cambiar línea 114-131 de:**
```php
private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaPedidoDTO $dto): void
{
    if (is_null($dto->coloresTelas)) {
        return;
    }

    if (empty($dto->coloresTelas)) {
        $prenda->coloresTelas()->delete();
        return;
    }

    $prenda->coloresTelas()->delete();  // ⚠️ Siempre borra
    foreach ($dto->coloresTelas as $colorTela) {
        $prenda->coloresTelas()->create([
            'color_id' => $colorTela['color_id'] ?? null,
            'tela_id' => $colorTela['tela_id'] ?? null,
        ]);
    }
}
```

**A:**
```php
private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaPedidoDTO $dto): void
{
    // Patrón SELECTIVO: Si es null, NO tocar (es actualización parcial)
    if (is_null($dto->coloresTelas)) {
        return;
    }

    if (empty($dto->coloresTelas)) {
        // Array vacío = intención explícita de eliminar TODO
        $prenda->coloresTelas()->delete();
        return;
    }

    // ✅ MERGE PATTERN: Buscar existentes, actualizar o crear nuevos
    $existentes = $prenda->coloresTelas()->get()->keyBy(function($ct) {
        return "{$ct->color_id}_{$ct->tela_id}";
    });

    // Crear nuevos
    foreach ($dto->coloresTelas as $colorTela) {
        $key = "{$colorTela['color_id']}_{$colorTela['tela_id']}";
        if (!isset($existentes[$key])) {
            $prenda->coloresTelas()->create([
                'color_id' => $colorTela['color_id'] ?? null,
                'tela_id' => $colorTela['tela_id'] ?? null,
            ]);
        }
    }

    // Eliminar los que ya no están
    foreach ($existentes as $key => $colorTelaRecord) {
        $keys = explode('_', $key);
        $existe = collect($dto->coloresTelas)->first(function($ct) use ($keys) {
            return $ct['color_id'] == $keys[0] && $ct['tela_id'] == $keys[1];
        });
        if (!$existe) {
            $colorTelaRecord->delete();
        }
    }
}
```

---

## 📊 Tabla de Comparación de Bugs

| Métrica | Bug #1 (Completa) | Bug #2 (Pedido) | Severidad |
|---------|------------------|-----------------|-----------|
| Archivo | ActualizarPrendaCompletaUseCase | ActualizarPrendaPedidoUseCase | - |
| Línea | 276 | 121, 125 | - |
| Tipo | Silencioso (solo si array vacío) | Destructivo (siempre borra) | Bug #2 CRÍTICO |
| Patrón | MERGE intent + flaw | DESTRUCTIVO | - |
| Frecuencia | Rara (frontend no envía []) | Común | Bug #2 pior |
| Impacto | Fotos eliminadas | Datos eliminados | Bug #2 pior |

---

## 🔍 Cómo Debuggear

**En el controlador, agregar logs:**

```php
// PedidosProduccionController::actualizarPrendaCompleta()

$dto = ActualizarPrendaCompletaDTO::fromRequest($validated['prenda_id'], $validated, $imagenesGuardadas, $imagenesExistentes);

// DEBUG
\Log::info('[DEBUG] DTO coloresTelas', [
    'is_null' => is_null($dto->coloresTelas),
    'is_empty' => empty($dto->coloresTelas),
    'value' => $dto->coloresTelas,
]);

$prenda = $this->actualizarPrendaCompletaUseCase->ejecutar($dto);
```

**Luego revisar `storage/logs/laravel.log`** para ver qué se está enviando.

---

## ✅ Verificación Post-Fix

Después de aplicar el fix, verificar:

```sql
-- 1. Verificar que NO hay soft deletes nuevos
SELECT COUNT(*) as fotos_eliminadas FROM prenda_fotos_tela_pedido 
WHERE prenda_pedido_colores_telas_id = 1 
AND deleted_at IS NOT NULL;
-- Debe retornar: 0 (o el mismo número de antes)

-- 2. Verificar que colores/telas se preservan
SELECT * FROM prenda_pedido_colores_telas 
WHERE prenda_pedido_id = 5 
AND deleted_at IS NULL;
-- Debe retornar: Todos los colores/telas originales

-- 3. Verificar fotos antiguas
SELECT id, ruta_original, deleted_at FROM prenda_fotos_tela_pedido 
WHERE prenda_pedido_colores_telas_id IN (
    SELECT id FROM prenda_pedido_colores_telas 
    WHERE prenda_pedido_id = 5
)
ORDER BY created_at DESC;
-- Debe mostrar: fotos antiguas sin deleted_at, nuevas fotos sin deleted_at
```
