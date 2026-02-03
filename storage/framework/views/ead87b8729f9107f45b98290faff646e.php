<?php $__env->startSection('title', 'Detalles de Sesión'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="bi bi-eye me-2"></i>
                        <?php echo e($sesion->nombre); ?>

                    </h1>
                    <p class="text-muted mb-0"><?php echo e($sesion->descripcion); ?></p>
                </div>
                <div>
                    <a href="<?php echo e(route('sesiones.edit', $sesion)); ?>" class="btn btn-outline-primary me-2">
                        <i class="bi bi-pencil-square me-2"></i>
                        Editar
                    </a>
                    <a href="<?php echo e(route('sesiones.index')); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información General -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Información General
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Tipo de Juicio</h6>
                            <p class="mb-3">
                                <?php switch($sesion->tipo):
                                    case ('civil'): ?>
                                        ⚖️ Civil
                                        <?php break; ?>
                                    <?php case ('penal'): ?>
                                        🔨 Penal
                                        <?php break; ?>
                                    <?php case ('laboral'): ?>
                                        💼 Laboral
                                        <?php break; ?>
                                    <?php case ('administrativo'): ?>
                                        📋 Administrativo
                                        <?php break; ?>
                                <?php endswitch; ?>
                            </p>
                            
                            <h6>Estado</h6>
                            <p class="mb-3">
                                <?php switch($sesion->estado):
                                    case ('programada'): ?>
                                        <span class="badge bg-secondary">📅 Programada</span>
                                        <?php break; ?>
                                    <?php case ('iniciada'): ?>
                                        <span class="badge bg-primary">▶️ Iniciada</span>
                                        <?php break; ?>
                                    <?php case ('en_curso'): ?>
                                        <span class="badge bg-info">🔄 En Curso</span>
                                        <?php break; ?>
                                    <?php case ('pausada'): ?>
                                        <span class="badge bg-warning">⏸️ Pausada</span>
                                        <?php break; ?>
                                    <?php case ('finalizada'): ?>
                                        <span class="badge bg-success">✅ Finalizada</span>
                                        <?php break; ?>
                                    <?php case ('cancelada'): ?>
                                        <span class="badge bg-danger">❌ Cancelada</span>
                                        <?php break; ?>
                                <?php endswitch; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Fecha de Inicio</h6>
                            <p class="mb-3"><?php echo e($sesion->fecha_inicio ? $sesion->fecha_inicio->format('d/m/Y H:i') : 'No programada'); ?></p>
                            
                            <h6>Máximo de Participantes</h6>
                            <p class="mb-3"><?php echo e($sesion->max_participantes); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-check me-2"></i>
                        Instructor
                    </h5>
                </div>
                <div class="card-body text-center">
                    <i class="bi bi-person-circle text-primary" style="font-size: 3rem;"></i>
                    <h5 class="mt-2"><?php echo e($sesion->instructor->name); ?></h5>
                    <p class="text-muted"><?php echo e($sesion->instructor->email); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Asignaciones de Roles -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i>
                        Asignaciones de Roles (<?php echo e($sesion->asignaciones->count()); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if($sesion->asignaciones->count() > 0): ?>
                        <div class="row">
                            <?php $__currentLoopData = $sesion->asignaciones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asignacion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card border-left-primary">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <i class="bi bi-<?php echo e($asignacion->rolDisponible->icono ?? 'person'); ?> text-primary" style="font-size: 2rem;"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1"><?php echo e($asignacion->rolDisponible->nombre); ?></h6>
                                                    <?php if($asignacion->usuario): ?>
                                                        <p class="mb-1 text-success">
                                                            <i class="bi bi-person-circle me-1"></i>
                                                            <?php echo e($asignacion->usuario->name); ?>

                                                        </p>
                                                        <small class="text-muted"><?php echo e($asignacion->usuario->email); ?></small>
                                                    <?php else: ?>
                                                        <p class="mb-1 text-muted">
                                                            <i class="bi bi-person-x me-1"></i>
                                                            Sin asignar
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <?php if($asignacion->confirmado): ?>
                                                        <span class="badge bg-success">✅</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">⏳</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            No hay asignaciones de roles para esta sesión.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                <i class="bi bi-calendar me-1"></i>
                                Creada: <?php echo e($sesion->created_at->format('d/m/Y H:i')); ?>

                                <?php if($sesion->updated_at != $sesion->created_at): ?>
                                    | <i class="bi bi-pencil me-1"></i>
                                    Modificada: <?php echo e($sesion->updated_at->format('d/m/Y H:i')); ?>

                                <?php endif; ?>
                            </small>
                        </div>
                        <div>
                            <?php if($sesion->estado === 'programada'): ?>
                                <button type="button" class="btn btn-success me-2" onclick="iniciarSesion()">
                                    <i class="bi bi-play-circle me-2"></i>
                                    Iniciar Sesión
                                </button>
                            <?php elseif($sesion->estado === 'iniciada'): ?>
                                <button type="button" class="btn btn-info me-2" onclick="continuarSesion()">
                                    <i class="bi bi-play-fill me-2"></i>
                                    Continuar
                                </button>
                            <?php elseif($sesion->estado === 'en_curso'): ?>
                                <button type="button" class="btn btn-warning me-2" onclick="pausarSesion()">
                                    <i class="bi bi-pause-circle me-2"></i>
                                    Pausar
                                </button>
                                <button type="button" class="btn btn-danger me-2" onclick="finalizarSesion()">
                                    <i class="bi bi-stop-circle me-2"></i>
                                    Finalizar
                                </button>
                            <?php endif; ?>
                            
                            <?php if($sesion->estado !== 'finalizada' && $sesion->estado !== 'cancelada'): ?>
                                <button type="button" class="btn btn-outline-danger me-2" onclick="cancelarSesion()">
                                    <i class="bi bi-x-circle me-2"></i>
                                    Cancelar
                                </button>
                            <?php endif; ?>
                            
                            <?php if(in_array($sesion->estado, ['programada', 'en_curso'])): ?>
                                <button type="button" class="btn btn-primary" onclick="generateUnityLink()">
                                    <i class="bi bi-controller me-2"></i>
                                    Entrar a Unity
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function iniciarSesion() {
    if (confirm('¿Estás seguro de que quieres iniciar esta sesión?')) {
        fetch('<?php echo e(route("sesiones.iniciar", $sesion)); ?>', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al iniciar la sesión: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al iniciar la sesión');
        });
    }
}

function finalizarSesion() {
    if (confirm('¿Estás seguro de que quieres finalizar esta sesión? Esta acción no se puede deshacer.')) {
        fetch('<?php echo e(route("sesiones.finalizar", $sesion)); ?>', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al finalizar la sesión: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al finalizar la sesión');
        });
    }
}

