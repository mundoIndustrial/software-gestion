# ANÁLISIS: Problema de Actualización Destructiva en ActualizarPrendaCompletaUseCase

## 🔴 PROBLEMA IDENTIFICADO

El `ActualizarPrendaCompletaUseCase` está eliminando TODOS los datos relacionados cada vez que se actualiza una prenda, incluso si solo se cambió UN campo.

### Ejemplos del Problema:

**Caso 1: Cambiar solo el COLOR**
```
ANTES: color=Rojo, tela=Algodón, fotos_tela=[foto1.webp, foto2.webp]
ACCIÓN: Usuario cambia color a Azul
ACTUAL (❌ INCORRECTO):
  - Elimina color Rojo
  - Crea color Azul
  - ❌ ELIMINA TAMBIÉN: tela (quedaría sin tela)
  - ❌ ELIMINA TAMBIÉN: fotos_tela
  - ❌ ELIMINA TAMBIÉN: variantes (manga, broche)
  - ❌ ELIMINA TAMBIÉN: tallas (S, M, L, XL)

CORRECTO ✅:
  - Actualiza solo la combinación color-tela
  - Preserva: fotos_tela, variantes, tallas, procesos
```

**Caso 2: Cambiar solo la TELA**
```
ANTES: color=Rojo, tela=Algodón
ACCIÓN: Usuario cambia tela a Poliéster
ACTUAL (❌ INCORRECTO):
  - ❌ Elimina color Rojo (que aún es válido)
  - ❌ Crea nueva combinación color-tela

CORRECTO ✅:
  - Solo actualiza tela en la combinación color-tela
  - Preserva: color, variantes, tallas, fotos
```

## 📋 ARCHIVOS AFECTADOS

### [ActualizarPrendaCompletaUseCase.php](app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php)

**Métodos problemáticos:**

1. **`actualizarFotos()` (línea ~100)**
   ```php
   // ❌ INCORRECTO: Siempre elimina todas las fotos
   if (empty($dto->fotos)) {
       $prenda->fotos()->delete();  // Elimina TODO
       return;
   }
   $prenda->fotos()->delete();  // Elimina TODO de nuevo
   ```

2. **`actualizarColoresTelas()` (línea ~220)**
   ```php
   // ❌ INCORRECTO: Siempre elimina todas las combinaciones
   $prenda->coloresTelas()->delete();  // Elimina TODO
   foreach ($dto->coloresTelas as $colorTela) {
       // Crea nuevos...
   }
   ```

3. **`actualizarFotosTelas()` (línea ~310)**
   ```php
   // ❌ INCORRECTO: Siempre elimina todas las fotos de telas
   $prenda->fotosTelas()->delete();  // Elimina TODO
   ```

4. **`actualizarVariantes()` (línea ~200)**
   ```php
   // ❌ INCORRECTO: Siempre elimina todas las variantes
   $prenda->variantes()->delete();  // Elimina TODO
   ```

5. **`actualizarTallas()` (línea ~135)**
   ```php
   // ❌ INCORRECTO: Elimina tallas no especificadas
   if (empty($dto->cantidadTalla)) {
       $prenda->tallas()->delete();  // Elimina TODO
   }
   ```

## 🎯 SOLUCIÓN REQUERIDA

### Patrón: "Actualización Selectiva"

**Principio:** Si un campo NO viene en el DTO (es null), NO tocar ese dato en la base de datos.

