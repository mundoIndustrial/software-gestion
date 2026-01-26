# 📸 Guía: Imágenes de Telas en Pedidos

## Verificar Imágenes de Telas de un Pedido

### 1️⃣ Usando la Terminal
```bash
# Verificar pedido específico
php artisan diagnostico:telas 45726

# Ingresar ID interactivamente
php artisan diagnostico:telas
```

**Salida esperada:**
```
╔════════════════════════════════════════════════════════════╗
║          VERIFICACIÓN DE IMÁGENES DE TELAS                ║
║          Pedido: 45726                                     ║
╚════════════════════════════════════════════════════════════╝

📦 Total de prendas: 2

┌─ PRENDA #1 (ID: 123)
│  Nombre: Camisa Blanca
│  Descripción: Talla S-M-L
│  Telas: 3
│  ├─ Tela #1 (ID: 45)
│  │  Color: Blanco
│  │  Tela: Popelina
│  │  Imágenes: 2
│  │  ├─ Foto #1 (Orden: 1)
│  │  │  Ruta: pedidos/45726/telas/color_tela_45_0.webp
│  │  │  ✅ En disco (125432 bytes)
│  │  │
│  │  └─ Foto #2 (Orden: 2)
│  │     Ruta: pedidos/45726/telas/color_tela_45_1.webp
│  │     ✅ En disco (98765 bytes)

📊 RESUMEN
Total de telas (color-tela): 3
📸 Total de imágenes en BD: 5
💾 Total de imágenes en disco: 5
✅ TODAS las imágenes están en disco
```

### 2️⃣ Consultando Base de Datos Directamente

```sql
-- Verificar todas las telas y sus imágenes en un pedido
SELECT 
    pp.id as prenda_id,
    pp.nombre_prenda,
    pct.id as color_tela_id,
    cp.nombre as color,
    tp.nombre as tela,
    COUNT(pft.id) as cantidad_fotos
FROM prendas_pedido pp
LEFT JOIN prenda_pedido_colores_telas pct ON pp.id = pct.prenda_pedido_id
LEFT JOIN colores_prenda cp ON pct.color_id = cp.id
LEFT JOIN telas_prenda tp ON pct.tela_id = tp.id
LEFT JOIN prenda_fotos_tela_pedido pft ON pct.id = pft.prenda_pedido_colores_telas_id
WHERE pp.pedido_produccion_id = 45726
GROUP BY pp.id, pct.id
ORDER BY pp.id;
```

**Resultado:**
```
prenda_id | nombre_prenda | color_tela_id | color    | tela     | cantidad_fotos
----------|---------------|---------------|----------|----------|----------------
123       | Camisa Blanca | 45            | Blanco   | Popelina | 2
123       | Camisa Blanca | 46            | Azul     | Algodón  | 1
123       | Camisa Blanca | 47            | Rojo     | Lino     | 0
124       | Pantalón Gris | 48            | Gris     | Tergal   | 3
```

### 3️⃣ Ver Todas las Imágenes de una Tela Específica

```sql
SELECT 
    pft.id,
    pft.ruta_webp,
    pft.orden,
    pft.created_at
FROM prenda_fotos_tela_pedido pft
WHERE pft.prenda_pedido_colores_telas_id = 45
ORDER BY pft.orden;
```

### 4️⃣ Verificar Estructura de Carpetas

```bash
# Listar archivos en la carpeta de telas del pedido
ls -lah storage/app/public/pedidos/45726/telas/

# Contar archivos
ls storage/app/public/pedidos/45726/telas/ | wc -l

# Ver tamaño total
du -sh storage/app/public/pedidos/45726/telas/
```

## Solucionar Problemas

### Problema: No se ven imágenes de telas
**Paso 1:** Verificar en BD
```sql
SELECT COUNT(*) as total FROM prenda_fotos_tela_pedido 
WHERE prenda_pedido_colores_telas_id IN (
    SELECT id FROM prenda_pedido_colores_telas 
    WHERE prenda_pedido_id = ?
);
```

**Paso 2:** Verificar en disco
```bash
# ¿Existe la carpeta?
test -d storage/app/public/pedidos/45726/telas && echo "✅ Carpeta existe" || echo "❌ NO existe"

# ¿Hay archivos?
ls storage/app/public/pedidos/45726/telas/ | head -10
```

