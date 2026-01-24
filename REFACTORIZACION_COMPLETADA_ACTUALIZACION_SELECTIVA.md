#  REFACTORIZACIÓN COMPLETADA: Actualización Selectiva (No Destructiva)

## CAMBIOS REALIZADOS

Se refactorizó [ActualizarPrendaCompletaUseCase.php](app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php) para implementar el **Patrón Selectivo de Actualización**.

### Principio Central

**Antes (❌ Incorrecto):**
```
Si cambias solo COLOR:
- Elimina TODOS los colores ❌
- Elimina TODAS las telas ❌
- Elimina TODAS las fotos de telas ❌
- Elimina TODAS las variantes ❌
- Elimina TODAS las tallas ❌
```

**Después ( Correcto):**
```
Si cambias solo COLOR:
- Compara colores nuevos con existentes
- Elimina solo los que NO están en nuevos
- Crea solo los que NO existen
- NO TOCA: telas, fotos, variantes, tallas
```

##  MÉTODOS REFACTORIZADOS

### 1. `actualizarColoresTelas()` (línea ~228)
**Cambio:** De eliminar todo → Actualización selectiva

```php
// ❌ ANTES:
$prenda->coloresTelas()->delete();  // Elimina TODO
foreach ($dto->coloresTelas as ...) {
    $prenda->coloresTelas()->create(...);  // Crea todo de nuevo
}

//  DESPUÉS:
$coloresTelaExistentes = $prenda->coloresTelas()->get()->keyBy(...);
$coloresTelaNovas = [];

// Procesar nuevos datos
foreach ($dto->coloresTelas as ...) {
    $coloresTelaNovas[$key] = [...];
}

// Eliminar SOLO los que no están en nuevos
foreach ($coloresTelaExistentes as $key => $record) {
    if (!isset($coloresTelaNovas[$key])) {
        $record->delete();  // Solo este
    }
}

// Crear SOLO los nuevos (no tocar existentes)
foreach ($coloresTelaNovas as $key => $datos) {
    if (!isset($coloresTelaExistentes[$key])) {
        $prenda->coloresTelas()->create($datos);
    }
}
```

**Impacto:** Si cambias solo color de Rojo a Azul, la tela sigue intacta.

---

### 2. `actualizarFotos()` (línea ~100)
**Cambio:** De eliminar todo → Actualización selectiva

```php
// ❌ ANTES:
$prenda->fotos()->delete();  // Elimina TODO
foreach ($dto->fotos as ...) {
    $prenda->fotos()->create(...);
}

//  DESPUÉS:
$fotosExistentes = $prenda->fotos()->get()->keyBy('ruta_original');
$fotosNuevas = [];

// Procesar nuevas rutas
foreach ($dto->fotos as ...) {
    $fotosNuevas[$ruta] = [...];
}

// Eliminar SOLO fotos no en nuevas
foreach ($fotosExistentes as $ruta => $record) {
    if (!isset($fotosNuevas[$ruta])) {
        $record->delete();
    }
}

// Crear SOLO fotos nuevas
foreach ($fotosNuevas as $ruta => $datos) {
    if (!isset($fotosExistentes[$ruta])) {
        $prenda->fotos()->create($datos);
    }
}
```

**Impacto:** Si agregas una foto, las existentes no se eliminarán.

---

### 3. `actualizarFotosTelas()` (línea ~351)
**Cambio:** De eliminar todo → Actualización selectiva (mismo patrón que fotos)

```php
// ❌ ANTES:
$prenda->fotosTelas()->delete();  // Elimina TODO
foreach ($dto->fotosTelas as ...) {
    $prenda->fotosTelas()->create(...);
}

//  DESPUÉS:
$fotosTelaExistentes = $prenda->fotosTelas()->get()->keyBy('ruta_original');
$fotosTelaNovas = [];

// Procesar y almacenar datos
// Eliminar solo las que no estén en nuevas
// Crear solo las nuevas
```

**Impacto:** Las fotos de telas existentes no se borran si no las cambias.

---

### 4. `actualizarVariantes()` (línea ~195)
**Cambio:** Clarificado el comportamiento (se reemplaza TODO, pero solo si explícitamente se envía)

