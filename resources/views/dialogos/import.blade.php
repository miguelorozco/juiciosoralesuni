@extends('layouts.app')

@section('title', 'Importar Diálogos - Simulador de Juicios Orales')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold text-dark mb-1">
                        <i class="bi bi-upload me-2 text-primary"></i>
                        Importar Diálogos
                    </h2>
                    <p class="text-muted mb-0">Importa diálogos completos desde archivos JSON</p>
                </div>
                <div>
                    <a href="/dialogos" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>
                        Volver a Diálogos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Panel de Importación -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark-code me-2"></i>
                        Importar desde JSON
                    </h5>
                </div>
                <div class="card-body">
                    <form id="importForm" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- Selector de archivo -->
                        <div class="mb-4">
                            <label for="jsonFile" class="form-label fw-medium">
                                <i class="bi bi-file-earmark-arrow-up me-2"></i>
                                Seleccionar archivo JSON
                            </label>
                            <input type="file" 
                                   class="form-control" 
                                   id="jsonFile" 
                                   name="jsonFile" 
                                   accept=".json"
                                   required>
                            <div class="form-text">
                                Selecciona un archivo JSON válido con la estructura de diálogo
                            </div>
                        </div>

                        <!-- Vista previa del JSON -->
                        <div class="mb-4" id="previewSection" style="display: none;">
                            <label class="form-label fw-medium">
                                <i class="bi bi-eye me-2"></i>
                                Vista previa del JSON
                            </label>
                            <div class="border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
                                <pre id="jsonPreview" class="mb-0"></pre>
                            </div>
                        </div>

                        <!-- Opciones de importación -->
                        <div class="mb-4">
                            <label class="form-label fw-medium">
                                <i class="bi bi-gear me-2"></i>
                                Opciones de importación
                            </label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="crearRoles" checked>
                                        <label class="form-check-label" for="crearRoles">
                                            Crear roles automáticamente si no existen
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="validarEstructura" checked>
                                        <label class="form-check-label" for="validarEstructura">
                                            Validar estructura antes de importar
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="importBtn">
                                <i class="bi bi-upload me-2"></i>
                                Importar Diálogo
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="clearBtn">
                                <i class="bi bi-x-circle me-2"></i>
                                Limpiar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panel de Ayuda -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-question-circle me-2"></i>
                        Ayuda
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">Formato JSON Requerido:</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check-circle text-success me-2"></i>Estructura de diálogo</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Array de nodos</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Array de conexiones</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Posiciones en grid</li>
                    </ul>

                    <h6 class="fw-bold mt-3">Ejemplo Básico:</h6>
                    <div class="bg-light p-2 rounded small">
                        <pre class="mb-0">{
  "dialogo": {
    "nombre": "Mi Diálogo",
    "descripcion": "Descripción..."
  },
  "nodos": [...],
  "conexiones": [...]
}</pre>
                    </div>

                    <div class="mt-3">
                        <a href="/docs/dialogo-json-format" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-book me-2"></i>
                            Ver documentación completa
                        </a>
                    </div>
                </div>
            </div>

            <!-- Plantillas de ejemplo -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark-code me-2"></i>
                        Plantillas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-success btn-sm" onclick="cargarPlantilla('basico')">
                            <i class="bi bi-play-circle me-2"></i>
                            Diálogo Básico
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="cargarPlantilla('complejo')">
                            <i class="bi bi-diagram-3 me-2"></i>
                            Diálogo Complejo
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="cargarPlantilla('juicio')">
                            <i class="bi bi-gavel me-2"></i>
                            Simulación de Juicio
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Resultado -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resultModalTitle">Resultado de Importación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="resultModalBody">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="viewDialogoBtn" style="display: none;">
                    <i class="bi bi-eye me-2"></i>
                    Ver Diálogo
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('jsonFile');
    const previewSection = document.getElementById('previewSection');
    const jsonPreview = document.getElementById('jsonPreview');
    const importForm = document.getElementById('importForm');
    const importBtn = document.getElementById('importBtn');
    const clearBtn = document.getElementById('clearBtn');
    let ultimoJsonValido = null; // cache del JSON ya validado

    function obtenerBearerToken() {
        // Fallbacks por si el token se guardó con distinta clave
        return (
            localStorage.getItem('jwt_token') ||
            localStorage.getItem('token') ||
            localStorage.getItem('access_token') ||
            ''
        );
    }

    // Función mejorada de validación JSON
    function validarJSON(jsonString) {
        const errores = [];
        
        try {
            // Validar sintaxis básica
            const jsonData = JSON.parse(jsonString);
            
            // Validar estructura requerida
            if (!jsonData.dialogo) {
                errores.push('❌ Falta la sección "dialogo"');
            } else {
                if (!jsonData.dialogo.nombre) {
                    errores.push('❌ Falta "nombre" en la sección diálogo');
                }
                if (!jsonData.dialogo.descripcion) {
                    errores.push('❌ Falta "descripcion" en la sección diálogo');
                }
            }
            
            if (!jsonData.nodos || !Array.isArray(jsonData.nodos)) {
                errores.push('❌ Falta la sección "nodos" o no es un array');
            } else {
                // Validar nodos
                jsonData.nodos.forEach((nodo, index) => {
                    if (!nodo.id) {
                        errores.push(`❌ Nodo ${index + 1}: Falta "id"`);
                    }
                    if (!nodo.contenido) {
                        errores.push(`❌ Nodo ${index + 1}: Falta "contenido"`);
                    }
                    if (!nodo.tipo) {
                        errores.push(`❌ Nodo ${index + 1}: Falta "tipo"`);
                    }
                    // Validación de coordenadas: permitir 0 como valor válido y exigir números finitos
                    const tienePosicion = nodo.posicion && typeof nodo.posicion === 'object';
                    const x = tienePosicion ? nodo.posicion.x : undefined;
                    const y = tienePosicion ? nodo.posicion.y : undefined;
                    const xValida = Number.isFinite(Number(x));
                    const yValida = Number.isFinite(Number(y));
                    if (!tienePosicion || !xValida || !yValida) {
                        errores.push(`❌ Nodo ${index + 1}: Falta "posicion" o coordenadas (x,y numéricos)`);
                    }
                });
            }
            
            if (!jsonData.conexiones || !Array.isArray(jsonData.conexiones)) {
                errores.push('❌ Falta la sección "conexiones" o no es un array');
            } else {
                // Validar conexiones
                jsonData.conexiones.forEach((conexion, index) => {
                    if (!conexion.desde) {
                        errores.push(`❌ Conexión ${index + 1}: Falta "desde"`);
                    }
                    if (!conexion.hacia) {
                        errores.push(`❌ Conexión ${index + 1}: Falta "hacia"`);
                    }
                    if (!conexion.texto) {
                        errores.push(`❌ Conexión ${index + 1}: Falta "texto"`);
                    }
                });
            }
            
            return { valido: errores.length === 0, errores, data: jsonData };
            
        } catch (error) {
            // Detectar errores específicos de sintaxis
            let mensajeError = error.message;
            
            if (error.message.includes('Unexpected end of JSON input')) {
                mensajeError = '❌ JSON incompleto: Falta cerrar llaves, corchetes o comillas';
            } else if (error.message.includes('Unexpected token')) {
                mensajeError = '❌ Token inesperado: Verifica comillas, comas y llaves';
            } else if (error.message.includes('Expected property name')) {
                mensajeError = '❌ Nombre de propiedad esperado: Verifica las comillas en las claves';
            } else if (error.message.includes('Expected double-quoted property name')) {
                mensajeError = '❌ Las claves deben estar entre comillas dobles';
            }
            
            return { valido: false, errores: [mensajeError], data: null };
        }
    }

    // Manejar selección de archivo
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const validacion = validarJSON(e.target.result);
                
                if (validacion.valido) {
                    ultimoJsonValido = validacion.data;
                    jsonPreview.textContent = JSON.stringify(validacion.data, null, 2);
                    previewSection.style.display = 'block';
                    
                    // Mostrar mensaje de éxito
                    const successMsg = document.createElement('div');
                    successMsg.className = 'alert alert-success mt-3';
                    successMsg.innerHTML = '<i class="bi bi-check-circle me-2"></i>JSON válido y listo para importar';
                    previewSection.appendChild(successMsg);
                } else {
                    ultimoJsonValido = null;
                    // Mostrar errores detallados
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'alert alert-danger mt-3';
                    errorMsg.innerHTML = '<h6><i class="bi bi-exclamation-triangle me-2"></i>Errores encontrados:</h6><ul>' + 
                        validacion.errores.map(error => `<li>${error}</li>`).join('') + '</ul>';
                    previewSection.appendChild(errorMsg);
                    
                    // Mostrar JSON con errores resaltados
                    jsonPreview.textContent = e.target.result;
                    jsonPreview.style.color = '#dc3545';
                }
                
                previewSection.style.display = 'block';
            };
            reader.readAsText(file);
        }
    });

    // Manejar envío del formulario
    importForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Si tenemos un JSON ya validado, lo enviamos como application/json (API espera JSON)
        const usarJsonDirecto = !!ultimoJsonValido;
        const file = fileInput.files[0];
        if (!usarJsonDirecto && !file) {
            alert('Por favor selecciona un archivo JSON');
            return;
        }

        const formData = new FormData();
        if (!usarJsonDirecto) {
            formData.append('jsonFile', file);
            formData.append('crearRoles', document.getElementById('crearRoles').checked);
            formData.append('validarEstructura', document.getElementById('validarEstructura').checked);
        }

        importBtn.disabled = true;
        importBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Importando...';

        try {
            const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
            const bearer = obtenerBearerToken();
            if (bearer) headers['Authorization'] = `Bearer ${bearer}`;

            const requestInit = usarJsonDirecto ? {
                method: 'POST',
                headers: { ...headers, 'Content-Type': 'application/json' },
                body: JSON.stringify(ultimoJsonValido)
            } : {
                method: 'POST',
                headers,
                body: formData
            };

            // Log detallado del request
            console.log('=== ENVIANDO REQUEST DE IMPORTACIÓN ===');
            console.log('URL:', '/api/dialogos/import');
            console.log('Method:', requestInit.method);
            console.log('Headers:', requestInit.headers);
            console.log('Usar JSON directo:', usarJsonDirecto);
            if (usarJsonDirecto) {
                console.log('JSON enviado:', ultimoJsonValido);
                console.log('Tamaño del JSON:', JSON.stringify(ultimoJsonValido).length, 'caracteres');
            } else {
                console.log('FormData keys:', Array.from(formData.keys()));
            }

            const response = await fetch('/api/dialogos/import', requestInit);
            
            // Log de la respuesta
            console.log('=== RESPUESTA RECIBIDA ===');
            console.log('Status:', response.status);
            console.log('Status Text:', response.statusText);
            console.log('Headers:', Object.fromEntries(response.headers.entries()));

            // Si la API devuelve 401/419/403, mostrar mensaje y no navegar
            if (response.status === 401 || response.status === 419) {
                mostrarResultado('error', 'Sesión expirada o no autenticada', { 
                    message: 'Inicia sesión nuevamente para importar el diálogo.',
                    errors: { auth: ['Token ausente o expirado'] }
                });
                return;
            }
            if (response.status === 403) {
                mostrarResultado('error', 'Permisos insuficientes', { 
                    message: 'Tu usuario no tiene permisos para importar diálogos (se requiere admin/instructor).'
                });
                return;
            }

            const result = await response.json();
            
            // Log del resultado
            console.log('=== RESULTADO DE LA IMPORTACIÓN ===');
            console.log('Success:', result.success);
            console.log('Message:', result.message);
            console.log('Data:', result.data);
            if (result.errors) {
                console.log('Errors:', result.errors);
            }
            
            if (result.success) {
                console.log('✅ Importación exitosa');
                mostrarResultado('success', 'Diálogo importado exitosamente', result);
            } else {
                console.log('❌ Importación falló');
                mostrarResultado('error', 'Error al importar diálogo', result);
            }
        } catch (error) {
            mostrarResultado('error', 'Error de conexión', { message: error.message });
        } finally {
            importBtn.disabled = false;
            importBtn.innerHTML = '<i class="bi bi-upload me-2"></i>Importar Diálogo';
        }
    });

    // Limpiar formulario
    clearBtn.addEventListener('click', function() {
        fileInput.value = '';
        previewSection.style.display = 'none';
        importForm.reset();
    });

    function mostrarResultado(tipo, titulo, data) {
        const modal = new bootstrap.Modal(document.getElementById('resultModal'));
        const title = document.getElementById('resultModalTitle');
        const body = document.getElementById('resultModalBody');
        const viewBtn = document.getElementById('viewDialogoBtn');

        title.textContent = titulo;
        title.className = `modal-title text-${tipo === 'success' ? 'success' : 'danger'}`;

        if (tipo === 'success') {
            body.innerHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    ${data.message}
                </div>
                <div class="row">
                    <div class="col-6">
                        <strong>Nodos creados:</strong> ${data.data.nodos_creados}
                    </div>
                    <div class="col-6">
                        <strong>Conexiones creadas:</strong> ${data.data.conexiones_creadas}
                    </div>
                </div>
            `;
            viewBtn.style.display = 'inline-block';
            viewBtn.onclick = () => window.location.href = `/dialogos/${data.data.dialogo_id}`;
        } else {
            body.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${data.message}
                </div>
                ${data.errors ? `
                    <div class="mt-3">
                        <h6>Errores de validación:</h6>
                        <ul class="list-unstyled">
                            ${Object.entries(data.errors).map(([key, value]) => 
                                `<li><small class="text-danger">${key}: ${Array.isArray(value) ? value.join(', ') : value}</small></li>`
                            ).join('')}
                        </ul>
                    </div>
                ` : ''}
            `;
            viewBtn.style.display = 'none';
        }

        modal.show();
    }
});

