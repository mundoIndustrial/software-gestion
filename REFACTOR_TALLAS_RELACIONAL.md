# 🔄 REFACTORIZACIÓN: cantidad_talla → prenda_pedido_tallas

**Fecha:** 22 de Enero de 2026  
**Status:**  ESTRUCTURA LISTA  
**Impacto:** Elimina JSON de tallas, usa tabla relacional

---

##  PROBLEMA ACTUAL

```php
// Hoy: JSON en prendas_pedido
$prenda->cantidad_talla = '{"DAMA":{"M":10,"L":20},"CABALLERO":{"32":15}}';
```

**Problemas:**
- Difícil de queryar directamente en BD
- Debe parsearse en PHP (error prone)
- No hay índices en tallas
- Duplicación en JSON: cada prenda con las mismas tallas duplica datos
- Imposible hacer queries SQL como "mostrar prendas con talla M"

---

##  SOLUCIÓN: Nueva Tabla Relacional

### Tabla: `prenda_pedido_tallas`
```sql
CREATE TABLE prenda_pedido_tallas (
    id BIGINT PRIMARY KEY,
    prenda_pedido_id BIGINT NOT NULL,  -- FK a prendas_pedido
    genero ENUM('DAMA','CABALLERO','UNISEX'),
    talla VARCHAR(50),  -- XS, S, M, L, XL, 28, 30, 32, etc
    cantidad INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (prenda_pedido_id) REFERENCES prendas_pedido(id) ON DELETE CASCADE,
    UNIQUE KEY (prenda_pedido_id, genero, talla),
    INDEX (prenda_pedido_id)
);
```

### Archivos Nuevos Creados:
-  `database/migrations/2026_01_22_000000_create_prenda_pedido_tallas_table.php`
-  `app/Models/PrendaPedidoTalla.php`
-  `app/Domain/PedidoProduccion/Traits/GestionaTallasRelacional.php`

### Cambios a Modelos:
-  `app/Models/PrendaPedido.php` - Agregada relación `tallas()`
-  `app/Domain/PedidoProduccion/Repositories/PedidoProduccionRepository.php` - Usa trait + carga relación

---

## 🔧 REFACTORIZACIÓN DE MÉTODOS

### ANTES: Parseo JSON en `PedidosProduccionController::actualizarPrendaCompleta()`

```php
//  INCORRECTO
$prenda->cantidad_talla = $validated['cantidad_talla'] 
    ? json_decode($validated['cantidad_talla'], true) 
    : [];
$prenda->save();
```

### DESPUÉS: INSERT en tabla relacional

```php
//  CORRECTO
$this->prendaPedidoRepository->guardarTallas(
    $prendaId,
    json_decode($validated['cantidad_talla'], true)
);
```

---

## 📚 MÉTODOS DEL TRAIT `GestionaTallasRelacional`

### 1. Guardar tallas (desde array)
```php
$this->guardarTallas($prendaId, [
    'DAMA' => ['XS' => 5, 'S' => 10, 'M' => 15],
    'CABALLERO' => ['28' => 20, '30' => 25]
]);
```

### 2. Guardar tallas (desde JSON)
```php
$this->guardarTallasDesdeJson($prendaId, $validated['cantidad_talla']);
```

### 3. Obtener tallas (como array)
```php
$tallas = $this->obtenerTallas($prendaId);
// Retorna: ['DAMA' => ['M' => 10, 'L' => 20], ...]
```

### 4. Obtener tallas (como JSON)
```php
$json = $this->obtenerTallasJson($prendaId);
// Retorna: '{"DAMA":{"M":10,"L":20},...}'
```

### 5. Actualizar una talla específica
```php
$this->actualizarTalla($prendaId, 'DAMA', 'M', 10);
```

### 6. Obtener cantidad total
```php
$total = $this->obtenerCantidadTotal($prendaId);
// Retorna: 50 (suma de todas las tallas)
```

### 7. Obtener tallas por género
```php
$tallasDAMA = $this->obtenerTallasGenero($prendaId, 'DAMA');
// Retorna: ['M' => 10, 'L' => 20, ...]
```

---

## 🚀 PASOS DE REFACTORIZACIÓN

