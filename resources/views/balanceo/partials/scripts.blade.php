<script>
function balanceoApp(balanceoId) {
    return {
        balanceoId: balanceoId,
        operaciones: @json($balanceo ? $balanceo->operaciones : []),
        parametros: {
            total_operarios: {{ $balanceo->total_operarios ?? 0 }},
            turnos: {{ $balanceo->turnos ?? 1 }},
            horas_por_turno: {{ $balanceo->horas_por_turno ?? 8 }}
        },
        metricas: {
            sam_total: {{ $balanceo->sam_total ?? 0 }},
            meta_teorica: {{ $balanceo->meta_teorica ?? 'null' }},
            meta_real: {{ $balanceo->meta_real ?? 'null' }},
            meta_sugerida_85: {{ $balanceo->meta_sugerida_85 ?? 'null' }},
            tiempo_disponible_horas: {{ $balanceo->tiempo_disponible_horas ?? 0 }},
            tiempo_disponible_segundos: {{ $balanceo->tiempo_disponible_segundos ?? 0 }},
            operario_cuello_botella: '{{ $balanceo->operario_cuello_botella ?? '' }}',
            tiempo_cuello_botella: {{ $balanceo->tiempo_cuello_botella ?? 'null' }},
            sam_real: {{ $balanceo->sam_real ?? 'null' }}
        },
        showAddModal: false,
        editingOperacion: null,
        pendingOperaciones: [],
        formData: {
            letra: '',
            operacion: '',
            precedencia: '',
            maquina: '',
            sam: '',
            operario: '',
            op: '',
            seccion: 'DEL',
            operario_a: '',
            orden: 0
        },

        getSectionColor(seccion) {
            const colors = {
                'DEL': '#667eea',
                'TRAS': '#f5576c',
                'ENS': '#43e97b',
                'OTRO': '#999'
            };
            return colors[seccion] || '#999';
        },

        async updateParametros() {
            try {
                const response = await fetch(`/balanceo/${this.balanceoId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.parametros)
                });
                
                const data = await response.json();
                if (data.success) {
                    this.updateMetricas(data.balanceo);
                }
            } catch (error) {
                console.error('Error updating parameters:', error);
            }
        },

        updateMetricas(balanceo) {
            this.metricas = {
                sam_total: balanceo.sam_total,
                meta_teorica: balanceo.meta_teorica,
                meta_real: balanceo.meta_real,
                meta_sugerida_85: balanceo.meta_sugerida_85,
                tiempo_disponible_horas: balanceo.tiempo_disponible_horas,
                tiempo_disponible_segundos: balanceo.tiempo_disponible_segundos,
                operario_cuello_botella: balanceo.operario_cuello_botella,
                tiempo_cuello_botella: balanceo.tiempo_cuello_botella,
                sam_real: balanceo.sam_real
            };
        },

        editOperacion(operacion) {
            this.editingOperacion = operacion.id;
            this.formData = { ...operacion };
            this.showAddModal = true;
        },

        resetForm() {
            this.formData = {
                letra: '',
                operacion: '',
                precedencia: '',
                maquina: '',
                sam: '',
                operario: '',
                op: '',
                seccion: 'DEL',
                operario_a: '',
                orden: this.operaciones.length
            };
            this.editingOperacion = null;
        },

        async saveOperacion(keepOpen = false) {
            try {
                const url = this.editingOperacion 
                    ? `/balanceo/operacion/${this.editingOperacion}`
                    : `/balanceo/${this.balanceoId}/operacion`;
                
                const method = this.editingOperacion ? 'PATCH' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.formData)
                });
                
                const data = await response.json();
                if (data.success) {
                    if (this.editingOperacion) {
                        const index = this.operaciones.findIndex(op => op.id === this.editingOperacion);
                        this.operaciones[index] = data.operacion;
                    } else {
                        this.operaciones.push(data.operacion);
                    }
                    
                    this.updateMetricas(data.balanceo);
                    
                    // Si keepOpen es true, solo resetear el formulario pero mantener el modal abierto
                    if (keepOpen && !this.editingOperacion) {
                        this.resetForm();
                        // Mostrar mensaje de éxito temporal
                        const successMsg = document.createElement('div');
                        successMsg.textContent = '✓ Operación guardada correctamente';
                        successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 12px 24px; border-radius: 8px; z-index: 10000; box-shadow: 0 4px 6px rgba(0,0,0,0.3);';
                        document.body.appendChild(successMsg);
                        setTimeout(() => successMsg.remove(), 2000);
                    } else {
                        this.showAddModal = false;
                        this.resetForm();
                    }
                }
            } catch (error) {
                console.error('Error saving operation:', error);
            }
        },

        async deleteOperacion(id) {
            if (!confirm('¿Estás seguro de eliminar esta operación?')) return;
            
            try {
                const response = await fetch(`/balanceo/operacion/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.operaciones = this.operaciones.filter(op => op.id !== id);
                    this.updateMetricas(data.balanceo);
                }
            } catch (error) {
                console.error('Error deleting operation:', error);
            }
        },

        // Agregar operación a la lista pendiente
        addOperacionToList() {
            // Validar campos requeridos
            if (!this.formData.letra || !this.formData.sam || !this.formData.operacion || !this.formData.seccion) {
                alert('Por favor completa los campos requeridos: Letra, SAM, Operación y Sección');
                return;
            }

            // Agregar a la lista pendiente
            this.pendingOperaciones.push({
                letra: this.formData.letra,
                operacion: this.formData.operacion,
                precedencia: this.formData.precedencia,
                maquina: this.formData.maquina,
                sam: parseFloat(this.formData.sam),
                operario: this.formData.operario,
                op: this.formData.op,
                seccion: this.formData.seccion,
                operario_a: this.formData.operario_a,
                orden: this.operaciones.length + this.pendingOperaciones.length
            });

            // Limpiar formulario
            this.resetForm();
        },

        // Eliminar operación de la lista pendiente
        removePendingOperacion(index) {
            this.pendingOperaciones.splice(index, 1);
        },

        // Limpiar toda la lista pendiente
        clearPendingList() {
            if (confirm('¿Estás seguro de limpiar toda la lista de operaciones pendientes?')) {
                this.pendingOperaciones = [];
            }
        },

        // Guardar todas las operaciones pendientes
        async saveAllOperaciones() {
            if (this.pendingOperaciones.length === 0) {
                alert('No hay operaciones pendientes para guardar');
                return;
            }

            try {
                let savedCount = 0;
                let failedCount = 0;

                for (const operacion of this.pendingOperaciones) {
                    try {
                        const response = await fetch(`/balanceo/${this.balanceoId}/operacion`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(operacion)
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.operaciones.push(data.operacion);
                            this.updateMetricas(data.balanceo);
                            savedCount++;
                        } else {
                            failedCount++;
                        }
                    } catch (error) {
                        console.error('Error saving operation:', error);
                        failedCount++;
                    }
                }

                // Mostrar mensaje de resultado
                if (savedCount > 0) {
                    const successMsg = document.createElement('div');
                    successMsg.textContent = `✓ ${savedCount} operación(es) guardada(s) correctamente${failedCount > 0 ? `. ${failedCount} fallaron.` : ''}`;
                    successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 12px 24px; border-radius: 8px; z-index: 10000; box-shadow: 0 4px 6px rgba(0,0,0,0.3);';
                    document.body.appendChild(successMsg);
                    setTimeout(() => successMsg.remove(), 3000);
                }

                // Limpiar lista y cerrar modal
                this.pendingOperaciones = [];
                this.showAddModal = false;
                this.resetForm();

            } catch (error) {
                console.error('Error saving operations:', error);
                alert('Error al guardar las operaciones');
            }
        }
    }
}
</script>
