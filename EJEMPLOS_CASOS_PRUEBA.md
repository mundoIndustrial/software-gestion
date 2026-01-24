# 🧪 EJEMPLOS Y CASOS DE PRUEBA - ACTUALIZACIÓN DE PRENDAS

## 📌 Ejemplo 1: Actualización Completa (Todas las Relaciones)

### Request (Frontend):
```javascript
const formData = new FormData();

// Datos básicos
formData.append('nombre_prenda', 'RET MEJORADO');
formData.append('descripcion', 'Retazo de tela de alta calidad');

// Tallas (formato requerido: { GENERO: { TALLA: CANTIDAD } })
formData.append('cantidad_talla', JSON.stringify({
  "DAMA": {
    "L": 10,
    "M": 15,
    "S": 5
  },
  "CABALLERO": {
    "XL": 3,
    "L": 7
  }
}));

// Variantes (manga, broche, bolsillos)
formData.append('variantes', JSON.stringify([
  {
    "tipo_manga_id": 1,
    "tipo_broche_boton_id": 2,
    "manga_obs": "Manga corta preferible",
    "broche_boton_obs": "Botón de 20mm",
    "tiene_bolsillos": true,
    "bolsillos_obs": "2 bolsillos laterales"
  }
]));

// Colores y Telas
formData.append('colores_telas', JSON.stringify([
  {
    "color_id": 5,
    "tela_id": 3
  },
  {
    "color_id": 8,
    "tela_id": 4
  }
]));

// Procesos
formData.append('procesos', JSON.stringify([
  {
    "tipo_proceso_id": 1,
    "ubicaciones": ["FRENTE", "ESPALDA"],
    "observaciones": "Bordado con hilo de seda",
    "estado": "PENDIENTE"
  },
  {
    "tipo_proceso_id": 3,
    "ubicaciones": ["FRENTE"],
    "observaciones": "Estampado directo",
    "estado": "PENDIENTE"
  }
]));

formData.append('novedad', 'Actualización completa de prenda con múltiples tallas y procesos');

// Enviar
fetch('/asesores/pedidos/2700/actualizar-prenda', {
  method: 'POST',
  headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: formData
}).then(r => r.json()).then(data => {
  console.log(' Prenda actualizada:', data);
});
```

### Response (Backend):
```json
{
  "success": true,
  "message": "Prenda actualizada correctamente en la base de datos",
  "prenda": {
    "id": 3418,
    "nombre_prenda": "RET MEJORADO",
    "descripcion": "Retazo de tela de alta calidad",
    "tallas": {
      "DAMA": { "L": 10, "M": 15, "S": 5 },
      "CABALLERO": { "L": 7, "XL": 3 }
    }
  }
}
```

---

## 📌 Ejemplo 2: Solo Actualizar Tallas

### Request:
```javascript
const formData = new FormData();
formData.append('nombre_prenda', 'RET');
formData.append('cantidad_talla', JSON.stringify({
  "DAMA": {
    "L": 20,
    "M": 30,
    "S": 10
  }
}));
formData.append('novedad', 'Se aumentó la cantidad de tallas');

fetch('/asesores/pedidos/2700/actualizar-prenda', { /* ... */ });
```

### Base de Datos (Antes):
```
prenda_pedido_tallas para prenda 3418:
ID | PRENDA_ID | GENERO | TALLA | CANTIDAD
163| 3418      | DAMA   | L     | 20
164| 3418      | DAMA   | M     | 20
165| 3418      | DAMA   | S     | 20
```

### Base de Datos (Después):
```
prenda_pedido_tallas para prenda 3418:
ID | PRENDA_ID | GENERO | TALLA | CANTIDAD
166| 3418      | DAMA   | L     | 20
167| 3418      | DAMA   | M     | 30
168| 3418      | DAMA   | S     | 10
```

---

## 📌 Ejemplo 3: Actualizar Variantes (Manga, Broche, Bolsillos)

### Request:
```javascript
const formData = new FormData();
formData.append('nombre_prenda', 'RET');
formData.append('variantes', JSON.stringify([
  {
    "tipo_manga_id": 2,           // Manga larga
    "tipo_broche_boton_id": 1,    // Cremallera
    "manga_obs": "Manga larga con puño",
    "broche_boton_obs": null,
    "tiene_bolsillos": true,
    "bolsillos_obs": "2 bolsillos grandes en pecho"
  }
]));
formData.append('novedad', 'Se cambió a manga larga con cremallera');

fetch('/asesores/pedidos/2700/actualizar-prenda', { /* ... */ });
```

### Base de Datos (Después):
```
prenda_pedido_variantes para prenda 3418:
ID | PRENDA_ID | TIPO_MANGA_ID | TIPO_BROCHE_ID | MANGA_OBS | TIENE_BOLSILLOS | BOLSILLOS_OBS
1  | 3418      | 2             | 1              | Manga ... | 1               | 2 bolsillos...
```

---

## 📌 Ejemplo 4: Actualizar Procesos (Bordado, Estampado, etc)

