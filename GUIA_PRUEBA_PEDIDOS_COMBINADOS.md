# 🧪 GUÍA DE PRUEBA: Crear 2 Pedidos Independientes desde Cotizaciones Combinadas

## 📋 PRECONDICIONES

Antes de hacer pruebas, asegúrate de:
- ✅ Base de datos actualizada con la migración de `cantidad`
- ✅ Los cambios en `PedidosProduccionController.php` estén aplicados
- ✅ Los cambios en `crear-pedido-editable.js` estén aplicados
- ✅ Limpiar la caché si es necesario: `php artisan cache:clear`

## 🧪 PASO 1: Preparar Datos de Prueba

### 1.1 - Crear una Cotización COMBINADA (PL) con datos reales

```
URL: /asesor/cotizaciones/crear
Formulario:
  - Cliente: [Seleccionar uno existente]
  - Tipo: Combinada (Prendas + Logo) → Código "PL"
  - Tab PRENDAS: Agregar al menos 2 tallas
    * Talla S: 30 unidades
    * Talla M: 50 unidades
    * Talla L: 20 unidades
  - Tab LOGO: Completar
    * Descripción: "Logo bordado en pecho"
    * Ubicaciones: Seleccionar "Pecho"
    * Técnica: Seleccionar "BORDADO"
    * Fotos: Agregar si hay disponibles
  
Presionar: "GUARDAR COTIZACIÓN"
Resultado esperado:
  ✅ Se crea cotización con tipo_cotizacion_codigo = 'PL'
  ✅ Anotar el NÚMERO DE COTIZACIÓN (ej: COT-00123)
```

### 1.2 - Verificar en BD que la cotización se creó con tipo PL

```sql
-- En MySQL:
SELECT id, numero, tipo_cotizacion_codigo, cliente_id 
FROM cotizaciones 
WHERE numero LIKE 'COT-%' 
ORDER BY id DESC 
LIMIT 5;

-- Debe aparecer tu cotización con tipo_cotizacion_codigo = 'PL'
```

## 🧪 PASO 2: Crear Pedidos desde la Cotización COMBINADA

### 2.1 - Ir a "Crear Pedido" desde la Cotización

```
URL: /asesor/cotizaciones/COT-00123  (tu número)
Botón: "Aceptar Cotización" o "Crear Pedido"

Resultado esperado:
  ✅ Se abre modal con 2 TABS: [PRENDAS] [LOGO]
```

### 2.2 - Verificar que ambos TABS muestren datos

**Tab PRENDAS:**
- ✅ Debe mostrar tabla con:
  - Código de prenda
  - Descripción
  - Colores
  - Tallas (S, M, L, etc.) con cantidades
  - Precio unitario
  - Subtotal

**Tab LOGO:**
- ✅ Debe mostrar tabla/formulario con:
  - Descripción del logo
  - Campos de entrada para especificar cantidad por talla
  - Ubicaciones seleccionables
  - Técnicas
  - Fotos

### 2.3 - Rellenar el Formulario de LOGO (Tab LOGO)

```
En el Tab LOGO:
  1. Campo "Cantidad por Talla":
     - Talla S: 30
     - Talla M: 50
     - Talla L: 20
     → Total debe calcular: 100 automáticamente

  2. Descripción: "Logo bordado uniforme"
  3. Ubicaciones: [✓] Pecho
  4. Técnica: [✓] BORDADO
  5. Observaciones técnicas: "Sin comentarios"
  6. Fotos: [Seleccionar si existen]

Presionar: "CREAR PEDIDO"
```

## 🧪 PASO 3: Verificar Respuesta en Frontend

### 3.1 - Mensaje de Éxito Esperado

```
El navegador debe mostrar un SweetAlert2 con:

┌─────────────────────────────────┐
│          ¡Éxito!                │
├─────────────────────────────────┤
│ Pedidos creados exitosamente    │
│                                 │
│ 📦 Pedido Producción: PED-00045 │
│ 🎨 Pedido Logo: LOGO-00006      │
└─────────────────────────────────┘
       [OK]
```

### 3.2 - Verificar Console (DevTools)

Abrir DevTools (F12) → Pestaña Console y buscar mensajes:

```javascript
// Debe encontrar:
✅ "📦 [LOGO] Cantidad total calculada (suma de tallas): 100"
✅ "🎨 [LOGO] Datos del LOGO pedido a guardar: {...}"
✅ "✅ [LOGO] Respuesta del servidor: {...}"

// En la respuesta del servidor debe ver:
{
  "success": true,
  "numero_pedido_produccion": "PED-00045",
  "numero_pedido_logo": "LOGO-00006"
}
```

### 3.3 - Verificar Redirección

```
Después de hacer click en "OK":
  ✅ Debe redirigir a: /asesores/pedidos
  ✅ La página debe mostrar el listado de pedidos
```

