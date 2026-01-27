# 🗂️ ÍNDICE DE ARCHIVOS - Problema de Imágenes Eliminadas

## 📑 DOCUMENTACIÓN GENERADA

Estos archivos contienen el análisis completo del problema:

1. **[ANALISIS_PROBLEMA_IMAGENES_SOFT_DELETE.md](ANALISIS_PROBLEMA_IMAGENES_SOFT_DELETE.md)**
   - Análisis completo del problema
   - Flujo causal del bug
   - Soluciones recomendadas
   - Referencias cruzadas

2. **[UBICACION_EXACTA_BUG_IMAGENES.md](UBICACION_EXACTA_BUG_IMAGENES.md)**
   - Ubicación precisa de cada bug
   - Números de línea exactos
   - Relaciones entre modelos
   - Flujo de ejecución paso a paso

3. **[CODIGO_EXACTO_BUGS.md](CODIGO_EXACTO_BUGS.md)**
   - Código exacto del problema
   - Opciones de fix
   - Verificación post-fix
   - Debug steps

---

## 🎯 ARCHIVOS PROBLEMÁTICOS

### 🔴 PRIORIDAD CRÍTICA

#### 1. Use Case #1 - MERGE pattern con flaw
**Archivo:** [app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php](app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php)

| Línea | Método | Problema |
|-------|--------|----------|
| 31 | `ejecutar()` | Orquestación del flujo |
| 267-330 | `actualizarColoresTelas()` | ⚠️ **BUG: Línea 276 soft-delete** |
| 372-441 | `actualizarFotosTelas()` | MERGE logic (OK, pero depende del bug anterior) |

**Estado:** NECESITA FIX URGENTE
**Impacto:** ALTO (silencioso, solo en algunos casos)

---

#### 2. Use Case #2 - Destructivo
**Archivo:** [app/Application/Pedidos/UseCases/ActualizarPrendaPedidoUseCase.php](app/Application/Pedidos/UseCases/ActualizarPrendaPedidoUseCase.php)

| Línea | Método | Problema |
|-------|--------|----------|
| 114-131 | `actualizarColoresTelas()` | ⚠️ **BUG CRÍTICO: Línea 121 y 125 soft-delete** |

**Estado:** NECESITA FIX INMEDIATO
**Impacto:** CRÍTICO (destruye datos siempre)

---

#### 3. Migración - Cascada
**Archivo:** [database/migrations/2026_01_28_add_foreign_keys_cascade_and_indexes.php](database/migrations/2026_01_28_add_foreign_keys_cascade_and_indexes.php)

| Línea | Tabla | Relación | Acción |
|-------|-------|----------|--------|
| 177-196 | `prenda_foto_tela_pedido` | FK a `prenda_pedido_colores_telas` | `onDelete('cascade')` |

**Estado:** WORKING AS DESIGNED (pero amplifica el bug del Use Case)
**Impacto:** Cascada que elimina fotos cuando colores/telas se borran

---

### 🟡 PRIORIDAD ALTA

#### 4. Modelo - Color-Tela
**Archivo:** [app/Models/PrendaPedidoColorTela.php](app/Models/PrendaPedidoColorTela.php)

| Línea | Relación | Modelo destino |
|-------|----------|-----------------|
| 47-49 | `fotos()` | `PrendaFotoTelaPedido` |

**Estado:** OK (relationships definidas correctamente)
**Nota:** Es víctima de la cascada, no causa

---

#### 5. Modelo - Foto Tela
**Archivo:** [app/Models/PrendaFotoTelaPedido.php](app/Models/PrendaFotoTelaPedido.php)

| Línea | Atributo | Valor |
|-------|----------|-------|
| 6 | use | `SoftDeletes` |
| 17 | - | - |
| 27-28 | relationship | `colorTela()` |

**Estado:** OK (usa SoftDeletes, pero se ve afectado por cascada)

---

#### 6. Controller - Actualización
**Archivo:** [app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php](app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php)

