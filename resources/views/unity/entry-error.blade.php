@extends('layouts.app')

@section('title', 'Error de Entrada a Unity')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error de Entrada a Unity
                    </h4>
                </div>
                
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="bi bi-x-circle text-danger" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h5 class="text-danger mb-3">No se pudo procesar la entrada a Unity</h5>
                    
                    <div class="alert alert-danger">
                        <strong>Error:</strong> {{ $error }}
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('sesiones.index') }}" class="btn btn-primary me-2">
                            <i class="bi bi-arrow-left me-2"></i>
                            Volver a Sesiones
                        </a>
                        
                        <button onclick="window.location.reload()" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-clockwise me-2"></i>
                            Reintentar
                        </button>
                    </div>
                </div>
                
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        Si el problema persiste, contacta al administrador del sistema.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
