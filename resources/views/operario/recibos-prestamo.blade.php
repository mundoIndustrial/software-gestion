@extends('operario.layout')

@section('title', 'Recibos Préstamo')
@section('page-title')
    <span style="display: inline-flex; align-items: center; gap: 0.6rem;">
        <span class="material-symbols-rounded">receipt_long</span>
        <span>RECIBOS PRÉSTAMO</span>
    </span>
@endsection

@push('styles')
<style>
    .prestamos-wrap {
        width: 100%;
        max-width: 980px;
        margin: 0 auto;
        display: grid;
        gap: 1rem;
    }
    .prestamos-form-card,
    .prestamos-list-card {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #e6ebf3;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        padding: 0.9rem;
    }
    .tipo-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.45rem;
        background: #f1f5f9;
        border-radius: 14px;
        padding: 0.3rem;
    }
    .tipo-btn {
        border: 0;
        background: transparent;
        border-radius: 14px;
        padding: 0.7rem 0.65rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        -webkit-text-decoration: none;
    }
    .tipo-btn:link,
    .tipo-btn:visited,
    .tipo-btn:hover,
    .tipo-btn:active {
        text-decoration: none;
    }
    .tipo-btn.active {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        box-shadow: 0 8px 18px rgba(3, 105, 161, 0.28);
    }
    .tipo-btn strong {
        color: #0f172a;
        display: block;
        font-size: 0.74rem;
        letter-spacing: 0.02em;
    }
    .tipo-btn span {
        color: #475569;
        font-size: 0.69rem;
        line-height: 1.2;
    }
    .tipo-btn.active strong,
    .tipo-btn.active span {
        color: #ffffff;
    }
    .tab-panel {
        display: none;
    }
    .tab-panel.active {
        display: block;
    }
    .add-btn {
        width: 100%;
        border: 0;
        border-radius: 12px;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #fff;
        font-weight: 700;
        font-size: 0.84rem;
        padding: 0.78rem 0.9rem;
        margin-bottom: 0.75rem;
        cursor: pointer;
    }
    .list-title {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        margin-bottom: 0.65rem;
    }
    .list-title h3 {
        margin: 0;
        font-size: 0.88rem;
        color: #0f172a;
        font-weight: 700;
    }
    .recibos-cards {
        display: grid;
        gap: 0.75rem;
    }
    .flash-success {
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #166534;
        border-radius: 12px;
        padding: 0.7rem 0.8rem;
        font-size: 0.82rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
    }
    .recibo-card-mobile {
        border: 1px solid #e4eaf3;
        border-radius: 16px;
        padding: 0.9rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    }
    .recibo-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.45rem;
    }
    .recibo-num {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
    }
    .recibo-meta {
        margin: 0.2rem 0 0;
        color: #334155;
        font-size: 0.82rem;
        line-height: 1.35;
    }
    .recibo-actions {
        margin-top: 0.8rem;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.45rem;
    }
    .recibo-actions button,
    .recibo-actions a {
        border: 1px solid #d2dceb;
        border-radius: 10px;
        background: #ffffff;
        color: #0f172a;
        font-size: 0.77rem;
        font-weight: 600;
        padding: 0.5rem 0.55rem;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
    }
    .recibo-actions .full-row {
        grid-column: 1 / -1;
    }
    .recibo-actions button:last-child,
    .recibo-actions a:last-child {
        background: #fff7ed;
        border-color: #fed7aa;
        color: #9a3412;
    }
    .btn-confirmar-entrada {
        background: #ecfdf5 !important;
        border-color: #86efac !important;
        color: #166534 !important;
    }
    .badge-inline {
        display: inline-block;
        margin-top: 6px;
        margin-right: 6px;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #334155;
    }
    .modal-backdrop-entrada {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.45);
        z-index: 3000;
    }
    .modal-entrada {
        width: min(92vw, 560px);
        background: #fff;
        border-radius: 12px;
        padding: 16px;
    }
    .modal-entrada textarea {
        width: 100%;
        min-height: 96px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 10px;
        font-size: 13px;
        resize: vertical;
    }
    @media (min-width: 768px) {
        .prestamos-form-card,
        .prestamos-list-card {
            padding: 1.2rem;
        }
        .recibos-cards {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>
@endpush

@section('content')
    @php
        $tabActiva = request()->query('tab', 'insumos');
        $tabActiva = in_array($tabActiva, ['insumos', 'contramuestra'], true) ? $tabActiva : 'insumos';
    @endphp

    <div class="prestamos-wrap">
        <section class="prestamos-form-card">
            <div class="tipo-grid">
                <a href="{{ route('operario.recibos-prestamo.index', ['tab' => 'insumos']) }}" class="tipo-btn {{ $tabActiva === 'insumos' ? 'active' : '' }}" data-tipo="insumos">
                    <strong>PRESTAMO DE INSUMOS</strong>
                    <span>Para salida temporal de insumos del área.</span>
                </a>
                <a href="{{ route('operario.recibos-prestamo.index', ['tab' => 'contramuestra']) }}" class="tipo-btn {{ $tabActiva === 'contramuestra' ? 'active' : '' }}" data-tipo="contramuestra">
                    <strong>PRESTAMO DE CONTRAMUESTRA COSTURA</strong>
                    <span>Para contramuestras solicitadas a costura.</span>
                </a>
            </div>
        </section>

        <section class="prestamos-list-card">
            <div class="list-title">
                <h3>Recibos Registrados</h3>
            </div>
            @if(session('success'))
                <div class="flash-success">{{ session('success') }}</div>
            @endif

            <div class="tab-panel {{ $tabActiva === 'insumos' ? 'active' : '' }}" data-panel="insumos">
                <a href="{{ route('operario.recibos-prestamo.insumos.crear') }}" class="add-btn" style="display:inline-block;text-align:center;text-decoration:none;">+ Agregar préstamo de insumos</a>
                <div class="recibos-cards">
                    @forelse(($recibosInsumos ?? []) as $recibo)
                        <article class="recibo-card-mobile">
                            <div class="recibo-top">
                                <p class="recibo-num">N° {{ $recibo->numero_orden }}</p>
                            </div>
                            <p class="recibo-meta"><strong>Tipo:</strong> Préstamo de Insumos</p>
                            <p class="recibo-meta"><strong>Responsable:</strong> {{ $recibo->nombre_costurero }}</p>
                            <p class="recibo-meta"><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($recibo->fecha)->format('d/m/Y') }}</p>
                            @if($recibo->anulado)
                                <span class="badge-inline" style="background:#fef2f2;color:#991b1b;">ANULADO</span>
                            @endif
                            @if($recibo->confirmado_entrada)
                                <span class="badge-inline" style="background:#ecfdf5;color:#166534;">ENTRADA CONFIRMADA</span>
                                @if($recibo->confirmado_entrada_en)
                                    <span class="badge-inline" style="background:#f8fafc;color:#334155;">{{ \Carbon\Carbon::parse($recibo->confirmado_entrada_en)->format('d/m/Y H:i') }}</span>
                                @endif
                            @endif
                            <div class="recibo-actions">
                                <a href="{{ route('operario.recibos-prestamo.insumos.show', $recibo->id) }}" class="full-row">Ver</a>
                                <a href="{{ route('operario.recibos-prestamo.insumos.show', ['id' => $recibo->id, 'firmante' => 'costurero']) }}">
                                    {{ !empty($recibo->firma_costurero) ? 'Actualizar firma costurero' : 'Pendiente firma costurero' }}
                                </a>
                                <a href="{{ route('operario.recibos-prestamo.insumos.show', ['id' => $recibo->id, 'firmante' => 'mensajero']) }}">
                                    {{ !empty($recibo->firma_mensajero) ? 'Actualizar firma mensajero' : 'Pendiente firma mensajero' }}
                                </a>
                                @if(!$recibo->confirmado_entrada)
                                    <button type="button" class="btn-confirmar-entrada full-row"
                                        data-action="confirmar-entrada"
                                        data-url="{{ route('operario.recibos-prestamo.insumos.confirmar-entrada', $recibo->id) }}">
                                        ✓ Confirmar entrada
                                    </button>
                                @endif
                                @if(!$recibo->anulado)
                                    <button type="button" class="full-row"
                                        data-action="anular"
                                        data-url="{{ route('operario.recibos-prestamo.insumos.anular', $recibo->id) }}">
                                        Anular
                                    </button>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="recibo-meta">Sin recibos registrados.</p>
                    @endforelse
                </div>
            </div>

            <div class="tab-panel {{ $tabActiva === 'contramuestra' ? 'active' : '' }}" data-panel="contramuestra">
                <a href="{{ route('operario.recibos-prestamo.contramuestra.crear') }}" class="add-btn" style="display:inline-block;text-align:center;text-decoration:none;">+ Agregar préstamo de contramuestra</a>
                <div class="recibos-cards">
                    @forelse(($recibosContramuestra ?? []) as $recibo)
                        <article class="recibo-card-mobile">
                            <div class="recibo-top">
                                <p class="recibo-num">N° {{ $recibo->numero_orden }}</p>
                            </div>
                            <p class="recibo-meta"><strong>Tipo:</strong> Préstamo de Contramuestra Costura</p>
                            <p class="recibo-meta"><strong>Responsable:</strong> {{ $recibo->nombre_costurero }}</p>
                            <p class="recibo-meta"><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($recibo->fecha)->format('d/m/Y') }}</p>
                            @if($recibo->anulado)
                                <span class="badge-inline" style="background:#fef2f2;color:#991b1b;">ANULADO</span>
                            @endif
                            @if($recibo->confirmado_entrada)
                                <span class="badge-inline" style="background:#ecfdf5;color:#166534;">ENTRADA CONFIRMADA</span>
                                @if($recibo->confirmado_entrada_en)
                                    <span class="badge-inline" style="background:#f8fafc;color:#334155;">{{ \Carbon\Carbon::parse($recibo->confirmado_entrada_en)->format('d/m/Y H:i') }}</span>
                                @endif
                            @endif
                            <div class="recibo-actions">
                                <a href="{{ route('operario.recibos-prestamo.contramuestra.show', $recibo->id) }}" class="full-row">Ver</a>
                                <a href="{{ route('operario.recibos-prestamo.contramuestra.show', ['id' => $recibo->id, 'firmante' => 'costurero']) }}">
                                    {{ !empty($recibo->firma_costurero) ? 'Actualizar firma costurero' : 'Pendiente firma costurero' }}
                                </a>
                                <a href="{{ route('operario.recibos-prestamo.contramuestra.show', ['id' => $recibo->id, 'firmante' => 'mensajero']) }}">
                                    {{ !empty($recibo->firma_mensajero) ? 'Actualizar firma mensajero' : 'Pendiente firma mensajero' }}
                                </a>
                                @if(!$recibo->confirmado_entrada)
                                    <button type="button" class="btn-confirmar-entrada full-row"
                                        data-action="confirmar-entrada"
                                        data-url="{{ route('operario.recibos-prestamo.contramuestra.confirmar-entrada', $recibo->id) }}">
                                        ✓ Confirmar entrada
                                    </button>
                                @endif
                                @if(!$recibo->anulado)
                                    <button type="button" class="full-row"
                                        data-action="anular"
                                        data-url="{{ route('operario.recibos-prestamo.contramuestra.anular', $recibo->id) }}">
                                        Anular
                                    </button>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="recibo-meta">Sin recibos registrados.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <div id="modal-confirmar-entrada" class="modal-backdrop-entrada">
        <div class="modal-entrada">
            <h3 style="margin:0 0 8px;">Confirmar Entrada</h3>
            <p style="margin:0 0 10px;font-size:13px;color:#475569;">¿Estás seguro de que este recibo corresponde con lo entregado?</p>
            <label style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
                <input type="checkbox" id="entrada-no-corresponde">
                <span style="font-size:13px;">No corresponde (registrar novedad)</span>
            </label>
            <textarea id="entrada-novedad" placeholder="Escribe la novedad si no corresponde..."></textarea>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:10px;">
                <button type="button" id="btn-cancelar-entrada">Cancelar</button>
                <button type="button" id="btn-guardar-entrada">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
    (() => {
        const csrf = "{{ csrf_token() }}";
        const modal = document.getElementById('modal-confirmar-entrada');
        const noCorresponde = document.getElementById('entrada-no-corresponde');
        const novedad = document.getElementById('entrada-novedad');
        const btnCancelar = document.getElementById('btn-cancelar-entrada');
        const btnGuardar = document.getElementById('btn-guardar-entrada');
        let urlConfirmar = '';

        function closeModal() {
            modal.style.display = 'none';
            noCorresponde.checked = false;
            novedad.value = '';
            urlConfirmar = '';
        }

        document.querySelectorAll('[data-action="anular"]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                if (!confirm('¿Seguro que deseas anular este recibo?')) return;
                const response = await fetch(btn.dataset.url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    alert(data.message || 'No se pudo anular.');
                    return;
                }
                location.reload();
            });
        });

        document.querySelectorAll('[data-action="confirmar-entrada"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                urlConfirmar = btn.dataset.url;
                modal.style.display = 'flex';
            });
        });

        btnCancelar?.addEventListener('click', closeModal);
        btnGuardar?.addEventListener('click', async () => {
            const payload = {
                corresponde: !noCorresponde.checked,
                novedades: novedad.value.trim() || null
            };

            const response = await fetch(urlConfirmar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                alert(data.message || 'No se pudo confirmar la entrada.');
                return;
            }
            closeModal();
            location.reload();
        });
    })();
    </script>
@endsection
