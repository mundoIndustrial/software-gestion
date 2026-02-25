/**
 * EppMenuHandlers - Gestión de eventos para menú de EPP (3 puntos)
 * Maneja clicks en el menú contextual, edición y eliminación de EPP
 */

window.EppMenuHandlers = {
    _inicializado: false, // Flag para evitar múltiples inicializaciones
    
    /**
     * Inicializar event listeners para menús de EPP
     */
    inicializar() {
        // Evitar inicializar múltiples veces
        if (this._inicializado) {
            return;
        }
        
        this._inicializado = true;
        
        // Usar event delegation para que funcione con elementos agregados dinámicamente
        document.addEventListener('click', (e) => {
            const btnMenu = e.target.closest('.btn-menu-epp');
            const btnEditar = e.target.closest('.btn-editar-epp');
            const btnEliminar = e.target.closest('.btn-eliminar-epp');
            const esSubmenu = e.target.closest('.submenu-epp');

            // Clic en botón de 3 puntos
            if (btnMenu) {
                e.stopPropagation();
                this._toggleMenu(btnMenu);
                return; // Importante: evitar que se ejecute el cierre de menús
            }

            // Clic en botón EDITAR
            if (btnEditar) {
                e.stopPropagation();
                this._editarEpp(btnEditar);
                return; // Importante: evitar que se ejecute el cierre de menús
            }

            // Clic en botón ELIMINAR
            if (btnEliminar) {
                e.stopPropagation();
                this._eliminarEpp(btnEliminar);
                return; // Importante: evitar que se ejecute el cierre de menús
            }

            // Si se hace clic en el submenu, no cerrar
            if (esSubmenu) {
                return;
            }

            // Cerrar menú si se hace clic en cualquier otro lugar
            this._cerrarTodosLosMenus();
        });

    },

    /**
     * Toggle menú de EPP
     */
    _toggleMenu(btn) {
        const itemId = btn.dataset.itemId;

        // Obtener el submenu desde el item-epp-card (contenedor principal)
        const itemCard = btn.closest('.item-epp-card') || btn.closest('.item-epp');
        const submenu = itemCard ? itemCard.querySelector('.submenu-epp') : null;
        
        if (!submenu) {
            return;
        }

        // Posicionamiento
        const btnRect = btn.getBoundingClientRect();
        const submenuRect = submenu.getBoundingClientRect();
        const btnParent = btn.parentElement;
        const btnParentRect = btnParent.getBoundingClientRect();

        // Cerrar otros menús primero
        document.querySelectorAll('.submenu-epp').forEach(menu => {
            if (menu !== submenu) {
                menu.style.display = 'none';
            }
        });

        // Mostrar/ocultar este menú
        const isHidden = submenu.style.display === 'none' || submenu.style.display === '';
        if (isHidden) {
            submenu.style.display = 'flex';
            submenu.style.flexDirection = 'column';
        } else {
            submenu.style.display = 'none';
        }
    },

    /**
     * Editar EPP - Intenta obtener datos del DOM, gestionItemsUI o BD
     */
    _editarEpp(btn) {
        console.log(' [_editarEpp] ===== CLICK EN EDITAR =====');
        const itemId = btn.dataset.itemId;
        const tipo = btn.dataset.tipo || 'epp'; // Obtener tipo desde data-tipo attribute
        console.log(' [_editarEpp] itemId:', itemId, 'tipo:', tipo);
        
        // Obtener el item EPP
        const item = btn.closest('.item-epp') || btn.closest('.item-epp-card');
        if (!item) {
            console.error(' [_editarEpp] No se encontró elemento .item-epp o .item-epp-card');
            return;
        }
        console.log(' [_editarEpp] Elemento item encontrado:', item.className);

        // Obtener pedido_epp_id del DOM si está disponible
        const pedidoEppId = item.dataset.pedidoEppId || itemId;
        console.log(' [_editarEpp] pedidoEppId:', pedidoEppId);

        // OPCIÓN 1: Intentar obtener del DOM (tarjeta)
        console.log(' [_editarEpp] OPCIÓN 1: Extrayendo datos del DOM...');
        let eppData = this._extraerDatosDelDOM(item, itemId);
        console.log(' [_editarEpp] Datos del DOM:', eppData);
        console.log(' [_editarEpp] Cantidad de campos:', Object.keys(eppData).length);
        
        // OPCIÓN 2: Si no está completo, buscar en window.gestionItemsUI
        if (!eppData || Object.keys(eppData).length < 3) {
            console.log(' [_editarEpp] OPCIÓN 1 INCOMPLETA - Intentando OPCIÓN 2: gestionItemsUI...');
            eppData = this._extraerDatosDelGestionItemsUI(itemId) || eppData;
            console.log(' [_editarEpp] Datos de gestionItemsUI:', eppData);
        }

        // OPCIÓN 3: Si aún no tiene datos, traer de la DB
        if (!eppData || Object.keys(eppData).length < 3) {
            console.log(' [_editarEpp] OPCIÓN 2 INCOMPLETA - Intentando OPCIÓN 3: BD...');
            this._traerEPPDelaBD(itemId, item);
            return; // El resto de la lógica se ejecutará cuando lleguen los datos de la DB
        }

        // Agregar pedido_epp_id a los datos
        if (eppData && !eppData.pedido_epp_id) {
            eppData.pedido_epp_id = pedidoEppId;
        }
        eppData.tipo = tipo; // Incluir tipo en los datos
        console.log(' [_editarEpp] Datos finales con pedido_epp_id:', eppData);

        // Si ya tenemos los datos, proceder a editar
        console.log(' [_editarEpp] Datos completos encontrados - Procediendo a editar (tipo: ' + tipo + ')');
        
        // Abrir el modal correcto según el tipo
        if (tipo === 'prenda') {
            this._procederAEditarPrenda(eppData, itemId, item);
        } else {
            this._procederAEditarEPP(eppData, itemId, item);
        }
    },

    /**
     * Extraer datos del DOM (tarjeta renderizada)
     */
    _extraerDatosDelDOM(item, itemId) {
        console.log('🟪 [_extraerDatosDelDOM] Extrayendo datos del DOM para itemId:', itemId);
        try {
            let nombre = '';
            let cantidad = 0;
            let observaciones = '';
            let valor_unitario = null;
            let total = null;
            
            // Detectar si es estructura de tabla o estructura div (item-epp-card)
            const cells = item.querySelectorAll('td');
            console.log('🟪 [_extraerDatosDelDOM] Celdas encontradas:', cells.length);
            
            if (cells.length >= 7) {
                // ESTRUCTURA ANTIGUA: tabla con <td> elementos
                console.log('🟪 [_extraerDatosDelDOM] Usando estructura de TABLA');
                
                // Extraer nombre de la columna DESCRIPCIÓN (celda 2)
                const descCell = cells[2];
                
                // Intentar encontrar el span con el nombre (caso normal)
                let nombreSpan = descCell.querySelector('span');
                if (nombreSpan) {
                    nombre = nombreSpan.textContent?.trim() || '';
                    console.log('🟪 [_extraerDatosDelDOM] Nombre encontrado en span:', nombre);
                } else {
                    // Si no hay span, buscar en el div (caso con imagen)
                    const divContainer = descCell.querySelector('div');
                    if (divContainer) {
                        // El nombre podría estar después de la imagen
                        const textNodes = Array.from(divContainer.childNodes).filter(node => 
                            node.nodeType === Node.TEXT_NODE && node.textContent?.trim()
                        );
                        if (textNodes.length > 0) {
                            nombre = textNodes[0].textContent.trim();
                        } else {
                            // Último recurso: tomar todo el texto y limpiar
                            const fullText = divContainer.textContent || '';
                            // Quitar nombre de imagen si está presente
                            nombre = fullText.replace(/\s*\([^)]*\)\s*$/, '').trim();
                        }
                        console.log('🟪 [_extraerDatosDelDOM] Nombre encontrado en div:', nombre);
                    } else {
                        // Fallback: tomar todo el texto de la celda
                        nombre = descCell.textContent?.trim() || '';
                        console.log('🟪 [_extraerDatosDelDOM] Nombre encontrado en fallback:', nombre);
                    }
                }
                
                // Extraer cantidad de la columna CANTIDAD (celda 3)
                cantidad = parseInt(cells[3].textContent?.trim()) || 0;
                console.log('🟪 [_extraerDatosDelDOM] Cantidad encontrada:', cantidad);
                
                // Extraer observaciones de la columna OBSERVACIONES (celda 4)
                observaciones = cells[4].textContent?.trim() || '';
                console.log('🟪 [_extraerDatosDelDOM] Observaciones encontradas:', observaciones);
                
                // Extraer valor unitario de la columna V. UNITARIO (celda 5)
                const vuText = cells[5].textContent?.trim() || '';
                if (vuText && vuText !== 'N/A') {
                    valor_unitario = parseFloat(vuText.replace(/[^0-9.\-]/g, '')) || null;
                }
                console.log('🟪 [_extraerDatosDelDOM] Valor unitario encontrado:', valor_unitario);
                
                // Extraer total de la columna TOTAL (celda 6)
                const totalText = cells[6].textContent?.trim() || '';
                if (totalText) {
                    const totalSpan = cells[6].querySelector('span');
                    if (totalSpan) {
                        total = parseFloat(totalSpan.textContent?.replace(/[^0-9.\-]/g, '') || 0);
                    } else {
                        total = parseFloat(totalText.replace(/[^0-9.\-]/g, '') || 0);
                    }
                }
                console.log('🟪 [_extraerDatosDelDOM] Total encontrado:', total);
            } else {
                // ESTRUCTURA NUEVA: div (item-epp-card)
                console.log('🟪 [_extraerDatosDelDOM] Usando estructura de DIV (item-epp-card)');
                
                // Extraer nombre de <h4>
                const h4Element = item.querySelector('h4');
                if (h4Element) {
                    nombre = h4Element.textContent?.trim() || '';
                    console.log('🟪 [_extraerDatosDelDOM] Nombre encontrado en <h4>:', nombre);
                } else {
                    console.warn('🟪 [_extraerDatosDelDOM] No se encontró <h4> para el nombre');
                }
                
                // Extraer Cantidad y Observaciones de la grid de información
                // Estructura: <div style="display: grid; ...">
                //   <div><p>Cantidad</p><p>8</p></div>
                //   <div><p>Observaciones</p><p>-</p></div>
                // </div>
                const gridDivs = item.querySelectorAll('div[style*="grid"]');
                if (gridDivs.length > 0) {
                    // Buscar el grid que contiene Cantidad y Observaciones
                    for (const gridDiv of gridDivs) {
                        const columnDivs = gridDiv.querySelectorAll(':scope > div');
                        for (const col of columnDivs) {
                            const paragraphs = col.querySelectorAll('p');
                            if (paragraphs.length >= 2) {
                                const label = paragraphs[0].textContent?.trim().toLowerCase() || '';
                                const value = paragraphs[1].textContent?.trim() || '';
                                
                                if (label.includes('cantidad')) {
                                    cantidad = parseInt(value) || 0;
                                    console.log('🟪 [_extraerDatosDelDOM] Cantidad encontrada en grid:', cantidad);
                                } else if (label.includes('observaciones')) {
                                    observaciones = value === '-' ? '' : value;
                                    console.log('🟪 [_extraerDatosDelDOM] Observaciones encontradas en grid:', observaciones);
                                }
                            }
                        }
                    }
                } else {
                    console.warn('🟪 [_extraerDatosDelDOM] No se encontraron grids de información');
                }
            }

            // IMPORTANTE: Priorizar imágenes del stateManager (si existen = cambios pendientes)
            // Si no hay en stateManager, extraer del DOM (imágenes originales)
            let imagenes = [];
            
            if (window.eppStateManager) {
                console.log('🟪 [_extraerDatosDelDOM] DEBUG - Variable nombre antes de stateManager:', nombre);
                const imagenesState = window.eppStateManager.getImagenesSubidas();
                console.log('🟪 [_extraerDatosDelDOM] DEBUG - ImágenesState:', imagenesState);
                if (imagenesState && imagenesState.length > 0) {
                    // Usar imágenes del stateManager (reflejan eliminaciones)
                    imagenes = imagenesState;
                    console.log('🟪 [_extraerDatosDelDOM] Imágenes de stateManager:', imagenes.length);
                    console.log('🟪 [_extraerDatosDelDOM] DEBUG - Variable nombre después de asignar stateManager:', nombre);
                } else {
                    // Si stateManager está vacío, obtener del DOM
                    const todosLosImg = item.querySelectorAll('img');
                    todosLosImg.forEach((img, idx) => {
                        if (img.src && !img.src.includes('placeholder')) {
                            imagenes.push({
                                id: `${itemId}-img-${idx}`,
                                url: img.src,
                                ruta_web: img.src,
                                nombre: img.alt || `imagen-${idx}`
                            });
                        }
                    });
                    console.log('🟪 [_extraerDatosDelDOM] Imágenes del DOM (vacío stateManager):', imagenes.length);
                    console.log('🟪 [_extraerDatosDelDOM] DEBUG - Variable nombre después de procesar imágenes:', nombre);
                }
            } else {
                // Fallback: si no hay stateManager, extraer del DOM
                const todosLosImg = item.querySelectorAll('img');
                todosLosImg.forEach((img, idx) => {
                    if (img.src && !img.src.includes('placeholder')) {
                        imagenes.push({
                            id: `${itemId}-img-${idx}`,
                            url: img.src,
                            ruta_web: img.src,
                            nombre: img.alt || `imagen-${idx}`
                        });
                    }
                });
                console.log('🟪 [_extraerDatosDelDOM] Imágenes del DOM (sin stateManager):', imagenes.length);
            }

            const datos = {
                epp_id: parseInt(itemId),
                nombre: nombre,
                cantidad: cantidad,
                observaciones: observaciones,
                imagenes: imagenes,
                valor_unitario: valor_unitario,
                total: total,
                esEdicion: true  // Indicador de que es edición
            };
            console.log('🟪 [_extraerDatosDelDOM] DEBUG - Variable nombre antes de asignar:', nombre);
            console.log('🟪 [_extraerDatosDelDOM] Datos finales:', datos);
            return datos;
        } catch (error) {
            console.error('🟪 [_extraerDatosDelDOM] Error:', error);
            return null;
        }
    },

    /**
     * Extraer datos de window.gestionItemsUI
     */
    _extraerDatosDelGestionItemsUI(itemId) {
        console.log('🟩 [_extraerDatosDelGestionItemsUI] Buscando itemId:', itemId);
        try {
            if (!window.gestionItemsUI || !window.gestionItemsUI.ordenItems) {
                console.log('🟩 [_extraerDatosDelGestionItemsUI] window.gestionItemsUI no disponible o sin ordenItems');
                return null;
            }

            console.log('🟩 [_extraerDatosDelGestionItemsUI] Buscando en', window.gestionItemsUI.ordenItems.length, 'items');
            // Buscar el EPP en los items ordenados
            const item = window.gestionItemsUI.ordenItems.find(i => i.epp_id === parseInt(itemId));
            
            if (item) {
                console.log('🟩 [_extraerDatosDelGestionItemsUI] Item encontrado:', item);
                return item;
            }
            
            console.log('🟩 [_extraerDatosDelGestionItemsUI] Item NO encontrado en gestionItemsUI');
            return null;
        } catch (error) {
            console.error('🟩 [_extraerDatosDelGestionItemsUI] Error:', error);
            return null;
        }
    },

    /**
     * Traer datos del EPP desde la BD
     */
    async _traerEPPDelaBD(itemId, item) {
        console.log(' [_traerEPPDelaBD] Obteniendo datos de BD para itemId:', itemId);
        try {
            console.log(' [_traerEPPDelaBD] Llamando a /api/epp/' + itemId);
            const response = await fetch(`/api/epp/${itemId}`);
            
            if (!response.ok) {
                console.error(' [_traerEPPDelaBD] Error en response:', response.status, response.statusText);
                return;
            }

            const data = await response.json();
            console.log(' [_traerEPPDelaBD] Datos recibidos de BD:', data);
            
            // Proceder con los datos de la BD
            const eppData = data.data || data;
            console.log(' [_traerEPPDelaBD] EPP Data extraída:', eppData);
            this._procederAEditarEPP(eppData, itemId, item);
            
        } catch (error) {
            console.error(' [_traerEPPDelaBD] Error:', error);
            alert('Error al cargar los datos del EPP');
        }
    },

    /**
     * Proceder a editar el EPP con los datos obtenidos
     */
    _procederAEditarEPP(eppData, itemId, item) {
        console.log(' [_procederAEditarEPP] ===== INICIANDO TRANSFORMACIÓN =====');
        console.log(' [_procederAEditarEPP] Datos recibidos:', eppData);
        
        // Transformar datos para que sean compatibles con editarEPPAgregado
        // Los datos pueden venir de diferentes fuentes y tienen estructuras diferentes
        const eppDataTransformado = {
            epp_id: eppData.epp_id || eppData.id,
            id: eppData.epp_id || eppData.id,
            nombre_epp: eppData.nombre_epp || eppData.nombre_completo || eppData.nombre || '',
            nombre: eppData.nombre_epp || eppData.nombre_completo || eppData.nombre || '',
            cantidad: eppData.cantidad || 1,
            observaciones: eppData.observaciones || '',
            imagenes: eppData.imagenes || [],
            imagen: eppData.imagen || null,
            valor_unitario: (eppData.valor_unitario !== undefined ? eppData.valor_unitario : (eppData.valorUnitario !== undefined ? eppData.valorUnitario : null)),
            total: (eppData.total !== undefined ? eppData.total : null),
        };
        console.log(' [_procederAEditarEPP] Datos transformados:', eppDataTransformado);
        
        // Crear evento personalizado con los datos
        const evento = new CustomEvent('epp:editar', {
            detail: {
                itemId,
                eppData: eppDataTransformado,
                elemento: item
            }
        });
        console.log(' [_procederAEditarEPP] Despachando evento personalizado "epp:editar"');
        document.dispatchEvent(evento);

        // Cerrar menú primero
        const submenu = item.querySelector('.submenu-epp');
        if (submenu) submenu.style.display = 'none';
        console.log(' [_procederAEditarEPP] Menú cerrado');

        // Usar setTimeout para asegurar que la función esté disponible
        // Esto permite que el Blade template se cargue
        console.log(' [_procederAEditarEPP] Esperando 100ms para llamar a editarEPPAgregado...');
        setTimeout(() => {
            if (typeof window.editarEPPAgregado === 'function') {
                console.log('🟥 [_procederAEditarEPP]  LLAMANDO A window.editarEPPAgregado() ');
                console.log('🟥 [_procederAEditarEPP] Con datos:', eppDataTransformado);
                window.editarEPPAgregado(eppDataTransformado);
            } else {
                console.warn('🟥 [_procederAEditarEPP]  window.editarEPPAgregado NO disponible');
                console.warn('🟥 [_procederAEditarEPP] Funciones disponibles en window:', Object.keys(window).filter(k => k.includes('edit') || k.includes('epp')));
                if (window.eppService && typeof window.eppService.abrirModalEditarEPP === 'function') {
                    console.log('🟥 [_procederAEditarEPP] Usando servicio antiguo para editar EPP');

                    window.eppService.abrirModalEditarEPP(eppDataTransformado);
                } else {
                    console.error('[EppMenuHandlers]  No hay función disponible para editar EPP');
                }
            }
        }, 100);
    },

    /**
     * Proceder a editar prenda - similar a EPP pero abre modal de prenda
     */
    _procederAEditarPrenda(prendaData, itemId, item) {
        console.log(' [_procederAEditarPrenda] ===== INICIANDO EDICIÓN DE PRENDA =====');
        console.log(' [_procederAEditarPrenda] Datos recibidos:', prendaData);
        
        // Transformar datos para que sean compatibles con el modal de prenda
        const prendaDataTransformado = {
            prenda_id: prendaData.prenda_id || prendaData.epp_id || prendaData.id,
            id: prendaData.prenda_id || prendaData.epp_id || prendaData.id,
            descripcion: prendaData.descripcion || prendaData.nombre || '',
            nombre: prendaData.descripcion || prendaData.nombre || '',
            cantidad: prendaData.cantidad || 1,
            observaciones: prendaData.observaciones || '',
            imagenes: prendaData.imagenes || [],
            imagen: prendaData.imagen || null,
            valor_unitario: (prendaData.valor_unitario !== undefined ? prendaData.valor_unitario : (prendaData.valorUnitario !== undefined ? prendaData.valorUnitario : null)),
            total: (prendaData.total !== undefined ? prendaData.total : null),
            tipo: 'prenda'
        };
        console.log(' [_procederAEditarPrenda] Datos transformados:', prendaDataTransformado);
        console.log(' [_procederAEditarPrenda] Imágenes disponibles:', prendaDataTransformado.imagenes.length);
        console.log(' [_procederAEditarPrenda] Total disponible:', prendaDataTransformado.total);
        
        // Cerrar menú primero
        const submenu = item.querySelector('.submenu-epp');
        if (submenu) submenu.style.display = 'none';
        console.log(' [_procederAEditarPrenda] Menú cerrado');

        // Usar setTimeout para asegurar que la función esté disponible
        console.log(' [_procederAEditarPrenda] Abriendo modal de prenda...');
        setTimeout(() => {
            if (typeof window.abrirModalAgregarPrenda === 'function') {
                console.log(' [_procederAEditarPrenda] Llamando a window.abrirModalAgregarPrenda()');
                window.abrirModalAgregarPrenda();
                
                // Cargar datos en el modal después de un pequeño delay para asegurar que está renderizado
                setTimeout(() => {
                    const descEl = document.getElementById('descripcionPrenda');
                    const cantEl = document.getElementById('cantidadPrenda');
                    const valUnitEl = document.getElementById('valorUnitarioPrenda');
                    const obsEl = document.getElementById('observacionesPrenda');
                    const totalEl = document.getElementById('totalPrenda');
                    const contenedorFotos = document.getElementById('contenedorFotosPrenda');
                    
                    if (descEl && cantEl && valUnitEl && obsEl) {
                        // Cargar datos de texto
                        descEl.value = prendaDataTransformado.descripcion || '';
                        cantEl.value = prendaDataTransformado.cantidad || 1;
                        valUnitEl.value = prendaDataTransformado.valor_unitario || '';
                        obsEl.value = prendaDataTransformado.observaciones || '';
                        
                        // Cargar total
                        if (totalEl && prendaDataTransformado.total) {
                            totalEl.value = prendaDataTransformado.total;
                            console.log(' [_procederAEditarPrenda] Total cargado:', prendaDataTransformado.total);
                        }
                        
                        // Cargar imágenes si existen
                        if (prendaDataTransformado.imagenes && prendaDataTransformado.imagenes.length > 0 && contenedorFotos) {
                            console.log(' [_procederAEditarPrenda] Cargando imágenes:', prendaDataTransformado.imagenes.length, 'imágenes');
                            
                            // Limpiar mensaje inicial
                            const mensajeDrag = document.getElementById('mensajeDragDropPrenda');
                            if (mensajeDrag) mensajeDrag.style.display = 'none';
                            
                            // Limpiar contenedor
                            contenedorFotos.innerHTML = '';
                            
                            // Agregar imágenes
                            prendaDataTransformado.imagenes.forEach((imgData, idx) => {
                                if (imgData) {
                                    // Extraer URL dependiendo de si es string u objeto
                                    let imgUrl = '';
                                    if (typeof imgData === 'string') {
                                        imgUrl = imgData;
                                    } else if (typeof imgData === 'object') {
                                        imgUrl = imgData.url || imgData.ruta_web || imgData.previewUrl || '';
                                    }
                                    
                                    if (!imgUrl) {
                                        console.warn(' [_procederAEditarPrenda] No se pudo obtener URL de imagen:', imgData);
                                        return;
                                    }
                                    
                                    const divFoto = document.createElement('div');
                                    divFoto.style.position = 'relative';
                                    divFoto.className = 'foto-prenda-item';
                                    divFoto.dataset.index = idx;
                                    
                                    const img = document.createElement('img');
                                    img.src = imgUrl;
                                    img.style.width = '100%';
                                    img.style.height = '100px';
                                    img.style.objectFit = 'cover';
                                    img.style.borderRadius = '4px';
                                    img.style.border = '1px solid #e5e7eb';
                                    img.alt = 'Foto de prenda';
                                    
                                    const btnEliminar = document.createElement('button');
                                    btnEliminar.type = 'button';
                                    btnEliminar.textContent = '✕';
                                    btnEliminar.style.position = 'absolute';
                                    btnEliminar.style.top = '-8px';
                                    btnEliminar.style.right = '-8px';
                                    btnEliminar.style.width = '24px';
                                    btnEliminar.style.height = '24px';
                                    btnEliminar.style.background = '#dc2626';
                                    btnEliminar.style.color = 'white';
                                    btnEliminar.style.border = 'none';
                                    btnEliminar.style.borderRadius = '50%';
                                    btnEliminar.style.cursor = 'pointer';
                                    btnEliminar.style.fontSize = '12px';
                                    btnEliminar.onclick = (e) => {
                                        e.preventDefault();
                                        divFoto.remove();
                                        console.log(' [_procederAEditarPrenda] Imagen eliminada');
                                    };
                                    
                                    divFoto.appendChild(img);
                                    divFoto.appendChild(btnEliminar);
                                    contenedorFotos.appendChild(divFoto);
                                    console.log(' [_procederAEditarPrenda] Imagen cargada:', imgUrl);
                                }
                            });
                        }
                        
                        // Guardar como prenda en edición (usar window.eppEnEdicion para estandarizar con EPP)
                        window.prendaEnEdicion = prendaDataTransformado;
                        window.eppEnEdicion = prendaDataTransformado;  // Estandarizar con EPP
                        console.log(' [_procederAEditarPrenda] Datos cargados en el modal de prenda');
                        console.log(' [_procederAEditarPrenda] window.eppEnEdicion configurado para prenda:', window.eppEnEdicion);
                    } else {
                        console.warn(' [_procederAEditarPrenda] No se encontraron todos los campos del modal');
                    }
                }, 150);
            } else {
                console.warn(' [_procederAEditarPrenda] No hay función disponible para editar prenda');
                alert('Por favor haz clic nuevamente en editar. El modal se está cargando.');
            }
        }, 100);
    },

    /**
     * Eliminar EPP
     */
    _eliminarEpp(btn) {
        const itemId = btn.dataset.itemId;

        // Obtener el item
        const item = btn.closest('.item-epp') || btn.closest('.item-epp-card');
        if (!item) {
            return;
        }

        // Mostrar SweetAlert de confirmación
        Swal.fire({
            title: '¿Eliminar este EPP?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                this._confirmarEliminacion(item, itemId);
            }
        });
    },

    /**
     * Confirmar eliminación de EPP
     */
    _confirmarEliminacion(item, itemId) {
        // Disparar evento personalizado
        const evento = new CustomEvent('epp:eliminar', {
            detail: {
                itemId,
                elemento: item
            }
        });
        document.dispatchEvent(evento);

        // Eliminar del DOM
        item.remove();

        // Si el item eliminado era el que estaba en edición en el modal, limpiar edición y cerrar modal
        try {
            if (window.eppEnEdicion) {
                const editId = window.eppEnEdicion?.epp_id || window.eppEnEdicion?.id || null;
                if (editId !== null && String(editId) === String(itemId)) {
                    window.eppEnEdicion = null;
                    if (typeof window.cerrarModalAgregarEPP === 'function') {
                        window.cerrarModalAgregarEPP();
                    }
                }
            }
        } catch (e) {
            // noop
        }

        // Eliminar del estado (para que al guardar borrador se elimine en BD)
        try {
            if (window.itemsPedido && Array.isArray(window.itemsPedido)) {
                const before = window.itemsPedido.length;
                window.itemsPedido = window.itemsPedido.filter(it => {
                    const id = it?.id ?? it?.epp_id ?? it?.pedidoEppId ?? null;
                    return String(id) !== String(itemId);
                });
                const after = window.itemsPedido.length;
                console.log('[EppMenuHandlers] itemsPedido actualizado tras eliminar. Antes:', before, 'Después:', after);
            }
        } catch (e) {
            // noop
        }

        // Actualizar contador si existe
        if (window.eppItemManager) {
            const total = window.eppItemManager.contarItems();
            
            // Actualizar UI del contador
            this._actualizarContadorItems(total);
        }
    },

    /**
     * Actualizar contador de items en la UI
     */
    _actualizarContadorItems(total) {
        // Opción 1: Actualizar el <span> en el h2 de "Ítems del Pedido"
        const seccion = document.getElementById('seccion-items-pedido');
        if (seccion) {
            const h2 = seccion.querySelector('h2');
            if (h2) {
                const span = h2.querySelector('span');
                if (span) {
                    span.textContent = total;
                }
            }
        }
        
        // Opción 2: Buscar y actualizar cualquier elemento que muestre "EPPs" seguido de un número
        const allText = document.body.innerText;
        const nodes = this._getAllTextNodes(document.body);
        nodes.forEach(node => {
            if (node.textContent.toLowerCase().includes('epps')) {
                const parent = node.parentElement;
                if (parent && parent.nextElementSibling) {
                    const nextEl = parent.nextElementSibling;
                    if (nextEl && !isNaN(nextEl.textContent)) {
                        nextEl.textContent = total;
                    }
                }
            }
        });
        
        // Opción 3: Buscar todos los spans con números y actualizar si están cerca de "EPP"
        document.querySelectorAll('span, div, p').forEach(el => {
            if (el.textContent.trim() === '1' || el.textContent.trim() === '0' || /^\d+$/.test(el.textContent.trim())) {
                const parent = el.parentElement;
                const sibling = el.previousElementSibling || el.nextElementSibling;
                if ((parent && parent.textContent.includes('EPP')) || (sibling && sibling.textContent.includes('EPP'))) {
                    if (/^\d+$/.test(el.textContent.trim())) {
                        el.textContent = total;
                    }
                }
            }
        });
    },

    /**
     * Helper: Obtener todos los text nodes
     */
    _getAllTextNodes(element) {
        const textNodes = [];
        const walk = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );
        let node;
        while (node = walk.nextNode()) {
            textNodes.push(node);
        }
        return textNodes;
    },

    /**
     * Cerrar todos los menús
     */
    _cerrarTodosLosMenus() {
        document.querySelectorAll('.submenu-epp').forEach(menu => {
            menu.style.display = 'none';
        });
    }
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.EppMenuHandlers.inicializar();
    });
} else {
    // DOM ya está listo
    window.EppMenuHandlers.inicializar();
}
