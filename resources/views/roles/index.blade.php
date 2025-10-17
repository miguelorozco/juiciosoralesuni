@extends('layouts.app')

@section('title', 'Gestión de Roles')

@section('content')
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
                    <a href="{{ route('roles.create') }}" class="btn btn-primary">
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
                    <form method="GET" action="{{ route('roles.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="buscar" class="form-label">Buscar</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="buscar" 
                                   name="buscar" 
                                   value="{{ request('buscar') }}" 
                                   placeholder="Nombre o descripción...">
                        </div>
                        <div class="col-md-3">
                            <label for="activo" class="form-label">Estado</label>
                            <select class="form-select" id="activo" name="activo">
                                <option value="">Todos</option>
                                <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activos</option>
                                <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort_by" class="form-label">Ordenar por</label>
                            <select class="form-select" id="sort_by" name="sort_by">
                                <option value="orden" {{ request('sort_by') === 'orden' ? 'selected' : '' }}>Orden</option>
                                <option value="nombre" {{ request('sort_by') === 'nombre' ? 'selected' : '' }}>Nombre</option>
                                <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Fecha creación</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="sort_order" class="form-label">Dirección</label>
                            <select class="form-select" id="sort_order" name="sort_order">
                                <option value="asc" {{ request('sort_order') === 'asc' ? 'selected' : '' }}>Ascendente</option>
                                <option value="desc" {{ request('sort_order') === 'desc' ? 'selected' : '' }}>Descendente</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="bi bi-search me-1"></i>
                                Filtrar
                            </button>
                            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
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
                            <span class="badge bg-primary ms-2">{{ $roles->total() }}</span>
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
                    @if($roles->count() > 0)
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
                                    @foreach($roles as $rol)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="{{ $rol->id }}">
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $rol->orden }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle me-2" 
                                                     style="width: 20px; height: 20px; background-color: {{ $rol->color }};"></div>
                                                <small class="text-muted">{{ $rol->color }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($rol->icono)
                                                    <i class="bi bi-{{ $rol->icono }} me-2"></i>
                                                @endif
                                                <strong>{{ $rol->nombre }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ Str::limit($rol->descripcion, 50) }}</span>
                                        </td>
                                        <td>
                                            @if($rol->icono)
                                                <i class="bi bi-{{ $rol->icono }} fs-5"></i>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($rol->activo)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('roles.show', $rol) }}" 
                                                   class="btn btn-outline-info" 
                                                   title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('roles.edit', $rol) }}" 
                                                   class="btn btn-outline-warning" 
                                                   title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('roles.toggle-activo', $rol) }}" 
                                                      method="POST" 
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-outline-{{ $rol->activo ? 'secondary' : 'success' }}" 
                                                            title="{{ $rol->activo ? 'Desactivar' : 'Activar' }}">
                                                        <i class="bi bi-{{ $rol->activo ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('roles.destroy', $rol) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de que quieres eliminar este rol?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-outline-danger" 
                                                            title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
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
                                @foreach($roles as $rol)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                @if($rol->icono)
                                                    <i class="bi bi-{{ $rol->icono }} me-2"></i>
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
                                            <p class="card-text text-muted">{{ $rol->descripcion }}</p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">Orden: {{ $rol->orden }}</small>
                                                <small class="text-muted">{{ $rol->created_at->format('d/m/Y') }}</small>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="btn-group w-100" role="group">
                                                <a href="{{ route('roles.show', $rol) }}" 
                                                   class="btn btn-outline-info btn-sm">
                                                    <i class="bi bi-eye me-1"></i>
                                                    Ver
                                                </a>
                                                <a href="{{ route('roles.edit', $rol) }}" 
                                                   class="btn btn-outline-warning btn-sm">
                                                    <i class="bi bi-pencil me-1"></i>
                                                    Editar
                                                </a>
                                                <form action="{{ route('roles.toggle-activo', $rol) }}" 
                                                      method="POST" 
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-outline-{{ $rol->activo ? 'secondary' : 'success' }} btn-sm">
                                                        <i class="bi bi-{{ $rol->activo ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-people display-1 text-muted"></i>
                            <h4 class="text-muted mt-3">No hay roles disponibles</h4>
                            <p class="text-muted">Crea tu primer rol para comenzar a organizar los simulacros de juicios.</p>
                            <a href="{{ route('roles.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>
                                Crear Primer Rol
                            </a>
                        </div>
                    @endif
                </div>
                
                @if($roles->hasPages())
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $roles->firstItem() }} a {{ $roles->lastItem() }} de {{ $roles->total() }} resultados
                        </div>
                        <div>
                            {{ $roles->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
                @endif
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
@endsection
