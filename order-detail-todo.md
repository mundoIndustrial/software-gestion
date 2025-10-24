# TODO: Add Logo to Order Detail Modal

## Steps for Logo:
- [x] Edit resources/views/components/order-detail-modal.blade.php to insert the centered logo image at the top of the order-detail-card div and add corresponding styles.
- [x] Update this TODO file to mark the edit as completed.
- [x] Adjust the logo position slightly higher (negative top margin of -10px) based on user feedback.
- [ ] Clear Laravel view cache (php artisan view:clear) and test the modal by opening it from the orders view to verify the logo appears centered and sized appropriately.

## Steps for Date Section:
- [x] Edit app/Http/Controllers/RegistroOrdenController.php to add show method for fetching order by pedido.
- [x] Edit routes/web.php to add GET route for /registros/{pedido}.
- [x] Edit resources/views/components/order-detail-modal.blade.php to add <div id="order-date" class="order-date"></div> after the logo and styles for positioning.
- [x] Edit resources/views/orders/index.blade.php to modify viewDetail function for async fetch, format date as "FEC DD MMM YY", and set the div content.
- [x] Improve date design: Change to black card with rounded borders, "FEC" label, and three separate white rounded boxes for day, month, year.
- [x] Update resources/views/components/order-detail-modal.blade.php with new HTML structure and styles for the date card.
- [x] Update resources/views/orders/index.blade.php JS to set individual day, month, year in the boxes.
- [x] Add "RECIBO DE COSTURA" title centered below logo and date with bold, uppercase, 20px Arial font.
- [x] Add order number (pedido) centered below "RECIBO DE COSTURA" with same style (bold, uppercase, 20px Arial, black).
- [x] Add "ASESORA:" label below date card and populate with asesora value from order data.
- [x] Add "FORMA DE PAGO:" label below asesora and populate with forma_de_pago value from order data.
- [x] Add "CLIENTE:" label below order number at the same top as forma de pago and populate with cliente value from order data.
- [x] Add descripcion (prenda details) centered below forma de pago, with navigation arrows for multiple prendas (red arrows, show next when clicked, add back arrow, hide next at last prenda).
- [x] Add blur backdrop to the modal (same as settings modal) to blur the background content.
- [x] Clear route and view cache (php artisan route:clear && php artisan view:clear) and test by opening modal to verify date appears in top-right position with new design and title.

## Steps for Encargado de Orden Section:
- [x] Edit resources/views/orders/index.blade.php: In the viewDetail() JS function, after populating clienteValue, add code to populate the new <span id="encargado-value"></span> with order.encargado_orden || ''.
- [x] Edit resources/views/components/order-detail-modal.blade.php: Add a new div class="signature-section" positioned absolutely at the bottom of .order-detail-card. Inside, two divs: left for "ENCARGADO DE ORDEN: <span id="encargado-value"></span>" and right for "RECIBIDO:", each with a horizontal line below (e.g., <div class="signature-line"></div>). Add styles: position absolute; bottom: 50px; left: 20px (left) / right: 20px (right); font-weight: bold; font-size: 14px; color: #000; .signature-line { border-bottom: 1px solid #000; margin-top: 5px; width: 200px; }.
- [x] Clear view cache: execute_command php artisan view:clear
- [x] Test: Use browser_action to launch http://localhost/mundoindustrial/orders (assuming local server), click "Ver" on an order with encargado_orden data, verify the new section appears at bottom with populated value on left and empty on right "Recibido".
