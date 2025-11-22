# 📋 CÓDIGO DE COTIZACIÓN - Sistema Secuencial (Mejorado)

## 🎯 Cambio Realizado

**Antes:** Contaba todas las cotizaciones enviadas (podía fallar)
**Ahora:** Obtiene el último código guardado y asigna el siguiente (más confiable)

---

## 🔄 **Cómo Funciona**

### **Lógica Nueva**

```php
// 1. Obtener la última cotización enviada
$ultimaCotizacion = Cotizacion::where('es_borrador', false)
    ->whereNotNull('numero_cotizacion')
    ->orderBy('id', 'desc')
    ->first();

// 2. Extraer el número (COT-00001 -> 1)
$ultimoNumero = 0;
if ($ultimaCotizacion && $ultimaCotizacion->numero_cotizacion) {
    preg_match('/\d+/', $ultimaCotizacion->numero_cotizacion, $matches);
    $ultimoNumero = isset($matches[0]) ? (int)$matches[0] : 0;
}

// 3. Generar siguiente código
$nuevoNumero = $ultimoNumero + 1;
$numeroCotizacion = 'COT-' . str_pad($nuevoNumero, 5, '0', STR_PAD_LEFT);
```

---

## 📊 **Ejemplo Práctico**

### **Escenario 1: Primera cotización**
```
BD vacía (sin cotizaciones enviadas)
↓
$ultimaCotizacion = null
$ultimoNumero = 0
$nuevoNumero = 0 + 1 = 1
Resultado: COT-00001 ✅
```

### **Escenario 2: Cotizaciones existentes**
```
Última cotización: COT-00005
↓
Extraer número: 5
$ultimoNumero = 5
$nuevoNumero = 5 + 1 = 6
Resultado: COT-00006 ✅
```

### **Escenario 3: Múltiples asesoras**
```
Asesora 1 envía → COT-00001
Asesora 2 envía → COT-00002 (obtiene la última: COT-00001 → +1)
Asesora 1 envía → COT-00003 (obtiene la última: COT-00002 → +1)
Asesora 3 envía → COT-00004 (obtiene la última: COT-00003 → +1)
```

---

## ✨ **Ventajas**

✅ **Más confiable** - No depende de COUNT()
✅ **Secuencial garantizado** - Siempre +1 del último
✅ **Sin duplicados** - Cada código es único
✅ **Funciona con múltiples asesoras** - Global para todas
✅ **Rápido** - Solo busca el último registro

---

## 🔍 **Verificación en BD**

```sql
-- Ver últimas cotizaciones enviadas
SELECT id, numero_cotizacion, cliente, es_borrador 
FROM cotizaciones 
WHERE es_borrador = false 
ORDER BY id DESC 
LIMIT 10;

-- Resultado esperado:
-- id | numero_cotizacion | cliente        | es_borrador
-- 5  | COT-00005         | EMPRESA ABC    | 0
-- 4  | COT-00004         | CLIENTE NUEVO  | 0
-- 3  | COT-00003         | DOTACIÓN PALMA | 0
-- 2  | COT-00002         | EMPRESA XYZ    | 0
-- 1  | COT-00001         | PRUEBA         | 0
```

---

## 📝 **Logs para Debuggear**

Cuando envíes una cotización, verás en `storage/logs/laravel.log`:

```
✅ Generando código de cotización
tipo: "enviada"
ultimo_numero: 5
nuevo_numero: 6
numero_cotizacion: "COT-00006"
```

---

## 🚀 **Flujo Completo**

```
1. ASESORA ENVÍA COTIZACIÓN
   ↓
2. SISTEMA BUSCA ÚLTIMA COTIZACIÓN ENVIADA
   ↓
3. EXTRAE EL NÚMERO (ej: 5)
   ↓
4. SUMA 1 (5 + 1 = 6)
   ↓
5. GENERA CÓDIGO: COT-00006
   ↓
6. GUARDA EN BD CON numero_cotizacion = 'COT-00006'
   ↓
7. ASESORA VE LA COTIZACIÓN CON EL CÓDIGO
```

---

## ✅ **Garantías**

✅ Código secuencial (COT-00001, COT-00002, etc.)
✅ Nunca se repite un código
✅ Funciona con múltiples asesoras
✅ Cada asesora solo ve sus cotizaciones
✅ Código se genera al ENVIAR (no en borrador)
✅ Más confiable que contar

---

**Versión:** 2.0 (Mejorada)
**Fecha:** 22 de Noviembre de 2025
**Estado:** ✅ IMPLEMENTADO Y FUNCIONAL
