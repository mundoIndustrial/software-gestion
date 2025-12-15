# 📝 GUÍA PASO A PASO: CÓMO DEBERÍA CREAR COTIZACIONES EL ASESOR

**Estado del Sistema:** ✅ Funcional con mejoras propuestas

---

## 🎯 FLUJO IDEAL - PASO A PASO

### PASO 1️⃣: ACCEDER AL FORMULARIO

```
📍 URL: /cotizaciones-prenda/crear
   └─ O: /cotizaciones-bordado/crear
   
Estado esperado:
├─ ✅ Formulario vacío
├─ ✅ Cliente logueado visto en esquina
├─ ✅ Campos requeridos marcados (*)
└─ ✅ Botones "Guardar Borrador" y "Enviar"
```

---

### PASO 2️⃣: SELECCIONAR CLIENTE

```
Acción:
├─ Usar autocomplete de cliente
│  └─ Escribe primeras letras
│  └─ Sistema busca en 973 clientes
│  └─ Selecciona uno
│
└─ O crear cliente nuevo
   └─ Si no existe en el sistema
   └─ Escribe nombre completo
   └─ Sistema lo crea automáticamente

Datos que se guardan:
├─ cliente_id (de BD clientes)
├─ cliente.nombre
└─ cliente.contacto
```

---

### PASO 3️⃣: SELECCIONAR TIPO DE VENTA (Solo Prenda)

```
Opciones:
├─ M (Muestra)
│  └─ Para presentar propuestas
│  └─ Cantidad: 1-5 unidades
│  └─ Plazo: 10-15 días
│
├─ P (Prototipo)  
│  └─ Para desarrollo
│  └─ Cantidad: 5-20 unidades
│  └─ Plazo: 20-30 días
│
└─ G (Grande)
   └─ Para producción
   └─ Cantidad: 100+ unidades
   └─ Plazo: 45-60 días

Impacto en cotización:
├─ Precio diferente por cantidad
├─ Técnicas disponibles cambian
└─ Plazo estimado cambia
```

---

### PASO 4️⃣: AGREGAR PRENDAS

```
Para CADA prenda que cotizar:

┌─ Búsqueda de prenda
│  ├─ Autocomplete: "Buscar prenda..."
│  ├─ Escribe: "Polo"
│  └─ Obtiene lista de polos disponibles
│
├─ Información básica
│  ├─ Nombre: "Polo Sport"
│  ├─ Cantidad: 100 unidades
│  ├─ Descripción: "Polo 100% algodón"
│  └─ Talla: XS, S, M, L, XL (con cantidades)
│
├─ Telas y Colores
│  ├─ Selecciona tela: "Piqué 100%"
│  ├─ Sube foto de tela (opcional)
│  ├─ Color: "Rojo"
│  └─ ¿Teñida especial? (Si/No)
│
├─ Variantes (si la prenda lo permite)
│  ├─ Tipo de manga: Corta/Larga/Sin manga
│  ├─ Tipo de broche: Botones/Cremallera/Nada
│  └─ Otros: Logo, etiqueta, etc.
│
├─ Técnicas a aplicar
│  ├─ ¿Bordado? Dónde y tamaño
│  ├─ ¿Estampado? Área y colores
│  ├─ ¿Serigrafía? Ubicación
│  └─ ¿Reflectivo? Áreas específicas
│
├─ FOTOS (hasta 5 por prenda)
│  ├─ 📷 Subir foto (arrastra o selecciona)
│  ├─ ✅ Sistema muestra preview
│  ├─ 🔄 Procesa a WebP automáticamente
│  ├─ 📏 Crea miniatura para lista
│  └─ 🗑️ Eliminar si cambias de idea
│
└─ Especificaciones técnicas
   ├─ Medidas exactas
   ├─ Tolerancias permitidas
   ├─ Acabados especiales
   └─ Referencias de color
```

---

### PASO 5️⃣: REVISIÓN ANTES DE GUARDAR

