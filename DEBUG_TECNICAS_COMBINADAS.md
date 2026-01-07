# 🔍 DEBUG: TÉCNICAS COMBINADAS

## Resumen
Se han agregado **logs detallados** en la función `renderizarTecnicasAgregadas()` de `public/js/logo-cotizacion-tecnicas.js` para entender cómo se agrupan y renderizan las técnicas combinadas.

## Pasos para Probar

### 1. Abre la consola del navegador
- Presiona: **F12** o **Ctrl+Shift+I**
- Ve a la pestaña **Console**

### 2. Guarda una técnica COMBINADA nueva
- Ve a la página de crear técnicas combinadas
- Selecciona COMBINADA como tipo
- Selecciona 2 técnicas (ej: BORDADO + ESTAMPADO)
- Rellena los campos (prenda, ubicaciones, tallas)
- Haz clic en **Guardar**

### 3. Observa los logs en la consola
Deberías ver logs como estos:

```
✅ Guardando técnicas combinadas con 2 técnicas
📦 Datos del formulario: {nombre_prenda: 'CAMISA DRILL', ...}
→ BORDADO + CAMISA DRILL: {es_combinada: true, grupo_combinado: 1767826819}
→ ESTAMPADO + CAMISA DRILL: {es_combinada: true, grupo_combinado: 1767826819}
📊 Total técnicas agregadas: 2
🔗 Grupo combinado asignado: 1767826819

🎯 [renderizarTecnicasAgregadas] Inicio - Total técnicas: 2
🎯 Grupos visuales detectados: 1
  → Grupo: 1767826819, Items: 2, Técnicas: ['BORDADO', 'ESTAMPADO']

🔍 Renderizando grupo: 1767826819, esCombinadasIguales: true, items: 2
✨ RENDERIZANDO TÉCNICAS COMBINADAS para grupo 1767826819
✅ [renderizarTecnicasAgregadas] COMPLETADO - Tabla renderizada exitosamente
```

## Logs Esperados (por sección)

### 1️⃣ Agrupación (líneas 1730-1742)
```
🎯 [renderizarTecnicasAgregadas] Inicio - Total técnicas: 2
🎯 Grupos visuales detectados: 1
  → Grupo: 1767826819, Items: 2, Técnicas: ['BORDADO', 'ESTAMPADO']
```
**Esto muestra:**
- Total de técnicas en la lista: 2
- Número de grupos creados: 1 (porque ambas tienen el mismo grupo_combinado)
- Detalles de cada grupo

### 2️⃣ Procesamiento de grupos (líneas 1771-1778)
```
🔍 Renderizando grupo: 1767826819, esCombinadasIguales: true, items: 2
✨ RENDERIZANDO TÉCNICAS COMBINADAS para grupo 1767826819
```
**Esto muestra:**
- El grupo_combinado siendo procesado
- **esCombinadasIguales: true** = Va a mostrar una sola fila de encabezado con ambas técnicas
- **esCombinadasIguales: false** = Va a mostrar técnicas individuales

### 3️⃣ Finalización (línea 2006)
```
✅ [renderizarTecnicasAgregadas] COMPLETADO - Tabla renderizada exitosamente
```

## Casos de Prueba

### ✅ Caso 1: Técnicas COMBINADAS (BORDADO + ESTAMPADO)
- Guardas con tipo = COMBINADA
- Ambas técnicas reciben: **grupo_combinado: 1767826819**
- En tabla:
  - **Esperado:** Una fila con badge "🔗 COMBINADA" mostrando BORDADO + ESTAMPADO
  - **Si sale mal:** Aparecen como dos filas separadas

### ✅ Caso 2: Técnica INDIVIDUAL (BORDADO solo)
- Guardas con tipo = Individual
- Técnica recibe: **grupo_combinado: undefined** → se le asigna `individual-0`
- En tabla:
  - **Esperado:** Una fila normal SIN badge "🔗 COMBINADA"
  - **Logs muestran:** esCombinadasIguales = false → RENDERIZANDO TÉCNICA INDIVIDUAL

## Qué Revisar

1. **¿Aparecen los logs?** 
   - Si NO → El archivo JS no se está cargando
   - Si SÍ → Continúa al siguiente punto

2. **¿esCombinadasIguales dice true?**
   - Si true → La lógica de grouping FUNCIONA ✅
   - Si false → Las técnicas NO se están grouping (grupo_combinado diferente)

3. **¿La tabla se renderiza?**
   - Si aparece el badge 🔗 COMBINADA → FUNCIONA ✅
   - Si NO aparece → El HTML tiene un problema

4. **¿Ves "RENDERIZANDO TÉCNICAS COMBINADAS"?**
   - Si → El bloque if (esCombinadasIguales) se ejecuta
   - Si ves "RENDERIZANDO TÉCNICA INDIVIDUAL" → Las técnicas no se agruparon

## Próximos Pasos

Comparte en el chat:
1. **Captura de pantalla** de los logs (F12 → Console)
2. **Confirmación:** ¿Dónde dice esCombinadasIguales? ¿true o false?
3. **¿Cómo se ve la tabla?** ¿Una fila o dos filas para BORDADO + ESTAMPADO?

Con esta información podré identificar si:
- ✅ El grouping funciona (el problema es visual)
- ❌ El grouping NO funciona (grupo_combinado no se está asignando)
- ⚠️ El HTML no se renderiza correctamente
