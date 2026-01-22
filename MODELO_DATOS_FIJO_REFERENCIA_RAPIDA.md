# 🔒 COMPROMISO: MODELO DE DATOS FIJO - REFERENCIA RÁPIDA

## ⚠️ CONTEXTO CRÍTICO

El modelo de datos de PRENDAS DE PRODUCCIÓN es **INMUTABLE**. No se pueden inventar columnas, tablas o campos.

---

##  MATRIZ RÁPIDA - DÓNDE VA CADA DATO

| Tipo de Dato | Tabla Correcta | Tabla INCORRECTA | Verificación |
|---|---|---|---|
| Nombre, descripción, tallas | `prendas_pedido` |  NO en imágenes_path |  Existe campo |
| Imágenes de prenda | `prenda_fotos_pedido` |  NO en prendas_pedido |  Tabla separada |
| Imágenes de telas | `prenda_fotos_tela_pedido` |  NO en prendas_pedido |  Tabla separada |
| Variantes (manga, broche) | `prenda_pedido_variantes` |  NO en prendas_pedido |  Tabla separada |
| Telas y colores | `prenda_pedido_colores_telas` |  NO en prendas_pedido |  Tabla separada |
| Procesos (bordado, etc) | `pedidos_procesos_prenda_detalles` |  NO en prendas_pedido |  Tabla separada |
| Imágenes de procesos | `pedidos_procesos_imagenes` |  NO en prendas_pedido |  Tabla separada |

---

##  COLUMNAS QUE NO EXISTEN (NUNCA USAR)

```
 prendas_pedido.imagenes_path          → NO EXISTE
 prendas_pedido.imagenes               → NO EXISTE
 prendas_pedido.procesos               → NO EXISTE
 prendas_pedido.variantes              → NO EXISTE
 prendas_pedido.telas                  → NO EXISTE
 prendas_pedido.colores                → NO EXISTE
 prendas_pedido.foto                   → NO EXISTE
 prendas_pedido.ruta                   → NO EXISTE
 Cualquier otra columna NO listada     → NO EXISTE
```

**Si un campo no está explícitamente listado en las 7 tablas, NO EXISTE.**

---

##  CHECKLIST ANTES DE ESCRIBIR CÓDIGO

Antes de tocar CUALQUIER código que interactúe con prendas:

```
PASO 1: ¿Dónde va este dato?
   ├─ ¿Es nombre/descripción/talla? → prendas_pedido
   ├─ ¿Es imagen de prenda? → prenda_fotos_pedido
   ├─ ¿Es imagen de tela? → prenda_fotos_tela_pedido
   ├─ ¿Es variante? → prenda_pedido_variantes
   ├─ ¿Es tela o color? → prenda_pedido_colores_telas
   ├─ ¿Es proceso? → pedidos_procesos_prenda_detalles
   ├─ ¿Es imagen de proceso? → pedidos_procesos_imagenes
   └─ ¿Es otra cosa? → NO EXISTE, no incluir

PASO 2: ¿La columna existe?
   ├─ Abrivo la descripción de la tabla
   ├─ Verifico que la columna esté listada
   ├─ Si NO está → NO SE USA
   └─ Si tengo dudas → PREGUNTO antes de codificar

PASO 3: ¿Es soft delete?
   ├─ ¿La tabla tiene deleted_at? 
   ├─ SI → Agregar ->where('deleted_at', null)
   └─ NO → Proceder sin filtro

PASO 4: ¿Es JSON field?
   ├─ ¿Dice (JSON) en la descripción?
   ├─ SI → Parsear defensivamente (is_array vs json_decode)
   └─ NO → Usar como string/int/bool

PASO 5: ¿Es JOIN a catálogo?
   ├─ ¿Estoy JOINeando a tipos_manga, colores_prenda, etc?
   ├─ SI → Solo para LEER nombres, nunca para guardar
   └─ NO → Continuar

PASO 6: ¿Respeto las restricciones?
   ├─  NO invento columnas
   ├─  NO mezclo datos entre tablas
   ├─  NO guardo en lugar incorrecto
   ├─  Respeto soft deletes
   ├─  Parseo JSON correctamente
   └─  Si algo falla, vuelvo al paso 1
```

---

##  LAS 7 TABLAS - REFERENCIA RÁPIDA

### 1️⃣ prendas_pedido (RAÍZ)
```
 Usar para: nombre, descripción, tallas, género, bodega
 NO usar para: imágenes, procesos, variantes, telas
Soft delete: SÍ (deleted_at)
JSON fields: cantidad_talla, genero
```

