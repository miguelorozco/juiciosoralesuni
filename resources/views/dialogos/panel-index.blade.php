@extends('layouts.app')

@section('title', 'Panel de Diálogos - Nuevo Sistema')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="bi bi-diagram-3 me-2"></i>
                    Panel de Diálogos - Nuevo Sistema
                </h1>
                <div>
                    <a href="/panel-dialogos/create" class="btn btn-success me-2">
                        <i class="bi bi-plus-circle me-1"></i>
                        Nuevo Escenario
                    </a>
                    <a href="/dialogos-legacy" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>
                        Sistema Legacy
                    </a>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Nuevo Sistema de Diálogos:</strong> Este es el nuevo sistema basado en flujos por rol. Cada escenario contiene roles específicos con sus propios flujos de diálogos.
            </div>
            
            <div class="row">
                <!-- Lista de Escenarios -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-collection me-2"></i>
                                Escenarios de Diálogo
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($escenarios->count() === 0)
                                <div class="text-center py-4">
                                    <i class="bi bi-inbox display-1 text-muted"></i>
                                    <p class="text-muted">No hay escenarios disponibles</p>
                                    <a href="/panel-dialogos/create" class="btn btn-primary">
                                        <i class="bi bi-plus me-1"></i>
                                        Crear Primer Escenario
                                    </a>
                                </div>
                            @else
                                <div class="row g-3">
                                    @foreach($escenarios as $escenario)
                                        <div class="col-md-6">
                                            <div class="card h-100 border-0 shadow-sm">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <h6 class="card-title mb-0">{{ $escenario->nombre }}</h6>
                                                        <span class="badge 
                                                            @if($escenario->estado === 'activo') bg-success
                                                            @elseif($escenario->estado === 'borrador') bg-warning
                                                            @else bg-secondary
                                                            @endif">
                                                            {{ $escenario->estado }}
                                                        </span>
                                                    </div>
                                                    
                                                    <p class="card-text text-muted small">{{ $escenario->descripcion }}</p>
                                                    
                                                    <div class="mb-3">
                                                        <span class="badge bg-primary me-1">{{ $escenario->configuracion['tipo'] ?? 'N/A' }}</span>
                                                        @if($escenario->configuracion['publico'] ?? false)
                                                            <span class="badge bg-info me-1">Público</span>
                                                        @else
                                                            <span class="badge bg-secondary me-1">Privado</span>
                                                        @endif
                                                    </div>
                                                    
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-4">
                                                            <div class="text-center p-2 bg-light rounded">
                                                                <div class="h6 mb-0 text-primary">{{ $escenario->total_roles }}</div>
                                                                <small class="text-muted">Roles</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="text-center p-2 bg-light rounded">
                                                                <div class="h6 mb-0 text-success">{{ $escenario->total_flujos }}</div>
                                                                <small class="text-muted">Flujos</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="text-center p-2 bg-light rounded">
                                                                <div class="h6 mb-0 text-info">{{ $escenario->total_dialogos }}</div>
                                                                <small class="text-muted">Diálogos</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex gap-2">
                                                        <a href="/panel-dialogos/{{ $escenario->id }}/editor" 
                                                           class="btn btn-primary btn-sm flex-fill">
                                                            <i class="bi bi-pencil me-1"></i>
                                                            Editar
                                                        </a>
                                                        <a href="/panel-dialogos/{{ $escenario->id }}" 
                                                           class="btn btn-outline-info btn-sm">
                                                            <i class="bi bi-eye me-1"></i>
                                                            Ver
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                
                                <!-- Paginación -->
                                @if($escenarios->hasPages())
                                    <div class="mt-4">
                                        {{ $escenarios->links() }}
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Panel de Información -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                Información del Sistema
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>Características del Nuevo Sistema:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <strong>Flujo por Rol:</strong> Cada rol tiene su propia secuencia de diálogos
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <strong>3 Opciones Máximo:</strong> Cada decisión tiene máximo 3 opciones (A, B, C)
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <strong>Tipos de Diálogo:</strong> Automático, Decisión y Final
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <strong>Visualización Clara:</strong> Conexiones visibles entre diálogos
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <strong>Escalable:</strong> Fácil agregar nuevos roles y flujos
                                </li>
                            </ul>
                            
                            <hr>
                            
                            <h6>Tipos de Diálogo:</h6>
                            <div class="mb-2">
                                <span class="badge bg-success me-2">AUTO</span>
                                <small>Se ejecuta automáticamente</small>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-warning me-2">DECISIÓN</span>
                                <small>Requiere elección del usuario</small>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-danger me-2">FINAL</span>
                                <small>Punto final del flujo</small>
                            </div>
                            
                            <hr>
                            
                            <div class="text-center">
                                <a href="/panel-dialogos/create" class="btn btn-success w-100">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Crear Nuevo Escenario
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
