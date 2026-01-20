/**
 * Modal Agregar EPP al Pedido
 * Consume API DDD para obtener EPP en tiempo real
 */

let productoSeleccionadoEPP = null;
let eppService = null;
let imagenesSubidasEPP = []; // Almacenar imágenes cargadas
let editandoEPPId = null; // Para saber si estamos editando
let eppItemsData = {}; // Almacenar datos de items EPP para edición

/**
 * Inicializar el servicio HTTP de EPP
 */
function inicializarEppService() {
    if (!eppService) {
        eppService = new EppHttpService('/api');
    }
}

/**
 * Crear y mostrar el modal dinámicamente
 */
function crearModalAgregarEPP() {
    // Si ya existe, no crear duplicado
    if (document.getElementById('modal-agregar-epp')) {
        return;
    }

    // Inicializar servicio
    inicializarEppService();

    const modalHTML = `
        <div id="modal-agregar-epp" class="modal-overlay" style="display: none;">
            <div class="modal-container" style="max-width: 500px;">
                <!-- Header -->
                <div class="modal-header modal-header-primary">
                    <h3 class="modal-title">
                        <span class="material-symbols-rounded">shield</span>Agregar EPP al Pedido
                    </h3>
                    <button class="modal-close-btn" onclick="cerrarModalAgregarEPP()">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
                
                <!-- Body -->
                <div class="modal-body">
                    <!-- Buscador -->
                    <div style="margin-bottom: 1.5rem; position: relative;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #1f2937; margin-bottom: 0.75rem;">Buscar por Referencia o Nombre</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <span class="material-symbols-rounded" style="position: absolute; left: 12px; color: #9ca3af; font-size: 20px; pointer-events: none;">search</span>
                            <input 
                                type="text" 
                                id="inputBuscadorEPP"
                                onkeyup="filtrarEPPBuscador(this.value); this.value = this.value.toUpperCase();"
                                placeholder="Ej. Casco, Nitrilo, Botas..." 
                                style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; transition: all 0.3s ease; font-family: inherit; text-transform: uppercase;"
                            >
                        </div>
                        <!-- Lista de resultados -->
                        <div id="resultadosBuscadorEPP" style="display: none; margin-top: 0.5rem; background: white; border: 1px solid #e5e7eb; border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 10; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);"></div>
                    </div>

                    <!-- Botón para crear EPP nuevo -->
                    <div style="display: flex; gap: 0.75rem; margin-bottom: 1.5rem;">
                        <button 
                            type="button"
                            onclick="mostrarFormularioCrearEPPNuevo()"
                            style="flex: 1; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; font-size: 0.95rem;"
                            onmouseover="this.style.background = '#1d4ed8';"
                            onmouseout="this.style.background = '#3b82f6';"
                        >
                            <span class="material-symbols-rounded" style="vertical-align: middle; font-size: 18px; margin-right: 0.5rem;">add</span>Crear EPP Nuevo
                        </button>
                    </div>

                    <!-- Formulario para crear EPP nuevo (inicialmente oculto) -->
                    <div id="formularioEPPNuevo" style="display: none; background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem;">
                        <h4 style="margin: 0 0 1rem 0; color: #1d4ed8; font-size: 0.95rem;">Crear EPP Nuevo</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <label style="font-size: 0.875rem; font-weight: 500; color: #1f2937; display: block; margin-bottom: 0.5rem;">Nombre *</label>
                                <input 
                                    type="text"
                                    id="nuevoEPPNombre"
                                    placeholder="Ej. Casco de Seguridad"
                                    style="width: 100%; padding: 0.75rem; border: 2px solid #bfdbfe; border-radius: 6px; font-size: 0.95rem; font-family: inherit;"
                                >
                            </div>
                            <div>
                                <label style="font-size: 0.875rem; font-weight: 500; color: #1f2937; display: block; margin-bottom: 0.5rem;">Categoría *</label>
                                <select 
                                    id="nuevoEPPCategoria"
                                    style="width: 100%; padding: 0.75rem; border: 2px solid #bfdbfe; border-radius: 6px; font-size: 0.95rem; font-family: inherit;"
                                >
                                    <option value="">Selecciona categoría</option>
                                    <option value="Cabeza">Cabeza</option>
                                    <option value="Manos">Manos</option>
                                    <option value="Pies">Pies</option>
                                    <option value="Cuerpo">Cuerpo</option>
                                    <option value="Oidos">Oídos</option>
                                    <option value="Ojos">Ojos</option>
                                    <option value="Respiratorio">Respiratorio</option>
                                    <option value="Otros">Otros</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <label style="font-size: 0.875rem; font-weight: 500; color: #1f2937; display: block; margin-bottom: 0.5rem;">Código *</label>
                                <input 
                                    type="text"
                                    id="nuevoEPPCodigo"
                                    placeholder="Ej. EPP-CAB-001"
                                    style="width: 100%; padding: 0.75rem; border: 2px solid #bfdbfe; border-radius: 6px; font-size: 0.95rem; font-family: inherit; text-transform: uppercase;"
                                    onkeyup="this.value = this.value.toUpperCase();"
                                >
                            </div>
                            <div>
                                <label style="font-size: 0.875rem; font-weight: 500; color: #1f2937; display: block; margin-bottom: 0.5rem;">Descripción</label>
                                <input 
                                    type="text"
                                    id="nuevoEPPDescripcion"
                                    placeholder="Ej. Casco de protección ABS"
                                    style="width: 100%; padding: 0.75rem; border: 2px solid #bfdbfe; border-radius: 6px; font-size: 0.95rem; font-family: inherit;"
                                >
                            </div>
                        </div>

                        <div style="display: flex; gap: 0.75rem;">
                            <button 
                                type="button"
                                onclick="crearEPPNuevoYAgregar()"
                                style="flex: 1; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;"
                                onmouseover="this.style.background = '#1d4ed8';"
                                onmouseout="this.style.background = '#3b82f6';"
                            >
                                Crear y Usar
                            </button>
                            <button 
                                type="button"
                                onclick="ocultarFormularioCrearEPP()"
                                style="flex: 1; padding: 0.75rem; background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;"
                                onmouseover="this.style.background = '#d1d5db';"
                                onmouseout="this.style.background = '#e5e7eb';"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>

                    <!-- Tarjeta Producto (inicialmente oculta) -->
                    <div id="productoCardEPP" style="display: none; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; animation: slideDown 0.3s ease;">
                        <div style="display: flex; flex-direction: column; justify-content: center; flex: 1;">
                            <span id="categoriaProductoEPP" style="display: inline-block; font-size: 0.7rem; font-weight: 700; color: #0066cc; letter-spacing: 0.5px; margin-bottom: 0.25rem; text-transform: uppercase;"></span>
                            <h3 id="nombreProductoEPP" style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1f2937; line-height: 1.4; margin-bottom: 0.25rem;"></h3>
                            <code id="codigoProductoEPP" style="font-size: 0.8rem; color: #6b7280;"></code>
                        </div>
                    </div>

                    <!-- Campos Medida/Talla y Cantidad -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="font-size: 0.875rem; font-weight: 500; color: #1f2937; display: block; margin-bottom: 0.5rem;">Medida / Talla</label>
                            <input 
                                type="text"
                                id="medidaTallaEPP"
                                oninput="this.value = this.value.toUpperCase(); actualizarEstilosBotonEPP();"
                                placeholder="S, M, L, XL, 40..."
                                disabled
                                style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem; font-family: inherit; background: #f3f4f6; color: #9ca3af; cursor: not-allowed; text-transform: uppercase;"
                            >
                        </div>
                        <div>
                            <label style="font-size: 0.875rem; font-weight: 500; color: #1f2937; display: block; margin-bottom: 0.5rem;">Cantidad</label>
                            <input 
                                type="number"
                                id="cantidadEPP"
                                min="1"
                                value="1"
                                oninput="actualizarEstilosBotonEPP();"
                                disabled
                                style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem; font-family: inherit; background: #f3f4f6; color: #9ca3af; cursor: not-allowed;"
                            >
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="font-size: 0.875rem; font-weight: 500; color: #1f2937; display: block; margin-bottom: 0.5rem;">Observaciones</label>
                        <textarea 
                            id="observacionesEPP"
                            placeholder="Ej. Requerimiento especial, notas..."
                            disabled
                            style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem; font-family: inherit; resize: vertical; min-height: 80px; background: #f3f4f6; color: #9ca3af; cursor: not-allowed; text-transform: uppercase;"
                        ></textarea>
                    </div>

                    <!-- Sección de Imágenes -->
                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: #fafafa; border: 1px dashed #d1d5db; border-radius: 8px;">
                        <label style="font-size: 0.875rem; font-weight: 600; color: #1f2937; display: block; margin-bottom: 0.75rem;">
                            <span class="material-symbols-rounded" style="vertical-align: middle; font-size: 18px; margin-right: 0.5rem;">image</span>Imágenes (Opcional)
                        </label>
                        
                        <!-- Área de carga de imágenes -->
                        <div id="areaCargarImagenes" style="display: none; margin-bottom: 1rem; padding: 1.5rem; background: white; border: 2px dashed #0066cc; border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.borderColor = '#0052a3'; this.style.background = '#f0f7ff';" onmouseout="this.style.borderColor = '#0066cc'; this.style.background = 'white';" onclick="document.getElementById('inputCargaImagenesEPP').click();">
                            <span class="material-symbols-rounded" style="font-size: 32px; color: #0066cc; margin-bottom: 0.5rem; display: block;">cloud_upload</span>
                            <p style="margin: 0; font-size: 0.95rem; font-weight: 500; color: #1f2937; margin-bottom: 0.25rem;">Arrastra imágenes o haz clic para seleccionar</p>
                            <p style="margin: 0; font-size: 0.8rem; color: #9ca3af;">JPG, PNG, WebP - Máximo 5MB</p>
                        </div>
                        
                        <!-- Input oculto -->
                        <input 
                            type="file" 
                            id="inputCargaImagenesEPP" 
                            multiple 
                            accept="image/jpeg,image/png,image/webp"
                            style="display: none;"
                            onchange="manejarSeleccionImagenes(event)"
                        >
                        
                        <!-- Lista de imágenes subidas -->
                        <div id="listaImagenesSubidas" style="display: none; margin-top: 1rem;">
                            <p style="font-size: 0.8rem; font-weight: 600; color: #6b7280; margin-bottom: 0.75rem; text-transform: uppercase;">Imágenes subidas:</p>
                            <div id="contenedorImagenesSubidas" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.75rem;"></div>
                        </div>

                        <!-- Mensaje cuando no hay EPP seleccionado -->
                        <div id="mensajeSelecccionarEPP" style="padding: 1rem; background: #fef3c7; border: 1px solid #fcd34d; border-radius: 6px; color: #92400e; font-size: 0.875rem; text-align: center;">
                            <span class="material-symbols-rounded" style="vertical-align: middle; font-size: 18px; margin-right: 0.5rem;">info</span>
                            Selecciona un EPP primero para agregar imágenes
                        </div>
                    </div>

                    <!-- Botones -->
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button 
                            class="btn-cancel" 
                            onclick="cerrarModalAgregarEPP()"
                            style="padding: 0.75rem 1.5rem; border: 1px solid #e5e7eb; background: white; color: #1f2937; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 0.95rem; transition: all 0.3s ease;"
                        >
                            Cancelar
                        </button>
                        <button 
                            id="btnAgregarEPP"
                            onclick="agregarEPPAlPedido()"
                            disabled
                            style="padding: 0.75rem 1.5rem; background: #0066cc; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: not-allowed; font-size: 0.95rem; opacity: 0.5; transition: all 0.3s ease;"
                        >
                            Agregar al Pedido
                        </button>
                    </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

/**
 * Abrir modal
 */
function abrirModalAgregarEPP() {
    inicializarEppService();
    
    const modal = document.getElementById('modal-agregar-epp');
    if (!modal) {
        crearModalAgregarEPP();
    }

    const modalElement = document.getElementById('modal-agregar-epp');
    modalElement.style.display = 'flex';

    // Limpiar estado
    limpiarModalEPP();

    // Cargar categorías para el formulario de nuevo EPP
    cargarCategoriasEnFormulario();

    // NO cargar EPP automáticamente - esperar a que el usuario escriba
    // cargarEPPBuscador();
}

/**
 * Cerrar modal
 */
function cerrarModalAgregarEPP() {
    const modal = document.getElementById('modal-agregar-epp');
    if (modal) {
        modal.style.display = 'none';
    }
    limpiarModalEPP();
}

/**
 * Mostrar formulario para crear EPP nuevo
 */
function mostrarFormularioCrearEPPNuevo() {
    document.getElementById('formularioEPPNuevo').style.display = 'block';
    document.getElementById('inputBuscadorEPP').value = '';
    document.getElementById('resultadosBuscadorEPP').style.display = 'none';
}

/**
 * Ocultar formulario para crear EPP nuevo
 */
function ocultarFormularioCrearEPP() {
    document.getElementById('formularioEPPNuevo').style.display = 'none';
    document.getElementById('nuevoEPPNombre').value = '';
    document.getElementById('nuevoEPPCategoria').value = '';
    document.getElementById('nuevoEPPCodigo').value = '';
    document.getElementById('nuevoEPPDescripcion').value = '';
}

/**
 * Crear EPP nuevo y agregarlo al pedido
 */
async function crearEPPNuevoYAgregar() {
    const nombre = document.getElementById('nuevoEPPNombre').value.trim();
    const categoria = document.getElementById('nuevoEPPCategoria').value.trim();
    const codigo = document.getElementById('nuevoEPPCodigo').value.trim();
    const descripcion = document.getElementById('nuevoEPPDescripcion').value.trim();

    // Validar campos obligatorios
    if (!nombre) {
        alert('Por favor ingresa el nombre del EPP');
        document.getElementById('nuevoEPPNombre').focus();
        return;
    }
    if (!categoria) {
        alert('Por favor selecciona la categoría');
        document.getElementById('nuevoEPPCategoria').focus();
        return;
    }
    if (!codigo) {
        alert('Por favor ingresa el código del EPP');
        document.getElementById('nuevoEPPCodigo').focus();
        return;
    }

    // Mostrar modal de cargando inmediatamente
    mostrarModalCargando('Creando EPP', 'Por favor espera...');

    try {
        // Crear EPP via API
        const response = await fetch('/api/epp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                nombre: nombre,
                categoria: categoria,
                codigo: codigo,
                descripcion: descripcion
            })
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error al crear EPP');
        }

        const eppNuevo = await response.json();
        
        // Actualizar modal a éxito
        actualizarModalAExito('EPP creado exitosamente', 'Ahora puedes agregar talla, cantidad e imágenes.');
        
        // Establecer como producto seleccionado
        productoSeleccionadoEPP = {
            id: eppNuevo.id || eppNuevo.data?.id,
            nombre: nombre,
            codigo: codigo,
            categoria: categoria
        };

        // Actualizar tarjeta
        document.getElementById('nombreProductoEPP').textContent = nombre;
        document.getElementById('categoriaProductoEPP').textContent = categoria;
        document.getElementById('codigoProductoEPP').textContent = codigo;
        document.getElementById('productoCardEPP').style.display = 'flex';

        // Limpiar y ocultar formulario
        ocultarFormularioCrearEPP();

        // Habilitar campos de talla, cantidad
        document.getElementById('medidaTallaEPP').disabled = false;
        document.getElementById('cantidadEPP').disabled = false;
        document.getElementById('observacionesEPP').disabled = false;
        document.getElementById('medidaTallaEPP').style.background = 'white';
        document.getElementById('medidaTallaEPP').style.color = '#1f2937';
        document.getElementById('medidaTallaEPP').style.cursor = 'text';
        document.getElementById('cantidadEPP').style.background = 'white';
        document.getElementById('cantidadEPP').style.color = '#1f2937';
        document.getElementById('cantidadEPP').style.cursor = 'text';
        document.getElementById('observacionesEPP').style.background = 'white';
        document.getElementById('observacionesEPP').style.color = '#1f2937';
        document.getElementById('observacionesEPP').style.cursor = 'text';

        // Mostrar área de carga de imágenes
        document.getElementById('areaCargarImagenes').style.display = 'block';
        document.getElementById('mensajeSelecccionarEPP').style.display = 'none';
        document.getElementById('listaImagenesSubidas').style.display = 'none';
        document.getElementById('contenedorImagenesSubidas').innerHTML = '';

        actualizarEstilosBotonEPP();
        
    } catch (error) {
        console.error('Error creando EPP:', error);
        // Actualizar modal a error
        actualizarModalAError('Error al crear EPP', error.message);
    }
}

/**
 * Mostrar modal de éxito
 */
function mostrarModalExito(titulo, mensaje) {
    const backdrop = document.createElement('div');
    backdrop.id = 'modal-exito-backdrop';
    backdrop.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;

    const modal = document.createElement('div');
    modal.style.cssText = `
        background: white;
        border-radius: 12px;
        padding: 2rem;
        max-width: 400px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        text-align: center;
        animation: slideUp 0.3s ease;
    `;

    modal.innerHTML = `
        <div style="margin-bottom: 1.5rem;">
            <div style="width: 60px; height: 60px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; margin-bottom: 1rem;">
                <span class="material-symbols-rounded" style="color: white; font-size: 32px;">check</span>
            </div>
        </div>
        <h3 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1.25rem; font-weight: 600;">${titulo}</h3>
        <p style="margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem; line-height: 1.5;">${mensaje}</p>
        <button onclick="cerrarModalExito()" style="padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;"
            onmouseover="this.style.background = '#1d4ed8';"
            onmouseout="this.style.background = '#3b82f6';">
            Entendido
        </button>
    `;

    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);

    // Agregar animación
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Mostrar modal de error
 */
function mostrarModalError(titulo, mensaje) {
    const backdrop = document.createElement('div');
    backdrop.id = 'modal-error-backdrop';
    backdrop.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;

    const modal = document.createElement('div');
    modal.style.cssText = `
        background: white;
        border-radius: 12px;
        padding: 2rem;
        max-width: 400px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        text-align: center;
        animation: slideUp 0.3s ease;
    `;

    modal.innerHTML = `
        <div style="margin-bottom: 1.5rem;">
            <div style="width: 60px; height: 60px; background: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; margin-bottom: 1rem;">
                <span class="material-symbols-rounded" style="color: white; font-size: 32px;">close</span>
            </div>
        </div>
        <h3 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1.25rem; font-weight: 600;">${titulo}</h3>
        <p style="margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem; line-height: 1.5;">${mensaje}</p>
        <button onclick="cerrarModalError()" style="padding: 0.75rem 1.5rem; background: #ef4444; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;"
            onmouseover="this.style.background = '#dc2626';"
            onmouseout="this.style.background = '#ef4444';">
            Cerrar
        </button>
    `;

    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);
}

/**
 * Mostrar modal de cargando
 */
function mostrarModalCargando(titulo, mensaje) {
    const backdrop = document.createElement('div');
    backdrop.id = 'modal-cargando-backdrop';
    backdrop.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;

    const modal = document.createElement('div');
    modal.id = 'modal-cargando';
    modal.style.cssText = `
        background: white;
        border-radius: 12px;
        padding: 2rem;
        max-width: 400px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        text-align: center;
        animation: slideUp 0.3s ease;
    `;

    modal.innerHTML = `
        <div style="margin-bottom: 1.5rem;">
            <div style="width: 60px; height: 60px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; margin-bottom: 1rem;">
                <div style="width: 40px; height: 40px; border: 4px solid rgba(59, 130, 246, 0.3); border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            </div>
        </div>
        <h3 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1.25rem; font-weight: 600;" id="modal-cargando-titulo">${titulo}</h3>
        <p style="margin: 0; color: #6b7280; font-size: 0.95rem; line-height: 1.5;" id="modal-cargando-mensaje">${mensaje}</p>
    `;

    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);

    // Agregar animación de spin si no existe
    if (!document.getElementById('spin-animation')) {
        const style = document.createElement('style');
        style.id = 'spin-animation';
        style.textContent = `
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
}

