# 📊 DIAGNÓSTICO: ALMACENAMIENTO DE PRENDAS EN MÓDULO ASESOR

## 🎯 PREGUNTA CLAVE
**¿Dónde se almacenan las prendas cuando el asesor crea un pedido?**

---

## ✅ RESPUESTA

### **Las prendas se guardan en la tabla `prendas_pedido`**

**Flujo de almacenamiento:**
```
AsesoresController::store()
    ↓
PrendaPedido::create() [Tabla: prendas_pedido]
    ↓
PedidoPrendaService::guardarPrendasEnPedido() [Tabla: prendas_pedido + relaciones]
```

---

## 📋 ESTRUCTURA ACTUAL DE ALMACENAMIENTO

### 1. **Tabla Principal: `prendas_pedido`**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | BIGINT | ID de la prenda |
| `numero_pedido` | INT | FK a pedidos_produccion |
| `nombre_prenda` | VARCHAR(255) | Nombre del producto |
| `cantidad` | INT | Cantidad total |
| `descripcion` | LONGTEXT | ✅ **Descripción completa formateada** |
| `descripcion_variaciones` | LONGTEXT | Detalles de variaciones |
| `cantidad_talla` | JSON | Array de tallas y cantidades |
| `color_id` | BIGINT | FK a colores_prenda |
| `tela_id` | BIGINT | FK a telas_prenda |
| `tipo_manga_id` | BIGINT | FK a tipo_manga |
| `tipo_broche_id` | BIGINT | FK a tipo_broche |
| `tiene_bolsillos` | BOOLEAN | Indica si tiene bolsillos |
| `tiene_reflectivo` | BOOLEAN | Indica si tiene reflectivo |
| `created_at` | TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | Fecha de actualización |
| `deleted_at` | TIMESTAMP | Soft delete |

### 2. **Tablas Relacionadas para Fotos**

#### **prenda_fotos_pedido**
```sql
Almacena: Fotos de la prenda (portadas/referencias)
Relación: prenda_pedido_id → prendas_pedido.id
```

#### **prenda_fotos_logo_pedido**
```sql
Almacena: Fotos de logos para la prenda
Relación: prenda_pedido_id → prendas_pedido.id
Campos: ubicacion, orden
```

#### **prenda_fotos_tela_pedido**
```sql
Almacena: Fotos de telas/colores específicos
Relación: prenda_pedido_id → prendas_pedido.id
Campos: tela_id, color_id, orden
```

---

## 🔄 FLUJO DETALLADO: CREACIÓN DE PEDIDO DESDE ASESOR

### **Paso 1: AsesoresController::store()**
```php
public function store(Request $request)
{
    // Crear pedido vacío
    $pedidoBorrador = PedidoProduccion::create([
        'numero_pedido' => null,
        'cliente' => $validated['cliente'],
        'asesor_id' => Auth::id(),
        'estado' => 'No iniciado',
    ]);

    // ❌ PROBLEMA: Se crea con relaciones directas (prendas() relationship)
    foreach ($validated[$productosKey] as $productoData) {
        $pedidoBorrador->prendas()->create([
            'nombre_prenda' => $productoData['nombre_producto'],
            'cantidad' => $productoData['cantidad'],
            // ⚠️ INCOMPLETO: Falta guardar descripción completa y variaciones
        ]);
    }
}
```

