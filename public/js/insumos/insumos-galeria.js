/**
 * Galería personalizada para insumos
 * Evita conflictos con otros módulos
 */

class InsumosGaleria {
    constructor() {
        this.imagenesActuales = [];
        this.indiceActual = 0;
        this.modalActivo = null;
    }

    /**
     * Alterna entre vista de recibo y galería
     */
    toggle() {
        console.log('[InsumosGaleria] Toggle iniciado');
        console.log('[InsumosGaleria] Buscando elementos del modal...');
        
        const modalWrapper = document.getElementById('order-detail-modal-wrapper');
        if (!modalWrapper) {
            console.error('[InsumosGaleria] No se encontró el modal wrapper');
            return;
        }
        
        const card = modalWrapper.querySelector('.order-detail-card');
        const galeria = document.getElementById('galeria-modal-costura');
        
        console.log('[InsumosGaleria] Estado actual - Card:', card ? 'visible' : 'no encontrado');
        console.log('[InsumosGaleria] Estado actual - Galería:', galeria ? (galeria.style.display === 'flex' ? 'visible' : 'oculta') : 'no encontrado');
        
        // Determinar estado actual - usar display en lugar de opacity
        const estaEnGaleria = galeria && galeria.style.display === 'flex';
        
        console.log('[InsumosGaleria] ¿Está en galería?:', estaEnGaleria);
        
        // Agregar botón X en la carga inicial o cuando se muestra el recibo
        if (!estaEnGaleria) {
            // Estamos en recibo, asegurar que el botón X esté visible
            this.agregarBotonCerrar(card);
        }
        
        if (estaEnGaleria) {
            // Estamos en galería, volver al recibo
            console.log('[InsumosGaleria] Cerrando galería y mostrando recibo');
            this.cerrarGaleria(card, galeria);
        } else {
            // Estamos en recibo, mostrar galería
            console.log('[InsumosGaleria] Abriendo galería y ocultando recibo');
            this.abrirGaleria(card, galeria, modalWrapper);
        }
    }

    /**
     * Cierra la galería y muestra el recibo (público)
     */
    cerrarGaleria(card, galeria) {
        console.log('[cerrarGaleria] Iniciando cierre de galería');
        console.log('[cerrarGaleria] Card:', card ? 'encontrado' : 'no encontrado');
        console.log('[cerrarGaleria] Galería:', galeria ? 'encontrada' : 'no encontrado');
        
        if (galeria) {
            console.log('[cerrarGaleria] Ocultando galería');
            galeria.style.display = 'none';
        }
        
        if (card) {
            console.log('[cerrarGaleria] Mostrando card con display block');
            card.style.display = 'block';
        }
        
        // Actualizar botones
        const btnFactura = document.getElementById('btn-factura');
        const btnGaleria = document.getElementById('btn-galeria');
        
        if (btnFactura) {
            console.log('[cerrarGaleria] Mostrando botón factura');
            btnFactura.style.display = 'block';
            btnFactura.style.visibility = 'visible';
            btnFactura.style.zIndex = '10';
            // Cambiar icono a galería cuando estamos en recibo
            const iconoFactura = btnFactura.querySelector('i');
            if (iconoFactura) {
                iconoFactura.className = 'fas fa-images';
                btnFactura.title = 'Ver galería';
            }
        }
        
        if (btnGaleria) {
            console.log('[cerrarGaleria] Ocultando botón galería');
            btnGaleria.style.display = 'none';
            btnGaleria.style.visibility = 'hidden';
            btnGaleria.style.zIndex = '-1';
        }
        
        console.log('[cerrarGaleria] Cierre completado');
    }
    
