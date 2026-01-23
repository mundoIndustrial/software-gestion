/**
 * Utilidades para recopilar datos del formulario
 * Extrae datos del DOM y los prepara para envío
 */

class FormDataCollector {
    /**
     * Recopilar datos de prendas desde el DOM
     * @param {Array} prendasCargadas - Array de prendas
     * @param {Set} prendasEliminadas - Set de índices eliminados
     * @returns {Array} Array de prendas con cantidades
     */
    static recopilarPrendas(prendasCargadas, prendasEliminadas) {
        const prendasParaEnviar = [];
        
        prendasCargadas.forEach((prenda, index) => {
            if (prendasEliminadas.has(index)) {

                return;
            }

            const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${index}"]`);
            if (!prendasCard) return;
            
            // Obtener cantidades por talla
            const cantidadesPorTalla = this.obtenerCantidadesPorTalla(prendasCard);
            
            prendasParaEnviar.push({
                index: index,
                nombre_producto: prenda.nombre_producto,
                cantidades: cantidadesPorTalla
            });
        });
        
        return prendasParaEnviar;
    }

    /**
     * Obtener cantidades por talla de una prenda
     * @param {HTMLElement} prendasCard - Card de la prenda
     * @returns {Object} Objeto con tallas y cantidades
     */
    static obtenerCantidadesPorTalla(prendasCard) {
        const tallasInputs = prendasCard.querySelectorAll('.talla-cantidad');
        const cantidadesPorTalla = {};
        
        tallasInputs.forEach(input => {
            const talla = input.getAttribute('data-talla');
            const cantidad = parseInt(input.value) || 0;
            if (talla && cantidad > 0) {
                cantidadesPorTalla[talla] = cantidad;
            }
        });
        
        return cantidadesPorTalla;
    }

    /**
     * Recopilar datos de logo desde cotización o DOM
     * @param {Object} currentLogoCotizacion - Datos de logo de cotización
     * @returns {Object} Datos del logo
     */
    static recopilarDatosLogo(currentLogoCotizacion) {
        let tecnicas = [];
        let secciones = [];
        let observacionesTecnicas = '';
        let descripcion = '';

        if (currentLogoCotizacion && Object.keys(currentLogoCotizacion).length > 0) {
            // Desde cotización

            
            // Técnicas
            if (currentLogoCotizacion.tecnicas) {
                tecnicas = Array.isArray(currentLogoCotizacion.tecnicas) 
                    ? currentLogoCotizacion.tecnicas 
                    : [currentLogoCotizacion.tecnicas];
            }
            
            // Observaciones técnicas
            if (currentLogoCotizacion.observaciones_tecnicas) {
                observacionesTecnicas = currentLogoCotizacion.observaciones_tecnicas;
            }
            
            // Descripción
            if (currentLogoCotizacion.descripcion) {
                descripcion = currentLogoCotizacion.descripcion;
            }
            
            // Ubicaciones/Secciones
            if (currentLogoCotizacion.ubicaciones && Array.isArray(currentLogoCotizacion.ubicaciones)) {
                secciones = currentLogoCotizacion.ubicaciones.map(ub => {
                    const tallas = ub.tallas || [];
                    const cantidadTotal = tallas.reduce((sum, t) => sum + (parseInt(t.cantidad) || 0), 0);
                    
                    return {
                        seccion: ub.seccion || ub.ubicacion || '',
                        tallas: tallas,
                        ubicaciones: ub.ubicaciones || [],
                        observaciones: ub.observaciones || '',
                        cantidad: cantidadTotal
                    };
                });
            }
            
        } else {
            // Desde formulario DOM

            
            // Técnicas
            tecnicas = this.obtenerTecnicasDelDOM();
            
            // Observaciones técnicas
            const obsInput = document.getElementById('observaciones_tecnicas');
            if (obsInput) {
                observacionesTecnicas = obsInput.value || '';
            }
            
            // Descripción
            const descripcionInput = document.getElementById('logo_descripcion');
            if (descripcionInput) {
                descripcion = descripcionInput.value || '';
            }
            
            // Secciones
            secciones = this.obtenerSeccionesDelDOM();
        }

        // Calcular cantidad total
        let cantidadTotal = 0;
        secciones.forEach(seccion => {
            seccion.tallas.forEach(talla => {
                cantidadTotal += talla.cantidad || 0;
            });
        });

        return {
            tecnicas,
            secciones,
            observacionesTecnicas,
            descripcion,
            cantidadTotal
        };
    }

