<?php $__env->startSection('title', 'Gestión de Sesiones'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="bi bi-calendar-event me-2"></i>
                        Gestión de Sesiones
                    </h1>
                    <p class="text-muted mb-0">Administra y participa en sesiones de juicios orales simulados</p>
                </div>
                <div>
                    <?php if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor'): ?>
                    <a class="btn btn-primary" href="<?php echo e(route('sesiones.create')); ?>">
                        <i class="bi bi-plus-circle me-2"></i>
                        Nueva Sesión
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen por estado -->
    <div class="row mb-4">
        <?php
            $estadoCards = [
                ['title' => 'Por iniciar', 'data' => $sesionesPorIniciar ?? collect(), 'icon' => 'clock', 'bg' => 'warning'],
                ['title' => 'Iniciadas', 'data' => $sesionesIniciadas ?? collect(), 'icon' => 'play-circle', 'bg' => 'success'],
                ['title' => 'Terminadas', 'data' => $sesionesTerminadas ?? collect(), 'icon' => 'flag', 'bg' => 'secondary'],
            ];
        ?>
        <?php $__currentLoopData = $estadoCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-<?php echo e($card['bg']); ?> text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-<?php echo e($card['icon']); ?> me-2"></i><?php echo e($card['title']); ?></span>
                        <span class="badge bg-light text-dark"><?php echo e($card['data']->count()); ?></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if($card['data']->count()): ?>
                        <ul class="list-group list-group-flush">
                            <?php $__currentLoopData = $card['data']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo e($item->nombre); ?></strong>
                                            <div class="text-muted small">
                                                <?php echo e($item->fecha_inicio?->format('d/m H:i') ?? 'Sin fecha'); ?> · <?php echo e(ucfirst($item->tipo)); ?>

                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="<?php echo e(route('sesiones.show', $item)); ?>" class="btn btn-sm btn-outline-info">Ver</a>
                                            <?php if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor'): ?>
                                            <a href="<?php echo e(route('sesiones.edit', $item)); ?>" class="btn btn-sm btn-outline-warning">Editar</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php else: ?>
                        <div class="p-3 text-muted small">No hay sesiones en este estado.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-calendar-event fs-2"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-white-50 small">Total Sesiones</div>
                            <div class="h4 mb-0" id="totalSesiones"><?php echo e($sesiones->total()); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-play-circle fs-2"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-white-50 small">En Curso</div>
                            <div class="h4 mb-0" id="enCurso"><?php echo e($sesiones->where('estado', 'en_curso')->count()); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-clock fs-2"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-white-50 small">Programadas</div>
                            <div class="h4 mb-0" id="programadas"><?php echo e($sesiones->where('estado', 'programada')->count()); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-people fs-2"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-white-50 small">Participantes</div>
                            <div class="h4 mb-0" id="participantes"><?php echo e($sesiones->sum('participantes_count')); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" action="<?php echo e(route('sesiones.index')); ?>" class="row g-3">
                        <div class="col-md-4">
                            <label for="buscar" class="form-label">Buscar</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="buscar" 
                                   name="buscar" 
                                   value="<?php echo e(request('buscar')); ?>" 
                                   placeholder="Nombre, descripción o instructor...">
                        </div>
                        <div class="col-md-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Todos</option>
                                <option value="programada" <?php echo e(request('estado') === 'programada' ? 'selected' : ''); ?>>Programada</option>
                                <option value="en_curso" <?php echo e(request('estado') === 'en_curso' ? 'selected' : ''); ?>>En Curso</option>
                                <option value="finalizada" <?php echo e(request('estado') === 'finalizada' ? 'selected' : ''); ?>>Finalizada</option>
                                <option value="cancelada" <?php echo e(request('estado') === 'cancelada' ? 'selected' : ''); ?>>Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="">Todos</option>
                                <option value="civil" <?php echo e(request('tipo') === 'civil' ? 'selected' : ''); ?>>Civil</option>
                                <option value="penal" <?php echo e(request('tipo') === 'penal' ? 'selected' : ''); ?>>Penal</option>
                                <option value="laboral" <?php echo e(request('tipo') === 'laboral' ? 'selected' : ''); ?>>Laboral</option>
                                <option value="administrativo" <?php echo e(request('tipo') === 'administrativo' ? 'selected' : ''); ?>>Administrativo</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="sort_by" class="form-label">Ordenar por</label>
                            <select class="form-select" id="sort_by" name="sort_by">
                                <option value="fecha_inicio" <?php echo e(request('sort_by') === 'fecha_inicio' ? 'selected' : ''); ?>>Fecha</option>
                                <option value="nombre" <?php echo e(request('sort_by') === 'nombre' ? 'selected' : ''); ?>>Nombre</option>
                                <option value="created_at" <?php echo e(request('sort_by') === 'created_at' ? 'selected' : ''); ?>>Creación</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="bi bi-search me-1"></i>
                                Filtrar
                            </button>
                            <a href="<?php echo e(route('sesiones.index')); ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Sesiones -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            Sesiones de Juicio
                            <span class="badge bg-primary ms-2"><?php echo e($sesiones->total()); ?></span>
                        </h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleView('grid')">
                                <i class="bi bi-grid-3x3-gap"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleView('list')">
                                <i class="bi bi-list"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if($sesiones->count() > 0): ?>
                        <!-- Vista de Lista -->
                        <div id="list-view" class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                            </div>
                                        </th>
                                        <th width="25%">Sesión</th>
                                        <th width="10%">Estado</th>
                                        <th width="15%">Fecha</th>
                                        <th width="10%">Participantes</th>
                                        <th width="15%">Instructor</th>
                                        <th width="20%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $sesiones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sesion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="<?php echo e($sesion->id); ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px;">
                                                        <i class="bi bi-calendar-event text-white"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <strong class="d-block"><?php echo e($sesion->nombre); ?></strong>
                                                    <small class="text-muted"><?php echo e(Str::limit($sesion->descripcion, 50)); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($sesion->estado === 'en_curso'): ?>
                                                <span class="badge bg-success">En Curso</span>
                                            <?php elseif($sesion->estado === 'programada'): ?>
                                                <span class="badge bg-warning">Programada</span>
                                            <?php elseif($sesion->estado === 'finalizada'): ?>
                                                <span class="badge bg-secondary">Finalizada</span>
                                            <?php elseif($sesion->estado === 'cancelada'): ?>
                                                <span class="badge bg-danger">Cancelada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo e($sesion->fecha_inicio ? $sesion->fecha_inicio->format('d/m/Y') : '-'); ?></strong>
                                            </div>
                                            <small class="text-muted"><?php echo e($sesion->fecha_inicio ? $sesion->fecha_inicio->format('H:i') : '-'); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo e($sesion->participantes_count ?? 0); ?> / <?php echo e($sesion->max_participantes ?? '∞'); ?>

                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-circle me-2"></i>
                                                <span><?php echo e($sesion->instructor->name ?? 'Sin asignar'); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?php echo e(route('sesiones.show', $sesion)); ?>" 
                                                   class="btn btn-outline-info" 
                                                   title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor'): ?>
                                                <a href="<?php echo e(route('sesiones.edit', $sesion)); ?>" 
                                                   class="btn btn-outline-warning" 
                                                   title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="<?php echo e(route('sesiones.destroy', $sesion)); ?>" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta sesión?')">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" 
                                                            class="btn btn-outline-danger" 
                                                            title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Vista de Grid -->
                        <div id="grid-view" class="d-none p-4">
                            <div class="row">
                                <?php $__currentLoopData = $sesiones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sesion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-calendar-event me-2"></i>
                                                <h6 class="mb-0"><?php echo e(Str::limit($sesion->nombre, 20)); ?></h6>
                                            </div>
                                            <div>
                                                <?php if($sesion->estado === 'en_curso'): ?>
                                                    <span class="badge bg-success">En Curso</span>
                                                <?php elseif($sesion->estado === 'programada'): ?>
                                                    <span class="badge bg-warning">Programada</span>
                                                <?php elseif($sesion->estado === 'finalizada'): ?>
                                                    <span class="badge bg-secondary">Finalizada</span>
                                                <?php elseif($sesion->estado === 'cancelada'): ?>
                                                    <span class="badge bg-danger">Cancelada</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text text-muted"><?php echo e(Str::limit($sesion->descripcion, 80)); ?></p>
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Fecha</small>
                                                    <strong><?php echo e($sesion->fecha_inicio ? $sesion->fecha_inicio->format('d/m/Y') : '-'); ?></strong>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Participantes</small>
                                                    <strong><?php echo e($sesion->participantes_count ?? 0); ?>/<?php echo e($sesion->max_participantes ?? '∞'); ?></strong>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-circle me-2"></i>
                                                <small class="text-muted"><?php echo e($sesion->instructor->name ?? 'Sin asignar'); ?></small>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="btn-group w-100" role="group">
                                                <a href="<?php echo e(route('sesiones.show', $sesion)); ?>" 
                                                   class="btn btn-outline-info btn-sm">
                                                    <i class="bi bi-eye me-1"></i>
                                                    Ver
                                                </a>
                                                <?php if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor'): ?>
                                                <a href="<?php echo e(route('sesiones.edit', $sesion)); ?>" 
                                                   class="btn btn-outline-warning btn-sm">
                                                    <i class="bi bi-pencil me-1"></i>
                                                    Editar
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-event display-1 text-muted"></i>
                            <h4 class="text-muted mt-3">No hay sesiones disponibles</h4>
                            <p class="text-muted">Crea tu primera sesión para comenzar a organizar los simulacros de juicios.</p>
                            <?php if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor'): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearSesionModal">
                                <i class="bi bi-plus-circle me-2"></i>
                                Crear Primera Sesión
                            </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if($sesiones->hasPages()): ?>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <?php echo e($sesiones->firstItem()); ?> a <?php echo e($sesiones->lastItem()); ?> de <?php echo e($sesiones->total()); ?> resultados
                        </div>
                        <div>
                            <?php echo e($sesiones->appends(request()->query())->links()); ?>

                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Sesión -->
<div class="modal fade" id="crearSesionModal" tabindex="-1" aria-labelledby="crearSesionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?php echo e(route('sesiones.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="crearSesionModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>
                        Nueva Sesión de Juicio
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?php $__errorArgs = ['nombre'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="<?php echo e(old('nombre')); ?>" 
                                   placeholder="Ej: Juicio Penal - Robo"
                                   required>
                            <?php $__errorArgs = ['nombre'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select <?php $__errorArgs = ['tipo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                    id="tipo" 
                                    name="tipo" 
                                    required>
                                <option value="">Seleccionar tipo</option>
                                <option value="civil" <?php echo e(old('tipo') === 'civil' ? 'selected' : ''); ?>>Civil</option>
                                <option value="penal" <?php echo e(old('tipo') === 'penal' ? 'selected' : ''); ?>>Penal</option>
                                <option value="laboral" <?php echo e(old('tipo') === 'laboral' ? 'selected' : ''); ?>>Laboral</option>
                                <option value="administrativo" <?php echo e(old('tipo') === 'administrativo' ? 'selected' : ''); ?>>Administrativo</option>
                            </select>
                            <?php $__errorArgs = ['tipo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        
                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control <?php $__errorArgs = ['descripcion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                      id="descripcion" 
                                      name="descripcion" 
                                      rows="3" 
                                      placeholder="Describe el caso y los objetivos de la sesión..."><?php echo e(old('descripcion')); ?></textarea>
                            <?php $__errorArgs = ['descripcion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="fecha_inicio" class="form-label">Fecha de Inicio <span class="text-danger">*</span></label>
                            <input type="datetime-local" 
                                   class="form-control <?php $__errorArgs = ['fecha_inicio'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                   id="fecha_inicio" 
                                   name="fecha_inicio" 
                                   value="<?php echo e(old('fecha_inicio')); ?>" 
                                   required>
                            <?php $__errorArgs = ['fecha_inicio'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="max_participantes" class="form-label">Máx. Participantes</label>
                            <input type="number" 
                                   class="form-control <?php $__errorArgs = ['max_participantes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                   id="max_participantes" 
                                   name="max_participantes" 
                                   value="<?php echo e(old('max_participantes', 10)); ?>" 
                                   min="1" 
                                   max="20">
                            <?php $__errorArgs = ['max_participantes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>
                        Crear Sesión
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
function toggleView(view) {
    const listView = document.getElementById('list-view');
    const gridView = document.getElementById('grid-view');
    
    if (view === 'list') {
        listView.classList.remove('d-none');
        gridView.classList.add('d-none');
    } else {
        listView.classList.add('d-none');
        gridView.classList.remove('d-none');
    }
}

// Select all functionality
const selectAllElement = document.getElementById('selectAll');
if (selectAllElement) {
    selectAllElement.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
}
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/miguel/Documents/github/juiciosorales/resources/views/sesiones/index.blade.php ENDPATH**/ ?>