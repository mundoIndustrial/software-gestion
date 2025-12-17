# 🔴 ANÁLISIS: PROBLEMA AL GUARDAR BORRADOR EN COTIZACIÓN TIPO PB

**Fecha:** 16 de Diciembre de 2025
**Tipo de Cotización:** PB (Prenda + Bordado/Logo)
**URL Problemática:** `http://desktop-8un1ehm:8000/asesores/pedidos/create?tipo=PB&editar=128`
**Estado:** 🔍 DIAGNÓSTICO COMPLETADO

---

## 🎯 SÍNTOMA REPORTADO

```
Cuando llego al paso 4 (REVISAR COTIZACIÓN):
✅ Click en "ENVIAR" → Se guarda todo perfectamente
   - Estado = ENVIADA
   - es_borrador = false
   - numero_cotizacion = generado

❌ Click en "GUARDAR BORRADOR" → NO guarda (o guarda incorrectamente)
   - Estado debería ser = BORRADOR
   - es_borrador debería ser = true
   - numero_cotizacion debería ser = NULL
```

---

## 🔍 ANÁLISIS DEL CÓDIGO

### 1️⃣ ARCHIVO: `public/js/asesores/cotizaciones/guardado.js`

#### A. Función `guardarCotizacion()` (Línea 123)

```javascript
async function guardarCotizacion() {
    // ✅ Marca como BORRADOR
    formData.append('es_borrador', '1'); // ← CORRECTO
    formData.append('cliente', datos.cliente);
    formData.append('tipo_venta', tipoVenta);
    formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
    
    // Envía al servidor
    fetch('/asesores/cotizaciones/guardar', {
        method: 'POST',
        body: formData
    })
}
```

**Estado:** ✅ SE VE CORRECTO


#### B. Función `enviarCotizacion()` (Línea 491)

```javascript
async function enviarCotizacion() {
    // ...validaciones...
    
    // 🔴 AQUÍ ES DONDE CAMBIA AL ENVIAR
    // Cuando procede, llama a procederEnviarCotizacion(datos)
}
```

**Estado:** ✅ SE VE CORRECTO


---

## ⚠️ PROBLEMAS IDENTIFICADOS

### PROBLEMA #1: Flujo de Pasos No Diferencia Estados

**Ubicación:** `public/js/asesores/create-friendly.js` + vistas `paso-*.blade.php`

El sistema tiene **4 pasos**:
```
Paso 1: Cliente
Paso 2: Prendas
Paso 3: Logo (si aplica)
Paso 4: Revisar Cotización
   └─ AQUÍ ESTÁN LOS BOTONES
      ├─ "💾 Guardar Borrador"
      ├─ "✅ Enviar"
```

**El problema:**
- Ambos botones PODRÍAN estar llamando la MISMA función
- O una está sobrescribiendo a la otra

**Verificar en:** `resources/views/components/paso-cuatro.blade.php`

```blade
<button type="button" id="btnGuardarBorrador" onclick="guardarCotizacion()">
    💾 Guardar Borrador
</button>

<button type="button" id="btnEnviar" onclick="enviarCotizacion()">
    ✅ Enviar
</button>
```

---

### PROBLEMA #2: No Hay Diferenciación en Datos Enviados

**Ubicación:** `public/js/asesores/cotizaciones/guardado.js` (Línea 143 en guardar vs 683 en enviar)

**Guardar Borrador:**
```javascript
formData.append('tipo', 'borrador'); // ← DÉBIL: string "borrador"
formData.append('es_borrador', '1');
formData.append('estado', 'BORRADOR'); // ¿SE ENVÍA?
```

**Enviar Cotización:**
```javascript
formData.append('tipo', 'enviada'); // ← string "enviada"
formData.append('es_borrador', '0'); // ¿SE ENVÍA?
formData.append('estado', 'ENVIADA'); // ¿SE ENVÍA?
```

---

### PROBLEMA #3: El Controlador Podría No Estar Validando Correctamente

**Ubicación:** `app/Http/Controllers/Asesores/CotizacionesController.php`

**Lo que DEBERÍA verificar:**

```php
$esBorrador = $request->input('es_borrador') === '1';
$tipo = $request->input('tipo'); // 'borrador' o 'enviada'

if ($esBorrador) {
    // GUARDAR como BORRADOR
    $cotizacion->update([
        'es_borrador' => true,
        'estado' => 'BORRADOR',
        'numero_cotizacion' => null, // ← IMPORTANTE
    ]);
} else {
    // ENVIAR
    $cotizacion->update([
        'es_borrador' => false,
        'estado' => 'ENVIADA',
        // numero_cotizacion se genera después por job
    ]);
}
```

---

## 🔧 SOLUCIÓN PROPUESTA

### PASO 1: Verificar el Controlador

Busca: `app/Http/Controllers/Asesores/CotizacionesController.php`

