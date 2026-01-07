@extends('layouts.app')

@section('title', 'Diálogos V2')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-diagram-3 me-2"></i>
            Diálogos V2
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('dialogos-v2.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i>
                Nuevo Diálogo
            </a>
            <a href="{{ route('panel-dialogos.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-collection me-1"></i>
                Escenarios (flujos por rol)
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Listado</h5>
            <span class="text-muted small">{{ $dialogos->total() }} diálogos</span>
        </div>
        <div class="card-body p-0">
            @if($dialogos->count() === 0)
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="text-muted mt-2 mb-3">No hay diálogos creados</p>
                    <a href="{{ route('dialogos-v2.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>
                        Crear primer diálogo
                    </a>
                </div>
            @else
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
                            @foreach($dialogos as $dialogo)
                                <tr>
                                    <td class="fw-semibold">{{ $dialogo->id }}</td>
                                    <td>{{ $dialogo->nombre }}</td>
                                    <td class="text-muted small">{{ Str::limit($dialogo->descripcion, 80) }}</td>
                                    <td>
                                        <span class="badge 
                                            @if($dialogo->estado === 'activo') bg-success
                                            @elseif($dialogo->estado === 'borrador') bg-warning
                                            @else bg-secondary
                                            @endif">
                                            {{ ucfirst($dialogo->estado) }}
                                        </span>
                                    </td>
                                    <td>{{ $dialogo->version ?? '1.0.0' }}</td>
                                    <td><span class="badge bg-primary">{{ $dialogo->nodos_count }}</span></td>
                                    <td class="text-muted small">{{ $dialogo->updated_at?->format('Y-m-d H:i') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('dialogos-v2.editor', ['dialogo' => $dialogo->id]) }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-pencil me-1"></i> Editar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($dialogos->hasPages())
                    <div class="p-3">
                        {{ $dialogos->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