### 2️⃣ prenda_pedido_variantes
```
 Usar para: manga, broche, bolsillos, observaciones
 NO usar para: imágenes, procesos
Soft delete: NO
Foreign keys: tipo_manga_id, tipo_broche_boton_id (a catálogos)
```

### 3️⃣ prenda_fotos_pedido (IMÁGENES PRENDA)
```
 Usar para: fotos del archivo de la prenda
 NO usar para: procesos, telas
Soft delete: SÍ (deleted_at)
Foreign keys: prenda_pedido_id
```

### 4️⃣ prenda_pedido_colores_telas (COMBINACIONES)
```
 Usar para: color_id + tela_id
 NO usar para: imágenes
Soft delete: NO
Foreign keys: color_id, tela_id (a catálogos)
```

### 5️⃣ prenda_fotos_tela_pedido (IMÁGENES TELAS)
```
 Usar para: fotos de cada combinación tela+color
 NO usar para: procesos
Soft delete: SÍ (deleted_at)
Foreign keys: prenda_pedido_colores_telas_id
```

### 6️⃣ pedidos_procesos_prenda_detalles (PROCESOS)
```
 Usar para: bordado, estampado, etc aplicados a prenda
 NO usar para: imágenes base
Soft delete: SÍ (deleted_at)
JSON fields: ubicaciones, tallas_dama, tallas_caballero, datos_adicionales
Foreign keys: tipo_proceso_id (a catálogo)
```

### 7️⃣ pedidos_procesos_imagenes (IMÁGENES PROCESOS)
```
 Usar para: fotos de cada proceso
 NO usar para: otra cosa
Soft delete: SÍ (deleted_at)
Foreign keys: proceso_prenda_detalle_id
```

---

## 🔍 PATRONES CORRECTOS E INCORRECTOS

### Patrón 1: Obtener una prenda

 **INCORRECTO:**
```php
$prenda = PrendaPedido::find($id);
echo $prenda->imagenes;        //  NO EXISTE
echo $prenda->procesos;        //  NO EXISTE
echo $prenda->variantes;       //  NO EXISTE
```

 **CORRECTO:**
```php
$prenda = PrendaPedido::find($id);
$imagenes = PrendaFotoPedido::where('prenda_pedido_id', $id)->get();
$procesos = PedidoProcesoPrendaDetalle::where('prenda_pedido_id', $id)->get();
$variantes = PrendaPedidoVariante::where('prenda_pedido_id', $id)->get();
```

---

### Patrón 2: Guardar imagen

 **INCORRECTO:**
```php
$prenda = PrendaPedido::find($id);
$prenda->imagenes_path = '/storage/...';  //  COLUMNA NO EXISTE
$prenda->save();
```

 **CORRECTO:**
```php
PrendaFotoPedido::create([
    'prenda_pedido_id' => $id,
    'ruta_webp' => '/storage/...',
    'ruta_original' => '/original/...',
    'orden' => 1,
]);
```

---

### Patrón 3: Guardar variante

 **INCORRECTO:**
```php
$prenda->variantes = json_encode([...]);  //  COLUMNA NO EXISTE
$prenda->save();
```

 **CORRECTO:**
```php
PrendaPedidoVariante::create([
    'prenda_pedido_id' => $id,
    'tipo_manga_id' => 5,
    'tipo_broche_boton_id' => 3,
    'manga_obs' => 'Manga reforzada',
    'tiene_bolsillos' => true,
    'bolsillos_obs' => 'Con cierre',
]);
```

---

### Patrón 4: Guardar proceso

 **INCORRECTO:**
```php
$prenda->procesos = [...]  //  COLUMNA NO EXISTE
$prenda->save();
```

 **CORRECTO:**
```php
$proceso = PedidoProcesoPrendaDetalle::create([
    'prenda_pedido_id' => $id,
    'tipo_proceso_id' => 5,
    'ubicaciones' => json_encode(['Pecho', 'Espalda']),
    'observaciones' => 'Bordado en hilo dorado',
    'estado' => 'PENDIENTE',
]);

// Luego guardar imágenes del proceso
PedidoProcesoimagen::create([
    'proceso_prenda_detalle_id' => $proceso->id,
    'ruta_webp' => '/storage/...',
    'ruta_original' => '/original/...',
    'orden' => 1,
    'es_principal' => true,
]);
```

---

### Patrón 5: Respetar soft deletes

 **INCORRECTO:**
```php
$imagenes = PrendaFotoPedido::where('prenda_pedido_id', $id)->get();  // Incluye eliminadas
```

 **CORRECTO:**
```php
$imagenes = PrendaFotoPedido::where('prenda_pedido_id', $id)
    ->where('deleted_at', null)  // Excluye eliminadas
    ->get();
```

---

