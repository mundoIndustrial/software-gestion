# Botón de Estado Completo/Incompleto del Balanceo

## ✅ Funcionalidad Implementada

Ahora puedes **marcar manualmente** si un balanceo está completo o incompleto directamente desde la vista del balanceo.

## 🎯 Características

### 1. **Botón Toggle en el Header**
- 📍 **Ubicación:** Header del balanceo, junto al tipo de prenda
- 🎨 **Colores:**
  - 🟢 **Verde** cuando está completo
  - 🔴 **Rojo** cuando está incompleto
- 🔄 **Acción:** Click para cambiar el estado
- 💫 **Animación:** Escala al hacer hover

### 2. **Estados Visuales**

#### Estado Completo ✅
```
┌──────────────────────────┐
│ ✓ COMPLETO               │ ← Verde
└──────────────────────────┘
- Icono: check_circle (✓)
- Color: Verde (#43e97b)
- Tooltip: "Marcar como incompleto"
```

#### Estado Incompleto ❌
```
┌──────────────────────────┐
│ ✕ INCOMPLETO             │ ← Rojo
└──────────────────────────┘
- Icono: cancel (✕)
- Color: Rojo (#ef4444)
- Tooltip: "Marcar como completo"
```

## 🗄️ Base de Datos

### Nueva Columna
```sql
ALTER TABLE balanceos 
ADD COLUMN estado_completo BOOLEAN DEFAULT FALSE;
```

### Modelo Balanceo
```php
protected $fillable = [
    // ... otros campos
    'estado_completo',
];

protected $casts = [
    // ... otros casts
    'estado_completo' => 'boolean',
];
```

## 🔧 Implementación Técnica

### 1. **Migración**
```php
// 2025_11_04_172712_add_estado_completo_to_balanceos_table.php
Schema::table('balanceos', function (Blueprint $table) {
    $table->boolean('estado_completo')->default(false)->after('activo');
});
```

### 2. **Controlador**
```php
// BalanceoController.php
public function toggleEstadoCompleto($id)
{
    $balanceo = Balanceo::findOrFail($id);
    $balanceo->estado_completo = !$balanceo->estado_completo;
    $balanceo->save();

    return response()->json([
        'success' => true,
        'estado_completo' => $balanceo->estado_completo,
        'message' => $balanceo->estado_completo 
            ? 'Balanceo marcado como completo' 
            : 'Balanceo marcado como incompleto',
    ]);
}
```

### 3. **Ruta**
```php
// web.php
Route::post('/balanceo/{id}/toggle-estado', [BalanceoController::class, 'toggleEstadoCompleto'])
    ->name('balanceo.toggle-estado');
```

### 4. **Frontend (Alpine.js)**
```javascript
// scripts.blade.php
balanceo: {
    estado_completo: {{ $balanceo->estado_completo ? 'true' : 'false' }}
},

async toggleEstadoCompleto() {
    const response = await fetch(`/balanceo/${this.balanceoId}/toggle-estado`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    });

    const data = await response.json();
    
    if (data.success) {
        this.balanceo.estado_completo = data.estado_completo;
        this.showSuccessMessage(data.message);
    }
}
```

### 5. **Vista (Blade)**
```html
<!-- header.blade.php -->
<button @click="toggleEstadoCompleto()" 
   :title="balanceo.estado_completo ? 'Marcar como incompleto' : 'Marcar como completo'"
   :style="'background: ' + (balanceo.estado_completo 
       ? 'linear-gradient(135deg, #43e97b 0%, #38d16a 100%)' 
       : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)')">
    <span x-text="balanceo.estado_completo ? 'check_circle' : 'cancel'"></span>
    <span x-text="balanceo.estado_completo ? 'Completo' : 'Incompleto'"></span>
</button>
```

## 🎨 Estilos del Botón

### Completo (Verde)
```css
background: linear-gradient(135deg, #43e97b 0%, #38d16a 100%);
box-shadow: 0 2px 4px rgba(67, 233, 123, 0.3);
color: white;
```

### Incompleto (Rojo)
```css
background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
color: white;
```

### Hover
```css
transform: scale(1.05);
```

## 📊 Integración con Vista Index

### Antes
```php
// Criterios automáticos
$balanceoIncompleto = !$prenda->balanceoActivo || 
                      $prenda->balanceoActivo->operaciones_count == 0 || 
                      $prenda->balanceoActivo->total_operarios == 0;
```

