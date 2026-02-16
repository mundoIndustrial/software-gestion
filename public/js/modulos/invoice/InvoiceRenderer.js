/**
 * Renderizador de Facturas
 * Genera el HTML completo de la factura con los datos capturados
 */

class InvoiceRenderer {
    constructor() {
        this.init();
    }

    init() {
        // Hacer método disponible globalmente para compatibilidad
        window.generarHTMLFactura = this.generarHTMLFactura.bind(this);
    }

    /**
     * Genera el HTML de la factura con los datos en tiempo real
     */
    generarHTMLFactura(datos) {
        try {
            console.log('[generarHTMLFactura] Datos recibidos:', {
                datos_existe: !!datos,
                datos_keys: datos ? Object.keys(datos) : 'null',
                prendas_existe: !!(datos && datos.prendas),
                prendas_es_array: !!(datos && datos.prendas && Array.isArray(datos.prendas)),
                prendas_length: datos && datos.prendas ? datos.prendas.length : 'N/A'
            });
            
            // Validar que datos y prendas existan
            if (!datos || !datos.prendas || !Array.isArray(datos.prendas)) {
                return '<div style="color: #dc2626; padding: 1rem; border: 1px solid #fca5a5; border-radius: 6px; background: #fee2e2;"> Error: No se pudieron cargar las prendas del pedido. Estructura de datos inválida.</div>';
            }

            // Si no hay prendas, verificar si hay EPP
            if (datos.prendas.length === 0 && (!datos.epps || datos.epps.length === 0)) {
                return '<div style="color: #f59e0b; padding: 1rem; border: 1px solid #fed7aa; border-radius: 6px; background: #fffbeb;"> Advertencia: El pedido no contiene prendas.</div>';
            }

            // Generar las tarjetas de prendas
            const prendasHTML = datos.prendas.map((prenda, idx) => {
                return this.renderizarPrenda(prenda, idx);
            }).join('');
            
            // Construir el HTML final
            const htmlFacturaFinal = `
                <div style="background: white; padding: 8px; border-radius: 4px; max-width: 100%; margin: 0 auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 11px;">
                    <!-- Header Profesional -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 2px solid #ddd; align-items: start;">
                        <div style="font-size: 11px;">
                            <div style="font-weight: 700; color: #1a3a52; font-size: 11px; margin-bottom: 2px;">${datos.cliente}</div>
                            <div style="color: #666; font-size: 11px;">Asesor: ${datos.asesora}</div>
                            <div style="color: #666; font-size: 11px; margin-top: 3px;">Forma de Pago: <span style="font-weight: 600; color: #1a3a52;">${datos.forma_de_pago || 'No especificada'}</span></div>
                        </div>
                        <div style="text-align: right; font-size: 11px;">
                            <div style="font-weight: 700; color: #1a3a52; font-size: 11px; margin-bottom: 2px;">
                                RECIBO DE PEDIDO #${datos.numero_pedido || datos.numero_pedido_temporal}
                            </div>
                            <div style="color: #666; font-size: 11px;">${datos.fecha_creacion}</div>
                        </div>
                    </div>
                    
                    <!-- Items (Prendas) -->
                    <div style="margin-top: 6px;">
                        ${prendasHTML}
                    </div>
                    
                    <!-- EPP Items -->
                    ${this.renderizarEPP(datos.epps)}
                </div>
            `;
            
            return htmlFacturaFinal;
        } catch (error) {
            return '<div style="color: #dc2626; padding: 1rem; border: 1px solid #fca5a5; border-radius: 6px; background: #fee2e2;"> Error generando factura: ' + error.message + '</div>';
        }
    }