| Línea | Método | Acción |
|-------|--------|--------|
| ~750-900 | `actualizarPrendaCompleta()` | Procesa request, llama Use Case |
| ~825 | - | Procesa imagenes nuevas |
| ~884 | - | Crea DTO |
| ~885 | - | Llama Use Case |

**Estado:** OK (crea DTO correctamente, pero depende del Use Case)

---

#### 7. DTO - Data Transfer
**Archivo:** [app/Application/Pedidos/DTOs/ActualizarPrendaCompletaDTO.php](app/Application/Pedidos/DTOs/ActualizarPrendaCompletaDTO.php)

| Línea | Propiedad | Tipo | Nullable |
|-------|-----------|------|----------|
| 25 | `imagenes` | array | ✓ |
| 26 | `imagenesExistentes` | array | ✓ |
| 30 | `fotosTelas` | array | ✓ |
| 31 | `fotos` | array | ✓ |

**Estado:** OK (estructura correcta)
**Nota:** El problema es cómo se interpreta un array vacío

---

### 🟢 PRIORIDAD NORMAL

#### 8. Rutas - API
**Archivo:** [routes/api.php](routes/api.php)

| Línea | Ruta | Método | Controller |
|-------|------|--------|-----------|
| ~90 | POST `/api/prendas/{id}/actualizar` | - | `PedidosProduccionController@actualizarPrendaCompleta` |

**Estado:** OK

---

## 📊 Diagrama de Impacto

```
┌─────────────────────────────────────────────────────────────────┐
│ Frontend: Agregar fotos nuevas a prenda existente              │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ Controller (PedidosProduccionController)                       │
│ ✓ Procesa imagenes nuevas                                      │
│ ✓ Obtiene imagenes existentes                                  │
│ ✓ Crea DTO: ActualizarPrendaCompletaDTO                        │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ Use Case (ActualizarPrendaCompletaUseCase::ejecutar)           │
│ ├─ actualizarCamposBasicos() ✓                                 │
│ ├─ actualizarFotos() ✓                                         │
│ ├─ actualizarTallas() ✓                                        │
│ ├─ actualizarVariantes() ✓                                     │
│ ├─ actualizarColoresTelas() ⚠️ ← BUG AQUI                      │
│ │  └─ IF $dto->coloresTelas IS EMPTY:                         │
│ │     $prenda->coloresTelas()->delete()  ← SOFT DELETE         │
│ ├─ actualizarFotosTelas() ✓                                    │
│ ├─ actualizarProcesos() ✓                                      │
│ └─ guardarNovedad() ✓                                          │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ Database - Ejecución                                           │
│ DELETE FROM prenda_pedido_colores_telas WHERE ...              │
│ └─ CASCADA AUTOMÁTICA:                                         │
│    UPDATE prenda_fotos_tela_pedido SET deleted_at = NOW()      │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ RESULTADO:                                                      │
│ ✓ Nuevas fotos: CREADAS                                        │
│ ❌ Fotos antiguas: SOFT DELETED (deleted_at = NOW())           │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔗 Relaciones Entre Archivos

```
routes/api.php
    └─> PedidosProduccionController::actualizarPrendaCompleta()
        ├─> ActualizarPrendaCompletaDTO::fromRequest()
        │   └─> uses data from request
        │
        └─> ActualizarPrendaCompletaUseCase::ejecutar()
            ├─> actualizarColoresTelas() ⚠️
            │   └─> $prenda->coloresTelas()->delete()
            │       └─> triggers cascade in migration
            │           └─> Migración: 2026_01_28_add_foreign_keys_cascade_and_indexes.php
            │               ├─> prenda_pedido_colores_telas (línea 265)
            │               │   └─> PrendaPedidoColorTela model
            │               │       └─> hasMany PrendaFotoTelaPedido
            │               │
            │               └─> prenda_foto_tela_pedido (línea 190)
            │                   └─> onDelete('cascade')
            │                       └─> soft deletes en PrendaFotoTelaPedido model
            │                           └─> uses SoftDeletes (línea 17)
            │
            ├─> actualizarFotosTelas()
            │   └─> depends on coloresTelas existing
            │
            └─> return $prenda
                └─> Frontend receives deleted images
