/**
 * EDICIÓN MODAL SIMPLE DE PRENDA
 * 
 * - Abre modal con factura editable
 * - NO redirige ni carga módulos pesados
 * - Edita inline: variaciones, tallas, procesos
 * - Guardar → POST API → Actualiza BD
 * - Re-renderiza tarjeta readonly
 */

/**
 * Generar HTML con datos completos de la prenda (tallas, colores, telas, variantes)
 */
function generarHTMLDatosPrenda(prenda) {
    let html = '';
    
    console.log('[generarHTMLDatosPrenda] 📋 Iniciando generación de HTML para prenda:', {
        nombre: prenda.nombre_prenda,
        colores_telas_count: prenda.colores_telas ? prenda.colores_telas.length : 0,
        colores_telas: prenda.colores_telas,
    });
    
    // ===== TALLAS POR GÉNERO =====
    if ((prenda.tallas_dama && prenda.tallas_dama.length > 0) || (prenda.tallas_caballero && prenda.tallas_caballero.length > 0)) {
        html += '<div style="margin: 20px 0; padding: 15px; background: #f0f4f8; border-radius: 8px; border-left: 4px solid #3b82f6;">';
        html += '<h4 style="margin: 0 0 12px 0; color: #1e40af; font-size: 12px; font-weight: 700; text-transform: uppercase;"> Tallas por Género</h4>';
        html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">';
        
        // Tallas Dama
        if (prenda.tallas_dama && prenda.tallas_dama.length > 0) {
            html += '<div>';
            html += '<strong style="color: #1e40af; font-size: 11px;">👗 DAMA</strong><br>';
            prenda.tallas_dama.forEach(t => {
                html += `<div style="font-size: 10px; color: #475569; padding: 4px 0;">• ${t.talla}: <strong>${t.cantidad}</strong> prendas</div>`;
            });
            html += '</div>';
        }
        
        // Tallas Caballero
        if (prenda.tallas_caballero && prenda.tallas_caballero.length > 0) {
            html += '<div>';
            html += '<strong style="color: #1e40af; font-size: 11px;">👔 CABALLERO</strong><br>';
            prenda.tallas_caballero.forEach(t => {
                html += `<div style="font-size: 10px; color: #475569; padding: 4px 0;">• ${t.talla}: <strong>${t.cantidad}</strong> prendas</div>`;
            });
            html += '</div>';
        }
        
        html += '</div></div>';
    }
    
    // ===== COLORES Y TELAS =====
    if (prenda.colores_telas && prenda.colores_telas.length > 0) {
        console.log('[generarHTMLDatosPrenda]  Renderizando colores y telas:', {
            count: prenda.colores_telas.length,
            items: prenda.colores_telas.map(ct => ({
                id: ct.id,
                color: ct.color_nombre,
                tela: ct.tela_nombre,
                fotos_count: ct.fotos ? ct.fotos.length : (ct.fotos_tela ? ct.fotos_tela.length : 0),
            }))
        });
        
        html += '<div style="margin: 20px 0; padding: 15px; background: #fef3f2; border-radius: 8px; border-left: 4px solid #ef4444;">';
        html += '<h4 style="margin: 0 0 12px 0; color: #991b1b; font-size: 12px; font-weight: 700; text-transform: uppercase;"> Colores y Telas</h4>';
        
        prenda.colores_telas.forEach((ct, idx) => {
            console.log(`[generarHTMLDatosPrenda] Tela ${idx}:`, {
                id: ct.id,
                color_nombre: ct.color_nombre,
                tela_nombre: ct.tela_nombre,
                fotos: ct.fotos || ct.fotos_tela,
            });
            
            html += '<div style="margin-bottom: 12px; padding: 10px; background: white; border-radius: 6px;">';
            html += `<div style="display: flex; gap: 12px; align-items: center; margin-bottom: 8px;">`;
            
            // Cuadro de color
            const colorCodigo = ct.color_codigo || '#cccccc';
            html += `<div style="width: 24px; height: 24px; background-color: ${colorCodigo}; border: 1px solid #ccc; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></div>`;
            
            // Nombre color y tela
            html += '<div>';
            html += `<strong style="color: #334155; font-size: 11px;"> ${ct.color_nombre}</strong><br>`;
            html += `<span style="color: #64748b; font-size: 10px;">Tela: <strong>${ct.tela_nombre}</strong></span>`;
            if (ct.tela_referencia) {
                html += `<br><span style="color: #94a3b8; font-size: 9px;">Ref: ${ct.tela_referencia}</span>`;
            }
            html += '</div></div>';
            
            // Fotos de tela
            if ((ct.fotos && ct.fotos.length > 0) || (ct.fotos_tela && ct.fotos_tela.length > 0)) {
                const fotosArray = ct.fotos || ct.fotos_tela || [];
                console.log(`[generarHTMLDatosPrenda] Tela ${idx} tiene ${fotosArray.length} fotos:`, fotosArray);
                
                html += '<div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px;">';
                fotosArray.forEach((foto, fotoIdx) => {
                    const urlFoto = foto.url || foto.ruta_webp || foto.ruta_original || '';
                    console.log(`[generarHTMLDatosPrenda] Foto ${fotoIdx}: ${urlFoto}`);
                    
                    if (urlFoto) {
                        html += `<img src="${urlFoto}" style="width: 50px; height: 50px; border-radius: 4px; object-fit: cover; border: 1px solid #e5e7eb; cursor: pointer;" onclick="window.open('${urlFoto}', '_blank')" title="Ver foto">`;
                    }
                });
                html += '</div>';
            } else {
                console.log(`[generarHTMLDatosPrenda] Tela ${idx} NO tiene fotos`);
            }
            html += '</div>';
        });
        
        html += '</div>';
    }
    
    // ===== VARIANTES =====
    if (prenda.variantes && prenda.variantes.length > 0) {
        html += '<div style="margin: 20px 0; padding: 15px; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">';
        html += '<h4 style="margin: 0 0 12px 0; color: #166534; font-size: 12px; font-weight: 700; text-transform: uppercase;"> Variantes</h4>';
        
        prenda.variantes.forEach(v => {
            html += '<div style="margin-bottom: 10px; padding: 10px; background: white; border-radius: 6px; border: 1px solid #dbeafe;">';
            html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 11px;">';
            
            if (v.tipo_manga) {
                html += `<div><strong>Manga:</strong> ${v.tipo_manga}${v.manga_obs ? ' <em style="color: #64748b;">(' + v.manga_obs + ')</em>' : ''}</div>`;
            }
            
            if (v.tipo_broche_boton) {
                html += `<div><strong>🔘 Broche:</strong> ${v.tipo_broche_boton}${v.broche_boton_obs ? ' <em style="color: #64748b;">(' + v.broche_boton_obs + ')</em>' : ''}</div>`;
            }
            
            if (v.tiene_bolsillos) {
                html += `<div><strong>👜 Bolsillos:</strong> Sí${v.bolsillos_obs ? ' <em style="color: #64748b;">(' + v.bolsillos_obs + ')</em>' : ''}</div>`;
            }
            
            html += '</div></div>';
        });
        
        html += '</div>';
    }
    
    return html;
}