### Patrón 6: Parsear JSON defensivamente

 **INCORRECTO:**
```php
$tallas = json_decode($prenda->cantidad_talla, true);  // Falla si ya es array
```

 **CORRECTO:**
```php
$tallas = [];
if ($prenda->cantidad_talla) {
    if (is_array($prenda->cantidad_talla)) {
        $tallas = $prenda->cantidad_talla;
    } else if (is_string($prenda->cantidad_talla)) {
        $tallas = json_decode($prenda->cantidad_talla, true) ?? [];
    }
}
```

---

##  DECISIÓN DE TABLA - ÁRBOL DE DECISIÓN

```
¿Qué debo guardar?
│
├─ ¿Es el nombre/descripción/talla/género/bodega de la prenda?
│  └─ SÍ → prendas_pedido
│
├─ ¿Es una imagen de la prenda (fotos del archivo)?
│  └─ SÍ → prenda_fotos_pedido
│
├─ ¿Es una variante (manga, broche, bolsillos)?
│  └─ SÍ → prenda_pedido_variantes
│
├─ ¿Es una relación color + tela de la prenda?
│  └─ SÍ → prenda_pedido_colores_telas
│
├─ ¿Es una imagen de una combinación tela+color?
│  └─ SÍ → prenda_fotos_tela_pedido
│
├─ ¿Es un proceso aplicado a la prenda?
│  └─ SÍ → pedidos_procesos_prenda_detalles
│
├─ ¿Es una imagen de un proceso?
│  └─ SÍ → pedidos_procesos_imagenes
│
└─ ¿Es otra cosa?
   └─ NO → No existe tabla, no guardar
```

---

##  VALIDACIÓN ANTES DE COMMIT

Cada vez que hagas cambios, verifica:

```bash
# 1. ¿El código menciona imagenes_path?
grep -r "imagenes_path" app/
# Resultado: NADA (si hay algo, ERROR)

# 2. ¿Se guardan imágenes en prendas_pedido?
grep -A5 "PrendaPedido.*update\|PrendaPedido.*create" app/ | grep "ruta"
# Resultado: NADA (las imágenes van en prenda_fotos_pedido)

# 3. ¿Se guardan procesos en prendas_pedido?
grep -A5 "PrendaPedido.*update\|PrendaPedido.*create" app/ | grep "proceso"
# Resultado: NADA (los procesos van en pedidos_procesos_prenda_detalles)

# 4. ¿Hay soft deletes donde corresponde?
grep -B5 "prenda_fotos_pedido\|pedidos_procesos" app/ | grep "deleted_at"
# Resultado: Debe haber múltiples coincidencias
```

---

##  PRINCIPIOS OBLIGATORIOS

1. **Separación de Responsabilidades**
   - Una tabla = Un propósito
   - No mezclar datos

2. **Integridad Referencial**
   - Si no tiene tabla, no existe
   - Si no está explícitamente listado, no se usa

3. **Respeto a Soft Deletes**
   - Siempre filtrar `deleted_at IS NULL`

4. **JSON Parsing Defensivo**
   - Nunca asumir tipo (array vs string)
   - Usar `is_array()` primero

5. **Catálogos Solo para Lectura**
   - JOIN a tipos_* solo para nombres
   - Nunca guardar IDs incorrectos

---

## 🚨 REGLA DE ORO

**Si está en duda, NO se inventa.**

Antes de escribir cualquier línea de código:
1. Abre la descripción de las 7 tablas
2. Verifica que la columna existe
3. Verifica que es la tabla correcta
4. Si no encuentras nada → CONSULTA

**NO asumir, NO inventar, NO improvisar.**

---

## 📞 REFERENCIAS RÁPIDAS

- [Validación Stricta](#validación-stricta-modelo-datos) → VALIDACION_STRICTA_MODELO_DATOS.md
- [Ejemplos Correctos](#guía-de-implementación-correcta) → GUIA_EJEMPLOS_IMPLEMENTACION_CORRECTA.md
- [Testing](#checklist-de-testing) → CHECKLIST_TESTING_SISTEMA_COMPLETO.md

---

##  ESTADO DEL CÓDIGO ACTUAL

El método `obtenerDatosUnaPrenda()` implementado:
-  USA SOLO las 7 tablas transaccionales
-  NO inventa columnas
-  Respeta soft deletes
-  Parsea JSON correctamente
-  Consulta catálogos solo para nombres

**Este es el PATRÓN correcto a seguir.**

---

**ÚLTIMA ACTUALIZACIÓN:** 22 de Enero de 2026
**ESTADO:**  MODELO CONFIRMADO Y VALIDADO
**PRÓXIMO CAMBIO:** Debe verificar esta guía primero

