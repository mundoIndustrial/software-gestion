@extends('asesores.layout')

@push('styles')
<style>
    .page-wrapper {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: 100vh;
        padding: 2rem;
    }

    .form-container {
        max-width: 900px;
        margin: 0 auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 2rem;
    }

    .form-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .form-header h1 {
        font-size: 1.875rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .form-header p {
        color: #64748b;
        font-size: 0.95rem;
    }

    .form-section {
        margin-bottom: 2rem;
    }

    .form-section-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1e40af;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        font-family: inherit;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #1e40af;
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 2px solid #e2e8f0;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }

    .btn-primary {
        background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%);
        color: white;
        flex: 1;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #64748b;
        flex: 1;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="form-container">
        <div class="form-header">
            <h1>🎨 Nueva Cotización de Bordado</h1>
            <p>Completa los pasos 3 y 4 para crear una cotización de bordado en borrador</p>
        </div>

        <form id="cotizacionBordadoForm">
            @csrf

            <!-- Información de Cliente -->
            <div class="form-section">
                <div class="form-section-title">
                    <span class="material-symbols-rounded">info</span>
                    Información General
                </div>

                <div class="form-group">
                    <label for="cliente">Cliente *</label>
                    <input type="text" id="cliente" name="cliente" required placeholder="Nombre del cliente">
                </div>

                <div class="form-group">
                    <label for="asesora">Asesora *</label>
                    <input type="text" id="asesora" name="asesora" required value="{{ auth()->user()->name }}" readonly>
                </div>
            </div>

            <!-- Paso 3: Especificaciones Técnicas -->
            <div class="form-section">
                <div class="form-section-title">
                    <span class="material-symbols-rounded">design_services</span>
                    Paso 3: Especificaciones Técnicas
                </div>

                <div class="form-group">
                    <label for="observaciones_tecnicas">Detalles del Bordado *</label>
                    <textarea id="observaciones_tecnicas" name="observaciones_tecnicas" rows="5" required
                        placeholder="Describe los detalles técnicos del bordado:&#10;- Tipo de bordado&#10;- Ubicación&#10;- Colores&#10;- Tamaño estimado&#10;- Especificaciones especiales"></textarea>
                </div>
            </div>

            <!-- Paso 4: Presupuesto -->
            <div class="form-section">
                <div class="form-section-title">
                    <span class="material-symbols-rounded">price_check</span>
                    Paso 4: Presupuesto y Especificaciones
                </div>

                <div class="form-group">
                    <label for="especificaciones">Presupuesto y Detalles *</label>
                    <textarea id="especificaciones" name="especificaciones" rows="5" required
                        placeholder="Ingresa el presupuesto y especificaciones:&#10;- Valor por unidad&#10;- Cantidad mínima&#10;- Tiempo de ejecución&#10;- Términos de pago"></textarea>
                </div>

                <div class="form-group">
                    <label for="observaciones_generales">Observaciones Adicionales</label>
                    <textarea id="observaciones_generales" name="observaciones_generales" rows="3"
                        placeholder="Notas adicionales o comentarios especiales..."></textarea>
                </div>
            </div>

            <!-- Botones -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-rounded" style="font-size: 1.25rem;">save</span>
                    Guardar en Borrador
                </button>
                <a href="{{ route('asesores.cotizaciones-bordado.lista') }}" class="btn btn-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('cotizacionBordadoForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = {
        cliente: formData.get('cliente'),
        asesora: formData.get('asesora'),
        observaciones_tecnicas: formData.get('observaciones_tecnicas'),
        especificaciones: formData.get('especificaciones'),
        observaciones_generales: formData.get('observaciones_generales'),
    };

    try {
        const response = await fetch('{{ route("asesores.cotizaciones-bordado.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                title: '✅ Éxito',
                text: 'Cotización guardada en borrador',
                icon: 'success',
                confirmButtonText: 'Continuar'
            }).then(() => {
                window.location.href = result.redirect;
            });
        } else {
            Swal.fire({
                title: '❌ Error',
                text: result.message,
                icon: 'error'
            });
        }
    } catch (error) {
        Swal.fire({
            title: '❌ Error',
            text: 'Error al guardar: ' + error.message,
            icon: 'error'
        });
    }
});
</script>
@endsection