function cancelarSesion() {
    if (confirm('¿Estás seguro de que quieres cancelar esta sesión? Esta acción no se puede deshacer.')) {
        // Implementar cancelación
        console.log('Cancelar sesión');
    }
}

function continuarSesion() {
    // Implementar continuar sesión
    console.log('Continuar sesión');
}

function pausarSesion() {
    // Implementar pausar sesión
    console.log('Pausar sesión');
}

function generateUnityLink() {
    // Mostrar modal de selección de usuario
    showUserSelectionModal();
}

function showUserSelectionModal() {
    // Crear modal dinámicamente
    const modalHtml = `
        <div class="modal fade" id="unityUserModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-controller me-2"></i>
                            Seleccionar Usuario para Unity
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Selecciona el usuario que entrará a Unity:</p>
                        <div id="userList" class="list-group">
                            <!-- Los usuarios se cargarán aquí -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal existente si existe
    const existingModal = document.getElementById('unityUserModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Cargar usuarios asignados
    loadAssignedUsers();
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('unityUserModal'));
    modal.show();
}

function loadAssignedUsers() {
    const userList = document.getElementById('userList');
    userList.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
    
    // Obtener usuarios asignados de la sesión
    const sessionId = <?php echo e($sesion->id); ?>;
    
    fetch(`/api/sesiones/${sessionId}/usuarios-asignados`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUsers(data.data);
            } else {
                userList.innerHTML = '<div class="alert alert-warning">No hay usuarios asignados a esta sesión.</div>';
            }
        })
        .catch(error => {
            console.error('Error cargando usuarios:', error);
            userList.innerHTML = '<div class="alert alert-danger">Error cargando usuarios asignados.</div>';
        });
}

function displayUsers(users) {
    const userList = document.getElementById('userList');
    
    if (users.length === 0) {
        userList.innerHTML = '<div class="alert alert-warning">No hay usuarios asignados a esta sesión.</div>';
        return;
    }
    
    let html = '';
    users.forEach(user => {
        html += `
            <div class="list-group-item list-group-item-action" onclick="selectUserForUnity(${user.usuario_id}, '${user.usuario.name}', '${user.rol.nombre}')">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${user.usuario.name}</h6>
                    <small class="text-muted">${user.usuario.email}</small>
                </div>
                <p class="mb-1">
                    <i class="bi bi-${user.rol.icono || 'person'} me-2" style="color: ${user.rol.color || '#007bff'};"></i>
                    <strong>Rol:</strong> ${user.rol.nombre}
                </p>
                <small class="text-muted">${user.rol.descripcion}</small>
            </div>
        `;
    });
    
    userList.innerHTML = html;
}

function selectUserForUnity(userId, userName, roleName) {
    // Generar enlace de Unity para el usuario seleccionado
    const sessionId = <?php echo e($sesion->id); ?>;
    
    fetch('/api/unity-entry/generate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            user_id: userId,
            session_id: sessionId
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('unityUserModal'));
            modal.hide();
            
            // Mostrar enlace de Unity
            showUnityLink(data.data);
        } else {
            console.error('API Error:', data);
            let errorMessage = 'Error generando enlace: ' + data.message;
            
            // Mostrar mensajes más específicos según el código de error
            if (data.error_code) {
                switch(data.error_code) {
                    case 'NO_ASSIGNMENT':
                        errorMessage = 'El usuario seleccionado no tiene una asignación en esta sesión.';
                        break;
                    case 'SESSION_NOT_FOUND':
                        errorMessage = 'La sesión no existe o ha sido eliminada.';
                        break;
                    case 'USER_NOT_FOUND':
                        errorMessage = 'El usuario no existe en el sistema.';
                        break;
                    case 'ROLE_NOT_FOUND':
                        errorMessage = 'El rol asignado no existe.';
                        break;
                    case 'SESSION_NOT_ACTIVE':
                        errorMessage = 'La sesión no está activa. Debe estar programada o en curso.';
                        break;
                    case 'INTERNAL_ERROR':
                        errorMessage = 'Error interno del servidor. Por favor, intenta nuevamente.';
                        break;
                    default:
                        errorMessage = data.message || 'Error desconocido al generar el enlace.';
                }
            }
            
            showErrorModal(errorMessage);
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        showErrorModal('Error generando enlace de Unity: ' + error.message);
    });
}

function showErrorModal(message) {
    // Crear modal de error
    const errorModalHtml = `
        <div class="modal fade" id="errorModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Error generando enlace de Unity
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <p class="mb-0">${message}</p>
                        </div>
                        <p>Por favor, intenta nuevamente o contacta al administrador si el problema persiste.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="retryUnityLinkGeneration()">Reintentar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal de error existente si existe
    const existingErrorModal = document.getElementById('errorModal');
    if (existingErrorModal) {
        existingErrorModal.remove();
    }
    
    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', errorModalHtml);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('errorModal'));
    modal.show();
}