### Ahora
```php
// Usa el campo manual estado_completo
$balanceoIncompleto = !$prenda->balanceoActivo || 
                      !$prenda->balanceoActivo->estado_completo;
```

## 🎯 Flujo de Uso

### 1. **Crear Balanceo**
```
Estado inicial: ❌ Incompleto (false)
```

### 2. **Trabajar en el Balanceo**
```
- Agregar operaciones
- Configurar parámetros
- Ajustar métricas
Estado: ❌ Incompleto (aún)
```

### 3. **Marcar como Completo**
```
Click en botón → ✅ Completo
- Botón cambia a verde
- Mensaje: "Balanceo marcado como completo"
- En index: Sin borde rojo
```

### 4. **Volver a Incompleto (si necesitas)**
```
Click en botón → ❌ Incompleto
- Botón cambia a rojo
- Mensaje: "Balanceo marcado como incompleto"
- En index: Con borde rojo
```

## 💡 Ventajas

### 1. **Control Manual**
- ✅ Tú decides cuándo está completo
- ✅ No depende de criterios automáticos
- ✅ Flexibilidad total

### 2. **Feedback Visual Inmediato**
- ✅ Botón cambia de color al instante
- ✅ Mensaje de confirmación
- ✅ Se refleja en el index

### 3. **Persistencia**
- ✅ El estado se guarda en la base de datos
- ✅ Se mantiene entre sesiones
- ✅ Visible para todos los usuarios

### 4. **Integración con Indicador Rojo**
- ✅ Si marcas como incompleto → Aparece borde rojo en index
- ✅ Si marcas como completo → Desaparece el borde rojo
- ✅ Consistencia visual en toda la app

## 🎨 Paleta de Colores

| Estado | Color Principal | Color Hover | Sombra |
|--------|----------------|-------------|---------|
| **Completo** | `#43e97b` | `#38d16a` | `rgba(67, 233, 123, 0.3)` |
| **Incompleto** | `#ef4444` | `#dc2626` | `rgba(239, 68, 68, 0.3)` |

## 📝 Mensajes del Sistema

### Al Marcar como Completo
```
✓ Balanceo marcado como completo
```

### Al Marcar como Incompleto
```
✓ Balanceo marcado como incompleto
```

## 🔧 Archivos Modificados

1. **`database/migrations/2025_11_04_172712_add_estado_completo_to_balanceos_table.php`**
   - Nueva migración para agregar campo

2. **`app/Models/Balanceo.php`**
   - Agregado `estado_completo` a `$fillable`
   - Agregado `estado_completo` a `$casts`

3. **`app/Http/Controllers/BalanceoController.php`**
   - Método `toggleEstadoCompleto()`

4. **`routes/web.php`**
   - Ruta `POST /balanceo/{id}/toggle-estado`

5. **`resources/views/balanceo/partials/header.blade.php`**
   - Botón toggle con estilos dinámicos

6. **`resources/views/balanceo/partials/scripts.blade.php`**
   - Función `toggleEstadoCompleto()`
   - Variable `balanceo.estado_completo`

7. **`resources/views/balanceo/index.blade.php`**
   - Lógica actualizada para usar `estado_completo`

## 🚀 Ejemplo de Uso

### Escenario 1: Balanceo Nuevo
```
1. Crear prenda → Estado: ❌ Incompleto
2. Agregar operaciones
3. Configurar todo
4. Click en botón → ✅ Completo
5. En index: Sin indicador rojo
```

### Escenario 2: Revisar Balanceo
```
1. Balanceo existente → Estado: ✅ Completo
2. Necesitas hacer cambios
3. Click en botón → ❌ Incompleto
4. Haces los cambios
5. Click en botón → ✅ Completo
```

### Escenario 3: Trabajo en Progreso
```
1. Balanceo parcial → Estado: ❌ Incompleto
2. Trabajas en él durante varios días
3. Cuando termines → Click en botón → ✅ Completo
4. Todos saben que está listo
```

## ✨ Características Especiales

### 1. **Reactivo**
- El botón cambia instantáneamente
- No necesita recargar la página
- Usa Alpine.js para reactividad

### 2. **Persistente**
- El estado se guarda en la base de datos
- Sobrevive a recargas de página
- Visible para todos los usuarios

### 3. **Visual**
- Colores claros y distintos
- Iconos descriptivos
- Animación suave

### 4. **Integrado**
- Funciona con el indicador rojo del index
- Consistente en toda la aplicación
- Feedback inmediato

**¡Ahora tienes control total sobre el estado de tus balanceos!** 🎯✅❌