    /**
     * Renderiza una prenda individual
     */
    renderizarPrenda(prenda, idx) {
        // Renderizar variantes
        const variantesHTML = this.renderizarVariantes(prenda);
        
        // Renderizar especificaciones principales
        const especificacionesHTML = this.renderizarEspecificaciones(prenda);
        
        // Renderizar información de tela
        const telaHTML = this.renderizarTela(prenda);
        
        // Renderizar tallas por género
        const generosTallasHTML = this.renderizarTallas(prenda);
        
        // Renderizar procesos
        const procesosListaHTML = this.renderizarProcesos(prenda);
        
        return `
            <div style="background: white; border: 1px solid #ddd; border-radius: 3px; padding: 8px; margin-bottom: 8px; page-break-inside: avoid; font-size: 11px;">
                <!-- Encabezado -->
                <div style="background: #f0f0f0; padding: 6px 8px; margin: -8px -8px 8px -8px; border-radius: 3px 3px 0 0; border-bottom: 2px solid #2c3e50;">
                    <span style="font-weight: 700; color: #2c3e50; font-size: 11px;"> PRENDA ${idx + 1}</span>
                </div>
                
                <!-- Layout 3 columnas -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                    <!-- Columna 1: Imagen + Nombre -->
                    <div style="display: flex; gap: 8px; align-items: flex-start;">
                        <div style="flex-shrink: 0;">
                            ${(prenda.imagenes && prenda.imagenes.length > 0) ? `
                                <img src="${window._extraerURLImagen(prenda.imagenes[0])}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 3px; border: 1px solid #ddd; cursor: pointer;" onclick="window._abrirGaleriaImagenesDesdeID(${window._registrarGalería(prenda.imagenes, 'Imágenes de Prenda')})" title="Click para ver todas las imágenes">
                            ` : `
                                <div style="width: 80px; height: 80px; background: #f0f0f0; border-radius: 3px; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; font-size: 32px;"></div>
                            `}
                        </div>
                        <div style="flex: 1; font-size: 11px;">
                            <div style="font-weight: 700; color: #2c3e50; margin-bottom: 3px; line-height: 1.3;">${prenda.nombre}${prenda.de_bodega ? ' <span style="color: #ea580c; font-weight: bold;">- SE SACA DE BODEGA</span>' : ''}</div>
                            ${prenda.descripcion ? `<div style="color: #666; font-size: 11px; line-height: 1.3;">${prenda.descripcion}</div>` : ''}
                        </div>
                    </div>
                    
                    <!-- Columna 2: Tela, Color, Ref + Tallas -->
                    <div style="font-size: 11px;">
                        <div style="display: grid; grid-template-columns: auto 1fr; gap: 12px;">
                            <div>${telaHTML}</div>
                            <div>${generosTallasHTML}</div>
                        </div>
                    </div>
                    
                    <!-- Columna 3: Variantes -->
                    <div style="font-size: 11px;">
                        <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px; font-size: 11px;"> Variaciones</div>
                        ${this.renderizarVariacionesColumna(prenda)}
                    </div>
                </div>
                
                <!-- Variantes detalladas -->
                ${variantesHTML}
                
                <!-- Procesos -->
                ${procesosListaHTML ? `
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee;">
                        <div style="font-size: 11px; font-weight: 700; color: #2c3e50; margin-bottom: 4px;"> Procesos ${prenda.procesos && Array.isArray(prenda.procesos) ? `(${prenda.procesos.length})` : ''}</div>
                        ${procesosListaHTML}
                    </div>
                ` : ''}
            </div>
        `;
    }

    renderizarVariantes(prenda) {
        // Sección de variantes eliminada - ya no se muestra en la factura
        return '';
    }

    renderizarEspecificaciones(prenda) {
        return '';
    }

    renderizarTela(prenda) {
        if (prenda.telas_array && Array.isArray(prenda.telas_array) && prenda.telas_array.length > 0) {
            return prenda.telas_array.map(tela => `
                <div style="margin-bottom: 8px; line-height: 1.4;">
                    ${tela.tela_nombre ? `<div><strong>Tela:</strong> ${tela.tela_nombre}</div>` : ''}
                    ${tela.color_nombre ? `<div><strong>Color:</strong> ${tela.color_nombre}</div>` : ''}
                    ${tela.referencia ? `<div><strong>Ref:</strong> ${tela.referencia}</div>` : ''}
                    ${(tela.fotos && tela.fotos.length > 0) ? `<img src="${window._extraerURLImagen(tela.fotos[0])}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 2px; border: 1px solid #ddd; cursor: pointer; margin-top: 4px;" onclick="window._abrirGaleriaImagenesDesdeID(${window._registrarGalería(tela.fotos, 'Imágenes de ' + (tela.tela_nombre || 'Tela'))})" title="Click para ver todas las imágenes de tela">` : ''}
                </div>
            `).join('');
        } else {
            return `
                ${prenda.tela ? `<div><strong>Tela:</strong> ${prenda.tela}</div>` : ''}
                ${prenda.color ? `<div><strong>Color:</strong> ${prenda.color}</div>` : ''}
                ${prenda.ref ? `<div><strong>Ref:</strong> ${prenda.ref}</div>` : ''}
                ${(prenda.imagenes_tela && prenda.imagenes_tela.length > 0) ? `
                    <div>
                        <img src="${window._extraerURLImagen(prenda.imagenes_tela[0])}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 2px; border: 1px solid #ddd; cursor: pointer;" onclick="window._abrirGaleriaImagenesDesdeID(${window._registrarGalería(prenda.imagenes_tela, 'Imágenes de Tela')})" title="Click para ver todas las imágenes de tela">
                    </div>
                ` : ''}
            `;
        }
    }

