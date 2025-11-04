<div style="background: var(--color-bg-sidebar); padding: 60px; border-radius: 12px; text-align: center; box-shadow: 0 1px 3px var(--color-shadow); border: 1px solid var(--color-border-hr);">
    <span class="material-symbols-rounded" style="font-size: 64px; color: var(--color-text-placeholder); opacity: 0.5; display: block; margin-bottom: 16px;">analytics</span>
    <h3 style="color: var(--color-text-primary); margin-bottom: 8px; font-weight: 700;">No hay balanceo configurado</h3>
    <p style="color: var(--color-text-placeholder); margin-bottom: 24px;">Crea un nuevo balanceo para esta prenda</p>
    <form action="{{ route('balanceo.create', $prenda->id) }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" style="background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-weight: 500; box-shadow: 0 4px 6px rgba(255, 157, 88, 0.3); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(255, 157, 88, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(255, 157, 88, 0.3)'">
            <span class="material-symbols-rounded">add</span>
            Crear Balanceo
        </button>
    </form>
</div>
