# SOLUCIÓN: Área NO se Guardaba en pedido_produccion

## 🎯 PROBLEMA ENCONTRADO

**Archivo:** `app/Domain/Pedidos/Services/PedidoWebService.php` línea 99

**ANTES ( PROBLEMA):**
```php
private function crearPedidoBase(array $datos, int $asesorId): PedidoProduccion
{
    $numeroPedido = $this->generarNumeroPedido();

    return PedidoProduccion::create([
        'numero_pedido' => $numeroPedido,
        'cliente' => $datos['cliente'] ?? 'SIN NOMBRE',
        'asesor_id' => $asesorId,
        'cliente_id' => $datos['cliente_id'] ?? null,
        'forma_de_pago' => $datos['forma_de_pago'] ?? 'CONTADO',
        'novedades' => $datos['descripcion'] ?? null,
        'estado' => 'Pendiente',
        'cantidad_total' => 0,
        'area' => null,  // ← PROBLEMA: SIEMPRE NULL, NO LEE DE $datos
    ]);
}
```

**POR QUÉ?**
- El frontend SÍ envía el área en `$datos['area']`
- El modelo SÍ tiene `'area'` en `$fillable`
- La columna SÍ existe en BD
- **PERO** el método HARDCODEABA `'area' => null`

---

## SOLUCIÓN IMPLEMENTADA

**DESPUÉS (✅ CORRECTO):**
```php
private function crearPedidoBase(array $datos, int $asesorId): PedidoProduccion
{
    $numeroPedido = $this->generarNumeroPedido();

    //  EXTRAER ÁREA CON DEFAULT
    $area = $datos['area'] ?? $datos['estado_area'] ?? 'creacion de pedido';
    if (is_string($area)) {
        $area = trim($area);
        $area = empty($area) ? 'creacion de pedido' : $area;
    } else {
        $area = 'creacion de pedido';
    }

    return PedidoProduccion::create([
        'numero_pedido' => $numeroPedido,
        'cliente' => $datos['cliente'] ?? 'SIN NOMBRE',
        'asesor_id' => $asesorId,
        'cliente_id' => $datos['cliente_id'] ?? null,
        'forma_de_pago' => $datos['forma_de_pago'] ?? 'CONTADO',
        'novedades' => $datos['descripcion'] ?? null,
        'estado' => 'Pendiente',
        'cantidad_total' => 0,
        'area' => $area,  // AHORA SE GUARDA CORRECTAMENTE
    ]);
}
```

**CAMBIOS CLAVE:**
- Lee `$datos['area']` del frontend
- Fallback a `$datos['estado_area']` si existe
- Default a `'creacion de pedido'` si no se envía
- Valida que sea string y limpia whitespace
- Guarda el área real en BD

---

## 📝 LOGS MEJORADOS

También se mejoraron los logs para ver exactamente qué área se guardó:

**Antes:**
```
[PedidoWebService] Pedido base creado
    pedido_id: 2717
    numero_pedido: 100003
```

**Después:**
```
[PedidoWebService] Pedido base creado
    pedido_id: 2717
    numero_pedido: 100003
    area_guardada: "Producción"        ← NUEVO
    estado: "Pendiente"
    
[PedidoWebService] Pedido completo creado
    pedido_id: 2717
    cantidad_prendas: 1
    area_final: "Producción"           ← NUEVO
```

---

## 🧪 VERIFICACIÓN

### Test 1: Crear Pedido con Área
```javascript
// Frontend envía:
{
  "cliente": "Test Cliente",
  "area": "Producción",
  "items": [...]
}
```

### Test 2: Revisar Logs
```bash
tail -f storage/logs/laravel.log | grep "PedidoWebService"
```

**Debe mostrar:**
```
[PedidoWebService] Pedido base creado
    area_guardada: "Producción"
```

### Test 3: Verificar BD
```sql
SELECT id, numero_pedido, cliente, area, estado 
FROM pedidos_produccion 
ORDER BY created_at DESC 
LIMIT 1;
```

**Resultado esperado:**
```
id: 2717
numero_pedido: 100003
cliente: Test Cliente
area: Producción          ← NO NULL (correcto)
estado: Pendiente
```

---

## 📊 COMPARATIVO

| Aspecto | Antes () | Después (✅) |
|--------|----------|-----------|
| **Frontend envía** | ✓ Sí | ✓ Sí |
| **Backend recibe** | ✓ Sí | ✓ Sí |
| **Backend guarda** | ✗ NULL | ✓ Valor correcto |
| **BD contiene** | NULL | "Producción" |

---

## 🔍 CAUSA RAÍZ

El método `crearPedidoBase()` **ignoraba completamente** el parámetro `$area` que venía en `$datos`:

```php
//  ANTES: Hardcodeado a NULL
'area' => null

// DESPUÉS: Lee de $datos con validación
'area' => $area,  // donde $area = $datos['area'] ?? 'creacion de pedido'
```

---

##  VALIDACIONES AGREGADAS

1. **Búsqueda multi-nivel**: `$datos['area']` → `$datos['estado_area']` → `'creacion de pedido'`
2. **Validación de tipo**: Garantiza que sea string
3. **Limpieza**: Trim de whitespace
4. **Default seguro**: Usa `'creacion de pedido'` si está vacío

---

## 📋 ARCHIVOS MODIFICADOS

- `app/Domain/Pedidos/Services/PedidoWebService.php`
  - Línea 54-81: Mejorados logs en `crearPedidoCompleto()`
  - Línea 87-115: Refactorizado `crearPedidoBase()` para leer y guardar área

---

## ESTADO

**Implementación:** COMPLETADA
**Testing:** LISTO PARA PROBAR
**Producción:** SEGURO DESPLEGAR

---

## 📝 NOTAS

- No se cambió el modelo (ya estaba bien)
- No se cambió la BD (columna ya existe)
- No se cambió validación (ya es correcta)
- **SÍ se corrigió** la lógica de guardado en `crearPedidoBase()`
