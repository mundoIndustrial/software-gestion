# 🔍 GUÍA DE FILTROS TIPO EMBUDO - COTIZACIONES

## 📋 Descripción General

Se ha implementado un sistema completo de filtros tipo embudo (funnel filters) en la tabla de cotizaciones. Cada columna tiene un botón de filtro que abre un modal para configurar los criterios de búsqueda.

## 🎯 Características Principales

### 1. **Botones de Filtro en Columnas**
- ✅ Icono de embudo (funnel) en cada encabezado de columna
- ✅ Indicador visual cuando hay filtros activos (punto amarillo)
- ✅ Hover effect para mejor interactividad
- ✅ Responsive en todos los dispositivos

### 2. **Modales de Filtro**
Cada columna tiene su propio modal con:
- ✅ Título descriptivo con emoji
- ✅ Campo de entrada/selección según el tipo
- ✅ Botón "Limpiar" para resetear el filtro
- ✅ Botón "Aplicar" para ejecutar el filtro
- ✅ Cierre con ESC o click en X

### 3. **Columnas Filtrables**

#### 📅 **Fecha**
- Tipo: Búsqueda de texto
- Formato: DD/MM/YYYY
- Ejemplo: "15/12/2025"
- Búsqueda: Parcial (contiene)

#### 🔢 **Código**
- Tipo: Búsqueda de texto
- Ejemplo: "COT-2025-001"
- Búsqueda: Parcial (contiene)

#### 👤 **Cliente**
- Tipo: Búsqueda de texto
- Ejemplo: "Empresa XYZ"
- Búsqueda: Parcial (contiene)

#### 🏷️ **Tipo**
- Tipo: Selección (dropdown)
- Opciones:
  - Prenda
  - Logo
  - Prenda/Bordado
- Búsqueda: Exacta

#### ✅ **Estado**
- Tipo: Selección (dropdown)
- Opciones:
  - Enviada
  - Aprobada
  - Rechazada
  - Pendiente
- Búsqueda: Exacta

## 🚀 Cómo Usar

### Paso 1: Abrir Modal de Filtro
1. Haz clic en el icono de embudo (🔽) en la columna que deseas filtrar
2. Se abrirá un modal con el campo de filtro

### Paso 2: Ingresar Criterio
- **Para texto**: Escribe el valor a buscar
  - Ejemplo: "Juan" para buscar clientes que contengan "Juan"
  - Presiona ENTER o haz clic en "Aplicar"

- **Para selección**: Elige una opción del dropdown
  - Ejemplo: Selecciona "Prenda" para ver solo cotizaciones de prenda

### Paso 3: Aplicar Filtro
- Haz clic en el botón "Aplicar"
- La tabla se actualizará automáticamente
- El botón de filtro mostrará un punto amarillo indicando que hay un filtro activo

### Paso 4: Ver Resultados
- La tabla mostrará solo las filas que coincidan con el criterio
- Si no hay resultados, verás un mensaje: "🔍 No se encontraron resultados con los filtros aplicados"

## 🧹 Limpiar Filtros

### Opción 1: Limpiar un Filtro Individual
1. Abre el modal del filtro (haz clic en el embudo)
2. Haz clic en "Limpiar"
3. El filtro se resetea y la tabla se actualiza

### Opción 2: Limpiar Todos los Filtros
1. Haz clic en el botón flotante "❌ Limpiar Filtros" (esquina inferior derecha)
2. Se limpiarán todos los filtros activos
3. La tabla mostrará todas las cotizaciones nuevamente

**Nota**: El botón flotante solo aparece cuando hay al menos un filtro activo.

## 💡 Ejemplos de Uso

### Ejemplo 1: Filtrar por Cliente
1. Haz clic en el embudo de la columna "Cliente"
2. Escribe "Empresa"
3. Haz clic en "Aplicar"
4. Resultado: Se muestran solo cotizaciones de clientes que contengan "Empresa"

### Ejemplo 2: Filtrar por Tipo
1. Haz clic en el embudo de la columna "Tipo"
2. Selecciona "Prenda" del dropdown
3. Haz clic en "Aplicar"
4. Resultado: Se muestran solo cotizaciones de tipo "Prenda"