**Ubicación:** [AsesoresController.php](app/Http/Controllers/AsesoresController.php#L253)

### **Paso 2: PedidoPrendaService::guardarPrendasEnPedido()**

Este servicio está diseñado para guardar prendas **COMPLETAS** pero NO se está usando en la creación desde el asesor.

```php
public function guardarPrendasEnPedido(PedidoProduccion $pedido, array $prendas): void
{
    foreach ($prendas as $prendaData) {
        $this->guardarPrenda($pedido, $prendaData, $index);
    }
}
```

**Ubicación:** [PedidoPrendaService.php](app/Application/Services/PedidoPrendaService.php#L31)

---

## 🚨 PROBLEMA IDENTIFICADO

### **El controlador AsesoresController::store() NO utiliza PedidoPrendaService**

**Situación actual:**
```
AsesoresController::store()
    ↓ 
Crea prendas INCOMPLETAS con solo: nombre_prenda, cantidad
    ↓
NO guarda: descripción, variaciones, telas, colores, fotos, logos
    ↓
❌ RESULTADO: Prendas vacías sin información completa
```

**Debería ser:**
```
AsesoresController::store()
    ↓
PedidoPrendaService::guardarPrendasEnPedido()
    ↓
Guarda TODA la información:
    ✅ Descripción formateada
    ✅ Variaciones (manga, broche, bolsillos, reflectivo)
    ✅ Telas y colores
    ✅ Fotos de prenda
    ✅ Logos
    ✅ Fotos de telas
```

---

## 📝 INFORMACIÓN QUE DEBERÍA GUARDARSE

### **En tabla `prendas_pedido`:**
```php
[
    'numero_pedido' => 45452,
    'nombre_prenda' => 'CAMISA DRILL',
    'cantidad' => 150,
    'descripcion' => 'PRENDA 1: CAMISA DRILL\nColor: NARANJA | Tela: DRILL BORNEO REF:REF-DB-001\nDescripción: LOGO BORDADO EN ESPALDA\nManga: LARGA\n...',
    'descripcion_variaciones' => 'Manga: LARGA | Bolsillos: SI | Reflectivo: SI',
    'cantidad_talla' => '{"S": 50, "M": 50, "L": 50}',
    'color_id' => 5,
    'tela_id' => 12,
    'tipo_manga_id' => 3,
    'tipo_broche_id' => null,
    'tiene_bolsillos' => true,
    'tiene_reflectivo' => true,
]
```

### **En tabla `prenda_fotos_pedido`:**
```php
[
    'prenda_pedido_id' => $prenda->id,
    'ruta_original' => 'storage/fotos/camisa-drill.jpg',
    'ruta_webp' => 'storage/fotos/camisa-drill.webp',
    'ruta_miniatura' => 'storage/fotos/camisa-drill-thumb.jpg',
    'orden' => 1,
    'ancho' => 1920,
    'alto' => 1080,
    'tamaño' => 102400,
]
```

### **En tabla `prenda_fotos_tela_pedido`:**
```php
[
    'prenda_pedido_id' => $prenda->id,
    'tela_id' => 12,
    'color_id' => 5,
    'ruta_original' => 'storage/telas/drill-naranja.jpg',
    'ruta_webp' => 'storage/telas/drill-naranja.webp',
    'orden' => 1,
]
```

### **En tabla `prenda_fotos_logo_pedido`:**
```php
[
    'prenda_pedido_id' => $prenda->id,
    'ruta_original' => 'storage/logos/logo-bordado.jpg',
    'ubicacion' => 'ESPALDA',
    'orden' => 1,
]
```

---

## ✅ SOLUCIÓN RECOMENDADA

### **Modificar AsesoresController::store() para usar PedidoPrendaService**

**Cambio requerido en línea 260:**

```php
// ❌ ACTUAL (Incompleto):
foreach ($validated[$productosKey] as $productoData) {
    $pedidoBorrador->prendas()->create([
        'nombre_prenda' => $productoData['nombre_producto'],
        'cantidad' => $productoData['cantidad'],
    ]);
}

// ✅ NUEVO (Completo):
$pedidoPrendaService = new PedidoPrendaService();
$pedidoPrendaService->guardarPrendasEnPedido(
    $pedidoBorrador, 
    $validated[$productosKey]
);
```

---

## 🔍 CONSULTAS PARA VERIFICAR

### **Ver prendas guardadas en un pedido:**
```sql
SELECT * FROM prendas_pedido WHERE numero_pedido = 45452;
```

### **Ver información completa de una prenda:**
```sql
SELECT 
    pp.id, 
    pp.numero_pedido, 
    pp.nombre_prenda,
    pp.descripcion,
    pp.descripcion_variaciones,
    pp.cantidad_talla,
    pp.color_id,
    pp.tela_id,
    COUNT(DISTINCT pfp.id) as fotos_prenda,
    COUNT(DISTINCT pflog.id) as fotos_logo,
    COUNT(DISTINCT pft.id) as fotos_tela
FROM prendas_pedido pp
LEFT JOIN prenda_fotos_pedido pfp ON pp.id = pfp.prenda_pedido_id
LEFT JOIN prenda_fotos_logo_pedido pflog ON pp.id = pflog.prenda_pedido_id
LEFT JOIN prenda_fotos_tela_pedido pft ON pp.id = pft.prenda_pedido_id
WHERE pp.numero_pedido = 45452
GROUP BY pp.id;
```

---

## 📊 ESTADO ACTUAL VS ESPERADO

| Aspecto | Estado Actual | Esperado | Status |
|---------|---------------|----------|--------|
| Tabla almacenamiento | `prendas_pedido` | `prendas_pedido` | ✅ Correcto |
| Nombre prenda | Guardado | Guardado | ✅ Correcto |
| Cantidad | Guardado | Guardado | ✅ Correcto |
| Descripción formateada | ❌ No guardada | Guardada | 🚨 Falta |
| Variaciones | ❌ No guardadas | Guardadas | 🚨 Falta |
| Color/Tela | ❌ No guardados | Guardados | 🚨 Falta |
| Fotos prenda | ❌ No guardadas | Guardadas | 🚨 Falta |
| Fotos tela | ❌ No guardadas | Guardadas | 🚨 Falta |
| Logos | ❌ No guardados | Guardados | 🚨 Falta |

---

## 🎯 PRÓXIMOS PASOS

1. **Usar PedidoPrendaService en AsesoresController::store()**
2. **Validar que se envíen todos los datos necesarios desde el frontend**
3. **Verificar que las fotos/logos se copien correctamente**
4. **Confirmar que la descripción se formatea con DescripcionPrendaLegacyFormatter**
5. **Hacer pruebas de guardado completo**

