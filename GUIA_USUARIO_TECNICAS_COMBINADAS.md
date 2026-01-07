# 📖 GUÍA DE USO - TÉCNICAS COMBINADAS

## Para el Asesor Nuevo

### ¿Qué son las Técnicas Combinadas?

Es cuando aplicamos **2 o más técnicas a la misma prenda en diferentes lugares**.

**Ejemplo:** 
- Una **CAMISA** con:
  - **BORDADO** en el PECHO
  - **ESTAMPADO** en la ESPALDA

---

## Paso 1️⃣ - Seleccionar Técnicas

1. Ve a la sección de **Cotización de Logo**
2. Marca el checkbox de cada técnica que necesitas:
   - ☑️ BORDADO
   - ☑️ ESTAMPADO
   - (o las que uses)

### Ejemplo en pantalla:
```
Técnicas disponibles:
  ☑️ BORDADO
  ☐ TEJIDO
  ☑️ ESTAMPADO
  ☐ SUBLIMACIÓN
```

---

## Paso 2️⃣ - Click en "Técnicas Combinadas"

Cuando marques 2+ técnicas, aparecerá un botón:

```
[Técnicas Combinadas] ← Click aquí
```

Al clickear, se abre un **formulario minimalista** con 4 secciones:

```
╔══════════════════════════════════════╗
║ TÉCNICAS COMBINADAS                  ║
╠══════════════════════════════════════╣
║                                      ║
║ Prenda                               ║
║ [Escribe aquí: POLO, CAMISA...]  ▼  ║
║ • POLO  ← Click para seleccionar   │ ║
║ • PANTALÓN                         │ ║
║ • CAMISA                           │ ║
║                                      ║
│ Ubicaciones                          │
│ BORDADO:    [PECHO        ]         │
│ ESTAMPADO:  [ESPALDA      ]         │
│                                      │
│ Observaciones                        │
│ [Escribe detalles especiales]        │
│                                      │
│ Tallas y Cantidades                  │
│ Talla: [M]  Cantidad: [10]    [✕]   │
│ [+ Agregar talla]                    │
│                                      │
│              [Guardar] [Cancelar]    │
╚══════════════════════════════════════╝
```

---

## Paso 3️⃣ - Completa cada campo

### 📌 Campo "Prenda"

**¿Qué escribir?** El nombre de la prenda en MAYÚSCULAS.

**Con Autocomplete:**
1. Escribe: `p` → ve un dropdown con sugerencias
2. Selecciona de la lista (si existe)
3. O escribe el nombre completo: `POLO`

```
Ejemplos válidos:
✅ POLO
✅ CAMISA
✅ PANTALÓN
✅ GORRO
✅ CALCETA
✅ JACKET
```

**¡Importante!** Si escribes una prenda nueva, se **guarda automáticamente** para próximas veces.

---

### 🎯 Campo "Ubicaciones"

Aquí dices **dónde va cada técnica** en la prenda.

**Estructura:**
```
Ubicaciones
BORDADO:    [PECHO        ]  ← Dónde va el bordado
ESTAMPADO:  [ESPALDA      ]  ← Dónde va el estampado
```

**Ejemplos de ubicaciones válidas:**
- PECHO (parte delantera)
- ESPALDA (parte trasera)
- MANGA DERECHA
- MANGA IZQUIERDA
- CUELLO
- BOLSILLO
- CINTURA
- COSTADO

**¡Importante!** 
- Cada técnica puede ir en un lugar diferente
- Todos los campos de ubicación son obligatorios
- Puedes escribir cualquier ubicación personalizada

---

### 💬 Campo "Observaciones"

Aquí pones **detalles especiales** (opcional).

**Ejemplos:**
- "Offset derecho 2cm"
- "Color bordado: rojo vino"
- "Tamaño logo: 5x5cm"
- "Con backing adhesivo"

Si no tienes notas especiales, **déjalo vacío**.

---

### 📏 Campo "Tallas y Cantidades"

Aquí especificas **cuántas de cada talla**.

**Estructura:**
```
Tallas y Cantidades
Talla: [M    ]  Cantidad: [10]  [✕]
Talla: [L    ]  Cantidad: [15]  [✕]
Talla: [XL   ]  Cantidad: [5]   [✕]

[+ Agregar talla]  ← Click para añadir más tallas
```

**Pasos:**
1. Escribe la talla: `M`, `L`, `XL`, etc.
2. Escribe la cantidad: `10`, `15`, etc.
3. Si necesitas más tallas, click en **"+ Agregar talla"**
4. Para eliminar una fila, click en **"✕"**

