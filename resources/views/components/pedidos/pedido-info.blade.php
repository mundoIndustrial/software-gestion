<!-- Componente: pedido-info.blade.php -->
<!-- Responsabilidad única: Mostrar información del pedido (read-only) -->
<div class="form-row">
    <div class="form-group">
        <label for="numero_cotizacion" class="block text-sm font-medium text-gray-700 mb-2">Número de Cotización</label>
        <input type="text" id="numero_cotizacion" name="numero_cotizacion" 
               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-blue-50 font-bold text-blue-600" 
               readonly>
    </div>

    <div class="form-group">
        <label for="cliente" class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
        <input type="text" id="cliente" name="cliente" 
               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" 
               readonly>
    </div>

    <div class="form-group">
        <label for="asesora" class="block text-sm font-medium text-gray-700 mb-2">Asesora</label>
        <input type="text" id="asesora" name="asesora" 
               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" 
               readonly>
    </div>

    <div class="form-group">
        <label for="forma_de_pago" class="block text-sm font-medium text-gray-700 mb-2">Forma de Pago</label>
        <input type="text" id="forma_de_pago" name="forma_de_pago" 
               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" 
               readonly>
    </div>

    <div class="form-group">
        <label for="numero_pedido" class="block text-sm font-medium text-gray-700 mb-2">Número de Pedido</label>
        <input type="text" id="numero_pedido" name="numero_pedido" 
               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" 
               readonly>
    </div>
</div>