    /**
     * Cierra completamente el modal (tanto recibo como galería)
     */
    cerrarModal() {
        // Log inmediato para verificar si se llama al método
        console.log('[cerrarModal] ===== MÉTODO CERRAR MODAL LLAMADO =====');
        
        try {
            console.log('[cerrarModal] ===== INICIO DE cerrarModal =====');
            console.log('[cerrarModal] Cerrando modal completamente');
            console.log('[cerrarModal] Buscando modal wrapper...');
            
            const modalWrapper = document.getElementById('order-detail-modal-wrapper');
            console.log('[cerrarModal] Modal wrapper encontrado:', !!modalWrapper);
            
            if (modalWrapper) {
                console.log('[cerrarModal] Eliminando modal wrapper...');
                modalWrapper.remove();
                console.log('[cerrarModal] Modal wrapper eliminado');
            }
            
            // Eliminar el botón de cerrar flotante
            console.log('[cerrarModal] Buscando botón de cerrar...');
            const btnCerrar = document.getElementById('btn-cerrar-modal-insumos');
            console.log('[cerrarModal] Botón de cerrar encontrado:', !!btnCerrar);
            
            if (btnCerrar) {
                console.log('[cerrarModal] Eliminando botón de cerrar...');
                btnCerrar.remove();
                console.log('[cerrarModal] Botón de cerrar eliminado');
            }
            
            // Limpiar datos
            console.log('[cerrarModal] Limpiando datos...');
            window.receiptManager = null;
            this.imagenesActuales = [];
            this.estilosOriginalesCard = null;
            
            console.log('[cerrarModal] Modal cerrado y datos limpiados - COMPLETADO');
        } catch (error) {
            console.error('[cerrarModal] ERROR al cerrar modal:', error);
            console.error('[cerrarModal] Stack trace:', error.stack);
        }
    }

    /**
     * Abre la galería y oculta el recibo (público)
     */
    abrirGaleria(card, galeria, modalWrapper) {
        console.log('[abrirGaleria] Iniciando apertura de galería');
        console.log('[abrirGaleria] Card:', card ? 'encontrado' : 'no encontrado');
        console.log('[abrirGaleria] Galería:', galeria ? 'encontrada' : 'no encontrado');
        
        // Obtener el contenedor donde está el card
        const container = modalWrapper.querySelector('.order-detail-modal-container');
        
        if (!container) {
            console.error('[abrirGaleria] No se encontró el contenedor del modal');
            return;
        }
        
        // Logs del contenedor
        const containerRect = container.getBoundingClientRect();
        console.log('[abrirGaleria] Posición del contenedor:', {
            width: containerRect.width,
            height: containerRect.height,
            top: containerRect.top,
            left: containerRect.left,
            bottom: containerRect.bottom,
            right: containerRect.right,
            x: containerRect.x,
            y: containerRect.y
        });
        
        if (card) {
            console.log('[abrirGaleria] Ocultando card con display none');
            card.style.display = 'none';
        }
        
        if (!galeria) {
            console.log('[abrirGaleria] Creando nueva galería');
            galeria = document.createElement('div');
            galeria.id = 'galeria-modal-costura';
            
            // Aplicar estilos similares al card pero sin transform
            galeria.style.cssText = `
                width: 600px; 
                height: 680px; 
                margin: 0 auto; 
                padding: 30px; 
                display: flex; 
                flex-direction: column; 
                overflow-y: auto;
                background: white;
                border-radius: 24px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                position: relative;
                z-index: 2;
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            `;
            container.appendChild(galeria);
        } else {
            console.log('[abrirGaleria] Mostrando galería existente');
            galeria.style.display = 'flex';
            galeria.style.visibility = 'visible';
            galeria.style.pointerEvents = 'auto';
            galeria.style.height = '680px';
            galeria.style.overflow = 'auto';
            galeria.style.margin = '0 auto';
            galeria.style.padding = '30px';
        }
        
        // Logs de la galería recién creada
        setTimeout(() => {
            const galeriaRect = galeria.getBoundingClientRect();
            console.log('[abrirGaleria] Posición de la galería:', {
                width: galeriaRect.width,
                height: galeriaRect.height,
                top: galeriaRect.top,
                left: galeriaRect.left,
                bottom: galeriaRect.bottom,
                right: galeriaRect.right,
                x: galeriaRect.x,
                y: galeriaRect.y
            });
        }, 10);
        
        // Construir galería con los datos existentes
        this.construirGaleria(galeria);
        
        // Actualizar botones
        const btnFactura = document.getElementById('btn-factura');
        const btnGaleria = document.getElementById('btn-galeria');
        
        if (btnFactura) {
            console.log('[abrirGaleria] Ocultando botón factura');
            btnFactura.style.display = 'none';
            btnFactura.style.visibility = 'hidden';
            btnFactura.style.zIndex = '-1';
        }
        
        if (btnGaleria) {
            console.log('[abrirGaleria] Mostrando botón galería');
            btnGaleria.style.display = 'block';
            btnGaleria.style.visibility = 'visible';
            btnGaleria.style.zIndex = '10';
            // Cambiar icono a recibos cuando estamos en galería
            const iconoGaleria = btnGaleria.querySelector('i');
            if (iconoGaleria) {
                iconoGaleria.className = 'fas fa-receipt';
                btnGaleria.title = 'Ver recibos';
            }
        }
        
        console.log('[abrirGaleria] Apertura completada');
    }

