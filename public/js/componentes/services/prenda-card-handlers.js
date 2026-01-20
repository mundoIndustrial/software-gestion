/**
 * PrendaCardHandlers - Gestión de eventos para tarjetas de prenda
 * Maneja clicks en menús, botones, fotos y galerías
 */

window.PrendaCardHandlers = {
    inicializar(tarjeta, prenda, indice, callbacks = {}) {
        console.log(' [PrendaCardHandlers] Inicializando tarjeta índice:', indice);
        this._setupEventListeners();
    },

    _setupEventListeners() {
        // Expandir/contraer secciones
        document.addEventListener('click', (e) => {
            if (e.target.closest('.seccion-expandible-header')) {
                console.log('🔽 Click en header expandible');
                const header = e.target.closest('.seccion-expandible-header');
                const content = header.nextElementSibling;
                
                if (content && content.classList.contains('seccion-expandible-content')) {
                    content.classList.toggle('active');
                    header.classList.toggle('active');
                    console.log(' Sección expandida/contraída');
                }
            }

            // Menú de 3 puntos
            if (e.target.closest('.btn-menu-tres-puntos')) {
                console.log('☰ Click en menú de 3 puntos');
                e.stopPropagation();
                const btn = e.target.closest('.btn-menu-tres-puntos');
                const submenu = btn.nextElementSibling;
                
                document.querySelectorAll('.submenu-prenda').forEach(menu => {
                    if (menu !== submenu) menu.style.display = 'none';
                });
                
                submenu.style.display = submenu.style.display === 'none' ? 'flex' : 'none';
            }

            // Botón EDITAR
            if (e.target.closest('.btn-editar-prenda')) {
                console.log('  Click en botón EDITAR');
                e.stopPropagation();
                const btn = e.target.closest('.btn-editar-prenda');
                const prendaIndex = parseInt(btn.dataset.prendaIndex);
                console.log(`   Editando prenda índice: ${prendaIndex}`);
                
                let prenda = null;
                let esCrearNuevo = false;
                
                // Prioridad 1: Obtener desde GestionItemsUI (crear-nuevo)
                if (window.gestionItemsUI) {
                    const itemsOrdenados = window.gestionItemsUI.obtenerItemsOrdenados();
                    if (itemsOrdenados && itemsOrdenados[prendaIndex]) {
                        prenda = itemsOrdenados[prendaIndex];
                        esCrearNuevo = true;
                        console.log('   Prenda obtenida desde GestionItemsUI.obtenerItemsOrdenados()');
                    }
                }
                
                // Prioridad 2: Obtener desde itemsPedido (fallback)
                if (!prenda && window.itemsPedido && window.itemsPedido[prendaIndex]) {
                    prenda = window.itemsPedido[prendaIndex];
                    console.log('   Prenda obtenida desde itemsPedido');
                }
                
                // Prioridad 3: Obtener desde gestor (pedidos guardados)
                if (!prenda && window.gestorPrendaSinCotizacion) {
                    prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
                    console.log('   Prenda obtenida desde gestorPrendaSinCotizacion');
                }
                
                console.log('   Prenda obtenida:', prenda);
                console.log('   esCrearNuevo:', esCrearNuevo);
                
                if (prenda) {
                    // Si es crear-nuevo, abrir el modal de creación con datos precargados
                    if (esCrearNuevo && window.gestionItemsUI) {
                        console.log('    Abriendo modal de creación (crear-nuevo) para editar');
                        window.gestionItemsUI.prendaEditIndex = prendaIndex;
                        window.gestionItemsUI.abrirModalAgregarPrendaNueva();
                        
                        // Esperar a que el modal se abra, se limpie y luego cargar datos
                        // Aumentar delay para asegurar que modal-cleanup termine
                        setTimeout(() => {
                            console.log('    Cargando datos de prenda en modal...');
                            window.gestionItemsUI.prendaEditor?.cargarPrendaEnModal(prenda, prendaIndex);
                        }, 500);
                    }
                    // Si es pedido guardado, abrir modal simple de edición
                    else if (window.abrirEditarPrendaModal) {
                        const pedidoId = document.querySelector('[data-pedido-id]')?.dataset.pedidoId || null;
                        console.log('    Abriendo modal de edición simple (pedido guardado), pedidoId:', pedidoId);
                        window.abrirEditarPrendaModal(prenda, prendaIndex, pedidoId);
                    }
                }
                
                const submenu = btn.closest('.submenu-prenda');
                if (submenu) submenu.style.display = 'none';
            }

            // Botón ELIMINAR
            if (e.target.closest('.btn-eliminar-prenda')) {
                console.log('🗑️  Click en botón ELIMINAR');
                e.stopPropagation();
                const btn = e.target.closest('.btn-eliminar-prenda');
                const prendaIndex = parseInt(btn.dataset.prendaIndex);
                console.log(`   Eliminando prenda índice: ${prendaIndex}`);
                
                Swal.fire({
                    title: '¿Eliminar prenda?',
                    text: 'Esta acción no se puede deshacer',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('    Confirmado eliminar');
                        
                        // Obtener instancia de GestionItemsUI si existe
                        if (window.gestionItemsUI) {
                            const itemsOrdenados = window.gestionItemsUI.obtenerItemsOrdenados();
                            if (prendaIndex >= 0 && prendaIndex < itemsOrdenados.length) {
                                itemsOrdenados.splice(prendaIndex, 1);
                                console.log(`    Item eliminado del orden`);
                            }
                        }
                        
                        // También eliminar desde gestor si existe
                        if (window.gestorPrendaSinCotizacion?.eliminar) {
                            window.gestorPrendaSinCotizacion.eliminar(prendaIndex);
                        }
                        
                        // Re-renderizar
                        const container = document.getElementById('lista-items-pedido');
                        if (container && window.generarTarjetaPrendaReadOnly) {
                            let items = [];
                            if (window.gestionItemsUI) {
                                items = window.gestionItemsUI.obtenerItemsOrdenados();
                            }
                            
                            if (items.length === 0) {
                                container.innerHTML = `
                                    <div class="empty-state" style="text-align: center; padding: 2rem; color: #9ca3af;">
                                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                        <p>No hay ítems agregados.</p>
                                    </div>
                                `;
                            } else {
                                let html = '';
                                items.forEach((item, idx) => {
                                    html += window.generarTarjetaPrendaReadOnly(item, idx);
                                });
                                container.innerHTML = html;
                            }
                            console.log(`    Items re-renderizados. Total: ${items.length}`);
                        }
                    }
                });
                
                const submenu = btn.closest('.submenu-prenda');
                if (submenu) submenu.style.display = 'none';
            }

            // Cerrar menú al hacer click fuera
            if (!e.target.closest('.prenda-menu-contextual')) {
                document.querySelectorAll('.submenu-prenda').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });

        // Galerías de fotos
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('foto-principal-readonly')) {
                console.log(' Click en foto principal');
                e.stopPropagation();
                const prendaIndex = parseInt(e.target.dataset.prendaIndex);
                console.log(`   Prenda índice: ${prendaIndex}`);
                
                let prenda = null;
                // Obtener desde GestionItemsUI (fuente principal)
                if (window.gestionItemsUI && window.gestionItemsUI.prendas && window.gestionItemsUI.prendas[prendaIndex]) {
                    prenda = window.gestionItemsUI.prendas[prendaIndex];
                    console.log('   Prenda obtenida desde GestionItemsUI');
                } else if (window.itemsPedido && window.itemsPedido[prendaIndex]) {
                    prenda = window.itemsPedido[prendaIndex];
                    console.log('   Prenda obtenida desde itemsPedido');
                } else if (window.gestorPrendaSinCotizacion) {
                    prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
                    console.log('   Prenda obtenida desde gestorPrendaSinCotizacion');
                }
                
                console.log('   Prenda obtenida para galería:', prenda);
                if (prenda && prenda.imagenes && prenda.imagenes.length > 0) {
                    this._abrirGaleriaFotos(prenda, prendaIndex);
                } else {
                    console.warn('   No hay imágenes para mostrar en galería');
                }
            }

            if (e.target.classList.contains('foto-tela-readonly')) {
                console.log(' Click en foto de tela');
                e.stopPropagation();
                const prendaIndex = parseInt(e.target.dataset.prendaIndex);
                console.log(`   Prenda índice: ${prendaIndex}`);
                
                let prenda = null;
                // Obtener desde GestionItemsUI (fuente principal)
                if (window.gestionItemsUI && window.gestionItemsUI.prendas && window.gestionItemsUI.prendas[prendaIndex]) {
                    prenda = window.gestionItemsUI.prendas[prendaIndex];
                    console.log('   Prenda obtenida desde GestionItemsUI');
                } else if (window.itemsPedido && window.itemsPedido[prendaIndex]) {
                    prenda = window.itemsPedido[prendaIndex];
                    console.log('   Prenda obtenida desde itemsPedido');
                } else if (window.gestorPrendaSinCotizacion) {
                    prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
                    console.log('   Prenda obtenida desde gestorPrendaSinCotizacion');
                }
                
                console.log('   Prenda obtenida para galería de tela:', prenda);
                if (prenda && prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
                    this._abrirGaleriaTelas(prenda, prendaIndex);
                } else {
                    console.warn('   No hay telas para mostrar en galería');
                }
            }
        });
    },

    _abrirGaleriaFotos(prenda, prendaIndex) {
        console.log('  Abriendo galería de fotos para prenda:', prendaIndex);
        
        let imagenes = (prenda.imagenes?.length > 0 ? prenda.imagenes : null) || 
                       (prenda.fotos?.length > 0 ? prenda.fotos : null) || 
                       [];
        console.log(`   Imágenes brutas encontradas: ${imagenes.length}`);
        
        const fotosUrls = imagenes.map((img, idx) => {
            // Usar servicio centralizado para convertir imágenes
            const url = window.ImageConverterService ? 
                window.ImageConverterService.convertirAUrl(img) : 
                null;
            
            if (url) {
                return url;
            }
            
            // Fallback si ImageConverterService no está disponible
            if (img && img.blobUrl && typeof img.blobUrl === 'string') {
                return img.blobUrl;
            }
            else if (img && img.file instanceof File) {
                return URL.createObjectURL(img.file);
            } else if (img instanceof File) {
                return URL.createObjectURL(img);
            } else if (typeof img === 'string') {
                return img;
            }
            return null;
        }).filter(url => url !== null);
        
        console.log(`    Fotos procesadas: ${fotosUrls.length}`);
        
        if (fotosUrls.length === 0) {
            console.warn('  Sin fotos disponibles');
            Swal.fire({
                title: ' Sin fotos',
                html: '<p style="color: #666;">Esta prenda no tiene fotos cargadas</p>',
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#0ea5e9'
            });
            return;
        }

        let indiceActual = 0;

        const generarContenidoGaleria = (idx) => {
            return `
                <div style="max-width: 500px; margin: 0 auto;">
                    <div id="galeria-foto-container" style="position: relative; margin-bottom: 1rem;">
                        <img 
                            id="galeria-foto-actual"
                            src="${fotosUrls[idx]}" 
                            alt="Foto prenda"
                            style="width: 100%; border-radius: 8px; max-height: 400px; object-fit: contain;"
                        />
                        ${fotosUrls.length > 1 ? `
                            <button id="btn-foto-anterior" type="button" 
                                style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); 
                                       background: rgba(0,0,0,0.6); color: white; border: none; 
                                       width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.2s;">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button id="btn-foto-siguiente" type="button" 
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); 
                                       background: rgba(0,0,0,0.6); color: white; border: none; 
                                       width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.2s;">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        ` : ''}
                    </div>
                    <div style="text-align: center; color: #666; font-size: 0.9rem;">
                        <i class="fas fa-images"></i> Foto ${idx + 1} de ${fotosUrls.length}
                    </div>
                </div>
            `;
        };

        Swal.fire({
            title: ` ${prenda.nombre_producto}`,
            html: generarContenidoGaleria(indiceActual),
            width: '600px',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#0ea5e9',
            didOpen: () => {
                const actualizarGaleria = () => {
                    const container = document.querySelector('.swal2-html-container');
                    if (container) {
                        container.innerHTML = generarContenidoGaleria(indiceActual);
                        
                        const btnAnterior = document.getElementById('btn-foto-anterior');
                        const btnSiguiente = document.getElementById('btn-foto-siguiente');

                        if (btnAnterior) {
                            btnAnterior.addEventListener('click', (e) => {
                                e.stopPropagation();
                                indiceActual = (indiceActual - 1 + fotosUrls.length) % fotosUrls.length;
                                actualizarGaleria();
                            });
                        }

                        if (btnSiguiente) {
                            btnSiguiente.addEventListener('click', (e) => {
                                e.stopPropagation();
                                indiceActual = (indiceActual + 1) % fotosUrls.length;
                                actualizarGaleria();
                            });
                        }
                    }
                };
                
                actualizarGaleria();
            }
        });
    },

    _abrirGaleriaTelas(prenda, prendaIndex) {
        console.log(' Abriendo galería de fotos de tela para prenda:', prendaIndex);
        
        const telas = prenda.telasAgregadas || [];
        console.log(`   Telas disponibles: ${telas.length}`);
        
        if (telas.length === 0) {
            console.warn('  Sin telas disponibles');
            Swal.fire({
                title: ' Sin telas',
                html: '<p style="color: #666;">Esta prenda no tiene telas cargadas</p>',
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#0ea5e9'
            });
            return;
        }

        const telasConFotos = [];
        telas.forEach((tela, telaIdx) => {
            if (tela.imagenes && Array.isArray(tela.imagenes)) {
                const fotosUrlsTela = tela.imagenes.map((img) => {
                    // Usar servicio centralizado para convertir imágenes
                    const url = window.ImageConverterService ? 
                        window.ImageConverterService.convertirAUrl(img) : 
                        null;
                    
                    if (url) {
                        return url;
                    }
                    
                    // Fallback si ImageConverterService no está disponible
                    if (img.blobUrl && typeof img.blobUrl === 'string') {
                        return img.blobUrl;
                    }
                    else if (img.file instanceof File) {
                        return URL.createObjectURL(img.file);
                    } else if (img instanceof File) {
                        return URL.createObjectURL(img);
                    } else if (typeof img === 'string') {
                        return img;
                    }
                    return null;
                }).filter(url => url !== null);

                if (fotosUrlsTela.length > 0) {
                    telasConFotos.push({
                        nombre: tela.tela || `Tela ${telaIdx + 1}`,
                        color: tela.color || 'N/A',
                        referencia: tela.referencia || 'N/A',
                        fotos: fotosUrlsTela
                    });
                }
            }
        });

        console.log(`   Telas con fotos: ${telasConFotos.length}`);

        if (telasConFotos.length === 0) {
            console.warn('  Sin fotos de telas disponibles');
            Swal.fire({
                title: ' Sin fotos',
                html: '<p style="color: #666;">Las telas no tienen fotos cargadas</p>',
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#0ea5e9'
            });
            return;
        }

        let telaActualIdx = 0;
        let fotoActualIdx = 0;

        const generarContenidoGaleriaTela = (telaIdx, fotoIdx) => {
            const tela = telasConFotos[telaIdx];
            const foto = tela.fotos[fotoIdx];

            return `
                <div style="max-width: 500px; margin: 0 auto;">
                    <div style="background: #f0f9ff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #0ea5e9;">
                        <div style="font-weight: 700; color: #0369a1; margin-bottom: 0.5rem;">
                            <i class="fas fa-cube"></i> ${tela.nombre}
                        </div>
                        <div style="font-size: 0.85rem; color: #4b5563;">
                            <div><strong>Color:</strong> ${tela.color}</div>
                            <div><strong>Ref:</strong> ${tela.referencia}</div>
                        </div>
                    </div>

                    <div id="galeria-tela-container" style="position: relative; margin-bottom: 1rem;">
                        <img 
                            id="galeria-tela-actual"
                            src="${foto}" 
                            alt="Foto tela"
                            style="width: 100%; border-radius: 8px; max-height: 400px; object-fit: contain; border: 2px solid #e5e7eb;"
                        />
                        ${tela.fotos.length > 1 ? `
                            <button id="btn-tela-anterior" type="button" 
                                style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); 
                                       background: rgba(0,0,0,0.6); color: white; border: none; 
                                       width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.2s;">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button id="btn-tela-siguiente" type="button" 
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); 
                                       background: rgba(0,0,0,0.6); color: white; border: none; 
                                       width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.2s;">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        ` : ''}
                    </div>

                    <div style="text-align: center; color: #666; font-size: 0.9rem;">
                        <i class="fas fa-images"></i> Tela ${telaIdx + 1} de ${telasConFotos.length} | Foto ${fotoIdx + 1} de ${tela.fotos.length}
                    </div>
                </div>
            `;
        };

        Swal.fire({
            title: ` Telas - ${prenda.nombre_producto}`,
            html: generarContenidoGaleriaTela(telaActualIdx, fotoActualIdx),
            width: '600px',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#0ea5e9',
            didOpen: () => {
                const actualizarGaleriaTela = () => {
                    const container = document.querySelector('.swal2-html-container');
                    if (container) {
                        container.innerHTML = generarContenidoGaleriaTela(telaActualIdx, fotoActualIdx);
                        
                        const btnAnterior = document.getElementById('btn-tela-anterior');
                        const btnSiguiente = document.getElementById('btn-tela-siguiente');

                        if (btnAnterior) {
                            btnAnterior.addEventListener('click', (e) => {
                                e.stopPropagation();
                                fotoActualIdx = (fotoActualIdx - 1 + telasConFotos[telaActualIdx].fotos.length) % telasConFotos[telaActualIdx].fotos.length;
                                actualizarGaleriaTela();
                            });
                        }

                        if (btnSiguiente) {
                            btnSiguiente.addEventListener('click', (e) => {
                                e.stopPropagation();
                                fotoActualIdx = (fotoActualIdx + 1) % telasConFotos[telaActualIdx].fotos.length;
                                actualizarGaleriaTela();
                            });
                        }
                    }
                };
                
                actualizarGaleriaTela();
            }
        });
    }
};

console.log(' [PrendaCardHandlers] Cargado - Gestión de eventos para tarjetas');
