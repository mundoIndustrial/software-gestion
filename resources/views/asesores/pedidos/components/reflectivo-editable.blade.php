<!-- PASO 3: Reflectivo (Proceso) - Componente Reutilizable -->
<div class="form-section" id="seccion-reflectivo" style="display: none;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0;">
            <span>3</span> Reflectivo
        </h2>
        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
            <input type="checkbox" id="checkbox-reflectivo" style="width: 18px; height: 18px; cursor: pointer;">
            <span>Aplicar Reflectivo</span>
        </label>
    </div>

    <div id="reflectivo-resumen-contenido" style="margin-top: 1rem; padding: 1rem; background: #f3f4f6; border-left: 4px solid #0066cc; border-radius: 6px; display: none;">
    </div>
</div>
<script>
    // Event listener para el checkbox de reflectivo
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxReflectivo = document.getElementById('checkbox-reflectivo');
        if (checkboxReflectivo) {
            checkboxReflectivo.addEventListener('change', function(e) {
                // Solo abrir modal si fue un cambio manual del usuario (no programático)
                if (e.isTrusted && this.checked) {
                    // Abre el modal de configuración de reflectivo
                    window.abrirModalReflectivo();
                }
            });
        }
    });
</script>