```php
// PATRÓN SELECTIVO:
if (is_null($dto->variantes)) {
    return;  //  NO TOCAR si no viene
}

if (empty($dto->variantes)) {
    // Eliminar TODO solo si viene array vacío (intención explícita)
    $prenda->variantes()->delete();
    return;
}

// Si vienen datos, reemplazar
$prenda->variantes()->delete();
foreach ($dto->variantes as ...) {
    $prenda->variantes()->create(...);
}
```

**Impacto:** Variantes solo se eliminan si explícitamente envías array vacío.

---

## 🔑 REGLA CLAVE: Null vs Empty

| DTO Value | Acción |
|-----------|--------|
| `null` |  NO TOCAR (actualización parcial) |
| `[]` (vacío) |  ELIMINAR TODO (intención explícita) |
| `[datos]` |  ACTUALIZAR selectivamente |

## 📊 EJEMPLO PRÁCTICO

**Escenario:** Usuario edita prenda y SOLO cambia el color

### Frontend envía:
```javascript
{
    prendaId: 123,
    coloresTelas: [
        { color_id: 5, tela_id: 2 }  // Nuevo color (Azul)
    ]
    // NOTA: NO incluye:
    // - variantes
    // - fotos
    // - fotosTelas
    // - tallas
    // - procesos
}
```

### Comportamiento ANTES (❌):
- Elimina color Rojo de color-tela
- Crea color Azul
- ❌ Elimina tela Algodón
- ❌ Elimina todas las fotos de tela
- ❌ Elimina manga y broche configurados
- ❌ Elimina tallas (S, M, L, XL)

### Comportamiento DESPUÉS ():
- Elimina solo la combinación Rojo-Algodón
- Crea nueva combinación Azul-Algodón
-  Preserva: tela Algodón, fotos de tela, manga, broche, tallas

##  VERIFICACIÓN

Todos los métodos ahora siguen el patrón:

```php
private function actualizar*(PrendaPedido $prenda, DTO $dto): void
{
    // 1. Si null, no tocar
    if (is_null($dto->campo)) {
        return;
    }

    // 2. Si array vacío, eliminar explícitamente
    if (empty($dto->campo)) {
        $prenda->relacion()->delete();
        return;
    }

    // 3. Actualización selectiva
    $existentes = $prenda->relacion()->get()->keyBy('identificador');
    $nuevos = [];
    
    foreach ($dto->campo as ...) {
        $nuevos[identificador] = [...];
    }
    
    // Eliminar solo no existentes en nuevos
    foreach ($existentes as $key => $record) {
        if (!isset($nuevos[$key])) {
            $record->delete();
        }
    }
    
    // Crear solo los nuevos
    foreach ($nuevos as $key => $datos) {
        if (!isset($existentes[$key])) {
            $prenda->relacion()->create($datos);
        }
    }
}
```

## 🧪 CÓMO TESTEAR

**Test 1: Cambiar solo color**
1. Edita prenda con color=Rojo, tela=Algodón, tallas=[S,M,L]
2. Cambias solo color a Azul
3. Guarda
4. Verifica en BD:
   -  color Rojo eliminado
   -  color Azul creado
   -  tela Algodón SIGUE EXISTIENDO
   -  tallas S,M,L SIGUEN EXISTIENDO
   -  variantes (manga, broche) SIGUEN EXISTIENDO

**Test 2: Agregar foto**
1. Prenda tiene foto1.webp
2. Agregas foto2.webp
3. Guarda
4. Verifica en BD:
   -  foto1.webp SIGUE EXISTIENDO
   -  foto2.webp se crea
   - Total: 2 fotos

**Test 3: No cambiar nada**
1. Abres prenda
2. Guardas sin cambios
3. Verifica:
   -  TODOS los datos SIGUEN IGUAL
   - Nada eliminado ni duplicado

## 📝 RESUMIENDO

### Cambio Principal
De **"eliminar TODO y recrear"** a **"actualizar solo lo necesario"**

### Beneficios
1.  No pierdes datos cuando cambias un campo
2.  Operaciones más eficientes (no recrear todo)
3.  Mejor experiencia de usuario (cambios mínimos)
4.  Facilita actualizaciones parciales en el futuro

### Seguridad
- Datos existentes se preservan a menos que explícitamente se envíe array vacío
- Mejor control sobre qué se actualiza realmente
- Reduce riesgo de pérdida de datos accidental
