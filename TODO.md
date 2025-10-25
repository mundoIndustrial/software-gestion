# TODO: Modificar Sidebar - Unir Ordenes-Pedidos y Ordenes-Bodega en un solo botón "Ordenes"

## Pasos a completar:

- [x] Modificar `resources/views/layouts/sidebar.blade.php`:
  - Reemplazar los dos elementos `<li>` separados para "Ordenes-Pedidos" y "Ordenes-Bodega" con un solo `<li>` que contenga un botón "Ordenes" y un submenú `<ul>` anidado con las dos opciones.
  - Agregar clases CSS necesarias para el submenú (e.g., `submenu`, `submenu-item`).

- [x] Actualizar `public/css/sidebar.css`:
  - Agregar estilos para el submenú desplegable: ocultar por defecto, animación de deslizamiento, colores consistentes con el tema.
  - Asegurar que funcione en modo collapsed y expanded del sidebar.

- [x] Actualizar `public/js/sidebar.js`:
  - Agregar event listener al botón "Ordenes" para toggle (mostrar/ocultar) el submenú.
  - Prevenir que el sidebar se colapse cuando se hace clic en el botón del submenú si está en modo collapsed.

- [x] Unir "Entrega-Pedidos" y "Entrega Bodega" en un solo botón "Entregas" con submenú.
  - Modificar `resources/views/layouts/sidebar.blade.php` para reemplazar los dos botones con uno solo.
  - Actualizar `public/js/sidebar.js` para manejar múltiples submenús (usar querySelectorAll).

- [x] Verificar funcionalidad:
  - Probar que los submenús se desplieguen correctamente al hacer clic en "Ordenes" y "Entregas".
  - Asegurar que las rutas y enlaces sigan funcionando.
  - Verificar en diferentes tamaños de pantalla y modos (collapsed/expanded).
