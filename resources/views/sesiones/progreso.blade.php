@extends('layouts.app')

@section('title', 'Progreso de sesión')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="{{ route('sesiones.index') }}">Sesiones</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('sesiones.show', $sesion) }}">{{ Str::limit($sesion->nombre, 30) }}</a></li>
                            <li class="breadcrumb-item active">Progreso</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="bi bi-graph-up-arrow me-2"></i>
                        Progreso del diálogo
                    </h1>
                    <p class="text-muted mb-0">Timeline de opciones elegidas en la sesión "{{ $sesion->nombre }}"</p>
                </div>
                <div>
                    <a href="{{ route('sesiones.show', $sesion) }}" class="btn btn-outline-primary me-2">
                        <i class="bi bi-eye me-2"></i>
                        Ver sesión
                    </a>
                    <a href="{{ route('sesiones.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>
                        Volver al listado
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-check me-2 text-primary"></i>
                        Opciones seleccionadas
                        <span class="badge bg-primary ms-2">{{ $decisiones->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($decisiones->count() > 0)
                        <div class="timeline-sesion">
                            @foreach($decisiones as $index => $decision)
                                <div class="timeline-item d-flex position-relative">
                                    <div class="timeline-marker flex-shrink-0 rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold" style="width: 36px; height: 36px;">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="timeline-content flex-grow-1 ms-3 pb-4">
                                        <div class="d-flex flex-wrap align-items-baseline gap-2 mb-1">
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ $decision->created_at->format('d/m/Y H:i:s') }}
                                            </small>
                                            @if($decision->nodoDialogo)
                                                <span class="badge bg-light text-dark border">
                                                    {{ $decision->nodoDialogo->titulo }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="border-start border-2 border-primary ps-3 py-2 bg-light rounded-end">
                                            <div class="fw-semibold text-dark mb-1">
                                                <i class="bi bi-chat-quote text-primary me-1"></i>
                                                {{ $decision->texto_respuesta ?? '—' }}
                                            </div>
                                            <div class="small text-muted">
                                                <i class="bi bi-person-badge me-1"></i>
                                                <strong>{{ $decision->rol ? $decision->rol->nombre : 'Rol no asignado' }}</strong>
                                                <span class="mx-1">·</span>
                                                <i class="bi bi-envelope me-1"></i>
                                                {{ $decision->usuario ? $decision->usuario->email : 'Usuario no registrado' }}
                                                @if($decision->usuario)
                                                    <span class="text-dark">({{ $decision->usuario->name }}{!! $decision->usuario->apellido ? ' ' . e($decision->usuario->apellido) : '' !!})</span>
                                                @endif
                                            </div>
                                            @if($decision->tiempo_respuesta !== null)
                                                <div class="small mt-1">
                                                    <i class="bi bi-stopwatch me-1"></i>
                                                    Tiempo de respuesta: {{ $decision->tiempo_respuesta_formateado }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <h5 class="text-muted mt-3">Aún no hay decisiones registradas</h5>
                            <p class="text-muted mb-0">Las opciones que los participantes elijan durante el diálogo aparecerán aquí en orden.</p>
                            <a href="{{ route('sesiones.show', $sesion) }}" class="btn btn-outline-primary mt-3">
                                <i class="bi bi-eye me-1"></i>
                                Ir a la sesión
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-sesion .timeline-item {
    position: relative;
}
.timeline-sesion .timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 17px;
    top: 36px;
    bottom: -1rem;
    width: 2px;
    background: #dee2e6;
}
</style>
@endsection
