@extends('layouts.app')

@section('title', 'Editar Rol: ' . $rol->nombre)

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
                            <i class="bi bi-pencil me-2"></i>
                        @endif
                        Editar Rol: {{ $rol->nombre }}
                    </h1>
                    <p class="text-muted mb-0">Modifica la información del rol</p>
                </div>
                <div>
                    <a href="{{ route('roles.show', $rol) }}" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-eye me-2"></i>
                        Ver Rol
                    </a>
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>
                        Volver a Roles
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Formulario Principal -->
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pencil-square me-2"></i>
                        Información del Rol
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('roles.update', $rol) }}" method="POST" id="rolForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <!-- Nombre -->
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">
                                    Nombre del Rol <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('nombre') is-invalid @enderror" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="{{ old('nombre', $rol->nombre) }}" 
                                       placeholder="Ej: Juez, Fiscal, Defensor..."
                                       required>
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Nombre único que identifica el rol</div>
                            </div>

                            <!-- Orden -->
                            <div class="col-md-6 mb-3">
                                <label for="orden" class="form-label">Orden de Visualización</label>
                                <input type="number" 
                                       class="form-control @error('orden') is-invalid @enderror" 
                                       id="orden" 
                                       name="orden" 
                                       value="{{ old('orden', $rol->orden) }}" 
                                       min="0">
                                @error('orden')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Número para ordenar los roles (menor = primero)</div>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                      id="descripcion" 
                                      name="descripcion" 
                                      rows="3" 
                                      placeholder="Describe las responsabilidades y características de este rol...">{{ old('descripcion', $rol->descripcion) }}</textarea>
                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Color -->
                            <div class="col-md-6 mb-3">
                                <label for="color" class="form-label">Color del Rol</label>
                                <div class="input-group">
                                    <input type="color" 
                                           class="form-control form-control-color @error('color') is-invalid @enderror" 
                                           id="color" 
                                           name="color" 
                                           value="{{ old('color', $rol->color) }}"
                                           title="Selecciona un color">
                                    <input type="text" 
                                           class="form-control @error('color') is-invalid @enderror" 
                                           id="colorText" 
                                           value="{{ old('color', $rol->color) }}"
                                           placeholder="#007bff">
                                </div>
                                @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Color que representará este rol en la interfaz</div>
                            </div>

                            <!-- Icono -->
                            <div class="col-md-6 mb-3">
                                <label for="icono" class="form-label">Icono</label>
                                <select class="form-select @error('icono') is-invalid @enderror" 
                                        id="icono" 
                                        name="icono">
                                    <option value="">Sin icono</option>
                                    <optgroup label="Profesionales">
                                        <option value="gavel" {{ old('icono', $rol->icono) === 'gavel' ? 'selected' : '' }}>Martillo (Juez)</option>
                                        <option value="balance-scale" {{ old('icono', $rol->icono) === 'balance-scale' ? 'selected' : '' }}>Balanza (Fiscal)</option>
                                        <option value="shield-alt" {{ old('icono', $rol->icono) === 'shield-alt' ? 'selected' : '' }}>Escudo (Defensor)</option>
                                        <option value="user-tie" {{ old('icono', $rol->icono) === 'user-tie' ? 'selected' : '' }}>Usuario con Corbata</option>
                                    </optgroup>
                                    <optgroup label="Personas">
                                        <option value="user" {{ old('icono', $rol->icono) === 'user' ? 'selected' : '' }}>Usuario</option>
                                        <option value="person" {{ old('icono', $rol->icono) === 'person' ? 'selected' : '' }}>Persona</option>
                                        <option value="people" {{ old('icono', $rol->icono) === 'people' ? 'selected' : '' }}>Personas</option>
                                        <option value="person-check" {{ old('icono', $rol->icono) === 'person-check' ? 'selected' : '' }}>Persona Verificada</option>
                                    </optgroup>
                                    <optgroup label="Testigos y Peritos">
                                        <option value="eye" {{ old('icono', $rol->icono) === 'eye' ? 'selected' : '' }}>Ojo (Testigo)</option>
                                        <option value="search" {{ old('icono', $rol->icono) === 'search' ? 'selected' : '' }}>Lupa (Investigador)</option>
                                        <option value="clipboard-data" {{ old('icono', $rol->icono) === 'clipboard-data' ? 'selected' : '' }}>Clipboard (Perito)</option>
                                        <option value="file-medical" {{ old('icono', $rol->icono) === 'file-medical' ? 'selected' : '' }}>Archivo Médico</option>
                                    </optgroup>
                                    <optgroup label="Seguridad">
                                        <option value="shield" {{ old('icono', $rol->icono) === 'shield' ? 'selected' : '' }}>Escudo</option>
                                        <option value="badge" {{ old('icono', $rol->icono) === 'badge' ? 'selected' : '' }}>Insignia</option>
                                        <option value="award" {{ old('icono', $rol->icono) === 'award' ? 'selected' : '' }}>Premio</option>
                                    </optgroup>
                                </select>
                                @error('icono')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Icono que representará este rol</div>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="activo" 
                                       name="activo" 
                                       value="1" 
                                       {{ old('activo', $rol->activo) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activo">
                                    <strong>Rol Activo</strong>
                                    <div class="form-text">Los roles inactivos no estarán disponibles para nuevas asignaciones</div>
                                </label>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('roles.show', $rol) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>
                                Actualizar Rol
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Vista Previa -->
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-eye me-2"></i>
                        Vista Previa
                    </h5>
                </div>
                <div class="card-body">
                    <div id="preview-card" class="card border">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i id="preview-icon" class="bi bi-person me-2"></i>
                                <h6 id="preview-nombre" class="mb-0">{{ $rol->nombre }}</h6>
                            </div>
                            <div class="d-flex align-items-center">
                                <div id="preview-color" class="rounded-circle me-2" 
                                     style="width: 16px; height: 16px; background-color: {{ $rol->color }};"></div>
                                <span id="preview-estado" class="badge bg-success">Activo</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <p id="preview-descripcion" class="card-text text-muted">{{ $rol->descripcion ?: 'Descripción del rol...' }}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Orden: <span id="preview-orden">{{ $rol->orden }}</span></small>
                                <small class="text-muted">Actualizado hoy</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Actual -->
            <div class="card shadow mt-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Información Actual
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="fw-bold text-muted">Creado:</td>
                            <td>{{ $rol->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">Última actualización:</td>
                            <td>{{ $rol->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">Plantillas:</td>
                            <td>{{ $rol->asignacionesPlantillas->count() }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">Sesiones:</td>
                            <td>{{ $rol->asignacionesRoles->count() }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Advertencias -->
            @if($rol->asignacionesPlantillas->count() > 0 || $rol->asignacionesRoles->count() > 0)
            <div class="card shadow mt-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Advertencia
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text small">
                        Este rol está siendo utilizado en:
                    </p>
                    <ul class="list-unstyled small">
                        @if($rol->asignacionesPlantillas->count() > 0)
                        <li class="mb-1">
                            <i class="bi bi-file-earmark-text text-warning me-2"></i>
                            {{ $rol->asignacionesPlantillas->count() }} plantilla(s)
                        </li>
                        @endif
                        @if($rol->asignacionesRoles->count() > 0)
                        <li class="mb-1">
                            <i class="bi bi-calendar-event text-warning me-2"></i>
                            {{ $rol->asignacionesRoles->count() }} sesión(es)
                        </li>
                        @endif
                    </ul>
                    <p class="card-text small text-muted">
                        Los cambios afectarán a todas las plantillas y sesiones que usen este rol.
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sincronizar color picker con input de texto
    const colorPicker = document.getElementById('color');
    const colorText = document.getElementById('colorText');
    
    colorPicker.addEventListener('input', function() {
        colorText.value = this.value;
        updatePreview();
    });
    
    colorText.addEventListener('input', function() {
        if (this.value.match(/^#[0-9A-F]{6}$/i)) {
            colorPicker.value = this.value;
            updatePreview();
        }
    });

    // Actualizar vista previa
    function updatePreview() {
        const nombre = document.getElementById('nombre').value || 'Nombre del Rol';
        const descripcion = document.getElementById('descripcion').value || 'Descripción del rol...';
        const color = document.getElementById('color').value;
        const icono = document.getElementById('icono').value;
        const orden = document.getElementById('orden').value || '0';
        const activo = document.getElementById('activo').checked;

        // Actualizar elementos
        document.getElementById('preview-nombre').textContent = nombre;
        document.getElementById('preview-descripcion').textContent = descripcion;
        document.getElementById('preview-color').style.backgroundColor = color;
        document.getElementById('preview-orden').textContent = orden;
        
        // Actualizar icono
        const iconElement = document.getElementById('preview-icon');
        if (icono) {
            iconElement.className = `bi bi-${icono} me-2`;
        } else {
            iconElement.className = 'bi bi-person me-2';
        }
        
        // Actualizar estado
        const estadoElement = document.getElementById('preview-estado');
        if (activo) {
            estadoElement.className = 'badge bg-success';
            estadoElement.textContent = 'Activo';
        } else {
            estadoElement.className = 'badge bg-secondary';
            estadoElement.textContent = 'Inactivo';
        }
    }

    // Escuchar cambios en los inputs
    ['nombre', 'descripcion', 'icono', 'orden', 'activo'].forEach(id => {
        document.getElementById(id).addEventListener('input', updatePreview);
        document.getElementById(id).addEventListener('change', updatePreview);
    });

    // Inicializar vista previa
    updatePreview();
});
</script>
@endsection
