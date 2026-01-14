# 📋 Propuesta de Diseño - Card de Item Agregado

## Problema Actual
La información del item es demasiado lineal y no aprovecha la jerarquía visual. Todos los datos tienen igual importancia visualmente.

## Solución Propuesta: Diseño de Card Modular

### Estructura de la Card

```
┌─────────────────────────────────────────────────┐
│ [Imagen Prenda]  │  NOMBRE DE PRENDA            │
│                  │  Descripción de la prenda    │
│                  │  REF: ABC123 | Color: Azul   │
│                  │  Tela: Algodón 100%          │
│                  │  [Imagen Tela Pequeña]       │
│                  │                          [⋮] │
└─────────────────────────────────────────────────┘
  ├─ Expandible: Variaciones  (manga, bolsillos, broche, reflectivo)
  ├─ Expandible: Tallas por Género
  ├─ Expandible: Procesos Asociados
  └─ Acciones: [Editar] [Eliminar]
```

---

## 🎨 Componentes Principales

### 1. **Header de la Card (Información Principal)**
- **Layout Horizontal** con imagen + datos
- Imagen pequeña pero visible (80x80px o 100x100px)
- Datos en 2 columnas dentro de la tarjeta

```html
<div class="card-header">
  <div class="card-imagen-contenedor">
    <img src="prenda.jpg" alt="Prenda" class="card-imagen-prenda">
  </div>
  <div class="card-datos-principales">
    <h3 class="card-titulo">Nombre de la Prenda</h3>
    <p class="card-descripcion">Descripción breve de la prenda</p>
    
    <div class="card-meta-grid">
      <div class="meta-item">
        <span class="meta-label">REF:</span>
        <span class="meta-valor">ABC-123</span>
      </div>
      <div class="meta-item">
        <span class="meta-label">Color:</span>
        <span class="meta-valor">Azul Navy</span>
      </div>
      <div class="meta-item">
        <span class="meta-label">Tela:</span>
        <span class="meta-valor">Algodón 100%</span>
        <img src="tela.jpg" alt="Tela" class="mini-imagen-tela">
      </div>
    </div>
  </div>
  
  <button class="btn-menu-expandible">⋮</button>
</div>
```

---

### 2. **Secciones Expandibles**

#### A. **Variaciones** (Dropdown)
```html
<div class="card-section expandible">
  <div class="section-header" onclick="toggleSection(this)">
    <span class="section-titulo">
      <i class="icon">✚</i> Variaciones
    </span>
    <span class="section-toggle">▼</span>
  </div>
  
  <div class="section-content" style="display: none;">
    <table class="variaciones-tabla">
      <tr>
        <td class="var-label">Manga:</td>
        <td class="var-valor">Larga | Obs: Con puño</td>
      </tr>
      <tr>
        <td class="var-label">Bolsillos:</td>
        <td class="var-valor">Sí (3) | Obs: Con cierre</td>
      </tr>
      <tr>
        <td class="var-label">Broche:</td>
        <td class="var-valor">Botones | Obs: Metálicos</td>
      </tr>
      <tr>
        <td class="var-label">Reflectivo:</td>
        <td class="var-valor">Sí | Obs: Franja 5cm</td>
      </tr>
    </table>
  </div>
</div>
```

#### B. **Tallas por Género** (Grid Compacto)
```html
<div class="card-section expandible">
  <div class="section-header" onclick="toggleSection(this)">
    <span class="section-titulo">
      <i class="icon">👕</i> Tallas (Total: 45 unidades)
    </span>
    <span class="section-toggle">▼</span>
  </div>
  
  <div class="section-content" style="display: none;">
    <div class="tallas-por-genero">
      
      <!-- HOMBRE -->
      <div class="genero-grupo">
        <h5 class="genero-titulo">Hombre</h5>
        <div class="tallas-grid">
          <div class="talla-item">
            <span class="talla-valor">XS</span>
            <span class="talla-cantidad">5</span>
          </div>
          <div class="talla-item">
            <span class="talla-valor">S</span>
            <span class="talla-cantidad">8</span>
          </div>
          <div class="talla-item">
            <span class="talla-valor">M</span>
            <span class="talla-cantidad">10</span>
          </div>
          <div class="talla-item">
            <span class="talla-valor">L</span>
            <span class="talla-cantidad">7</span>
          </div>
        </div>
      </div>
      
      <!-- MUJER -->
      <div class="genero-grupo">
        <h5 class="genero-titulo">Mujer</h5>
        <div class="tallas-grid">
          <div class="talla-item">
            <span class="talla-valor">XS</span>
            <span class="talla-cantidad">3</span>
          </div>
          <div class="talla-item">
            <span class="talla-valor">S</span>
            <span class="talla-cantidad">4</span>
          </div>
          <div class="talla-item">
            <span class="talla-valor">M</span>
            <span class="talla-cantidad">8</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
```

