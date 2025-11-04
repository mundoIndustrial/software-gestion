<style>
    .balanceo-table td {
        position: relative;
    }
    .balanceo-table td::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 100%;
        pointer-events: none;
    }
    .balanceo-table tbody tr:hover {
        background: rgba(255, 157, 88, 0.05) !important;
    }
</style>

<div style="background: var(--color-bg-sidebar); padding: 24px; border-radius: 12px; margin-bottom: 24px; border: 1px solid var(--color-border-hr); box-shadow: 0 1px 3px var(--color-shadow);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; font-size: 18px; color: var(--color-text-primary); display: flex; align-items: center; gap: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
            <span class="material-symbols-rounded" style="color: #ff9d58; font-size: 24px;">list_alt</span>
            Operaciones del Balanceo
        </h2>
        <button @click="showAddModal = true" 
                title="Nueva Operación"
                style="background: #ff9d58; color: white; border: none; padding: 12px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; box-shadow: 0 2px 4px rgba(255, 157, 88, 0.3); transition: all 0.2s;" 
                onmouseover="this.style.background='#e88a47'; this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(255, 157, 88, 0.4)'" 
                onmouseout="this.style.background='#ff9d58'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(255, 157, 88, 0.3)'">
            <span class="material-symbols-rounded" style="font-size: 24px;">add</span>
        </button>
    </div>

    <div style="background: var(--color-bg-primary); border-radius: 10px; overflow: hidden; border: 1px solid var(--color-border-hr);">
        <div class="table-scroll-container" style="overflow-x: auto;">
            <table class="modern-table balanceo-table" style="border-collapse: separate; border-spacing: 0; width: auto; min-width: 100%; user-select: text;">
                <thead>
                    <tr style="background: #ff9d58; color: white;">
                        <th style="padding: 12px 10px; text-align: center; font-weight: 600; font-size: 12px; text-transform: uppercase; width: 60px; white-space: nowrap; user-select: text;">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                Letra
                                <button @click="copyColumn('letra')" title="Copiar columna" style="background: rgba(255,255,255,0.2); border: none; padding: 2px 4px; border-radius: 3px; cursor: pointer; display: flex; align-items: center;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                    <span class="material-symbols-rounded" style="font-size: 14px;">content_copy</span>
                                </button>
                            </div>
                        </th>
                        <th style="padding: 12px 14px; text-align: left; font-weight: 600; font-size: 12px; text-transform: uppercase; white-space: nowrap; user-select: text;">
                            <div style="display: flex; align-items: center; gap: 4px;">
                                Operación
                                <button @click="copyColumn('operacion')" title="Copiar columna" style="background: rgba(255,255,255,0.2); border: none; padding: 2px 4px; border-radius: 3px; cursor: pointer; display: flex; align-items: center;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                    <span class="material-symbols-rounded" style="font-size: 14px;">content_copy</span>
                                </button>
                            </div>
                        </th>
                        <th style="padding: 12px 10px; text-align: center; font-weight: 600; font-size: 12px; text-transform: uppercase; width: 90px; white-space: nowrap; user-select: text;">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                Prec.
                                <button @click="copyColumn('precedencia')" title="Copiar columna" style="background: rgba(255,255,255,0.2); border: none; padding: 2px 4px; border-radius: 3px; cursor: pointer; display: flex; align-items: center;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                    <span class="material-symbols-rounded" style="font-size: 14px;">content_copy</span>
                                </button>
                            </div>
                        </th>
                        <th style="padding: 12px 10px; text-align: left; font-weight: 600; font-size: 12px; text-transform: uppercase; width: 130px; white-space: nowrap; user-select: text;">
                            <div style="display: flex; align-items: center; gap: 4px;">
                                Máquina
                                <button @click="copyColumn('maquina')" title="Copiar columna" style="background: rgba(255,255,255,0.2); border: none; padding: 2px 4px; border-radius: 3px; cursor: pointer; display: flex; align-items: center;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                    <span class="material-symbols-rounded" style="font-size: 14px;">content_copy</span>
                                </button>
                            </div>
                        </th>
                        <th style="padding: 12px 10px; text-align: center; font-weight: 600; font-size: 12px; text-transform: uppercase; width: 80px; white-space: nowrap; user-select: text;">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                SAM
                                <button @click="copyColumn('sam')" title="Copiar columna" style="background: rgba(255,255,255,0.2); border: none; padding: 2px 4px; border-radius: 3px; cursor: pointer; display: flex; align-items: center;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                    <span class="material-symbols-rounded" style="font-size: 14px;">content_copy</span>
                                </button>
                            </div>
                        </th>
                        <th style="padding: 12px 10px; text-align: center; font-weight: 600; font-size: 12px; text-transform: uppercase; width: 80px; white-space: nowrap; user-select: text;">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                Operario
                                <button @click="copyColumn('operario')" title="Copiar columna" style="background: rgba(255,255,255,0.2); border: none; padding: 2px 4px; border-radius: 3px; cursor: pointer; display: flex; align-items: center;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                    <span class="material-symbols-rounded" style="font-size: 14px;">content_copy</span>
                                </button>
                            </div>
                        </th>
                        <th style="padding: 12px 10px; text-align: center; font-weight: 600; font-size: 12px; text-transform: uppercase; width: 60px; white-space: nowrap; user-select: text;">OP</th>
                        <th style="padding: 12px 10px; text-align: center; font-weight: 600; font-size: 12px; text-transform: uppercase; width: 90px; white-space: nowrap; user-select: text;">Sección</th>
                        <th style="padding: 12px 10px; text-align: center; font-weight: 600; font-size: 12px; text-transform: uppercase; width: 90px; white-space: nowrap; user-select: text;">Op. A</th>
                        <th style="padding: 12px 10px; text-align: center; font-weight: 600; font-size: 12px; text-transform: uppercase; width: 120px; white-space: nowrap; user-select: none;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="operacion in operaciones" :key="operacion.id">
                        <tr style="border-bottom: 1px solid var(--color-border-hr); transition: background 0.2s;" onmouseover="this.style.background='rgba(255, 157, 88, 0.05)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 10px; font-weight: 600; color: #ff9d58; text-align: center; font-size: 14px; white-space: nowrap;" x-text="operacion.letra"></td>
                            <td style="padding: 10px 14px; color: var(--color-text-primary); font-size: 13px; white-space: nowrap;" x-text="operacion.operacion"></td>
                            <td style="padding: 10px; color: var(--color-text-placeholder); text-align: center; font-size: 13px; white-space: nowrap;" x-text="operacion.precedencia || '-'"></td>
                            <td style="padding: 10px; color: var(--color-text-placeholder); font-size: 13px; white-space: nowrap;" x-text="operacion.maquina || '-'"></td>
                            <td style="padding: 10px; font-weight: 600; color: #f5576c; text-align: center; font-size: 14px; white-space: nowrap;" x-text="parseFloat(operacion.sam).toFixed(2)"></td>
                            <td style="padding: 10px; color: var(--color-text-placeholder); text-align: center; font-size: 13px; white-space: nowrap;" x-text="operacion.operario || '-'"></td>
                            <td style="padding: 10px; color: var(--color-text-placeholder); text-align: center; font-size: 13px; white-space: nowrap;" x-text="operacion.op || '-'"></td>
                            <td style="padding: 10px; text-align: center; white-space: nowrap;">
                                <span :style="'background: ' + getSectionColor(operacion.seccion) + '; color: white; padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; display: inline-block;'" x-text="operacion.seccion"></span>
                            </td>
                            <td style="padding: 10px; color: var(--color-text-placeholder); text-align: center; font-size: 13px; white-space: nowrap;" x-text="operacion.operario_a || '-'"></td>
                            <td style="padding: 10px; text-align: center; user-select: none;">
                                <div style="display: flex; gap: 4px; justify-content: center;">
                                    <button @click="editOperacion(operacion)" 
                                            title="Editar"
                                            style="background: #ff9d58; color: white; border: none; padding: 6px 8px; border-radius: 6px; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; justify-content: center;" 
                                            onmouseover="this.style.background='#e88a47'" 
                                            onmouseout="this.style.background='#ff9d58'">
                                        <span class="material-symbols-rounded" style="font-size: 16px;">edit</span>
                                    </button>
                                    <button @click="deleteOperacion(operacion.id)" 
                                            title="Eliminar"
                                            style="background: #f5576c; color: white; border: none; padding: 6px 8px; border-radius: 6px; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; justify-content: center;" 
                                            onmouseover="this.style.background='#e04558'" 
                                            onmouseout="this.style.background='#f5576c'">
                                        <span class="material-symbols-rounded" style="font-size: 16px;">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    
                    <!-- Fila de Total -->
                    <template x-if="operaciones.length > 0">
                        <tr style="background: rgba(255, 157, 88, 0.1); border-top: 2px solid #ff9d58;">
                            <td colspan="4" style="padding: 12px 14px; text-align: right; font-weight: 700; color: var(--color-text-primary); font-size: 13px; text-transform: uppercase;">
                                Total SAM:
                            </td>
                            <td style="padding: 12px 10px; font-weight: 700; color: #ff9d58; font-size: 16px; text-align: center;" 
                                x-text="operaciones.reduce((sum, op) => sum + parseFloat(op.sam || 0), 0).toFixed(2) + 's'">
                            </td>
                            <td colspan="5" style="padding: 12px;"></td>
                        </tr>
                    </template>
                    
                    <template x-if="operaciones.length === 0">
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px; background: rgba(255, 157, 88, 0.05);">
                                <span class="material-symbols-rounded" style="font-size: 48px; display: block; margin-bottom: 10px; opacity: 0.3; color: var(--color-text-placeholder);">inbox</span>
                                <p style="color: var(--color-text-placeholder); font-size: 14px; margin: 0;">No hay operaciones registradas</p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