### 1. Migration & Modelos ( HECHO)
- [x] Crear tabla `prenda_pedido_tallas`
- [x] Crear modelo `PrendaPedidoTalla`
- [x] Agregar relación en `PrendaPedido`
- [x] Crear trait `GestionaTallasRelacional`
- [x] Usar trait en `PedidoProduccionRepository`

### 2. Controladores (⏳ PRÓXIMO)
Refactorizar:
- `agregarPrendaCompleta()` - Use `guardarTallas()`
- `actualizarPrendaCompleta()` - Use `guardarTallas()`
- `obtenerDatosUnaPrenda()` - Use `obtenerTallas()`

### 3. Respuestas API (⏳ PRÓXIMO)
- Construir respuestas desde `prenda_pedido_tallas`
- NO incluir `cantidad_talla` JSON
- Incluir array estructurado de tallas

### 4. Views/Frontend (⏳ PRÓXIMO)
- Actualizar Blade templates para consumir nuevo formato
- Actualizar JavaScript para trabajar con tallas relacionales

### 5. Migración de Datos (⏳ PRÓXIMO)
- Crear seeder para migrar JSON existente
- Script para un solo pedido si es necesario

---

##  ESTRUCTURA DE DATOS

### JSON Actual ( Viejo)
```json
{
  "prendas_pedido": {
    "cantidad_talla": "{\"DAMA\":{\"M\":10,\"L\":20},\"CABALLERO\":{\"32\":15}}"
  }
}
```

### Estructura Nueva ( Nuevo)
```
prenda_pedido_tallas
├─ id: 1, prenda_pedido_id: 100, genero: DAMA, talla: M, cantidad: 10
├─ id: 2, prenda_pedido_id: 100, genero: DAMA, talla: L, cantidad: 20
└─ id: 3, prenda_pedido_id: 100, genero: CABALLERO, talla: 32, cantidad: 15
```

---

##  VENTAJAS

| Aspecto | JSON | Relacional |
|--------|------|-----------|
| Queries SQL |  Complejo |  Simple |
| Índices |  No |  Sí |
| Consistencia |  Débil |  Fuerte |
| Normalización |  Desnormalizado |  Normalizado |
| Performance | ⚠️ Lento (parse) |  Rápido |
| Escalabilidad |  Limitada |  Ilimitada |
| Duplicación |  Sí |  No |

---

## 🔄 COMPATIBILIDAD

### Fase de Transición
```php
// Métodos helper para compatibilidad
$json = $this->obtenerTallasJson($prendaId);  // Retorna JSON si API la necesita
$array = $this->obtenerTallas($prendaId);     // Retorna array normalizado
```

### Después de Migración
- Eliminar `cantidad_talla` de `prendas_pedido`
- Cambiar API a responder solo con tallas relacionales
- Actualizar todas las views/js

---

##  VALIDACIÓN

### SQL
```bash
# Verificar tabla existe
SHOW TABLES LIKE 'prenda_pedido_tallas';

# Verificar estructura
DESC prenda_pedido_tallas;

# Verificar datos después de migración
SELECT COUNT(*) FROM prenda_pedido_tallas;
```

### PHP Sintaxis
```bash
php -l app/Models/PrendaPedidoTalla.php
php -l app/Domain/PedidoProduccion/Traits/GestionaTallasRelacional.php
php -l app/Models/PrendaPedido.php
php -l app/Domain/PedidoProduccion/Repositories/PedidoProduccionRepository.php
```

---

## 📝 PRÓXIMOS DOCUMENTOS

1. **REFACTOR_CONTROLADORES_TALLAS.md** - Refactorizar save/update/read
2. **MIGRACION_DATOS_CANTIDAD_TALLA.md** - Script para migrar datos
3. **API_CONTRACTS_TALLAS.md** - Nuevas respuestas JSON
4. **VALIDACION_REFACTOR_COMPLETO.md** - Testing y validación

---

## 🔐 GARANTÍAS

```
 NUNCA leeremos cantidad_talla JSON
 SIEMPRE guardaremos en prenda_pedido_tallas
 NUNCA inventaremos columnas
 SIEMPRE usaremos las 7 tablas correctas
```

**Status:**  ESTRUCTURA LISTA PARA REFACTORIZACIÓN