```php
// ❌ ACTUALMENTE:
private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
{
    if (is_null($dto->coloresTelas)) {
        return;  // Si es null, OK
    }
    
    if (empty($dto->coloresTelas)) {
        $prenda->coloresTelas()->delete();  // ❌ PROBLEMA: Elimina si array vacío
        return;
    }
    
    $prenda->coloresTelas()->delete();  // ❌ PROBLEMA: Siempre elimina
}

// ✅ DEBERÍA SER:
private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
{
    if (is_null($dto->coloresTelas)) {
        return;  // Si es null, NO tocar (es actualización parcial)
    }
    
    if (empty($dto->coloresTelas)) {
        // Si viene array vacío, es intención explícita de eliminar
        $prenda->coloresTelas()->delete();
        return;
    }
    
    // ACTUALIZAR SOLO:
    // - Obtener combinaciones existentes
    // - Comparar con las nuevas
    // - Eliminar solo las que NO están en las nuevas
    // - Crear solo las que NO existen
    // - NO ELIMINAR TODO
}
```

## 📊 TABLA DE ACTUALIZACIÓN CORRECTA

| Campo DTO | Estado | Acción Correcta |
|-----------|--------|-----------------|
| `coloresTelas` | null | ✅ NO TOCAR (es actualización parcial) |
| `coloresTelas` | [] (vacío) | ✅ ELIMINAR TODO (intención explícita) |
| `coloresTelas` | [datos] | ✅ ACTUALIZAR solo diferencias |
| `variantes` | null | ✅ NO TOCAR |
| `variantes` | [] (vacío) | ✅ ELIMINAR TODO |
| `variantes` | [datos] | ✅ ACTUALIZAR solo diferencias |

## 🔧 REFACTORIZACIÓN REQUERIDA

### Para cada método que actualiza relaciones:

1. **`actualizarColoresTelas()`**
   - Si null → return (no tocar)
   - Si [] → delete() y return (limpiar explícitamente)
   - Si [datos] → comparar y actualizar solo lo necesario

2. **`actualizarVariantes()`**
   - Mismo patrón
   - Permitir actualizar parcialmente variantes

3. **`actualizarFotos()`**
   - Mismo patrón

4. **`actualizarFotosTelas()`**
   - Mismo patrón

5. **`actualizarTallas()`**
   - ✅ YA IMPLEMENTA CORRECTAMENTE (línea ~135)
   - Preserva tallas no especificadas

## ✅ EJEMPLO: Lo que tallas hace BIEN

```php
private function actualizarTallas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
{
    if (is_null($dto->cantidadTalla)) {
        return;  // ✅ No tocar si no viene
    }

    if (empty($dto->cantidadTalla)) {
        $prenda->tallas()->delete();  // Eliminar explícitamente si vacío
        return;
    }

    // ✅ CORRECTO: Obtener existentes, comparar, eliminar solo lo necesario
    $tallasExistentes = $prenda->tallas()->get()->keyBy(...);
    $tallasNuevas = [];
    
    // Eliminar solo tallas NO en la nueva lista
    foreach ($tallasExistentes as $key => $tallaRecord) {
        if (!isset($tallasNuevas[$key])) {
            $tallaRecord->delete();  // Solo elimina si no está en nuevas
        }
    }
    
    // Insertar o actualizar
    foreach ($tallasNuevas as $key => $dataTalla) {
        if (isset($tallasExistentes[$key])) {
            $tallasExistentes[$key]->update(...);  // Actualiza existente
        } else {
            $prenda->tallas()->create($dataTalla);  // Crea nuevo
        }
    }
}
```

## 🚨 IMPACTO

Si solo cambias 1 campo (ej: color), actualmente pierdes:
- Todas las telas asociadas ❌
- Todas las fotos de telas ❌
- Todos los mangos/broche configurados ❌
- Todas las tallas (S, M, L, XL) ❌
- Todos los procesos de producción ❌

**Esto es crítico porque es una pérdida de datos no intencionada.**

## 📝 PRÓXIMOS PASOS

1. ✅ Este análisis (COMPLETADO)
2. ⏳ Refactorizar `ActualizarPrendaCompletaUseCase.php` con patrón selectivo
3. ⏳ Agregar tests para cambios parciales
4. ⏳ Verificar que cambiar solo color no elimine nada más
5. ⏳ Verificar que cambiar solo tela no elimine nada más
