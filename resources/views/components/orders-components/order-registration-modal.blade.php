@props(['show' => false, 'areaOptions'])

<x-modal name="order-registration" :show="$show" maxWidth="2xl">
    <div class="modern-modal-container">
        <div class="modal-header">
            <div class="header-content">
                <div class="icon-wrapper">
                    <svg class="header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <h2 class="modal-title">Nueva Orden</h2>
            </div>
        </div>

        <form id="orderRegistrationForm" x-data="orderRegistration" x-init="init()" x-bind:data-context="getContext()">
            <!-- Mensaje Emergente -->
            <template x-if="showSuccessMessage || showErrorMessage">
                <div class="overlay" x-show="showSuccessMessage || showErrorMessage" 
                     @click="showSuccessMessage = false; showErrorMessage = false"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100">
                    <div class="notification" 
                         :class="showSuccessMessage ? 'notification-success' : 'notification-error'"
                         x-text="showSuccessMessage ? successMessage : errorMessage"
                         @click.stop
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-90"
                         x-transition:enter-end="opacity-100 scale-100">
                    </div>
                </div>
            </template>

            <div class="form-content">
                <!-- Información Principal -->
                <div class="section-card">
                    <h3 class="section-title">Información General</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Pedido
                            </label>
                            <input type="number" id="pedido" name="pedido" class="form-input" placeholder="000" required />
                            <p id="pedidoError" class="error-message hidden"></p>
                            <label class="checkbox-label">
                                <input type="checkbox" id="allowAnyPedido" class="form-checkbox" />
                                <span>Permitir cualquier número</span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Estado
                            </label>
                            <select id="estado" name="estado" class="form-select" required>
                                <option value="No iniciado">No iniciado</option>
                                <option value="En Ejecución">En Ejecución</option>
                                <option value="Entregado">Entregado</option>
                                <option value="Anulada">Anulada</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Cliente
                            </label>
                            <input type="text" id="cliente" name="cliente" class="form-input" placeholder="Nombre del cliente" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Área
                            </label>
                            <select id="area" name="area" class="form-select" required>
                                <option value="Creación Orden">Creación Orden</option>
                                @foreach($areaOptions as $areaOption)
                                    <option value="{{ $areaOption }}">{{ $areaOption }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="2"/>
                                    <path d="M16 2v4M8 2v4M3 10h18" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Fecha
                            </label>
                            <input type="date" id="fecha_creacion" name="fecha_creacion" class="form-input" required />
                        </div>

                        <template x-if="!isBodega">
                            <div class="form-group">
                                <label class="form-label">
                                    <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                    Encargado
                                </label>
                                <input type="text" id="encargado" name="encargado" class="form-input" placeholder="Nombre del encargado" />
                            </div>
                        </template>

                        <template x-if="!isBodega">
                            <div class="form-group">
                                <label class="form-label">
                                    <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                    Asesora
                                </label>
                                <input type="text" id="asesora" name="asesora" class="form-input" placeholder="Nombre de la asesora" />
                            </div>
                        </template>

                        <template x-if="!isBodega">
                            <div class="form-group">
                                <label class="form-label">
                                    <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                    Forma de Pago
                                </label>
                                <input type="text" id="forma_pago" name="forma_pago" class="form-input" placeholder="Método de pago" />
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Prendas -->
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">Prendas</h3>
                        <button type="button" id="añadirPrendaBtn" class="btn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>

                    <div id="prendasContainer" class="prendas-container">
                        <div class="prenda-card" data-prenda-index="0">
                            <div class="prenda-header">
                                <span class="prenda-number">1</span>
                                <button type="button" class="btn-delete eliminar-prenda-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="prenda-content">
                                <div class="form-group">
                                    <label class="form-label-small">Descripción de la prenda</label>
                                    <input type="text" name="prenda[0]" class="form-input-compact" placeholder="Ej: Polo roja" required />
                                </div>

                                <div class="form-group">
                                    <label class="form-label-small">Detalles adicionales</label>
                                    <textarea name="descripcion[0]" rows="2" class="form-textarea" placeholder="Ej: Pegar bolsillo en la parte frontal"></textarea>
                                </div>

                                <div class="tallas-section">
                                    <label class="form-label-small">Tallas y Cantidades</label>
                                    <div class="tallas-list"></div>
                                    <button type="button" class="btn-add-talla añadir-talla-btn" data-prenda-index="0">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                        Añadir talla
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="form-actions">
                    <button type="button" id="cancelarBtn" class="btn btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Cancelar
                    </button>
                    <button type="submit" id="guardarBtn" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M5 13l4 4L19 7" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Guardar Orden
                    </button>
                </div>
            </div>
        </form>
    </div>

    <style>
        /* Estilos específicos para el modal de registro de orden */
        .order-registration-modal .modern-modal-container {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            min-height: 600px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            z-index: 1100;
        }

        .order-registration-modal .modal-header {
            background: rgba(255, 157, 88, 0.1);
            backdrop-filter: blur(10px);
            padding: 24px 32px;
            border-bottom: 1px solid rgba(255, 157, 88, 0.2);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .order-registration-modal .header-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .order-registration-modal .icon-wrapper {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(255, 157, 88, 0.3);
        }

        .order-registration-modal .header-icon {
            width: 28px;
            height: 28px;
            color: white;
        }

        .order-registration-modal .modal-title {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        .order-registration-modal .form-content {
            padding: 32px;
        }

        .order-registration-modal .section-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }

        .order-registration-modal .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
            margin: 0 0 20px 0;
        }

        .order-registration-modal .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .order-registration-modal .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .order-registration-modal .form-group {
            display: flex;
            flex-direction: column;
        }

        .order-registration-modal .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 500;
            color: #1f1f1fff;
            margin-bottom: 8px;
        }

        .order-registration-modal .form-label-small {
            font-size: 16px;
            font-weight: 500;
            color: #1f1f1fff;
            margin-bottom: 6px;
        }

        .order-registration-modal .label-icon {
            width: 18px;
            height: 18px;
            color: #ff9d58;
            stroke-width: 2;
        }

        .order-registration-modal .form-input,
        .order-registration-modal .form-select,
        .order-registration-modal .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            color: #2d3748;
            background: #f7fafc;
            transition: all 0.3s ease;
        }

        .order-registration-modal .form-input:focus,
        .order-registration-modal .form-select:focus,
        .order-registration-modal .form-textarea:focus {
            outline: none;
            border-color: #ff9d58;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 157, 88, 0.1);
        }

        .order-registration-modal .form-input-compact {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            color: #2d3748;
            background: #f7fafc;
            transition: all 0.3s ease;
        }

        .order-registration-modal .form-input-compact:focus {
            outline: none;
            border-color: #ff9d58;
            background: white;
        }

        .order-registration-modal .form-textarea {
            resize: vertical;
            min-height: 60px;
            font-family: inherit;
        }

        .order-registration-modal .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            font-size: 13px;
            color: #718096;
            cursor: pointer;
        }

        .order-registration-modal .form-checkbox {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #ff9d58;
        }

        .order-registration-modal .error-message {
            color: #f56565;
            font-size: 12px;
            margin-top: 4px;
        }

        .order-registration-modal .hidden {
            display: none;
        }

        .order-registration-modal .prendas-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .order-registration-modal .prenda-card {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .order-registration-modal .prenda-card:hover {
            border-color: #ff9d58;
            box-shadow: 0 4px 12px rgba(255, 157, 88, 0.15);
        }

        .order-registration-modal .prenda-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .order-registration-modal .prenda-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%);
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(255, 157, 88, 0.3);
        }

        .order-registration-modal .prenda-content {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .order-registration-modal .tallas-section {
            margin-top: 8px;
        }

        .order-registration-modal .tallas-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
        }

        .order-registration-modal .tallas-list > div {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 8px;
            align-items: center;
        }

        .order-registration-modal .tallas-list input {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            color: #1f1f1fff;
            background: white;
            transition: all 0.2s ease;
        }

        .order-registration-modal .tallas-list input:focus {
            outline: none;
            border-color: #ff9d58;
        }

        .order-registration-modal .btn-add-talla {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: white;
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            color: #ff9d58;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .order-registration-modal .btn-add-talla:hover {
            border-color: #ff9d58;
            background: #fff5f0;
        }

        .order-registration-modal .btn-add-talla svg {
            width: 16px;
            height: 16px;
            stroke-width: 2;
        }

        .order-registration-modal .btn-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%);
            border: none;
            border-radius: 10px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(255, 157, 88, 0.3);
        }

        .order-registration-modal .btn-icon:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(255, 157, 88, 0.5);
        }

        .order-registration-modal .btn-icon svg {
            width: 20px;
            height: 20px;
            stroke-width: 2.5;
        }

        .order-registration-modal .btn-delete {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fee;
            border: none;
            border-radius: 8px;
            color: #f56565;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .order-registration-modal .btn-delete:hover {
            background: #f56565;
            color: white;
        }

        .order-registration-modal .btn-delete svg {
            width: 16px;
            height: 16px;
            stroke-width: 2.5;
        }

        .order-registration-modal .eliminar-talla-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 2px solid #feb2b2;
            border-radius: 6px;
            color: #f56565;
            cursor: pointer;
            font-weight: bold;
            font-size: 18px;
            transition: all 0.2s ease;
        }

        .order-registration-modal .eliminar-talla-btn:hover {
            background: #f56565;
            color: white;
            border-color: #f56565;
        }

        .order-registration-modal .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }

        .order-registration-modal .btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .order-registration-modal .btn svg {
            width: 18px;
            height: 18px;
            stroke-width: 2.5;
        }

        .order-registration-modal .btn-primary {
            background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%);
            color: white;
        }

        .order-registration-modal .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }

        .order-registration-modal .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .order-registration-modal .btn-secondary:hover {
            background: #cbd5e0;
        }

        .order-registration-modal .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }

        .order-registration-modal .notification {
            max-width: 400px;
            padding: 20px 28px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .order-registration-modal .notification-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }

        .order-registration-modal .notification-error {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
            color: white;
        }

        @media (max-width: 768px) {
            .order-registration-modal .form-grid {
                grid-template-columns: 1fr;
            }

            .order-registration-modal .form-content {
                padding: 20px;
            }

            .order-registration-modal .modal-header {
                padding: 20px;
            }
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('orderRegistration', () => ({
                prendasCount: 1,
                showSuccessMessage: false,
                showErrorMessage: false,
                successMessage: '',
                errorMessage: '',
                isBodega: false,
                context: window.modalContext || 'orden',
                isAddingPrenda: false,

                async init() {
                    this.isBodega = this.context === 'bodega';
                    this.setupEventListeners();
                    this.setFechaActual();
                    this.addTallaListeners();
                    this.updateEliminarPrendaButtons();
                    await this.loadNextPedido();
                },

                async loadNextPedido() {
                    try {
                        const endpoint = this.context === 'bodega' ? '/bodega/next-pedido' : '/registros/next-pedido';
                        const response = await fetch(endpoint);
                        const data = await response.json();
                        document.getElementById('pedido').value = data.next_pedido;
                        document.getElementById('pedidoError').textContent = `Consecutivo disponible: ${data.next_pedido}`;
                        document.getElementById('pedidoError').classList.remove('hidden');
                    } catch (error) {
                    }
                },

                setFechaActual() {
                    const fechaInput = document.getElementById('fecha_creacion');
                    if (fechaInput) fechaInput.value = new Date().toISOString().split('T')[0];
                },

                setupEventListeners() {
                    const añadirBtn = document.getElementById('añadirPrendaBtn');
                    añadirBtn.replaceWith(añadirBtn.cloneNode(true));
                    document.getElementById('añadirPrendaBtn').addEventListener('click', () => this.addPrenda());
                    
                    document.getElementById('cancelarBtn').addEventListener('click', () => this.closeModal());
                    document.getElementById('orderRegistrationForm').addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.handleSubmit();
                    });
                    document.getElementById('allowAnyPedido').addEventListener('change', () => this.validatePedido());
                    document.getElementById('pedido').addEventListener('input', () => this.validatePedido());
                },

                updateIsBodega() {
                    const area = document.getElementById('area').value;
                    this.isBodega = area === 'Bodega';
                },

                addPrenda() {
                    if (this.isAddingPrenda) return;
                    this.isAddingPrenda = true;
                    const btn = document.getElementById('añadirPrendaBtn');
                    btn.disabled = true;

                    const index = this.prendasCount;
                    const container = document.getElementById('prendasContainer');
                    const div = document.createElement('div');
                    div.className = 'prenda-card';
                    div.dataset.prendaIndex = index;
                    div.innerHTML = `
                        <div class="prenda-header">
                            <span class="prenda-number">${index + 1}</span>
                            <button type="button" class="btn-delete eliminar-prenda-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>
                        <div class="prenda-content">
                            <div class="form-group">
                                <label class="form-label-small">Descripción de la prenda</label>
                                <input type="text" name="prenda[${index}]" class="form-input-compact" placeholder="Ej: Polo roja" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label-small">Detalles adicionales</label>
                                <textarea name="descripcion[${index}]" rows="2" class="form-textarea" placeholder="Ej: Pegar bolsillo"></textarea>
                            </div>
                            <div class="tallas-section">
                                <label class="form-label-small">Tallas y Cantidades</label>
                                <div class="tallas-list"></div>
                                <button type="button" class="btn-add-talla añadir-talla-btn" data-prenda-index="${index}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                    Añadir talla
                                </button>
                            </div>
                        </div>
                    `;
                    container.appendChild(div);
                    this.addTallaListeners();
                    this.updateEliminarPrendaButtons();

                    this.prendasCount++;

                    btn.disabled = false;
                    this.isAddingPrenda = false;
                },

                addTallaListeners() {
                    document.querySelectorAll('.añadir-talla-btn').forEach(btn => {
                        btn.replaceWith(btn.cloneNode(true));
                    });
                    document.querySelectorAll('.añadir-talla-btn').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            const index = e.target.closest('button').dataset.prendaIndex;
                            const list = e.target.closest('.tallas-section').querySelector('.tallas-list');
                            const div = document.createElement('div');
                            div.innerHTML = `
                                <input type="text" name="talla[${index}][]" placeholder="Talla (ej: XS)" required />
                                <input type="number" name="cantidad[${index}][]" placeholder="Cantidad" min="1" required />
                                <button type="button" class="eliminar-talla-btn">×</button>
                            `;
                            list.appendChild(div);
                            this.addTallaListeners();
                        });
                    });
                    
                    document.querySelectorAll('.eliminar-talla-btn').forEach(btn => {
                        btn.replaceWith(btn.cloneNode(true));
                    });
                    document.querySelectorAll('.eliminar-talla-btn').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.target.closest('div').remove();
                        });
                    });
                },

                updateEliminarPrendaButtons() {
                    const prendas = document.querySelectorAll('.prenda-card');
                    prendas.forEach((prenda, idx) => {
                        const btn = prenda.querySelector('.eliminar-prenda-btn');
                        const num = prenda.querySelector('.prenda-number');
                        if (num) num.textContent = idx + 1;
                        
                        if (btn) {
                            btn.style.display = prendas.length === 1 ? 'none' : 'flex';
                            btn.replaceWith(btn.cloneNode(true));
                        }
                    });
                    
                    document.querySelectorAll('.eliminar-prenda-btn').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.target.closest('.prenda-card').remove();
                            this.prendasCount--;
                            this.updateEliminarPrendaButtons();
                        });
                    });
                },

                async validatePedido() {
                    const input = document.getElementById('pedido');
                    const allowAny = document.getElementById('allowAnyPedido').checked;
                    const error = document.getElementById('pedidoError');

                    error.classList.add('hidden');

                    if (allowAny) {
                        input.setCustomValidity('');
                        return true;
                    }

                    const value = parseInt(input.value);
                    if (isNaN(value)) {
                        input.setCustomValidity('Número inválido');
                        return false;
                    }

                    try {
                        const endpoint = this.context === 'bodega' ? '/bodega/validate-pedido' : '/registros/validate-pedido';
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ pedido: value })
                        });

                        const data = await response.json();
                        if (!data.valid) {
                            error.textContent = `Consecutivo disponible: ${data.next_pedido}`;
                            error.classList.remove('hidden');
                            input.setCustomValidity('Número no consecutivo');
                            return false;
                        }

                        input.setCustomValidity('');
                        return true;
                    } catch (error) {
                        return false;
                    }
                },

                closeModal() {
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'order-registration' }));
                },

                clearForm() {
                    document.getElementById('orderRegistrationForm').reset();
                    const container = document.getElementById('prendasContainer');
                    while (container.children.length > 1) {
                        container.removeChild(container.lastChild);
                    }
                    this.prendasCount = 1;
                    this.updateEliminarPrendaButtons();
                    this.setFechaActual();
                    this.loadNextPedido();
                    this.updateIsBodega();
                },

                async handleSubmit() {
                    if (!(await this.validatePedido())) return;

                    const form = document.getElementById('orderRegistrationForm');
                    const formData = new FormData(form);

                    const data = {
                        pedido: formData.get('pedido'),
                        estado: formData.get('estado'),
                        cliente: formData.get('cliente'),
                        area: formData.get('area'),
                        fecha_creacion: formData.get('fecha_creacion'),
                        encargado: formData.get('encargado'),
                        asesora: formData.get('asesora'),
                        forma_pago: formData.get('forma_pago'),
                        prendas: [],
                        allow_any_pedido: document.getElementById('allowAnyPedido').checked
                    };

                    const prendasMap = {};
                    for (let [key, value] of formData.entries()) {
                        const prendaMatch = key.match(/prenda\[(\d+)\]/);
                        const descMatch = key.match(/descripcion\[(\d+)\]/);
                        const tallaMatch = key.match(/talla\[(\d+)\]\[\]/);
                        const cantMatch = key.match(/cantidad\[(\d+)\]\[\]/);

                        if (prendaMatch) {
                            const idx = prendaMatch[1];
                            if (!prendasMap[idx]) prendasMap[idx] = {};
                            prendasMap[idx].prenda = value;
                        } else if (descMatch) {
                            const idx = descMatch[1];
                            if (!prendasMap[idx]) prendasMap[idx] = {};
                            prendasMap[idx].descripcion = value;
                        } else if (tallaMatch) {
                            const idx = tallaMatch[1];
                            if (!prendasMap[idx]) prendasMap[idx] = {};
                            if (!prendasMap[idx].tallas) prendasMap[idx].tallas = [];
                            prendasMap[idx].tallas.push({ talla: value });
                        } else if (cantMatch) {
                            const idx = cantMatch[1];
                            if (!prendasMap[idx]) prendasMap[idx] = {};
                            if (!prendasMap[idx].tallas) prendasMap[idx].tallas = [];
                            const last = prendasMap[idx].tallas[prendasMap[idx].tallas.length - 1];
                            if (last) last.cantidad = value;
                        }
                    }

                    data.prendas = Object.values(prendasMap);

                    try {
                        const endpoint = this.context === 'bodega' ? '/bodega' : '/registros';
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(data)
                        });

                        const result = await response.json();

                        if (response.ok) {
                            this.successMessage = result.message || 'Orden registrada exitosamente';
                            this.showSuccessMessage = true;
                            setTimeout(() => this.showSuccessMessage = false, 5000);
                            this.clearForm();

                            if (typeof reloadCurrentPage === 'function' && document.getElementById('tablaOrdenesBody')) {
                                reloadCurrentPage();
                            } else if (typeof recargarTablaPedidos === 'function' && document.getElementById('tablaOrdenesBody')) {
                                recargarTablaPedidos();
                            }
                        } else if (response.status === 422 && result.message?.includes('consecutivo')) {
                            this.errorMessage = result.message;
                            this.showErrorMessage = true;
                            setTimeout(() => this.showErrorMessage = false, 5000);
                        } else {
                            this.errorMessage = 'Error: ' + (result.message || 'Error desconocido');
                            this.showErrorMessage = true;
                            setTimeout(() => this.showErrorMessage = false, 5000);
                        }
                    } catch (error) {
                        this.errorMessage = 'Error al enviar el formulario';
                        this.showErrorMessage = true;
                        setTimeout(() => this.showErrorMessage = false, 5000);
                    }
                },

                getContext() {
                    return this.context;
                }
            }));
        });
    </script>
</x-modal>
