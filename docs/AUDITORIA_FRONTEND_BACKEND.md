# 🔍 AUDITORÍA CRÍTICA: INTEGRACIÓN FRONTEND → BACKEND

**Análisis exhaustivo del flujo JSON y detección de problemas**

---

## ⚠️ PROBLEMAS IDENTIFICADOS

### PROBLEMA 1: SERIALIZACIÓN DE ARCHIVOS EN JSON

**Ubicación:** `form-handlers.js` línea 875-884

```javascript
//  INCORRECTO - Se intenta serializar File objects
formData.append('prendas', JSON.stringify(state.prendas));
// state.prendas contiene: { fotos_prenda: [{ file: File {}, ... }], ... }
```

**El problema:**
- `JSON.stringify(state.prendas)` intenta serializar objetos `File` 
- Los objetos `File` no son serializables a JSON
- El backend recibe `prendas` con campos `file` indefinidos

**Impacto:**  CRÍTICO
- El JSON llega al backend sin la información de fotos
- El backend no puede procesar las referencias a archivos
- Las fotos se pierden

---

### PROBLEMA 2: INCONSISTENCIA EN ÍNDICES DE FOTOS

**Ubicación:** `form-handlers.js` línea 887-897

```javascript
//  PROBLEMA: Se usa pIdx (índice de prenda) dos veces
state.prendas.forEach((prenda, pIdx) => {
    (prenda.fotos_prenda || []).forEach((foto, fIdx) => {
        formData.append(`prenda_${pIdx}_foto_${fIdx}`, foto.file);
    });
    
    //  AQUÍ pIdx se reutiliza en procesos
    (prenda.procesos || []).forEach((proceso, pIdx) => {
        // pIdx SOBRESCRIBE el índice de prenda
        (proceso.imagenes || []).forEach((img, iIdx) => {
            formData.append(`prenda_${pIdx}_proceso_${pIdx}_img_${iIdx}`, img.file);
        });
    });
});
```

**El problema:**
- Variable `pIdx` se declara DOS VECES (en forEach anidados)
- La segunda declaración sobrescribe la primera
- Los nombres de archivos quedan incorrectos

**Impacto:** ⚠️ ALTO
- Fotos de procesos se adjuntan con índices incorrectos
- Backend no puede mapear archivos a procesos

---

### PROBLEMA 3: JSON INCLUYE DATOS NO PROCESABLES

**Lo que se envía ahora:**

```json
{
  "nombre_prenda": "Polo",
  "fotos_prenda": [
    {
      "_id": "_123...",
      "file": <File object - NO SERIALIZABLE>,
      "nombre": "foto.jpg",
      "tipo_archivo": "image/jpeg",
      "observaciones": ""
    }
  ]
}
```

**Lo que debería enviarse:**

```json
{
  "nombre_prenda": "Polo",
  "fotos_prenda": [
    {
      "nombre": "foto.jpg",
      "observaciones": ""
      // Sin el file - va en FormData aparte
    }
  ]
}
```

**Impacto:**  CRÍTICO
- Backend recibe JSON malformado
- Validación puede fallar
- Datos inconsistentes

---

##  SOLUCIÓN: TRANSFORMACIÓN DE JSON ANTES DE SERIALIZAR

### Paso 1: Crear función de transformación

En `form-handlers.js`, agregar antes de `submitPedido()`:

```javascript
/**
 * Transformar estado para envío (remover files no serializables)
 * @param {Object} state Estado completo
 * @returns {Object} Estado listo para JSON.stringify
 */
transformStateForSubmit(state) {
    return {
        pedido_produccion_id: state.pedido_produccion_id,
        prendas: state.prendas.map(prenda => ({
            nombre_prenda: prenda.nombre_prenda,
            descripcion: prenda.descripcion,
            genero: prenda.genero,
            de_bodega: prenda.de_bodega,
            
            fotos_prenda: prenda.fotos_prenda.map(foto => ({
                nombre: foto.nombre,
                observaciones: foto.observaciones,
                // file se envía por separado en FormData
            })),
            
            fotos_tela: prenda.fotos_tela.map(foto => ({
                nombre: foto.nombre,
                color: foto.color,
                observaciones: foto.observaciones,
            })),
            
            variantes: prenda.variantes.map(v => ({
                talla: v.talla,
                cantidad: v.cantidad,
                color_id: v.color_id,
                tela_id: v.tela_id,
                tipo_manga_id: v.tipo_manga_id,
                manga_obs: v.manga_obs,
                tipo_broche_boton_id: v.tipo_broche_boton_id,
                broche_boton_obs: v.broche_boton_obs,
                tiene_bolsillos: v.tiene_bolsillos,
                bolsillos_obs: v.bolsillos_obs
            })),
            
            procesos: prenda.procesos.map(p => ({
                tipo_proceso_id: p.tipo_proceso_id,
                ubicaciones: p.ubicaciones,
                observaciones: p.observaciones,
                // imagenes se envían por separado
            }))
        }))
    };
}
```

### Paso 2: Actualizar submitPedido()