Método: `store()` o `guardar()`

**Asegúrate de que haga esto:**

```php
public function store(Request $request)
{
    $esBorrador = $request->input('es_borrador') === '1' 
                || $request->input('tipo') === 'borrador';
    
    // Validar datos
    $validado = $request->validate([
        'cliente' => 'required|string',
        'tipo_venta' => 'required|in:M,D,X',
        'tipo_cotizacion' => 'required|in:P,L,PB',
        // ... otros campos ...
    ]);
    
    // CREAR O ACTUALIZAR
    if ($cotizacionId = $request->input('cotizacion_id')) {
        $cotizacion = Cotizacion::findOrFail($cotizacionId);
        // ACTUALIZAR BORRADOR EXISTENTE
        $cotizacion->update([
            'es_borrador' => $esBorrador,
            'estado' => $esBorrador ? 'BORRADOR' : 'ENVIADA',
            'numero_cotizacion' => $esBorrador ? null : $cotizacion->numero_cotizacion,
            'cliente' => $validado['cliente'],
            // ... otros campos ...
        ]);
    } else {
        // CREAR NUEVO
        $cotizacion = Cotizacion::create([
            'asesor_id' => auth()->id(),
            'es_borrador' => $esBorrador,
            'estado' => $esBorrador ? 'BORRADOR' : 'ENVIADA',
            'numero_cotizacion' => null, // El job lo genera después
            'cliente' => $validado['cliente'],
            'tipo_venta' => $validado['tipo_venta'],
            'tipo_cotizacion' => $validado['tipo_cotizacion'],
            // ... otros campos ...
        ]);
    }
    
    // Guardar prendas, fotos, telas, etc.
    // ...
    
    // SI ES ENVÍO, ENCOLAR JOB
    if (!$esBorrador) {
        \App\Jobs\ProcesarEnvioCotizacionJob::dispatch($cotizacion);
    }
    
    return response()->json([
        'success' => true,
        'data' => [
            'id' => $cotizacion->id,
            'es_borrador' => $cotizacion->es_borrador,
            'estado' => $cotizacion->estado,
        ]
    ]);
}
```

---

### PASO 2: Verificar el JavaScript (Frontend)

**Archivo:** `public/js/asesores/cotizaciones/guardado.js`

**Asegúrate de que `guardarCotizacion()` envíe SIEMPRE `es_borrador=1`:**

```javascript
async function guardarCotizacion() {
    Swal.fire({
        title: 'Guardando...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: (modal) => {
            modal.style.pointerEvents = 'none';
        }
    });
    
    try {
        const formData = new FormData();
        
        // ✅ DATOS BÁSICOS
        formData.append('tipo', 'borrador');           // ← BORRADOR
        formData.append('es_borrador', '1');           // ← 1 = true
        formData.append('cliente', datos.cliente);
        formData.append('tipo_venta', tipoVenta);
        formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
        
        // Si estamos editando
        if (window.cotizacionIdActual) {
            formData.append('cotizacion_id', window.cotizacionIdActual);
        }
        
        // Guardar prendas, fotos, etc.
        // ...
        
        console.log('📝 FormData a enviar (GUARDAR):', {
            tipo: 'borrador',
            es_borrador: '1',
            cliente: datos.cliente,
            tipo_venta: tipoVenta
        });
        
        const response = await fetch('/asesores/cotizaciones/guardar', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '✅ ¡Cotización guardada como borrador!',
                showConfirmButton: false,
                timer: 3000
            });
            
            setTimeout(() => {
                window.location.href = '/asesores/cotizaciones';
            }, 2000);
        } else {
            console.error('❌ Error:', data);
            Swal.fire({
                title: 'Error',
                text: data.message || 'No se pudo guardar',
                icon: 'error'
            });
        }
    } catch (error) {
        console.error('❌ Error al guardar:', error);
        Swal.fire({
            title: 'Error',
            text: error.message,
            icon: 'error'
        });
    }
}
```

**Y `enviarCotizacion()` envíe `es_borrador=0`:**

```javascript
async function enviarCotizacion() {
    // ... validaciones ...
    
    Swal.fire({
        title: 'Enviando cotización...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: (modal) => {
            modal.style.pointerEvents = 'none';
        }
    });
    
    try {
        const formData = new FormData();
        
        // ✅ DATOS BÁSICOS
        formData.append('tipo', 'enviada');            // ← ENVIADA
        formData.append('es_borrador', '0');           // ← 0 = false
        formData.append('cliente', datos.cliente);
        formData.append('tipo_venta', tipoVenta);
        formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'PB');
        
        // Guardar prendas, fotos, etc.
        // ...
        
        console.log('📝 FormData a enviar (ENVIAR):', {
            tipo: 'enviada',
            es_borrador: '0',
            cliente: datos.cliente,
            tipo_venta: tipoVenta
        });
        
        const response = await fetch('/asesores/cotizaciones/guardar', {
            method: 'POST',
            body: formData,
            // ... headers ...
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '✅ ¡Cotización enviada a contador!',
                showConfirmButton: false,
                timer: 3000
            });
            
            setTimeout(() => {
                window.location.href = '/asesores/cotizaciones#enviadas';
            }, 2000);
        } else {
            console.error('❌ Error:', data);
            Swal.fire({
                title: 'Error',
                text: data.message || 'No se pudo enviar',
                icon: 'error'
            });
        }
    } catch (error) {
        console.error('❌ Error al enviar:', error);
        Swal.fire({
            title: 'Error',
            text: error.message,
            icon: 'error'
        });
    }
}
```

