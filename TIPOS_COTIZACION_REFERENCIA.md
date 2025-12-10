# 📋 TIPOS DE COTIZACIÓN - REFERENCIA

**Fecha:** 10 de Diciembre de 2025
**Estado:** ✅ VERIFICADO

---

## 🎯 TIPOS DE COTIZACIÓN DISPONIBLES

### Tipo 1: PL (Prenda/Logo) ✅
```
ID: 1
Código: PL
Nombre: Prenda/Logo
Descripción: Cotización de prendas con bordado
Activo: ✅

Uso:
- Cuando se cotiza PRENDAS + LOGO/BORDADO
- Incluye fotos de prenda
- Incluye fotos de tela
- Incluye fotos de logo (máximo 5)
```

### Tipo 2: L (Logo) ✅
```
ID: 2
Código: L
Nombre: Logo
Descripción: Cotización de bordado únicamente
Activo: ✅

Uso:
- Cuando se cotiza SOLO LOGO/BORDADO
- NO incluye prendas
- Incluye fotos de logo (máximo 5)
```

### Tipo 3: P (Prenda) ✅
```
ID: 3
Código: P
Nombre: Prenda
Descripción: Cotización de prendas únicamente
Activo: ✅

Uso:
- Cuando se cotiza SOLO PRENDAS
- Incluye fotos de prenda
- Incluye fotos de tela
- NO incluye logo
```

---

## 📊 MAPEO DE IDs

| Código | ID | Nombre | Prendas | Telas | Logo |
|--------|----|---------|---------|---------|----|
| **P** | **3** | Prenda | ✅ | ✅ | ❌ |
| **L** | **2** | Logo | ❌ | ❌ | ✅ |
| **PL** | **1** | Prenda/Logo | ✅ | ✅ | ✅ |

---

## 🔄 FLUJO DE SELECCIÓN

```
USUARIO SELECCIONA TIPO DE COTIZACIÓN
        ↓
¿Qué desea cotizar?
├── Solo Prendas → tipo_cotizacion_id = 3 (P)
├── Solo Logo/Bordado → tipo_cotizacion_id = 2 (L)
└── Prendas + Logo → tipo_cotizacion_id = 1 (PL)
        ↓
FORMULARIO SE ADAPTA
├── Mostrar/Ocultar secciones según tipo
├── Validar campos requeridos
└── Guardar tipo_cotizacion_id en BD
```

---

## 💾 CÓMO USAR EN CÓDIGO

### Obtener tipo por código
```php
$tipo = TipoCotizacion::where('codigo', 'P')->first();
// Resultado: ID = 3

$tipo = TipoCotizacion::where('codigo', 'L')->first();
// Resultado: ID = 2

$tipo = TipoCotizacion::where('codigo', 'PL')->first();
// Resultado: ID = 1
```

### Obtener tipo por ID
```php
$tipo = TipoCotizacion::find(3);
// Resultado: Código = P, Nombre = Prenda

$tipo = TipoCotizacion::find(2);
// Resultado: Código = L, Nombre = Logo

$tipo = TipoCotizacion::find(1);
// Resultado: Código = PL, Nombre = Prenda/Logo
```

### En formulario
```php
$tipos = TipoCotizacion::where('activo', true)->get();
// Resultado: 3 tipos activos

foreach ($tipos as $tipo) {
    // $tipo->id, $tipo->codigo, $tipo->nombre
}
```

---

## 🎯 VALIDACIONES POR TIPO

### Tipo P (Prenda - ID 3)
```
✅ Requiere: prendas
✅ Requiere: telas (fotos)
❌ NO requiere: logo
```

### Tipo L (Logo - ID 2)
```
❌ NO requiere: prendas
❌ NO requiere: telas
✅ Requiere: logo (máximo 5 fotos)
```

### Tipo PL (Prenda/Logo - ID 1)
```
✅ Requiere: prendas
✅ Requiere: telas (fotos)
✅ Requiere: logo (máximo 5 fotos)
```

---

## 📝 GUARDAR COTIZACIÓN

```php
$cotizacion = Cotizacion::create([
    'asesor_id' => Auth::id(),
    'tipo_cotizacion_id' => 3,  // P (Prenda)
    'cliente' => 'Nombre Cliente',
    'asesora' => auth()->user()->name,
    'fecha_inicio' => now(),
    'es_borrador' => true,
    'estado' => 'BORRADOR'
]);
```

---

## 🔗 RELACIÓN EN MODELO

```php
// Cotizacion.php
public function tipoCotizacion(): BelongsTo
{
    return $this->belongsTo(TipoCotizacion::class, 'tipo_cotizacion_id');
}

// Uso
$cotizacion = Cotizacion::find(1);
$tipo = $cotizacion->tipoCotizacion; // Obtener tipo
echo $tipo->nombre; // "Prenda"
```

---

## 🟢 ESTADO

**Tipos Verificados:** ✅ 3 tipos activos
**IDs Confirmados:** ✅ P=3, L=2, PL=1
**Documentación:** ✅ COMPLETADA
**Listo para:** 🚀 USAR EN FORMULARIOS

---

**Referencia creada:** 10 de Diciembre de 2025
**Última actualización:** 10 de Diciembre de 2025
