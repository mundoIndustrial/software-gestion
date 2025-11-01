<x-modal name="piso-corte-form" :show="false" maxWidth="4xl">
    <div class="piso-corte-form-modal-container">
        <div class="modal-header">
            <div class="header-content">
                <div class="icon-wrapper">
                    <svg class="header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <h2 class="modal-title">Registro Control Piso de Corte</h2>
            </div>
        </div>

        <form id="registroCorteForm" method="POST" action="#">
            @csrf
            <div class="form-content">
                <!-- Información Básica -->
                <div class="section-card">
                    <h3 class="section-title">Información Básica</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="2"/>
                                    <path d="M16 2v4M8 2v4M3 10h18" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                FECHA *
                            </label>
                            <input type="date" name="fecha" class="form-input" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                ORDEN DE PRODUCCIÓN *
                            </label>
                            <input type="text" name="orden_produccion" class="form-input uppercase-input" placeholder="Número de orden" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M4 6h16M4 12h16M4 18h16" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                TELA *
                            </label>
                            <div class="autocomplete-container">
                                <input type="text" id="tela_autocomplete" class="form-input uppercase-input" placeholder="Buscar o crear tela" required autocomplete="off" />
                                <input type="hidden" name="tela_id" id="tela_id" />
                                <div id="tela_suggestions" class="autocomplete-suggestions"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                HORA *
                            </label>
                            <select name="hora_id" class="form-select" required>
                                <option value="">Seleccionar hora</option>
                                @foreach($horas as $hora)
                                    <option value="{{ $hora->id }}">HORA {{ $hora->hora }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-width="2"/>
                                </svg>
                                OPERARIO *
                            </label>
                            <div class="autocomplete-container">
                                <input type="text" id="operario_autocomplete" class="form-input uppercase-input" placeholder="Buscar o crear operario" required autocomplete="off" />
                                <input type="hidden" name="operario_id" id="operario_id" />
                                <div id="operario_suggestions" class="autocomplete-suggestions"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalles de Actividad -->
                <div class="section-card">
                    <h3 class="section-title">Detalles de Actividad</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="2"/>
                                </svg>
                                ACTIVIDAD *
                            </label>
                            <select name="actividad" class="form-select" required>
                                <option value="">Seleccionar actividad</option>
                                <option value="Extender/Trazar">Extender/Trazar</option>
                                <option value="Corte">Corte</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-width="2"/>
                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                MÁQUINA *
                            </label>
                            <div class="autocomplete-container">
                                <input type="text" id="maquina_autocomplete" class="form-input uppercase-input" placeholder="Buscar o crear máquina" required autocomplete="off" />
                                <input type="hidden" name="maquina_id" id="maquina_id" />
                                <div id="maquina_suggestions" class="autocomplete-suggestions"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                TIEMPO DE CICLO *
                            </label>
                            <input type="number" name="tiempo_ciclo" step="0.01" class="form-input" placeholder="0.00" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <line x1="10" y1="4" x2="14" y2="4" stroke-width="2" stroke-linecap="round"/>
                                    <line x1="12" y1="12" x2="15" y2="9" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                PORCIÓN DE TIEMPO *
                            </label>
                            <select name="porcion_tiempo" class="form-select" required>
                                <option value="">Seleccionar</option>
                                <option value="0.1">0.1</option>
                                <option value="0.2">0.2</option>
                                <option value="0.3">0.3</option>
                                <option value="0.4">0.4</option>
                                <option value="0.5">0.5</option>
                                <option value="0.6">0.6</option>
                                <option value="0.7">0.7</option>
                                <option value="0.8">0.8</option>
                                <option value="0.9">0.9</option>
                                <option value="1.0">1.0</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <rect x="4" y="2" width="16" height="20" rx="2" stroke-width="2"/>
                                    <line x1="8" y1="6" x2="16" y2="6" stroke-width="2" stroke-linecap="round"/>
                                    <line x1="16" y1="14" x2="16" y2="18" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M16 10h.01M12 10h.01M8 10h.01M12 14h.01M8 14h.01M12 18h.01M8 18h.01" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                CANTIDAD PRODUCIDA *
                            </label>
                            <input type="number" name="cantidad_producida" class="form-input" placeholder="0" required />
                        </div>
                    </div>
                </div>

                <!-- Paradas Programadas -->
                <div class="section-card">
                    <h3 class="section-title">Paradas Programadas</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                PARADAS PROGRAMADAS *
                            </label>
                            <select name="paradas_programadas" class="form-select" required>
                                <option value="">Seleccionar</option>
                                <option value="DESAYUNO">DESAYUNO</option>
                                <option value="MEDIA TARDE">MEDIA TARDE</option>
                                <option value="NINGUNA">NINGUNA</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Detalles de Extendido y Trazado -->
                <div class="section-card">
                    <h3 class="section-title">Detalles de Extendido y Trazado</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M3 6h18M3 12h18M3 18h18" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                TIPO DE EXTENDIDO *
                            </label>
                            <select name="tipo_extendido" class="form-select" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="Trazo Largo">Trazo Largo</option>
                                <option value="Trazo Corto">Trazo Corto</option>
                                <option value="Ninguna">Ninguna</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M4 7h16M4 12h16M4 17h16" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                NÚMERO DE CAPAS *
                            </label>
                            <input type="number" name="numero_capas" class="form-input" placeholder="0" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                TRAZADO *
                            </label>
                            <select name="trazado" class="form-select" required>
                                <option value="">Seleccionar método</option>
                                <option value="PLOTTER">PLOTTER</option>
                                <option value="TRAZO A MANO">TRAZO A MANO</option>
                                <option value="NINGUNA">NINGUNA</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                TIEMPO DE TRAZADO
                            </label>
                            <input type="number" name="tiempo_trazado" step="0.01" class="form-input" placeholder="0.00" />
                        </div>
                    </div>
                </div>

                <!-- Paradas No Programadas -->
                <div class="section-card">
                    <h3 class="section-title">Paradas No Programadas</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M14 2v4a2 2 0 0 0 2 2h4" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M16 22h2a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v3" stroke-width="2" stroke-linecap="round"/>
                                    <circle cx="8" cy="16" r="6" stroke-width="2"/>
                                </svg>
                                PARADAS NO PROGRAMADAS
                            </label>
                            <input type="text" name="paradas_no_programadas" class="form-input uppercase-input" placeholder="Describa las paradas" />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                TIEMPO DE PARADA NO PROGRAMADA
                            </label>
                            <input type="number" name="tiempo_parada_no_programada" step="0.01" class="form-input" placeholder="0.00" />
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCorteModal()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M5 13l4 4L19 7" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Registrar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <style>
        .piso-corte-form-modal-container {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            min-height: 600px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            z-index: 1100;
        }

        .piso-corte-form-modal-container .modal-header {
            background: rgba(59, 130, 246, 0.1);
            backdrop-filter: blur(10px);
            padding: 24px 32px;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
        }

        .piso-corte-form-modal-container .header-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .piso-corte-form-modal-container .icon-wrapper {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .piso-corte-form-modal-container .header-icon {
            width: 28px;
            height: 28px;
            color: white;
        }

        .piso-corte-form-modal-container .modal-title {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        .piso-corte-form-modal-container .form-content {
            padding: 32px;
        }

        .piso-corte-form-modal-container .section-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }

        .piso-corte-form-modal-container .section-title {
            color: #1a202c;
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 16px;
        }

        .piso-corte-form-modal-container .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .piso-corte-form-modal-container .form-group {
            display: flex;
            flex-direction: column;
        }

        .piso-corte-form-modal-container .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 500;
            color: #1f1f1fff;
            margin-bottom: 8px;
        }

        .piso-corte-form-modal-container .label-icon {
            width: 18px;
            height: 18px;
            color: #3b82f6;
            stroke-width: 2;
        }

        .piso-corte-form-modal-container .form-input,
        .piso-corte-form-modal-container .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            color: #2d3748;
            background: #f7fafc;
            transition: all 0.3s ease;
        }

        .piso-corte-form-modal-container .uppercase-input {
            text-transform: uppercase;
        }

        .piso-corte-form-modal-container .form-input:focus,
        .piso-corte-form-modal-container .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .piso-corte-form-modal-container .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }

        .piso-corte-form-modal-container .btn {
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

        .piso-corte-form-modal-container .btn svg {
            width: 18px;
            height: 18px;
        }

        .piso-corte-form-modal-container .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .piso-corte-form-modal-container .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.4);
        }

        .piso-corte-form-modal-container .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .piso-corte-form-modal-container .btn-secondary:hover {
            background: #cbd5e0;
        }

        .piso-corte-form-modal-container .autocomplete-container {
            position: relative;
        }

        .piso-corte-form-modal-container .autocomplete-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .piso-corte-form-modal-container .autocomplete-suggestions div {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            color: #2d3748;
        }

        .piso-corte-form-modal-container .autocomplete-suggestions div:hover {
            background: #f8fafc;
        }

        .piso-corte-form-modal-container .autocomplete-suggestions div:last-child {
            border-bottom: none;
        }

        .piso-corte-form-modal-container .autocomplete-suggestions .create-new {
            color: #3b82f6;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .piso-corte-form-modal-container .form-grid {
                grid-template-columns: 1fr;
            }

            .piso-corte-form-modal-container .form-content {
                padding: 20px;
            }

            .piso-corte-form-modal-container .modal-header {
                padding: 20px;
            }
        }
    </style>

    <script>
        // ========================================
        // UTILIDADES GENERALES (Single Responsibility)
        // ========================================
        const Utils = {
            debounce(func, delay) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), delay);
                };
            },

            getCsrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            },

            handleError(error, message) {
                console.error(message, error);
                alert(`${message}: ${error.message || 'Error desconocido'}`);
            }
        };

        // ========================================
        // GESTOR DE INPUTS EN MAYÚSCULAS (Single Responsibility)
        // ========================================
        class UppercaseInputManager {
            constructor(selector) {
                this.inputs = document.querySelectorAll(selector);
                this.initialize();
            }

            initialize() {
                this.inputs.forEach(input => {
                    input.addEventListener('input', this.handleInput.bind(this));
                    input.addEventListener('paste', this.handlePaste.bind(this));
                });
            }

            handleInput(e) {
                const input = e.target;
                const start = input.selectionStart;
                const end = input.selectionEnd;
                input.value = input.value.toUpperCase();
                input.setSelectionRange(start, end);
            }

            handlePaste(e) {
                e.preventDefault();
                const input = e.target;
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const start = input.selectionStart;
                const end = input.selectionEnd;
                const before = input.value.substring(0, start);
                const after = input.value.substring(end);
                input.value = before + pastedText.toUpperCase() + after;
                const newPosition = start + pastedText.length;
                input.setSelectionRange(newPosition, newPosition);
            }
        }

        // ========================================
        // SERVICIO HTTP (Single Responsibility)
        // ========================================
        class HttpService {
            async get(url) {
                try {
                    const response = await fetch(url);
                    return await response.json();
                } catch (error) {
                    throw new Error(`GET request failed: ${error.message}`);
                }
            }

            async post(url, data) {
                try {
                    const formData = new FormData();
                    for (const key in data) {
                        formData.append(key, data[key]);
                    }
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': Utils.getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    return await response.json();
                } catch (error) {
                    throw new Error(`POST request failed: ${error.message}`);
                }
            }
        }

        // ========================================
        // GESTOR DE SUGERENCIAS (Single Responsibility)
        // ========================================
        class SuggestionsManager {
            constructor(container) {
                this.container = container;
            }

            show() {
                this.container.style.display = 'block';
            }

            hide() {
                this.container.style.display = 'none';
            }

            clear() {
                this.container.innerHTML = '';
            }

            addItem(text, onClick, isCreateNew = false) {
                const div = document.createElement('div');
                div.textContent = text;
                if (isCreateNew) {
                    div.classList.add('create-new');
                }
                div.addEventListener('click', onClick);
                this.container.appendChild(div);
            }
        }

        // ========================================
        // AUTOCOMPLETE BASE (Open/Closed Principle)
        // ========================================
        class AutocompleteBase {
            constructor(config) {
                this.inputElement = document.getElementById(config.inputId);
                this.hiddenElement = document.getElementById(config.hiddenId);
                this.suggestionsElement = document.getElementById(config.suggestionsId);
                this.searchRoute = config.searchRoute;
                this.createRoute = config.createRoute;
                this.displayKey = config.displayKey;
                this.createKey = config.createKey;
                this.onSelect = config.onSelect || (() => {});
                
                console.log('AutocompleteBase inicializado para:', config.inputId, 'con callback:', typeof this.onSelect);
                
                this.httpService = new HttpService();
                this.suggestionsManager = new SuggestionsManager(this.suggestionsElement);
                
                this.initialize();
            }

            initialize() {
                this.inputElement.addEventListener('input', 
                    Utils.debounce(this.handleInput.bind(this), 300)
                );
            }

            async handleInput(e) {
                const query = e.target.value.trim();
                
                if (query.length < 2) {
                    this.suggestionsManager.hide();
                    return;
                }

                try {
                    const data = await this.search(query);
                    this.renderSuggestions(data, query);
                } catch (error) {
                    Utils.handleError(error, 'Error al buscar');
                }
            }

            async search(query) {
                return await this.httpService.get(`${this.searchRoute}?q=${encodeURIComponent(query)}`);
            }

            renderSuggestions(data, query) {
                this.suggestionsManager.clear();
                
                const items = this.getItemsFromData(data);
                
                if (items.length > 0) {
                    items.forEach(item => {
                        this.suggestionsManager.addItem(
                            item[this.displayKey],
                            () => this.selectItem(item)
                        );
                    });
                }

                this.suggestionsManager.addItem(
                    `Crear nuevo: "${query}"`,
                    () => this.createNewItem(query),
                    true
                );

                this.suggestionsManager.show();
            }

            getItemsFromData(data) {
                // Override en clases hijas
                return [];
            }

            selectItem(item) {
                console.log('Seleccionando item:', item);
                this.inputElement.value = item[this.displayKey];
                this.hiddenElement.value = item.id;
                console.log('Hidden input ID actualizado a:', item.id);
                this.suggestionsManager.hide();
                console.log('Ejecutando callback onSelect...');
                this.onSelect(item);
            }

            async createNewItem(nombre) {
                try {
                    const data = await this.httpService.post(this.createRoute, {
                        [this.createKey]: nombre.toUpperCase()
                    });

                    if (data.success) {
                        const createdItem = this.getCreatedItemFromData(data);
                        this.selectItem(createdItem);
                    } else {
                        alert(`Error al crear: ${data.message || 'Error desconocido'}`);
                    }
                } catch (error) {
                    Utils.handleError(error, 'Error al crear el elemento');
                }
            }

            getCreatedItemFromData(data) {
                // Override en clases hijas
                return null;
            }
        }

        // ========================================
        // AUTOCOMPLETE ESPECÍFICOS (Liskov Substitution)
        // ========================================
        class TelaAutocomplete extends AutocompleteBase {
            getItemsFromData(data) {
                return data.telas || [];
            }

            getCreatedItemFromData(data) {
                return data.tela;
            }
        }

        class MaquinaAutocomplete extends AutocompleteBase {
            getItemsFromData(data) {
                return data.maquinas || [];
            }

            getCreatedItemFromData(data) {
                return data.maquina;
            }
        }

        class OperarioAutocomplete extends AutocompleteBase {
            getItemsFromData(data) {
                return data.operarios || [];
            }

            getCreatedItemFromData(data) {
                return data.operario;
            }
        }

        // ========================================
        // GESTOR DE TIEMPO DE CICLO (Single Responsibility)
        // ========================================
        class TiempoCicloManager {
            constructor(telaIdSelector, maquinaIdSelector, tiempoCicloSelector, route) {
                this.telaIdElement = document.getElementById(telaIdSelector);
                this.maquinaIdElement = document.getElementById(maquinaIdSelector);
                this.tiempoCicloElement = document.querySelector(tiempoCicloSelector);
                this.route = route;
                this.httpService = new HttpService();
                
                console.log('TiempoCicloManager inicializado:');
                console.log('  - Tela element:', this.telaIdElement);
                console.log('  - Maquina element:', this.maquinaIdElement);
                console.log('  - TiempoCiclo element:', this.tiempoCicloElement);
                console.log('  - Route:', this.route);
            }

            async update() {
                const telaId = this.telaIdElement.value;
                const maquinaId = this.maquinaIdElement.value;

                console.log('Actualizando tiempo de ciclo - Tela ID:', telaId, 'Máquina ID:', maquinaId);

                if (!telaId || !maquinaId) {
                    console.log('Falta tela o máquina, no se puede buscar tiempo de ciclo');
                    return;
                }

                try {
                    const data = await this.httpService.get(
                        `${this.route}?tela_id=${telaId}&maquina_id=${maquinaId}`
                    );

                    console.log('Respuesta del servidor:', data);

                    if (data.success) {
                        console.log('Intentando actualizar campo. Elemento:', this.tiempoCicloElement);
                        console.log('Valor actual del campo:', this.tiempoCicloElement?.value);
                        console.log('Nuevo valor a asignar:', data.tiempo_ciclo);
                        
                        this.tiempoCicloElement.value = data.tiempo_ciclo;
                        this.tiempoCicloElement.style.backgroundColor = '#d4edda';
                        
                        console.log('Valor del campo después de asignar:', this.tiempoCicloElement.value);
                        console.log('Tiempo de ciclo encontrado:', data.tiempo_ciclo);
                        
                        setTimeout(() => {
                            this.tiempoCicloElement.style.backgroundColor = '';
                        }, 2000);
                    } else {
                        this.tiempoCicloElement.value = '';
                        this.tiempoCicloElement.style.backgroundColor = '#fff3cd';
                        console.log('No se encontró tiempo de ciclo para esta combinación');
                        setTimeout(() => {
                            this.tiempoCicloElement.style.backgroundColor = '';
                        }, 2000);
                    }
                } catch (error) {
                    console.error('Error al obtener tiempo de ciclo:', error);
                    this.tiempoCicloElement.value = '';
                    this.tiempoCicloElement.style.backgroundColor = '#f8d7da';
                    setTimeout(() => {
                        this.tiempoCicloElement.style.backgroundColor = '';
                    }, 2000);
                }
            }
        }

        // ========================================
        // GESTOR DE FORMULARIO (Single Responsibility)
        // ========================================
        class FormManager {
            constructor(formId, submitRoute) {
                this.form = document.getElementById(formId);
                this.submitRoute = submitRoute;
                this.httpService = new HttpService();
                this.initialize();
            }

            initialize() {
                this.form.addEventListener('submit', this.handleSubmit.bind(this));
            }

            async handleSubmit(e) {
                e.preventDefault();

                // Validate hidden inputs
                const telaId = document.getElementById('tela_id').value;
                const maquinaId = document.getElementById('maquina_id').value;
                const operarioId = document.getElementById('operario_id').value;

                if (!telaId) {
                    alert('Por favor selecciona una tela válida.');
                    document.getElementById('tela_autocomplete').focus();
                    return;
                }

                if (!maquinaId) {
                    alert('Por favor selecciona una máquina válida.');
                    document.getElementById('maquina_autocomplete').focus();
                    return;
                }

                if (!operarioId) {
                    alert('Por favor selecciona un operario válido.');
                    document.getElementById('operario_autocomplete').focus();
                    return;
                }

                const formData = new FormData(this.form);
                const data = {};
                formData.forEach((value, key) => {
                    data[key] = value;
                });

                try {
                    const response = await this.httpService.post(this.submitRoute, data);

                    if (response.success) {
                        this.onSuccess(response);
                    } else {
                        alert(`Error al guardar: ${response.message || 'Error desconocido'}`);
                    }
                } catch (error) {
                    Utils.handleError(error, 'Error al procesar la solicitud');
                }
            }

            onSuccess(response) {
                NotificationManager.showSuccess('✅ Registro guardado correctamente');
                this.closeModal();
                this.resetForm();
                this.updateTable(response.registro);
            }

            closeModal() {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'piso-corte-form' }));
            }

            resetForm() {
                this.form.reset();
                document.getElementById('tela_id').value = '';
                document.getElementById('maquina_id').value = '';
                document.getElementById('operario_id').value = '';
            }

            updateTable(registro) {
                if (window.actualizarTablaCorte) {
                    window.actualizarTablaCorte(registro);
                } else {
                    window.location.reload();
                }
            }
        }

        // ========================================
        // GESTOR DE NOTIFICACIONES (Single Responsibility)
        // ========================================
        class NotificationManager {
            static showSuccess(text) {
                const notification = document.createElement('div');
                notification.textContent = text;
                notification.style.cssText = `
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background-color: #2e7d32;
                    color: white;
                    padding: 20px 40px;
                    font-size: 18px;
                    border-radius: 10px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                    z-index: 9999;
                    text-align: center;
                `;

                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }
        }

        // ========================================
        // GESTOR DE CLICKS EXTERNOS (Single Responsibility)
        // ========================================
        class OutsideClickManager {
            constructor(elements) {
                this.elements = elements;
                this.initialize();
            }

            initialize() {
                document.addEventListener('click', this.handleClick.bind(this));
            }

            handleClick(e) {
                this.elements.forEach(({ trigger, target }) => {
                    if (!trigger.contains(e.target) && !target.contains(e.target)) {
                        target.style.display = 'none';
                    }
                });
            }
        }

        // ========================================
        // FUNCIÓN PARA CERRAR MODAL
        // ========================================
        function closeCorteModal() {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'piso-corte-form' }));
        }

        // ========================================
        // INICIALIZACIÓN (Dependency Injection)
        // ========================================
        let isInitialized = false;
        
        function initializeCorteForm() {
            if (isInitialized) return;
            
            console.log('Inicializando formulario de corte...');
            
            // Verificar que los elementos existen
            if (!document.getElementById('tela_autocomplete')) {
                console.error('Elementos del formulario no encontrados, reintentando...');
                setTimeout(initializeCorteForm, 100);
                return;
            }
            
            isInitialized = true;
            
            // Inicializar inputs en mayúsculas
            new UppercaseInputManager('.uppercase-input');

            // Gestor de tiempo de ciclo
            const tiempoCicloManager = new TiempoCicloManager(
                'tela_id',
                'maquina_id',
                '#registroCorteForm input[name="tiempo_ciclo"]',
                '{{ route("get-tiempo-ciclo") }}'
            );

            // Inicializar autocompletes con callback para tiempo de ciclo
            const onSelectCallback = () => {
                console.log('Callback ejecutado, llamando a tiempoCicloManager.update()');
                tiempoCicloManager.update();
            };

            new TelaAutocomplete({
                inputId: 'tela_autocomplete',
                hiddenId: 'tela_id',
                suggestionsId: 'tela_suggestions',
                searchRoute: '{{ route("search-telas") }}',
                createRoute: '{{ route("store-tela") }}',
                displayKey: 'nombre_tela',
                createKey: 'nombre_tela',
                onSelect: onSelectCallback
            });

            new MaquinaAutocomplete({
                inputId: 'maquina_autocomplete',
                hiddenId: 'maquina_id',
                suggestionsId: 'maquina_suggestions',
                searchRoute: '{{ route("search-maquinas") }}',
                createRoute: '{{ route("store-maquina") }}',
                displayKey: 'nombre_maquina',
                createKey: 'nombre_maquina',
                onSelect: onSelectCallback
            });

            new OperarioAutocomplete({
                inputId: 'operario_autocomplete',
                hiddenId: 'operario_id',
                suggestionsId: 'operario_suggestions',
                searchRoute: '{{ route("search-operarios") }}',
                createRoute: '{{ route("store-operario") }}',
                displayKey: 'name',
                createKey: 'name'
            });

            // Gestor de clicks externos
            new OutsideClickManager([
                {
                    trigger: document.getElementById('tela_autocomplete'),
                    target: document.getElementById('tela_suggestions')
                },
                {
                    trigger: document.getElementById('maquina_autocomplete'),
                    target: document.getElementById('maquina_suggestions')
                },
                {
                    trigger: document.getElementById('operario_autocomplete'),
                    target: document.getElementById('operario_suggestions')
                }
            ]);

            // Gestor de formulario
            new FormManager('registroCorteForm', '{{ route("piso-corte.store") }}');
            
            console.log('Formulario de corte inicializado correctamente');
        }
        
        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', initializeCorteForm);
        
        // También inicializar cuando se abra el modal
        window.addEventListener('open-modal', (e) => {
            if (e.detail === 'piso-corte-form') {
                console.log('Modal de corte abierto, inicializando...');
                setTimeout(initializeCorteForm, 100);
            }
        });
    </script>
</x-modal>