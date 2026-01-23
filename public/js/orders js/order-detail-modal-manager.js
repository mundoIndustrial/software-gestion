/**
 * Order Detail Modal Manager para Registro de Órdenes
 * Maneja la apertura y cierre del modal de detalles de orden
 * SINCRONIZADO CON: pedidos-detail-modal.js (asesores)
 */



/**
 * Abre el modal de detalle de la orden
 * Compatible con la estructura de asesores
 */
window.openOrderDetailModal = function(orderId) {
    // Cerrar el modal de logo si está abierto
    const modalWrapperLogo = document.getElementById('order-detail-modal-wrapper-logo');
    if (modalWrapperLogo) {
        modalWrapperLogo.style.display = 'none';

    }
    
    // Obtener el overlay
    let overlay = document.getElementById('modal-overlay');

    
    if (overlay) {
        // Mover al body si es necesario
        if (overlay.parentElement !== document.body) {
            document.body.appendChild(overlay);
        }
        
        // Mostrar overlay
        overlay.style.display = 'block';
        overlay.style.zIndex = '9997';
        overlay.style.position = 'fixed';
        overlay.style.opacity = '1';
        overlay.style.visibility = 'visible';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';

        
        // Mostrar el wrapper del modal
        const modalWrapper = document.getElementById('order-detail-modal-wrapper');
        if (modalWrapper) {
            modalWrapper.style.display = 'block';
            modalWrapper.style.zIndex = '9998';
            modalWrapper.style.position = 'fixed';
            modalWrapper.style.top = '60%';
            modalWrapper.style.left = '50%';
            modalWrapper.style.transform = 'translate(-50%, -50%)';
            modalWrapper.style.pointerEvents = 'auto';

        } else {

        }
    }
};

/**
 * Cierra el modal de detalle de la orden
 */
window.closeOrderDetailModal = function() {
    const overlay = document.getElementById('modal-overlay');
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    
    if (overlay) {
        overlay.style.display = 'none';

    }
    
    if (modalWrapper) {
        modalWrapper.style.display = 'none';

        
        // Ocultar flechas del modal
        const arrowContainers = modalWrapper.querySelectorAll('.arrow-container');
        arrowContainers.forEach((container) => {
            container.style.display = 'none';
        });
        
        // Limpiar contenido del modal de costura
        const descripcionText = modalWrapper.querySelector('#descripcion-text');
        if (descripcionText) descripcionText.innerHTML = '';

    }
};

/**
 * Cierra el modal al hacer click en el overlay (cierra ambos modales)
 */
window.closeModalOverlay = function() {

    window.closeOrderDetailModal();
    window.closeOrderDetailModalLogo();
};

/**
 * Abre el modal de detalle de la orden con LOGO (Bordados)
 */
window.openOrderDetailModalLogo = function(orderId) {
    // Cerrar el modal de costura si está abierto
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    if (modalWrapper) {
        modalWrapper.style.display = 'none';

    }
    
    // Obtener el overlay
    let overlay = document.getElementById('modal-overlay');

    
    if (overlay) {
        // Mover al body si es necesario
        if (overlay.parentElement !== document.body) {
            document.body.appendChild(overlay);
        }
        
        // Mostrar overlay
        overlay.style.display = 'block';
        overlay.style.zIndex = '99997';
        overlay.style.position = 'fixed';
        overlay.style.opacity = '1';
        overlay.style.visibility = 'visible';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';

        
        // Mostrar el wrapper del modal de LOGO
        const modalWrapper = document.getElementById('order-detail-modal-wrapper-logo');
        if (modalWrapper) {
            modalWrapper.style.display = 'block';
            modalWrapper.style.zIndex = '99999';
            modalWrapper.style.position = 'fixed';
            modalWrapper.style.top = '60%';
            modalWrapper.style.left = '50%';
            modalWrapper.style.transform = 'translate(-50%, -50%)';
            modalWrapper.style.pointerEvents = 'auto';

        } else {

        }
    }
};

/**
 * Cierra el modal de detalle de la orden (Logo)
 */
window.closeOrderDetailModalLogo = function() {
    const overlay = document.getElementById('modal-overlay');
    const modalWrapper = document.getElementById('order-detail-modal-wrapper-logo');
    
    if (overlay) {
        overlay.style.display = 'none';

    }
    
    if (modalWrapper) {
        modalWrapper.style.display = 'none';

        
        // Ocultar flechas del modal logo
        const arrowContainers = modalWrapper.querySelectorAll('.arrow-container');
        arrowContainers.forEach((container) => {
            container.style.display = 'none';
        });
        
        // Limpiar contenido del modal de logo
        const descripcionText = modalWrapper.querySelector('#descripcion-text');
        if (descripcionText) descripcionText.innerHTML = '';
        const galeriaContainer = modalWrapper.querySelector('#galeria-modal-logo');
        if (galeriaContainer) galeriaContainer.innerHTML = '';

    }
};

