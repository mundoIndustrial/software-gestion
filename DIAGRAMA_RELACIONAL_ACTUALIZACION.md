# 🗂️ DIAGRAMA RELACIONAL - ESTRUCTURA DE ACTUALIZACIÓN DE PRENDAS

## Relaciones Entre Tablas

```
┌─────────────────────────┐
│  pedidos_produccion     │
│  ├─ id (PK)             │
│  ├─ numero_pedido       │
│  ├─ cliente             │
│  └─ ...                 │
└────────────┬────────────┘
             │
             │ (1:N)
             ↓
┌─────────────────────────────────┐
│  prendas_pedido                 │ ◄── PRENDA A ACTUALIZAR
│  ├─ id (PK)                     │
│  ├─ pedido_produccion_id (FK)   │
│  ├─ nombre_prenda               │ ← actualizado
│  ├─ descripcion                 │ ← actualizado
│  ├─ de_bodega                   │ ← actualizado
│  └─ ...                         │
└────────┬────────────────────────┘
         │
         ├────────────────────────────────────────────────┐
         │                                                │
         ↓ (1:N) tallas()                                 ↓ (1:N) variantes()
    ┌─────────────────────────┐            ┌────────────────────────────┐
    │ prenda_pedido_tallas    │            │ prenda_pedido_variantes    │
    ├─ id (PK)               │            ├─ id (PK)                   │
    ├─ prenda_pedido_id (FK) │            ├─ prenda_pedido_id (FK)     │
    ├─ genero                │◄───────┐   ├─ tipo_manga_id (FK)        │
    ├─ talla                 │        │   ├─ tipo_broche_boton_id (FK)│
    └─ cantidad              │        │   ├─ manga_obs                │
                             │        │   ├─ broche_boton_obs         │
         ACTUALIZACIÓN       │        │   ├─ tiene_bolsillos          │
         Estructura:         │        │   └─ bolsillos_obs            │
         {                   │        │                                │
           "DAMA": {         │        └────────────────────────────────┘
             "L": 10,        │
             "M": 20         │        ACTUALIZACIÓN
           },                │        Estructura:
           "CABALLERO": {    │        [{
             "XL": 5         │          tipo_manga_id: 1,
           }                 │          tipo_broche_boton_id: 2,
         }                   │          tiene_bolsillos: true,
                             │          manga_obs: "...",
         ◄──────────────────┘        broche_boton_obs: "...",
                                      bolsillos_obs: "..."
                                    }]
         │
         ├────────────────────────────────────────────────┐
         │                                                │
         ↓ (1:N) coloresTelas()                          ↓ (1:N) procesos()
    ┌──────────────────────────────┐      ┌────────────────────────────────┐
    │ prenda_pedido_colores_telas  │      │ pedidos_procesos_prenda_detalles│
    ├─ id (PK)                     │      ├─ id (PK)                        │
    ├─ prenda_pedido_id (FK)       │      ├─ prenda_pedido_id (FK)          │
    ├─ color_id (FK) ──────┐       │      ├─ tipo_proceso_id (FK)          │
    ├─ tela_id (FK) ───────┼──┐    │      ├─ ubicaciones (JSON)            │
    └──────────────────────┼──┼────┘      ├─ observaciones                 │
         │                 │  │            ├─ estado                        │
         │        ┌────────┘  │            └─ ...                           │
         │        │           │                 │
         ↓        ↓           ↓                 ↓ (1:N) imagenes()
    ┌─────────┐ ┌──────────┐                ┌──────────────────────────┐
    │ colores │ │  telas   │                │ pedidos_procesos_imagenes│
    │ ────────│ │ ─────────│                ├─ id (PK)                 │
    │ id (PK) │ │ id (PK)  │                ├─ proceso_prenda_detalle  │
    │ nombre  │ │ nombre   │                ├─ ruta_original           │
    └─────────┘ └──────────┘                ├─ ruta_webp               │
                                            ├─ orden                   │
                                            └─ es_principal            │
         │
         ↓ (1:N) fotosTelas() [HasManyThrough]
    ┌──────────────────────────────┐
    │ prenda_fotos_tela_pedido     │
    ├─ id (PK)                     │
    ├─ prenda_pedido_colores       │
    │   _telas_id (FK)             │
    ├─ ruta_original               │
    ├─ ruta_webp                   │
    └─ orden                        │
                                    
         │
         ↓ (1:N) fotos()
    ┌──────────────────────────────┐
    │ prenda_fotos_pedido          │
    ├─ id (PK)                     │
    ├─ prenda_pedido_id (FK)       │
    ├─ ruta_original               │
    ├─ ruta_webp                   │
    └─ orden                        │
```

---

## 📊 Tabla de Actualizaciones

| Relación | Tabla | Operación | Estructura DTO | Método Use Case |
|----------|-------|-----------|----------------|-----------------|
| **Tallas** | `prenda_pedido_tallas` | DELETE + INSERT | `cantidadTalla: { GENERO: { TALLA: CANTIDAD } }` | `actualizarTallas()` |
| **Variantes** | `prenda_pedido_variantes` | DELETE + INSERT | `variantes: [{ tipo_manga_id, tipo_broche_boton_id, ... }]` | `actualizarVariantes()` |
| **Colores/Telas** | `prenda_pedido_colores_telas` | DELETE + INSERT | `coloresTelas: [{ color_id, tela_id }]` | `actualizarColoresTelas()` |
| **Fotos Telas** | `prenda_fotos_tela_pedido` | DELETE + INSERT | `fotosTelas: [{ color_tela_id, ruta }]` | `actualizarFotosTelas()` |
| **Fotos Prenda** | `prenda_fotos_pedido` | DELETE + INSERT | `imagenes: [ruta1, ruta2]` | `actualizarFotos()` |
| **Procesos** | `pedidos_procesos_prenda_detalles` | DELETE + INSERT | `procesos: [{ tipo_proceso_id, ubicaciones, obs }]` | `actualizarProcesos()` |
| **Imágenes Procesos** | `pedidos_procesos_imagenes` | DELETE + INSERT | Dentro de procesos | Dentro de `actualizarProcesos()` |

