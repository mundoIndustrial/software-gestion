<script>
function balanceoApp(balanceoId) {
    return {
        balanceoId: balanceoId,
        operaciones: @json($balanceo ? $balanceo->operaciones : []),
        editingCell: null,
        balanceo: {
            estado_completo: {{ $balanceo && $balanceo->estado_completo === null ? 'null' : ($balanceo && $balanceo->estado_completo ? 'true' : 'false') }}
        },
        parametros: {
            total_operarios: {{ $balanceo ? ($balanceo->total_operarios ?? 0) : 0 }},
            turnos: {{ $balanceo ? ($balanceo->turnos ?? 1) : 1 }},
            horas_por_turno: {{ $balanceo ? ($balanceo->horas_por_turno ?? 8) : 8 }}
        },
        metricas: {
            sam_total: {{ $balanceo ? ($balanceo->sam_total ?? 0) : 0 }},
            meta_teorica: {{ $balanceo ? ($balanceo->meta_teorica ?? 'null') : 'null' }},
            meta_real: {{ $balanceo ? ($balanceo->meta_real ?? 'null') : 'null' }},
            meta_sugerida_85: {{ $balanceo ? ($balanceo->meta_sugerida_85 ?? 'null') : 'null' }},
            tiempo_disponible_horas: {{ $balanceo ? ($balanceo->tiempo_disponible_horas ?? 0) : 0 }},
            tiempo_disponible_segundos: {{ $balanceo ? ($balanceo->tiempo_disponible_segundos ?? 0) : 0 }},
            operario_cuello_botella: '{{ $balanceo ? ($balanceo->operario_cuello_botella ?? '') : '' }}',
            tiempo_cuello_botella: {{ $balanceo ? ($balanceo->tiempo_cuello_botella ?? 'null') : 'null' }},
            sam_real: {{ $balanceo ? ($balanceo->sam_real ?? 'null') : 'null' }}
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

        copyColumn(columnName) {
            const values = this.operaciones.map(op => {
                const value = op[columnName];
                return value !== null && value !== undefined ? value : '-';
            });
            
            const text = values.join('\n');
            
            navigator.clipboard.writeText(text).then(() => {
                // Mostrar mensaje de éxito
                const successMsg = document.createElement('div');
                successMsg.textContent = `✓ Columna "${columnName}" copiada`;
                successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 12px 24px; border-radius: 8px; z-index: 10000; box-shadow: 0 4px 6px rgba(0,0,0,0.3); font-size: 14px;';
                document.body.appendChild(successMsg);
                
                setTimeout(() => {
                    successMsg.remove();
                }, 2000);
            }).catch(err => {
                console.error('Error al copiar:', err);
                alert('No se pudo copiar la columna');
            });
        },

        startEditingCell(operacion, field, event) {
            event.stopPropagation();
            this.editingCell = `${operacion.id}-${field}`;
            
            // Focus en el input después de que se muestre
            this.$nextTick(() => {
                const input = event.target.querySelector('input, select');
                if (input) {
                    input.focus();
                    if (input.tagName === 'INPUT' && input.type === 'text') {
                        input.select();
                    }
                }
            });
        },

        cancelEdit() {
            this.editingCell = null;
        },

        async saveCellSAM(operacion, newValue) {
            // Limpiar y validar el valor
            let cleanValue = newValue.toString().trim().replace(',', '.');
            
            // Convertir a número
            let numValue = parseFloat(cleanValue);
            
            // Validar que sea un número válido
            if (isNaN(numValue) || numValue < 0) {
                alert('Por favor ingresa un valor numérico válido');
                this.cancelEdit();
                return;
            }
            
            // Redondear a 1 decimal para consistencia
            numValue = Math.round(numValue * 10) / 10;
            
            // Si el valor no cambió, solo cancelar
            if (parseFloat(operacion.sam) === numValue) {
                this.cancelEdit();
                return;
            }

            try {
                const response = await fetch(`/balanceo/operacion/${operacion.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        sam: numValue
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    // Actualizar el valor en el array local
                    operacion.sam = numValue;
                    
                    // Actualizar métricas
                    this.updateMetricas(data.balanceo);
                    
                    // Mostrar feedback visual
                    this.showSuccessMessage('✓ SAM actualizado');
                } else {
                    alert('Error al guardar: ' + (data.message || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error saving SAM:', error);
                alert('Error al guardar el cambio');
            } finally {
                this.cancelEdit();
            }
        },

        async saveCell(operacion, field, newValue) {
            // Si el valor no cambió, solo cancelar
            if (operacion[field] == newValue) {
                this.cancelEdit();
                return;
            }

            try {
                const response = await fetch(`/balanceo/operacion/${operacion.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        [field]: newValue || null
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    // Actualizar el valor en el array local
                    operacion[field] = newValue;
                    
                    // Si se editó el SAM, actualizar métricas
                    if (field === 'sam') {
                        this.updateMetricas(data.balanceo);
                    }
                    
                    // Mostrar feedback visual
                    this.showSuccessMessage('✓ Guardado');
                } else {
                    alert('Error al guardar: ' + (data.message || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error saving cell:', error);
                alert('Error al guardar el cambio');
            } finally {
                this.cancelEdit();
            }
        },

        showSuccessMessage(message) {
            const successMsg = document.createElement('div');
            successMsg.textContent = message;
            successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 8px 16px; border-radius: 6px; z-index: 10000; box-shadow: 0 2px 4px rgba(0,0,0,0.2); font-size: 13px;';
            document.body.appendChild(successMsg);
            
            setTimeout(() => {
                successMsg.remove();
            }, 1500);
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

        async deleteBalanceo(id) {
            if (!confirm('¿Estás seguro de eliminar este balanceo? Se eliminarán todas las operaciones asociadas.')) return;
            
            try {
                const response = await fetch(`/balanceo/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    // Mostrar mensaje de éxito
                    const successMsg = document.createElement('div');
                    successMsg.textContent = '✓ Balanceo eliminado correctamente';
                    successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 12px 24px; border-radius: 8px; z-index: 10000; box-shadow: 0 4px 6px rgba(0,0,0,0.3);';
                    document.body.appendChild(successMsg);
                    
                    // Redirigir después de 1 segundo
                    setTimeout(() => {
                        window.location.href = `/balanceo/prenda/${data.prenda_id}`;
                    }, 1000);
                }
            } catch (error) {
                console.error('Error deleting balanceo:', error);
                alert('Error al eliminar el balanceo');
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
        },

        // Cambiar estado completo/incompleto
        // Ciclo: null (sin marcar) → true (completo) → false (incompleto) → null
        async toggleEstadoCompleto() {
            // Validar que existe un balanceo
            if (!this.balanceoId || this.balanceoId === null) {
                alert('No hay un balanceo activo para cambiar el estado');
                return;
            }

            try {
                // Determinar el siguiente estado
                let nuevoEstado;
                if (this.balanceo.estado_completo === null) {
                    nuevoEstado = true; // null → completo
                } else if (this.balanceo.estado_completo === true) {
                    nuevoEstado = false; // completo → incompleto
                } else {
                    nuevoEstado = null; // incompleto → sin marcar
                }

                const response = await fetch(`/balanceo/${this.balanceoId}/toggle-estado`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ estado: nuevoEstado })
                });

                const data = await response.json();
                
                if (data.success) {
                    this.balanceo.estado_completo = data.estado_completo;
                    this.showSuccessMessage(data.message);
                } else {
                    alert('Error al cambiar el estado');
                }
            } catch (error) {
                console.error('Error toggling estado:', error);
                alert('Error al cambiar el estado');
            }
        }
    }
}

// Función para eliminar prenda
async function deletePrenda(prendaId) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta prenda? Esta acción eliminará también su balanceo y todas las operaciones asociadas.')) {
        return;
    }

    try {
        const response = await fetch(`/balanceo/prenda/${prendaId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();
        
        if (data.success) {
            // Mostrar mensaje de éxito
            const successMsg = document.createElement('div');
            successMsg.textContent = '✓ Prenda eliminada correctamente';
            successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 12px 24px; border-radius: 8px; z-index: 10000; box-shadow: 0 4px 6px rgba(0,0,0,0.3); font-size: 14px;';
            document.body.appendChild(successMsg);
            
            // Redirigir al index después de 1 segundo
            setTimeout(() => {
                window.location.href = '/balanceo';
            }, 1000);
        } else {
            alert(data.message || 'Error al eliminar la prenda');
        }
    } catch (error) {
        console.error('Error deleting prenda:', error);
        alert('Error al eliminar la prenda');
    }
}
</script>
