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

        async saveOperacion() {
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
                    this.showAddModal = false;
                    this.resetForm();
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
        }
    }
}
</script>
