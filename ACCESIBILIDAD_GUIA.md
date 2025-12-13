# 🎯 MEJORAS DE ACCESIBILIDAD - PASO A PASO

## PROBLEMA IDENTIFICADO

El input de fecha en `paso-uno.blade.php` línea 19 **NO tiene label**:

```php
<input type="date" id="fechaActual" name="fecha_cotizacion" style="...">
```

### ❌ POR QUE FALLA
- Lectores de pantalla no entienden qué es este input
- Usuarios móviles no ven la etiqueta claramente
- Accessibility: -10 puntos

### ✅ SOLUCIÓN

Reemplazar esa línea con:

```php
<label for="fechaActual" style="font-weight: 600; display: block; margin-bottom: 4px;">FECHA</label>
<input type="date" id="fechaActual" name="fecha_cotizacion" style="border: 1px solid #ccc; padding: 4px 8px; border-radius: 4px; font-size: 0.9rem; cursor: pointer;" aria-label="Fecha de cotización">
```

---

## OTROS INPUTS SIN LABELS ENCONTRADOS

Buscar con este comando:
```bash
grep -r '<input.*type=' resources/views/ | grep -v 'aria-label' | grep -v '<label' | head -30
```

Estos también necesitan labels:
1. Inputs de fecha en modales
2. Inputs en tablas inline
3. Inputs de búsqueda

---

## CONTRASTE DE COLORES

### Colores actuales problemáticos:
```css
/* Texto gris muy claro sobre fondo blanco */
color: #666;  /* ⚠️ TOO LIGHT - Only 4.3:1 ratio */
color: #999;  /* ⚠️ FAIL - Only 2.8:1 ratio */
```

### Solución - Actualizar en app.css:
```css
/* Variables de contraste mejorado */
:root {
  --text-primary: #1f2937;    /* Nearly black - 16:1 ratio ✅ */
  --text-secondary: #374151;  /* Dark gray - 9.5:1 ratio ✅ */
  --text-muted: #6b7280;      /* Medium gray - 4.7:1 ratio ✅ */
}

body {
  color: var(--text-primary);
}

.help-text {
  color: var(--text-muted);
}

label {
  color: var(--text-primary);
  font-weight: 600;
}
```

---

## ANIMACIONES CSS NO COMPOSITED

Las 8 animaciones que flagea Lighthouse probablemente usan propiedades como:
- `left`, `top`, `width`, `height` (causa reflow)
- Deben usar `transform` y `opacity` (GPU accelerated)

### Buscar animaciones:
```bash
grep -r '@keyframes' public/css/ | head -10
```

### Convertir a transform:
```css
/* ❌ ANTES - No composited */
@keyframes slideIn {
    from { margin-left: -100px; }
    to { margin-left: 0; }
}

/* ✅ DESPUÉS - GPU accelerated */
@keyframes slideIn {
    from { transform: translateX(-100px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
```

---

## CHECKLIST DE ACCESIBILIDAD

- [ ] Agregar label a input fecha en paso-uno.blade.php
- [ ] Buscar otros inputs sin label (comando arriba)
- [ ] Mejorar contraste: cambiar color #666 y #999 por #374151
- [ ] Optimizar animaciones CSS (usar transform instead of position)
- [ ] Probar con lectores de pantalla (NVDA, JAWS)

---

## VALIDAR CAMBIOS

Después de cada cambio:

```bash
npm run build
php artisan cache:clear
php artisan view:clear
```

Luego ejecutar Lighthouse nuevamente:
```bash
lighthouse https://tudominio.com --view
```

Resultado esperado:
- Accessibility: 86 → 92+
