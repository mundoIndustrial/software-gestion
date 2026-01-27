# 📍 UBICACIÓN EXACTA DE LA LÓGICA PROBLEMÁTICA

## 🔴 PUNTO CRÍTICO #1: Actualización de Colores y Telas

**Archivo:** `app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php`

**Método:** `actualizarColoresTelas()`

**Líneas:** 267-330

**Código problemático:**
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
276          $prenda->coloresTelas()->delete();  // ⚠️ BUG: SOFT DELETE de coloresTelas
277          return;
278      }
279
280      // ✅ MERGE PATTERN: UPDATE o CREATE según id
281      foreach ($dto->coloresTelas as $colorTela) {
282          // ... resto de lógica MERGE
283      }
284  }
```

**¿Por qué es un problema?**
- Línea 276 ejecuta SOFT DELETE cuando `$dto->coloresTelas` es un array vacío `[]`
- Esto borra TODAS las relaciones `prenda_pedido_colores_telas`
- Debido a la cascada en la BD (línea 190 de migración), esto TAMBIÉN soft-deletes todas las `prenda_fotos_tela_pedido` relacionadas
- El frontend NUNCA envía `coloresTelas = []` cuando solo agrega fotos, por lo que esto es un bug silencioso

---

## 🔴 PUNTO CRÍTICO #2: Cascada en la Base de Datos

**Archivo:** `database/migrations/2026_01_28_add_foreign_keys_cascade_and_indexes.php`

**Líneas:** 177-195

**Código:**
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
190                  ->onDelete('cascade');  // ⚠️ CASCADA: Borra fotos cuando se borra color-tela
191          }
192
193          if (!Schema::hasColumn('prenda_foto_tela_pedido', 'idx_tela_id')) {
194              $table->index('prenda_pedido_colores_telas_id');
195          }
196      }
```

**¿Por qué es un problema?**
- Línea 190 define `onDelete('cascade')`
- Cuando se ejecuta `$prenda->coloresTelas()->delete()` en el Use Case, la BD automáticamente ejecuta cascada
- Esto marca como deleted_at (soft delete) a todas las imágenes en `prenda_fotos_tela_pedido`

---

## 🔴 PUNTO CRÍTICO #3: Mismo Bug en Otro Use Case (DUPLICADO)

**Archivo:** `app/Application/Pedidos/UseCases/ActualizarPrendaPedidoUseCase.php`

**Método:** `actualizarColoresTelas()`

**Líneas:** 114-130

**Código:**
```php
114  private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaPedidoDTO $dto): void
115  {
116      if (is_null($dto->coloresTelas)) {
117          return;
118      }
119
120      if (empty($dto->coloresTelas)) {
121          $prenda->coloresTelas()->delete();  // ⚠️ BUG DUPLICADO
122          return;
123      }
124
125      $prenda->coloresTelas()->delete();  // ⚠️ OTRO BUG: Siempre borra antes de crear
126      foreach ($dto->coloresTelas as $colorTela) {
127          // ...
128      }
129  }
```

**Diferencias entre los dos Use Cases:**

| Aspecto | ActualizarPrendaCompletaUseCase | ActualizarPrendaPedidoUseCase |
|---------|-----------------------------|-----------------------------|
| Línea con bug | 276 | 121, 125 |
| Patrón | MERGE (intenta preservar) | DESTRUCTIVO (siempre borra) |
| Severity | ALTO (silencioso) | CRÍTICO (borra siempre) |

---

## 📊 Relación de Modelos con Soft Deletes

```
PrendaPedido
    ├── coloresTelas() [PrendaPedidoColorTela]
    │   └── fotos() [PrendaFotoTelaPedido] ← Soft Delete aquí
    │       └── uses SoftDeletes (línea 17 del modelo)
    │
    └── fotosTelas() [HasManyThrough] ← También usa Soft Deletes
        └── uses SoftDeletes
```

**Archivos:**
- [app/Models/PrendaPedido.php](app/Models/PrendaPedido.php) - Línea 110-113
- [app/Models/PrendaPedidoColorTela.php](app/Models/PrendaPedidoColorTela.php) - Línea 47-49
- [app/Models/PrendaFotoTelaPedido.php](app/Models/PrendaFotoTelaPedido.php) - Línea 6, 17

---

## 🔄 FLUJO DE EJECUCIÓN