```
El sistema muestra RESUMEN:

┌─ INFORMACIÓN
│  ├─ Cliente: ACME Corporation
│  ├─ Tipo: Prenda / Prototipo (P)
│  ├─ Asesor: Tú (logueado)
│  └─ Fecha: Hoy
│
├─ PRENDAS AGREGADAS: 2
│  ├─ 1️⃣ Polo Sport × 100 unidades
│  │   ├─ Fotos: 3
│  │   ├─ Técnicas: Bordado + Estampado
│  │   └─ Precio estimado: $15,000
│  │
│  └─ 2️⃣ Pantalón Casual × 50 unidades
│      ├─ Fotos: 2
│      ├─ Técnicas: Solo etiqueta
│      └─ Precio estimado: $8,000
│
└─ TOTAL ESTIMADO: $23,000

❓ Validaciones:
├─ ✅ Cliente: Sí
├─ ✅ Mínimo 1 prenda: Sí (2)
├─ ✅ Al menos 1 foto: Sí (5)
├─ ⚠️ Observaciones técnicas: Vacías (recomendado rellenar)
└─ ⚠️ ¿Descargas PDF? (solo al enviar)
```

---

### PASO 6️⃣: OPCIONES DE GUARDADO

#### **OPCIÓN A: GUARDAR COMO BORRADOR** ✏️

```
Click en: "💾 Guardar como Borrador"

Qué pasa:
├─ POST /cotizaciones-prenda
├─ action = "borrador"
├─ estado = "BORRADOR"
├─ numero_cotizacion = NULL (sin número aún)
├─ es_borrador = true
│
Respuesta del servidor:
├─ ✅ "Cotización guardada como borrador"
├─ 💾 ID generado: 12345
├─ 📋 Puedes seguir editando
├─ ⏱️ Auto-guardará cada 30 segundos
│
Después:
├─ ✅ Aparece en /cotizaciones-prenda
├─ 📌 Estado: "BORRADOR" (amarillo)
├─ ✏️ Botón "Editar"
├─ 🗑️ Botón "Eliminar"
├─ ▶️ Botón "Enviar"
└─ 👁️ NO aparece con número
```

#### **OPCIÓN B: ENVIAR A APROBADOR** ✅

```
Click en: "📤 Enviar a Aprobador"

Validaciones que hace el sistema:
├─ ✅ Cliente seleccionado → Sí
├─ ✅ Mínimo 1 prenda → Sí
├─ ✅ Cada prenda tiene fotos → Sí
├─ ⚠️ Observaciones completas → (ADVERTENCIA)
│  └─ "Continuar sin observaciones?"
│
Si todo OK → Procede:
├─ 1️⃣ Abre TRANSACCIÓN en BD
│  ├─ Lee numero_secuencias (LOCK pessimista)
│  ├─ Genera número: COT-20251214-001
│  ├─ Guarda Cotizacion:
│  │  ├─ numero_cotizacion = COT-20251214-001
│  │  ├─ estado = "ENVIADA"
│  │  ├─ es_borrador = false
│  │  ├─ enviada_en = NOW()
│  │  └─ enviada_por = Auth::id()
│  └─ COMMIT transacción
│
├─ 2️⃣ Genera PDF automáticamente
├─ 3️⃣ Envía notificaciones
├─ 4️⃣ Crea entrada en historial
│
Respuesta inmediata:
├─ ✅ "Cotización #COT-20251214-001 enviada"
├─ 📄 Descarga automática de PDF
├─ 📍 Redirige a: /cotizaciones-prenda
└─ 🔒 Ya NO puede editar
```

---

### PASO 7️⃣: DESPUÉS DE GUARDAR

#### **SI FUE BORRADOR:**

```
Lista en /cotizaciones-prenda

Columnas que ve el asesor:
┌─ Nº | Cliente | Tipo | Prendas | Estado | Acciones
├─     │ ACME   │  P   │    2    │🟡 BORRADOR │ ✏️ 🗑️ ▶️
└─     │ TECH   │  M   │    3    │🟡 BORRADOR │ ✏️ 🗑️ ▶️

Puede:
├─ ✏️ Editar → Modifica campos
├─ 🗑️ Eliminar → Borra (confirmación)
└─ ▶️ Enviar → Pasa a ENVIADA (genera número)
```

#### **SI FUE ENVIADA:**

