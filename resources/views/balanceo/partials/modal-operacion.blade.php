<div x-show="showAddModal" x-cloak style="position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px);" @click.self="showAddModal = false">
    <div style="background: rgba(255, 255, 255, 0.03); padding: 32px; border-radius: 16px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); border: 1px solid rgba(255, 157, 88, 0.15);">
        <h3 style="margin: 0 0 24px 0; font-size: 24px; color: white; display: flex; align-items: center; gap: 10px;">
            <span class="material-symbols-rounded" style="color: #ff9d58;">edit_note</span>
            <span x-text="editingOperacion ? 'Editar Operación' : 'Nueva Operación'"></span>
        </h3>

        <form @submit.prevent="saveOperacion()">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; font-weight: 600;">Letra *</label>
                    <input type="text" x-model="formData.letra" required
                           style="width: 100%; padding: 12px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 8px; font-size: 15px; transition: all 0.3s; background: rgba(255, 157, 88, 0.05); color: white;"
                           onfocus="this.style.borderColor='rgba(255, 157, 88, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                           onblur="this.style.borderColor='rgba(255, 157, 88, 0.3)'; this.style.boxShadow='none'">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; font-weight: 600;">SAM (segundos) *</label>
                    <input type="number" step="0.01" x-model="formData.sam" required
                           style="width: 100%; padding: 12px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 8px; font-size: 15px; transition: all 0.3s; background: rgba(255, 157, 88, 0.05); color: white;"
                           onfocus="this.style.borderColor='rgba(255, 157, 88, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                           onblur="this.style.borderColor='rgba(255, 157, 88, 0.3)'; this.style.boxShadow='none'">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; font-weight: 600;">Operación *</label>
                    <textarea x-model="formData.operacion" required rows="2"
                              style="width: 100%; padding: 12px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 8px; font-size: 15px; transition: all 0.3s; background: rgba(255, 157, 88, 0.05); color: white;"
                              onfocus="this.style.borderColor='rgba(255, 157, 88, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                              onblur="this.style.borderColor='rgba(255, 157, 88, 0.3)'; this.style.boxShadow='none'"></textarea>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; font-weight: 600;">Precedencia</label>
                    <input type="text" x-model="formData.precedencia"
                           style="width: 100%; padding: 12px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 8px; font-size: 15px; transition: all 0.3s; background: rgba(255, 157, 88, 0.05); color: white;"
                           onfocus="this.style.borderColor='rgba(255, 157, 88, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                           onblur="this.style.borderColor='rgba(255, 157, 88, 0.3)'; this.style.boxShadow='none'">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; font-weight: 600;">Máquina</label>
                    <input type="text" x-model="formData.maquina"
                           style="width: 100%; padding: 12px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 8px; font-size: 15px; transition: all 0.3s; background: rgba(255, 157, 88, 0.05); color: white;"
                           onfocus="this.style.borderColor='rgba(255, 157, 88, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                           onblur="this.style.borderColor='rgba(255, 157, 88, 0.3)'; this.style.boxShadow='none'">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; font-weight: 600;">Operario</label>
                    <input type="text" x-model="formData.operario"
                           style="width: 100%; padding: 12px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 8px; font-size: 15px; transition: all 0.3s; background: rgba(255, 157, 88, 0.05); color: white;"
                           onfocus="this.style.borderColor='rgba(255, 157, 88, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                           onblur="this.style.borderColor='rgba(255, 157, 88, 0.3)'; this.style.boxShadow='none'">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; font-weight: 600;">OP</label>
                    <input type="text" x-model="formData.op"
                           style="width: 100%; padding: 12px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 8px; font-size: 15px; transition: all 0.3s; background: rgba(255, 157, 88, 0.05); color: white;"
                           onfocus="this.style.borderColor='rgba(255, 157, 88, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                           onblur="this.style.borderColor='rgba(255, 157, 88, 0.3)'; this.style.boxShadow='none'">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; font-weight: 600;">Sección *</label>
                    <select x-model="formData.seccion" required
                            style="width: 100%; padding: 12px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 8px; font-size: 15px; transition: all 0.3s; background: rgba(255, 157, 88, 0.05); color: white;"
                            onfocus="this.style.borderColor='rgba(255, 157, 88, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                            onblur="this.style.borderColor='rgba(255, 157, 88, 0.3)'; this.style.boxShadow='none'">
                        <option value="DEL">Delantero (DEL)</option>
                        <option value="TRAS">Trasero (TRAS)</option>
                        <option value="ENS">Ensamble (ENS)</option>
                        <option value="OTRO">Otro</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; font-weight: 600;">Operario A</label>
                    <input type="text" x-model="formData.operario_a"
                           style="width: 100%; padding: 12px; border: 1px solid rgba(255, 157, 88, 0.3); border-radius: 8px; font-size: 15px; transition: all 0.3s; background: rgba(255, 157, 88, 0.05); color: white;"
                           onfocus="this.style.borderColor='rgba(255, 157, 88, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                           onblur="this.style.borderColor='rgba(255, 157, 88, 0.3)'; this.style.boxShadow='none'">
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                <button type="button" @click="showAddModal = false"
                        style="background: rgba(255, 255, 255, 0.1); color: #94a3b8; border: 1px solid rgba(255, 157, 88, 0.3); padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 500;">
                    Cancelar
                </button>
                <button type="submit"
                        style="background: #ff9d58; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 500; transition: background 0.2s; box-shadow: 0 4px 6px rgba(255, 157, 88, 0.3);"
                        onmouseover="this.style.background='#e88a47'" onmouseout="this.style.background='#ff9d58'">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
</style>