/**
 * Actualizar modal de cargando a éxito
 */
function actualizarModalAExito(titulo, mensaje) {
    const backdrop = document.getElementById('modal-cargando-backdrop');
    const modal = document.getElementById('modal-cargando');
    
    if (modal && backdrop) {
        // Actualizar contenido
        const contenedor = modal.querySelector('div:first-child');
        contenedor.innerHTML = `
            <div style="width: 60px; height: 60px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; margin-bottom: 1rem; animation: scaleIn 0.5s ease;">
                <span class="material-symbols-rounded" style="color: white; font-size: 32px;">check</span>
            </div>
        `;
        
        document.getElementById('modal-cargando-titulo').textContent = titulo;
        document.getElementById('modal-cargando-mensaje').textContent = mensaje;
        
        // Agregar botón
        const btn = document.createElement('button');
        btn.textContent = 'Entendido';
        btn.onclick = cerrarModalCargando;
        btn.style.cssText = `
            margin-top: 1.5rem;
            padding: 0.75rem 1.5rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        `;
        btn.onmouseover = function() { this.style.background = '#059669'; };
        btn.onmouseout = function() { this.style.background = '#10b981'; };
        
        modal.appendChild(btn);
        
        // Agregar animación si no existe
        if (!document.getElementById('scale-animation')) {
            const style = document.createElement('style');
            style.id = 'scale-animation';
            style.textContent = `
                @keyframes scaleIn {
                    from {
                        opacity: 0;
                        transform: scale(0.8);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1);
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
}

/**
 * Actualizar modal de cargando a error
 */
function actualizarModalAError(titulo, mensaje) {
    const backdrop = document.getElementById('modal-cargando-backdrop');
    const modal = document.getElementById('modal-cargando');
    
    if (modal && backdrop) {
        // Actualizar contenido
        const contenedor = modal.querySelector('div:first-child');
        contenedor.innerHTML = `
            <div style="width: 60px; height: 60px; background: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; margin-bottom: 1rem; animation: scaleIn 0.5s ease;">
                <span class="material-symbols-rounded" style="color: white; font-size: 32px;">close</span>
            </div>
        `;
        
        document.getElementById('modal-cargando-titulo').textContent = titulo;
        document.getElementById('modal-cargando-mensaje').textContent = mensaje;
        
        // Agregar botón
        const btn = document.createElement('button');
        btn.textContent = 'Cerrar';
        btn.onclick = cerrarModalCargando;
        btn.style.cssText = `
            margin-top: 1.5rem;
            padding: 0.75rem 1.5rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        `;
        btn.onmouseover = function() { this.style.background = '#dc2626'; };
        btn.onmouseout = function() { this.style.background = '#ef4444'; };
        
        modal.appendChild(btn);
    }
}

/**
 * Cerrar modal de cargando
 */
function cerrarModalCargando() {
    const backdrop = document.getElementById('modal-cargando-backdrop');
    if (backdrop) {
        backdrop.style.animation = 'slideDown 0.3s ease';
        setTimeout(() => {
            backdrop.remove();
        }, 300);
    }
}

/**
 * Cerrar modal de éxito
 */
function cerrarModalExito() {
    const backdrop = document.getElementById('modal-exito-backdrop');
    if (backdrop) {
        backdrop.style.animation = 'slideDown 0.3s ease';
        setTimeout(() => {
            backdrop.remove();
        }, 300);
    }
}

/**
 * Cerrar modal de error
 */
function cerrarModalError() {
    const backdrop = document.getElementById('modal-error-backdrop');
    if (backdrop) {
        backdrop.style.animation = 'slideDown 0.3s ease';
        setTimeout(() => {
            backdrop.remove();
        }, 300);
    }
}

/**
 * Limpiar estado del modal
 */
function limpiarModalEPP() {
    productoSeleccionadoEPP = null;
    imagenesSubidasEPP = []; // Limpiar imágenes cargadas
    editandoEPPId = null; // Limpiar modo edición
    document.getElementById('inputBuscadorEPP').value = '';
    document.getElementById('productoCardEPP').style.display = 'none';
    document.getElementById('medidaTallaEPP').value = '';
    document.getElementById('cantidadEPP').value = '1';
    document.getElementById('observacionesEPP').value = '';
    document.getElementById('resultadosBuscadorEPP').style.display = 'none';
    actualizarEstilosBotonEPP();
}

/**
 * Cargar categorías desde la API y llenar el select
 */
async function cargarCategoriasEnFormulario() {
    try {
        const categorias = await eppService.obtenerCategorias();
        const selectCategoria = document.getElementById('nuevoEPPCategoria');
        
        if (selectCategoria && categorias && categorias.length > 0) {
            // Mantener la opción por defecto
            const opcionDefault = selectCategoria.querySelector('option[value=""]');
            
            // Limpiar opciones excepto la de defecto
            while (selectCategoria.options.length > 1) {
                selectCategoria.remove(1);
            }
            
            // Agregar categorías de la BD
            categorias.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.nombre;
                option.textContent = cat.nombre;
                selectCategoria.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando categorías:', error);
    }
}

/**
 * Cargar EPP disponibles en el buscador
 */
async function cargarEPPBuscador() {
    try {
        const epps = await eppService.buscar();
        mostrarResultadosEPP(epps);
    } catch (error) {
        console.error('Error cargando EPP:', error);
        mostrarErrorEPP('Error cargando EPP');
    }
}

/**
 * Filtrar EPP por término de búsqueda
 */
async function filtrarEPPBuscador(valor) {
    // Si el input está vacío, no mostrar nada
    if (!valor.trim()) {
        document.getElementById('resultadosBuscadorEPP').style.display = 'none';
        return;
    }

    try {
        const epps = await eppService.buscar(valor);
        mostrarResultadosEPP(epps);
    } catch (error) {
        console.error('Error buscando EPP:', error);
        mostrarErrorEPP('Error buscando EPP');
    }
}

/**
 * Mostrar resultados de búsqueda
 */
function mostrarResultadosEPP(epps) {
    const container = document.getElementById('resultadosBuscadorEPP');
    
    if (!epps || epps.length === 0) {
        container.innerHTML = '<div style="padding: 1rem; color: #9ca3af; text-align: center;">No se encontraron EPP</div>';
        container.style.display = 'block';
        return;
    }

    container.innerHTML = epps.map(epp => `
        <div 
            onclick="seleccionarEPPDelBuscador(${epp.id}, '${epp.nombre}', '${epp.codigo}', '${epp.categoria}', '${epp.imagen_principal_url || ''}')"
            style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; cursor: pointer; display: flex; gap: 0.75rem; align-items: center; transition: background 0.2s ease;"
            onmouseover="this.style.background = '#f3f4f6';"
            onmouseout="this.style.background = 'transparent';"
        >
            <div style="width: 40px; height: 40px; border-radius: 4px; background: #e5e7eb; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <span class="material-symbols-rounded" style="font-size: 24px; color: #9ca3af;">shield</span>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 0.875rem; font-weight: 500; color: #1f2937;">${epp.nombre}</div>
                <div style="font-size: 0.75rem; color: #9ca3af;">${epp.codigo}</div>
            </div>
        </div>
    `).join('');

    container.style.display = 'block';
}

/**
 * Seleccionar EPP del buscador
 */
function seleccionarEPPDelBuscador(id, nombre, codigo, categoria, imagenUrl) {
    productoSeleccionadoEPP = { id, nombre, codigo, categoria };

    // Mostrar tarjeta sin imagen
    document.getElementById('nombreProductoEPP').textContent = nombre;
    document.getElementById('categoriaProductoEPP').textContent = categoria;
    document.getElementById('codigoProductoEPP').textContent = codigo;
    document.getElementById('productoCardEPP').style.display = 'flex';

    // Limpiar y resetear campos
    document.getElementById('medidaTallaEPP').value = '';
    document.getElementById('cantidadEPP').value = '1';
    document.getElementById('observacionesEPP').value = '';
    
    // Habilitar campos
    document.getElementById('medidaTallaEPP').disabled = false;
    document.getElementById('cantidadEPP').disabled = false;
    document.getElementById('observacionesEPP').disabled = false;
    document.getElementById('medidaTallaEPP').style.background = 'white';
    document.getElementById('medidaTallaEPP').style.color = '#1f2937';
    document.getElementById('medidaTallaEPP').style.cursor = 'text';
    document.getElementById('cantidadEPP').style.background = 'white';
    document.getElementById('cantidadEPP').style.color = '#1f2937';
    document.getElementById('cantidadEPP').style.cursor = 'text';
    document.getElementById('observacionesEPP').style.background = 'white';
    document.getElementById('observacionesEPP').style.color = '#1f2937';
    document.getElementById('observacionesEPP').style.cursor = 'text';

    // Ocultar buscador
    document.getElementById('resultadosBuscadorEPP').style.display = 'none';

    // Mostrar área de carga de imágenes
    document.getElementById('areaCargarImagenes').style.display = 'block';
    document.getElementById('mensajeSelecccionarEPP').style.display = 'none';
    document.getElementById('listaImagenesSubidas').style.display = 'none';
    document.getElementById('contenedorImagenesSubidas').innerHTML = '';

    actualizarEstilosBotonEPP();
}

/**
 * Actualizar estilos del botón de agregar
 */
function actualizarEstilosBotonEPP() {
    const btnAgregar = document.getElementById('btnAgregarEPP');
    const talla = document.getElementById('medidaTallaEPP').value.trim();
    const cantidad = parseInt(document.getElementById('cantidadEPP').value) || 0;

    if (productoSeleccionadoEPP && talla && cantidad > 0) {
        btnAgregar.disabled = false;
        btnAgregar.style.opacity = '1';
        btnAgregar.style.cursor = 'pointer';
        // Cambiar texto según si estamos editando o no
        btnAgregar.textContent = editandoEPPId ? 'Actualizar en Pedido' : 'Agregar al Pedido';
    } else {
        btnAgregar.disabled = true;
        btnAgregar.style.opacity = '0.5';
        btnAgregar.style.cursor = 'not-allowed';
    }
}

/**
 * Agregar o actualizar EPP al pedido
 */
async function agregarEPPAlPedido() {
    if (!productoSeleccionadoEPP) {
        alert('Selecciona un EPP primero');
        return;
    }

    const talla = document.getElementById('medidaTallaEPP').value.trim();
    const cantidad = parseInt(document.getElementById('cantidadEPP').value) || 0;
    const observaciones = document.getElementById('observacionesEPP').value.trim() || null;

    if (!talla) {
        alert('Especifica la talla');
        return;
    }

    if (cantidad <= 0) {
        alert('Cantidad debe ser mayor a 0');
        return;
    }

    try {
        if (editandoEPPId) {
            // Estamos editando: eliminar el item anterior visual y de window.itemsPedido
            const itemAnterior = document.querySelector(`.item-epp[data-item-id="${editandoEPPId}"]`);
            if (itemAnterior) {
                itemAnterior.remove();
            }
            
            //  REMOVER DEL ARRAY itemsPedido TAMBIÉN
            if (window.itemsPedido && Array.isArray(window.itemsPedido)) {
                const indexToRemove = window.itemsPedido.findIndex(item => item.tipo === 'epp' && item.epp_id === editandoEPPId);
                if (indexToRemove !== -1) {
                    window.itemsPedido.splice(indexToRemove, 1);
                    console.log(' EPP antiguo removido durante edición. Total items ahora:', window.itemsPedido.length);
                }
            }
            
            editandoEPPId = null; // Limpiar modo edición
        }
        
        // Crear item visual con imágenes cargadas
        crearItemEPP(
            productoSeleccionadoEPP.id,
            productoSeleccionadoEPP.nombre,
            productoSeleccionadoEPP.codigo,
            productoSeleccionadoEPP.categoria,
            talla,
            cantidad,
            observaciones,
            imagenesSubidasEPP // Pasar las imágenes cargadas
        );

        // Cerrar modal
        cerrarModalAgregarEPP();

    } catch (error) {
        console.error('Error agregando EPP:', error);
        alert('Error: ' + error.message);
    }
}

/**
 * Crear item visual de EPP en la lista
 */
function crearItemEPP(id, nombre, codigo, categoria, talla, cantidad, observaciones, imagenes = []) {
    const listaItems = document.getElementById('lista-items-pedido');
    
    // HTML para galería de imágenes si las hay
    const galeriaHTML = imagenes && imagenes.length > 0 ? `
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #bfdbfe;">
            <p style="margin: 0 0 0.75rem 0; font-size: 0.8rem; font-weight: 600; color: #0066cc; text-transform: uppercase; letter-spacing: 0.5px;">Imágenes</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 0.5rem;">
                ${imagenes.map(img => `
                    <div style="position: relative; border-radius: 4px; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb; aspect-ratio: 1;">
                        <img src="${img.url}" alt="${nombre}" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                    </div>
                `).join('')}
            </div>
        </div>
    ` : '';
    
    const itemHTML = `
        <div class="item-epp" data-item-id="${id}" data-item-tipo="epp" style="background: #eff6ff; border-left: 4px solid #0066cc; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; position: relative;">
            <!-- Header con título y menú -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span class="material-symbols-rounded" style="color: #0066cc; font-size: 20px;">shield</span>
                        <span style="font-size: 0.7rem; font-weight: 700; color: #0066cc; text-transform: uppercase; letter-spacing: 0.5px;">${categoria}</span>
                    </div>
                    <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1f2937;">${nombre}</h4>
                </div>
                
                <!-- Menú de 3 puntos -->
                <div style="position: relative;">
                    <button 
                        class="btn-menu-epp"
                        data-item-id="${id}"
                        style="background: none; border: none; cursor: pointer; padding: 0.25rem; color: #6b7280; font-size: 1.25rem; transition: color 0.2s ease;"
                        onmouseover="this.style.color = '#1f2937';"
                        onmouseout="this.style.color = '#6b7280';"
                    >
                        <span class="material-symbols-rounded">more_vert</span>
                    </button>
                    <div class="submenu-epp" data-item-id="${id}" style="display: none; position: absolute; top: 2rem; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); min-width: 140px; z-index: 100;">
                        <button 
                            type="button"
                            class="btn-editar-epp"
                            data-item-id="${id}"
                            style="display: block; width: 100%; padding: 0.75rem 1rem; text-align: left; background: none; border: none; cursor: pointer; font-size: 0.9rem; color: #1f2937; transition: background 0.2s ease; border-bottom: 1px solid #f3f4f6;"
                            onmouseover="this.style.background = '#f9fafb';"
                            onmouseout="this.style.background = 'transparent';"
                        >
                            <span class="material-symbols-rounded" style="vertical-align: middle; font-size: 18px; margin-right: 0.5rem;">edit</span>Editar
                        </button>
                        <button 
                            type="button"
                            class="btn-eliminar-epp"
                            data-item-id="${id}"
                            style="display: block; width: 100%; padding: 0.75rem 1rem; text-align: left; background: none; border: none; cursor: pointer; font-size: 0.9rem; color: #dc2626; transition: background 0.2s ease;"
                            onmouseover="this.style.background = '#fef2f2';"
                            onmouseout="this.style.background = 'transparent';"
                        >
                            <span class="material-symbols-rounded" style="vertical-align: middle; font-size: 18px; margin-right: 0.5rem;">delete</span>Eliminar
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Información -->
            <div style="flex: 1;">
                <p style="margin: 0; font-size: 0.85rem; color: #6b7280; margin-bottom: 0.75rem;">
                    <strong>Código:</strong> ${codigo} | <strong>Talla:</strong> ${talla} | <strong>Cantidad:</strong> ${cantidad}
                </p>
                ${observaciones ? `<p style="margin: 0; font-size: 0.85rem; color: #6b7280; font-style: italic;"><strong>Notas:</strong> ${observaciones}</p>` : ''}
                ${galeriaHTML}
            </div>
        </div>
    `;

    listaItems.insertAdjacentHTML('beforeend', itemHTML);
    
    // Almacenar datos del item para edición
    eppItemsData[id] = {
        id, nombre, codigo, categoria, talla, cantidad, observaciones, imagenes
    };
    
    // Obtener elementos del menú
    const btnMenu = document.querySelector(`[data-item-id="${id}"].btn-menu-epp`);
    const submenu = document.querySelector(`[data-item-id="${id}"].submenu-epp`);
    const btnEditar = document.querySelector(`[data-item-id="${id}"].btn-editar-epp`);
    const btnEliminar = document.querySelector(`[data-item-id="${id}"].btn-eliminar-epp`);
    
    if (btnMenu && submenu) {
        btnMenu.addEventListener('click', (e) => {
            e.stopPropagation();
            submenu.style.display = submenu.style.display === 'none' ? 'block' : 'none';
        });
    }
    
    if (btnEditar) {
        btnEditar.addEventListener('click', () => {
            // Recuperar los datos del item desde el mapa
            const datosItem = eppItemsData[id];
            if (datosItem) {
                editarItemEPP(datosItem.id, datosItem.nombre, datosItem.codigo, datosItem.categoria, datosItem.talla, datosItem.cantidad, datosItem.observaciones, datosItem.imagenes);
            }
            if (submenu) submenu.style.display = 'none';
        });
    }
    
    if (btnEliminar) {
        btnEliminar.addEventListener('click', () => {
            eliminarItemEPP(id);
            if (submenu) submenu.style.display = 'none';
        });
    }
    
    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest(`[data-item-id="${id}"]`)) {
            if (submenu) submenu.style.display = 'none';
        }
    });
    
    //  AGREGAR ITEM A window.itemsPedido PARA QUE SE INCLUYA EN EL FORMULARIO
    if (!window.itemsPedido) {
        window.itemsPedido = [];
    }
    
    // Crear objeto EPP en el formato esperado por el backend
    const itemEPP = {
        tipo: 'epp',
        epp_id: id,
        nombre: nombre,
        codigo: codigo,
        categoria: categoria,
        talla: talla,
        cantidad: cantidad,
        observaciones: observaciones || null,
        imagenes: imagenes || [],
        tallas_medidas: talla, // Campo requerido por PedidoEppService
    };
    
    console.log(' Agregando EPP a window.itemsPedido:', itemEPP);
    window.itemsPedido.push(itemEPP);
    console.log('📊 Total items en pedido después de EPP:', window.itemsPedido.length);
    
    // Actualizar contador si existe
    actualizarContadorItems();
}

/**
 * Eliminar item EPP
 */
function eliminarItemEPP(eppId) {
    if (!confirm('¿Eliminar este EPP del pedido?')) {
        return;
    }
    
    const itemDiv = document.querySelector(`.item-epp[data-item-id="${eppId}"]`);
    if (itemDiv) {
        itemDiv.remove();
        // Limpiar datos del mapa
        delete eppItemsData[eppId];
        
        //  REMOVER TAMBIÉN DE window.itemsPedido
        if (window.itemsPedido && Array.isArray(window.itemsPedido)) {
            const indexToRemove = window.itemsPedido.findIndex(item => item.tipo === 'epp' && item.epp_id === eppId);
            if (indexToRemove !== -1) {
                window.itemsPedido.splice(indexToRemove, 1);
                console.log(' EPP removido de window.itemsPedido. Total items ahora:', window.itemsPedido.length);
            }
        }
        
        actualizarContadorItems();
    }
}

/**
 * Editar item EPP
 */
function editarItemEPP(id, nombre, codigo, categoria, talla, cantidad, observaciones, imagenes) {
    // Establecer modo edición
    editandoEPPId = id;
    
    // Establecer valores en el modal
    productoSeleccionadoEPP = { id, nombre, codigo, categoria };
    imagenesSubidasEPP = imagenes || [];
    
    // Actualizar campos
    document.getElementById('nombreProductoEPP').textContent = nombre;
    document.getElementById('categoriaProductoEPP').textContent = categoria;
    document.getElementById('codigoProductoEPP').textContent = codigo;
    document.getElementById('productoCardEPP').style.display = 'flex';
    
    document.getElementById('medidaTallaEPP').value = talla;
    document.getElementById('cantidadEPP').value = cantidad;
    document.getElementById('observacionesEPP').value = observaciones || '';
    
    // Habilitar campos
    document.getElementById('medidaTallaEPP').disabled = false;
    document.getElementById('cantidadEPP').disabled = false;
    document.getElementById('observacionesEPP').disabled = false;
    document.getElementById('medidaTallaEPP').style.background = 'white';
    document.getElementById('medidaTallaEPP').style.color = '#1f2937';
    document.getElementById('medidaTallaEPP').style.cursor = 'text';
    document.getElementById('cantidadEPP').style.background = 'white';
    document.getElementById('cantidadEPP').style.color = '#1f2937';
    document.getElementById('cantidadEPP').style.cursor = 'text';
    document.getElementById('observacionesEPP').style.background = 'white';
    document.getElementById('observacionesEPP').style.color = '#1f2937';
    document.getElementById('observacionesEPP').style.cursor = 'text';
    
    // Mostrar imágenes cargadas
    document.getElementById('areaCargarImagenes').style.display = 'block';
    document.getElementById('mensajeSelecccionarEPP').style.display = 'none';
    
    const contenedorImagenes = document.getElementById('contenedorImagenesSubidas');
    contenedorImagenes.innerHTML = '';
    
    if (imagenes && imagenes.length > 0) {
        document.getElementById('listaImagenesSubidas').style.display = 'block';
        imagenes.forEach(img => {
            const card = document.createElement('div');
            card.id = `imagen-${img.id}`;
            card.style.cssText = 'position: relative; border-radius: 6px; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb;';
            card.innerHTML = `
                <img src="${img.url}" alt="Imagen" style="width: 100%; height: 80px; object-fit: cover; display: block;">
                <button 
                    type="button"
                    onclick="eliminarImagenCargada(${img.id})"
                    style="position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; background: rgba(255,0,0,0.8); color: white; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; padding: 0; transition: background 0.2s ease;"
                    onmouseover="this.style.background = 'rgba(220,0,0,1)'"
                    onmouseout="this.style.background = 'rgba(255,0,0,0.8)'"
                >
                    ×
                </button>
            `;
            contenedorImagenes.appendChild(card);
        });
    } else {
        document.getElementById('listaImagenesSubidas').style.display = 'none';
    }
    
    // Actualizar botón
    actualizarEstilosBotonEPP();
    
    // Abrir modal
    abrirModalAgregarEPP();
}

/**
 * Mostrar error
 */
function mostrarErrorEPP(mensaje) {
    const container = document.getElementById('resultadosBuscadorEPP');
    container.innerHTML = `<div style="padding: 1rem; color: #dc2626; text-align: center;">${mensaje}</div>`;
    container.style.display = 'block';
}

/**
 * Actualizar contador de items
 */
function actualizarContadorItems() {
    const cantidad = document.querySelectorAll('#lista-items-pedido > div').length;
    // Aquí puedes actualizar un contador si existe
    console.log('Cantidad de items:', cantidad);
}

/**
 * Manejar selección de imágenes
 */
async function manejarSeleccionImagenes(event) {
    const archivos = event.target.files;
    
    if (!productoSeleccionadoEPP) {
        alert('Selecciona un EPP primero');
        document.getElementById('inputCargaImagenesEPP').value = '';
        return;
    }

    if (archivos.length === 0) return;

    const contenedor = document.getElementById('contenedorImagenesSubidas');
    
    for (const archivo of archivos) {
        try {
            // Subir imagen
            const resultado = await eppService.subirImagen(
                productoSeleccionadoEPP.id,
                archivo,
                false // No marcar como principal
            );

            if (resultado) {
                // Guardar en variable para usar cuando se agregue el item
                imagenesSubidasEPP.push(resultado);
                
                // Agregar card de imagen
                const card = document.createElement('div');
                card.id = `imagen-${resultado.id}`;
                card.style.cssText = 'position: relative; border-radius: 6px; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb;';
                card.innerHTML = `
                    <img src="${resultado.url}" alt="Imagen" style="width: 100%; height: 80px; object-fit: cover; display: block;">
                    <button 
                        type="button"
                        onclick="eliminarImagenCargada(${resultado.id})"
                        style="position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; background: rgba(255,0,0,0.8); color: white; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; padding: 0; transition: background 0.2s ease;"
                        onmouseover="this.style.background = 'rgba(220,0,0,1)'"
                        onmouseout="this.style.background = 'rgba(255,0,0,0.8)'"
                    >
                        ×
                    </button>
                `;
                contenedor.appendChild(card);
            }
        } catch (error) {
            console.error('Error subiendo imagen:', error);
            alert('Error subiendo imagen: ' + error.message);
        }
    }

    // Mostrar lista de imágenes
    if (contenedor.children.length > 0) {
        document.getElementById('listaImagenesSubidas').style.display = 'block';
    }

    // Limpiar input
    document.getElementById('inputCargaImagenesEPP').value = '';
}

/**
 * Eliminar imagen cargada
 */
async function eliminarImagenCargada(imagenId) {
    if (!confirm('¿Eliminar esta imagen?')) return;

    try {
        await eppService.eliminarImagen(imagenId);
        
        // Eliminar de la variable
        imagenesSubidasEPP = imagenesSubidasEPP.filter(img => img.id !== imagenId);
        
        const card = document.getElementById(`imagen-${imagenId}`);
        if (card) {
            card.remove();
        }

        // Ocultar lista si no hay imágenes
        const contenedor = document.getElementById('contenedorImagenesSubidas');
        if (contenedor.children.length === 0) {
            document.getElementById('listaImagenesSubidas').style.display = 'none';
        }
    } catch (error) {
        console.error('Error eliminando imagen:', error);
        alert('Error eliminando imagen: ' + error.message);
    }
}

// Escuchar cambios en los inputs para habilitar/deshabilitar botón
document.addEventListener('DOMContentLoaded', function() {
    // Event listeners se agregarán cuando se cree el modal
    setTimeout(() => {
        const talla = document.getElementById('medidaTallaEPP');
        const cantidad = document.getElementById('cantidadEPP');
        
        if (talla) talla.addEventListener('input', actualizarEstilosBotonEPP);
        if (cantidad) cantidad.addEventListener('input', actualizarEstilosBotonEPP);
    }, 100);
});

// Crear modal al cargar la página
window.addEventListener('load', crearModalAgregarEPP);
