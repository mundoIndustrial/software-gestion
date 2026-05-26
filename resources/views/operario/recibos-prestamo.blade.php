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
    .recibo-actions button {
        flex: 1;
        border: 1px solid #d2dceb;
        border-radius: 10px;
        background: #ffffff;
        color: #0f172a;
        font-size: 0.77rem;
        font-weight: 600;
        padding: 0.5rem 0.55rem;
        cursor: pointer;
    }
    .recibo-actions button:last-child {
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
        $demoRecibos = [
            [
                'numero' => 1,
                'tipo' => 'Préstamo de Insumos',
                'tipo_key' => 'insumos',
                'responsable' => 'Tatiana',
                'fecha' => '26/05/2026 09:12',
            ],
            [
                'numero' => 2,
                'tipo' => 'Préstamo de Contramuestra Costura',
                'tipo_key' => 'contramuestra',
                'responsable' => 'Tatiana',
                'fecha' => '25/05/2026 16:40',
            ],
        ];
    @endphp

    <div class="prestamos-wrap">
        <section class="prestamos-form-card">
            <div class="tipo-grid">
                <button type="button" class="tipo-btn active" data-tipo="insumos">
                    <strong>PRESTAMO DE INSUMOS</strong>
                    <span>Para salida temporal de insumos del área.</span>
                </button>
                <button type="button" class="tipo-btn" data-tipo="contramuestra">
                    <strong>PRESTAMO DE CONTRAMUESTRA COSTURA</strong>
                    <span>Para contramuestras solicitadas a costura.</span>
                </button>
            </div>
        </section>

        <section class="prestamos-list-card">
            <div class="list-title">
                <h3>Recibos Registrados</h3>
            </div>

            <div class="tab-panel active" data-panel="insumos">
                <a href="{{ route('operario.recibos-prestamo.insumos.crear') }}" class="add-btn" style="display:inline-block;text-align:center;text-decoration:none;">+ Agregar préstamo de insumos</a>
                <div class="recibos-cards">
                    @foreach($demoRecibos as $recibo)
                        @if($recibo['tipo_key'] === 'insumos')
                            <article class="recibo-card-mobile">
                                <div class="recibo-top">
                                    <p class="recibo-num">N° {{ $recibo['numero'] }}</p>
                                </div>
                                <p class="recibo-meta"><strong>Tipo:</strong> {{ $recibo['tipo'] }}</p>
                                <p class="recibo-meta"><strong>Responsable:</strong> {{ $recibo['responsable'] }}</p>
                                <p class="recibo-meta"><strong>Fecha:</strong> {{ $recibo['fecha'] }}</p>
                                <div class="recibo-actions">
                                    <button type="button">Ver</button>
                                    <button type="button">Anular</button>
                                </div>
                            </article>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="tab-panel" data-panel="contramuestra">
                <a href="{{ route('operario.recibos-prestamo.contramuestra.crear') }}" class="add-btn" style="display:inline-block;text-align:center;text-decoration:none;">+ Agregar préstamo de contramuestra</a>
                <div class="recibos-cards">
                    @foreach($demoRecibos as $recibo)
                        @if($recibo['tipo_key'] === 'contramuestra')
                            <article class="recibo-card-mobile">
                                <div class="recibo-top">
                                    <p class="recibo-num">N° {{ $recibo['numero'] }}</p>
                                </div>
                                <p class="recibo-meta"><strong>Tipo:</strong> {{ $recibo['tipo'] }}</p>
                                <p class="recibo-meta"><strong>Responsable:</strong> {{ $recibo['responsable'] }}</p>
                                <p class="recibo-meta"><strong>Fecha:</strong> {{ $recibo['fecha'] }}</p>
                                <div class="recibo-actions">
                                    <button type="button">Ver</button>
                                    <button type="button">Anular</button>
                                </div>
                            </article>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const botones = document.querySelectorAll('.tipo-btn');
        const panels = document.querySelectorAll('.tab-panel');

        botones.forEach((btn) => {
            btn.addEventListener('click', function () {
                botones.forEach((b) => b.classList.remove('active'));
                this.classList.add('active');

                const tipo = this.dataset.tipo;
                panels.forEach((panel) => {
                    panel.classList.toggle('active', panel.dataset.panel === tipo);
                });
            });
        });
    });
</script>
@endpush
