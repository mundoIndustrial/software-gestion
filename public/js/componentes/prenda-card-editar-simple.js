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



    
    // Si no viene pedidoId, intentar obtenerlo
    if (!pedidoId) {
        pedidoId = obtenerPedidoId();

    }

    //  NUEVO: Si tenemos pedidoId y prenda.id, obtener datos frescos de la BD
    let prendaEditable = JSON.parse(JSON.stringify(prenda));
    if (pedidoId && prenda.id) {
        try {

            const response = await fetch(`/asesores/pedidos-produccion/${pedidoId}/prenda/${prenda.id}/datos`);
            if (response.ok) {
                const resultado = await response.json();
                if (resultado.success && resultado.prenda) {

                    prendaEditable = resultado.prenda;
                } else {

                }
            } else {

            }
        } catch (error) {


        }
    } else {

    }
    
    // Preparar datos para generarHTMLFactura
    const datosParaFactura = {
        numero_pedido: pedidoId || 'EDICIÓN',
        cliente: 'Edición',
        prendas: [prendaEditable]
    };
    
    // Obtener HTML de factura
    if (typeof generarHTMLFactura !== 'function') {

        Swal.fire('Error', 'No se puede generar el formulario', 'error');
        return;
    }
    
    let htmlFactura = generarHTMLFactura(datosParaFactura);
    
    // Convertir a editable: inputs en campos importantes
    htmlFactura = hacerFacturaEditable(htmlFactura, prendaEditable);
    
    // Mostrar modal
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



