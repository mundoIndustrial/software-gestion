/**
 * Order Detail Modal Manager para Registro de Órdenes
 * Maneja la apertura y cierre del modal de detalles de orden
 * SINCRONIZADO CON: pedidos-detail-modal.js (asesores)
 */

console.log(' [MODAL] Cargando order-detail-modal-manager.js');

/**
 * Abre el modal de detalle de la orden
 * Compatible con la estructura de asesores
 */
window.openOrderDetailModal = function(orderId) {
    console.log('%c [MODAL] Abriendo modal para orden: ' + orderId, 'color: blue; font-weight: bold; font-size: 14px;');
    
    // Cerrar el modal de logo si está abierto
    const modalWrapperLogo = document.getElementById('order-detail-modal-wrapper-logo');
    if (modalWrapperLogo) {
        modalWrapperLogo.style.display = 'none';
        console.log(' [MODAL] Modal de logo cerrado');
    }
    
    // Obtener el overlay
    let overlay = document.getElementById('modal-overlay');
    console.log(' [MODAL] Overlay encontrado:', !!overlay);
    
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
        console.log(' [MODAL] Overlay mostrado');
        
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
            console.log(' [MODAL] Wrapper mostrado');
        } else {
            console.error(' [MODAL] Wrapper no encontrado');
        }
    }
};

/**
 * Cierra el modal de detalle de la orden
 */
window.closeOrderDetailModal = function() {
    console.log('%c [MODAL] Cerrando modal', 'color: blue; font-weight: bold; font-size: 14px;');
    
    const overlay = document.getElementById('modal-overlay');
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    
    if (overlay) {
        overlay.style.display = 'none';
        console.log(' [MODAL] Overlay ocultado');
    }
    
    if (modalWrapper) {
        modalWrapper.style.display = 'none';
        console.log(' [MODAL] Wrapper ocultado');
        
        // Ocultar flechas del modal
        const arrowContainers = modalWrapper.querySelectorAll('.arrow-container');
        arrowContainers.forEach((container) => {
            container.style.display = 'none';
        });
        
        // Limpiar contenido del modal de costura
        const descripcionText = modalWrapper.querySelector('#descripcion-text');
        if (descripcionText) descripcionText.innerHTML = '';
        console.log('🧹 [MODAL] Contenido limpiado');
    }
};

/**
 * Cierra el modal al hacer click en el overlay (cierra ambos modales)
 */
window.closeModalOverlay = function() {
    console.log(' [MODAL] Click en overlay, cerrando...');
    window.closeOrderDetailModal();
    window.closeOrderDetailModalLogo();
};

/**
 * Abre el modal de detalle de la orden con LOGO (Bordados)
 */
window.openOrderDetailModalLogo = function(orderId) {
    console.log('%c [MODAL LOGO] Abriendo modal logo para orden: ' + orderId, 'color: red; font-weight: bold; font-size: 14px;');
    
    // Cerrar el modal de costura si está abierto
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    if (modalWrapper) {
        modalWrapper.style.display = 'none';
        console.log(' [MODAL LOGO] Modal de costura cerrado');
    }
    
    // Obtener el overlay
    let overlay = document.getElementById('modal-overlay');
    console.log(' [MODAL LOGO] Overlay encontrado:', !!overlay);
    
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
        console.log(' [MODAL LOGO] Overlay mostrado');
        
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
            console.log(' [MODAL LOGO] Wrapper mostrado');
        } else {
            console.error(' [MODAL LOGO] Wrapper no encontrado');
        }
    }
};

/**
 * Cierra el modal de detalle de la orden (Logo)
 */
