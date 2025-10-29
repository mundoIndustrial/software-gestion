# TODO: Cambiar Vista Costura a Tarjetas Agrupadas por Pedido

## Pasos a Realizar
- [x] Modificar `resources/views/vista-costura/index.blade.php` para reemplazar la tabla con tarjetas agrupadas por pedido.
- [x] Actualizar `public/css/vista-costura.css` para estilizar las nuevas tarjetas.
- [x] Verificar que el modal de celdas clickables siga funcionando en las tarjetas.
- [x] Probar la vista para asegurar que se muestren correctamente las tarjetas con los datos agrupados.
- [x] Implementar barra de búsqueda en tiempo real para filtrar por número de pedido.

## Detalles del Cambio
- Cada tarjeta representa un pedido único.
- Título de la tarjeta: "Pedido - Cliente".
- Dentro de cada tarjeta: tabla con columnas Prenda, Descripción, Talla, Cantidad, Costurero, Total Producido, Total Pendiente, Fecha Completado.
- Barra de búsqueda automática que filtra por número de pedido a medida que se escribe.
- Mantener paginación y modal existente.
