@extends('layouts.app')

@section('title', 'Editor de Diálogos - ' . $escenario->nombre)

@section('content')
<style>
        .flujo-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
        }
        
        .rol-flujo {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .rol-flujo:hover {
            border-color: #007bff;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
        }
        
        .rol-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .rol-nombre {
            font-size: 1.5rem;
            font-weight: bold;
            color: #495057;
        }
        
        .rol-color {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 15px;
        }
        
        .dialogo-secuencia {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .dialogo-nodo {
            background: white;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 15px;
            min-width: 200px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .dialogo-nodo:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.2);
        }
        
        .dialogo-nodo.automatico {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .dialogo-nodo.decision {
            border-color: #ffc107;
            background: #fff3cd;
        }
        
        .dialogo-nodo.final {
            border-color: #dc3545;
            background: #f8d7da;
        }
        
        .nodo-titulo {
            font-weight: bold;
            margin-bottom: 8px;
            color: #495057;
        }
        
        .nodo-contenido {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .nodo-tipo {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .nodo-tipo.automatico {
            background: #28a745;
        }
        
        .nodo-tipo.decision {
            background: #ffc107;
            color: #212529;
        }
        
        .nodo-tipo.final {
            background: #dc3545;
        }
        
        .opciones-container {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .opcion {
            background: #e9ecef;
            border: 2px solid #6c757d;
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.85rem;
        }
        
        .opcion:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .opcion.opcion-a {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .opcion.opcion-b {
            border-color: #ffc107;
            background: #fff3cd;
        }
        
        .opcion.opcion-c {
            border-color: #fd7e14;
            background: #ffeaa7;
        }
        
        .conexion-flecha {
            color: #6c757d;
            font-size: 1.2rem;
            margin: 0 5px;
        }
        
        .agregar-dialogo {
            background: #f8f9fa;
            border: 2px dashed #6c757d;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #6c757d;
        }
        
        .agregar-dialogo:hover {
            border-color: #007bff;
            color: #007bff;
            background: #e7f3ff;
        }
        
        .acciones-rol {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }
        
        .btn-rol {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">
                            <i class="bi bi-diagram-3 me-2"></i>
                            Editor de Diálogos - {{ $escenario->nombre }}
                        </h1>
                        <p class="text-muted mb-0">{{ $escenario->descripcion }}</p>
                    </div>
                    <div>
                        <a href="/panel-dialogos" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-arrow-left me-1"></i>
                            Volver
                        </a>
                        <button class="btn btn-success me-2">
                            <i class="bi bi-check-circle me-1"></i>
                            Guardar
                        </button>
                        <button class="btn btn-primary">
                            <i class="bi bi-eye me-1"></i>
                            Previsualizar
                        </button>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Nuevo Sistema:</strong> Cada rol tiene su propio flujo de diálogos. Los nodos automáticos se ejecutan sin intervención del usuario, mientras que los nodos de decisión requieren que el usuario elija entre opciones.
                </div>
                
                <div class="flujo-container" x-data="dialogoEditorFlujo()">
                    @foreach($escenario->roles as $rol)
                        <div class="rol-flujo">
                            <div class="rol-header">
                                <div class="d-flex align-items-center">
                                    <div class="rol-color" style="background: {{ $rol->configuracion['color'] ?? '#007bff' }};"></div>
                                    <div class="rol-nombre">{{ $rol->nombre }}</div>
                                    @if($rol->requerido)
                                        <span class="badge bg-primary ms-2">Requerido</span>
                                    @else
                                        <span class="badge bg-secondary ms-2">Opcional</span>
                                    @endif
                                </div>
                                <div class="acciones-rol">
                                    <button class="btn btn-outline-primary btn-rol" @click="agregarDialogo({{ $rol->id }})">
                                        <i class="bi bi-plus"></i> Agregar Diálogo
                                    </button>
                                    <button class="btn btn-outline-secondary btn-rol" @click="configurarRol({{ $rol->id }})">
                                        <i class="bi bi-gear"></i> Configurar
                                    </button>
                                </div>
                            </div>
                            
                            <div class="dialogo-secuencia">
                                @if($rol->flujos->count() > 0)
                                    @foreach($rol->flujos as $flujo)
                                        @if($flujo->dialogos->count() > 0)
                                            @foreach($flujo->dialogos->sortBy('orden') as $index => $dialogo)
                                                <div class="dialogo-nodo {{ $dialogo->tipo }}">
                                                    <div class="nodo-tipo {{ $dialogo->tipo }}">
                                                        @if($dialogo->tipo === 'automatico')
                                                            AUTO
                                                        @elseif($dialogo->tipo === 'decision')
                                                            DECISIÓN
                                                        @elseif($dialogo->tipo === 'final')
                                                            FINAL
                                                        @endif
                                                    </div>
                                                    <div class="nodo-titulo">{{ $dialogo->titulo }}</div>
                                                    <div class="nodo-contenido">
                                                        {{ Str::limit($dialogo->contenido, 100) }}
                                                    </div>
                                                    
                                                    @if($dialogo->tipo === 'decision' && $dialogo->opciones->count() > 0)
                                                        <div class="opciones-container">
                                                            @foreach($dialogo->opciones->sortBy('orden') as $opcion)
                                                                <div class="opcion opcion-{{ strtolower($opcion->letra) }}" style="border-color: {{ $opcion->color }}; background: {{ $opcion->color }}20;">
                                                                    <strong>{{ $opcion->letra }}:</strong> {{ $opcion->texto }}
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                @if($index < $flujo->dialogos->count() - 1)
                                                    <div class="conexion-flecha">
                                                        <i class="bi bi-arrow-right"></i>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @else
                                            <div class="agregar-dialogo" @click="agregarDialogo({{ $rol->id }})">
                                                <i class="bi bi-plus-circle fs-1"></i>
                                                <p class="mb-0">No hay diálogos en este flujo. Haz clic para agregar el primero.</p>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="agregar-dialogo" @click="agregarDialogo({{ $rol->id }})">
                                        <i class="bi bi-plus-circle fs-1"></i>
                                        <p class="mb-0">No hay flujos para este rol. Haz clic para crear el primer flujo.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function dialogoEditorFlujo() {
        return {
            init() {
                console.log('Editor de flujo por rol inicializado');
            },
            
            agregarDialogo(rolId) {
                console.log('Agregar diálogo al rol:', rolId);
            },
            
            configurarRol(rolId) {
                console.log('Configurar rol:', rolId);
            }
        }
    }
</script>
@endsection