// Exponer en window para acceso global
window.generarHTMLDatosPrenda = generarHTMLDatosPrenda;

/**
 * Obtener pedidoId del contexto (varias fuentes)
 */
function obtenerPedidoId() {
    // 1. Del body data attribute (establecido en modal)
    let id = document.querySelector('body').dataset.pedidoIdEdicion;
    if (id) return id;
    
    // 2. Del URL (si estamos en página de edición)
    const urlParams = new URLSearchParams(window.location.search);
    id = urlParams.get('editar');
    if (id) return id;
    
    // 3. Del elemento con data-pedido-id
    id = document.querySelector('[data-pedido-id]')?.dataset.pedidoId;
    if (id) return id;
    
    return null;
}

/**
 * Abrir modal de edición para una prenda
 * @param {Object} prenda - Objeto de prenda a editar
 * @param {number} prendaIndex - Índice en gestor local
 * @param {number} pedidoId - ID del pedido en BD (para guardar)
 */
async function abrirEditarPrendaModal(prenda, prendaIndex, pedidoId) {
    console.log('🔥🔥🔥 [INIT] abrirEditarPrendaModal - Valores recibidos:', {
        prenda_nombre: prenda?.nombre_prenda || prenda?.nombre,
        prenda_id: prenda?.id,
        prendaIndex: prendaIndex,
        pedidoId_RECIBIDO: pedidoId,
        tipo_pedidoId: typeof pedidoId
    });
    
    // Si no viene pedidoId, intentar obtenerlo
    if (!pedidoId) {
        console.warn(' [OBTENER-ID] pedidoId vacío, buscando...');
        pedidoId = obtenerPedidoId();
        console.log(' [OBTENER-ID] Después de obtenerPedidoId():', pedidoId);
    }

    console.log(' [PEDIDO-ID-FINAL] pedidoId usado será:', pedidoId);

    // Obtener datos frescos de la BD
    let prendaEditable = JSON.parse(JSON.stringify(prenda));
    let datosParaFactura = {
        numero_pedido: 'Cargando...',
        numero: 'Cargando...',
        cliente: 'Cargando...',
        asesor: 'Cargando...',
        estado: 'Cargando...',
        fecha_creacion: 'Cargando...',
        prendas: [prendaEditable]
    };
    
    console.log('🔥 [FETCH-INICIO] Condiciones:', {
        tiene_pedidoId: !!pedidoId,
        tiene_prenda_id: !!prenda?.id,
        ejecutara_fetch: !!(pedidoId && prenda?.id)
    });
    
    if (pedidoId && prenda.id) {
        try {
            const url = `/asesores/pedidos/${pedidoId}/factura-datos`;
            console.log('🔥 [FETCH] Llamando a URL:', url);
            console.log(' [FETCH-DEBUG] Parámetros - pedidoId:', pedidoId);

            const response = await fetch(url);
            console.log(' [FETCH-RESPONSE] Status:', response.status, 'OK:', response.ok);
            
            if (response.ok) {
                const resultado = await response.json();
                console.log(' [FETCH-JSON] Respuesta completa:', {
                    success: resultado.success,
                    tiene_data: !!resultado.data,
                    tiene_prendas: !!(resultado.data?.prendas),
                    prendas_count: resultado.data?.prendas?.length ?? 0
                });
                
                // Encontrar la prenda específica en los datos del pedido - BÚSQUEDA BIDIRECCIONAL MEJORADA
                //  FIX: Priorizar búsqueda por prenda_pedido_id que es el identificador más confiable
                const prendaDelPedido = resultado.data?.prendas?.find(p => {
                    // Coincidencia por prenda_pedido_id (PRIORIDAD MÁXIMA - es el ID único de la BD)
                    const coincidePrendaPedidoId = (p.prenda_pedido_id === prenda.prenda_pedido_id || 
                                                   p.prenda_pedido_id === prenda.id);
                    
                    // Coincidencia por ID general
                    const coincideId = (p.id === prenda.id);
                    
                    // Coincidencia por nombre (TODAS LAS COMBINACIONES POSIBLES) - baja prioridad
                    //  FIX: Evitar comparar undefined === undefined (siempre es true)
                    const coincideNombre = (
                        // Caso 1: nombre_prenda local == nombre_prenda servidor (AMBOS DEBEN SER VÁLIDOS)
                        (prenda.nombre_prenda && p.nombre_prenda && p.nombre_prenda === prenda.nombre_prenda) ||
                        // Caso 2: nombre local == nombre servidor  
                        (prenda.nombre && p.nombre && p.nombre === prenda.nombre) ||
                        // Caso 3: nombre_prenda local == nombre servidor (cruzado - AMBOS DEBEN SER VÁLIDOS)
                        (prenda.nombre_prenda && p.nombre && p.nombre === prenda.nombre_prenda) ||
                        // Caso 4: nombre local == nombre_prenda servidor (cruzado - AMBOS DEBEN SER VÁLIDOS)
                        (prenda.nombre && p.nombre_prenda && p.nombre_prenda === prenda.nombre) ||
                        // Caso 5: nombre_producto local == nombre servidor (AMBOS DEBEN SER VÁLIDOS)
                        (prenda.nombre_producto && p.nombre && p.nombre === prenda.nombre_producto) ||
                        // Caso 6: nombre_producto local == nombre_prenda servidor (AMBOS DEBEN SER VÁLIDOS)
                        (prenda.nombre_producto && p.nombre_prenda && p.nombre_prenda === prenda.nombre_producto) ||
                        // Caso 7: nombre local == nombre_producto servidor (AMBOS DEBEN SER VÁLIDOS)
                        (prenda.nombre && p.nombre_producto && p.nombre_producto === prenda.nombre) ||
                        // Caso 8: nombre_prenda local == nombre_producto servidor (AMBOS DEBEN SER VÁLIDOS)
                        (prenda.nombre_prenda && p.nombre_producto && p.nombre_producto === prenda.nombre_prenda)
                    );
                    
                    // Si coincide por prenda_pedido_id, es la más confiable (PRIORIDAD MÁXIMA)
                    if (coincidePrendaPedidoId) {
                        return true;
                    }
                    
                    // Si coincide por ID, es suficiente (segunda prioridad)
                    if (coincideId) {
                        return true;
                    }
                    
                    // Si no coincide por ID, requerir coincidencia por nombre (baja prioridad)
                    return coincideNombre;
                });
                
                if (prendaDelPedido) {
                    console.log(' [PRENDA-ENCONTRADA] Prenda encontrada en datos del pedido:', prendaDelPedido.nombre_prenda);
                    console.log(' [DATOS-RECIBIDOS]', {
                        procesos: prendaDelPedido.procesos?.length ?? 0,
                        tallas: Object.keys(prendaDelPedido.tallas || {}).length,
                        variantes: prendaDelPedido.variantes?.length ?? 0,
                        colores_telas: prendaDelPedido.colores_telas?.length ?? 0,
                        imagenes: prendaDelPedido.imagenes?.length ?? 0
                    });
                    
                    // Actualizar la prenda editable con los datos frescos
                    prendaEditable = prendaDelPedido;
                    
                    // Construir datosParaFactura con datos del pedido
                    const pedidoData = resultado.data;
                    datosParaFactura = {
                        numero_pedido: pedidoData.numero_pedido || pedidoData.numero || pedidoData.id || pedidoId,
                        numero: pedidoData.numero || pedidoData.numero_pedido || pedidoId,
                        cliente: pedidoData.cliente || 'Cliente sin especificar',
                        asesor: pedidoData.asesor || 'Asesor sin especificar',
                        estado: pedidoData.estado || 'Pendiente',
                        fecha_creacion: pedidoData.fecha_creacion || new Date().toLocaleDateString(),
                        prendas: [prendaEditable]
                    };
                    console.log(' [DATOS-FACTURA-ACTUALIZADO]:', datosParaFactura);
                } else {
                    console.warn(' [PRENDA-NO-ENCONTRADA] Prenda no encontrada en datos del pedido');
                    datosParaFactura.prendas = [prendaEditable];
                }
            } else {
                console.warn(' [ERROR-SUCCESS] Respuesta sin success:', resultado);
                datosParaFactura.prendas = [prendaEditable];
            }
        } catch (error) {
            console.error(' [ERROR-EXCEPTION] Error en fetch:', error);
            datosParaFactura.prendas = [prendaEditable];
        }
    } else {
        console.warn(' [NO-FETCH] No se ejecuta fetch - pedidoId o prenda.id faltante');
        datosParaFactura.prendas = [prendaEditable];
    }
    
    console.log(' [FINAL-DATOS-FACTURA] Datos finales para generar HTML:', datosParaFactura);
    
    // Obtener HTML de factura
    if (typeof generarHTMLFactura !== 'function') {
        console.error(' [ERROR-FUNCIONES] generarHTMLFactura no está definida');
        Swal.fire('Error', 'No se puede generar el formulario', 'error');
        return;
    }
    
    console.log(' [HTML-INICIO] Iniciando generación de HTML');
    let htmlFactura = generarHTMLFactura(datosParaFactura);
    console.log(' [HTML-FACTURA] HTML de factura generado, largo:', htmlFactura.length);
    
    // Agregar sección de datos de la prenda (tallas, colores, telas, variantes)
    console.log(' [HTML-DATOS] Agregando datos de prenda:', {
        tallas_dama: prendaEditable.tallas_dama?.length ?? 0,
        tallas_caballero: prendaEditable.tallas_caballero?.length ?? 0,
        variantes: prendaEditable.variantes?.length ?? 0,
        colores_telas: prendaEditable.colores_telas?.length ?? 0
    });
    htmlFactura += generarHTMLDatosPrenda(prendaEditable);
    console.log(' [HTML-DATOS-COMPLETADO] HTML actualizado, largo total:', htmlFactura.length);
    
    // Convertir a editable: inputs en campos importantes
    console.log(' [HTML-EDITABLE] Iniciando conversión a editable');
    htmlFactura = hacerFacturaEditable(htmlFactura, prendaEditable);
    console.log(' [HTML-EDITABLE-COMPLETADO] HTML editable completado, largo:', htmlFactura.length);
    
    // Mostrar modal
    console.log('📱 [MODAL-MOSTRAR] Mostrando modal SweetAlert2');
    Swal.fire({
        title: ` Editar: ${prenda.nombre_producto || 'Prenda'}`,
        html: `<div style="text-align: left; max-height: 600px; overflow-y: auto; background: white; padding: 1rem; border-radius: 8px;">${htmlFactura}</div>`,
        width: '900px',
        showConfirmButton: true,
        confirmButtonText: ' Guardar Cambios',
        confirmButtonColor: '#10b981',
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
        cancelButtonColor: '#ef4444',
        preConfirm: async () => {

            
            // Extraer datos editados
            const datosModificados = extraerDatosModalEdicion(prendaEditable);

            
            // Guardar en BD
            if (pedidoId && prenda.id) {
                const guardado = await guardarPrendaEnBD(pedidoId, prenda.id, datosModificados);
                if (!guardado) {
                    Swal.showValidationMessage('Error al guardar en BD');
                    return false;
                }
            }
            
            // Actualizar en gestor local
            if (window.gestorPrendaSinCotizacion) {
                window.gestorPrendaSinCotizacion.actualizar(prendaIndex, datosModificados);

            }
            
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {

            
            // Re-renderizar tarjeta
            reRenderizarTarjetaPrendaEditada(prendaIndex);
            
            Swal.fire({
                title: ' Guardado',
                text: 'Prenda actualizada correctamente',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        } else {

        }
    });
}

// Exponer en window para acceso global
window.abrirEditarPrendaModal = abrirEditarPrendaModal;

/**
 * Convertir factura a editable
 */
function hacerFacturaEditable(htmlFactura, prenda) {

    
    const temp = document.createElement('div');
    temp.innerHTML = htmlFactura;
    
    // === 1. EDITAR VARIACIONES (Tabla) ===

    temp.querySelectorAll('table').forEach((table) => {
        const header = table.previousElementSibling?.textContent || '';
        
        if (header.includes('VARIANTES') || header.includes('ESPECIFICACIONES')) {

            
            table.querySelectorAll('tbody tr').forEach((row) => {
                const cells = row.querySelectorAll('td');
                
                // Últimas 3 columnas editable
                for (let i = 2; i < cells.length; i++) {
                    const cell = cells[i];
                    const texto = cell.textContent.trim();
                    
                    if (texto && texto !== '—' && texto !== '—' && !texto.startsWith('<')) {
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.value = texto;
                        input.style.cssText = 'width: 100%; padding: 0.5rem; border: 1px solid #0ea5e9; border-radius: 4px; font-size: 11px;';
                        input.className = 'editar-variacion-input';
                        
                        cell.innerHTML = '';
                        cell.appendChild(input);
                    }
                }
            });
        }
    });
    
    // === 2. EDITAR TALLAS (Tabla) ===

    temp.querySelectorAll('table').forEach((table) => {
        const filas = table.querySelectorAll('tbody tr');
        let esTablaTallas = false;
        
        // Detectar si es tabla de tallas (tiene géneros como Dama, Caballero)
        filas.forEach(row => {
            const primeraCelda = row.querySelector('td:first-child')?.textContent || '';
            if (/dama|caballero|niño|niña|masculino|femenino/i.test(primeraCelda)) {
                esTablaTallas = true;
            }
        });
        
        if (esTablaTallas) {

            
            filas.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 2) {
                    const cellCantidades = cells[cells.length - 1];
                    const cantText = cellCantidades.textContent.trim();
                    
                    // Crear campos editables para cada cantidad
                    const regex = /(\w+):(\d+)/g;
                    let match;
                    const inputs = [];
                    
                    while ((match = regex.exec(cantText)) !== null) {
                        const talla = match[1];
                        const cantidad = match[2];
                        
                        const input = document.createElement('input');
                        input.type = 'number';
                        input.value = cantidad;
                        input.min = '0';
                        input.placeholder = talla;
                        input.dataset.talla = talla;
                        input.style.cssText = 'width: 60px; padding: 0.4rem; border: 1px solid #0ea5e9; border-radius: 4px; margin: 0.2rem;';
                        input.className = 'editar-cantidad-input';
                        
                        inputs.push(input);
                    }
                    
                    if (inputs.length > 0) {
                        cellCantidades.innerHTML = '';
                        inputs.forEach(inp => cellCantidades.appendChild(inp));
                    }
                }
            });
        }
    });
    
    // === 3. EDITAR PROCESOS (Observaciones) ===

    temp.querySelectorAll('[style*="color: #64748b"]').forEach((elem) => {
        const texto = elem.textContent.trim();
        
        // Si parece una observación
        if (texto.length > 3 && !texto.startsWith('<')) {
            const textarea = document.createElement('textarea');
            textarea.value = texto;
            textarea.style.cssText = 'width: 100%; padding: 0.5rem; border: 1px solid #0ea5e9; border-radius: 4px; font-size: 10px; min-height: 50px;';
            textarea.className = 'editar-observacion-input';
            
            elem.replaceWith(textarea);
        }
    });
    
    return temp.innerHTML;
}