    /**
     * Obtener técnicas desde el DOM
     * @returns {Array} Array de técnicas
     */
    static obtenerTecnicasDelDOM() {
        let tecnicas = [];
        
        // Desde campo hidden
        const tecnicasHiddenField = document.getElementById('paso3_tecnicas_datos');
        if (tecnicasHiddenField && tecnicasHiddenField.value) {
            try {
                tecnicas = JSON.parse(tecnicasHiddenField.value);

                return tecnicas;
            } catch (e) {

            }
        }
        
        // Fallback: badges visuales
        const tecnicasBadges = document.querySelectorAll('#tecnicas_seleccionadas span');
        tecnicasBadges.forEach(badge => {
            const tecnicaText = badge.textContent.replace('×', '').trim();
            if (tecnicaText) tecnicas.push(tecnicaText);
        });
        

        return tecnicas;
    }

    /**
     * Obtener secciones desde el DOM
     * @returns {Array} Array de secciones
     */
    static obtenerSeccionesDelDOM() {
        let secciones = [];
        
        // Desde campo hidden
        const seccionesHiddenField = document.getElementById('paso3_secciones_datos');
        if (seccionesHiddenField && seccionesHiddenField.value) {
            try {
                const seccionesRaw = JSON.parse(seccionesHiddenField.value);
                secciones = seccionesRaw.map(seccion => {
                    const cantidadTotal = seccion.tallas?.reduce((sum, t) => sum + (parseInt(t.cantidad) || 0), 0) || 0;
                    return {
                        seccion: seccion.ubicacion,
                        tallas: seccion.tallas || [],
                        ubicaciones: seccion.opciones || [],
                        observaciones: seccion.observaciones || '',
                        cantidad: cantidadTotal
                    };
                });

                return secciones;
            } catch (e) {

            }
        }
        
        // Fallback: cards visuales
        const seccionCards = document.querySelectorAll('#secciones_agregadas > div');
        seccionCards.forEach(card => {
            const headerSpan = card.querySelector('div:first-child span:first-child');
            const prenda = headerSpan?.textContent.trim() || '';
            
            const contentDiv = card.querySelector('div:last-child');
            const contentHtml = contentDiv?.innerHTML || '';
            
            const tallas = [];
            const tallasMatch = contentHtml.match(/<strong>Tallas:<\/strong>\s*([^<]+)/);
            if (tallasMatch) {
                const tallaMatches = tallasMatch[1].matchAll(/([A-Z0-9]+)\s*\((\d+)\)/g);
                for (const match of tallaMatches) {
                    tallas.push({ talla: match[1], cantidad: parseInt(match[2]) });
                }
            }
            
            const ubicacionesMatch = contentHtml.match(/<strong>Ubicaciones:<\/strong>\s*([^<]+)/);
            const ubicaciones = ubicacionesMatch ? ubicacionesMatch[1].split(',').map(u => u.trim()).filter(u => u) : [];
            
            const obsMatch = contentHtml.match(/<strong>Obs:<\/strong>\s*([^<]+)/);
            const observaciones = obsMatch ? obsMatch[1].trim() : '';
            
            const cantidadTotal = tallas.reduce((sum, t) => sum + t.cantidad, 0);
            
            if (prenda) {
                secciones.push({
                    seccion: prenda,
                    tallas: tallas,
                    ubicaciones: ubicaciones,
                    observaciones: observaciones,
                    cantidad: cantidadTotal
                });
            }
        });
        

        return secciones;
    }

    /**
     * Detectar tipo de cotización
     * @returns {Object} Información del tipo de cotización
     */
    static detectarTipoCotizacion() {
        const tipoCotizacionElement = document.querySelector('[data-tipo-cotizacion]');
        const tipoCotizacion = tipoCotizacionElement?.dataset.tipoCotizacion || 'P';
        
        return {
            tipo: tipoCotizacion,
            esCombinada: tipoCotizacion === 'PL',
            esLogoSolo: tipoCotizacion === 'L',
            esPrenda: tipoCotizacion === 'P',
            esReflectivo: tipoCotizacion === 'RF'
        };
    }
}

// Exportar globalmente
window.FormDataCollector = FormDataCollector;
