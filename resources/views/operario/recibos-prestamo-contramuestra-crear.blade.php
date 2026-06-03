@extends('operario.layout')

@section('title', 'Nuevo Préstamo de Contramuestra')
@section('page-title')
    <span style="display: inline-flex; align-items: center; gap: 0.6rem;">
        <span class="material-symbols-rounded">assignment</span>
        <span>PRÉSTAMO DE CONTRAMUESTRA COSTURA</span>
    </span>
@endsection

@push('styles')
<style>
    .recibo-page { max-width: 980px; margin: 0 auto; padding: 0.4rem 0 1rem; }
    .page-top-actions { display: flex; justify-content: flex-end; margin-bottom: 0.6rem; }
    .recibo-card { background: #fff; border: 1px solid #dde4ef; border-radius: 14px; box-shadow: 0 16px 35px rgba(15, 23, 42, 0.08); overflow: hidden; }
    .recibo-head { border-bottom: 2px solid #dbe5f2; background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%); color: #fff; padding: 1rem; }
    .recibo-head h2 { margin: 0; font-size: 1rem; letter-spacing: 0.04em; font-weight: 700; }
    .recibo-body { padding: 1rem; }
    .recibo-meta-grid { display: grid; grid-template-columns: 1fr; gap: 0.75rem; margin-bottom: 1rem; }
    .meta-field { border: 1px solid #d9e2ef; border-radius: 10px; padding: 0.65rem 0.7rem; background: #f8fafc; }
    .meta-label { display: block; font-size: 0.7rem; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.22rem; font-weight: 600; }
    .meta-value, .meta-input, .meta-textarea { width: 100%; border: 0; background: transparent; font-size: 0.88rem; color: #0f172a; outline: none; padding: 0; }
    .meta-value { font-weight: 700; }
    .meta-textarea { min-height: 120px; resize: vertical; }
    .form-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.8rem; }
    .btn-main, .btn-soft { border-radius: 10px; border: 1px solid transparent; padding: 0.52rem 0.75rem; font-size: 0.8rem; font-weight: 700; cursor: pointer; text-decoration: none; text-align: center; }
    .btn-main { background: #0f172a; color: #fff; }
    .btn-soft { background: #f1f5f9; color: #0f172a; border-color: #d8e1ec; }
    @media (min-width: 768px) { .recibo-body { padding: 1.2rem; } .recibo-meta-grid { grid-template-columns: repeat(2, 1fr); } .meta-field-full { grid-column: 1 / -1; } }
</style>
@endpush

@section('content')
    @php
        $hoy = now();
        $numeroOrden = isset($numeroOrden) ? (int) $numeroOrden : 1;
    @endphp

    <div class="recibo-page">
        <div class="page-top-actions">
            <a class="btn-soft" href="{{ route('operario.recibos-prestamo.index') }}">Volver</a>
        </div>
        <form class="recibo-card" method="POST" action="{{ route('operario.recibos-prestamo.contramuestra.store') }}" autocomplete="off">
            @csrf
            <header class="recibo-head">
                <h2>RECIBO DE PRÉSTAMO DE CONTRAMUESTRA COSTURA</h2>
            </header>
            <div class="recibo-body">
                <section class="recibo-meta-grid">
                    <div class="meta-field">
                        <label class="meta-label">Fecha</label>
                        <input class="meta-value" type="text" value="{{ $hoy->format('d/m/Y') }}" readonly>
                    </div>
                    <div class="meta-field">
                        <label class="meta-label">Número de Orden</label>
                        <input class="meta-value" type="text" value="N° {{ $numeroOrden }}" readonly>
                    </div>
                    <div class="meta-field">
                        <label class="meta-label" for="nombre_costurero_contra">Nombre del Costurero(a)</label>
                        <input id="nombre_costurero_contra" name="nombre_costurero" class="meta-input" type="text" placeholder="Nombre completo" list="lista_talleres_contra" required>
                        <datalist id="lista_talleres_contra">
                            @foreach(($talleres ?? []) as $tallerNombre)
                                <option value="{{ $tallerNombre }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="meta-field meta-field-full">
                        <label class="meta-label" for="descripcion_contramuestra">Descripción</label>
                        <textarea id="descripcion_contramuestra" name="descripcion" class="meta-textarea" placeholder="Describe la contramuestra..." required></textarea>
                    </div>
                </section>
                <div class="form-actions">
                    <button type="submit" class="btn-main">Generar Recibo</button>
                    <a class="btn-soft" href="{{ route('operario.recibos-prestamo.index') }}">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const textarea = document.getElementById('descripcion_contramuestra');
        if (textarea) {
            textarea.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = `${Math.min(this.scrollHeight, 220)}px`;
            });
        }
    });
</script>
@endpush
