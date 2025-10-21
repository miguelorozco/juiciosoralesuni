<!-- Modal de Edición de Respuesta -->
<div class="modal fade" id="modalRespuesta" tabindex="-1" x-ref="modalRespuesta">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-chat-dots me-2"></i>
                    <span x-text="respuestaEditandoComputed.id ? 'Editar Respuesta' : 'Nueva Respuesta'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="guardarRespuesta()">
                    <!-- Texto de la Respuesta -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Texto de la Respuesta</label>
                        <input type="text" class="form-control" 
                               x-model="respuestaEditandoComputed.texto" 
                               placeholder="Ej: Sí, estoy de acuerdo"
                               required>
                    </div>
                    
                    <!-- Descripción -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Descripción</label>
                        <textarea class="form-control" rows="2" x-model="respuestaEditandoComputed.descripcion" 
                                  placeholder="Descripción adicional de la respuesta..."></textarea>
                    </div>
                    
                    <!-- Configuración -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Orden</label>
                            <input type="number" class="form-control" x-model="respuestaEditandoComputed.orden" 
                                   placeholder="0" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Puntuación</label>
                            <input type="number" class="form-control" x-model="respuestaEditandoComputed.puntuacion" 
                                   placeholder="0" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Color</label>
                            <input type="color" class="form-control form-control-color" 
                                   x-model="respuestaEditandoComputed.color" value="#007bff">
                        </div>
                    </div>
                    
                    <!-- Nodo Siguiente -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Nodo Siguiente</label>
                        <select class="form-select" x-model="respuestaEditandoComputed.nodo_siguiente_id">
                            <option value="">Seleccionar nodo siguiente...</option>
                            <template x-for="nodo in nodosDisponibles" :key="nodo.id">
                                <option :value="nodo.id" x-text="nodo.titulo || 'Sin título'"></option>
                            </template>
                        </select>
                    </div>
                    
                    <!-- Condiciones -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Condiciones</label>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label small">Variable</label>
                                        <input type="text" class="form-control form-control-sm" 
                                               placeholder="nombre_variable">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Operador</label>
                                        <select class="form-select form-select-sm">
                                            <option value="=">=</option>
                                            <option value="!=">!=</option>
                                            <option value=">">></option>
                                            <option value="<"><</option>
                                            <option value=">=">>=</option>
                                            <option value="<="><=</option>
                                            <option value="in">En</option>
                                            <option value="not_in">No en</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Valor</label>
                                        <input type="text" class="form-control form-control-sm" 
                                               placeholder="valor">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">&nbsp;</label>
                                        <button type="button" class="btn btn-outline-primary btn-sm w-100">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Consecuencias -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Consecuencias</label>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label small">Variable</label>
                                        <input type="text" class="form-control form-control-sm" 
                                               placeholder="nombre_variable">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Operación</label>
                                        <select class="form-select form-select-sm">
                                            <option value="set">Establecer</option>
                                            <option value="add">Sumar</option>
                                            <option value="subtract">Restar</option>
                                            <option value="multiply">Multiplicar</option>
                                            <option value="divide">Dividir</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Valor</label>
                                        <input type="text" class="form-control form-control-sm" 
                                               placeholder="valor">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">&nbsp;</label>
                                        <button type="button" class="btn btn-outline-primary btn-sm w-100">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estado -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" x-model="respuestaEditandoComputed.activo">
                            <label class="form-check-label fw-medium">
                                Respuesta Activa
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger" @click="eliminarRespuesta()" 
                        x-show="respuestaEditandoComputed.id">
                    <i class="bi bi-trash me-1"></i>
                    Eliminar
                </button>
                <button type="button" class="btn btn-primary" @click="guardarRespuesta()">
                    <i class="bi bi-save me-1"></i>
                    <span x-text="respuestaEditandoComputed.id ? 'Actualizar' : 'Crear'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