/**
 * Estado global para navegación de prendas
 */
window.prendasState = {
    todasLasPrendas: [],
    currentPage: 0,
    prendasPorPagina: 2,
    esCotizacion: false
};

/**
 * Renderizar datos de la orden en el modal
 */
function renderOrderDetail(orden) {

    
    // Guardar estado de prendas
    window.prendasState.todasLasPrendas = orden.prendas || [];
    window.prendasState.currentPage = 0;
    window.prendasState.esCotizacion = orden.es_cotizacion || false;
    
    //  NUEVO: Guardar descripcion_prendas construida en el controlador
    window.prendasState.descripcionPrendasCompleta = orden.descripcion_prendas || '';
    
    //  NUEVO: Llenar prendasGaleria con fotos del servidor
    if (!window.prendasGaleria) {
        window.prendasGaleria = [];
    }
    if (!window.telasGaleria) {
        window.telasGaleria = [];
    }
    
    if (orden.prendas && Array.isArray(orden.prendas)) {
        orden.prendas.forEach((prenda, index) => {
            //  NUEVO: Si la prenda tiene información de reflectivo, guardarla para mostrar
            if (prenda.reflectivo) {
                // Decodificar campos JSON si llegan como strings
                if (typeof prenda.reflectivo.generos === 'string') {
                    prenda.reflectivo.generos = JSON.parse(prenda.reflectivo.generos);
                }
                if (typeof prenda.reflectivo.tallas === 'string') {
                    prenda.reflectivo.tallas = JSON.parse(prenda.reflectivo.tallas);
                }
                if (typeof prenda.reflectivo.ubicaciones === 'string') {
                    prenda.reflectivo.ubicaciones = JSON.parse(prenda.reflectivo.ubicaciones);
                }

            }
            
            // Llenar galería de fotos de prenda
            if (prenda.fotos && Array.isArray(prenda.fotos)) {
                window.prendasGaleria[index] = prenda.fotos.filter(f => f); // Filtrar null/undefined

            } else {
                window.prendasGaleria[index] = [];
            }
            
            // Llenar galería de fotos de tela
            if (prenda.tela_fotos && Array.isArray(prenda.tela_fotos)) {
                if (!window.telasGaleria[index]) {
                    window.telasGaleria[index] = {};
                }
                window.telasGaleria[index][0] = prenda.tela_fotos.filter(f => f); // Filtrar null/undefined

            }
        });
    }
    

    
    // Llenar fecha
    const dayBox = document.querySelector('.day-box');
    const monthBox = document.querySelector('.month-box');
    const yearBox = document.querySelector('.year-box');
    
    if (dayBox && monthBox && yearBox) {
        const fecha = new Date(orden.fecha_de_creacion_de_orden);
        if (!isNaN(fecha.getTime())) {
            const dia = String(fecha.getDate()).padStart(2, '0');
            const mes = String(fecha.getMonth() + 1).padStart(2, '0');
            const año = fecha.getFullYear();
            
            dayBox.textContent = dia;
            monthBox.textContent = mes;
            yearBox.textContent = año;
        }
    }
    
    // Llenar cliente
    const clienteValue = document.getElementById('cliente-value');
    if (clienteValue) clienteValue.textContent = orden.cliente || '-';
    
    // Llenar asesora
    const asesoraValue = document.getElementById('asesora-value');
    if (asesoraValue) asesoraValue.textContent = orden.asesora || '-';
    
    // Llenar forma de pago
    const formaPagoValue = document.getElementById('forma-pago-value');
    if (formaPagoValue) formaPagoValue.textContent = orden.forma_de_pago || '-';
    
    // Renderizar prendas con paginación
    renderPrendasPage();
    
    // Llenar pedido número
    const pedidoNumber = document.querySelector('.pedido-number');
    if (pedidoNumber) {
        pedidoNumber.textContent = `#${orden.numero_pedido}`;
    }
    
    // Llenar encargado de orden
    const encargadoValue = document.getElementById('encargado-value');
    if (encargadoValue) encargadoValue.textContent = orden.encargado_orden || '-';
    
    // Llenar prendas entregadas
    const prendasValue = document.getElementById('prendas-entregadas-value');
    if (prendasValue) {
        prendasValue.textContent = `${orden.total_entregado || 0}/${orden.cantidad_total || orden.cantidad || 0}`;
    }
    
    // Actualizar visibilidad de flechas de navegación
    updateNavigationArrows();
    

}

