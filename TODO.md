# TODO: Implementar Filtro Global en Vista Costura

## Información Recopilada
- Vista actual muestra cards agrupados por pedido y cliente.
- Controlador maneja búsqueda AJAX.
- Datos: cliente, costurero, cortador (encargado de corte).
- Funcionalidad existente: búsqueda por pedido en tiempo real.

## Plan Aprobado
1. **Agregar Botón de Filtro**: Botón con ícono al lado derecho de la barra de búsqueda.
2. **Crear Modal de Filtros**: Modal con checkboxes para cliente, costurero, cortador. Botones "Aplicar" y "Limpiar".
3. **Actualizar Controlador**: Modificar método `search` para aceptar y aplicar filtros (cliente, costurero, cortador).
4. **JavaScript para Filtros**: Manejar modal, cargar opciones dinámicas, enviar AJAX con filtros, persistir en localStorage.
5. **Estilos CSS**: Agregar estilos para botón y modal.

## Archivos a Editar
- `resources/views/vista-costura/index.blade.php`: HTML del botón/modal, JS.
- `app/Http/Controllers/VistaCosturaController.php`: Lógica de filtros en `search`.
- `public/css/vista-costura.css`: Estilos.

## Pasos de Implementación
- [x] Editar `VistaCosturaController.php` para manejar filtros en método `search`.
- [x] Editar `index.blade.php` para agregar botón de filtro y modal HTML.
- [x] Agregar JS en `index.blade.php` para funcionalidad del modal y filtros.
- [x] Editar `vista-costura.css` para estilos del botón y modal.
- [ ] Probar filtro en diferentes escenarios (con/sin búsqueda, múltiples filtros).
- [ ] Verificar persistencia en localStorage.
- [ ] Ajustar responsive si necesario.

---

# TODO: Refactorizar Botones de Acción en Tabla de Órdenes - CANCELADO

## Información Recopilada
- Los botones "Limpiar Filtros" y "Agregar Orden" se estaban creando dinámicamente en JavaScript dentro de `modern-table.js`.
- El usuario pidió mantener la vista como estaba originalmente.

## Plan Cancelado
- Se revirtió todos los cambios realizados.
- La vista `resources/views/orders/index.blade.php` se dejó como estaba originalmente.
- El archivo `public/js/orders.js` creado se mantiene por si se necesita en el futuro, pero no se usa actualmente.

## Archivos Revertidos
- `resources/views/orders/index.blade.php`: Revertido a estado original.
- `public/js/modern-table.js`: Restaurada creación dinámica de botones.
- `public/js/orders.js`: Archivo creado pero no utilizado actualmente.