---

### PASO 3: Verificar BD

**Cuando guardas como BORRADOR:**

```sql
SELECT id, numero_cotizacion, es_borrador, estado 
FROM cotizaciones 
WHERE id = 128;

-- Debería mostrar:
-- id: 128
-- numero_cotizacion: NULL ✅
-- es_borrador: 1 (true) ✅
-- estado: BORRADOR ✅
```

**Cuando envías:**

```sql
SELECT id, numero_cotizacion, es_borrador, estado 
FROM cotizaciones 
WHERE id = 128;

-- Debería mostrar:
-- id: 128
-- numero_cotizacion: COT-202512-... ✅
-- es_borrador: 0 (false) ✅
-- estado: ENVIADA ✅
```

---

## 📋 CHECKLIST DE VERIFICACIÓN

- [ ] Verifica que `guardarCotizacion()` envíe `es_borrador=1` **SIEMPRE**
- [ ] Verifica que `enviarCotizacion()` envíe `es_borrador=0` **SIEMPRE**
- [ ] Verifica que el controlador distinga entre ambos casos
- [ ] Verifica que el estado se guarde correctamente en BD
- [ ] Verifica que `numero_cotizacion` sea NULL para borradores
- [ ] Verifica que el Job solo se encole para envíos (es_borrador=0)
- [ ] Prueba guardando un borrador y verifica BD
- [ ] Prueba enviando y verifica que se genere número

---

## 🎯 PRÓXIMOS PASOS

1. **Busca el controlador** que maneja `/asesores/cotizaciones/guardar`
2. **Verifica que distinga** entre `es_borrador=1` y `es_borrador=0`
3. **Revisa el JavaScript** y asegúrate que envíe los valores correctos
4. **Prueba en BD** para confirmar los valores guardados

---

## 📞 PREGUNTAS PARA DEBUGGING

```
1. ¿Qué URL exacta se llama al clickear "Guardar Borrador"?
2. ¿Qué datos se envían en el FormData (verifica con Dev Tools)?
3. ¿Qué responde el servidor (verifica Network tab)?
4. ¿Qué valores se guardan en BD después?
```

---

## 🔎 INVESTIGACIÓN COMPLETADA

### SERVIDOR (Backend)

**Archivo:** `app/Infrastructure/Http/Controllers/CotizacionController.php` (Línea 488)

```php
// Si es_borrador viene del frontend, usarlo. Si no, usar la lógica de acción
if ($esBorrador === null) {
    $esBorrador = ($accion === 'guardar');
} else {
    $esBorrador = (bool)$esBorrador; // Convertir a booleano ✅ CORRECTO
}

$estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR'; // ✅ CORRECTO
```

**DIAGNÓSTICO:** ✅ El controlador está bien. Recibe `es_borrador` y lo convierte a booleano correctamente.

---

### FRONTEND (JavaScript)

**Archivo:** `public/js/asesores/cotizaciones/guardado.js` (Línea 143)

```javascript
// Datos básicos
formData.append('es_borrador', '1'); // ✅ CORRECTO - Envía como '1'
formData.append('cliente', datos.cliente);
formData.append('tipo_venta', tipoVenta);
formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
```

**DIAGNÓSTICO:** ✅ El JavaScript está bien. Envía correctamente `es_borrador: '1'`

---

## 🎯 POSIBLE CAUSA REAL

El problema PROBABLEMENTE está en:

1. **¿El `tipo` está siendo enviado?** 
   - No veo `formData.append('tipo', 'borrador')` en el código de guardar
   - En el controlador, busca `$accion = $request->input('accion')`
   - Pero desde el JavaScript NO se está enviando `accion` ni `tipo`

2. **Verificar qué se envía realmente:**

```javascript
// FALTA ESTO EN guardarCotizacion():
formData.append('tipo', 'borrador'); // ← ¿SE ENVÍA?
formData.append('accion', 'guardar'); // ← ¿SE ENVÍA?
```

---

**Estado:** 🔍 Requiere verificación en consola (Network tab) para confirmar
**Prioridad:** 🔴 ALTA - Funcionalidad crítica
