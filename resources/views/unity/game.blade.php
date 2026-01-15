<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>JuiciosSimulator - Sala Principal</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <style>
        /* Estilos básicos para Unity WebGL */
        #unity-container {
            width: 100vw;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        #unity-canvas {
            width: 100%;
            height: 100%;
            display: block;
        }
        
        #unity-loading-bar {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            display: none;
        }
        
        #unity-logo {
            width: 154px;
            height: 130px;
            background: #007bff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        
        #unity-logo::before {
            content: "⚖️";
            font-size: 48px;
        }
        
        #unity-progress-bar-empty {
            width: 141px;
            height: 18px;
            margin-top: 10px;
            background: #333;
            border-radius: 9px;
        }
        
        #unity-progress-bar-full {
            width: 0%;
            height: 18px;
            background: #007bff;
            border-radius: 9px;
        }
        
        .loading-text {
            color: #fff;
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .error-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #ff6b6b;
            text-align: center;
            display: none;
        }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Simulador de Juicios Orales - Sala Principal">
    
    <!-- Meta tags para Unity WebGL -->
    <meta name="unity-webgl" content="true">
    <meta name="unity-platform" content="WebGL">
    
    <!-- Estilos personalizados -->
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #1a1a1a;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        #unity-container {
            width: 100vw;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        #unity-canvas {
            width: 100%;
            height: 100%;
            display: block;
        }
        
        #unity-loading-bar {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            display: none;
        }
        
        #unity-logo {
            width: 154px;
            height: 130px;
            background: #007bff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        
        #unity-logo::before {
            content: "⚖️";
            font-size: 48px;
        }
        
        #unity-progress-bar-empty {
            width: 141px;
            height: 18px;
            margin-top: 10px;
            background: #333;
            border-radius: 9px;
        }
        
        #unity-progress-bar-full {
            width: 0%;
            height: 18px;
            background: #007bff;
            border-radius: 9px;
        }
        
        #unity-footer {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            color: #888;
            font-size: 12px;
        }
        
        .loading-text {
            color: #fff;
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .error-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #ff6b6b;
            text-align: center;
            display: none;
        }
        
        /* Ventana de Logs de Debug */
        #debug-log-window {
          position: fixed;
          bottom: 10px;
          left: 10px;
          width: 600px;
          max-height: 500px;
          background: rgba(20, 20, 20, 0.95);
          border: 2px solid #4CAF50;
          border-radius: 8px;
          z-index: 100000;
          display: flex;
          flex-direction: column;
          box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
          font-family: 'Courier New', monospace;
          font-size: 11px;
        }
        
        #debug-log-header {
          background: #2d2d2d;
          padding: 8px 12px;
          display: flex;
          justify-content: space-between;
          align-items: center;
          border-bottom: 1px solid #4CAF50;
          cursor: move;
          user-select: none;
        }
        
        #debug-log-title {
          color: #4CAF50;
          font-weight: bold;
          font-size: 12px;
        }
        
        #debug-log-controls {
          display: flex;
          gap: 5px;
        }
        
        .debug-log-btn {
          background: #444;
          border: 1px solid #666;
          color: #fff;
          padding: 4px 8px;
          border-radius: 3px;
          cursor: pointer;
          font-size: 10px;
        }
        
        .debug-log-btn:hover {
          background: #555;
        }
        
        #debug-log-content {
          flex: 1;
          overflow-y: auto;
          padding: 8px;
          color: #e0e0e0;
          max-height: 450px;
        }
        
        .debug-log-entry {
          margin: 2px 0;
          padding: 2px 4px;
          border-left: 2px solid transparent;
          word-wrap: break-word;
          white-space: pre-wrap;
        }
        
        .debug-log-entry.debug { border-left-color: #888; color: #aaa; }
        .debug-log-entry.info { border-left-color: #2196F3; color: #90caf9; }
        .debug-log-entry.warning { border-left-color: #ff9800; color: #ffb74d; }
        .debug-log-entry.error { border-left-color: #f44336; color: #ef5350; }
        .debug-log-entry.phase { border-left-color: #4CAF50; color: #81c784; font-weight: bold; }
        .debug-log-entry.api { border-left-color: #9c27b0; color: #ba68c8; }
        .debug-log-entry.event { border-left-color: #00bcd4; color: #4dd0e1; }
        
        .debug-log-entry .timestamp {
          color: #666;
          margin-right: 5px;
        }
        
        .debug-log-entry .category {
          color: #888;
          margin-right: 5px;
          font-weight: bold;
        }
        
        #debug-log-window.minimized {
          height: auto;
          max-height: 40px;
        }
        
        #debug-log-window.minimized #debug-log-content {
          display: none;
        }
        
        #debug-log-content::-webkit-scrollbar {
          width: 8px;
        }
        
        #debug-log-content::-webkit-scrollbar-track {
          background: #1a1a1a;
        }
        
        #debug-log-content::-webkit-scrollbar-thumb {
          background: #4CAF50;
          border-radius: 4px;
        }
        
        #debug-log-content::-webkit-scrollbar-thumb:hover {
          background: #66bb6a;
        }
    </style>
</head>
<body>
    <!-- Ventana de Logs de Debug -->
    <div id="debug-log-window">
        <div id="debug-log-header">
            <div id="debug-log-title">📋 DEBUG LOGS</div>
            <div id="debug-log-controls">
                <button class="debug-log-btn" onclick="clearDebugLogs()">Limpiar</button>
                <button class="debug-log-btn" onclick="toggleDebugLogWindow()">Minimizar</button>
                <button class="debug-log-btn" onclick="toggleDebugLogEnabled()" id="toggle-log-btn">Desactivar</button>
            </div>
        </div>
        <div id="debug-log-content"></div>
    </div>
    <div id="unity-container">
        <canvas id="unity-canvas" tabindex="-1"></canvas>
        
        <div id="unity-loading-bar">
            <div id="unity-logo"></div>
            <div id="unity-progress-bar-empty">
                <div id="unity-progress-bar-full"></div>
            </div>
            <div class="loading-text">Cargando JuiciosSimulator...</div>
        </div>
        
        <div id="unity-footer">
            <p>JuiciosSimulator v1.0.0 - Simulador de Juicios Orales</p>
        </div>
        
        <div class="error-message" id="error-message">
            <h3>Error al cargar el juego</h3>
            <p id="error-text">Por favor, recarga la página e intenta nuevamente.</p>
            <button id="retry-button" onclick="retryUnityLoad()" style="margin-top: 15px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Reintentar
            </button>
        </div>
    </div>

    <!-- Scripts de Unity -->
    <!-- Nota: el loader se inyectará más abajo, después de definir `config` -->
    <script>
        // Placeholder: la inyección del loader se hace después de la configuración de Unity
        window.__unity_loader_url = "{{ route('unity.assets', 'Build/unity-build.loader.js') }}";
        window.__injectUnityLoader = function() {
            if (!window.__unity_loader_url) return;
            var loaderScript = document.createElement('script');
            loaderScript.src = window.__unity_loader_url;
            loaderScript.onload = function() {
                addDebugLog('phase', 'UNITY', 'Loader script cargado correctamente');
                setTimeout(function() {
                    if (typeof createUnityInstance !== 'undefined') {
                        initializeUnity();
                    } else {
                        addDebugLog('error', 'UNITY', 'createUnityInstance no disponible después de cargar el loader');
                        if (typeof config !== 'undefined' && config.onError) config.onError('No se pudo cargar el loader de Unity correctamente');
                    }
                }, 100);
            };
            loaderScript.onerror = function(error) {
                var errorMsg = 'No se pudo cargar el archivo unity-build.loader.js. ';
                if (window.location.protocol === 'file:') {
                    errorMsg += 'No puedes abrir Unity WebGL directamente desde el sistema de archivos. Usa un servidor web (http://localhost:8000/unity-game)';
                } else {
                    errorMsg += 'Verifica que el build esté completo y que el servidor esté corriendo.';
                }
                addDebugLog('error', 'UNITY', 'Error al cargar el loader script', {
                    error: String(error),
                    loaderUrl: loaderScript.src,
                    protocol: window.location.protocol
                });
                if (typeof config !== 'undefined' && config.onError) config.onError(errorMsg);
            };
            document.head.appendChild(loaderScript);
        };
    </script>
    <script>
        // ===== SISTEMA DE LOGGING EN HTML =====
        let debugLogEnabled = true;
        let debugLogs = [];
        const MAX_LOG_ENTRIES = 1000;
        
        function addDebugLog(level, category, message, data = null) {
          if (!debugLogEnabled) return;
          
          const timestamp = new Date().toLocaleTimeString('es-ES', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit', fractionalSecondDigits: 3 });
          const logEntry = { timestamp, level, category, message, data };
          
          debugLogs.push(logEntry);
          if (debugLogs.length > MAX_LOG_ENTRIES) {
            debugLogs.shift();
          }
          
          const content = document.getElementById('debug-log-content');
          if (content) {
            const entry = document.createElement('div');
            entry.className = `debug-log-entry ${level}`;
            
            let html = `<span class="timestamp">[${timestamp}]</span>`;
            html += `<span class="category">[${category}]</span>`;
            html += `<span>${escapeHtml(message)}</span>`;
            
            if (data) {
              try {
                html += `<br><span style="color: #888; margin-left: 20px;">${escapeHtml(JSON.stringify(data, null, 2))}</span>`;
              } catch (e) {
                html += `<br><span style="color: #888; margin-left: 20px;">${escapeHtml(String(data))}</span>`;
              }
            }
            
            entry.innerHTML = html;
            content.appendChild(entry);
            content.scrollTop = content.scrollHeight;
          }
        }
        
        function escapeHtml(text) {
          const div = document.createElement('div');
          div.textContent = text;
          return div.innerHTML;
        }
        
        function clearDebugLogs() {
          debugLogs = [];
          const content = document.getElementById('debug-log-content');
          if (content) content.innerHTML = '';
          addDebugLog('info', 'SYSTEM', 'Logs limpiados');
        }
        
        function toggleDebugLogWindow() {
          const window = document.getElementById('debug-log-window');
          if (window) window.classList.toggle('minimized');
        }
        
        function toggleDebugLogEnabled() {
          debugLogEnabled = !debugLogEnabled;
          const btn = document.getElementById('toggle-log-btn');
          if (btn) btn.textContent = debugLogEnabled ? 'Desactivar' : 'Activar';
          addDebugLog('info', 'SYSTEM', `Logging ${debugLogEnabled ? 'activado' : 'desactivado'}`);
        }
        
        // Interceptar console
        const originalLog = console.log;
        const originalError = console.error;
        const originalWarn = console.warn;
        const originalInfo = console.info;
        
        console.log = function(...args) {
          originalLog.apply(console, args);
          const message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg) : String(arg)).join(' ');
          addDebugLog('info', 'CONSOLE', message);
        };
        
        console.error = function(...args) {
          originalError.apply(console, args);
          const message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg) : String(arg)).join(' ');
          addDebugLog('error', 'CONSOLE', message);
        };
        
        console.warn = function(...args) {
          originalWarn.apply(console, args);
          const message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg) : String(arg)).join(' ');
          addDebugLog('warning', 'CONSOLE', message);
        };
        
        console.info = function(...args) {
          originalInfo.apply(console, args);
          const message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg) : String(arg)).join(' ');
          addDebugLog('info', 'CONSOLE', message);
        };
        
        // Capturar errores globales
        window.addEventListener('error', function(e) {
          addDebugLog('error', 'GLOBAL_ERROR', `${e.message}`, {
            filename: e.filename,
            lineno: e.lineno,
            colno: e.colno,
            stack: e.error?.stack
          });
        });
        
        window.addEventListener('unhandledrejection', function(e) {
          addDebugLog('error', 'PROMISE_REJECTION', `Promise rechazada: ${e.reason}`, {
            reason: String(e.reason),
            stack: e.reason?.stack
          });
        });
        
        // Funciones para Unity
        window.unityDebugLog = function(level, category, message, data) {
          addDebugLog(level || 'info', category || 'UNITY', message || '', data);
        };
        
        window.unityLogPhase = function(phaseName, status, data) {
          addDebugLog('phase', 'PHASE', `[${phaseName}] ${status}`, data);
        };
        
        window.unityLogAPI = function(method, url, status, data) {
          addDebugLog('api', 'API', `[${method}] ${url} - ${status}`, data);
        };
        
        window.unityLogEvent = function(eventName, message, data) {
          addDebugLog('event', 'EVENT', `[${eventName}] ${message}`, data);
        };
        
        window.addDebugLog = addDebugLog;
        window.clearDebugLogs = clearDebugLogs;
        window.toggleDebugLogWindow = toggleDebugLogWindow;
        window.toggleDebugLogEnabled = toggleDebugLogEnabled;
        
        addDebugLog('info', 'SYSTEM', 'Sistema de logging inicializado');
        addDebugLog('phase', 'INIT', 'Página cargada', {
          url: window.location.href,
          userAgent: navigator.userAgent,
          timestamp: new Date().toISOString()
        });
        // ===== FIN SISTEMA DE LOGGING =====
        
        // Configuración de Unity
        var container = document.querySelector("#unity-container");
        var canvas = document.querySelector("#unity-canvas");
        var loadingBar = document.querySelector("#unity-loading-bar");
        var progressBarFull = document.querySelector("#unity-progress-bar-full");
        var fullscreenButton = document.querySelector("#unity-fullscreen-button");
        var errorMessage = document.querySelector("#error-message");
        var errorText = document.querySelector("#error-text");

        // Mostrar loading
        loadingBar.style.display = "block";
        addDebugLog('phase', 'UNITY', 'Iniciando carga de Unity');

        // Función banner (compatible con plantillas Unity)
        function unityShowBanner(msg, type) {
            var warningBanner = document.querySelector("#unity-warning");
            if (!warningBanner) return;
            function update() { warningBanner.style.display = warningBanner.children.length ? 'block' : 'none' }
            var div = document.createElement('div');
            div.innerHTML = msg;
            if (type == 'error') div.style = 'background:red;padding:10px;color:#fff;';
            else if (type == 'warning') div.style = 'background:yellow;padding:10px;color:#000;';
            else div.style = 'background:#333;padding:6px;color:#fff;';
            warningBanner.appendChild(div);
            if (type !== 'warning') update();
            setTimeout(function(){ try{ warningBanner.removeChild(div); update(); }catch(e){} }, type=='warning'?5000:8000);
        }

        // Configuración del módulo Unity
        // Usar ruta de Laravel para archivos con headers correctos
        var buildUrl = "{{ route('unity.assets', 'Build') }}";
        var streamingAssetsUrl = "{{ route('unity.assets', 'StreamingAssets') }}";
        // IMPORTANTE: Usar el nombre correcto del build (unity-build, no juicio)
        var buildName = "unity-build";

        // Usar por defecto las versiones comprimidas (.br) servidas por Laravel
        var dataExt = '.data.br';
        var frameworkExt = '.framework.js.br';
        var codeExt = '.wasm.br';

        var config = {
            dataUrl: buildUrl + "/" + buildName + dataExt,
            frameworkUrl: buildUrl + "/" + buildName + frameworkExt,
            codeUrl: buildUrl + "/" + buildName + codeExt,
            streamingAssetsUrl: streamingAssetsUrl,
            companyName: "JuiciosSimulator",
            productName: "JuiciosSimulator",
            productVersion: "1.0.0",
            // Use the default Unity banner handler from the template
            showBanner: unityShowBanner,
            // Basic settings
            matchWebGLToCanvasSize: true,
            devicePixelRatio: window.devicePixelRatio || 1,
            preserveDrawingBuffer: false,
            powerPreference: "default",
            // Keep a reasonable startup timeout
            startupTimeout: 60000
        };

        // Configurar canvas
        config.canvas = canvas;

            // Ahora que `config` está definido, ejecutar diagnósticos de los assets
            async function runAssetDiagnostics() {
                const assets = [
                    { url: config.codeUrl, name: 'code (wasm)', ext: codeExt },
                    { url: config.frameworkUrl, name: 'framework (js)', ext: frameworkExt },
                    { url: config.dataUrl, name: 'data', ext: dataExt }
                ];

                // If server serves .br files without Content-Encoding: br, we must switch
                // to uncompressed URLs (remove .br). Detect and return a flag.
                let needUncompressed = false;

                for (const a of assets) {
                    try {
                        addDebugLog('debug', 'DIAG', `Comprobando asset: ${a.name}`, { url: a.url });
                        const resp = await fetch(a.url, { cache: 'no-store' });
                        const headers = {};
                        resp.headers.forEach((v,k) => headers[k] = v);
                        let buf = null;
                        try {
                            buf = await resp.arrayBuffer();
                        } catch (e) {
                            addDebugLog('error', 'DIAG', `No se pudo leer ArrayBuffer de ${a.name}`, { error: String(e), url: a.url, headers });
                            return { ok: false, reason: 'arrayBuffer' };
                        }
                        addDebugLog('info', 'DIAG', `Asset descargado: ${a.name}`, { url: a.url, headers, byteLength: buf.byteLength });

                        // If the URL requested ends with .br but the server didn't send
                        // Content-Encoding: br, assume it's serving the uncompressed file
                        // (common when Compression disabled). In that case, switch to
                        // uncompressed URLs to avoid loader confusion.
                        if (a.url.endsWith('.br')) {
                            const ce = (headers['content-encoding'] || headers['Content-Encoding'] || '').toLowerCase();
                            if (!ce || ce.indexOf('br') === -1) {
                                addDebugLog('warning', 'DIAG', `Server returned .br URL without Content-Encoding: br for ${a.name}. Will switch to uncompressed URLs.`, { url: a.url, headers, byteLength: buf.byteLength });
                                needUncompressed = true;
                                break;
                            }
                        }

                        // Basic sanity checks
                        if (a.name.includes('wasm') && buf.byteLength < 1024) {
                            addDebugLog('error', 'DIAG', 'WASM demasiado pequeño, posible corrupción o decompression issue', { url: a.url, size: buf.byteLength });
                            return { ok: false, reason: 'wasm_too_small' };
                        }
                    } catch (err) {
                        addDebugLog('error', 'DIAG', `Error comprobando asset ${a.name}`, { error: String(err), url: a.url });
                        return { ok: false, reason: 'fetch_error' };
                    }
                }

                return { ok: true, needUncompressed };
            }

            (async function() {
                const result = await runAssetDiagnostics();
                if (!result.ok) {
                    const msg = 'Diagnostics failed for Unity assets. Revisa Content-Encoding/Content-Type o recompila Unity.';
                    addDebugLog('error', 'DIAG', msg, result);
                    loadingBar.style.display = 'none';
                    errorMessage.style.display = 'block';
                    errorText.textContent = msg + ' (ver logs)';
                    return;
                }

                if (result.needUncompressed) {
                    addDebugLog('info', 'DIAG', 'Switching to uncompressed asset URLs because server did not provide Brotli encoding for .br files');
                    dataExt = dataExt.replace('.br', '');
                    frameworkExt = frameworkExt.replace('.br', '');
                    codeExt = codeExt.replace('.br', '');
                    config.dataUrl = buildUrl + "/" + buildName + dataExt;
                    config.frameworkUrl = buildUrl + "/" + buildName + frameworkExt;
                    config.codeUrl = buildUrl + "/" + buildName + codeExt;
                    addDebugLog('debug', 'DIAG', 'New asset URLs', { dataUrl: config.dataUrl, frameworkUrl: config.frameworkUrl, codeUrl: config.codeUrl });
                }

                if (typeof window.__injectUnityLoader === 'function') {
                    try { window.__injectUnityLoader(); } catch (e) { console.error('Error injecting Unity loader:', e); addDebugLog('error','DIAG','Error injecting Unity loader', {error: String(e)}); }
                }
            })();
        // Configurar eventos de progreso
        config.onProgress = function (progress) {
            progressBarFull.style.width = 100 * progress + "%";
            addDebugLog('debug', 'UNITY', `Progreso de carga: ${(progress * 100).toFixed(1)}%`);
        };

        // Configurar eventos de error
        config.onError = function (message) {
            addDebugLog('error', 'UNITY', `Error de Unity: ${message}`);
            console.error("Error de Unity:", message);
            loadingBar.style.display = "none";
            errorMessage.style.display = "block";
            errorText.textContent = message;
            isInitializing = false;
        };

        // Configurar eventos de éxito
        config.onSuccess = function () {
            addDebugLog('phase', 'UNITY', 'Unity cargado exitosamente');
            console.log("Unity cargado exitosamente");
            loadingBar.style.display = "none";
            
            // Configurar comunicación con Laravel
            setupLaravelCommunication();
        };

        // Variable global para la instancia de Unity
        var unityInstance = null;
        var isInitializing = false;
        var errorHandlingConfig = null;

        // Cargar configuración de manejo de errores
        function loadErrorHandlingConfig() {
            fetch('{{ route('unity.assets', 'StreamingAssets/unity-error-handling.json') }}')
                .then(response => response.json())
                .then(config => {
                    errorHandlingConfig = config;
                    console.log("Configuración de manejo de errores cargada:", config);
                })
                .catch(error => {
                    console.warn("No se pudo cargar la configuración de errores, usando configuración por defecto");
                    errorHandlingConfig = {
                        errorHandling: {
                            suppressBlitterErrors: true,
                            suppressFormatExceptions: true,
                            suppressPhotonErrors: true,
                            suppressServerCertificateErrors: true,
                            logErrorsToConsole: true,
                            showErrorsToUser: false
                        }
                    };
                });
        }

        // Cargar configuración al inicio
        loadErrorHandlingConfig();

        // Función para limpiar recursos antes de reinicializar
        function cleanupUnityInstance() {
            if (unityInstance) {
                try {
                    if (unityInstance.Module && unityInstance.Module.destroy) {
                        unityInstance.Module.destroy();
                    }
                } catch (error) {
                    console.warn("Error al limpiar instancia de Unity:", error);
                }
                unityInstance = null;
            }
        }

        // Crear instancia de Unity - ESPERAR a que el loader esté cargado
        function initializeUnity() {
            if (isInitializing) {
                addDebugLog('warning', 'UNITY', 'Unity ya se está inicializando');
                console.warn("Unity ya se está inicializando");
                return;
            }
            
            // Verificar que createUnityInstance esté disponible
            if (typeof createUnityInstance === 'undefined') {
                addDebugLog('error', 'UNITY', 'createUnityInstance no está disponible. El loader script no se cargó correctamente.');
                console.error("createUnityInstance no está disponible. Esperando a que el loader se cargue...");
                
                // Esperar y reintentar
                setTimeout(function() {
                    if (typeof createUnityInstance !== 'undefined') {
                        initializeUnity();
                    } else {
                        config.onError("No se pudo cargar el loader de Unity. Verifica que el archivo unity-build.loader.js esté disponible.");
                    }
                }, 500);
                return;
            }
            
            addDebugLog('phase', 'UNITY', 'Inicializando instancia de Unity', {
              dataUrl: config.dataUrl,
              frameworkUrl: config.frameworkUrl,
              codeUrl: config.codeUrl
            });
            
            isInitializing = true;
            cleanupUnityInstance();

            createUnityInstance(canvas, config, function (progress) {
                config.onProgress(progress);
            }).then(function (instance) {
                addDebugLog('phase', 'UNITY', 'Instancia de Unity creada exitosamente');
                unityInstance = instance;
                isInitializing = false;
                config.onSuccess();
                setupLaravelCommunication();
            }).catch(function (message) {
                addDebugLog('error', 'UNITY', `Error al crear instancia: ${message}`);
                isInitializing = false;
                config.onError(message);
            });
        }

        // Función de reintento mejorada
        window.retryUnityLoad = function() {
            console.log("Reintentando carga de Unity...");
            errorMessage.style.display = "none";
            loadingBar.style.display = "block";
            progressBarFull.style.width = "0%";
            
            // Limpiar completamente antes de reintentar
            cleanupUnityInstance();
            
            // Esperar un poco antes de reinicializar
            setTimeout(function() {
                initializeUnity();
            }, 1000);
        };

        // NO inicializar Unity aquí - se inicializará cuando el loader se cargue
        // (ver el script que carga unity-build.loader.js arriba)

        // Configurar comunicación con Laravel
        function setupLaravelCommunication() {
            // Función para enviar datos a Unity
            window.sendToUnity = function(data) {
                if (unityInstance && unityInstance.SendMessage) {
                    try {
                        unityInstance.SendMessage('LaravelUnityEntryManager', 'ReceiveLaravelData', JSON.stringify(data));
                    } catch (error) {
                        console.error("Error enviando datos a Unity:", error);
                    }
                }
            };

            // Función para recibir datos de Unity
            window.receiveFromUnity = function(data) {
                console.log("Datos recibidos de Unity:", data);
                // Aquí puedes procesar los datos recibidos de Unity
            };

            // Configurar comunicación bidireccional cuando la instancia esté lista
            if (unityInstance && unityInstance.Module) {
                unityInstance.Module.onRuntimeInitialized = function() {
                    console.log("Runtime de Unity inicializado");
                    
                    // Enviar información de la sesión si está disponible
                    @if(isset($sessionData))
                        setTimeout(function() {
                            window.sendToUnity(@json($sessionData));
                        }, 1000); // Esperar un poco para asegurar que Unity esté completamente listo
                    @endif
                };
            } else {
                console.warn("Unity instance no está disponible para configuración de comunicación");
            }
        }

        // Manejar errores de WebGL
        window.addEventListener('error', function(e) {
            console.error("Error global:", e);
            
            // Usar configuración de manejo de errores
            var config = errorHandlingConfig ? errorHandlingConfig.errorHandling : {
                suppressBlitterErrors: true,
                suppressFormatExceptions: true,
                suppressPhotonErrors: true,
                suppressServerCertificateErrors: true,
                logErrorsToConsole: true,
                showErrorsToUser: false
            };
            
            // Detectar errores específicos de Unity
            if (e.message.includes('Blitter is already initialized')) {
                if (config.logErrorsToConsole) {
                    console.warn("Error de Blitter detectado - Unity se está reinicializando");
                }
                if (!config.suppressBlitterErrors && config.showErrorsToUser) {
                    errorMessage.style.display = "block";
                    errorText.textContent = "Error de renderizado: " + e.message;
                    loadingBar.style.display = "none";
                }
                return;
            }
            
            if (e.message.includes('FormatException') || e.message.includes('StringBuilder')) {
                if (config.logErrorsToConsole) {
                    console.warn("Error de formato detectado - posible problema de datos");
                }
                if (!config.suppressFormatExceptions && config.showErrorsToUser) {
                    errorMessage.style.display = "block";
                    errorText.textContent = "Error de datos: " + e.message;
                    loadingBar.style.display = "none";
                }
                return;
            }
            
            if (e.message.includes('PhotonNetwork') || e.message.includes('Photon')) {
                if (config.logErrorsToConsole) {
                    console.warn("Error de Photon detectado - problemas de red");
                }
                if (!config.suppressPhotonErrors && config.showErrorsToUser) {
                    errorMessage.style.display = "block";
                    errorText.textContent = "Error de red: " + e.message;
                    loadingBar.style.display = "none";
                }
                return;
            }
            
            if (e.message.includes('ServerCertificate')) {
                if (config.logErrorsToConsole) {
                    console.warn("Error de ServerCertificate detectado - script faltante");
                }
                if (!config.suppressServerCertificateErrors && config.showErrorsToUser) {
                    errorMessage.style.display = "block";
                    errorText.textContent = "Error de certificado: " + e.message;
                    loadingBar.style.display = "none";
                }
                return;
            }
            
            // Solo mostrar errores críticos al usuario
            if (e.message.includes('WebGL') || e.message.includes('Unity') || e.message.includes('unityInstance')) {
                errorMessage.style.display = "block";
                errorText.textContent = "Error de WebGL: " + e.message;
                loadingBar.style.display = "none";
            }
        });

        // Manejar errores no capturados
        window.addEventListener('unhandledrejection', function(e) {
            console.error("Promise rechazada:", e.reason);
            if (e.reason && e.reason.toString().includes('Unity')) {
                errorMessage.style.display = "block";
                errorText.textContent = "Error de Unity: " + e.reason.toString();
                loadingBar.style.display = "none";
            }
        });

        // Manejar resize de ventana
        window.addEventListener('resize', function() {
            if (unityInstance && unityInstance.Module && unityInstance.Module.setCanvasSize) {
                try {
                    unityInstance.Module.setCanvasSize(window.innerWidth, window.innerHeight);
                } catch (error) {
                    console.warn("Error al redimensionar canvas:", error);
                }
            }
        });
    </script>
</body>
</html>
