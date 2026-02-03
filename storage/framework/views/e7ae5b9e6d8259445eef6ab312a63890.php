<?php $__env->startSection('title', 'Diálogos V2'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-diagram-3 me-2"></i>
            Diálogos V2
        </h1>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('dialogos-v2.create')); ?>" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i>
                Nuevo Diálogo
            </a>
            <a href="<?php echo e(route('panel-dialogos.index')); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-collection me-1"></i>
                Escenarios (flujos por rol)
            </a>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="alert alert-success"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Listado</h5>
            <span class="text-muted small"><?php echo e($dialogos->total()); ?> diálogos</span>
        </div>
        <div class="card-body p-0">
            <?php if($dialogos->count() === 0): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="text-muted mt-2 mb-3">No hay diálogos creados</p>
                    <a href="<?php echo e(route('dialogos-v2.create')); ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>
                        Crear primer diálogo
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Versión</th>
                                <th>Nodos</th>
                                <th>Actualizado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $dialogos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dialogo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($dialogo->id); ?></td>
                                    <td><?php echo e($dialogo->nombre); ?></td>
                                    <td class="text-muted small"><?php echo e(Str::limit($dialogo->descripcion, 80)); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php if($dialogo->estado === 'activo'): ?> bg-success
                                            <?php elseif($dialogo->estado === 'borrador'): ?> bg-warning
                                            <?php else: ?> bg-secondary
                                            <?php endif; ?>">
                                            <?php echo e(ucfirst($dialogo->estado)); ?>

                                        </span>
                                    </td>
                                    <td><?php echo e($dialogo->version ?? '1.0.0'); ?></td>
                                    <td><span class="badge bg-primary"><?php echo e($dialogo->nodos_count); ?></span></td>
                                    <td class="text-muted small"><?php echo e($dialogo->updated_at?->format('Y-m-d H:i')); ?></td>
                                    <td class="text-end">
                                        <a href="<?php echo e(route('dialogos-v2.editor', ['dialogo' => $dialogo->id])); ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-pencil me-1"></i> Editar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                <?php if($dialogos->hasPages()): ?>
                    <div class="p-3">
                        <?php echo e($dialogos->links()); ?>

                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/miguel/Documents/github/juiciosorales/resources/views/dialogos/v2/index.blade.php ENDPATH**/ ?>