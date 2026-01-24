# Ejemplos Prácticos: Actualización Selectiva de Prendas

## Introducción

Este documento contiene ejemplos prácticos de cómo usar el sistema de actualización selectiva de prendas. El principio es simple: **solo envía lo que quieres cambiar**.

---

## 📡 Ejemplo 1: Editar Solo Tallas

### Caso de Uso
Un asesor abre una prenda en la cartera y hace clic en "Editar Tallas". Solo quiere cambiar las cantidades de tallas, sin tocar variantes, procesos, etc.

### JSON Enviado

```json
{
  "prenda_id": 42,
  "nombre_prenda": null,
  "descripcion": null,
  "de_bodega": null,
  "cantidad_talla": {
    "NIÑOS": {
      "2": 5,
      "4": 3,
      "6": 2
    },
    "ADULTOS": {
      "XS": 10,
      "S": 8
    }
  },
  "variantes": null,
  "colores_telas": null,
  "fotos_telas": null,
  "fotos": null,
  "procesos": null
}
```

### Lo Que Sucede en el Backend

```php
// 1. ActualizarPrendaPedidoUseCase::ejecutar() es llamado

// 2. actualizarCamposBasicos() 
// → nombre_prenda es null → skip
// → descripcion es null → skip
// → de_bodega es null → skip

// 3. actualizarTallas()
// → cantidad_talla NO es null → continuar
// → cantidad_talla NO está vacío → continuar
// → DELETE registros viejos de tallas
// → INSERT registros nuevos
//  TABLA AFECTADA: prenda_pedido_tallas

// 4. actualizarVariantes()
// → variantes es null → return (SKIP)

// 5. actualizarColoresTelas()
// → colores_telas es null → return (SKIP)

// 6. actualizarProcesos()
// → procesos es null → return (SKIP)
```

### Resultado en Base de Datos

```sql
-- CAMBIOS
DELETE FROM prenda_pedido_tallas WHERE prenda_pedido_id = 42;
INSERT INTO prenda_pedido_tallas (prenda_pedido_id, genero, talla, cantidad) 
VALUES 
  (42, 'NIÑOS', '2', 5),
  (42, 'NIÑOS', '4', 3),
  (42, 'NIÑOS', '6', 2),
  (42, 'ADULTOS', 'XS', 10),
  (42, 'ADULTOS', 'S', 8);

-- SIN CAMBIOS
-- prenda_pedido_variantes → sin modificar
-- prenda_pedido_colores_telas → sin modificar
-- pedidos_procesos_prenda_detalles → sin modificar
```

### Respuesta HTTP

```json
{
  "id": 42,
  "nombre_prenda": "Polo Clasico",
  "tallas": [
    {"genero": "NIÑOS", "talla": "2", "cantidad": 5},
    {"genero": "NIÑOS", "talla": "4", "cantidad": 3},
    {"genero": "NIÑOS", "talla": "6", "cantidad": 2},
    {"genero": "ADULTOS", "talla": "XS", "cantidad": 10},
    {"genero": "ADULTOS", "talla": "S", "cantidad": 8}
  ],
  "variantes": [...],
  "colores_telas": [...],
  "procesos": [...]
}
```

---

## 📡 Ejemplo 2: Editar Variantes y Procesos

### Caso de Uso
Un asesor quiere cambiar los tipos de manga y procesos, pero mantener tallas, colores y fotos exactamente como están.

### JSON Enviado

```json
{
  "prenda_id": 42,
  "nombre_prenda": null,
  "descripcion": null,
  "de_bodega": null,
  "cantidad_talla": null,
  "variantes": [
    {
      "tipo_manga_id": 3,
      "tipo_broche_boton_id": 5,
      "manga_obs": "Manga larga 3x3",
      "broche_boton_obs": null,
      "tiene_bolsillos": true,
      "bolsillos_obs": "Bolsillos con costuras"
    }
  ],
  "colores_telas": null,
  "fotos_telas": null,
  "fotos": null,
  "procesos": [
    {
      "tipo_proceso_id": 1,
      "ubicaciones": ["frente", "espalda"],
      "observaciones": "Bordar logo a 10cm del cuello",
      "estado": "PENDIENTE"
    },
    {
      "tipo_proceso_id": 2,
      "ubicaciones": ["mangas"],
      "observaciones": "Etiqueta de cuidados",
      "estado": "PENDIENTE"
    }
  ]
}
```

