<div x-show="showAddModal" x-cloak style="position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px);" @click.self="showAddModal = false">
    <div style="background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%); border-radius: 24px; max-width: 900px; width: 75%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);">
        <!-- Header -->
        <div style="background: rgba(255, 107, 53, 0.1); backdrop-filter: blur(10px); padding: 24px 32px; border-bottom: 1px solid rgba(255, 107, 53, 0.2);">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #FF6B35 0%, #e55a2b 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);">
                    <span class="material-symbols-rounded" style="color: white; font-size: 28px;">edit_note</span>
                </div>
                <h3 style="margin: 0; font-size: 28px; font-weight: 700; color: white;" x-text="editingOperacion ? 'Editar Operación' : 'Nueva Operación'"></h3>
            </div>
        </div>

        <!-- Form Content -->
        <div style="padding: 32px;">
            <form @submit.prevent="saveOperacion()">
                <div style="background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);">
                    <h4 style="font-size: 18px; font-weight: 600; color: #1a202c; margin: 0 0 20px 0;">Datos de la Operación</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- Letra -->
                        <div>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 500; color: #1f1f1f; margin-bottom: 8px;">
                                <svg style="width: 18px; height: 18px; color: #3B82F6; stroke-width: 2;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Letra *
                            </label>
                            <input type="text" x-model="formData.letra" required
                                   style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; color: #2d3748; background: #f7fafc; transition: all 0.3s ease; text-transform: uppercase;"
                                   onfocus="this.style.borderColor='#3B82F6'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f7fafc'; this.style.boxShadow='none'">
                        </div>

                        <!-- SAM -->
                        <div>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 500; color: #1f1f1f; margin-bottom: 8px;">
                                <svg style="width: 18px; height: 18px; color: #3B82F6; stroke-width: 2;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                SAM (segundos) *
                            </label>
                            <input type="number" step="0.01" x-model="formData.sam" required
                                   style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; color: #2d3748; background: #f7fafc; transition: all 0.3s ease;"
                                   onfocus="this.style.borderColor='#3B82F6'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f7fafc'; this.style.boxShadow='none'">
                        </div>

                        <!-- Operación -->
                        <div style="grid-column: 1 / -1;">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 500; color: #1f1f1f; margin-bottom: 8px;">
                                <svg style="width: 18px; height: 18px; color: #3B82F6; stroke-width: 2;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Operación *
                            </label>
                            <textarea x-model="formData.operacion" required rows="2"
                                      style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; color: #2d3748; background: #f7fafc; transition: all 0.3s ease; resize: vertical; text-transform: uppercase;"
                                      onfocus="this.style.borderColor='#3B82F6'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                                      onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f7fafc'; this.style.boxShadow='none'"></textarea>
                        </div>

                        <!-- Precedencia -->
                        <div>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 500; color: #1f1f1f; margin-bottom: 8px;">
                                <svg style="width: 18px; height: 18px; color: #3B82F6; stroke-width: 2;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M9 5l7 7-7 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Precedencia
                            </label>
                            <input type="text" x-model="formData.precedencia"
                                   style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; color: #2d3748; background: #f7fafc; transition: all 0.3s ease; text-transform: uppercase;"
                                   onfocus="this.style.borderColor='#3B82F6'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f7fafc'; this.style.boxShadow='none'">
                        </div>

                        <!-- Máquina -->
                        <div>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 500; color: #1f1f1f; margin-bottom: 8px;">
                                <svg style="width: 18px; height: 18px; color: #3B82F6; stroke-width: 2;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Máquina
                            </label>
                            <input type="text" x-model="formData.maquina"
                                   style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; color: #2d3748; background: #f7fafc; transition: all 0.3s ease; text-transform: uppercase;"
                                   onfocus="this.style.borderColor='#3B82F6'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f7fafc'; this.style.boxShadow='none'">
                        </div>

                        <!-- Operario -->
                        <div>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 500; color: #1f1f1f; margin-bottom: 8px;">
                                <svg style="width: 18px; height: 18px; color: #3B82F6; stroke-width: 2;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Operario
                            </label>
                            <input type="text" x-model="formData.operario"
                                   style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; color: #2d3748; background: #f7fafc; transition: all 0.3s ease; text-transform: uppercase;"
                                   onfocus="this.style.borderColor='#3B82F6'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f7fafc'; this.style.boxShadow='none'">
                        </div>

                        <!-- OP -->
                        <div>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 500; color: #1f1f1f; margin-bottom: 8px;">
                                <svg style="width: 18px; height: 18px; color: #3B82F6; stroke-width: 2;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <rect x="4" y="2" width="16" height="20" rx="2" stroke-width="2"/>
                                    <line x1="8" y1="6" x2="16" y2="6" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                OP
                            </label>
                            <input type="text" x-model="formData.op"
                                   style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; color: #2d3748; background: #f7fafc; transition: all 0.3s ease; text-transform: uppercase;"
                                   onfocus="this.style.borderColor='#3B82F6'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f7fafc'; this.style.boxShadow='none'">
                        </div>

                        <!-- Sección -->
                        <div>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 500; color: #1f1f1f; margin-bottom: 8px;">
                                <svg style="width: 18px; height: 18px; color: #3B82F6; stroke-width: 2;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Sección *
                            </label>
                            <select x-model="formData.seccion" required
                                    style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; color: #2d3748; background: #f7fafc; transition: all 0.3s ease;"
                                    onfocus="this.style.borderColor='#3B82F6'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                                    onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f7fafc'; this.style.boxShadow='none'">
                                <option value="DEL">Delantero (DEL)</option>
                                <option value="TRAS">Trasero (TRAS)</option>
                                <option value="ENS">Ensamble (ENS)</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px;">
                    <button type="button" @click="showAddModal = false"
                            style="display: flex; align-items: center; gap: 8px; padding: 12px 24px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; background: #e2e8f0; color: #4a5568;"
                            onmouseover="this.style.background='#cbd5e0'" onmouseout="this.style.background='#e2e8f0'">
                        <svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Cancelar
                    </button>
                    
                    <div style="display: flex; gap: 12px;">
                        <button type="button" @click="saveOperacion(true)"
                                style="display: flex; align-items: center; gap: 8px; padding: 12px 24px; border: 1px solid #FF6B35; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; background: rgba(255, 107, 53, 0.1); color: #FF6B35;"
                                onmouseover="this.style.background='rgba(255, 107, 53, 0.2)'" onmouseout="this.style.background='rgba(255, 107, 53, 0.1)'">
                            <svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            Guardar y Agregar Otra
                        </button>
                        
                        <button type="submit"
                                style="display: flex; align-items: center; gap: 8px; padding: 12px 24px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; background: linear-gradient(135deg, #FF6B35 0%, #e55a2b 100%); color: white; box-shadow: 0 4px 6px rgba(255, 107, 53, 0.3);"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(255, 107, 53, 0.4)'" 
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(255, 107, 53, 0.3)'">
                            <svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M5 13l4 4L19 7" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            Guardar y Cerrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
</style>
