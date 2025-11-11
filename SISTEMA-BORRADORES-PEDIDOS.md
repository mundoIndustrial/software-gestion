# 📝 Sistema de Borradores para Pedidos

## 🎯 Objetivo

Implementar un sistema que permita guardar pedidos como borradores sin asignar número consecutivo hasta que se confirmen, evitando desorden en la numeración.

## 🔑 Conceptos Clave

### **Problema a Resolver:**
- Los asesores no siempre completan un pedido de una vez
- Si se asigna número de pedido inmediatamente, se generan huecos en la numeración
- Necesidad de guardar progreso sin comprometer la secuencia

### **Solución:**
- **Borradores**: Pedidos sin número oficial, identificados por ID temporal
- **Confirmación**: Al finalizar, se asigna el siguiente número consecutivo
- **Numeración limpia**: Solo pedidos confirmados tienen número oficial

## 📊 Estructura de Base de Datos

### Campos Agregados a `ordenes_asesores`:

```sql
-- Número de pedido oficial (solo para confirmados)
pedido INT NULL

-- Número temporal mientras es borrador
numero_pedido_temporal INT NULL

-- Estado del pedido
estado_pedido ENUM('borrador', 'confirmado', 'en_proceso', 'completado', 'cancelado')
DEFAULT 'borrador'

-- Bandera de borrador
es_borrador BOOLEAN DEFAULT TRUE

-- Fecha de confirmación
fecha_confirmacion TIMESTAMP NULL
```

## 🔄 Flujo de Trabajo

### **1. Crear Borrador**
```
Usuario crea pedido
    ↓
Se guarda como borrador
    ↓
ID: BORRADOR-123
pedido: NULL
es_borrador: true
```

### **2. Editar Borrador**
```
Usuario puede:
- Agregar productos
- Modificar datos
- Guardar múltiples veces
- Cerrar y volver después
```

### **3. Confirmar Pedido**
```
Usuario confirma pedido
    ↓
Sistema obtiene último número
    ↓
Asigna siguiente consecutivo
    ↓
ID: PEDIDO-45161
pedido: 45161
es_borrador: false
fecha_confirmacion: 2025-11-10 17:30:00
```

## 💻 Implementación en Código

### **Modelo OrdenAsesor**

```php
// Scopes útiles
$borradores = OrdenAsesor::borradores()->get();
$confirmados = OrdenAsesor::confirmados()->get();

// Confirmar un borrador
$orden = OrdenAsesor::find(123);
$orden->confirmar(); // Asigna número consecutivo

// Verificar estado
if ($orden->esBorrador()) {
    echo "Es borrador";
}

// Obtener identificador
echo $orden->identificador; // "BORRADOR-123" o "PEDIDO-45161"
```

### **Controlador - Guardar Borrador**

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'cliente' => 'required|string',
        // ... otros campos
    ]);

    $orden = OrdenAsesor::create([
        'asesor_id' => Auth::id(),
        'cliente' => $validated['cliente'],
        'es_borrador' => true, // Guardar como borrador
        'estado_pedido' => 'borrador',
        // pedido se mantiene NULL
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Borrador guardado',
        'identificador' => $orden->identificador,
        'id' => $orden->id
    ]);
}
```

### **Controlador - Confirmar Pedido**

```php
public function confirmar($id)
{
    $orden = OrdenAsesor::findOrFail($id);
    
    if (!$orden->esBorrador()) {
        return response()->json([
            'success' => false,
            'message' => 'El pedido ya está confirmado'
        ], 400);
    }

    $orden->confirmar(); // Asigna número consecutivo

    return response()->json([
        'success' => true,
        'message' => 'Pedido confirmado',
        'numero_pedido' => $orden->pedido,
        'identificador' => $orden->identificador
    ]);
}
```

## 🎨 Interfaz de Usuario

### **Formulario de Creación**

```html
<!-- Botones de acción -->
<div class="erp-form-actions">
    <div class="erp-actions-left">
        <a href="{{ route('asesores.pedidos.index') }}" 
           class="erp-btn erp-btn-secondary">
            <span class="material-symbols-rounded">arrow_back</span>
            Cancelar
        </a>
    </div>
    <div class="erp-actions-right">
        <!-- Guardar como borrador -->
        <button type="button" 
                onclick="guardarBorrador()" 
                class="erp-btn erp-btn-secondary">
            <span class="material-symbols-rounded">save</span>
            Guardar Borrador
        </button>
        
        <!-- Confirmar y crear pedido -->
        <button type="submit" 
                class="erp-btn erp-btn-success erp-btn-lg">
            <span class="material-symbols-rounded">check_circle</span>
            Confirmar Pedido
        </button>
    </div>
