# Test Plan - Sistema de Tabs para Crear Pedidos desde Cotizaciones

## 🎯 Objetivos de Prueba

Verificar que el sistema de tabs funciona correctamente para crear pedidos desde cotizaciones combinadas (PL), con especial énfasis en el tab de Logo que muestra toda la información de Bordado/Logo.

---

## 📋 Casos de Prueba

### Test 1: Cotización Tipo Prendas Solamente (P)
**Objetivo:** Verificar que solo aparece el tab de PRENDAS

**Pasos:**
1. Navegar a: `/asesores/pedidos-produccion/crear-desde-cotizacion`
2. En PASO 1, buscar y seleccionar una cotización tipo P (solo prendas)
3. En PASO 3, verificar:
   - [ ] Solo aparece 1 tab: "📦 PRENDAS"
   - [ ] No aparece el tab "🎨 LOGO"
   - [ ] Tab PRENDAS está activo por defecto
   - [ ] Se cargan las prendas correctamente

**Expected Result:** ✅ Solo tab de prendas visible

---

### Test 2: Cotización Tipo Logo Solamente (L)
**Objetivo:** Verificar que solo aparece el tab de LOGO

**Pasos:**
1. Navegar a formulario de crear pedido
2. En PASO 1, buscar y seleccionar una cotización tipo L (solo logo)
3. En PASO 3, verificar:
   - [ ] Solo aparece 1 tab: "🎨 LOGO"
   - [ ] No aparece el tab "📦 PRENDAS"
   - [ ] Tab LOGO está activo automáticamente
   - [ ] Se carga la información del logo

**Expected Result:** ✅ Solo tab de logo visible y activo

---

### Test 3: Cotización Combinada (PL)
**Objetivo:** Verificar que aparecen ambos tabs y funcionan correctamente

**Pasos:**
1. Navegar a formulario de crear pedido
2. En PASO 1, buscar y seleccionar una cotización tipo PL (combinada)
3. En PASO 3, verificar:
   - [ ] Aparecen 2 tabs: "📦 PRENDAS" y "🎨 LOGO"
   - [ ] Tab PRENDAS está activo por defecto
   - [ ] Se cargan las prendas correctamente

**Expected Result:** ✅ Ambos tabs visible, PRENDAS activo

---

### Test 4: Tab PRENDAS - Visualización de Datos
**Objetivo:** Verificar que las prendas se renderizan correctamente

**Pasos:**
1. Seleccionar cotización combinada (PL) o solo prendas (P)
2. En tab PRENDAS, verificar para cada prenda:
   - [ ] Nombre de la prenda visible
   - [ ] Tabla de tallas con cantidades editable
   - [ ] Botón para eliminar prenda (si aplica)
   - [ ] Información de telas (si aplica)
   - [ ] Estilos visuales correctos (colores, sombras, etc.)

**Expected Result:** ✅ Todas las prendas se muestran correctamente con datos editables

---

### Test 5: Tab LOGO - Visualización de Descripción
**Objetivo:** Verificar que se muestra la descripción del logo correctamente

**Pasos:**
1. Seleccionar cotización combinada (PL) o solo logo (L)
2. Hacer click en tab "🎨 LOGO"
3. Verificar:
   - [ ] Se muestra sección "📝 Descripción del Logo"
   - [ ] Texto se muestra con preservación de saltos de línea
   - [ ] Fondo gris claro con borde azul
   - [ ] Si no hay descripción, muestra "Sin descripción"

**Expected Result:** ✅ Descripción del logo visible y formateada

---

### Test 6: Tab LOGO - Visualización de Técnicas
**Objetivo:** Verificar que las técnicas se muestran como badges de color