/**
 * Extraer datos del modal de edición
 */
function extraerDatosModalEdicion(prendaOriginal) {

    
    const datos = JSON.parse(JSON.stringify(prendaOriginal));
    
    // Extraer variaciones editadas
    document.querySelectorAll('.editar-variacion-input').forEach((input) => {
        // Buscar qué campo es (por el contexto de la fila)
        const fila = input.closest('tr');
        if (fila) {
            const celdas = fila.querySelectorAll('td');
            const nombreCampo = celdas[0]?.textContent.trim().toLowerCase() || '';
            const valor = input.value.trim();
            

            
            datos.variantes = datos.variantes || {};
            
            if (nombreCampo.includes('manga')) {
                datos.variantes.tipo_manga = valor || 'No aplica';
            } else if (nombreCampo.includes('broche')) {
                datos.variantes.tipo_broche = valor || 'No aplica';
            }
        }
    });
    
    // Extraer tallas/cantidades
    document.querySelectorAll('.editar-cantidad-input').forEach((input) => {
        const cantidad = parseInt(input.value) || 0;
        const talla = input.dataset.talla;
        
        if (talla) {

            
            // Buscar género de la fila
            const fila = input.closest('tr');
            const genero = fila?.querySelector('td:first-child')?.textContent.trim().toLowerCase() || 'general';
            
            const clave = `${genero}-${talla}`;
            datos.cantidadesPorTalla = datos.cantidadesPorTalla || {};
            datos.cantidadesPorTalla[clave] = cantidad;
        }
    });
    
    // Extraer observaciones de procesos
    document.querySelectorAll('.editar-observacion-input').forEach((textarea) => {
        const observacion = textarea.value.trim();

        
        // Buscar a qué proceso pertenece
        const proc = textarea.closest('[style*="border"]')?.querySelector('[style*="1e40af"]')?.textContent || '';
        if (proc) {

        }
    });
    

    return datos;
}

