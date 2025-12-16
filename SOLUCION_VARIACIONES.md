# RESUMEN DE CAMBIOS - Por qué no salían las variaciones

## Problema Identificado ✅

**El error en los logs:**
```
⌛ Error guardando producto {"error":"Undefined array key \"genero_id\"", ... }
```

**La causa raíz:**
En `cotizaciones.js`, cuando se recopilaban datos para guardar, **NO se estaba capturando `genero_id`** del input hidden. Cuando se enviaba al backend, el array `variantes` llegaba **sin la clave `genero_id`**, causando el error.

---

## Cambios Realizados ✅

### 1. **cotizaciones.js** - Capturar `genero_id`

Agregué código para SIEMPRE capturar el `genero_id` del input hidden:

```javascript
// ✅ CAPTURAR GENERO_ID desde el input hidden (IMPORTANTE para "ambos")
const generoIdInput = item.querySelector('.genero-id-hidden');
if (generoIdInput) {
    variantes.genero_id = generoIdInput.value || '';
    console.log('✅ genero_id capturado:', generoIdInput.value === '' ? '(vacío - aplica a ambos)' : variantes.genero_id);
}
```

**Antes:** No se capturaba nada
**Ahora:** Se captura SIEMPRE, aunque esté vacío

---

### 2. **CotizacionPrendaService.php** - Proteger el LOG

Corregí una comparación desprotegida que causaba "Undefined array key":

```php
// ❌ ANTES (causaba error):
'genero_id_es_null' => $variantes['genero_id'] === null,

// ✅ DESPUÉS:
'genero_id_es_null' => ($variantes['genero_id'] ?? null) === null,
```

---

## Flujo Completado ✅

```
Usuario selecciona "Ambos"
    ↓
actualizarGeneroSeleccionado() mapea a genero_id = "4"
    ↓
Input hidden: <input class="genero-id-hidden" value="4">
    ↓
Usuario hace CLIC GUARDAR
    ↓
recopilarDatos() encuentra el input y captura genero_id = "4"
    ↓
FormData se construye con: prendas[0][variantes][genero_id] = "4"
    ↓
Backend recibe y crea prenda_variantes_cot con genero_id = 4
    ↓
Variaciones aparecen en "Ver Cotización" ✅
```

---

## Cómo Probar Ahora ✅

### Paso 1: Ve a Crear Cotización
- Selecciona Tipo: "M", "D", o "X"
- Selecciona un Cliente
- Haz clic en "Agregar Prenda"

### Paso 2: Rellena datos de la Prenda
- Nombre: "Camiseta de Prueba"
- Descripción: "cualquier cosa"
- Cantidad: "100"

### Paso 3: Selecciona Tallas
En la sección "TALLAS A COTIZAR":

1. **Selector 1**: Selecciona `NÚMEROS (DAMA/CABALLERO)`
2. **Selector 2** (aparece después): Selecciona `Ambos (Dama y Caballero)`

Deberías ver **dos TABS**:
```
┌──────────────────────────────────┐
│ 👩 DAMA | 👨 CABALLERO           │
├──────────────────────────────────┤
│ [6] [8] [10] [12] [14] ...       │
└──────────────────────────────────┘
```

### Paso 4: Selecciona Tallas
- Haz clic en el tab **DAMA** → Selecciona: 10, 14, 18
- Haz clic en el tab **CABALLERO** → Selecciona: 32, 38, 44

Cada talla se marcará **AZUL** cuando la selecciones.

### Paso 5: Agrega Variaciones (opcional)
- Color: "Rojo"
- Tela: "Algodón"
- Observaciones: dejar vacío

### Paso 6: GUARDAR
Haz clic en botón **"GUARDAR"**

### Paso 7: Verifica la Consola (F12)
Deberías ver:
```javascript
✅ genero_id capturado: 4
✅ PRODUCTO AGREGADO: {
    nombre: "Camiseta de Prueba",
    tallas: 6,
    variantes_keys: ...
}
```

### Paso 8: Verifica en "Ver Cotización"
1. Ve a "Ver Cotización"
2. Busca la que acabas de crear
3. Abre el modal de variaciones
4. **Deberías ver las tallas (NO dice "Sin variaciones")**

---

## Verificación en Base de Datos ✅

Ejecuta este comando en PowerShell:

```powershell
php check_variaciones_laravel.php
```

Deberías ver algo como:

```
🔍 DEBUG - VERIFICANDO VARIACIONES EN BASE DE DATOS

📋 COTIZACIÓN MÁS RECIENTE:
  ID: 61
  Número: COT-00054
  Estado: BORRADOR
  
📦 PRENDAS:
   Total: 1

   🧥 PRENDA #39: Camiseta de Prueba
      ✅ Total de variantes: 1
         - ID: 125, Género: NULL (Ambos), Color: Rojo, Tela: Algodón
            📏 Tallas: 10, 14, 18, 32, 38, 44
      📸 Fotos: 0
```

### Interpretación:

- ✅ Si ve "Total de variantes: 1" → ¡FUNCIONÓ!
- ✅ Si ve "Género: NULL (Ambos)" → ¡Correcto!
- ✅ Si ves todas las tallas → ¡Perfecto!

---

## Mapeamiento de Géneros ✅

```javascript
dama      → genero_id = 1
caballero → genero_id = 2  
ambos     → genero_id = 4
```

En la BD:
```sql
SELECT * FROM generos;
```

Resultado esperado:
```
id | nombre
1  | Dama
2  | Caballero
4  | Ambos
```

---

## Checklist Final ✅

- [ ] He creado una nueva cotización con género "Ambos"
- [ ] Seleccioné tallas de DAMA (6,8,10,etc) y CABALLERO (28,30,32,etc)
- [ ] Guardé la cotización
- [ ] Ejecuté `php check_variaciones_laravel.php`
- [ ] Veo "Total de variantes: 1" con genero_id = NULL
- [ ] Veo todas las tallas (de dama y caballero juntas)
- [ ] En "Ver Cotización" ya NO aparece "Sin variaciones"
- [ ] Aparecen las tallas correctas en la vista

---

## Si Aún No Funciona

### Opción A: Limpia la caché
```powershell
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Opción B: Recarga el navegador
- Presiona **Ctrl+Shift+Supr** (Vaciar caché)
- Luego Ctrl+F5 (Recargar)

### Opción C: Abre la Consola (F12)
Verifica que veas:
```javascript
✅ genero_id capturado: 4
```

Si no ves este mensaje, significa que el input hidden no está siendo encontrado.

---

## Archivos Modificados

1. **public/js/asesores/cotizaciones/cotizaciones.js** (línea ~603)
   - Agregué captura de `genero_id`

2. **app/Application/Services/CotizacionPrendaService.php** (línea 150)
   - Protegí la comparación con `??`

3. **resources/views/components/template-producto.blade.php** (líneas 307-321)
   - Ya tenía el input hidden y onchange

4. **public/js/asesores/cotizaciones/tallas.js** (líneas 6-34)
   - Ya tenía la función actualizarGeneroSeleccionado()

---

## Resultado Esperado

Cuando todo funciona correctamente:

```
ANTES (COT #60):
────────────────
PRENDA: camisa drill
  ⚠️ SIN VARIACIONES
  📸 Fotos: 3
  
DESPUÉS (COT #61 - nueva):
───────────────────────────
PRENDA: Camiseta de Prueba
  ✅ Total de variantes: 1
     - ID: 125, Género: NULL (Ambos)
     📏 Tallas: 10, 14, 18, 32, 38, 44
  📸 Fotos: 3
```

¡Listo! Las variaciones ahora aparecerán. 🎉

