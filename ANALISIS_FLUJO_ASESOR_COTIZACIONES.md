# 📊 ANÁLISIS COMPLETO: CÓMO UN ASESOR CREA COTIZACIONES

**Fecha:** 14 de Diciembre de 2025  
**Análisis:** Flujo completo de creación de cotizaciones desde perspectiva del asesor

---

## 🔄 FLUJO ACTUAL (CÓMO LO ESTÁ HACIENDO TU SISTEMA)

### 1️⃣ EL ASESOR INICIA LA COTIZACIÓN

```
Asesor logueado en el sistema
    ↓
Accede a /cotizaciones-prenda/crear
    ↓
Ve formulario en blanco
```

**Rutas usadas:**
- `GET /cotizaciones-prenda/crear` → `CotizacionPrendaController::create()`
- `GET /cotizaciones-bordado/crear` → `CotizacionBordadoController::create()`

---

### 2️⃣ RELLENA EL FORMULARIO

El asesor completa estos campos:

#### **INFORMACIÓN BÁSICA**
```
┌─ Tipo de Cotización
│  ├─ Prenda (M/P/G)
│  └─ Bordado/Logo
│
├─ Cliente
│  ├─ Selecciona de lista existente
│  └─ O crea uno nuevo (autocomplete)
│
├─ Tipo de Venta (solo Prenda)
│  ├─ M (Muestra)
│  ├─ P (Prototipo)
│  └─ G (Grande)
│
└─ Observaciones
   ├─ Técnicas
   └─ Generales
```

#### **PRENDAS (Solo Prenda)**
```
Para cada prenda:
├─ Nombre de la prenda
├─ Cantidad
├─ Tallas disponibles
├─ Telas (con foto de tela)
├─ Colores
├─ Variantes (manga, broche, etc.)
├─ Ubicaciones de técnicas
├─ Fotos de la prenda (UP TO 5)
└─ Especificaciones
```

#### **TÉCNICAS Y UBICACIONES**
```
Datos técnicos por prenda:
├─ Técnicas aplicadas (bordado, estampado, etc.)
├─ Ubicación en la prenda
├─ Observaciones técnicas
└─ Referencias de color
```

---

### 3️⃣ GUARDA O ENVÍA

El asesor tiene DOS opciones:

#### **OPCIÓN A: Guardar como BORRADOR**
```
Click en "Guardar Borrador"
    ↓
POST /cotizaciones-prenda
    ├─ action = "borrador"
    ├─ estado = "BORRADOR"
    ├─ numero_cotizacion = NULL ⚠️
    └─ es_borrador = true
    ↓
✅ Se guarda pero NO tiene número
✅ Puede editar/eliminar después
❌ No puede enviarse así
```

**Controlador:**
```php
$esBorrador = $request->input('action') === 'borrador';
$estado = $esBorrador ? 'BORRADOR' : 'ENVIADA';
$cotizacion = Cotizacion::create([
    'numero_cotizacion' => null, // ← SIN NÚMERO
    'es_borrador' => $esBorrador,
    'estado' => $estado,
    ...
]);
```

#### **OPCIÓN B: Enviar INMEDIATAMENTE**
```
Click en "Enviar Cotización"
    ↓
POST /cotizaciones-prenda
    ├─ action = "enviar"
    ├─ estado = "ENVIADA"
    ├─ numero_cotizacion = NULL (temporalmente)
    └─ es_borrador = false
    ↓
Sistema encola JOB:
    ProcesarEnvioCotizacionJob::dispatch(cotizacion_id, 3)
    ↓
🔄 JOB procesa:
    1. Genera número secuencial
    2. Actualiza numero_cotizacion
    3. Registra en historial
    4. Envía notificaciones
    ↓
✅ Cotización enviada con número asignado
❌ NO puede editar después
```

---

### 4️⃣ DESPUÉS DE GUARDAR

#### **SI FUE BORRADOR:**
```
El asesor VE en lista /cotizaciones-prenda:
├─ Nombre de cotización
├─ Cliente
├─ Estado: "BORRADOR" (amarillo)
├─ Sin número de cotización
└─ Acciones:
   ├─ Editar ✏️
   ├─ Eliminar 🗑️
   └─ Enviar ▶️
```

#### **SI FUE ENVIADA:**
```
El asesor VE en lista /cotizaciones-prenda:
├─ Nombre de cotización
├─ Cliente
├─ Estado: "ENVIADA" (verde)
├─ Número: COT-20251214-001
└─ Acciones:
   ├─ Ver 👁️
   ├─ Descargar PDF 📄
   └─ Solo lectura (NO editar)
```

---

## ✅ COMPARATIVA: CÓMO DEBERÍA HACERLO (BEST PRACTICES)

### MEJORA 1: VALIDACIÓN ANTES DE GUARDAR

**Actual:**
```javascript
// Sin validación previa
formulario.submit()  // ← Puede mandar datos vacíos
```

**Debería:**
```javascript
// Validar ANTES de enviar
function validarFormulario() {
    if (!cliente) {
        mostrar_error("Selecciona un cliente");
        return false;
    }
    if (prendas.length === 0) {
        mostrar_error("Agrega al menos una prenda");
        return false;
    }
    if (!tieneAlgunaFoto) {
        mostrar_advertencia("¿Seguro sin fotos? Continuar igual");
        return false;
    }
    return true;
}
```

---

### MEJORA 2: FLUJO MÁS CLARO

**Actual (Confuso):**
```
Guardar como borrador ←→ Enviar
(ambos están juntos)
```

