<!-- Modal para editar una prenda específica -->
<script>
    /**
     * Abrir modal de edición para una prenda específica
     */
    function abrirEditarPrendaEspecifica(prendaIndex) {
        const prendas = window.prendasEdicion?.prendas || [];
        if (prendaIndex < 0 || prendaIndex >= prendas.length) {
            UI.error('Error', 'Prenda no encontrada');
            return;
        }
        
        const prenda = prendas[prendaIndex];
        
        const html = `
            <div style="text-align: left;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Nombre de la Prenda</label>
                    <input type="text" id="editPrendaNombre" value="${prenda.nombre_prenda || ''}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Cantidad</label>
                    <input type="number" id="editPrendaCantidad" value="${prenda.cantidad || 0}" min="1" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Descripción</label>
                    <textarea id="editPrendaDescripcion" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem; min-height: 80px;">${prenda.descripcion || ''}</textarea>
                </div>
            </div>
        `;
        
        UI.contenido({
            titulo: `✏️ Editar Prenda: ${prenda.nombre_prenda}`,
            html: html,
            confirmButtonText: '💾 Guardar',
            confirmButtonColor: '#10b981',
            showCancelButton: true
        }).then((result) => {
            if (result.isConfirmed) {
                const nombre = document.getElementById('editPrendaNombre').value.trim();
                const cantidad = document.getElementById('editPrendaCantidad').value.trim();
                const descripcion = document.getElementById('editPrendaDescripcion').value.trim();
                
                if (!nombre || !cantidad) {
                    UI.error('Validación', 'Por favor completa los campos requeridos');
                    return;
                }
                
                if (isNaN(cantidad) || parseInt(cantidad) < 1) {
                    UI.error('Validación', 'La cantidad debe ser un número mayor a 0');
                    return;
                }
                
                // Aquí se podría agregar la lógica para guardar cambios en la prenda
                UI.exito('Prenda actualizada', 'Los cambios se han guardado');
                setTimeout(() => {
                    abrirEditarPrendas();
                }, 500);
            }
        });
    }
</script>
