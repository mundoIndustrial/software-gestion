@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/tableros.css') }}">

<div class="tableros-container">
    <div style="max-width: 800px; margin: 0 auto;">
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

        <div style="background: rgba(255, 255, 255, 0.03); padding: 32px; border-radius: 12px; border: 1px solid rgba(255, 157, 88, 0.15);">
            <form action="{{ route('balanceo.prenda.update', $prenda->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; font-weight: 600;">Nombre de la Prenda *</label>
                    <input type="text" name="nombre" required value="{{ old('nombre', $prenda->nombre) }}"
                           style="width: 100%; padding: 12px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 8px; font-size: 15px; transition: all 0.3s; background: rgba(255, 157, 88, 0.05); color: white;"
                           placeholder="Ej: Camisa Polo Básica"
                           onfocus="this.style.borderColor='rgba(255, 157, 88, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                           onblur="this.style.borderColor='rgba(255, 157, 88, 0.3)'; this.style.boxShadow='none'">
                    @error('nombre')
                    <p style="color: #f5576c; font-size: 13px; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                        <span class="material-symbols-rounded" style="font-size: 16px;">error</span>
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; font-weight: 600;">Referencia</label>
                    <input type="text" name="referencia" value="{{ old('referencia', $prenda->referencia) }}"
                           style="width: 100%; padding: 12px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 8px; font-size: 15px; transition: all 0.3s; background: rgba(255, 157, 88, 0.05); color: white;"
                           placeholder="Ej: REF-001"
                           onfocus="this.style.borderColor='rgba(255, 157, 88, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                           onblur="this.style.borderColor='rgba(255, 157, 88, 0.3)'; this.style.boxShadow='none'">
                    @error('referencia')
                    <p style="color: #f5576c; font-size: 13px; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                        <span class="material-symbols-rounded" style="font-size: 16px;">error</span>
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; font-weight: 600;">Tipo de Prenda *</label>
                    <select name="tipo" required
                            style="width: 100%; padding: 12px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 8px; font-size: 15px; transition: all 0.3s; background: rgba(255, 157, 88, 0.05); color: white;"
                            onfocus="this.style.borderColor='rgba(255, 157, 88, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                            onblur="this.style.borderColor='rgba(255, 157, 88, 0.3)'; this.style.boxShadow='none'">
                        <option value="">Seleccione un tipo</option>
                        <option value="camisa" {{ old('tipo', $prenda->tipo) == 'camisa' ? 'selected' : '' }}>Camisa</option>
                        <option value="pantalon" {{ old('tipo', $prenda->tipo) == 'pantalon' ? 'selected' : '' }}>Pantalón</option>
                        <option value="polo" {{ old('tipo', $prenda->tipo) == 'polo' ? 'selected' : '' }}>Polo</option>
                        <option value="chaqueta" {{ old('tipo', $prenda->tipo) == 'chaqueta' ? 'selected' : '' }}>Chaqueta</option>
                        <option value="vestido" {{ old('tipo', $prenda->tipo) == 'vestido' ? 'selected' : '' }}>Vestido</option>
                        <option value="otro" {{ old('tipo', $prenda->tipo) == 'otro' ? 'selected' : '' }}>Otro</option>
                    </select>
                    @error('tipo')
                    <p style="color: #f5576c; font-size: 13px; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                        <span class="material-symbols-rounded" style="font-size: 16px;">error</span>
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; font-weight: 600;">Descripción</label>
                    <textarea name="descripcion" rows="4"
                              style="width: 100%; padding: 12px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 8px; font-size: 15px; resize: vertical; transition: all 0.3s; background: rgba(255, 157, 88, 0.05); color: white;"
                              placeholder="Descripción detallada de la prenda..."
                              onfocus="this.style.borderColor='rgba(255, 157, 88, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                              onblur="this.style.borderColor='rgba(255, 157, 88, 0.3)'; this.style.boxShadow='none'">{{ old('descripcion', $prenda->descripcion) }}</textarea>
                    @error('descripcion')
                    <p style="color: #f5576c; font-size: 13px; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                        <span class="material-symbols-rounded" style="font-size: 16px;">error</span>
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-weight: 500;">Imagen de la Prenda</label>
                    
                    @if($prenda->imagen)
                    <div style="margin-bottom: 12px;">
                        <p style="color: #94a3b8; font-size: 13px; margin-bottom: 8px;">Imagen actual:</p>
                        <img src="{{ asset($prenda->imagen) }}" alt="Imagen actual" style="max-width: 200px; border-radius: 8px; border: 2px solid rgba(255, 157, 88, 0.3);">
                    </div>
                    @endif
                    
                    <div style="border: 2px dashed rgba(255, 157, 88, 0.3); border-radius: 8px; padding: 24px; text-align: center; background: rgba(255, 157, 88, 0.05);">
                        <input type="file" name="imagen" id="imagen" accept="image/*" 
                               style="display: none;"
                               onchange="previewImage(event)">
                        <label for="imagen" style="cursor: pointer;">
                            <div id="preview-container">
                                <span class="material-symbols-rounded" style="font-size: 48px; color: #ff9d58; display: block; margin-bottom: 8px;">add_photo_alternate</span>
                                <p style="color: #94a3b8; margin: 0;">{{ $prenda->imagen ? 'Cambiar imagen' : 'Seleccionar imagen' }}</p>
                                <p style="color: #94a3b8; font-size: 13px; margin-top: 4px;">JPG, PNG, GIF (máx. 2MB)</p>
                            </div>
                            <img id="image-preview" style="max-width: 100%; max-height: 300px; display: none; border-radius: 8px; margin-top: 12px;">
                        </label>
                    </div>
                    @error('imagen')
                    <p style="color: #f5576c; font-size: 13px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <a href="{{ route('balanceo.show', $prenda->id) }}"
                       style="background: rgba(255, 255, 255, 0.1); color: #94a3b8; border: 1px solid rgba(255, 157, 88, 0.3); padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-block;">
                        Cancelar
                    </a>
                    <button type="submit"
                            style="background: #ff9d58; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: background 0.2s;"
                            onmouseover="this.style.background='#e88a47'" onmouseout="this.style.background='#ff9d58'">
                        <span class="material-symbols-rounded">save</span>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
