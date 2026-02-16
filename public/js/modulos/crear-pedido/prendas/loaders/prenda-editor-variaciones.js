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
        console.log('⚙️ [Variaciones] Objeto prenda recibido:', prenda);
        console.log('⚙️ [Variaciones] prenda.variaciones:', prenda?.variaciones);
        console.log('⚙️ [Variaciones] prenda.manga:', prenda?.manga);
        console.log('⚙️ [Variaciones] prenda.bolsillos:', prenda?.bolsillos);
        console.log('⚙️ [Variaciones] prenda.broche:', prenda?.broche);
        
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

        // 🔑 CRÍTICO: Buscar en prenda.variantes (no variaciones) - Aquí es donde se guarda realmente
        const manga = prenda.variantes?.tipo_manga || prenda.variaciones?.manga || prenda.manga;
        const obsValue = prenda.variantes?.obs_manga;
        
        if (manga) {
            // Marcar checkbox
            checkbox.checked = true;
            
            // Habilitar inputs ANTES de llenarlos
            input.disabled = false;
            obs.disabled = false;
            
            // Llenar valores
            input.value = manga;
            if (obsValue) {
                obs.value = obsValue;
            }
            
            // Disparar change event para que otros listeners se actualicen
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            
            console.log('✅ [Manga] Cargado - Tipo:', manga, 'Obs:', obsValue);
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

        // 🔑 CRÍTICO: Buscar en prenda.variantes.tiene_bolsillos y obs_bolsillos
        const tieneBolsillos = prenda.variantes?.tiene_bolsillos;
        const obsValue = prenda.variantes?.obs_bolsillos;
        
        if (tieneBolsillos || obsValue) {
            // Marcar checkbox
            checkbox.checked = true;
            
            // Habilitar input ANTES de llenarlo
            obs.disabled = false;
            
            // Llenar valor
            if (obsValue) {
                obs.value = obsValue;
            }
            
            // Disparar change event para que otros listeners se actualicen
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            
            console.log('✅ [Bolsillos] Cargado - Obs:', obsValue);
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

        // 🔑 CRÍTICO: Buscar en prenda.variantes.tipo_broche
        const broche = prenda.variantes?.tipo_broche;
        const obsValue = prenda.variantes?.obs_broche;
        
        if (broche || obsValue) {
            // Marcar checkbox
            checkbox.checked = true;
            
            // Habilitar inputs ANTES de llenarlos
            input.disabled = false;
            obs.disabled = false;
            
            // Llenar valores
            // 🔴 El <select> tiene values "boton"/"broche" (lowercase, sin acento)
            // pero la BD retorna "Botón"/"Broche" (con mayúscula y acento)
            // Normalizar para que matchee las options del select
            if (broche) {
                const brocheNormalizado = broche
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')  // quitar acentos
                    .trim();
                input.value = brocheNormalizado;
                
                // Si no matcheó ninguna option, intentar match parcial
                if (input.value === '' || input.selectedIndex === 0) {
                    const options = Array.from(input.options);
                    const match = options.find(opt => {
                        const optNorm = opt.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                        return optNorm === brocheNormalizado || brocheNormalizado.includes(optNorm) || optNorm.includes(brocheNormalizado);
                    });
                    if (match) {
                        input.value = match.value;
                    }
                    console.log('[Broche] Normalización:', { original: broche, normalizado: brocheNormalizado, seleccionado: input.value });
                }
            }
            if (obsValue) {
                obs.value = obsValue;
            }
            
            // Disparar change event para que otros listeners se actualicen
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            
            console.log('✅ [Broche] Cargado - Tipo:', broche, 'Obs:', obsValue);
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
