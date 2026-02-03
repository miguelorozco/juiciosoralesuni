<?php $__env->startSection('title', 'Entrada a Unity - Simulador de Juicios'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-play-circle me-2"></i>
                        Entrada a Unity - <?php echo e($assignment->sesion->nombre); ?>

                    </h4>
                </div>
                
                <div class="card-body">
                    <!-- Información de la Sesión -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-primary">
                                <i class="bi bi-calendar-event me-2"></i>
                                Información de la Sesión
                            </h5>
                            <div class="bg-light p-3 rounded">
                                <p><strong>Nombre:</strong> <?php echo e($assignment->sesion->nombre); ?></p>
                                <p><strong>Estado:</strong> 
                                    <span class="badge bg-<?php echo e($assignment->sesion->estado === 'en_curso' ? 'success' : 'warning'); ?>">
                                        <?php echo e(ucfirst($assignment->sesion->estado)); ?>

                                    </span>
                                </p>
                                <p><strong>Fecha:</strong> <?php echo e($assignment->sesion->fecha_inicio->format('d/m/Y H:i')); ?></p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="text-success">
                                <i class="bi bi-person-badge me-2"></i>
                                Tu Rol Asignado
                            </h5>
                            <div class="bg-light p-3 rounded">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-<?php echo e($assignment->rolDisponible->icono ?? 'person'); ?> me-2" 
                                       style="color: <?php echo e($assignment->rolDisponible->color ?? '#007bff'); ?>; font-size: 1.5rem;"></i>
                                    <h6 class="mb-0"><?php echo e($assignment->rolDisponible->nombre); ?></h6>
                                </div>
                                <p class="text-muted mb-0"><?php echo e($assignment->rolDisponible->descripcion); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Instrucciones -->
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle me-2"></i>
                            Instrucciones para Entrar a Unity
                        </h6>
                        <ol class="mb-0">
                            <li>Haz clic en el botón "Entrar a Unity"</li>
                            <li>Unity se abrirá en una nueva ventana</li>
                            <li>Serás autenticado automáticamente</li>
                            <li>Se cargará tu sesión y rol asignado</li>
                            <li>El diálogo comenzará automáticamente</li>
                        </ol>
                    </div>

                    <!-- Botón de Entrada -->
                    <div class="text-center mb-4">
                        <button id="enterUnityBtn" class="btn btn-success btn-lg px-5 py-3">
                            <i class="bi bi-play-circle me-2"></i>
                            Entrar a Unity
                        </button>
                    </div>

                    <!-- Estado de Conexión -->
                    <div id="connectionStatus" class="alert alert-secondary text-center" style="display: none;">
                        <div class="spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <span id="statusText">Conectando con Unity...</span>
                    </div>

                    <!-- Información Adicional -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Información del Usuario</h6>
                            <p class="mb-1"><strong>Nombre:</strong> <?php echo e($assignment->usuario->name); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo e($assignment->usuario->email); ?></p>
                            <p class="mb-0"><strong>Asignado:</strong> <?php echo e($assignment->fecha_asignacion->format('d/m/Y H:i')); ?></p>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-muted">Configuración del Sistema</h6>
                            <p class="mb-1"><strong>Token:</strong> <code><?php echo e(substr($token, 0, 20)); ?>...</code></p>
                            <p class="mb-1"><strong>Sesión ID:</strong> <?php echo e($assignment->sesion->id); ?></p>
                            <p class="mb-0"><strong>Rol ID:</strong> <?php echo e($assignment->rolDisponible->id); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer bg-light">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Conexión segura con Unity
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>
                                Token válido por 1 hora
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Error -->
<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Error de Conexión
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="errorMessage">Ha ocurrido un error al conectar con Unity.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="retryConnection()">Reintentar</button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const enterBtn = document.getElementById('enterUnityBtn');
    const connectionStatus = document.getElementById('connectionStatus');
    const statusText = document.getElementById('statusText');
    
    enterBtn.addEventListener('click', function() {
        enterUnity();
    });
});

function enterUnity() {
    const enterBtn = document.getElementById('enterUnityBtn');
    const connectionStatus = document.getElementById('connectionStatus');
    const statusText = document.getElementById('statusText');
    
    // Mostrar estado de conexión
    enterBtn.disabled = true;
    connectionStatus.style.display = 'block';
    statusText.textContent = 'Abriendo Unity...';
    
    // Abrir Unity en nueva ventana
    const unityUrl = '<?php echo e($unityUrl); ?>';
    const unityWindow = window.open(unityUrl, 'UnityWindow', 'width=1200,height=800,scrollbars=yes,resizable=yes');
    
    if (!unityWindow) {
        showError('No se pudo abrir Unity. Verifica que los pop-ups estén habilitados.');
        return;
    }
    
    // Monitorear la ventana de Unity
    const checkInterval = setInterval(function() {
        if (unityWindow.closed) {
            clearInterval(checkInterval);
            connectionStatus.style.display = 'none';
            enterBtn.disabled = false;
            statusText.textContent = 'Conectando con Unity...';
        }
    }, 1000);
    
    // Simular progreso de conexión
    setTimeout(function() {
        statusText.textContent = 'Cargando sesión...';
    }, 2000);
    
    setTimeout(function() {
        statusText.textContent = 'Configurando rol...';
    }, 4000);
    
    setTimeout(function() {
        statusText.textContent = 'Iniciando diálogo...';
    }, 6000);
    
    setTimeout(function() {
        connectionStatus.style.display = 'none';
        enterBtn.disabled = false;
        statusText.textContent = 'Conectando con Unity...';
    }, 8000);
}

function showError(message) {
    document.getElementById('errorMessage').textContent = message;
    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
    errorModal.show();
}

function retryConnection() {
    const errorModal = bootstrap.Modal.getInstance(document.getElementById('errorModal'));
    errorModal.hide();
    enterUnity();
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/miguel/Documents/github/juiciosorales/resources/views/unity/entry-page.blade.php ENDPATH**/ ?>