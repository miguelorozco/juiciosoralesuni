<!-- Modal de Validación de Estructura -->
<div class="modal fade" id="modalValidacion" tabindex="-1" x-ref="modalValidacion">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle me-2"></i>
                    Validación de Estructura del Diálogo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Resumen de Validación -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <div class="h3 mb-0" :class="erroresValidacionComputed.length === 0 ? 'text-success' : 'text-danger'">
                                    <i :class="erroresValidacionComputed.length === 0 ? 'bi bi-check-circle' : 'bi bi-exclamation-triangle'"></i>
                                </div>
                                <small class="text-muted">Estado</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <div class="h3 mb-0 text-primary" x-text="nodos.length"></div>
                                <small class="text-muted">Nodos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <div class="h3 mb-0 text-info" x-text="totalRespuestas"></div>
                                <small class="text-muted">Respuestas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <div class="h3 mb-0" :class="complejidadColor" x-text="nivelComplejidad"></div>
                                <small class="text-muted">Complejidad</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Errores de Validación -->
                <div x-show="erroresValidacionComputed.length > 0" class="mb-4">
                    <h6 class="fw-bold text-danger mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Errores Encontrados
                    </h6>
                    <div class="list-group">
                        <template x-for="(error, index) in erroresValidacionComputed" :key="index">
                            <div class="list-group-item list-group-item-danger">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-x-circle me-2"></i>
                                    <span x-text="error"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Advertencias -->
                <div x-show="advertenciasValidacionComputed.length > 0" class="mb-4">
                    <h6 class="fw-bold text-warning mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Advertencias
                    </h6>
                    <div class="list-group">
                        <template x-for="(advertencia, index) in advertenciasValidacionComputed" :key="index">
                            <div class="list-group-item list-group-item-warning">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <span x-text="advertencia"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Información de Estructura -->
                <div class="mb-4">
                    <h6 class="fw-bold text-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Información de Estructura
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Nodos Iniciales</h6>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-success me-2" x-text="nodos.filter(n => n.es_inicial).length"></span>
                                        <span class="small text-muted">nodos marcados como iniciales</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Nodos Finales</h6>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-danger me-2" x-text="nodos.filter(n => n.es_final).length"></span>
                                        <span class="small text-muted">nodos marcados como finales</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recomendaciones -->
                <div x-show="recomendacionesValidacionComputed.length > 0" class="mb-4">
                    <h6 class="fw-bold text-primary mb-3">
                        <i class="bi bi-lightbulb me-2"></i>
                        Recomendaciones
                    </h6>
                    <div class="list-group">
                        <template x-for="(recomendacion, index) in recomendacionesValidacionComputed" :key="index">
                            <div class="list-group-item list-group-item-info">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-lightbulb me-2"></i>
                                    <span x-text="recomendacion"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Estadísticas Detalladas -->
                <div class="mb-4">
                    <h6 class="fw-bold text-secondary mb-3">
                        <i class="bi bi-graph-up me-2"></i>
                        Estadísticas Detalladas
                    </h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="h4 mb-0 text-primary" x-text="nodosPorRol.length"></div>
                                    <small class="text-muted">Roles Utilizados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="h4 mb-0 text-success" x-text="promedioRespuestasPorNodo"></div>
                                    <small class="text-muted">Promedio Respuestas/Nodo</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="h4 mb-0 text-info" x-text="profundidadMaxima"></div>
                                    <small class="text-muted">Profundidad Máxima</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>
                    Cerrar
                </button>
                <button type="button" class="btn btn-warning" @click="validarEstructura()">
                    <i class="bi bi-arrow-clockwise me-1"></i>
                    Revalidar
                </button>
                <button type="button" class="btn btn-primary" @click="exportarReporteValidacion()" 
                        x-show="erroresValidacionComputed.length === 0">
                    <i class="bi bi-download me-1"></i>
                    Exportar Reporte
                </button>
            </div>
        </div>
    </div>
</div>
