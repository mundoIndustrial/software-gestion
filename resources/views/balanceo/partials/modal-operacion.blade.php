<div x-show="showAddModal" x-cloak style="position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px);" @click.self="showAddModal = false">
    <div class="modern-modal-container" style="max-width: 850px; width: 70%; margin: auto;">
        <div class="modal-header">
            <div class="header-content">
                <div class="icon-wrapper">
                    <svg class="header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <h2 class="modal-title" x-text="editingOperacion ? 'Editar Operación' : 'Nueva Operación'"></h2>
            </div>
        </div>

        <div>
            <div class="form-content">
                <div class="section-card">
                    <h3 class="section-title">Datos de la Operación</h3>
                    <div class="form-grid">
                        <!-- Letra -->
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Letra
                            </label>
                            <input type="text" x-model="formData.letra" class="form-input" />
                        </div>

                        <!-- SAM -->
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                SAM (segundos)
                            </label>
                            <input type="number" step="0.01" x-model="formData.sam" class="form-input" />
                        </div>

                        <!-- Operación -->
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Operación
                            </label>
                            <textarea x-model="formData.operacion" rows="2" class="form-input" style="resize: vertical;"></textarea>
                        </div>

                        <!-- Precedencia -->
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M9 5l7 7-7 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Precedencia
                            </label>
                            <input type="text" x-model="formData.precedencia" class="form-input" />
                        </div>

                        <!-- Máquina -->
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Máquina
                            </label>
                            <input type="text" x-model="formData.maquina" class="form-input" />
                        </div>

                        <!-- Operario -->
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Operario
                            </label>
                            <input type="text" x-model="formData.operario" class="form-input" />
                        </div>

                        <!-- OP -->
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <rect x="4" y="2" width="16" height="20" rx="2" stroke-width="2"/>
                                    <line x1="8" y1="6" x2="16" y2="6" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                OP
                            </label>
                            <input type="text" x-model="formData.op" class="form-input" />
                        </div>

                        <!-- Sección -->
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Sección
                            </label>
                            <select x-model="formData.seccion" class="form-select">
                                <option value="DEL">Delantero (DEL)</option>
                                <option value="TRAS">Trasero (TRAS)</option>
                                <option value="ENS">Ensamble (ENS)</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Botones del formulario -->
                <div class="form-actions">
                    <button type="button" @click="resetForm()" class="btn btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Limpiar
                    </button>
                    <button type="button" @click="addOperacionToList()" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Agregar a la Lista
                    </button>
                </div>

                <!-- Lista de Operaciones Pendientes -->
                <div x-show="pendingOperaciones.length > 0" class="section-card" style="margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h3 class="section-title" style="margin: 0;">
                            Operaciones Pendientes (<span x-text="pendingOperaciones.length"></span>)
                        </h3>
                        <button type="button" @click="clearPendingList()" class="btn-clear-list">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 16px; height: 16px;">
                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Limpiar Lista
                        </button>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="pending-table">
                            <thead>
                                <tr>
                                    <th>Letra</th>
                                    <th>SAM</th>
                                    <th>Operación</th>
                                    <th>Precedencia</th>
                                    <th>Máquina</th>
                                    <th>Sección</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(op, index) in pendingOperaciones" :key="index">
                                    <tr>
                                        <td x-text="op.letra"></td>
                                        <td x-text="op.sam"></td>
                                        <td x-text="op.operacion" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></td>
                                        <td x-text="op.precedencia || '-'"></td>
                                        <td x-text="op.maquina || '-'"></td>
                                        <td x-text="op.seccion"></td>
                                        <td>
                                            <button type="button" @click="removePendingOperacion(index)" class="btn-remove">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Botones Finales -->
                <div class="form-actions" style="margin-top: 24px;">
                    <button type="button" @click="showAddModal = false" class="btn btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Cancelar
                    </button>
                    <button type="button" @click="saveAllOperaciones()" class="btn btn-primary" x-show="pendingOperaciones.length > 0">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M5 13l4 4L19 7" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Guardar Todas (<span x-text="pendingOperaciones.length"></span>)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }

.modern-modal-container {
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    min-height: 600px;
    max-height: 90vh;
    overflow-y: auto;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    z-index: 1100;
}

.modal-header {
    background: rgba(59, 130, 246, 0.1);
    backdrop-filter: blur(10px);
    padding: 24px 32px;
    border-bottom: 1px solid rgba(59, 130, 246, 0.2);
}

.header-content {
    display: flex;
    align-items: center;
    gap: 16px;
}

.icon-wrapper {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.header-icon {
    width: 28px;
    height: 28px;
    color: white;
}

.modal-title {
    font-size: 28px;
    font-weight: 700;
    color: white;
    margin: 0;
}

.form-content {
    padding: 32px;
}

.section-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 20px 0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 500;
    color: #1f1f1fff;
    margin-bottom: 8px;
}

.label-icon {
    width: 18px;
    height: 18px;
    color: #3B82F6;
    stroke-width: 2;
}

.form-input,
.form-select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 16px;
    color: #2d3748;
    background: #f7fafc;
    transition: all 0.3s ease;
    text-transform: uppercase;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: #3B82F6;
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 24px;
}

.btn {
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

.btn svg {
    width: 18px;
    height: 18px;
}

.btn-primary {
    background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(59, 130, 246, 0.4);
}

.btn-secondary {
    background: #e2e8f0;
    color: #4a5568;
}

.btn-secondary:hover {
    background: #cbd5e0;
}

.btn-secondary-alt {
    background: rgba(59, 130, 246, 0.1);
    color: #3B82F6;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.btn-secondary-alt:hover {
    background: rgba(59, 130, 246, 0.2);
}

/* Tabla de operaciones pendientes */
.pending-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.pending-table thead {
    background: #f7fafc;
}

.pending-table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #2d3748;
    border-bottom: 2px solid #e2e8f0;
}

.pending-table td {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
    color: #4a5568;
}

.pending-table tbody tr:hover {
    background: #f7fafc;
}

.btn-remove {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 6px;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-remove svg {
    width: 16px;
    height: 16px;
    color: #ef4444;
    stroke-width: 2;
}

.btn-remove:hover {
    background: rgba(239, 68, 68, 0.2);
}

.btn-clear-list {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 13px;
    font-weight: 500;
    color: #ef4444;
}

.btn-clear-list:hover {
    background: rgba(239, 68, 68, 0.2);
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-content {
        padding: 20px;
    }
    
    .modal-header {
        padding: 20px;
    }
    
    .pending-table {
        font-size: 12px;
    }
    
    .pending-table th,
    .pending-table td {
        padding: 8px;
    }
}
</style>
