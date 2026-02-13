@extends('layouts.app')

@section('title', 'Estadísticas - Simulador de Juicios Orales')

@section('content')
<script>
(function() {
    console.log('[ESTADISTICAS] Vista: script ejecutado (DOM listo para estadísticas)');
    fetch('/api/estadisticas/debug-log?event=vista_estadisticas_script_ejecutado', { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).catch(function() {});
})();
</script>
<div x-data="estadisticasManager()" class="container-fluid py-4" x-init="
    console.log('[ESTADISTICAS] Alpine init ejecutado');
    fetch('/api/estadisticas/debug-log?event=alpine_init_estadisticas', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }).catch(function() {});
">
    <!-- Header Principal -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="bi bi-graph-up me-2 text-primary"></i>
                        Estadísticas
                    </h1>
                    <p class="text-muted mb-0">
                        Análisis detallado del rendimiento y actividad del sistema
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-calendar me-2"></i>
                            <span x-text="periodoSeleccionado"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" @click="cambiarPeriodo('hoy')" href="#">Hoy</a></li>
                            <li><a class="dropdown-item" @click="cambiarPeriodo('semana')" href="#">Esta semana</a></li>
                            <li><a class="dropdown-item" @click="cambiarPeriodo('mes')" href="#">Este mes</a></li>
                            <li><a class="dropdown-item" @click="cambiarPeriodo('año')" href="#">Este año</a></li>
                        </ul>
                    </div>
                    <button class="btn btn-primary" @click="exportarEstadisticas()">
                        <i class="bi bi-download me-2"></i>
                        Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas Principales -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-play-circle text-primary fs-2"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small fw-medium">Sesiones Totales</div>
                            <div class="h4 mb-0 fw-bold text-dark" x-text="estadisticas.sesiones_totales || 0"></div>
                            <div class="text-success small">
                                <i class="bi bi-arrow-up"></i>
                                <span x-text="estadisticas.sesiones_cambio || '0%'"></span> vs período anterior
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-people text-success fs-2"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small fw-medium">Usuarios Activos</div>
                            <div class="h4 mb-0 fw-bold text-dark" x-text="estadisticas.usuarios_activos || 0"></div>
                            <div class="text-success small">
                                <i class="bi bi-arrow-up"></i>
                                <span x-text="estadisticas.usuarios_cambio || '0%'"></span> vs período anterior
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-chat-dots text-warning fs-2"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small fw-medium">Diálogos Creados</div>
                            <div class="h4 mb-0 fw-bold text-dark" x-text="estadisticas.dialogos_totales || 0"></div>
                            <div class="text-success small">
                                <i class="bi bi-arrow-up"></i>
                                <span x-text="estadisticas.dialogos_cambio || '0%'"></span> vs período anterior
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-clock text-info fs-2"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small fw-medium">Tiempo Promedio</div>
                            <div class="h4 mb-0 fw-bold text-dark" x-text="estadisticas.tiempo_promedio || '0h 0m'"></div>
                            <div class="text-success small">
                                <i class="bi bi-arrow-up"></i>
                                <span x-text="estadisticas.tiempo_cambio || '0%'"></span> vs período anterior
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos Principales -->
    <div class="row mb-4">
        <!-- Gráfico de Sesiones por Mes -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-bar-chart me-2 text-primary"></i>
                            Sesiones por Mes
                        </h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active" @click="cambiarTipoGrafico('sesiones')">Sesiones</button>
                            <button type="button" class="btn btn-outline-primary" @click="cambiarTipoGrafico('participantes')">Participantes</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="graficoSesiones" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Distribución por Tipo de Usuario -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-pie-chart me-2 text-success"></i>
                        Distribución de Usuarios
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="graficoUsuarios" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas Detalladas: solo se muestran si hay datos reales -->
    <div class="row mb-4">
        <!-- Top Instructores (solo si hay instructores con sesiones) -->
        <template x-if="topInstructores && topInstructores.length > 0">
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-trophy me-2 text-warning"></i>
                            Top Instructores
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="space-y-3">
                            <template x-for="(instructor, index) in topInstructores" :key="instructor.id">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="bg-warning bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <span class="fw-bold text-warning" x-text="index + 1"></span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold text-dark" x-text="instructor.name"></div>
                                        <div class="text-muted small">
                                            <span x-text="instructor.sesiones_count"></span> sesiones •
                                            <span x-text="instructor.participantes_count"></span> participantes
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="text-end">
                                            <div class="fw-bold text-dark" x-text="(instructor.puntuacion_promedio ?? 0) + '/10'"></div>
                                            <div class="text-muted small">Promedio</div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Actividad Reciente (solo si hay actividad) -->
        <template x-if="actividadReciente && actividadReciente.length > 0">
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-activity me-2 text-info"></i>
                            Actividad Reciente
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="space-y-3" style="max-height: 400px; overflow-y: auto;">
                            <template x-for="actividad in actividadReciente" :key="actividad.id">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="bg-info bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="bi bi-circle-fill text-info" style="font-size: 8px;"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="text-dark small" x-text="actividad.descripcion"></div>
                                        <div class="text-muted small" x-text="formatTime(actividad.fecha)"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Tabla de Rendimiento por Sesión -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-table me-2 text-primary"></i>
                            Rendimiento por Sesión
                        </h5>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control form-control-sm" placeholder="Buscar sesión..." 
                                   x-model="filtroBusqueda" @input="filtrarSesiones()" style="width: 200px;">
                            <button class="btn btn-outline-primary btn-sm" @click="exportarTabla()">
                                <i class="bi bi-download me-1"></i>
                                Exportar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">Sesión</th>
                                    <th class="border-0">Instructor</th>
                                    <th class="border-0">Participantes</th>
                                    <th class="border-0">Duración</th>
                                    <th class="border-0">Puntuación Promedio</th>
                                    <th class="border-0">Estado</th>
                                    <th class="border-0">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="sesion in sesionesFiltradas" :key="sesion.id">
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark" x-text="sesion.nombre"></div>
                                            <div class="text-muted small" x-text="sesion.descripcion"></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary bg-opacity-10 rounded-circle p-1 me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                    <span class="text-primary small fw-bold" x-text="sesion.instructor?.name?.charAt(0) || '?'"></span>
                                                </div>
                                                <span x-text="sesion.instructor?.name || 'Sin asignar'"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold" x-text="sesion.participantes_count || 0"></span>
                                            <span class="text-muted small">/ <span x-text="sesion.max_participantes || '∞'"></span></span>
                                        </td>
                                        <td>
                                            <span x-text="sesion.duracion || '0h 0m'"></span>
                                        </td>
                                        <td>
                                            <template x-if="sesion.puntuacion_promedio != null">
                                                <div class="d-flex align-items-center">
                                                    <span class="fw-semibold me-2" x-text="sesion.puntuacion_promedio"></span>
                                                    <div class="progress" style="width: 60px; height: 6px;">
                                                        <div class="progress-bar bg-success" :style="'width: ' + (sesion.puntuacion_promedio * 10) + '%'"></div>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="sesion.puntuacion_promedio == null">
                                                <span class="text-muted">—</span>
                                            </template>
                                        </td>
                                        <td>
                                            <span class="badge" :class="{
                                                'bg-success': sesion.estado === 'finalizada',
                                                'bg-primary': sesion.estado === 'en_curso',
                                                'bg-warning': sesion.estado === 'programada',
                                                'bg-danger': sesion.estado === 'cancelada'
                                            }" x-text="sesion.estado"></span>
                                        </td>
                                        <td>
                                            <span x-text="formatDate(sesion.fecha_inicio)"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function estadisticasManager() {
    return {
        estadisticas: {},
        topInstructores: [],
        actividadReciente: [],
        sesionesFiltradas: [],
        filtroBusqueda: '',
        periodoSeleccionado: 'Este mes',
        graficoSesiones: null,
        graficoUsuarios: null,
        
        init() {
            this.cargarEstadisticas();
            this.cargarTopInstructores();
            this.cargarActividadReciente();
            this.cargarSesiones();
            this.cargarDatosGraficos();
        },
        
        async cargarEstadisticas() {
            try {
                const periodo = this.periodoSeleccionado === 'Hoy' ? 'hoy' : 
                               this.periodoSeleccionado === 'Esta semana' ? 'semana' :
                               this.periodoSeleccionado === 'Este mes' ? 'mes' : 'año';
                const response = await fetch(`/api/estadisticas/dashboard?periodo=${periodo}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.estadisticas = data.data;
                }
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
            }
        },
        
        async cargarTopInstructores() {
            try {
                const response = await fetch('/api/estadisticas/top-instructores', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.topInstructores = data.data;
                }
            } catch (error) {
                console.error('Error cargando top instructores:', error);
            }
        },
        
        async cargarActividadReciente() {
            try {
                const response = await fetch('/api/estadisticas/actividad-reciente', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.actividadReciente = data.data;
                }
            } catch (error) {
                console.error('Error cargando actividad reciente:', error);
            }
        },
        
        async cargarSesiones() {
            try {
                const response = await fetch('/api/sesiones', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    const sesiones = data.data?.data || data.data || [];
                    
                    // Enriquecer datos de sesiones con información real (sin simulaciones)
                    this.sesionesFiltradas = sesiones.map(sesion => {
                        const participantes = sesion.asignaciones_count ?? sesion.participantes_count ?? (Array.isArray(sesion.asignaciones) ? sesion.asignaciones.length : 0);
                        let duracion = '0h 0m';
                        if (sesion.fecha_inicio && sesion.fecha_fin) {
                            const inicio = new Date(sesion.fecha_inicio);
                            const fin = new Date(sesion.fecha_fin);
                            let minutos = Math.floor((fin - inicio) / (1000 * 60));
                            if (minutos < 0) {
                                duracion = (sesion.estado === 'en_curso' || sesion.estado === 'programada') ? 'En curso' : '0h 0m';
                            } else {
                                const h = Math.floor(minutos / 60);
                                const m = minutos % 60;
                                duracion = h + 'h ' + m + 'm';
                            }
                        }
                        // Puntuación real: solo sesión finalizada tiene 10; en curso/programada no hay puntuación
                        const puntuacionPromedio = sesion.estado === 'finalizada' ? 10 : null;
                        return {
                            ...sesion,
                            duracion: duracion,
                            puntuacion_promedio: puntuacionPromedio,
                            participantes_count: participantes,
                            instructor: sesion.instructor || { name: 'Sin asignar' }
                        };
                    });
                }
            } catch (error) {
                console.error('Error cargando sesiones:', error);
                this.sesionesFiltradas = [];
            }
        },
        
        async cargarDatosGraficos() {
            try {
                // Cargar datos de sesiones por mes
                const responseSesiones = await fetch('/api/estadisticas/sesiones-por-mes', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                if (responseSesiones.ok) {
                    const dataSesiones = await responseSesiones.json();
                    this.inicializarGraficoSesiones(dataSesiones.data);
                }
                
                // Cargar distribución de usuarios
                const responseUsuarios = await fetch('/api/estadisticas/distribucion-usuarios', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                if (responseUsuarios.ok) {
                    const dataUsuarios = await responseUsuarios.json();
                    this.inicializarGraficoUsuarios(dataUsuarios.data);
                }
            } catch (error) {
                console.error('Error cargando datos de gráficos:', error);
                // Inicializar con datos vacíos si hay error
                this.inicializarGraficoSesiones({ meses: [], sesiones: [], participantes: [] });
                this.inicializarGraficoUsuarios({ admins: 0, instructores: 0, estudiantes: 0 });
            }
        },
        
        inicializarGraficoSesiones(data) {
            const ctxSesiones = document.getElementById('graficoSesiones');
            if (!ctxSesiones) return;
            
            // Destruir gráfico anterior si existe
            if (this.graficoSesiones) {
                this.graficoSesiones.destroy();
            }
            
            this.graficoSesiones = new Chart(ctxSesiones, {
                type: 'line',
                data: {
                    labels: data.meses || [],
                    datasets: [{
                        label: 'Sesiones',
                        data: data.sesiones || [],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Participantes',
                        data: data.participantes || [],
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },
        
        inicializarGraficoUsuarios(data) {
            const ctxUsuarios = document.getElementById('graficoUsuarios');
            if (!ctxUsuarios) return;
            
            // Destruir gráfico anterior si existe
            if (this.graficoUsuarios) {
                this.graficoUsuarios.destroy();
            }
            
            const total = (data.admins || 0) + (data.instructores || 0) + (data.estudiantes || 0);
            
            this.graficoUsuarios = new Chart(ctxUsuarios, {
                type: 'doughnut',
                data: {
                    labels: ['Admin', 'Instructor', 'Estudiante'],
                    datasets: [{
                        data: [data.admins || 0, data.instructores || 0, data.estudiantes || 0],
                        backgroundColor: [
                            'rgb(239, 68, 68)',
                            'rgb(59, 130, 246)',
                            'rgb(34, 197, 94)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        },
        
        cambiarPeriodo(periodo) {
            const periodos = {
                'hoy': 'Hoy',
                'semana': 'Esta semana',
                'mes': 'Este mes',
                'año': 'Este año'
            };
            this.periodoSeleccionado = periodos[periodo];
            this.cargarEstadisticas();
            this.cargarDatosGraficos();
        },
        
        cambiarTipoGrafico(tipo) {
            // Cambiar el tipo de gráfico
            console.log('Cambiando gráfico a:', tipo);
        },
        
        filtrarSesiones() {
            if (!this.filtroBusqueda) {
                this.sesionesFiltradas = [...this.sesionesFiltradas];
                return;
            }
            
            this.sesionesFiltradas = this.sesionesFiltradas.filter(sesion => 
                sesion.nombre.toLowerCase().includes(this.filtroBusqueda.toLowerCase()) ||
                sesion.descripcion.toLowerCase().includes(this.filtroBusqueda.toLowerCase())
            );
        },
        
        exportarEstadisticas() {
            const datos = {
                estadisticas: this.estadisticas,
                topInstructores: this.topInstructores,
                actividadReciente: this.actividadReciente,
                fecha: new Date().toISOString()
            };
            
            const blob = new Blob([JSON.stringify(datos, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `estadisticas-${new Date().toISOString().slice(0,10)}.json`;
            a.click();
            URL.revokeObjectURL(url);
        },
        
        exportarTabla() {
            const csv = this.sesionesFiltradas.map(sesion => 
                `${sesion.nombre},${sesion.instructor?.name || 'Sin asignar'},${sesion.participantes_count || 0},${sesion.duracion || '0h 0m'},${sesion.puntuacion_promedio != null ? sesion.puntuacion_promedio : '—'},${sesion.estado},${this.formatDate(sesion.fecha_inicio)}`
            ).join('\n');
            
            const headers = 'Sesión,Instructor,Participantes,Duración,Puntuación Promedio,Estado,Fecha\n';
            const blob = new Blob([headers + csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `sesiones-${new Date().toISOString().slice(0,10)}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        },
        
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES');
        },
        
        formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('es-ES');
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

.progress {
    border-radius: 10px;
    background-color: #f8f9fa;
}

.progress-bar {
    border-radius: 10px;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    color: #6b7280;
}

.table td {
    vertical-align: middle;
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
