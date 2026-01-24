# 🧪 GUÍA DE TESTING: Actualización Selectiva

##  Checklist de Pruebas

###  Test 1: Cambiar solo COLOR (la prueba más importante)

**Configuración Inicial:**
```
Prenda: ID=123, nombre="Camiseta Roja"
- Color: Rojo (id=1)
- Tela: Algodón (id=2)
- Tallas: S, M, L, XL (4 registros)
- Variantes: manga_id=5, broche_id=2
- Fotos de tela: foto1.webp, foto2.webp (2 registros)
- Fotos de prenda: referencia1.webp (1 registro)
```

**Acción del Usuario:**
1. Abre prenda en modal de edición
2. En selector de color, cambia de "Rojo" a "Azul"
3. NO cambia nada más
4. Guarda (botón guardar)

**Verificación en BD (después de guardar):**
```sql
-- 1. Combinación color-tela
SELECT * FROM prenda_pedido_colores_telas WHERE prenda_pedido_id = 123;
RESULTADO ESPERADO:
- Rojo-Algodón (1-2): ❌ DEBE ELIMINARSE
- Azul-Algodón (X-2):  DEBE CREARSE
- Total: 1 registro

-- 2. Telas (DEBEN PRESERVARSE)
SELECT * FROM telas_prendas WHERE prenda_pedido_id = 123;
RESULTADO ESPERADO:
- Algodón (id=2):  DEBE EXISTIR
- Total: 1 registro (MISMO QUE ANTES)

-- 3. Tallas (DEBEN PRESERVARSE)
SELECT * FROM prenda_pedido_tallas WHERE prenda_pedido_id = 123;
RESULTADO ESPERADO:
- Dama-S:  PRESERVADA
- Dama-M:  PRESERVADA
- Dama-L:  PRESERVADA
- Dama-XL:  PRESERVADA
- Total: 4 registros (IGUAL QUE ANTES)

-- 4. Variantes (DEBEN PRESERVARSE)
SELECT * FROM prenda_pedido_variantes WHERE prenda_pedido_id = 123;
RESULTADO ESPERADO:
- tipo_manga_id: 5  PRESERVADO
- tipo_broche_boton_id: 2  PRESERVADO
- Total: 1 registro (IGUAL QUE ANTES)

-- 5. Fotos de tela (DEBEN PRESERVARSE)
SELECT * FROM prenda_fotos_tela_pedido 
WHERE prenda_pedido_colores_telas_id IN (
    SELECT id FROM prenda_pedido_colores_telas 
    WHERE prenda_pedido_id = 123
);
RESULTADO ESPERADO:
- Las fotos de la NUEVA combinación Azul-Algodón deben preservarse
```

**Logs a revisar (en laravel.log):**
```
[ActualizarPrendaCompletaUseCase] Iniciando actualización
  - prenda_id: 123
  - tiene_colores_telas: true
  - tiene_variantes: null     (no viene en actualización)
  - tiene_fotos: null         (no viene)
  - tiene_tallas: null        (no viene)
  - tiene_fotos_telas: null   (no viene)
```

** TEST PASA SI:**
-  Combinación antigua (Rojo-Algodón) se elimina
-  Combinación nueva (Azul-Algodón) se crea
-  Talla S, M, L, XL SIGUEN EXISTIENDO (4 de 4)
-  Variantes (manga, broche) SIGUEN SIENDO IGUALES
-  Las fotos de tela existentes se preservan O se actualiza solo la combinación

---

###  Test 2: Cambiar solo TELA

**Configuración Inicial:**
```
Prenda: ID=124
- Color: Rojo (id=1)
- Tela: Algodón (id=2)
- Tallas: S, M, L
```

**Acción:** Cambiar tela de Algodón a Poliéster

**Verificación en BD:**
```sql
-- Color DEBE preservarse
SELECT * FROM colores_prendas WHERE id=1;
RESULTADO: Rojo  DEBE EXISTIR

-- Combinación debe actualizarse
SELECT * FROM prenda_pedido_colores_telas WHERE prenda_pedido_id=124;
RESULTADO:
- Rojo-Algodón: ❌ ELIMINARSE
- Rojo-Poliéster:  CREARSE

-- Tallas DEBEN preservarse
SELECT COUNT(*) FROM prenda_pedido_tallas WHERE prenda_pedido_id=124;
RESULTADO: 3 
```

---

###  Test 3: Agregar UNA FOTO

**Configuración Inicial:**
```
Prenda: ID=125
- Fotos: foto1.webp, foto2.webp (2 registros)
```

**Acción:** Agregar foto3.webp

**Verificación en BD:**
```sql
SELECT * FROM prenda_fotos_pedido WHERE prenda_pedido_id = 125;
RESULTADO:
- foto1.webp:  PRESERVADA
- foto2.webp:  PRESERVADA
- foto3.webp:  NUEVA
- Total: 3 registros (NO 1)
```

