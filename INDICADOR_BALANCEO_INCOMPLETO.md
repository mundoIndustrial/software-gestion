# Indicador Visual de Balanceo Incompleto

## ✅ Funcionalidad Implementada

Las prendas con balanceo incompleto ahora se destacan visualmente con un **borde rojo** y una **alerta animada**.

## 🎯 Criterios de "Balanceo Incompleto"

Una prenda se considera con balanceo incompleto si cumple **cualquiera** de estas condiciones:

1. ❌ **No tiene balanceo configurado** (`balanceoActivo` es null)
2. ❌ **Tiene 0 operaciones** (`operaciones_count == 0`)
3. ❌ **Tiene 0 operarios asignados** (`total_operarios == 0`)

## 🎨 Indicadores Visuales

### 1. **Badge de Alerta**
- 📍 **Posición:** Esquina superior izquierda de la tarjeta
- 🎨 **Color:** Rojo degradado (#ef4444 → #dc2626)
- ⚡ **Animación:** Pulso suave cada 2 segundos
- 📝 **Texto:** "Balanceo Incompleto"
- 🔔 **Icono:** Warning (⚠️)

### 2. **Borde Rojo**
- 🎨 **Color:** #ef4444 (rojo)
- 📏 **Grosor:** 2px
- 🌈 **Fondo:** Degradado rojo sutil (5% opacidad)

### 3. **Hover Especial**
- 🎨 **Borde:** #dc2626 (rojo más oscuro)
- 💫 **Sombra:** Roja con 30% opacidad
- ⬆️ **Elevación:** Igual que tarjetas normales

## 📸 Ejemplo Visual

### Tarjeta Normal
```
┌─────────────────────────┐
│   [Imagen]              │
│   Badge: Tipo           │
├─────────────────────────┤
│ Nombre Prenda           │
│ Ref: ABC123             │
│                         │
│ Operaciones: 25         │
│ SAM Total: 784.2s       │
│ Operarios: 30           │
│ Meta Real: 46.8         │
│                         │
│ [Ver Balanceo]          │
└─────────────────────────┘
```

### Tarjeta con Balanceo Incompleto
```
┌═════════════════════════┐ ← Borde ROJO
║ ⚠️ Balanceo Incompleto  ║ ← Badge animado
║   [Imagen]              ║
║   Badge: Tipo           ║
╠═════════════════════════╣
║ Nombre Prenda           ║
║ Ref: ABC123             ║
║                         ║
║ Sin balanceo configurado║ ← Mensaje
║                         ║
║ [Ver Balanceo]          ║
╚═════════════════════════╝
```

## 💻 Código Implementado

### PHP (Blade)
```php
@php
    // Determinar si el balanceo está incompleto
    $balanceoIncompleto = !$prenda->balanceoActivo || 
                          $prenda->balanceoActivo->operaciones_count == 0 || 
                          $prenda->balanceoActivo->total_operarios == 0;
@endphp

<div class="prenda-card {{ $balanceoIncompleto ? 'prenda-card--incompleto' : '' }}">
    @if($balanceoIncompleto)
    <div class="prenda-card__alert">
        <span class="material-symbols-rounded">warning</span>
        <span>Balanceo Incompleto</span>
    </div>
    @endif
    <!-- resto del contenido -->
</div>
```

### CSS
```css
/* Tarjeta con balanceo incompleto */
.prenda-card--incompleto {
    border: 2px solid #ef4444 !important;
    background: linear-gradient(to bottom, rgba(239, 68, 68, 0.05), transparent) !important;
}

.prenda-card--incompleto:hover {
    border-color: #dc2626 !important;
    box-shadow: 0 8px 16px rgba(239, 68, 68, 0.3) !important;
}

/* Alerta de balanceo incompleto */
.prenda-card__alert {
    position: absolute;
    top: 12px;
    left: 12px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    animation: pulse-alert 2s infinite;
}

@keyframes pulse-alert {
    0%, 100% {
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    }
    50% {
        box-shadow: 0 2px 12px rgba(239, 68, 68, 0.6);
    }
}
```

## 🎭 Casos de Uso

### Caso 1: Prenda Nueva (Sin Balanceo)
```
Estado: ❌ Incompleto
Razón: No tiene balanceoActivo
Visual: Borde rojo + Badge "Balanceo Incompleto"
```

### Caso 2: Balanceo Sin Operaciones
```
Estado: ❌ Incompleto
Razón: operaciones_count == 0
Visual: Borde rojo + Badge "Balanceo Incompleto"
Mensaje: "Sin balanceo configurado"
```

### Caso 3: Balanceo Sin Operarios
```
Estado: ❌ Incompleto
Razón: total_operarios == 0
Visual: Borde rojo + Badge "Balanceo Incompleto"
Métricas: Operaciones: X, SAM: Y, Operarios: 0
```

### Caso 4: Balanceo Completo
```
Estado: ✅ Completo
Visual: Borde normal + Sin badge
Métricas: Todas visibles y correctas
```

## 🎨 Paleta de Colores

| Elemento | Color | Uso |
|----------|-------|-----|
| **Borde Normal** | `#e5e7eb` | Tarjetas completas |
| **Borde Incompleto** | `#ef4444` | Tarjetas incompletas |
| **Borde Hover** | `#dc2626` | Hover en incompletas |
| **Badge Fondo** | `#ef4444 → #dc2626` | Gradiente del badge |
| **Badge Texto** | `white` | Texto del badge |
| **Fondo Sutil** | `rgba(239, 68, 68, 0.05)` | Fondo de tarjeta |
| **Sombra Hover** | `rgba(239, 68, 68, 0.3)` | Sombra en hover |

## ✨ Características Especiales

### 1. **Animación Pulse**
- ⏱️ **Duración:** 2 segundos
- 🔄 **Repetición:** Infinita
- 💫 **Efecto:** Pulso suave de la sombra del badge

### 2. **Posicionamiento Absoluto**
- 📍 El badge se posiciona sobre la imagen
- 🎯 No afecta el layout de la tarjeta
- 📱 Responsive y visible en todos los tamaños

### 3. **Z-Index Correcto**
- 🔝 Badge: z-index 10
- 🖼️ Imagen: z-index por defecto
- ✅ Siempre visible sobre la imagen

## 📊 Impacto Visual

### Antes
```
Todas las tarjetas lucen iguales
❌ Difícil identificar prendas sin configurar
❌ Usuario debe hacer clic para verificar
```

### Después
```
Tarjetas incompletas destacan inmediatamente
✅ Identificación visual instantánea
✅ Badge informativo con icono
✅ Animación sutil llama la atención
✅ Borde rojo diferencia claramente
```

## 🔧 Archivo Modificado

**`resources/views/balanceo/index.blade.php`**
- Líneas 82-97: Lógica PHP para detectar balanceo incompleto
- Líneas 214-255: Estilos CSS para indicador visual

## 🚀 Beneficios

1. ✅ **Identificación rápida** de prendas que necesitan atención
2. ✅ **Mejora la UX** con feedback visual claro
3. ✅ **Reduce errores** al destacar configuraciones incompletas
4. ✅ **Guía al usuario** hacia prendas que requieren trabajo
5. ✅ **Animación sutil** sin ser molesta
6. ✅ **Responsive** funciona en todos los dispositivos

## 💡 Sugerencias de Uso

### Para el Usuario
1. Al ver una tarjeta roja, sabes que necesita configuración
2. El badge te dice exactamente qué falta
3. Haz clic para completar el balanceo
4. Una vez completo, el indicador desaparece automáticamente

### Para Priorizar Trabajo
1. Filtra visualmente las prendas incompletas
2. Completa primero las que tienen borde rojo
3. Verifica que tengan operaciones y operarios
4. El sistema actualiza el indicador en tiempo real

**¡Ahora es imposible pasar por alto una prenda sin configurar!** 🎯🔴
