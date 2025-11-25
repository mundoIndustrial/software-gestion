/**
 * Script de carga de borrador completo
 * Carga técnicas, observaciones, imágenes y otros datos al editar una cotización
 */

document.addEventListener('DOMContentLoaded', function() {
    // Verificar si hay datos de cotización para cargar
    const cotizacionElement = document.querySelector('script[data-cotizacion]');
    if (!cotizacionElement) return;
    
    try {
        const cotizacion = JSON.parse(cotizacionElement.getAttribute('data-cotizacion'));
        console.log('📂 Cargando borrador completo:', cotizacion);
        
        // Cargar técnicas
        if (cotizacion.tecnicas && Array.isArray(cotizacion.tecnicas)) {
            console.log('🔧 Cargando técnicas:', cotizacion.tecnicas);
            cotizacion.tecnicas.forEach(tecnica => {
                const selector = document.getElementById('selector_tecnicas');
                if (selector) {
                    selector.value = tecnica;
                    agregarTecnica();
                }
            });
        }
        
        // Cargar observaciones técnicas
        if (cotizacion.observaciones_tecnicas) {
            const textareaObs = document.getElementById('observaciones_tecnicas');
            if (textareaObs) {
                textareaObs.value = cotizacion.observaciones_tecnicas;
                console.log('✅ Observaciones técnicas cargadas');
            }
        }
        
        // Cargar imágenes de bordado/estampado
        if (cotizacion.imagenes && Array.isArray(cotizacion.imagenes)) {
            console.log('📸 Cargando imágenes:', cotizacion.imagenes);
            const galeriaImagenes = document.getElementById('galeria_imagenes');
            if (galeriaImagenes) {
                cotizacion.imagenes.forEach(imagen => {
                    const div = document.createElement('div');
                    div.style.cssText = 'position: relative; width: 100px; height: 100px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';
                    div.innerHTML = `
                        <img src="${imagen}" style="width: 100%; height: 100%; object-fit: cover;">
                        <button type="button" onclick="this.parentElement.remove()" style="position: absolute; top: 2px; right: 2px; background: #f44336; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 16px; padding: 0; line-height: 1;">✕</button>
                    `;
                    galeriaImagenes.appendChild(div);
                });
            }
        }
        
        // Cargar observaciones generales
        if (cotizacion.observaciones_generales && Array.isArray(cotizacion.observaciones_generales)) {
            console.log('📝 Cargando observaciones generales:', cotizacion.observaciones_generales);
            const contenedor = document.getElementById('observaciones_lista');
            if (contenedor) {
                cotizacion.observaciones_generales.forEach(obs => {
                    let texto = '';
                    let tipo = 'texto';
                    let valor = '';
                    
                    if (typeof obs === 'string') {
                        texto = obs;
                    } else if (typeof obs === 'object' && obs.texto) {
                        texto = obs.texto || '';
                        tipo = obs.tipo || 'texto';
                        valor = obs.valor || '';
                    }
                    
                    if (!texto.trim()) return;
                    
                    const fila = document.createElement('div');
                    fila.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
                    fila.innerHTML = `
                        <input type="text" name="observaciones_generales[]" class="input-large" value="${texto}" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                        <div style="display: flex; gap: 5px; align-items: center; flex-shrink: 0;">
                            <button type="button" class="obs-toggle-btn" style="background: #3498db; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; flex-shrink: 0;">Texto</button>
                        </div>
                        <button type="button" onclick="this.closest('div').remove()" style="background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 1rem; flex-shrink: 0;">✕</button>
                    `;
                    contenedor.appendChild(fila);
                    
                    const toggleBtn = fila.querySelector('.obs-toggle-btn');
                    toggleBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (this.textContent === 'Texto') {
                            this.textContent = 'Checkbox';
                            this.style.background = '#95a5a6';
                        } else {
                            this.textContent = 'Texto';
                            this.style.background = '#3498db';
                        }
                    });
                });
            }
        }
        
        console.log('✅ Borrador cargado completamente');
        actualizarResumenFriendly();
    } catch (error) {
        console.error('❌ Error al cargar borrador:', error);
    }
});
