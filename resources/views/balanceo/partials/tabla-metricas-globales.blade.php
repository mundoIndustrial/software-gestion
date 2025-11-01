<div style="background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%); padding: 32px; border-radius: 20px; border: 1px solid rgba(255, 157, 88, 0.2); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);">
    <!-- Header -->
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 28px;">
        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(255, 157, 88, 0.3);">
            <svg style="width: 28px; height: 28px; color: white;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h2 style="margin: 0; font-size: 24px; color: white; font-weight: 700;">
            Métricas Globales de Producción
        </h2>
    </div>

    <div style="background: rgba(255, 255, 255, 0.05); border-radius: 8px; padding: 20px; border: 1px solid rgba(255, 157, 88, 0.1);">
        <table style="width: 100%; border-collapse: collapse;">
            <tbody>
                <!-- Parámetros Editables -->
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 12px 0; color: #94a3b8; font-size: 13px; font-weight: 500;">Total de operarios</td>
                    <td style="padding: 12px 0; text-align: right; width: 100px;">
                        <input type="number" x-model="parametros.total_operarios" @change="updateParametros()"
                               style="width: 80px; padding: 6px 10px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 4px; text-align: right; font-weight: 600; color: white; background: rgba(255, 157, 88, 0.1); font-size: 14px;">
                    </td>
                    <td style="padding: 12px 0 12px 40px; color: #94a3b8; font-size: 13px; font-weight: 500;">Meta Real</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: 600; color: #ff9d58; font-size: 16px; width: 100px;" x-text="metricas.meta_real || 'N/A'"></td>
                </tr>
                
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 12px 0; color: #94a3b8; font-size: 13px; font-weight: 500;">Turnos de trabajo</td>
                    <td style="padding: 12px 0; text-align: right;">
                        <input type="number" x-model="parametros.turnos" @change="updateParametros()"
                               style="width: 80px; padding: 6px 10px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 4px; text-align: right; font-weight: 600; color: white; background: rgba(255, 157, 88, 0.1); font-size: 14px;">
                    </td>
                    <td style="padding: 12px 0 12px 40px;"></td>
                    <td style="padding: 12px 0;"></td>
                </tr>
                
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 12px 0; color: #94a3b8; font-size: 13px; font-weight: 500;">Horas/turno</td>
                    <td style="padding: 12px 0; text-align: right;">
                        <input type="number" step="0.1" x-model="parametros.horas_por_turno" @change="updateParametros()"
                               style="width: 80px; padding: 6px 10px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 4px; text-align: right; font-weight: 600; color: white; background: rgba(255, 157, 88, 0.1); font-size: 14px;">
                    </td>
                    <td style="padding: 12px 0 12px 40px; color: #94a3b8; font-size: 13px; font-weight: 500;">Operario cuello de botella</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: 600; color: white; font-size: 14px;" x-text="metricas.operario_cuello_botella || 'N/A'"></td>
                </tr>
                
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 12px 0; color: #94a3b8; font-size: 13px; font-weight: 500;">T. Disponible en Horas</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: 600; color: white; font-size: 14px;" x-text="parseFloat(metricas.tiempo_disponible_horas || 0).toFixed(2)"></td>
                    <td style="padding: 12px 0 12px 40px; color: #94a3b8; font-size: 13px; font-weight: 500;">Tiempo cuello de botella (s)</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: 600; color: white; font-size: 14px;" x-text="metricas.tiempo_cuello_botella ? parseFloat(metricas.tiempo_cuello_botella).toFixed(1) : 'N/A'"></td>
                </tr>
                
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 12px 0; color: #94a3b8; font-size: 13px; font-weight: 500;">T. Disponible en Segundos</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: 600; color: white; font-size: 14px;" x-text="parseFloat(metricas.tiempo_disponible_segundos || 0).toFixed(0)"></td>
                    <td style="padding: 12px 0 12px 40px; color: #94a3b8; font-size: 13px; font-weight: 500;">SAM Real</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: 600; color: white; font-size: 14px;" x-text="metricas.sam_real ? parseFloat(metricas.sam_real).toFixed(1) : 'N/A'"></td>
                </tr>
                
                <!-- SAM Total destacado -->
                <tr style="background: rgba(255, 157, 88, 0.1); border-bottom: 1px solid rgba(255, 157, 88, 0.2);">
                    <td style="padding: 14px 10px; color: #ff9d58; font-weight: 600; font-size: 14px;">SAM</td>
                    <td style="padding: 14px 10px; text-align: right; font-weight: 700; color: #ff9d58; font-size: 18px;" x-text="parseFloat(metricas.sam_total).toFixed(1)"></td>
                    <td style="padding: 14px 10px; padding-left: 40px; color: #ff9d58; font-weight: 600; font-size: 14px;">Meta Real</td>
                    <td style="padding: 14px 10px; text-align: right; font-weight: 700; color: #ff9d58; font-size: 18px;" x-text="metricas.meta_real || 'N/A'"></td>
                </tr>
                
                <tr style="border-bottom: 1px solid rgba(255, 157, 88, 0.15);">
                    <td style="padding: 12px 0; color: #94a3b8; font-size: 13px; font-weight: 500;">Meta teórica</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: 600; color: white; font-size: 14px;" x-text="metricas.meta_teorica || 'N/A'"></td>
                    <td style="padding: 12px 0 12px 40px;"></td>
                    <td style="padding: 12px 0;"></td>
                </tr>
                
                <!-- Meta Sugerida destacada -->
                <tr>
                    <td style="padding: 12px 0; color: #94a3b8; font-size: 13px; font-weight: 500;">Meta sugerida (85%)</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: 700; color: #ff9d58; font-size: 16px;" x-text="metricas.meta_sugerida_85 || 'N/A'"></td>
                    <td style="padding: 12px 0 12px 40px;"></td>
                    <td style="padding: 12px 0;"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 16px; padding: 12px; background: rgba(255, 157, 88, 0.05); border-radius: 6px; border-left: 3px solid #ff9d58;">
        <p style="margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.6;">
            <strong style="color: #ff9d58;">Nota:</strong> Los campos editables actualizan automáticamente todas las métricas calculadas.
        </p>
    </div>
</div>
