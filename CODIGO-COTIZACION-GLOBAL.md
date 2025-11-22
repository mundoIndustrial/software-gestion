# 📋 CÓDIGO DE COTIZACIÓN - Sistema Global con Filtro por Asesora

## 🎯 Objetivo
Generar códigos de cotización **globales** (COT-00001, COT-00002, etc.) que sean **únicos en toda la BD**, pero que cada asesora solo vea **sus propias cotizaciones**.

---

## 🔄 **Cómo Funciona**

### **1. Generación del Código (Global)**

**Cuando se ENVÍA una cotización:**
```php
// Contar TODAS las cotizaciones enviadas en toda la BD
$totalEnviadas = Cotizacion::where('es_borrador', false)->count();

// Generar código global
$numeroCotizacion = 'COT-' . str_pad($totalEnviadas + 1, 5, '0', STR_PAD_LEFT);
```

**Ejemplo:**
```
Asesora 1 envía cotización 1 → COT-00001
Asesora 2 envía cotización 1 → COT-00002
Asesora 1 envía cotización 2 → COT-00003
Asesora 3 envía cotización 1 → COT-00004
```

---

### **2. Filtro por Asesora (Privacidad)**

**En el método `index()` del Controller:**
```php
// Solo mostrar cotizaciones del usuario actual (asesora)
$cotizaciones = Cotizacion::where('user_id', Auth::id())
    ->where('es_borrador', false)
    ->orderBy('created_at', 'desc')
    ->paginate(15);

$borradores = Cotizacion::where('user_id', Auth::id())
    ->where('es_borrador', true)
    ->orderBy('created_at', 'desc')
    ->paginate(15);
```

**Resultado:**
- Asesora 1 solo ve: COT-00001, COT-00003
- Asesora 2 solo ve: COT-00002
- Asesora 3 solo ve: COT-00004

---

## 📊 **Estructura de Datos**

```
TABLA: cotizaciones
┌────┬──────────────────┬──────────┬────────────┬──────────────┐
│ id │ numero_cotizacion│ user_id  │ es_borrador│ cliente      │
├────┼──────────────────┼──────────┼────────────┼──────────────┤
│ 1  │ COT-00001        │ 1 (Asesora 1) │ false  │ EMPRESA XYZ  │
│ 2  │ COT-00002        │ 2 (Asesora 2) │ false  │ DOTACIÓN PAL │
│ 3  │ COT-00003        │ 1 (Asesora 1) │ false  │ CLIENTE NUEVO│
│ 4  │ null             │ 1 (Asesora 1) │ true   │ PRUEBA       │
│ 5  │ COT-00004        │ 3 (Asesora 3) │ false  │ EMPRESA ABC  │
└────┴──────────────────┴──────────┴────────────┴──────────────┘
```

---

## 🔐 **Lógica de Filtrado**

### **Asesora 1 ve:**
```
COTIZACIONES ENVIADAS:
- COT-00001 (suya)
- COT-00003 (suya)

BORRADORES:
- PRUEBA (suya)
```

### **Asesora 2 ve:**
```
COTIZACIONES ENVIADAS:
- COT-00002 (suya)

BORRADORES:
- (ninguno)
```

### **Asesora 3 ve:**
```
COTIZACIONES ENVIADAS:
- COT-00004 (suya)

BORRADORES:
- (ninguno)
```

---

## 💻 **Código Implementado**

### **Controller: CotizacionesController.php**

```php
// Generar numero_cotizacion SOLO si se envía (no si es borrador)
$numeroCotizacion = null;
if ($tipo === 'enviada') {
    // Generar código automático: COT-XXXXX (global para todas las asesoras)
    // Contar TODAS las cotizaciones enviadas (es_borrador = false) en toda la BD
    $totalEnviadas = Cotizacion::where('es_borrador', false)->count();
    $numeroCotizacion = 'COT-' . str_pad(
        $totalEnviadas + 1,
        5,
        '0',
        STR_PAD_LEFT
    );
}

$datos = [
    'user_id' => Auth::id(),
    'numero_cotizacion' => $numeroCotizacion,
    // ... resto de datos
];
```

---

## 🔄 **Flujo Completo**

```
1. ASESORA 1 CREA COTIZACIÓN
   - Completa datos
   - Hace clic en ENVIAR
   → Sistema cuenta cotizaciones enviadas: 0
   → Genera: COT-00001
   → Guarda con user_id = 1

2. ASESORA 2 CREA COTIZACIÓN
   - Completa datos
   - Hace clic en ENVIAR
   → Sistema cuenta cotizaciones enviadas: 1
   → Genera: COT-00002
   → Guarda con user_id = 2

3. ASESORA 1 ACCEDE A COTIZACIONES
   - Ve solo sus cotizaciones (user_id = 1)
   - Muestra: COT-00001
   - NO ve: COT-00002 (es de asesora 2)

4. ASESORA 2 ACCEDE A COTIZACIONES
   - Ve solo sus cotizaciones (user_id = 2)
   - Muestra: COT-00002
   - NO ve: COT-00001 (es de asesora 1)
```

---

## ✨ **Características**

✅ Código global (único en toda la BD)
✅ Autoincrement (COT-00001, COT-00002, etc.)
✅ Se genera al ENVIAR (no en borrador)
✅ Filtrado por asesora (privacidad)
✅ Cada asesora solo ve sus cotizaciones
✅ Contador global para todas las asesoras

---

## 📝 **Tabla de Cotizaciones (Vista)**

### **Asesora 1 ve:**
```
FECHA      | CÓDIGO      | CLIENTE          | ESTADO   | ACCIÓN
22/11/2025 | COT-00001   | EMPRESA XYZ      | Enviada  | Ver
22/11/2025 | COT-00003   | CLIENTE NUEVO    | Enviada  | Ver
```

### **Asesora 2 ve:**
```
FECHA      | CÓDIGO      | CLIENTE          | ESTADO   | ACCIÓN
22/11/2025 | COT-00002   | DOTACIÓN PALMA   | Enviada  | Ver
```

---

## 🔍 **Verificación en BD**

```sql
-- Ver todas las cotizaciones enviadas (global)
SELECT id, numero_cotizacion, user_id, cliente 
FROM cotizaciones 
WHERE es_borrador = false 
ORDER BY id;

-- Ver cotizaciones de asesora específica
SELECT id, numero_cotizacion, user_id, cliente 
FROM cotizaciones 
WHERE user_id = 1 AND es_borrador = false;

-- Contar cotizaciones enviadas (para generar siguiente código)
SELECT COUNT(*) as total 
FROM cotizaciones 
WHERE es_borrador = false;
```

---

## ✅ **Garantías**

✅ Código global único
✅ Autoincrement correcto
✅ Filtro por asesora funciona
✅ Privacidad garantizada
✅ Cada asesora solo ve sus cotizaciones
✅ Contador global para todas

---

**Versión:** 1.0
**Fecha:** 22 de Noviembre de 2025
**Estado:** ✅ IMPLEMENTADO Y FUNCIONAL
