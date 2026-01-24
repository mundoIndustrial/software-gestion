# 🔍 GUÍA DE DEBUG: TALLAS NO APARECEN EN TARJETA DE PRENDA

## Estado Actual
-  Logs agregados en 4 puntos clave del pipeline
-  Validación de tallas agregada
-  Fallback de nombre agregado
- 🔄 Pendiente: Usuario ejecutar flow y capturar logs

## 📊 PIPELINE COMPLETO CON DEBUG LOGS

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. USUARIO SELECCIONA TALLAS EN MODAL                           │
│    → Click "DAMA" → Selecciona XXXL: 30, S: 30                 │
│    Result: window.tallasRelacionales = { DAMA: { XXXL: 30, S: 30 } }
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. PRENDA-FORM-COLLECTOR.JS (líneas 95-98, 221-223)            │
│    Log: [prenda-form-collector] 📦 Datos capturados             │
│    Captura: prendaData.cantidad_talla = window.tallasRelacionales
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. GESTION-ITEMS-PEDIDO.JS (líneas 243-255)                    │
│    Log: [gestion-items-pedido] 🔍 Validación de tallas         │
│    ✓ Valida que cantidad_talla no esté vacío                   │
│    ✓ Muestra error si no hay tallas seleccionadas              │
│    Result: Si OK → Añade a this.prendas[]                      │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. ITEM-RENDERER.JS (llama función)                             │
│    → Llama window.generarTarjetaPrendaReadOnly(prenda, index)  │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5. PRENDA-CARD-READONLY.JS (línea 19-20, 31)                   │
│    Log: [generarTarjetaPrendaReadOnly] 📋 Prenda a renderizar   │
│    Log: [generarTarjetaPrendaReadOnly]  HTML generado         │
│    Llamada: PrendaCardService.generar(prenda, indice)          │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ 6. PRENDA-CARD-SERVICE.JS (línea 16, 20, 22)                   │
│    Log: [PrendaCardService.generar] 📦 ENTRADA - prendaRaw     │
│    Log: [PrendaCardService.generar]  DESPUÉS TRANSFORMAR     │
│    Llamada: PrendaDataTransformer.transformar(prendaRaw)       │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ 7. PRENDA-DATA-TRANSFORMER.JS (línea 25, 33-34)                │
│    Log: [PrendaDataTransformer] 🔄 Transformando cantidad_talla│
│    Log: [PrendaDataTransformer]  Resultado:                  │
│    - generosConTallas: { dama: { tallas: ['XXXL', 'S'] } }    │
│    - cantidadesPorTalla: { 'dama-XXXL': 30, 'dama-S': 30 }    │
│    Retorna: prenda objeto con data transformada                │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ 8. TALLAS-BUILDER.JS (línea 8-10, 34-35)                       │
│    Log: [TallasBuilder.construir] 📦 ENTRADA - generosConTallas│
│    Log: [TallasBuilder.construir] 🔍 totalTallas               │
│    ✓ Verifica si prenda.generosConTallas tiene datos           │
│    ✓ Si vacío: Log ⚠️ SIN TALLAS - RETORNANDO VACÍO           │
│    ✓ Si tiene datos: Genera HTML de tallas                     │
└─────────────────────────────────────────────────────────────────┘
                            ↓
               TARJETA RENDERIZADA EN UI
```

## PASOS PARA EJECUTAR DEBUG

### 1. Abre el formulario de crear prenda
```
- Click en botón "Crear Prenda"
- Se abre modal con formulario
```

### 2. Completa el formulario
```
Campo: Nombre de Prenda
Valor: "Mi Prenda Test"

(Otros campos opcionales - completa según necesites)
```

### 3. Selecciona tallas
```
1. Click en botón "DAMA" 
   → Se expande sección de tallas
