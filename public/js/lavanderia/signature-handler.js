/**
 * SIGNATURE HANDLER - Lavandería
 * Maneja la captura y visualización de firmas digitales
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
        this.resizeCanvas();
        window.addEventListener('resize', () => this.resizeCanvas());

        this.canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
        this.canvas.addEventListener('mousemove', (e) => this.draw(e));
        this.canvas.addEventListener('mouseup', () => this.stopDrawing());
        this.canvas.addEventListener('mouseout', () => this.stopDrawing());

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

class SignatureHandler {
    constructor(apiSearchUrl) {
        this.apiSearchUrl = apiSearchUrl;
        this.currentMovementId = null;
        this.signatureCapture = null;
        this.allMovements = [];
    }

    /**
     * Abre el modal de firma
     */
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

    /**
     * Abre el modal para ver firma
     */
    openModalVerFirma(firmaUrl) {
        const modal = document.getElementById('modalVerFirma');
        if (modal) {
            const firmaImg = modal.querySelector('#firmaImagenPreview');
            if (firmaImg) {
                firmaImg.src = '/' + firmaUrl;
                firmaImg.dataset.rotation = 0;
                firmaImg.style.transform = 'rotate(0deg) scale(1)';
            }
            
            modal.classList.add('active');

            if (firmaImg) {
                setTimeout(() => {
                    this.actualizarTransformacionFirma(firmaImg, 0);
                }, 100);
            }
        }
    }

    /**
     * Actualiza la transformación de la firma
     */
    actualizarTransformacionFirma(firmaImg, rotation) {
        if (!firmaImg) return;
        
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
            const scaleX = containerWidth / renderedHeight;
            const scaleY = containerHeight / renderedWidth;
            scale = Math.min(1, scaleX, scaleY);
        }
        
        firmaImg.style.transform = `rotate(${rotation}deg) scale(${scale})`;
    }

    /**
     * Rota la firma a la izquierda
     */
    rotarFirmaIzquierda() {
        const firmaImg = document.querySelector('#firmaImagenPreview');
        if (firmaImg) {
            let rotation = parseInt(firmaImg.dataset.rotation) || 0;
            rotation = (rotation - 90 + 360) % 360;
            firmaImg.dataset.rotation = rotation;
            this.actualizarTransformacionFirma(firmaImg, rotation);
        }
    }

    /**
     * Rota la firma a la derecha
     */
    rotarFirmaDerecha() {
        const firmaImg = document.querySelector('#firmaImagenPreview');
        if (firmaImg) {
            let rotation = parseInt(firmaImg.dataset.rotation) || 0;
            rotation = (rotation + 90) % 360;
            firmaImg.dataset.rotation = rotation;
            this.actualizarTransformacionFirma(firmaImg, rotation);
        }
    }

    /**
     * Guarda la firma
     */
    guardarFirma() {
        if (!this.signatureCapture) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Error', message: 'Canvas no inicializado', type: 'error' }
            }));
            return;
        }

        if (this.signatureCapture.isEmpty()) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Firma Requerida', message: 'Por favor dibuja tu firma', type: 'error' }
            }));
            return;
        }

        this.signatureCapture.canvas.toBlob((blob) => {
            const formData = new FormData();
            formData.append('movimiento_id', this.currentMovementId);
            formData.append('firma', blob, 'firma.webp');

            fetch(`${this.apiSearchUrl.replace('search-recibos', 'guardar-firma-salida')}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('showToast', { 
                        detail: { title: '¡Firma Guardada!', message: 'Tu firma se ha registrado exitosamente', type: 'success' }
                    }));
                    document.getElementById('modalFirmaSalida').classList.remove('active');
                    window.dispatchEvent(new CustomEvent('reloadMovements'));
                } else {
                    window.dispatchEvent(new CustomEvent('showToast', { 
                        detail: { title: 'Error', message: data.message || 'No se pudo guardar la firma', type: 'error' }
                    }));
                }
            })
            .catch(error => {
                console.error('Error al guardar firma:', error);
                window.dispatchEvent(new CustomEvent('showToast', { 
                    detail: { title: 'Error', message: 'Error al guardar la firma', type: 'error' }
                }));
            });
        }, 'image/webp', 0.95);
    }
}

export { SignatureCapture, SignatureHandler };
