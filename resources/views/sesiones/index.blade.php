@extends('layouts.app')

@section('title', 'Gestión de Sesiones')

@section('content')
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
                    @if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor')
                    <a class="btn btn-primary" href="{{ route('sesiones.create') }}">
                        <i class="bi bi-plus-circle me-2"></i>
                        Nueva Sesión
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen por estado -->
    <div class="row mb-4">
        @php
            $estadoCards = [
                ['title' => 'Por iniciar', 'data' => $sesionesPorIniciar ?? collect(), 'icon' => 'clock', 'bg' => 'warning'],
                ['title' => 'Iniciadas', 'data' => $sesionesIniciadas ?? collect(), 'icon' => 'play-circle', 'bg' => 'success'],
                ['title' => 'Terminadas', 'data' => $sesionesTerminadas ?? collect(), 'icon' => 'flag', 'bg' => 'secondary'],
            ];
        @endphp
        @foreach($estadoCards as $card)
        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-{{ $card['bg'] }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-{{ $card['icon'] }} me-2"></i>{{ $card['title'] }}</span>
                        <span class="badge bg-light text-dark">{{ $card['data']->count() }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($card['data']->count())
                        <ul class="list-group list-group-flush">
                            @foreach($card['data'] as $item)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>{{ $item->nombre }}</strong>
                                            <div class="text-muted small">
                                                {{ $item->fecha_inicio?->format('d/m H:i') ?? 'Sin fecha' }} · {{ ucfirst($item->tipo) }}
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('sesiones.show', $item) }}" class="btn btn-sm btn-outline-info">Ver</a>
                                            @if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor')
                                            <a href="{{ route('sesiones.edit', $item) }}" class="btn btn-sm btn-outline-warning">Editar</a>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="p-3 text-muted small">No hay sesiones en este estado.</div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
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
                            <div class="h4 mb-0" id="totalSesiones">{{ $sesiones->total() }}</div>
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
                            <div class="h4 mb-0" id="enCurso">{{ $sesiones->where('estado', 'en_curso')->count() }}</div>
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
                            <div class="h4 mb-0" id="programadas">{{ $sesiones->where('estado', 'programada')->count() }}</div>
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
                            <div class="h4 mb-0" id="participantes">{{ $sesiones->sum('participantes_count') }}</div>
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
                    <form method="GET" action="{{ route('sesiones.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="buscar" class="form-label">Buscar</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="buscar" 
                                   name="buscar" 
                                   value="{{ request('buscar') }}" 
                                   placeholder="Nombre, descripción o instructor...">
                        </div>
                        <div class="col-md-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Todos</option>
                                <option value="programada" {{ request('estado') === 'programada' ? 'selected' : '' }}>Programada</option>
                                <option value="en_curso" {{ request('estado') === 'en_curso' ? 'selected' : '' }}>En Curso</option>
                                <option value="finalizada" {{ request('estado') === 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                                <option value="cancelada" {{ request('estado') === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="">Todos</option>
                                <option value="civil" {{ request('tipo') === 'civil' ? 'selected' : '' }}>Civil</option>
                                <option value="penal" {{ request('tipo') === 'penal' ? 'selected' : '' }}>Penal</option>
                                <option value="laboral" {{ request('tipo') === 'laboral' ? 'selected' : '' }}>Laboral</option>
                                <option value="administrativo" {{ request('tipo') === 'administrativo' ? 'selected' : '' }}>Administrativo</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="sort_by" class="form-label">Ordenar por</label>
                            <select class="form-select" id="sort_by" name="sort_by">
                                <option value="fecha_inicio" {{ request('sort_by') === 'fecha_inicio' ? 'selected' : '' }}>Fecha</option>
                                <option value="nombre" {{ request('sort_by') === 'nombre' ? 'selected' : '' }}>Nombre</option>
                                <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Creación</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="bi bi-search me-1"></i>
                                Filtrar
                            </button>
                            <a href="{{ route('sesiones.index') }}" class="btn btn-outline-secondary">
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
                            <span class="badge bg-primary ms-2">{{ $sesiones->total() }}</span>
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
                    @if($sesiones->count() > 0)
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
                                    @foreach($sesiones as $sesion)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="{{ $sesion->id }}">
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
                                                    <strong class="d-block">{{ $sesion->nombre }}</strong>
                                                    <small class="text-muted">{{ Str::limit($sesion->descripcion, 50) }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($sesion->estado === 'en_curso')
                                                <span class="badge bg-success">En Curso</span>
                                            @elseif($sesion->estado === 'programada')
                                                <span class="badge bg-warning">Programada</span>
                                            @elseif($sesion->estado === 'finalizada')
                                                <span class="badge bg-secondary">Finalizada</span>
                                            @elseif($sesion->estado === 'cancelada')
                                                <span class="badge bg-danger">Cancelada</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $sesion->fecha_inicio ? $sesion->fecha_inicio->format('d/m/Y') : '-' }}</strong>
                                            </div>
                                            <small class="text-muted">{{ $sesion->fecha_inicio ? $sesion->fecha_inicio->format('H:i') : '-' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ $sesion->participantes_count ?? 0 }} / {{ $sesion->max_participantes ?? '∞' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-circle me-2"></i>
                                                <span>{{ $sesion->instructor->name ?? 'Sin asignar' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('sesiones.show', $sesion) }}" 
                                                   class="btn btn-outline-info" 
                                                   title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                @if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor')
                                                <a href="{{ route('sesiones.edit', $sesion) }}" 
                                                   class="btn btn-outline-warning" 
                                                   title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('sesiones.destroy', $sesion) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta sesión?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-outline-danger" 
                                                            title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Vista de Grid -->
                        <div id="grid-view" class="d-none p-4">
                            <div class="row">
                                @foreach($sesiones as $sesion)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-calendar-event me-2"></i>
                                                <h6 class="mb-0">{{ Str::limit($sesion->nombre, 20) }}</h6>
                                            </div>
                                            <div>
                                                @if($sesion->estado === 'en_curso')
                                                    <span class="badge bg-success">En Curso</span>
                                                @elseif($sesion->estado === 'programada')
                                                    <span class="badge bg-warning">Programada</span>
                                                @elseif($sesion->estado === 'finalizada')
                                                    <span class="badge bg-secondary">Finalizada</span>
                                                @elseif($sesion->estado === 'cancelada')
                                                    <span class="badge bg-danger">Cancelada</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text text-muted">{{ Str::limit($sesion->descripcion, 80) }}</p>
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Fecha</small>
                                                    <strong>{{ $sesion->fecha_inicio ? $sesion->fecha_inicio->format('d/m/Y') : '-' }}</strong>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Participantes</small>
                                                    <strong>{{ $sesion->participantes_count ?? 0 }}/{{ $sesion->max_participantes ?? '∞' }}</strong>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-circle me-2"></i>
                                                <small class="text-muted">{{ $sesion->instructor->name ?? 'Sin asignar' }}</small>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="btn-group w-100" role="group">
                                                <a href="{{ route('sesiones.show', $sesion) }}" 
                                                   class="btn btn-outline-info btn-sm">
                                                    <i class="bi bi-eye me-1"></i>
                                                    Ver
                                                </a>
                                                @if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor')
                                                <a href="{{ route('sesiones.edit', $sesion) }}" 
                                                   class="btn btn-outline-warning btn-sm">
                                                    <i class="bi bi-pencil me-1"></i>
                                                    Editar
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-event display-1 text-muted"></i>
                            <h4 class="text-muted mt-3">No hay sesiones disponibles</h4>
                            <p class="text-muted">Crea tu primera sesión para comenzar a organizar los simulacros de juicios.</p>
                            @if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor')
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearSesionModal">
                                <i class="bi bi-plus-circle me-2"></i>
                                Crear Primera Sesión
                            </button>
                            @endif
                        </div>
                    @endif
                </div>
                
                @if($sesiones->hasPages())
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $sesiones->firstItem() }} a {{ $sesiones->lastItem() }} de {{ $sesiones->total() }} resultados
                        </div>
                        <div>
                            {{ $sesiones->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Sesión -->
<div class="modal fade" id="crearSesionModal" tabindex="-1" aria-labelledby="crearSesionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('sesiones.store') }}" method="POST">
                @csrf
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
                                   class="form-control @error('nombre') is-invalid @enderror" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="{{ old('nombre') }}" 
                                   placeholder="Ej: Juicio Penal - Robo"
                                   required>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select @error('tipo') is-invalid @enderror" 
                                    id="tipo" 
                                    name="tipo" 
                                    required>
                                <option value="">Seleccionar tipo</option>
                                <option value="civil" {{ old('tipo') === 'civil' ? 'selected' : '' }}>Civil</option>
                                <option value="penal" {{ old('tipo') === 'penal' ? 'selected' : '' }}>Penal</option>
                                <option value="laboral" {{ old('tipo') === 'laboral' ? 'selected' : '' }}>Laboral</option>
                                <option value="administrativo" {{ old('tipo') === 'administrativo' ? 'selected' : '' }}>Administrativo</option>
                            </select>
                            @error('tipo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                      id="descripcion" 
                                      name="descripcion" 
                                      rows="3" 
                                      placeholder="Describe el caso y los objetivos de la sesión...">{{ old('descripcion') }}</textarea>
                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="fecha_inicio" class="form-label">Fecha de Inicio <span class="text-danger">*</span></label>
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
                            <label for="max_participantes" class="form-label">Máx. Participantes</label>
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
@endsection