2. Click en "XXXL" → Ingresa 30
3. Click en "S" → Ingresa 30
4. Verifica que aparezca: " DAMA - XXXL: 30" y " DAMA - S: 30"
```

### 4. Confirma tallas
```
- Si aparece botón "Confirmar Tallas": Clickealo
- La tarjeta de tallas debe actualizar
```

### 5. Guardar prenda
```
- Click botón "Guardar Prenda" (en modal o tarjeta de tallas)
```

### 6. CAPTURAR LOGS
```
- Abre DevTools: F12 o Ctrl+Shift+I
- Ve a pestaña "Console"
- Busca logs que empiezan con [ :
  * [prenda-form-collector]
  * [gestion-items-pedido]
  * [generarTarjetaPrendaReadOnly]
  * [PrendaCardService.generar]
  * [PrendaDataTransformer]
  * [TallasBuilder.construir]
```

## 🔎 QUÉ BUSCAR EN LOS LOGS

###  FLUJO CORRECTO - Logs que DEBEN aparecer:
```
[prenda-form-collector] 📦 Datos capturados: 
  nombre_prenda: "Mi Prenda Test"
  cantidad_talla: { DAMA: { XXXL: 30, S: 30 } }

[gestion-items-pedido] 🔍 Validación de tallas:
  - prendaData.cantidad_talla: { DAMA: { XXXL: 30, S: 30 } }
  - tieneTallas: true

[generarTarjetaPrendaReadOnly] 📋 Prenda a renderizar:
  { nombre_prenda: "Mi Prenda Test", cantidad_talla: { DAMA: { XXXL: 30, S: 30 } } }

[PrendaCardService.generar] 📦 ENTRADA - prendaRaw:
  { nombre_prenda: "Mi Prenda Test", cantidad_talla: { DAMA: { XXXL: 30, S: 30 } } }

[PrendaDataTransformer] 🔄 Transformando cantidad_talla:
  { DAMA: { XXXL: 30, S: 30 } }

[PrendaDataTransformer]  Resultado:
  - generosConTallas: { dama: { tallas: ['XXXL', 'S'] } }
  - cantidadesPorTalla: { 'dama-XXXL': 30, 'dama-S': 30 }

[PrendaCardService.generar]  DESPUÉS TRANSFORMAR - prenda:
  { nombre_prenda: "Mi Prenda Test", generosConTallas: { dama: { tallas: ['XXXL', 'S'] } } }

[TallasBuilder.construir] 📦 ENTRADA - generosConTallas:
  { dama: { tallas: ['XXXL', 'S'] } }

[TallasBuilder.construir] 🔍 totalTallas: 2

[generarTarjetaPrendaReadOnly]  HTML generado exitosamente
```

### ❌ PROBLEMAS POSIBLES - Logs que indican fallo:

**Problema 1: "Sin nombre" en tarjeta**
- Busca log: `nombre_prenda: "Mi Prenda Test"` en [PrendaCardService]
- Si NO está: El problema es en collector.js (no está capturando nombre)
- Si está: El problema es en transformer o builder (no está renderizando)

**Problema 2: "Sin Tallas" en tarjeta**
- Búsqueda 1: `cantidad_talla: { DAMA: { XXXL: 30, S: 30 } }` en [prenda-form-collector]
  - Si NO aparece: Usuario no está confirmando tallas (botón no clickeado)
  - Si aparece : Continúa con búsqueda 2
  
- Búsqueda 2: `tieneTallas: true` en [gestion-items-pedido]
  - Si muestra `tieneTallas: false`: Los datos llegaron vacíos (problema en collector)
  - Si muestra `tieneTallas: true` : Continúa con búsqueda 3
  
- Búsqueda 3: `generosConTallas: { dama: { tallas: ['XXXL', 'S'] } }` en [PrendaDataTransformer]
  - Si NO aparece: El transformer no recibió `cantidad_talla` (datos perdidos en tránsito)
  - Si aparece : Continúa con búsqueda 4
  
- Búsqueda 4: `totalTallas: 2` en [TallasBuilder]
  - Si muestra `totalTallas: 0`: Los datos no llegaron al builder
  - Si muestra `totalTallas: 2` : El HTML debe haber sido generado

## 📋 CHECKLIST DE VERIFICACIÓN

- [ ] Nombre en campo de entrada: "Mi Prenda Test" (o tu nombre)
- [ ] Botón "DAMA" clickeado (se expande)
- [ ] Talla XXXL: 30 ingresada
- [ ] Talla S: 30 ingresada
- [ ] Aparece " DAMA - XXXL: 30"
- [ ] Aparece " DAMA - S: 30"
- [ ] Botón "Guardar Prenda" clickeado
- [ ] Tarjeta aparece en la lista
- [ ] Console abierto (F12 → Console)
- [ ] Buscar logs con [ 
- [ ] Copiar TODOS los logs relevantes

##  PRÓXIMOS PASOS

Una vez captures los logs, reporta:

1. **¿Qué logs aparecen?** (Copiar/pegar de console)
2. **¿En qué punto falla?** (Usa el checklist arriba)
3. **¿Qué muestra la tarjeta?** (Nombre: ?, Tallas: ?)
4. **¿Hay errores en console?** (Líneas rojas)

Con esta información podré identificar exactamente dónde se pierden los datos.
