@extends('layouts.app')

@section('title', 'Editor de Diálogos v2 - Simulador de Juicios Orales')

@push('head-scripts')
<script src="/js/jsplumb.min.js"></script>
<script>
// Definir la función ANTES de que Alpine.js la necesite
(function() {
    window.dialogoEditorV2 = function() {
        const dialogoData = @json($dialogo ?? null);
        const dialogoId = dialogoData ? dialogoData.id : null;
        const rolesDisponibles = @json($rolesDisponibles ?? []);
        
        console.log('Dialogo data desde servidor:', dialogoData);
        console.log('Dialogo ID extraído:', dialogoId);
        console.log('Roles disponibles:', rolesDisponibles);
        
        return {
            dialogo: dialogoData,
            dialogoId: dialogoId,
            rolesDisponibles: rolesDisponibles,
            dialogoData: {
                nombre: dialogoData ? (dialogoData.nombre || '') : '',
                descripcion: dialogoData ? (dialogoData.descripcion || '') : '',
                publico: dialogoData ? (dialogoData.publico || false) : false,
                estado: dialogoData ? (dialogoData.estado || 'borrador') : 'borrador',
            },
            nodos: [],
            nodoSeleccionado: null,
            guardando: false,
            jsPlumbInstance: null,
                jsPlumbReady: false,
            zoom: 1.0,
            validacionResultado: null,
            showModalNodo: false,
            redibujando: false,
            guardarPosicionesTimer: null,
            nuevoNodoFormulario: {
                tipo: 'desarrollo',
                rol_id: null,
                titulo: '',
                contenido: ''
            },

            init() {
                console.log('Inicializando editor de diálogos...');
                console.log('dialogoId:', this.dialogoId);
                console.log('dialogo:', this.dialogo);
                
                // Inicializar nodos desde los datos del servidor
                if (this.dialogo && this.dialogo.nodos && this.dialogo.nodos.length > 0) {
                    console.log('Cargando nodos desde datos del servidor:', this.dialogo.nodos.length);
                    const nodosIniciales = [];
                    for (let i = 0; i < this.dialogo.nodos.length; i++) {
                        const nodo = this.dialogo.nodos[i];
                        nodosIniciales.push({
                            id: nodo.id,
                            dialogo_id: nodo.dialogo_id,
                            tipo: nodo.tipo,
                            titulo: nodo.titulo || '',
                            contenido: nodo.contenido || '',
                            menu_text: nodo.menu_text || '',
                            rol_id: nodo.rol_id,
                            conversant_id: nodo.conversant_id,
                            posicion_x: nodo.posicion_x || 100,
                            posicion_y: nodo.posicion_y || 100,
                            es_inicial: nodo.es_inicial || false,
                            es_final: nodo.es_final || false,
                            instrucciones: nodo.instrucciones || '',
                            activo: nodo.activo !== undefined ? nodo.activo : true,
                            condiciones: nodo.condiciones || [],
                            consecuencias: nodo.consecuencias || [],
                            respuestas: nodo.respuestas || []
                        });
                    }
                    this.nodos = nodosIniciales;
                }
                
                console.log('nodos iniciales:', this.nodos.length);
                
                // Inicializar jsPlumb primero
                this.$nextTick(() => {
                    this.inicializarJsPlumb();
                    // Luego cargar el diálogo y renderizar
                    if (this.dialogoId) {
                        console.log('Cargando diálogo con ID:', this.dialogoId);
                        this.cargarDialogo();
                    } else {
                        // Si ya tenemos nodos del servidor, renderizarlos
                        if (this.nodos.length > 0) {
                            this.$nextTick(() => {
                                this.hacerNodosArrastrables();
                                this.renderizarConexiones();
                            });
                        }
                    }
                });
            },

            abrirModalNodo() {
                this.nuevoNodoFormulario = { tipo: 'desarrollo', rol_id: null, titulo: '', contenido: '' };
                this.showModalNodo = true;
            },

            cerrarModalNodo() {
                this.showModalNodo = false;
            },

            async confirmarNuevoNodo() {
                if (!this.nuevoNodoFormulario.tipo || !this.nuevoNodoFormulario.rol_id) {
                    alert('Selecciona tipo y rol');
                    return;
                }
                await this.crearNodoConRol(
                    this.nuevoNodoFormulario.tipo,
                    this.nuevoNodoFormulario.rol_id,
                    this.nuevoNodoFormulario.titulo,
                    this.nuevoNodoFormulario.contenido
                );
                this.showModalNodo = false;
            },

            obtenerColorRol(rolId) {
                if (!rolId) return '#666';
                const rol = this.rolesDisponibles.find(r => r.id === rolId);
                return rol && rol.color ? rol.color : '#666';
            },

            colorNodo(nodo) {
                if (!nodo) return '#666';
                if (nodo.tipo === 'respuesta') {
                    // Color especial para nodos de tipo respuesta
                    return '#ffb347';
                }
                return this.obtenerColorRol(nodo.rol_id);
            },

            rolNombre(rolId) {
                if (!rolId) return null;
                const rol = this.rolesDisponibles.find(r => r.id === rolId);
                return rol ? rol.nombre : null;
            },

            parseNodeId(domId) {
                if (!domId) return null;
                return domId.toString().replace('node-', '');
            },

            allowInput(tipo) {
                return tipo !== 'inicio';
            },

            allowOutput(tipo) {
                if (tipo === 'final') return 0;
                if (tipo === 'decision') return 3; // máximo 3 salidas visibles
                // respuesta y desarrollo: 1 salida
                return 1;
            },

            configurarEndpoints() {
                if (!this.jsPlumbInstance) return;
                this.nodos.forEach((nodo) => {
                    const elId = 'node-' + nodo.id;
                    try {
                        this.jsPlumbInstance.removeAllEndpoints(elId);
                    } catch (_) {}

                    const color = this.colorNodo(nodo);
                    const common = {
                        endpoint: 'Dot',
                        paintStyle: { fill: color, strokeWidth: 0, radius: 6 },
                        connectorStyle: { stroke: color, strokeWidth: 2 },
                        connectorOverlays: [['Arrow', { width: 10, length: 10, location: 1 }]],
                        maxConnections: -1,
                        cssClass: 'dp-endpoint'
                    };

                    // Entrada
                    if (this.allowInput(nodo.tipo)) {
                    this.jsPlumbInstance.addEndpoint(elId, {
                        ...common,
                        isTarget: true,
                        isSource: false,
                        anchor: ['Top'],
                    });
                    }

                    // Salida(s)
                    const outMax = this.allowOutput(nodo.tipo);
                    if (outMax !== 0) {
                        if (nodo.tipo === 'decision') {
                            // Tres puntos visibles en la parte inferior
                            const anchors = [
                                [0.2, 1, 0, 1],
                                [0.5, 1, 0, 1],
                                [0.8, 1, 0, 1],
                            ];
                            anchors.forEach((anch) => {
                                this.jsPlumbInstance.addEndpoint(elId, {
                                    ...common,
                                    isSource: true,
                                    isTarget: false,
                                    anchor: anch,
                                    maxConnections: 1,
                                });
                            });
                        } else {
                            this.jsPlumbInstance.addEndpoint(elId, {
                                ...common,
                                isSource: true,
                                isTarget: false,
                                anchor: ['Bottom'],
                                maxConnections: outMax,
                            });
                        }
                    }
                });
            },

            async cargarDialogo() {
                if (!this.dialogoId) {
                    return;
                }
                
                try {
                    const response = await fetch(`/api/dialogos-v2/${this.dialogoId}/editor`);
                    const data = await response.json();
                    if (data.success) {
                        this.nodos = (data.data.nodos || []).map(function(nodo) {
                            return {
                                id: nodo.id,
                                dialogo_id: nodo.dialogo_id,
                                tipo: nodo.tipo,
                                titulo: nodo.titulo || '',
                                contenido: nodo.contenido || '',
                                menu_text: nodo.menu_text || '',
                                rol_id: nodo.rol_id,
                                conversant_id: nodo.conversant_id,
                                posicion_x: nodo.posicion_x || 100,
                                posicion_y: nodo.posicion_y || 100,
                                es_inicial: nodo.es_inicial || false,
                                es_final: nodo.es_final || false,
                                instrucciones: nodo.instrucciones || '',
                                activo: nodo.activo !== undefined ? nodo.activo : true,
                                condiciones: nodo.condiciones || [],
                                consecuencias: nodo.consecuencias || [],
                                respuestas: nodo.respuestas || []
                            };
                        });
                        this.dialogoData = {
                            nombre: data.data.dialogo.nombre,
                            descripcion: data.data.dialogo.descripcion || '',
                            publico: data.data.dialogo.publico,
                            estado: data.data.dialogo.estado,
                        };
                        // Renderizar después de cargar
                        this.$nextTick(() => {
                            this.hacerNodosArrastrables();
                            this.renderizarConexiones();
                        });
                    }
                } catch (error) {
                    console.error('Error al cargar diálogo:', error);
                }
            },

            inicializarJsPlumb() {
                // Esperar a que jsPlumb esté disponible
                let intentos = 0;
                const maxIntentos = 50; // Máximo 5 segundos (50 * 100ms)
                
                const checkJsPlumb = () => {
                    intentos++;
                    
                    // Verificar múltiples formas de acceso a jsPlumb
                    const jsPlumbLib = window.jsPlumb || 
                                     (typeof jsPlumb !== 'undefined' ? jsPlumb : null) ||
                                     (typeof window.jsPlumb !== 'undefined' ? window.jsPlumb : null);
                    
                    if (!jsPlumbLib || typeof jsPlumbLib.getInstance !== 'function') {
                        if (intentos >= maxIntentos) {
                            console.error('❌ jsPlumb no se pudo cargar después de ' + maxIntentos + ' intentos');
                            console.error('Verifica que el script se esté cargando correctamente desde el CDN');
                            console.error('Estado actual:', {
                                windowJsPlumb: typeof window.jsPlumb,
                                jsPlumb: typeof jsPlumb,
                                scripts: Array.from(document.querySelectorAll('script[src*="jsplumb"]')).map(s => s.src)
                            });
                            alert('Error: No se pudo cargar la biblioteca jsPlumb. Por favor, recarga la página.');
                            return;
                        }
                        
                        if (intentos % 10 === 0) {
                            console.warn('jsPlumb aún no está cargado, reintentando... (' + intentos + '/' + maxIntentos + ')');
                        }
                        setTimeout(checkJsPlumb, 100);
                        return;
                    }

                    const canvas = document.getElementById('editor-canvas');
                    if (!canvas) {
                        console.error('No se encontró el elemento editor-canvas');
                        return;
                    }

                    try {
                        // jsPlumb 2.x usa jsPlumb.getInstance() directamente
                        this.jsPlumbInstance = jsPlumbLib.getInstance({
                            PaintStyle: { stroke: '#007bff', strokeWidth: 2 },
                        EndpointStyle: { fill: '#007bff' },
                            Connector: ['Bezier', { curviness: 50 }],
                            Anchors: ['Top', 'Bottom', 'Left', 'Right'],
                            Container: canvas
                        });

                        // Eventos de conexión/desconexión para construir respuestas
                        this.jsPlumbInstance.bind('connection', (info) => {
                            const skipSave = info.connection.getParameter('skipSave');
                            if (skipSave) return;
                            const sourceId = this.parseNodeId(info.sourceId);
                            const targetId = this.parseNodeId(info.targetId);
                            // Mostrar conexión inmediata con color del rol
                            const srcNodo = this.nodos.find(n => n.id == sourceId);
                            const color = this.obtenerColorRol(srcNodo ? srcNodo.rol_id : null);
                            try {
                                this.jsPlumbInstance.connect({
                                    source: info.sourceId,
                                    target: info.targetId,
                                    paintStyle: { stroke: color, strokeWidth: 2 },
                                    parameters: { skipSave: true }
                                }, { connector: ['Bezier', { curviness: 50 }], anchors: ['Bottom', 'Top'] });
                            } catch (e) {
                                console.warn('No se pudo pintar conexión inmediata:', e);
                            }
                            this.agregarRespuestaDesdeConexion(sourceId, targetId);
                        });

                        this.jsPlumbInstance.bind('connectionDetached', (info) => {
                            if (this.redibujando) return;
                            const sourceId = this.parseNodeId(info.sourceId);
                            const targetId = this.parseNodeId(info.targetId);
                            this.eliminarRespuestaDesdeConexion(sourceId, targetId);
                        });

                        this.jsPlumbReady = true;

                        // En cuanto jsPlumb esté listo, asegurar render y endpoints
                        this.$nextTick(() => {
                            this.hacerNodosArrastrables();
                            this.renderizarConexiones();
                        });

                        console.log('✓ jsPlumb inicializado correctamente');
                    } catch (error) {
                        console.error('Error al inicializar jsPlumb:', error);
                    }
                };

                // Iniciar el check después de un pequeño delay para asegurar que el DOM está listo
                setTimeout(checkJsPlumb, 50);
            },

            hacerNodosArrastrables() {
                if (!this.jsPlumbInstance) return;
                
                this.nodos.forEach(function(nodo) {
                    const element = document.getElementById('node-' + nodo.id);
                    if (element) {
                        this.jsPlumbInstance.draggable('node-' + nodo.id, {
                            // En jsPlumb 2.x el callback correcto es "drag" (no onDrag)
                            drag: function(params) {
                                const index = this.nodos.findIndex(function(n) { return n.id === nodo.id; });
                                if (index !== -1) {
                                    this.nodos[index].posicion_x = params.pos[0];
                                    this.nodos[index].posicion_y = params.pos[1];
                                }
                            }.bind(this),
                            stop: function(params) {
                                // Programar guardado de posiciones al soltar
                                this.programarGuardadoPosiciones();
                            }.bind(this)
                        });
                    }
                }.bind(this));
            },

            renderizarConexiones() {
                if (!this.jsPlumbInstance) {
                    // Reintentar suavemente hasta que jsPlumb esté listo
                    if (!this._renderRetry) this._renderRetry = 0;
                    if (this._renderRetry < 20) {
                        this._renderRetry++;
                        setTimeout(() => this.renderizarConexiones(), 80);
                    } else {
                        console.warn('jsPlumb no está inicializado; se agotaron reintentos');
                    }
                    return;
                }
                this._renderRetry = 0;

                // Esperar a DOM y dar un pequeño margen para montar nodos antes de conectar
                this.$nextTick(() => {
                    setTimeout(() => {
                        try {
                            this.redibujando = true;
                            this.jsPlumbInstance.batch(() => {
                                try {
                                    this.jsPlumbInstance.deleteEveryConnection();
                                } catch (e) {
                                    console.warn('Error al limpiar conexiones:', e);
                                }

                                this.configurarEndpoints();
                                this.hacerNodosArrastrables();

                                this.nodos.forEach((nodo) => {
                                    if (nodo.respuestas && nodo.respuestas.length > 0) {
                                        nodo.respuestas.forEach((respuesta) => {
                                            if (respuesta.nodo_siguiente_id) {
                                                const sourceElement = document.getElementById('node-' + nodo.id);
                                                const targetElement = document.getElementById('node-' + respuesta.nodo_siguiente_id);
                                                if (sourceElement && targetElement) {
                                                    try {
                                                        this.jsPlumbInstance.connect({
                                                            source: 'node-' + nodo.id,
                                                            target: 'node-' + respuesta.nodo_siguiente_id,
                                                        paintStyle: { stroke: respuesta.color || this.colorNodo(this.nodos.find(n => n.id == nodo.id)), strokeWidth: 2 },
                                                            parameters: { skipSave: true }
                                                        }, {
                                                            connector: ['Bezier', { curviness: 50 }],
                                                            anchors: ['Bottom', 'Top']
                                                        });
                                                    } catch (e) {
                                                        console.warn('Error conectando nodo ' + nodo.id + ' a ' + respuesta.nodo_siguiente_id + ':', e);
                                                    }
                                                }
                                            }
                                        });
                                    }
                                });
                            });
                            this.jsPlumbInstance.repaintEverything();
                        } catch (e) {
                            console.warn('Error en renderizarConexiones batch:', e);
                        } finally {
                            this.redibujando = false;
                        }
                    }, 30);
                });
            },

            agregarRespuestaDesdeConexion(sourceId, targetId) {
                if (!sourceId || !targetId) return;
                // Si algún ID es temporal, no persistir todavía
                if (sourceId.toString().startsWith('temp-') || targetId.toString().startsWith('temp-')) {
                    console.warn('Conexión entre nodos temporales; guarda los nodos primero.');
                    return;
                }
                const source = this.nodos.find(n => n.id == sourceId);
                if (!source) return;
                if (!source.respuestas) source.respuestas = [];

                const target = this.nodos.find(n => n.id == targetId);

                // Reglas de herencia/transformación por conexión
                if (source.tipo === 'decision' && target) {
                    // Al conectarse desde decisión: el hijo se vuelve tipo respuesta,
                    // hereda el rol del padre y usa color de respuesta
                    target.tipo = 'respuesta';
                    if (source.rol_id) {
                        target.rol_id = source.rol_id;
                    }
                    this.persistirNodoTipoRol(target);
                } else if (source.tipo !== 'decision' && target && target.tipo === 'respuesta') {
                    // Si se reconecta a salida de nodo NO decisión y el hijo era respuesta,
                    // vuelve a desarrollo (conserva rol actual)
                    target.tipo = 'desarrollo';
                    this.persistirNodoTipoRol(target);
                }

                // Evitar duplicados
                const existe = source.respuestas.some(r => r.nodo_siguiente_id == targetId);
                if (existe) return;

                source.respuestas.push({
                    id: 'temp-' + Date.now(),
                    nodo_padre_id: sourceId,
                    nodo_siguiente_id: targetId,
                    texto: 'Continuar',
                    orden: source.respuestas.length,
                    puntuacion: 0,
                    color: this.obtenerColorRol(source.rol_id),
                    requiere_usuario_registrado: false,
                    es_opcion_por_defecto: false,
                    requiere_rol: [],
                    condiciones: [],
                    consecuencias: []
                });

                // Refrescar nodo seleccionado si es el mismo origen
                if (this.nodoSeleccionado && this.nodoSeleccionado.id == sourceId) {
                    this.nodoSeleccionado = JSON.parse(JSON.stringify(source));
                }

                // Persistir respuesta nueva
                const respuestaRef = source.respuestas[source.respuestas.length - 1];
                this.persistirRespuestaConexion(sourceId, respuestaRef);

                // Forzar re-render de conexiones
                this.$nextTick(() => {
                    this.renderizarConexiones();
                    try { this.jsPlumbInstance.repaintEverything(); } catch (_) {}
                });
            },

            eliminarRespuestaDesdeConexion(sourceId, targetId) {
                if (!sourceId || !targetId) return;
                const source = this.nodos.find(n => n.id == sourceId);
                if (!source || !source.respuestas) return;
                const resp = source.respuestas.find(r => r.nodo_siguiente_id == targetId);
                source.respuestas = source.respuestas.filter(r => r.nodo_siguiente_id != targetId);

                // Si la respuesta existía en BD, eliminar; si no, solo limpiar en memoria
                if (resp && resp.id && !resp.id.toString().startsWith('temp-')) {
                    fetch('/api/dialogos-v2/' + this.dialogoId + '/nodos/' + sourceId + '/respuestas/' + resp.id, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    }).then(r => {
                        if (!r.ok) {
                            console.warn('DELETE respuesta no OK, ignorando:', r.status);
                        }
                    }).catch(e => console.warn('Error eliminando respuesta (ignorado):', e));
                }

                // Refrescar nodo seleccionado si aplica
                if (this.nodoSeleccionado && this.nodoSeleccionado.id == sourceId) {
                    this.nodoSeleccionado = JSON.parse(JSON.stringify(source));
                }

                this.$nextTick(() => this.renderizarConexiones());
            },

            async persistirRespuestaConexion(nodoPadreId, respuesta) {
                if (!this.dialogoId) return;
                // Validar IDs numéricos
                if (!nodoPadreId || nodoPadreId.toString().startsWith('temp-')) return;
                if (!respuesta.nodo_siguiente_id || respuesta.nodo_siguiente_id.toString().startsWith('temp-')) return;
                try {
                    const url = '/api/dialogos-v2/' + this.dialogoId + '/nodos/' + nodoPadreId + '/respuestas';
                    const resp = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            nodo_siguiente_id: respuesta.nodo_siguiente_id,
                            texto: respuesta.texto && respuesta.texto.trim() !== '' ? respuesta.texto : 'Continuar',
                            orden: respuesta.orden || 0,
                            puntuacion: respuesta.puntuacion || 0,
                            color: respuesta.color || this.obtenerColorRol(this.nodos.find(n => n.id == nodoPadreId)?.rol_id),
                            requiere_usuario_registrado: respuesta.requiere_usuario_registrado || false,
                            es_opcion_por_defecto: respuesta.es_opcion_por_defecto || false,
                            requiere_rol: respuesta.requiere_rol || [],
                            condiciones: respuesta.condiciones || [],
                            consecuencias: respuesta.consecuencias || []
                        })
                    });
                    const data = await resp.json();
                    if (data.success && data.data && data.data.respuesta) {
                        respuesta.id = data.data.respuesta.id;
                        respuesta.orden = data.data.respuesta.orden;
                        console.log('Respuesta persistida', data.data.respuesta);
                        // Recargar nodos desde backend para alinear respuestas/IDs y redibujar
                        this.$nextTick(() => this.cargarDialogo());
                    } else {
                        console.warn('No se pudo persistir respuesta:', data);
                    }
                } catch (e) {
                    console.warn('No se pudo persistir respuesta:', e);
                }
            },

            async persistirNodoTipoRol(nodo) {
                // Solo persistir si ya es un nodo real
                if (!nodo || !this.dialogoId || nodo.id.toString().startsWith('temp-')) return;
                try {
                    const url = '/api/dialogos-v2/' + this.dialogoId + '/nodos/' + nodo.id;
                    const payload = {
                        id: nodo.id,
                        tipo: nodo.tipo,
                        titulo: nodo.titulo || '',
                        contenido: nodo.contenido || '',
                        menu_text: nodo.menu_text || '',
                        rol_id: nodo.rol_id || null,
                        conversant_id: nodo.conversant_id || null,
                        posicion_x: parseInt(nodo.posicion_x) || 0,
                        posicion_y: parseInt(nodo.posicion_y) || 0,
                        es_inicial: !!nodo.es_inicial,
                        es_final: !!nodo.es_final,
                        instrucciones: nodo.instrucciones || '',
                        activo: nodo.activo !== undefined ? nodo.activo : true,
                        condiciones: nodo.condiciones || [],
                        consecuencias: nodo.consecuencias || [],
                    };
                    await fetch(url, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload)
                    });
                } catch (e) {
                    console.warn('No se pudo persistir tipo/rol del nodo', nodo?.id, e);
                }
            },

            seleccionarNodo(nodo) {
                // Crear una copia del nodo sin usar spread operator
                this.nodoSeleccionado = {
                    id: nodo.id,
                    dialogo_id: nodo.dialogo_id,
                    tipo: nodo.tipo,
                    titulo: nodo.titulo || '',
                    contenido: nodo.contenido || '',
                    menu_text: nodo.menu_text || '',
                    rol_id: nodo.rol_id,
                    conversant_id: nodo.conversant_id,
                    posicion_x: nodo.posicion_x || 100,
                    posicion_y: nodo.posicion_y || 100,
                    es_inicial: nodo.es_inicial || false,
                    es_final: nodo.es_final || false,
                    instrucciones: nodo.instrucciones || '',
                    activo: nodo.activo !== undefined ? nodo.activo : true,
                    condiciones: nodo.condiciones || [],
                    consecuencias: nodo.consecuencias || [],
                    respuestas: nodo.respuestas ? JSON.parse(JSON.stringify(nodo.respuestas)) : []
                };
            },

            deseleccionarNodo() {
                this.nodoSeleccionado = null;
            },

            crearNodo(tipo) {
                console.log('crearNodo llamado con tipo:', tipo);
                console.log('dialogoId actual:', this.dialogoId);
                
                // Si no hay diálogo guardado, crear uno automáticamente primero
                if (!this.dialogoId) {
                    console.log('No hay dialogoId, guardando primero...');
                    // Guardar el diálogo primero
                    this.guardar().then(function() {
                        console.log('Diálogo guardado, creando nodo...');
                        // Después de guardar, crear el nodo
                        this.crearNodoDespuesDeGuardar(tipo);
                    }.bind(this)).catch(function(error) {
                        console.error('Error al guardar diálogo:', error);
                        alert('Error al guardar el diálogo. Por favor, completa el nombre del diálogo.');
                    });
                    return;
                }

                console.log('Creando nodo directamente...');
                this.crearNodoDespuesDeGuardar(tipo);
            },

            async crearNodoConRol(tipo, rolId, titulo, contenido) {
                // Reutilizar flujo existente pero asignando rol y textos
                await this.crearNodoDespuesDeGuardar(tipo, rolId, titulo, contenido);
            },

            crearNodoDespuesDeGuardar(tipo, rolId = null, titulo = '', contenido = '') {
                console.log('=== CREAR NODO DESPUES DE GUARDAR ===');
                console.log('Tipo:', tipo);
                console.log('Dialogo ID:', this.dialogoId);
                
                if (!this.dialogoId) {
                    console.error('No hay dialogoId, no se puede crear el nodo');
                    alert('Error: No se pudo obtener el ID del diálogo. Por favor, recarga la página.');
                    return;
                }
                console.log('Nodos actuales:', this.nodos.length);
                
                const nuevoNodo = {
                    id: 'temp-' + Date.now(),
                    dialogo_id: this.dialogoId,
                    tipo: tipo,
                    titulo: titulo || ('Nodo ' + tipo),
                    contenido: contenido || '',
                    menu_text: '',
                    rol_id: rolId || null,
                    conversant_id: null,
                    posicion_x: 100 + (this.nodos.length * 200),
                    posicion_y: 100 + (this.nodos.length * 150),
                    es_inicial: tipo === 'inicio',
                    es_final: tipo === 'final',
                    instrucciones: '',
                    activo: true,
                    condiciones: [],
                    consecuencias: [],
                    respuestas: []
                };

                console.log('Nuevo nodo creado:', nuevoNodo);
                this.nodos.push(nuevoNodo);
                console.log('Nodo agregado a la lista. Total nodos:', this.nodos.length);
                
                this.seleccionarNodo(nuevoNodo);
                
                // Inicializar jsPlumb para el nuevo nodo después de que se renderice
                this.$nextTick(function() {
                    const element = document.getElementById('node-' + nuevoNodo.id);
                    console.log('Elemento del nodo encontrado:', element);
                    if (element && this.jsPlumbInstance) {
                        try {
                            this.jsPlumbInstance.draggable('node-' + nuevoNodo.id, {
                                // En jsPlumb 2.x el callback correcto es "drag"
                                drag: function(params) {
                                    const index = this.nodos.findIndex(function(n) { return n.id === nuevoNodo.id; });
                                    if (index !== -1) {
                                        this.nodos[index].posicion_x = params.pos[0];
                                        this.nodos[index].posicion_y = params.pos[1];
                                    }
                                }.bind(this),
                                stop: function() {
                                    this.programarGuardadoPosiciones();
                                }.bind(this)
                            });
                            console.log('Nodo hecho arrastrable');

                        // Reconfigurar endpoints y conexiones tras nuevo nodo
                        this.configurarEndpoints();
                        this.renderizarConexiones();
                        } catch (error) {
                            console.error('Error al hacer nodo arrastrable:', error);
                        }
                    } else {
                        console.warn('No se pudo hacer el nodo arrastrable:', {
                            element: !!element,
                            jsPlumbInstance: !!this.jsPlumbInstance
                        });
                    }
                }.bind(this));
            },

            programarGuardadoPosiciones() {
                if (!this.dialogoId) return;
                if (this.guardarPosicionesTimer) {
                    clearTimeout(this.guardarPosicionesTimer);
                }
                this.guardarPosicionesTimer = setTimeout(() => {
                    this.guardarPosicionesTimer = null;
                    this.guardarPosiciones();
                }, 500);
            },

            async guardarPosiciones() {
                if (!this.dialogoId) return;
                const payload = {
                    nodos: this.nodos
                        .filter(n => n.id && !n.id.toString().startsWith('temp-'))
                        .map(n => ({
                            id: n.id,
                            posicion_x: parseInt(n.posicion_x) || 0,
                            posicion_y: parseInt(n.posicion_y) || 0,
                        }))
                };
                if (!payload.nodos.length) return;
                try {
                    const resp = await fetch(`/api/dialogos-v2/${this.dialogoId}/posiciones`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload)
                    });
                    const data = await resp.json().catch(() => ({}));
                    if (!resp.ok || !data.success) {
                        console.warn('No se pudieron guardar posiciones', data);
                    } else {
                        console.log('Posiciones guardadas');
                    }
                } catch (e) {
                    console.warn('Error guardando posiciones', e);
                }
            },

            async guardarNodo() {
                console.log('=== GUARDAR NODO ===');
                console.log('Nodo seleccionado:', this.nodoSeleccionado);
                console.log('Dialogo ID:', this.dialogoId);
                
                if (!this.nodoSeleccionado) {
                    console.error('No hay nodo seleccionado');
                    return;
                }

                this.guardando = true;
                try {
                    const url = this.nodoSeleccionado.id.toString().startsWith('temp-')
                        ? '/api/dialogos-v2/' + this.dialogoId + '/nodos'
                        : '/api/dialogos-v2/' + this.dialogoId + '/nodos/' + this.nodoSeleccionado.id;

                    console.log('URL:', url);
                    console.log('Method:', this.nodoSeleccionado.id.toString().startsWith('temp-') ? 'POST' : 'PUT');

                    const response = await fetch(url, {
                        method: this.nodoSeleccionado.id.toString().startsWith('temp-') ? 'POST' : 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            id: this.nodoSeleccionado.id,
                            tipo: this.nodoSeleccionado.tipo,
                            titulo: this.nodoSeleccionado.titulo || '',
                            contenido: this.nodoSeleccionado.contenido || '',
                            menu_text: this.nodoSeleccionado.menu_text || '',
                            rol_id: this.nodoSeleccionado.rol_id || null,
                            conversant_id: this.nodoSeleccionado.conversant_id || null,
                            posicion_x: parseInt(this.nodoSeleccionado.posicion_x) || 100,
                            posicion_y: parseInt(this.nodoSeleccionado.posicion_y) || 100,
                            es_inicial: this.nodoSeleccionado.es_inicial || false,
                            es_final: this.nodoSeleccionado.es_final || false,
                            instrucciones: this.nodoSeleccionado.instrucciones || '',
                            activo: this.nodoSeleccionado.activo !== undefined ? this.nodoSeleccionado.activo : true,
                            condiciones: this.nodoSeleccionado.condiciones || [],
                            consecuencias: this.nodoSeleccionado.consecuencias || []
                        })
                    });

                    console.log('Respuesta recibida:', response.status, response.statusText);
                    
                    // Verificar si la respuesta es JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('Respuesta no es JSON:', text.substring(0, 500));
                        throw new Error('El servidor devolvió una respuesta no válida. Verifica la consola para más detalles.');
                    }

                    const data = await response.json();
                    console.log('Datos recibidos:', data);
                    
                    if (data.success) {
                        // Actualizar el ID del nodo si era temporal
                        const oldId = this.nodoSeleccionado.id;
                        const nuevoNodo = data.data.nodo;
                        const nuevoNodoId = nuevoNodo.id;
                        
                        // Actualizar el nodo en la lista
                        const index = this.nodos.findIndex(function(n) { return n.id === oldId; }.bind(this));
                        if (index !== -1) {
                            this.nodos[index] = nuevoNodo;
                            this.seleccionarNodo(nuevoNodo);
                        }
                        
                        // Guardar todas las respuestas del nodo
                        if (this.nodoSeleccionado.respuestas && this.nodoSeleccionado.respuestas.length > 0) {
                            console.log('Guardando respuestas del nodo...');
                            await this.guardarRespuestasDelNodo(nuevoNodoId);
                        }
                        
                        this.renderizarConexiones();
                    }
                } catch (error) {
                    console.error('Error al guardar nodo:', error);
                    const errorMessage = error.message || 'Error desconocido al guardar el nodo';
                    alert('Error al guardar el nodo: ' + errorMessage);
                } finally {
                    this.guardando = false;
                }
            },

            async eliminarNodo() {
                if (!this.nodoSeleccionado || !confirm('¿Estás seguro de eliminar este nodo?')) return;

                if (this.nodoSeleccionado.id.toString().startsWith('temp-')) {
                    this.nodos = this.nodos.filter(function(n) { return n.id !== this.nodoSeleccionado.id; }.bind(this));
                    this.deseleccionarNodo();
                    return;
                }

                try {
                    const response = await fetch('/api/dialogos-v2/' + this.dialogoId + '/nodos/' + this.nodoSeleccionado.id, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.nodos = this.nodos.filter(function(n) { return n.id !== this.nodoSeleccionado.id; }.bind(this));
                        this.deseleccionarNodo();
                        this.renderizarConexiones();
                    }
                } catch (error) {
                    console.error('Error al eliminar nodo:', error);
                    alert('Error al eliminar el nodo');
                }
            },

            agregarRespuesta() {
                if (!this.nodoSeleccionado) return;
                if (!this.nodoSeleccionado.respuestas) {
                    this.nodoSeleccionado.respuestas = [];
                }
                this.nodoSeleccionado.respuestas.push({
                    id: 'temp-' + Date.now(),
                    nodo_padre_id: this.nodoSeleccionado.id,
                    nodo_siguiente_id: null,
                    texto: '',
                    orden: this.nodoSeleccionado.respuestas.length,
                    puntuacion: 0,
                    color: '#007bff',
                    requiere_usuario_registrado: false,
                    es_opcion_por_defecto: false,
                    requiere_rol: [],
                    condiciones: [],
                    consecuencias: []
                });
            },

            crearNodoDesdeSelector(respuesta) {
                if (!respuesta) return;
                
                // Mostrar modal para seleccionar tipo de nodo
                const tipoNodo = prompt('¿Qué tipo de nodo quieres crear?\n\n1. desarrollo (muestra contenido y continúa)\n2. decision (permite múltiples opciones)\n3. final (termina el diálogo)\n\nEscribe el número o el nombre:');
                
                if (!tipoNodo) {
                    respuesta.nodo_siguiente_id = null;
                    return;
                }
                
                let tipo = tipoNodo.toLowerCase().trim();
                if (tipoNodo === '1') tipo = 'desarrollo';
                else if (tipoNodo === '2') tipo = 'decision';
                else if (tipoNodo === '3') tipo = 'final';
                
                if (!['desarrollo', 'decision', 'final'].includes(tipo)) {
                    alert('Tipo de nodo inválido. Usa: desarrollo, decision o final');
                    respuesta.nodo_siguiente_id = null;
                    return;
                }
                
                // Guardar referencia a la respuesta
                const respuestaRef = respuesta;
                
                // Crear el nodo
                this.crearNodoDespuesDeGuardar(tipo);
                
                // Esperar a que se cree y conectarlo
                setTimeout(() => {
                    const nuevoNodo = this.nodos[this.nodos.length - 1];
                    if (nuevoNodo && respuestaRef) {
                        respuestaRef.nodo_siguiente_id = nuevoNodo.id;
                        console.log('Nodo creado y conectado:', nuevoNodo.id, 'a respuesta:', respuestaRef.texto);
                        // Forzar actualización de Alpine.js
                        this.$nextTick(() => {
                            this.renderizarConexiones();
                        });
                    }
                }, 100);
            },

            async guardarRespuestasDelNodo(nodoId) {
                if (!this.nodoSeleccionado || !this.nodoSeleccionado.respuestas) {
                    return;
                }
                
                console.log('Guardando ' + this.nodoSeleccionado.respuestas.length + ' respuestas para el nodo ' + nodoId);
                
                for (let i = 0; i < this.nodoSeleccionado.respuestas.length; i++) {
                    const respuesta = this.nodoSeleccionado.respuestas[i];
                    
                    // Actualizar el nodo_padre_id si el nodo era temporal
                    respuesta.nodo_padre_id = nodoId;
                    
                    try {
                        const url = respuesta.id.toString().startsWith('temp-')
                            ? '/api/dialogos-v2/' + this.dialogoId + '/nodos/' + nodoId + '/respuestas'
                            : '/api/dialogos-v2/' + this.dialogoId + '/nodos/' + nodoId + '/respuestas/' + respuesta.id;
                        
                        const response = await fetch(url, {
                            method: respuesta.id.toString().startsWith('temp-') ? 'POST' : 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                id: respuesta.id.toString().startsWith('temp-') ? null : respuesta.id,
                                nodo_siguiente_id: respuesta.nodo_siguiente_id || null,
                                texto: respuesta.texto || '',
                                orden: respuesta.orden !== undefined ? respuesta.orden : i,
                                puntuacion: respuesta.puntuacion || 0,
                                color: respuesta.color || '#007bff',
                                requiere_usuario_registrado: respuesta.requiere_usuario_registrado || false,
                                es_opcion_por_defecto: respuesta.es_opcion_por_defecto || false,
                                requiere_rol: respuesta.requiere_rol || [],
                                condiciones: respuesta.condiciones || [],
                                consecuencias: respuesta.consecuencias || []
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            // Actualizar el ID de la respuesta si era temporal
                            if (respuesta.id.toString().startsWith('temp-') && data.data && data.data.respuesta) {
                                respuesta.id = data.data.respuesta.id;
                            }
                            console.log('Respuesta guardada:', respuesta.texto);
                        } else {
                            console.error('Error al guardar respuesta:', data.message);
                        }
                    } catch (error) {
                        console.error('Error al guardar respuesta:', error);
                    }
                }
                
                // Recargar el nodo con sus respuestas actualizadas
                await this.cargarDialogo();
            },

            async eliminarRespuesta(respuesta) {
                if (!confirm('¿Estás seguro de eliminar esta respuesta?')) return;

                if (respuesta.id.toString().startsWith('temp-')) {
                    this.nodoSeleccionado.respuestas = this.nodoSeleccionado.respuestas.filter(function(r) { return r.id !== respuesta.id; });
                    return;
                }

                try {
                    const response = await fetch('/api/dialogos-v2/' + this.dialogoId + '/nodos/' + this.nodoSeleccionado.id + '/respuestas/' + respuesta.id, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.nodoSeleccionado.respuestas = this.nodoSeleccionado.respuestas.filter(function(r) { return r.id !== respuesta.id; });
                        this.renderizarConexiones();
                    }
                } catch (error) {
                    console.error('Error al eliminar respuesta:', error);
                    alert('Error al eliminar la respuesta');
                }
            },

            async guardar() {
                // Validar que tenga nombre
                console.log('Validando nombre:', this.dialogoData.nombre);
                if (!this.dialogoData.nombre || this.dialogoData.nombre.trim() === '') {
                    alert('Por favor, ingresa un nombre para el diálogo.');
                    return Promise.reject('Nombre requerido');
                }

                this.guardando = true;
                try {
                    const url = this.dialogoId
                        ? '/api/dialogos-v2/' + this.dialogoId + '/editor'
                        : '/api/dialogos-v2/editor';

                    const requestBody = {
                        id: this.dialogoId || null,
                        nombre: this.dialogoData.nombre.trim(),
                        descripcion: this.dialogoData.descripcion || '',
                        publico: this.dialogoData.publico || false,
                        estado: this.dialogoData.estado || 'borrador'
                    };

                    console.log('Enviando petición a:', url);
                    console.log('Datos a enviar:', requestBody);

                    const response = await fetch(url, {
                        method: this.dialogoId ? 'PUT' : 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(requestBody)
                    });

                    console.log('Respuesta recibida:', response.status, response.statusText);
                    
                    // Verificar si la respuesta es JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('Respuesta no es JSON:', text.substring(0, 500));
                        throw new Error('El servidor devolvió una respuesta no válida. Verifica la consola para más detalles.');
                    }

                    const data = await response.json();
                    console.log('Datos recibidos:', data);
                    
                    if (data.success) {
                        if (!this.dialogoId) {
                            this.dialogoId = data.data.dialogo.id;
                            window.history.pushState({}, '', '/dialogos-v2/' + this.dialogoId + '/editor');
                        }
                        return Promise.resolve();
                    } else {
                        throw new Error(data.message || 'Error al guardar');
                    }
                } catch (error) {
                    console.error('Error al guardar diálogo:', error);
                    const errorMessage = error.message || 'Error desconocido al guardar el diálogo';
                    alert('Error al guardar el diálogo: ' + errorMessage);
                    return Promise.reject(error);
                } finally {
                    this.guardando = false;
                }
            },

            async validar() {
                if (!this.dialogoId) {
                    alert('Primero debes guardar el diálogo');
                    return;
                }

                try {
                    const response = await fetch('/api/dialogos-v2/' + this.dialogoId + '/validar');
                    const data = await response.json();
                    if (data.success) {
                        this.validacionResultado = {
                            valido: data.data.valido || false,
                            errores: data.data.errores || [],
                            advertencias: data.data.advertencias || []
                        };
                        
                        // Mostrar el modal
                        const modal = new bootstrap.Modal(document.getElementById('modalValidacion'));
                        modal.show();
                    } else {
                        this.validacionResultado = {
                            valido: false,
                            errores: [data.message || 'Error al validar el diálogo'],
                            advertencias: []
                        };
                        const modal = new bootstrap.Modal(document.getElementById('modalValidacion'));
                        modal.show();
                    }
                } catch (error) {
                    console.error('Error al validar:', error);
                    this.validacionResultado = {
                        valido: false,
                        errores: ['Error al conectar con el servidor'],
                        advertencias: []
                    };
                    const modal = new bootstrap.Modal(document.getElementById('modalValidacion'));
                    modal.show();
                }
            },

            zoomIn() {
                this.zoom = Math.min(this.zoom + 0.1, 2.0);
                document.getElementById('editor-canvas').style.transform = 'scale(' + this.zoom + ')';
            },

            zoomOut() {
                this.zoom = Math.max(this.zoom - 0.1, 0.5);
                document.getElementById('editor-canvas').style.transform = 'scale(' + this.zoom + ')';
            },

            resetZoom() {
                this.zoom = 1.0;
                document.getElementById('editor-canvas').style.transform = 'scale(1.0)';
            },

            get totalRespuestas() {
                return this.nodos.reduce(function(total, nodo) {
                    return total + (nodo.respuestas ? nodo.respuestas.length : 0);
                }, 0);
            },

            get puedeActivar() {
                return this.dialogoId && this.nodos.length > 0;
            }
        };
    };
})();
</script>
@endpush

