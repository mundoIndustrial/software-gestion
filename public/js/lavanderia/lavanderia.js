/**
 * Clase para capturar firma digital con precisión
 * Soporta mouse y táctil con alta precisión
 */
class SignatureCapture {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.isDrawing = false;
        this.lastX = 0;
        this.lastY = 0;
        this.init();
    }

    init() {
        // Configurar canvas con DPI awareness
        this.resizeCanvas();
        window.addEventListener('resize', () => this.resizeCanvas());

        // Eventos de mouse
        this.canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
        this.canvas.addEventListener('mousemove', (e) => this.draw(e));
        this.canvas.addEventListener('mouseup', () => this.stopDrawing());
        this.canvas.addEventListener('mouseout', () => this.stopDrawing());

        // Eventos táctiles
        this.canvas.addEventListener('touchstart', (e) => this.startDrawing(e));
        this.canvas.addEventListener('touchmove', (e) => this.draw(e));
        this.canvas.addEventListener('touchend', () => this.stopDrawing());
        this.canvas.addEventListener('touchcancel', () => this.stopDrawing());
    }

    resizeCanvas() {
        const rect = this.canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;

        this.canvas.width = rect.width * dpr;
        this.canvas.height = rect.height * dpr;

        this.ctx.scale(dpr, dpr);
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
        this.ctx.lineWidth = 2;
        this.ctx.strokeStyle = '#1e293b';
    }

    getCoordinates(e) {
        const rect = this.canvas.getBoundingClientRect();
        let x, y;

        if (e.touches) {
            x = e.touches[0].clientX - rect.left;
            y = e.touches[0].clientY - rect.top;
        } else {
            x = e.clientX - rect.left;
            y = e.clientY - rect.top;
        }

        return { x, y };
    }

    startDrawing(e) {
        e.preventDefault();
        this.isDrawing = true;
        const { x, y } = this.getCoordinates(e);
        this.lastX = x;
        this.lastY = y;
    }

    draw(e) {
        if (!this.isDrawing) return;
        e.preventDefault();

        const { x, y } = this.getCoordinates(e);

        this.ctx.beginPath();
        this.ctx.moveTo(this.lastX, this.lastY);
        this.ctx.lineTo(x, y);
        this.ctx.stroke();

        this.lastX = x;
        this.lastY = y;
    }

    stopDrawing() {
        this.isDrawing = false;
    }

    clear() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    }

    getSignatureData() {
        return this.canvas.toDataURL('image/png');
    }

    isEmpty() {
        const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
        return imageData.data.every((pixel, index) => {
            return index % 4 === 3 ? pixel === 255 : pixel === 0;
        });
    }
}

/**
 * Módulo de Gestión de Lavandería
 * Maneja registros de salidas y llegadas con firma digital
 */
class LavanderiaManager {
    constructor() {
        this.currentRecibo = null;
        this.selectedTallas = [];
        this.currentMovementId = null;
        this.signatureCapture = null;
        this.allMovements = [];
        this.currentTab = 'salidas';
        this.currentPage = 1;
        this.itemsPerPage = 15;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupTabListeners();
        this.setupPaginationListeners();
        this.loadMovements();
    }