### Request:
```javascript
const formData = new FormData();
formData.append('nombre_prenda', 'RET');
formData.append('procesos', JSON.stringify([
  {
    "tipo_proceso_id": 1,         // Bordado
    "ubicaciones": ["FRENTE", "ESPALDA"],
    "observaciones": "Bordado con hilo de plata",
    "estado": "PENDIENTE"
  },
  {
    "tipo_proceso_id": 3,         // Estampado DTF
    "ubicaciones": ["FRENTE"],
    "observaciones": "Estampado a todo color",
    "estado": "PENDIENTE"
  }
]));
formData.append('novedad', 'Se agregaron 2 procesos: bordado + estampado');

fetch('/asesores/pedidos/2700/actualizar-prenda', { /* ... */ });
```

### Base de Datos (Después):
```
pedidos_procesos_prenda_detalles para prenda 3418:
ID | PRENDA_ID | TIPO_PROCESO_ID | UBICACIONES          | OBSERVACIONES      | ESTADO
10 | 3418      | 1               | ["FRENTE","ESPALDA"] | Bordado con hilo.. | PENDIENTE
11 | 3418      | 3               | ["FRENTE"]           | Estampado a todo.. | PENDIENTE
```

---

## 📌 Ejemplo 5: Actualización Parcial (Solo Cambiar Nombre)

### Request:
```javascript
const formData = new FormData();
formData.append('nombre_prenda', 'RET - VERSIÓN PREMIUM');
formData.append('novedad', 'Solo se cambió el nombre de la prenda');

// NO enviar otros campos = se mantienen igual
fetch('/asesores/pedidos/2700/actualizar-prenda', { /* ... */ });
```

### Resultado:
-  Nombre actualizado
-  Tallas, variantes, procesos se MANTIENEN igual (no se envían = no se tocan)

---

## 🔍 Casos de Prueba Recomendados

### Test 1: Actualización Exitosa
```
POST /asesores/pedidos/2700/actualizar-prenda
Content-Type: multipart/form-data
X-CSRF-TOKEN: [token]

Body: {
  nombre_prenda: "RET",
  cantidad_talla: "{\"DAMA\":{\"L\":10}}",
  variantes: "[{\"tipo_manga_id\":1}]",
  novedad: "Test 1"
}

Expected: 200 OK + { success: true }
```

### Test 2: Falta Campo Obligatorio
```
POST /asesores/pedidos/2700/actualizar-prenda
Body: {
  nombre_prenda: "",  // VACÍO - campos requeridos
  novedad: "Test 2"
}

Expected: 422 Unprocessable Entity + errors
```

### Test 3: Prenda No Existe
```
POST /asesores/pedidos/2700/actualizar-prenda
Body: {
  prenda_id: 99999,  // No existe
  novedad: "Test 3"
}

Expected: 404 Not Found + error message
```

### Test 4: JSON Inválido
```
POST /asesores/pedidos/2700/actualizar-prenda
Body: {
  cantidad_talla: "{INVALID JSON}",  // JSON malformado
  novedad: "Test 4"
}

Expected: 422 Validation Error
```

### Test 5: Actualizar Todo a Vacío
```
POST /asesores/pedidos/2700/actualizar-prenda
Body: {
  cantidad_talla: "{}",      // Tallas vacías
  variantes: "[]",           // Variantes vacías
  colores_telas: "[]",       // Colores/Telas vacías
  procesos: "[]",            // Procesos vacíos
  novedad: "Limpiar datos"
}

Expected: 200 OK + todas las relaciones eliminadas
```

---

##  Verificación Post-Actualización

Después de cada actualización, verificar en BD:

```sql
-- 1. Verificar prenda se actualizó
SELECT * FROM prendas_pedido WHERE id = 3418;

-- 2. Verificar tallas
SELECT * FROM prenda_pedido_tallas WHERE prenda_pedido_id = 3418;

-- 3. Verificar variantes
SELECT * FROM prenda_pedido_variantes WHERE prenda_pedido_id = 3418;

-- 4. Verificar colores/telas
SELECT * FROM prenda_pedido_colores_telas WHERE prenda_pedido_id = 3418;

-- 5. Verificar procesos
SELECT * FROM pedidos_procesos_prenda_detalles WHERE prenda_pedido_id = 3418;

-- 6. Verificar en factura
SELECT * FROM ... [Ver ObtenerFacturaUseCase]
```

---

## 📊 Tabla de Comportamientos

| Campo | Si se envía | Si NO se envía | Efecto |
|-------|------------|----------------|--------|
| `nombre_prenda` | Se actualiza | Se mantiene | Condicional |
| `cantidad_talla` | DELETE + INSERT | Se mantiene | Destructivo |
| `variantes` | DELETE + INSERT | Se mantiene | Destructivo |
| `colores_telas` | DELETE + INSERT | Se mantiene | Destructivo |
| `procesos` | DELETE + INSERT | Se mantiene | Destructivo |
| `imagenes` | DELETE + INSERT | Se mantiene | Destructivo |

---

##  Flujo de Testing Recomendado

```
1. Crear pedido con prenda (prenda_id = 3418)
2. Agregar tallas, variantes, procesos
3. Actualizar con nuevos datos
4. Verificar en BD todos se actualizaron
5. Abrir factura
6. Verificar que todo se renderiza correctamente
7. Repetir con diferentes combinaciones
```

---

**Última Actualización:** 2026-01-23
**Versión:** 1.0 - COMPLETA
