<div style="background: rgba(255, 255, 255, 0.03); padding: 24px; border-radius: 12px; border: 1px solid rgba(255, 157, 88, 0.15);">
    <h2 style="margin: 0 0 20px 0; font-size: 18px; color: white; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
        Métricas Globales de Producción
    </h2>

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