/**
 * Renderizar página actual de prendas
 */
function renderPrendasPage() {
    const { todasLasPrendas, currentPage, prendasPorPagina, esCotizacion, descripcionPrendasCompleta } = window.prendasState;
    
    if (!todasLasPrendas || todasLasPrendas.length === 0) {
        const descripcionText = document.getElementById('descripcion-text');
        if (descripcionText) {
            descripcionText.innerHTML = '-';
        }
        return;
    }
    
    let descripcionHTML = '';
    
    //  PRIMERO: Si existe descripcion_prendas construida en el controlador, usarla directamente
    if (descripcionPrendasCompleta && descripcionPrendasCompleta.trim() !== '') {




        
        let bloquesPrendas = [];
        
        //  SI EL HTML YA TIENE SPANS CON ESTILOS (contiene <span style), NO DIVIDIR
        // Simplemente usarlo como está
        if (descripcionPrendasCompleta.includes("<span style='font-size:") || 
            descripcionPrendasCompleta.includes('<span style="font-size:')) {

            bloquesPrendas = [descripcionPrendasCompleta.trim()];
        } else if (descripcionPrendasCompleta.includes('PRENDA ')) {
            // Hay formato PRENDA X: - dividir por eso
            const partes = descripcionPrendasCompleta.split('PRENDA ');
            


            
            bloquesPrendas = partes
                .map((parte, idx) => {
                    if (idx === 0 && !parte.trim()) {

                        return null;
                    }
                    const resultado = (idx > 0 ? 'PRENDA ' : '') + parte.trim();

                    return resultado;
                })
                .filter(b => {
                    // Filtrar bloques que sean solo HTML sin texto real
                    if (!b) return false;
                    
                    // Remover tags HTML para ver si hay contenido real
                    const sinHTML = b.replace(/<[^>]*>/g, '').trim();
                    if (!sinHTML || sinHTML.length < 5) {

                        return false;
                    }
                    return true;
                });
            

        } else {
            // No hay formato PRENDA - dividir por \n\n pero agrupar tallas con su contenido
            const bloques = descripcionPrendasCompleta
                .split('\n\n')
                .filter(b => b && b.trim() !== '');
            
            // Agrupar bloques de tallas con el bloque anterior
            bloquesPrendas = [];
            let bloqueActual = '';
            
            for (let i = 0; i < bloques.length; i++) {
                const bloque = bloques[i];
                
                // Si es una línea de tallas o cantidad, agregarlo al bloque actual
                if (/^(TALLAS?:|CANTIDAD TOTAL:)/i.test(bloque.trim())) {
                    bloqueActual += '\n\n' + bloque;
                } else {
                    // Si había un bloque anterior, guardarlo
                    if (bloqueActual) {
                        bloquesPrendas.push(bloqueActual.trim());
                    }
                    // Iniciar nuevo bloque
                    bloqueActual = bloque;
                }
            }
            
            // Agregar el último bloque
            if (bloqueActual) {
                bloquesPrendas.push(bloqueActual.trim());
            }
        }
        


        
        // Aplicar paginación
        const startIndex = currentPage * prendasPorPagina;
        const endIndex = startIndex + prendasPorPagina;
        const bloquesActuales = bloquesPrendas.slice(startIndex, endIndex);
        

        
        // Formatear bloques actuales con estilos
        const descripcionFormateada = bloquesActuales
            .map((bloque, bloqueIdx) => {

                // Convertir saltos de línea a <br> pero preservar estructura
                // También trim() cada línea para evitar espacios extra
                const lineas = bloque.split('\n').map(l => l.trim()).filter(l => l !== '');
                
                // Procesar líneas y filtrar duplicados
                const lineasProcesadas = [];
                let hayTallasYa = false;
                
                for (let i = 0; i < lineas.length; i++) {
                    let linea = lineas[i];
                    if (linea === '') continue;
                    
                    //  FILTRAR: No mostrar líneas de CANTIDAD TOTAL
                    if (/^CANTIDAD TOTAL:/i.test(linea)) {

                        continue;
                    }
                    
                    //  FILTRAR: Si hay "TALLAS:", ignorar "Talla:" (evitar duplicados)
                    if (/^Talla:/i.test(linea) && hayTallasYa) {

                        continue;
                    }
                    
                    //  Detectar si hay TALLAS para filtrar duplicados después
                    if (/^TALLAS:/i.test(linea)) {
                        hayTallasYa = true;
                    }
                    

                    
                    //  NEGRILLA en títulos: PRENDA X:, Color:, Tela:, Manga:, DESCRIPCION:, etc.
                    linea = linea.replace(/^(PRENDA \d+:)/g, '<strong>$1</strong>');
                    linea = linea.replace(/(Color:|Tela:|Manga:|DESCRIPCION:)/g, '<strong>$1</strong>');
                    
                    //  NEGRILLA en viñetas: • Reflectivo:, • Bolsillos:, • BOTÓN:, etc.
                    linea = linea.replace(/^(•\s+(Reflectivo:|Bolsillos:|BOTÓN:|[A-Z]+:))/g, '<strong>$1</strong>');
                    
                    //  ROJO en tallas: detectar tanto "Talla:" como "TALLAS:"
                    // Líneas como: "TALLAS: XS: 10, S: 20..." o "Talla: S: 10, M: 10"
                    if (/^TALLAS?:/i.test(linea)) {
                        linea = linea.replace(/^(TALLAS?:)\s+(.+)$/i, '$1 <span style="color: #d32f2f; font-weight: bold;">$2</span>');

                    }
                    
                    lineasProcesadas.push(linea);
                }
                
                return lineasProcesadas.join('<br>');
            })
            .join('<br><br>'); // Separar bloques de prendas
        
        //  AGREGAR INFORMACIÓN DE REFLECTIVO SI EXISTE
        let reflectivoHTML = '';
        const startIndexReflectivo = currentPage * prendasPorPagina;
        const endIndexReflectivo = startIndexReflectivo + prendasPorPagina;
        const prendasActualesReflectivo = todasLasPrendas.slice(startIndexReflectivo, endIndexReflectivo);
        



        
        prendasActualesReflectivo.forEach((prenda, index) => {

            if (prenda.reflectivo) {

                
                const reflectivo = prenda.reflectivo;
                let reflectivoContent = '<br>';
                
                // 1. Descripción del reflectivo
                if (reflectivo.descripcion) {
                    reflectivoContent += `<strong>DESCRIPCIÓN REFLECTIVO:</strong><br>${reflectivo.descripcion.toUpperCase()}<br>`;
                }
                
                // 2. Ubicaciones del reflectivo
                if (reflectivo.ubicaciones && Array.isArray(reflectivo.ubicaciones) && reflectivo.ubicaciones.length > 0) {
                    reflectivoContent += `<br><strong>UBICACIONES REFLECTIVO:</strong><br>`;
                    reflectivo.ubicaciones.forEach((ubicacion) => {
                        let ubicacionStr = `• ${ubicacion.nombre || 'Sin nombre'}`;
                        if (ubicacion.observaciones) {
                            ubicacionStr += ` - ${ubicacion.observaciones}`;
                        }
                        reflectivoContent += `${ubicacionStr}<br>`;
                    });
                }
                
                // 3. Tallas (array de objetos {genero, talla, cantidad})
                if (reflectivo.tallas && Array.isArray(reflectivo.tallas)) {
                    reflectivoContent += '<br>';
                    reflectivo.tallas
                        .filter(tallaRecord => tallaRecord.cantidad > 0)
                        .forEach(tallaRecord => {
                            const generoLabel = tallaRecord.genero.charAt(0).toUpperCase() + tallaRecord.genero.slice(1);
                            reflectivoContent += `<strong>${generoLabel} ${tallaRecord.talla}:</strong> <span style="color: #d32f2f; font-weight: bold;">${tallaRecord.cantidad}</span><br>`;
                        });
                }
                
                // 4. Observaciones generales
                if (reflectivo.observaciones_generales) {
                    reflectivoContent += `<br><strong>OBSERVACIONES:</strong><br>${reflectivo.observaciones_generales}<br>`;
                }
                
                reflectivoHTML += reflectivoContent;
            }
        });
        
        descripcionHTML = `<div style="line-height: 1.8; font-size: 16px; color: #333; word-break: break-word; overflow-wrap: break-word; max-width: 100%; margin: 0; padding: 0;">
            ${descripcionFormateada}${reflectivoHTML}
        </div>`;
        
        // Actualizar navegación de prendas
        const totalPaginas = Math.ceil(bloquesPrendas.length / prendasPorPagina);
        const prevArrow = document.getElementById('prev-arrow');
        const nextArrow = document.getElementById('next-arrow');
        
        if (prevArrow) {
            prevArrow.style.display = currentPage > 0 ? 'block' : 'none';
        }
        if (nextArrow) {
            nextArrow.style.display = currentPage < totalPaginas - 1 ? 'block' : 'none';
        }
        

        
    } else {
        // FALLBACK: Generar descripción dinámica desde prendas (lógica original)

        
        // Calcular índices de inicio y fin
        const startIndex = currentPage * prendasPorPagina;
        const endIndex = startIndex + prendasPorPagina;
        const prendasActuales = todasLasPrendas.slice(startIndex, endIndex);
        
        // Generar descripción dinámica para pedidos
        prendasActuales.forEach((prenda, index) => {






            
            let html = '';
            
            // 1. Nombre de la prenda
            html += `<strong style="font-weight: 800; font-size: 1.05em;">PRENDA ${prenda.numero}: ${prenda.nombre.toUpperCase()}</strong><br>`;
            
            // 2. Línea de atributos: Color | Tela | Manga (con observación de manga si existe)
            const atributos = [];
            if (prenda.color) {
                atributos.push(`<strong>Color:</strong> ${prenda.color.toUpperCase()}`);
            }
            if (prenda.tela) {
                let telaTexto = prenda.tela.toUpperCase();
                if (prenda.tela_referencia) {
                    telaTexto += ` REF:${prenda.tela_referencia.toUpperCase()}`;
                }
                atributos.push(`<strong>Tela:</strong> ${telaTexto}`);
            }
            if (prenda.tipo_manga) {
                let mangaTexto = prenda.tipo_manga.toUpperCase();
                // Agregar observación de manga si existe en descripcion_variaciones
                if (prenda.descripcion_variaciones) {
                    const mangaMatch = prenda.descripcion_variaciones.match(/Manga:\s*(.+?)(?:\s*\||$)/i);
                    if (mangaMatch) {
                        const observacionManga = mangaMatch[1].trim().toUpperCase();
                        // Solo agregar si es diferente al tipo de manga
                        if (observacionManga !== mangaTexto) {
                            mangaTexto += ` (${observacionManga})`;
                        }
                    }
                }
                atributos.push(`<strong>Manga:</strong> ${mangaTexto}`);
            }
            
            if (atributos.length > 0) {
                html += atributos.join(' | ') + '<br>';
            }
            
            // 3. DESCRIPCION - Priorizar descripción completa guardada en BD
            //  NUEVO: Si tiene información de reflectivo, mostrar de forma especial
            if (prenda.reflectivo) {

                
                const reflectivo = prenda.reflectivo;
                
                // 1. Descripción del reflectivo
                if (reflectivo.descripcion) {
                    html += `<strong>DESCRIPCIÓN REFLECTIVO:</strong><br>${reflectivo.descripcion.toUpperCase()}<br>`;
                }
                
                // 2. Ubicaciones del reflectivo
                if (reflectivo.ubicaciones && Array.isArray(reflectivo.ubicaciones) && reflectivo.ubicaciones.length > 0) {
                    html += `<strong>UBICACIONES REFLECTIVO:</strong><br>`;
                    reflectivo.ubicaciones.forEach((ubicacion) => {
                        let ubicacionStr = `• ${ubicacion.nombre || 'Sin nombre'}`;
                        if (ubicacion.observaciones) {
                            ubicacionStr += ` - ${ubicacion.observaciones}`;
                        }
                        html += `${ubicacionStr}<br>`;
                    });
                }
                
                // 3. Tallas por género desde array {genero, talla, cantidad}
                if (reflectivo.tallas && Array.isArray(reflectivo.tallas)) {
                    // Agrupar tallas por género
                    const tallasPorGenero = {};
                    reflectivo.tallas.forEach(tallaObj => {
                        if (tallaObj.cantidad > 0) {
                            const genero = tallaObj.genero || 'DESCONOCIDO';
                            if (!tallasPorGenero[genero]) {
                                tallasPorGenero[genero] = [];
                            }
                            tallasPorGenero[genero].push(`${tallaObj.talla}: ${tallaObj.cantidad}`);
                        }
                    });
                    
                    // Mostrar tallas agrupadas por género
                    Object.entries(tallasPorGenero).forEach(([genero, tallasArr]) => {
                        if (tallasArr.length > 0) {
                            const generoLabel = genero.charAt(0).toUpperCase() + genero.slice(1);
                            html += `<strong>TALLAS ${generoLabel}:</strong> ${tallasArr.join(', ')}<br>`;
                        }
                    });
                }
                
                // 4. Observaciones generales
                if (reflectivo.observaciones_generales) {
                    html += `<strong>OBSERVACIONES:</strong><br>${reflectivo.observaciones_generales}<br>`;
                }
                
            } else if (prenda.descripcion && prenda.descripcion !== '-') {
                // Usar la descripción completa de la BD (para prendas normales)
                const descripcionCompleta = prenda.descripcion.toUpperCase();
                
                // Formatear la descripción: si tiene saltos de línea, convertirlos a <br>
                const descripcionFormateada = descripcionCompleta.replace(/\n/g, '<br>');
                
                html += `<strong>DESCRIPCION:</strong><br>${descripcionFormateada}<br>`;
            } else if (prenda.descripcion_variaciones) {
                // Fallback: usar descripcion_variaciones si no hay descripción completa
                const descripcionVar = prenda.descripcion_variaciones;
                const partes = [];
                
                // Reflectivo
                const reflectivoMatch = descripcionVar.match(/Reflectivo:\s*(.+?)(?:\s*\||$)/i);
                if (reflectivoMatch) {
                    partes.push(`<strong style="margin-left: 1.5em;">•</strong> <strong style="color: #000;">Reflectivo:</strong> ${reflectivoMatch[1].trim().toUpperCase()}`);
                }
                
                // Bolsillos
                const bolsillosMatch = descripcionVar.match(/Bolsillos:\s*(.+?)(?:\s*\||$)/i);
                if (bolsillosMatch) {
                    partes.push(`<strong style="margin-left: 1.5em;">•</strong> <strong style="color: #000;">Bolsillos:</strong> ${bolsillosMatch[1].trim().toUpperCase()}`);
                }
                
                // Broche/Botón - SOLO si existe tipo_broche en los datos (label dinámico según el tipo)
                if (prenda.tipo_broche) {
                    const brocheMatch = descripcionVar.match(/Broche:\s*(.+?)(?:\s*\||$)/i);
                    if (brocheMatch) {
                        // Usar el tipo_broche como label (ej: "Botón", "Broche", etc.)
                        const tipoLabel = prenda.tipo_broche.toUpperCase();
                        const observacion = brocheMatch[1].trim().toUpperCase();
                        partes.push(`<strong style="margin-left: 1.5em;">•</strong> <strong style="color: #000;">${tipoLabel}:</strong> ${observacion}`);
                    }
                }
                
                if (partes.length > 0) {
                    html += '<strong>DESCRIPCION:</strong><br>';
                    html += partes.join('<br>') + '<br>';
                }
            }
            
            // 4. Tallas
            if (prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0) {
                const tallasFormateadas = [];
                prenda.tallas.forEach((tallaObj) => {
                    if (tallaObj.cantidad > 0) {
                        tallasFormateadas.push(`${tallaObj.genero}-${tallaObj.talla}: ${tallaObj.cantidad}`);
                    }
                });
                
                if (tallasFormateadas.length > 0) {
                    html += `<strong>Tallas:</strong> <span style="color: #d32f2f; font-weight: bold;">${tallasFormateadas.join(', ')}</span>`;
                }
            }
            
            descripcionHTML += `<div class="prenda-item" style="margin-bottom: 16px; line-height: 1.4; font-size: 0.75rem; color: #333;">
                ${html}
            </div>`;
            
            // Agregar separador solo entre prendas mostradas
            if (index < prendasActuales.length - 1) {
                descripcionHTML += `<hr style="border: none; border-top: 2px solid #ccc; margin: 16px 0;">`;
            }
        });
    }
    
    const descripcionText = document.getElementById('descripcion-text');
    if (descripcionText) {
        descripcionText.innerHTML = descripcionHTML;
    }
}

