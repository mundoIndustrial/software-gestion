# Checklist de Verificación - Optimizaciones Tablero Corte

## Antes de Empezar
- [ ] Abre el proyecto en VS Code
- [ ] Abre la consola del navegador (F12)
- [ ] Tab "Console" para ver logs
- [ ] Tab "Network" para ver tiempos de request

---

## Test 1: Performance Básico

### Paso 1.1 - Editar Hora
1. Abre tableros.blade.php → sección Corte
2. Busca una fila y haz doble clic en columna "Hora"
3. Ingresa un número (ej: 8)
4. Presiona Enter o click fuera

**Resultados esperados:**
- ✅ El campo se actualiza **rápidamente** (<1 segundo)
- ✅ Aparece el número/hora en la celda
- ✅ Console muestra: "✅ Celda actualizada INMEDIATAMENTE en el front"
- ✅ NO aparece error: `toUpperCase is not a function`

### Paso 1.2 - Editar Operario
1. Busca otra fila, haz doble clic en "Operario"
2. Ingresa un nombre (ej: JUAN)
3. Presiona Enter

**Resultados esperados:**
- ✅ Actualiza rápidamente
- ✅ Muestra "JUAN" (mayúscula)
- ✅ Console muestra caché hit: "✅ operario obtenido del caché"

### Paso 1.3 - Editar Máquina
1. Doble clic en "Máquina"
2. Ingresa nombre (ej: MAQUINA A)
3. Presiona Enter

**Resultados esperados:**
- ✅ Rápido
- ✅ Muestra nombre completo

### Paso 1.4 - Editar Tela
1. Doble clic en "Tela"
2. Ingresa nombre (ej: ALGODON)
3. Presiona Enter

**Resultados esperados:**
- ✅ Rápido
- ✅ Muestra nombre

---

## Test 2: Caché Funcionando

### Paso 2.1 - Repetir Búsqueda
1. Edita otro operario con el MISMO nombre anterior
2. Mira la consola

**Resultados esperados:**
- ✅ Console muestra: "✅ operario obtenido del caché" (NO hace HTTP request)
- ✅ La actualización es inmediata

### Paso 2.2 - Network Tab
1. Abre Network tab
2. Edita una hora que ya habías editado antes
3. Mira las requests

**Resultados esperados:**
- ✅ NO hay request POST a `/find-hora-id`
- ✅ Viene del caché

---

## Test 3: Reload de Página

### Paso 3.1 - Reload Completo
1. Edita una celda de operario, máquina o tela
2. Presiona F5 para reload completo

**Resultados esperados:**
- ✅ Después del reload, la celda sigue mostrando el **nombre**, no el ID
- ✅ Si editas "JUAN", después de F5 sigue mostrando "JUAN"
- ✅ NO muestra ID como "15"

### Paso 3.2 - Reload con Filtros
1. Aplica un filtro de fecha
2. Edita una hora
3. Aplica otro filtro (el mismo)

**Resultados esperados:**
- ✅ Sigue mostrando la hora actualizada (nombre, no ID)
- ✅ El filter todavía funciona

---

## Test 4: Relaciones en Broadcast (Tiempo Real)

### Paso 4.1 - Dos Navegadores
1. Abre la misma tabla en otra ventana/navegador
2. En la primera ventana, edita una celda (operario, máquina, tela)
3. Mira la segunda ventana

**Resultados esperados:**
- ✅ El cambio aparece en la segunda ventana con el **nombre**, no ID
- ✅ Console muestra: "🎉 Evento CorteRecordCreated recibido"

---

## Test 5: Console Logs

### Verificar que todos estos logs aparecen:

**Al editar hora:**
```
📝 Columna original: hora, Columna mapeada: hora_id
✅ Celda actualizada INMEDIATAMENTE en el front: 8
📤 Enviando PATCH a /tableros/123
📥 Respuesta HTTP: 200
✅ Respuesta del servidor: {success: true}
✅ Celda re-confirmada con: 8 (es el nombre, no el ID)
```

**Al editar operario (segunda vez):**
```
✅ operario obtenido del caché: {id: 5, name: "JUAN"}
📝 Columna original: operario, Columna mapeada: operario_id
✅ Celda actualizada INMEDIATAMENTE en el front: JUAN
```

**Al recibir evento en tiempo real:**
```
🎉 Evento CorteRecordCreated recibido! {registro: {...}}
Registro 123 ya existe, actualizando...
```

