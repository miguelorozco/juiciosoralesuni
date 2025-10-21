<!-- Modal de Edición de Nodo -->
<div class="modal fade" id="modalNodo" tabindex="-1" x-ref="modalNodo">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-diagram-3 me-2"></i>
                    <span x-text="nodoEditando.id ? 'Editar Nodo' : 'Nuevo Nodo'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="guardarNodo()">
                    <!-- Información Básica -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-medium">Título del Nodo</label>
                            <input type="text" class="form-control" x-model="nodoEditando.titulo" 
                                   placeholder="Título del nodo" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Tipo</label>
                            <select class="form-select" x-model="nodoEditando.tipo">
                                <option value="inicio">Inicio</option>
                                <option value="desarrollo">Desarrollo</option>
                                <option value="decision">Decisión</option>
                                <option value="final">Final</option>
                            </select>
                        </div>
                    </div>

                    <!-- Rol -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Rol</label>
                            <select class="form-select" x-model="nodoEditando.rol_id" @change="actualizarRolSeleccionado()">
                                <option value="">Seleccionar rol...</option>
                                <template x-for="rol in rolesDisponibles" :key="rol.id">
                                    <option :value="rol.id" x-text="rol.nombre"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Orden</label>
                            <input type="number" class="form-control" x-model="nodoEditando.orden" 
                                   placeholder="0" min="0">
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="mb-4">
                        <label class="form-label fw-medium">Contenido del Diálogo</label>
                        <textarea class="form-control" rows="4" x-model="nodoEditando.contenido" 
                                  placeholder="Texto que dirá el personaje en este nodo..."></textarea>
                    </div>

                    <!-- Configuración -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" x-model="nodoEditando.es_inicial">
                                <label class="form-check-label fw-medium">
                                    Nodo Inicial
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" x-model="nodoEditando.es_final">
                                <label class="form-check-label fw-medium">
                                    Nodo Final
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Opciones de Respuesta -->
                    <div class="mb-4">
                        <label class="form-label fw-medium">Opciones de Respuesta (A, B, C, D)</label>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <p class="text-muted small mb-3">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Las opciones A, B, C, D se generan automáticamente. 
                                            Puedes editarlas haciendo clic en cada opción en el nodo.
                                        </p>
                                        <div class="opciones-preview">
                                            <div class="opcion-preview opcion-A">
                                                <div class="opcion-letra">A</div>
                                                <div class="opcion-texto">Opción A</div>
                                            </div>
                                            <div class="opcion-preview opcion-B">
                                                <div class="opcion-letra">B</div>
                                                <div class="opcion-texto">Opción B</div>
                                            </div>
                                            <div class="opcion-preview opcion-C">
                                                <div class="opcion-letra">C</div>
                                                <div class="opcion-texto">Opción C</div>
                                            </div>
                                            <div class="opcion-preview opcion-D">
                                                <div class="opcion-letra">D</div>
                                                <div class="opcion-texto">Opción D</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" @click="guardarNodo()">
                    <i class="bi bi-save me-1"></i>
                    <span x-text="nodoEditando.id ? 'Actualizar' : 'Crear'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.opciones-preview {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.opcion-preview {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.opcion-preview .opcion-letra {
    background: #007bff;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.opcion-preview.opcion-A .opcion-letra {
    background: #28a745;
}

.opcion-preview.opcion-B .opcion-letra {
    background: #ffc107;
    color: #000;
}

.opcion-preview.opcion-C .opcion-letra {
    background: #fd7e14;
}

.opcion-preview.opcion-D .opcion-letra {
    background: #6f42c1;
}
</style>