    /**
     * Construye la galería usando los datos del pedido actual
     */
    construirGaleria(container) {
        console.log('[construirGaleria] Iniciando construcción de galería');
        
        // Obtener los datos del pedido actual
        const datosActuales = window.receiptManager ? window.receiptManager.datosFactura : null;
        
        console.log('[construirGaleria] Datos del ReceiptManager:', datosActuales);
        
        if (!datosActuales) {
            console.error('[construirGaleria] No hay ReceiptManager disponible');
            container.innerHTML = `
                <div style="padding: 2rem; text-align: center;">
                    <p style="color: #6b7280; font-size: 1rem;">No hay datos de prendas disponibles</p>
                </div>
            `;
            return;
        }
        
        if (!datosActuales.prendas || datosActuales.prendas.length === 0) {
            console.warn('[construirGaleria] No hay prendas en los datos');
            container.innerHTML = `
                <div style="padding: 2rem; text-align: center;">
                    <p style="color: #6b7280; font-size: 1rem;">No hay prendas disponibles en el pedido</p>
                </div>
            `;
            return;
        }
        
        console.log('[construirGaleria] Prendas disponibles:', datosActuales.prendas.length);
        
        let html = '';
        let tieneImagenes = false;
        this.imagenesActuales = [];
        
        // Recorrer prendas y mostrar imágenes
        datosActuales.prendas.forEach((prenda, prendaIndex) => {
            console.log(`[construirGaleria] Analizando prenda ${prendaIndex}:`, prenda.nombre);
            
            if (prenda.imagenes && prenda.imagenes.length > 0) {
                tieneImagenes = true;
                console.log(`[construirGaleria] Encontradas ${prenda.imagenes.length} imágenes en prenda ${prendaIndex}`);
                html += `
                    <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">${prenda.nombre}</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                `;
                
                prenda.imagenes.forEach((imagen, index) => {
                    console.log(`[construirGaleria] Agregando imagen ${index}:`, imagen.ruta_webp);
                    this.imagenesActuales.push({
                        src: imagen.ruta_webp,
                        titulo: `${prenda.nombre} - Imagen ${index + 1}`,
                        tipo: 'prenda',
                        prendaNombre: prenda.nombre,
                        index: index
                    });
                    
                    html += `
                        <div style="
                            border: 2px solid #e5e7eb; 
                            border-radius: 12px; 
                            overflow: hidden; 
                            cursor: pointer; 
                            transition: all 0.2s ease;
                            background: white;
                        " onclick="window.insumosGaleria.mostrarImagen(${this.imagenesActuales.length - 1})"
                        onmouseover="this.style.borderColor='#3b82f6'; this.style.transform='scale(1.02)';"
                        onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)';">
                            <img src="${imagen.ruta_webp}" alt="${prenda.nombre}" style="
                                width: 100%; 
                                height: 180px; 
                                object-fit: cover;
                                display: block;
                            ">
                            <div style="padding: 0.75rem; background: #f9fafb;">
                                <div style="font-size: 0.875rem; font-weight: 600; color: #1f2937; margin-bottom: 0.25rem;">${prenda.nombre}</div>
                                <div style="font-size: 0.75rem; color: #6b7280;">Imagen ${index + 1}</div>
                            </div>
                        </div>
                    `;
                });
                
                html += `</div></div>`;
            }
            
            // Agregar imágenes de procesos
            if (prenda.procesos && prenda.procesos.length > 0) {
                console.log(`[construirGaleria] Analizando ${prenda.procesos.length} procesos de prenda ${prendaIndex}`);
                
                prenda.procesos.forEach(proceso => {
                    if (proceso.imagenes && proceso.imagenes.length > 0) {
                        tieneImagenes = true;
                        console.log(`[construirGaleria] Encontradas ${proceso.imagenes.length} imágenes en proceso: ${proceso.tipo_proceso}`);
                        html += `
                            <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">${prenda.nombre} - ${proceso.tipo_proceso}</h3>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                        `;
                        
                        proceso.imagenes.forEach((imagen, index) => {
                            console.log(`[construirGaleria] Agregando imagen ${index} de proceso:`, imagen.ruta_webp);
                            this.imagenesActuales.push({
                                src: imagen.ruta_webp,
                                titulo: `${prenda.nombre} - ${proceso.tipo_proceso} - Imagen ${index + 1}`,
                                tipo: 'proceso',
                                prendaNombre: prenda.nombre,
                                procesoTipo: proceso.tipo_proceso,
                                index: index
                            });
                            
                            html += `
                                <div style="
                                    border: 2px solid #e5e7eb; 
                                    border-radius: 12px; 
                                    overflow: hidden; 
                                    cursor: pointer; 
                                    transition: all 0.2s ease;
                                    background: white;
                                " onclick="window.insumosGaleria.mostrarImagen(${this.imagenesActuales.length - 1})"
                                onmouseover="this.style.borderColor='#3b82f6'; this.style.transform='scale(1.02)';"
                                onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)';">
                                    <img src="${imagen.ruta_webp}" alt="${prenda.nombre} - ${proceso.tipo_proceso}" style="
                                        width: 100%; 
                                        height: 180px; 
                                        object-fit: cover;
                                        display: block;
                                    ">
                                    <div style="padding: 0.75rem; background: #f9fafb;">
                                        <div style="font-size: 0.875rem; font-weight: 600; color: #1f2937; margin-bottom: 0.25rem;">${proceso.tipo_proceso}</div>
                                        <div style="font-size: 0.75rem; color: #6b7280;">${prenda.nombre}</div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += `</div></div>`;
                    }
                });
            }
        });
        
        if (!tieneImagenes) {
            console.warn('[construirGaleria] No se encontraron imágenes en el pedido');
            html = `
                <div style="padding: 3rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem; color: #9ca3af;">📷</div>
                    <p style="color: #6b7280; font-size: 1rem; margin-bottom: 1rem;">No hay fotos de costura disponibles para este pedido</p>
                    <p style="color: #9ca3af; font-size: 0.875rem;">Las imágenes se mostrarán aquí cuando estén disponibles</p>
                </div>
            `;
        }
        
        html += '</div>';
        
        console.log('[construirGaleria] Galería construida con', tieneImagenes ? 'con imágenes' : 'sin imágenes');
        console.log('[construirGaleria] Total de imágenes guardadas:', this.imagenesActuales.length);
        console.log('[construirGaleria] HTML generado:', html.substring(0, 200) + '...');
        
        container.innerHTML = html;
        
        // Agregar botón de cerrar (X) en la esquina superior derecha
        this.agregarBotonCerrar(container);
        
        // Logs después de asignar el HTML
        setTimeout(() => {
            console.log('[construirGaleria] Container innerHTML después de asignar:', container.innerHTML.substring(0, 200) + '...');
            const containerRect = container.getBoundingClientRect();
            console.log('[construirGaleria] Dimensión final del contenedor:', {
                width: containerRect.width,
                height: containerRect.height,
                scrollHeight: container.scrollHeight
            });
        }, 10);
    }
    
    /**
     * Agrega un botón de cerrar (X) flotante en el lado derecho en una esquina
     */
    agregarBotonCerrar(container) {
        // Verificar si ya existe un botón de cerrar para evitar duplicados
        const btnExistente = document.getElementById('btn-cerrar-modal-insumos');
        if (btnExistente) {
            console.log('[agregarBotonCerrar] Botón de cerrar ya existe, no se duplica');
            return;
        }
        
        // Crear botón de cerrar flotante
        const btnCerrar = document.createElement('button');
        btnCerrar.id = 'btn-cerrar-modal-insumos';
        btnCerrar.innerHTML = '×';
        btnCerrar.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border: none;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            font-size: 1.8rem;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        `;
        
        btnCerrar.onmouseover = () => {
            btnCerrar.style.background = 'rgba(0, 0, 0, 0.95)';
            btnCerrar.style.transform = 'scale(1.1)';
            btnCerrar.style.boxShadow = '0 6px 16px rgba(0, 0, 0, 0.4)';
        };
        
        btnCerrar.onmouseout = () => {
            btnCerrar.style.background = 'rgba(0, 0, 0, 0.9)';
            btnCerrar.style.transform = 'scale(1)';
            btnCerrar.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.3)';
        };
        
        // Usar addEventListener en lugar de onclick para evitar conflictos
        // Guardar referencia a this para usarla dentro del evento
        const self = this;
        btnCerrar.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            console.log('[agregarBotonCerrar] Botón X clickeado con addEventListener');
            console.log('[agregarBotonCerrar] Llamando a cerrarModal...');
            console.log('[agregarBotonCerrar] Contexto self:', self);
            console.log('[agregarBotonCerrar] ¿self.cerrarModal existe?', typeof self.cerrarModal);
            console.log('[agregarBotonCerrar] ¿self tiene cerrarModal?', 'cerrarModal' in self);
            console.log('[agregarBotonCerrar] ¿self es instancia de InsumosGaleria?', self instanceof InsumosGaleria);
            console.log('[agregarBotonCerrar] Prototipo de self:', Object.getPrototypeOf(self));
            console.log('[agregarBotonCerrar] Métodos en prototipo:', Object.getOwnPropertyNames(Object.getPrototypeOf(self)).filter(name => typeof Object.getPrototypeOf(self)[name] === 'function'));
            console.log('[agregarBotonCerrar] Métodos en objeto:', Object.getOwnPropertyNames(self).filter(name => typeof self[name] === 'function'));
            
            if (typeof self.cerrarModal === 'function') {
                console.log('[agregarBotonCerrar] Ejecutando código de cerrarModal directamente...');
                try {
                    // Ejecutar el código directamente en lugar de llamar al método
                    console.log('[cerrarModal] ===== MÉTODO CERRAR MODAL EJECUTADO DIRECTAMENTE =====');
                    console.log('[cerrarModal] Cerrando modal completamente');
                    console.log('[cerrarModal] Buscando modal wrapper...');
                    
                    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
                    console.log('[cerrarModal] Modal wrapper encontrado:', !!modalWrapper);
                    
                    if (modalWrapper) {
                        console.log('[cerrarModal] Ocultando modal wrapper...');
                        modalWrapper.style.display = 'none';
                        modalWrapper.style.zIndex = '-1';
                        modalWrapper.style.opacity = '0';
                        modalWrapper.style.visibility = 'hidden';
                        modalWrapper.style.pointerEvents = 'none';
                        console.log('[cerrarModal] Modal wrapper oculto');
                    }
                    
                    // Eliminar el overlay (lámina gris)
                    console.log('[cerrarModal] Buscando overlay...');
                    const overlay = document.getElementById('modal-overlay');
                    console.log('[cerrarModal] Overlay encontrado:', !!overlay);
                    
                    if (overlay) {
                        console.log('[cerrarModal] Ocultando overlay...');
                        overlay.style.display = 'none';
                        overlay.style.zIndex = '-1';
                        overlay.style.opacity = '0';
                        overlay.style.visibility = 'hidden';
                        overlay.style.pointerEvents = 'none';
                        console.log('[cerrarModal] Overlay oculto');
                    }
                    
                    // Eliminar el botón de cerrar flotante
                    console.log('[cerrarModal] Buscando botón de cerrar...');
                    const btnCerrar = document.getElementById('btn-cerrar-modal-insumos');
                    console.log('[cerrarModal] Botón de cerrar encontrado:', !!btnCerrar);
                    
                    if (btnCerrar) {
                        console.log('[cerrarModal] Eliminando botón de cerrar...');
                        btnCerrar.remove();
                        console.log('[cerrarModal] Botón de cerrar eliminado');
                    }
                    
                    // Limpiar datos
                    console.log('[cerrarModal] Limpiando datos...');
                    window.receiptManager = null;
                    self.imagenesActuales = [];
                    self.estilosOriginalesCard = null;
                    
                    console.log('[cerrarModal] Modal cerrado y datos limpiados - COMPLETADO');
                    console.log('[agregarBotonCerrar] ===== DESPUÉS DE EJECUTAR CERRAR MODAL =====');
                } catch (error) {
                    console.error('[agregarBotonCerrar] ERROR al ejecutar cerrarModal directamente:', error);
                    console.error('[agregarBotonCerrar] Stack trace:', error.stack);
                }
            } else {
                console.error('[agregarBotonCerrar] ERROR: self.cerrarModal no es una función');
                console.error('[agregarBotonCerrar] Intentando llamar a cerrarModal global...');
                if (typeof window.cerrarModal === 'function') {
                    window.cerrarModal();
                } else {
                    console.error('[agregarBotonCerrar] ERROR: window.cerrarModal tampoco existe');
                }
            }
        });
        
        // Agregar el botón al body (flotante)
        document.body.appendChild(btnCerrar);
        console.log('[agregarBotonCerrar] Botón de cerrar agregado al body');
    }

    /**
     * Muestra una imagen en tamaño grande con navegación
     */
    mostrarImagen(indice) {
        if (this.imagenesActuales.length === 0) return;
        
        if (indice < 0) indice = 0;
        if (indice >= this.imagenesActuales.length) indice = this.imagenesActuales.length - 1;
        
        this.indiceActual = indice;
        
        // Cerrar modal anterior si existe
        if (this.modalActivo) {
            this.modalActivo.remove();
        }
        
        // Crear overlay
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
        `;
        
        // Crear contenedor principal
        const container = document.createElement('div');
        container.style.cssText = `
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        `;
        
        // Crear header con título y botón cerrar
        const header = document.createElement('div');
        header.style.cssText = `
            position: absolute;
            top: -60px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 12px 12px 0 0;
            color: white;
        `;
        
        const tituloElemento = document.createElement('div');
        tituloElemento.style.cssText = `
            font-size: 1.1rem;
            font-weight: 600;
            text-align: center;
            flex: 1;
        `;
        
        const btnCerrar = document.createElement('button');
        btnCerrar.innerHTML = '×';
        btnCerrar.style.cssText = `
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        `;
        btnCerrar.onmouseover = () => btnCerrar.style.background = 'rgba(255, 255, 255, 0.3)';
        btnCerrar.onmouseout = () => btnCerrar.style.background = 'rgba(255, 255, 255, 0.2)';
        btnCerrar.onclick = () => this.cerrarModal();
        
        header.appendChild(tituloElemento);
        header.appendChild(btnCerrar);
        
        // Crear contenedor de imagen con flechas
        const imageContainer = document.createElement('div');
        imageContainer.style.cssText = `
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
        
        // Botón anterior
        const btnAnterior = document.createElement('button');
        btnAnterior.innerHTML = '‹';
        btnAnterior.style.cssText = `
            position: absolute;
            left: -60px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            color: #1f2937;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        `;
        btnAnterior.onmouseover = () => btnAnterior.style.background = 'white';
        btnAnterior.onmouseout = () => btnAnterior.style.background = 'rgba(255, 255, 255, 0.9)';
        btnAnterior.onclick = () => this.navegarImagen('anterior');
        
        // Botón siguiente
        const btnSiguiente = document.createElement('button');
        btnSiguiente.innerHTML = '›';
        btnSiguiente.style.cssText = `
            position: absolute;
            right: -60px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            color: #1f2937;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        `;
        btnSiguiente.onmouseover = () => btnSiguiente.style.background = 'white';
        btnSiguiente.onmouseout = () => btnSiguiente.style.background = 'rgba(255, 255, 255, 0.9)';
        btnSiguiente.onclick = () => this.navegarImagen('siguiente');
        
        // Imagen principal
        const img = document.createElement('img');
        img.style.cssText = `
            max-width: 90vw;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        `;
        
        // Contador de imágenes
        const contador = document.createElement('div');
        contador.style.cssText = `
            position: absolute;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        `;
        
        // Ensamblar todo
        imageContainer.appendChild(btnAnterior);
        imageContainer.appendChild(img);
        imageContainer.appendChild(btnSiguiente);
        
        container.appendChild(header);
        container.appendChild(imageContainer);
        container.appendChild(contador);
        overlay.appendChild(container);
        document.body.appendChild(overlay);
        
        // Guardar referencia
        this.modalActivo = overlay;
        this.imgActual = img;
        this.tituloActual = tituloElemento;
        this.contadorActual = contador;
        this.btnAnterior = btnAnterior;
        this.btnSiguiente = btnSiguiente;
        
        // Configurar eventos
        this.configurarEventosModal();
        
        // Inicializar
        this.actualizarImagen();
    }

    /**
     * Navega entre imágenes
     */
    navegarImagen(direccion) {
        if (direccion === 'anterior') {
            this.indiceActual = (this.indiceActual - 1 + this.imagenesActuales.length) % this.imagenesActuales.length;
        } else {
            this.indiceActual = (this.indiceActual + 1) % this.imagenesActuales.length;
        }
        this.actualizarImagen();
    }

    /**
     * Actualiza la imagen actual
     */
    actualizarImagen() {
        if (this.imagenesActuales.length > 0 && this.imgActual) {
            const imagenActual = this.imagenesActuales[this.indiceActual];
            this.imgActual.src = imagenActual.src;
            this.imgActual.alt = imagenActual.titulo;
            this.tituloActual.textContent = imagenActual.titulo;
            this.contadorActual.textContent = `${this.indiceActual + 1} / ${this.imagenesActuales.length}`;
            
            // Ocultar botones si solo hay una imagen
            if (this.btnAnterior && this.btnSiguiente) {
                const mostrarBotones = this.imagenesActuales.length > 1;
                this.btnAnterior.style.display = mostrarBotones ? 'flex' : 'none';
                this.btnSiguiente.style.display = mostrarBotones ? 'flex' : 'none';
            }
        }
    }

    /**
     * Configura eventos del modal
     */
    configurarEventosModal() {
        if (!this.modalActivo) return;
        
        const handleKeydown = (e) => {
            if (e.key === 'Escape') this.cerrarModal();
            if (e.key === 'ArrowLeft') this.navegarImagen('anterior');
            if (e.key === 'ArrowRight') this.navegarImagen('siguiente');
        };
        
        document.addEventListener('keydown', handleKeydown);
        this.modalActivo.addEventListener('click', (e) => {
            if (e.target === this.modalActivo) this.cerrarModal();
        });
        
        // Guardar referencia para limpiar
        this.modalActivo._keydownHandler = handleKeydown;
    }

    /**
     * Cierra el modal de imagen
     */
    cerrarModal() {
        if (this.modalActivo) {
            if (this.modalActivo._keydownHandler) {
                document.removeEventListener('keydown', this.modalActivo._keydownHandler);
            }
            this.modalActivo.remove();
            this.modalActivo = null;
        }
    }
}

// Crear instancia global
window.insumosGaleria = new InsumosGaleria();

// Función global para compatibilidad
window.toggleGaleriaInsumos = function() {
    return window.insumosGaleria.toggle();
};

// Función toggleGaleria que redirige a nuestra implementación
window.toggleGaleria = function() {
    console.log('[toggleGaleria] Redirigiendo a insumosGaleria');
    console.log('[toggleGaleria] Estado actual - ReceiptManager disponible:', !!window.receiptManager);
    console.log('[toggleGaleria] Modal wrapper disponible:', !!document.getElementById('order-detail-modal-wrapper'));
    return window.insumosGaleria.toggle();
};

// Función toggleFactura para compatibilidad con botones flotantes
window.toggleFactura = function() {
    console.log('[toggleFactura] Toggle entre recibo y galería');
    
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    if (!modalWrapper) {
        console.error('[toggleFactura] No se encontró el modal wrapper');
        return;
    }
    
    const card = modalWrapper.querySelector('.order-detail-card');
    const galeria = document.getElementById('galeria-modal-costura');
    
    console.log('[toggleFactura] Estado actual - Card:', card ? 'visible' : 'no encontrado');
    console.log('[toggleFactura] Estado actual - Galería:', galeria ? (galeria.style.display === 'flex' ? 'visible' : 'oculta') : 'no encontrada');
    
    // Determinar estado actual
    const estaEnGaleria = galeria && galeria.style.display === 'flex';
    
    console.log('[toggleFactura] ¿Está en galería?:', estaEnGaleria);
    
    if (estaEnGaleria) {
        // Estamos en galería, volver al recibo
        console.log('[toggleFactura] Cerrando galería y mostrando recibo');
        window.insumosGaleria.cerrarGaleria(card, galeria);
    } else {
        // Estamos en recibo, mostrar galería
        console.log('[toggleFactura] Abriendo galería y ocultando recibo');
        window.insumosGaleria.abrirGaleria(card, galeria, modalWrapper);
    }
};

// Función global para cerrar el modal
window.cerrarModal = function() {
    console.log('[cerrarModal] Cerrando modal global');
    return window.insumosGaleria.cerrarModal();
};

// Función global para inicializar el botón X cuando se carga el recibo
window.inicializarBotonCerrarInsumos = function() {
    console.log('[inicializarBotonCerrarInsumos] Inicializando botón X para recibo');
    
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    if (!modalWrapper) {
        console.error('[inicializarBotonCerrarInsumos] No se encontró el modal wrapper');
        return;
    }
    
    const card = modalWrapper.querySelector('.order-detail-card');
    if (card) {
        window.insumosGaleria.agregarBotonCerrar(card);
        console.log('[inicializarBotonCerrarInsumos] Botón X agregado al recibo');
    } else {
        console.warn('[inicializarBotonCerrarInsumos] No se encontró el card del recibo');
    }
};
