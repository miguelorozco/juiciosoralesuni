@extends('layouts.app')

@section('title', 'Editor de Diálogos - Simulador de Juicios Orales')

@section('content')
<div class="container-fluid py-4" x-data="dialogoEditorMejorado()" x-init="init()">
    <!-- Header del Editor Mejorado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="bi bi-diagram-3 me-2 text-primary"></i>
                        <span x-text="dialogo ? 'Editando: ' + dialogo.nombre : 'Nuevo Diálogo'"></span>
                        <span x-show="guardando" class="spinner-border spinner-border-sm text-primary ms-2"></span>
                    </h1>
                    <p class="text-muted mb-0">
                        <span x-text="dialogo ? dialogo.descripcion || 'Sin descripción' : 'Crea un diálogo ramificado para simulacros de juicios'"></span>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <div class="btn-group" role="group">
                        <button class="btn btn-outline-secondary" @click="guardarBorrador()" :disabled="guardando">
                            <i class="bi bi-save me-2"></i>
                            <span x-text="guardando ? 'Guardando...' : 'Guardar Borrador'"></span>
                        </button>
                        <button class="btn btn-outline-success" @click="previsualizar()" :disabled="guardando">
                            <i class="bi bi-eye me-2"></i>
                            Previsualizar
                        </button>
                        <button class="btn btn-outline-info" @click="exportar()" :disabled="guardando">
                            <i class="bi bi-download me-2"></i>
                            Exportar
                        </button>
                    </div>
                    <button class="btn btn-primary" @click="activarDialogo()" :disabled="!puedeActivar || guardando">
                        <i class="bi bi-play-circle me-2"></i>
                        Activar Diálogo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Herramientas Principal -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <!-- Herramientas de Vista -->
                        <div class="d-flex gap-2 align-items-center">
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-secondary btn-sm" @click="zoomOut()" title="Alejar">
                                    <i class="bi bi-zoom-out"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" @click="zoomIn()" title="Acercar">
                                    <i class="bi bi-zoom-in"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" @click="centrarVista()" title="Centrar Vista">
                                    <i class="bi bi-arrows-move"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" @click="ajustarVista()" title="Ajustar a Contenido">
                                    <i class="bi bi-arrows-fullscreen"></i>
                                </button>
                            </div>
                            
                            <div class="vr"></div>
                            
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-secondary btn-sm" 
                                        :class="{ 'active': grid.visible }"
                                        @click="toggleGrid()"
                                        title="Mostrar/Ocultar Grid">
                                    <i class="bi bi-grid-3x3"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" 
                                        :class="{ 'active': mostrarMinimap }"
                                        @click="toggleMinimap()"
                                        title="Minimapa">
                                    <i class="bi bi-map"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" 
                                        :class="{ 'active': mostrarRulers }"
                                        @click="toggleRulers()"
                                        title="Reglas">
                                    <i class="bi bi-rulers"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Modos de Herramienta -->
                        <div class="d-flex gap-2 align-items-center">
                            <span class="text-muted small fw-medium">Modo:</span>
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-primary btn-sm" 
                                        :class="{ 'active': modo === 'seleccion' }"
                                        @click="cambiarModo('seleccion')"
                                        title="Modo Selección (S)">
                                    <i class="bi bi-cursor"></i>
                                </button>
                                <button class="btn btn-outline-success btn-sm" 
                                        :class="{ 'active': modo === 'conexion' }"
                                        @click="cambiarModo('conexion')"
                                        title="Modo Conexión (C)">
                                    <i class="bi bi-link-45deg"></i>
                                </button>
                                <button class="btn btn-outline-warning btn-sm" 
                                        :class="{ 'active': modo === 'texto' }"
                                        @click="cambiarModo('texto')"
                                        title="Modo Texto (T)">
                                    <i class="bi bi-type"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Información de Estado -->
                        <div class="d-flex gap-3 align-items-center text-muted small">
                            <div>
                                <i class="bi bi-diagram-3 me-1"></i>
                                <span x-text="nodos.length"></span> nodos
                            </div>
                            <div>
                                <i class="bi bi-arrow-right me-1"></i>
                                <span x-text="totalRespuestas"></span> respuestas
                            </div>
                            <div>
                                <i class="bi bi-zoom-in me-1"></i>
                                <span x-text="Math.round(zoom * 100)"></span>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Panel de Herramientas Mejorado -->
        <div class="col-lg-3">
            <!-- Información del Diálogo -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        Información del Diálogo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Nombre</label>
                        <input type="text" class="form-control" x-model="dialogoData.nombre" 
                               placeholder="Nombre del diálogo" @input="marcarComoModificado()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Descripción</label>
                        <textarea class="form-control" rows="3" x-model="dialogoData.descripcion" 
                                  placeholder="Descripción del diálogo" @input="marcarComoModificado()"></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" x-model="dialogoData.publico" @change="marcarComoModificado()">
                        <label class="form-check-label fw-medium">
                            Público
                        </label>
                    </div>
                    <div x-show="modificado" class="alert alert-warning alert-sm">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Cambios sin guardar
                    </div>
                </div>
            </div>

            <!-- Roles Disponibles -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-people me-2 text-success"></i>
                        Roles Disponibles
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <template x-for="rol in rolesDisponibles" :key="rol.id">
                            <button class="btn btn-outline-primary btn-sm d-flex align-items-center" 
                                    @click="agregarNodo(rol.id)"
                                    :disabled="guardando">
                                <div class="me-2" :style="{ width: '12px', height: '12px', backgroundColor: rol.color, borderRadius: '50%' }"></div>
                                <i class="bi bi-plus-circle me-2"></i>
                                <span x-text="rol.nombre"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Estadísticas Avanzadas -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-graph-up me-2 text-info"></i>
                        Estadísticas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
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
                    
                    <div class="mb-2">
                        <small class="text-muted">Nodos Iniciales:</small>
                        <span class="badge bg-success ms-1" x-text="nodos.filter(n => n.es_inicial).length"></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Nodos Finales:</small>
                        <span class="badge bg-danger ms-1" x-text="nodos.filter(n => n.es_final).length"></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Complejidad:</small>
                        <span class="badge" :class="complejidadColor" x-text="nivelComplejidad"></span>
                    </div>
                </div>
            </div>

            <!-- Validación de Estructura -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-check-circle me-2 text-warning"></i>
                        Validación
                    </h5>
                </div>
                <div class="card-body">
                    <div x-show="erroresValidacionComputed.length === 0" class="text-success">
                        <i class="bi bi-check-circle me-1"></i>
                        <small>Estructura válida</small>
                    </div>
                    <div x-show="erroresValidacionComputed.length > 0">
                        <template x-for="error in erroresValidacionComputed" :key="error">
                            <div class="text-danger small mb-1">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <span x-text="error"></span>
                            </div>
                        </template>
                    </div>
                    <button class="btn btn-outline-warning btn-sm w-100 mt-2" @click="validarEstructura()">
                        <i class="bi bi-arrow-clockwise me-1"></i>
                        Validar
                    </button>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-lightning me-2 text-warning"></i>
                        Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" @click="duplicarNodoSeleccionado()" :disabled="!nodoSeleccionado">
                            <i class="bi bi-files me-2"></i>
                            Duplicar Nodo
                        </button>
                        <button class="btn btn-outline-success btn-sm" @click="autoOrganizar()">
                            <i class="bi bi-arrow-down-up me-2"></i>
                            Auto-organizar
                        </button>
                        <button class="btn btn-outline-info btn-sm" @click="exportarImagen()">
                            <i class="bi bi-image me-2"></i>
                            Exportar Imagen
                        </button>
                        <button class="btn btn-outline-warning btn-sm" @click="limpiarCanvas()">
                            <i class="bi bi-trash me-2"></i>
                            Limpiar Todo
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de Trabajo Mejorada -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-diagram-3 me-2 text-primary"></i>
                            Editor de Flujo
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-success btn-sm" @click="guardar()" :disabled="guardando">
                                <i class="bi bi-save me-1"></i>
                                <span x-text="guardando ? 'Guardando...' : 'Guardar'"></span>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" @click="deshacer()" :disabled="!puedeDeshacer">
                                <i class="bi bi-arrow-left me-1"></i>
                                Deshacer
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" @click="rehacer()" :disabled="!puedeRehacer">
                                <i class="bi bi-arrow-right me-1"></i>
                                Rehacer
                            </button>
                            <button class="btn btn-outline-info btn-sm" @click="calcularDimensionesCanvas()" title="Recalcular Canvas">
                                <i class="bi bi-arrows-fullscreen me-1"></i>
                                Expandir
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0 position-relative">
                    <!-- Canvas del Editor Mejorado -->
                    <div class="editor-canvas-mejorado dinamico" 
                         x-ref="canvas"
                         @drop="onDrop($event)" 
                         @dragover.prevent
                         @click="deseleccionarNodo()"
                         @keydown="manejarTeclado($event)"
                         tabindex="0"
                         :style="{ 
                             width: canvasWidth + 'px',
                             height: canvasHeight + 'px',
                             transform: `scale(${zoom}) translate(${panX}px, ${panY}px)`,
                             transformOrigin: '0 0'
                         }">
                        
                        <!-- Reglas (si están habilitadas) -->
                        <div x-show="mostrarRulers" class="rulers">
                            <div class="ruler-horizontal"></div>
                            <div class="ruler-vertical"></div>
                        </div>

                        <!-- Grid Visual Mejorado -->
                        <div x-show="grid.visible" 
                             class="grid-overlay-mejorado"
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
                                    <div class="grid-celda-mejorada"
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

                        <!-- Nodos del Diálogo Mejorados -->
                        <template x-for="nodo in nodos" :key="nodo.id">
                            <div class="nodo-dialogo-mejorado" 
                                 :class="{ 
                                     'seleccionado': nodoSeleccionado === nodo.id,
                                     'arrastrando': nodo.arrastrando,
                                     'conectando': conectando && nodoOrigenConexion === nodo.id,
                                     'conectable': modo === 'conexion' && !conectando && nodo.id !== nodoOrigenConexion,
                                     'inicial': nodo.es_inicial,
                                     'final': nodo.es_final,
                                     'error': nodo.tieneError
                                 }"
                                 :style="{ 
                                     left: nodo.x + 'px', 
                                     top: nodo.y + 'px',
                                     '--rol-color': nodo.rol?.color || '#007bff'
                                 }"
                                 @click.stop="manejarClicNodo(nodo.id)"
                                 @mousedown="iniciarArrastre($event, nodo.id)"
                                 @contextmenu="mostrarMenuContextual($event, nodo.id)">
                                
                                <!-- Header del Nodo Mejorado -->
                                <div class="nodo-header-mejorado">
                                    <div class="d-flex align-items-center">
                                        <div class="rol-badge-mejorado" :style="{ backgroundColor: nodo.rol?.color || '#007bff' }">
                                            <i :class="nodo.rol?.icono || 'bi bi-person-fill'" class="text-white"></i>
                                        </div>
                                        <div class="ms-2 flex-grow-1">
                                            <div class="fw-semibold text-dark" x-text="nodo.titulo || 'Sin título'"></div>
                                            <small class="text-muted" x-text="nodo.rol?.nombre || 'Sin rol'"></small>
                                        </div>
                                        <div class="nodo-acciones-mejoradas">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    @click.stop="editarNodo(nodo.id)"
                                                    title="Editar nodo (E)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" 
                                                    @click.stop="duplicarNodo(nodo.id)"
                                                    title="Duplicar nodo (D)">
                                                <i class="bi bi-files"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    @click.stop="eliminarNodo(nodo.id)"
                                                    title="Eliminar nodo (Del)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contenido del Nodo Mejorado -->
                                <div class="nodo-contenido-mejorado">
                                    <div class="contenido-texto-mejorado" x-text="nodo.contenido || 'Sin contenido'"></div>
                                    
                                    <!-- Opciones de Respuesta (A, B, C, D) -->
                                    <div class="opciones-respuesta" x-show="nodo.respuestas && nodo.respuestas.length > 0">
                                        <div class="opciones-grid">
                                            <template x-for="(respuesta, index) in nodo.respuestas" :key="respuesta.id">
                                                <div class="opcion-item" 
                                                     :class="'opcion-' + String.fromCharCode(65 + index)"
                                                     :style="{ borderLeftColor: respuesta.color || '#007bff' }"
                                                     @click.stop="editarRespuesta(respuesta.id)">
                                                    <div class="opcion-letra">
                                                        <span x-text="String.fromCharCode(65 + index)"></span>
                                                    </div>
                                                    <div class="opcion-texto">
                                                        <span x-text="respuesta.texto"></span>
                                                    </div>
                                                    <div class="opcion-puntuacion" x-show="respuesta.puntuacion">
                                                        <span class="badge bg-secondary" x-text="'+' + respuesta.puntuacion"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Indicadores Mejorados -->
                                <div class="nodo-indicadores-mejorados">
                                    <span x-show="nodo.es_inicial" class="badge bg-success">
                                        <i class="bi bi-play-fill me-1"></i>Inicial
                                    </span>
                                    <span x-show="nodo.es_final" class="badge bg-danger">
                                        <i class="bi bi-stop-fill me-1"></i>Final
                                    </span>
                                    <span x-show="nodo.tieneError" class="badge bg-warning">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Error
                                    </span>
                                </div>

                                <!-- Puntos de Conexión -->
                                <div class="puntos-conexion">
                                    <!-- Punto de entrada único (arriba) -->
                                    <div class="punto-conexion punto-entrada" 
                                         :style="{ left: '50%', top: '-8px', transform: 'translateX(-50%)' }"
                                         @click.stop="seleccionarPuntoConexion(nodo.id, 'entrada', 0)"
                                         title="Punto de entrada">
                                    </div>
                                    
                                    <!-- Puntos de salida (abajo) - A, B, C, D -->
                                    <template x-for="(punto, index) in obtenerPuntosConexion(nodo).filter(p => p.tipo === 'salida')" :key="'salida-' + index">
                                        <div class="punto-conexion punto-salida" 
                                             :style="{ left: (punto.x - nodo.x - 8) + 'px', bottom: '-8px' }"
                                             :class="{ 'punto-ocupado': esPuntoOcupado(nodo.id, 'salida', index) }"
                                             @click.stop="seleccionarPuntoConexion(nodo.id, 'salida', index)"
                                             :title="'Opción ' + String.fromCharCode(65 + index)">
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Líneas de Conexión Mejoradas -->
                        <div class="conexiones-container" x-show="conexiones.length > 0">
                            <svg class="conexiones-svg-mejorado" 
                                 :width="canvasWidth" 
                                 :height="canvasHeight"
                                 x-ref="svgConnections">
                                <defs>
                                    <marker id="arrowhead-mejorado" markerWidth="10" markerHeight="7" 
                                            refX="9" refY="3.5" orient="auto">
                                        <polygon points="0 0, 10 3.5, 0 7" fill="#007bff" />
                                    </marker>
                                    <marker id="arrowhead-connecting-mejorado" markerWidth="10" markerHeight="7" 
                                            refX="9" refY="3.5" orient="auto">
                                        <polygon points="0 0, 10 3.5, 0 7" fill="#ffc107" />
                                    </marker>
                                    <marker id="arrowhead-error" markerWidth="10" markerHeight="7" 
                                            refX="9" refY="3.5" orient="auto">
                                        <polygon points="0 0, 10 3.5, 0 7" fill="#dc3545" />
                                    </marker>
                                </defs>
                                
                                <!-- Contenedor para conexiones -->
                                <g id="conexiones-container"></g>
                                
                                <!-- Línea temporal de conexión -->
                                <g id="conexion-temporal" x-show="conectando && nodoOrigenConexion">
                                    <line :x1="conexionTemporal.x1" :y1="conexionTemporal.y1" 
                                          :x2="conexionTemporal.x2" :y2="conexionTemporal.y2"
                                          stroke="#ffc107" 
                                          stroke-width="3" 
                                          stroke-dasharray="5,5"
                                          marker-end="url(#arrowhead-connecting-mejorado)"/>
                                </g>
                            </svg>
                            
                            <!-- Debug: Mostrar información de conexiones -->
                            <div class="debug-conexiones">
                                <small class="text-muted">
                                    Conexiones: <span x-text="conexiones.length"></span>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Minimapa -->
                    <div x-show="mostrarMinimap" class="minimap">
                        <div class="minimap-content" :style="{ transform: `scale(${1/zoom})` }">
                            <!-- Representación simplificada de los nodos -->
                            <template x-for="nodo in nodos" :key="'minimap-' + nodo.id">
                                <div class="minimap-nodo" 
                                     :style="{ 
                                         left: (nodo.x * 0.1) + 'px', 
                                         top: (nodo.y * 0.1) + 'px',
                                         backgroundColor: nodo.rol?.color || '#007bff'
                                     }">
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Indicador de Modo Mejorado -->
        <div x-show="modo === 'conexion'" class="alert alert-info alert-sm mb-3">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Modo Conexión:</strong> 
            <span x-show="!conectando">Haz clic en el nodo origen para iniciar la conexión.</span>
            <span x-show="conectando">Ahora haz clic en el nodo destino para completar la conexión.</span>
            <button class="btn btn-sm btn-outline-secondary ms-2" @click="cancelarConexion()">
                <i class="bi bi-x"></i> Cancelar
            </button>
        </div>
    </div>

    <!-- Modales y Formularios -->
    @include('dialogos.modals.nodo')
    @include('dialogos.modals.conexion')
    @include('dialogos.modals.respuesta')
    @include('dialogos.modals.validacion')
