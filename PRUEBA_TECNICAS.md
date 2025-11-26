# Prueba de Técnicas - Diagnóstico

## Problema Reportado
Las técnicas no se están guardando en la BD (tabla logo_cotizaciones.tecnicas llega vacío [])

## Pasos para Probar

### 1. Abre las DevTools del navegador (F12)
- Ve a la pestaña **Console**

### 2. Haz lo siguiente EN ORDEN:

#### Paso A: Verifica que el selector existe
```javascript
// Ejecuta en consola:
document.getElementById('selector_tecnicas')
```
**Debe mostrar:** El elemento select con las opciones (BORDADO, DTF, ESTAMPADO, SUBLIMADO)

#### Paso B: Verifica que el contenedor técnicas existe
```javascript
// Ejecuta en consola:
document.getElementById('tecnicas_seleccionadas')
```
**Debe mostrar:** Un div vacío

#### Paso C: Simula hacer click en el botón + para agregar técnica
```javascript
// Primero, selecciona una técnica en el dropdown
const selector = document.getElementById('selector_tecnicas');
selector.value = 'BORDADO';

// Luego llama a agregarTecnica()
agregarTecnica();
```
**Debe mostrar en consola:**
- 🔧 agregarTecnica() llamado
- 🔧 Selector encontrado: true
- 🔧 Técnica seleccionada: BORDADO
- ✅ Técnica agregada: BORDADO

#### Paso D: Verifica que el div se agregó
```javascript
// Ejecuta en consola:
document.getElementById('tecnicas_seleccionadas').innerHTML
```
**Debe mostrar:** Un div con un input hidden con value="BORDADO"

#### Paso E: Simula guardar con recopilarDatos()
```javascript
// Ejecuta en consola:
recopilarDatos()
```
**En la consola debe aparecer:**
- 🎨 Técnicas recopiladas: ["BORDADO"]
- ✅ Elementos encontrados: 1

---

## Posibles Resultados

### Si el Paso D muestra el div pero el Paso E muestra []
→ **PROBLEMA EN TIMING**: Las técnicas se agregan pero algo las borra antes de guardar
→ Solución: Revisar si hay código que limpia el contenedor

### Si el Paso C muestra error 
→ **PROBLEMA EN agregarTecnica()**: La función no se ejecuta correctamente
→ Verifica que haya hecho click en el botón + de la interfaz

### Si el Paso B muestra elemento no encontrado
→ **PROBLEMA EN HTML**: El elemento no existe en la página
→ Verifica que la página se cargó correctamente

---

## Logs Importantes a Buscar en Console

Cuando hagas click en "Guardar", busca:

```
🎨 Contenedor técnicas encontrado: true
🎨 innerHTML del contenedor: (debe mostrar el HTML del div con la técnica)
🎨 Número de children: (debe ser > 0)
🎨 Técnicas recopiladas: ["BORDADO", "DTF", ...] (debe tener valores)
🎨 Elementos encontrados: 3 (o el número de técnicas agregadas)
```

Si aparecen vacíos, el contenedor se limpió entre agregar técnicas y guardar.

---

## Prueba Rápida en la Página

1. Abre la cotización
2. En el apartado de "Bordado/Estampado"
3. Selecciona una técnica del dropdown
4. Haz click en el botón "+" azul
5. **Debe aparecer un badge azul con la técnica y una X para eliminar**
6. Haz click en "Guardar Cotización"
7. Abre DevTools (F12) → Console
8. Busca las líneas que empiezan con 🎨

---

## Reporte de Resultados

Por favor ejecuta los Pasos A-E y comparte:
- ¿Qué muestra cada paso?
- ¿Ves el badge azul con la técnica en la UI?
- ¿Qué logs aparecen en console cuando haces click en Guardar?
- ¿Aparece un error en la consola?

Esto nos ayudará a identificar exactamente dónde se están perdiendo las técnicas.
