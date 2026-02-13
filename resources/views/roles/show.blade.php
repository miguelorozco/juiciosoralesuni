@extends('layouts.app')

@section('title', 'Detalles del Rol: ' . $rol->nombre)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        @if($rol->icono)
                            <i class="bi bi-{{ $rol->icono }} me-2"></i>
                        @else
                            <i class="bi bi-person me-2"></i>
                        @endif
                        {{ $rol->nombre }}
                        @if($rol->activo)
                            <span class="badge bg-success ms-2">Activo</span>
                        @else
                            <span class="badge bg-secondary ms-2">Inactivo</span>
                        @endif
                    </h1>
                    <p class="text-muted mb-0">{{ $rol->descripcion }}</p>
                </div>
                <div>
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left me-2"></i>
                        Volver a Roles
                    </a>
                    <a href="{{ route('roles.edit', $rol) }}" class="btn btn-warning">
                        <i class="bi bi-pencil me-2"></i>
                        Editar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Información Principal -->
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Información del Rol
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold text-muted">Nombre:</td>
                                    <td>{{ $rol->nombre }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">Descripción:</td>
                                    <td>{{ $rol->descripcion ?: 'Sin descripción' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">Color:</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle me-2" 
                                                 style="width: 20px; height: 20px; background-color: {{ $rol->color }};"></div>
                                            <code>{{ $rol->color }}</code>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold text-muted">Icono:</td>
                                    <td>
                                        @if($rol->icono)
                                            <i class="bi bi-{{ $rol->icono }} fs-4"></i>
                                            <code class="ms-2">{{ $rol->icono }}</code>
                                        @else
                                            <span class="text-muted">Sin icono</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">Orden:</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $rol->orden }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">Estado:</td>
                                    <td>
                                        @if($rol->activo)
                                            <span class="badge bg-success">Activo</span>
                                        @else
                                            <span class="badge bg-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas de Uso -->
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>
                        Estadísticas de Uso
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="display-6 text-primary">{{ $rol->asignacionesPlantillas->count() }}</div>
                                <div class="text-muted">Plantillas</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="display-6 text-success">{{ $rol->asignacionesRoles->count() }}</div>
                                <div class="text-muted">Sesiones</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="display-6 text-info">{{ (int) $rol->created_at->diffInDays(now()) }}</div>
                                <div class="text-muted">Días desde creación</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plantillas que usan este rol -->
            @if($rol->asignacionesPlantillas->count() > 0)
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark-text me-2"></i>
                        Plantillas que usan este rol
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Plantilla</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rol->asignacionesPlantillas as $asignacion)
                                <tr>
                                    <td>
                                        <a href="{{ route('plantillas.show', $asignacion->plantilla) }}" 
                                           class="text-decoration-none">
                                            {{ $asignacion->plantilla->nombre }}
                                        </a>
                                    </td>
                                    <td>{{ Str::limit($asignacion->plantilla->descripcion, 50) }}</td>
                                    <td>
                                        @if($asignacion->plantilla->activa)
                                            <span class="badge bg-success">Activa</span>
                                        @else
                                            <span class="badge bg-secondary">Inactiva</span>
                                        @endif
                                    </td>
                                    <td>{{ $asignacion->plantilla->created_at->format('d/m/Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Sesiones que usan este rol -->
            @if($rol->asignacionesRoles->count() > 0)
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-calendar-event me-2"></i>
                        Sesiones que usan este rol
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Sesión</th>
                                    <th>Usuario</th>
                                    <th>Estado</th>
                                    <th>Fecha Asignación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rol->asignacionesRoles as $asignacion)
                                <tr>
                                    <td>
                                        <a href="{{ route('sesiones.show', $asignacion->sesion) }}" 
                                           class="text-decoration-none">
                                            {{ $asignacion->sesion->nombre }}
                                        </a>
                                    </td>
                                    <td>{{ $asignacion->usuario->name }} {{ $asignacion->usuario->apellido }}</td>
                                    <td>
                                        @if($asignacion->confirmado)
                                            <span class="badge bg-success">Confirmado</span>
                                        @else
                                            <span class="badge bg-warning">Pendiente</span>
                                        @endif
                                    </td>
                                    <td>{{ $asignacion->fecha_asignacion->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Acciones Rápidas -->
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>
                        Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('roles.edit', $rol) }}" class="btn btn-warning">
                            <i class="bi bi-pencil me-2"></i>
                            Editar Rol
                        </a>
                        
                        <form action="{{ route('roles.toggle-activo', $rol) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" 
                                    class="btn btn-{{ $rol->activo ? 'outline-secondary' : 'outline-success' }} w-100">
                                <i class="bi bi-{{ $rol->activo ? 'pause' : 'play' }} me-2"></i>
                                {{ $rol->activo ? 'Desactivar' : 'Activar' }} Rol
                            </button>
                        </form>

                        @if($rol->asignacionesPlantillas->count() === 0 && $rol->asignacionesRoles->count() === 0)
                        <form action="{{ route('roles.destroy', $rol) }}" 
                              method="POST" 
                              onsubmit="return confirm('¿Estás seguro de que quieres eliminar este rol? Esta acción no se puede deshacer.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-trash me-2"></i>
                                Eliminar Rol
                            </button>
                        </form>
                        @else
                        <button class="btn btn-outline-danger w-100" disabled title="No se puede eliminar porque está en uso">
                            <i class="bi bi-trash me-2"></i>
                            Eliminar Rol
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Información Técnica -->
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-gear me-2"></i>
                        Información Técnica
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="fw-bold text-muted">ID:</td>
                            <td><code>{{ $rol->id }}</code></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">Creado:</td>
                            <td>{{ $rol->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">Actualizado:</td>
                            <td>{{ $rol->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Vista Previa -->
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-eye me-2"></i>
                        Vista Previa
                    </h5>
                </div>
                <div class="card-body">
                    <div class="card border">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                @if($rol->icono)
                                    <i class="bi bi-{{ $rol->icono }} me-2"></i>
                                @else
                                    <i class="bi bi-person me-2"></i>
                                @endif
                                <h6 class="mb-0">{{ $rol->nombre }}</h6>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle me-2" 
                                     style="width: 16px; height: 16px; background-color: {{ $rol->color }};"></div>
                                @if($rol->activo)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="card-text text-muted">{{ $rol->descripcion ?: 'Sin descripción' }}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Orden: {{ $rol->orden }}</small>
                                <small class="text-muted">{{ $rol->created_at->format('d/m/Y') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
