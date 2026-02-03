<?php $__env->startSection('title', 'Gestión de Roles'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="bi bi-people me-2"></i>
                        Gestión de Roles
                    </h1>
                    <p class="text-muted mb-0">Administra los roles disponibles para los simulacros de juicios</p>
                </div>
                <div>
                    <a href="<?php echo e(route('roles.create')); ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Nuevo Rol
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" action="<?php echo e(route('roles.index')); ?>" class="row g-3">
                        <div class="col-md-4">
                            <label for="buscar" class="form-label">Buscar</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="buscar" 
                                   name="buscar" 
                                   value="<?php echo e(request('buscar')); ?>" 
                                   placeholder="Nombre o descripción...">
                        </div>
                        <div class="col-md-3">
                            <label for="activo" class="form-label">Estado</label>
                            <select class="form-select" id="activo" name="activo">
                                <option value="">Todos</option>
                                <option value="1" <?php echo e(request('activo') === '1' ? 'selected' : ''); ?>>Activos</option>
                                <option value="0" <?php echo e(request('activo') === '0' ? 'selected' : ''); ?>>Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort_by" class="form-label">Ordenar por</label>
                            <select class="form-select" id="sort_by" name="sort_by">
                                <option value="orden" <?php echo e(request('sort_by') === 'orden' ? 'selected' : ''); ?>>Orden</option>
                                <option value="nombre" <?php echo e(request('sort_by') === 'nombre' ? 'selected' : ''); ?>>Nombre</option>
                                <option value="created_at" <?php echo e(request('sort_by') === 'created_at' ? 'selected' : ''); ?>>Fecha creación</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="sort_order" class="form-label">Dirección</label>
                            <select class="form-select" id="sort_order" name="sort_order">
                                <option value="asc" <?php echo e(request('sort_order') === 'asc' ? 'selected' : ''); ?>>Ascendente</option>
                                <option value="desc" <?php echo e(request('sort_order') === 'desc' ? 'selected' : ''); ?>>Descendente</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="bi bi-search me-1"></i>
                                Filtrar
                            </button>
                            <a href="<?php echo e(route('roles.index')); ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Roles -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            Roles Disponibles
                            <span class="badge bg-primary ms-2"><?php echo e($roles->total()); ?></span>
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
                    <?php if($roles->count() > 0): ?>
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
                                        <th width="5%">Orden</th>
                                        <th width="10%">Color</th>
                                        <th width="15%">Nombre</th>
                                        <th width="25%">Descripción</th>
                                        <th width="10%">Icono</th>
                                        <th width="10%">Estado</th>
                                        <th width="20%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rol): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="<?php echo e($rol->id); ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo e($rol->orden); ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle me-2" 
                                                     style="width: 20px; height: 20px; background-color: <?php echo e($rol->color); ?>;"></div>
                                                <small class="text-muted"><?php echo e($rol->color); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if($rol->icono): ?>
                                                    <i class="bi bi-<?php echo e($rol->icono); ?> me-2"></i>
                                                <?php endif; ?>
                                                <strong><?php echo e($rol->nombre); ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted"><?php echo e(Str::limit($rol->descripcion, 50)); ?></span>
                                        </td>
                                        <td>
                                            <?php if($rol->icono): ?>
                                                <i class="bi bi-<?php echo e($rol->icono); ?> fs-5"></i>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($rol->activo): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?php echo e(route('roles.show', $rol)); ?>" 
                                                   class="btn btn-outline-info" 
                                                   title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo e(route('roles.edit', $rol)); ?>" 
                                                   class="btn btn-outline-warning" 
                                                   title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="<?php echo e(route('roles.toggle-activo', $rol)); ?>" 
                                                      method="POST" 
                                                      class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" 
                                                            class="btn btn-outline-<?php echo e($rol->activo ? 'secondary' : 'success'); ?>" 
                                                            title="<?php echo e($rol->activo ? 'Desactivar' : 'Activar'); ?>">
                                                        <i class="bi bi-<?php echo e($rol->activo ? 'pause' : 'play'); ?>"></i>
                                                    </button>
                                                </form>
                                                <form action="<?php echo e(route('roles.destroy', $rol)); ?>" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de que quieres eliminar este rol?')">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" 
                                                            class="btn btn-outline-danger" 
                                                            title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
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
                                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rol): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <?php if($rol->icono): ?>
                                                    <i class="bi bi-<?php echo e($rol->icono); ?> me-2"></i>
                                                <?php endif; ?>
                                                <h6 class="mb-0"><?php echo e($rol->nombre); ?></h6>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle me-2" 
                                                     style="width: 16px; height: 16px; background-color: <?php echo e($rol->color); ?>;"></div>
                                                <?php if($rol->activo): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text text-muted"><?php echo e($rol->descripcion); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">Orden: <?php echo e($rol->orden); ?></small>
                                                <small class="text-muted"><?php echo e($rol->created_at->format('d/m/Y')); ?></small>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="btn-group w-100" role="group">
                                                <a href="<?php echo e(route('roles.show', $rol)); ?>" 
                                                   class="btn btn-outline-info btn-sm">
                                                    <i class="bi bi-eye me-1"></i>
                                                    Ver
                                                </a>
                                                <a href="<?php echo e(route('roles.edit', $rol)); ?>" 
                                                   class="btn btn-outline-warning btn-sm">
                                                    <i class="bi bi-pencil me-1"></i>
                                                    Editar
                                                </a>
                                                <form action="<?php echo e(route('roles.toggle-activo', $rol)); ?>" 
                                                      method="POST" 
                                                      class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" 
                                                            class="btn btn-outline-<?php echo e($rol->activo ? 'secondary' : 'success'); ?> btn-sm">
                                                        <i class="bi bi-<?php echo e($rol->activo ? 'pause' : 'play'); ?>"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-people display-1 text-muted"></i>
                            <h4 class="text-muted mt-3">No hay roles disponibles</h4>
                            <p class="text-muted">Crea tu primer rol para comenzar a organizar los simulacros de juicios.</p>
                            <a href="<?php echo e(route('roles.create')); ?>" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>
                                Crear Primer Rol
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if($roles->hasPages()): ?>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <?php echo e($roles->firstItem()); ?> a <?php echo e($roles->lastItem()); ?> de <?php echo e($roles->total()); ?> resultados
                        </div>
                        <div>
                            <?php echo e($roles->appends(request()->query())->links()); ?>

                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
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
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/miguel/Documents/github/juiciosorales/resources/views/roles/index.blade.php ENDPATH**/ ?>