/**
 * FormModule - Gestión de formularios
 * 
 * Single Responsibility: Manejo de estados y eventos del formulario
 * 
 * @module FormModule
 */
class FormModule {
    constructor() {
        this.formElement = document.getElementById('cotizacionPrendaForm');
        this.state = {
            cliente: '',
            asesora: '',
            fecha: new Date().toISOString().split('T')[0],
            tipo_cotizacion: '',
            productos: [],
            especificaciones: {}
        };
        this.errorHandlers = {};
    }

    /**
     * Inicializa el módulo
     */
    init() {
        this.syncHeaderWithForm();
        this.setupEventListeners();
        this.updateButtonStates();
    }

    /**
     * Sincroniza los valores del header con el formulario oculto
     * Single Responsibility: Solo sincronización de datos
     */
    syncHeaderWithForm() {
        const headerCliente = document.getElementById('header-cliente');
        const headerAsesor = document.getElementById('header-asesor');
        const headerTipo = document.getElementById('header-tipo-cotizacion');
        const headerFecha = document.getElementById('header-fecha');

        if (headerCliente) {
            headerCliente.addEventListener('input', (e) => {
                this.state.cliente = e.target.value;
                document.getElementById('cliente').value = e.target.value;
            });
        }

        if (headerTipo) {
            headerTipo.addEventListener('change', (e) => {
                this.state.tipo_cotizacion = e.target.value;
                document.getElementById('tipo_cotizacion').value = e.target.value;
                this.updateButtonStates();
            });
        }

        if (headerFecha) {
            headerFecha.addEventListener('change', (e) => {
                this.state.fecha = e.target.value;
                document.getElementById('fecha').value = e.target.value;
            });
        }

        if (headerAsesor) {
            this.state.asesora = headerAsesor.value;
            document.getElementById('asesora').value = headerAsesor.value;
        }
    }

    /**
     * Configura listeners de eventos
     */
    setupEventListeners() {
        const btnGuardarBorrador = document.getElementById('btnGuardarBorrador');
        const btnEnviar = document.getElementById('btnEnviar');

        if (btnGuardarBorrador) {
            btnGuardarBorrador.addEventListener('click', () => {
                this.handleSave('borrador');
            });
        }

        if (btnEnviar) {
            btnEnviar.addEventListener('click', () => {
                this.handleSave('enviar');
            });
        }
    }

