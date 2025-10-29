<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Echo - Tiempo Real</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #1a1d29;
            color: white;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
        }
        .success { background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; }
        .error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; }
        .info { background: rgba(59, 130, 246, 0.2); border: 1px solid #3b82f6; }
        #log {
            background: #000;
            padding: 15px;
            border-radius: 8px;
            height: 400px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
        }
        .log-entry {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid #3b82f6;
            padding-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test de Conexión Echo/Reverb</h1>
        
        <div id="echo-status" class="status info">
            Verificando Echo...
        </div>
        
        <div id="connection-status" class="status info">
            Verificando conexión WebSocket...
        </div>
        
        <div id="channel-status" class="status info">
            Verificando canal 'corte'...
        </div>
        
        <h2>📋 Log de Eventos</h2>
        <div id="log"></div>
    </div>
    
    <script>
        const log = document.getElementById('log');
        
        function addLog(message, type = 'info') {
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            entry.style.borderLeftColor = type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : '#3b82f6';
            entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            log.appendChild(entry);
            log.scrollTop = log.scrollHeight;
        }
        
        // Verificar Echo
        setTimeout(() => {
            const echoStatus = document.getElementById('echo-status');
            if (window.Echo) {
                echoStatus.className = 'status success';
                echoStatus.textContent = '✅ Echo está disponible';
                addLog('Echo está disponible', 'success');
            } else {
                echoStatus.className = 'status error';
                echoStatus.textContent = '❌ Echo NO está disponible';
                addLog('Echo NO está disponible - Verifica que Vite esté corriendo', 'error');
                return;
            }
            
            // Verificar conexión
            const connStatus = document.getElementById('connection-status');
            
            window.Echo.connector.pusher.connection.bind('connected', () => {
                connStatus.className = 'status success';
                connStatus.textContent = '✅ WebSocket conectado a Reverb';
                addLog('WebSocket conectado exitosamente', 'success');
            });
            
            window.Echo.connector.pusher.connection.bind('error', (err) => {
                connStatus.className = 'status error';
                connStatus.textContent = '❌ Error de conexión: ' + JSON.stringify(err);
                addLog('Error de conexión: ' + JSON.stringify(err), 'error');
            });
            
            // Suscribirse al canal
            const channelStatus = document.getElementById('channel-status');
            const channel = window.Echo.channel('corte');
            
            channel.subscribed(() => {
                channelStatus.className = 'status success';
                channelStatus.textContent = '✅ Suscrito al canal "corte"';
                addLog('Suscrito exitosamente al canal "corte"', 'success');
            });
            
            channel.error((error) => {
                channelStatus.className = 'status error';
                channelStatus.textContent = '❌ Error en canal: ' + JSON.stringify(error);
                addLog('Error en canal "corte": ' + JSON.stringify(error), 'error');
            });
            
            // Escuchar eventos
            channel.listen('CorteRecordCreated', (e) => {
                addLog('🎉 Evento CorteRecordCreated recibido!', 'success');
                addLog('Datos: ' + JSON.stringify(e, null, 2), 'info');
            });
            
            addLog('Configuración completada. Esperando eventos...', 'info');
        }, 1000);
    </script>
</body>
</html>