---

## 🔄 Flujo de Datos

### 1️⃣ FRONTEND ENVÍA:
```javascript
{
  "nombre_prenda": "RET",
  "descripcion": "Retazo",
  "cantidad_talla": "{\"DAMA\":{\"L\":10,\"M\":20}}",
  "variantes": "[{\"tipo_manga_id\":1}]",
  "colores_telas": "[{\"color_id\":1,\"tela_id\":2}]",
  "procesos": "[{\"tipo_proceso_id\":3}]"
}
```

### 2️⃣ DTO PARSEA Y CONVIERTE:
```php
ActualizarPrendaCompletaDTO {
  +cantidadTalla: array = [
    "DAMA" => ["L" => 10, "M" => 20]
  ]
  +variantes: array = [
    [ "tipo_manga_id" => 1 ]
  ]
  +coloresTelas: array = [
    [ "color_id" => 1, "tela_id" => 2 ]
  ]
  +procesos: array = [
    [ "tipo_proceso_id" => 3 ]
  ]
}
```

### 3️⃣ USE CASE ACTUALIZA BD:
```sql
-- Eliminar relaciones viejas
DELETE FROM prenda_pedido_tallas WHERE prenda_pedido_id = 3418;
DELETE FROM prenda_pedido_variantes WHERE prenda_pedido_id = 3418;
DELETE FROM prenda_pedido_colores_telas WHERE prenda_pedido_id = 3418;
DELETE FROM pedidos_procesos_prenda_detalles WHERE prenda_pedido_id = 3418;

-- Insertar nuevas relaciones
INSERT INTO prenda_pedido_tallas (prenda_pedido_id, genero, talla, cantidad) VALUES
  (3418, 'DAMA', 'L', 10),
  (3418, 'DAMA', 'M', 20);

INSERT INTO prenda_pedido_variantes (prenda_pedido_id, tipo_manga_id, ...) VALUES
  (3418, 1, ...);

-- ... etc para cada relación
```

### 4️⃣ BACKEND DEVUELVE TRANSFORMADO:
```json
{
  "nombre_prenda": "RET",
  "tallas": {
    "DAMA": { "L": 10, "M": 20 }
  },
  "variantes": [
    { "manga": "Corta", "broche": "Botón" }
  ],
  "colores_telas": [
    { "color": "Rojo", "tela": "Algodón" }
  ]
}
```

### 5️⃣ FRONTEND RENDERIZA:
```
Tallas: DAMA: L(10), M(20)
Variantes: Manga: Corta | Broche: Botón
Colores/Telas: Rojo / Algodón
```

---

## ⚡ Operaciones Realizadas

### **ActualizarPrendaCompletaUseCase::execute()**
```php
1. ✓ actualizarCamposBasicos()       // prendas_pedido
2. ✓ actualizarFotos()               // prenda_fotos_pedido
3. ✓ actualizarTallas()              // prenda_pedido_tallas
4. ✓ actualizarVariantes()           // prenda_pedido_variantes
5. ✓ actualizarColoresTelas()        // prenda_pedido_colores_telas
6. ✓ actualizarFotosTelas()          // prenda_fotos_tela_pedido
7. ✓ actualizarProcesos()            // pedidos_procesos_prenda_detalles + imagenes
8. ✓ prenda->refresh()               // Recargar modelo
```

---

## 🧪 Verificación de Cambios

Después de actualizar, verifique en BD:
```sql
-- Ver tallas actuales
SELECT * FROM prenda_pedido_tallas WHERE prenda_pedido_id = 3418;

-- Ver variantes actuales
SELECT * FROM prenda_pedido_variantes WHERE prenda_pedido_id = 3418;

-- Ver colores/telas actuales
SELECT * FROM prenda_pedido_colores_telas WHERE prenda_pedido_id = 3418;

-- Ver procesos actuales
SELECT * FROM pedidos_procesos_prenda_detalles WHERE prenda_pedido_id = 3418;
```

---

## 📝 Resumen de Métodos Privados en Use Case

| Método | Responsabilidad | Tabla |
|--------|-----------------|-------|
| `actualizarCamposBasicos()` | UPDATE nombre, descripción, origen | `prendas_pedido` |
| `actualizarFotos()` | DELETE + INSERT fotos de referencia | `prenda_fotos_pedido` |
| `actualizarTallas()` | DELETE + INSERT tallas formateadas | `prenda_pedido_tallas` |
| `actualizarVariantes()` | DELETE + INSERT variantes | `prenda_pedido_variantes` |
| `actualizarColoresTelas()` | DELETE + INSERT colores/telas | `prenda_pedido_colores_telas` |
| `actualizarFotosTelas()` | DELETE + INSERT fotos de telas | `prenda_fotos_tela_pedido` |
| `actualizarProcesos()` | DELETE + INSERT procesos + imágenes | `pedidos_procesos_prenda_detalles`, `pedidos_procesos_imagenes` |

---

**Última Actualización:** 2026-01-23
**Estado:**  IMPLEMENTADO Y VERIFICADO
