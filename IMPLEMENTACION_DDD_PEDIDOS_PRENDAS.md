# ✅ IMPLEMENTACIÓN DDD - PRENDAS Y LOGOS DE PEDIDOS

## 📋 RESUMEN DE CAMBIOS

Se ha implementado una arquitectura DDD y SOLID para guardar prendas y logos de pedidos en las nuevas tablas normalizadas.

## 🗂️ ARCHIVOS CREADOS

### Services
- ✅ `app/Application/Services/PedidoPrendaService.php`
  - Guardar prendas con fotos, telas, tallas y variantes
  - Copia URLs de cotizaciones (sin duplicar archivos)
  
- ✅ `app/Application/Services/PedidoLogoService.php`
  - Guardar logos con fotos
  - Copia URLs de cotizaciones (sin duplicar archivos)

### Models
- ✅ `app/Models/PrendaPed.php` - Prenda de pedido
- ✅ `app/Models/PrendaFotoPed.php` - Foto de prenda
- ✅ `app/Models/PrendaTelaPed.php` - Tela/color de prenda
- ✅ `app/Models/PrendaTalaFotoPed.php` - Foto de tela
- ✅ `app/Models/PrendaTalaPed.php` - Talla de prenda
- ✅ `app/Models/PrendaVariantePed.php` - Variante de prenda
- ✅ `app/Models/LogoPed.php` - Logo de pedido
- ✅ `app/Models/LogoFotoPed.php` - Foto de logo

### Migrations (ya ejecutadas)
- ✅ `database/migrations/2025_12_14_create_prendas_pedidos_tables.php`
- ✅ `database/migrations/2025_12_14_create_logo_pedidos_tables.php`

## 🔌 CÓMO INTEGRAR EN CONTROLADOR

### En el Controlador al crear un pedido (ej: PedidosController)

```php
use App\Application\Services\PedidoPrendaService;
use App\Application\Services\PedidoLogoService;

class PedidosController extends Controller
{
    public function __construct(
        private PedidoPrendaService $prendaService,
        private PedidoLogoService $logoService,
    ) {}

    public function store(Request $request)
    {
        // 1. Crear pedido
        $pedido = PedidoProduccion::create([
            'numero_pedido' => $request->numero_pedido,
            'cliente_id' => $request->cliente_id,
            // ... otros campos
        ]);

        // 2. Guardar prendas con fotos y variantes
        if (!empty($request->prendas)) {
            $this->prendaService->guardarPrendasEnPedido($pedido, $request->prendas);
        }

        // 3. Guardar logo con fotos
        if (!empty($request->logo)) {
            $this->logoService->guardarLogoEnPedido($pedido, $request->logo);
        }

        return response()->json(['success' => true, 'pedido_id' => $pedido->id]);
    }
}
```

## 📊 ESTRUCTURA DE DATOS ESPERADA

### Para Prendas (array):
```php
$prendas = [
    [
        'nombre_producto' => 'CAMISA DRILL',
        'descripcion' => 'Camisa de trabajo',
        'cantidad' => 100,
        'fotos' => [
            [
                'ruta_original' => 'storage/fotos/prenda_1.jpg',
                'ruta_webp' => 'storage/fotos/prenda_1.webp',
                'ruta_miniatura' => 'storage/fotos/prenda_1_thumb.jpg',
                'ancho' => 1920,
                'alto' => 1080,
                'tamaño' => 102400,
            ]
        ],
        'telas' => [
            [
                'color_id' => 1,
                'tela_id' => 5,
                'fotos' => [
                    [
                        'ruta_original' => 'storage/telas/tela_1.jpg',
                        // ... más campos
                    ]
                ]
            ]
        ],
        'tallas' => [
            ['talla' => 'S', 'cantidad' => 50],
            ['talla' => 'M', 'cantidad' => 30],
            ['talla' => 'L', 'cantidad' => 20],
        ],
        'variantes' => [
            [
                'tipo_prenda' => 'CAMISA',
                'genero_id' => 1,
                'tipo_manga_id' => 2,
                'tiene_bolsillos' => true,
                'obs_bolsillos' => 'Pecho',
                'tiene_reflectivo' => false,
                'telas_multiples' => ['tela_1', 'tela_2'],
            ]
        ]
    ]
];
```

### Para Logo (array):
```php
$logo = [
    'descripcion' => 'Logo bordado en espalda',
    'ubicacion' => 'Espalda',
    'observaciones_generales' => ['Hilo azul marino'],
    'fotos' => [
        [
            'ruta_original' => 'storage/logos/logo_1.jpg',
            'ruta_webp' => 'storage/logos/logo_1.webp',
            'orden' => 1,
        ]
    ]
];
```

## 🔄 FLUJO COMPLETO

```
Usuario crea pedido en controlador
    ↓
PedidoProduccion::create() - Crear pedido
    ↓
PedidoPrendaService::guardarPrendasEnPedido()
    ├─ PrendaPed::create() - Prenda principal
    ├─ PrendaFotoPed::create() - Fotos de prenda (copia URLs)
    ├─ PrendaTelaPed::create() - Telas/colores
    ├─ PrendaTalaFotoPed::create() - Fotos de telas (copia URLs)
    ├─ PrendaTalaPed::create() - Tallas
    └─ PrendaVariantePed::create() - Variantes
    ↓
PedidoLogoService::guardarLogoEnPedido()
    ├─ LogoPed::create() - Logo principal
    └─ LogoFotoPed::create() - Fotos de logo (copia URLs)
    ↓
Pedido completamente guardado con toda su información
```

## 💾 CONSULTAS ÚTILES

### Obtener pedido con toda su información:
```php
$pedido = PedidoProduccion::with([
    'prendasPed.fotos',
    'prendasPed.telas.fotos',
    'prendasPed.tallas',
    'prendasPed.variantes',
    'logo.fotos',
])->find($id);
```

### Obtener todas las fotos de un pedido:
```php
$fotos = $pedido->prendasPed
    ->flatMap(fn($prenda) => $prenda->fotos)
    ->merge(
        $pedido->prendasPed->flatMap(
            fn($prenda) => $prenda->telas->flatMap(fn($tela) => $tela->fotos)
        )
    )
    ->merge($pedido->logo[0]->fotos ?? [])
    ->all();
```

## ✅ PRÓXIMOS PASOS

1. Registrar servicios en Service Provider
2. Actualizar controladores para llamar a los servicios
3. Actualizar DTOs si es necesario
4. Crear handlers si se sigue patrón DDD completo
5. Tests para verificar guardado correcto