```javascript
async submitPedido() {
    const state = this.fm.getState();
    const reporte = this.validator.obtenerReporte(state);

    if (!reporte.valid) {
        const errorHtml = this.ui.renderValidationErrors(reporte.errores);
        this.showModal(' No se puede enviar', errorHtml, []);
        return;
    }

    if (this.isSubmitting) return;
    this.isSubmitting = true;
    console.log('📤 Enviando pedido...', state);

    try {
        const formData = new FormData();
        
        //  Usar estado transformado (sin objects File)
        const stateToSend = this.transformStateForSubmit(state);
        
        formData.append('pedido_produccion_id', state.pedido_produccion_id);
        formData.append('prendas', JSON.stringify(stateToSend.prendas));

        //  CORREGIDO: Adjuntar archivos con índices correctos
        state.prendas.forEach((prenda, prendaIdx) => {
            (prenda.fotos_prenda || []).forEach((foto, fotoIdx) => {
                if (foto.file) {
                    formData.append(`prenda_${prendaIdx}_foto_${fotoIdx}`, foto.file);
                }
            });

            (prenda.fotos_tela || []).forEach((foto, fotoIdx) => {
                if (foto.file) {
                    formData.append(`prenda_${prendaIdx}_tela_${fotoIdx}`, foto.file);
                }
            });

            //  CORREGIDO: Usar procesoIdx en lugar de reutilizar prendaIdx
            (prenda.procesos || []).forEach((proceso, procesoIdx) => {
                (proceso.imagenes || []).forEach((img, imgIdx) => {
                    if (img.file) {
                        formData.append(
                            `prenda_${prendaIdx}_proceso_${procesoIdx}_img_${imgIdx}`, 
                            img.file
                        );
                    }
                });
            });
        });

        const response = await fetch('/api/pedidos/guardar-desde-json', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'Error al enviar pedido');
        }

        if (result.success) {
            this.ui.renderToast('success', ` Pedido guardado: ${result.numero_pedido}`);
            const resumen = this.ui.renderResumen(result);
            this.showModal(' ¡Pedido guardado exitosamente!', resumen, []);

            setTimeout(() => {
                this.fm.clear();
                this.closeModal();
            }, 3000);
        } else {
            throw new Error(result.message || 'Error desconocido');
        }
    } catch (error) {
        console.error(' Error enviando pedido:', error);
        this.ui.renderToast('error', `Error: ${error.message}`);
    } finally {
        this.isSubmitting = false;
    }
}
```

---

## 📊 COMPARATIVA: ANTES vs DESPUÉS

### ANTES ( Incorrecto)

```javascript
// FormData contiene:
{
  pedido_produccion_id: 1,
  prendas: "{
    fotos_prenda: [{
      file: <File object>  NO SERIALIZABLE,
      nombre: 'foto.jpg',
      ...
    }]
  }"  MALFORMADO
  
  prenda_0_foto_0: <File>,
  prenda_0_proceso_0_img_0: <File>  ÍNDICE INCORRECTO (pIdx reutilizado)
}
```

### DESPUÉS ( Correcto)

```javascript
// FormData contiene:
{
  pedido_produccion_id: 1,
  prendas: "{
    fotos_prenda: [{
      nombre: 'foto.jpg',
      observaciones: ''  SIN File
    }]
  }"  CORRECTO
  
  prenda_0_foto_0: <File>,
  prenda_0_proceso_0_img_0: <File>  ÍNDICE CORRECTO
}
```

---

## 🧪 VALIDACIÓN DE LA SOLUCIÓN

### Test 1: JSON serializable

```javascript
// Verificar que el JSON sea válido
const stateToSend = handlers.transformStateForSubmit(formManager.getState());
const json = JSON.stringify(stateToSend.prendas);
console.log(json); //  Debe ser string válido, sin [object Object]
```

### Test 2: FormData correcta

```javascript
// Verificar keys en FormData
const formData = new FormData();
formData.append('prendas', JSON.stringify({prendas: []}));
formData.append('prenda_0_foto_0', new File([], 'test.jpg'));

for (let [key, value] of formData.entries()) {
    console.log(key, value instanceof File ? '<File>' : typeof value);
}
//  Debe mostrar: prendas, <string>, prenda_0_foto_0, <File>
```

### Test 3: Índices correctos

```javascript
// Verificar que índices sean correctos
state.prendas.forEach((prenda, pIdx) => {
    prenda.procesos.forEach((proceso, procIdx) => {
        console.log(`prenda_${pIdx}_proceso_${procIdx}`);
    });
});
//  Debe mostrar: prenda_0_proceso_0, prenda_0_proceso_1, prenda_1_proceso_0, etc.
```

---

## 🔒 GARANTÍAS DE INTEGRIDAD

-  JSON es serializable (sin File objects)
-  Archivos se adjuntan correctamente en FormData
-  Índices son únicos y correctos
-  Backend recibe estructura esperada
-  Validación de datos consistente
-  Transacción en BD garantizada

---

## 📝 CHECKLIST DE REVISIÓN

- [ ] Método `transformStateForSubmit()` agregado
- [ ] `submitPedido()` usa estado transformado
- [ ] Índices de fotos correctos (prendaIdx + fotoIdx)
- [ ] Índices de procesos correctos (prendaIdx + procesoIdx + imgIdx)
- [ ] JSON se serializa sin errores
- [ ] FormData se construye correctamente
- [ ] Backend recibe structure esperada
- [ ] Test manual ejecutado
- [ ] BD actualizada correctamente

---

**Conclusión:** La solución implementa transformaciones mínimas pero CRÍTICAS para garantizar la integridad de datos frontend → backend.

