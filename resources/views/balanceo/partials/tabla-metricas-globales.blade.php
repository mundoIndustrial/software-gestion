<div class="metricas-globales-container" x-data="{ mostrarCuelloBotella: false, redondearValores: false }">
    <!-- Header -->
    <div class="metricas-header">
        <div class="metricas-title-section">
            <div class="metricas-icon-box">
                <svg style="width: 28px; height: 28px; color: white;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h2 class="metricas-title-text">
                Métricas Globales de Producción
            </h2>
        </div>
        
        <div class="metricas-buttons">
            <!-- Botón para redondear valores -->
            <button @click="redondearValores = !redondearValores" 
                    class="metricas-toggle-btn"
                    :title="redondearValores ? 'Mostrar valores exactos' : 'Redondear valores'"
                    :style="'background: ' + (redondearValores ? 'rgba(67, 233, 123, 0.15)' : 'rgba(255, 157, 88, 0.1)') + '; border: 1px solid ' + (redondearValores ? 'rgba(67, 233, 123, 0.4)' : 'rgba(255, 157, 88, 0.3)')'"
                    onmouseover="this.style.transform='scale(1.05)'"
                    onmouseout="this.style.transform='scale(1)'">
                <span class="material-symbols-rounded" :style="'font-size: 20px; color: ' + (redondearValores ? '#43e97b' : '#ff9d58')">calculate</span>
                <span x-text="redondearValores ? 'Redondeado' : 'Exacto'" style="font-size: 13px; font-weight: 600;"></span>
            </button>
            
            <!-- Botón para alternar vista -->
            <button @click="mostrarCuelloBotella = !mostrarCuelloBotella" 
                    class="metricas-toggle-btn"
                    :title="mostrarCuelloBotella ? 'Ocultar análisis de cuello de botella' : 'Mostrar análisis de cuello de botella'"
                    onmouseover="this.style.background='rgba(255, 157, 88, 0.2)'; this.style.borderColor='rgba(255, 157, 88, 0.5)'"
                    onmouseout="this.style.background='rgba(255, 157, 88, 0.1)'; this.style.borderColor='rgba(255, 157, 88, 0.3)'">
                <span class="material-symbols-rounded" style="font-size: 20px; color: #ff9d58;">analytics</span>
                <span x-text="mostrarCuelloBotella ? 'Vista Simple' : 'Cuello de Botella'" style="font-size: 13px; font-weight: 600;"></span>
            </button>
        </div>
    </div>

    <!-- Vista Simple (por defecto) -->
    <div x-show="!mostrarCuelloBotella" x-transition class="metricas-table-wrapper">
        <table class="metricas-table">
            <tbody>
                <!-- Parámetros Editables -->
                <tr>
                    <td class="metricas-table-label">Total de operarios</td>
                    <td class="metricas-table-value">
                        <input type="number" x-model="parametros.total_operarios" @change="updateParametros()" class="metricas-input">
                    </td>
                </tr>
                
                <tr>
                    <td class="metricas-table-label">Turnos de trabajo</td>
                    <td class="metricas-table-value">
                        <input type="number" x-model="parametros.turnos" @change="updateParametros()" class="metricas-input">
                    </td>
                </tr>
                
                <tr>
                    <td class="metricas-table-label">Horas/turno</td>
                    <td class="metricas-table-value">
                        <input type="number" step="0.1" x-model="parametros.horas_por_turno" @change="updateParametros()" class="metricas-input">
                    </td>
                </tr>
                
                <tr>
                    <td class="metricas-table-label">% Eficiencia</td>
                    <td class="metricas-table-value">
                        <input type="number" step="0.01" min="0" max="100" x-model="parametros.porcentaje_eficiencia" @change="updateParametros()" class="metricas-input">
                    </td>
                </tr>
                
                <tr>
                    <td class="metricas-table-label">T. Disponible en Horas</td>
                    <td class="metricas-table-value" x-text="parseFloat(metricas.tiempo_disponible_horas || 0).toFixed(2)"></td>
                </tr>
                
                <tr>
                    <td class="metricas-table-label">T. Disponible en Segundos</td>
                    <td class="metricas-table-value" x-text="parseFloat(metricas.tiempo_disponible_segundos || 0).toFixed(0)"></td>
                </tr>
                
                <!-- SAM Total destacado -->
                <tr class="metricas-highlight-row">
                    <td class="metricas-highlight-label">SAM</td>
                    <td class="metricas-highlight-value" x-text="parseFloat(metricas.sam_total || 0).toFixed(1)"></td>
                </tr>
                
                <tr>
                    <td class="metricas-table-label">Meta teórica</td>
                    <td class="metricas-table-value" x-text="metricas.meta_teorica || 'N/A'"></td>
                </tr>
                
                <!-- Meta Real destacada (con % dinámico) -->
                <tr>
                    <td class="metricas-table-label">
                        <span x-text="'Meta Real (' + (parametros.porcentaje_eficiencia || 90) + '%)'"></span>
                    </td>
                    <td class="metricas-main-value" style="text-align: right; padding: 10px 0;" 
                        x-text="metricas.meta_real ? (redondearValores ? Math.round(parseFloat(metricas.meta_real)) : parseFloat(metricas.meta_real).toFixed(2)) : 'N/A'"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Vista Cuello de Botella -->
    <div x-show="mostrarCuelloBotella" x-transition class="metricas-table-wrapper">
        <table class="metricas-table">
            <tbody>
                <tr>
                    <td class="metricas-table-label">Operario cuello de botella</td>
                    <td class="metricas-table-value" x-text="metricas.operario_cuello_botella || 'N/A'"></td>
                </tr>
                
                <tr>
                    <td class="metricas-table-label">Tiempo cuello de botella (s)</td>
                    <td class="metricas-table-value" x-text="metricas.tiempo_cuello_botella ? parseFloat(metricas.tiempo_cuello_botella).toFixed(1) : 'N/A'"></td>
                </tr>
                
                <tr>
                    <td class="metricas-table-label">SAM Real</td>
                    <td class="metricas-table-value" x-text="metricas.sam_real ? parseFloat(metricas.sam_real).toFixed(1) : 'N/A'"></td>
                </tr>
                
                <tr>
                    <td class="metricas-table-label">Meta Real (cuello de botella)</td>
                    <td class="metricas-main-value" style="text-align: right; padding: 10px 0;" 
                        x-text="metricas.meta_real ? (redondearValores ? Math.round(parseFloat(metricas.meta_real)) : parseFloat(metricas.meta_real).toFixed(2)) : 'N/A'"></td>
                </tr>
                
                <!-- Meta Sugerida destacada -->
                <tr>
                    <td class="metricas-table-label">Meta sugerida (85%)</td>
                    <td class="metricas-main-value" style="text-align: right; padding: 10px 0;" x-text="metricas.meta_sugerida_85 || 'N/A'"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="metricas-note">
        <p style="margin: 0; color: var(--color-text-placeholder); font-size: 12px; line-height: 1.6;">
            <strong style="color: #ff9d58;">Nota:</strong> Los campos editables actualizan automáticamente todas las métricas calculadas. 
            <span x-show="redondearValores" style="color: #43e97b; font-weight: 600;">• Valores redondeados activos</span>
        </p>
    </div>
</div>
