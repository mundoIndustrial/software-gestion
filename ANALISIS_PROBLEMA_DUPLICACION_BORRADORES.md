# Análisis: Problema de Duplicación en Borradores

## 🔴 Problema Reportado

Cuando un asesor:
1. Crea un borrador de pedido
2. Agrega una prenda
3. Edita la talla/cantidad de esa prenda
4. En la vista se ve **DUPLICADA** (prenda original + prenda editada)
5. Al guardar... ¿cuál se envía?

## 🔍 Investigación Realizada

### Backend (Handlers)
- ✓ `AgregarPrendaAlPedidoHandler`: Correcto, valida y agrega
- ✓ `ActualizarPedidoHandler`: Invalida caches correctamente
- ⚠️ No hay handler específico para editar tallas de una prenda en borrador

### Frontend
- ⚠️ Posible bug en la lógica de estado cuando se edita prenda
- ⚠️ Puede estar creando un nuevo registro en lugar de actualizar el existente

## 🎯 Causas Probables

### Causa 1: Lógica de Edición en Frontend
```
Cuando asesor edita talla:
- INCORRECTO: Se crea NUEVO item en array
- CORRECTO: Se MODIFICA el item existente

Si hace:
prenda = {nombre: "CAMISA", talla: "S", cantidad: 10}
edita talla a "M"
resultado esperado: {nombre: "CAMISA", talla: "M", cantidad: 10}
resultado actual: dos items en el array
```

### Causa 2: Estado Desincronizado
```
Array local: [CAMISA S:10, CAMISA M:20]
Vista renderiza: CAMISA S:10, CAMISA M:20
Usuario edita M:20 a M:30
Array actualizado: [CAMISA S:10, CAMISA M:30]
Vista re-renderiza: CAMISA S:10, CAMISA M:30
PERO por lag/bug: CAMISA S:10, CAMISA M:20, CAMISA M:30 (3 items!)
```

### Causa 3: Envío de Múltiples Requests
```
1. Click en guardar
2. Se envía request con array completo
3. Double-click (o reintento) envía OTRA VEZ
4. Servidor recibe 2 requests
5. Se crean 2 prendas
```

## 📋 Archivos a Revisar

Para confirmar la causa exacta, necesito revisar:
1. `resources/views/asesores/pedidos/crear-pedido-nuevo.blade.php` - Lógica de edición
2. `resources/views/asesores/pedidos/create-friendly.blade.php` - Otra forma de crear
3. JavaScript que maneja el array de prendas
4. Cómo se renderiza la vista al editar

## ✅ Soluciones Propuestas

### Solución 1: Fix en Frontend (Validación de Edición)
```javascript
// Cuando edita una prenda:
const editarPrenda = (index, nuevosDatos) => {
  // ✓ CORRECTO: Actualizar el item existente
  prendas[index] = { ...prendas[index], ...nuevosDatos };
  
  // ✗ INCORRECTO: No hacer push
  // prendas.push({ ...nuevosDatos });
  
  // Forzar re-render
  actualizarVista();
};
```

### Solución 2: Protección contra Double-Click (YA IMPLEMENTADA)
```javascript
deshabilitarBotonAl Guardar()
// Previene re-envío múltiple
```

### Solución 3: Validación en Backend
```php
// Si llegan 2 prendas con MISMO nombre + descripción
// El constraint único las rechaza
unique(['pedido_id', 'nombre_prenda', 'descripcion'])
```

### Solución 4: Idempotencia
```php
// Usar identificador único por prenda en borrador
// Si asesor intenta guardar 2 veces la misma, se actualiza, no se duplica
```

## 🚀 Próximos Pasos

1. Necesito revisar el JavaScript de edición de prendas
2. Confirmar cómo se maneja el array de prendas
3. Ver si hay un bug donde se crea nuevo en lugar de actualizar
4. Proponer fix específico basado en la causa real

## 📌 Nota Importante

Este problema es **diferente** al de double-click:
- **Double-click**: Mismo botón 2 veces → 2 requests
- **Duplicación en borrador**: Editar prenda → Aparece 2 veces en vista

Necesita investigación más profunda en el frontend.
