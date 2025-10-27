# TODO: Implementar Actualizaciones en Tiempo Real entre Múltiples Pestañas

## Archivos a Modificar

### 1. resources/views/orders/index.blade.php
- Agregar script para BroadcastChannel API
- Crear función para escuchar mensajes de otras pestañas
- Crear función para enviar mensajes cuando se actualice estado/área/celda
- Integrar con las funciones existentes de actualización

### 2. public/js/modern-table.js
- Agregar BroadcastChannel en la clase ModernTable
- Crear método updateRowFromBroadcast(data) para actualizar fila específica
- Modificar updateOrderStatus y updateOrderArea para enviar mensajes broadcast
- Modificar saveCellEdit para enviar mensajes broadcast
- Asegurar que las actualizaciones se propaguen sin recargar

### 3. app/Http/Controllers/RegistroOrdenController.php
- Modificar método update para retornar todos los campos actualizados en la respuesta JSON
- Incluir updated_fields en la respuesta para propagar cambios

## Pasos de Implementación

1. Modificar RegistroOrdenController.php para retornar campos actualizados
2. Agregar BroadcastChannel a modern-table.js
3. Agregar BroadcastChannel a index.blade.php
4. Probar funcionalidad entre múltiples pestañas
5. Verificar que no haya conflictos de actualización

## Notas Técnicas
- Usar BroadcastChannel API (nativo del navegador, no requiere servidor)
- Mensajes incluyen: orderId, field, newValue, updatedFields
- Actualizaciones solo afectan la fila específica, no toda la tabla
- Mantener consistencia con colores de fila basados en estado
