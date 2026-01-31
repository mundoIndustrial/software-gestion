/**
 * RENDERIZADOR DE COTIZACIONES - Solución específica para renderizar prendas desde cotizaciones
 * 
 * Este módulo se encarga de renderizar las prendas que vienen de una cotización
 * en el contenedor "Ítems del Pedido" sin afectar la lógica existente.
 */

(function() {
    'use strict';

    /**
     * Renderizar prendas desde cotización en el contenedor principal
     * @param {Array} prendas - Array de prendas desde cotización
     */
    window.renderizarPrendasDesdeCotizacion = function(prendas) {
        console.log('[renderizador-cotizaciones] 🎬 Iniciando renderizado de prendas desde cotización:', prendas.length);
        
        const container = document.getElementById('prendas-container-editable');
        if (!container) {
            console.error('[renderizador-cotizaciones] ❌ Container no encontrado');
            return;
        }

        // ✅ LIMPIAR COMPLETAMENTE el container ANTES de agregar nuevos elementos
        console.log('[renderizador-cotizaciones] 🧹 Limpiando container...');
        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }
        container.innerHTML = ''; // Asegurar limpieza total

        if (!prendas || prendas.length === 0) {
            const emptyDiv = document.createElement('div');
            emptyDiv.style.cssText = 'text-align: center; padding: 2rem;';
            const emptyP = document.createElement('p');
            emptyP.style.cssText = 'color: #6b7280; margin-bottom: 1rem;';
            emptyP.textContent = 'No hay prendas agregadas desde la cotización.';
            emptyDiv.appendChild(emptyP);
            container.appendChild(emptyDiv);
            return;
        }

        // Crear fragmento para agregar elementos de una sola vez (mejor performance)
        const fragment = document.createDocumentFragment();

        // Usar el mismo sistema que el sistema normal: generarTarjetaPrendaReadOnly
        prendas.forEach((prenda, index) => {
            if (typeof window.generarTarjetaPrendaReadOnly === 'function') {
                console.log('[renderizador-cotizaciones]  Usando generarTarjetaPrendaReadOnly para prenda:', index);
                const tarjetaHtml = window.generarTarjetaPrendaReadOnly(prenda, index);
                if (tarjetaHtml) {
                    // Crear elemento temporal para parsear HTML
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = tarjetaHtml;
                    // Agregar cada elemento al fragmento
                    while (tempDiv.firstChild) {
                        fragment.appendChild(tempDiv.firstChild);
                    }
                }
            } else {
                console.warn('[renderizador-cotizaciones] ⚠️ generarTarjetaPrendaReadOnly no disponible, usando fallback simple');
                // Fallback simple si el sistema normal no está disponible
                const tarjetaHtml = generarTarjetaSimple(prenda, index);
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = tarjetaHtml;
                while (tempDiv.firstChild) {
                    fragment.appendChild(tempDiv.firstChild);
                }
            }
        });

        // ✅ Agregar el fragmento completo de una sola vez
        container.appendChild(fragment);
        
        console.log('[renderizador-cotizaciones] ✅ Renderizado completado - ' + prendas.length + ' prendas renderizadas');
    };

    /**
     * Fallback simple si el sistema normal no está disponible
     * @param {Object} prenda - Datos de la prenda
     * @param {number} index - Índice de la prenda
     * @returns {string} HTML de la tarjeta simple
     */
    function generarTarjetaSimple(prenda, index) {
        const nombre = prenda.nombre_prenda || prenda.nombre || 'Prenda sin nombre';
        const descripcion = prenda.descripcion || '';
        const cantidad = prenda.cantidad || 1;
        
        return `
            <div class="prenda-cotizacion-item" data-index="${index}" style="
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 1rem;
                margin-bottom: 1rem;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            ">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #1f2937;">${nombre}</h4>
                        ${descripcion ? `<p style="margin: 0 0 1rem 0; color: #6b7280; font-size: 0.9rem;">${descripcion}</p>` : ''}
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <span style="background: #10b981; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                                Cantidad: ${cantidad}
                            </span>
                            <span style="background: #6366f1; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                                Cotización
                            </span>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                    <button onclick="editarPrendaCotizacion(${index})" style="
                        background: #3b82f6;
                        color: white;
                        border: none;
                        padding: 0.5rem 1rem;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 0.875rem;
                    ">Editar</button>
                    <button onclick="eliminarPrendaCotizacion(${index})" style="
                        background: #ef4444;
                        color: white;
                        border: none;
                        padding: 0.5rem 1rem;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 0.875rem;
                    ">Eliminar</button>
                </div>
            </div>
        `;
    };

    /**
     * Generar HTML para una tarjeta de prenda desde cotización
     * @param {Object} prenda - Datos de la prenda
     * @param {number} index - Índice de la prenda
     * @returns {string} HTML de la tarjeta
     */
    function generarTarjetaPrendaCotizacion(prenda, index) {
        const nombre = prenda.nombre_prenda || prenda.nombre || 'Prenda sin nombre';
        const descripcion = prenda.descripcion || '';
        const cantidad = prenda.cantidad || 1;
        
        // Generar HTML de imágenes
        let imagenesHtml = '';
        if (prenda.imagenes && prenda.imagenes.length > 0) {
            imagenesHtml = prenda.imagenes.map(img => 
                `<img src="${img.ruta}" alt="${nombre}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;" />`
            ).join('');
        }

        // Generar HTML de telas
        let telasHtml = '';
        if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
            telasHtml = prenda.telasAgregadas.map(tela => 
                `<div style="background: #f3f4f6; padding: 4px; border-radius: 4px; margin: 2px;">
                    <strong>${tela.nombre_tela}</strong><br>
                    <small>${tela.color}</small>
                </div>`
            ).join('');
        }

        return `
            <div class="prenda-cotizacion-item" data-index="${index}" style="
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 1rem;
                margin-bottom: 1rem;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            ">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #1f2937;">${nombre}</h4>
                        ${descripcion ? `<p style="margin: 0 0 1rem 0; color: #6b7280; font-size: 0.9rem;">${descripcion}</p>` : ''}
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <span style="background: #10b981; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                                Cantidad: ${cantidad}
                            </span>
                            <span style="background: #6366f1; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                                Cotización
                            </span>
                        </div>
                    </div>
                    ${imagenesHtml ? `<div style="margin-left: 1rem;">${imagenesHtml}</div>` : ''}
                </div>
                
                ${telasHtml ? `
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                        <strong style="font-size: 0.85rem; color: #374151;">Telas:</strong>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem;">
                            ${telasHtml}
                        </div>
                    </div>
                ` : ''}
                
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                    <button onclick="editarPrendaCotizacion(${index})" style="
                        background: #3b82f6;
                        color: white;
                        border: none;
                        padding: 0.5rem 1rem;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 0.875rem;
                    ">Editar</button>
                    <button onclick="eliminarPrendaCotizacion(${index})" style="
                        background: #ef4444;
                        color: white;
                        border: none;
                        padding: 0.5rem 1rem;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 0.875rem;
                    ">Eliminar</button>
                </div>
            </div>
        `;
    }

    /**
     * Agregar event listeners para las tarjetas
     */
    function agregarEventListenersCotizacion() {
        // Los event listeners se agregan inline en el HTML generado
        console.log('[renderizador-cotizaciones] 📋 Event listeners agregados');
    }

    /**
     * Funciones globales para interactuar con las prendas
     */
    window.editarPrendaCotizacion = function(index) {
        console.log('[renderizador-cotizaciones] ✏️ Editando prenda:', index);
        // Aquí se puede agregar la lógica para editar la prenda
    };

    window.eliminarPrendaCotizacion = function(index) {
        console.log('[renderizador-cotizaciones] 🗑️ Eliminando prenda:', index);
        // Aquí se puede agregar la lógica para eliminar la prenda
    };

    console.log('[renderizador-cotizaciones] 🚀 Módulo de renderizador de cotizaciones cargado');
})();