## 🧪 PASO 4: Verificar en Base de Datos

### 4.1 - Verificar que se creó SOLO UN registro en pedidos_produccion

```sql
-- Buscar el pedido de PRENDAS
SELECT id, numero_pedido, cotizacion_id, cliente, forma_de_pago, estado
FROM pedidos_produccion
WHERE numero_pedido LIKE 'PED-%'
ORDER BY id DESC
LIMIT 3;

-- Resultado esperado:
┌────┬───────────────┬──────────────┬──────┬──────┬────────┐
│ id │ numero_pedido │ cotizacion_id│ ...  │ ...  │ estado │
├────┼───────────────┼──────────────┼──────┼──────┼────────┤
│45  │ PED-00045     │ 123          │ ...  │ ...  │pending │
└────┴───────────────┴──────────────┴──────┴──────┴────────┘

⚠️ DEBE EXISTIR SOLO 1 REGISTRO con PED-00045
❌ NO DEBE HABER DUPLICADOS
```

### 4.2 - Verificar que se creó SOLO UN registro en logo_pedidos

```sql
-- Buscar el pedido de LOGO
SELECT id, pedido_id, numero_pedido, cantidad, descripcion, estado
FROM logo_pedidos
WHERE numero_pedido LIKE 'LOGO-%'
ORDER BY id DESC
LIMIT 3;

-- Resultado esperado:
┌────┬──────────┬───────────────┬──────────┬──────────────────────┬────────┐
│ id │ pedido_id│ numero_pedido  │ cantidad │ descripcion          │ estado │
├────┼──────────┼───────────────┼──────────┼──────────────────────┼────────┤
│6   │ 45       │ LOGO-00006    │ 100      │ Logo bordado uniforme│pending │
└────┴──────────┴───────────────┴──────────┴──────────────────────┴────────┘

✅ DEBE EXISTIR EXACTAMENTE 1 REGISTRO
✅ pedido_id DEBE SER 45 (vinculado a pedidos_produccion)
✅ cantidad DEBE SER 100 (suma de tallas: 30+50+20)
✅ descripcion DEBE CONTENER el texto ingresado
```

### 4.3 - Verificar relación entre tablas

```sql
-- Ver ambos pedidos vinculados
SELECT 
  pp.id as pp_id,
  pp.numero_pedido as num_prendas,
  lp.id as lp_id,
  lp.numero_pedido as num_logo,
  lp.cantidad as cant_logo
FROM pedidos_produccion pp
LEFT JOIN logo_pedidos lp ON lp.pedido_id = pp.id
WHERE pp.numero_pedido = 'PED-00045';

-- Resultado esperado:
┌───────┬──────────────┬───────┬──────────────┬───────────┐
│ pp_id │ num_prendas  │ lp_id │ num_logo     │ cant_logo │
├───────┼──────────────┼───────┼──────────────┼───────────┤
│ 45    │ PED-00045    │ 6     │ LOGO-00006   │ 100       │
└───────┴──────────────┴───────┴──────────────┴───────────┘

✅ Ambos registros deben estar presentes
✅ Están correctamente vinculados por pedido_id
```

### 4.4 - Verificar prendas asociadas al pedido de producción

```sql
-- Ver las prendas del pedido
SELECT pp_id, cantidad, talla, descripcion
FROM prendas_pedido
WHERE pedido_id = 45;

-- Resultado esperado: Las prendas que ingresaste
```

### 4.5 - Verificar técnicas y ubicaciones del logo

```sql
-- Ver los datos del logo guardados
SELECT numero_pedido, cantidad, tecnicas, ubicaciones
FROM logo_pedidos
WHERE numero_pedido = 'LOGO-00006';

-- tecnicas debe ser: ["BORDADO"]
-- ubicaciones debe contener: ["Pecho"]
```

## ✅ CHECKLIST DE VALIDACIÓN EXITOSA

- [ ] ✅ Se crea UNA sola entrada en `pedidos_produccion` (no duplicados)
- [ ] ✅ Se crea UNA sola entrada en `logo_pedidos` (no duplicados)
- [ ] ✅ El campo `cantidad` en `logo_pedidos` contiene la suma correcta (30+50+20=100)
- [ ] ✅ El campo `pedido_id` en `logo_pedidos` vincula correctamente al `id` de `pedidos_produccion`
- [ ] ✅ Se muestra mensaje con ambos números: "PED-xxxxx" y "LOGO-xxxxx"
- [ ] ✅ No hay errores en la consola (Console del DevTools)
- [ ] ✅ Los datos se guardaron correctamente en la BD
- [ ] ✅ Se redirige a `/asesores/pedidos` después del éxito
- [ ] ✅ Al entrar en el pedido, se ven AMBOS (producción y logo)

## ❌ PROBLEMAS Y SOLUCIONES

### Problema: Se crea 2 veces en pedidos_produccion