/**
 * Actualizar visibilidad de flechas de navegación
 */
function updateNavigationArrows() {
    const { todasLasPrendas, currentPage, prendasPorPagina } = window.prendasState;
    const totalPages = Math.ceil(todasLasPrendas.length / prendasPorPagina);
    
    const prevArrow = document.getElementById('prev-arrow');
    const nextArrow = document.getElementById('next-arrow');
    
    if (prevArrow) {
        prevArrow.style.display = currentPage > 0 ? 'block' : 'none';
    }
    
    if (nextArrow) {
        nextArrow.style.display = currentPage < totalPages - 1 ? 'block' : 'none';
    }
}

/**
 * Navegar a la página anterior
 */
window.prevPrendas = function() {
    if (window.prendasState.currentPage > 0) {
        window.prendasState.currentPage--;
        renderPrendasPage();
        updateNavigationArrows();
    }
};

/**
 * Navegar a la página siguiente
 */
window.nextPrendas = function() {
    const { todasLasPrendas, currentPage, prendasPorPagina } = window.prendasState;
    const totalPages = Math.ceil(todasLasPrendas.length / prendasPorPagina);
    
    if (currentPage < totalPages - 1) {
        window.prendasState.currentPage++;
        renderPrendasPage();
        updateNavigationArrows();
    }
};