    showToast(title, message, type = 'success') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = type === 'success' ? '✓' : '✕';
        toast.innerHTML = `
            <div class="toast-icon">${icon}</div>
            <div class="toast-content">
                <p class="toast-title">${title}</p>
                <p class="toast-message">${message}</p>
            </div>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('removing');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    setupEventListeners() {
        // Búsqueda de movimientos en tiempo real
        const searchMovimientosInput = document.getElementById('searchMovimientosInput');
        if (searchMovimientosInput) {
            searchMovimientosInput.addEventListener('input', (e) => this.handleSearchMovimientos(e));
            searchMovimientosInput.addEventListener('blur', () => {
                setTimeout(() => {
                    const results = document.getElementById('searchResultsMovimientos');
                    if (results) results.classList.remove('active');
                }, 200);
            });
        }

        // Botón abrir modal salida
        const btnAbrirModal = document.getElementById('btnAbrirModalSalida');
        if (btnAbrirModal) {
            btnAbrirModal.addEventListener('click', () => this.openModalSalida());
        }

        // Búsqueda de recibo
        const searchInput = document.getElementById('searchRecibo');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e));
            searchInput.addEventListener('blur', () => {
                setTimeout(() => {
                    const results = document.querySelector('.autocomplete-results');
                    if (results) results.classList.remove('active');
                }, 200);
            });
        }

        // Botón registrar salida
        const btnRegistrar = document.getElementById('btnRegistrarSalida');
        if (btnRegistrar) {
            btnRegistrar.addEventListener('click', () => this.registrarSalida());
        }

        // Firma - Limpiar
        const btnLimpiar = document.getElementById('btnLimpiarFirma');
        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', () => {
                if (this.signatureCapture) {
                    this.signatureCapture.clear();
                }
            });
        }

        // Firma - Cancelar
        const btnCancelarFirma = document.getElementById('btnCancelarFirma');
        if (btnCancelarFirma) {
            btnCancelarFirma.addEventListener('click', () => {
                document.getElementById('modalFirmaSalida').classList.remove('active');
            });
        }

        // Firma - Guardar
        const btnGuardarFirma = document.getElementById('btnGuardarFirma');
        if (btnGuardarFirma) {
            btnGuardarFirma.addEventListener('click', () => this.guardarFirma());
        }

        // Modales
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.modal').classList.remove('active');
            });
        });

        // Cerrar modal al hacer clic fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
    }

    setupTabListeners() {
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tabType = e.currentTarget.dataset.tab;
                this.currentPage = 1; // Reiniciar a página 1 cuando cambias de tab
                this.filterMovementsByTab(tabType);
            });
        });
    }

    setupPaginationListeners() {
        const btnPrevPage = document.getElementById('btnPrevPage');
        const btnNextPage = document.getElementById('btnNextPage');

        if (btnPrevPage) {
            btnPrevPage.addEventListener('click', () => {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.renderPaginatedMovements();
                }
            });
        }

        if (btnNextPage) {
            btnNextPage.addEventListener('click', () => {
                const totalPages = this.getTotalPages();
                if (this.currentPage < totalPages) {
                    this.currentPage++;
                    this.renderPaginatedMovements();
                }
            });
        }
    }

    openModalSalida() {
        const modal = document.getElementById('modalSalida');
        if (modal) {
            modal.classList.add('active');
            this.clearSearch();
            this.currentRecibo = null;
            this.selectedTallas = [];
        }
    }

    clearSearch() {
        const searchInput = document.getElementById('searchRecibo');
        if (searchInput) searchInput.value = '';
        
        document.querySelector('.autocomplete-results').classList.remove('active');
        document.getElementById('reciboInfo').style.display = 'none';
    }

    handleSearch(e) {
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            document.querySelector('.autocomplete-results').classList.remove('active');
            return;
        }

        this.searchRecibos(query);
    }

    handleSearchMovimientos(e) {
        const query = e.target.value.trim();

        // Si está vacío, mostrar todos los movimientos del tab actual
        if (query.length === 0) {
            this.currentPage = 1;
            this.renderPaginatedMovements();
            return;
        }

        // Filtrar movimientos según el tab actual y la búsqueda
        const filteredMovements = this.getFilteredMovements().filter(m => 
            String(m.recibo).toLowerCase().includes(query.toLowerCase()) ||
            String(m.cliente).toLowerCase().includes(query.toLowerCase()) ||
            String(m.prenda).toLowerCase().includes(query.toLowerCase())
        );

        // Renderizar solo los resultados filtrados sin paginación
        this.renderMovements(filteredMovements);
        
        // Ocultar paginación cuando hay búsqueda
        const paginationContainer = document.getElementById('paginationContainer');
        if (paginationContainer) {
            paginationContainer.style.display = filteredMovements.length > 15 ? 'flex' : 'none';
        }
    }

    searchRecibos(query) {
        const results = document.querySelector('.autocomplete-results');
        results.innerHTML = '<div style="padding: 12px; text-align: center; color: #94a3b8;">Buscando...</div>';
        results.classList.add('active');

        fetch(`${apiSearchUrl}?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    this.renderSearchResults(data.data);
                } else {
                    results.innerHTML = '<div style="padding: 12px; text-align: center; color: #94a3b8;">No se encontraron recibos</div>';
                }
            })
            .catch(error => {
                console.error('Error en búsqueda:', error);
                results.innerHTML = '<div style="padding: 12px; text-align: center; color: #ef4444;">Error al buscar</div>';
            });
    }

    renderSearchResults(recibos) {
        const results = document.querySelector('.autocomplete-results');
        const searchInput = document.getElementById('searchRecibo');
        
        results.innerHTML = recibos.map(recibo => `
            <div class="autocomplete-item" data-recibo-id="${recibo.id}" data-recibo-data='${JSON.stringify(recibo)}'>
                <div style="display: flex; justify-content: space-between; align-items: start; gap: 8px;">
                    <div style="flex: 1;">
                        <strong style="color: #1e293b; display: block; margin-bottom: 2px;">
                            Recibo #${recibo.numero_recibo}
                        </strong>
                        <small style="color: #64748b; display: block; margin-bottom: 4px;">
                            ${recibo.cliente}
                        </small>
                        <small style="color: #94a3b8; display: block;">
                            ${recibo.prenda} • ${recibo.tipo_recibo}
                        </small>
                    </div>
                    <span style="background: #f0f4ff; color: #2450ef; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; white-space: nowrap;">
                        ${recibo.cantidad_total} prendas
                    </span>
                </div>
            </div>
        `).join('');
        
        const rect = searchInput.getBoundingClientRect();
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        
        results.style.position = 'fixed';
        results.style.top = (rect.bottom + scrollTop) + 'px';
        results.style.left = rect.left + 'px';
        results.style.width = rect.width + 'px';
        results.classList.add('active');

        document.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', () => this.selectRecibo(item));
        });
    }

    selectRecibo(item) {
        const reciboId = item.dataset.reciboId;
        const reciboData = JSON.parse(item.dataset.reciboData);
        
        this.currentRecibo = reciboData;
        this.selectedTallas = [];

        document.getElementById('searchRecibo').value = `Recibo #${reciboData.numero_recibo}`;
        document.querySelector('.autocomplete-results').classList.remove('active');

        this.showReciboInfo(reciboData);
    }

    showReciboInfo(recibo) {
        document.getElementById('infoCliente').textContent = recibo.cliente;
        document.getElementById('infoPrenda').textContent = recibo.prenda;
        document.getElementById('reciboInfo').style.display = 'block';
        this.renderTallas(recibo);
    }

    renderTallas(recibo) {
        const tallasContainer = document.getElementById('tallasContainer');
        const tallas = recibo.tallas || [];

        if (tallas.length === 0) {
            tallasContainer.innerHTML = '<p style="color: #94a3b8; text-align: center;">No hay tallas disponibles</p>';
            return;
        }

        tallasContainer.innerHTML = tallas.map((talla, index) => `
            <label class="talla-item" data-talla-index="${index}">
                <input type="checkbox" class="talla-checkbox" data-talla="${talla.talla}" data-cantidad-disponible="${talla.cantidad}" data-genero="${talla.genero || ''}">
                <div class="talla-item-content">
                    <div class="talla-nombre">${talla.talla}${talla.genero ? ' (' + talla.genero + ')' : ''}</div>
                    <div class="talla-cantidad">DISP: ${talla.cantidad} pzas</div>
                </div>
                <input type="number" class="talla-input-cantidad" value="${talla.cantidad}" min="0" max="${talla.cantidad}" style="display: none;">
            </label>
        `).join('');

        document.querySelectorAll('.talla-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => this.handleTallaChange(e));
        });

        document.querySelectorAll('.talla-input-cantidad').forEach(input => {
            input.addEventListener('change', (e) => this.handleCantidadChange(e));
        });
    }

    handleTallaChange(e) {
        const checkbox = e.target;
        const label = checkbox.closest('.talla-item');
        const input = label.querySelector('.talla-input-cantidad');
        const talla = checkbox.dataset.talla;
        const cantidadDisponible = parseInt(checkbox.dataset.cantidadDisponible);

        if (checkbox.checked) {
            label.classList.add('checked');
            input.style.display = 'block';
            input.value = cantidadDisponible;
            input.max = cantidadDisponible;
        } else {
            label.classList.remove('checked');
            input.style.display = 'none';
            this.selectedTallas = this.selectedTallas.filter(t => t.talla !== talla);
        }
    }

    handleCantidadChange(e) {
        const input = e.target;
        const label = input.closest('.talla-item');
        const checkbox = label.querySelector('.talla-checkbox');
        const talla = checkbox.dataset.talla;
        const cantidad = parseInt(input.value) || 0;
        const cantidadDisponible = parseInt(checkbox.dataset.cantidadDisponible);

        if (cantidad > cantidadDisponible) {
            input.value = cantidadDisponible;
            return;
        }

        const existingIndex = this.selectedTallas.findIndex(t => t.talla === talla);
        if (existingIndex >= 0) {
            this.selectedTallas[existingIndex].cantidad = cantidad;
        } else if (checkbox.checked) {
            this.selectedTallas.push({ 
                talla, 
                cantidad,
                genero: checkbox.dataset.genero
            });
        }
    }

    registrarSalida() {
        if (!this.currentRecibo) {
            this.showToast('Recibo Requerido', 'Por favor selecciona un recibo', 'error');
            return;
        }

        const tallasSeleccionadas = [];
        document.querySelectorAll('.talla-checkbox:checked').forEach(checkbox => {
            const label = checkbox.closest('.talla-item');
            const input = label.querySelector('.talla-input-cantidad');
            const cantidad = parseInt(input.value) || 0;

            if (cantidad > 0) {
                tallasSeleccionadas.push({
                    talla: checkbox.dataset.talla,
                    genero: checkbox.dataset.genero || '',
                    cantidad_enviada: cantidad
                });
            }
        });

        if (tallasSeleccionadas.length === 0) {
            this.showToast('Tallas Requeridas', 'Por favor selecciona al menos una talla con cantidad mayor a 0', 'error');
            return;
        }

        const novedad = document.getElementById('inputNovedad').value.trim();
        const tipoMovimiento = document.getElementById('selectTipoMovimiento').value;

        const datos = {
            recibo_id: parseInt(this.currentRecibo.id),
            numero_recibo: String(this.currentRecibo.numero_recibo),
            tipo_recibo: String(this.currentRecibo.tipo_recibo),
            tipo_movimiento: tipoMovimiento,
            novedad: novedad,
            tallas: tallasSeleccionadas
        };

        fetch(`${apiSearchUrl.replace('search-recibos', 'registrar-salida')}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(datos)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast('¡Movimiento Registrado!', 'El movimiento se ha registrado exitosamente');
                document.getElementById('modalSalida').classList.remove('active');
                this.loadMovements();
            } else {
                this.showToast('Error', data.message || 'No se pudo registrar el movimiento', 'error');
            }
        })
        .catch(error => {
            console.error('Error al registrar:', error);
            this.showToast('Error', 'Error al registrar el movimiento', 'error');
        });
    }

    openModalFirmaSalida(movementId) {
        this.currentMovementId = movementId;
        const modal = document.getElementById('modalFirmaSalida');
        if (modal) {
            modal.classList.add('active');
            
            setTimeout(() => {
                if (!this.signatureCapture) {
                    this.signatureCapture = new SignatureCapture('signatureCanvas');
                } else {
                    this.signatureCapture.clear();
                }
            }, 100);
        }
    }

    openModalVerFirma(firmaUrl) {
        const modal = document.getElementById('modalVerFirma');
        if (modal) {
            const firmaImg = modal.querySelector('#firmaImagenPreview');
            if (firmaImg) {
                firmaImg.src = '/' + firmaUrl;
                firmaImg.dataset.rotation = 0; // Inicializar rotación
                firmaImg.style.transform = 'rotate(0deg) scale(1)'; // Reset transform
            }
            
            // Buscar el movimiento para obtener la fecha_firma
            const movementCard = event.target.closest('.movement-card');
            if (movementCard) {
                const buttons = movementCard.querySelectorAll('[data-movement-id]');
                let movementId = null;
                buttons.forEach(btn => {
                    if (btn.dataset.movementId) {
                        movementId = btn.dataset.movementId;
                    }
                });
                
                const movement = this.allMovements.find(m => m.id == movementId);
                
                if (movement && movement.fechaFirma) {
                    // Mostrar fecha_firma en el modal
                    let fechaFirmaElement = modal.querySelector('#fechaFirmaDisplay');
                    if (!fechaFirmaElement) {
                        fechaFirmaElement = document.createElement('div');
                        fechaFirmaElement.id = 'fechaFirmaDisplay';
                        fechaFirmaElement.style.cssText = 'text-align: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid #f1f5f9;';
                        modal.querySelector('.modal-body').appendChild(fechaFirmaElement);
                    }
                    fechaFirmaElement.innerHTML = `
                        <p style="margin: 0; font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Fecha de Firma</p>
                        <p style="margin: 8px 0 0 0; font-size: 16px; color: #1e293b; font-weight: 600;">${movement.fechaFirma}</p>
                    `;
                }
            }
            
            modal.classList.add('active');

            // Recalcular una vez que el modal está activo y renderizado para asegurar dimensiones correctas
            if (firmaImg) {
                setTimeout(() => {
                    this.actualizarTransformacionFirma(firmaImg, 0);
                }, 100);
            }
        }
    }

    actualizarTransformacionFirma(firmaImg, rotation) {
        if (!firmaImg) return;
        
        // Reset transform para calcular el tamaño original renderizado por CSS
        firmaImg.style.transform = 'none';
        
        const renderedWidth = firmaImg.offsetWidth;
        const renderedHeight = firmaImg.offsetHeight;
        
        const container = firmaImg.parentElement;
        if (!container) {
            firmaImg.style.transform = `rotate(${rotation}deg)`;
            return;
        }
        
        const containerStyle = window.getComputedStyle(container);
        const containerWidth = container.clientWidth - parseFloat(containerStyle.paddingLeft) - parseFloat(containerStyle.paddingRight);
        const containerHeight = container.clientHeight - parseFloat(containerStyle.paddingTop) - parseFloat(containerStyle.paddingBottom);
        
        const isRotated = (rotation % 180 !== 0);
        
        let scale = 1;
        if (isRotated && renderedWidth > 0 && renderedHeight > 0) {
            // Dimensiones visuales rotadas: ancho es renderedHeight, alto es renderedWidth
            const scaleX = containerWidth / renderedHeight;
            const scaleY = containerHeight / renderedWidth;
            scale = Math.min(1, scaleX, scaleY);
        }
        
        firmaImg.style.transform = `rotate(${rotation}deg) scale(${scale})`;
    }

    rotarFirmaIzquierda() {
        const firmaImg = document.querySelector('#firmaImagenPreview');
        if (firmaImg) {
            let rotation = parseInt(firmaImg.dataset.rotation) || 0;
            rotation = (rotation - 90 + 360) % 360;
            firmaImg.dataset.rotation = rotation;
            this.actualizarTransformacionFirma(firmaImg, rotation);
        }
    }

    rotarFirmaDerecha() {
        const firmaImg = document.querySelector('#firmaImagenPreview');
        if (firmaImg) {
            let rotation = parseInt(firmaImg.dataset.rotation) || 0;
            rotation = (rotation + 90) % 360;
            firmaImg.dataset.rotation = rotation;
            this.actualizarTransformacionFirma(firmaImg, rotation);
        }
    }

    guardarFirma() {
        if (!this.signatureCapture) {
            this.showToast('Error', 'Canvas no inicializado', 'error');
            return;
        }

        if (this.signatureCapture.isEmpty()) {
            this.showToast('Firma Requerida', 'Por favor dibuja tu firma', 'error');
            return;
        }

        // Convertir canvas a Blob en formato WebP
        this.signatureCapture.canvas.toBlob((blob) => {
            const formData = new FormData();
            formData.append('movimiento_id', this.currentMovementId);
            formData.append('firma', blob, 'firma.webp');

            fetch(`${apiSearchUrl.replace('search-recibos', 'guardar-firma-salida')}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showToast('¡Firma Guardada!', 'Tu firma se ha registrado exitosamente');
                    document.getElementById('modalFirmaSalida').classList.remove('active');
                    this.loadMovements();
                } else {
                    this.showToast('Error', data.message || 'No se pudo guardar la firma', 'error');
                }
            })
            .catch(error => {
                console.error('Error al guardar firma:', error);
                this.showToast('Error', 'Error al guardar la firma', 'error');
            });
        }, 'image/webp', 0.95);
    }

    loadMovements() {
        const apiUrl = apiSearchUrl.replace('search-recibos', 'movimientos');
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.allMovements = data.data;
                    this.currentPage = 1; // Reiniciar a página 1
                    this.renderPaginatedMovements();
                } else {
                    console.error('Error al cargar movimientos:', data.message);
                    this.allMovements = [];
                    this.renderMovements([]);
                }
            })
            .catch(error => {
                console.error('Error en loadMovements:', error);
                this.allMovements = [];
                this.renderMovements([]);
            });
    }

    filterMovementsByTab(tabType) {
        this.currentTab = tabType;

        // Actualizar botones de tab
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.tab === tabType) {
                btn.classList.add('active');
            }
        });

        // Renderizar movimientos paginados
        this.renderPaginatedMovements();
    }

    getFilteredMovements() {
        let filteredMovements = this.allMovements;

        if (this.currentTab === 'salidas') {
            filteredMovements = this.allMovements.filter(m => m.tipoMovimiento === 'SALIDA');
        } else if (this.currentTab === 'entradas') {
            filteredMovements = this.allMovements.filter(m => m.tipoMovimiento === 'ENTRADA');
        }

        return filteredMovements;
    }

    getTotalPages() {
        const filteredMovements = this.getFilteredMovements();
        return Math.ceil(filteredMovements.length / this.itemsPerPage);
    }

    getPaginatedMovements() {
        const filteredMovements = this.getFilteredMovements();
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        return filteredMovements.slice(startIndex, endIndex);
    }

    renderPaginatedMovements() {
        const paginatedMovements = this.getPaginatedMovements();
        this.renderMovements(paginatedMovements);
        this.renderPagination();
    }

    renderPagination() {
        const totalPages = this.getTotalPages();
        const paginationContainer = document.getElementById('paginationContainer');
        const pageNumbers = document.getElementById('pageNumbers');

        if (totalPages <= 1) {
            paginationContainer.style.display = 'none';
            return;
        }

        paginationContainer.style.display = 'flex';

        // Limpiar números de página
        pageNumbers.innerHTML = '';

        // Calcular rango de páginas a mostrar
        let startPage = Math.max(1, this.currentPage - 2);
        let endPage = Math.min(totalPages, this.currentPage + 2);

        // Mostrar primera página si no está en el rango
        if (startPage > 1) {
            pageNumbers.innerHTML += `<button class="page-number" data-page="1" style="padding: 6px 10px; background: #f1f5f9; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; color: #1e293b;">1</button>`;
            if (startPage > 2) {
                pageNumbers.innerHTML += `<span style="color: #94a3b8; padding: 0 4px;">...</span>`;
            }
        }

        // Mostrar páginas en el rango
        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === this.currentPage;
            const bgColor = isActive ? '#2450ef' : '#f1f5f9';
            const textColor = isActive ? 'white' : '#1e293b';
            pageNumbers.innerHTML += `<button class="page-number" data-page="${i}" style="padding: 6px 10px; background: ${bgColor}; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; color: ${textColor};">${i}</button>`;
        }

        // Mostrar última página si no está en el rango
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                pageNumbers.innerHTML += `<span style="color: #94a3b8; padding: 0 4px;">...</span>`;
            }
            pageNumbers.innerHTML += `<button class="page-number" data-page="${totalPages}" style="padding: 6px 10px; background: #f1f5f9; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; color: #1e293b;">${totalPages}</button>`;
        }

        // Agregar event listeners a los números de página
        document.querySelectorAll('.page-number').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.currentPage = parseInt(e.target.dataset.page);
                this.renderPaginatedMovements();
            });
        });

        // Actualizar estado de botones prev/next
        const btnPrevPage = document.getElementById('btnPrevPage');
        const btnNextPage = document.getElementById('btnNextPage');

        if (btnPrevPage) {
            btnPrevPage.style.opacity = this.currentPage === 1 ? '0.5' : '1';
            btnPrevPage.style.cursor = this.currentPage === 1 ? 'not-allowed' : 'pointer';
        }

        if (btnNextPage) {
            btnNextPage.style.opacity = this.currentPage === totalPages ? '0.5' : '1';
            btnNextPage.style.cursor = this.currentPage === totalPages ? 'not-allowed' : 'pointer';
        }
    }

    renderMovements(movements) {
        const container = document.getElementById('movementsContainer');
        if (!container) return;

        if (movements.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="padding: 60px 20px;">
                    <div class="empty-icon">📦</div>
                    <h3 class="empty-title">Sin movimientos</h3>
                    <p class="empty-text">No hay registros en esta categoría</p>
                </div>
            `;
            return;
        }

        container.innerHTML = movements.map(m => {
            const tallasHtml = m.tallas.map(t => 
                `<span class="talla-badge">Talla ${t.talla}: ${t.cantidad_enviada}</span>`
            ).join('');

            const firmaBadgeClass = m.estadoFirma === 'FIRMADO' ? 'badge-firmado' : 'badge-pendiente';
            const firmaIcon = m.estadoFirma === 'FIRMADO' ? 'check_circle' : 'schedule';
            
            // Determinar el tipo de movimiento y su badge
            const tipoMovimiento = m.tipoMovimiento || 'SALIDA';
            const tipoMovimientoBadgeClass = tipoMovimiento === 'ENTRADA' ? 'badge-entrada' : 'badge-salida';
            const tipoMovimientoIcon = tipoMovimiento === 'ENTRADA' ? 'arrow_downward' : 'arrow_upward';
            
            // Mostrar novedad si existe
            const novedadHtml = m.novedad 
                ? `<div class="card-section">
                    <div class="card-label">Novedad</div>
                    <p style="margin: 8px 0 0 0; font-size: 14px; color: #1e293b;">${m.novedad}</p>
                  </div>`
                : '';

            const firmaButtonText = tipoMovimiento === 'ENTRADA' ? 'Firmar entrada' : 'Firmar salida';
            const firmaButtonHtml = m.estadoFirma === 'PENDIENTE FIRMA' 
                ? `<button class="btn-firmar-salida-card btn-firmar-salida" data-movement-id="${m.id}">
                    <span class="material-symbols-rounded">edit</span>
                    ${firmaButtonText}
                  </button>`
                : '';

            return `
                <div class="movement-card">
                    <div class="card-header-top">
                        <div class="card-section">
                            <div class="card-label">Recibo / Tipo</div>
                            <div class="card-value">${m.recibo}</div>
                        </div>
                        <div class="card-fecha">
                            ${m.fechaMovimiento}
                        </div>
                    </div>

                    <div class="card-divider"></div>

                    <div class="card-section">
                        <div class="card-label">Cliente</div>
                        <div class="card-value">${m.cliente}</div>
                    </div>

                    <div class="card-divider"></div>

                    <div class="card-section">
                        <div class="card-label">Prenda / Tallas</div>
                        <div class="card-value">${m.prenda}</div>
                        <div class="tallas-enviadas" style="margin-top: 8px;">
                            ${tallasHtml}
                        </div>
                    </div>

                    <div class="card-divider"></div>

                    <div class="card-section-row">
                        <div class="card-section">
                            <div class="card-label">Tipo de Movimiento</div>
                            <span class="badge ${tipoMovimientoBadgeClass}">
                                <span class="material-symbols-rounded badge-icon">${tipoMovimientoIcon}</span>
                                <span>${tipoMovimiento}</span>
                            </span>
                        </div>

                        <div class="card-section">
                            <div class="card-label">Estado</div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="badge ${firmaBadgeClass}">
                                    <span class="material-symbols-rounded badge-icon">${firmaIcon}</span>
                                    <span>${m.estadoFirma}</span>
                                </span>
                                ${m.firmaMovimiento && m.firmaMovimiento !== 'pendiente' ? `<button class="btn-ver-firma" data-firma-url="${m.firmaMovimiento}" data-movement-id="${m.id}" style="padding: 4px 8px; font-size: 11px;">Ver Firma</button>` : ''}
                            </div>
                        </div>
                    </div>

                    ${novedadHtml ? '<div class="card-divider"></div>' + novedadHtml : ''}

                    ${firmaButtonHtml ? '<div class="card-divider"></div>' + firmaButtonHtml : ''}
                </div>
            `;
        }).join('');

        document.querySelectorAll('.btn-firmar-salida').forEach(btn => {
            btn.addEventListener('click', (e) => this.openModalFirmaSalida(e.target.closest('button').dataset.movementId));
        });

        document.querySelectorAll('.btn-ver-firma').forEach(btn => {
            btn.addEventListener('click', (e) => this.openModalVerFirma(e.target.closest('button').dataset.firmaUrl));
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Esperar a que todos los estilos estén cargados
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hideLoadingScreen);
    } else {
        hideLoadingScreen();
    }

    // También esperar a que todas las imágenes y recursos estén cargados
    window.addEventListener('load', hideLoadingScreen);
});

function hideLoadingScreen() {
    const loadingScreen = document.getElementById('loadingScreen');
    if (loadingScreen) {
        loadingScreen.style.opacity = '0';
        loadingScreen.style.transition = 'opacity 0.3s ease-out';
        setTimeout(() => {
            loadingScreen.style.display = 'none';
        }, 300);
    }
}

window.lavanderiaManager = new LavanderiaManager();
