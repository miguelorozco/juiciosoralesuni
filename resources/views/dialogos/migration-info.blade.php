@extends('layouts.app')

@section('title', 'Información sobre la Migración del Sistema de Diálogos')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="text-center mb-4">
                <h1 class="display-6 text-primary">
                    <i class="bi bi-arrow-repeat me-2"></i>
                    Migración del Sistema de Diálogos
                </h1>
                <p class="lead text-muted">Información sobre el nuevo sistema de diálogos ramificados</p>
            </div>
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                ¿Qué ha cambiado?
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="bi bi-lightbulb me-2"></i>
                                <strong>Nuevo Sistema:</strong> Hemos migrado a un sistema de diálogos más intuitivo y escalable basado en flujos por rol.
                            </div>
                            
                            <h6 class="text-primary mb-3">Principales Mejoras:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-check-circle text-success me-3 mt-1"></i>
                                        <div>
                                            <strong>Flujo por Rol:</strong> Cada rol tiene su propia secuencia de diálogos independiente
                                        </div>
                                    </div>
                                </li>
                                <li class="mb-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-check-circle text-success me-3 mt-1"></i>
                                        <div>
                                            <strong>Máximo 3 Opciones:</strong> Cada decisión tiene máximo 3 opciones (A, B, C) para mayor claridad
                                        </div>
                                    </div>
                                </li>
                                <li class="mb-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-check-circle text-success me-3 mt-1"></i>
                                        <div>
                                            <strong>Tipos de Diálogo:</strong> Automático, Decisión y Final para mejor organización
                                        </div>
                                    </div>
                                </li>
                                <li class="mb-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-check-circle text-success me-3 mt-1"></i>
                                        <div>
                                            <strong>Visualización Mejorada:</strong> Conexiones claras entre diálogos con colores distintivos
                                        </div>
                                    </div>
                                </li>
                                <li class="mb-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-check-circle text-success me-3 mt-1"></i>
                                        <div>
                                            <strong>Escalabilidad:</strong> Fácil agregar nuevos roles y flujos sin afectar otros
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Sistema Legacy
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">
                                El sistema anterior sigue disponible pero ya no se recomienda para nuevos proyectos. 
                                Los diálogos existentes pueden seguir funcionando normalmente.
                            </p>
                            
                            <div class="d-flex gap-2">
                                <a href="/dialogos-legacy" class="btn btn-outline-warning">
                                    <i class="bi bi-arrow-left me-1"></i>
                                    Acceder al Sistema Legacy
                                </a>
                                <a href="/panel-dialogos" class="btn btn-primary">
                                    <i class="bi bi-arrow-right me-1"></i>
                                    Ir al Nuevo Sistema
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-diagram-3 me-2"></i>
                                Nuevo Sistema
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="text-success mb-3">Estructura del Nuevo Sistema:</h6>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-collection text-primary me-2"></i>
                                    <strong>Escenarios</strong>
                                </div>
                                <small class="text-muted">Contenedores principales (ej: "Juicio Penal por Robo")</small>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-people text-info me-2"></i>
                                    <strong>Roles</strong>
                                </div>
                                <small class="text-muted">Participantes del escenario (Juez, Fiscal, Defensor, etc.)</small>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-diagram-2 text-warning me-2"></i>
                                    <strong>Flujos</strong>
                                </div>
                                <small class="text-muted">Secuencia de diálogos para cada rol</small>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-chat-dots text-danger me-2"></i>
                                    <strong>Diálogos</strong>
                                </div>
                                <small class="text-muted">Nodos individuales (Automático, Decisión, Final)</small>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-list-ul text-secondary me-2"></i>
                                    <strong>Opciones</strong>
                                </div>
                                <small class="text-muted">Respuestas disponibles (máximo 3: A, B, C)</small>
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
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-question-circle me-2"></i>
                                ¿Necesitas Ayuda?
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">
                                Si tienes preguntas sobre el nuevo sistema o necesitas migrar diálogos existentes, 
                                contacta al administrador del sistema.
                            </p>
                            
                            <div class="d-grid gap-2">
                                <a href="/dashboard" class="btn btn-outline-primary">
                                    <i class="bi bi-house me-1"></i>
                                    Volver al Dashboard
                                </a>
                                <a href="/panel-dialogos" class="btn btn-outline-success">
                                    <i class="bi bi-diagram-3 me-1"></i>
                                    Explorar Nuevo Sistema
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