// Plantillas de ejemplo
function cargarPlantilla(tipo) {
    const plantillas = {
        basico: {
            dialogo: {
                nombre: "Diálogo Básico",
                descripcion: "Un diálogo simple con inicio y fin",
                publico: false
            },
            nodos: [
                {
                    id: "nodo_1",
                    titulo: "Inicio",
                    contenido: "Bienvenido al diálogo",
                    rol_nombre: "Sistema",
                    tipo: "inicio",
                    es_inicial: true,
                    es_final: false,
                    posicion: { x: 0, y: 0 }
                },
                {
                    id: "nodo_2",
                    titulo: "Desarrollo",
                    contenido: "Contenido del diálogo",
                    rol_nombre: "Usuario",
                    tipo: "desarrollo",
                    es_inicial: false,
                    es_final: false,
                    posicion: { x: 200, y: 0 }
                },
                {
                    id: "nodo_3",
                    titulo: "Fin",
                    contenido: "Fin del diálogo",
                    rol_nombre: "Sistema",
                    tipo: "final",
                    es_inicial: false,
                    es_final: true,
                    posicion: { x: 400, y: 0 }
                }
            ],
            conexiones: [
                {
                    desde: "nodo_1",
                    hacia: "nodo_2",
                    texto: "Continuar",
                    color: "#007bff",
                    puntuacion: 0
                },
                {
                    desde: "nodo_2",
                    hacia: "nodo_3",
                    texto: "Finalizar",
                    color: "#28a745",
                    puntuacion: 10
                }
            ]
        }
    };

    const plantilla = plantillas[tipo];
    if (plantilla) {
        const blob = new Blob([JSON.stringify(plantilla, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `plantilla_${tipo}.json`;
        a.click();
        URL.revokeObjectURL(url);
    }
}
</script>
@endsection