**Debería:**
```
┌─ GUARDAR PROGRESIVO
│  Click: "Guardar Borrador" (Auto-save cada 30 seg)
│  └─ Asesor puede volver después
│
└─ ENVÍO FINAL Y DEFINITIVO
   Click: "Enviar a Aprobador"
   └─ Genera número automático
   └─ Ya no se puede editar
```

---

### MEJORA 3: SECUENCIA DE NÚMEROS SEGURA

**Actual:**
```
1. Asesor1 envía → Job1 genera número
2. Asesor2 envía → Job2 genera número
3. ¿Mismo número? ❌ RACE CONDITION
```

**Debería:**
```
Usar LOCK pessimista en BD:
┌─ Asesor1 envía
│  ├─ Adquiere LOCK en numero_secuencias
│  ├─ Lee último: 042
│  ├─ Genera: 043
│  ├─ Guarda: 043
│  └─ Libera LOCK ✅
│
└─ Asesor2 espera LOCK
   ├─ Asesor1 libera
   ├─ Asesor2 adquiere LOCK
   ├─ Lee: 043
   ├─ Genera: 044
   └─ Libera LOCK ✅
```

---

### MEJORA 4: FOTOGRAFÍAS

**Actual:**
```
Subir 5 fotos por prenda
└─ ¿Qué pasa si falla? ❌ No hay reintentos
```

**Debería:**
```
Subir fotos con:
├─ Progress bar
├─ Reintentos automáticos
├─ Validación de dimensiones antes de subir
├─ Previsualizaciones
└─ Opción de eliminar individual
```

---

## 📋 MAPEO DE DATOS: QUÉ SE GUARDA DÓNDE

### **Tabla: `cotizaciones`**
```
✅ asesor_id        (Auth::id())
✅ cliente_id       (De formulario)
✅ numero_cotizacion (Generado por job)
✅ tipo_cotizacion_id (3 para Prenda, 1 para Bordado)
✅ tipo_venta       (M/P/G)
✅ estado           (BORRADOR/ENVIADA/APROBADA/RECHAZADA)
✅ es_borrador      (true/false)
✅ productos        (JSON array de prendas)
✅ especificaciones (JSON)
✅ observaciones_tecnicas
✅ created_at, updated_at
```

### **Tabla: `prendas_cot`** (Detalle de cada prenda)
```
✅ cotizacion_id
✅ nombre_prenda
✅ cantidad
✅ descripcion
✅ especificaciones (JSON)
```

### **Tabla: `prenda_fotos_cot`** (Fotos de prendas)
```
✅ prenda_cot_id
✅ ruta_original
✅ ruta_webp
✅ ruta_miniatura
✅ orden
```

---

## 🎯 PROBLEMAS DETECTADOS EN CÓDIGO ACTUAL

### ❌ PROBLEMA 1: SIN TRANSACCIÓN EN ALGUNAS OPERACIONES
```php
// En CotizacionPrendaController::store()
$cotizacion = Cotizacion::create([...]);  // ← Si falla aquí

// Luego
$this->procesarImagenesCotizacion($request, $cotizacion->id);  // ← Imagenes huérfanas
```

### ❌ PROBLEMA 2: NÚMERO GENERADO ASINCRONAMENTE
```php
// El cliente NO sabe el número inmediatamente
// Tiene que esperar al job
// Confunde al asesor

Post a /enviar → JSON { success: true }
But numero_cotizacion = NULL until job processes
```

### ❌ PROBLEMA 3: SIN VALIDACIÓN DE CONCURRENCIA
```
Asesor1 envía a las 14:30:00
Asesor2 envía a las 14:30:00
Mismo timestamp, ¿mismo número?
```

### ❌ PROBLEMA 4: BORRADOR + ENVÍO MEZCLADO
```
Confuso: ¿Hacer borrador o enviar?
UI no está clara
```

---

## ✨ RECOMENDACIONES

### 1. **Crear número ANTES de responder**
```php
// Generar DENTRO de transacción
DB::transaction(function() {
    $numero = $this->generarNumeroCotizacion();
    $cotizacion = Cotizacion::create([
        'numero_cotizacion' => $numero,
        ...
    ]);
    return $cotizacion;
});
```

### 2. **Separar Borrador ↔ Envío**
```
Opción A: Guardar (auto-save cada 30s)
Opción B: Enviar (genera número, bloqueado)
```

### 3. **Usar LOCK en secuencias**
```php
$numero = DB::transaction(function() {
    $seq = NumeroSecuencia::lockForUpdate()->first();
    $nuevo = $seq->siguiente;
    $seq->siguiente++;
    $seq->save();
    return formatearNumero($nuevo);
});
```

### 4. **Agregar validaciones frontend**
```javascript
validateForm()
├─ Cliente no vacío
├─ Mínimo 1 prenda
├─ Mínimo 1 foto por prenda
└─ Confirmar si hay errores
```

---

## 📊 ESTADO ACTUAL EN BD

**Datos reales capturados:**
```
✓ 48 cotizaciones existentes
✓ 25 prendas en cotizaciones
✓ 19 fotos de prendas
✓ 973 clientes
✓ 64 usuarios/asesores
✓ 3 tipos de cotización (M, P, G)
```

---

## 🎬 PRÓXIMOS PASOS RECOMENDADOS

1. **INMEDIATO:** Generar número dentro de transacción (NO async)
2. **CORTO PLAZO:** Mejorar UI borrador vs envío
3. **CORTO PLAZO:** Agregar validaciones frontend
4. **MEDIANO PLAZO:** Lock pessimista en secuencias
5. **MEDIANO PLAZO:** Mejorar flujo de fotos con reintentos