```
Lista en /cotizaciones-prenda

Columnas que ve el asesor:
┌─ Nº | Cliente | Tipo | Prendas | Estado | Acciones
├─ COT-20251214-001 │ ACME │ P │ 2 │🟢 ENVIADA │ 👁️ 📄
└─ COT-20251214-002 │ TECH │ M │ 3 │🟢 ENVIADA │ 👁️ 📄

Puede:
├─ 👁️ Ver detalles (solo lectura)
├─ 📄 Descargar PDF
└─ ❌ NO editar ni eliminar

Estado en el proceso:
├─ 🟢 ENVIADA (esperando aprobación)
├─ ⏳ Aprobador revisa
├─ 🟢 APROBADA (cliente puede verla)
└─ ❌ RECHAZADA (con motivo)
```

---

## 📊 COMPARATIVA: ACTUAL vs IDEAL

### Generación de Número de Cotización

```
ACTUAL (Problemático):
└─ POST /enviar
   ├─ Guarda cotización (numero = NULL)
   ├─ Retorna JSON { success: true }  ← Asesor piensa que ya tiene número
   └─ Job procesa DESPUÉS
      ├─ ⏳ Genera número (puede durar 5-10 seg)
      ├─ Actualiza numero_cotizacion
      ├─ Envía email
      └─ ¿Asesor recibe dos cambios? 😕

IDEAL (Sincrónico):
└─ POST /enviar [TRANSACCIÓN]
   ├─ Lock numero_secuencias
   ├─ Lee: último número = 042
   ├─ Genera: 043
   ├─ Guarda Cotizacion(numero = 043)
   ├─ Unlock
   ├─ Commit
   └─ Retorna JSON { success, numero: '043' }  ← Inmediato y seguro
```

---

## 🔒 SEGURIDAD EN CONCURRENCIA

```
ESCENARIO: Dos asesores envían al MISMO tiempo

┌─ ASESOR1 hace click en ENVIAR (14:30:00.000)
│  ├─ Solicita LOCK
│  ├─ Obtiene LOCK ✅
│  ├─ Lee último número: 042
│  ├─ Genera: 043
│  ├─ Guarda en BD
│  └─ Libera LOCK
│
├─ ASESOR2 hace click en ENVIAR (14:30:00.001) ← Casi simultáneo
│  ├─ Solicita LOCK
│  ├─ Espera... (ASESOR1 tiene LOCK)
│  ├─ ASESOR1 libera LOCK
│  ├─ ASESOR2 obtiene LOCK ✅
│  ├─ Lee último: 043 (actualizado)
│  ├─ Genera: 044
│  ├─ Guarda en BD
│  └─ Libera LOCK
│
Resultado:
├─ ASESOR1 → COT-20251214-043 ✅
└─ ASESOR2 → COT-20251214-044 ✅ (Sin colisión)
```

---

## ✨ VENTAJAS DEL FLUJO IDEAL

| Aspecto | Actual | Ideal |
|---------|--------|-------|
| **Número inmediato** | ❌ Después (job async) | ✅ Inmediato |
| **Seguridad concurrencia** | ⚠️ Posible colisión | ✅ 100% seguro (LOCK) |
| **User Experience** | 😕 Confuso | 😊 Claro |
| **Validación** | ❌ Mínima | ✅ Completa |
| **Transacciones** | ⚠️ Parciales | ✅ Atómicas |
| **Fotos con reintentos** | ❌ Falla todo | ✅ Reintentos auto |
| **Auto-save borrador** | ❌ No | ✅ Cada 30s |

---

## 🎬 PRÓXIMOS PASOS

### FASE 1: CRÍTICO (Esta semana)
```
1. Cambiar generación de número a SÍNCRONO
2. Agregar LOCK pessimista en numero_secuencias
3. Validación mínima frontend (cliente + 1 prenda + foto)
```

### FASE 2: IMPORTANTE (Próx 2 semanas)
```
1. Auto-save de borradores cada 30s
2. UI clara borrador ↔ envío
3. Fotos con reintentos automáticos
```

### FASE 3: MEJORAS (Próx mes)
```
1. Validaciones completas
2. Confirmaciones antes de enviar
3. Historial detallado por cotización
```

