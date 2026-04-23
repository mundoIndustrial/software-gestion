(function() {
    'use strict';

    function abrirPanelDiagnostico() {
        if (!window.ErrorLoggerService) {
            alert('ErrorLoggerService no disponible');
            return;
        }

        const logs = window.ErrorLoggerService.obtenerLogs();
        const resumen = window.ErrorLoggerService.obtenerResumen();

        // Crear modal HTML
        const html = `
            <div id="panel-diagnostico" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            ">
                <div style="
                    background: white;
                    border-radius: 8px;
                    width: 90%;
                    max-width: 900px;
                    max-height: 80vh;
                    display: flex;
                    flex-direction: column;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                ">
                    <!-- Header -->
                    <div style="
                        padding: 20px;
                        border-bottom: 1px solid #e5e7eb;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <h2 style="margin: 0; color: #1f2937;">🔍 Panel de Diagnóstico</h2>
                        <button onclick="document.getElementById('panel-diagnostico').remove()" style="
                            background: none;
                            border: none;
                            font-size: 24px;
                            cursor: pointer;
                            color: #6b7280;
                        ">✕</button>
                    </div>

                    <!-- Tabs -->
                    <div style="
                        padding: 0 20px;
                        border-bottom: 1px solid #e5e7eb;
                        display: flex;
                        gap: 10px;
                    ">
                        <button onclick="mostrarTab('resumen')" style="
                            padding: 12px 16px;
                            border: none;
                            background: none;
                            cursor: pointer;
                            border-bottom: 2px solid #0066cc;
                            color: #0066cc;
                            font-weight: 500;
                        ">Resumen</button>
                        <button onclick="mostrarTab('errores')" style="
                            padding: 12px 16px;
                            border: none;
                            background: none;
                            cursor: pointer;
                            color: #6b7280;
                            font-weight: 500;
                        ">Errores (${logs.filter(l => l.tipo.startsWith('ERROR')).length})</button>
                        <button onclick="mostrarTab('completo')" style="
                            padding: 12px 16px;
                            border: none;
                            background: none;
                            cursor: pointer;
                            color: #6b7280;
                            font-weight: 500;
                        ">Todos (${logs.length})</button>
                    </div>

                    <!-- Content -->
                    <div style="
                        flex: 1;
                        overflow-y: auto;
                        padding: 20px;
                    " id="panel-content">
                        ${generarContenidoResumen(resumen, logs)}
                    </div>

                    <!-- Footer -->
                    <div style="
                        padding: 15px 20px;
                        border-top: 1px solid #e5e7eb;
                        display: flex;
                        gap: 10px;
                        justify-content: flex-end;
                        background: #f9fafb;
                    ">
                        <button onclick="exportarResumen()" style="
                            padding: 8px 16px;
                            border: 1px solid #d1d5db;
                            background: white;
                            border-radius: 4px;
                            cursor: pointer;
                            font-size: 14px;
                        ">📋 Copiar Resumen</button>
                        <button onclick="exportarJSON()" style="
                            padding: 8px 16px;
                            border: 1px solid #d1d5db;
                            background: white;
                            border-radius: 4px;
                            cursor: pointer;
                            font-size: 14px;
                        ">📥 Descargar JSON</button>
                        <button onclick="limpiarLogs()" style="
                            padding: 8px 16px;
                            border: 1px solid #ef4444;
                            background: white;
                            color: #ef4444;
                            border-radius: 4px;
                            cursor: pointer;
                            font-size: 14px;
                        ">🗑 Limpiar</button>
                    </div>
                </div>
            </div>

            <style>
                #panel-diagnostico code {
                    background: #f3f4f6;
                    padding: 2px 6px;
                    border-radius: 3px;
                    font-family: 'Courier New', monospace;
                    font-size: 12px;
                }

                #panel-diagnostico pre {
                    background: #1f2937;
                    color: #10b981;
                    padding: 12px;
                    border-radius: 4px;
                    overflow-x: auto;
                    font-size: 12px;
                    line-height: 1.5;
                }
            </style>
        `;

        // Crear contenedor
        const container = document.createElement('div');
        container.innerHTML = html;
        document.body.appendChild(container.firstElementChild);

        // Inyectar funciones globales
        window.mostrarTab = mostrarTab;
        window.exportarResumen = exportarResumen;
        window.exportarJSON = exportarJSON;
        window.limpiarLogs = limpiarLogs;
    }

    function generarContenidoResumen(resumen, logs) {
        const erroresRecientes = logs.filter(l => l.tipo.startsWith('ERROR')).slice(0, 10);
        const logsRecientes = logs.slice(0, 10);

        return `
            <div id="tab-resumen" style="display: block;">
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
                    <div style="background: #f0f7ff; padding: 16px; border-radius: 8px; border-left: 4px solid #0066cc;">
                        <div style="color: #0066cc; font-size: 24px; font-weight: bold;">${resumen.total}</div>
                        <div style="color: #6b7280; font-size: 12px;">Total de eventos</div>
                    </div>
                    <div style="background: #fef3c7; padding: 16px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        <div style="color: #f59e0b; font-size: 24px; font-weight: bold;">${resumen.porTipo['ERROR_IMAGEN'] || 0}</div>
                        <div style="color: #6b7280; font-size: 12px;">Errores de imagen</div>
                    </div>
                    <div style="background: #fee2e2; padding: 16px; border-radius: 8px; border-left: 4px solid #ef4444;">
                        <div style="color: #ef4444; font-size: 24px; font-weight: bold;">${resumen.porTipo['ERROR_RED'] || 0}</div>
                        <div style="color: #6b7280; font-size: 12px;">Errores de red</div>
                    </div>
                    <div style="background: #f0fdf4; padding: 16px; border-radius: 8px; border-left: 4px solid #10b981;">
                        <div style="color: #10b981; font-size: 24px; font-weight: bold;">${resumen.porTipo['EXITO'] || 0}</div>
                        <div style="color: #6b7280; font-size: 12px;">Operaciones exitosas</div>
                    </div>
                </div>

                <div style="background: #f3f4f6; padding: 12px; border-radius: 8px; margin-bottom: 24px;">
                    <strong style="color: #374151;">Últimas 24 horas:</strong> ${resumen.ultimasHoras24} eventos |
                    <strong style="color: #374151;">Últimos 30 min:</strong> ${resumen.ultimos30Min} eventos
                </div>

                <h3 style="color: #1f2937; margin-top: 0;">Últimos Errores:</h3>
                ${erroresRecientes.length > 0 ?
                    `<div style="border-left: 4px solid #ef4444; padding-left: 12px;">
                        ${erroresRecientes.map(log => `
                            <div style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb;">
                                <div style="color: #ef4444; font-weight: bold; font-size: 12px;">${log.tipo}</div>
                                <div style="color: #374151; font-size: 12px; margin-top: 4px;">
                                    ${log.archivo ? `Archivo: <code>${log.archivo}</code>` : ''}
                                    ${log.error ? `Error: <code>${log.error}</code>` : ''}
                                    ${log.mensaje ? `Msg: <code>${log.mensaje}</code>` : ''}
                                </div>
                                <div style="color: #9ca3af; font-size: 11px; margin-top: 4px;">${new Date(log.timestamp).toLocaleString()}</div>
                            </div>
                        `).join('')}
                    </div>`
                    : '<p style="color: #9ca3af;">Sin errores registrados</p>'
                }

                <h3 style="color: #1f2937; margin-top: 24px;">Últimos Eventos:</h3>
                <div style="border-left: 4px solid #0066cc; padding-left: 12px;">
                    ${logsRecientes.map(log => `
                        <div style="margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; font-size: 12px;">
                            <span style="color: ${log.tipo.startsWith('ERROR') ? '#ef4444' : log.tipo === 'EXITO' ? '#10b981' : '#6b7280'}; font-weight: bold;">●</span>
                            <code>${log.tipo}</code> - <span style="color: #6b7280;">${new Date(log.timestamp).toLocaleTimeString()}</span>
                        </div>
                    `).join('')}
                </div>
            </div>

            <div id="tab-errores" style="display: none;">
                ${generarTablaErrores(logs)}
            </div>

            <div id="tab-completo" style="display: none;">
                ${generarTablaCompleta(logs)}
            </div>
        `;
    }

    function generarTablaErrores(logs) {
        const errores = logs.filter(l => l.tipo.startsWith('ERROR'));
        if (errores.length === 0) {
            return '<p style="color: #9ca3af;">Sin errores registrados</p>';
        }

        return `
            <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                <thead>
                    <tr style="background: #f3f4f6; border-bottom: 1px solid #d1d5db;">
                        <th style="padding: 8px; text-align: left; color: #374151;">Tipo</th>
                        <th style="padding: 8px; text-align: left; color: #374151;">Descripción</th>
                        <th style="padding: 8px; text-align: left; color: #374151;">Hora</th>
                    </tr>
                </thead>
                <tbody>
                    ${errores.map(log => `
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 8px; color: #ef4444; font-weight: bold;">${log.tipo}</td>
                            <td style="padding: 8px; color: #374151;">
                                ${log.archivo ? `Archivo: ${log.archivo}<br>` : ''}
                                ${log.error ? `Error: ${log.error}` : log.mensaje || ''}
                            </td>
                            <td style="padding: 8px; color: #9ca3af; white-space: nowrap;">${new Date(log.timestamp).toLocaleString()}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    function generarTablaCompleta(logs) {
        return `
            <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                <thead>
                    <tr style="background: #f3f4f6; border-bottom: 1px solid #d1d5db;">
                        <th style="padding: 8px; text-align: left; color: #374151;">Tipo</th>
                        <th style="padding: 8px; text-align: left; color: #374151;">Detalles</th>
                        <th style="padding: 8px; text-align: left; color: #374151;">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    ${logs.map(log => `
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 8px; color: ${log.tipo.startsWith('ERROR') ? '#ef4444' : log.tipo === 'EXITO' ? '#10b981' : '#6b7280'}; font-weight: bold;">${log.tipo}</td>
                            <td style="padding: 8px; color: #374151; max-width: 500px; overflow: hidden; text-overflow: ellipsis;">
                                <pre style="margin: 0; background: none; color: #374151; font-size: 11px; white-space: pre-wrap; word-wrap: break-word;">
${JSON.stringify(log, null, 2).substring(0, 200)}
                                </pre>
                            </td>
                            <td style="padding: 8px; color: #9ca3af; font-size: 10px;">${new Date(log.timestamp).toLocaleString()}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    function mostrarTab(tabName) {
        // Ocultar todos
        document.querySelectorAll('[id^="tab-"]').forEach(el => el.style.display = 'none');
        // Mostrar el seleccionado
        const tab = document.getElementById(`tab-${tabName}`);
        if (tab) tab.style.display = 'block';

        // Actualizar botones
        document.querySelectorAll('#panel-diagnostico button').forEach(btn => {
            btn.style.borderBottomColor = btn.textContent.includes(tabName.charAt(0).toUpperCase()) ? '#0066cc' : 'transparent';
            btn.style.color = btn.textContent.includes(tabName.charAt(0).toUpperCase()) ? '#0066cc' : '#6b7280';
        });
    }

    function exportarResumen() {
        const resumen = window.ErrorLoggerService.exportarResumen();
        navigator.clipboard.writeText(resumen).then(() => {
            alert('✅ Resumen copiado al portapapeles');
        });
    }

    function exportarJSON() {
        const json = window.ErrorLoggerService.exportarJSON();
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `error-logs-${new Date().toISOString().slice(0, 10)}.json`;
        a.click();
        URL.revokeObjectURL(url);
    }

    function limpiarLogs() {
        if (confirm('¿Estás seguro de que quieres limpiar todos los logs?')) {
            window.ErrorLoggerService.limpiar();
            alert('✅ Logs limpios');
            document.getElementById('panel-diagnostico')?.remove();
        }
    }

    // Exportar función pública
    globalThis.abrirPanelDiagnostico = abrirPanelDiagnostico;
})();
