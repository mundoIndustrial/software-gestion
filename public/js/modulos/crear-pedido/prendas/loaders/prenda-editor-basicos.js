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
        
        // Origen - Mapear desde prenda_bodega si origen no existe
        const origenSelect = document.getElementById('nueva-prenda-origen-select');
        if (origenSelect) {
            let origen = prenda.origen;
            
            // Si no hay origen directo, intentar mapear desde prenda_bodega
            if (!origen && prenda.prenda_bodega !== undefined && prenda.prenda_bodega !== null) {
                origen = (prenda.prenda_bodega === 1 || prenda.prenda_bodega === true) ? 'bodega' : 'confeccion';
                console.log('[📝 Basicos] 🔄 Mapeado origen desde prenda_bodega:', {
                    prenda_bodega: prenda.prenda_bodega,
                    origen_resultante: origen
                });
            }
            
            origenSelect.value = origen || 'confeccion';
            console.log('[📝 Basicos] Origen establecido:', origenSelect.value);
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
