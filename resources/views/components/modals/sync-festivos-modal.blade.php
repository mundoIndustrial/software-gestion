<!-- Modal para Sincronizar Festivos -->
<div id="sync-festivos-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 30px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #1f2937; font-weight: 600; font-size: 1.25rem;">
                <i class="fas fa-sync-alt" style="margin-right: 8px;"></i>
                Sincronizar Festivos
            </h3>
            <button onclick="cerrarModalSincronizar()" style="background: none; border: none; cursor: pointer; font-size: 1.5rem; color: #9ca3af;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; margin-bottom: 12px; font-weight: 500; color: #4b5563;">
                ¿Qué deseas sincronizar?
            </label>
            <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                <label style="display: flex; align-items: center; cursor: pointer; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;">
                    <input type="radio" name="sync-type" value="single" checked style="margin-right: 8px; cursor: pointer;">
                    <span style="font-size: 0.95rem;">Un año específico</span>
                </label>
                <label style="display: flex; align-items: center; cursor: pointer; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;">
                    <input type="radio" name="sync-type" value="multiple" style="margin-right: 8px; cursor: pointer;">
                    <span style="font-size: 0.95rem;">Múltiples años</span>
                </label>
            </div>
        </div>

        <!-- Opción 1: Un año específico -->
        <div id="single-year-section" style="margin-bottom: 24px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #4b5563; font-size: 0.9rem;">
                Selecciona el año:
            </label>
            <input type="number" id="year-input" min="2020" max="2100" value="2027" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
        </div>

        <!-- Opción 2: Múltiples años -->
        <div id="multiple-years-section" style="display: none; margin-bottom: 24px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #4b5563; font-size: 0.9rem;">
                Ingresa los años (separados por comas):
            </label>
            <input type="text" id="years-input" placeholder="2027, 2028, 2029, 2030" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
            <small style="display: block; margin-top: 6px; color: #9ca3af;">Ejemplo: 2026, 2027, 2028</small>
        </div>

        <!-- Info de festivos -->
        <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-bottom: 24px;">
            <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                <i class="fas fa-info-circle" style="margin-right: 6px;"></i>
                Se sincronizarán los festivos de Colombia desde la API de Nager.Date
            </p>
        </div>

        <!-- Status -->
        <div id="sync-status" style="display: none; margin-bottom: 16px; padding: 12px; border-radius: 6px;">
            <div id="sync-loading" style="display: none; text-align: center;">
                <i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>
                <span>Sincronizando festivos...</span>
            </div>
            <div id="sync-success" style="display: none; background: #d1fae5; color: #065f46; padding: 12px; border-radius: 6px;">
                <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                <span id="success-message"></span>
            </div>
            <div id="sync-error" style="display: none; background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px;">
                <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                <span id="error-message"></span>
            </div>
        </div>

        <!-- Botones -->
        <div style="display: flex; gap: 12px;">
            <button onclick="cerrarModalSincronizar()" style="flex: 1; padding: 10px; background: #e5e7eb; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; color: #4b5563; transition: background 0.2s;">
                Cancelar
            </button>
            <button onclick="sincronizarFestivos()" style="flex: 1; padding: 10px; background: #3b82f6; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; color: white; transition: background 0.2s;">
                <i class="fas fa-sync-alt" style="margin-right: 6px;"></i>
                Sincronizar
            </button>
        </div>
    </div>
</div>

<script>
// Mostrar/Ocultar secciones según el tipo seleccionado
document.querySelectorAll('input[name="sync-type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const singleSection = document.getElementById('single-year-section');
        const multipleSection = document.getElementById('multiple-years-section');
        
        if (this.value === 'single') {
            singleSection.style.display = 'block';
            multipleSection.style.display = 'none';
        } else {
            singleSection.style.display = 'none';
            multipleSection.style.display = 'block';
        }
    });
});

function abrirModalSincronizar() {
    document.getElementById('sync-festivos-modal').style.display = 'flex';
    document.getElementById('sync-status').style.display = 'none';
    document.getElementById('sync-loading').style.display = 'none';
    document.getElementById('sync-success').style.display = 'none';
    document.getElementById('sync-error').style.display = 'none';
}

function cerrarModalSincronizar() {
    document.getElementById('sync-festivos-modal').style.display = 'none';
}

function sincronizarFestivos() {
    const syncType = document.querySelector('input[name="sync-type"]:checked').value;
    const statusDiv = document.getElementById('sync-status');
    const loadingDiv = document.getElementById('sync-loading');
    const successDiv = document.getElementById('sync-success');
    const errorDiv = document.getElementById('sync-error');

    // Mostrar loading
    statusDiv.style.display = 'block';
    loadingDiv.style.display = 'block';
    successDiv.style.display = 'none';
    errorDiv.style.display = 'none';

    if (syncType === 'single') {
        sincronizarAnoUnico();
    } else {
        sincronizarMultiplesAños();
    }
}

function sincronizarAnoUnico() {
    const year = document.getElementById('year-input').value;

    if (!year || year < 2020 || year > 2100) {
        mostrarError('Ingresa un año válido entre 2020 y 2100');
        return;
    }

    fetch(`/api/festivos/sincronizar/${year}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const created = data.data.created;
            const updated = data.data.updated;
            const total = data.data.total;
            mostrarExito(`✓ ${total} festivos sincronizados (${created} nuevos, ${updated} actualizados)`);
        } else {
            mostrarError(data.message || 'Error al sincronizar festivos');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error de conexión: ' + error.message);
    });
}

function sincronizarMultiplesAños() {
    const yearsInput = document.getElementById('years-input').value;
    const years = yearsInput.split(',').map(y => parseInt(y.trim())).filter(y => y >= 2020 && y <= 2100);

    if (years.length === 0) {
        mostrarError('Ingresa años válidos entre 2020 y 2100');
        return;
    }

    fetch('/api/festivos/sincronizar-rango', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ years: years })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            let resumen = `✓ ${years.length} año(s) sincronizados:\n`;
            let totalFestivos = 0;
            
            for (const [year, result] of Object.entries(data.results)) {
                if (result.success) {
                    totalFestivos += result.total;
                    resumen += `${year}: ${result.total} festivos\n`;
                }
            }
            
            mostrarExito(`${resumen.replace(/\n/g, ' | ')}`);
        } else {
            mostrarError(data.message || 'Error al sincronizar festivos');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error de conexión: ' + error.message);
    });
}

function mostrarExito(mensaje) {
    document.getElementById('sync-loading').style.display = 'none';
    document.getElementById('sync-success').style.display = 'block';
    document.getElementById('sync-error').style.display = 'none';
    document.getElementById('success-message').textContent = mensaje;
}

function mostrarError(mensaje) {
    document.getElementById('sync-loading').style.display = 'none';
    document.getElementById('sync-success').style.display = 'none';
    document.getElementById('sync-error').style.display = 'block';
    document.getElementById('error-message').textContent = mensaje;
}

// Cerrar modal al hacer click fuera
document.getElementById('sync-festivos-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalSincronizar();
    }
});
</script>
