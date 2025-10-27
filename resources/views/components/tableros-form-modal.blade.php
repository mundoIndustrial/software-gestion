<x-modal name="tableros-form" :show="false" maxWidth="4xl">
    <div class="tableros-form-modal-container">
        <div class="modal-header">
            <div class="header-content">
                <div class="icon-wrapper">
                    <svg class="header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <h2 class="modal-title" id="modalTitle">Registro Control de Piso Producción</h2>
            </div>
        </div>

        <form id="registroForm" method="POST" action="{{ route('tableros.store') }}">
            @csrf
            <input type="hidden" id="activeSection" name="section" value="">
            <div class="form-content">
                <!-- Información General -->
                <div class="section-card">
                    <h3 class="section-title">Información General</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="2"/>
                                    <path d="M16 2v4M8 2v4M3 10h18" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Fecha *
                            </label>
                            <input type="date" name="fecha" class="form-input" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Módulo *
                            </label>
                            <input type="text" name="modulo" list="modulos" class="form-input" placeholder="Escribe o elige un módulo" required />
                            <datalist id="modulos">
                                <option value="MODULO 1">
                                <option value="MODULO 2">
                                <option value="MODULO 3">
                            </datalist>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Orden de Producción *
                            </label>
                            <input type="text" name="orden_produccion" class="form-input" placeholder="Número de orden" required />
                        </div>
                    </div>
                </div>

                <!-- Horas a Registrar -->
                <div class="section-card">
                    <h3 class="section-title">Horas a Registrar *</h3>
                    <div class="horas-selector" id="horasSelector">
                        <!-- Se generarán dinámicamente las 12 horas -->
                    </div>
                    <div class="acciones-horas">
                        <button type="button" class="btn-seleccionar-todas" onclick="seleccionarTodasHoras()">
                            ✅ Seleccionar todas
                        </button>
                         <button type="button" class="btn-deseleccionar-todas" onclick="deseleccionarTodasHoras()">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-trash-icon lucide-trash">
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
            <path d="M3 6h18"/>
            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
        </svg>
    </button>
                    </div>
                    <div class="horas-seleccionadas" id="horasSeleccionadas" style="display: none;">
                        <strong>Horas seleccionadas:</strong>
                        <div class="lista-horas" id="listaHoras"></div>
                    </div>
                </div>

                <!-- Detalles de Producción -->
                <div class="section-card">
                    <h3 class="section-title">Detalles de Producción</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Tiempo de Ciclo *
                            </label>
                            <input type="number" name="tiempo_ciclo" step="any" class="form-input" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
    <circle cx="12" cy="12" r="10" stroke-width="2"/>
    <line x1="10" y1="4" x2="14" y2="4" stroke-width="2" stroke-linecap="round"/>
    <line x1="12" y1="12" x2="15" y2="9" stroke-width="2" stroke-linecap="round"/>
</svg>

                                Porción de Tiempo *
                            </label>
                            <select name="porcion_tiempo" class="form-select" required>
                                <option value="0">0</option>
                                <option value="0.1">0.1</option>
                                <option value="0.2">0.2</option>
                                <option value="0.3">0.3</option>
                                <option value="0.4">0.4</option>
                                <option value="0.5">0.5</option>
                                <option value="0.6">0.6</option>
                                <option value="0.7">0.7</option>
                                <option value="0.8">0.8</option>
                                <option value="0.9">0.9</option>
                                <option value="1">1</option>
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

                                Cantidad Producida
                            </label>
                            <input type="number" name="cantidad" class="form-input" />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Paradas Programadas *
                            </label>
                            <select name="paradas_programadas" class="form-select" required>
                                <option value="DESAYUNO">DESAYUNO</option>
                                <option value="MEDIA TARDE">MEDIA TARDE</option>
                                <option value="NINGUNA">NINGUNA</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
    <path d="M14 2v4a2 2 0 0 0 2 2h4" stroke-width="2" stroke-linecap="round"/>
    <path d="M16 22h2a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v3" stroke-width="2" stroke-linecap="round"/>
    <circle cx="8" cy="16" r="6" stroke-width="2"/>
    <path d="M8 14v2.2l1.6 1" stroke-width="2" stroke-linecap="round"/>
</svg>

                                Paradas No Programadas
                            </label>
                            <input type="text" name="paradas_no_programadas" class="form-input" />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
    <path d="M20 12v5" stroke-width="2" stroke-linecap="round"/>
    <path d="M20 21h.01" stroke-width="2" stroke-linecap="round"/>
    <path d="M21.25 8.2A10 10 0 1 0 16 21.16" stroke-width="2" stroke-linecap="round"/>