### Lo Que Sucede en el Backend

```php
// 1. actualizarCamposBasicos() → todos null → SKIP

// 2. actualizarTallas()
// → cantidad_talla es null → return (SKIP)

// 3. actualizarVariantes()
// → variantes NO es null → continuar
// → variantes NO está vacío → continuar
// → DELETE registros viejos de variantes
// → INSERT 1 nuevo registro de variante
//  TABLA AFECTADA: prenda_pedido_variantes

// 4. actualizarColoresTelas()
// → colores_telas es null → return (SKIP)

// 5. actualizarProcesos()
// → procesos NO es null → continuar
// → procesos NO está vacío → continuar
// → DELETE registros viejos de procesos (y sus imágenes en cascada)
// → INSERT 2 nuevos registros de procesos
//  TABLA AFECTADA: pedidos_procesos_prenda_detalles
//  TABLA AFECTADA: pedidos_procesos_prenda_detalle_imagenes (en cascada)
```

### Resultado en Base de Datos

```sql
-- CAMBIOS
DELETE FROM prenda_pedido_variantes WHERE prenda_pedido_id = 42;
INSERT INTO prenda_pedido_variantes (prenda_pedido_id, tipo_manga_id, ...) 
VALUES (42, 3, 5, 'Manga larga 3x3', NULL, 1, 'Bolsillos con costuras');

DELETE FROM pedidos_procesos_prenda_detalles WHERE prenda_pedido_id = 42;
-- (Las imágenes se eliminan en cascada automáticamente)
INSERT INTO pedidos_procesos_prenda_detalles (prenda_pedido_id, tipo_proceso_id, ...) 
VALUES 
  (42, 1, '["frente", "espalda"]', 'Bordar logo a 10cm del cuello', 'PENDIENTE'),
  (42, 2, '["mangas"]', 'Etiqueta de cuidados', 'PENDIENTE');

-- SIN CAMBIOS
-- prenda_pedido_tallas → sin modificar (null = skip)
-- prenda_pedido_colores_telas → sin modificar (null = skip)
-- prenda_fotos_pedido → sin modificar (null = skip)
```

---

## 📡 Ejemplo 3: Limpiar Procesos

### Caso de Uso
Un asesor decide que esta prenda no necesita procesos especiales y quiere limpiar toda la información de procesos.

### JSON Enviado

```json
{
  "prenda_id": 42,
  "nombre_prenda": null,
  "descripcion": null,
  "de_bodega": null,
  "cantidad_talla": null,
  "variantes": null,
  "colores_telas": null,
  "fotos_telas": null,
  "fotos": null,
  "procesos": []
}
```

### Lo Que Sucede en el Backend

```php
// 1-4. Todos los checks null o variantes/colores → SKIP

// 5. actualizarProcesos()
// → procesos NO es null → continuar
// → procesos SÍ está vacío (empty([]) = true) → ejecutar:
// $prenda->procesos()->delete();
// return;
//  TABLA AFECTADA: pedidos_procesos_prenda_detalles (eliminada)
//  TABLA AFECTADA: pedidos_procesos_prenda_detalle_imagenes (eliminada en cascada)
```

### Resultado en Base de Datos

```sql
-- CAMBIOS
DELETE FROM pedidos_procesos_prenda_detalles WHERE prenda_pedido_id = 42;
-- (Las imágenes se eliminan automáticamente por cascada)

-- Resultado: Prenda 42 ahora NO tiene procesos

-- SIN CAMBIOS
-- Todas las otras tablas (tallas, variantes, colores, fotos) permanecen igual
```

---

## 📡 Ejemplo 4: Actualizar Todo (Poco Común)

### Caso de Uso
Un asesor carga una prenda completamente nueva con TODAS las propiedades.

### JSON Enviado

