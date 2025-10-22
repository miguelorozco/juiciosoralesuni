@extends('layouts.app')

@section('title', 'Crear Nuevo Escenario de Diálogo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="text-center mb-4">
                <h1 class="display-6 text-primary">
                    <i class="bi bi-plus-circle me-2"></i>
                    Crear Nuevo Escenario de Diálogo
                </h1>
                <p class="lead text-muted">Diseña diálogos ramificados con el nuevo sistema de flujo por rol</p>
            </div>
            
            <!-- Indicador de Pasos -->
            <div class="step-indicator">
                <div class="step active">1</div>
                <div class="step-line"></div>
                <div class="step pending">2</div>
                <div class="step-line"></div>
                <div class="step pending">3</div>
            </div>
            
            <!-- Ejemplo Interactivo -->
            <div class="ejemplo-container">
                <h3 class="mb-4">
                    <i class="bi bi-lightbulb me-2"></i>
                    Ejemplo: Sistema de Flujo por Rol
                </h3>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="ejemplo-rol">
                            <h5 class="mb-3">
                                <div class="rol-color-preview" style="background: #007bff;"></div>
                                JUEZ
                            </h5>
                            
                            <div class="ejemplo-dialogo">
                                <strong>1. Apertura del Juicio</strong>
                                <small class="d-block text-muted">(Automático)</small>
                                <p class="mb-0">"Se abre la audiencia del caso..."</p>
                            </div>
                            
                            <div class="ejemplo-dialogo">
                                <strong>2. Escuchar Argumentos</strong>
                                <small class="d-block text-muted">(Automático)</small>
                                <p class="mb-0">"Proceda con sus argumentos..."</p>
                            </div>
                            
                            <div class="ejemplo-dialogo">
                                <strong>3. Decisión Final</strong>
                                <small class="d-block text-muted">(Decisión)</small>
                                <p class="mb-2">"¿Cuál es su decisión?"</p>
                                <div class="ejemplo-opcion a">A: Absolver al acusado</div>
                                <div class="ejemplo-opcion b">B: Condenar al acusado</div>
                                <div class="ejemplo-opcion c">C: Suspender audiencia</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="ejemplo-rol">
                            <h5 class="mb-3">
                                <div class="rol-color-preview" style="background: #dc3545;"></div>
                                FISCAL
                            </h5>
                            
                            <div class="ejemplo-dialogo">
                                <strong>1. Presentación de Cargos</strong>
                                <small class="d-block text-muted">(Automático)</small>
                                <p class="mb-0">"Se presentan los cargos contra el imputado..."</p>
                            </div>
                            
                            <div class="ejemplo-dialogo">
                                <strong>2. Estrategia de Acusación</strong>
                                <small class="d-block text-muted">(Decisión)</small>
                                <p class="mb-2">"¿Qué estrategia utilizará?"</p>
                                <div class="ejemplo-opcion a">A: Presentar evidencia directa</div>
                                <div class="ejemplo-opcion b">B: Interrogar testigos</div>
                                <div class="ejemplo-opcion c">C: Solicitar peritaje</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="ejemplo-rol">
                            <h5 class="mb-3">
                                <div class="rol-color-preview" style="background: #28a745;"></div>
                                DEFENSOR
                            </h5>
                            
                            <div class="ejemplo-dialogo">
                                <strong>1. Defensa del Cliente</strong>
                                <small class="d-block text-muted">(Automático)</small>
                                <p class="mb-0">"En defensa de mi cliente..."</p>
                            </div>
                            
                            <div class="ejemplo-dialogo">
                                <strong>2. Estrategia de Defensa</strong>
                                <small class="d-block text-muted">(Decisión)</small>
                                <p class="mb-2">"¿Qué estrategia utilizará?"</p>
                                <div class="ejemplo-opcion a">A: Negar los hechos</div>
                                <div class="ejemplo-opcion b">B: Alegar atenuantes</div>
                                <div class="ejemplo-opcion c">C: Solicitar absolución</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <small class="opacity-75">
                        <i class="bi bi-info-circle me-1"></i>
                        Cada rol tiene su propio flujo independiente con máximo 3 opciones por decisión
                    </small>
                </div>
            </div>
            
            <!-- Formulario de Creación -->
            <div class="form-container" x-data="crearEscenario()">
                <h4 class="mb-4">
                    <i class="bi bi-gear me-2"></i>
                    Configuración del Escenario
                </h4>
                
                <form @submit.prevent="crearEscenario">
                    <!-- Información Básica -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre del Escenario</label>
                            <input type="text" class="form-control" id="nombre" x-model="escenario.nombre" 
                                   placeholder="Ej: Juicio Penal por Robo" required>
                        </div>
                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo de Juicio</label>
                            <select class="form-select" id="tipo" x-model="escenario.tipo" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="penal">Penal</option>
                                <option value="civil">Civil</option>
                                <option value="laboral">Laboral</option>
                                <option value="administrativo">Administrativo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" rows="3" x-model="escenario.descripcion"
                                  placeholder="Describe brevemente el escenario del juicio..."></textarea>
                    </div>
                    
                    <!-- Roles del Escenario -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="bi bi-people me-2"></i>
                                Seleccionar Roles del Escenario
                            </h5>
                            <small class="text-muted">Selecciona los roles disponibles que participarán en este escenario</small>
                        </div>
                        
                        <div class="row g-3">
                            @foreach($rolesDisponibles as $rol)
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100" :class="{ 'border-primary bg-primary bg-opacity-10': escenario.rolesSeleccionados.includes({{ $rol->id }}) }">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   :id="'rol_{{ $rol->id }}'"
                                                   :value="{{ $rol->id }}"
                                                   x-model="escenario.rolesSeleccionados">
                                            <label class="form-check-label w-100" :for="'rol_{{ $rol->id }}'">
                                                <div class="d-flex align-items-center mb-2">
                                                    @if($rol->icono)
                                                        <i class="{{ $rol->icono }} me-2" style="color: {{ $rol->color }};"></i>
                                                    @else
                                                        <div class="rol-color-preview me-2" style="background: {{ $rol->color }};"></div>
                                                    @endif
                                                    <strong>{{ $rol->nombre }}</strong>
                                                </div>
                                                @if($rol->descripcion)
                                                    <p class="small text-muted mb-2">{{ $rol->descripcion }}</p>
                                                @endif
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           :id="'requerido_{{ $rol->id }}'"
                                                           x-model="escenario.rolesRequeridos"
                                                           :value="{{ $rol->id }}"
                                                           :disabled="!escenario.rolesSeleccionados.includes({{ $rol->id }})">
                                                    <label class="form-check-label small" :for="'requerido_{{ $rol->id }}'">
                                                        Rol requerido
                                                    </label>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <div x-show="escenario.rolesSeleccionados.length === 0" class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Selecciona al menos un rol</strong> para el escenario de diálogo.
                        </div>
                        
                        <div x-show="escenario.rolesSeleccionados.length > 0" class="alert alert-success mt-3">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>Roles seleccionados:</strong> <span x-text="escenario.rolesSeleccionados.length"></span> rol(es)
                        </div>
                    </div>
                    
                    <!-- Configuración Adicional -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" x-model="escenario.publico">
                            <label class="form-check-label">
                                <strong>Escenario Público</strong>
                                <small class="d-block text-muted">Otros usuarios podrán usar este escenario</small>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Botones de Acción -->
                    <div class="d-flex justify-content-between">
                        <a href="/panel-dialogos" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-success" :disabled="creando">
                            <span x-show="!creando">
                                <i class="bi bi-check-circle me-1"></i>
                                Crear Escenario
                            </span>
                            <span x-show="creando">
                                <span class="spinner-border spinner-border-sm me-1"></span>
                                Creando...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function crearEscenario() {
        return {
            escenario: {
                nombre: '',
                descripcion: '',
                tipo: '',
                publico: false,
                rolesSeleccionados: [],
                rolesRequeridos: []
            },
            creando: false,
            
            async crearEscenario() {
                if (this.creando) return;
                
                // Validar que se hayan seleccionado roles
                if (this.escenario.rolesSeleccionados.length === 0) {
                    this.mostrarMensaje('Debes seleccionar al menos un rol para el escenario', 'error');
                    return;
                }
                
                this.creando = true;
                
                try {
                    // Preparar datos para envío
                    const datosEscenario = {
                        nombre: this.escenario.nombre,
                        descripcion: this.escenario.descripcion,
                        tipo: this.escenario.tipo,
                        publico: this.escenario.publico,
                        roles: this.escenario.rolesSeleccionados.map(rolId => ({
                            rol_id: rolId,
                            requerido: this.escenario.rolesRequeridos.includes(rolId)
                        }))
                    };
                    
                    const response = await fetch('/api/panel-dialogos', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Authorization': 'Bearer ' + localStorage.getItem('token')
                        },
                        body: JSON.stringify(datosEscenario)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Mostrar mensaje de éxito
                        this.mostrarMensaje('Escenario creado exitosamente', 'success');
                        
                        // Redirigir al editor
                        setTimeout(() => {
                            window.location.href = `/panel-dialogos/${data.data.id}/editor`;
                        }, 1500);
                    } else {
                        this.mostrarMensaje(data.message || 'Error al crear el escenario', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.mostrarMensaje('Error de conexión', 'error');
                } finally {
                    this.creando = false;
                }
            },
            
            mostrarMensaje(mensaje, tipo) {
                // Crear toast de Bootstrap
                const toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(toastContainer);
                
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${tipo === 'success' ? 'success' : 'danger'} border-0`;
                toast.setAttribute('role', 'alert');
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-${tipo === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                            ${mensaje}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                
                toastContainer.appendChild(toast);
                
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                // Limpiar después de 5 segundos
                setTimeout(() => {
                    toastContainer.remove();
                }, 5000);
            }
        }
    }
</script>

<style>
    .ejemplo-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 30px;
        color: white;
        margin-bottom: 30px;
    }
    
    .ejemplo-rol {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .ejemplo-dialogo {
        background: rgba(255, 255, 255, 0.9);
        color: #333;
        border-radius: 8px;
        padding: 15px;
        margin: 10px 0;
        border-left: 4px solid #007bff;
    }
    
    .ejemplo-opcion {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 6px;
        padding: 8px 12px;
        margin: 5px;
        display: inline-block;
        font-size: 0.9rem;
    }
    
    .ejemplo-opcion.a { border-left: 3px solid #28a745; }
    .ejemplo-opcion.b { border-left: 3px solid #ffc107; }
    .ejemplo-opcion.c { border-left: 3px solid #fd7e14; }
    
    .form-container {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .rol-color-preview {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 10px;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .step-indicator {
        display: flex;
        justify-content: center;
        margin-bottom: 30px;
    }
    
    .step {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 10px;
        font-weight: bold;
        color: white;
    }
    
    .step.active { background: #007bff; }
    .step.completed { background: #28a745; }
    .step.pending { background: #6c757d; }
    
    .step-line {
        width: 50px;
        height: 2px;
        background: #dee2e6;
        margin-top: 19px;
    }
    
    .step-line.completed { background: #28a745; }
</style>
@endsection