**Paso 3:** Ver logs
```bash
# Filtrar logs de procesamiento de telas
tail -f storage/logs/laravel.log | grep -i "tela\|color_tela"
```

### Problema: Imágenes en BD pero no en disco
```sql
-- Encontrar imágenes huérfanas
SELECT 
    pft.id,
    pft.prenda_pedido_colores_telas_id,
    pft.ruta_webp
FROM prenda_fotos_tela_pedido pft
WHERE NOT EXISTS (
    SELECT 1 FROM pedidos_produccion pp
    WHERE pp.id = ? 
    AND pp.id = (
        SELECT pedido_produccion_id FROM prendas_pedido pp2
        WHERE pp2.id = (
            SELECT prenda_pedido_id FROM prenda_pedido_colores_telas
            WHERE id = pft.prenda_pedido_colores_telas_id
        )
    )
);
```

## Estructura del Disco (Ejemplo)

```
storage/app/public/pedidos/45726/
├── prendas/
│   ├── prenda_123_0.webp
│   ├── prenda_123_1.webp
│   ├── prenda_124_0.webp
│   └── prenda_124_1.webp
├── telas/
│   ├── color_tela_45_0.webp      ← Fotos del color-tela #45
│   ├── color_tela_45_1.webp
│   ├── color_tela_46_0.webp      ← Fotos del color-tela #46
│   ├── color_tela_47_0.webp      ← Fotos del color-tela #47
│   └── color_tela_48_0.webp      ← Fotos del color-tela #48 (de otra prenda)
├── procesos/
│   ├── BORDADO/
│   │   ├── proceso_10_0.webp
│   │   └── proceso_10_1.webp
│   └── ESTAMPADO/
│       └── proceso_11_0.webp
└── epps/
    ├── 1/
    │   ├── epp_1_0.webp
    │   └── epp_1_1.webp
    └── 2/
        └── epp_2_0.webp
```

## Campos Importantes

### prenda_pedido_colores_telas
- `id` - Identificador único de la combinación color-tela
- `prenda_pedido_id` - FK a la prenda
- `color_id` - FK a `colores_prenda` (tabla catálogo)
- `tela_id` - FK a `telas_prenda` (tabla catálogo)

### prenda_fotos_tela_pedido
- `id` - Identificador de la foto
- `prenda_pedido_colores_telas_id` - FK a la combinación color-tela
- `ruta_webp` - Ruta relativa del archivo optimizado
- `orden` - Orden de visualización (1, 2, 3...)
- `created_at` - Fecha de carga
- `deleted_at` - Soft delete (NULL si está activa)

## Frontend: Cómo se envía FormData

```javascript
// Envío de imágenes de telas
for (let itemIdx = 0; itemIdx < items.length; itemIdx++) {
    const item = items[itemIdx];
    
    if (item.telas && item.telas.length > 0) {
        for (let telaIdx = 0; telaIdx < item.telas.length; telaIdx++) {
            const tela = item.telas[telaIdx];
            
            if (tela.imagenes && tela.imagenes.length > 0) {
                for (let imgIdx = 0; imgIdx < tela.imagenes.length; imgIdx++) {
                    const archivo = tela.imagenes[imgIdx];
                    
                    // ✅ ESTRUCTURA CORRECTA
                    formData.append(
                        `prendas[${itemIdx}][telas][${telaIdx}][imagenes][${imgIdx}]`,
                        archivo  // File object
                    );
                }
            }
        }
    }
}
```

## Verificación Rápida de Salud

```bash
# Crear un alias para verificación rápida
alias check-telas='php artisan diagnostico:telas'

# Uso
check-telas 45726
```

## Estadísticas del Pedido

```bash
# Script para obtener resumen completo
php artisan tinker
```

```php
$pedidoId = 45726;
$pedido = \App\Models\PedidoProduccion::with('prendas.coloresTelas.fotos')->find($pedidoId);

echo "Pedido: " . $pedido->numero_pedido . "\n";
echo "Total Prendas: " . $pedido->prendas->count() . "\n";

$totalTelas = 0;
$totalFotos = 0;

foreach ($pedido->prendas as $prenda) {
    $totalTelas += $prenda->coloresTelas->count();
    foreach ($prenda->coloresTelas as $colorTela) {
        $totalFotos += $colorTela->fotos->count();
    }
}

echo "Total Telas (color-tela): $totalTelas\n";
echo "Total Imágenes: $totalFotos\n";
```

