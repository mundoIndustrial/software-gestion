# Análisis: Estructura del Método `obtenerDatosRecibos`

## Ubicación
- **Archivo**: `app/Domain/Pedidos/Repositories/PedidoProduccionRepository.php`
- **Línea**: 423
- **Método**: `obtenerDatosRecibos(int $pedidoId): array`

## Flujo de Construcción del Array de Prendas

### 1. Obtención de Tallas desde Base de Datos Relacional
```php
// Línea 608
$tallas = $this->obtenerTallas($prenda->id);
```

**Ubicación real del método**: `app/Domain/Pedidos/Traits/GestionaTallasRelacional.php` (Línea 59)

**¿Qué hace?**
- Consulta la tabla `prenda_pedido_tallas`
- Retorna un array estructurado: 
  ```php
  [
      'DAMA' => ['M' => 10, 'L' => 20, 'S' => 5],
      'CABALLERO' => ['32' => 15, '34' => 20]
  ]
  ```

**Construcción de `generosConTallas`** (Línea 720-745):
```php
$generosConTallas = [];
if (!empty($tallas) && is_array($tallas)) {
    foreach ($tallas as $genero => $tallasCant) {
        if (is_array($tallasCant)) {
            // Detectar si son tallas letra (S,M,L,XL) o número (34,36,38)
            $tipo = null;
            $primerasTallas = array_keys($tallasCant);
            if (!empty($primerasTallas)) {
                $primeraTalla = $primerasTallas[0];
                if (strlen($primeraTalla) <= 3 && !is_numeric($primeraTalla)) {
                    $tipo = 'letra';
                } else if (is_numeric($primeraTalla)) {
                    $tipo = 'numero';
                }
            }
            
            $generosConTallas[$genero] = [
                'tallas' => array_keys($tallasCant),           // ['M', 'L', 'S']
                'tipo' => $tipo,                               // 'letra' o 'numero'
                'cantidades' => $tallasCant                    // {'M': 10, 'L': 20, ...}
            ];
        }
    }
}
```

### 2. Estructura Final de Cada Prenda en el Array

```php
$prendasFormato = [
    // Identificadores
    'id' => $prenda->id,
    'prenda_pedido_id' => $prenda->id,
    'numero' => $prendaIndex + 1,
    
    // Datos Básicos
    'nombre_prenda' => $prenda->nombre_prenda,
    'nombre' => $prenda->nombre_prenda,
    'origen' => 'bodega' | 'confección',  // Basado en $prenda->de_bodega
    'descripcion' => $prenda->descripcion,
    
    // Telas, Colores y Referencias
    'tela' => 'Tela1, Tela2',  // Desde prenda_pedido_colores_telas
    'color' => 'Rojo, Azul',   // Desde prenda_pedido_colores_telas
    'ref' => 'REF1, REF2',      // Referencias desde telas_prenda
    
    // TALLAS (DE TABLA RELACIONAL)
    'tallas' => [                       // Línea 273 - Array directo de BD
        'DAMA' => ['M' => 10, 'L' => 20],
        'CABALLERO' => ['32' => 15, '34' => 20]
    ],
    
    'generosConTallas' => [             // Línea 720-745 - Estructura procesada
        'DAMA' => [
            'tallas' => ['M', 'L'],
            'tipo' => 'letra',
            'cantidades' => ['M' => 10, 'L' => 20]
        ],
        'CABALLERO' => [
            'tallas' => ['32', '34'],
            'tipo' => 'numero',
            'cantidades' => ['32' => 15, '34' => 20]
        ]
    ],
    
    // Telas Agregadas (Con Imágenes)
    'telasAgregadas' => [               // Línea 680-717
        [
            'tela' => 'Tela Premium',
            'color' => 'Rojo',
            'referencia' => 'REF-001',
            'imagenes' => ['/storage/fotos/tela1.webp', '/storage/fotos/tela2.webp']
        ]
    ],
    
    // Variantes y Especificaciones
    'variantes' => [                    // Línea 575-605 - Desde prendas->variantes
        [
            'talla' => 'Estándar',
            'cantidad' => 50,
            'manga' => 'Larga',
            'manga_obs' => 'Observaciones de manga',
            'broche' => 'Botón',
            'broche_obs' => 'Observaciones de broche',
            'bolsillos' => true,
            'bolsillos_obs' => 'Bolsillos grandes'
        ]
    ],
    
    // Datos de Bodega/Confección
    'de_bodega' => 0 | 1,
    
    // Procesos (Con Tallas desde PedidosProcesosPrendaTalla)
    'procesos' => [                     // Línea 617-670
        [
            'nombre_proceso' => 'Bordado',
            'tipo_proceso' => 'Bordado',
            'tallas' => [                // Desde tabla prenda_pedido_talla
                'dama' => ['M' => 10, 'L' => 20],
                'caballero' => ['32' => 15]
            ],
            'observaciones' => 'Bordado a pecho',
            'ubicaciones' => ['pecho', 'espalda'],
            'imagenes' => ['/storage/procesos/img1.webp'],
            'estado' => 'Pendiente'
        ]
    ],
    
    // Imágenes
    'imagenes' => ['/storage/prendas/prenda1.webp'],          // Desde prenda_fotos_pedido
    'imagenes_tela' => ['/storage/telas/tela1.webp'],         // Desde prenda_fotos_tela_pedido
    'fotos_tela' => ['/storage/telas/tela1.webp'],
    
    // Observaciones de Variaciones
    'obs_manga' => 'Manga larga',
    'obs_bolsillos' => 'Con bolsillos',
    'obs_broche' => 'Botones de madera',
    'obs_reflectivo' => 'Sí' | '',
    'tipo_manga' => 'Larga',
    'tipo_broche' => 'Botón',
    'tipo_broche_boton_id' => 5,
    
    // Atributos Booleanos
    'tiene_bolsillos' => true | false,
    'tiene_reflectivo' => true | false,
];
```

