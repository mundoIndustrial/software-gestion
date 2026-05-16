# Plan de Unificación: Serialización de Pedidos y Borradores

## 1. Objetivo
Consolidar la lógica de preparación de datos en un único servicio centralizado (`PayloadNormalizer` e `ItemAPIService`), eliminando la duplicidad actual con `DraftPedidoBuilder` y previniendo desincronizaciones en el guardado de imágenes, tallas o procesos.

## 2. Estado Actual
*   **Flujo Directo**: `ItemAPIService` → `PayloadNormalizer` → `POST /api/asesores/pedidos/crear`
*   **Flujo Borrador**: `DraftPedidoUnsavedChanges` → `DraftPedidoBuilder` → `POST /api/asesores/pedidos/{id}/borrador`

## 3. Arquitectura Propuesta
Utilizar el patrón **Orchestrator** para que toda la aplicación dependa de una única fuente de verdad para la serialización.

### Componentes Clave
1.  **`PayloadNormalizer.js`**: Único responsable de convertir el estado de la UI (Prendas, EPPs, Logos) en un objeto JSON limpio y un `FormData` de archivos.
2.  **`ItemAPIService.js`**: Único responsable de las comunicaciones HTTP, manejando tanto la creación final como el guardado de borradores.

---

## 4. Hoja de Ruta de Implementación

### Fase 1: Enriquecer el Normalizador (PayloadNormalizer.js)
Actualmente, el normalizador está optimizado para la creación final. Debe expandirse para soportar:
*   [ ] **Metadata de Borradores**: Inclusión de `borrador_pedido_id` y `prendas_eliminadas`.
*   [ ] **Detección de Cambios**: Lógica para distinguir entre ítems nuevos (sin ID) e ítems existentes (con ID) para enviar solo lo necesario.
*   [ ] **Soporte Multimedia Completo**: Asegurar que las rutas de imágenes existentes (reuse) se envíen correctamente junto a los archivos nuevos (upload).

### Fase 2: Expandir el Servicio de API (ItemAPIService.js)
Añadir métodos específicos para la gestión de borradores:
*   [ ] `guardarBorrador(pedidoData)`: Que utilice la misma lógica de `extraerFilesDelPedido` y `buildFormData` que ya usa la creación directa.
*   [ ] Unificar el manejo de errores y las notificaciones de éxito/contingencia.

### Fase 3: Refactorización de la UI
*   [ ] Modificar los botones de "Guardar Borrador" en `crear-pedido-nuevo.blade.php` y `crear-pedido-editable.blade.php` para que invoquen al nuevo método de `ItemAPIService`.
*   [ ] Eliminar la dependencia de `DraftPedidoBuilder.js` en los templates.

### Fase 4: Limpieza y Eliminación de Código Muerto
*   [ ] Eliminar el archivo `draft-pedido-builder.js`.
*   [ ] Limpiar funciones redundantes en `validacion-envio-fase3.js` y otros scripts de utilidades.

---

## 5. Beneficios Esperados
*   **Cero Desincronización**: Si una imagen guarda bien en borrador, obligatoriamente guardará bien en creación directa.
*   **Reducción de Deuda Técnica**: Eliminación de aproximadamente 800-1000 líneas de código duplicado.
*   **Mantenibilidad**: Los cambios en la estructura de la base de datos solo requerirán cambios en un archivo de frontend.

## 6. Riesgos y Mitigación
*   **Riesgo**: Diferencias en los endpoints de Laravel (Borrador vs Crear).
*   **Mitigación**: El backend ya está preparado para recibir el formato de `PayloadNormalizer` en la mayoría de sus servicios (`PedidoImagenesEppService`, `ResolutorImagenesService`). Solo se requiere validar que los controladores mapeen correctamente los campos adicionales de borradores.