```json
{
  "prenda_id": 42,
  "nombre_prenda": "Polo Premium XL",
  "descripcion": "Polo de lujo con detalles especiales",
  "de_bodega": false,
  "cantidad_talla": {
    "ADULTOS": {
      "S": 5,
      "M": 10,
      "L": 8,
      "XL": 3
    }
  },
  "variantes": [
    {
      "tipo_manga_id": 2,
      "tipo_broche_boton_id": 1,
      "manga_obs": "Manga corta con ribete",
      "broche_boton_obs": "Botones de nácar",
      "tiene_bolsillos": false,
      "bolsillos_obs": null
    }
  ],
  "colores_telas": [
    {
      "color_id": 5,
      "tela_id": 12
    },
    {
      "color_id": 6,
      "tela_id": 12
    }
  ],
  "fotos_telas": [
    {
      "tela_id": 12,
      "path": "/storage/telas/tela-12.jpg"
    }
  ],
  "fotos": [
    {
      "path": "/storage/prendas/prenda-42-1.jpg"
    },
    {
      "path": "/storage/prendas/prenda-42-2.jpg"
    }
  ],
  "procesos": [
    {
      "tipo_proceso_id": 1,
      "ubicaciones": ["frente", "pecho"],
      "observaciones": "Bordado personalizado",
      "estado": "PENDIENTE"
    }
  ]
}
```

### Lo Que Sucede en el Backend

```php
// 1. actualizarCamposBasicos() 
// → nombre_prenda, descripcion, de_bodega tienen valores → UPDATE

// 2-6. Todas las relaciones tienen valores → UPDATE/DELETE/INSERT cada una

// Resultado: Prenda completamente actualizada
```

### Resultado en Base de Datos

```sql
-- CAMBIOS EN TABLA PRINCIPAL
UPDATE prendas_pedido 
SET nombre_prenda = 'Polo Premium XL',
    descripcion = 'Polo de lujo con detalles especiales',
    de_bodega = 0
WHERE id = 42;

-- CAMBIOS EN TALLAS
DELETE FROM prenda_pedido_tallas WHERE prenda_pedido_id = 42;
INSERT INTO prenda_pedido_tallas (...) VALUES (4 registros);

-- CAMBIOS EN VARIANTES
DELETE FROM prenda_pedido_variantes WHERE prenda_pedido_id = 42;
INSERT INTO prenda_pedido_variantes (...) VALUES (1 registro);

-- CAMBIOS EN COLORES/TELAS
DELETE FROM prenda_pedido_colores_telas WHERE prenda_pedido_id = 42;
INSERT INTO prenda_pedido_colores_telas (...) VALUES (2 registros);

-- CAMBIOS EN FOTOS DE TELAS
DELETE FROM prenda_fotos_telas_pedido WHERE prenda_pedido_id = 42;
INSERT INTO prenda_fotos_telas_pedido (...) VALUES (1 registro);

-- CAMBIOS EN FOTOS
DELETE FROM prenda_fotos_pedido WHERE prenda_pedido_id = 42;
INSERT INTO prenda_fotos_pedido (...) VALUES (2 registros);

-- CAMBIOS EN PROCESOS
DELETE FROM pedidos_procesos_prenda_detalles WHERE prenda_pedido_id = 42;
INSERT INTO pedidos_procesos_prenda_detalles (...) VALUES (1 registro);
```

---

## 📡 Ejemplo 5: Cambio Parcial - Solo Descripción

### Caso de Uso
Un asesor solo quiere corregir una descripción, sin tocar ningún dato de configuración.

### JSON Enviado

```json
{
  "prenda_id": 42,
  "nombre_prenda": null,
  "descripcion": "Descripción corregida y mejorada",
  "de_bodega": null,
  "cantidad_talla": null,
  "variantes": null,
  "colores_telas": null,
  "fotos_telas": null,
  "fotos": null,
  "procesos": null
}
```

### Lo Que Sucede en el Backend

```php
// 1. actualizarCamposBasicos()
// → nombre_prenda es null → skip
// → descripcion NO es null → UPDATE descripcion
// → de_bodega es null → skip
//  UPDATE tabla: prendas_pedido (solo 1 columna)

// 2-6. Todas las relaciones null → SKIP todas
```

### Resultado en Base de Datos

```sql
-- CAMBIO MÍNIMO
UPDATE prendas_pedido 
SET descripcion = 'Descripción corregida y mejorada'
WHERE id = 42;

-- SIN CAMBIOS EN NINGUNA OTRA TABLA
```

---

## 🎨 Tabla Comparativa de Comportamientos