window.closeOrderDetailModalLogo = function() {
    console.log('%c [MODAL LOGO] Cerrando modal logo', 'color: red; font-weight: bold; font-size: 14px;');
    
    const overlay = document.getElementById('modal-overlay');
    const modalWrapper = document.getElementById('order-detail-modal-wrapper-logo');
    
    if (overlay) {
        overlay.style.display = 'none';
        console.log(' [MODAL LOGO] Overlay ocultado');
    }
    
    if (modalWrapper) {
        modalWrapper.style.display = 'none';
        console.log(' [MODAL LOGO] Wrapper ocultado');
        
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
        console.log('🧹 [MODAL LOGO] Contenido limpiado');
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
    console.log(' [MODAL] Renderizando detalles de orden:', orden.numero_pedido);
    
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
                if (typeof prenda.reflectivo.cantidad_talla === 'string') {
                    prenda.reflectivo.cantidad_talla = JSON.parse(prenda.reflectivo.cantidad_talla);
                }
                if (typeof prenda.reflectivo.ubicaciones === 'string') {
                    prenda.reflectivo.ubicaciones = JSON.parse(prenda.reflectivo.ubicaciones);
                }
                console.log(` [REFLECTIVO] Prenda ${index + 1} tiene información de reflectivo:`, JSON.stringify(prenda.reflectivo, null, 2));
            }
            
            // Llenar galería de fotos de prenda
            if (prenda.fotos && Array.isArray(prenda.fotos)) {
                window.prendasGaleria[index] = prenda.fotos.filter(f => f); // Filtrar null/undefined
                console.log(` [GALERIA] Prenda ${index}: ${window.prendasGaleria[index]?.length || 0} fotos cargadas`);
            } else {
                window.prendasGaleria[index] = [];
            }
            
            // Llenar galería de fotos de tela
            if (prenda.tela_fotos && Array.isArray(prenda.tela_fotos)) {
                if (!window.telasGaleria[index]) {
                    window.telasGaleria[index] = {};
                }
                window.telasGaleria[index][0] = prenda.tela_fotos.filter(f => f); // Filtrar null/undefined
                console.log(` [GALERIA TELA] Prenda ${index}: ${window.telasGaleria[index][0]?.length || 0} fotos de tela cargadas`);
            }
        });
    }
    
    console.log(' [GALERIA] prendasGaleria y telasGaleria inicializadas');
    
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
    
    console.log(' [MODAL] Detalles renderizados');
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
        console.log(' [MODAL] Usando descripcion_prendas del controlador con paginación');
        console.log(' [DESCRIPCION COMPLETA]:\n' + descripcionPrendasCompleta);
        console.log(' [RAW DESCRIPCION]:', JSON.stringify(descripcionPrendasCompleta));
        console.log('----------------------------');
        
        let bloquesPrendas = [];
        
        //  SI EL HTML YA TIENE SPANS CON ESTILOS (contiene <span style), NO DIVIDIR
        // Simplemente usarlo como está
        if (descripcionPrendasCompleta.includes("<span style='font-size:") || 
            descripcionPrendasCompleta.includes('<span style="font-size:')) {
            console.log(' [MODAL] HTML con spans detectado, usando tal cual sin dividir');
            bloquesPrendas = [descripcionPrendasCompleta.trim()];
        } else if (descripcionPrendasCompleta.includes('PRENDA ')) {
            // Hay formato PRENDA X: - dividir por eso
            const partes = descripcionPrendasCompleta.split('PRENDA ');
            
            console.log(' [DEBUG SPLIT] Raw split:', partes);
            console.log(' [DEBUG SPLIT] Total partes:', partes.length);
            
            bloquesPrendas = partes
                .map((parte, idx) => {
                    if (idx === 0 && !parte.trim()) {
                        console.log(`  [PARTE ${idx}] DESCARTADA (empty al inicio)`);
                        return null;
                    }
                    const resultado = (idx > 0 ? 'PRENDA ' : '') + parte.trim();
                    console.log(`  [PARTE ${idx}] Guardada: "${resultado.substring(0, 50)}..."`);
                    return resultado;
                })
                .filter(b => {
                    // Filtrar bloques que sean solo HTML sin texto real
                    if (!b) return false;
                    
                    // Remover tags HTML para ver si hay contenido real
                    const sinHTML = b.replace(/<[^>]*>/g, '').trim();
                    if (!sinHTML || sinHTML.length < 5) {
                        console.log(`  ⊘ BLOQUE VACIO DESCARTADO: "${b.substring(0, 40)}..."`);
                        return false;
                    }
                    return true;
                });
            
            console.log(' [DEBUG SPLIT] Bloques finales:', bloquesPrendas.length);
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
        
        console.log(' [MODAL] Total bloques de prendas:', bloquesPrendas.length);
        console.log(' [MODAL] Bloques:', bloquesPrendas.map((b, i) => `[${i}]: "${b.substring(0, 40)}..."`));
        
        // Aplicar paginación
        const startIndex = currentPage * prendasPorPagina;
        const endIndex = startIndex + prendasPorPagina;
        const bloquesActuales = bloquesPrendas.slice(startIndex, endIndex);
        
        console.log(' [MODAL] Bloques actuales (página ' + (currentPage + 1) + '):', bloquesActuales.length);
        
        // Formatear bloques actuales con estilos
        const descripcionFormateada = bloquesActuales
            .map((bloque, bloqueIdx) => {
                console.log(` [BLOQUE ${bloqueIdx}] Contenido:\n${bloque}`);
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
                        console.log(`  ⊘ IGNORADA: ${linea}`);
                        continue;
                    }
                    
                    //  FILTRAR: Si hay "TALLAS:", ignorar "Talla:" (evitar duplicados)
                    if (/^Talla:/i.test(linea) && hayTallasYa) {
                        console.log(`  ⊘ IGNORADA (duplicado): ${linea}`);
                        continue;
                    }
                    
                    //  Detectar si hay TALLAS para filtrar duplicados después
                    if (/^TALLAS:/i.test(linea)) {
                        hayTallasYa = true;
                    }
                    
                    console.log(`  Línea ${i}: "${linea}"`);
                    
                    //  NEGRILLA en títulos: PRENDA X:, Color:, Tela:, Manga:, DESCRIPCION:, etc.
                    linea = linea.replace(/^(PRENDA \d+:)/g, '<strong>$1</strong>');
                    linea = linea.replace(/(Color:|Tela:|Manga:|DESCRIPCION:)/g, '<strong>$1</strong>');
                    
                    //  NEGRILLA en viñetas: • Reflectivo:, • Bolsillos:, • BOTÓN:, etc.
                    linea = linea.replace(/^(•\s+(Reflectivo:|Bolsillos:|BOTÓN:|[A-Z]+:))/g, '<strong>$1</strong>');
                    
                    //  ROJO en tallas: detectar tanto "Talla:" como "TALLAS:"
                    // Líneas como: "TALLAS: XS: 10, S: 20..." o "Talla: S: 10, M: 10"
                    if (/^TALLAS?:/i.test(linea)) {
                        linea = linea.replace(/^(TALLAS?:)\s+(.+)$/i, '$1 <span style="color: #d32f2f; font-weight: bold;">$2</span>');
                        console.log(`   APLICADO ESTILO ROJO A: ${linea.substring(0, 50)}...`);
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
        
        console.log(' [DEBUG REFLECTIVO] todasLasPrendas length:', todasLasPrendas.length);
        console.log(' [DEBUG REFLECTIVO] prendasActualesReflectivo length:', prendasActualesReflectivo.length);
        console.log(' [DEBUG REFLECTIVO] prendasActualesReflectivo:', JSON.stringify(prendasActualesReflectivo, null, 2));
        
        prendasActualesReflectivo.forEach((prenda, index) => {
            console.log(` [DEBUG REFLECTIVO] Prenda ${index} - nombre: ${prenda.nombre}, tiene reflectivo: ${!!prenda.reflectivo}`);
            if (prenda.reflectivo) {
                console.log(' [REFLECTIVO] Renderizando información de reflectivo para prenda', prenda.numero);
                
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
                
                // 3. Tallas por género (el género ya está incluido en la etiqueta)
                if (reflectivo.cantidad_talla && typeof reflectivo.cantidad_talla === 'object') {
                    reflectivoContent += '<br>';
                    Object.entries(reflectivo.cantidad_talla).forEach(([genero, tallas]) => {
                        if (typeof tallas === 'object') {
                            const tallasStr = Object.entries(tallas)
                                .filter(([_, cantidad]) => cantidad > 0)
                                .map(([talla, cantidad]) => `${talla}: ${cantidad}`)
                                .join(', ');
                            
                            if (tallasStr) {
                                const generoLabel = genero.charAt(0).toUpperCase() + genero.slice(1);
                                reflectivoContent += `<strong>TALLAS ${generoLabel}:</strong> <span style="color: #d32f2f; font-weight: bold;">${tallasStr}</span><br>`;
                            }
                        }
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
        
        console.log(` [MODAL] Página ${currentPage + 1}/${totalPaginas}`);
        
    } else {
        // FALLBACK: Generar descripción dinámica desde prendas (lógica original)
        console.log(' [MODAL] Usando lógica de construcción dinámica (descripcion_prendas vacía)');
        
        // Calcular índices de inicio y fin
        const startIndex = currentPage * prendasPorPagina;
        const endIndex = startIndex + prendasPorPagina;
        const prendasActuales = todasLasPrendas.slice(startIndex, endIndex);
        
        // Generar descripción dinámica para pedidos
        prendasActuales.forEach((prenda, index) => {
            console.log(' [PRENDA] Datos completos de prenda:', JSON.stringify(prenda, null, 2));
            console.log(' [PRENDA] Keys disponibles:', Object.keys(prenda));
            console.log(' [PRENDA] Color:', prenda.color);
            console.log(' [PRENDA] Tela:', prenda.tela);
            console.log(' [PRENDA] Tipo manga:', prenda.tipo_manga);
            console.log(' [PRENDA] Cantidad talla:', prenda.cantidad_talla);
            
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
                console.log(' [REFLECTIVO] Renderizando información de reflectivo para prenda', prenda.numero);
                
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
                
                // 3. Tallas por género (el género ya está incluido en la etiqueta)
                if (reflectivo.cantidad_talla && typeof reflectivo.cantidad_talla === 'object') {
                    Object.entries(reflectivo.cantidad_talla).forEach(([genero, tallas]) => {
                        if (typeof tallas === 'object') {
                            const tallasStr = Object.entries(tallas)
                                .filter(([_, cantidad]) => cantidad > 0)
                                .map(([talla, cantidad]) => `${talla}: ${cantidad}`)
                                .join(', ');
                            
                            if (tallasStr) {
                                const generoLabel = genero.charAt(0).toUpperCase() + genero.slice(1);
                                html += `<strong>TALLAS ${generoLabel}:</strong> ${tallasStr}<br>`;
                            }
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
            if (prenda.cantidad_talla && prenda.cantidad_talla !== '-') {
                try {
                    const tallas = typeof prenda.cantidad_talla === 'string' 
                        ? JSON.parse(prenda.cantidad_talla) 
                        : prenda.cantidad_talla;
                    
                    const tallasFormateadas = [];
                    for (const [talla, cantidad] of Object.entries(tallas)) {
                        if (cantidad > 0) {
                            tallasFormateadas.push(`${talla}: ${cantidad}`);
                        }
                    }
                    
                    if (tallasFormateadas.length > 0) {
                        html += `<strong>Tallas:</strong> <span style="color: #d32f2f; font-weight: bold;">${tallasFormateadas.join(', ')}</span>`;
                    }
                } catch (e) {
                    html += `<strong>Tallas:</strong> <span style="color: #d32f2f; font-weight: bold;">${prenda.cantidad_talla}</span>`;
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
    console.log('%c [MODAL] DOM cargado, registrando listeners', 'color: green; font-weight: bold; font-size: 14px;');
    
    // Listener para cargar datos de la orden
    window.addEventListener('load-order-detail', function(event) {
        console.log('%c [MODAL] Evento load-order-detail recibido', 'color: orange; font-weight: bold; font-size: 14px;');
        const orden = event.detail;
        renderOrderDetail(orden);

        // Cargar imágenes de la orden si el módulo está disponible
        if (typeof loadOrderImages === 'function') {
            try {
                loadOrderImages(orden.numero_pedido);
            } catch (err) {
                console.warn(' Error cargando imágenes de la orden:', err);
            }
        }

        window.openOrderDetailModal();
    });
    // DEBUG: Contador de eventos
    window.loadOrderDetailLogoCount = 0;

    // Listener para cargar datos del logo/bordados de la orden
    window.addEventListener('load-order-detail-logo', function(event) {
        window.loadOrderDetailLogoCount++;
        console.log('%c [MODAL LOGO] Evento load-order-detail-logo recibido (#' + window.loadOrderDetailLogoCount + ')', 'color: red; font-weight: bold; font-size: 14px;');
        console.log(' [MODAL LOGO] event:', event);
        console.log(' [MODAL LOGO] event.detail:', event.detail);
        console.log(' [MODAL LOGO] event.detail.numero_pedido:', event.detail?.numero_pedido);
        console.log(' [MODAL LOGO] Tipo de event.detail:', typeof event.detail);
        
        const orden = event.detail;
        
        // Guardar el número de pedido en variable global para uso en galería
        // Asignar directamente a window para asegurar que esté disponible
        if (orden && orden.numero_pedido) {
            //  Limpiar el # del número de pedido si existe
            window.currentPedidoNumberLogo = orden.numero_pedido.replace('#', '');
            console.log(' [MODAL LOGO] Número de pedido guardado en variable global:', window.currentPedidoNumberLogo);
        } else {
            console.error(' [MODAL LOGO] No se pudo obtener numero_pedido de orden:', orden);
        }
        
        // Llenar los campos del modal de logo
        if (document.querySelector('#order-detail-modal-wrapper-logo')) {
            console.log(' [MODAL LOGO] Modal wrapper encontrado en DOM');
            console.log(' [MODAL LOGO] Datos de orden completos:', orden);
            
            // Fecha
            if (orden.fecha_de_creacion_de_orden) {
                const fecha = new Date(orden.fecha_de_creacion_de_orden);
                const dayBox = document.querySelector('#order-detail-modal-wrapper-logo .day-box');
                const monthBox = document.querySelector('#order-detail-modal-wrapper-logo .month-box');
                const yearBox = document.querySelector('#order-detail-modal-wrapper-logo .year-box');
                
                console.log(' [MODAL LOGO] Fecha:', {
                    original: orden.fecha_de_creacion_de_orden,
                    parsed: fecha,
                    day: dayBox ? dayBox.textContent : 'no encontrado',
                    month: monthBox ? monthBox.textContent : 'no encontrado',
                    year: yearBox ? yearBox.textContent : 'no encontrado'
                });
                
                if (dayBox) {
                    dayBox.textContent = String(fecha.getDate()).padStart(2, '0');
                    console.log(' Día establecido:', dayBox.textContent);
                }
                if (monthBox) {
                    monthBox.textContent = String(fecha.getMonth() + 1).padStart(2, '0');
                    console.log(' Mes establecido:', monthBox.textContent);
                }
                if (yearBox) {
                    yearBox.textContent = fecha.getFullYear();
                    console.log(' Año establecido:', yearBox.textContent);
                }
            } else {
                console.warn(' [MODAL LOGO] No hay fecha_de_creacion_de_orden');
            }
            
            // Cliente
            const clienteSpan = document.querySelector('#order-detail-modal-wrapper-logo #cliente-value-logo');
            console.log(' [MODAL LOGO] Cliente span encontrado:', !!clienteSpan, 'valor:', orden.cliente);
            if (clienteSpan) {
                clienteSpan.textContent = orden.cliente || '-';
                console.log(' Cliente establecido:', clienteSpan.textContent);
            }
            
            // Asesora
            const asesoraSpan = document.querySelector('#order-detail-modal-wrapper-logo #asesora-value-logo');
            console.log(' [MODAL LOGO] Asesora span encontrado:', !!asesoraSpan, 'valor:', orden.asesora);
            if (asesoraSpan) {
                asesoraSpan.textContent = orden.asesora || '-';
                console.log(' Asesora establecida:', asesoraSpan.textContent);
            }
            
            // Forma de pago
            const formaPagoSpan = document.querySelector('#order-detail-modal-wrapper-logo #forma-pago-value-logo');
            console.log(' [MODAL LOGO] Forma de pago span encontrado:', !!formaPagoSpan, 'valor:', orden.forma_de_pago);
            if (formaPagoSpan) {
                formaPagoSpan.textContent = orden.forma_de_pago || '-';
                console.log(' Forma de pago establecida:', formaPagoSpan.textContent);
            }
            
            // Número de orden (usando ID único del modal logo)
            const pedidoDiv = document.querySelector('#order-detail-modal-wrapper-logo #order-pedido-logo');
            console.log('🔢 [MODAL LOGO] Pedido div encontrado:', !!pedidoDiv, 'valor:', orden.numero_pedido);
            if (pedidoDiv) {
                pedidoDiv.textContent = `#${orden.numero_pedido}`;
                console.log(' Número de pedido establecido:', pedidoDiv.textContent);
            }
            
            // Encargado de orden
            const encargadoSpan = document.querySelector('#order-detail-modal-wrapper-logo #encargado-value-logo');
            console.log(' [MODAL LOGO] Encargado span encontrado:', !!encargadoSpan, 'valor:', orden.encargado_orden);
            if (encargadoSpan) {
                encargadoSpan.textContent = orden.encargado_orden || '-';
                console.log(' Encargado establecido:', encargadoSpan.textContent);
            }
            
            // Prendas entregadas
            const prendasSpan = document.querySelector('#order-detail-modal-wrapper-logo #prendas-entregadas-value-logo');
            console.log(' [MODAL LOGO] Prendas span encontrado:', !!prendasSpan, 'prendas:', orden.prendas);
            if (prendasSpan) {
                const cantidadPrendas = orden.prendas ? (Array.isArray(orden.prendas) ? orden.prendas.length : Object.keys(orden.prendas).length) : 0;
                prendasSpan.textContent = cantidadPrendas;
                console.log(' Prendas entregadas establecidas:', cantidadPrendas);
            }
            
            // Descripción
            const descripcionEl = document.querySelector('#order-detail-modal-wrapper-logo #descripcion-text-logo');
            console.log(' [MODAL LOGO] Descripción elemento encontrado:', !!descripcionEl, 'valor:', orden.descripcion);
            if (descripcionEl) {
                descripcionEl.textContent = orden.descripcion || '-';
                console.log(' [MODAL LOGO] Descripción cargada:', orden.descripcion);
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
                    console.error(' [MODAL LOGO] Error parseando secciones/ubicaciones:', e);
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

            
            console.log(' [MODAL LOGO] Todos los datos del modal de logo llenados');
        } else {
            console.error(' [MODAL LOGO] Modal wrapper NO encontrado en DOM');
            console.log(' [MODAL LOGO] Buscando elemento #order-detail-modal-wrapper-logo');
            const wrapper = document.getElementById('order-detail-modal-wrapper-logo');
            console.log('   Resultado directo por ID:', !!wrapper);
        }

        console.log(' [MODAL LOGO] Llamando a openOrderDetailModalLogo()');
        window.openOrderDetailModalLogo();
        console.log(' [MODAL LOGO] openOrderDetailModalLogo() completada');
    });
    
    // Listener para abrir el modal
    window.addEventListener('open-modal', function(event) {
        console.log('%c [MODAL] Evento open-modal recibido', 'color: purple; font-weight: bold; font-size: 14px;');
        console.log('   - detail:', event.detail);
        
        if (event.detail === 'order-detail') {
            console.log('%c [MODAL] Detail es "order-detail", abriendo...', 'color: green; font-weight: bold;');
            window.openOrderDetailModal();
        }
    });
    
    // Listener para cerrar el modal
    window.addEventListener('close-modal', function(event) {
        if (event.detail === 'order-detail') {
            console.log(' [MODAL] Evento close-modal recibido');
            window.closeOrderDetailModal();
        }
    });
    
    // Cerrar modal con ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const overlay = document.getElementById('modal-overlay');
            if (overlay && overlay.style.display !== 'none') {
                console.log(' [MODAL] ESC presionado, cerrando modal');
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
            console.log(' [MODAL] Flecha anterior presionada');
            window.prevPrendas();
        });
    }
    
    if (nextArrow) {
        nextArrow.addEventListener('click', function(e) {
            e.preventDefault();
            console.log(' [MODAL] Flecha siguiente presionada');
            window.nextPrendas();
        });
    }
    
    console.log(' [MODAL] Listeners registrados');
});
