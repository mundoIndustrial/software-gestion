# Sistema de Personalización de Prendas - COMPLETO ✅

## Resumen Ejecutivo

Se ha implementado un **sistema completo de personalización** para bordados y estampados con:
- ✅ Selector visual moderno con checkboxes
- ✅ Campo combinado cuando se seleccionan ambos
- ✅ Upload de **múltiples imágenes** de referencia
- ✅ Preview en tiempo real
- ✅ Diseño profesional con animaciones

## Características Principales

### 1. **Selector Visual de Personalización**

#### Diseño Mejorado:
- **Tarjetas grandes** (180px altura mínima)
- **Iconos gigantes** (4rem) con sombras
- **Bordes gruesos** (3px) con gradientes
- **Efecto hover**: Elevación 8px + escala 1.02
- **Animación suave** con cubic-bezier
- **Gradiente naranja** cuando está seleccionado
- **Icono rota 5°** al activar

#### Opciones Disponibles:
```
🖊️ Bordado
   Logos y textos bordados

🎨 Estampado
   Serigrafía y estampados
```

### 2. **Lógica Inteligente de Campos**

#### Caso 1: Solo Bordado
```
✅ Bordado seleccionado
❌ Estampado no seleccionado
→ Muestra: "Detalles de Bordado" (120px altura)
```

#### Caso 2: Solo Estampado
```
❌ Bordado no seleccionado
✅ Estampado seleccionado
→ Muestra: "Detalles de Estampado" (120px altura)
```

#### Caso 3: AMBOS (Campo Combinado)
```
✅ Bordado seleccionado
✅ Estampado seleccionado
→ Muestra: "Detalles de Bordado y Estampado" (150px altura)
→ Incluye: Sección de múltiples imágenes
```

### 3. **Sistema de Múltiples Imágenes**

#### Características:
- ✅ **Upload múltiple** con `input[multiple]`
- ✅ **Preview en grid** responsive (120px x 120px)
- ✅ **Validación automática** (5MB por imagen)
- ✅ **Botón eliminar** en cada imagen
- ✅ **Efecto hover** con elevación
- ✅ **Aspecto cuadrado** (aspect-ratio: 1)

#### Funcionalidad:
```javascript
Usuario selecciona múltiples imágenes
    ↓
Preview aparece en grid (auto-fill, min 120px)
    ↓
Hover: imagen se eleva y escala
    ↓
Click en ×: elimina del preview y del input
    ↓
Al guardar: todas se suben a storage/productos/personalizacion
```

## Estructura de Base de Datos

### Campos en `productos_pedido`:
```sql
- bordados (TEXT) - Solo bordados
- estampados (TEXT) - Solo estampados
- personalizacion_combinada (TEXT) - Ambos combinados
```

### Tabla `producto_imagenes`:
```sql
- id
- producto_pedido_id (FK)
- tipo (modelo, referencia, bordado, resultado)
- imagen (ruta)
- titulo
- descripcion
- orden
```

#### Tipos de Imágenes:
- **modelo**: Foto del modelo/referencia
- **referencia**: Imágenes adicionales
- **bordado**: Imágenes de personalización (bordados/estampados)
- **resultado**: Foto del producto terminado

## Flujo de Guardado

### 1. Validación
```php
'productos.*.bordados' => 'nullable|string',
'productos.*.estampados' => 'nullable|string',
'productos.*.personalizacion_combinada' => 'nullable|string',
'productos.*.imagenes_personalizacion' => 'nullable|array',
'productos.*.imagenes_personalizacion.*' => 'nullable|image|max:5120',
```

### 2. Guardado del Producto
```php
ProductoPedido::create([
    'bordados' => $productoData['bordados'] ?? null,
    'estampados' => $productoData['estampados'] ?? null,
    'personalizacion_combinada' => $productoData['personalizacion_combinada'] ?? null,
    // ... otros campos
]);
```