</div>

<!-- Estilos CSS Mejorados -->
<style>
/* Estilos del Editor Mejorado */
.editor-canvas-mejorado {
    position: relative;
    width: 100%;
    height: 800px;
    min-height: 800px;
    background: linear-gradient(45deg, #f8f9fa 25%, transparent 25%), 
                linear-gradient(-45deg, #f8f9fa 25%, transparent 25%), 
                linear-gradient(45deg, transparent 75%, #f8f9fa 75%), 
                linear-gradient(-45deg, transparent 75%, #f8f9fa 75%);
    background-size: 20px 20px;
    background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
    overflow: auto;
    cursor: grab;
    padding: 50px;
    transition: all 0.3s ease;
}

/* Canvas dinámico */
.editor-canvas-mejorado.dinamico {
    min-width: 1200px;
    min-height: 800px;
    width: max-content;
    height: max-content;
}

.editor-canvas-mejorado:active {
    cursor: grabbing;
}

/* Nodos Mejorados */
.nodo-dialogo-mejorado {
    position: absolute;
    width: 250px;
    min-height: 120px;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    cursor: move;
    transition: all 0.2s ease;
    z-index: 10;
}

.nodo-dialogo-mejorado:hover {
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.nodo-dialogo-mejorado.seleccionado {
    border-color: var(--rol-color);
    box-shadow: 0 0 0 3px rgba(var(--rol-color), 0.2);
}

.nodo-dialogo-mejorado.arrastrando {
    transform: rotate(2deg);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

.nodo-dialogo-mejorado.conectando {
    border-color: #ffc107;
    box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.3);
}

.nodo-dialogo-mejorado.conectable {
    border-color: #28a745;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.3);
}

.nodo-dialogo-mejorado.inicial {
    border-color: #28a745;
    border-width: 3px;
}

.nodo-dialogo-mejorado.final {
    border-color: #dc3545;
    border-width: 3px;
}

.nodo-dialogo-mejorado.error {
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.2);
}

/* Header del Nodo Mejorado */
.nodo-header-mejorado {
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px 10px 0 0;
}

.rol-badge-mejorado {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.nodo-acciones-mejoradas {
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.nodo-dialogo-mejorado:hover .nodo-acciones-mejoradas {
    opacity: 1;
}

/* Contenido del Nodo Mejorado */
.nodo-contenido-mejorado {
    padding: 12px;
}

.contenido-texto-mejorado {
    font-size: 14px;
    line-height: 1.4;
    color: #495057;
    min-height: 40px;
}

/* Respuestas Mejoradas */
/* Opciones de Respuesta (A, B, C, D) */
.opciones-respuesta {
    margin-top: 8px;
}

.opciones-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px;
}

.opcion-item {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-left: 4px solid #007bff;
    border-radius: 6px;
    padding: 6px 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
}

.opcion-item:hover {
    background: #e9ecef;
    transform: translateX(2px);
}

.opcion-letra {
    background: #007bff;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.7rem;
    flex-shrink: 0;
}

.opcion-texto {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.opcion-puntuacion {
    flex-shrink: 0;
}

/* Colores específicos para cada opción */
.opcion-A {
    border-left-color: #28a745;
}

.opcion-A .opcion-letra {
    background: #28a745;
}

.opcion-B {
    border-left-color: #ffc107;
}

.opcion-B .opcion-letra {
    background: #ffc107;
    color: #000;
}

.opcion-C {
    border-left-color: #fd7e14;
}

.opcion-C .opcion-letra {
    background: #fd7e14;
}

.opcion-D {
    border-left-color: #6f42c1;
}

.opcion-D .opcion-letra {
    background: #6f42c1;
}

/* Indicadores Mejorados */
.nodo-indicadores-mejorados {
    position: absolute;
    top: -8px;
    right: -8px;
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

.nodo-indicadores-mejorados .badge {
    font-size: 10px;
    padding: 4px 6px;
}

/* Puntos de Conexión */
.puntos-conexion {
    position: absolute;
    top: 50%;
    left: -8px;
    right: -8px;
    height: 0;
    pointer-events: none;
}

.punto-conexion {
    position: absolute;
    width: 16px;
    height: 16px;
    background: #007bff;
    border: 2px solid white;
    border-radius: 50%;
    cursor: pointer;
    pointer-events: all;
    transition: all 0.2s ease;
}

.punto-conexion:hover {
    background: #0056b3;
    transform: scale(1.2);
}

.punto-entrada {
    left: -8px;
}

.punto-salida {
    right: -8px;
}

/* Grid Mejorado */
.grid-overlay-mejorado {
    background-image: 
        linear-gradient(to right, #e9ecef 1px, transparent 1px),
        linear-gradient(to bottom, #e9ecef 1px, transparent 1px);
    background-size: 20px 20px;
    opacity: 0.3;
}

.grid-celda-mejorada {
    border: 1px solid rgba(233, 236, 239, 0.5);
}

.grid-celda-mejorada.ocupada {
    background-color: rgba(220, 53, 69, 0.1);
    border-color: #dc3545;
}

.grid-celda-mejorada.seleccionada {
    background-color: rgba(0, 123, 255, 0.1);
    border-color: #007bff;
}

/* Conexiones Mejoradas */
.conexiones-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 5;
}

.conexiones-svg-mejorado {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    background: transparent;
}

.debug-conexiones {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(255, 255, 255, 0.9);
    padding: 5px 10px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

/* Puntos de Conexión */
.puntos-conexion {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.punto-conexion {
    position: absolute;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 3px solid #007bff;
    background: white;
    cursor: pointer;
    pointer-events: all;
    transition: all 0.2s ease;
    z-index: 20;
}

.punto-conexion:hover {
    transform: scale(1.2);
    box-shadow: 0 0 8px rgba(0, 123, 255, 0.6);
}

.punto-conexion.punto-entrada {
    border-color: #28a745;
    background: #d4edda;
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
}

.punto-conexion.punto-salida {
    border-color: #007bff;
    background: #d1ecf1;
    box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
}

.punto-conexion.punto-ocupado {
    border-color: #dc3545;
    background: #f8d7da;
    opacity: 0.6;
}

.punto-conexion.punto-ocupado:hover {
    transform: none;
    box-shadow: none;
    cursor: not-allowed;
}

.conexion-line-mejorada {
    cursor: pointer;
    transition: all 0.2s ease;
    pointer-events: all;
    stroke-width: 3;
}

.conexion-line-mejorada:hover {
    stroke-width: 5px;
    filter: drop-shadow(0 0 3px rgba(0, 123, 255, 0.5));
}

.conexion-line-mejorada:active {
    stroke-width: 6px;
}

.conexion-label-mejorada {
    pointer-events: none;
    user-select: none;
}

.conexion-score-mejorada {
    pointer-events: none;
    user-select: none;
}

/* Minimapa */
.minimap {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 200px;
    height: 150px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    z-index: 20;
}

.minimap-content {
    position: relative;
    width: 100%;
    height: 100%;
}

.minimap-nodo {
    position: absolute;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    border: 1px solid white;
}

/* Reglas */
.rulers {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.ruler-horizontal {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 20px;
    background: linear-gradient(to right, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
}

.ruler-vertical {
    position: absolute;
    top: 0;
    left: 0;
    width: 20px;
    height: 100%;
    background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
    border-right: 1px solid #dee2e6;
}

/* Responsive */
@media (max-width: 768px) {
    .nodo-dialogo-mejorado {
        width: 200px;
        min-height: 100px;
    }
    
    .editor-canvas-mejorado {
        height: 400px;
    }
    
    .minimap {
        width: 150px;
        height: 100px;
    }
}
</style>

<script>
function dialogoEditorMejorado() {
    return {
        // Estado principal
        dialogo: @json($dialogo ?? null),
        dialogoData: {
            nombre: '',
            descripcion: '',
            publico: false
        },
        nodos: [],
        conexiones: [],
        rolesDisponibles: [],
        
        // Estado de edición
        guardando: false,
        modificado: false,
        nodoSeleccionado: null,
        nodoEditando: {},
        
        // Modos y herramientas
        modo: 'seleccion', // seleccion, conexion, texto
        zoom: 1,
        panX: 0,
        panY: 0,
        
        // Grid mejorado
        grid: {
            visible: true,
            filas: 30,
            columnas: 40,
            celdaSize: 20
        },
        gridData: [],
        
        // Arrastre mejorado
        arrastrando: false,
        nodoArrastrando: null,
        offsetX: 0,
        offsetY: 0,
        
        // Conexiones mejoradas
        conectando: false,
        nodoOrigenConexion: null,
        puntoOrigenConexion: null,
        conexionTemporal: { x1: 0, y1: 0, x2: 0, y2: 0 },
        conexionEditando: {},
        
        // Validación mejorada
        erroresValidacion: [],
        advertenciasValidacion: [],
        recomendacionesValidacion: [],
        
        // Historial para deshacer/rehacer
        historial: [],
        indiceHistorial: -1,
        
        // Variables de edición
        respuestaEditando: {},
        
        // UI mejorada
        mostrarMinimap: false,
        mostrarRulers: false,
        canvasWidth: 2000,
        canvasHeight: 2000,
        minCanvasWidth: 1200,
        minCanvasHeight: 800,
        
        // Computed properties mejoradas
        get totalRespuestas() {
            return this.nodos.reduce((total, nodo) => total + (nodo.respuestas?.length || 0), 0);
        },

        get puedeActivar() {
            return this.nodos.length > 0 && this.nodos.some(nodo => nodo.es_inicial);
        },

        get nivelComplejidad() {
            const totalNodos = this.nodos.length;
            const totalConexiones = this.conexiones.length;
            
            if (totalNodos <= 5 && totalConexiones <= 8) return 'Baja';
            if (totalNodos <= 15 && totalConexiones <= 25) return 'Media';
            return 'Alta';
        },

        get complejidadColor() {
            const nivel = this.nivelComplejidad;
            return {
                'bg-success': nivel === 'Baja',
                'bg-warning': nivel === 'Media',
                'bg-danger': nivel === 'Alta'
            };
        },

        get puedeDeshacer() {
            return this.indiceHistorial > 0;
        },

        get puedeRehacer() {
            return this.indiceHistorial < this.historial.length - 1;
        },

        get nodosDisponiblesParaConexion() {
            return this.nodos.filter(nodo => nodo.id !== this.nodoOrigenConexion);
        },

        get nodosDisponibles() {
            return this.nodos;
        },

        get erroresValidacionComputed() {
            return this.erroresValidacion || [];
        },

        get advertenciasValidacionComputed() {
            return this.advertenciasValidacion || [];
        },

        get recomendacionesValidacionComputed() {
            return this.recomendacionesValidacion || [];
        },

        get nodosPorRol() {
            const roles = {};
            this.nodos.forEach(nodo => {
                if (nodo.rol_id) {
                    if (!roles[nodo.rol_id]) {
                        roles[nodo.rol_id] = [];
                    }
                    roles[nodo.rol_id].push(nodo);
                }
            });
            return Object.values(roles);
        },

        get promedioRespuestasPorNodo() {
            if (this.nodos.length === 0) return 0;
            return (this.totalRespuestas / this.nodos.length).toFixed(2);
        },

        get profundidadMaxima() {
            // Implementar cálculo de profundidad máxima
            return 0;
        },

        get respuestaEditandoComputed() {
            return this.respuestaEditando || {};
        },

        obtenerNodoPorId(nodoId) {
            return this.nodos.find(nodo => nodo.id === nodoId);
        },

        obtenerColorOpcion(indice) {
            const colores = ['#28a745', '#ffc107', '#fd7e14', '#6f42c1'];
            return colores[indice] || '#007bff';
        },

        calcularDimensionesCanvas() {
            if (this.nodos.length === 0) {
                this.canvasWidth = this.minCanvasWidth;
                this.canvasHeight = this.minCanvasHeight;
                return;
            }

            // Calcular los límites de los nodos
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            
            this.nodos.forEach(nodo => {
                const anchoNodo = 250;
                const altoNodo = 120;
                
                minX = Math.min(minX, nodo.x);
                minY = Math.min(minY, nodo.y);
                maxX = Math.max(maxX, nodo.x + anchoNodo);
                maxY = Math.max(maxY, nodo.y + altoNodo);
            });

            // Agregar padding generoso alrededor de los nodos
            const paddingCSS = 50; // Padding del CSS del canvas
            const paddingExtra = 400; // Aumentar padding para más espacio de trabajo
            const paddingTotal = paddingCSS + paddingExtra;
            
            const nuevoAncho = Math.max(maxX + paddingTotal, this.minCanvasWidth);
            const nuevoAlto = Math.max(maxY + paddingTotal, this.minCanvasHeight);

            // Forzar expansión del canvas (siempre expandir, nunca reducir)
            this.canvasWidth = Math.max(this.canvasWidth, nuevoAncho);
            this.canvasHeight = Math.max(this.canvasHeight, nuevoAlto);

            // Expandir el grid para acomodar el nuevo tamaño del canvas
            this.expandirGrid();

            // Actualizar el SVG también
            this.actualizarDimensionesSVG();
            
            console.log('Canvas dimensions updated:', {
                width: this.canvasWidth,
                height: this.canvasHeight,
                nodes: this.nodos.length,
                bounds: { minX, minY, maxX, maxY },
                padding: paddingTotal,
                calculatedWidth: nuevoAncho,
                calculatedHeight: nuevoAlto
            });
        },

        actualizarDimensionesSVG() {
            const svg = this.$refs.svgConnections;
            if (svg) {
                svg.setAttribute('width', this.canvasWidth);
                svg.setAttribute('height', this.canvasHeight);
            }
        },

        verificarLimitesCanvas() {
            // Verificar si algún nodo está cerca del borde del canvas
            const margenSeguro = 200; // Aumentar margen de seguridad
            let necesitaExpansion = false;
            
            this.nodos.forEach(nodo => {
                const anchoNodo = 250;
                const altoNodo = 120;
                
                // Verificar si el nodo está cerca del borde derecho o inferior
                if (nodo.x + anchoNodo + margenSeguro > this.canvasWidth ||
                    nodo.y + altoNodo + margenSeguro > this.canvasHeight) {
                    necesitaExpansion = true;
                    console.log('Nodo cerca del borde:', {
                        nodo: nodo.titulo,
                        x: nodo.x,
                        y: nodo.y,
                        anchoNodo: anchoNodo,
                        altoNodo: altoNodo,
                        canvasWidth: this.canvasWidth,
                        canvasHeight: this.canvasHeight,
                        margenSeguro: margenSeguro
                    });
                }
            });
            
            if (necesitaExpansion) {
                console.log('Expandiendo canvas...');
                this.calcularDimensionesCanvas();
            }
        },

        // Inicialización mejorada
        async init() {
            this.inicializarGrid();
            this.configurarAtajosTeclado();
            this.configurarResizeListener();
            
            // Calcular dimensiones iniciales del canvas
            this.calcularDimensionesCanvas();
            
            // Configurar verificación periódica de límites
            this.configurarVerificacionPeriodica();
            
            // Esperar un poco para que el DOM esté listo
            setTimeout(() => {
                this.actualizarDimensionesCanvas();
            }, 100);
            
            if (this.dialogo) {
                this.dialogoData = {
                    nombre: this.dialogo.nombre,
                    descripcion: this.dialogo.descripcion,
                    publico: this.dialogo.publico
                };
                await this.cargarDialogo();
            }
            await this.cargarRoles();
            this.guardarEstado();
        },

        actualizarDimensionesCanvas() {
            if (this.$refs.canvas) {
                this.canvasWidth = this.$refs.canvas.offsetWidth;
                this.canvasHeight = this.$refs.canvas.offsetHeight;
                console.log('Canvas dimensions:', this.canvasWidth, 'x', this.canvasHeight);
            }
        },

        configurarResizeListener() {
            window.addEventListener('resize', () => {
                this.actualizarDimensionesCanvas();
            });
        },

        configurarVerificacionPeriodica() {
            // Verificar límites del canvas cada 2 segundos
            setInterval(() => {
                this.verificarLimitesCanvas();
            }, 2000);
        },

        // Sistema de Grid mejorado
        inicializarGrid() {
            this.gridData = Array(this.grid.filas).fill().map(() => Array(this.grid.columnas).fill(null));
        },

        expandirGrid() {
            // Expandir el grid dinámicamente
            const nuevasFilas = Math.ceil(this.canvasHeight / this.grid.tamanoCelda) + 5;
            const nuevasColumnas = Math.ceil(this.canvasWidth / this.grid.tamanoCelda) + 5;
            
            if (nuevasFilas > this.grid.filas || nuevasColumnas > this.grid.columnas) {
                console.log('Expandiendo grid:', {
                    filas: this.grid.filas + ' -> ' + nuevasFilas,
                    columnas: this.grid.columnas + ' -> ' + nuevasColumnas
                });
                
                // Expandir filas
                while (this.grid.filas < nuevasFilas) {
                    this.gridData.push(Array(this.grid.columnas).fill(null));
                    this.grid.filas++;
                }
                
                // Expandir columnas
                while (this.grid.columnas < nuevasColumnas) {
                    this.gridData.forEach(fila => fila.push(null));
                    this.grid.columnas++;
                }
            }
        },

        obtenerPosicionGrid(x, y) {
            const columna = Math.floor(x / this.grid.tamanoCelda);
            const fila = Math.floor(y / this.grid.tamanoCelda);
            return { columna, fila };
        },

        obtenerCoordenadasGrid(columna, fila) {
            const x = columna * this.grid.tamanoCelda;
            const y = fila * this.grid.tamanoCelda;
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
            const maxRadio = Math.max(20, Math.max(this.grid.columnas, this.grid.filas));
            
            for (let radio = 0; radio < maxRadio; radio++) {
                for (let dc = -radio; dc <= radio; dc++) {
                    for (let df = -radio; df <= radio; df++) {
                        if (Math.abs(dc) === radio || Math.abs(df) === radio) {
                            const nuevaColumna = columna + dc;
                            const nuevaFila = fila + df;
                            if (this.esPosicionValida(nuevaColumna, nuevaFila) && !this.estaCeldaOcupada(nuevaColumna, nuevaFila)) {
                                return { columna: nuevaColumna, fila: nuevaFila };
                            }
                        }
                    }
                }
            }
            
            // Si no encuentra nada, buscar en cualquier lugar del grid
            for (let f = 0; f < this.grid.filas; f++) {
                for (let c = 0; c < this.grid.columnas; c++) {
                    if (!this.estaCeldaOcupada(c, f)) {
                        return { columna: c, fila: f };
                    }
                }
            }
            
            return null;
        },

        // Atajos de teclado
        configurarAtajosTeclado() {
            document.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
                
                switch(e.key.toLowerCase()) {
                    case 's':
                        if (e.ctrlKey || e.metaKey) {
                            e.preventDefault();
                            this.guardar();
                        }
                        break;
                    case 'n':
                        if (e.ctrlKey || e.metaKey) {
                            e.preventDefault();
                            this.agregarNodoRapido();
                        }
                        break;
                    case 'delete':
                    case 'backspace':
                        if (this.nodoSeleccionado) {
                            e.preventDefault();
                            this.eliminarNodo(this.nodoSeleccionado);
                        }
                        break;
                    case 'escape':
                        this.cancelarConexion();
                        this.deseleccionarNodo();
                        break;
                    case 'c':
                        if (!e.ctrlKey && !e.metaKey) {
                            this.cambiarModo('conexion');
                        }
                        break;
                    case 't':
                        if (!e.ctrlKey && !e.metaKey) {
                            this.cambiarModo('texto');
                        }
                        break;
                    case 'z':
                        if (e.ctrlKey || e.metaKey) {
                            e.preventDefault();
                            if (e.shiftKey) {
                                this.rehacer();
                            } else {
                                this.deshacer();
                            }
                        }
                        break;
                }
            });
        },

        // Historial para deshacer/rehacer
        guardarEstado() {
            const estado = {
                nodos: JSON.parse(JSON.stringify(this.nodos)),
                conexiones: JSON.parse(JSON.stringify(this.conexiones)),
                dialogoData: JSON.parse(JSON.stringify(this.dialogoData))
            };
            
            this.historial = this.historial.slice(0, this.indiceHistorial + 1);
            this.historial.push(estado);
            this.indiceHistorial = this.historial.length - 1;
            
            // Limitar historial a 50 estados
            if (this.historial.length > 50) {
                this.historial.shift();
                this.indiceHistorial--;
            }
        },

        deshacer() {
            if (this.puedeDeshacer) {
                this.indiceHistorial--;
                this.aplicarEstado(this.historial[this.indiceHistorial]);
            }
        },

        rehacer() {
            if (this.puedeRehacer) {
                this.indiceHistorial++;
                this.aplicarEstado(this.historial[this.indiceHistorial]);
            }
        },

        aplicarEstado(estado) {
            this.nodos = JSON.parse(JSON.stringify(estado.nodos));
            this.conexiones = JSON.parse(JSON.stringify(estado.conexiones));
            this.dialogoData = JSON.parse(JSON.stringify(estado.dialogoData));
            this.inicializarPosicionesNodos();
            this.calcularConexiones();
        },

        // Funciones de zoom y pan mejoradas
        zoomIn() {
            this.zoom = Math.min(this.zoom * 1.2, 3);
        },

        zoomOut() {
            this.zoom = Math.max(this.zoom / 1.2, 0.1);
        },

        centrarVista() {
            this.panX = 0;
            this.panY = 0;
        },

        ajustarVista() {
            if (this.nodos.length === 0) return;
            
            const bounds = this.calcularBounds();
            const canvasWidth = this.$refs.canvas.offsetWidth;
            const canvasHeight = this.$refs.canvas.offsetHeight;
            
            const scaleX = canvasWidth / (bounds.width + 100);
            const scaleY = canvasHeight / (bounds.height + 100);
            this.zoom = Math.min(scaleX, scaleY, 1);
            
            this.panX = (canvasWidth - bounds.width * this.zoom) / 2 - bounds.x * this.zoom;
            this.panY = (canvasHeight - bounds.height * this.zoom) / 2 - bounds.y * this.zoom;
        },

        calcularBounds() {
            if (this.nodos.length === 0) return { x: 0, y: 0, width: 0, height: 0 };
            
            let minX = Math.min(...this.nodos.map(n => n.x));
            let minY = Math.min(...this.nodos.map(n => n.y));
            let maxX = Math.max(...this.nodos.map(n => n.x + 250));
            let maxY = Math.max(...this.nodos.map(n => n.y + 120));
            
            return {
                x: minX,
                y: minY,
                width: maxX - minX,
                height: maxY - minY
            };
        },

        // Funciones de UI mejoradas
        toggleGrid() {
            this.grid.visible = !this.grid.visible;
        },

        toggleMinimap() {
            this.mostrarMinimap = !this.mostrarMinimap;
        },

        toggleRulers() {
            this.mostrarRulers = !this.mostrarRulers;
        },

        cambiarModo(nuevoModo) {
            this.modo = nuevoModo;
            this.cancelarConexion();
        },

        // Validación mejorada
        validarEstructura() {
            this.erroresValidacion = [];
            
            // Verificar nodos iniciales
            if (this.nodos.filter(n => n.es_inicial).length === 0) {
                this.erroresValidacion.push('Debe haber al menos un nodo inicial');
            }
            
            // Verificar nodos finales
            if (this.nodos.filter(n => n.es_final).length === 0) {
                this.erroresValidacion.push('Debe haber al menos un nodo final');
            }
            
            // Verificar nodos huérfanos
            const nodosConConexiones = new Set();
            this.conexiones.forEach(conexion => {
                nodosConConexiones.add(conexion.desde);
                nodosConConexiones.add(conexion.hacia);
            });
            
            const nodosHuerfanos = this.nodos.filter(nodo => 
                !nodosConConexiones.has(nodo.id) && !nodo.es_inicial
            );
            
            if (nodosHuerfanos.length > 0) {
                this.erroresValidacion.push(`Hay ${nodosHuerfanos.length} nodos huérfanos`);
            }
            
            // Marcar nodos con errores
            this.nodos.forEach(nodo => {
                nodo.tieneError = this.erroresValidacion.some(error => 
                    error.includes(nodo.titulo) || error.includes(nodo.id)
                );
            });
        },

        // Funciones de utilidad mejoradas
        marcarComoModificado() {
            this.modificado = true;
        },

        autoOrganizar() {
            // Implementar algoritmo de auto-organización
            this.guardarEstado();
            // Algoritmo simple de organización
            const nodosPorFila = Math.ceil(Math.sqrt(this.nodos.length));
            this.nodos.forEach((nodo, index) => {
                const fila = Math.floor(index / nodosPorFila);
                const columna = index % nodosPorFila;
                nodo.x = columna * 300 + 100;
                nodo.y = fila * 200 + 100;
            });
            this.inicializarPosicionesNodos();
        },

        duplicarNodoSeleccionado() {
            if (this.nodoSeleccionado) {
                this.duplicarNodo(this.nodoSeleccionado);
            }
        },

        duplicarNodo(nodoId) {
            const nodoOriginal = this.nodos.find(n => n.id === nodoId);
            if (!nodoOriginal) return;
            
            const celdaLibre = this.encontrarCeldaLibreCerca(0, 0);
            if (!celdaLibre) return;
            
            const coordenadas = this.obtenerCoordenadasGrid(celdaLibre.columna, celdaLibre.fila);
            
            // Generar título consecutivo para la copia
            const numeroNodo = this.nodos.length + 1;
            const tituloCopia = `Nodo ${numeroNodo} (Copia)`;
            
            const nodoDuplicado = {
                ...JSON.parse(JSON.stringify(nodoOriginal)),
                id: 'temp_' + Date.now(),
                titulo: tituloCopia,
                x: coordenadas.x,
                y: coordenadas.y,
                es_inicial: false,
                es_final: false
            };
            
            this.ocuparCelda(celdaLibre.columna, celdaLibre.fila, nodoDuplicado.id);
            this.nodos.push(nodoDuplicado);
            this.guardarEstado();
        },

        limpiarCanvas() {
            if (confirm('¿Estás seguro de que quieres limpiar todo el canvas?')) {
                this.nodos = [];
                this.conexiones = [];
                this.inicializarGrid();
                this.guardarEstado();
            }
        },

        exportarImagen() {
            // Implementar exportación a imagen
            console.log('Exportar imagen - en desarrollo');
        },

        // Funciones existentes mejoradas (mantener compatibilidad)
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
                        respuestas: nodo.respuestas || [],
                        tieneError: false
                    }));
                    
                    this.inicializarPosicionesNodos();
                    this.calcularConexiones();
                    this.validarEstructura();
                }
            } catch (error) {
                console.error('Error cargando diálogo:', error);
            }
        },

        inicializarPosicionesNodos() {
            this.inicializarGrid();
            
            this.nodos.forEach(nodo => {
                const posicion = this.obtenerPosicionGrid(nodo.x, nodo.y);
                if (this.esPosicionValida(posicion.columna, posicion.fila)) {
                    this.ocuparCelda(posicion.columna, posicion.fila, nodo.id);
                } else {
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
            
            // Buscar una posición libre cerca de los nodos existentes o en el centro
            let celdaLibre;
            if (this.nodos.length === 0) {
                // Primer nodo en el centro del grid expandido
                const centroColumna = Math.floor(this.grid.columnas / 2);
                const centroFila = Math.floor(this.grid.filas / 2);
                celdaLibre = this.encontrarCeldaLibreCerca(centroColumna, centroFila);
            } else {
                // Buscar cerca del último nodo agregado, pero más hacia la derecha
                const ultimoNodo = this.nodos[this.nodos.length - 1];
                const posicionUltimo = this.obtenerPosicionGrid(ultimoNodo.x, ultimoNodo.y);
                celdaLibre = this.encontrarCeldaLibreCerca(posicionUltimo.columna + 3, posicionUltimo.fila);
            }
            
            if (!celdaLibre) {
                // Si no hay espacio cerca, buscar en cualquier lugar
                celdaLibre = this.encontrarCeldaLibreCerca(0, 0);
                if (!celdaLibre) {
                    this.mostrarMensaje('No hay espacio disponible en el grid', 'error');
                    return;
                }
            }

            const coordenadas = this.obtenerCoordenadasGrid(celdaLibre.columna, celdaLibre.fila);
            
            // Generar título automático consecutivo
            const numeroNodo = this.nodos.length + 1;
            const tituloAutomatico = `Nodo ${numeroNodo}`;
            
            // El primer nodo será automáticamente inicial
            const esPrimerNodo = this.nodos.length === 0;
            
            // Crear opciones de respuesta automáticamente (A, B, C, D)
            const opcionesRespuesta = [
                { id: 'respuesta_A_' + Date.now(), texto: 'Opción A', color: '#28a745', puntuacion: 0, orden: 1, activo: true },
                { id: 'respuesta_B_' + Date.now(), texto: 'Opción B', color: '#ffc107', puntuacion: 0, orden: 2, activo: true },
                { id: 'respuesta_C_' + Date.now(), texto: 'Opción C', color: '#fd7e14', puntuacion: 0, orden: 3, activo: true },
                { id: 'respuesta_D_' + Date.now(), texto: 'Opción D', color: '#6f42c1', puntuacion: 0, orden: 4, activo: true }
            ];

            const nuevoNodo = {
                id: 'temp_' + Date.now(),
                titulo: tituloAutomatico,
                contenido: '',
                tipo: esPrimerNodo ? 'inicio' : 'desarrollo',
                es_inicial: esPrimerNodo,
                es_final: false,
                rol_id: rolId,
                rol: rol,
                respuestas: opcionesRespuesta,
                x: coordenadas.x,
                y: coordenadas.y,
                arrastrando: false,
                tieneError: false
            };
            
            this.ocuparCelda(celdaLibre.columna, celdaLibre.fila, nuevoNodo.id);
            this.nodos.push(nuevoNodo);
            this.seleccionarNodo(nuevoNodo.id);
            
            // Recalcular dimensiones del canvas
            this.calcularDimensionesCanvas();
            
            this.abrirModalNodo();
            this.guardarEstado();
        },

        // Funciones restantes (mantener compatibilidad con el código existente)
        seleccionarNodo(nodoId) {
            this.nodoSeleccionado = nodoId;
            const nodo = this.nodos.find(n => n.id === nodoId);
            if (nodo) {
                this.nodoEditando = { ...nodo };
                if (nodo.rol_id && !this.nodoEditando.rol) {
                    this.nodoEditando.rol = this.rolesDisponibles.find(r => r.id === nodo.rol_id);
                }
            }
        },

        deseleccionarNodo() {
            this.nodoSeleccionado = null;
        },

        manejarClicNodo(nodoId) {
            if (this.modo === 'conexion') {
                if (!this.conectando) {
                    this.iniciarConexion(nodoId);
                } else {
                    this.completarConexion(nodoId);
                }
            } else {
                this.seleccionarNodo(nodoId);
            }
        },

        iniciarConexion(nodoId) {
            this.conectando = true;
            this.nodoOrigenConexion = nodoId;
            this.mostrarMensaje('Haz clic en el nodo destino para crear la conexión', 'info');
            
            // Configurar seguimiento del mouse para línea temporal
            this.configurarSeguimientoMouse();
        },

        configurarSeguimientoMouse() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;

            const actualizarLineaTemporal = (event) => {
                if (!this.conectando || !this.nodoOrigenConexion) return;
                
                const rect = canvas.getBoundingClientRect();
                const mouseX = event.clientX - rect.left;
                const mouseY = event.clientY - rect.top;
                
                const nodoOrigen = this.obtenerNodoPorId(this.nodoOrigenConexion);
                if (nodoOrigen) {
                    const puntoOrigen = this.encontrarPuntoConexionDisponible(nodoOrigen, 'salida');
                    if (puntoOrigen) {
                        this.conexionTemporal = {
                            x1: puntoOrigen.x,
                            y1: puntoOrigen.y,
                            x2: mouseX,
                            y2: mouseY
                        };
                    }
                }
            };

            canvas.addEventListener('mousemove', actualizarLineaTemporal);
            
            // Limpiar listener cuando se cancele la conexión
            this.cleanupMouseTracking = () => {
                canvas.removeEventListener('mousemove', actualizarLineaTemporal);
            };
        },

        completarConexion(nodoId) {
            if (nodoId !== this.nodoOrigenConexion) {
                this.crearConexionDirecta(nodoId);
            } else {
                this.mostrarMensaje('No puedes conectar un nodo consigo mismo', 'warning');
            }
            this.cancelarConexion();
        },

        crearConexionDirecta(nodoDestinoId) {
            const nodoOrigen = this.nodos.find(n => n.id === this.nodoOrigenConexion);
            const nodoDestino = this.nodos.find(n => n.id === nodoDestinoId);
            
            if (!nodoOrigen || !nodoDestino) return;

            // Buscar el punto de conexión más cercano disponible
            const puntoOrigen = this.encontrarPuntoConexionDisponible(nodoOrigen, 'salida');
            const puntoDestino = this.encontrarPuntoConexionDisponible(nodoDestino, 'entrada');

            if (!puntoOrigen || !puntoDestino) {
                this.mostrarMensaje('No hay puntos de conexión disponibles', 'warning');
                return;
            }

            // Crear conexión
            const nuevaConexion = {
                id: 'conexion_' + Date.now(),
                desde: this.nodoOrigenConexion,
                hacia: nodoDestinoId,
                desdePunto: puntoOrigen.indice,
                haciaPunto: puntoDestino.indice,
                x1: puntoOrigen.x,
                y1: puntoOrigen.y,
                x2: puntoDestino.x,
                y2: puntoDestino.y,
                texto: 'Nueva Conexión',
                color: '#007bff',
                puntuacion: 0,
                tieneError: false
            };

            this.conexiones.push(nuevaConexion);
            console.log('Conexión creada:', nuevaConexion);
            console.log('Total conexiones:', this.conexiones.length);
            this.mostrarMensaje(`Conexión creada entre "${nodoOrigen.titulo}" y "${nodoDestino.titulo}"`, 'success');
            
            // Renderizar conexiones manualmente
            this.renderizarConexiones();
            
            this.guardarEstado();
            
            // Limpiar seguimiento del mouse
            if (this.cleanupMouseTracking) {
                this.cleanupMouseTracking();
                this.cleanupMouseTracking = null;
            }
        },

        calcularPuntoConexion(nodo, tipo, indice = 0) {
            const anchoNodo = 250;
            const altoNodo = 120;
            const centroX = nodo.x + anchoNodo / 2;
            
            if (tipo === 'entrada') {
                // Solo un punto de entrada en el centro arriba del nodo
                return {
                    x: centroX,
                    y: nodo.y - 10 // Arriba del nodo
                };
            } else {
                // 4 puntos de salida distribuidos horizontalmente abajo del nodo
                const puntos = [
                    { x: nodo.x + 20 },                    // Punto izquierdo
                    { x: nodo.x + anchoNodo / 3 },         // Punto izquierdo-centro
                    { x: nodo.x + (anchoNodo * 2) / 3 },   // Punto derecho-centro
                    { x: nodo.x + anchoNodo - 20 }         // Punto derecho
                ];
                
                return {
                    x: puntos[indice].x,
                    y: nodo.y + altoNodo + 10 // Abajo del nodo
                };
            }
        },

        obtenerPuntosConexion(nodo) {
            const puntos = [];
            
            // Solo un punto de entrada arriba del nodo
            puntos.push({
                tipo: 'entrada',
                indice: 0,
                ...this.calcularPuntoConexion(nodo, 'entrada', 0)
            });
            
            // 4 puntos de salida abajo del nodo (A, B, C, D)
            for (let i = 0; i < 4; i++) {
                puntos.push({
                    tipo: 'salida',
                    indice: i,
                    ...this.calcularPuntoConexion(nodo, 'salida', i)
                });
            }
            
            return puntos;
        },

        encontrarPuntoConexionDisponible(nodo, tipo) {
            // Obtener todos los puntos de conexión del nodo
            const puntos = this.obtenerPuntosConexion(nodo).filter(p => p.tipo === tipo);
            
            if (tipo === 'entrada') {
                // Solo hay un punto de entrada, siempre está disponible
                return puntos[0];
            } else {
                // Para puntos de salida, buscar el primero disponible en orden A, B, C, D
                const puntosOcupados = new Set();
                this.conexiones.forEach(conexion => {
                    if (conexion.desde === nodo.id) {
                        puntosOcupados.add(conexion.desdePunto);
                    }
                });
                
                // Buscar el primer punto disponible en orden (A=0, B=1, C=2, D=3)
                for (let i = 0; i < 4; i++) {
                    if (!puntosOcupados.has(i)) {
                        return puntos[i];
                    }
                }
                
                return null; // No hay puntos disponibles
            }
        },

        esPuntoOcupado(nodoId, tipo, indice) {
            return this.conexiones.some(conexion => {
                if (tipo === 'salida' && conexion.desde === nodoId) {
                    return conexion.desdePunto === indice;
                } else if (tipo === 'entrada' && conexion.hacia === nodoId) {
                    return conexion.haciaPunto === indice;
                }
                return false;
            });
        },

        seleccionarPuntoConexion(nodoId, tipo, indice) {
            if (this.modo === 'conexion') {
                if (!this.conectando) {
                    // Solo permitir iniciar conexión desde puntos de salida (abajo)
                    if (tipo === 'salida') {
                        // Verificar si el punto ya está ocupado
                        if (this.esPuntoOcupado(nodoId, 'salida', indice)) {
                            this.mostrarMensaje('Esta opción ya está conectada', 'warning');
                            return;
                        }
                        
                        this.conectando = true;
                        this.nodoOrigenConexion = nodoId;
                        this.puntoOrigenConexion = { tipo, indice };
                        const letraOpcion = String.fromCharCode(65 + indice);
                        this.mostrarMensaje(`Opción ${letraOpcion} seleccionada. Haz clic en un punto de entrada para completar la conexión`, 'info');
                    } else {
                        this.mostrarMensaje('Solo puedes iniciar conexiones desde las opciones A, B, C, D (abajo)', 'warning');
                    }
                } else {
                    // Completar conexión solo con puntos de entrada (arriba)
                    if (tipo === 'entrada' && this.puntoOrigenConexion.tipo === 'salida') {
                        this.completarConexionConPunto(nodoId, indice);
                    } else {
                        this.mostrarMensaje('Debes conectar una opción (A, B, C, D) con un punto de entrada', 'warning');
                    }
                }
            }
        },

        completarConexionConPunto(nodoDestinoId, puntoDestinoIndice) {
            const nodoOrigen = this.nodos.find(n => n.id === this.nodoOrigenConexion);
            const nodoDestino = this.nodos.find(n => n.id === nodoDestinoId);
            
            if (!nodoOrigen || !nodoDestino) return;

            if (nodoDestinoId === this.nodoOrigenConexion) {
                this.mostrarMensaje('No puedes conectar un nodo consigo mismo', 'warning');
                this.cancelarConexion();
                return;
            }

            // Encontrar el siguiente punto de salida disponible (A, B, C, D)
            const puntoOrigen = this.encontrarPuntoConexionDisponible(nodoOrigen, 'salida');
            if (!puntoOrigen) {
                this.mostrarMensaje('No hay más opciones disponibles (máximo 4 conexiones)', 'warning');
                this.cancelarConexion();
                return;
            }

            // El punto de entrada siempre es el único disponible
            const puntoDestino = this.calcularPuntoConexion(nodoDestino, 'entrada', 0);

            // Obtener la letra de la opción (A, B, C, D)
            const letrasOpciones = ['A', 'B', 'C', 'D'];
            const letraOpcion = letrasOpciones[puntoOrigen.indice];

            // Crear conexión
            const nuevaConexion = {
                id: 'conexion_' + Date.now(),
                desde: this.nodoOrigenConexion,
                hacia: nodoDestinoId,
                desdePunto: puntoOrigen.indice,
                haciaPunto: 0, // Siempre 0 para entrada
                x1: puntoOrigen.x,
                y1: puntoOrigen.y,
                x2: puntoDestino.x,
                y2: puntoDestino.y,
                texto: `Opción ${letraOpcion}`,
                color: this.obtenerColorOpcion(puntoOrigen.indice),
                puntuacion: 0,
                tieneError: false
            };

            this.conexiones.push(nuevaConexion);
            console.log('Conexión creada:', nuevaConexion);
            console.log('Total conexiones:', this.conexiones.length);
            this.mostrarMensaje(`Opción ${letraOpcion} conectada: "${nodoOrigen.titulo}" → "${nodoDestino.titulo}"`, 'success');
            
            // Renderizar conexiones manualmente
            this.renderizarConexiones();
            
            this.guardarEstado();
            this.cancelarConexion();
        },

        renderizarConexiones() {
            const container = document.getElementById('conexiones-container');
            if (!container) return;

            // Limpiar conexiones existentes
            container.innerHTML = '';

            // Renderizar cada conexión
            this.conexiones.forEach(conexion => {
                const grupo = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                grupo.setAttribute('data-conexion-id', conexion.id);

                // Línea de conexión
                const linea = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                linea.setAttribute('x1', conexion.x1);
                linea.setAttribute('y1', conexion.y1);
                linea.setAttribute('x2', conexion.x2);
                linea.setAttribute('y2', conexion.y2);
                linea.setAttribute('stroke', conexion.color || '#007bff');
                linea.setAttribute('stroke-width', conexion.tieneError ? '4' : '3');
                linea.setAttribute('stroke-dasharray', conexion.tieneError ? '5,5' : 'none');
                linea.setAttribute('fill', 'none');
                linea.setAttribute('marker-end', conexion.tieneError ? 'url(#arrowhead-error)' : 'url(#arrowhead-mejorado)');
                linea.setAttribute('class', 'conexion-line-mejorada');
                linea.style.cursor = 'pointer';
                
                // Eventos de clic
                linea.addEventListener('click', () => this.editarConexion(conexion.id));
                linea.addEventListener('dblclick', () => this.eliminarConexion(conexion.id));

                // Etiqueta de texto
                const texto = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                texto.setAttribute('x', (conexion.x1 + conexion.x2) / 2);
                texto.setAttribute('y', (conexion.y1 + conexion.y2) / 2 - 10);
                texto.setAttribute('text-anchor', 'middle');
                texto.setAttribute('class', 'conexion-label-mejorada');
                texto.setAttribute('fill', conexion.color || '#007bff');
                texto.setAttribute('font-size', '12');
                texto.setAttribute('font-weight', 'bold');
                texto.textContent = conexion.texto || '';

                // Puntuación
                const puntuacion = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                puntuacion.setAttribute('x', (conexion.x1 + conexion.x2) / 2);
                puntuacion.setAttribute('y', (conexion.y1 + conexion.y2) / 2 + 10);
                puntuacion.setAttribute('text-anchor', 'middle');
                puntuacion.setAttribute('class', 'conexion-score-mejorada');
                puntuacion.setAttribute('fill', conexion.color || '#007bff');
                puntuacion.setAttribute('font-size', '10');
                puntuacion.setAttribute('opacity', '0.8');
                puntuacion.textContent = conexion.puntuacion ? `+${conexion.puntuacion}` : '';

                grupo.appendChild(linea);
                grupo.appendChild(texto);
                grupo.appendChild(puntuacion);
                container.appendChild(grupo);
            });
        },

        cancelarConexion() {
            this.conectando = false;
            this.nodoOrigenConexion = null;
            this.puntoOrigenConexion = null;
            this.conexionTemporal = { x1: 0, y1: 0, x2: 0, y2: 0 };
            
            // Limpiar seguimiento del mouse
            if (this.cleanupMouseTracking) {
                this.cleanupMouseTracking();
                this.cleanupMouseTracking = null;
            }
        },

        // Funciones placeholder para compatibilidad
        mostrarMensaje(mensaje, tipo) {
            // Crear notificación Bootstrap
            const alertClass = {
                'success': 'alert-success',
                'error': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            }[tipo] || 'alert-info';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    <i class="bi bi-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    ${mensaje}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Agregar al DOM
            document.body.insertAdjacentHTML('beforeend', alertHtml);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                const alert = document.querySelector('.alert:last-of-type');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        },

        abrirModalNodo() {
            // Mostrar el modal de nodo
            const modal = new bootstrap.Modal(document.getElementById('modalNodo'));
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
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNodo'));
            modal.hide();
            this.guardarEstado();
        },

        actualizarRolSeleccionado() {
            if (this.nodoEditando.rol_id) {
                this.nodoEditando.rol = this.rolesDisponibles.find(r => r.id === this.nodoEditando.rol_id);
            } else {
                this.nodoEditando.rol = null;
            }
        },

        abrirModalConexion(nodoDestinoId) {
            // Implementar modal de conexión
            console.log('Abrir modal conexión', nodoDestinoId);
        },

        editarConexion(conexionId) {
            const conexion = this.conexiones.find(c => c.id === conexionId);
            if (conexion) {
                this.conexionEditando = { ...conexion };
                const modal = new bootstrap.Modal(document.getElementById('modalConexion'));
                modal.show();
            }
        },

        eliminarConexion(conexionId) {
            if (confirm('¿Estás seguro de que quieres eliminar esta conexión?')) {
                const conexionEliminada = this.conexiones.find(c => c.id === conexionId);
                if (!conexionEliminada) return;

                // Eliminar la conexión
                this.conexiones = this.conexiones.filter(c => c.id !== conexionId);
                
                // Reorganizar las conexiones restantes del mismo nodo origen en orden
                this.reorganizarConexiones(conexionEliminada.desde);
                
                this.mostrarMensaje('Conexión eliminada', 'success');
                this.renderizarConexiones();
                this.guardarEstado();
            }
        },

        reorganizarConexiones(nodoId) {
            // Obtener todas las conexiones del nodo
            const conexionesNodo = this.conexiones.filter(c => c.desde === nodoId);
            
            // Ordenar por punto de salida
            conexionesNodo.sort((a, b) => a.desdePunto - b.desdePunto);
            
            // Reasignar puntos en orden A, B, C, D
            conexionesNodo.forEach((conexion, index) => {
                const puntoAnterior = conexion.desdePunto;
                const nuevoPunto = index;
                
                if (puntoAnterior !== nuevoPunto) {
                    // Actualizar el punto de la conexión
                    conexion.desdePunto = nuevoPunto;
                    
                    // Actualizar las coordenadas
                    const nodo = this.nodos.find(n => n.id === nodoId);
                    if (nodo) {
                        const nuevoPuntoCoords = this.calcularPuntoConexion(nodo, 'salida', nuevoPunto);
                        conexion.x1 = nuevoPuntoCoords.x;
                        conexion.y1 = nuevoPuntoCoords.y;
                        
                        // Actualizar el texto de la opción
                        const letrasOpciones = ['A', 'B', 'C', 'D'];
                        conexion.texto = `Opción ${letrasOpciones[nuevoPunto]}`;
                        conexion.color = this.obtenerColorOpcion(nuevoPunto);
                    }
                }
            });
        },

        guardarConexion() {
            const index = this.conexiones.findIndex(c => c.id === this.conexionEditando.id);
            if (index !== -1) {
                this.conexiones[index] = { ...this.conexiones[index], ...this.conexionEditando };
            }
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalConexion'));
            modal.hide();
            this.renderizarConexiones();
            this.guardarEstado();
        },

        editarNodo(nodoId) {
            this.seleccionarNodo(nodoId);
            this.abrirModalNodo();
        },

        eliminarNodo(nodoId) {
            if (confirm('¿Estás seguro de que quieres eliminar este nodo?')) {
                const nodo = this.nodos.find(n => n.id === nodoId);
                if (nodo) {
                    const posicion = this.obtenerPosicionGrid(nodo.x, nodo.y);
                    this.liberarCelda(posicion.columna, posicion.fila);
                }
                
                // Eliminar conexiones relacionadas con este nodo
                this.conexiones = this.conexiones.filter(c => c.desde !== nodoId && c.hacia !== nodoId);
                
                this.nodos = this.nodos.filter(n => n.id !== nodoId);
                this.deseleccionarNodo();
                this.calcularConexiones();
                this.renderizarConexiones();
                
                // Recalcular dimensiones del canvas
                this.calcularDimensionesCanvas();
                
                this.guardarEstado();
            }
        },

        calcularConexiones() {
            // Actualizar posiciones de las conexiones cuando los nodos se mueven
            this.conexiones.forEach(conexion => {
                const nodoOrigen = this.nodos.find(n => n.id === conexion.desde);
                const nodoDestino = this.nodos.find(n => n.id === conexion.hacia);
                
                if (nodoOrigen && nodoDestino) {
                    const puntoOrigen = this.calcularPuntoConexion(nodoOrigen, 'salida', conexion.desdePunto || 0);
                    const puntoDestino = this.calcularPuntoConexion(nodoDestino, 'entrada', conexion.haciaPunto || 0);
                    
                    conexion.x1 = puntoOrigen.x;
                    conexion.y1 = puntoOrigen.y;
                    conexion.x2 = puntoDestino.x;
                    conexion.y2 = puntoDestino.y;
                }
            });
            
            // Renderizar las conexiones actualizadas
            this.renderizarConexiones();
        },

        iniciarArrastre(event, nodoId) {
            if (event.button !== 0) return;
            
            event.preventDefault();
            event.stopPropagation();
            
            this.arrastrando = true;
            this.nodoArrastrando = nodoId;
            const nodo = this.nodos.find(n => n.id === nodoId);
            
            if (!nodo) return;
            
            this.offsetX = event.clientX - nodo.x;
            this.offsetY = event.clientY - nodo.y;
            nodo.arrastrando = true;
            
            if (this.nodoSeleccionado !== nodoId) {
                this.seleccionarNodo(nodoId);
            }
            
            document.addEventListener('mousemove', (e) => this.arrastrar(e));
            document.addEventListener('mouseup', (e) => this.soltar(e));
        },

        arrastrar(event) {
            if (!this.arrastrando || !this.nodoArrastrando) return;
            
            event.preventDefault();
            event.stopPropagation();
            
            const nodo = this.nodos.find(n => n.id === this.nodoArrastrando);
            if (nodo) {
                const nuevaX = event.clientX - this.offsetX;
                const nuevaY = event.clientY - this.offsetY;
                
                const posicionGrid = this.obtenerPosicionGrid(nuevaX, nuevaY);
                
                if (!this.estaCeldaOcupada(posicionGrid.columna, posicionGrid.fila, nodo.id)) {
                    const posicionAnterior = this.obtenerPosicionGrid(nodo.x, nodo.y);
                    this.liberarCelda(posicionAnterior.columna, posicionAnterior.fila);
                    
                    this.ocuparCelda(posicionGrid.columna, posicionGrid.fila, nodo.id);
                    
                    const coordenadas = this.obtenerCoordenadasGrid(posicionGrid.columna, posicionGrid.fila);
                    nodo.x = coordenadas.x;
                    nodo.y = coordenadas.y;
                    
                    // Verificar si necesita expandir el canvas
                    this.verificarLimitesCanvas();
                    
                    // Forzar recálculo de conexiones
                    this.calcularConexiones();
                }
            }
        },

        soltar(event) {
            if (!this.arrastrando) return;
            
            const nodo = this.nodos.find(n => n.id === this.nodoArrastrando);
            if (nodo) {
                nodo.arrastrando = false;
            }
            
            this.arrastrando = false;
            this.nodoArrastrando = null;
            
            document.removeEventListener('mousemove', this.arrastrar);
            document.removeEventListener('mouseup', this.soltar);
            
            // Recalcular conexiones después de mover el nodo
            this.calcularConexiones();
            
            // Recalcular dimensiones del canvas
            this.calcularDimensionesCanvas();
            
            this.guardarEstado();
        },

        manejarTeclado(event) {
            // Implementar manejo de teclado
        },

        mostrarMenuContextual(event, nodoId) {
            event.preventDefault();
            // Implementar menú contextual
        },

        editarRespuesta(respuestaId) {
            // Implementar edición de respuesta
        },

        onDrop(event) {
            // Implementar drop
        },

        // Funciones de guardado
        async guardar() {
            this.guardando = true;
            try {
                // Implementar guardado
                this.modificado = false;
                this.mostrarMensaje('Guardado exitosamente', 'success');
            } catch (error) {
                this.mostrarMensaje('Error al guardar', 'error');
            } finally {
                this.guardando = false;
            }
        },

        async guardarBorrador() {
            await this.guardar();
        },

        async activarDialogo() {
            this.validarEstructura();
            if (this.erroresValidacionComputed.length > 0) {
                this.mostrarMensaje('Corrige los errores antes de activar', 'error');
                return;
            }
            // Implementar activación
        },

        previsualizar() {
            // Implementar previsualización
        },

        exportar() {
            // Implementar exportación
        },

        exportarImagen() {
            // Implementar exportación de imagen
        }
    }
}
</script>
@endsection
