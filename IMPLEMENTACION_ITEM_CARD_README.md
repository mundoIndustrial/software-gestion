# 📋 Implementación del Diseño de Item Card

## ✅ Archivos Creados/Modificados

### 1. **Componente Blade** ✓
- **Ubicación**: `resources/views/asesores/pedidos/components/item-card.blade.php`
- **Descripción**: Componente reutilizable que renderiza una card de item con toda la información
- **Props**:
  - `$item`: array con datos del item (nombre, descripción, ref, color, tela, imágenes, variaciones, tallas, procesos)
  - `$index`: índice del item en la lista

### 2. **Estilos CSS** ✓
- **Ubicación**: `public/css/componentes/item-card.css`
- **Características**:
  - Responsive (Desktop, Tablet, Mobile)
  - Secciones expandibles con animaciones
  - Jerarquía visual clara
  - Hover effects y transiciones suaves
  - Estilos para todos los elementos (meta datos, tallas, procesos, etc.)

### 3. **JavaScript de Interactividad** ✓
- **Ubicación**: `public/js/modulos/crear-pedido/components/item-card-interactions.js`
- **Funciones**:
  - `toggleSection(headerElement)`: Expande/contrae secciones
  - `handleEliminarItem(itemIndex)`: Elimina un item
  - `handleEditarItem(itemIndex)`: Prepara edición de item
  - `updateItemCardInteractions()`: Reinicializa event listeners

### 4. **Controlador API** ✓
- **Ubicación**: `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php`
- **Método**: `renderItemCard(Request $request)`
- **Endpoint**: `POST /api/pedidos-editable/render-item-card`
- **Respuesta**: JSON con HTML renderizado del componente

### 5. **Rutas API** ✓
- **Ubicación**: `routes/api-pedidos-editable.php`
- **Nueva Ruta**: `POST /api/pedidos-editable/render-item-card`

### 6. **Actualización de Vista** ✓
- **Ubicación**: `resources/views/asesores/pedidos/crear-pedido-nuevo.blade.php`
- **Cambios**:
  - Agregado link a `item-card.css`
  - Agregado script de `item-card-interactions.js`

### 7. **JavaScript de Gestión de Items** ✓
- **Ubicación**: `public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js`
- **Cambios**:
  - Nuevo método `renderizarItems()`: renderiza items de forma asíncrona
  - Nuevo método `obtenerItemCardHTML()`: obtiene HTML del componente via API
  - Nuevo método `renderizarItemFallback()`: fallback si API falla
  - Actualizado `actualizarVistaItems()` para usar la nueva estructura

---

## 🎯 Flujo de Renderización

```
1. Usuario agrega un item al pedido
   ↓
2. actualizarVistaItems() es llamado
   ↓
3. renderizarItems() renderiza cada item asincronamente
   ↓
4. obtenerItemCardHTML() hace POST a /api/pedidos-editable/render-item-card
   ↓
5. Controlador renderiza componente Blade y devuelve HTML
   ↓
6. HTML se inserta en el DOM
   ↓
7. updateItemCardInteractions() inicializa event listeners
   ↓
8. Usuario puede expandir secciones, editar o eliminar items
```

---

## 📱 Estructura de la Card

```
┌─────────────────────────────────────────────────────┐
│ [IMAGEN] │ NOMBRE DE PRENDA                    │ ⋮ │
│          │ Descripción de la prenda             │   │
│          │ REF: ABC123 | Color: Azul | Tela    │   │
│          │ [Imagen Tela Pequeña]                │   │
└─────────────────────────────────────────────────────┘
│
├─ ✚ Variaciones (Expandible)
│  ├─ Manga: Larga | Obs: Con puño
│  ├─ Bolsillos: Sí | Obs: Con cierre
│  ├─ Broche: Botones | Obs: Metálicos
│  └─ Reflectivo: Sí | Obs: Franja 5cm
│
├─ 👕 Tallas (Expandible)
│  ├─ HOMBRE
│  │  ├─ XS: 5
│  │  ├─ S: 8
│  │  └─ M: 10
│  └─ MUJER
│     ├─ XS: 3
│     └─ S: 4
│
├─ ⚙️ Procesos (Expandible)
│  ├─ [Estampado] [Bordado] [Reflectivo]
│
└─ [Editar] [Eliminar]
```