**Pasos:**
1. Tab LOGO activo
2. Verificar sección "🎯 Técnicas":
   - [ ] Aparecen badges para cada técnica
   - [ ] BORDADO → Verde (#4CAF50)
   - [ ] DTF → Azul (#2196F3)
   - [ ] ESTAMPADO → Naranja (#FF9800)
   - [ ] SUBLIMADO → Púrpura (#9C27B0)
   - [ ] Texto blanco en badges
   - [ ] Si no hay técnicas, muestra texto informativo

**Expected Result:** ✅ Técnicas mostradas con colores correctos

---

### Test 7: Tab LOGO - Visualización de Ubicaciones
**Objetivo:** Verificar que las ubicaciones se muestran correctamente

**Pasos:**
1. Tab LOGO activo
2. Verificar sección "📍 Ubicaciones":
   - [ ] Se muestra ubicación principal (ej: CAMISA)
   - [ ] Se muestran opciones anidadas (ej: PECHO, ESPALDA)
   - [ ] Formato legible con indentación
   - [ ] Separación clara entre ubicaciones
   - [ ] Si no hay ubicaciones, muestra texto informativo

**Expected Result:** ✅ Ubicaciones mostradas en formato jerárquico

---

### Test 8: Tab LOGO - Visualización de Observaciones
**Objetivo:** Verificar que las observaciones técnicas se muestran

**Pasos:**
1. Tab LOGO activo
2. Verificar sección "📋 Observaciones Técnicas":
   - [ ] Aparece sección (si hay datos)
   - [ ] Fondo amarillo claro (#fffde7)
   - [ ] Borde izquierdo dorado (#FBC02D)
   - [ ] Preserva saltos de línea
   - [ ] Si no hay observaciones, sección se oculta

**Expected Result:** ✅ Observaciones mostradas con estilo destacado

---

### Test 9: Tab LOGO - Galería de Fotos
**Objetivo:** Verificar que las fotos del logo se muestran en galería

**Pasos:**
1. Tab LOGO activo
2. Verificar sección "🖼️ Galería de Fotos":
   - [ ] Se muestran miniaturas en grid
   - [ ] Grid responsivo (cambia columnas según pantalla)
   - [ ] Cada foto tiene sombra y bordes redondeados
   - [ ] Contador de fotos "(X)" aparece en título

**Expected Result:** ✅ Galería de fotos visible en grid responsivo

---

### Test 10: Tab LOGO - Interacción con Fotos
**Objetivo:** Verificar que las fotos son interactivas

**Pasos:**
1. Tab LOGO activo con fotos disponibles
2. Hacer hover sobre una foto:
   - [ ] Aparece efecto visual (fondo oscuro)
   - [ ] Se muestra icono de lupa 🔍
   - [ ] Cursor cambia a pointer
3. Hacer click en la foto:
   - [ ] Se abre un modal con la imagen ampliada
   - [ ] Fondo modal es semi-oscuro (rgba(0,0,0,0.95))
   - [ ] Imagen se centra en la pantalla
   - [ ] Aparece título de la foto
   - [ ] Aparece botón "✕ Cerrar"

**Expected Result:** ✅ Modal se abre con imagen ampliada

---

### Test 11: Modal de Foto - Interacción
**Objetivo:** Verificar que el modal funciona correctamente

**Pasos:**
1. Modal de foto abierto
2. Hacer click en botón "✕ Cerrar":
   - [ ] Modal se cierra
   - [ ] Vuelve a ver el tab de logo
3. Hacer click en el fondo (fuera de la imagen):
   - [ ] Modal se cierra
4. Presionar tecla Escape (si implementado):
   - [ ] Modal se cierra (opcional)

**Expected Result:** ✅ Modal cierra correctamente en múltiples formas

---

### Test 12: Cambio de Tabs
**Objetivo:** Verificar que se puede cambiar entre tabs sin problemas

**Pasos:**
1. Cotización combinada (PL) seleccionada
2. Tab PRENDAS activo
3. Hacer click en botón "🎨 LOGO":
   - [ ] Botón tab LOGO se resalta (active)
   - [ ] Botón tab PRENDAS se desactiva
   - [ ] Contenido de PRENDAS se oculta (display: none)
   - [ ] Contenido de LOGO se muestra (display: block)
   - [ ] Transición suave (si hay CSS)
4. Hacer click nuevamente en "📦 PRENDAS":
   - [ ] Vuelve a tab PRENDAS
   - [ ] Datos de prendas están intactos

**Expected Result:** ✅ Cambio de tabs fluido sin perder datos

---

### Test 13: Cotización sin Logo
**Objetivo:** Verificar comportamiento cuando logo no tiene datos

**Pasos:**
1. Seleccionar cotización combinada (PL) pero sin datos de logo
2. Hacer click en tab LOGO:
   - [ ] Se muestra "No hay información de logo disponible"
   - [ ] No hay errores en consola (F12)
   - [ ] No se renderizan secciones vacías

**Expected Result:** ✅ Manejo gracioso de datos faltantes

---

### Test 14: Formulario Completo - Envío
**Objetivo:** Verificar que el formulario se envía correctamente

**Pasos:**
1. Seleccionar cotización combinada (PL)
2. En tab PRENDAS:
   - [ ] Editar cantidades de prendas
   - [ ] Verificar que se pueden eliminar prendas
3. En tab LOGO:
   - [ ] Verificar que la información del logo está visible
4. En PASO 4, hacer click en "Crear Pedido":
   - [ ] Se muestra confirmación
   - [ ] Se crean 2 pedidos (uno de prendas, uno de logo)
   - [ ] Se muestran números de ambos pedidos
   - [ ] Se redirige correctamente

**Expected Result:** ✅ Formulario se envía y crea ambos pedidos

---

### Test 15: Responsive Design
**Objetivo:** Verificar que interfaz se adapta a diferentes tamaños de pantalla

**Pasos:**
1. Abrir en Desktop (1920px):
   - [ ] Todos los elementos visibles
   - [ ] Grid de fotos con múltiples columnas
2. Abrir en Tablet (768px):
   - [ ] Tabs se vuelven a acomodar (si es responsive)
   - [ ] Grid de fotos con menos columnas
3. Abrir en Mobile (360px):
   - [ ] Tabs siguen siendo clickeables
   - [ ] Grid de fotos una columna
   - [ ] Scroll horizontal no necesario para contenido principal

**Expected Result:** ✅ Interfaz responsiva en todos los tamaños

---

## 🔍 Verificaciones Técnicas

### Consola del Navegador (F12)
- [ ] No hay errores rojo (Errors)
- [ ] No hay warnings naranja relevantes
- [ ] Se ve log: "🎨 Renderizando logo en tab: {objeto}"
- [ ] No hay referencias a funciones indefinidas

### Network (F12 → Network)
- [ ] Petición AJAX a `/obtener-datos-cotizacion/{id}` es exitosa (200 OK)
- [ ] Response contiene estructura correcta (prendas, logo, etc.)
- [ ] Imágenes de fotos cargan correctamente (200 OK)

### Performance
- [ ] No hay lag al cambiar tabs
- [ ] Renderización es rápida (<100ms)
- [ ] No hay memory leaks (consola Memoria)

---

## 📊 Matriz de Casos Críticos

| Test | P (Prendas) | L (Logo) | PL (Combinada) | Prioridad |
|------|-------------|---------|----------------|-----------|
| 1    | ✓           | -       | -              | 🔴 Alta  |
| 2    | -           | ✓       | -              | 🔴 Alta  |
| 3    | -           | -       | ✓              | 🔴 Alta  |
| 4    | ✓           | -       | ✓              | 🟡 Media |
| 5    | -           | ✓       | ✓              | 🔴 Alta  |
| 6    | -           | ✓       | ✓              | 🟡 Media |
| 7    | -           | ✓       | ✓              | 🟡 Media |
| 8    | -           | ✓       | ✓              | 🟢 Baja  |
| 9    | -           | ✓       | ✓              | 🔴 Alta  |
| 10   | -           | ✓       | ✓              | 🔴 Alta  |
| 11   | -           | ✓       | ✓              | 🔴 Alta  |
| 12   | -           | -       | ✓              | 🔴 Alta  |
| 13   | -           | -       | ✓              | 🟢 Baja  |
| 14   | ✓           | -       | ✓              | 🔴 Alta  |
| 15   | ✓           | ✓       | ✓              | 🟡 Media |

---

## 📝 Resultados de Prueba

| Test | Estado | Notas |
|------|--------|-------|
| 1    | ⏳ Pending | - |
| 2    | ⏳ Pending | - |
| 3    | ⏳ Pending | - |
| 4    | ⏳ Pending | - |
| 5    | ⏳ Pending | - |
| 6    | ⏳ Pending | - |
| 7    | ⏳ Pending | - |
| 8    | ⏳ Pending | - |
| 9    | ⏳ Pending | - |
| 10   | ⏳ Pending | - |
| 11   | ⏳ Pending | - |
| 12   | ⏳ Pending | - |
| 13   | ⏳ Pending | - |
| 14   | ⏳ Pending | - |
| 15   | ⏳ Pending | - |

---

## 🎬 Conclusión

Este plan de pruebas cubre:
- ✅ Todos los tipos de cotización (P, L, PL, RF)
- ✅ Visualización de datos del logo
- ✅ Interactividad de galería de fotos
- ✅ Cambio de tabs
- ✅ Envío del formulario
- ✅ Responsive design
- ✅ Verificaciones técnicas

**Próximos pasos:** Ejecutar pruebas manualmente en navegador con datos reales.

---

**Documento versión:** 1.0
**Fecha de creación:** 2025
**Última actualización:** 2025