```
1. Usuario: Agrega fotos nuevas a prenda existente

2. Frontend envía:
   POST /api/pedidos/{id}/prendas/actualizar
   {
     prenda_id: 5,
     imagenes: [...nuevas],
     // ❌ NO envía: coloresTelas
   }

3. PedidosProduccionController::actualizarPrendaCompleta() (línea ~850)
   ├─ Procesa imagenes_nuevas ✓
   ├─ Obtiene imagenes_existentes ✓
   └─ Crea DTO:
      ActualizarPrendaCompletaDTO::fromRequest(
         prendaId, 
         datos,
         imagenesGuardadas,
         imagenesExistentes
      )

4. ActualizarPrendaCompletaUseCase::ejecutar() (línea 31)
   ├─ actualizarCamposBasicos() ✓
   ├─ actualizarFotos() ✓
   ├─ actualizarTallas() ✓
   ├─ actualizarVariantes() ✓
   ├─ actualizarColoresTelas() ⚠️ ← AQUI VA EL PROBLEMA
   │  └─ $dto->coloresTelas = NULL (nunca fue enviado)
   │     return; // No hace nada
   │
   │  PERO si $dto->coloresTelas = [] (vacío):
   │     $prenda->coloresTelas()->delete() ⚠️
   │        ↓
   │        DB: DELETE FROM prenda_pedido_colores_telas WHERE prenda_pedido_id=5
   │        ↓
   │        CASCADA: UPDATE prenda_fotos_tela_pedido SET deleted_at=NOW()
   │        WHERE prenda_pedido_colores_telas_id IN (...)
   │
   ├─ actualizarFotosTelas() ✓
   ├─ actualizarProcesos() ✓
   └─ guardarNovedad() ✓

5. Resultado:
   ✅ Nuevas fotos: CREADAS
   ❌ Fotos antiguas: MARCADAS COMO DELETED (soft delete)
```

---

## 🧪 CÓMO REPRODUCIR EL BUG

**Paso 1:** Crear una prenda con fotos de telas
```
POST /api/prendas-pedido
{
  nombre_prenda: "CAMISA POLO",
  coloresTelas: [
    { color_id: 1, tela_id: 10 }  // Rojo, Algodón
  ],
  fotosTelas: [
    { prenda_pedido_colores_telas_id: 1, ruta_original: "/fotos/tela1.jpg" }
  ]
}
```

**Paso 2:** Actualizar la prenda SOLO con nuevas fotos
```
PATCH /api/prendas-pedido/5/actualizar
{
  prenda_id: 5,
  imagenes: [
    { ruta_original: "/fotos/nueva_foto.jpg" }  // Foto nueva
  ]
  // ❌ No enviar coloresTelas
}
```

**Resultado esperado:**
- ✅ Foto nueva se crea
- ✅ Foto antigua se preserva

**Resultado real (BUG):**
- ✅ Foto nueva se crea
- ❌ Foto antigua → deleted_at = NOW() (SOFT DELETE)

---

## 💡 DIAGNÓSTICO RÁPIDO

Para verificar si el bug está ocurriendo:

```sql
-- Ver fotos con soft delete en una prenda
SELECT * FROM prenda_fotos_tela_pedido 
WHERE prenda_pedido_colores_telas_id = 1 
AND deleted_at IS NOT NULL;

-- Ver colores-telas blandamente eliminados
SELECT * FROM prenda_pedido_colores_telas 
WHERE prenda_pedido_id = 5 
AND deleted_at IS NOT NULL;
```

Si hay registros con `deleted_at IS NOT NULL` cuando debería haber fotos, entonces el bug está en efecto.

---

## 📞 REFERENCIAS CRUZADAS

| Concepto | Archivo | Línea |
|----------|---------|-------|
| DTO Builder | [app/Application/Pedidos/DTOs/ActualizarPrendaCompletaDTO.php](app/Application/Pedidos/DTOs/ActualizarPrendaCompletaDTO.php) | ~37 |
| Use Case Ejecutor | [app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php](app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php) | 31 |
| Controller que llama | [app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php](app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php) | ~885 |
| Modelo con cascada | [app/Models/PrendaPedidoColorTela.php](app/Models/PrendaPedidoColorTela.php) | 47-49 |
| Migración cascada | [database/migrations/2026_01_28_add_foreign_keys_cascade_and_indexes.php](database/migrations/2026_01_28_add_foreign_keys_cascade_and_indexes.php) | 190 |