**❌ TEST FALLA SI:**
- Si el total es 1 (significa eliminó las viejas)

---

###  Test 4: Remover UNA FOTO (enviar array sin esa foto)

**Configuración Inicial:**
```
Fotos: foto1.webp, foto2.webp, foto3.webp (3 registros)
```

**Acción:** Eliminar foto2.webp (solo envía foto1 y foto3 en array)

**Verificación en BD:**
```sql
RESULTADO ESPERADO:
- foto1.webp:  PRESERVADA
- foto2.webp: ❌ ELIMINADA
- foto3.webp:  PRESERVADA
- Total: 2 registros
```

---

###  Test 5: NO cambiar NADA (guardar sin modificaciones)

**Acción:** Abrir prenda, no cambiar nada, guardar

**Verificación en BD:**
```sql
-- Todos los datos deben ser EXACTAMENTE IGUALES
SELECT * FROM prendas_pedido WHERE id=123;        -- IGUAL
SELECT * FROM prenda_pedido_tallas WHERE prenda_pedido_id=123;       -- IGUAL (count)
SELECT * FROM prenda_pedido_colores_telas WHERE prenda_pedido_id=123; -- IGUAL (count)
SELECT * FROM prenda_pedido_variantes WHERE prenda_pedido_id=123;     -- IGUAL (values)
```

** TEST PASA SI:** Count de cada tabla es EXACTAMENTE igual

---

###  Test 6: Cambiar MÚLTIPLES cosas (validar interacción)

**Acción:**
1. Cambiar color de Rojo a Verde
2. Cambiar tela de Algodón a Poliéster
3. Agregar talla XXL
4. Cambiar manga de 5 a 7
5. Guardar

**Verificación:**
```sql
-- Solo color-tela debe cambiar completamente
SELECT * FROM prenda_pedido_colores_telas;
RESULTADO:
- Rojo-Algodón: ❌ ELIMINARSE
- Verde-Poliéster:  CREARSE

-- Tallas: la nueva (XXL) se agrega
SELECT * FROM prenda_pedido_tallas WHERE talla='XXL';
RESULTADO:  DEBE EXISTIR

-- Variantes: manga debe actualizarse
SELECT tipo_manga_id FROM prenda_pedido_variantes;
RESULTADO: 7 
```

---

## 🐛 Debugging si Test FALLA

### Síntoma: "Se eliminan tallas cuando cambio color"

**Causa Probable:** `actualizarTallas()` no está chequeando correctamente `is_null()`

**Check en código:**
```php
private function actualizarTallas(...) {
    if (is_null($dto->cantidadTalla)) {
        return;  //  DEBE ESTAR
    }
    // ...
}
```

**Solución:** Verifica que TODOS los métodos tengan el check `is_null()` al inicio.

---

### Síntoma: "Se duplican fotos cuando agrego una"

**Causa Probable:** La lógica de `keyBy()` no está funcionando correctamente

**Check en código:**
```php
private function actualizarFotos(...) {
    $fotosExistentes = $prenda->fotos()->get()->keyBy('ruta_original');
    //  El keyBy DEBE usar la misma columna que se compara
    
    foreach ($fotosNuevas as $ruta => $datos) {
        if (!isset($fotosExistentes[$ruta])) {
            $prenda->fotos()->create($datos);  //  Solo si NO existe
        }
    }
}
```

---

### Síntoma: "No se elimina color-tela cuando debería"

**Causa Probable:** El `keyBy` en `actualizarColoresTelas()` no coincide con la lógica de new

**Check:**
```php
// DEBE COINCIDIR:
$coloresTelaExistentes = $prenda->coloresTelas()->get()->keyBy(function($ct) {
    return "{$ct->color_id}_{$ct->tela_id}";  // Color_Tela
});

foreach ($dto->coloresTelas as ...) {
    $coloresTelaNovas[$key] = [
        'color_id' => $colorId,
        'tela_id' => $telaId,
    ];
}

//  $key DEBE coincidir con el formato del keyBy
$key = "{$colorId}_{$telaId}";  // MISMO FORMATO
```

---

## 📝 Ejecución

1. Ejecuta Test 1 primero (cambiar color)
2. Verifica BD con comandos SQL
3. Revisa laravel.log para logs
4. Si Test 1 pasa, ejecuta Tests 2-6
5. Si alguno falla, revisa Debugging

---

## 📊 Resultados Esperados

| Test | Cambio | Debe Preservarse | Debe Cambiar |
|------|--------|------------------|--------------|
| 1 | Color | Tela, Tallas, Variantes, Fotos | Color-Tela |
| 2 | Tela | Color, Tallas, Variantes | Tela, Color-Tela |
| 3 | +Foto | Fotos viejas | +Foto nueva |
| 4 | -Foto | Fotos restantes | -Foto eliminada |
| 5 | Nada | TODO | NADA |
| 6 | Múltiple | Solo lo no tocado | Lo cambiado |
