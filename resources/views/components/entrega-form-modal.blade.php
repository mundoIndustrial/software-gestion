<x-modal name="entrega-form" :show="false" maxWidth="3xl">
    <div class="entrega-modal-container">
        <div class="modal-header">
            <div class="header-content">
                <div class="icon-wrapper">
                    <svg class="header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <h2 class="modal-title">Registrar Entrega</h2>
            </div>
        </div>

        <form id="entregaForm" x-data="entregaForm" x-init="init()">
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
                <!-- Información General -->
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
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Fecha
                            </label>
                            <input type="date" id="fecha_entrega" name="fecha_entrega" class="form-input" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Cliente
                            </label>
                            <input type="text" id="cliente" name="cliente" class="form-input" placeholder="Nombre del cliente" readonly />
                        </div>
                    </div>
                </div>

                <!-- Tipo de Entrega -->
                <div class="section-card">
                    <h3 class="section-title">Tipo de Entrega</h3>
                    <div class="tipo-selector">
                        <button type="button" id="btnCostura" class="tipo-btn active" data-tipo="costura">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            Costura
                        </button>
                        <button type="button" id="btnCorte" class="tipo-btn" data-tipo="corte">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l.707.707A1 1 0 0012.414 11H13m-3 3.5a.5.5 0 11-1 0 .5.5 0 011 0z" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            Corte
                        </button>
                    </div>
                </div>

                <!-- Formulario Costura -->
                <div id="costuraForm" class="section-card" style="display: block;">
                    <h3 class="section-title">Información de Entrega - Costura</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Prenda
                            </label>
                            <select id="prenda" name="prenda" class="form-select" required>
                                <option value="">Seleccionar prenda</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Talla
                            </label>
                            <select id="talla" name="talla" class="form-select" required>
                                <option value="">Seleccionar talla</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Cantidad
                            </label>
                            <input type="number" id="cantidad_entregada" name="cantidad_entregada" class="form-input" placeholder="0" min="1" required />
                            <p id="cantidadSummary" class="summary-text"></p>
                            <p id="cantidadError" class="error-message hidden"></p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Costurero
                            </label>
                            <input type="text" id="costurero" name="costurero" class="form-input" placeholder="Nombre del costurero" required />
                        </div>

                        <!-- Removed Mes/Año input as it is set internally -->
                    </div>

                    <!-- Resumen -->
                    <div id="summarySection" class="summary-card hidden">
                        <h4>Resumen de Entrega</h4>
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="summary-label">Total Producido:</span>
                                <span id="totalProducido" class="summary-value">0</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Total Pendiente:</span>
                                <span id="totalPendiente" class="summary-value">0</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Entregando:</span>
                                <span id="entregando" class="summary-value">0</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="addEntregaBtn" class="btn-primary btn-add-entrega" disabled @click="addEntrega()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="icon-add-entrega">
                            <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Añadir Entrega
                    </button>
                </div>

                <!-- Formulario Corte -->
                <div id="corteForm" class="section-card" style="display: none;">
                    <h3 class="section-title">Información de Entrega - Corte</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Cortador
                            </label>
                            <input type="text" id="cortador" name="cortador" class="form-input" placeholder="Nombre del cortador" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Cantidad de Prendas
                            </label>
                            <input type="number" id="cantidad_prendas" name="cantidad_prendas" class="form-input" placeholder="0" min="1" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Piezas
                            </label>
                            <input type="number" id="piezas" name="piezas" class="form-input" placeholder="0" min="1" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M9 5l7 7-7 7" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Pasadas
                            </label>
                            <input type="number" id="pasadas" name="pasadas" class="form-input" placeholder="0" min="0" />
                        </div>



                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Etiquetador
                            </label>
                            <input type="text" id="etiquetador" name="etiquetador" class="form-input" placeholder="Nombre del etiquetador" />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Mes
                            </label>
                            <input type="text" id="mes" name="mes" class="form-input" placeholder="MM/YYYY" />
                        </div>
                    </div>

                    <button type="button" id="addEntregaCorteBtn" class="btn-primary" disabled @click="addEntrega()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Añadir Entrega
                    </button>
                </div>

                <!-- Lista de Entregas a Registrar -->
                <div x-show="entregas.length > 0" class="section-card">
                    <h3 class="section-title cursor-pointer" @click="showDropdown = !showDropdown">
                        Entregas a Registrar
                        <svg x-show="!showDropdown" xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                        <svg x-show="showDropdown" xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </h3>
                    <div x-show="showDropdown" class="entregas-list max-h-48 overflow-auto mt-2">
                        <template x-for="(entrega, index) in entregas" :key="index">
                            <div class="entrega-item flex justify-between items-center p-3 bg-white rounded shadow-sm mb-2 border border-gray-300 text-black">
                                <div class="entrega-info text-sm font-semibold" x-text="getEntregaText(entrega)"></div>
                                <button type="button" @click="removeEntrega(index)" class="btn-delete ml-2">×</button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Botones -->
                <div class="form-actions">
                    <button type="button" @click="closeModal" class="btn btn-secondary">Cancelar</button>
                    <button type="button" @click="submitForm" class="btn btn-primary" :disabled="entregas.length === 0">Registrar Entregas</button>
                </div>
            </div>
        </form>
    </div>

    <style>
        .entrega-modal-container {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            min-height: 750px;
            max-height: 95vh;
            overflow-y: auto;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            margin: 0 auto;
            max-width: 1200px;
            transform: scale(0.75);
            transform-origin: top center;
        }

        .entrega-modal-container .modal-header {
            background: rgba(255, 157, 88, 0.1);
            backdrop-filter: blur(10px);
            padding: 24px 32px;
            border-bottom: 1px solid rgba(255, 157, 88, 0.2);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .entrega-modal-container .header-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .entrega-modal-container .icon-wrapper {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(255, 157, 88, 0.3);
        }

        .entrega-modal-container .header-icon {
            width: 20px;
            height: 20px;
            color: white;
        }

        .entrega-modal-container .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        .entrega-modal-container .form-content {
            padding: 32px;
        }

        .entrega-modal-container .section-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }

        .entrega-modal-container .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
            margin: 0 0 20px 0;
        }

        .entrega-modal-container .tipo-selector {
            display: flex;
            gap: 16px;
        }

        .entrega-modal-container .tipo-btn.active {
            background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .entrega-modal-container .tipo-btn {
            background: #d1d5db;
            color: #374151;
            border: 1px solid #9ca3af;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .entrega-modal-container .tipo-btn:hover {
            background: #e5e7eb;
        }

        .entrega-modal-container .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .entrega-modal-container .form-group {
            display: flex;
            flex-direction: column;
        }

        .entrega-modal-container .form-label {
            font-size: 16px;
            font-weight: 500;
            color: #1f1f1fff;
            margin-bottom: 8px;
        }

        .entrega-modal-container .label-icon {
            width: 16px;
            height: 16px;
        }

        .entrega-modal-container .form-input,
        .entrega-modal-container .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            color: #2d3748;
            background: #f7fafc;
            transition: all 0.3s ease;
        }

        .entrega-modal-container .form-input:focus,
        .entrega-modal-container .form-select:focus {
            outline: none;
            border-color: #ff9d58;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 157, 88, 0.1);
        }

        .entrega-modal-container .summary-table {
            margin-top: 20px;
            padding: 16px;
            background: #f7fafc;
            border-radius: 8px;
        }

        .entrega-modal-container .summary-table h4 {
            margin: 0 0 12px 0;
            color: #1a202c;
            font-size: 16px;
            font-weight: 600;
        }

        .entrega-modal-container .table {
            width: 100%;
            border-collapse: collapse;
        }

        .entrega-modal-container .table th,
        .entrega-modal-container .table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .entrega-modal-container .table th {
            background: #edf2f7;
            font-weight: 600;
            color: #1a202c;
        }

        .entrega-modal-container .btn-primary {
            background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%);
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .entrega-modal-container .btn-add-entrega {
            padding: 4px 10px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            border-radius: 8px;
            max-width: 160px;
        }
        .entrega-modal-container .icon-add-entrega {
            width: 18px;
            height: 18px;
        }

        .entrega-modal-container .btn-primary:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(255, 157, 88, 0.4);
        }

        .entrega-modal-container .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .entrega-modal-container .entregas-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .entrega-modal-container .entrega-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: white;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            color: black;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .entrega-modal-container .btn-delete {
            background: #f56565;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .entrega-modal-container .btn-delete:hover {
            background: #e53e3e;
        }

        .entrega-modal-container .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }

        .entrega-modal-container .btn {
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

        .entrega-modal-container .btn-primary {
            background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%);
            color: white;
        }

        .entrega-modal-container .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }

        .entrega-modal-container .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .entrega-modal-container .btn-secondary:hover {
            background: #cbd5e0;
        }

        .entrega-modal-container .error-message {
            color: #f56565 !important;
            font-size: 12px !important;
            margin-top: 4px !important;
        }

        .entrega-modal-container .summary-text {
            font-size: 12px !important;
            color: #4a5568 !important;
            margin-top: 4px !important;
        }

        .notification-success {
            background-color: #d1fae5 !important;
            color: #065f46 !important;
            padding: 10px 20px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            box-shadow: 0 2px 6px rgba(6, 95, 70, 0.3) !important;
        }

        .notification-error {
            background-color: #fee2e2 !important;
            color: #b91c1c !important;
            padding: 10px 20px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            box-shadow: 0 2px 6px rgba(185, 28, 28, 0.3) !important;
        }

        @media (max-width: 768px) {
            .entrega-modal-container .form-grid {
                grid-template-columns: 1fr;
            }

            .entrega-modal-container .form-content {
                padding: 20px;
            }

            .entrega-modal-container .modal-header {
                padding: 20px;
            }

            .entrega-modal-container .tipo-selector {
                flex-direction: column;
            }
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('entregaForm', () => ({
                tipo: window.location.pathname.includes('/pedido') ? 'pedido' : 'bodega',
                subtipo: 'costura', // Default to costura
                form: {},
                orderData: {},
                garments: [],
                sizes: [],
                summary: {},
                entregas: [],
                quantityError: '',
                showSuccessMessage: false,
                successMessage: '',
                showErrorMessage: false,
                errorMessage: '',
                showDropdown: true, // New state for dropdown visibility

                init() {
                    this.resetForm();
                    this.setupEventListeners();
                },

                setupEventListeners() {
                    // Tipo de entrega buttons
                    document.getElementById('btnCostura').addEventListener('click', () => {
                        this.setSubtipo('costura');
                    });

                    document.getElementById('btnCorte').addEventListener('click', () => {
                        this.setSubtipo('corte');
                    });

                    // Pedido input with debouncing
                    let pedidoTimeout;
                    document.getElementById('pedido').addEventListener('input', (e) => {
                        this.form.pedido = e.target.value;
                        clearTimeout(pedidoTimeout);
                        if (this.form.pedido.length >= 3) { // Only search after 3 characters
                            pedidoTimeout = setTimeout(() => {
                                this.fetchOrderData();
                            }, 500); // Wait 500ms after user stops typing
                        } else {
                            // Clear error if less than 3 characters
                            document.getElementById('pedidoError').classList.add('hidden');
                            document.getElementById('cliente').value = '';
                            this.orderData = {};
                            this.garments = [];
                        }
                    });

                    // Costura form fields
                    document.getElementById('prenda').addEventListener('change', (e) => {
                        this.form.prenda = e.target.value;
                        this.fetchSizes();
                    });

                    document.getElementById('talla').addEventListener('change', (e) => {
                        this.form.talla = e.target.value;
                        this.updateSummary();
                        this.validateQuantity();
                    });

                    document.getElementById('cantidad_entregada').addEventListener('input', (e) => {
                        this.form.cantidad_entregada = e.target.value;
                        this.validateQuantity();
                        this.updateButtonState();
                    });

                    document.getElementById('costurero').addEventListener('input', (e) => {
                        this.form.costurero = e.target.value;
                        this.updateButtonState();
                    });

                    // Corte form fields
                    document.getElementById('cortador').addEventListener('input', (e) => {
                        this.form.cortador = e.target.value;
                        this.updateButtonState();
                    });

                    // Removed prenda_corte event listener as field was removed

                    document.getElementById('cantidad_prendas').addEventListener('input', (e) => {
                        this.form.cantidad_prendas = e.target.value;
                        this.updateButtonState();
                    });

                    document.getElementById('piezas').addEventListener('input', (e) => {
                        this.form.piezas = e.target.value;
                        this.updateButtonState();
                    });

                    // Optional fields
                    ['pasadas', 'etiquetador'].forEach(field => {
                        document.getElementById(field).addEventListener('input', (e) => {
                            this.form[field] = e.target.value;
                        });
                    });

                    // Buttons
                // Removed event listeners for addEntregaBtn and addEntregaCorteBtn to avoid duplicate events
                // document.getElementById('addEntregaBtn').addEventListener('click', () => {
                //     this.addEntrega();
                // });

                // document.getElementById('addEntregaCorteBtn').addEventListener('click', () => {
                //     if (!this.canAddEntrega) return;
                //     // For corte, set prenda concatenated string before adding entrega
                //     if (this.subtipo === 'corte') {
                //         let prendaString = '';
                //         this.garments.forEach((prenda, index) => {
                //             prendaString += `PRENDA ${index + 1}: ${prenda} `;
                //         });
                //         this.form.prenda = prendaString.trim();
                //     }
                //     this.addEntrega();
                // });
                },

                setSubtipo(subtipo) {
                    this.subtipo = subtipo;
                    this.resetForm();

                    // Update button states
                    document.getElementById('btnCostura').classList.toggle('active', subtipo === 'costura');
                    document.getElementById('btnCorte').classList.toggle('active', subtipo === 'corte');

                    // Show/hide forms
                    document.getElementById('costuraForm').style.display = subtipo === 'costura' ? 'block' : 'none';
                    document.getElementById('corteForm').style.display = subtipo === 'corte' ? 'block' : 'none';
                },

                resetForm() {
                    this.form = {
                        pedido: '',
                        cliente: '',
                        prenda: '',
                        talla: '',
                        cantidad_entregada: '',
                        costurero: '',
                        fecha_entrega: new Date().toISOString().split('T')[0],
                        cortador: '',
                        cantidad_prendas: '',
                        piezas: '',
                        pasadas: '',
                        etiquetador: ''
                    };
                    this.orderData = {};
                    this.garments = [];
                    this.sizes = [];
                    this.summary = {};
                    this.quantityError = '';

                    this.clearFormFields();
                    this.updateButtonState();
                },

                clearFormFields() {
                    // Costura fields
                    document.getElementById('prenda').value = '';
                    document.getElementById('talla').innerHTML = '<option value="">Seleccionar talla</option>';
                    document.getElementById('cantidad_entregada').value = '';
                    document.getElementById('costurero').value = '';

                    // Corte fields
                    document.getElementById('cortador').value = '';
                    // Removed prenda_corte as field was removed
                    document.getElementById('cantidad_prendas').value = '';
                    document.getElementById('piezas').value = '';
                    document.getElementById('pasadas').value = '';
                    // Removed etiquetadas as field was removed
                    document.getElementById('etiquetador').value = '';
                    // Removed mes as field was removed

                    // Hide summary
                    document.getElementById('summarySection').classList.add('hidden');
                },

                updateButtonState() {
                    const canAdd = this.canAddEntrega;
                    if (this.subtipo === 'costura') {
                    document.getElementById('addEntregaBtn').disabled = !canAdd;
                } else {
                    document.getElementById('addEntregaCorteBtn').disabled = !canAdd;
                }
            },

            async fetchOrderData() {
                if (!this.form.pedido) {
                    document.getElementById('cliente').value = '';
                    return;
                }

                try {
                    const response = await fetch(`/entrega/${this.tipo}/order-data/${this.form.pedido}`);
                    const data = await response.json();
                    if (response.ok) {
                        this.orderData = data;
                        document.getElementById('cliente').value = data.cliente;
                        this.form.cliente = data.cliente;
                        // Hide error message when order is found
                        document.getElementById('pedidoError').classList.add('hidden');
                        // Show success message
                        this.showSuccessMessage = true;
                        this.successMessage = 'Pedido encontrado correctamente';
                        setTimeout(() => {
                            this.showSuccessMessage = false;
                        }, 2000);
                        await this.fetchGarments();
                    } else {
                        document.getElementById('cliente').value = '';
                        this.orderData = {};
                        this.garments = [];
                        document.getElementById('pedidoError').textContent = 'Pedido no encontrado';
                        document.getElementById('pedidoError').classList.remove('hidden');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    document.getElementById('pedidoError').textContent = 'Error al buscar pedido';
                    document.getElementById('pedidoError').classList.remove('hidden');
                }
            },

            async fetchGarments() {
                if (!this.form.pedido) return;

                try {
                    const response = await fetch(`/entrega/${this.tipo}/garments/${this.form.pedido}`);
                    const data = await response.json();
                    this.garments = data;

                    // Populate prenda select
                    const prendaSelect = document.getElementById('prenda');
                    prendaSelect.innerHTML = '<option value="">Seleccionar prenda</option>';
                    data.forEach(garment => {
                        const option = document.createElement('option');
                        option.value = garment;
                        option.textContent = garment;
                        prendaSelect.appendChild(option);
                    });
                } catch (error) {
                    console.error('Error:', error);
                }
            },

            async fetchSizes() {
                if (!this.form.pedido || !this.form.prenda) return;

                try {
                    const response = await fetch(`/entrega/${this.tipo}/sizes/${this.form.pedido}/${encodeURIComponent(this.form.prenda)}`);
                    const data = await response.json();
                    this.sizes = data;

                    // Populate talla select
                    const tallaSelect = document.getElementById('talla');
                    tallaSelect.innerHTML = '<option value="">Seleccionar talla</option>';
                    data.forEach(size => {
                        const option = document.createElement('option');
                        option.value = size.talla;
                        option.textContent = size.talla;
                        tallaSelect.appendChild(option);
                    });
                } catch (error) {
                    console.error('Error:', error);
                }
            },

            updateSummary() {
                const size = this.sizes.find(s => s.talla === this.form.talla);
                if (size) {
                    this.summary = {
                        pedido: this.form.pedido,
                        cliente: this.orderData.cliente,
                        prenda: this.form.prenda,
                        talla: this.form.talla,
                        cantidad_entregada: this.form.cantidad_entregada,
                        total_producido_por_talla: size.total_producido_por_talla,
                        total_pendiente_por_talla: size.total_pendiente_por_talla
                    };

                    // Update summary display
                    document.getElementById('totalProducido').textContent = size.total_producido_por_talla;
                    document.getElementById('totalPendiente').textContent = size.total_pendiente_por_talla;
                    document.getElementById('entregando').textContent = this.form.cantidad_entregada || 0;
                    document.getElementById('summarySection').classList.remove('hidden');

                    // Update cantidadSummary with summary text
                    const cantidad = parseInt(this.form.cantidad_entregada) || 0;
                    const summaryText = `Entregando ${cantidad} de ${size.total_pendiente_por_talla} pendientes`;
                    document.getElementById('cantidadSummary').textContent = summaryText;
                }
            },

            validateQuantity() {
                const size = this.sizes.find(s => s.talla === this.form.talla);
                const errorElement = document.getElementById('cantidadError');
                if (size) {
                    const cantidad = parseInt(this.form.cantidad_entregada) || 0;

                    if (size.total_pendiente_por_talla <= 0) {
                        this.quantityError = 'No hay prendas pendientes para esta talla';
                        errorElement.textContent = this.quantityError;
                        errorElement.classList.remove('hidden');
                        return false;
                    }
                    if (cantidad > size.total_pendiente_por_talla) {
                        this.quantityError = `No se puede entregar más de ${size.total_pendiente_por_talla} prendas`;
                        errorElement.textContent = this.quantityError;
                        errorElement.classList.remove('hidden');
                        return false;
                    }
                    this.quantityError = '';
                    errorElement.classList.add('hidden');
                    this.updateSummary();
                    return true;
                }
                return false;
            },

            get canAddEntrega() {
                if (this.subtipo === 'costura') {
                    return this.form.pedido && this.form.prenda && this.form.talla &&
                           this.form.cantidad_entregada && this.form.costurero &&
                           this.form.fecha_entrega && !this.quantityError;
                } else if (this.subtipo === 'corte') {
                    return this.form.pedido && this.form.cortador && this.form.cantidad_prendas &&
                           this.form.piezas && this.form.fecha_entrega;
                }
                return false;
            },

            addEntrega() {
                if (!this.canAddEntrega) return;

                if (this.subtipo === 'corte') {
                    // For corte, set prenda concatenated string before adding entrega
                    let prendaString = '';
                    this.garments.forEach((prenda, index) => {
                        prendaString += `PRENDA ${index + 1}: ${prenda} `;
                    });
                    this.form.prenda = prendaString.trim();
                }

                const entrega = { ...this.form, subtipo: this.subtipo };
                this.entregas.push(entrega);

                // Disable add button immediately to prevent double clicks
                if (this.subtipo === 'costura') {
                    document.getElementById('addEntregaBtn').disabled = true;
                } else {
                    document.getElementById('addEntregaCorteBtn').disabled = true;
                }

                // Show success message
                this.showSuccessMessage = true;
                this.successMessage = 'Entrega añadida correctamente';
                setTimeout(() => {
                    this.showSuccessMessage = false;
                }, 3000);

                // Clear only prenda, talla, cantidad_entregada, costurero fields but keep pedido and cliente
                this.form.prenda = '';
                this.form.talla = '';
                this.form.cantidad_entregada = '';
                this.form.costurero = '';

                // Clear the corresponding form inputs
                document.getElementById('prenda').value = '';
                document.getElementById('talla').innerHTML = '<option value="">Seleccionar talla</option>';
                document.getElementById('cantidad_entregada').value = '';
                document.getElementById('costurero').value = '';

                this.updateButtonState();
            },

            removeEntrega(index) {
                this.entregas.splice(index, 1);
            },

            getEntregaText(entrega) {
                try {
                    return `Pedido: ${entrega.pedido}, Prenda: ${entrega.prenda || 'N/A'}, Cantidad: ${entrega.cantidad_entregada || entrega.piezas}`;
                } catch (error) {
                    console.error('Error in getEntregaText:', error, entrega);
                    return 'Error displaying entrega';
                }
            },

            async submitForm() {
                if (this.entregas.length === 0) return;

                // Disable submit button to prevent multiple submissions
                const submitBtn = document.querySelector('.form-actions .btn-primary');
                if (submitBtn) submitBtn.disabled = true;

                // Debug log entregas before sending
                console.log('Submitting entregas:', this.entregas);

                // Check all entregas have prenda field non-empty
                for (const entrega of this.entregas) {
                    if (!entrega.prenda || entrega.prenda.trim() === '') {
                        this.showErrorMessage = true;
                        this.errorMessage = 'Error: La prenda no puede estar vacía en alguna entrega.';
                        if (submitBtn) submitBtn.disabled = false;
                        return;
                    }
                }

                try {
                    const response = await fetch(`/entrega/${this.tipo}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            subtipo: this.subtipo,
                            entregas: this.entregas
                        })
                    });

                    const result = await response.json();
                    if (response.ok) {
                        const count = this.entregas.length;
                        this.showSuccessMessage = true;
                        this.successMessage = `${count} entregas realizadas correctamente`;
                        this.entregas = [];
                        
                        // Recargar datos en la vista principal (para el usuario actual)
                        if (window.filtrarDatos && typeof window.filtrarDatos === 'function') {
                            window.filtrarDatos();
                        }
                        
                        // Vaciar completamente el formulario después del envío exitoso
                        this.resetForm();
                        setTimeout(() => {
                            this.closeModal();
                        }, 2000);
                    } else {
                        this.showErrorMessage = true;
                        this.errorMessage = result.message || 'Error desconocido';
                        setTimeout(() => {
                            this.showErrorMessage = false;
                        }, 5000);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.showErrorMessage = true;
                    this.errorMessage = 'Error al enviar el formulario';
                    setTimeout(() => {
                        this.showErrorMessage = false;
                    }, 5000);
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            },

            closeModal() {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'entrega-form' }));
            }
        }));
    });
</script>
</x-modal>