function retryUnityLinkGeneration() {
    // Cerrar modal de error
    const errorModal = bootstrap.Modal.getInstance(document.getElementById('errorModal'));
    errorModal.hide();
    
    // Reabrir modal de selección de usuario
    const userModal = new bootstrap.Modal(document.getElementById('unityUserModal'));
    userModal.show();
}

function showUnityLink(data) {
    // Crear modal con el enlace de Unity
    const modalHtml = `
        <div class="modal fade" id="unityLinkModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-controller me-2"></i>
                            Enlace de Unity Generado
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">Usuario: ${data.user.name}</h6>
                            <p class="mb-0"><strong>Rol:</strong> ${data.role.nombre}</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Enlace de Unity:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="unityLink" value="${data.unity_entry_url}" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyUnityLink()">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <a href="${data.unity_entry_url}" target="_blank" class="btn btn-success btn-lg">
                                <i class="bi bi-play-circle me-2"></i>
                                Abrir Unity
                            </a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal existente si existe
    const existingModal = document.getElementById('unityLinkModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('unityLinkModal'));
    modal.show();
}

function copyUnityLink() {
    const linkInput = document.getElementById('unityLink');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // Para dispositivos móviles
    
    try {
        document.execCommand('copy');
        // Mostrar notificación de copiado
        const button = event.target.closest('button');
        const originalIcon = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check"></i>';
        button.classList.add('btn-success');
        button.classList.remove('btn-outline-secondary');
        
        setTimeout(() => {
            button.innerHTML = originalIcon;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 2000);
    } catch (err) {
        console.error('Error copiando enlace:', err);
    }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/miguel/Documents/github/juiciosorales/resources/views/sesiones/show.blade.php ENDPATH**/ ?>