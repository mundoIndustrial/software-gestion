<div style="background: white; padding: 60px; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <span class="material-symbols-rounded" style="font-size: 64px; color: #ccc; display: block; margin-bottom: 16px;">analytics</span>
    <h3 style="color: #666; margin-bottom: 8px;">No hay balanceo configurado</h3>
    <p style="color: #999; margin-bottom: 24px;">Crea un nuevo balanceo para esta prenda</p>
    <form action="{{ route('balanceo.create', $prenda->id) }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-weight: 500; box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);">
            <span class="material-symbols-rounded">add</span>
            Crear Balanceo
        </button>
    </form>
</div>
