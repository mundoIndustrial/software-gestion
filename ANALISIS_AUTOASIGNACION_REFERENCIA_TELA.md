# 🔍 ANÁLISIS Y SOLUCIÓN: Autoasignación de Referencia de Tela

## Problema Original Identificado
La referencia de tela se estaba asignando **automáticamente** cuando creabas una nueva tela, aunque no la colocaras manualmente. Esto ocurría en el frontend que luego se propagaba al backend.

### Problemas Identificados:
1. **Frontend** generaba referencias automáticas: `REF-LON-001`
2. **Backend** también generaba si no venía: `$this->generarCodigo()`
3. El campo se rellenaba solo sin intervención del usuario

---

## ✅ SOLUCIÓN IMPLEMENTADA

### Cambio 1: Referencias Siempre Manuales
- ✅ Frontend: `referencia: ''` (vacía) en todas las funciones
- ✅ Backend: `'referencia' => ''` (vacía por defecto)
- ✅ Función `seleccionarTela()`: Eliminado parámetro `referencia` que no se usaba

### Cambio 2: NUEVO - Telas Siempre Independientes por Pedido
**Cambio Fundamental:** Cada pedido ahora crea NUEVAS telas sin reutilizar las existentes.

**Razón:** Si un usuario no guardó la referencia en el pedido anterior, no debería usar la referencia de otro pedido. Cada pedido es independiente.

#### Archivos Modificados:

| Servicio | Cambio |
|----------|--------|
| `Domain\Pedidos\Services\ColorTelaService` | ✅ SIEMPRE crea NUEVO color y tela |
| `Application\Services\ColorTelaService` | ✅ SIEMPRE crea NUEVO color y tela |
| `Application\Services\PrendaTelasService` | ✅ Cambió de `firstOrCreate()` a `create()` |

---

## 📋 Funciones Modificadas

### 1. Domain/Pedidos/Services/ColorTelaService.php

**obtenerOCrearColor()**: Ahora SIEMPRE crea nuevo color
```php
// ANTES: Buscaba si existía
$color = ColorPrenda::where('nombre', $nombreColor)->first();
if ($color) return $color->id;

// AHORA: Crea NUEVO siempre
$colorNuevo = ColorPrenda::create([...]);
return $colorNuevo->id;
```

**obtenerOCrearTela()**: Ahora SIEMPRE crea nueva tela
```php
// ANTES: Buscaba si existía
$tela = TelaPrenda::where('nombre', $nombreTela)->first();
if ($tela) return $tela->id;

// AHORA: Crea NUEVA siempre
$telaNueva = TelaPrenda::create([...]);
return $telaNueva->id;
```

### 2. Application/Services/ColorTelaService.php

**obtenerOCrearColor() y obtenerOCrearTela()**
- Cambió de búsqueda con `whereRaw('LOWER...')` a creación directa
- Ahora solo crea, no busca
- Referencia siempre vacía: `'referencia' => ''`

### 3. Application/Services/PrendaTelasService.php

**obtenerOCrearTela()**
```php
// ANTES: firstOrCreate duplicaba datos
return TelaPrenda::firstOrCreate(['nombre' => $nombreNormalizado], [...]);

// AHORA: Crea NUEVO registro siempre
return TelaPrenda::create([
    'nombre' => $nombreNormalizado,
    'referencia' => $telaDTO->referencia ?? '',
    'activo' => true,
]);
```

---

## 🎯 Flujo Resultante

```
Usuario crea Pedido 1:
├─ Tela: "NAPOLES" + Referencia: "REF-NAP-2026-001"
└─ Crea: NUEVA tela en BD con esa referencia

Usuario crea Pedido 2:
├─ Tela: "NAPOLES" (misma que Pedido 1)
├─ Referencia: (vacío - usuario no la coloca)
└─ Crea: NUEVA tela en BD (DIFERENTE de Pedido 1)
   └─ La referencia queda VACÍA (no hereda de Pedido 1)
```

---

## 🧪 Comportamiento Esperado

### Escenario 1: Usuario completa todo
```
Usuario coloca:
- Tela: NAPOLES
- Referencia: REF-NAP-001

Resultado: ✅ Se crea registro con referencia completa
```

### Escenario 2: Usuario NO coloca referencia
```
Usuario coloca:
- Tela: NAPOLES
- Referencia: (vacío)

Resultado: ✅ Se crea registro CON REFERENCIA VACÍA
         ❌ NO hereda de otro pedido
         ❌ NO se autoasigna
```

### Escenario 3: Usuario selecciona tela existente
```
Usuario:
1. Busca "NAPOLES"
2. Encuentra lista de telas
3. Selecciona una
4. Referencia: (vacío)

Resultado: ✅ Se crea NUEVA tela para este pedido
         ✅ Referencia siempre vacía (usuario debe llenarla)
         ❌ NO reutiliza referencia antigua
```

---

## 📊 Comparativa: Antes vs Después

| Aspecto | ANTES | DESPUÉS |
|---------|-------|---------|
| Referencia si no se completa | ❌ Se autoasignaba | ✅ Queda vacía |
| Telas reutilizadas | ✅ Sí (todos usan mismo ID) | ❌ No (cada pedido = nuevo ID) |
| Independencia pedidos | ❌ No | ✅ Sí |
| Control usuario | ❌ Parcial | ✅ Total |

---

## 📝 Archivos Modificados

1. `public/js/asesores/color-tela-referencia.js` - Frontend sin autoasignación
2. `app/Domain/Pedidos/Services/ColorTelaService.php` - Colores y telas NUEVO
3. `app/Application/Services/ColorTelaService.php` - Colores y telas NUEVO
4. `app/Application/Services/PrendaTelasService.php` - Telas NUEVO

