<div style="background: rgba(255, 255, 255, 0.03); padding: 24px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(255, 157, 88, 0.15);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; font-size: 18px; color: white; display: flex; align-items: center; gap: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
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

    <div style="background: rgba(255, 255, 255, 0.05); border-radius: 10px; overflow: hidden; border: 1px solid rgba(255, 157, 88, 0.1);">
        <div class="table-scroll-container">
            <table class="modern-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #ff9d58; color: white;">
                        <th style="padding: 14px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase;">Letra</th>
                        <th style="padding: 14px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase;">Operación</th>
                        <th style="padding: 14px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase;">Precedencia</th>
                        <th style="padding: 14px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase;">Máquina</th>
                        <th style="padding: 14px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase;">SAM (s)</th>
                        <th style="padding: 14px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase;">Operario</th>
                        <th style="padding: 14px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase;">OP</th>
                        <th style="padding: 14px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase;">Sección</th>
                        <th style="padding: 14px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase;">Operario A</th>
                        <th style="padding: 14px; text-align: center; font-weight: 600; font-size: 13px; text-transform: uppercase;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="operacion in operaciones" :key="operacion.id">
                        <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.1); transition: background 0.2s;" onmouseover="this.style.background='rgba(255, 157, 88, 0.05)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 14px; font-weight: 600; color: #ff9d58;" x-text="operacion.letra"></td>
                            <td style="padding: 14px; color: white;" x-text="operacion.operacion"></td>
                            <td style="padding: 14px; color: #94a3b8;" x-text="operacion.precedencia || '-'"></td>
                            <td style="padding: 14px; color: #666;" x-text="operacion.maquina || '-'"></td>
                            <td style="padding: 14px; font-weight: 600; color: #f5576c;" x-text="parseFloat(operacion.sam).toFixed(2)"></td>
                            <td style="padding: 14px; color: #94a3b8;" x-text="operacion.operario || '-'"></td>
                            <td style="padding: 14px; color: #666;" x-text="operacion.op || '-'"></td>
                            <td style="padding: 14px;">
                                <span :style="'background: ' + getSectionColor(operacion.seccion) + '; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;'" x-text="operacion.seccion"></span>
                            </td>
                            <td style="padding: 14px; color: #555;" x-text="operacion.operario_a || '-'"></td>
                            <td style="padding: 14px; text-align: center;">
                                <button @click="editOperacion(operacion)" style="background: #ff9d58; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; margin-right: 6px; transition: background 0.2s;" onmouseover="this.style.background='#e88a47'" onmouseout="this.style.background='#ff9d58'">
                                    <span class="material-symbols-rounded" style="font-size: 18px;">edit</span>
                                </button>
                                <button @click="deleteOperacion(operacion.id)" style="background: #f5576c; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#e04558'" onmouseout="this.style.background='#f5576c'">
                                    <span class="material-symbols-rounded" style="font-size: 18px;">delete</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="operaciones.length === 0">
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px; background: rgba(255, 157, 88, 0.05);">
                                <span class="material-symbols-rounded" style="font-size: 48px; display: block; margin-bottom: 10px; opacity: 0.3; color: #94a3b8;">inbox</span>
                                <p style="color: #94a3b8; font-size: 14px; margin: 0;">No hay operaciones registradas</p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