### Ejemplo 3: Filtrar por Múltiples Criterios
1. Abre el modal de "Cliente" y filtra por "XYZ"
2. Abre el modal de "Tipo" y selecciona "Prenda"
3. Abre el modal de "Estado" y selecciona "Enviada"
4. Resultado: Se muestran cotizaciones que cumplan TODOS los criterios

### Ejemplo 4: Limpiar Filtros
1. Haz clic en "❌ Limpiar Filtros" (esquina inferior derecha)
2. Todos los filtros se resetean
3. La tabla vuelve a mostrar todas las cotizaciones

## 🎨 Diseño Visual

### Botones de Filtro
- **Inactivo**: Gris claro, transparente
- **Activo**: Azul, con punto amarillo debajo
- **Hover**: Fondo azul claro, escala aumentada

### Modales
- **Fondo**: Overlay oscuro (50% opacidad)
- **Contenido**: Tarjeta blanca con sombra suave
- **Animación**: Desliza hacia arriba (slide-up)

### Tabla Filtrada
- **Sin resultados**: Mensaje amarillo con icono de búsqueda
- **Con resultados**: Filas visibles, otras ocultas

## ⌨️ Atajos de Teclado

- **ENTER**: Aplicar filtro (cuando estés en un campo de texto)
- **ESC**: Cerrar modal de filtro
- **Click fuera del modal**: Cerrar modal

## 🔧 Características Técnicas

### Almacenamiento
- Los filtros se guardan en la URL como parámetros de query
- Ejemplo: `?filter_cliente=XYZ&filter_tipo=Prenda`
- Puedes compartir URLs con filtros aplicados

### Performance
- Filtrado en tiempo real (sin recargar página)
- Búsqueda parcial (case-insensitive)
- Soporta múltiples filtros simultáneamente

### Compatibilidad
- ✅ Desktop (Chrome, Firefox, Safari, Edge)
- ✅ Tablet (iPad, Android)
- ✅ Móvil (iPhone, Android)
- ✅ Tema claro y oscuro

## 📱 Responsive Design

### Desktop (> 1024px)
- Botones de filtro visibles
- Modales centrados
- Tabla completa

### Tablet (768px - 1024px)
- Botones de filtro visibles
- Modales ajustados
- Tabla con scroll horizontal

### Móvil (< 768px)
- Botones de filtro visibles
- Modales a pantalla completa
- Tabla con scroll horizontal
- Font aumentado para mejor legibilidad

## 🐛 Troubleshooting

### Problema: El filtro no funciona
**Solución**: 
- Asegúrate de hacer clic en "Aplicar"
- Verifica que el valor ingresado sea correcto
- Intenta limpiar el filtro y volver a intentar

### Problema: El modal no se abre
**Solución**:
- Recarga la página
- Verifica que JavaScript esté habilitado
- Abre la consola (F12) para ver si hay errores

### Problema: Los resultados no coinciden
**Solución**:
- Recuerda que la búsqueda es parcial (contiene)
- Para "Tipo" y "Estado", la búsqueda es exacta
- Intenta con menos caracteres

## 📚 Archivos Relacionados

- **CSS**: `public/css/cotizaciones/filtros-embudo.css`
- **JavaScript**: `public/js/asesores/cotizaciones/filtros-embudo.js`
- **Vista**: `resources/views/asesores/cotizaciones/index.blade.php`

## ✨ Mejoras Futuras

- [ ] Agregar filtro por rango de fechas
- [ ] Agregar filtro por rango de números
- [ ] Agregar filtro avanzado (AND/OR)
- [ ] Agregar guardado de filtros personalizados
- [ ] Agregar exportación de resultados filtrados
- [ ] Agregar búsqueda por múltiples valores

## 📞 Soporte

Si encuentras algún problema o tienes sugerencias, contacta al equipo de desarrollo.

---

**Versión**: 1.0  
**Fecha**: Diciembre 2025  
**Estado**: ✅ Funcional
