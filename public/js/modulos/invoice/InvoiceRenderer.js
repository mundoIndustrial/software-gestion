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
                        <div style="font-size: 13px;">
                            <div style="font-weight: 700; color: #1a3a52; font-size: 13px; margin-bottom: 2px;">${datos.cliente}</div>
                            <div style="color: #666; font-size: 13px;">Asesor: ${datos.asesora}</div>
                            <div style="color: #666; font-size: 13px; margin-top: 3px;">Forma de Pago: <span style="font-weight: 600; color: #1a3a52;">${datos.forma_de_pago || 'No especificada'}</span></div>
                            ${datos.observaciones ? `
                                <div style="color: #666; font-size: 13px; margin-top: 3px;">
                                    <strong>Observaciones:</strong> ${datos.observaciones}
                                </div>
                            ` : ''}
                        </div>
                        <div style="text-align: right; font-size: 13px;">
                            <div style="font-weight: 700; color: #1a3a52; font-size: 13px; margin-bottom: 2px;">
                                RECIBO DE PEDIDO #${datos.numero_pedido || datos.numero_pedido_temporal}
                            </div>
                            <div style="color: #666; font-size: 13px;">${datos.fecha_creacion}</div>
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
                        <div style="font-size: 11px; font-weight: 700; color: #2c3e50; margin-bottom: 4px; text-align: center;"> Procesos ${prenda.procesos && Array.isArray(prenda.procesos) ? `(${prenda.procesos.length})` : ''}</div>
                        <div style="text-align: left;">${procesosListaHTML}</div>
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
        // 🔴 NUEVO: Verificar si hay colores asignados por talla (múltiples fuentes)
        const hayColorPorTalla = (
            (prenda.talla_colores && Array.isArray(prenda.talla_colores) && prenda.talla_colores.length > 0) ||
            (prenda.asignaciones && Array.isArray(prenda.asignaciones) && prenda.asignaciones.length > 0) ||
            (prenda.asignacionesColoresPorTalla && Object.keys(prenda.asignacionesColoresPorTalla).length > 0)
        );
        
        // Debug logging para diagnóstico
        console.log('[InvoiceRenderer] renderizarTela - Datos:', {
            prendas_id: prenda.id,
            telas_array: prenda.telas_array,
            imagenes_tela: prenda.imagenes_tela,
            talla_colores: prenda.talla_colores,
            asignaciones: prenda.asignaciones,
            asignacionesColoresPorTalla: prenda.asignacionesColoresPorTalla,
            hayColorPorTalla
        });
        
        if (prenda.telas_array && Array.isArray(prenda.telas_array) && prenda.telas_array.length > 0) {
            return prenda.telas_array.map(tela => {
                // Debug logging para cada tela
                if (tela.fotos && tela.fotos.length > 0) {
                    console.log('[InvoiceRenderer] Foto de tela encontrada:', {
                        tela_nombre: tela.tela_nombre,
                        fotos_count: tela.fotos.length,
                        primera_foto: tela.fotos[0],
                        url_extraida: window._extraerURLImagen(tela.fotos[0])
                    });
                }
                
                return `
                <div style="margin-bottom: 8px; line-height: 1.4;">
                    ${tela.tela_nombre ? `<div><strong>Tela:</strong> ${tela.tela_nombre}</div>` : ''}
                    ${tela.color_nombre && !hayColorPorTalla ? `<div><strong>Color:</strong> ${tela.color_nombre}</div>` : ''}
                    ${tela.referencia ? `<div><strong>Ref:</strong> ${tela.referencia}</div>` : ''}
                    ${(tela.fotos && tela.fotos.length > 0) ? `<img src="${window._extraerURLImagen(tela.fotos[0])}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 2px; border: 1px solid #ddd; cursor: pointer; margin-top: 4px;" onclick="window._abrirGaleriaImagenesDesdeID(${window._registrarGalería(tela.fotos, 'Imágenes de ' + (tela.tela_nombre || 'Tela'))})" title="Click para ver todas las imágenes de tela">` : ''}
                </div>
            `;
            }).join('');
        } else {
            // Debug logging para fallback
            if (prenda.imagenes_tela && prenda.imagenes_tela.length > 0) {
                console.log('[InvoiceRenderer] Usando imagenes_tela fallback:', {
                    imagenes_count: prenda.imagenes_tela.length,
                    primera_imagen: prenda.imagenes_tela[0],
                    url_extraida: window._extraerURLImagen(prenda.imagenes_tela[0])
                });
            }
            
            return `
                ${prenda.tela ? `<div><strong>Tela:</strong> ${prenda.tela}</div>` : ''}
                ${prenda.color && !hayColorPorTalla ? `<div><strong>Color:</strong> ${prenda.color}</div>` : ''}
                ${prenda.ref ? `<div><strong>Ref:</strong> ${prenda.ref}</div>` : ''}
                ${(prenda.imagenes_tela && prenda.imagenes_tela.length > 0) ? `
                    <div>
                        <img src="${window._extraerURLImagen(prenda.imagenes_tela[0])}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 2px; border: 1px solid #ddd; cursor: pointer; margin-top: 4px;" title="Imagen de tela">
                    </div>
                ` : ''}
            `;
        }
    }

    renderizarTallas(prenda) {
        console.log('[InvoiceRenderer] renderizarTallas - Prenda completa:', prenda);
        console.log('[InvoiceRenderer] renderizarTallas - Variantes:', prenda.variantes);
        console.log('[InvoiceRenderer] DEBUG CANTIDAD_TALLA:', prenda.cantidad_talla);
        console.log('[InvoiceRenderer] DEBUG TALLAS:', prenda.tallas);
        
        // 🔴 NUEVO: Primero intentar con cantidad_talla (estructura del editor)
        if (prenda.cantidad_talla && prenda.cantidad_talla.GENERICO) {
            console.log('[InvoiceRenderer] ✅ DETECTADO SOLO CANTIDAD en cantidad_talla');
            console.log('[InvoiceRenderer] cantidad_talla.GENERICO:', prenda.cantidad_talla.GENERICO);
            
            let cantidad = 0;
            const generericoObj = prenda.cantidad_talla.GENERICO;
            
            if (typeof generericoObj === 'object') {
                const valores = Object.values(generericoObj);
                console.log('[InvoiceRenderer] Valores en GENERICO:', valores);
                
                if (valores.length > 0) {
                    const primerValor = valores[0];
                    console.log('[InvoiceRenderer] Primer valor:', primerValor, 'Tipo:', typeof primerValor);
                    
                    // Si es un número, usarlo directamente
                    if (typeof primerValor === 'number') {
                        cantidad = primerValor;
                    } 
                    // Si es un array, tomar el primer elemento y extraer cantidad
                    else if (Array.isArray(primerValor) && primerValor.length > 0) {
                        const primerElemento = primerValor[0];
                        if (primerElemento && typeof primerElemento.cantidad === 'number') {
                            cantidad = primerElemento.cantidad;
                        }
                    }
                    // Si es un objeto, buscar cantidad dentro
                    else if (typeof primerValor === 'object' && primerValor !== null && !Array.isArray(primerValor)) {
                        if (typeof primerValor.cantidad === 'number') {
                            cantidad = primerValor.cantidad;
                        }
                    }
                }
            }
            
            console.log('[InvoiceRenderer] CANTIDAD FINAL EXTRAIDA:', cantidad);
            
            return `
                <div style="font-weight: 700; color: #2c3e50; margin-bottom: 6px; font-size: 11px;"> Cantidad</div>
                <div style="font-weight: 600; color: #0369a1; font-size: 12px; background: #f0f9ff; padding: 4px 8px; border-radius: 3px; border-left: 3px solid #0ea5e9; display: inline-block;">
                    ${cantidad}
                </div>
            `;
        }
        
        if (prenda.tallas && typeof prenda.tallas === 'object' && Object.keys(prenda.tallas).length > 0) {
            const generosConTallas = Object.entries(prenda.tallas).filter(([gen, tallasObj]) => 
                typeof tallasObj === 'object' && !Array.isArray(tallasObj) && Object.keys(tallasObj).length > 0
            );
            
            // 🔴 NUEVO: Detectar si SOLO hay GENERICO (SOLO CANTIDAD)
            const tieneGenerico = generosConTallas.some(([gen]) => gen.toUpperCase() === 'GENERICO');
            const soloGenerico = tieneGenerico && generosConTallas.length === 1;
            
            // Si SOLO hay GENERICO, mostrar "Cantidad: X" de forma simple
            if (soloGenerico) {
                const [genero, tallasObj] = generosConTallas[0];
                
                // 🔴 FIX: Extraer cantidad de forma robusta
                let cantidad = 0;
                const valores = Object.values(tallasObj);
                
                if (valores.length > 0) {
                    const primerValor = valores[0];
                    
                    // Si es un número, usarlo directamente
                    if (typeof primerValor === 'number') {
                        cantidad = primerValor;
                    } 
                    // Si es un array, tomar el primer elemento y extraer cantidad
                    else if (Array.isArray(primerValor) && primerValor.length > 0) {
                        const primerElemento = primerValor[0];
                        if (primerElemento && typeof primerElemento.cantidad === 'number') {
                            cantidad = primerElemento.cantidad;
                        }
                    }
                    // Si es un objeto con cantidad
                    else if (typeof primerValor === 'object' && primerValor !== null && !Array.isArray(primerValor)) {
                        if (typeof primerValor.cantidad === 'number') {
                            cantidad = primerValor.cantidad;
                        } else {
                            // Fallback: buscar valor numérico dentro
                            const valoresNumericos = Object.values(primerValor).filter(v => typeof v === 'number');
                            if (valoresNumericos.length > 0) {
                                cantidad = valoresNumericos[0];
                            }
                        }
                    }
                }
                
                return `
                    <div style="font-weight: 700; color: #2c3e50; margin-bottom: 6px; font-size: 11px;"> Cantidad</div>
                    <div style="font-weight: 600; color: #0369a1; font-size: 12px; background: #f0f9ff; padding: 4px 8px; border-radius: 3px; border-left: 3px solid #0ea5e9; display: inline-block;">
                        ${cantidad}
                    </div>
                `;
            }
            
            // Filtrar GENERICO del mapeo normal (si hay más géneros además de GENERICO)
            const generosParaMostrar = generosConTallas.filter(([gen]) => {
                const generoUpper = String(gen || '').toUpperCase().trim();
                return generoUpper !== 'GENERICO';
            });
            
            if (generosParaMostrar.length > 0) {
                // 🔴 REFUERZO: Filtrar GENERICO nuevamente como precaución extra
                const generosFiltrados = generosParaMostrar.filter(([gen]) => {
                    return gen && String(gen).toUpperCase().trim() !== 'GENERICO';
                });
                
                if (generosFiltrados.length === 0) {
                    return ''; // Si solo quedan GENERICOs, no mostrar tabla
                }
                
                return `
                    <div style="font-weight: 700; color: #2c3e50; margin-bottom: 6px; font-size: 11px;"> Tallas</div>
                    <div style="display: flex; flex-direction: column; gap: 10px; text-align: left;">
                        ${generosFiltrados.map(([genero, tallasObj]) => {
                            // Recopilar todos los colores con sus tallas
                            const porColor = {};
                            let hayColores = false;
                            
                            Object.entries(tallasObj).forEach(([talla, cant]) => {
                                let coloresConCantidad = [];
                                
                                // Detectar sobremedida
                                let tallaFinal = talla;
                                if (!talla || talla.trim() === '') {
                                    if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
                                        const varianteConTallaId = prenda.variantes.find(v => v.talla_id);
                                        if (varianteConTallaId) {
                                            tallaFinal = 'SOBREMEDIDA';
                                        }
                                    }
                                    if (tallaFinal === talla && (!talla || talla.trim() === '')) {
                                        tallaFinal = 'SOBREMEDIDA';
                                    }
                                }
                                
                                // Buscar colores en talla_colores
                                if (prenda.talla_colores && Array.isArray(prenda.talla_colores) && prenda.talla_colores.length > 0) {
                                    const coloresEnTalla = prenda.talla_colores.filter(tc => 
                                        tc.genero && tc.genero.toUpperCase() === genero.toUpperCase() && 
                                        tc.talla === talla
                                    );
                                    if (coloresEnTalla.length > 0) {
                                        coloresConCantidad = coloresEnTalla.map(c => ({
                                            nombre: c.color_nombre || c.color || 'Sin color',
                                            cantidad: c.cantidad || 1,
                                            imagen_ruta: c.imagen_ruta || null
                                        }));
                                    }
                                }
                                
                                // Buscar en asignaciones
                                if (coloresConCantidad.length === 0 && prenda.asignaciones && Array.isArray(prenda.asignaciones) && prenda.asignaciones.length > 0) {
                                    const coloresEnTalla = prenda.asignaciones.filter(a => 
                                        a.genero && a.genero.toUpperCase() === genero.toUpperCase() && 
                                        a.talla === talla
                                    );
                                    if (coloresEnTalla.length > 0) {
                                        coloresConCantidad = coloresEnTalla.map(c => ({
                                            nombre: c.color || c.color_nombre || 'Sin color',
                                            cantidad: c.cantidad || 1,
                                            imagen_ruta: c.imagen_ruta || null
                                        }));
                                    }
                                }
                                
                                // Buscar en asignacionesColoresPorTalla (formato key)
                                if (coloresConCantidad.length === 0 && prenda.asignacionesColoresPorTalla && Object.keys(prenda.asignacionesColoresPorTalla).length > 0) {
                                    const key = `${genero.toUpperCase()}-${prenda.telas_array?.[0]?.tela_nombre || 'DRILL'}-${talla}`;
                                    const asignacion = prenda.asignacionesColoresPorTalla[key];
                                    if (asignacion && asignacion.colores && Array.isArray(asignacion.colores)) {
                                        coloresConCantidad = asignacion.colores.map(c => ({
                                            nombre: c.nombre || c.color_nombre || 'Sin color',
                                            cantidad: c.cantidad || 1,
                                            imagen_ruta: c.imagen_ruta || null
                                        }));
                                    }
                                }
                                
                                // Buscar en variantes
                                if (coloresConCantidad.length === 0 && prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
                                    const varianteColor = prenda.variantes.find(v => v.talla === talla);
                                    if (varianteColor && varianteColor.colores_asignados && Array.isArray(varianteColor.colores_asignados) && varianteColor.colores_asignados.length > 0) {
                                        coloresConCantidad = varianteColor.colores_asignados.map(c => ({
                                            nombre: c.color_nombre || c.color || c.nombre || 'N/A',
                                            cantidad: c.cantidad || 1,
                                            imagen_ruta: c.imagen_ruta || null
                                        }));
                                    }
                                }
                                
                                // Buscar en asignacionesColoresPorTalla (formato objeto)
                                if (coloresConCantidad.length === 0 && prenda.asignacionesColoresPorTalla && typeof prenda.asignacionesColoresPorTalla === 'object') {
                                    const clavePorObjeto = Object.keys(prenda.asignacionesColoresPorTalla).find(clave => {
                                        const asig = prenda.asignacionesColoresPorTalla[clave];
                                        return asig && asig.genero && asig.genero.toLowerCase() === genero.toLowerCase() && asig.talla === talla;
                                    });
                                    if (clavePorObjeto) {
                                        const asignacion = prenda.asignacionesColoresPorTalla[clavePorObjeto];
                                        if (asignacion && asignacion.colores && Array.isArray(asignacion.colores) && asignacion.colores.length > 0) {
                                            coloresConCantidad = asignacion.colores.map(c => ({
                                                nombre: c.nombre || c.color || c.color_nombre || 'N/A',
                                                cantidad: c.cantidad || 1,
                                                imagen_ruta: c.imagen_ruta || null
                                            }));
                                        }
                                    } else {
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
                                                    cantidad: c.cantidad || 1,
                                                    imagen_ruta: c.imagen_ruta || null
                                                }));
                                            }
                                        }
                                    }
                                }
                                
                                // Agrupar por color
                                if (coloresConCantidad.length > 0) {
                                    coloresConCantidad.forEach(color => {
                                        const esColorValido = color.nombre && color.nombre.toLowerCase() !== 'sin color' && color.nombre.trim() !== '';
                                        if (esColorValido) {
                                            hayColores = true;
                                            const nombreColor = color.nombre.toUpperCase();
                                            if (!porColor[nombreColor]) porColor[nombreColor] = [];
                                            porColor[nombreColor].push({ talla: tallaFinal, cantidad: color.cantidad, imagen_ruta: color.imagen_ruta || null });
                                        } else {
                                            if (!porColor['__SIN_COLOR__']) porColor['__SIN_COLOR__'] = [];
                                            porColor['__SIN_COLOR__'].push({ talla: tallaFinal, cantidad: color.cantidad });
                                        }
                                    });
                                } else {
                                    // Sin color, agrupar bajo "SIN COLOR"
                                    let cantidadFinal = cant;
                                    if (typeof cant === 'number') {
                                        cantidadFinal = cant;
                                    } else if (Array.isArray(cant) && cant.length > 0) {
                                        const p = cant[0];
                                        cantidadFinal = (p && typeof p.cantidad === 'number') ? p.cantidad : (typeof p === 'number' ? p : 0);
                                    } else if (typeof cant === 'object' && cant !== null) {
                                        cantidadFinal = typeof cant.cantidad === 'number' ? cant.cantidad : 
                                            Object.values(cant).reduce((s, v) => s + (typeof v === 'number' ? v : 0), 0);
                                    }
                                    if (!porColor['__SIN_COLOR__']) porColor['__SIN_COLOR__'] = [];
                                    porColor['__SIN_COLOR__'].push({ talla: tallaFinal, cantidad: cantidadFinal });
                                }
                            });
                            
                            // Renderizar agrupado por color
                            let tallaRows = '';
                            const coloresReales = Object.entries(porColor).filter(([c]) => c !== '__SIN_COLOR__');
                            const sinColorArr = porColor['__SIN_COLOR__'] || [];
                            
                            if (coloresReales.length > 0) {
                                tallaRows = coloresReales.map(([color, tallasArr]) => {
                                        tallasArr.sort((a, b) => {
                                            const nA = parseInt(a.talla), nB = parseInt(b.talla);
                                            if (!isNaN(nA) && !isNaN(nB)) return nA - nB;
                                            return a.talla.localeCompare(b.talla);
                                        });
                                        const tallasStr = tallasArr.map(t => `${t.talla}-${t.cantidad}`).join(', ');
                                        // Buscar imagen_ruta del color (primera no nula)
                                        const imgRutaRaw = tallasArr.find(t => t.imagen_ruta)?.imagen_ruta || null;
                                        // Normalizar ruta: Si no comienza con /storage/, agregarlo
                                        let imgRuta = imgRutaRaw;
                                        if (imgRuta && !imgRuta.startsWith('/storage/')) {
                                            imgRuta = '/storage/' + (imgRuta.startsWith('/') ? imgRuta.slice(1) : imgRuta);
                                        }
                                        const imgHtml = imgRuta 
                                            ? `<div style="margin: 3px 0 2px 0;"><img src="${imgRuta}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 3px; border: 1px solid #ddd;" onerror="this.style.display='none'"></div>` 
                                            : '';
                                        return `<div style="margin: 2px 0;"><strong style="color: #0369a1;">${color}:</strong> ${tallasStr}${imgHtml}</div>`;
                                    }).join('');
                            } else if (sinColorArr.length > 0) {
                                sinColorArr.sort((a, b) => {
                                    const nA = parseInt(a.talla), nB = parseInt(b.talla);
                                    if (!isNaN(nA) && !isNaN(nB)) return nA - nB;
                                    return a.talla.localeCompare(b.talla);
                                });
                                tallaRows = sinColorArr.map(t => 
                                    `<div style="margin: 2px 0;">${t.talla}:${t.cantidad}</div>`
                                ).join('');
                            }
                            
                            return `
                                <div style="text-align: left;">
                                    <div style="font-weight: 800; color: #111827; font-size: 11px; margin-bottom: 4px;">${String(genero).toUpperCase()}</div>
                                    <div style="padding-left: 0; font-size: 11px; font-weight: 600; color: #374151;">${tallaRows}</div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            } else {
                // Si solo hay GENERICO (soloGenerico) o no hay géneros para mostrar
                return '';
            }
        }
        
        return '<span style="color: #999; font-size: 11px;">Sin tallas</span>';
    }

    renderizarVariacionesColumna(prenda) {
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            const firstVar = prenda.variantes[0];
            const specs = [];
            
            // 🔑 CRÍTICO: Aceptar AMBOS formatos
            // Formato 1: Desde BD (tipo_manga, tipo_broche_boton, tiene_bolsillos, manga_obs, broche_boton_obs, bolsillos_obs)
            // Formato 2: Desde otros lugares (manga, broche, bolsillos, manga_obs, broche_obs, bolsillos_obs)
            
            const manga = firstVar.tipo_manga || firstVar.manga;
            const mangaObs = firstVar.manga_obs;
            
            const broche = firstVar.tipo_broche_boton || firstVar.tipo_broche || firstVar.broche;
            const brocheObs = firstVar.broche_boton_obs || firstVar.broche_obs || firstVar.obs_broche;
            
            const tieneBolsillos = firstVar.tiene_bolsillos || firstVar.bolsillos;
            const bolsillosObs = firstVar.bolsillos_obs || firstVar.obs_bolsillos;
            
            if (manga) {
                specs.push(`<div><strong>Manga:</strong> ${manga}${mangaObs ? ` <span style="color: #64748b; font-style: italic; font-size: 10px;">(${mangaObs})</span>` : ''}</div>`);
            }
            if (broche) {
                // Usar el nombre real del tipo (Broche/Botón) en vez de hardcodear "Botón"
                const brocheLabel = typeof broche === 'string' ? broche : 'Broche/Botón';
                specs.push(`<div><strong>${brocheLabel}:</strong> Sí${brocheObs ? ` <span style="color: #64748b; font-style: italic; font-size: 10px;">(${brocheObs})</span>` : ''}</div>`);
            }
            if (tieneBolsillos) {
                specs.push(`<div><strong>Bolsillo:</strong> Sí${bolsillosObs ? ` <span style="color: #64748b; font-style: italic; font-size: 10px;">(${bolsillosObs})</span>` : ''}</div>`);
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
                <div style="background: #f9f9f9; padding: 6px; margin: 4px 0; border-left: 3px solid #9ca3af; border-radius: 2px; font-size: 11px; text-align: left;">
                    <div style="font-weight: 700; color: #3b82f6; margin-bottom: 4px; text-transform: uppercase; text-align: left;">Proceso: ${proc.tipo || proc.nombre || `(ID: ${proc.tipo_proceso_id})`}</div>
                    
                    ${(proc.ubicaciones?.length > 0 || proc.observaciones || proc.tallas) ? `
                        <table style="width: 100%; font-size: 11px; margin-bottom: 4px; border-collapse: collapse;">
                            ${proc.ubicaciones && proc.ubicaciones.length > 0 ? `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 2px 2px 2px 0; font-weight: 600; color: #6b7280; width: 18%; vertical-align: top; text-align: left;">Ubicación:</td>
                                    <td style="padding: 2px 0; vertical-align: top; text-align: left;">${Array.isArray(proc.ubicaciones) ? proc.ubicaciones.join(', ') : (typeof proc.ubicaciones === 'string' ? proc.ubicaciones.replace(/[\[\]"]/g, '') : proc.ubicaciones)}</td>
                                </tr>
                            ` : ''}
                            ${proc.observaciones ? `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 2px 2px 2px 0; font-weight: 600; color: #6b7280; width: 18%; vertical-align: top; text-align: left;">Observaciones:</td>
                                    <td style="padding: 2px 0; font-size: 11px; vertical-align: top; text-align: left;">${proc.observaciones}</td>
                                </tr>
                            ` : ''}
                            ${proc.tallas && typeof proc.tallas === 'object' && Object.keys(proc.tallas).length > 0 ? `
                                <tr>
                                    <td style="padding: 2px 2px 2px 0; font-weight: 600; color: #6b7280; width: 18%; vertical-align: top; text-align: left;">Tallas:</td>
                                    <td style="padding: 2px 0; font-size: 11px; vertical-align: top; text-align: left;">${this.renderizarTallasProceso(proc.tallas)}</td>
                                </tr>
                            ` : ''}
                        </table>
                    ` : ''}
                    
                    ${(proc.modo_tallas === 'general' || proc.modo_tallas === 'especifico') && proc.tallas_detalles && Object.keys(proc.tallas_detalles).length > 0 ? `
                        <div style="margin-top: 3px; padding-top: 3px; border-top: 1px solid #eee; font-weight: 600; color: #374151; font-size: 10px; margin-bottom: 2px;">Detalles por Talla:</div>
                        <table style="width: 100%; font-size: 9px; border-collapse: collapse; margin-bottom: 2px;">
                            <thead>
                                <tr style="background: #f3f4f6; border-bottom: 1px solid #d1d5db;">
                                    <th style="padding: 2px 4px; text-align: left; font-weight: 600; color: #374151; width: ${proc.modo_tallas === 'especifico' ? '20%' : '50%'};">Talla</th>
                                    ${proc.modo_tallas === 'especifico' ? `<th style="padding: 2px 4px; text-align: left; font-weight: 600; color: #374151; width: 30%;">Ubicación</th>` : ''}
                                    <th style="padding: 2px 4px; text-align: left; font-weight: 600; color: #374151; width: ${proc.modo_tallas === 'especifico' ? '50%' : '50%'};">Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${Object.entries(proc.tallas_detalles).map(([genero, tallas]) => {
                                    if (!tallas || typeof tallas !== 'object') return '';
                                    return Object.entries(tallas).map(([talla, datos]) => `
                                        <tr style="border-bottom: 1px solid #f0f0f0;">
                                            <td style="padding: 2px 4px; color: #1f2937; font-weight: 600;">${genero.toUpperCase()} ${talla}</td>
                                            ${proc.modo_tallas === 'especifico' ? `<td style="padding: 2px 4px; color: #666;">${datos.ubicaciones && (Array.isArray(datos.ubicaciones) ? datos.ubicaciones.join(', ') : (typeof datos.ubicaciones === 'string' ? JSON.parse(datos.ubicaciones).join(', ') : datos.ubicaciones)) || '—'}</td>` : ''}
                                            <td style="padding: 2px 4px; color: #666;">${datos.observaciones || '—'}</td>
                                        </tr>
                                    `).join('');
                                }).join('')}
                            </tbody>
                        </table>
                        ${(() => {
                            let todasImagenes = [];
                            Object.entries(proc.tallas_detalles).forEach(([genero, tallas]) => {
                                if (tallas && typeof tallas === 'object') {
                                    Object.entries(tallas).forEach(([talla, datos]) => {
                                        if (datos.imagenes && Array.isArray(datos.imagenes)) {
                                            todasImagenes = [...new Set([...todasImagenes, ...datos.imagenes])];
                                        }
                                    });
                                }
                            });
                            return todasImagenes.length > 0 ? `<div style="display: flex; gap: 3px; flex-wrap: wrap;">${todasImagenes.map(img => `<img src="${window._extraerURLImagen ? window._extraerURLImagen(img) : img}" style="width: 40px; height: 40px; border-radius: 2px; border: 1px solid #ddd; object-fit: cover;">`).join('')}</div>` : '';
                        })()}
                    ` : ''}
                    
                    ${proc.imagenes && proc.imagenes.length > 0 ? `
                        <div style="margin-top: 4px; padding-top: 4px; border-top: 1px solid #eee; display: flex; gap: 4px; position: relative;">
                            <div style="position: relative; cursor: pointer;" onclick="window._abrirGaleriaImagenes(${JSON.stringify(proc.imagenes).replace(/"/g, '&quot;')}, 'Imágenes de ${proc.tipo || 'Proceso'}')">
                                <img src="${window._extraerURLImagen(proc.imagenes[0])}" style="width: 68px; height: 68px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                ${proc.imagenes.length > 1 ? `
                                    <div style="position: absolute; top: 0; right: 0; background: #3b82f6; color: white; font-size: 11px; font-weight: 700; padding: 2px 4px; border-radius: 0 4px 0 4px; cursor: pointer;">
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

    renderizarTallasProceso(tallas) {
        if (!tallas || typeof tallas !== 'object') {
            return '<span style="color: #999; font-size: 10px;">Sin tallas</span>';
        }

        const generoToHTML = [];

        const up = (v) => String(v ?? '').trim().toUpperCase();

        const agregarKeyCantidad = (porColor, tallaKey, cantidad) => {
            const qty = parseInt(cantidad, 10) || 0;
            if (!tallaKey || qty <= 0) return;
            const raw = String(tallaKey);
            const parts = raw.split('__');
            const talla = up(parts[0]);
            const color = up(parts[1] || '') || '__SIN_COLOR__';
            if (!talla) return;

            if (!porColor[color]) porColor[color] = [];
            const existente = porColor[color].find(t => t.talla === talla);
            if (existente) {
                existente.cantidad += qty;
            } else {
                porColor[color].push({ talla, cantidad: qty });
            }
        };

        const agregarTallaConColoresArray = (porColor, talla, coloresArr) => {
            const tallaUp = up(talla);
            if (!tallaUp) return;

            (coloresArr || []).forEach(c => {
                const qty = parseInt(c?.cantidad, 10) || 0;
                if (qty <= 0) return;
                const color = up(c?.color) || '__SIN_COLOR__';
                if (!porColor[color]) porColor[color] = [];
                const existente = porColor[color].find(t => t.talla === tallaUp);
                if (existente) {
                    existente.cantidad += qty;
                } else {
                    porColor[color].push({ talla: tallaUp, cantidad: qty });
                }
            });
        };

        // Procesar cada género (excluyendo GENERICO)
        Object.entries(tallas).forEach(([genero, tallasObj]) => {
            if (genero && up(genero) === 'GENERICO') return;
            if (!tallasObj || typeof tallasObj !== 'object') return;

            const porColor = {};

            Object.entries(tallasObj).forEach(([tallaKey, valor]) => {
                if (Array.isArray(valor)) {
                    // Formato: { talla: [{color,cantidad}, ...] }
                    agregarTallaConColoresArray(porColor, tallaKey, valor);
                    return;
                }
                agregarKeyCantidad(porColor, tallaKey, valor);
            });

            const coloresOrdenados = Object.keys(porColor);
            if (coloresOrdenados.length === 0) return;

            const generoUpper = up(genero);
            const lineas = [];

            // Primero colores reales, luego sin color
            const reales = coloresOrdenados.filter(c => c !== '__SIN_COLOR__').sort();
            const sinColor = coloresOrdenados.includes('__SIN_COLOR__') ? ['__SIN_COLOR__'] : [];

            [...reales, ...sinColor].forEach(color => {
                const arr = porColor[color] || [];
                if (arr.length === 0) return;
                arr.sort((a, b) => a.talla.localeCompare(b.talla));
                const tallasStr = arr.map(t => `${t.talla}-${t.cantidad}`).join(', ');
                const labelColor = color === '__SIN_COLOR__' ? '' : `<span style="color: #d32f2f; font-weight: 700;">${color}:</span> `;
                lineas.push(`<div style="margin: 1px 0;">${labelColor}${tallasStr}</div>`);
            });

            generoToHTML.push(`
                <div style="margin-bottom: 4px;">
                    <div style="font-weight: 700; color: #111827;">${generoUpper}</div>
                    <div style="padding-left: 8px;">${lineas.join('')}</div>
                </div>
            `);
        });

        return generoToHTML.length > 0
            ? `<div style="text-align: left;">${generoToHTML.join('')}</div>`
            : '<span style="color: #999; font-size: 10px;">Sin tallas asignadas</span>';
    }

    renderizarEPP(epps) {
        console.log('[InvoiceRenderer] renderizarEPP - INICIO', {
            epps_existe: !!epps,
            epps_es_array: Array.isArray(epps),
            epps_length: epps ? epps.length : 0,
            epps_data: epps,
            timestamp: new Date().toISOString()
        });
        
        if (epps && epps.length > 0) {
            const resultado = `
                <div style="margin-top: 12px; padding-top: 12px; border-top: 2px solid #6b7280;">
                    <div style="font-weight: 700; color: #374151; font-size: 11px; margin-bottom: 8px;">
                        ⚡ EQUIPO DE PROTECCIÓN PERSONAL (${epps.length})
                    </div>
                    ${epps.map((epp, idx) => {
                        console.log(`[InvoiceRenderer] Procesando EPP ${idx}:`, {
                            nombre: epp.nombre_completo || epp.nombre,
                            cantidad: epp.cantidad,
                            imagenes_existe: !!epp.imagenes,
                            imagenes_es_array: Array.isArray(epp.imagenes),
                            imagenes_length: epp.imagenes ? epp.imagenes.length : 0,
                            imagenes: epp.imagenes
                        });
                        
                        // Estandarizar: crear propiedad 'imagen' si no existe pero hay 'imagenes'
                        if (!epp.imagen && epp.imagenes && Array.isArray(epp.imagenes) && epp.imagenes.length > 0) {
                            epp.imagen = epp.imagenes[0];
                        }
                        
                        // Generar HTML para las imágenes del EPP
                        const imagenesHTML = this.renderizarImagenesEPP(epp.imagenes || []);
                        
                        console.log(`[InvoiceRenderer] HTML generado para EPP ${idx}:`, {
                            imagenes_html_length: imagenesHTML.length,
                            tiene_imagenes: imagenesHTML.length > 0,
                            imagenes_html_preview: imagenesHTML.substring(0, 200)
                        });
                        
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
                            
                            <!-- Imágenes del EPP -->
                            ${imagenesHTML ? `
                                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e7eb;">
                                    <div style="color: #6b7280; font-size: 11px; text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">🖼️ Imágenes (${epp.imagenes ? epp.imagenes.length : 0})</div>
                                    ${imagenesHTML}
                                </div>
                            ` : ''}
                            
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
            
            console.log('[InvoiceRenderer] renderizarEPP - HTML FINAL GENERADO:', {
                length: resultado.length,
                preview: resultado.substring(0, 500),
                contiene_imagenes: resultado.includes('img src'),
                timestamp: new Date().toISOString()
            });
            
            return resultado;
        }
        
        console.log('[InvoiceRenderer] renderizarEPP - SIN EPPs');
        return '';
    }

    /**
     * Renderiza las imágenes de un EPP
     */
    renderizarImagenesEPP(imagenes) {
        if (!imagenes || imagenes.length === 0) {
            return '';
        }

        return `
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 6px;">
                ${imagenes.map((imagen, index) => {
                    let imgUrl = '';
                    let imgTitle = imagen.nombre || `Imagen ${index + 1}`;
                    
                    // Determinar la URL de la imagen según el formato
                    if (typeof imagen === 'string') {
                        imgUrl = imagen;
                    } else if (imagen.ruta_web) {
                        // URL del servidor (formato preferido) - asegurar que incluya /storage/
                        imgUrl = imagen.ruta_web.startsWith('/') ? imagen.ruta_web : `/storage/${imagen.ruta_web}`;
                        imgTitle = imagen.nombre || imgTitle;
                    } else if (imagen.url) {
                        imgUrl = imagen.url.startsWith('/') ? imagen.url : `/storage/${imagen.url}`;
                        imgTitle = imagen.nombre || imgTitle;
                    } else if (imagen.base64) {
                        // Base64 (fallback)
                        imgUrl = imagen.base64;
                        imgTitle = imagen.nombre || imgTitle;
                    } else if (imagen.previewUrl) {
                        // Preview URL temporal (fallback)
                        imgUrl = imagen.previewUrl;
                        imgTitle = imagen.nombre || imgTitle;
                    }
                    
                    // Asegurar que siempre incluya /storage/ para URLs relativas
                    if (imgUrl && !imgUrl.startsWith('http') && !imgUrl.startsWith('/') && !imgUrl.startsWith('data:')) {
                        imgUrl = '/storage/' + imgUrl.replace(/^\/+/, '');
                    }
                    
                    if (!imgUrl) {
                        return '';
                    }
                    
                    return `
                        <div style="position: relative; border-radius: 3px; overflow: hidden; background: #f9fafb; border: 1px solid #e5e7eb; aspect-ratio: 1; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" 
                             onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)'; this.querySelector('.hover-overlay').style.opacity='1';"
                             onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'; this.querySelector('.hover-overlay').style.opacity='0';"
                             onclick="window.abrirModalImagen('${imgUrl}', '${imgTitle.replace(/'/g, "\\'")}')"
                             title="Click para ver imagen completa">
                            <img src="${imgUrl}" 
                                 alt="${imgTitle}" 
                                 style="width: 100%; height: 100%; object-fit: cover; display: block;"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                 title="${imgTitle}">
                            <div style="display: none; align-items: center; justify-content: center; width: 100%; height: 100%; background: #f3f4f6; color: #6b7280; font-size: 10px; text-align: center; padding: 4px;">
                                Sin imagen
                            </div>
                            <!-- Overlay de hover -->
                            <div class="hover-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; pointer-events: none;">
                                <div style="color: white; font-size: 18px; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">🔍</div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    }

    /**
     * Abre un modal para mostrar una imagen a tamaño completo
     */
    abrirModalImagen(imgUrl, imgTitle) {
        // Crear modal si no existe
        let modal = document.getElementById('modal-imagen-epp');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'modal-imagen-epp';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                cursor: pointer;
                padding: 20px;
                box-sizing: border-box;
            `;
            
            modal.innerHTML = `
                <div style="position: relative; width: 95%; height: 95%; max-width: 1200px; max-height: 800px; background: white; border-radius: 12px; overflow: hidden; cursor: default; box-shadow: 0 20px 60px rgba(0,0,0,0.3);" onclick="event.stopPropagation()">
                    <div style="position: absolute; top: 15px; right: 15px; background: rgba(0,0,0,0.8); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 20px; z-index: 10; transition: background 0.2s;" 
                         onmouseover="this.style.background='rgba(0,0,0,0.9)'" 
                         onmouseout="this.style.background='rgba(0,0,0,0.8)'"
                         onclick="document.getElementById('modal-imagen-epp').remove()">
                        ✕
                    </div>
                    <div style="display: flex; flex-direction: column; height: 100%; background: #f8f9fa;">
                        <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 10px; overflow: hidden; position: relative;">
                            <img id="modal-imagen-epp-img" style="width: 100%; height: 100%; object-fit: contain; border-radius: 4px;" alt="${imgTitle}">
                        </div>
                        <div style="background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.6)); color: white; padding: 15px; text-align: center; position: relative;">
                            <div id="modal-imagen-epp-title" style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">${imgTitle}</div>
                            <div style="font-size: 14px; opacity: 0.9;">Click fuera o presiona ESC para cerrar</div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Evento para cerrar al hacer clic fuera
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
            
            // Evento para cerrar con ESC
            document.addEventListener('keydown', function escHandler(e) {
                if (e.key === 'Escape') {
                    modal.remove();
                    document.removeEventListener('keydown', escHandler);
                }
            });
        }
        
        // Actualizar imagen y título
        const img = document.getElementById('modal-imagen-epp-img');
        const title = document.getElementById('modal-imagen-epp-title');
        
        if (img) {
            img.src = imgUrl;
            img.alt = imgTitle;
        }
        
        if (title) {
            title.textContent = imgTitle;
        }
        
        // Mostrar modal
        modal.style.display = 'flex';
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
