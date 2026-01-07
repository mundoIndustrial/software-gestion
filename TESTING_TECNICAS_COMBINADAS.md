# 🧪 GUÍA DE TESTING - Técnicas Combinadas con Grupo Combinado

## Problema Resuelto ✅

**Antes:** Las técnicas combinadas con la misma prenda, ubicaciones diferentes y tallas iguales NO aparecían agrupadas.

**Ahora:** El sistema genera automáticamente un `grupo_combinado` único para cada bundle de técnicas combinadas.

---

## Cómo Probar en http://servermi:8000/asesores/cotizaciones/bordado/crear

### 🎯 Escenario de Prueba

Crear una cotización con técnicas combinadas:
- **Prenda:** POLO
- **Técnica 1:** BORDADO en PECHO
- **Técnica 2:** ESTAMPADO en ESPALDA
- **Tallas:** M:10, L:15, XL:5 (iguales para ambas)

---

## Paso a Paso

### 1️⃣ Seleccionar Técnicas
```
Marca los checkboxes:
☑ BORDADO
☐ TEJIDO
☑ ESTAMPADO
☐ SUBLIMACIÓN

Resultado: Aparece botón "Técnicas Combinadas"
```

### 2️⃣ Click en "Técnicas Combinadas"
```
Se abre modal minimalista (gris/blanco) con:
- Prenda
- Ubicaciones (una por técnica)
- Observaciones
- Tallas y Cantidades
```

### 3️⃣ Completa Prenda
```
Input: [POLO]
(Se convierte automáticamente a MAYÚSCULAS)

Aparecen sugerencias si existen:
▼ POLO
▼ PANTALÓN
▼ CAMISA

Click en POLO o escribe POLO completo
```

### 4️⃣ Completa Ubicaciones
```
BORDADO:    [PECHO   ]
ESTAMPADO:  [ESPALDA ]

(Sin placeholders, títulos claros)
```

### 5️⃣ Completa Tallas
```
Talla       Cantidad
[M    ]     [10]    [✕]
[L    ]     [15]    [✕]
[XL   ]     [5 ]    [✕]

+ Agregar talla (si necesitas más)
```

### 6️⃣ Click "Guardar"
```
✅ El sistema guarda la prenda "POLO" en historial
✅ Genera grupo_combinado único (ej: 1704700000000)
✅ Actualiza la tabla
```

---

## Verificación en Tabla

### 🔍 Busca estos indicadores de éxito:

```
┌──────────────────────────────────────────────────────────────────┐
│ Técnica(s)              │ Prenda │ Ubicaciones        │ ... │ ... │
├──────────────────────────────────────────────────────────────────┤
│ 🔗 COMBINADA            │        │                    │     │ ✕   │
│ BORDADO                 │ POLO   │ PECHO              │ M:10│     │
│ ESTAMPADO               │        │ ESPALDA            │ L:15│     │
│                         │        │                    │ XL:5│     │
└──────────────────────────────────────────────────────────────────┘
```

### ✅ Checklist de Validación:

- [ ] Badge "🔗 COMBINADA" aparece en GRIS (no verde)
- [ ] Se muestran ambas técnicas (BORDADO + ESTAMPADO)
- [ ] Ubicaciones diferentes (PECHO vs ESPALDA)
- [ ] Tallas iguales para ambas (M:10, L:15, XL:5)
- [ ] Botón eliminar es GRIS con X simple (no rojo)
- [ ] Tabla tiene estilo minimalista (fondo gris claro)

---

## Verificación en Consola del Navegador (F12)

Abre la consola de desarrollador y busca:

```javascript
// Debe mostrar esto al guardar:
✅ Guardando técnicas combinadas con 2 técnicas
📦 Datos del formulario: { ... }
🔗 Grupo combinado asignado: 1704700000000
📊 Total técnicas agregadas: 2
```

---

## En la Base de Datos (Opcional)

Si tienes acceso a la BD:

```sql
SELECT * FROM logo_cotizacion_tecnica_prendas 
WHERE grupo_combinado IS NOT NULL
ORDER BY grupo_combinado DESC;
```

Debería mostrar:
```
ID | logo_cotizacion_id | tipo_logo_id | nombre_prenda | ubicaciones | grupo_combinado
---+--------------------+--------------+---------------+-------------+------------------
1  | 123                | 1 (BORDADO)  | POLO          | PECHO       | 1704700000000
2  | 123                | 2 (ESTAMPADO)| POLO          | ESPALDA     | 1704700000000
   ↑ Mismo grupo_combinado = técnicas combinadas
```

---

## Casos de Uso Adicionales

### 📌 Caso 1: Técnicas Diferentes
```
BORDADO   + CAMISA + PECHO  + M:10
ESTAMPADO + CAMISA + ESPALDA + M:10
TEJIDO    + CAMISA + MANGA  + M:10

Result: Todas con el MISMO grupo_combinado
Badge: 🔗 COMBINADA
```

### 📌 Caso 2: Una Sola Técnica (control)
```
BORDADO + POLO + PECHO + M:10

Result: NO aparece badge (es un registro individual)
Comportamiento: Como antes (sin cambios)
```

### 📌 Caso 3: Prenda Nueva (auto-save)
```
Escribe: "JACKET"
Guardar: 
  → Se guarda JACKET en prendas_cotizaciones_tipos
  → Próxima vez aparecerá en autocomplete
```

---

## Si Algo No Funciona ❌

### Problema: No aparece badge "COMBINADA"
**Solución:**
- ¿Seleccionaste 2+ técnicas? ✓
- ¿Completaste todas las ubicaciones? ✓
- Abre F12 y verifica grupo_combinado en consola

### Problema: La tabla se ve "muy azul"
**Solución:**
- Recarga la página (Ctrl+F5 para borrar cache)
- Limpia cookies/cache del navegador

### Problema: Autocomplete no funciona
**Solución:**
- ¿Escribiste por lo menos 1 letra? ✓
- ¿Los prendas están en la BD? ✓
- Verifica en F12 la llamada a `/api/logo-cotizacion-tecnicas/prendas`

---

## Diferencias Visuales Antes vs Después

| Elemento | Antes | Ahora |
|----------|-------|-------|
| **Badge** | Verde (#10b981) | Gris (#ddd) |
| **Header tabla** | Azul gradiente | Gris claro (#f0f0f0) |
| **Botón eliminar** | Rojo con ícono | Gris con X |
| **Padding tabla** | 12px 16px | 10px 12px |
| **Bordes** | #e5e7eb | #eee |
| **Font size** | 0.9rem | 0.85rem |

---

## Resumen de Cambios

✅ **Frontend:** Genera grupo_combinado numérico único (timestamp + random)
✅ **Agrupación:** Tabla agrupa técnicas por grupo_combinado
✅ **Visual:** Badge minimalista TNS (gris)
✅ **UX:** Modal también minimalista (sin colores vivos)
✅ **Database:** Guardará grupo_combinado igual para técnicas combinadas

---

## Próximos Pasos

1. Prueba en desarrollo: http://servermi:8000/...
2. Verifica en F12 que grupo_combinado se genera
3. Verifica en tabla que aparece el badge "COMBINADA"
4. Verifica en BD que grupo_combinado es igual para ambas técnicas
5. Listo para producción ✅

---

**¿Preguntas?**
- Revisa `FIX_GRUPO_COMBINADO.md` para detalles técnicos
- Revisa `ACTUALIZACION_ESTILO_TNS.md` para cambios visuales
- Abre consola (F12) para ver logs detallados

