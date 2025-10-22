@extends('layouts.app')

@section('title', 'Crear Nueva Sesión')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="bi bi-plus-circle me-2"></i>
                        Crear Nueva Sesión de Juicio
                    </h1>
                    <p class="text-muted mb-0">Configura una nueva sesión de juicio oral simulado</p>
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

    <form action="{{ route('sesiones.store') }}" method="POST" id="crearSesionForm">
        @csrf
        
        <!-- Paso 1: Información Básica -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Paso 1: Información Básica
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
                                       value="{{ old('nombre') }}" 
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
                                    <option value="civil" {{ old('tipo') === 'civil' ? 'selected' : '' }}>⚖️ Civil</option>
                                    <option value="penal" {{ old('tipo') === 'penal' ? 'selected' : '' }}>🔨 Penal</option>
                                    <option value="laboral" {{ old('tipo') === 'laboral' ? 'selected' : '' }}>💼 Laboral</option>
                                    <option value="administrativo" {{ old('tipo') === 'administrativo' ? 'selected' : '' }}>📋 Administrativo</option>
                                </select>
                                @error('tipo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12">
                                <label for="descripcion" class="form-label">Descripción del Caso</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                          id="descripcion" 
                                          name="descripcion" 
                                          rows="3" 
                                          placeholder="Describe brevemente el caso que se va a simular...">{{ old('descripcion') }}</textarea>
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
                                       value="{{ old('fecha_inicio') }}" 
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
                                       value="{{ old('max_participantes', 10) }}" 
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

        <!-- Paso 2: Selección de Diálogo -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-chat-dots me-2"></i>
                            Paso 2: Seleccionar Diálogo
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <label for="dialogo_id" class="form-label">Diálogo a Utilizar <span class="text-danger">*</span></label>
                                <select class="form-select @error('dialogo_id') is-invalid @enderror" 
                                        id="dialogo_id" 
                                        name="dialogo_id" 
                                        required>
                                    <option value="">Seleccionar diálogo...</option>
                                    @foreach($dialogos as $dialogo)
                                        <option value="{{ $dialogo->id }}" {{ old('dialogo_id') == $dialogo->id ? 'selected' : '' }}>
                                            {{ $dialogo->nombre }} 
                                            @if($dialogo->descripcion)
                                                - {{ Str::limit($dialogo->descripcion, 50) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('dialogo_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="button" class="btn btn-outline-info" onclick="previewDialogo()">
                                        <i class="bi bi-eye me-1"></i>
                                        Vista Previa
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Vista previa del diálogo -->
                        <div id="dialogoPreview" class="mt-3" style="display: none;">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle me-1"></i> Vista Previa del Diálogo</h6>
                                <div id="dialogoContent"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paso 3: Asignación de Roles -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-people me-2"></i>
                            Paso 3: Asignación de Roles a Estudiantes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="buscarEstudiante" class="form-label">Buscar Estudiante</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="buscarEstudiante" 
                                       placeholder="Nombre o email del estudiante...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="button" class="btn btn-outline-primary" onclick="cargarEstudiantes()">
                                        <i class="bi bi-arrow-clockwise me-1"></i>
                                        Actualizar Lista
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de estudiantes disponibles -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6>Estudiantes Disponibles:</h6>
                                <div id="estudiantesDisponibles" class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    <div class="text-center text-muted">
                                        <i class="bi bi-hourglass-split"></i>
                                        Cargando estudiantes...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Roles disponibles -->
                        <div class="row">
                            <div class="col-12">
                                <h6>Roles Disponibles:</h6>
                                <div id="rolesContainer" class="row">
                                    <!-- Los roles se cargarán dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('sesiones.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" id="crearSesionBtn">
                                <i class="bi bi-check-circle me-2"></i>
                                Crear Sesión
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal para vista previa del diálogo -->
<div class="modal fade" id="dialogoModal" tabindex="-1" aria-labelledby="dialogoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dialogoModalLabel">Vista Previa del Diálogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="dialogoModalContent">
                <!-- Contenido del diálogo -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
let estudiantes = [];
let roles = [];
let asignaciones = {};

document.addEventListener('DOMContentLoaded', function() {
    cargarEstudiantes();
    cargarRoles();
});

async function cargarEstudiantes() {
    try {
        const response = await fetch('/api/usuarios?tipo=alumno');
        const data = await response.json();
        estudiantes = data.data || [];
        mostrarEstudiantes();
    } catch (error) {
        console.error('Error cargando estudiantes:', error);
        document.getElementById('estudiantesDisponibles').innerHTML = 
            '<div class="text-center text-danger"><i class="bi bi-exclamation-triangle"></i> Error cargando estudiantes</div>';
    }
}

async function cargarRoles() {
    try {
        const response = await fetch('/api/roles');
        const data = await response.json();
        roles = data.data || [];
        mostrarRoles();
    } catch (error) {
        console.error('Error cargando roles:', error);
    }
}

function mostrarEstudiantes() {
    const container = document.getElementById('estudiantesDisponibles');
    const buscar = document.getElementById('buscarEstudiante').value.toLowerCase();
    
    const estudiantesFiltrados = estudiantes.filter(estudiante => 
        estudiante.name.toLowerCase().includes(buscar) ||
        estudiante.email.toLowerCase().includes(buscar)
    );
    
    if (estudiantesFiltrados.length === 0) {
        container.innerHTML = '<div class="text-center text-muted">No hay estudiantes disponibles</div>';
        return;
    }
    
    container.innerHTML = estudiantesFiltrados.map(estudiante => `
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" 
                   id="estudiante_${estudiante.id}" 
                   value="${estudiante.id}"
                   onchange="toggleEstudiante(${estudiante.id})">
            <label class="form-check-label" for="estudiante_${estudiante.id}">
                <strong>${estudiante.name}</strong> - ${estudiante.email}
            </label>
        </div>
    `).join('');
}

function mostrarRoles() {
    const container = document.getElementById('rolesContainer');
    
    container.innerHTML = roles.map(rol => `
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-${rol.icono || 'person'} me-2"></i>
                        <strong>${rol.nombre}</strong>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text small">${rol.descripcion || 'Sin descripción'}</p>
                    <select class="form-select form-select-sm" 
                            id="rol_${rol.id}" 
                            onchange="asignarRol(${rol.id}, this.value)">
                        <option value="">Seleccionar estudiante...</option>
                        <option value="auto">🤖 Procesamiento Automático</option>
                    </select>
                </div>
            </div>
        </div>
    `).join('');
}

function toggleEstudiante(estudianteId) {
    const checkbox = document.getElementById(`estudiante_${estudianteId}`);
    const estudiante = estudiantes.find(e => e.id === estudianteId);
    
    if (checkbox.checked) {
        // Agregar estudiante a todos los selects de roles
        roles.forEach(rol => {
            const select = document.getElementById(`rol_${rol.id}`);
            const option = document.createElement('option');
            option.value = estudianteId;
            option.textContent = `${estudiante.name} (${estudiante.email})`;
            select.appendChild(option);
        });
    } else {
        // Remover estudiante de todos los selects de roles
        roles.forEach(rol => {
            const select = document.getElementById(`rol_${rol.id}`);
            const option = select.querySelector(`option[value="${estudianteId}"]`);
            if (option) {
                option.remove();
            }
            // Si este rol estaba asignado a este estudiante, limpiarlo
            if (asignaciones[rol.id] === estudianteId) {
                select.value = '';
                delete asignaciones[rol.id];
            }
        });
    }
}

function asignarRol(rolId, estudianteId) {
    asignaciones[rolId] = estudianteId;
    
    // Remover este estudiante de otros roles
    if (estudianteId && estudianteId !== 'auto') {
        roles.forEach(rol => {
            if (rol.id !== rolId) {
                const select = document.getElementById(`rol_${rol.id}`);
                const option = select.querySelector(`option[value="${estudianteId}"]`);
                if (option) {
                    option.remove();
                }
                if (asignaciones[rol.id] === estudianteId) {
                    select.value = '';
                    delete asignaciones[rol.id];
                }
            }
        });
    }
}

async function previewDialogo() {
    const dialogoId = document.getElementById('dialogo_id').value;
    if (!dialogoId) {
        alert('Por favor selecciona un diálogo primero');
        return;
    }
    
    try {
        const response = await fetch(`/api/dialogos/${dialogoId}`);
        const data = await response.json();
        
        if (data.success) {
            const dialogo = data.data;
            document.getElementById('dialogoModalContent').innerHTML = `
                <h6>${dialogo.nombre}</h6>
                <p>${dialogo.descripcion || 'Sin descripción'}</p>
                <div class="mt-3">
                    <strong>Nodos:</strong> ${dialogo.nodos_count || 0}<br>
                    <strong>Respuestas:</strong> ${dialogo.respuestas_count || 0}<br>
                    <strong>Estado:</strong> ${dialogo.estado}
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('dialogoModal'));
            modal.show();
        }
    } catch (error) {
        console.error('Error cargando diálogo:', error);
        alert('Error al cargar la vista previa del diálogo');
    }
}

// Filtrar estudiantes en tiempo real
document.getElementById('buscarEstudiante').addEventListener('input', mostrarEstudiantes);

// Validación del formulario
document.getElementById('crearSesionForm').addEventListener('submit', function(e) {
    const dialogoId = document.getElementById('dialogo_id').value;
    if (!dialogoId) {
        e.preventDefault();
        alert('Por favor selecciona un diálogo');
        return;
    }
    
    // Agregar asignaciones al formulario
    const formData = new FormData(this);
    formData.append('asignaciones', JSON.stringify(asignaciones));
    
    // Reemplazar el formulario con los datos actualizados
    this.innerHTML = '';
    for (let [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        this.appendChild(input);
    }
});
</script>
@endsection