    /**
     * Actualiza el estado de los botones según validación
     */
    updateButtonStates() {
        const tipoSeleccionado = this.state.tipo_cotizacion;
        const deshabilitado = !tipoSeleccionado;

        ['btnGuardarBorrador', 'btnEnviar'].forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (btn) {
                btn.disabled = deshabilitado;
                btn.style.opacity = deshabilitado ? '0.5' : '1';
                btn.style.cursor = deshabilitado ? 'not-allowed' : 'pointer';
            }
        });
    }

    /**
     * Valida el formulario antes de guardar
     * Single Responsibility: Solo validación
     */
    validate() {
        const errors = [];

        // Validar cliente
        const cliente = document.getElementById('header-cliente').value.trim();
        if (!cliente) {
            errors.push({
                field: 'cliente',
                message: 'Por favor escribe el NOMBRE DEL CLIENTE'
            });
        }

        // Validar tipo de cotización
        if (!this.state.tipo_cotizacion) {
            errors.push({
                field: 'tipo_cotizacion',
                message: 'Por favor selecciona el TIPO DE COTIZACIÓN (M, D o X)'
            });
        }

        // Validar que haya productos
        const productos = document.querySelectorAll('input[name*="nombre_producto"]');
        let tieneAlgunProducto = false;
        productos.forEach(input => {
            if (input.value.trim()) {
                tieneAlgunProducto = true;
            }
        });

        if (!tieneAlgunProducto) {
            errors.push({
                field: 'productos',
                message: 'Por favor agrega al menos una prenda'
            });
        }

        return { valid: errors.length === 0, errors };
    }

    /**
     * Maneja el guardado del formulario
     */
    async handleSave(action) {
        const validation = this.validate();

        if (!validation.valid) {
            this.showValidationErrors(validation.errors);
            return;
        }

        try {
            const formData = await this.buildFormData(action);
            const response = await this.submitForm(formData);

            if (response.success) {
                this.handleSuccess(response);
            } else {
                this.handleError(response);
            }
        } catch (error) {

            this.handleError({
                success: false,
                message: error.message
            });
        }
    }

    /**
     * Muestra errores de validación
     */
    showValidationErrors(errors) {
        errors.forEach(error => {
            const input = document.getElementById(`header-${error.field}`);
            if (input) {
                input.classList.add('campo-invalido');
                input.style.borderColor = '#ff4444';
            }
        });

        const message = errors.map(e => ` ${e.message}`).join('\n\n');
        alert(message);
    }

    /**
     * Construye el FormData para envío
     */
    async buildFormData(action) {
        const formData = new FormData();

        // Datos básicos
        const cliente = document.getElementById('header-cliente')?.value || '';
        const asesora = document.getElementById('header-asesor')?.value || '';
        const fecha = document.getElementById('header-fecha')?.value || '';
        const tipoVenta = document.getElementById('header-tipo-cotizacion')?.value || '';
        const token = document.querySelector('input[name="_token"]')?.value || '';
        // Validar campos requeridos
        if (!cliente) {

            throw new Error('El nombre del cliente es requerido');
        }
        if (!tipoVenta) {

            throw new Error('El tipo de venta (M/D/X) es requerido');
        }

        formData.append('cliente', cliente);
        formData.append('asesora', asesora);
        formData.append('fecha', fecha);
        formData.append('tipo_venta', tipoVenta);
        formData.append('tipo_cotizacion', 'P');
        formData.append('tipo', action === 'borrador' ? 'borrador' : 'enviada');
        formData.append('action', action);
        formData.append('_token', token);

        // Especificaciones
        if (window.especificacionesSeleccionadas) {
            formData.append('especificaciones', JSON.stringify(window.especificacionesSeleccionadas));
        }

        // Productos
        const productCards = document.querySelectorAll('.producto-card');

        
        for (let index = 0; index < productCards.length; index++) {
            try {
                await this.addProductToFormData(formData, productCards[index], index);
            } catch (error) {

                throw error;
            }
        }



        return formData;
    }

    /**
     * Agrega un producto al FormData
     */
    async addProductToFormData(formData, card, index) {
        const productoId = card.dataset.productoId;

        console.log(` Procesando producto ${index}:`, {
            productoId,
            fotosDisponibles: window.fotosSeleccionadas ? Object.keys(window.fotosSeleccionadas) : [],
            telasDisponibles: window.telasSeleccionadas ? Object.keys(window.telasSeleccionadas) : []
        });

        // Datos básicos del producto
        const nombre = card.querySelector('input[name*="nombre_producto"]')?.value || '';
        const descripcion = card.querySelector('textarea[name*="descripcion"]')?.value || '';
        const tallasInput = card.querySelector('input[name*="tallas"]')?.value || '';

        formData.append(`productos[${index}][nombre_producto]`, nombre);
        formData.append(`productos[${index}][descripcion]`, descripcion);

        // Tallas - Siempre enviar como array (vacío si no hay)
        const tallas = tallasInput 
            ? (typeof tallasInput === 'string' 
                ? tallasInput.split(',').map(t => t.trim()).filter(t => t)
                : [tallasInput])
            : [];
        
        // Enviar tallas como JSON para evitar problemas con FormData
        formData.append(`productos[${index}][tallas]`, JSON.stringify(tallas));

        // Fotos - Enviar archivos File directamente
        if (window.fotosSeleccionadas && window.fotosSeleccionadas[productoId]) {
            const fotos = window.fotosSeleccionadas[productoId];

            fotos.forEach((foto, fotoIdx) => {
                if (foto instanceof File) {
                    formData.append(`productos[${index}][fotos][${fotoIdx}]`, foto, foto.name);

                }
            });
        } else {

        }

        // Telas - Procesar múltiples telas por prenda
        // Capturar datos de cada fila de tela de la tabla
        const tblasRows = card.querySelectorAll('.fila-tela');

        
        tblasRows.forEach((row, rowIdx) => {
            const telaIndex = row.getAttribute('data-tela-index') || rowIdx;
            const colorIdInput = row.querySelector(`input[name*="[${telaIndex}][color_id]"]`);
            const telaIdInput = row.querySelector(`input[name*="[${telaIndex}][tela_id]"]`);
            const referenciaInput = row.querySelector(`input[name*="[${telaIndex}][referencia]"]`);
            
            const colorId = colorIdInput ? colorIdInput.value : null;
            const telaId = telaIdInput ? telaIdInput.value : null;
            const referencia = referenciaInput ? referenciaInput.value : null;
            });
            
            // Guardar datos básicos de la tela
            formData.append(`productos[${index}][telas][${telaIndex}][color_id]`, colorId || '');
            formData.append(`productos[${index}][telas][${telaIndex}][tela_id]`, telaId || '');
            formData.append(`productos[${index}][telas][${telaIndex}][referencia]`, referencia || '');
            
            // Agregar fotos de esta tela específica
            if (window.telasSeleccionadas && window.telasSeleccionadas[productoId] && window.telasSeleccionadas[productoId][telaIndex]) {
                const fotosDelaTela = window.telasSeleccionadas[productoId][telaIndex];

                
                fotosDelaTela.forEach((foto, fotoIdx) => {
                    if (foto instanceof File) {
                        formData.append(`productos[${index}][telas][${telaIndex}][fotos][${fotoIdx}]`, foto, foto.name);

                    }
                });
            }
        });

        // Variantes
        const inputs = card.querySelectorAll('input[name*="variantes"], select[name*="variantes"], textarea[name*="variantes"]');
        inputs.forEach(input => {
            const name = input.name;
            const match = name.match(/\[variantes\]\[([^\]]+)\]/);
            if (match) {
                const campo = match[1];
                const value = input.type === 'checkbox' ? (input.checked ? 1 : 0) : (input.value || '');
                if (value !== '') {
                    formData.append(`productos[${index}][variantes][${campo}]`, value);
                }
            }
        });
    }

    /**
     * Convierte un archivo a Base64
     */
    fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    /**
     * Envía el formulario al servidor
     */
    async submitForm(formData) {
        // Determinar la ruta según el tipo de cotización
        let url = '/asesores/cotizaciones/guardar'; // Por defecto para Prenda/Logo
        
        // Si es cotización de Prenda pura, usar ruta específica
        if (window.tipoCotizacionGlobal === 'P') {
            url = '/asesores/cotizaciones/prenda';
        }
        

        
        // Verificar que formData es válido
        if (!(formData instanceof FormData)) {

            throw new Error('FormData inválido');
        }
        

        
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
                // NO agregar headers - FormData maneja el Content-Type automáticamente
            });

            const responseData = await response.json();

            return responseData;
        } catch (error) {

            throw error;
        }
    }

    /**
     * Maneja respuesta exitosa
     */
    handleSuccess(data) {

        alert(data.message || 'Cotización guardada correctamente');
        if (data.redirect) {
            window.location.href = data.redirect;
        }
    }

    /**
     * Maneja errores en la respuesta
     */
    handleError(data) {

        let mensaje = data.message || 'Error desconocido';

        if (data.errors && Object.keys(data.errors).length > 0) {
            const errorList = Object.entries(data.errors)
                .map(([field, messages]) => {
                    const errorMsg = Array.isArray(messages) ? messages.join(', ') : messages;
                    return `• ${field}: ${errorMsg}`;
                })
                .join('\n');
            mensaje += '\n\nDetalles:\n' + errorList;
        }

        alert(' Error: ' + mensaje);
    }

    /**
     * Obtiene el estado actual del formulario
     */
    getState() {
        return { ...this.state };
    }
}

// Exportar para uso global
const formModule = new FormModule();

