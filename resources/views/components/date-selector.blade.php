<!-- Date selector component -->
<div class="date-selector-section">
    <div class="date-input-group">
        <label>Fecha inicio</label>
        <input type="date" id="startDate" value="{{ request('start_date', now()->format('Y-m-d')) }}">
    </div>
    <div class="date-input-group">
        <label>Fecha fin</label>
        <input type="date" id="endDate" value="{{ request('end_date', now()->format('Y-m-d')) }}">
    </div>
    <button class="btn-apply" onclick="filtrarPorFechas()">Aplicar Filtro</button>
</div>

<style>
 .date-selector-section {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    justify-content: center; /* CENTRA HORIZONTALMENTE */
    margin: 20px auto; /* margen automático para centrar horizontalmente si es bloque */
    padding: 20px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
    flex-wrap: wrap;
    max-width: 600px; /* opcional: limita el ancho del selector */
}


    .date-input-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .date-input-group label {
        color: #9ca3af;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .date-input-group input[type="date"] {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 8px;
        padding: 10px 15px;
        color: #fff;
        font-size: 14px;
        outline: none;
        transition: all 0.3s;
        cursor: pointer;
    }

    .date-input-group input[type="date"]:hover {
        border-color: rgba(255, 255, 255, 0.25);
        background: rgba(255, 255, 255, 0.1);
    }

    .date-input-group input[type="date"]:focus {
        border-color: #6366f1;
        background: rgba(255, 255, 255, 0.12);
    }

    .date-input-group input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        cursor: pointer;
    }

    .btn-apply {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-apply:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
    }

    .btn-apply:active {
        transform: translateY(0);
    }

    @media (max-width: 768px) {
        .date-selector-section {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-apply {
            width: 100%;
        }
    }
</style>

<script>
    function filtrarPorFechas() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        if (!startDate || !endDate) {
            alert('Por favor selecciona ambas fechas');
            return;
        }

        if (new Date(startDate) > new Date(endDate)) {
            alert('La fecha de inicio debe ser anterior a la fecha fin');
            return;
        }

        // Actualizar URL con parámetros de fecha
        const url = new URL(window.location);
        url.searchParams.set('start_date', startDate);
        url.searchParams.set('end_date', endDate);
        window.location.href = url.toString();
    }
</script>
