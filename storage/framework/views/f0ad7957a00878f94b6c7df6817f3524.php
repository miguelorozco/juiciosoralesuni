<!-- Modal de Validación -->
<div class="modal fade" id="modalValidacion" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Validación del Diálogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div x-show="validacionResultado !== null">
                    <template x-if="validacionResultado && validacionResultado.valido">
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            El diálogo es válido y está listo para usar.
                        </div>
                    </template>

                    <template x-if="validacionResultado && !validacionResultado.valido && validacionResultado.errores && validacionResultado.errores.length > 0">
                        <div class="alert alert-danger">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>Errores encontrados:</h6>
                            <ul class="mb-0">
                                <template x-for="error in validacionResultado.errores" :key="error">
                                    <li x-text="error"></li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <template x-if="validacionResultado && validacionResultado.advertencias && validacionResultado.advertencias.length > 0">
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-info-circle me-2"></i>Advertencias:</h6>
                            <ul class="mb-0">
                                <template x-for="advertencia in validacionResultado.advertencias" :key="advertencia">
                                    <li x-text="advertencia"></li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/miguel/Documents/github/juiciosorales/resources/views/dialogos/v2/modals/validacion.blade.php ENDPATH**/ ?>