/**
 * Guardar prenda en BD
 */
async function guardarPrendaEnBD(pedidoId, prendaId, datos) {




    
    try {
        const response = await fetch(`/asesores/pedidos-produccion/${pedidoId}/prendas/${prendaId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(datos)
        });
        
        if (!response.ok) {
            const error = await response.json();

            Swal.fire('Error', `No se pudo guardar: ${error.message}`, 'error');
            return false;
        }
        
        const result = await response.json();

        return true;
        
    } catch (error) {

        Swal.fire('Error', 'Error de conexión al guardar', 'error');
        return false;
    }
}

/**
 * Re-renderizar tarjeta de prenda editada
 */
function reRenderizarTarjetaPrendaEditada(prendaIndex) {

    
    if (!window.gestorPrendaSinCotizacion || !window.generarTarjetaPrendaReadOnly) {

        return;
    }
    
    const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
    if (!prenda) {

        return;
    }
    
    // Buscar tarjeta en DOM
    const tarjeta = document.querySelector(`[data-prenda-index="${prendaIndex}"]`);
    if (!tarjeta) {

        return;
    }
    
    // Re-generar HTML
    const nuevoHTML = window.generarTarjetaPrendaReadOnly(prenda, prendaIndex);
    const nuevoElemento = document.createElement('div');
    nuevoElemento.innerHTML = nuevoHTML;
    
    // Reemplazar tarjeta
    tarjeta.replaceWith(nuevoElemento.firstElementChild);

}