### 3. Guardado de Imágenes
```php
if ($request->hasFile("productos.{$index}.imagenes_personalizacion")) {
    foreach ($request->file("productos.{$index}.imagenes_personalizacion") as $imgIndex => $imagen) {
        $path = $imagen->store('productos/personalizacion', 'public');
        
        ProductoImagen::create([
            'producto_pedido_id' => $producto->id,
            'tipo' => 'bordado',
            'imagen' => $path,
            'titulo' => 'Referencia de Bordado/Estampado',
            'orden' => $imgIndex + 100,
        ]);
    }
}
```

## Visualización en Detalle

### Vista `show.blade.php`:
```php
@if($producto->personalizacion_combinada)
    <div class="producto-descripcion">
        <label>
            <span class="material-symbols-rounded">draw</span>
            <span class="material-symbols-rounded">palette</span>
            Bordados y Estampados:
        </label>
        <p style="white-space: pre-wrap;">{{ $producto->personalizacion_combinada }}</p>
    </div>
@else
    @if($producto->bordados)
        <!-- Mostrar solo bordados -->
    @endif
    
    @if($producto->estampados)
        <!-- Mostrar solo estampados -->
    @endif
@endif

<!-- Imágenes de personalización -->
@foreach($producto->imagenes->where('tipo', 'bordado') as $imagen)
    <img src="{{ asset('storage/' . $imagen->imagen) }}" />
@endforeach
```

## Estilos CSS Implementados

### Selector de Personalización:
```css
.personalizacion-label {
    min-height: 180px;
    border: 3px solid var(--border-color);
    border-radius: 16px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.personalizacion-label:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 12px 24px rgba(255, 107, 53, 0.2);
}

.personalizacion-checkbox:checked + .personalizacion-label {
    background: linear-gradient(135deg, rgba(255, 107, 53, 0.15), rgba(247, 147, 30, 0.15));
    box-shadow: 0 8px 32px rgba(255, 107, 53, 0.3);
}
```

### Contenedor de Detalles:
```css
.personalizacion-content {
    background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-primary) 100%);
    border: 2px solid var(--primary-color);
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 16px rgba(255, 107, 53, 0.1);
}

.personalizacion-content textarea {
    width: 100%;
    min-height: 150px;
    border-left: 4px solid var(--primary-color);
    resize: vertical;
}
```

### Grid de Imágenes:
```css
.personalizacion-images-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
}

.personalizacion-image-item {
    aspect-ratio: 1;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.personalizacion-image-item:hover {
    transform: translateY(-4px) scale(1.05);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}
```

## JavaScript Implementado

### Toggle de Campos:
```javascript
function handlePersonalizacionToggle(checkbox) {
    const productoItem = checkbox.closest('.producto-item');
    const bordadoCheckbox = productoItem.querySelector('[data-target="bordado"]');
    const estampadoCheckbox = productoItem.querySelector('[data-target="estampado"]');
    
    const bordadoChecked = bordadoCheckbox?.checked;
    const estampadoChecked = estampadoCheckbox?.checked;
    
    if (bordadoChecked && estampadoChecked) {
        // Mostrar campo combinado
        combinadoDiv.style.display = 'block';
        bordadoDiv.style.display = 'none';
        estampadoDiv.style.display = 'none';
    } else if (bordadoChecked) {
        // Solo bordado
        bordadoDiv.style.display = 'block';
    } else if (estampadoChecked) {
        // Solo estampado
        estampadoDiv.style.display = 'block';
    }
}
```

