@extends('layouts.app')

@section('title', 'Configuración - Simulador de Juicios Orales')

@section('content')
<div x-data="configuracionManager()" class="container-fluid py-4">
    <!-- Header Principal -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="bi bi-gear me-2 text-primary"></i>
                        Configuración del Sistema
                    </h1>
                    <p class="text-muted mb-0">
                        Administra la configuración general del simulador
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" @click="resetearConfiguracion()">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Restaurar Valores
                    </button>
                    <button class="btn btn-primary" @click="guardarConfiguracion()" :disabled="guardando">
                        <i class="bi bi-check-lg me-2"></i>
                        <span x-text="guardando ? 'Guardando...' : 'Guardar Cambios'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Configuraciones Generales -->
        <div class="col-lg-8">
            <!-- Configuración de Sesiones -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-play-circle me-2 text-primary"></i>
                        Configuración de Sesiones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Duración Máxima de Sesión (minutos)</label>
                            <input type="number" class="form-control" x-model="configuracion.duracion_maxima_sesion" min="30" max="480">
                            <div class="form-text">Tiempo máximo permitido para una sesión de juicio</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Máximo de Participantes por Sesión</label>
                            <input type="number" class="form-control" x-model="configuracion.max_participantes_sesion" min="2" max="20">
                            <div class="form-text">Número máximo de participantes en una sesión</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Tiempo de Espera para Respuestas (segundos)</label>
                            <input type="number" class="form-control" x-model="configuracion.tiempo_espera_respuesta" min="10" max="300">
                            <div class="form-text">Tiempo límite para que los participantes respondan</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Puntuación Máxima por Respuesta</label>
                            <input type="number" class="form-control" x-model="configuracion.puntuacion_maxima" min="1" max="100">
                            <div class="form-text">Puntuación máxima que se puede obtener por respuesta</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de Usuarios -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-people me-2 text-success"></i>
                        Configuración de Usuarios
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" x-model="configuracion.registro_usuarios_habilitado">
                                <label class="form-check-label fw-medium">
                                    Registro de Usuarios Habilitado
                                </label>
                                <div class="form-text">Permitir que nuevos usuarios se registren</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" x-model="configuracion.verificacion_email_requerida">
                                <label class="form-check-label fw-medium">
                                    Verificación de Email Requerida
                                </label>
                                <div class="form-text">Los usuarios deben verificar su email al registrarse</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Límite de Intentos de Login</label>
                            <input type="number" class="form-control" x-model="configuracion.limite_intentos_login" min="3" max="10">
                            <div class="form-text">Número máximo de intentos de login fallidos</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Tiempo de Bloqueo (minutos)</label>
                            <input type="number" class="form-control" x-model="configuracion.tiempo_bloqueo" min="5" max="60">
                            <div class="form-text">Tiempo de bloqueo después de exceder el límite de intentos</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de Notificaciones -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-bell me-2 text-warning"></i>
                        Configuración de Notificaciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" x-model="configuracion.notificaciones_email_habilitadas">
                                <label class="form-check-label fw-medium">
                                    Notificaciones por Email
                                </label>
                                <div class="form-text">Enviar notificaciones por correo electrónico</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" x-model="configuracion.notificaciones_push_habilitadas">
                                <label class="form-check-label fw-medium">
                                    Notificaciones Push
                                </label>
                                <div class="form-text">Enviar notificaciones push en tiempo real</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" x-model="configuracion.notificar_inicio_sesion">
                                <label class="form-check-label fw-medium">
                                    Notificar Inicio de Sesión
                                </label>
                                <div class="form-text">Notificar cuando una sesión inicie</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" x-model="configuracion.notificar_fin_sesion">
                                <label class="form-check-label fw-medium">
                                    Notificar Fin de Sesión
                                </label>
                                <div class="form-text">Notificar cuando una sesión termine</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de Integración Unity -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-controller me-2 text-info"></i>
                        Configuración de Integración Unity
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" x-model="configuracion.integracion_unity_habilitada">
                                <label class="form-check-label fw-medium">
                                    Integración Unity Habilitada
                                </label>
                                <div class="form-text">Permitir integración con Unity 3D</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">URL del Servidor Unity</label>
                            <input type="url" class="form-control" x-model="configuracion.unity_server_url" placeholder="ws://localhost:8080">
                            <div class="form-text">URL del servidor WebSocket de Unity</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Puerto del Servidor Unity</label>
                            <input type="number" class="form-control" x-model="configuracion.unity_server_port" min="1000" max="65535">
                            <div class="form-text">Puerto del servidor Unity</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Timeout de Conexión (segundos)</label>
                            <input type="number" class="form-control" x-model="configuracion.unity_timeout" min="5" max="60">
                            <div class="form-text">Tiempo límite para conexiones con Unity</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Estado del Sistema -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-activity me-2 text-success"></i>
                        Estado del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Servidor Web</span>
                            <span class="badge bg-success">Activo</span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Base de Datos</span>
                            <span class="badge bg-success">Conectada</span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Servidor Unity</span>
                            <span class="badge" :class="configuracion.integracion_unity_habilitada ? 'bg-success' : 'bg-secondary'" 
                                  x-text="configuracion.integracion_unity_habilitada ? 'Conectado' : 'Deshabilitado'"></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Cache</span>
                            <span class="badge bg-success">Activo</span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Logs</span>
                            <span class="badge bg-success">Funcionando</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-lightning me-2 text-warning"></i>
                        Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" @click="limpiarCache()">
                            <i class="bi bi-trash me-2"></i>
                            Limpiar Cache
                        </button>
                        
                        <button class="btn btn-outline-warning" @click="regenerarLogs()">
                            <i class="bi bi-file-text me-2"></i>
                            Regenerar Logs
                        </button>
                        
                        <button class="btn btn-outline-info" @click="probarConexionUnity()">
                            <i class="bi bi-controller me-2"></i>
                            Probar Unity
                        </button>
                        
                        <button class="btn btn-outline-danger" @click="reiniciarSistema()">
                            <i class="bi bi-arrow-clockwise me-2"></i>
                            Reiniciar Sistema
                        </button>
                    </div>
                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-info-circle me-2 text-info"></i>
                        Información del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        <div>
                            <div class="text-muted small">Versión</div>
                            <div class="fw-semibold text-dark">v1.0.0</div>
                        </div>
                        
                        <div>
                            <div class="text-muted small">Laravel</div>
                            <div class="fw-semibold text-dark">12.x</div>
                        </div>
                        
                        <div>
                            <div class="text-muted small">PHP</div>
                            <div class="fw-semibold text-dark">8.2+</div>
                        </div>
                        
                        <div>
                            <div class="text-muted small">Última Actualización</div>
                            <div class="fw-semibold text-dark" x-text="formatDate(new Date())"></div>
                        </div>
                        
                        <div>
                            <div class="text-muted small">Espacio en Disco</div>
                            <div class="fw-semibold text-dark">2.5 GB / 10 GB</div>
                            <div class="progress mt-1" style="height: 6px;">
                                <div class="progress-bar bg-info" style="width: 25%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function configuracionManager() {
    return {
        configuracion: {
            duracion_maxima_sesion: 120,
            max_participantes_sesion: 10,
            tiempo_espera_respuesta: 30,
            puntuacion_maxima: 10,
            registro_usuarios_habilitado: true,
            verificacion_email_requerida: false,
            limite_intentos_login: 5,
            tiempo_bloqueo: 15,
            notificaciones_email_habilitadas: true,
            notificaciones_push_habilitadas: false,
            notificar_inicio_sesion: true,
            notificar_fin_sesion: true,
            integracion_unity_habilitada: false,
            unity_server_url: 'ws://localhost:8080',
            unity_server_port: 8080,
            unity_timeout: 30
        },
        guardando: false,
        
        init() {
            this.cargarConfiguracion();
        },
        
        async cargarConfiguracion() {
            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch('/api/configuraciones/sistema', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.configuracion = { ...this.configuracion, ...data.data };
                }
            } catch (error) {
                console.error('Error cargando configuración:', error);
            }
        },
        
        async guardarConfiguracion() {
            this.guardando = true;
            
            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch('/api/configuraciones/actualizar-multiples', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.configuracion)
                });
                
                if (response.ok) {
                    this.showToast('Configuración guardada exitosamente', 'success');
                } else {
                    const error = await response.json();
                    this.showToast(error.message || 'Error al guardar la configuración', 'error');
                }
            } catch (error) {
                this.showToast('Error de conexión', 'error');
            } finally {
                this.guardando = false;
            }
        },
        
        resetearConfiguracion() {
            if (confirm('¿Estás seguro de que quieres restaurar los valores por defecto?')) {
                this.configuracion = {
                    duracion_maxima_sesion: 120,
                    max_participantes_sesion: 10,
                    tiempo_espera_respuesta: 30,
                    puntuacion_maxima: 10,
                    registro_usuarios_habilitado: true,
                    verificacion_email_requerida: false,
                    limite_intentos_login: 5,
                    tiempo_bloqueo: 15,
                    notificaciones_email_habilitadas: true,
                    notificaciones_push_habilitadas: false,
                    notificar_inicio_sesion: true,
                    notificar_fin_sesion: true,
                    integracion_unity_habilitada: false,
                    unity_server_url: 'ws://localhost:8080',
                    unity_server_port: 8080,
                    unity_timeout: 30
                };
                this.showToast('Configuración restaurada', 'info');
            }
        },
        
        async limpiarCache() {
            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch('/api/configuraciones/limpiar-cache', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    this.showToast('Cache limpiado exitosamente', 'success');
                } else {
                    this.showToast('Error al limpiar el cache', 'error');
                }
            } catch (error) {
                this.showToast('Error de conexión', 'error');
            }
        },
        
        async regenerarLogs() {
            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch('/api/configuraciones/regenerar-logs', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    this.showToast('Logs regenerados exitosamente', 'success');
                } else {
                    this.showToast('Error al regenerar los logs', 'error');
                }
            } catch (error) {
                this.showToast('Error de conexión', 'error');
            }
        },
        
        async probarConexionUnity() {
            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch('/api/configuraciones/probar-unity', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        url: this.configuracion.unity_server_url,
                        port: this.configuracion.unity_server_port
                    })
                });
                
                if (response.ok) {
                    this.showToast('Conexión con Unity exitosa', 'success');
                } else {
                    this.showToast('Error al conectar con Unity', 'error');
                }
            } catch (error) {
                this.showToast('Error de conexión', 'error');
            }
        },
        
        async reiniciarSistema() {
            if (confirm('¿Estás seguro de que quieres reiniciar el sistema? Esto puede causar interrupciones temporales.')) {
                try {
                    const token = localStorage.getItem('auth_token');
                    const response = await fetch('/api/configuraciones/reiniciar-sistema', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (response.ok) {
                        this.showToast('Sistema reiniciado exitosamente', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        this.showToast('Error al reiniciar el sistema', 'error');
                    }
                } catch (error) {
                    this.showToast('Error de conexión', 'error');
                }
            }
        },
        
        formatDate(date) {
            return new Date(date).toLocaleDateString('es-ES');
        },
        
        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
            } text-white`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    }
}
</script>

<style>
.card {
    transition: all 0.3s ease;
    border-radius: 12px;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.form-control {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

.progress {
    border-radius: 10px;
    background-color: #f8f9fa;
}

.progress-bar {
    border-radius: 10px;
}

/* Animaciones suaves */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
}

/* Responsive mejoras */
@media (max-width: 768px) {
    .btn-lg {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .h2 {
        font-size: 1.5rem;
    }
}
</style>
@endsection
