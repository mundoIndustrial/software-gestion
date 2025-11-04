<div style="background: var(--color-bg-sidebar); padding: 32px; border-radius: 20px; border: 1px solid var(--color-border-hr); box-shadow: 0 1px 3px var(--color-shadow);" x-data="{ mostrarCuelloBotella: false, redondearValores: false }">
    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(255, 157, 88, 0.3);">
                <svg style="width: 28px; height: 28px; color: white;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h2 style="margin: 0; font-size: 24px; color: var(--color-text-primary); font-weight: 700;">
                Métricas Globales de Producción
            </h2>
        </div>
        
        <div style="display: flex; gap: 8px;">
            <!-- Botón para redondear valores -->
            <button @click="redondearValores = !redondearValores" 
                    :title="redondearValores ? 'Mostrar valores exactos' : 'Redondear valores'"
                    :style="'background: ' + (redondearValores ? 'rgba(67, 233, 123, 0.15)' : 'rgba(255, 157, 88, 0.1)') + '; border: 1px solid ' + (redondearValores ? 'rgba(67, 233, 123, 0.4)' : 'rgba(255, 157, 88, 0.3)') + '; padding: 10px 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; color: var(--color-text-primary);'"
                    onmouseover="this.style.transform='scale(1.05)'"
                    onmouseout="this.style.transform='scale(1)'">
                <span class="material-symbols-rounded" :style="'font-size: 20px; color: ' + (redondearValores ? '#43e97b' : '#ff9d58')">calculate</span>
                <span x-text="redondearValores ? 'Redondeado' : 'Exacto'" style="font-size: 13px; font-weight: 600;"></span>
            </button>
            
            <!-- Botón para alternar vista -->
            <button @click="mostrarCuelloBotella = !mostrarCuelloBotella" 
                    :title="mostrarCuelloBotella ? 'Ocultar análisis de cuello de botella' : 'Mostrar análisis de cuello de botella'"
                    style="background: rgba(255, 157, 88, 0.1); border: 1px solid rgba(255, 157, 88, 0.3); padding: 10px 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; color: var(--color-text-primary);"
                    onmouseover="this.style.background='rgba(255, 157, 88, 0.2)'; this.style.borderColor='rgba(255, 157, 88, 0.5)'"
                    onmouseout="this.style.background='rgba(255, 157, 88, 0.1)'; this.style.borderColor='rgba(255, 157, 88, 0.3)'">
                <span class="material-symbols-rounded" style="font-size: 20px; color: #ff9d58;">analytics</span>
                <span x-text="mostrarCuelloBotella ? 'Vista Simple' : 'Cuello de Botella'" style="font-size: 13px; font-weight: 600;"></span>
            </button>
        </div>
    </div>

    <!-- Vista Simple (por defecto) -->
    <div x-show="!mostrarCuelloBotella" x-transition style="background: var(--color-bg-primary); border-radius: 8px; padding: 20px; border: 1px solid var(--color-border-hr); max-width: 600px; margin: 0 auto;">
        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            <tbody>
                <!-- Parámetros Editables -->
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 10px 0; color: var(--color-text-placeholder); font-size: 13px; font-weight: 500; width: 60%;">Total de operarios</td>
                    <td style="padding: 10px 0; text-align: right; width: 40%;">
                        <input type="number" x-model="parametros.total_operarios" @change="updateParametros()"
                               style="width: 70px; padding: 6px 10px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 6px; text-align: center; font-weight: 600; color: var(--color-text-primary); background: rgba(255, 157, 88, 0.1); font-size: 15px;">
                    </td>
                </tr>
                
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 10px 0; color: var(--color-text-placeholder); font-size: 13px; font-weight: 500;">Turnos de trabajo</td>
                    <td style="padding: 10px 0; text-align: right;">
                        <input type="number" x-model="parametros.turnos" @change="updateParametros()"
                               style="width: 70px; padding: 6px 10px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 6px; text-align: center; font-weight: 600; color: var(--color-text-primary); background: rgba(255, 157, 88, 0.1); font-size: 15px;">
                    </td>
                </tr>
                
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 10px 0; color: var(--color-text-placeholder); font-size: 13px; font-weight: 500;">Horas/turno</td>
                    <td style="padding: 10px 0; text-align: right;">
                        <input type="number" step="0.1" x-model="parametros.horas_por_turno" @change="updateParametros()"
                               style="width: 70px; padding: 6px 10px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 6px; text-align: center; font-weight: 600; color: var(--color-text-primary); background: rgba(255, 157, 88, 0.1); font-size: 15px;">
                    </td>
                </tr>
                
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 10px 0; color: var(--color-text-placeholder); font-size: 13px; font-weight: 500;">T. Disponible en Horas</td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 600; color: var(--color-text-primary); font-size: 15px;" x-text="parseFloat(metricas.tiempo_disponible_horas || 0).toFixed(2)"></td>
                </tr>
                
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 10px 0; color: var(--color-text-placeholder); font-size: 13px; font-weight: 500;">T. Disponible en Segundos</td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 600; color: var(--color-text-primary); font-size: 15px;" x-text="parseFloat(metricas.tiempo_disponible_segundos || 0).toFixed(0)"></td>
                </tr>
                
                <!-- SAM Total destacado -->
                <tr style="background: rgba(255, 157, 88, 0.1); border-bottom: 1px solid rgba(255, 157, 88, 0.2);">
                    <td style="padding: 12px 10px; color: #ff9d58; font-weight: 700; font-size: 14px; text-transform: uppercase;">SAM</td>
                    <td style="padding: 12px 10px; text-align: right; font-weight: 700; color: #ff9d58; font-size: 17px;" x-text="parseFloat(metricas.sam_total || 0).toFixed(1)"></td>
                </tr>
                
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 10px 0; color: var(--color-text-placeholder); font-size: 13px; font-weight: 500;">Meta teórica</td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 600; color: var(--color-text-primary); font-size: 15px;" x-text="metricas.meta_teorica || 'N/A'"></td>
                </tr>
                
                <!-- Meta Real destacada (90% de meta teórica) -->
                <tr>
                    <td style="padding: 10px 0; color: var(--color-text-placeholder); font-size: 13px; font-weight: 500;">Meta Real (90%)</td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 700; color: #ff9d58; font-size: 18px;" 
                        x-text="metricas.meta_real ? (redondearValores ? Math.round(parseFloat(metricas.meta_real)) : parseFloat(metricas.meta_real).toFixed(2)) : 'N/A'"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Vista Cuello de Botella -->
    <div x-show="mostrarCuelloBotella" x-transition style="background: var(--color-bg-primary); border-radius: 8px; padding: 20px; border: 1px solid var(--color-border-hr); max-width: 600px; margin: 0 auto;">
        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            <tbody>
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 10px 0; color: var(--color-text-placeholder); font-size: 13px; font-weight: 500; width: 60%;">Operario cuello de botella</td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 600; color: var(--color-text-primary); font-size: 15px; width: 40%;" x-text="metricas.operario_cuello_botella || 'N/A'"></td>
                </tr>
                
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 10px 0; color: var(--color-text-placeholder); font-size: 13px; font-weight: 500;">Tiempo cuello de botella (s)</td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 600; color: var(--color-text-primary); font-size: 15px;" x-text="metricas.tiempo_cuello_botella ? parseFloat(metricas.tiempo_cuello_botella).toFixed(1) : 'N/A'"></td>
                </tr>
                
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 10px 0; color: var(--color-text-placeholder); font-size: 13px; font-weight: 500;">SAM Real</td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 600; color: var(--color-text-primary); font-size: 15px;" x-text="metricas.sam_real ? parseFloat(metricas.sam_real).toFixed(1) : 'N/A'"></td>
                </tr>
                
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 10px 0; color: var(--color-text-placeholder); font-size: 13px; font-weight: 500;">Meta Real (cuello de botella)</td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 700; color: #ff9d58; font-size: 18px;" 
                        x-text="metricas.meta_real ? (redondearValores ? Math.round(parseFloat(metricas.meta_real)) : parseFloat(metricas.meta_real).toFixed(2)) : 'N/A'"></td>
                </tr>
                
                <!-- Meta Sugerida destacada -->
                <tr>
                    <td style="padding: 10px 0; color: var(--color-text-placeholder); font-size: 13px; font-weight: 500;">Meta sugerida (85%)</td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 700; color: #ff9d58; font-size: 18px;" x-text="metricas.meta_sugerida_85 || 'N/A'"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 16px; padding: 12px; background: rgba(255, 157, 88, 0.05); border-radius: 6px; border-left: 3px solid #ff9d58;">
        <p style="margin: 0; color: var(--color-text-placeholder); font-size: 12px; line-height: 1.6;">
            <strong style="color: #ff9d58;">Nota:</strong> Los campos editables actualizan automáticamente todas las métricas calculadas. 
            <span x-show="redondearValores" style="color: #43e97b; font-weight: 600;">• Valores redondeados activos</span>
        </p>
    </div>
</div>
