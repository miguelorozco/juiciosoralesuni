@extends('layouts.app')

@section('title', 'Editar Sesión')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="bi bi-pencil-square me-2"></i>
                        Editar Sesión: {{ $sesion->nombre }}
                    </h1>
                    <p class="text-muted mb-0">Modifica los detalles de la sesión de juicio</p>
                </div>
                <div>
                    <a href="{{ route('sesiones.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>
                        Volver a Sesiones
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('sesiones.update', $sesion) }}" method="POST">
        @csrf
        @method('PUT')
        
        <!-- Información Básica -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Información Básica
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre de la Sesión <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('nombre') is-invalid @enderror" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="{{ old('nombre', $sesion->nombre) }}" 
                                       placeholder="Ej: Juicio Penal - Robo a Tienda"
                                       required>
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="tipo" class="form-label">Tipo de Juicio <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo') is-invalid @enderror" 
                                        id="tipo" 
                                        name="tipo" 
                                        required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="civil" {{ old('tipo', $sesion->tipo) === 'civil' ? 'selected' : '' }}>⚖️ Civil</option>
                                    <option value="penal" {{ old('tipo', $sesion->tipo) === 'penal' ? 'selected' : '' }}>🔨 Penal</option>
                                    <option value="laboral" {{ old('tipo', $sesion->tipo) === 'laboral' ? 'selected' : '' }}>💼 Laboral</option>
                                    <option value="administrativo" {{ old('tipo', $sesion->tipo) === 'administrativo' ? 'selected' : '' }}>📋 Administrativo</option>
                                </select>
                                @error('tipo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="dialogo_id" class="form-label">Diálogo a utilizar <span class="text-danger">*</span></label>
                                <select class="form-select @error('dialogo_id') is-invalid @enderror"
                                        id="dialogo_id"
                                        name="dialogo_id"
                                        required>
                                    <option value="">Selecciona un diálogo</option>
                                    @foreach($dialogos as $dialogo)
                                        <option value="{{ $dialogo->id }}" {{ (string)old('dialogo_id', $dialogoId) === (string)$dialogo->id ? 'selected' : '' }}>
                                            {{ $dialogo->nombre }} @if($dialogo->descripcion) - {{ Str::limit($dialogo->descripcion, 60) }} @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('dialogo_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Los roles disponibles se basan en este diálogo.</small>
                            </div>
                            
                            <div class="col-12">
                                <label for="descripcion" class="form-label">Descripción del Caso</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                          id="descripcion" 
                                          name="descripcion" 
                                          rows="3" 
                                          placeholder="Describe brevemente el caso que se va a simular...">{{ old('descripcion', $sesion->descripcion) }}</textarea>
                                @error('descripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="fecha_inicio" class="form-label">Fecha y Hora de Inicio <span class="text-danger">*</span></label>
                                <input type="datetime-local" 
                                       class="form-control @error('fecha_inicio') is-invalid @enderror" 
                                       id="fecha_inicio" 
                                       name="fecha_inicio" 
                                       value="{{ old('fecha_inicio', $sesion->fecha_inicio ? $sesion->fecha_inicio->format('Y-m-d\TH:i') : '') }}" 
                                       required>
                                @error('fecha_inicio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="max_participantes" class="form-label">Máximo de Participantes</label>
                                <input type="number" 
                                       class="form-control @error('max_participantes') is-invalid @enderror" 
                                       id="max_participantes" 
                                       name="max_participantes" 
                                       value="{{ old('max_participantes', $sesion->max_participantes) }}" 
                                       min="1" 
                                       max="20">
                                @error('max_participantes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado de la Sesión -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-gear me-2"></i>
                            Estado de la Sesión
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="estado" class="form-label">Estado Actual</label>
                                <select class="form-select @error('estado') is-invalid @enderror" 
                                        id="estado" 
                                        name="estado">
                                    <option value="programada" {{ old('estado', $sesion->estado) === 'programada' ? 'selected' : '' }}>📅 Programada</option>
                                    <option value="iniciada" {{ old('estado', $sesion->estado) === 'iniciada' ? 'selected' : '' }}>▶️ Iniciada</option>
                                    <option value="en_curso" {{ old('estado', $sesion->estado) === 'en_curso' ? 'selected' : '' }}>🔄 En Curso</option>
                                    <option value="pausada" {{ old('estado', $sesion->estado) === 'pausada' ? 'selected' : '' }}>⏸️ Pausada</option>
                                    <option value="finalizada" {{ old('estado', $sesion->estado) === 'finalizada' ? 'selected' : '' }}>✅ Finalizada</option>
                                    <option value="cancelada" {{ old('estado', $sesion->estado) === 'cancelada' ? 'selected' : '' }}>❌ Cancelada</option>
                                </select>
                                @error('estado')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Información de la Sesión</label>
                                <div class="alert alert-light">
                                    <small>
                                        <strong>Creada:</strong> {{ $sesion->created_at->format('d/m/Y H:i') }}<br>
                                        <strong>Instructor:</strong> {{ $sesion->instructor->name }}<br>
                                        <strong>Participantes:</strong> {{ $sesion->asignaciones->count() }} / {{ $sesion->max_participantes }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asignación de Roles del Diálogo -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0 d-flex justify-content-between align-items-center">
                            <span>
                                <i class="bi bi-people me-2"></i>
                                Asignación de Roles del Diálogo
                            </span>
                            <small class="text-white-50">
                                {{ $dialogoActivo ? 'Diálogo: '.$dialogoActivo->nombre : 'Sin diálogo activo' }}
                            </small>
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(isset($roles) && $roles->count())
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Rol</th>
                                            <th>Asignar a estudiante</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($roles as $rol)
                                            @php
                                                $asig = $asignaciones->get($rol->id);
                                                $selectedUsuario = $asig ? $asig->usuario_id : null;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="badge rounded-pill" style="background-color: {{ $rol->color ?? '#0d6efd' }}; color: #fff;">
                                                        {{ $rol->nombre }}
                                                    </span>
                                                    <div class="text-muted small">{{ $rol->descripcion }}</div>
                                                </td>
                                                <td style="min-width: 260px;">
                                                    <select class="form-select"
                                                            name="asignaciones[{{ $rol->id }}]">
                                                        <option value="">Sin asignar</option>
                                                        @foreach($alumnos as $alumno)
                                                            <option value="{{ $alumno->id }}" {{ (string)$selectedUsuario === (string)$alumno->id ? 'selected' : '' }}>
                                                                {{ $alumno->name }} ({{ $alumno->email }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    @if($asig && $asig->usuario)
                                                        <span class="badge bg-primary">Asignado</span>
                                                        @if($asig->confirmado)
                                                            <span class="badge bg-success ms-1">Confirmado</span>
                                                        @else
                                                            <span class="badge bg-warning text-dark ms-1">Pendiente</span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-secondary">Libre</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">Puedes dejar roles sin asignar; el sistema los resolverá en automático.</small>
                        @else
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                No hay roles activos disponibles.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Asignaciones de Roles -->
        <!-- (Se sustituye por el bloque anterior con selectores) -->

        <!-- Botones de Acción -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <a href="{{ route('sesiones.show', $sesion) }}" class="btn btn-outline-info me-2">
                                    <i class="bi bi-eye me-2"></i>
                                    Ver Sesión
                                </a>
                                <a href="{{ route('sesiones.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-2"></i>
                                    Cancelar
                                </a>
                            </div>
                            <div>
                                @if($sesion->estado === 'programada')
                                    <button type="button" class="btn btn-success me-2" onclick="iniciarSesion()">
                                        <i class="bi bi-play-circle me-2"></i>
                                        Iniciar Sesión
                                    </button>
                                @endif
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function iniciarSesion() {
    if (confirm('¿Estás seguro de que quieres iniciar esta sesión? Esto cambiará el estado a "iniciada".')) {
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

function removerAsignacion(asignacionId) {
    if (confirm('¿Estás seguro de que quieres remover esta asignación?')) {
        // Implementar lógica para remover asignación
        console.log('Remover asignación:', asignacionId);
    }
}

function asignarEstudiante(rolId) {
    // Implementar lógica para asignar estudiante
    console.log('Asignar estudiante al rol:', rolId);
}
</script>
@endsection