---

## 🔧 Cómo Usar

### Agregar un Ítem al Pedido
Los items se agregan automáticamente a través de las modales existentes. El diseño se renderiza dinámicamente.

### Expandir Secciones
Simplemente haz click en el header de cualquier sección:
- Variaciones
- Tallas
- Procesos

### Editar un Ítem
Haz click en el botón "Editar" (implementar lógica según necesidad)

### Eliminar un Ítem
Haz click en el botón "Eliminar" y confirma

---

## 🎨 Personalización

### Modificar Estilos
Edita `public/css/componentes/item-card.css`:
- Colores: Busca `#1e40af`, `#6b7280`, etc.
- Espacios: Busca `padding`, `gap`, `margin`
- Breakpoints responsivos: Busca `@media (max-width: ...)`

### Agregar Campos al Item
1. Actualiza el componente Blade `resources/views/asesores/pedidos/components/item-card.blade.php`
2. Agrega HTML nuevo con las clases apropiadas
3. Si necesita estilos nuevos, agregalos a `item-card.css`

### Agregar Nuevas Secciones Expandibles
Usa la estructura:
```html
<div class="card-section expandible" data-section="nombre">
  <div class="section-header" onclick="toggleSection(this)">
    <span class="section-titulo">
      <span class="icon">ICON</span> Título
    </span>
    <span class="section-toggle">▼</span>
  </div>
  
  <div class="section-content" style="display: none;">
    <!-- Contenido aquí -->
  </div>
</div>
```

---

## 🐛 Troubleshooting

### Las cards no se renderizan
1. Verifica que el endpoint `/api/pedidos-editable/render-item-card` esté disponible
2. Revisa la consola del navegador para errores
3. Verifica que el CSRF token esté presente en la página
4. Comprueba que la vista Blade existe en `resources/views/asesores/pedidos/components/item-card.blade.php`

### Las secciones no se expanden
1. Verifica que `item-card-interactions.js` esté cargado
2. Revisa que la estructura HTML sea correcta (`.section-header` debe estar seguido de `.section-content`)
3. Abre la consola y ejecuta `window.toggleSection` para confirmar que la función existe

### Estilos no se aplican
1. Limpia la caché del navegador (Ctrl+Shift+Delete)
2. Verifica que `item-card.css` esté incluido en la vista
3. Usa DevTools para inspeccionar los elementos y ver qué estilos se aplican

---

## 📝 Notas Técnicas

### Renderización Asíncrona
El JavaScript ahora renderiza items de forma asíncrona haciendo POST a un endpoint. Esto permite:
- Reutilizar lógica Blade
- Mantener consistencia entre renderizado lado servidor y cliente
- Facilitar cambios futuros sin tocar JavaScript

### Fallback
Si el endpoint no responde, se renderiza una version simplificada del item (fallback)

### Event Delegation
Los event listeners se reinicializan después de cada renderización para asegurar que funcionan correctamente

---

## 🚀 Próximos Pasos (Opcionales)

1. **Implementar Edición Inline**: Hacer que las cards sean editables en el mismo lugar
2. **Agregar Validaciones**: Mostrar errores de validación en la card
3. **Drag & Drop**: Permitir reordenar items
4. **Guardado Automático**: Guardar cambios automáticamente
5. **Preview PDF**: Mostrar preview del pedido antes de guardar

---

## 📞 Soporte

Si encuentras problemas:
1. Revisa los logs del servidor (`storage/logs/laravel.log`)
2. Abre DevTools (F12) y revisa la consola JavaScript
3. Verifica que todos los archivos estén en las ubicaciones correctas
4. Comprueba que las rutas y namespaces sean correctos