| Campo | null | [] (vacío) | Con datos | Resultado |
|-------|------|-----------|-----------|-----------|
| cantidad_talla | ❌ Skip | 🗑️ Delete all | ✏️ Update | Solo se modifican tallas |
| variantes | ❌ Skip | 🗑️ Delete all | ✏️ Update | Solo se modifican variantes |
| colores_telas | ❌ Skip | 🗑️ Delete all | ✏️ Update | Solo se modifican colores |
| fotos_telas | ❌ Skip | 🗑️ Delete all | ✏️ Update | Solo se modifican fotos telas |
| fotos | ❌ Skip | 🗑️ Delete all | ✏️ Update | Solo se modifican fotos |
| procesos | ❌ Skip | 🗑️ Delete all | ✏️ Update | Solo se modifican procesos |

---

## 🔄 Flujo de Decisión en Código

```
¿Campo enviado?
├─ NO (null)
│  └─ ❌ SKIP (no hacer nada)
│
└─ SÍ (tiene valor)
   └─ ¿Está vacío?
      ├─ SÍ (empty())
      │  └─ 🗑️ DELETE ALL
      │
      └─ NO (tiene datos)
         └─ ✏️ UPDATE/DELETE/INSERT selectivamente
```

---

## 📊 Comparación de Rendimiento

### Antes (Antiguo)
```php
// Siempre DELETE ALL + INSERT ALL
$prenda->tallas()->delete();
foreach ($dto->tallas as $talla) {
    $prenda->tallas()->create($talla);
}
// Querydemia cuántas tablas se afecten
```

**Problema:** Elimina y recrea AUNQUE no se haya editado

### Después (Nuevo)
```php
// Solo si NO es null
if (is_null($dto->cantidadTalla)) {
    return; // 0 queries
}

// Solo si tiene datos
$prenda->tallas()->delete();
foreach ($dto->cantidadTalla as $genero => $tallas) {
    foreach ($tallas as $talla => $cantidad) {
        $prenda->tallas()->create([...]);
    }
}
```

**Ventaja:** 
- Si null → 0 queries
- Si datos → Only necessary queries
- Si empty → 1 delete query

---

## 🧪 Testing: Cómo Probar Localmente

### Setup
```bash
# 1. Instalar herramientas
composer install
npm install

# 2. Crear base de datos de test
php artisan migrate:fresh --seed

# 3. Obtener ID de una prenda existente
SELECT id FROM prendas_pedido LIMIT 1;
# → Asumir prenda_id = 1
```

### Test 1: Editar solo tallas
```bash
curl -X POST http://localhost:8000/asesores/pedidos/1/actualizar \
  -H "Content-Type: application/json" \
  -d '{
    "cantidad_talla": {"NIÑOS": {"2": 10}},
    "variantes": null,
    "colores_telas": null,
    "procesos": null
  }'

# Verificar respuesta: cantidad_talla debe tener nuevos datos
# Verificar DB: SELECT * FROM prenda_pedido_tallas WHERE prenda_pedido_id = 1;
# Verificar DB: SELECT * FROM prenda_pedido_variantes WHERE prenda_pedido_id = 1; (sin cambios)
```

### Test 2: Limpiar procesos
```bash
curl -X POST http://localhost:8000/asesores/pedidos/1/actualizar \
  -H "Content-Type: application/json" \
  -d '{
    "procesos": [],
    "cantidad_talla": null,
    "variantes": null,
    "colores_telas": null
  }'

# Verificar DB: SELECT * FROM pedidos_procesos_prenda_detalles WHERE prenda_pedido_id = 1;
# Debe estar vacío
```

---

##  Mejores Prácticas

###  Hacer

1. **Enviar solo lo que cambia**
   ```json
   { "cantidad_talla": {...}, "variantes": null, "procesos": null }
   ```

2. **Usar arrays vacíos para limpiar**
   ```json
   { "procesos": [] }
   ```

3. **Usar null para omitir**
   ```json
   { "procesos": null }
   ```

### ❌ Evitar

1. **No enviar campos que no cambiarán**
   ```json
   { "variantes": [], "colores_telas": [], "fotos": [] }
   // Si todas estas están vacías, estás borrando sin intención
   ```

2. **No repetir todos los datos**
   ```json
   { "cantidad_talla": [...], "variantes": [...], "colores_telas": [...] }
   // Si solo querías cambiar tallas, solo envía eso
   ```

---

## 📞 Soporte

¿Dudas sobre estos ejemplos?

- Revisar: `IMPLEMENTACION_ACTUALIZACION_SELECTIVA_PRENDAS.md`
- Revisar: `VALIDACION_ACTUALIZACION_SELECTIVA.md`
- Ver código: `ActualizarPrendaPedidoUseCase.php`

