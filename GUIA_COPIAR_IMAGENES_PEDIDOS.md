# Guía: Copiar Imágenes de Cotización a Pedido

## ¿Qué sucede cuando creas un pedido desde una cotización?

### 1. **Flujo Completo**

```
Usuario crea Pedido desde Cotización
    ↓
Frontend envía: cotizacion_id + prendas + forma_de_pago
    ↓
Backend valida datos
    ↓
Se ejecuta CrearPedidoProduccionJob
    ├─ Crea el pedido en BD
    ├─ Guarda prendas normalizadas
    ├─ ✅ COPIA IMÁGENES (NUEVO)
    └─ Guarda logo si existe
    ↓
Pedido creado con imágenes copiadas
```

### 2. **¿Cómo se copian las imágenes?**

El servicio `CopiarImagenesCotizacionAPedidoService` hace lo siguiente:

#### Paso 1: Obtener prendas de cotización
```php
$prendasCotizacion = PrendaCot::where('cotizacion_id', $cotizacionId)
    ->with(['fotos', 'telaFotos'])
    ->orderBy('id')
    ->get();
```
- Obtiene TODAS las prendas de la cotización
- Carga las relaciones: `fotos` (imágenes de prenda) y `telaFotos` (imágenes de tela)

#### Paso 2: Obtener prendas del pedido creado
```php
$prendasPedido = PrendaPed::where('pedido_produccion_id', $pedidoId)
    ->orderBy('id')
    ->get();
```
- Obtiene las prendas del pedido recién creado
- **Importante**: Se ordenan por ID para mantener el mismo orden que la cotización

#### Paso 3: Sincronizar imágenes por índice
```php
foreach ($prendasCotizacion as $index => $prendaCot) {
    $prendaPed = $prendasPedido->get($index);
    
    // Copiar fotos de prenda
    $fotosCopiadas = $this->copiarFotosPrenda($prendaCot, $prendaPed);
    
    // Copiar fotos de tela
    $fotosTelaCopiadas = $this->copiarFotosTela($prendaCot, $prendaPed);
}
```
- Itera sobre cada prenda de cotización
- Obtiene la prenda correspondiente del pedido usando el mismo índice
- Copia las imágenes

### 3. **¿Qué imágenes se copian?**

#### A. Fotos de Prenda
```
Tabla: prenda_fotos_cot (Cotización)
    ↓ COPIA URLs
Tabla: prenda_fotos_ped (Pedido)
```

**Campos copiados:**
- `ruta_original` - URL original de la imagen
- `ruta_webp` - URL en formato WebP
- `ruta_miniatura` - URL de miniatura
- `orden` - Orden de la imagen
- `ancho`, `alto`, `tamaño` - Metadatos

#### B. Fotos de Tela
```
Tabla: prenda_tela_fotos_cot (Cotización)
    ↓ COPIA URLs
Tabla: prenda_tela_fotos_ped (Pedido)
```

**Proceso:**
1. Se crea una entrada en `prenda_telas_ped` (tela del pedido)
2. Se copian todas las fotos de tela a `prenda_tela_fotos_ped`

### 4. **Ejemplo Práctico**

**Cotización COT-001:**
```
Prenda 1: Camiseta
  ├─ Foto 1: /storage/cotizaciones/5/prendas/camiseta_1.webp
  ├─ Foto 2: /storage/cotizaciones/5/prendas/camiseta_2.webp
  └─ Foto Tela: /storage/cotizaciones/5/telas/tela_azul.webp

Prenda 2: Pantalón
  ├─ Foto 1: /storage/cotizaciones/5/prendas/pantalon_1.webp
  └─ Foto Tela: /storage/cotizaciones/5/telas/tela_negra.webp
```

**Al crear Pedido PED-001:**
```
Prenda 1 (Camiseta):
  ├─ prenda_fotos_ped:
  │  ├─ ruta_original: /storage/cotizaciones/5/prendas/camiseta_1.webp ✅
  │  └─ ruta_original: /storage/cotizaciones/5/prendas/camiseta_2.webp ✅
  └─ prenda_tela_fotos_ped:
     └─ ruta_original: /storage/cotizaciones/5/telas/tela_azul.webp ✅

Prenda 2 (Pantalón):
  ├─ prenda_fotos_ped:
  │  └─ ruta_original: /storage/cotizaciones/5/prendas/pantalon_1.webp ✅
  └─ prenda_tela_fotos_ped:
     └─ ruta_original: /storage/cotizaciones/5/telas/tela_negra.webp ✅
```

### 5. **Logging para Verificar**

El servicio genera logs detallados. Busca en `storage/logs/laravel.log`:

```
✅ Imágenes copiadas exitosamente de cotización a pedido
   cotizacion_id: 5
   pedido_id: 12
   prendas_procesadas: 2
   total_imagenes_copiadas: 4

📸 Fotos de prenda copiadas
   prenda_cot_id: 15
   prenda_ped_id: 42
   cantidad_fotos: 2

🧵 Fotos de tela copiadas
   prenda_cot_id: 15
   prenda_ped_id: 42
   tela_ped_id: 8
   cantidad_fotos_tela: 1
```

### 6. **Estructura de Base de Datos**

```sql
-- Cotización (origen)
SELECT * FROM prenda_fotos_cot WHERE prenda_cot_id = 15;
-- Resultado: 2 fotos

SELECT * FROM prenda_tela_fotos_cot WHERE prenda_cot_id = 15;
-- Resultado: 1 foto de tela

-- Pedido (destino)
SELECT * FROM prenda_fotos_ped WHERE prenda_ped_id = 42;
-- Resultado: 2 fotos (COPIADAS)

SELECT * FROM prenda_tela_fotos_ped WHERE prenda_tela_ped_id = 8;
-- Resultado: 1 foto de tela (COPIADA)
```

### 7. **¿Qué sucede si hay errores?**

El servicio está diseñado para ser robusto:

- **Si una prenda no tiene fotos**: Se salta y continúa con la siguiente
- **Si falla al copiar una foto**: Se registra el error pero continúa
- **Si hay diferente cantidad de prendas**: Se registra advertencia pero continúa
- **Si falla todo**: Se lanza excepción y se registra en logs

### 8. **Verificación Manual**

Para verificar que las imágenes se copiaron correctamente:

```php
// En Tinker o en un controlador
$pedido = PedidoProduccion::find(12);
$prendas = $pedido->prendas; // Relación a prendas_ped

foreach ($prendas as $prenda) {
    echo "Prenda: " . $prenda->nombre_producto . "\n";
    echo "Fotos: " . $prenda->fotos()->count() . "\n";
    echo "Fotos de tela: " . $prenda->telas()->sum(fn($t) => $t->fotos()->count()) . "\n";
}
```

### 9. **Resumen**

| Aspecto | Detalles |
|---------|----------|
| **¿Se copian?** | ✅ SÍ - Automáticamente al crear pedido |
| **¿Qué se copia?** | URLs de imágenes (no los archivos) |
| **¿De dónde?** | De `prenda_fotos_cot` y `prenda_tela_fotos_cot` |
| **¿A dónde?** | A `prenda_fotos_ped` y `prenda_tela_fotos_ped` |
| **¿Cuándo?** | Después de guardar prendas, antes de guardar logo |
| **¿Cómo se sincroniza?** | Por índice (misma posición en ambas listas) |
| **¿Hay validación?** | ✅ SÍ - Valida cantidad de prendas y registra logs |