```

---

## 🧪 Testing Checklist

- [ ] **Test 1:** Crear prenda con colores/telas y fotos
- [ ] **Test 2:** Agregar nuevas fotos sin enviar coloresTelas
  - [ ] Verificar fotos nuevas se crean
  - [ ] **Verificar fotos antiguas NO se eliminan** ⚠️
- [ ] **Test 3:** Agregar nuevas fotos Y actualizar colores/telas
  - [ ] Verificar fotos antiguas se preservan
  - [ ] Verificar nuevas fotos se crean
- [ ] **Test 4:** Eliminar todas las fotos (array vacío)
  - [ ] Verificar que realmente se eliminen si es intención
- [ ] **Test 5:** Check database para soft deletes
  ```sql
  SELECT * FROM prenda_fotos_tela_pedido WHERE deleted_at IS NOT NULL;
  ```

---

## 📝 Logs a Revisar

**Archivo:** `storage/logs/laravel.log`

**Buscar logs de:**
1. `[ActualizarPrendaCompletaUseCase] Iniciando actualizacion` - línea 43
2. `[ActualizarPrendaCompletaUseCase] Variantes recibidas` - línea 216
3. `[ActualizarPrendaCompletaUseCase] Prenda completa actualizada` - línea 96
4. `[PedidosProduccionController] Datos validados` - línea 874

**Para debug adicional, agregar en controller:**
```php
\Log::info('[DEBUG] DTO recibido', [
    'coloresTelas' => $dto->coloresTelas,
    'is_null' => is_null($dto->coloresTelas),
    'is_empty' => empty($dto->coloresTelas),
    'fotosTelas' => $dto->fotosTelas,
]);
```

---

## ✅ Checklist de Archivos para Review

**DEBE REVISAR ESTOS:**
- [ ] [app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php](app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php) - Línea 267-330
- [ ] [app/Application/Pedidos/UseCases/ActualizarPrendaPedidoUseCase.php](app/Application/Pedidos/UseCases/ActualizarPrendaPedidoUseCase.php) - Línea 114-131
- [ ] [database/migrations/2026_01_28_add_foreign_keys_cascade_and_indexes.php](database/migrations/2026_01_28_add_foreign_keys_cascade_and_indexes.php) - Línea 177-196

**PUEDE REVISAR PARA CONTEXTO:**
- [ ] [app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php](app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php) - Línea 750-900
- [ ] [app/Models/PrendaPedidoColorTela.php](app/Models/PrendaPedidoColorTela.php) - Relaciones
- [ ] [app/Models/PrendaFotoTelaPedido.php](app/Models/PrendaFotoTelaPedido.php) - SoftDeletes

---

## 🚀 Próximos Pasos

1. **Confirmar el bug:**
   - Agregar logs en los Use Cases
   - Ejecutar actualización de prenda con fotos nuevas
   - Revisar si `coloresTelas` es `null` o `[]`

2. **Aplicar fix:**
   - Cambiar línea 274-277 en ActualizarPrendaCompletaUseCase
   - Cambiar línea 114-131 en ActualizarPrendaPedidoUseCase

3. **Testear:**
   - Crear prendas con fotos
   - Actualizar solo con fotos nuevas
   - Verificar BD que no hay soft deletes

4. **Revertir soft deletes existentes (si aplica):**
   ```sql
   -- Para restaurar fotos blandamente eliminadas
   UPDATE prenda_fotos_tela_pedido 
   SET deleted_at = NULL 
   WHERE prenda_pedido_colores_telas_id IN (
       SELECT id FROM prenda_pedido_colores_telas WHERE prenda_pedido_id = X
   );
   ```
