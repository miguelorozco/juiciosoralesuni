@extends('layouts.app')

@section('title', 'Dashboard - Simulador de Juicios Orales')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Principal -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="bi bi-house-door me-2 text-primary"></i>
                        Dashboard
                    </h1>
                    <p class="text-muted mb-0">
                        Bienvenido, <span class="fw-semibold text-dark">{{ auth()->user()->name }}</span> 
                        <span class="badge bg-{{ auth()->user()->tipo === 'admin' ? 'danger' : (auth()->user()->tipo === 'instructor' ? 'warning' : 'info') }} ms-2">
                            {{ ucfirst(auth()->user()->tipo) }}
                        </span>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    @if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor')
                    <a href="{{ route('sesiones.create') }}" class="btn btn-primary btn-lg shadow-sm">
                        <i class="bi bi-plus-circle me-2"></i>
                        Nueva Sesión
                    </a>
                    <a href="{{ route('configuracion') }}" class="btn btn-outline-primary btn-lg shadow-sm">
                        <i class="bi bi-gear me-2"></i>
                        Configuración
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas Principales -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-play-circle text-primary fs-2"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small fw-medium">Sesiones Activas</div>
                            <div class="h4 mb-0 fw-bold text-dark">{{ $sesionesActivas }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-calendar-check text-success fs-2"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small fw-medium">Total Sesiones</div>
                            <div class="h4 mb-0 fw-bold text-dark">{{ $totalSesiones }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor')
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-chat-dots text-warning fs-2"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small fw-medium">Diálogos</div>
                            <div class="h4 mb-0 fw-bold text-dark">{{ $totalDialogos }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-people text-info fs-2"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small fw-medium">Participaciones</div>
                            <div class="h4 mb-0 fw-bold text-dark">{{ $participacionesUsuario }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="row">
        <!-- Columna Principal -->
        <div class="col-lg-8">
            <!-- Sesiones Recientes -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-clock-history me-2 text-primary"></i>
                            Sesiones Recientes
                        </h5>
                        <a href="{{ route('sesiones.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye me-1"></i>
                            Ver todas
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($sesionesRecientes->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($sesionesRecientes as $sesion)
                                <div class="list-group-item border-0 px-0 py-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-semibold">
                                                <a href="{{ route('sesiones.show', $sesion->id) }}" class="text-dark text-decoration-none">
                                                    {{ $sesion->nombre }}
                                                </a>
                                            </h6>
                                            <p class="text-muted small mb-1">{{ Str::limit($sesion->descripcion, 80) }}</p>
                                            <div class="d-flex align-items-center gap-3 mt-2">
                                                <span class="badge bg-{{ $sesion->estado === 'en_curso' ? 'success' : ($sesion->estado === 'programada' ? 'warning' : 'secondary') }}">
                                                    {{ ucfirst($sesion->estado) }}
                                                </span>
                                                <span class="text-muted small">
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    {{ $sesion->fecha_creacion->format('d/m/Y') }}
                                                </span>
                                                @if($sesion->instructor)
                                                    <span class="text-muted small">
                                                        <i class="bi bi-person me-1"></i>
                                                        {{ $sesion->instructor->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 ms-3">
                                            <span class="badge bg-light text-dark">
                                                {{ $sesion->asignaciones->count() }} participantes
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                @if(!$loop->last)
                                    <hr class="my-0">
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
                            </div>
                            <h6 class="text-muted mb-2">No hay sesiones recientes</h6>
                            <p class="text-muted small mb-4">Las sesiones en las que participes aparecerán aquí</p>
                            @if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor')
                            <a href="{{ route('sesiones.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>
                                Crear Primera Sesión
                            </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actividad Reciente -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-activity me-2 text-success"></i>
                        Actividad Reciente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="bi bi-graph-up text-muted mb-3" style="font-size: 3rem;"></i>
                        <h6 class="text-muted mb-2">Sin actividad reciente</h6>
                        <p class="text-muted small">Tu actividad aparecerá aquí</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Próximas Sesiones -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-calendar-event me-2 text-warning"></i>
                        Próximas Sesiones
                    </h5>
                </div>
                <div class="card-body">
                    @if($proximasSesiones->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($proximasSesiones as $sesion)
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-semibold small">
                                                <a href="{{ route('sesiones.show', $sesion->id) }}" class="text-dark text-decoration-none">
                                                    {{ Str::limit($sesion->nombre, 30) }}
                                                </a>
                                            </h6>
                                            @if($sesion->fecha_inicio)
                                                <p class="text-muted small mb-0">
                                                    <i class="bi bi-clock me-1"></i>
                                                    {{ $sesion->fecha_inicio->format('d/m/Y H:i') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if(!$loop->last)
                                    <hr class="my-1">
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-plus text-muted mb-3" style="font-size: 3rem;"></i>
                            <h6 class="text-muted mb-2">No hay sesiones programadas</h6>
                            <p class="text-muted small">Las próximas sesiones aparecerán aquí</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-graph-up me-2 text-info"></i>
                        Estadísticas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-medium">Sesiones este mes</span>
                            <span class="fw-bold text-dark">{{ $sesionesEsteMes }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $porcentajeSesionesMes ?? 0 }}%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-medium">Participaciones</span>
                            <span class="fw-bold text-dark">{{ $participacionesUsuario }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $porcentajeParticipaciones ?? 0 }}%"></div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-medium">Tiempo total</span>
                            <span class="fw-bold text-dark">{{ $tiempoTotalFormateado }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ min($porcentajeParticipaciones ?? 0, 100) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            @if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor')
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-lightning me-2 text-danger"></i>
                        Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('sesiones.create') }}" class="btn btn-primary">
                            <i class="bi bi-calendar-plus me-2"></i>
                            Nueva Sesión
                        </a>
                        
                        <a href="{{ route('dialogos-v2.create') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-chat-dots me-2"></i>
                            Nuevo Diálogo
                        </a>

                        <a href="{{ route('roles.index') }}" class="btn btn-outline-info">
                            <i class="bi bi-people me-2"></i>
                            Gestionar Roles
                        </a>

                        <a href="{{ route('configuracion') }}" class="btn btn-outline-warning">
                            <i class="bi bi-gear me-2"></i>
                            Configuración
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.card {
    transition: all 0.3s ease;
    border-radius: 12px;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.progress {
    border-radius: 10px;
    background-color: #f8f9fa;
}

.progress-bar {
    border-radius: 10px;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

/* Animaciones suaves */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
}

/* Responsive mejoras */
@media (max-width: 768px) {
    .btn-lg {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .h2 {
        font-size: 1.5rem;
    }
}
</style>
@endsection