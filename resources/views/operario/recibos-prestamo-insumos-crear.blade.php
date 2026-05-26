@extends('operario.layout')

@section('title', 'Nuevo Préstamo de Insumos')
@section('page-title')
    <span style="display: inline-flex; align-items: center; gap: 0.6rem;">
        <span class="material-symbols-rounded">note_stack</span>
        <span>PRÉSTAMO DE INSUMOS</span>
    </span>
@endsection

@push('styles')
<style>
    .recibo-page {
        max-width: 980px;
        margin: 0 auto;
        padding: 0.4rem 0 1rem;
    }
    .page-top-actions {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 0.6rem;
    }
    .recibo-card {
        background: #fff;
        border: 1px solid #dde4ef;
        border-radius: 14px;
        box-shadow: 0 16px 35px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .recibo-head {
        border-bottom: 2px solid #dbe5f2;
        background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%);
        color: #fff;
        padding: 1rem;
    }
    .recibo-head h2 {
        margin: 0;
        font-size: 1rem;
        letter-spacing: 0.04em;
        font-weight: 700;
    }
    .recibo-head p {
        margin: 0.25rem 0 0;
        font-size: 0.78rem;
        opacity: 0.9;
    }
    .recibo-body {
        padding: 1rem;
    }
    .recibo-meta-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .meta-field {
        border: 1px solid #d9e2ef;
        border-radius: 10px;
        padding: 0.65rem 0.7rem;
        background: #f8fafc;
    }
    .meta-label {
        display: block;
        font-size: 0.7rem;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.22rem;
        font-weight: 600;
    }
    .meta-value,
    .meta-input {
        width: 100%;
        border: 0;
        background: transparent;
        font-size: 0.88rem;
        color: #0f172a;
        font-weight: 600;
        padding: 0;
        outline: none;
    }
    .meta-input {
        font-weight: 500;
    }
    .meta-input::placeholder {
        color: #94a3b8;
    }
    .detail-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.55rem;
    }
    .detail-title h3 {
        margin: 0;
        font-size: 0.85rem;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .insumos-table-wrap {
        border: 1px solid #d9e2ef;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
    }
    .insumos-table {
        width: 100%;
        border-collapse: collapse;
    }
    .insumos-table thead th {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #334155;
        background: #f1f5f9;
        padding: 0.55rem;
        border-bottom: 1px solid #d9e2ef;
    }
    .insumos-table tbody td {
        border-top: 1px solid #eef2f7;
        padding: 0.42rem;
        vertical-align: middle;
    }
    .insumo-input {
        width: 100%;
        border: 1px solid #d5deea;
        border-radius: 8px;
        font-size: 0.82rem;
        padding: 0.4rem 0.5rem;
        outline: none;
    }
    .insumo-input:focus {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.15);
    }
    .qty-input {
        max-width: 110px;
    }
    .btn-row-remove {
        border: 1px solid #fed7aa;
        color: #9a3412;
        background: #fff7ed;
        border-radius: 8px;
        padding: 0.3rem 0.55rem;
        font-size: 0.74rem;
        font-weight: 600;
        cursor: pointer;
    }
    .form-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 0.8rem;
    }
    .btn-main,
    .btn-soft {
        border-radius: 10px;
        border: 1px solid transparent;
        padding: 0.52rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        text-align: center;
    }
    .btn-main {
        background: #0f172a;
        color: #fff;
    }
    .btn-soft {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #d8e1ec;
    }
    .signature-grid {
        margin-top: 1.2rem;
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    .signature-box {
        border-top: 1px solid #334155;
        padding-top: 0.35rem;
        min-height: 58px;
    }
    .signature-box p {
        margin: 0;
        font-size: 0.76rem;
        font-weight: 600;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .print-note {
        margin-top: 0.9rem;
        font-size: 0.74rem;
        color: #64748b;
    }
    @media (min-width: 768px) {
        .recibo-body {
            padding: 1.2rem;
        }
        .recibo-meta-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .signature-grid {
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
    }
    @media print {
        .top-nav,
        .notificaciones-dropdown,
        .user-dropdown,
        .form-actions,
        .print-note {
            display: none !important;
        }
        .main-content,
        .page-content {
            padding: 0 !important;
            margin: 0 !important;
        }
        .recibo-page {
            max-width: 100%;
            padding: 0;
        }
        .recibo-card {
            border: 1px solid #000;
            box-shadow: none;
            border-radius: 0;
        }
        .recibo-head {
            background: #fff !important;
            color: #000 !important;
            border-bottom: 1px solid #000;
        }
        .meta-field,
        .insumos-table-wrap {
            border-color: #000;
            background: #fff;
        }
        .insumos-table thead th,
        .insumos-table tbody td {
            border-color: #000;
            background: #fff;
            color: #000;
        }
        .insumo-input {
            border: 0;
            box-shadow: none !important;
            padding: 0;
        }
    }
</style>
@endpush

@section('content')
    @php
        $hoy = now();
        $numeroOrdenDemo = 1;
    @endphp

    <div class="recibo-page">
        <div class="page-top-actions">
            <a class="btn-soft" href="{{ route('operario.recibos-prestamo.index') }}">Volver</a>
        </div>
        <form class="recibo-card" method="POST" action="#" autocomplete="off">
            @csrf
            <header class="recibo-head">
                <h2>RECIBO DE PRÉSTAMO DE INSUMOS</h2>
                <p>Formato administrativo para impresión y firma manual</p>
            </header>

            <div class="recibo-body">
                <section class="recibo-meta-grid">
                    <div class="meta-field">
                        <label class="meta-label">Fecha</label>
                        <input class="meta-value" type="text" value="{{ $hoy->format('d/m/Y') }}" readonly>
                    </div>
                    <div class="meta-field">
                        <label class="meta-label" for="numero_orden">Número de Orden</label>
                        <input id="numero_orden" name="numero_orden" class="meta-value" type="text" value="N° {{ $numeroOrdenDemo }}" readonly>
                    </div>
                    <div class="meta-field">
                        <label class="meta-label" for="nombre_costurero">Nombre del Costurero(a)</label>
                        <input id="nombre_costurero" name="nombre_costurero" class="meta-input" type="text" placeholder="Nombre completo" required>
                    </div>
                </section>

                <section>
                    <div class="detail-title">
                        <h3>Detalle del Recibo</h3>
                    </div>
                    <div class="insumos-table-wrap">
                        <table class="insumos-table">
                            <thead>
                                <tr>
                                    <th style="width: 22%;">Cantidad</th>
                                    <th>Descripción del Insumo</th>
                                    <th style="width: 14%;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="insumosRows">
                                <tr>
                                    <td><input class="insumo-input qty-input" type="number" min="0" step="1" name="items[0][cantidad]" placeholder="0"></td>
                                    <td><input class="insumo-input" type="text" name="items[0][descripcion]" placeholder="Escribe manualmente el insumo"></td>
                                    <td style="text-align:center;"><button type="button" class="btn-row-remove" data-remove-row>Quitar</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-actions">
                        <button type="button" id="addRowBtn" class="btn-soft">+ Agregar Fila</button>
                        <button type="submit" class="btn-main">Generar Recibo</button>
                        <a class="btn-soft" href="{{ route('operario.recibos-prestamo.index') }}">Cancelar</a>
                    </div>
                </section>

                <p class="print-note">Este recibo se imprime y se firma en físico. Las firmas no son digitales.</p>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rowsBody = document.getElementById('insumosRows');
        const addRowBtn = document.getElementById('addRowBtn');

        const refreshIndexes = function () {
            const rows = rowsBody.querySelectorAll('tr');
            rows.forEach((row, index) => {
                const cantidad = row.querySelector('input[name*="[cantidad]"]');
                const descripcion = row.querySelector('input[name*="[descripcion]"]');
                if (cantidad) cantidad.name = `items[${index}][cantidad]`;
                if (descripcion) descripcion.name = `items[${index}][descripcion]`;
            });
        };

        const removeRow = function (button) {
            const rows = rowsBody.querySelectorAll('tr');
            if (rows.length === 1) {
                rows[0].querySelectorAll('input').forEach((input) => { input.value = ''; });
                return;
            }
            button.closest('tr').remove();
            refreshIndexes();
        };

        addRowBtn.addEventListener('click', function () {
            const index = rowsBody.querySelectorAll('tr').length;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input class="insumo-input qty-input" type="number" min="0" step="1" name="items[${index}][cantidad]" placeholder="0"></td>
                <td><input class="insumo-input" type="text" name="items[${index}][descripcion]" placeholder="Escribe manualmente el insumo"></td>
                <td style="text-align:center;"><button type="button" class="btn-row-remove" data-remove-row>Quitar</button></td>
            `;
            rowsBody.appendChild(row);
        });

        rowsBody.addEventListener('click', function (event) {
            const target = event.target;
            if (target && target.matches('[data-remove-row]')) {
                removeRow(target);
            }
        });
    });
</script>
@endpush
