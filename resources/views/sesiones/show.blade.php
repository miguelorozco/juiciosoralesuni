@extends('layouts.app')

@section('title', 'Detalles de Sesión')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="bi bi-eye me-2"></i>
                        {{ $sesion->nombre }}
                    </h1>
                    <p class="text-muted mb-0">{{ $sesion->descripcion }}</p>
                </div>
                <div>
                    <a href="{{ route('sesiones.edit', $sesion) }}" class="btn btn-outline-primary me-2">
                        <i class="bi bi-pencil-square me-2"></i>
                        Editar
                    </a>
                    <a href="{{ route('sesiones.index') }}" class="btn btn-outline-secondary">
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
                                @switch($sesion->tipo)
                                    @case('civil')
                                        ⚖️ Civil
                                        @break
                                    @case('penal')
                                        🔨 Penal
                                        @break
                                    @case('laboral')
                                        💼 Laboral
                                        @break
                                    @case('administrativo')
                                        📋 Administrativo
                                        @break
                                @endswitch
                            </p>
                            
                            <h6>Estado</h6>
                            <p class="mb-3">
                                @switch($sesion->estado)
                                    @case('programada')
                                        <span class="badge bg-secondary">📅 Programada</span>
                                        @break
                                    @case('iniciada')
                                        <span class="badge bg-primary">▶️ Iniciada</span>
                                        @break
                                    @case('en_curso')
                                        <span class="badge bg-info">🔄 En Curso</span>
                                        @break
                                    @case('pausada')
                                        <span class="badge bg-warning">⏸️ Pausada</span>
                                        @break
                                    @case('finalizada')
                                        <span class="badge bg-success">✅ Finalizada</span>
                                        @break
                                    @case('cancelada')
                                        <span class="badge bg-danger">❌ Cancelada</span>
                                        @break
                                @endswitch
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Fecha de Inicio</h6>
                            <p class="mb-3">{{ $sesion->fecha_inicio ? $sesion->fecha_inicio->format('d/m/Y H:i') : 'No programada' }}</p>
                            
                            <h6>Máximo de Participantes</h6>
                            <p class="mb-3">{{ $sesion->max_participantes }}</p>
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
                    <h5 class="mt-2">{{ $sesion->instructor->name }}</h5>
                    <p class="text-muted">{{ $sesion->instructor->email }}</p>
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
                        Asignaciones de Roles ({{ $sesion->asignaciones->count() }})
                    </h5>
                </div>
                <div class="card-body">
                    @if($sesion->asignaciones->count() > 0)
                        <div class="row">
                            @foreach($sesion->asignaciones as $asignacion)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card border-left-primary">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <i class="bi bi-{{ $asignacion->rol->icono ?? 'person' }} text-primary" style="font-size: 2rem;"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1">{{ $asignacion->rol->nombre }}</h6>
                                                    @if($asignacion->usuario)
                                                        <p class="mb-1 text-success">
                                                            <i class="bi bi-person-circle me-1"></i>
                                                            {{ $asignacion->usuario->name }}
                                                        </p>
                                                        <small class="text-muted">{{ $asignacion->usuario->email }}</small>
                                                    @else
                                                        <p class="mb-1 text-muted">
                                                            <i class="bi bi-person-x me-1"></i>
                                                            Sin asignar
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="flex-shrink-0">
                                                    @if($asignacion->confirmado)
                                                        <span class="badge bg-success">✅</span>
                                                    @else
                                                        <span class="badge bg-warning">⏳</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            No hay asignaciones de roles para esta sesión.
                        </div>
                    @endif
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
                                Creada: {{ $sesion->created_at->format('d/m/Y H:i') }}
                                @if($sesion->updated_at != $sesion->created_at)
                                    | <i class="bi bi-pencil me-1"></i>
                                    Modificada: {{ $sesion->updated_at->format('d/m/Y H:i') }}
                                @endif
                            </small>
                        </div>
                        <div>
                            @if($sesion->estado === 'programada')
                                <button type="button" class="btn btn-success me-2" onclick="iniciarSesion()">
                                    <i class="bi bi-play-circle me-2"></i>
                                    Iniciar Sesión
                                </button>
                            @elseif($sesion->estado === 'iniciada')
                                <button type="button" class="btn btn-info me-2" onclick="continuarSesion()">
                                    <i class="bi bi-play-fill me-2"></i>
                                    Continuar
                                </button>
                            @elseif($sesion->estado === 'en_curso')
                                <button type="button" class="btn btn-warning me-2" onclick="pausarSesion()">
                                    <i class="bi bi-pause-circle me-2"></i>
                                    Pausar
                                </button>
                                <button type="button" class="btn btn-danger me-2" onclick="finalizarSesion()">
                                    <i class="bi bi-stop-circle me-2"></i>
                                    Finalizar
                                </button>
                            @endif
                            
                            @if($sesion->estado !== 'finalizada' && $sesion->estado !== 'cancelada')
                                <button type="button" class="btn btn-outline-danger" onclick="cancelarSesion()">
                                    <i class="bi bi-x-circle me-2"></i>
                                    Cancelar
                                </button>
                            @endif
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
        fetch('{{ route("sesiones.iniciar", $sesion) }}', {
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
        fetch('{{ route("sesiones.finalizar", $sesion) }}', {
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
</script>
@endsection
