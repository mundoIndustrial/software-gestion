# ✅ INTEGRACIÓN COMPLETA - DDD PEDIDOS CON PRENDAS Y LOGOS

## 📊 ESTADO ACTUAL

Cuando una asesora crea un pedido, **AHORA SÍ** se guardan TODAS las prendas y logos en las nuevas tablas normalizadas automáticamente.

## 🔄 FLUJO COMPLETO DE GUARDADO

```
Asesora crea un pedido desde cotización
    ↓
Solicitud HTTP llega a PedidoProduccionController
    ↓
Controller valida y crea CrearPedidoProduccionDTO
    ↓
Inyecta PedidoProduccionCreatorService
    ↓
CrearPedidoProduccionCreatorService ejecuta CrearPedidoProduccionJob (sincrónico)
    ↓
Dentro de la transacción DB:
    ├─ Obtener número de pedido secuencial (con lock)
    ├─ Crear PedidoProduccion en tabla
    ├─ PedidoPrendaService::guardarPrendasEnPedido()
    │  ├─ PrendaPed::create()
    │  ├─ PrendaFotoPed::create() (copia URLs)
    │  ├─ PrendaTelaPed::create()
    │  ├─ PrendaTalaFotoPed::create() (copia URLs)
    │  ├─ PrendaTalaPed::create()
    │  └─ PrendaVariantePed::create()
    └─ PedidoLogoService::guardarLogoEnPedido()
       ├─ LogoPed::create()
       └─ LogoFotoPed::create() (copia URLs)
    ↓
Todo guardado en una sola transacción ✅
```

## 📁 ARCHIVOS MODIFICADOS

### Service Provider
- ✅ `app/Providers/PedidosServiceProvider.php`
  - Registró `PedidoPrendaService`
  - Registró `PedidoLogoService`

### Job
- ✅ `app/Jobs/CrearPedidoProduccionJob.php`
  - Inyecta `PedidoPrendaService`
  - Inyecta `PedidoLogoService`
  - Llama a guardado de prendas y logo

### DTO
- ✅ `app/DTOs/CrearPedidoProduccionDTO.php`
  - Agregó campo `logo` (opcional)

## 📋 TABLAS QUE SE LLENAN AL CREAR UN PEDIDO

### Tabla Principal
- `pedidos_produccion` ✅

### Prendas
- `prendas_ped` ✅
- `prenda_fotos_ped` ✅ (copia URLs)
- `prenda_telas_ped` ✅
- `prenda_tela_fotos_ped` ✅ (copia URLs)
- `prenda_tallas_ped` ✅
- `prenda_variantes_ped` ✅

### Logo
- `logo_ped` ✅
- `logo_fotos_ped` ✅ (copia URLs)

## 💡 ESTRUCTURA DE DATOS ESPERADA

### Desde frontend (JSON que envía la asesora):

```json
{
  "cotizacion_id": 123,
  "prendas": [
    {
      "nombre_producto": "CAMISA DRILL",
      "descripcion": "Camisa de trabajo",
      "cantidad": 100,
      "fotos": [
        {
          "ruta_original": "storage/fotos/prenda_1.jpg",
          "ruta_webp": "storage/fotos/prenda_1.webp",
          "orden": 1
        }
      ],
      "telas": [
        {
          "color_id": 1,
          "tela_id": 5,
          "fotos": [
            {
              "ruta_original": "storage/telas/tela_1.jpg",
              "orden": 1
            }
          ]
        }
      ],
      "tallas": [
        { "talla": "S", "cantidad": 50 },
        { "talla": "M", "cantidad": 30 },
        { "talla": "L", "cantidad": 20 }
      ],
      "variantes": [
        {
          "tipo_prenda": "CAMISA",
          "tipo_manga_id": 2,
          "tiene_bolsillos": true,
          "obs_bolsillos": "Pecho",
          "tiene_reflectivo": false
        }
      ]
    }
  ],
  "logo": {
    "descripcion": "Logo bordado en espalda",
    "ubicacion": "Espalda",
    "fotos": [
      {
        "ruta_original": "storage/logos/logo_1.jpg",
        "orden": 1
      }
    ]
  }
}
```

## 🔐 CARACTERÍSTICAS DE SEGURIDAD

✅ **Transacción única**: Todo se guarda o nada si hay error
✅ **Lock para números secuenciales**: Evita duplicados
✅ **Sin duplicación de fotos**: Copia URLs de cotizaciones
✅ **Rollback automático**: Si falla algo, todo se revierte
✅ **Logging**: Registra éxito y errores

## 📊 CONSULTAS ÚTILES EN LARAVEL

### Obtener pedido completo con todas las relaciones:
```php
$pedido = PedidoProduccion::with([
    'prendasPed' => function($q) {
        $q->with('fotos', 'telas.fotos', 'tallas', 'variantes');
    },
    'logo.fotos'
])->find($id);

// Acceso a datos:
foreach ($pedido->prendasPed as $prenda) {
    echo $prenda->nombre_producto;
    echo $prenda->cantidad;
    
    foreach ($prenda->fotos as $foto) {
        echo $foto->ruta_original;
    }
    
    foreach ($prenda->telas as $tela) {
        foreach ($tela->fotos as $telaFoto) {
            echo $telaFoto->ruta_original;
        }
    }
    
    foreach ($prenda->tallas as $talla) {
        echo $talla->talla . ': ' . $talla->cantidad;
    }
}

if ($pedido->logo) {
    foreach ($pedido->logo as $logo) {
        foreach ($logo->fotos as $foto) {
            echo $foto->ruta_original;
        }
    }
}
```

### Obtener todas las imágenes de un pedido:
```php
$imagenes = collect()
    ->merge($pedido->prendasPed->flatMap(fn($p) => $p->fotos))
    ->merge($pedido->prendasPed->flatMap(fn($p) => $p->telas->flatMap(fn($t) => $t->fotos)))
    ->merge($pedido->logo->flatMap(fn($l) => $l->fotos) ?? []);
```

## ✅ PRÓXIMOS PASOS (OPCIONAL)

1. **Handlers DDD adicionales**: Si quieres aún más abstracción
2. **Tests**: Crear tests para verificar guardado
3. **API JSON**: Documentar estructura esperada
4. **Eventos Domain**: Disparar eventos cuando se crea pedido
5. **Notificaciones**: Alertar cuando se completa

## 🎯 CONCLUSIÓN

**Ahora el sistema DDD está completo:**
- ✅ Prendas se guardan automáticamente en `prendas_ped`
- ✅ Logos se guardan automáticamente en `logo_ped`
- ✅ Fotos se copian desde cotizaciones (sin duplicar)
- ✅ Todo en una sola transacción atómica
- ✅ Principios SOLID respetados
- ✅ Fácil de mantener y extender
