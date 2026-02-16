/**
 * generar-script-sql.js
 * Funcionalidad para generar y descargar script SQL completo del pedido
 */

/**
 * Generar script SQL del pedido
 */
async function generarScriptSQL(pedidoId) {
    try {
        // Mostrar loading
        Swal.fire({
            title: 'Generando Script SQL',
            html: 'Por favor espera mientras se genera el script...',
            icon: 'info',
            allowOutsideClick: false,
            didOpen: async () => {
                Swal.showLoading();
                
                try {
                    const response = await fetch(`/api/pedidos/${pedidoId}/generar-script-sql`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        // Mostrar modal con el script SQL
                        mostrarModalScriptSQL(data.sql, data.pedido_numero, data.cliente);
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Error al generar el script SQL',
                            icon: 'error'
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al generar el script SQL: ' + error.message,
                        icon: 'error'
                    });
                }
            }
        });

    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error',
            text: 'Error inesperado: ' + error.message,
            icon: 'error'
        });
    }
}

/**
 * Mostrar modal con el script SQL generado
 */
function mostrarModalScriptSQL(sql, numeroPedido, cliente) {
    const html = `
        <div style="text-align: left; max-height: 70vh; overflow-y: auto;">
            <div style="margin-bottom: 1.5rem;">
                <h3 style="margin: 0 0 0.5rem 0; color: #1f2937;">Pedido: <strong>#${numeroPedido}</strong></h3>
                <p style="margin: 0; color: #6b7280; font-size: 0.95rem;">Cliente: <strong>${cliente}</strong></p>
            </div>
            
            <div style="background: #f3f4f6; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                <p style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.85rem;">
                    <i class="fas fa-info-circle"></i> Este script SQL contiene todos los INSERT necesarios para recrear el pedido completo con todas sus tablas y relaciones.
                </p>
            </div>
            
            <div style="background: #1f2937; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; position: relative;">
                <textarea id="scriptSQLTextarea" readonly style="
                    width: 100%;
                    height: 400px;
                    background: #1f2937;
                    color: #10b981;
                    border: 1px solid #374151;
                    border-radius: 6px;
                    padding: 1rem;
                    font-family: 'Courier New', monospace;
                    font-size: 0.85rem;
                    line-height: 1.5;
                    resize: vertical;
                    box-sizing: border-box;
                ">${escapeHtml(sql)}</textarea>
                
                <button id="btnCopiarScript" style="
                    position: absolute;
                    top: 1rem;
                    right: 1rem;
                    background: #10b981;
                    color: white;
                    border: none;
                    padding: 0.5rem 1rem;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 0.85rem;
                    font-weight: 600;
                    transition: all 0.2s ease;
                " onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                    <i class="fas fa-copy"></i> Copiar
                </button>
            </div>
            
            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <p style="margin: 0; color: #92400e; font-size: 0.9rem;">
                    <strong>⚠️ Importante:</strong> Antes de ejecutar este script en otra base de datos, asegúrate de que:
                </p>
                <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem; color: #92400e; font-size: 0.9rem;">
                    <li>Los IDs no entren en conflicto con datos existentes</li>
                    <li>Las relaciones (FK) existan en la base de datos destino</li>
                    <li>Tengas permisos de INSERT en todas las tablas</li>
                </ul>
            </div>
        </div>
    `;

    Swal.fire({
        title: '📋 Script SQL Generado',
        html: html,
        width: '90%',
        maxWidth: '1000px',
        confirmButtonText: '✓ Descargar Script',
        confirmButtonColor: '#10b981',
        showCancelButton: true,
        cancelButtonText: 'Cerrar',
        didOpen: () => {
            // Agregar evento al botón de copiar
            const btnCopiar = document.getElementById('btnCopiarScript');
            if (btnCopiar) {
                btnCopiar.addEventListener('click', () => {
                    const textarea = document.getElementById('scriptSQLTextarea');
                    textarea.select();
                    document.execCommand('copy');
                    
                    // Mostrar confirmación
                    const textoOriginal = btnCopiar.innerHTML;
                    btnCopiar.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
                    btnCopiar.style.background = '#059669';
                    
                    setTimeout(() => {
                        btnCopiar.innerHTML = textoOriginal;
                        btnCopiar.style.background = '#10b981';
                    }, 2000);
                });
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Descargar como archivo
            descargarScriptSQL(sql, numeroPedido);
        }
    });
}

/**
 * Descargar script SQL como archivo
 */
function descargarScriptSQL(sql, numeroPedido) {
    const element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(sql));
    element.setAttribute('download', `pedido_${numeroPedido}_script.sql`);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
    
    // Mostrar confirmación
    Swal.fire({
        title: '✓ Descargado',
        text: `Script descargado como: pedido_${numeroPedido}_script.sql`,
        icon: 'success',
        timer: 2000
    });
}

/**
 * Escapar HTML para evitar inyecciones
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