/**
 * Escuchar el evento de apertura del modal
 */
document.addEventListener('DOMContentLoaded', function() {
    // Listener para cargar datos de la orden
    window.addEventListener('load-order-detail', function(event) {
        const orden = event.detail;
        renderOrderDetail(orden);

        // Cargar imágenes de la orden si el módulo está disponible
        if (typeof loadOrderImages === 'function') {
            try {
                loadOrderImages(orden.numero_pedido);
            } catch (err) {

            }
        }

        window.openOrderDetailModal();
    });
    // DEBUG: Contador de eventos
    window.loadOrderDetailLogoCount = 0;

    // Listener para cargar datos del logo/bordados de la orden
    window.addEventListener('load-order-detail-logo', function(event) {
        window.loadOrderDetailLogoCount++;

        const orden = event.detail;
        
        // Guardar el número de pedido en variable global para uso en galería
        // Asignar directamente a window para asegurar que esté disponible
        if (orden && orden.numero_pedido) {
            //  Limpiar el # del número de pedido si existe
            window.currentPedidoNumberLogo = orden.numero_pedido.replace('#', '');

        } else {

        }
        
        // Llenar los campos del modal de logo
        if (document.querySelector('#order-detail-modal-wrapper-logo')) {


            
            // Fecha
            if (orden.fecha_de_creacion_de_orden) {
                const fecha = new Date(orden.fecha_de_creacion_de_orden);
                const dayBox = document.querySelector('#order-detail-modal-wrapper-logo .day-box');
                const monthBox = document.querySelector('#order-detail-modal-wrapper-logo .month-box');
                const yearBox = document.querySelector('#order-detail-modal-wrapper-logo .year-box');
                if (dayBox) {
                    dayBox.textContent = String(fecha.getDate()).padStart(2, '0');

                }
                if (monthBox) {
                    monthBox.textContent = String(fecha.getMonth() + 1).padStart(2, '0');

                }
                if (yearBox) {
                    yearBox.textContent = fecha.getFullYear();

                }
            } else {

            }
            
            // Cliente
            const clienteSpan = document.querySelector('#order-detail-modal-wrapper-logo #cliente-value-logo');

            if (clienteSpan) {
                clienteSpan.textContent = orden.cliente || '-';

            }
            
            // Asesora
            const asesoraSpan = document.querySelector('#order-detail-modal-wrapper-logo #asesora-value-logo');

            if (asesoraSpan) {
                asesoraSpan.textContent = orden.asesora || '-';

            }
            
            // Forma de pago
            const formaPagoSpan = document.querySelector('#order-detail-modal-wrapper-logo #forma-pago-value-logo');

            if (formaPagoSpan) {
                formaPagoSpan.textContent = orden.forma_de_pago || '-';

            }
            
            // Número de orden (usando ID único del modal logo)
            const pedidoDiv = document.querySelector('#order-detail-modal-wrapper-logo #order-pedido-logo');

            if (pedidoDiv) {
                pedidoDiv.textContent = `#${orden.numero_pedido}`;

            }
            
            // Encargado de orden
            const encargadoSpan = document.querySelector('#order-detail-modal-wrapper-logo #encargado-value-logo');

            if (encargadoSpan) {
                encargadoSpan.textContent = orden.encargado_orden || '-';

            }
            
            // Prendas entregadas
            const prendasSpan = document.querySelector('#order-detail-modal-wrapper-logo #prendas-entregadas-value-logo');

            if (prendasSpan) {
                const cantidadPrendas = orden.prendas ? (Array.isArray(orden.prendas) ? orden.prendas.length : Object.keys(orden.prendas).length) : 0;
                prendasSpan.textContent = cantidadPrendas;

            }
            
            // Descripción
            const descripcionEl = document.querySelector('#order-detail-modal-wrapper-logo #descripcion-text-logo');

            if (descripcionEl) {
                descripcionEl.textContent = orden.descripcion || '-';

            }

            // Técnicas
            const tecnicasEl = document.querySelector('#order-detail-modal-wrapper-logo #logo-tecnicas');
            if (tecnicasEl) {
                const tecnicas = Array.isArray(orden.tecnicas)
                    ? orden.tecnicas
                    : (typeof orden.tecnicas === 'string' ? (JSON.parse(orden.tecnicas || '[]')) : []);
                tecnicasEl.textContent = (tecnicas && tecnicas.length > 0) ? tecnicas.join(', ') : '-';
            }

            // Observaciones técnicas
            const obsTecEl = document.querySelector('#order-detail-modal-wrapper-logo #logo-observaciones-tecnicas');
            if (obsTecEl) {
                obsTecEl.textContent = orden.observaciones_tecnicas || '-';
            }

            // Ubicaciones/Secciones (JSON array): [{seccion:"...", tallas:[...], ubicaciones:[...], observaciones:"..."}]
            const ubicacionesEl = document.querySelector('#order-detail-modal-wrapper-logo #logo-ubicaciones');
            if (ubicacionesEl) {
                //  BUSCAR EN "secciones" O "ubicaciones" (compatibilidad)
                let ubicaciones = [];
                try {
                    const seccionesData = orden.secciones || orden.ubicaciones;
                    ubicaciones = Array.isArray(seccionesData)
                        ? seccionesData
                        : (typeof seccionesData === 'string' ? (JSON.parse(seccionesData || '[]')) : []);
                } catch (e) {

                    ubicaciones = [];
                }

                if (ubicaciones && ubicaciones.length > 0) {
                    const lineas = ubicaciones.map((u) => {
                        //  MAPEAR NUEVA ESTRUCTURA: {seccion, tallas, ubicaciones, observaciones}
                        const prenda = u?.seccion ? String(u.seccion).toUpperCase() : (u?.ubicacion ? String(u.ubicacion).toUpperCase() : '');
                        const tallasArr = Array.isArray(u?.tallas) ? u.tallas : [];
                        const ubicacionesArr = Array.isArray(u?.ubicaciones) ? u.ubicaciones : (Array.isArray(u?.opciones) ? u.opciones : []);
                        const obs = u?.observaciones ? String(u.observaciones) : '';

                        let linea = '';
                        if (prenda) linea += prenda;
                        
                        // Mostrar tallas si existen
                        if (tallasArr.length > 0) {
                            const tallasStr = tallasArr.map(t => `${t.talla}: ${t.cantidad}`).join(', ');
                            linea += (linea ? ' - Tallas: ' : 'Tallas: ') + tallasStr;
                        }
                        
                        // Mostrar ubicaciones si existen
                        if (ubicacionesArr.length > 0) {
                            const ubicStr = ubicacionesArr.map(u => String(u).toUpperCase()).join(', ');
                            linea += (linea ? ' - ' : '') + ubicStr;
                        }
                        
                        if (obs) linea += (linea ? ' - Obs: ' : 'Obs: ') + obs;
                        return linea || '-';
                    });
                    ubicacionesEl.textContent = lineas.join('\n');
                } else {
                    ubicacionesEl.textContent = '-';
                }
            }

            

        } else {


            const wrapper = document.getElementById('order-detail-modal-wrapper-logo');

        }


        window.openOrderDetailModalLogo();

    });
    
    // Listener para abrir el modal
    window.addEventListener('open-modal', function(event) {
        if (event.detail === 'order-detail') {
            window.openOrderDetailModal();
        }
    });
    
    // Listener para cerrar el modal
    window.addEventListener('close-modal', function(event) {
        if (event.detail === 'order-detail') {

            window.closeOrderDetailModal();
        }
    });
    
    // Cerrar modal con ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const overlay = document.getElementById('modal-overlay');
            if (overlay && overlay.style.display !== 'none') {

                window.closeOrderDetailModal();
            }
        }
    });
    
    // Listeners para botones de navegación de prendas
    const prevArrow = document.getElementById('prev-arrow');
    const nextArrow = document.getElementById('next-arrow');
    
    if (prevArrow) {
        prevArrow.addEventListener('click', function(e) {
            e.preventDefault();

            window.prevPrendas();
        });
    }
    
    if (nextArrow) {
        nextArrow.addEventListener('click', function(e) {
            e.preventDefault();

            window.nextPrendas();
        });
    }
    

});

