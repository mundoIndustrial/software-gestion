<!-- Componente: cotizacion-search.blade.php -->
<!-- Responsabilidad única: Mostrar búsqueda de cotizaciones -->
<div class="form-group">
    <label for="cotizacion_search" class="block text-sm font-medium text-gray-700 mb-2">
        Cotización <span class="text-red-500">*</span>
    </label>
    <div style="position: relative;">
        <input type="text" id="cotizacion_search" 
               placeholder=" Buscar por número, cliente o asesora..." 
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
               autocomplete="off">
        <input type="hidden" id="cotizacion_id" name="cotizacion_id" required>
        <div id="cotizacion_dropdown" 
             style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #d1d5db; border-top: none; border-radius: 0 0 8px 8px; max-height: 300px; overflow-y: auto; display: none; z-index: 1000; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
        </div>
    </div>
    <div id="cotizacion_selected" 
         style="margin-top: 0.75rem; padding: 0.75rem; background: #f0f9ff; border-left: 3px solid #0066cc; border-radius: 4px; display: none;">
        <div style="font-size: 0.875rem; color: #1e40af;">
            <strong>Seleccionada:</strong> <span id="cotizacion_selected_text"></span>
        </div>
    </div>
</div>
