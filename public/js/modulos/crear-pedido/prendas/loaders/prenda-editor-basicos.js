/**
 * 📝 Módulo de Campos Básicos
 * Responsabilidad: Cargar nombre, origen, descripción en el modal
 */

class PrendaEditorBasicos {
    /**
     * Cargar campos básicos en modal
     */
    static cargar(prenda) {
        console.log('[📝 Basicos] Cargando nombre, origen, descripción');
        
        // Nombre
        const nombreInput = document.getElementById('nueva-prenda-nombre');
        if (nombreInput) {
            nombreInput.value = prenda.nombre_prenda || prenda.nombre || '';
        }
        
        // Origen
        const origenSelect = document.getElementById('nueva-prenda-origen-select');
        if (origenSelect) {
            origenSelect.value = prenda.origen || 'confeccion';
        }
        
        // Descripción
        const descripcionInput = document.getElementById('nueva-prenda-descripcion');
        if (descripcionInput) {
            descripcionInput.value = prenda.descripcion || '';
        }
        
        console.log('✅ [Basicos] Cargado');
    }

    /**
     * Obtener valores del formulario
     */
    static obtener() {
        return {
            nombre: document.getElementById('nueva-prenda-nombre')?.value || '',
            origen: document.getElementById('nueva-prenda-origen-select')?.value || 'confeccion',
            descripcion: document.getElementById('nueva-prenda-descripcion')?.value || ''
        };
    }

    /**
     * Limpiar campos
     */
    static limpiar() {
        const nombreInput = document.getElementById('nueva-prenda-nombre');
        if (nombreInput) nombreInput.value = '';
        
        const origenSelect = document.getElementById('nueva-prenda-origen-select');
        if (origenSelect) origenSelect.value = 'confeccion';
        
        const descripcionInput = document.getElementById('nueva-prenda-descripcion');
        if (descripcionInput) descripcionInput.value = '';
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorBasicos;
}
