@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/tableros.css') }}">

<div class="tableros-container">
    <div style="max-width: 900px; width: 70%; margin: 0 auto;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
            <a href="{{ route('balanceo.show', $prenda->id) }}" 
               style="color: #ff9d58; text-decoration: none; display: flex; align-items: center; transition: all 0.2s; padding: 8px; border-radius: 8px; background: rgba(255, 157, 88, 0.1);" 
               onmouseover="this.style.background='rgba(255, 157, 88, 0.2)'; this.style.transform='translateX(-5px)'" 
               onmouseout="this.style.background='rgba(255, 157, 88, 0.1)'; this.style.transform='translateX(0)'">
                <span class="material-symbols-rounded">arrow_back</span>
            </a>
            <h1 style="margin: 0; font-size: 28px; color: white; display: flex; align-items: center; gap: 10px;">
                <span class="material-symbols-rounded" style="color: #ff9d58;">edit</span>
                Editar Prenda
            </h1>
        </div>

        <div class="modern-modal-container" style="margin: 0;">
            <form action="{{ route('balanceo.prenda.update', $prenda->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="form-content">
                    <div class="section-card">
                        <h3 class="section-title">Información de la Prenda</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">
                                    <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                    Nombre de la Prenda *
                                </label>
                                <input type="text" name="nombre" required value="{{ old('nombre', $prenda->nombre) }}" class="form-input" placeholder="Ej: Camisa Polo Básica">
                                @error('nombre')
                                <p style="color: #f5576c; font-size: 13px; margin-top: 4px;">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <rect x="4" y="2" width="16" height="20" rx="2" stroke-width="2"/>
                                        <line x1="8" y1="6" x2="16" y2="6" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                    Referencia
                                </label>
                                <input type="text" name="referencia" value="{{ old('referencia', $prenda->referencia) }}" class="form-input" placeholder="Ej: REF-001">
                                @error('referencia')
                                <p style="color: #f5576c; font-size: 13px; margin-top: 4px;">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Tipo de Prenda *
                                </label>
                                <select name="tipo" required class="form-select">
                                    <option value="">Seleccione un tipo</option>
                                    <option value="camisa" {{ old('tipo', $prenda->tipo) == 'camisa' ? 'selected' : '' }}>Camisa</option>
                                    <option value="pantalon" {{ old('tipo', $prenda->tipo) == 'pantalon' ? 'selected' : '' }}>Pantalón</option>
                                    <option value="polo" {{ old('tipo', $prenda->tipo) == 'polo' ? 'selected' : '' }}>Polo</option>
                                    <option value="chaqueta" {{ old('tipo', $prenda->tipo) == 'chaqueta' ? 'selected' : '' }}>Chaqueta</option>
                                    <option value="vestido" {{ old('tipo', $prenda->tipo) == 'vestido' ? 'selected' : '' }}>Vestido</option>
                                    <option value="otro" {{ old('tipo', $prenda->tipo) == 'otro' ? 'selected' : '' }}>Otro</option>
                                </select>
                                @error('tipo')
                                <p style="color: #f5576c; font-size: 13px; margin-top: 4px;">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">
                                    <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                    Descripción
                                </label>
                                <textarea name="descripcion" rows="4" class="form-input" style="resize: vertical;" placeholder="Descripción detallada de la prenda...">{{ old('descripcion', $prenda->descripcion) }}</textarea>
                                @error('descripcion')
                                <p style="color: #f5576c; font-size: 13px; margin-top: 4px;">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="section-card">
                        <h3 class="section-title">Imagen de la Prenda</h3>
                        @if($prenda->imagen)
                        <div style="margin-bottom: 16px;">
                            <p style="color: #4a5568; font-size: 14px; margin-bottom: 8px; font-weight: 500;">Imagen actual:</p>
                            <img src="{{ asset($prenda->imagen) }}" alt="Imagen actual" style="max-width: 200px; border-radius: 10px; border: 2px solid #e2e8f0;">
                        </div>
                        @endif
                        
                        <div style="border: 2px dashed #e2e8f0; border-radius: 10px; padding: 24px; text-align: center; background: #f7fafc;">
                            <input type="file" name="imagen" id="imagen" accept="image/*" 
                                   style="display: none;"
                                   onchange="previewImage(event)">
                            <label for="imagen" style="cursor: pointer;">
                                <div id="preview-container">
                                    <span class="material-symbols-rounded" style="font-size: 48px; color: #ff9d58; display: block; margin-bottom: 8px;">add_photo_alternate</span>
                                    <p style="color: #4a5568; margin: 0; font-weight: 500;">{{ $prenda->imagen ? 'Cambiar imagen' : 'Seleccionar imagen' }}</p>
                                    <p style="color: #94a3b8; font-size: 13px; margin-top: 4px;">JPG, PNG, GIF (máx. 2MB)</p>
                                </div>
                                <img id="image-preview" style="max-width: 100%; max-height: 300px; display: none; border-radius: 10px; margin-top: 12px;">
                            </label>
                        </div>
                        @error('imagen')
                        <p style="color: #f5576c; font-size: 13px; margin-top: 8px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('balanceo.show', $prenda->id) }}" class="btn btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M5 13l4 4L19 7" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.modern-modal-container {
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    padding: 0;
}

.form-content {
    padding: 32px;
}

.section-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 20px 0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 500;
    color: #1f1f1fff;
    margin-bottom: 8px;
}

.label-icon {
    width: 18px;
    height: 18px;
    color: #ff9d58;
    stroke-width: 2;
}

.form-input,
.form-select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 16px;
    color: #2d3748;
    background: #f7fafc;
    transition: all 0.3s ease;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: #ff9d58;
    background: white;
    box-shadow: 0 0 0 3px rgba(255, 157, 88, 0.1);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
}

.btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn svg {
    width: 18px;
    height: 18px;
}

.btn-primary {
    background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(255, 157, 88, 0.4);
}

.btn-secondary {
    background: #e2e8f0;
    color: #4a5568;
}

.btn-secondary:hover {
    background: #cbd5e0;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-content {
        padding: 20px;
    }
}
</style>

<script>
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('image-preview').src = e.target.result;
            document.getElementById('image-preview').style.display = 'block';
            document.getElementById('preview-container').style.display = 'none';
        }
        reader.readAsDataURL(file);
    }
}
</script>

@endsection
