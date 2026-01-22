<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analizador de Artículos - PHP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .content {
            padding: 30px;
        }

        .upload-section {
            margin-bottom: 30px;
        }

        .upload-section h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
        }

        .paste-box {
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            resize: vertical;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #764ba2;
        }

        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .tabs {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 20px;
        }

        .tab {
            padding: 12px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 14px;
            font-weight: 600;
            color: #999;
            transition: all 0.3s;
        }

        .tab.active {
            color: #667eea;
            border-bottom: 2px solid #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead {
            background: #f5f5f5;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        .duplicate-group {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .duplicate-group h3 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #856404;
        }

        .duplicate-criteria {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .criteria-item {
            background: white;
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
        }

        .criteria-label {
            font-weight: 600;
            color: #667eea;
            display: block;
            margin-bottom: 5px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f5f7fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .stat-label {
            font-size: 13px;
            color: #999;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }

        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #f5c6cb;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Analizador de Artículos (PHP)</h1>
            <p>Detecta duplicaciones y agrupa por atributos</p>
        </div>

        <div class="content">
            <div class="upload-section">
                <h2>📤 Ingresa tus datos</h2>
                <div class="paste-box">
                    <strong>Pega aquí en formato CSV o JSON:</strong>
                    <textarea id="dataInput" placeholder="nombre,codigo,color,marca,material,talla&#10;Remera,REM001,Rojo,Nike,Algodón,M&#10;..."></textarea>
                    <div class="button-group">
                        <button class="btn-primary" id="btnProcesar" onclick="procesarDatos()"> Procesar Datos</button>
                        <button class="btn-secondary" onclick="limpiarTodo()">🗑️ Limpiar</button>
                    </div>
                </div>
            </div>

            <div id="resultsSection" class="hidden">
                <div id="statsContainer" class="stats"></div>

                <div class="tabs">
                    <button class="tab active" onclick="cambiarTab('tabla')"> Tabla de Datos</button>
                    <button class="tab" onclick="cambiarTab('duplicados')">⚠️ Análisis de Duplicaciones</button>
                </div>

                <div id="tabla" class="tab-content active">
                    <div id="tableContainer" class="table-wrapper"></div>
                </div>

                <div id="duplicados" class="tab-content">
                    <div id="duplicatesContainer"></div>
                </div>
            </div>

            <div id="messageContainer"></div>
        </div>
    </div>

    <script>
        const API_URL = 'api-analizador.php';
        let datosActuales = [];

        async function procesarDatos() {
            const contenido = document.getElementById('dataInput').value.trim();
            if (!contenido) {
                mostrarMensaje('Por favor ingresa datos', 'error');
                return;
            }

            const btn = document.getElementById('btnProcesar');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span> Procesando...';

            try {
                const respuesta = await fetch(API_URL + '?accion=procesar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'contenido=' + encodeURIComponent(contenido)
                });

                const resultado = await respuesta.json();

                if (!respuesta.ok || resultado.error) {
                    throw new Error(resultado.error || 'Error al procesar');
                }

                datosActuales = resultado;
                await cargarDatos();
                mostrarMensaje(`✓ Se cargaron ${resultado.total} artículos correctamente`, 'success');
                document.getElementById('resultsSection').classList.remove('hidden');

            } catch (error) {
                mostrarMensaje('Error: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = ' Procesar Datos';
            }
        }

        async function cargarDatos() {
            try {
                const respuesta = await fetch(API_URL + '?accion=datos');
                const datos = await respuesta.json();

                mostrarTabla(datos);
                await cargarDuplicaciones();
                mostrarEstadisticas(datos);

            } catch (error) {
                mostrarMensaje('Error cargando datos: ' + error.message, 'error');
            }
        }

        function mostrarTabla(datos) {
            if (!datos.encabezados || !datos.datos) return;

            let html = '<table><thead><tr>';
            datos.encabezados.forEach(h => {
                html += `<th>${escapeHtml(h)}</th>`;
            });
            html += '</tr></thead><tbody>';

            datos.datos.forEach(fila => {
                html += '<tr>';
                datos.encabezados.forEach(h => {
                    html += `<td>${escapeHtml(fila[h] || '')}</td>`;
                });
                html += '</tr>';
            });

            html += '</tbody></table>';
            document.getElementById('tableContainer').innerHTML = html;
        }

        function mostrarEstadisticas(datos) {
            if (!datos.estadisticas) return;

            const stats = datos.estadisticas;
            const html = `
                <div class="stat-card">
                    <div class="stat-label">Total de Artículos</div>
                    <div class="stat-value">${stats.total_articulos}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Campos Detectados</div>
                    <div class="stat-value">${stats.total_campos}</div>
                </div>
            `;
            document.getElementById('statsContainer').innerHTML = html;
        }

        async function cargarDuplicaciones() {
            try {
                const respuesta = await fetch(API_URL + '?accion=duplicaciones');
                const resultado = await respuesta.json();

                if (resultado.error) {
                    throw new Error(resultado.error);
                }

                mostrarDuplicaciones(resultado);

            } catch (error) {
                mostrarMensaje('Error analizando duplicaciones: ' + error.message, 'error');
            }
        }

        function mostrarDuplicaciones(resultado) {
            let html = '';

            if (resultado.total_duplicaciones === 0) {
                html = '<div class="message success">✓ No se encontraron duplicaciones</div>';
            } else {
                html = `<div class="message">⚠️ Se encontraron ${resultado.total_duplicaciones} grupos de artículos duplicados</div>`;

                resultado.grupos_duplicados.forEach((grupo, idx) => {
                    html += `
                        <div class="duplicate-group">
                            <h3>Duplicación #${idx + 1} - ${grupo.cantidad} artículos repetidos</h3>
                            <div class="duplicate-criteria">
                                ${Object.entries(grupo.criterios).map(([campo, valor]) => `
                                    <div class="criteria-item">
                                        <span class="criteria-label">${escapeHtml(campo)}</span>
                                        <span>${escapeHtml(valor)}</span>
                                    </div>
                                `).join('')}
                            </div>
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr>
                                            ${Object.keys(grupo.articulos[0] || {}).map(h => `<th>${escapeHtml(h)}</th>`).join('')}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${grupo.articulos.map(item => `
                                            <tr>
                                                ${Object.values(item).map(v => `<td>${escapeHtml(v)}</td>`).join('')}
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                });
            }

            document.getElementById('duplicatesContainer').innerHTML = html;
        }

        function cambiarTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));

            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        function limpiarTodo() {
            document.getElementById('dataInput').value = '';
            document.getElementById('resultsSection').classList.add('hidden');
            document.getElementById('messageContainer').innerHTML = '';
        }

        function mostrarMensaje(texto, tipo) {
            const msg = document.createElement('div');
            msg.className = `message ${tipo}`;
            msg.textContent = texto;
            document.getElementById('messageContainer').innerHTML = '';
            document.getElementById('messageContainer').appendChild(msg);

            if (tipo === 'success') {
                setTimeout(() => msg.remove(), 5000);
            }
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
    </script>
</body>
</html>