</svg>

                                Tiempo de Parada No Programada
                            </label>
                            <input type="number" name="tiempo_parada_no_programada" step="any" class="form-input" />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Número de Operarios *
                            </label>
                            <input type="number" name="numero_operarios" class="form-input" required />
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
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
        .tableros-form-modal-container {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            min-height: 600px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            z-index: 1100;
        }

        .tableros-form-modal-container .modal-header {
            background: rgba(255, 107, 53, 0.1);
            backdrop-filter: blur(10px);
            padding: 24px 32px;
            border-bottom: 1px solid rgba(255, 107, 53, 0.2);
        }

        .tableros-form-modal-container .header-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .tableros-form-modal-container .icon-wrapper {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #FF6B35 0%, #e55a2b 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }

        .tableros-form-modal-container .header-icon {
            width: 28px;
            height: 28px;
            color: white;
        }

        .tableros-form-modal-container .modal-title {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        .tableros-form-modal-container .form-content {
            padding: 32px;
        }

        .tableros-form-modal-container .section-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }

        .tableros-form-modal-container .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
            margin: 0 0 20px 0;
        }

        .tableros-form-modal-container .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .tableros-form-modal-container .form-group {
            display: flex;
            flex-direction: column;
        }

        .tableros-form-modal-container .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 500;
            color: #1f1f1fff;
            margin-bottom: 8px;
        }

        .tableros-form-modal-container .label-icon {
            width: 18px;
            height: 18px;
            color: #ff9d58;
            stroke-width: 2;
        }

        .tableros-form-modal-container .form-input,
        .tableros-form-modal-container .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            color: #2d3748;
            background: #f7fafc;
            transition: all 0.3s ease;
        }

        .tableros-form-modal-container .form-input:focus,
        .tableros-form-modal-container .form-select:focus {
            outline: none;
            border-color: #ff9d58;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 157, 88, 0.1);
        }

        /* Estilos para el selector de horas */
        .tableros-form-modal-container .horas-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .tableros-form-modal-container .hora-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: white;
            border-radius: 6px;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tableros-form-modal-container .hora-checkbox:hover {
            border-color: #FF6B35;
            background: #f0f8ff;
        }

        .tableros-form-modal-container .hora-checkbox.selected {
            border-color: #FF6B35;
            background: #ffe8e8;
        }

        .tableros-form-modal-container .hora-checkbox input[type="checkbox"] {
            width: auto;
            margin: 0;
            cursor: pointer;
            accent-color: #FF6B35;
        }

        .tableros-form-modal-container .hora-checkbox label {
            margin: 0;
            font-weight: 500;
            cursor: pointer;
            font-size: 14px;
            color: black;
        }

        .tableros-form-modal-container .horas-seleccionadas {
            margin-top: 15px;
            padding: 12px;
            background: #e8f5e8;
            border-radius: 6px;
            border-left: 4px solid #FF6B35;
        }

        .tableros-form-modal-container .horas-seleccionadas strong {
            color: #FF6B35;
        }

        .tableros-form-modal-container .horas-seleccionadas .lista-horas {
            margin-top: 5px;
            font-size: 14px;
            color: #555;
        }

        .tableros-form-modal-container .acciones-horas {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .tableros-form-modal-container .acciones-horas button {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
        }

        .tableros-form-modal-container .btn-seleccionar-todas {
            background: #2196f3;
            color: white;
        }

        .tableros-form-modal-container .btn-deseleccionar-todas {
            background: #ff9800;
            color: white;
        }

        .tableros-form-modal-container .btn-seleccionar-todas:hover {
            background: #1976d2;
        }

        .tableros-form-modal-container .btn-deseleccionar-todas:hover {
            background: #f57c00;
        }

        .tableros-form-modal-container .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }

        .tableros-form-modal-container .btn {
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

        .tableros-form-modal-container .btn-primary {
            background: linear-gradient(135deg, #FF6B35 0%, #e55a2b 100%);
            color: white;
        }

        .tableros-form-modal-container .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 53, 0.4);
        }

        .tableros-form-modal-container .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .tableros-form-modal-container .btn-secondary:hover {
            background: #cbd5e0;
        }

        @media (max-width: 768px) {
            .tableros-form-modal-container .form-grid {
                grid-template-columns: 1fr;
            }

            .tableros-form-modal-container .form-content {
                padding: 20px;
            }

            .tableros-form-modal-container .modal-header {
                padding: 20px;
            }
        }
    </style>

    <script>
        let horasSeleccionadas = [];

        // Generar selector de horas al cargar la página
        function inicializarSelectorHoras() {
            const selector = document.getElementById('horasSelector');

            for (let i = 1; i <= 12; i++) {
                const horaId = "HORA " + String(i).padStart(2, '0');
                const div = document.createElement('div');
                div.className = 'hora-checkbox';
                div.innerHTML = `
                    <input type="checkbox" id="hora${i}" value="${i}">
                    <label>${horaId}</label>
                `;
                div.onclick = function() {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                    actualizarHorasSeleccionadas();
                };
                selector.appendChild(div);
            }
        }

        function actualizarHorasSeleccionadas() {
            const checkboxes = document.querySelectorAll('#horasSelector input[type="checkbox"]');
            horasSeleccionadas = [];

            checkboxes.forEach(checkbox => {
                const container = checkbox.closest('.hora-checkbox');
                if (checkbox.checked) {
                    horasSeleccionadas.push(parseInt(checkbox.value));
                    container.classList.add('selected');
                } else {
                    container.classList.remove('selected');
                }
            });

            // Mostrar/ocultar resumen de horas seleccionadas
            const resumen = document.getElementById('horasSeleccionadas');
            const listaHoras = document.getElementById('listaHoras');

            if (horasSeleccionadas.length > 0) {
                resumen.style.display = 'block';
                const horasTexto = horasSeleccionadas
                    .sort((a, b) => a - b)
                    .map(num => "HORA " + String(num).padStart(2, '0'))
                    .join(', ');
                listaHoras.textContent = horasTexto;
            } else {
                resumen.style.display = 'none';
            }
        }

        function seleccionarTodasHoras() {
            const checkboxes = document.querySelectorAll('#horasSelector input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            actualizarHorasSeleccionadas();
        }

        function deseleccionarTodasHoras() {
            const checkboxes = document.querySelectorAll('#horasSelector input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            actualizarHorasSeleccionadas();
        }

        function closeModal() {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'tableros-form' }));
        }

        // Inicializar cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            inicializarSelectorHoras();
        });

        document.getElementById('registroForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = new FormData(this);

            if (horasSeleccionadas.length === 0) {
                alert("Debes seleccionar al menos una hora");
                return;
            }

            const registros = horasSeleccionadas
                .sort((a, b) => a - b)
                .map(horaNum => ({
                    fecha: form.get("fecha"),
                    modulo: form.get("modulo"),
                    orden_produccion: form.get("orden_produccion"),
                    hora: "HORA " + String(horaNum).padStart(2, '0'),
                    tiempo_ciclo: parseFloat(form.get("tiempo_ciclo")),
                    porcion_tiempo: parseFloat(form.get("porcion_tiempo")),
                    cantidad: form.get("cantidad") ? parseInt(form.get("cantidad")) : null,
                    producida: form.get("cantidad") ? parseInt(form.get("cantidad")) : 0,
                    paradas_programadas: form.get("paradas_programadas"),
                    paradas_no_programadas: form.get("paradas_no_programadas") || null,
                    tiempo_parada_no_programada: form.get("tiempo_parada_no_programada") || null,
                    numero_operarios: parseInt(form.get("numero_operarios")),
                    tiempo_para_programada: 0.00,
                    tiempo_disponible: 0.00,
                    meta: 0,
                    eficiencia: 0.00
                }));

            // Enviar datos al servidor
            fetch('{{ route("tableros.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    registros: registros,
                    section: document.getElementById('activeSection').value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    mostrarMensajeExito(`✅ Se registraron ${registros.length} registro(s) correctamente.`);

                    // Cerrar modal y resetear formulario
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'tableros-form' }));
                    this.reset();

                    // Resetear selector de horas
                    horasSeleccionadas = [];
                    deseleccionarTodasHoras();

                    // Agregar los nuevos registros directamente a la tabla
                    if (window.agregarRegistrosATabla) {
                        window.agregarRegistrosATabla(data.registros, data.section);
                    } else {
                        console.error('Función agregarRegistrosATabla no encontrada');
                    }
                } else {
                    alert('Error al guardar los registros: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud.');
            });
        });

        function mostrarMensajeExito(texto) {
            const mensaje = document.createElement("div");
            mensaje.textContent = texto;
            mensaje.style.position = "fixed";
            mensaje.style.top = "50%";
            mensaje.style.left = "50%";
            mensaje.style.transform = "translate(-50%, -50%)";
            mensaje.style.backgroundColor = "#2e7d32";
            mensaje.style.color = "white";
            mensaje.style.padding = "20px 40px";
            mensaje.style.fontSize = "18px";
            mensaje.style.borderRadius = "10px";
            mensaje.style.boxShadow = "0 4px 12px rgba(0,0,0,0.2)";
            mensaje.style.zIndex = "9999";
            mensaje.style.textAlign = "center";

            document.body.appendChild(mensaje);

            setTimeout(() => {
                mensaje.remove();
            }, 3000);
        }


    </script>
</x-modal>
