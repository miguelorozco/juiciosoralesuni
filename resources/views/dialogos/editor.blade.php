@extends('layouts.app')

@section('title', 'Editor de Diálogos - Simulador de Juicios Orales')

@section('content')
<div class="container-fluid py-4" x-data="dialogoEditor()" x-init="init()">
    <!-- Header del Editor -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="bi bi-diagram-3 me-2 text-primary"></i>
                        <span x-text="dialogo ? 'Editando: ' + dialogo.nombre : 'Nuevo Diálogo'"></span>
                    </h1>
                    <p class="text-muted mb-0">
                        <span x-text="dialogo ? dialogo.descripcion || 'Sin descripción' : 'Crea un diálogo ramificado para simulacros de juicios'"></span>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" @click="guardarBorrador()" :disabled="guardando">
                        <i class="bi bi-save me-2"></i>
                        <span x-text="guardando ? 'Guardando...' : 'Guardar Borrador'"></span>
                    </button>
                    <button class="btn btn-primary" @click="activarDialogo()" :disabled="!puedeActivar">
                        <i class="bi bi-play-circle me-2"></i>
                        Activar Diálogo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Panel de Herramientas -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-tools me-2 text-primary"></i>
                        Herramientas
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Información del Diálogo -->
                    <div class="mb-4">
                        <h6 class="fw-semibold text-dark mb-3">Información del Diálogo</h6>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Nombre</label>
                            <input type="text" class="form-control" x-model="dialogoData.nombre" 
                                   placeholder="Nombre del diálogo">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Descripción</label>
                            <textarea class="form-control" rows="3" x-model="dialogoData.descripcion" 
                                      placeholder="Descripción del diálogo"></textarea>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" x-model="dialogoData.publico">
                            <label class="form-check-label fw-medium">
                                Público
                            </label>
                        </div>
                    </div>

                    <!-- Roles Disponibles -->
                    <div class="mb-4">
                        <h6 class="fw-semibold text-dark mb-3">Roles Disponibles</h6>
                        <div class="d-grid gap-2">
                            <template x-for="rol in rolesDisponibles" :key="rol.id">
                                <button class="btn btn-outline-primary btn-sm" 
                                        @click="agregarNodo(rol.id)"
                                        :disabled="guardando">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    <span x-text="rol.nombre"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="mb-4">
                        <h6 class="fw-semibold text-dark mb-3">Estadísticas</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="text-center p-2 bg-light rounded">
                                    <div class="h5 mb-0 fw-bold text-primary" x-text="nodos.length"></div>
                                    <small class="text-muted">Nodos</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-2 bg-light rounded">
                                    <div class="h5 mb-0 fw-bold text-success" x-text="totalRespuestas"></div>
                                    <small class="text-muted">Respuestas</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" @click="previsualizar()">
                            <i class="bi bi-eye me-2"></i>
                            Previsualizar
                        </button>
                        <button class="btn btn-outline-info" @click="exportar()">
                            <i class="bi bi-download me-2"></i>
                            Exportar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de Trabajo -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-diagram-3 me-2 text-primary"></i>
                            Editor de Flujo
                        </h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <!-- Herramientas de Zoom -->
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-secondary btn-sm" @click="zoomOut()">
                                    <i class="bi bi-zoom-out"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" @click="zoomIn()">
                                    <i class="bi bi-zoom-in"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" @click="centrarVista()">
                                    <i class="bi bi-arrows-move"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" 
                                        :class="{ 'active': grid.visible }"
                                        @click="toggleGrid()"
                                        title="Alternar Grid">
                                    <i class="bi bi-grid-3x3"></i>
                                </button>
                            </div>
                            
                            <div class="vr"></div>
                            
                            <!-- Modo de Herramientas -->
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-secondary btn-sm" 
                                        :class="{ 'active': modo === 'seleccion' }"
                                        @click="cambiarModo('seleccion')"
                                        title="Modo Selección">
                                    <i class="bi bi-cursor"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" 
                                        :class="{ 'active': modo === 'conexion' }"
                                        @click="cambiarModo('conexion')"
                                        title="Modo Conexión">
                                    <i class="bi bi-link-45deg"></i>
                                </button>
                            </div>
                            
                            <div class="vr"></div>
                            
                            <!-- Acciones -->
                            <button class="btn btn-outline-success btn-sm" @click="guardar()" :disabled="guardando">
                                <i class="bi bi-save me-1"></i>
                                <span x-text="guardando ? 'Guardando...' : 'Guardar'"></span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Canvas del Editor -->
                    <div class="editor-canvas" 
                         x-ref="canvas"
                         @drop="onDrop($event)" 
                         @dragover.prevent
                         @click="deseleccionarNodo()"
                         :style="{ transform: `scale(${zoom})` }">
                        
                        <!-- Nodos del Diálogo -->
                        <template x-for="nodo in nodos" :key="nodo.id">
                            <div class="nodo-dialogo" 
                                 :class="{ 
                                     'seleccionado': nodoSeleccionado === nodo.id,
                                     'arrastrando': nodo.arrastrando,
                                     'conectando': conectando && nodoOrigenConexion === nodo.id,
                                     'conectable': modo === 'conexion' && !conectando && nodo.id !== nodoOrigenConexion
                                 }"
                                 :style="{ 
                                     left: nodo.x + 'px', 
                                     top: nodo.y + 'px',
                                     '--rol-color': nodo.rol?.color || '#007bff'
                                 }"
                                 @click.stop="manejarClicNodo(nodo.id)"
                                 @mousedown="iniciarArrastre($event, nodo.id)">
                                
                                <!-- Header del Nodo -->
                                <div class="nodo-header">
                                    <div class="d-flex align-items-center">
                                        <div class="rol-badge" :style="{ backgroundColor: nodo.rol?.color || '#007bff' }">
                                            <i class="bi bi-person-fill text-white"></i>
                                        </div>
                                        <div class="ms-2 flex-grow-1">
                                            <div class="fw-semibold text-dark" x-text="nodo.titulo || 'Sin título'"></div>
                                            <small class="text-muted" x-text="nodo.rol?.nombre || 'Sin rol'"></small>
                                        </div>
                                        <div class="nodo-acciones">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    @click.stop="editarNodo(nodo.id)"
                                                    title="Editar nodo">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    @click.stop="eliminarNodo(nodo.id)"
                                                    title="Eliminar nodo">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contenido del Nodo -->
                                <div class="nodo-contenido">
                                    <div class="contenido-texto" x-text="nodo.contenido || 'Sin contenido'"></div>
                                    
                                    <!-- Respuestas -->
                                    <div class="respuestas" x-show="nodo.respuestas && nodo.respuestas.length > 0">
                                        <template x-for="respuesta in nodo.respuestas" :key="respuesta.id">
                                            <div class="respuesta-item" 
                                                 :style="{ borderLeftColor: respuesta.color || '#007bff' }">
                                                <span x-text="respuesta.texto"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Indicadores -->
                                <div class="nodo-indicadores">
                                    <span x-show="nodo.es_inicial" class="badge bg-success">
                                        <i class="bi bi-play-fill me-1"></i>Inicial
                                    </span>
                                    <span x-show="nodo.es_final" class="badge bg-danger">
                                        <i class="bi bi-stop-fill me-1"></i>Final
                                    </span>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Grid Visual -->
                        <div x-show="grid.visible" 
                             class="grid-overlay"
                             :style="{
                                 position: 'absolute',
                                 top: 0,
                                 left: 0,
                                 width: (grid.columnas * grid.celdaSize) + 'px',
                                 height: (grid.filas * grid.celdaSize) + 'px',
                                 pointerEvents: 'none',
                                 zIndex: 0
                             }">
                            <template x-for="fila in Array(grid.filas).fill(0)" :key="'fila-' + fila">
                                <template x-for="columna in Array(grid.columnas).fill(0)" :key="'celda-' + fila + '-' + columna">
                                    <div class="grid-celda"
                                         :class="{
                                             'ocupada': estaCeldaOcupada(columna, fila),
                                             'seleccionada': nodoSeleccionado && obtenerNodoPorId(nodoSeleccionado) && obtenerPosicionGrid(obtenerNodoPorId(nodoSeleccionado).x, obtenerNodoPorId(nodoSeleccionado).y).columna === columna && obtenerPosicionGrid(obtenerNodoPorId(nodoSeleccionado).x, obtenerNodoPorId(nodoSeleccionado).y).fila === fila
                                         }"
                                         :style="{
                                             position: 'absolute',
                                             left: (columna * grid.celdaSize) + 'px',
                                             top: (fila * grid.celdaSize) + 'px',
                                             width: grid.celdaSize + 'px',
                                             height: grid.celdaSize + 'px'
                                         }">
                                    </div>
                                </template>
                            </template>
                        </div>

                        <!-- Líneas de Conexión -->
                        <svg class="conexiones-svg">
                            <defs>
                                <marker id="arrowhead" markerWidth="10" markerHeight="7" 
                                        refX="9" refY="3.5" orient="auto">
                                    <polygon points="0 0, 10 3.5, 0 7" fill="#007bff" />
                                </marker>
                                <marker id="arrowhead-connecting" markerWidth="10" markerHeight="7" 
                                        refX="9" refY="3.5" orient="auto">
                                    <polygon points="0 0, 10 3.5, 0 7" fill="#ffc107" />
                                </marker>
                            </defs>
                            
                            <!-- Conexiones existentes multipuntos -->
                            <template x-for="conexion in conexiones" :key="conexion.id">
                                <g>
                                    <!-- Línea multipuntos -->
                                    <polyline :points="conexion.puntos ? conexion.puntos.map(p => `${p.x},${p.y}`).join(' ') : `${conexion.x1},${conexion.y1} ${conexion.x2},${conexion.y2}`"
                                              :stroke="conexion.color || '#007bff'" 
                                              stroke-width="3" 
                                              fill="none"
                                              marker-end="url(#arrowhead)"
                                              class="conexion-line"
                                              @click="editarConexion(conexion.id)"/>
                                    
                                    <!-- Puntos de control (opcionales, para debug) -->
                                    <template x-for="(punto, index) in (conexion.puntos || [])" :key="'punto-' + conexion.id + '-' + index">
                                        <circle :cx="punto.x" 
                                                :cy="punto.y" 
                                                r="2" 
                                                :fill="conexion.color || '#007bff'" 
                                                opacity="0.4"
                                                x-show="false"/>
                                    </template>
                                    
                                    <!-- Etiqueta de la conexión en el punto medio -->
                                    <text :x="conexion.puntos ? conexion.puntos[Math.floor(conexion.puntos.length / 2)].x : (conexion.x1 + conexion.x2) / 2" 
                                          :y="conexion.puntos ? conexion.puntos[Math.floor(conexion.puntos.length / 2)].y - 10 : (conexion.y1 + conexion.y2) / 2 - 5"
                                          text-anchor="middle"
                                          class="conexion-label"
                                          :fill="conexion.color || '#007bff'"
                                          font-size="12"
                                          font-weight="bold"
                                          x-text="conexion.texto || ''">
                                    </text>
                                    
                                    <!-- Puntuación -->
                                    <text :x="conexion.puntos ? conexion.puntos[Math.floor(conexion.puntos.length / 2)].x : (conexion.x1 + conexion.x2) / 2" 
                                          :y="conexion.puntos ? conexion.puntos[Math.floor(conexion.puntos.length / 2)].y + 5 : (conexion.y1 + conexion.y2) / 2 + 10"
                                          text-anchor="middle"
                                          class="conexion-score"
                                          :fill="conexion.color || '#007bff'"
                                          font-size="10"
                                          opacity="0.8"
                                          x-text="conexion.puntuacion ? `+${conexion.puntuacion}` : ''">
                                    </text>
                                </g>
                            </template>
                            
                            <!-- Línea temporal de conexión -->
                            <template x-if="conectando && nodoOrigenConexion">
                                <line :x1="conexionTemporal.x1" :y1="conexionTemporal.y1" 
                                      :x2="conexionTemporal.x2" :y2="conexionTemporal.y2"
                                      stroke="#ffc107" 
                                      stroke-width="3" 
                                      stroke-dasharray="5,5"
                                      marker-end="url(#arrowhead-connecting)"/>
                            </template>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Indicador de Modo -->
        <div x-show="modo === 'conexion'" class="alert alert-info alert-sm mb-3">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Modo Conexión:</strong> Haz clic en un nodo para iniciar la conexión, luego haz clic en otro nodo para completarla.
            <button class="btn btn-sm btn-outline-secondary ms-2" @click="cancelarConexion()">
                <i class="bi bi-x"></i> Cancelar
            </button>
        </div>
    </div>

    <!-- Modal de Conexión -->
    <div class="modal fade" id="modalConexion" tabindex="-1" x-ref="modalConexion">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Conexión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="guardarConexion()">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Desde Nodo</label>
                            <input type="text" class="form-control" 
                                   :value="nodoOrigenConexion ? obtenerNodoPorId(nodoOrigenConexion).titulo : ''" 
                                   readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-medium">Hacia Nodo</label>
                            <select class="form-select" x-model="conexionEditando.nodo_destino_id" required>
                                <option value="">Seleccionar nodo destino...</option>
                                <template x-for="nodo in nodosDisponiblesParaConexion" :key="nodo.id">
                                    <option :value="nodo.id" x-text="nodo.titulo || 'Sin título'"></option>
                                </template>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-medium">Texto de la Respuesta</label>
                            <input type="text" class="form-control" 
                                   x-model="conexionEditando.texto" 
                                   placeholder="Ej: Sí, estoy de acuerdo"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-medium">Descripción</label>
                            <textarea class="form-control" rows="2" 
                                      x-model="conexionEditando.descripcion" 
                                      placeholder="Descripción adicional de esta opción..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Color</label>
                                <input type="color" class="form-control form-control-color" 
                                       x-model="conexionEditando.color">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Puntuación</label>
                                <input type="number" class="form-control" 
                                       x-model="conexionEditando.puntuacion" 
                                       min="0" max="100">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                Crear Conexión
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición de Nodo -->
    <div class="modal fade" id="modalNodo" tabindex="-1" x-ref="modalNodo">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Nodo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="guardarNodo()">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Título</label>
                                <input type="text" class="form-control" x-model="nodoEditando.titulo" 
                                       placeholder="Título del nodo">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Tipo</label>
                                <select class="form-select" x-model="nodoEditando.tipo">
                                    <option value="inicio">Inicio</option>
                                    <option value="desarrollo">Desarrollo</option>
                                    <option value="decision">Decisión</option>
                                    <option value="final">Final</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Rol</label>
                                <select class="form-select" x-model="nodoEditando.rol_id" @change="actualizarRolSeleccionado()">
                                    <option value="">Seleccionar rol...</option>
                                    <template x-for="rol in rolesDisponibles" :key="rol.id">
                                        <option :value="rol.id" x-text="rol.nombre"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" x-show="nodoEditando.rol">
                                <label class="form-label fw-medium">Vista Previa del Rol</label>
                                <div class="d-flex align-items-center p-2 border rounded" 
                                     :style="{ backgroundColor: nodoEditando.rol?.color + '20' }">
                                    <div class="rounded-circle me-2 d-flex align-items-center justify-content-center"
                                         :style="{ 
                                             width: '32px', 
                                             height: '32px', 
                                             backgroundColor: nodoEditando.rol?.color || '#007bff' 
                                         }">
                                        <i class="bi bi-person-fill text-white"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" x-text="nodoEditando.rol?.nombre || 'Sin rol'"></div>
                                        <small class="text-muted" x-text="nodoEditando.rol?.descripcion || ''"></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-medium">Contenido</label>
                            <textarea class="form-control" rows="4" x-model="nodoEditando.contenido" 
                                      placeholder="Contenido del nodo"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-medium">Instrucciones</label>
                            <textarea class="form-control" rows="2" x-model="nodoEditando.instrucciones" 
                                      placeholder="Instrucciones adicionales"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" x-model="nodoEditando.es_inicial">
                                    <label class="form-check-label fw-medium">
                                        Nodo Inicial
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" x-model="nodoEditando.es_final">
                                    <label class="form-check-label fw-medium">
                                        Nodo Final
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="guardarNodo()">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.editor-canvas {
    position: relative;
    min-height: 600px;
    background: linear-gradient(45deg, #f8f9fa 25%, transparent 25%), 
                linear-gradient(-45deg, #f8f9fa 25%, transparent 25%), 
                linear-gradient(45deg, transparent 75%, #f8f9fa 75%), 
                linear-gradient(-45deg, transparent 75%, #f8f9fa 75%);
    background-size: 20px 20px;
    background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
    transform-origin: top left;
    transition: transform 0.3s ease;
}

.nodo-dialogo {
    position: absolute;
    width: 250px;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    cursor: move;
    transition: all 0.3s ease;
    z-index: 10;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

.nodo-dialogo:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.nodo-dialogo.seleccionado {
    border-color: var(--rol-color);
    box-shadow: 0 0 0 3px rgba(var(--rol-color), 0.2);
}

.nodo-dialogo.arrastrando {
    cursor: grabbing;
    transform: rotate(2deg);
    box-shadow: 0 12px 32px rgba(0,0,0,0.2);
    z-index: 1000;
}

.nodo-header {
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 10px 10px 0 0;
}

.rol-badge {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.nodo-acciones {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.nodo-dialogo:hover .nodo-acciones {
    opacity: 1;
}

.nodo-contenido {
    padding: 12px;
}

.contenido-texto {
    font-size: 14px;
    line-height: 1.4;
    color: #495057;
    margin-bottom: 8px;
}

.respuestas {
    margin-top: 8px;
}

.respuesta-item {
    padding: 4px 8px;
    margin: 2px 0;
    background: #f8f9fa;
    border-left: 3px solid #007bff;
    border-radius: 4px;
    font-size: 12px;
    color: #6c757d;
}

.nodo-indicadores {
    position: absolute;
    top: -8px;
    right: -8px;
    display: flex;
    gap: 4px;
}

.conexiones-svg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.badge {
    font-size: 10px;
    padding: 4px 8px;
}

.conexion-line {
    cursor: pointer;
    transition: all 0.2s ease;
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
}

.conexion-line:hover {
    stroke-width: 4;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}

.conexion-label {
    pointer-events: none;
    user-select: none;
    text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
    background: rgba(255,255,255,0.9);
    padding: 2px 6px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.conexion-score {
    pointer-events: none;
    user-select: none;
    text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
}

.nodo-dialogo.conectando {
    border-color: #ffc107;
    box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.3);
}

.nodo-dialogo.conectable {
    border-color: #28a745;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.3);
    cursor: pointer;
    animation: pulse-green 1.5s infinite;
}

.nodo-dialogo.conectando {
    border-color: #ffc107;
    box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.5);
    animation: pulse-yellow 1s infinite;
}

@keyframes pulse-green {
    0% { box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.3); }
    50% { box-shadow: 0 0 0 6px rgba(40, 167, 69, 0.1); }
    100% { box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.3); }
}

@keyframes pulse-yellow {
    0% { box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.5); }
    50% { box-shadow: 0 0 0 6px rgba(255, 193, 7, 0.2); }
    100% { box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.5); }
}

.btn-group .btn.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

/* Estilos del Grid */
.grid-overlay {
    background-image: 
        linear-gradient(rgba(0,0,0,0.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.1) 1px, transparent 1px);
    background-size: 200px 200px;
}

.grid-celda {
    border: 1px solid rgba(0,0,0,0.1);
    background-color: transparent;
    transition: all 0.2s ease;
}

.grid-celda.ocupada {
    background-color: rgba(40, 167, 69, 0.1);
    border-color: rgba(40, 167, 69, 0.3);
}

.grid-celda.seleccionada {
    background-color: rgba(13, 110, 253, 0.1);
    border-color: rgba(13, 110, 253, 0.5);
    box-shadow: inset 0 0 0 2px rgba(13, 110, 253, 0.3);
}

.grid-celda:hover {
    background-color: rgba(0,0,0,0.05);
}
</style>

<script>
function dialogoEditor() {
    return {
        dialogo: @json($dialogo ?? null),
        dialogoData: {
            nombre: '',
            descripcion: '',
            publico: false
        },
        nodos: [],
        conexiones: [],
        rolesDisponibles: [],
        nodoSeleccionado: null,
        modo: 'seleccion', // 'seleccion' o 'conexion'
        conectando: false,
        nodoOrigenConexion: null,
        
        // Sistema de Grid
        grid: {
            columnas: 5,
            filas: 50,
            celdaSize: 200, // px
            expandible: true,
            visible: true
        },
        gridData: [], // Array 2D para rastrear ocupación
        scrollX: 0,
        scrollY: 0,
        conexionTemporal: { x1: 0, y1: 0, x2: 0, y2: 0 },
        conexionEditando: {
            nodo_destino_id: null,
            texto: '',
            descripcion: '',
            color: '#007bff',
            puntuacion: 0
        },
        nodoEditando: {
            id: null,
            titulo: '',
            contenido: '',
            instrucciones: '',
            tipo: 'desarrollo',
            es_inicial: false,
            es_final: false,
            rol_id: null,
            rol: null
        },
        guardando: false,
        zoom: 1,
        arrastrando: false,
        nodoArrastrando: null,
        offsetX: 0,
        offsetY: 0,

        get totalRespuestas() {
            return this.nodos.reduce((total, nodo) => total + (nodo.respuestas?.length || 0), 0);
        },

        get puedeActivar() {
            return this.nodos.length > 0 && this.nodos.some(nodo => nodo.es_inicial);
        },

        async init() {
            this.inicializarGrid();
            if (this.dialogo) {
                this.dialogoData = {
                    nombre: this.dialogo.nombre,
                    descripcion: this.dialogo.descripcion,
                    publico: this.dialogo.publico
                };
                await this.cargarDialogo();
            }
            await this.cargarRoles();
        },

        // Sistema de Grid
        inicializarGrid() {
            // Inicializar array 2D para rastrear ocupación
            this.gridData = Array(this.grid.filas).fill().map(() => Array(this.grid.columnas).fill(null));
        },

        obtenerPosicionGrid(x, y) {
            const columna = Math.floor(x / this.grid.celdaSize);
            const fila = Math.floor(y / this.grid.celdaSize);
            return { columna, fila };
        },

        obtenerCoordenadasGrid(columna, fila) {
            const x = columna * this.grid.celdaSize;
            const y = fila * this.grid.celdaSize;
            return { x, y };
        },

        esPosicionValida(columna, fila) {
            return columna >= 0 && columna < this.grid.columnas && 
                   fila >= 0 && fila < this.grid.filas;
        },

        estaCeldaOcupada(columna, fila, excluirNodoId = null) {
            if (!this.esPosicionValida(columna, fila)) return true;
            const ocupante = this.gridData[fila][columna];
            return ocupante !== null && ocupante !== excluirNodoId;
        },

        ocuparCelda(columna, fila, nodoId) {
            if (this.esPosicionValida(columna, fila)) {
                this.gridData[fila][columna] = nodoId;
            }
        },

        liberarCelda(columna, fila) {
            if (this.esPosicionValida(columna, fila)) {
                this.gridData[fila][columna] = null;
            }
        },

        encontrarCeldaLibreCerca(columna, fila) {
            // Buscar en espiral desde la posición original
            for (let radio = 0; radio < Math.max(this.grid.columnas, this.grid.filas); radio++) {
                for (let dc = -radio; dc <= radio; dc++) {
                    for (let df = -radio; df <= radio; df++) {
                        if (Math.abs(dc) === radio || Math.abs(df) === radio) {
                            const nuevaColumna = columna + dc;
                            const nuevaFila = fila + df;
                            if (!this.estaCeldaOcupada(nuevaColumna, nuevaFila)) {
                                return { columna: nuevaColumna, fila: nuevaFila };
                            }
                        }
                    }
                }
            }
            return null; // No hay celdas libres
        },

        async cargarRoles() {
            try {
                const response = await fetch('/api/roles/activos');
                const data = await response.json();
                if (data.success) {
                    this.rolesDisponibles = data.data;
                }
            } catch (error) {
                console.error('Error cargando roles:', error);
            }
        },

        async cargarDialogo() {
            if (!this.dialogo) return;
            
            try {
                const response = await fetch(`/api/dialogos/${this.dialogo.id}/estructura`);
                const data = await response.json();
                if (data.success) {
                    this.nodos = data.data.nodos.map(nodo => ({
                        ...nodo,
                        x: nodo.posicion?.x || Math.random() * 400 + 100,
                        y: nodo.posicion?.y || Math.random() * 300 + 100,
                        arrastrando: false,
                        respuestas: nodo.respuestas || []
                    }));
                    
                    // Inicializar posiciones en el grid
                    this.inicializarPosicionesNodos();
                    this.calcularConexiones();
                }
            } catch (error) {
                console.error('Error cargando diálogo:', error);
            }
        },

        inicializarPosicionesNodos() {
            // Limpiar grid
            this.inicializarGrid();
            
            // Colocar cada nodo en el grid
            this.nodos.forEach(nodo => {
                const posicion = this.obtenerPosicionGrid(nodo.x, nodo.y);
                if (this.esPosicionValida(posicion.columna, posicion.fila)) {
                    this.ocuparCelda(posicion.columna, posicion.fila, nodo.id);
                } else {
                    // Si la posición no es válida, encontrar una celda libre
                    const celdaLibre = this.encontrarCeldaLibreCerca(0, 0);
                    if (celdaLibre) {
                        const coordenadas = this.obtenerCoordenadasGrid(celdaLibre.columna, celdaLibre.fila);
                        nodo.x = coordenadas.x;
                        nodo.y = coordenadas.y;
                        this.ocuparCelda(celdaLibre.columna, celdaLibre.fila, nodo.id);
                    }
                }
            });
        },

        agregarNodo(rolId) {
            const rol = this.rolesDisponibles.find(r => r.id === rolId);
            
            // Encontrar una celda libre para el nuevo nodo
            const celdaLibre = this.encontrarCeldaLibreCerca(0, 0);
            if (!celdaLibre) {
                this.mostrarMensaje('No hay espacio disponible en el grid', 'error');
                return;
            }

            const coordenadas = this.obtenerCoordenadasGrid(celdaLibre.columna, celdaLibre.fila);
            const nuevoNodo = {
                id: 'temp_' + Date.now(),
                titulo: '',
                contenido: '',
                instrucciones: '',
                tipo: 'desarrollo',
                es_inicial: false,
                es_final: false,
                rol_id: rolId,
                rol: rol,
                respuestas: [],
                x: coordenadas.x,
                y: coordenadas.y,
                arrastrando: false
            };
            
            // Ocupar la celda
            this.ocuparCelda(celdaLibre.columna, celdaLibre.fila, nuevoNodo.id);
            
            this.nodos.push(nuevoNodo);
            this.seleccionarNodo(nuevoNodo.id);
            this.abrirModalNodo();
        },

        seleccionarNodo(nodoId) {
            this.nodoSeleccionado = nodoId;
            const nodo = this.nodos.find(n => n.id === nodoId);
            if (nodo) {
                this.nodoEditando = { ...nodo };
                // Asegurar que el rol esté disponible en nodoEditando
                if (nodo.rol_id && !this.nodoEditando.rol) {
                    this.nodoEditando.rol = this.rolesDisponibles.find(r => r.id === nodo.rol_id);
                }
            }
        },

        editarNodo(nodoId) {
            this.seleccionarNodo(nodoId);
            this.abrirModalNodo();
        },

        actualizarRolSeleccionado() {
            if (this.nodoEditando.rol_id) {
                this.nodoEditando.rol = this.rolesDisponibles.find(r => r.id === this.nodoEditando.rol_id);
            } else {
                this.nodoEditando.rol = null;
            }
        },

        deseleccionarNodo() {
            this.nodoSeleccionado = null;
        },

        abrirModalNodo() {
            const modal = new bootstrap.Modal(this.$refs.modalNodo);
            modal.show();
        },

        guardarNodo() {
            const index = this.nodos.findIndex(n => n.id === this.nodoEditando.id);
            if (index !== -1) {
                this.nodos[index] = { ...this.nodos[index], ...this.nodoEditando };
                // Asegurar que el rol esté actualizado en el nodo
                if (this.nodoEditando.rol_id) {
                    this.nodos[index].rol = this.rolesDisponibles.find(r => r.id === this.nodoEditando.rol_id);
                } else {
                    this.nodos[index].rol = null;
                }
            }
            
            const modal = bootstrap.Modal.getInstance(this.$refs.modalNodo);
            modal.hide();
        },

        eliminarNodo(nodoId) {
            if (confirm('¿Estás seguro de que quieres eliminar este nodo?')) {
                const nodo = this.nodos.find(n => n.id === nodoId);
                if (nodo) {
                    // Liberar la celda ocupada
                    const posicion = this.obtenerPosicionGrid(nodo.x, nodo.y);
                    this.liberarCelda(posicion.columna, posicion.fila);
                }
                
                this.nodos = this.nodos.filter(n => n.id !== nodoId);
                this.deseleccionarNodo();
                this.calcularConexiones();
            }
        },

        iniciarArrastre(event, nodoId) {
            console.log('Iniciando arrastre para nodo:', nodoId);
            
            // Solo permitir arrastre con el botón izquierdo del mouse
            if (event.button !== 0) return;
            
            event.preventDefault();
            event.stopPropagation();
            
            this.arrastrando = true;
            this.nodoArrastrando = nodoId;
            const nodo = this.nodos.find(n => n.id === nodoId);
            
            if (!nodo) {
                console.error('Nodo no encontrado:', nodoId);
                return;
            }
            
            this.offsetX = event.clientX - nodo.x;
            this.offsetY = event.clientY - nodo.y;
            
            // Agregar clase de arrastre al nodo
            nodo.arrastrando = true;
            
            // Seleccionar el nodo si no está seleccionado
            if (this.nodoSeleccionado !== nodoId) {
                this.seleccionarNodo(nodoId);
            }
            
            console.log('Arrastre iniciado. Offset:', this.offsetX, this.offsetY);
            
            document.addEventListener('mousemove', (e) => this.arrastrar(e));
            document.addEventListener('mouseup', (e) => this.soltar(e));
        },

        arrastrar(event) {
            if (!this.arrastrando || !this.nodoArrastrando) return;
            
            event.preventDefault();
            event.stopPropagation();
            
            const nodo = this.nodos.find(n => n.id === this.nodoArrastrando);
            if (nodo) {
                // Calcular nueva posición
                const nuevaX = event.clientX - this.offsetX;
                const nuevaY = event.clientY - this.offsetY;
                
                // Convertir a posición de grid
                const posicionGrid = this.obtenerPosicionGrid(nuevaX, nuevaY);
                
                // Verificar si la celda está libre
                if (!this.estaCeldaOcupada(posicionGrid.columna, posicionGrid.fila, nodo.id)) {
                    // Liberar celda anterior
                    const posicionAnterior = this.obtenerPosicionGrid(nodo.x, nodo.y);
                    this.liberarCelda(posicionAnterior.columna, posicionAnterior.fila);
                    
                    // Ocupar nueva celda
                    this.ocuparCelda(posicionGrid.columna, posicionGrid.fila, nodo.id);
                    
                    // Actualizar posición del nodo
                    const coordenadas = this.obtenerCoordenadasGrid(posicionGrid.columna, posicionGrid.fila);
                    nodo.x = coordenadas.x;
                    nodo.y = coordenadas.y;
                }
            }
        },

        soltar() {
            if (this.nodoArrastrando) {
                const nodo = this.nodos.find(n => n.id === this.nodoArrastrando);
                if (nodo) {
                    nodo.arrastrando = false;
                }
            }
            
            this.arrastrando = false;
            this.nodoArrastrando = null;
            document.removeEventListener('mousemove', (e) => this.arrastrar(e));
            document.removeEventListener('mouseup', (e) => this.soltar(e));
        },

        calcularConexiones() {
            this.conexiones = [];
            this.nodos.forEach(nodo => {
                if (nodo.respuestas) {
                    nodo.respuestas.forEach(respuesta => {
                        if (respuesta.nodo_siguiente_id) {
                            const nodoSiguiente = this.nodos.find(n => n.id === respuesta.nodo_siguiente_id);
                            if (nodoSiguiente) {
                                // Calcular puntos de conexión multipuntos
                                const puntos = this.calcularPuntosConexion(nodo, nodoSiguiente);
                                
                                this.conexiones.push({
                                    id: `${nodo.id}_${respuesta.id}`,
                                    respuesta_id: respuesta.id,
                                    x1: nodo.x + 125,
                                    y1: nodo.y + 100,
                                    x2: nodoSiguiente.x + 125,
                                    y2: nodoSiguiente.y + 50,
                                    puntos: puntos,
                                    texto: respuesta.texto,
                                    color: respuesta.color || '#007bff',
                                    puntuacion: respuesta.puntuacion || 0
                                });
                            }
                        }
                    });
                }
            });
        },

        calcularPuntosConexion(nodoOrigen, nodoDestino) {
            const origen = { x: nodoOrigen.x + 125, y: nodoOrigen.y + 100 };
            const destino = { x: nodoDestino.x + 125, y: nodoDestino.y + 50 };
            
            // Calcular dirección de la conexión
            const deltaX = destino.x - origen.x;
            const deltaY = destino.y - origen.y;
            
            // Determinar si es horizontal o vertical
            const esHorizontal = Math.abs(deltaX) > Math.abs(deltaY);
            
            if (esHorizontal) {
                // Conexión horizontal: origen -> punto medio -> destino
                const puntoMedioX = origen.x + (deltaX / 2);
                return [
                    { x: origen.x, y: origen.y },
                    { x: puntoMedioX, y: origen.y },
                    { x: puntoMedioX, y: destino.y },
                    { x: destino.x, y: destino.y }
                ];
            } else {
                // Conexión vertical: origen -> punto medio -> destino
                const puntoMedioY = origen.y + (deltaY / 2);
                return [
                    { x: origen.x, y: origen.y },
                    { x: origen.x, y: puntoMedioY },
                    { x: destino.x, y: puntoMedioY },
                    { x: destino.x, y: destino.y }
                ];
            }
        },

        // Funciones para manejo de modos y conexiones
        cambiarModo(nuevoModo) {
            this.modo = nuevoModo;
            if (nuevoModo === 'seleccion') {
                this.cancelarConexion();
            }
        },

        manejarClicNodo(nodoId) {
            if (this.modo === 'conexion') {
                this.manejarClicConexion(nodoId);
            } else {
                this.seleccionarNodo(nodoId);
            }
        },

        manejarClicConexion(nodoId) {
            if (!this.conectando) {
                // Iniciar conexión
                this.conectando = true;
                this.nodoOrigenConexion = nodoId;
                this.mostrarMensaje('Selecciona el nodo destino', 'info');
            } else {
                // Completar conexión
                if (nodoId === this.nodoOrigenConexion) {
                    this.mostrarMensaje('No puedes conectar un nodo consigo mismo', 'warning');
                    return;
                }
                
                if (this.tieneConexionExistente(this.nodoOrigenConexion, nodoId)) {
                    this.mostrarMensaje('Ya existe una conexión entre estos nodos', 'warning');
                    return;
                }
                
                this.completarConexion(nodoId);
            }
        },

        completarConexion(nodoDestinoId) {
            // Crear respuesta automáticamente
            const nodoOrigen = this.obtenerNodoPorId(this.nodoOrigenConexion);
            const nodoDestino = this.obtenerNodoPorId(nodoDestinoId);
            
            const nuevaRespuesta = {
                id: 'temp_respuesta_' + Date.now(),
                nodo_padre_id: this.nodoOrigenConexion,
                nodo_siguiente_id: nodoDestinoId,
                texto: `Ir a ${nodoDestino.titulo || 'Nodo destino'}`,
                descripcion: '',
                color: '#007bff',
                puntuacion: 0,
                activo: true,
                orden: (nodoOrigen.respuestas?.length || 0) + 1
            };

            // Agregar respuesta al nodo origen
            if (!nodoOrigen.respuestas) {
                nodoOrigen.respuestas = [];
            }
            nodoOrigen.respuestas.push(nuevaRespuesta);

            // Recalcular conexiones
            this.calcularConexiones();

            this.mostrarMensaje(`Conexión creada: ${nodoOrigen.titulo} → ${nodoDestino.titulo}`, 'success');
            
            // Resetear estado
            this.conectando = false;
            this.nodoOrigenConexion = null;
        },

        cancelarConexion() {
            this.conectando = false;
            this.nodoOrigenConexion = null;
            this.modo = 'seleccion';
        },

        // Funciones para manejo de conexiones (método anterior)
        iniciarConexion(nodoId) {
            this.conectando = true;
            this.nodoOrigenConexion = nodoId;
            this.conexionEditando = {
                nodo_destino_id: null,
                texto: '',
                descripcion: '',
                color: '#007bff',
                puntuacion: 0
            };
            
            // Mostrar modal de conexión
            const modal = new bootstrap.Modal(this.$refs.modalConexion);
            modal.show();
        },

        obtenerNodoPorId(nodoId) {
            return this.nodos.find(n => n.id === nodoId) || {};
        },

        get nodosDisponiblesParaConexion() {
            if (!this.nodoOrigenConexion) return [];
            
            return this.nodos.filter(nodo => 
                nodo.id !== this.nodoOrigenConexion && 
                !this.tieneConexionExistente(this.nodoOrigenConexion, nodo.id)
            );
        },

        tieneConexionExistente(nodoOrigen, nodoDestino) {
            return this.nodos.find(n => n.id === nodoOrigen)?.respuestas?.some(r => r.nodo_siguiente_id === nodoDestino) || false;
        },

        guardarConexion() {
            if (!this.conexionEditando.nodo_destino_id) {
                this.mostrarMensaje('Selecciona un nodo destino', 'error');
                return;
            }

            const nodoOrigen = this.obtenerNodoPorId(this.nodoOrigenConexion);
            const nodoDestino = this.obtenerNodoPorId(this.conexionEditando.nodo_destino_id);

            // Crear respuesta en el nodo origen
            const nuevaRespuesta = {
                id: 'temp_respuesta_' + Date.now(),
                nodo_padre_id: this.nodoOrigenConexion,
                nodo_siguiente_id: this.conexionEditando.nodo_destino_id,
                texto: this.conexionEditando.texto,
                descripcion: this.conexionEditando.descripcion,
                color: this.conexionEditando.color,
                puntuacion: this.conexionEditando.puntuacion,
                activo: true,
                orden: (nodoOrigen.respuestas?.length || 0) + 1
            };

            // Agregar respuesta al nodo origen
            if (!nodoOrigen.respuestas) {
                nodoOrigen.respuestas = [];
            }
            nodoOrigen.respuestas.push(nuevaRespuesta);

            // Recalcular conexiones
            this.calcularConexiones();

            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(this.$refs.modalConexion);
            modal.hide();

            this.conectando = false;
            this.nodoOrigenConexion = null;

            this.mostrarMensaje('Conexión creada exitosamente', 'success');
        },

        editarConexion(conexionId) {
            // Implementar edición de conexión
            this.mostrarMensaje('Función de edición de conexión en desarrollo', 'info');
        },

        eliminarConexion(conexionId) {
            if (confirm('¿Estás seguro de que quieres eliminar esta conexión?')) {
                // Encontrar y eliminar la respuesta
                const [nodoId, respuestaId] = conexionId.split('_');
                const nodo = this.nodos.find(n => n.id === nodoId);
                if (nodo && nodo.respuestas) {
                    nodo.respuestas = nodo.respuestas.filter(r => r.id !== respuestaId);
                }
                
                this.calcularConexiones();
                this.mostrarMensaje('Conexión eliminada', 'success');
            }
        },

        zoomIn() {
            this.zoom = Math.min(this.zoom * 1.2, 2);
        },

        zoomOut() {
            this.zoom = Math.max(this.zoom / 1.2, 0.5);
        },

        centrarVista() {
            this.zoom = 1;
        },

        toggleGrid() {
            this.grid.visible = !this.grid.visible;
        },

        async guardarBorrador() {
            this.guardando = true;
            try {
                const url = this.dialogo ? `/api/dialogos/${this.dialogo.id}` : '/api/dialogos';
                const method = this.dialogo ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                    },
                    body: JSON.stringify(this.dialogoData)
                });
                
                const data = await response.json();
                if (data.success) {
                    if (!this.dialogo) {
                        this.dialogo = data.data;
                        window.history.pushState(null, '', `/dialogos/${this.dialogo.id}/edit`);
                    }
                    this.mostrarMensaje('Diálogo guardado exitosamente', 'success');
                } else {
                    this.mostrarMensaje('Error al guardar: ' + data.message, 'error');
                }
            } catch (error) {
                this.mostrarMensaje('Error de conexión', 'error');
            } finally {
                this.guardando = false;
            }
        },

        async activarDialogo() {
            if (!this.dialogo) {
                await this.guardarBorrador();
            }
            
            try {
                const response = await fetch(`/api/dialogos/${this.dialogo.id}/activar`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.mostrarMensaje('Diálogo activado exitosamente', 'success');
                } else {
                    this.mostrarMensaje('Error al activar: ' + data.message, 'error');
                }
            } catch (error) {
                this.mostrarMensaje('Error de conexión', 'error');
            }
        },

        previsualizar() {
            // Implementar previsualización
            this.mostrarMensaje('Función de previsualización en desarrollo', 'info');
        },

        exportar() {
            // Implementar exportación
            this.mostrarMensaje('Función de exportación en desarrollo', 'info');
        },

        mostrarMensaje(mensaje, tipo) {
            // Implementar sistema de notificaciones
            console.log(`${tipo.toUpperCase()}: ${mensaje}`);
        }
    }
}
</script>
@endsection