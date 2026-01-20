/**
 * RenderizadorPrendasComponent
 * 
 * Componente responsable de renderizar prendas editables en el formulario de pedidos.
 * Maneja diferentes tipos de cotizaciones: PRENDA, LOGO, REFLECTIVO, COMBINADA (PL)
 * 
 * Funcionalidades:
 * - Renderizado de prendas normales con tallas y fotos
 * - Renderizado de cotizaciones tipo LOGO
 * - Renderizado de cotizaciones REFLECTIVAS
 * - Gestión de tabs (PRENDAS / LOGO)
 * - Integración con galerías de fotos
 * 
 * @author Sistema de Refactorización
 * @date 2026-01-12
 */

(function() {
    'use strict';

    /**
     * Clase principal del componente
     */
    class RenderizadorPrendasComponent {
        constructor() {
            this.prendasContainer = null;
            this.prendasEliminadas = new Set();
        }

        /**
         * Inicializar el componente
         */
        init(prendasContainer, prendasEliminadas) {
            this.prendasContainer = prendasContainer;
            this.prendasEliminadas = prendasEliminadas;
        }

        /**
         * Renderizar prendas editables (función principal)
         * Delega a funciones especializadas según el tipo de cotización
         */
        renderizar(prendas, logoCotizacion = null, especificacionesCotizacion = null, esReflectivo = false, datosReflectivo = null, esLogo = false, tipoCotizacion = 'P') {
            try {
                // Reset galerías por prenda
                window.prendasGaleria = [];
                window.telasGaleria = [];

                // Agregar estilos responsivos
                this._agregarEstilosResponsivos();

                // Caso 1: Sin prendas pero con LOGO
                if ((!prendas || prendas.length === 0) && esLogo && logoCotizacion) {
                    return this._renderizarLogoSolo(tipoCotizacion);
                }

                // Caso 2: Sin prendas
                if (!prendas || prendas.length === 0) {
                    this.prendasContainer.innerHTML = '<p class="text-gray-500 text-center py-8">Esta cotización no tiene prendas</p>';
                    return;
                }

                // Caso 3: Cotización REFLECTIVA
                if (esReflectivo) {
                    return this._renderizarReflectivo(prendas, datosReflectivo, tipoCotizacion);
                }

                // Caso 4: Cotización normal o combinada (con/sin logo)
                this._renderizarPrendasConTabs(prendas, logoCotizacion, tipoCotizacion);

            } catch (error) {
                console.error(' ERROR en renderizar:', error);
                this.prendasContainer.innerHTML = `<p style="color: #ef4444;">Error al renderizar: ${error.message}</p>`;
            }
        }

        /**
         * Agregar estilos responsivos para botones
         */
        _agregarEstilosResponsivos() {
            if (!document.getElementById('tallas-btn-responsive-style')) {
                const style = document.createElement('style');
                style.id = 'tallas-btn-responsive-style';
                style.textContent = `
                    .btn-agregar-talla-texto { display: inline; }
                    @media (max-width: 640px) {
                        .btn-agregar-talla-texto { display: none; }
                    }
                `;
                document.head.appendChild(style);
            }
        }

        /**
         * Renderizar cotización tipo LOGO (sin prendas)
         */
        _renderizarLogoSolo(tipoCotizacion) {
            window.currentTipoCotizacion = tipoCotizacion;
            window.currentEsLogo = true;
            
            if (typeof mostrarSeccionPrendasTecnicasLogoNuevo === 'function') {
                mostrarSeccionPrendasTecnicasLogoNuevo();
            } else {
                console.warn(' mostrarSeccionPrendasTecnicasLogoNuevo no está disponible');
            }
        }

        /**
         * Renderizar cotización REFLECTIVA
         */
        _renderizarReflectivo(prendas, datosReflectivo, tipoCotizacion) {
            const htmlReflectivo = renderizarReflectivo(prendas, datosReflectivo);
            this.prendasContainer.setAttribute('data-tipo-cotizacion', tipoCotizacion);
            this.prendasContainer.innerHTML = htmlReflectivo;
        }

        /**
         * Renderizar prendas con tabs (PRENDAS / LOGO)
         */
        _renderizarPrendasConTabs(prendas, logoCotizacion, tipoCotizacion) {
            const tienePrendas = prendas && prendas.length > 0;
            const tieneLogoPrendas = debeRenderizarLogoTab(tipoCotizacion, logoCotizacion);
            
            let html = '';

            // Crear tabs si hay prendas O logo
            if (tienePrendas || tieneLogoPrendas) {
                html += this._crearTabsNavigation(tienePrendas, tieneLogoPrendas);
                html += window.templates.tabContentWrapper();
                
                if (tienePrendas) {
                    html += window.templates.tabContent('tab-prendas', true);
                }
            }

            // Renderizar cada prenda
            html += this._renderizarPrendas(prendas);

            // Cerrar tab de prendas
            if (tienePrendas) {
                html += '</div>'; // cierra tab-prendas
            }

            // Tab de LOGO (si aplica)
            if (tieneLogoPrendas && logoCotizacion) {
                html += this._renderizarTabLogo(logoCotizacion, tienePrendas);
            }

            // Cerrar tab-content-wrapper
            if (tienePrendas || tieneLogoPrendas) {
                html += '</div>';
            }

            // Insertar HTML en el DOM
            this.prendasContainer.setAttribute('data-tipo-cotizacion', tipoCotizacion);
            this.prendasContainer.innerHTML = html;

            // Cargar técnicas en el tab logo (si aplica)
            if (tieneLogoPrendas && logoCotizacion && logoCotizacion.tecnicas) {
                this._cargarTecnicasLogo(logoCotizacion);
            }
        }

        /**
         * Crear navegación de tabs
         */
        _crearTabsNavigation(tienePrendas, tieneLogoPrendas) {
            let html = window.templates.tabsContainer();
            
            if (tienePrendas) {
                html += window.templates.tabButton('PRENDAS', 'fas fa-box', true);
            }
            
            if (tieneLogoPrendas) {
                const isActive = !tienePrendas;
                html += window.templates.tabButton('LOGO', 'fas fa-tools', isActive);
            }
            
            html += '</div>';
            return html;
        }

        /**
         * Renderizar todas las prendas
         */
        _renderizarPrendas(prendas) {
            let html = '';
            const fotoToUrl = window.FotoHelper.toUrl.bind(window.FotoHelper);

            prendas.forEach((prenda, index) => {
                // Saltar prendas eliminadas
                if (this.prendasEliminadas.has(index)) {
                    return;
                }

                // Procesar fotos de la prenda
                const fotos = this._procesarFotosPrenda(prenda, index, fotoToUrl);
                const telaFotos = this._procesarFotosTelas(prenda, index);

                // Normalizar fotos
                const fotosNormalizadas = (fotos || []).map(f => fotoToUrl(f)).filter(Boolean);
                window.prendasGaleria[index] = fotosNormalizadas;

                // Renderizar HTML de la prenda usando el renderizador existente
                html += this._renderizarPrendaHTML(prenda, index, fotos, telaFotos);
            });

            return html;
        }

        /**
         * Procesar fotos de una prenda (combinar originales + nuevas - eliminadas)
         */
        _procesarFotosPrenda(prenda, index, fotoToUrl) {
            const nuevasFotosPrenda = (window.prendasFotosNuevas && window.prendasFotosNuevas[index]) 
                ? window.prendasFotosNuevas[index] 
                : [];
            
            const fotosBase = (window.prendasCargadas && window.prendasCargadas[index]) 
                ? window.prendasCargadas[index].fotos 
                : (prenda.fotos || []);
            
            let fotos = [...fotosBase];
            
            // Agregar fotos nuevas sin duplicar
            nuevasFotosPrenda.forEach(fotoNueva => {
                const yaExiste = fotosBase.some(f => fotoToUrl(f) === fotoToUrl(fotoNueva));
                if (!yaExiste) {
                    fotos.push(fotoNueva);
                }
            });
            
            // Filtrar fotos eliminadas
            fotos = fotos.filter(foto => !window.fotosEliminadas.has(fotoToUrl(foto)));
            
            return fotos;
        }

        /**
         * Procesar fotos de telas
         */
        _procesarFotosTelas(prenda, index) {
            const telaFotosBase = prenda.telaFotos || [];
            let telaFotos = [...telaFotosBase];
            
            if (window.telasFotosNuevas && window.telasFotosNuevas[index]) {
                Object.entries(window.telasFotosNuevas[index]).forEach(([telaIdx, fotosArr]) => {
                    fotosArr.forEach(f => {
                        telaFotos.push({
                            ...f,
                            tela_id: prenda.telas?.[telaIdx]?.id ?? prenda.telaFotos?.[telaIdx]?.tela_id ?? null
                        });
                    });
                });
            }
            
            return telaFotos;
        }

        /**
         * Renderizar HTML de una prenda individual
         * Delega al renderizador de prenda existente
         */
        _renderizarPrendaHTML(prenda, index, fotos, telaFotos) {
            // Usar el renderizador de prenda existente (definido en otro archivo)
            if (typeof window.renderizarPrendaCard === 'function') {
                return window.renderizarPrendaCard(prenda, index, fotos, telaFotos);
            }
            
            // Fallback: mensaje de error
            return `<div class="alert alert-warning">Error: renderizarPrendaCard no disponible</div>`;
        }

        /**
         * Renderizar tab de LOGO
         */
        _renderizarTabLogo(logoCotizacion, tienePrendas) {
            const isActive = !tienePrendas;
            let html = window.templates.tabContent('tab-logo', isActive);
            
            // Usar LogoComponent para renderizar el contenido del tab
            if (window.LogoComponent) {
                html += window.LogoComponent.renderTabContent(logoCotizacion);
            }
            
            html += window.templates.logoTabContainerClose();
            return html;
        }

        /**
         * Cargar técnicas en el tab logo
         */
        _cargarTecnicasLogo(logoCotizacion) {
            setTimeout(() => {
                const galeriaFotos = document.getElementById('galeria-fotos-logo');
                const tecnicasSeleccionadasDiv = document.getElementById('tecnicas_seleccionadas_logo');
                
                // Renderizar fotos iniciales
                if (galeriaFotos && logoCotizacion.fotos && logoCotizacion.fotos.length > 0) {
                    galeriaFotos.innerHTML = '';
                    logoCotizacion.fotos.forEach((foto) => {
                        const fotoUrl = foto.url || foto.ruta_webp || foto.ruta_original;
                        if (fotoUrl) {
                            const div = document.createElement('div');
                            div.style.cssText = 'position: relative; display: inline-block; width: 100%;';
                            div.innerHTML = `
                                <img src="${fotoUrl}" 
                                     alt="Foto" 
                                     style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 1px solid #d0d0d0;" 
                                     onclick="abrirModalImagen('${fotoUrl}', 'Foto del logo')">
                                <button type="button" 
                                        style="position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 0.9rem; display: flex; align-items: center; justify-content: center;">×</button>
                            `;
                            galeriaFotos.appendChild(div);
                        }
                    });
                }
            }, 100);
        }
    }

    // Crear instancia global
    window.RenderizadorPrendasComponent = new RenderizadorPrendasComponent();

})();