#### C. **Procesos Asociados** (Badges/Pills)
```html
<div class="card-section expandible">
  <div class="section-header" onclick="toggleSection(this)">
    <span class="section-titulo">
      <i class="icon">⚙️</i> Procesos (3 procesos)
    </span>
    <span class="section-toggle">▼</span>
  </div>
  
  <div class="section-content" style="display: none;">
    <div class="procesos-lista">
      <span class="proceso-badge">Estampado</span>
      <span class="proceso-badge">Bordado</span>
      <span class="proceso-badge">Reflectivo</span>
    </div>
  </div>
</div>
```

---

### 3. **Acciones (Footer)**
```html
<div class="card-footer">
  <button class="btn btn-secondary btn-small">
    <span class="icon">✏️</span> Editar
  </button>
  <button class="btn btn-danger btn-small">
    <span class="icon">🗑️</span> Eliminar
  </button>
</div>
```

---

## 🎯 CSS Recomendado

```css
/* Card General */
.item-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.item-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border-color: #d1d5db;
}

/* Header */
.card-header {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.card-imagen-contenedor {
  flex-shrink: 0;
}

.card-imagen-prenda {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.card-datos-principales {
  flex: 1;
}

.card-titulo {
  margin: 0 0 0.25rem 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #1e40af;
}

.card-descripcion {
  margin: 0 0 0.5rem 0;
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.4;
}

.card-meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 0.75rem;
  font-size: 0.8rem;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.meta-label {
  font-weight: 600;
  color: #4b5563;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.meta-valor {
  color: #1e40af;
  font-weight: 500;
}

.mini-imagen-tela {
  width: 30px;
  height: 30px;
  object-fit: cover;
  border-radius: 3px;
  border: 1px solid #d1d5db;
  margin-top: 0.25rem;
}

/* Secciones Expandibles */
.card-section {
  border-top: 1px solid #f3f4f6;
  padding-top: 0.75rem;
  margin-top: 0.75rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem;
  cursor: pointer;
  user-select: none;
  border-radius: 4px;
  transition: background 0.2s;
}

.section-header:hover {
  background: #f9fafb;
}

.section-titulo {
  font-weight: 600;
  color: #374151;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-toggle {
  color: #9ca3af;
  transition: transform 0.3s;
}

.section-header.active .section-toggle {
  transform: rotate(180deg);
}

.section-content {
  padding: 0.75rem 0.5rem;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Variaciones */
.variaciones-tabla {
  width: 100%;
  font-size: 0.85rem;
  border-collapse: collapse;
}

.variaciones-tabla tr {
  border-bottom: 1px solid #f3f4f6;
}

.variaciones-tabla tr:last-child {
  border-bottom: none;
}

.var-label {
  font-weight: 600;
  color: #4b5563;
  padding: 0.5rem 0;
  width: 20%;
}

.var-valor {
  color: #1e40af;
  padding: 0.5rem 0 0.5rem 1rem;
}

/* Tallas */
.tallas-por-genero {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.genero-grupo {
  background: #f9fafb;
  padding: 0.75rem;
  border-radius: 4px;
}

.genero-titulo {
  margin: 0 0 0.5rem 0;
  font-weight: 600;
  color: #374151;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.tallas-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.5rem;
}

.talla-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.5rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  text-align: center;
}

.talla-valor {
  font-weight: 600;
  color: #1e40af;
  font-size: 0.8rem;
}

.talla-cantidad {
  font-size: 0.75rem;
  color: #6b7280;
  padding-top: 0.25rem;
}

/* Procesos */
.procesos-lista {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.proceso-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  background: #dbeafe;
  color: #0c4a6e;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  border: 1px solid #bfdbfe;
}

/* Footer */
.card-footer {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #f3f4f6;
  justify-content: flex-end;
}

.btn-small {
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
}

.btn-menu-expandible {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #9ca3af;
  padding: 0;
  margin-left: auto;
}
```

---

## ✅ Ventajas de esta Propuesta

1. **Información Jerárquica**: Lo más importante (nombre, ref, tela) está visible de inmediato
2. **No Sobrecargado**: Usa secciones expandibles para detalles secundarios
3. **Imágenes Visibles**: Muestra tanto la prenda como la tela
4. **Responsive**: Funciona en móvil, tablet y desktop
5. **Interactivo**: Las secciones se expanden/contraen según necesidad
6. **Escalable**: Fácil de agregar más información sin perder claridad
7. **Acciones Claras**: Botones de editar/eliminar al final

---

## 📱 Comportamiento Responsive

### Desktop (> 1024px)
- Card de ancho completo
- Header con imagen a la izquierda
- Tallas en 2-3 columnas

### Tablet (768px - 1024px)
- Card de ancho completo
- Header igual
- Tallas en 2 columnas

### Mobile (< 768px)
- Card de ancho completo
- Header apilado (imagen arriba, datos abajo)
- Tallas en 2 columnas
- Acciones en fila completa

---

## 🚀 Implementación

Este diseño puede implementarse:
1. Como componente Blade reutilizable
2. Con JavaScript vanilla para expandir/contraer
3. Con Tailwind CSS para estilos
4. Con clases modulares para fácil customización