---

## Test 6: Network Performance

### Paso 6.1 - Medir Tiempo de PATCH
1. Abre Developer Tools → Network tab
2. Edita una hora (primera vez, no en caché)
3. Mira la request PATCH

**Resultados esperados:**
- ✅ Time: **< 200ms** (idealmente 50-100ms para hora ahora con índice)
- ❌ NO debería ser **800ms+** (lo que era sin índice)
- ❌ Definitivamente NO debería ser **4000ms** (4 segundos)

### Paso 6.2 - Comparar Hora vs Operario/Máquina
1. Abre Network tab
2. Edita una HORA (POST a /find-hora-id)
3. Mira el tiempo
4. Luego edita un OPERARIO (POST a /find-or-create-operario)
5. Mira el tiempo

**Resultados esperados:**
- ✅ Ambos deberían tomar **~50-100ms** (ahora que hora tiene índice)
- ❌ Hora NO debería ser más lenta que operario
- 🎯 Si antes hora era mucho más lenta, ahora debería ser igual

### Paso 6.3 - Caché Hit
1. Edita la misma hora nuevamente
2. Network tab

**Resultados esperados:**
- ✅ NO hay request POST a `/find-hora-id`
- ✅ Viene directo del caché
- ✅ Request PATCH es todavía rápida

---

## Test 7: Error Handling

### Paso 7.1 - Valor Inválido
1. Edita una hora con valor invalido (ej: 99)
2. Presiona Enter

**Resultados esperados:**
- ✅ Alert mostrando error: "Error al procesar hora"
- ✅ Console muestra: "❌ Error al buscar/crear: ..."
- ✅ Celda NO se actualiza

### Paso 7.2 - Operario Nuevo
1. Edita operario con nombre completamente nuevo (ej: PEPE NUEVO)
2. Presiona Enter

**Resultados esperados:**
- ✅ Se crea el operario automáticamente
- ✅ Celda muestra "PEPE NUEVO"
- ✅ Siguiente vez que edites "PEPE NUEVO", viene del caché

---

## Test 8: Event Delegation

### Verificación (sin acción visible)
1. En Console, ejecuta:
```javascript
document.querySelectorAll('table[data-section="corte"] tbody tr td.editable-cell').length
```

2. Ahora cuenta los event listeners:
```javascript
getEventListeners(document).dblclick
```

**Resultados esperados:**
- ✅ Hay 200+ celdas editables
- ✅ Hay solo 1 listener dblclick en document (no 200+)

---

## Resumen de Checklist

| Test | Items | Todos OK? |
|------|-------|-----------|
| 1. Performance | 4 ediciones rápidas | ☐ |
| 2. Caché | 2 verificaciones | ☐ |
| 3. Reload | 2 scenarios | ☐ |
| 4. Broadcast | Tiempo real entre navegadores | ☐ |
| 5. Console | Logs aparecer correctamente | ☐ |
| 6. Network | <500ms requests | ☐ |
| 7. Errores | Manejo correcto | ☐ |
| 8. Delegation | 1 listener para 200+ celdas | ☐ |

---

## Si Algo Falla

### Problema: "4 segundos de delay"
- ✅ Verifica que en `saveCellEdit()` se llama `fetch()` UNA vez, no 4
- ✅ Network tab debe mostrar 1 request, no 4

### Problema: "Ver ID en lugar de nombre"
- ✅ Verifica que `displayName` tiene el valor correcto antes de actualizar
- ✅ Console debe mostrar: "Celda re-confirmada con: JUAN"
- ✅ Verifica que la vista Blade accede a `$registro->operario->name`

### Problema: "TypeError: toUpperCase is not a function"
- ✅ Verifica que hora NO tiene `.toUpperCase()` aplicado
- ✅ Solo operario/máquina/tela deberían tener `.toUpperCase()`

### Problema: "Caché no funciona"
- ✅ Verifica que `searchCache` está inicializado
- ✅ Console debe mostrar caché hits

### Problema: "Reload muestra ID"
- ✅ Abre Sources → DB para verificar que las relaciones se guardaron
- ✅ Abre server logs para ver si hay error al cargar relaciones

---

## Notas

- Todos los tests deberían completarse en **< 3 minutos**
- Los logs de console son muy específicos - si no aparecen, algo está mal
- El "antes y después" de performance debería ser obviamente diferente