### Preview de Imágenes:
```javascript
function handlePersonalizacionImagesPreview(input) {
    const files = Array.from(input.files);
    const previewContainer = input.nextElementSibling;
    
    previewContainer.innerHTML = '';
    
    files.forEach((file, index) => {
        if (file.size > 5 * 1024 * 1024) {
            mostrarToast(`Imagen ${index + 1} supera 5MB`, 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const imageItem = document.createElement('div');
            imageItem.className = 'personalizacion-image-item';
            imageItem.innerHTML = `
                <img src="${e.target.result}" alt="Preview ${index + 1}">
                <button type="button" class="remove-btn" onclick="removePersonalizacionImage(this, ${index})">×</button>
            `;
            previewContainer.appendChild(imageItem);
        };
        reader.readAsDataURL(file);
    });
}
```

## Ejemplo de Uso Completo

### Caso: EMTEL con Bordados y Estampados

#### Usuario hace:
1. ✅ Click en "Bordado"
2. ✅ Click en "Estampado"
3. ✅ Aparece campo combinado
4. ✅ Escribe detalles:
```
BORDADOS:
- Logo Gobernación en pecho izquierdo
- Logo EMTEL en pecho derecho
- Texto "CONTRATISTA" en espalda

ESTAMPADOS:
- Serigrafía reflectiva en espalda
```
5. ✅ Sube 4 imágenes:
   - Logo Gobernación (PNG)
   - Logo EMTEL (PNG)
   - Diseño de espalda (JPG)
   - Referencia reflectivos (JPG)

#### Sistema guarda:
```php
ProductoPedido {
    personalizacion_combinada: "BORDADOS:\n- Logo Gobernación...",
    bordados: null,
    estampados: null
}

ProductoImagen (4 registros) {
    tipo: 'bordado',
    imagen: 'productos/personalizacion/xxx.png',
    orden: 100, 101, 102, 103
}
```

## Archivos Modificados

### Migraciones:
1. ✅ `2025_11_10_154835_add_estampados_to_productos_pedido_table.php`
2. ✅ `2025_11_10_155548_add_personalizacion_combinada_to_productos_pedido_table.php`

### Modelos:
1. ✅ `ProductoPedido.php` - fillable actualizado

### Controlador:
1. ✅ `AsesoresController.php` - validación y guardado de imágenes

### Vistas:
1. ✅ `create.blade.php` - selector y upload de imágenes
2. ✅ `show.blade.php` - visualización de personalización

### Assets:
1. ✅ `pedidos.css` - estilos mejorados
2. ✅ `pedidos.js` - lógica de toggle y preview

## Ventajas del Sistema

### 1. **Flexibilidad**
- ✅ Permite solo bordado
- ✅ Permite solo estampado
- ✅ Permite ambos en un solo campo

### 2. **Visual**
- ✅ Diseño moderno y atractivo
- ✅ Animaciones suaves
- ✅ Preview de imágenes en tiempo real

### 3. **Múltiples Imágenes**
- ✅ Sin límite de cantidad (solo tamaño)
- ✅ Preview individual
- ✅ Eliminación selectiva

### 4. **Validación**
- ✅ Tamaño máximo 5MB por imagen
- ✅ Solo formatos de imagen
- ✅ Mensajes de error claros

### 5. **Almacenamiento Organizado**
- ✅ `storage/productos/personalizacion/`
- ✅ Relación con producto
- ✅ Tipo identificado

## Comandos Ejecutados

```bash
# Crear migraciones
php artisan make:migration add_estampados_to_productos_pedido_table
php artisan make:migration add_personalizacion_combinada_to_productos_pedido_table

# Ejecutar migraciones
php artisan migrate

# Limpiar caché
php artisan view:clear
php artisan cache:clear

# Crear symlink (si no existe)
php artisan storage:link
```

## Resultado Final

**¡El sistema está completamente funcional y permite:**
- ✅ Seleccionar bordado, estampado o ambos
- ✅ Subir MÚLTIPLES imágenes de referencia
- ✅ Ver preview en tiempo real
- ✅ Eliminar imágenes antes de guardar
- ✅ Guardar todo correctamente en BD
- ✅ Visualizar en detalle del pedido

**¡Diseño profesional, moderno y funcional!** 🎨✨📸
