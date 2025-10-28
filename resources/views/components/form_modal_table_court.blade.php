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
                            <input type="text" name="orden_produccion" class="form-input" placeholder="Número de orden" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M4 6h16M4 12h16M4 18h16" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                TELA *
                            </label>
                            <input type="text" name="tela" class="form-input" placeholder="Tipo de tela" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                HORA *
                            </label>
                            <select name="hora" class="form-select" required>
                                <option value="">Seleccionar hora</option>
                                <option value="HORA 01">HORA 01</option>
                                <option value="HORA 02">HORA 02</option>
                                <option value="HORA 03">HORA 03</option>
                                <option value="HORA 04">HORA 04</option>
                                <option value="HORA 05">HORA 05</option>

                                <option value="HORA 07">HORA 07</option>
                                <option value="HORA 08">HORA 08</option>
                                <option value="HORA 09">HORA 09</option>
                                <option value="HORA 10">HORA 10</option>
                                <option value="HORA 11">HORA 11</option>
                                <option value="HORA 12">HORA 12</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-width="2"/>
                                </svg>
                                OPERARIO *
                            </label>
                            <input type="text" name="operario" list="operarios" class="form-input" placeholder="Seleccionar operario" required />
                            <datalist id="operarios">
                                <option value="Operario 1">
                                <option value="Operario 2">
                                <option value="Operario 3">
                            </datalist>
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
                            <select name="maquina" class="form-select" required>
                                <option value="">Seleccionar máquina</option>
                                <option value="BANANA">BANANA</option>
                                <option value="VERTICAL">VERTICAL</option>
                                <option value="TIJERA">TIJERA</option>
                                <option value="N.A">N.A</option>
                            </select>
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
                                CANTIDAD PRODUCIDA
                            </label>
                            <input type="number" name="cantidad_producida" class="form-input" placeholder="0" />
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
                                TIEMPO DE TRAZADO *
                            </label>
                            <input type="number" name="tiempo_trazado" step="0.01" class="form-input" placeholder="0.00" required />
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
        function closeCorteModal() {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'piso-corte-form' }));
        }

        document.getElementById('registroCorteForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            // Convertir FormData a objeto
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            // Enviar datos al servidor
            fetch('{{ route("piso-corte.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarMensajeExito('✅ Registro guardado correctamente');
                    
                    // Cerrar modal y resetear formulario
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'piso-corte-form' }));
                    this.reset();

                    // Actualizar tabla si existe la función
                    if (window.actualizarTablaCorte) {
                        window.actualizarTablaCorte(data.registro);
                    } else {
                        // Recargar página si no existe la función
                        window.location.reload();
                    }
                } else {
                    alert('Error al guardar el registro: ' + (data.message || 'Error desconocido'));
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