    renderizarTallas(prenda) {
        if (prenda.tallas && typeof prenda.tallas === 'object' && Object.keys(prenda.tallas).length > 0) {
            const generosConTallas = Object.entries(prenda.tallas).filter(([gen, tallasObj]) => 
                typeof tallasObj === 'object' && !Array.isArray(tallasObj) && Object.keys(tallasObj).length > 0
            );
            
            if (generosConTallas.length > 0) {
                return `
                    <div style="font-weight: 700; color: #2c3e50; margin-bottom: 6px; font-size: 11px;"> Tallas</div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px; table-layout: fixed;">
                        <tbody>
                            ${generosConTallas.map(([genero, tallasObj]) => {
                                const tallaRows = Object.entries(tallasObj).map(([talla, cant]) => {
                                    let coloresConCantidad = [];
                                    
                                    // PRIMERO: Buscar en prenda.variantes (si viene del servidor)
                                    if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
                                        const varianteColor = prenda.variantes.find(v => v.talla === talla);
                                        if (varianteColor && varianteColor.colores_asignados && Array.isArray(varianteColor.colores_asignados) && varianteColor.colores_asignados.length > 0) {
                                            coloresConCantidad = varianteColor.colores_asignados.map(c => ({
                                                nombre: c.color_nombre || c.color || c.nombre || 'N/A',
                                                cantidad: c.cantidad || 1
                                            }));
                                        }
                                    }
                                    
                                    // SEGUNDO: Si no encontró en variantes, buscar en asignacionesColoresPorTalla
                                    if (coloresConCantidad.length === 0 && prenda.asignacionesColoresPorTalla && typeof prenda.asignacionesColoresPorTalla === 'object') {
                                        // MÉTODO 1: Buscar por objeto con genero y talla
                                        const clavePorObjeto = Object.keys(prenda.asignacionesColoresPorTalla).find(clave => {
                                            const asig = prenda.asignacionesColoresPorTalla[clave];
                                            return asig && asig.genero && asig.genero.toLowerCase() === genero.toLowerCase() && asig.talla === talla;
                                        });
                                        
                                        if (clavePorObjeto) {
                                            const asignacion = prenda.asignacionesColoresPorTalla[clavePorObjeto];
                                            if (asignacion && asignacion.colores && Array.isArray(asignacion.colores) && asignacion.colores.length > 0) {
                                                coloresConCantidad = asignacion.colores.map(c => ({
                                                    nombre: c.nombre || c.color || c.color_nombre || 'N/A',
                                                    cantidad: c.cantidad || 1
                                                }));
                                            }
                                        } else {
                                            // MÉTODO 2: Buscar por formato de clave "genero-...-talla"
                                            const clavePorFormato = Object.keys(prenda.asignacionesColoresPorTalla).find(clave => {
                                                const partes = clave.split('-');
                                                if (partes.length >= 2) {
                                                    return partes[0].toLowerCase() === genero.toLowerCase() && partes[partes.length - 1] === talla;
                                                }
                                                return false;
                                            });
                                            
                                            if (clavePorFormato) {
                                                const valor = prenda.asignacionesColoresPorTalla[clavePorFormato];
                                                let coloresArr = [];
                                                
                                                if (valor.colores && Array.isArray(valor.colores)) {
                                                    coloresArr = valor.colores;
                                                } else if (Array.isArray(valor)) {
                                                    coloresArr = valor;
                                                } else if (typeof valor === 'object') {
                                                    coloresArr = Object.values(valor).filter(v => v && typeof v === 'object');
                                                }
                                                
                                                if (coloresArr.length > 0) {
                                                    coloresConCantidad = coloresArr.map(c => ({
                                                        nombre: c.nombre || c.color || c.color_nombre || 'N/A',
                                                        cantidad: c.cantidad || 1
                                                    }));
                                                }
                                            }
                                        }
                                    }
                                    
                                    // Si hay colores, mostrar M:cantidad - Color: NOMBRE para cada uno
                                    if (coloresConCantidad.length > 0) {
                                        return coloresConCantidad.map(color => `<div style="margin-bottom: 2px;">${talla}:${color.cantidad} - <strong style="color: #0369a1;">Color:</strong> ${color.nombre}</div>`).join('');
                                    } else {
                                        // Si no hay colores, mostrar solo talla:cantidad
                                        return `<div style="margin-bottom: 2px;">${talla}:${cant}</div>`;
                                    }
                                }).join('');
                                
                                return `
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 4px 4px; font-weight: 600; color: #374151; width: 35%; word-break: break-word; font-size: 11px; overflow: hidden;">${genero}</td>
                                        <td style="padding: 4px 4px; color: #374151; word-break: break-word; overflow: hidden; font-size: 11px; font-weight: 600;">${tallaRows}</td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                `;
            }
        }
        
        return '<span style="color: #999; font-size: 11px;">Sin tallas</span>';
    }

    renderizarVariacionesColumna(prenda) {
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            const firstVar = prenda.variantes[0];
            const specs = [];
            
            if (firstVar.manga) {
                specs.push(`<div><strong>Manga:</strong> ${firstVar.manga}${firstVar.manga_obs ? ` <span style="color: #64748b; font-style: italic; font-size: 10px;">(${firstVar.manga_obs})</span>` : ''}</div>`);
            }
            if (firstVar.broche) {
                specs.push(`<div><strong>${firstVar.broche}:</strong> Sí${firstVar.broche_obs ? ` <span style="color: #64748b; font-style: italic; font-size: 10px;">(${firstVar.broche_obs})</span>` : ''}</div>`);
            }
            if (firstVar.bolsillos) {
                specs.push(`<div><strong>Bolsillo:</strong> Sí${firstVar.bolsillos_obs ? ` <span style="color: #64748b; font-style: italic; font-size: 10px;">(${firstVar.bolsillos_obs})</span>` : ''}</div>`);
            }
            
            return specs.length > 0 ? specs.join('') : '<span style="color: #999; font-size: 11px;">Sin especificaciones</span>';
        } else if (prenda.manga || prenda.broche || prenda.tiene_bolsillos) {
            const specs = [];
            
            if (prenda.manga) {
                specs.push(`<div><strong>Manga:</strong> ${prenda.manga}${prenda.obs_manga ? ` <span style="color: #64748b; font-style: italic; font-size: 10px;">(${prenda.obs_manga})</span>` : ''}</div>`);
            }
            if (prenda.broche) {
                specs.push(`<div><strong>${prenda.broche}:</strong> Sí${prenda.obs_broche ? ` <span style="color: #64748b; font-style: italic; font-size: 10px;">(${prenda.obs_broche})</span>` : ''}</div>`);
            }
            if (prenda.tiene_bolsillos) {
                specs.push(`<div><strong>Bolsillo:</strong> Sí${prenda.obs_bolsillos ? ` <span style="color: #64748b; font-style: italic; font-size: 10px;">(${prenda.obs_bolsillos})</span>` : ''}</div>`);
            }
            
            return specs.join('');
        } else {
            return '<span style="color: #999; font-size: 11px;">Sin variantes</span>';
        }
    }

    renderizarProcesos(prenda) {
        if (prenda.procesos && Array.isArray(prenda.procesos) && prenda.procesos.length > 0) {
            return prenda.procesos.map(proc => `
                <div style="background: #f9f9f9; padding: 6px; margin: 4px 0; border-left: 3px solid #9ca3af; border-radius: 2px; font-size: 11px;">
                    <div style="font-weight: 700; color: #3b82f6; margin-bottom: 4px; text-transform: uppercase;">Proceso: ${proc.tipo || proc.nombre || `(ID: ${proc.tipo_proceso_id})`}</div>
                    
                    ${(proc.ubicaciones?.length > 0 || proc.observaciones) ? `
                        <table style="width: 100%; font-size: 11px; margin-bottom: 4px; border-collapse: collapse;">
                            ${proc.ubicaciones && proc.ubicaciones.length > 0 ? `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 2px 3px; font-weight: 600; color: #6b7280; width: 25%;">Ubicación:</td>
                                    <td style="padding: 2px 3px;">${Array.isArray(proc.ubicaciones) ? proc.ubicaciones.join(', ') : (typeof proc.ubicaciones === 'string' ? proc.ubicaciones.replace(/[\[\]"]/g, '') : proc.ubicaciones)}</td>
                                </tr>
                            ` : ''}
                            ${proc.observaciones ? `
                                <tr>
                                    <td style="padding: 2px 3px; font-weight: 600; color: #6b7280; width: 25%;">Observaciones:</td>
                                    <td style="padding: 2px 3px; font-size: 11px;">${proc.observaciones}</td>
                                </tr>
                            ` : ''}
                        </table>
                    ` : ''}
                    
                    ${proc.imagenes && proc.imagenes.length > 0 ? `
                        <div style="margin-top: 4px; padding-top: 4px; border-top: 1px solid #eee; display: flex; gap: 4px; position: relative;">
                            <div style="position: relative; cursor: pointer;" onclick="window._abrirGaleriaImagenes(${JSON.stringify(proc.imagenes).replace(/"/g, '&quot;')}, 'Imágenes de ${proc.tipo || 'Proceso'}')">
                                <img src="${window._extraerURLImagen(proc.imagenes[0])}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 2px; border: 1px solid #ddd;">
                                ${proc.imagenes.length > 1 ? `
                                    <div style="position: absolute; top: 0; right: 0; background: #3b82f6; color: white; font-size: 11px; font-weight: 700; padding: 2px 4px; border-radius: 0 2px 0 2px; cursor: pointer;">
                                        ${proc.imagenes.length}+
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    ` : ''}
                </div>
            `).join('');
        }
        
        return '<div style="color: #999; font-size: 11px; font-style: italic;">Sin procesos asociados</div>';
    }

    renderizarEPP(epps) {
        if (epps && epps.length > 0) {
            return `
                <div style="margin-top: 12px; padding-top: 12px; border-top: 2px solid #6b7280;">
                    <div style="font-weight: 700; color: #374151; font-size: 11px; margin-bottom: 8px;">
                        EQUIPO DE PROTECCIÓN PERSONAL (${epps.length})
                    </div>
                    ${epps.map((epp, idx) => {
                        // Estandarizar: crear propiedad 'imagen' si no existe pero hay 'imagenes'
                        if (!epp.imagen && epp.imagenes && Array.isArray(epp.imagenes) && epp.imagenes.length > 0) {
                            epp.imagen = epp.imagenes[0];
                        }
                        return `
                        <div style="background: white; border: 1px solid #d1d5db; border-left: 4px solid #6b7280; padding: 8px; border-radius: 4px; margin-bottom: 8px; page-break-inside: avoid;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; align-items: start;">
                                <div style="font-size: 11px;">
                                    <div style="font-weight: 700; color: #374151; margin-bottom: 4px;">${epp.nombre_completo || epp.nombre || ''}</div>
                                    ${epp.talla ? `<div style="color: #6b7280; font-size: 11px; margin-bottom: 2px;"><strong>Talla:</strong> ${epp.talla}</div>` : ''}
                                </div>
                                <div style="font-size: 11px; text-align: right;">
                                    <div style="color: #6b7280; font-size: 11px; text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">Cantidad</div>
                                    <div style="font-weight: 600; color: #374151; font-size: 11px;"><strong>${epp.cantidad || 0}</strong></div>
                                </div>
                            </div>
                            ${epp.observaciones ? `
                                <div style="margin-top: 6px; padding-top: 6px; border-top: 1px solid #e5e7eb;">
                                    <div style="color: #6b7280; font-size: 11px; text-transform: uppercase; margin-bottom: 2px; font-weight: 600;">Observaciones</div>
                                    <div style="color: #555; font-size: 11px; font-style: italic;">${epp.observaciones}</div>
                                </div>
                            ` : ''}
                        </div>
                    `;
                    }).join('')}
                </div>
            `;
        }
        return '';
    }
}

// Inicializar el renderizador cuando se cargue el script
document.addEventListener('DOMContentLoaded', () => {
    window.invoiceRenderer = new InvoiceRenderer();
});

// También permitir inicialización manual
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.invoiceRenderer = new InvoiceRenderer();
    });
} else {
    window.invoiceRenderer = new InvoiceRenderer();
}
