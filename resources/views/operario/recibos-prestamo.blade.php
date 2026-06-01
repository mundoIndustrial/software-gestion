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
        display: flex;
        gap: 0.5rem;
    }
    .recibo-actions button,
    .recibo-actions a {
        flex: 1;
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
    .recibo-actions button:last-child,
    .recibo-actions a:last-child {
        background: #fff7ed;
        border-color: #fed7aa;
        color: #9a3412;
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
                            <div class="recibo-actions">
                                <a href="{{ route('operario.recibos-prestamo.insumos.show', $recibo->id) }}">Ver</a>
                                <a href="{{ route('operario.recibos-prestamo.insumos.show', ['id' => $recibo->id, 'firmante' => 'costurero']) }}">
                                    {{ !empty($recibo->firma_costurero) ? 'Actualizar firma costurero' : 'Pendiente firma costurero' }}
                                </a>
                                <a href="{{ route('operario.recibos-prestamo.insumos.show', ['id' => $recibo->id, 'firmante' => 'mensajero']) }}">
                                    {{ !empty($recibo->firma_mensajero) ? 'Actualizar firma mensajero' : 'Pendiente firma mensajero' }}
                                </a>
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
                            <div class="recibo-actions">
                                <a href="{{ route('operario.recibos-prestamo.contramuestra.show', $recibo->id) }}">Ver</a>
                                <a href="{{ route('operario.recibos-prestamo.contramuestra.show', ['id' => $recibo->id, 'firmante' => 'costurero']) }}">
                                    {{ !empty($recibo->firma_costurero) ? 'Actualizar firma costurero' : 'Pendiente firma costurero' }}
                                </a>
                                <a href="{{ route('operario.recibos-prestamo.contramuestra.show', ['id' => $recibo->id, 'firmante' => 'mensajero']) }}">
                                    {{ !empty($recibo->firma_mensajero) ? 'Actualizar firma mensajero' : 'Pendiente firma mensajero' }}
                                </a>
                            </div>
                        </article>
                    @empty
                        <p class="recibo-meta">Sin recibos registrados.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection
