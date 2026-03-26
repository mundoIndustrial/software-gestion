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
        console.log('⚙️ [Variaciones] prenda.variantes (tipo):', typeof prenda?.variantes);
        console.log('⚙️ [Variaciones] prenda.variantes (es array?):', Array.isArray(prenda?.variantes));
        console.log('⚙️ [Variaciones] prenda.variantes (contenido):', prenda?.variantes);
        
        // 🔴 FIX: El backend devuelve variantes como ARRAY, no como objeto
        // Usar la primera variante si es array
        let varianteObj = prenda?.variantes;
        if (Array.isArray(varianteObj) && varianteObj.length > 0) {
            console.log('⚙️ [Variaciones] Detectado array de variantes, usando primera:', varianteObj[0]);
            varianteObj = varianteObj[0];
        }
        
        // Crear objeto con variantes para pasar a métodos
        const prendaConVariantes = {
            ...prenda,
            variantes: varianteObj
        };
        
        this._cargarManga(prendaConVariantes);
        this._cargarBolsillos(prendaConVariantes);
        this._cargarBroche(prendaConVariantes);
        
        console.log(' [Variaciones] Completado');
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

        // 🔑 CRÍTICO: Aceptar ambos casos
        // Caso 1: prenda.variantes es un OBJETO (desde otros lugares)
        // Caso 2: prenda.variantes es un ARRAY (desde BD) - ya convertido a objeto en cargar()
        const manga = prenda.variantes?.tipo_manga || 
                     prenda.variantes?.manga ||
                     prenda.variaciones?.manga || 
                     prenda.manga;
        const obsValue = prenda.variantes?.obs_manga || 
                        prenda.variantes?.manga_obs;
        
        console.log(' [Manga] Buscando manga:', {
            'prenda.variantes.tipo_manga': prenda.variantes?.tipo_manga,
            'prenda.variantes.manga': prenda.variantes?.manga,
            'prenda.variaciones.manga': prenda.variaciones?.manga,
            'prenda.manga': prenda.manga,
            'manga_encontrado': manga,
            'obs_encontrada': obsValue
        });
        
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
            
            console.log(' [Manga] Cargado - Tipo:', manga, 'Obs:', obsValue);
        } else {
            console.log('ℹ️ [Manga] Sin manga para cargar');
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

        // 🔑 CRÍTICO: Aceptar ambos casos
        // Caso 1: prenda.variantes es un OBJETO
        // Caso 2: prenda.variantes es un ARRAY (ya convertido a objeto en cargar())
        const tieneBolsillos = prenda.variantes?.tiene_bolsillos || 
                              prenda.variaciones?.bolsillos?.aplicar;
        const obsValue = prenda.variantes?.obs_bolsillos || 
                        prenda.variantes?.bolsillos_obs ||
                        prenda.variaciones?.bolsillos?.observacion;
        
        console.log(' [Bolsillos] Buscando bolsillos:', {
            'prenda.variantes.tiene_bolsillos': prenda.variantes?.tiene_bolsillos,
            'prenda.variantes.bolsillos_obs': prenda.variantes?.bolsillos_obs,
            'prenda.variaciones.bolsillos': prenda.variaciones?.bolsillos,
            'tieneBolsillos_encontrado': tieneBolsillos,
            'obs_encontrada': obsValue
        });
        
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
            
            console.log(' [Bolsillos] Cargado - Obs:', obsValue);
        } else {
            console.log('ℹ️ [Bolsillos] Sin bolsillos para cargar');
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

        // 🔑 CRÍTICO: Aceptar ambos casos
        // Caso 1: prenda.variantes es un OBJETO
        // Caso 2: prenda.variantes es un ARRAY (ya convertido a objeto en cargar())
        const broche = prenda.variantes?.tipo_broche || 
                      prenda.variantes?.tipo_broche_boton ||
                      prenda.variaciones?.broche?.tipo;
        const obsValue = prenda.variantes?.obs_broche || 
                        prenda.variantes?.broche_boton_obs ||
                        prenda.variaciones?.broche?.observacion;
        
        console.log(' [Broche] Buscando broche:', {
            'prenda.variantes.tipo_broche': prenda.variantes?.tipo_broche,
            'prenda.variantes.tipo_broche_boton': prenda.variantes?.tipo_broche_boton,
            'prenda.variantes.broche_boton_obs': prenda.variantes?.broche_boton_obs,
            'prenda.variaciones.broche': prenda.variaciones?.broche,
            'broche_encontrado': broche,
            'obs_encontrada': obsValue
        });
        
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
            
            console.log(' [Broche] Cargado - Tipo:', broche, 'Obs:', obsValue);
        } else {
            console.log('ℹ️ [Broche] Sin broche para cargar');
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