### 3. Datos Base del Pedido (Nivel Superior)

```php
$datos = [
    'numero_pedido' => 'PED-001',
    'numero_pedido_temporal' => 1,
    'cliente' => 'Nombre del Cliente',
    'asesora' => 'Nombre de la Asesora',
    'forma_de_pago' => 'Contado',
    'fecha' => '23/01/2026',
    'fecha_creacion' => '23/01/2026',
    'observaciones' => 'Observaciones del pedido',
    'prendas' => [ /* Array de prendas como se describió arriba */ ],
    'epps' => [ /* EPPs del pedido */ ],
];
```

## Punto Clave: ¿Dónde se Cargan las Tallas?

### A nivel de PRENDA:
```php
// Línea 608
$tallas = $this->obtenerTallas($prenda->id);  // Consulta tabla prenda_pedido_tallas
```

**Método en el Trait** (GestionaTallasRelacional.php, línea 59):
```php
public function obtenerTallas(int $prendaPedidoId): array
{
    $tallas = [];
    
    PrendaPedidoTalla::where('prenda_pedido_id', $prendaPedidoId)
        ->get()
        ->each(function ($tallaRecord) use (&$tallas) {
            $genero = $tallaRecord->genero;
            if (!isset($tallas[$genero])) {
                $tallas[$genero] = [];
            }
            $tallas[$genero][$tallaRecord->talla] = $tallaRecord->cantidad;
        });

    return $tallas;
}
```

### A nivel de PROCESOS (Dentro de cada Prenda):
```php
// Línea 630-645
$procesos = [];
foreach ($prenda->procesos as $proc) {
    $procTallas = [
        'dama' => [],
        'caballero' => [],
        'unisex' => []
    ];
    
    // Procesar tallas DESDE LA TABLA RELACIONAL
    $tallasRelacionales = \App\Models\PedidosProcesosPrendaTalla::where(
        'proceso_prenda_detalle_id', 
        $proc->id
    )->get();
    
    foreach ($tallasRelacionales as $tallaRecord) {
        $genero = strtolower($tallaRecord->genero);
        if ($tallaRecord->cantidad > 0) {
            $procTallas[$genero][$tallaRecord->talla] = $tallaRecord->cantidad;
        }
    }
    // ...
}
```

## Tablas Involucradas en la Construcción

| Tabla | Propósito | Uso en obtenerDatosRecibos |
|-------|----------|---------------------------|
| `prenda_pedido_tallas` | Almacena tallas por prenda | Línea 608 - obtenerTallas() |
| `prenda_fotos_pedido` | Imágenes de prendas | Línea 465 |
| `prenda_pedido_colores_telas` | Telas y colores de prendas | Línea 510 |
| `prenda_fotos_tela_pedido` | Imágenes de telas | Línea 526 |
| `tipos_manga` | Tipos de manga | Línea 575 |
| `tipos_broche_boton` | Tipos de broche/botón | Línea 583 |
| `procesos_prenda_detalle` | Procesos de prendas | Línea 620 (relación prendas->procesos) |
| `pedidos_procesos_prenda_talla` | Tallas por proceso | Línea 632 |

## Resumen: Cómo Agregar Tallas a Recibos

1. **Las tallas YA se cargan** desde `prenda_pedido_tallas` (línea 608)
2. **Se construyen dos estructuras**:
   - `'tallas'`: Array directo [genero => [talla => cantidad]]
   - `'generosConTallas'`: Estructura enriquecida con tipo y lista de tallas

3. **Para procesos**, las tallas se cargan desde `pedidos_procesos_prenda_talla` (línea 632)

## Puntos de Modificación si Necesitas Cambios

Si necesitas agregar o modificar cómo se cargan las tallas:

1. **Para tallas de prendas**:
   - Trait: `GestionaTallasRelacional::obtenerTallas()` (línea 59)
   - Repositorio: `obtenerDatosRecibos()` (línea 608)

2. **Para tallas de procesos**:
   - Repositorio: `obtenerDatosRecibos()` (línea 632-645)
   - Modelo: `PedidosProcesosPrendaTalla`

3. **Para estructura de presentación**:
   - Repositorio: `obtenerDatosRecibos()` (línea 720-745) - generosConTallas

## Logs Disponibles para Debugging

```php
// Línea 606
\Log::info('[RECIBOS] Tallas cargadas para prenda ' . $prendaIndex, ['tallas' => $tallas]);

// Usa estos logs para verificar que las tallas se cargan correctamente
```
