/**
 * ⚙️ Módulo de Variaciones (Manga, Bolsillos, Broche)
 * Responsabilidad: Cargar variaciones específicas en el modal
 */

class PrendaEditorVariaciones {
    /**
     * Cargar variaciones específicas (manga, bolsillos, broche)
     */
    static cargar(prenda) {
        console.log('⚙️ [Variaciones] Cargando manga, bolsillos, broche');
        
        this._cargarManga(prenda);
        this._cargarBolsillos(prenda);
        this._cargarBroche(prenda);
        
        console.log('✅ [Variaciones] Completado');
    }

    /**
     * Cargar manga
     * @private
     */
    static _cargarManga(prenda) {
        const checkbox = document.getElementById('aplica-manga');
        const input = document.getElementById('manga-input');
        const obs = document.getElementById('manga-obs');

        if (!checkbox || !input || !obs) {
            console.warn('⚠️ [Manga] Elementos no encontrados');
            return;
        }

        const manga = prenda.variaciones?.manga || prenda.manga;
        if (manga) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            
            if (manga.tipo || manga.nombre) {
                input.value = manga.tipo || manga.nombre;
                input.disabled = false;
            }
            if (manga.observaciones) {
                obs.value = manga.observaciones;
                obs.disabled = false;
            }
            console.log('✅ [Manga] Cargado');
        }
    }

    /**
     * Cargar bolsillos
     * @private
     */
    static _cargarBolsillos(prenda) {
        const checkbox = document.getElementById('aplica-bolsillos');
        const obs = document.getElementById('bolsillos-obs');

        if (!checkbox || !obs) {
            console.warn('⚠️ [Bolsillos] Elementos no encontrados');
            return;
        }

        const bolsillos = prenda.variaciones?.bolsillos || prenda.bolsillos;
        if (bolsillos) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            
            if (bolsillos.observaciones) {
                obs.value = bolsillos.observaciones;
                obs.disabled = false;
            }
            console.log('✅ [Bolsillos] Cargado');
        }
    }

    /**
     * Cargar broche/botón
     * @private
     */
    static _cargarBroche(prenda) {
        const checkbox = document.getElementById('aplica-broche');
        const input = document.getElementById('broche-input');
        const obs = document.getElementById('broche-obs');

        if (!checkbox || !input || !obs) {
            console.warn('⚠️ [Broche] Elementos no encontrados');
            return;
        }

        const broche = prenda.variaciones?.broche || prenda.broche;
        if (broche) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            
            if (broche.tipo || broche.nombre) {
                input.value = broche.tipo || broche.nombre;
                input.disabled = false;
            }
            if (broche.observaciones) {
                obs.value = broche.observaciones;
                obs.disabled = false;
            }
            console.log('✅ [Broche] Cargado');
        }
    }

    /**
     * Limpiar variaciones
     */
    static limpiar() {
        const checkboxes = [
            document.getElementById('aplica-manga'),
            document.getElementById('aplica-bolsillos'),
            document.getElementById('aplica-broche')
        ];
        
        checkboxes.forEach(cb => {
            if (cb) cb.checked = false;
        });

        const inputs = [
            document.getElementById('manga-input'),
            document.getElementById('manga-obs'),
            document.getElementById('bolsillos-obs'),
            document.getElementById('broche-input'),
            document.getElementById('broche-obs')
        ];
        
        inputs.forEach(input => {
            if (input) {
                input.value = '';
                input.disabled = true;
            }
        });
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorVariaciones;
}