**¡Importante!**
- Las tallas son **IGUALES para todas las técnicas**
- Solo se escriben una vez
- Mínimo 1 talla

---

## Paso 4️⃣ - Click "Guardar"

Cuando hayas completado todo:

1. Verifica que todos los campos obligatorios estén llenos:
   - ✅ Prenda (no puede estar vacío)
   - ✅ Ubicación para cada técnica
   - ✅ Al menos 1 talla con cantidad

2. Click en **[Guardar]**

**¿Qué pasa automáticamente?**
```
✅ Se guarda la prenda en historial
✅ Se crean 2 registros en la DB (uno por técnica)
✅ Ambos comparten:
   - Misma prenda
   - Mismas tallas
   - PERO distintas ubicaciones
```

---

## Ejemplo Completo - Paso a Paso

### Escenario:
Crear cotización para camisas con BORDADO en pecho y ESTAMPADO en espalda.

### Proceso:

#### 1. Marcas técnicas
```
☑️ BORDADO
☑️ ESTAMPADO
```

#### 2. Click "Técnicas Combinadas" → Se abre modal

#### 3. Completas Prenda
```
Prenda: [c    ]
        • CAMISA ← Click

Resultado: [CAMISA]
```

#### 4. Completas Ubicaciones
```
Ubicaciones
BORDADO:    [PECHO   ]
ESTAMPADO:  [ESPALDA ]
```

#### 5. Observaciones (opcional)
```
[Bordado con hilo dorado]
```

#### 6. Tallas y Cantidades
```
Talla: [S   ]  Cantidad: [5  ]  [✕]
Talla: [M   ]  Cantidad: [10 ]  [✕]
Talla: [L   ]  Cantidad: [8  ]  [✕]
Talla: [XL  ]  Cantidad: [3  ]  [✕]
```

#### 7. Click "Guardar"
```
✅ Sistema guarda "CAMISA" en historial
✅ Crea 2 registros:
   - BORDADO + CAMISA + PECHO + S:5, M:10, L:8, XL:3
   - ESTAMPADO + CAMISA + ESPALDA + S:5, M:10, L:8, XL:3
```

---

## 💡 Tips y Trucos

### 🚀 Acelera tu trabajo
1. **Usa el autocomplete:** Después de crear algunas prendas, aparecerán en las sugerencias
2. **Reutiliza ubicaciones comunes:** PECHO, ESPALDA, MANGA (muy frecuentes)
3. **Copia-pega técnicas similares:** Si usas las mismas ubicaciones, el sistema las recordará

### ❌ Errores comunes
```
❌ Dejar Prenda vacía
   → Error: "Completa el nombre de la prenda"

❌ Olvidar una ubicación
   → Error: "Agrega ubicación para BORDADO"

❌ No agregar tallas
   → Error: "Agrega al menos una talla"

❌ Cantidad en 0 o vacía
   → Ese renglón se ignora (agrega talla válida)
```

### 🎯 ¿Cuándo usar Técnicas Combinadas?

**USA ESTO CUANDO:**
- ✅ 2 o más técnicas en la misma prenda
- ✅ Cada técnica va en un lugar diferente
- ✅ Las tallas son iguales para todas

**NO USES ESTO CUANDO:**
- ❌ Solo 1 técnica (usa el flujo normal)
- ❌ Cada técnica tiene tallas diferentes (crea registros separados)
- ❌ Son prendas distintas (CAMISA vs PANTALÓN)

---

## 📞 Soporte

Si algo no funciona:

1. **Verifica que hayas marcado 2+ técnicas**
   - El botón solo aparece si hay 2+ técnicas

2. **Completa todos los campos obligatorios**
   - Prenda, Ubicaciones, Tallas

3. **Usa MAYÚSCULAS**
   - El sistema convierte automáticamente

4. **Si el dropdown no aparece:**
   - Escribe por lo menos 1 letra
   - Presiona lentamente (no muy rápido)

---

## 📊 Resumen Rápido

| Acción | Resultado |
|--------|-----------|
| Marca 2 técnicas | Aparece botón "Técnicas Combinadas" |
| Click botón | Abre modal con 4 campos |
| Escribe prenda | Autocomplete con sugerencias |
| Completa ubicaciones | Una por técnica |
| Agrega tallas | Iguales para todas |
| Click "Guardar" | Guarda prenda + crea 2 registros |

---

**¿Listo?** ¡Prueba ahora mismo y acelera tus cotizaciones! 🚀