**Síntomas:**
- `pedidos_produccion` tiene 2 registros con números parecidos
- `logo_pedidos` está vacío

**Causa:** El código anterior estaba creando automáticamente en ambas tablas

**Solución:**
1. Verificar que `crearDesdeCotizacion()` NO cree `logo_pedido`
2. Verificar que `guardarLogoPedido()` tenga la lógica de CREATE vs UPDATE

```php
// En guardarLogoPedido(), debe tener:
if (!$logoPedidoExistente) {
    // CREAR nuevo
    DB::table('logo_pedidos')->insertGetId([...]);
} else {
    // ACTUALIZAR existente
    DB::table('logo_pedidos')->where('id', $pedidoId)->update([...]);
}
```

### Problema: No se calcula la cantidad correctamente

**Síntomas:**
- Campo `cantidad` en `logo_pedidos` es 0
- O no coincide con la suma

**Causa:** El JavaScript no está capturando correctamente las tallas

**Solución:**
```javascript
// En crear-pedido-editable.js, verificar que:
let cantidadTotal = 0;
// Sumar todas las tallas del tab LOGO
const tallaInputs = document.querySelectorAll('[data-talla]');
tallaInputs.forEach(input => {
    cantidadTotal += parseInt(input.value) || 0;
});
```

### Problema: El número de LOGO no se genera

**Síntomas:**
- Campo `numero_pedido` en `logo_pedidos` está vacío

**Causa:** La función `generarNumeroLogoPedido()` no existe

**Solución:**
```php
// Debe existir en el Controller:
private function generarNumeroLogoPedido()
{
    $lastLogoPedido = DB::table('logo_pedidos')
        ->where('numero_pedido', 'LIKE', 'LOGO-%')
        ->orderByDesc('id')
        ->first();
    
    $numero = $lastLogoPedido ? 
              (int)str_replace('LOGO-', '', $lastLogoPedido->numero_pedido) + 1 : 1;
    
    return 'LOGO-' . str_pad($numero, 5, '0', STR_PAD_LEFT);
}
```

### Problema: Error "Column not found: cantidad"

**Síntomas:**
- Error en la migración o en BD

**Causa:** La migración no se ejecutó

**Solución:**
```bash
# Ejecutar las migraciones pendientes
php artisan migrate

# Si la tabla ya existe, puedes verificar:
php artisan migrate:fresh  # ⚠️ SOLO EN DESARROLLO
```

## 📊 COMPARACIÓN ANTES vs DESPUÉS

### ANTES (INCORRECTO)
```
POST /crear-desde-cotizacion
  ↓
  ✗ Crea en pedidos_produccion (prendas)
  ✗ Crea TAMBIÉN en logo_pedidos (DUPLICADO)
  ↓
POST /guardar-logo-pedido
  ↓
  ✗ Intenta actualizar pero ya existe
  ↓
RESULTADO: 2 en pedidos_produccion, 1 en logo_pedidos (INCORRECTO)
```

### DESPUÉS (CORRECTO)
```
POST /crear-desde-cotizacion
  ↓
  ✅ Crea SOLO en pedidos_produccion (prendas)
  ✅ Devuelve: {pedido_id: 45, es_combinada: true}
  ↓
POST /guardar-logo-pedido (con pedido_id: 45)
  ↓
  ✅ NO encuentra logo_pedido con id=45
  ✅ CREA nuevo en logo_pedidos
  ✅ Vincula con pedido_id = 45
  ↓
RESULTADO: 1 en pedidos_produccion, 1 en logo_pedidos (CORRECTO)
```

## 🎯 LOGS ESPERADOS EN EL SERVIDOR

Si habilitaste logs, debes ver:

```
[INFO] 📦 [crearDesdeCotizacion] Creando pedido desde cotización
[INFO] ✅ Pedido de PRENDAS creado: PED-00045
[INFO] ✅ [crearDesdeCotizacion] Indicador es_combinada: true
[INFO] 🎨 [guardarLogoPedido] Guardando datos de LOGO
[INFO] 🎨 [guardarLogoPedido] CREANDO nuevo registro en logo_pedidos (COMBINADA PL)
[INFO] ✅ [guardarLogoPedido] Nuevo logo_pedido creado: LOGO-00006
```

## 📞 SOPORTE

Si algo no funciona correctamente:

1. **Verifica los logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Limpia caché:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

3. **Revisa DevTools Console (F12)**
   - Busca errores de JavaScript
   - Verifica que los datos se envíen correctamente

4. **Consulta directamente la BD:**
   ```sql
   SELECT * FROM pedidos_produccion WHERE numero_pedido LIKE 'PED-%' ORDER BY id DESC LIMIT 5;
   SELECT * FROM logo_pedidos WHERE numero_pedido LIKE 'LOGO-%' ORDER BY id DESC LIMIT 5;
   ```