@push('styles')
<style>
    .editor-container {
        height: calc(100vh - 200px);
        min-height: 700px;
        position: relative !important;
        overflow: auto;
        background: #2d2d2d;
        background-image: 
            linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
        background-size: 20px 20px;
        background-position: 0 0, 0 0;
        border-radius: 8px;
        padding: 40px;
        width: 100%;
    }

    /* Mensaje cuando no hay nodos */
    .editor-container:empty::after {
        content: 'Crea tu primer nodo usando los botones del panel izquierdo';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: rgba(255,255,255,0.7);
        font-size: 18px;
        font-weight: 500;
        text-align: center;
        pointer-events: none;
    }

    .node {
        position: absolute !important;
        width: 220px;
        min-height: 100px;
        background: #ffffff !important;
        border: 2px solid #666;
        border-radius: 8px;
        padding: 0;
        cursor: move;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3), 0 2px 4px rgba(0,0,0,0.2);
        z-index: 10 !important;
        transition: all 0.2s ease;
        user-select: none;
        overflow: hidden;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    .node:hover {
        transform: translateY(-4px) scale(1.01);
        box-shadow: 0 12px 32px rgba(0,0,0,0.16), 0 4px 8px rgba(0,0,0,0.12);
        border-color: #b0b0b0;
    }

    .node.selected {
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0,123,255,0.2), 0 12px 32px rgba(0,123,255,0.25), 0 4px 8px rgba(0,0,0,0.12);
        transform: scale(1.03);
    }

    /* Header del nodo con icono */
    .node-header {
        font-weight: 600;
        margin: 0;
        font-size: 14px;
        color: white;
        padding: 12px 14px;
        background: var(--node-color, #555);
        border-bottom: 1px solid rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        gap: 8px;
        position: relative;
    }

    .node-header::before {
        content: '';
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }

    /* Iconos por tipo de nodo en el header */
    .node.inicial .node-header::before {
        content: '▶';
        width: auto;
        height: auto;
        background: transparent;
        font-size: 12px;
        color: white;
    }

    .node.final .node-header::before {
        content: '■';
        width: auto;
        height: auto;
        background: transparent;
        font-size: 12px;
        color: white;
    }

    .node.decision .node-header::before {
        content: '◊';
        width: auto;
        height: auto;
        background: transparent;
        font-size: 14px;
        color: white;
        font-weight: bold;
    }

    .node:not(.inicial):not(.final):not(.decision) .node-header::before {
        content: '●';
        width: auto;
        height: auto;
        background: transparent;
        font-size: 10px;
        color: white;
    }

    .node-content {
        font-size: 13px;
        color: #111;
        line-height: 1.5;
        /* Se reduce la sangría lateral para que el texto no arranque tan adentro */
        padding: 10px 10px;
        min-height: 50px;
        max-height: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        word-wrap: break-word;
        /* Evitar que la primera línea se indente por espacios guardados */
        white-space: pre-line;
    }

    .node-content:empty::before {
        content: '(Sin contenido)';
        color: #666;
        font-style: italic;
    }

    .node-footer {
        padding: 8px 14px;
        background: rgba(0,0,0,0.15);
        border-top: 1px solid rgba(255,255,255,0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .node-type {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 3px 8px;
        border-radius: 4px;
        display: inline-block;
    }

    .node-responses-count {
        background: rgba(0,123,255,0.1);
        color: #007bff;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .node-responses-count::before {
        content: '🔗';
        font-size: 10px;
    }

    /* Colores por tipo (fallback si no hay rol) */
    .node.inicial { border-color: #ff8c00; }
    .node.final { border-color: #666; }
    .node.decision { border-color: #4169E1; }
    .node:not(.inicial):not(.final):not(.decision) { border-color: #666; }

    .jtk-connector {
        z-index: 5;
    }

    .jtk-endpoint {
        z-index: 15;
    }

    .properties-panel {
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4" x-data="dialogoEditorV2()" x-init="init()" x-cloak>
    <!-- Header del Editor -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="bi bi-diagram-3 me-2 text-primary"></i>
                        <span x-text="dialogo ? 'Editando: ' + dialogo.nombre : 'Nuevo Diálogo v2'"></span>
                    </h1>
                    <p class="text-muted mb-0">
                        <span x-text="dialogo ? dialogo.descripcion || 'Sin descripción' : 'Crea un diálogo ramificado para simulacros de juicios'"></span>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" @click="guardar()" :disabled="guardando">
                        <i class="bi bi-save me-2"></i>
                        <span x-text="guardando ? 'Guardando...' : 'Guardar'"></span>
                    </button>
                    <button class="btn btn-outline-info" @click="validar()">
                        <i class="bi bi-check-circle me-2"></i>
                        Validar
                    </button>
                    <button class="btn btn-primary" @click="activar()" :disabled="!puedeActivar">
                        <i class="bi bi-play-circle me-2"></i>
                        Activar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra superior con datos y creación -->
    <div class="row g-2 align-items-end mb-3">
        <div class="col-lg-3">
            <label class="form-label fw-medium mb-1">Título</label>
            <input type="text" class="form-control form-control-sm" x-model="dialogoData.nombre" placeholder="Nombre del diálogo">
        </div>
        <div class="col-lg-4">
            <label class="form-label fw-medium mb-1">Descripción</label>
            <input type="text" class="form-control form-control-sm" x-model="dialogoData.descripcion" placeholder="Descripción breve">
        </div>
        <div class="col-lg-2">
            <label class="form-label fw-medium mb-1">Estado</label>
            <select class="form-select form-select-sm" x-model="dialogoData.estado">
                <option value="borrador">Borrador</option>
                <option value="activo">Activo</option>
                <option value="archivado">Archivado</option>
            </select>
        </div>
        <div class="col-lg-1 d-flex align-items-center">
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" x-model="dialogoData.publico" id="chkPublico">
                <label class="form-check-label small" for="chkPublico">Público</label>
            </div>
        </div>
        <div class="col-lg-2 text-end">
            <button class="btn btn-primary w-100" @click="abrirModalNodo()">
                <i class="bi bi-plus-circle me-1"></i> Agregar nodo
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Panel Central - Canvas del Editor -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Canvas del Editor</h6>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomIn()">
                                <i class="bi bi-zoom-in"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomOut()">
                                <i class="bi bi-zoom-out"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="resetZoom()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="editor-container" id="editor-canvas" @click.self="deseleccionarNodo()">
                        <!-- Mensaje cuando no hay nodos -->
                        <template x-if="nodos.length === 0">
                            <div class="text-center text-white-50 py-5" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none; z-index: 1;">
                                <i class="bi bi-diagram-3" style="font-size: 4rem; opacity: 0.4;"></i>
                                <p class="mt-3 mb-0" style="font-size: 1.1rem; font-weight: 500;">Crea tu primer nodo usando los botones del panel izquierdo</p>
                                <small class="d-block mt-2" style="opacity: 0.7;">Empieza con un nodo "Inicio"</small>
                            </div>
                        </template>
                        <!-- Los nodos se renderizarán aquí dinámicamente -->
                        <template x-for="nodo in nodos" :key="nodo.id">
                            <div 
                                :id="'node-' + nodo.id"
                                class="node"
                                :class="{
                                    'selected': nodoSeleccionado && nodoSeleccionado.id === nodo.id,
                                    'inicial': nodo.tipo === 'inicio' || nodo.es_inicial,
                                    'final': nodo.tipo === 'final' || nodo.es_final,
                        'decision': nodo.tipo === 'decision',
                        'respuesta': nodo.tipo === 'respuesta'
                                }"
                    :style="'position: absolute !important; left: ' + (nodo.posicion_x || 100) + 'px !important; top: ' + (nodo.posicion_y || 100) + 'px !important; z-index: 10 !important; --node-color:' + colorNodo(nodo)"
                                @click.stop="seleccionarNodo(nodo)"
                                @dblclick.stop="editarNodo(nodo)"
                            >
                                <div class="node-header">
                                    <div class="d-flex flex-column">
                                        <span class="small fw-semibold" x-text="'ID: ' + nodo.id"></span>
                                        <span class="small fw-semibold" x-text="rolNombre(nodo.rol_id) || 'Sin rol'"></span>
                                        <span x-text="nodo.titulo || 'Nodo ' + nodo.id"></span>
                                    </div>
                                </div>
                                <div class="node-content">
                                    <span x-text="nodo.contenido || '(Sin contenido)'"></span>
                                </div>
                                <div class="node-footer">
                                    <span class="node-type" x-text="nodo.tipo.toUpperCase()"></span>
                                    <template x-if="nodo.respuestas && nodo.respuestas.length > 0">
                                        <span class="node-responses-count" x-text="nodo.respuestas.length"></span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Derecho - Propiedades -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-sliders me-2 text-primary"></i>
                        Propiedades
                    </h5>
                </div>
                <div class="card-body properties-panel">
                    <template x-if="!nodoSeleccionado">
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-info-circle fs-1 d-block mb-3"></i>
                            <p>Selecciona un nodo para editar sus propiedades</p>
                        </div>
                    </template>

                    <template x-if="nodoSeleccionado">
                        <div>
                            <h6 class="fw-semibold text-dark mb-3">Propiedades del Nodo</h6>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Título</label>
                                <input type="text" class="form-control" x-model="nodoSeleccionado.titulo">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Contenido</label>
                                <textarea class="form-control" rows="4" x-model="nodoSeleccionado.contenido"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Tipo</label>
                                <select class="form-select" x-model="nodoSeleccionado.tipo">
                                    <option value="inicio">Inicio</option>
                                    <option value="desarrollo">Desarrollo</option>
                                    <option value="decision">Decisión</option>
                                    <option value="final">Final</option>
                                    <option value="agrupacion">Agrupación</option>
                        <option value="respuesta">Respuesta</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Menu Text</label>
                                <input type="text" class="form-control" x-model="nodoSeleccionado.menu_text">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="bi bi-person-badge me-1"></i>
                                    Rol
                                </label>
                                <select class="form-select" x-model.number="nodoSeleccionado.rol_id">
                                    <option value="">-- Sin rol asignado --</option>
                                    <template x-for="rol in rolesDisponibles" :key="rol.id">
                                        <option :value="rol.id" x-text="rol.nombre + (rol.descripcion ? ' - ' + rol.descripcion : '')"></option>
                                    </template>
                                </select>
                                <small class="text-muted" x-show="nodoSeleccionado.rol_id">
                                    <template x-if="rolesDisponibles.find(r => r.id === nodoSeleccionado.rol_id)">
                                        <span x-text="'Rol seleccionado: ' + rolesDisponibles.find(r => r.id === nodoSeleccionado.rol_id).nombre"></span>
                                    </template>
                                </small>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" x-model="nodoSeleccionado.es_inicial">
                                    <label class="form-check-label">Es Inicial</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" x-model="nodoSeleccionado.es_final">
                                    <label class="form-check-label">Es Final</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" x-model="nodoSeleccionado.activo">
                                    <label class="form-check-label">Activo</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Instrucciones</label>
                                <textarea class="form-control" rows="2" x-model="nodoSeleccionado.instrucciones"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" @click="guardarNodo()">
                                    <i class="bi bi-save me-2"></i>Guardar Nodo
                                </button>
                                <button class="btn btn-danger" @click="eliminarNodo()">
                                    <i class="bi bi-trash me-2"></i>Eliminar Nodo
                                </button>
                            </div>

                            <!-- Respuestas del Nodo -->
                            <div class="mt-4 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-semibold text-dark mb-0">
                                        <i class="bi bi-diagram-2 me-2" :class="{'text-warning': nodoSeleccionado.tipo === 'decision'}"></i>
                                        <span x-show="nodoSeleccionado.tipo === 'decision'">Opciones de Decisión</span>
                                        <span x-show="nodoSeleccionado.tipo !== 'decision'">Respuestas</span>
                                    </h6>
                                    <span class="badge bg-primary" x-text="(nodoSeleccionado.respuestas || []).length"></span>
                                </div>
                                
                                <div class="alert alert-info alert-sm py-2 mb-3" x-show="nodoSeleccionado.tipo === 'decision'">
                                    <small><i class="bi bi-info-circle me-1"></i>Los nodos de decisión requieren al menos 2 opciones que conecten a otros nodos.</small>
                                </div>
                                
                                <template x-if="!nodoSeleccionado.respuestas || nodoSeleccionado.respuestas.length === 0">
                                    <div class="text-center text-muted py-3 border rounded mb-3">
                                        <i class="bi bi-inbox d-block mb-2" style="font-size: 2rem;"></i>
                                        <small>No hay respuestas configuradas</small>
                                    </div>
                                </template>
                                
                                <template x-for="(respuesta, index) in nodoSeleccionado.respuestas || []" :key="respuesta.id">
                                    <div class="card mb-2 border-start border-3" 
                                         :class="{'border-warning': nodoSeleccionado.tipo === 'decision'}">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <span class="badge bg-secondary">Opción <span x-text="index + 1"></span></span>
                                                <button class="btn btn-sm btn-outline-danger" @click="eliminarRespuesta(respuesta)" title="Eliminar respuesta">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-medium">Texto de la opción:</label>
                                                <input type="text" class="form-control form-control-sm" 
                                                       x-model="respuesta.texto" 
                                                       placeholder="Ej: Aceptar, Rechazar, Continuar...">
                                            </div>
                                            <div>
                                                <label class="form-label small fw-medium">Conectar a nodo:</label>
                                                <div class="input-group input-group-sm">
                                                    <select class="form-select" x-model="respuesta.nodo_siguiente_id" 
                                                            @change="if (respuesta.nodo_siguiente_id === '__crear_nuevo__') { respuesta.nodo_siguiente_id = null; crearNodoDesdeSelector(respuesta); }">
                                                        <option value="">-- Seleccionar nodo destino --</option>
                                                        <template x-for="nodo in nodos" :key="nodo.id">
                                                            <option :value="nodo.id" 
                                                                    :disabled="nodo.id === nodoSeleccionado.id"
                                                                    x-text="(nodo.titulo || 'Nodo ' + nodo.id) + ' (' + nodo.tipo + ')'"></option>
                                                        </template>
                                                        <option value="__crear_nuevo__">➕ Crear nuevo nodo...</option>
                                                    </select>
                                                    <button class="btn btn-outline-success" type="button" 
                                                            @click="crearNodoDesdeSelector(respuesta)"
                                                            title="Crear un nuevo nodo y conectarlo automáticamente">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted d-block mt-1" x-show="respuesta.nodo_siguiente_id && respuesta.nodo_siguiente_id !== '__crear_nuevo__'">
                                                    <i class="bi bi-link-45deg me-1"></i>
                                                    Conectado a: <strong x-text="nodos.find(n => n.id === respuesta.nodo_siguiente_id)?.titulo || 'Nodo ' + respuesta.nodo_siguiente_id"></strong>
                                                </small>
                                                <div class="alert alert-warning alert-sm py-2 mt-2" x-show="respuesta.nodo_siguiente_id === '__crear_nuevo__'">
                                                    <small><i class="bi bi-exclamation-triangle me-1"></i>Selecciona el tipo de nodo a crear o usa el botón ➕</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <button class="btn btn-sm w-100" 
                                        :class="{'btn-warning': nodoSeleccionado.tipo === 'decision', 'btn-outline-primary': nodoSeleccionado.tipo !== 'decision'}"
                                        @click="agregarRespuesta()">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    <span x-show="nodoSeleccionado.tipo === 'decision'">Agregar Opción</span>
                                    <span x-show="nodoSeleccionado.tipo !== 'decision'">Agregar Respuesta</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Nodo -->
    <div class="modal fade" tabindex="-1" :class="{'show d-block': showModalNodo}" style="background: rgba(0,0,0,0.5);" @click.self="cerrarModalNodo">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar nodo</h5>
                    <button type="button" class="btn-close" @click="cerrarModalNodo"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-medium">Tipo</label>
                            <select class="form-select" x-model="nuevoNodoFormulario.tipo">
                                <option value="inicio">Inicio</option>
                                <option value="desarrollo">Desarrollo</option>
                                <option value="decision">Decisión</option>
                                <option value="final">Final</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Rol</label>
                            <select class="form-select" x-model="nuevoNodoFormulario.rol_id">
                                <option value="" disabled>Selecciona rol</option>
                                <template x-for="rol in rolesDisponibles" :key="rol.id">
                                    <option :value="rol.id" x-text="rol.nombre"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Título</label>
                            <input type="text" class="form-control" x-model="nuevoNodoFormulario.titulo" placeholder="Título del nodo">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Texto / diálogo</label>
                            <textarea class="form-control" rows="3" x-model="nuevoNodoFormulario.contenido" placeholder="Contenido del nodo"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" @click="cerrarModalNodo()">Cancelar</button>
                    <button class="btn btn-primary" @click="confirmarNuevoNodo()">Agregar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Validación (debe estar dentro del scope de Alpine.js) -->
    @include('dialogos.v2.modals.validacion')
</div>

@endsection