</div>
```

### **JavaScript - Guardar Borrador**

```javascript
async function guardarBorrador() {
    const formData = new FormData(document.getElementById('formCrearPedido'));
    formData.append('guardar_como_borrador', '1');

    try {
        const response = await fetch('/asesores/pedidos/borrador', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Borrador Guardado',
                text: `Identificador: ${data.identificador}`,
                confirmButtonText: 'Continuar Editando'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo guardar el borrador'
        });
    }
}
```

### **Lista de Pedidos con Borradores**

```html
<div class="pedidos-list">
    @foreach($pedidos as $pedido)
    <div class="pedido-card {{ $pedido->esBorrador() ? 'borrador' : '' }}">
        <div class="pedido-header">
            @if($pedido->esBorrador())
                <span class="badge badge-warning">
                    <span class="material-symbols-rounded">edit_note</span>
                    BORRADOR
                </span>
            @else
                <span class="badge badge-success">
                    <span class="material-symbols-rounded">check_circle</span>
                    CONFIRMADO
                </span>
            @endif
            
            <h3>{{ $pedido->identificador }}</h3>
        </div>
        
        <div class="pedido-body">
            <p><strong>Cliente:</strong> {{ $pedido->cliente }}</p>
            <p><strong>Fecha:</strong> {{ $pedido->created_at->format('d/m/Y') }}</p>
        </div>
        
        <div class="pedido-actions">
            @if($pedido->esBorrador())
                <a href="{{ route('asesores.pedidos.edit', $pedido->id) }}" 
                   class="erp-btn erp-btn-sm erp-btn-primary">
                    <span class="material-symbols-rounded">edit</span>
                    Continuar Editando
                </a>
                <button onclick="confirmarPedido({{ $pedido->id }})" 
                        class="erp-btn erp-btn-sm erp-btn-success">
                    <span class="material-symbols-rounded">check</span>
                    Confirmar
                </button>
            @else
                <a href="{{ route('asesores.pedidos.show', $pedido->id) }}" 
                   class="erp-btn erp-btn-sm erp-btn-secondary">
                    <span class="material-symbols-rounded">visibility</span>
                    Ver Detalles
                </a>
            @endif
        </div>
    </div>
    @endforeach
</div>
```

## 🎨 Estilos CSS para Borradores

```css
/* Badge de borrador */
.badge-warning {
    background: linear-gradient(135deg, #F77F00, #FFA726);
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

/* Tarjeta de borrador */
.pedido-card.borrador {
    border-left: 4px solid #F77F00;
    background: linear-gradient(to right, rgba(247, 127, 0, 0.05), transparent);
}

/* Badge de confirmado */
.badge-success {
    background: linear-gradient(135deg, #00A86B, #00C97D);
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}
```

## 📋 Rutas Necesarias

```php
// routes/web.php

Route::middleware(['auth', 'role:asesor'])->group(function () {
    // Guardar borrador
    Route::post('/asesores/pedidos/borrador', [AsesoresController::class, 'guardarBorrador'])
         ->name('asesores.pedidos.borrador');
    
    // Confirmar pedido
    Route::post('/asesores/pedidos/{id}/confirmar', [AsesoresController::class, 'confirmar'])
         ->name('asesores.pedidos.confirmar');
    
    // Listar borradores
    Route::get('/asesores/pedidos/borradores', [AsesoresController::class, 'borradores'])
         ->name('asesores.pedidos.borradores');
    
    // Editar borrador
    Route::get('/asesores/pedidos/{id}/editar', [AsesoresController::class, 'edit'])
         ->name('asesores.pedidos.edit');
});
```

## ✅ Ventajas del Sistema

1. **Numeración Limpia**: Solo pedidos confirmados tienen número oficial
2. **Flexibilidad**: Guardar y continuar después sin presión
3. **Sin Huecos**: Números consecutivos sin saltos
4. **Trazabilidad**: Fecha de confirmación registrada
5. **Organización**: Separación clara entre borradores y confirmados
6. **Recuperación**: No se pierde trabajo si hay interrupciones

## 🔒 Validaciones Importantes

```php
// Validar antes de confirmar
public function confirmar($id)
{
    $orden = OrdenAsesor::with('productos')->findOrFail($id);
    
    // Validar que tenga productos
    if ($orden->productos->count() === 0) {
        return response()->json([
            'success' => false,
            'message' => 'El pedido debe tener al menos un producto'
        ], 400);
    }
    
    // Validar que tenga cliente
    if (empty($orden->cliente)) {
        return response()->json([
            'success' => false,
            'message' => 'El pedido debe tener un cliente asignado'
        ], 400);
    }
    
    // Confirmar
    $orden->confirmar();
    
    return response()->json([
        'success' => true,
        'message' => 'Pedido confirmado exitosamente',
        'numero_pedido' => $orden->pedido
    ]);
}
```

## 📊 Dashboard con Borradores

```php
// Mostrar estadísticas incluyendo borradores
public function dashboard()
{
    $asesorId = Auth::id();
    
    $stats = [
        'borradores' => OrdenAsesor::delAsesor($asesorId)
                                   ->borradores()
                                   ->count(),
        
        'confirmados_hoy' => OrdenAsesor::delAsesor($asesorId)
                                        ->confirmados()
                                        ->delDia()
                                        ->count(),
        
        'confirmados_mes' => OrdenAsesor::delAsesor($asesorId)
                                        ->confirmados()
                                        ->delMes()
                                        ->count(),
    ];
    
    return view('asesores.dashboard', compact('stats'));
}
```

## 🎯 Resumen

El sistema de borradores permite:

✅ **Guardar progreso** sin asignar número oficial
✅ **Editar múltiples veces** antes de confirmar
✅ **Numeración consecutiva** solo en confirmados
✅ **Identificación clara** (BORRADOR-123 vs PEDIDO-45161)
✅ **Flexibilidad total** para el asesor
✅ **Orden perfecto** en la numeración

---

**¡Sistema de borradores listo para implementar!** 📝✨
