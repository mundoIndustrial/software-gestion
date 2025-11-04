<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Prueba Firebase Storage</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
        }
        
        .upload-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        
        .drop-zone {
            border: 3px dashed #667eea;
            border-radius: 12px;
            padding: 60px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9ff;
        }
        
        .drop-zone:hover {
            border-color: #764ba2;
            background: #f0f2ff;
        }
        
        .drop-zone.dragover {
            border-color: #764ba2;
            background: #e8ebff;
            transform: scale(1.02);
        }
        
        .drop-zone-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .drop-zone-text {
            font-size: 18px;
            color: #667eea;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .drop-zone-hint {
            color: #999;
            font-size: 14px;
        }
        
        .file-input {
            display: none;
        }
        
        .controls {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f0f2ff;
            color: #667eea;
        }
        
        .btn-secondary:hover {
            background: #e8ebff;
        }
        
        .btn-danger {
            background: #ff4757;
            color: white;
        }
        
        .btn-danger:hover {
            background: #ff3838;
        }
        
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .gallery-item {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
        }
        
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .gallery-item-info {
            padding: 15px;
        }
        
        .gallery-item-name {
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .gallery-item-size {
            font-size: 12px;
            color: #999;
        }
        
        .gallery-item-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            flex: 1;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #667eea;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔥 Firebase Storage - Prueba de Integración</h1>
            <p>Sube, visualiza y gestiona imágenes en Firebase Storage</p>
        </div>
        
        <div id="alerts"></div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value" id="totalImages">0</div>
                <div class="stat-label">Imágenes Totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="totalSize">0 MB</div>
                <div class="stat-label">Tamaño Total</div>
            </div>
        </div>
        
        <div class="upload-section">
            <div class="drop-zone" id="dropZone">
                <div class="drop-zone-icon">📁</div>
                <div class="drop-zone-text">Arrastra imágenes aquí</div>
                <div class="drop-zone-hint">o haz clic para seleccionar archivos</div>
            </div>
            <input type="file" id="fileInput" class="file-input" multiple accept="image/*">
            
            <div class="controls">
                <button class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                    📤 Seleccionar Archivos
                </button>
                <button class="btn btn-secondary" onclick="loadGallery()">
                    🔄 Recargar Galería
                </button>
                <button class="btn btn-danger" onclick="clearGallery()">
                    🗑️ Limpiar Todo
                </button>
            </div>
        </div>
        
        <div id="loading" class="loading" style="display: none;">
            <div class="spinner"></div>
            <div>Cargando imágenes...</div>
        </div>
        
        <div class="gallery" id="gallery"></div>
    </div>
    
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const gallery = document.getElementById('gallery');
        const loading = document.getElementById('loading');
        const alerts = document.getElementById('alerts');
        
        // Cargar galería al inicio
        loadGallery();
        
        // Eventos de drag & drop
        dropZone.addEventListener('click', () => fileInput.click());
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });
        
        dropZone.addEventListener('drop', async (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            const files = Array.from(e.dataTransfer.files);
            await uploadImages(files);
        });
        
        fileInput.addEventListener('change', async (e) => {
            const files = Array.from(e.target.files);
            await uploadImages(files);
            e.target.value = '';
        });
        
        // Subir imágenes
        async function uploadImages(files) {
            if (files.length === 0) return;
            
            showAlert('Subiendo ' + files.length + ' imagen(es)...', 'info');
            
            const formData = new FormData();
            files.forEach(file => formData.append('images[]', file));
            formData.append('folder', 'test-gallery');
            
            try {
                const response = await fetch('/images/upload-multiple', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(`✅ ${data.data.length} imagen(es) subida(s) exitosamente`, 'success');
                    loadGallery();
                } else {
                    showAlert('❌ Error al subir imágenes: ' + data.message, 'error');
                }
            } catch (error) {
                showAlert('❌ Error: ' + error.message, 'error');
            }
        }
        
        // Cargar galería
        async function loadGallery() {
            loading.style.display = 'block';
            gallery.innerHTML = '';
            
            try {
                const response = await fetch('/images/list?folder=test-gallery');
                const data = await response.json();
                
                if (data.success) {
                    updateStats(data.data);
                    
                    if (data.data.length === 0) {
                        gallery.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">No hay imágenes. ¡Sube algunas!</div>';
                    } else {
                        data.data.forEach(img => {
                            const size = formatBytes(img.size);
                            gallery.innerHTML += `
                                <div class="gallery-item">
                                    <img src="${img.url}" alt="${img.name}" loading="lazy">
                                    <div class="gallery-item-info">
                                        <div class="gallery-item-name" title="${img.name}">${img.name.split('/').pop()}</div>
                                        <div class="gallery-item-size">${size}</div>
                                        <div class="gallery-item-actions">
                                            <button class="btn btn-secondary btn-small" onclick="copyUrl('${img.url}')">
                                                📋 Copiar URL
                                            </button>
                                            <button class="btn btn-danger btn-small" onclick="deleteImage('${img.name}')">
                                                🗑️ Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                }
            } catch (error) {
                showAlert('❌ Error al cargar galería: ' + error.message, 'error');
            } finally {
                loading.style.display = 'none';
            }
        }
        
        // Eliminar imagen
        async function deleteImage(path) {
            if (!confirm('¿Estás seguro de eliminar esta imagen?')) return;
            
            try {
                const response = await fetch('/images/delete', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ path })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('✅ Imagen eliminada exitosamente', 'success');
                    loadGallery();
                } else {
                    showAlert('❌ Error al eliminar imagen', 'error');
                }
            } catch (error) {
                showAlert('❌ Error: ' + error.message, 'error');
            }
        }
        
        // Limpiar toda la galería
        async function clearGallery() {
            if (!confirm('¿Estás seguro de eliminar TODAS las imágenes?')) return;
            
            try {
                const response = await fetch('/images/list?folder=test-gallery');
                const data = await response.json();
                
                if (data.success) {
                    for (const img of data.data) {
                        await fetch('/images/delete', {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ path: img.name })
                        });
                    }
                    showAlert('✅ Todas las imágenes eliminadas', 'success');
                    loadGallery();
                }
            } catch (error) {
                showAlert('❌ Error: ' + error.message, 'error');
            }
        }
        
        // Copiar URL
        function copyUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                showAlert('✅ URL copiada al portapapeles', 'success');
            });
        }
        
        // Mostrar alerta
        function showAlert(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            const alertHtml = `
                <div class="alert ${alertClass}">
                    ${message}
                </div>
            `;
            alerts.innerHTML = alertHtml;
            setTimeout(() => {
                alerts.innerHTML = '';
            }, 5000);
        }
        
        // Actualizar estadísticas
        function updateStats(images) {
            const totalImages = images.length;
            const totalSize = images.reduce((sum, img) => sum + (img.size || 0), 0);
            
            document.getElementById('totalImages').textContent = totalImages;
            document.getElementById('totalSize').textContent = formatBytes(totalSize);
        }
        
        // Formatear bytes
        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    </script>
</body>
</html>
