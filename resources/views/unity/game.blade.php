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
    <!-- Indicadores de audio (micrófono / audio entrante) -->
    <style>
        .audio-status-fixed {
            position: fixed;
            top: 12px;
            right: 12px;
            display: flex;
            gap: 10px;
            z-index: 100002;
            align-items: center;
            pointer-events: none;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .audio-card-small {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            background: rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform .12s ease, box-shadow .12s ease, background .12s ease;
            pointer-events: auto;
            backdrop-filter: blur(4px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }
        .audio-card-small img { width: 24px; height: 24px; filter: invert(1); }
        .audio-pill {
            margin-left: 6px;
            padding: 6px 8px;
            border-radius: 999px;
            background: rgba(0,0,0,0.45);
            color: #eee;
            font-size: 12px;
            pointer-events: auto;
            box-shadow: 0 2px 6px rgba(0,0,0,0.4);
        }
        .audio-pill.ok { background: #2e7d32; color: #e8f5e9; }
        .audio-pill.off { background: #424242; color: #e0e0e0; }
        /* Panel de chat de voz (LiveKit) - WebGL */
        #voice-chat-panel {
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 100001;
            min-width: 260px;
            max-width: 320px;
            background: rgba(20, 20, 24, 0.95);
            border: 1px solid rgba(76, 175, 80, 0.4);
            border-radius: 10px;
            padding: 12px 14px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 13px;
            color: #e8e8e8;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
            backdrop-filter: blur(8px);
        }
        #voice-chat-panel .voice-panel-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #4CAF50;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        #voice-chat-panel .voice-panel-title .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #666;
            flex-shrink: 0;
        }
        #voice-chat-panel .voice-panel-title .dot.connected { background: #4CAF50; box-shadow: 0 0 8px #4CAF50; }
        #voice-chat-panel .voice-panel-title .dot.connecting { background: #FFC107; animation: pulse 1s infinite; }
        @keyframes pulse { 50% { opacity: 0.5; } }
        #voice-chat-panel .voice-status-line {
            margin-bottom: 10px;
            font-size: 12px;
            color: #b0b0b0;
        }
        #voice-chat-panel .voice-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        #voice-chat-panel .voice-btn {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s, opacity 0.2s;
        }
        #voice-chat-panel .voice-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        #voice-chat-panel .voice-btn-enable {
            background: #4CAF50;
            color: #fff;
        }
        #voice-chat-panel .voice-btn-enable:hover:not(:disabled) { background: #66bb6a; }
        #voice-chat-panel .voice-btn-mute {
            background: #37474F;
            color: #e0e0e0;
        }
        #voice-chat-panel .voice-btn-mute:hover:not(:disabled) { background: #455a64; }
        #voice-chat-panel .voice-btn-mute.muted { background: #c62828; color: #fff; }
        #voice-chat-panel .voice-btn-disconnect {
            background: transparent;
            color: #b0b0b0;
            border: 1px solid #555;
        }
        #voice-chat-panel .voice-btn-disconnect:hover:not(:disabled) { background: rgba(255,255,255,0.06); color: #e0e0e0; }
        #voice-chat-panel .voice-btn-test { background: #1565c0; color: #fff; }
        #voice-chat-panel .voice-btn-test:hover:not(:disabled) { background: #1976d2; }
        #voice-chat-panel .voice-info-block { margin-bottom: 8px; font-size: 12px; color: #b0b0b0; }
        #voice-chat-panel .voice-info-block strong { color: #e0e0e0; margin-right: 6px; }
        #voice-chat-panel .voice-connection-state { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; }
        #voice-chat-panel .voice-connection-state.disconnected { background: #424242; color: #b0b0b0; }
        #voice-chat-panel .voice-connection-state.connecting { background: #FFC107; color: #333; }
        #voice-chat-panel .voice-connection-state.connected { background: #2e7d32; color: #e8f5e9; }
        #voice-chat-panel .voice-connection-state.reconnecting { background: #F57C00; color: #fff; }
        #voice-chat-panel .voice-connection-state.error { background: #c62828; color: #fff; }
        #voice-chat-panel .voice-mics-label { display: block; margin: 8px 0 4px; font-size: 11px; color: #888; }
        #voice-chat-panel select.voice-mic-select { width: 100%; max-width: 100%; padding: 6px 8px; margin-bottom: 8px; font-size: 11px; background: #2a2a2e; color: #e0e0e0; border: 1px solid #444; border-radius: 4px; }
    </style>

    <!-- Panel Chat de voz (LiveKit) - habilitar, usar y ver conexión -->
    <div id="voice-chat-panel" aria-label="Chat de voz">
        <div class="voice-panel-title">
            <span class="dot" id="voice-status-dot"></span>
            <span>Chat de voz (LiveKit)</span>
        </div>
        <div class="voice-info-block">
            <strong>Sala:</strong> <span id="voice-room-name">—</span>
        </div>
        <div class="voice-info-block">
            <strong>Estado conexión:</strong> <span id="voice-connection-state" class="voice-connection-state disconnected">Desconectado</span>
        </div>
        <div class="voice-status-line" id="voice-status-text">Sin conexión a sala de voz.</div>
        <label class="voice-mics-label" id="voice-mics-label">Micrófonos disponibles:</label>
        <select id="voice-mic-select" class="voice-mic-select" aria-label="Seleccionar micrófono" style="display:none;"></select>
        <div class="voice-buttons">
            <button type="button" class="voice-btn voice-btn-enable" id="voice-btn-enable" onclick="window.voiceChatPanelEnable()">Habilitar micrófono</button>
            <button type="button" class="voice-btn voice-btn-test" id="voice-btn-test" onclick="window.voiceChatPanelTestConnect()" title="Probar conexión al servidor LiveKit (sala de prueba)">Conectar a sala de prueba</button>
            <button type="button" class="voice-btn voice-btn-mute" id="voice-btn-mute" disabled onclick="window.voiceChatPanelToggleMute()">Silenciar / Activar mic</button>
            <button type="button" class="voice-btn voice-btn-disconnect" id="voice-btn-disconnect" disabled onclick="window.voiceChatPanelDisconnect()">Desconectar</button>
        </div>
    </div>

    <div class="audio-status-fixed" id="audio-status-fixed" aria-hidden="false">
        <div id="micCardSmall" class="audio-card-small" title="Micrófono">
            <img src="https://img.icons8.com/ios-filled/50/microphone.png" alt="mic" />
        </div>
        <div id="micPill" class="audio-pill off">Mic: sin señal</div>

        <div id="speakerCardSmall" class="audio-card-small" title="Audio entrante">
            <img src="https://img.icons8.com/ios-filled/50/speaker.png" alt="speaker" />
        </div>
        <div id="speakerPill" class="audio-pill off">Audio: inactivo</div>
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

    <!-- LiveKit Client SDK -->
    <script src="https://cdn.jsdelivr.net/npm/livekit-client@2.11.0/dist/livekit-client.umd.min.js"></script>
    
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
        // ===== SISTEMA DE LOGGING EN HTML (debe cargar antes de LiveKit/panel de voz) =====
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
    </script>
    <script>
        // Funciones globales para controlar los indicadores (para testing rápido)
        window.setMicActive = function(active) {
            try {
                const pill = document.getElementById('micPill');
                const card = document.getElementById('micCardSmall');
                if (!pill || !card) return;
                if (active) {
                    pill.classList.add('ok'); pill.classList.remove('off'); pill.textContent = 'Mic: detectando';
                    card.style.transform = 'scale(1.06)'; card.style.background = 'linear-gradient(135deg,#2e7d32,#66bb6a)';
                } else {
                    pill.classList.remove('ok'); pill.classList.add('off'); pill.textContent = 'Mic: sin señal';
                    card.style.transform = 'scale(1)'; card.style.background = 'rgba(255,255,255,0.06)';
                }
            } catch(e) { console.warn(e); }
        };

        window.setSpeakerActive = function(active) {
            try {
                const pill = document.getElementById('speakerPill');
                const card = document.getElementById('speakerCardSmall');
                if (!pill || !card) return;
                if (active) {
                    pill.classList.add('ok'); pill.classList.remove('off'); pill.textContent = 'Audio: reproduciendo';
                    card.style.transform = 'scale(1.06)'; card.style.background = 'linear-gradient(135deg,#1565c0,#42a5f5)';
                } else {
                    pill.classList.remove('ok'); pill.classList.add('off'); pill.textContent = 'Audio: inactivo';
                    card.style.transform = 'scale(1)'; card.style.background = 'rgba(255,255,255,0.06)';
                }
            } catch(e) { console.warn(e); }
        };

        // Helper para solicitar micrófono de forma manual (solo para pruebas)
        window.requestLocalMic = async function() {
            try {
                const s = await navigator.mediaDevices.getUserMedia({ audio: true });
                // medidor simple: usar AudioContext para detectar actividad y actualizar indicator
                try {
                    const AC = window.AudioContext || window.webkitAudioContext;
                    const ctx = new AC();
                    const src = ctx.createMediaStreamSource(s);
                    const an = ctx.createAnalyser(); an.fftSize = 512;
                    const buf = new Uint8Array(an.frequencyBinCount);
                    src.connect(an);
                    (function upd() {
                        an.getByteFrequencyData(buf);
                        const v = buf.reduce((a,b)=>a+b,0)/buf.length;
                        window.setMicActive(v > 8);
                        requestAnimationFrame(upd);
                    })();
                } catch(e) { console.warn('Audio meter error', e); window.setMicActive(true); }
            } catch (err) {
                alert('No se pudo acceder al micrófono: ' + err.message);
            }
        };

        // Exponer funciones sencillas para que Unity o pruebas las llamen
        window.__test_setMicActive = window.setMicActive;
        window.__test_setSpeakerActive = window.setSpeakerActive;

        // Accesibilidad y binding de evento: solicitar micrófono al hacer clic en el icono
        document.addEventListener('DOMContentLoaded', function() {
            try {
                var mic = document.getElementById('micCardSmall');
                if (mic) {
                    mic.setAttribute('role', 'button');
                    mic.setAttribute('tabindex', '0');
                    mic.style.cursor = 'pointer';
                    mic.addEventListener('click', function(e) {
                        // User gesture -> prompt for mic
                        window.requestLocalMic();
                    });
                    mic.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            window.requestLocalMic();
                        }
                    });
                }
            } catch (e) { console.warn('mic binding failed', e); }
        });

        // Utility: check microphone permission state (returns Promise)
        window.checkMicPermission = function() {
            if (!navigator.permissions || !navigator.permissions.query) return Promise.resolve('unknown');
            return navigator.permissions.query({ name: 'microphone' }).then(function(s) { return s.state; }).catch(function() { return 'unknown'; });
        };
    </script>
    
    <!-- ===== LIVEKIT INTEGRATION ===== -->
    <script>
        // ===== SISTEMA DE AUDIO LIVEKIT (REEMPLAZO DE PEERJS) =====
        window.LiveKitManager = (function() {
            const LivekitClient = window.LivekitClient;
            
            // Estado del manager
            let room = null;
            let localAudioTrack = null;
            let isConnected = false;
            let currentRoomName = null;
            let participantIdentity = null;
            let unityToken = null;
            
            // Configuración
            const config = {
                serverUrl: '{{ config("livekit.host", "ws://localhost:7880") }}',
                apiEndpoint: '/api/livekit/token',
                reconnectAttempts: 3,
                reconnectDelay: 2000
            };
            
            // Audio elements para participantes remotos
            const remoteAudioElements = new Map();
            
            /**
             * Obtener token de LiveKit desde Laravel
             */
            async function getToken(roomName, participantName, identity) {
                try {
                    addDebugLog('api', 'LIVEKIT', `Solicitando token para sala: ${roomName}`);
                    
                    const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
                    if (unityToken) headers['Authorization'] = 'Bearer ' + unityToken;
                    const response = await fetch(config.apiEndpoint, {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify({
                            room_name: roomName,
                            participant_name: participantName,
                            participant_identity: identity
                        })
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    addDebugLog('api', 'LIVEKIT', 'Token obtenido exitosamente', { room: roomName });
                    return data;
                } catch (error) {
                    addDebugLog('error', 'LIVEKIT', `Error obteniendo token: ${error.message}`);
                    throw error;
                }
            }
            
            /**
             * Conectar a una sala de LiveKit
             */
            async function connect(roomName, participantName, identity, token) {
                if (isConnected && currentRoomName === roomName) {
                    addDebugLog('warning', 'LIVEKIT', 'Ya conectado a esta sala');
                    return true;
                }
                
                // Desconectar si está en otra sala
                if (isConnected) {
                    await disconnect();
                }
                
                try {
                    addDebugLog('phase', 'LIVEKIT', `Conectando a sala: ${roomName}`);
                    
                    // Guardar token de Unity para futuras peticiones
                    unityToken = token;
                    
                    // Obtener token de LiveKit
                    const tokenData = await getToken(roomName, participantName, identity);
                    
                    // Crear instancia de Room
                    room = new LivekitClient.Room({
                        adaptiveStream: true,
                        dynacast: true,
                        videoCaptureDefaults: {
                            resolution: { width: 640, height: 480, frameRate: 15 }
                        },
                        audioCaptureDefaults: {
                            echoCancellation: true,
                            noiseSuppression: true,
                            autoGainControl: true
                        }
                    });
                    
                    // Configurar event handlers
                    setupRoomEvents();
                    
                    // Conectar
                    await room.connect(tokenData.url || config.serverUrl, tokenData.token);
                    
                    isConnected = true;
                    currentRoomName = roomName;
                    participantIdentity = identity;
                    
                    addDebugLog('phase', 'LIVEKIT', `Conectado exitosamente a sala: ${roomName}`, {
                        participants: room.numParticipants,
                        localIdentity: room.localParticipant?.identity
                    });
                    
                    // Notificar a Unity
                    notifyUnity('LiveKitConnected', {
                        roomName: roomName,
                        identity: identity,
                        participants: room.numParticipants
                    });
                    
                    return true;
                } catch (error) {
                    addDebugLog('error', 'LIVEKIT', `Error conectando: ${error.message}`);
                    notifyUnity('LiveKitError', { error: error.message, type: 'connection' });
                    return false;
                }
            }
            
            /**
             * Configurar eventos del Room
             */
            function setupRoomEvents() {
                if (!room) return;
                
                // Participante conectado
                room.on(LivekitClient.RoomEvent.ParticipantConnected, (participant) => {
                    addDebugLog('event', 'LIVEKIT', `Participante conectado: ${participant.identity}`);
                    notifyUnity('ParticipantJoined', {
                        identity: participant.identity,
                        name: participant.name
                    });
                });
                
                // Participante desconectado
                room.on(LivekitClient.RoomEvent.ParticipantDisconnected, (participant) => {
                    addDebugLog('event', 'LIVEKIT', `Participante desconectado: ${participant.identity}`);
                    removeRemoteAudio(participant.identity);
                    notifyUnity('ParticipantLeft', { identity: participant.identity });
                });
                
                // Track suscrito (audio/video de otros participantes)
                room.on(LivekitClient.RoomEvent.TrackSubscribed, (track, publication, participant) => {
                    addDebugLog('event', 'LIVEKIT', `Track suscrito: ${track.kind} de ${participant.identity}`);
                    
                    if (track.kind === 'audio') {
                        attachRemoteAudio(track, participant.identity);
                        window.setSpeakerActive(true);
                    }
                });
                
                // Track desuscrito
                room.on(LivekitClient.RoomEvent.TrackUnsubscribed, (track, publication, participant) => {
                    addDebugLog('event', 'LIVEKIT', `Track desuscrito: ${track.kind} de ${participant.identity}`);
                    
                    if (track.kind === 'audio') {
                        removeRemoteAudio(participant.identity);
                    }
                });
                
                // Track publicado localmente
                room.on(LivekitClient.RoomEvent.LocalTrackPublished, (publication, participant) => {
                    addDebugLog('event', 'LIVEKIT', `Track local publicado: ${publication.kind}`);
                    if (publication.kind === 'audio') {
                        window.setMicActive(true);
                    }
                });
                
                // Track local despublicado
                room.on(LivekitClient.RoomEvent.LocalTrackUnpublished, (publication, participant) => {
                    addDebugLog('event', 'LIVEKIT', `Track local despublicado: ${publication.kind}`);
                    if (publication.kind === 'audio') {
                        window.setMicActive(false);
                    }
                });
                
                // Reconexión
                room.on(LivekitClient.RoomEvent.Reconnecting, () => {
                    addDebugLog('warning', 'LIVEKIT', 'Reconectando...');
                    notifyUnity('LiveKitReconnecting', {});
                });
                
                room.on(LivekitClient.RoomEvent.Reconnected, () => {
                    addDebugLog('phase', 'LIVEKIT', 'Reconectado exitosamente');
                    notifyUnity('LiveKitReconnected', {});
                });
                
                // Desconexión
                room.on(LivekitClient.RoomEvent.Disconnected, (reason) => {
                    addDebugLog('warning', 'LIVEKIT', `Desconectado: ${reason}`);
                    isConnected = false;
                    cleanupAudio();
                    notifyUnity('LiveKitDisconnected', { reason: reason });
                });
                
                // Datos recibidos (para mensajes de Unity)
                room.on(LivekitClient.RoomEvent.DataReceived, (payload, participant, kind) => {
                    try {
                        const decoder = new TextDecoder();
                        const message = JSON.parse(decoder.decode(payload));
                        addDebugLog('event', 'LIVEKIT', `Datos recibidos de ${participant?.identity}`, message);
                        notifyUnity('DataReceived', {
                            from: participant?.identity,
                            data: message
                        });
                    } catch (e) {
                        addDebugLog('error', 'LIVEKIT', 'Error parseando datos recibidos');
                    }
                });
                
                // Nivel de audio activo (para indicadores visuales)
                room.on(LivekitClient.RoomEvent.ActiveSpeakersChanged, (speakers) => {
                    const speakerIds = speakers.map(s => s.identity);
                    notifyUnity('ActiveSpeakersChanged', { speakers: speakerIds });
                    
                    // Actualizar indicador de speaker si hay audio entrante
                    window.setSpeakerActive(speakers.length > 0);
                });
            }
            
            /**
             * Adjuntar audio remoto
             */
            function attachRemoteAudio(track, identity) {
                // Remover elemento anterior si existe
                removeRemoteAudio(identity);
                
                // Crear elemento de audio
                const audioElement = document.createElement('audio');
                audioElement.id = `livekit-audio-${identity}`;
                audioElement.autoplay = true;
                audioElement.style.display = 'none';
                document.body.appendChild(audioElement);
                
                // Adjuntar track
                track.attach(audioElement);
                remoteAudioElements.set(identity, audioElement);
                
                addDebugLog('info', 'LIVEKIT', `Audio adjuntado para: ${identity}`);
            }
            
            /**
             * Remover audio remoto
             */
            function removeRemoteAudio(identity) {
                const audioElement = remoteAudioElements.get(identity);
                if (audioElement) {
                    audioElement.srcObject = null;
                    audioElement.remove();
                    remoteAudioElements.delete(identity);
                    addDebugLog('info', 'LIVEKIT', `Audio removido para: ${identity}`);
                }
                
                // Actualizar indicador si no hay más audio
                if (remoteAudioElements.size === 0) {
                    window.setSpeakerActive(false);
                }
            }
            
            /**
             * Limpiar todos los elementos de audio
             */
            function cleanupAudio() {
                remoteAudioElements.forEach((element, identity) => {
                    element.srcObject = null;
                    element.remove();
                });
                remoteAudioElements.clear();
                window.setMicActive(false);
                window.setSpeakerActive(false);
            }
            
            /**
             * Publicar audio del micrófono
             */
            async function publishAudio() {
                if (!room || !isConnected) {
                    addDebugLog('error', 'LIVEKIT', 'No conectado a ninguna sala');
                    return false;
                }
                
                try {
                    addDebugLog('phase', 'LIVEKIT', 'Publicando audio del micrófono...');
                    
                    // Habilitar micrófono y publicar
                    await room.localParticipant.setMicrophoneEnabled(true);
                    
                    addDebugLog('phase', 'LIVEKIT', 'Audio publicado exitosamente');
                    window.setMicActive(true);
                    notifyUnity('AudioPublished', { enabled: true });
                    
                    return true;
                } catch (error) {
                    addDebugLog('error', 'LIVEKIT', `Error publicando audio: ${error.message}`);
                    notifyUnity('LiveKitError', { error: error.message, type: 'audio' });
                    return false;
                }
            }
            
            /**
             * Detener publicación de audio
             */
            async function unpublishAudio() {
                if (!room || !isConnected) return;
                
                try {
                    await room.localParticipant.setMicrophoneEnabled(false);
                    window.setMicActive(false);
                    addDebugLog('info', 'LIVEKIT', 'Audio despublicado');
                    notifyUnity('AudioPublished', { enabled: false });
                } catch (error) {
                    addDebugLog('error', 'LIVEKIT', `Error despublicando audio: ${error.message}`);
                }
            }
            
            /**
             * Toggle mute del micrófono
             */
            async function toggleMute() {
                if (!room || !isConnected) return false;
                
                const isMuted = room.localParticipant.isMicrophoneEnabled;
                await room.localParticipant.setMicrophoneEnabled(!isMuted);
                window.setMicActive(!isMuted);
                
                addDebugLog('info', 'LIVEKIT', `Micrófono ${!isMuted ? 'activado' : 'silenciado'}`);
                notifyUnity('MicrophoneToggled', { enabled: !isMuted });
                
                return !isMuted;
            }
            
            /**
             * Enviar datos a otros participantes
             */
            async function sendData(data, reliable = true) {
                if (!room || !isConnected) {
                    addDebugLog('error', 'LIVEKIT', 'No conectado para enviar datos');
                    return false;
                }
                
                try {
                    const encoder = new TextEncoder();
                    const payload = encoder.encode(JSON.stringify(data));
                    
                    await room.localParticipant.publishData(
                        payload,
                        reliable ? LivekitClient.DataPacket_Kind.RELIABLE : LivekitClient.DataPacket_Kind.LOSSY
                    );
                    
                    addDebugLog('info', 'LIVEKIT', 'Datos enviados', data);
                    return true;
                } catch (error) {
                    addDebugLog('error', 'LIVEKIT', `Error enviando datos: ${error.message}`);
                    return false;
                }
            }
            
            /**
             * Desconectar de la sala
             */
            async function disconnect() {
                if (!room) return;
                
                try {
                    addDebugLog('phase', 'LIVEKIT', 'Desconectando de la sala...');
                    
                    cleanupAudio();
                    await room.disconnect();
                    room = null;
                    isConnected = false;
                    currentRoomName = null;
                    
                    addDebugLog('phase', 'LIVEKIT', 'Desconectado exitosamente');
                } catch (error) {
                    addDebugLog('error', 'LIVEKIT', `Error desconectando: ${error.message}`);
                }
            }
            
            /**
             * Notificar a Unity
             */
            function notifyUnity(eventName, data) {
                if (window.unityInstance && window.unityInstance.SendMessage) {
                    try {
                        window.unityInstance.SendMessage(
                            'LiveKitManager',
                            'OnLiveKitEvent',
                            JSON.stringify({ event: eventName, data: data })
                        );
                    } catch (e) {
                        // Unity puede no estar listo
                    }
                }
                
                // También disparar evento DOM para otros listeners
                window.dispatchEvent(new CustomEvent('livekit:' + eventName, { detail: data }));
            }
            
            /**
             * Obtener estado actual
             */
            function getState() {
                return {
                    isConnected: isConnected,
                    roomName: currentRoomName,
                    identity: participantIdentity,
                    participants: room ? room.numParticipants : 0,
                    isMicEnabled: room?.localParticipant?.isMicrophoneEnabled || false
                };
            }
            
            /**
             * Obtener lista de participantes
             */
            function getParticipants() {
                if (!room) return [];
                
                const participants = [];
                room.remoteParticipants.forEach((participant, identity) => {
                    participants.push({
                        identity: identity,
                        name: participant.name,
                        isSpeaking: participant.isSpeaking,
                        audioLevel: participant.audioLevel
                    });
                });
                
                return participants;
            }
            
            // API pública
            return {
                connect: connect,
                disconnect: disconnect,
                publishAudio: publishAudio,
                unpublishAudio: unpublishAudio,
                toggleMute: toggleMute,
                sendData: sendData,
                getState: getState,
                getParticipants: getParticipants,
                
                // Acceso directo al room para casos avanzados
                getRoom: function() { return room; }
            };
        })();
        
        // Exponer globalmente para Unity
        window.livekit = window.LiveKitManager;
        
        // Funciones de conveniencia para Unity (legacy compatibility)
        window.connectToLiveKitRoom = async function(roomName, participantName, identity, token) {
            return await window.LiveKitManager.connect(roomName, participantName, identity, token);
        };
        
        window.disconnectFromLiveKit = async function() {
            return await window.LiveKitManager.disconnect();
        };
        
        window.publishLiveKitAudio = async function() {
            return await window.LiveKitManager.publishAudio();
        };
        
        window.toggleLiveKitMute = async function() {
            return await window.LiveKitManager.toggleMute();
        };
        
        window.sendLiveKitData = async function(data) {
            return await window.LiveKitManager.sendData(data);
        };
        
        window.getLiveKitState = function() {
            return window.LiveKitManager.getState();
        };
        
        addDebugLog('phase', 'LIVEKIT', 'LiveKit Manager inicializado y listo');
        // ===== FIN SISTEMA DE AUDIO LIVEKIT =====

        // ===== UI PANEL CHAT DE VOZ (habilitar, usar, ver conexión) =====
        var voicePanelConnectionState = 'disconnected';
        function setVoiceConnectionState(s) { voicePanelConnectionState = s; }

        function updateVoiceChatPanel() {
            const dot = document.getElementById('voice-status-dot');
            const text = document.getElementById('voice-status-text');
            const btnEnable = document.getElementById('voice-btn-enable');
            const btnMute = document.getElementById('voice-btn-mute');
            const btnDisconnect = document.getElementById('voice-btn-disconnect');
            const btnTest = document.getElementById('voice-btn-test');
            const roomNameEl = document.getElementById('voice-room-name');
            const stateBadge = document.getElementById('voice-connection-state');

            if (roomNameEl) roomNameEl.textContent = '—';
            if (stateBadge) { stateBadge.textContent = 'Desconectado'; stateBadge.className = 'voice-connection-state disconnected'; }

            if (!window.LiveKitManager) {
                refreshVoiceMicList();
                return;
            }
            const state = window.LiveKitManager.getState();

            if (state.isConnected) {
                setVoiceConnectionState('connected');
                if (dot) { dot.className = 'dot connected'; }
                if (roomNameEl) roomNameEl.textContent = state.roomName || 'sala';
                if (stateBadge) { stateBadge.textContent = 'Conectado'; stateBadge.className = 'voice-connection-state connected'; }
                if (text) {
                    const participants = window.LiveKitManager.getParticipants ? window.LiveKitManager.getParticipants().length : (state.participants || 0);
                    text.textContent = (state.roomName ? 'Sala "' + state.roomName + '"' : 'Sala') + ' · ' + participants + ' participante(s). Mic: ' + (state.isMicEnabled ? 'activado' : 'silenciado');
                }
                if (btnEnable) { btnEnable.disabled = true; btnEnable.textContent = 'Conectado'; }
                if (btnTest) { btnTest.style.display = 'none'; }
                if (btnMute) { btnMute.disabled = false; btnMute.textContent = state.isMicEnabled ? 'Silenciar mic' : 'Activar mic'; btnMute.classList.toggle('muted', !state.isMicEnabled); }
                if (btnDisconnect) btnDisconnect.disabled = false;
            } else {
                setVoiceConnectionState(voicePanelConnectionState === 'reconnecting' ? 'reconnecting' : 'disconnected');
                if (dot) { dot.className = voicePanelConnectionState === 'reconnecting' ? 'dot connecting' : 'dot'; }
                if (stateBadge) {
                    stateBadge.textContent = voicePanelConnectionState === 'reconnecting' ? 'Reconectando' : 'Desconectado';
                    stateBadge.className = 'voice-connection-state ' + (voicePanelConnectionState === 'reconnecting' ? 'reconnecting' : 'disconnected');
                }
                if (text) text.textContent = 'Sin conexión a sala de voz. Usa "Habilitar micrófono" y entra a una sesión desde Unity.';
                if (btnEnable) { btnEnable.disabled = false; btnEnable.textContent = 'Habilitar micrófono'; }
                if (btnTest) { btnTest.style.display = 'inline-block'; btnTest.disabled = false; }
                if (btnMute) { btnMute.disabled = true; btnMute.textContent = 'Silenciar / Activar mic'; btnMute.classList.remove('muted'); }
                if (btnDisconnect) btnDisconnect.disabled = true;
            }
            refreshVoiceMicList();
        }

        function refreshVoiceMicList() {
            const select = document.getElementById('voice-mic-select');
            const label = document.getElementById('voice-mics-label');
            if (!select || !label) return;
            if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
                select.style.display = 'none';
                return;
            }
            navigator.mediaDevices.enumerateDevices().then(function(devices) {
                var mics = devices.filter(function(d) { return d.kind === 'audioinput'; });
                select.innerHTML = '';
                if (mics.length === 0) {
                    select.style.display = 'none';
                    label.textContent = 'Micrófonos: concede permiso para listar.';
                    return;
                }
                label.textContent = 'Micrófonos disponibles:';
                select.style.display = 'block';
                mics.forEach(function(mic, i) {
                    var opt = document.createElement('option');
                    opt.value = mic.deviceId;
                    opt.textContent = mic.label || ('Micrófono ' + (i + 1));
                    select.appendChild(opt);
                });
            }).catch(function() {
                select.style.display = 'none';
                label.textContent = 'Micrófonos: no se pudo listar.';
            });
        }

        window.voiceChatPanelEnable = function() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                addDebugLog('error', 'VOICE_PANEL', 'Tu navegador no soporta acceso al micrófono');
                return;
            }
            const text = document.getElementById('voice-status-text');
            if (text) text.textContent = 'Solicitando permiso de micrófono...';
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(function(stream) {
                    stream.getTracks().forEach(function(t) { t.stop(); });
                    refreshVoiceMicList();
                    var state = window.LiveKitManager ? window.LiveKitManager.getState() : {};
                    if (state.isConnected && window.LiveKitManager.publishAudio) {
                        if (text) text.textContent = 'Permiso concedido. Conectando micrófono a la sala...';
                        window.LiveKitManager.publishAudio().then(function() {
                            if (text) text.textContent = 'Micrófono habilitado en la sala.';
                            updateVoiceChatPanel();
                        }).catch(function(err) {
                            if (text) text.textContent = 'Permiso concedido. Error al publicar audio: ' + (err && err.message ? err.message : '');
                            updateVoiceChatPanel();
                        });
                    } else {
                        if (text) text.textContent = 'Permiso concedido. Entra a una sesión desde Unity para usar el micrófono en la sala.';
                        addDebugLog('phase', 'VOICE_PANEL', 'Micrófono autorizado; listo para cuando Unity una a la sala.');
                        updateVoiceChatPanel();
                    }
                })
                .catch(function(err) {
                    if (text) text.textContent = 'Estado: Desconectado (permiso denegado)';
                    addDebugLog('error', 'VOICE_PANEL', 'Error permiso micrófono: ' + err.message);
                    updateVoiceChatPanel();
                });
        };

        window.voiceChatPanelToggleMute = function() {
            if (window.LiveKitManager && window.LiveKitManager.toggleMute) {
                window.LiveKitManager.toggleMute().then(function() { updateVoiceChatPanel(); });
            }
        };

        window.voiceChatPanelDisconnect = function() {
            if (window.LiveKitManager && window.LiveKitManager.disconnect) {
                window.LiveKitManager.disconnect().then(function() { updateVoiceChatPanel(); });
            }
        };

        window.voiceChatPanelTestConnect = function() {
            if (!window.LiveKitManager || !window.LiveKitManager.connect) return;
            var state = window.LiveKitManager.getState();
            if (state.isConnected) {
                addDebugLog('warning', 'VOICE_PANEL', 'Ya estás conectado a una sala.');
                return;
            }
            function getQueryParam(name) {
                var m = new RegExp('[?&]' + name + '=([^&#]*)').exec(window.location.href);
                return m ? decodeURIComponent(m[1]) : '';
            }
            var token = getQueryParam('token') || '';
            var roomName = 'sala-prueba';
            var participantName = 'Usuario';
            var identity = 'user-' + Date.now();
            var text = document.getElementById('voice-status-text');
            if (text) text.textContent = 'Conectando a sala de prueba (LiveKit)...';
            addDebugLog('phase', 'VOICE_PANEL', 'Conectando a sala de prueba: ' + roomName);
            window.LiveKitManager.connect(roomName, participantName, identity, token).then(function(ok) {
                if (ok) addDebugLog('phase', 'VOICE_PANEL', 'Conectado a sala de prueba.');
                else if (text) text.textContent = 'Error al conectar. Revisa consola y que LiveKit esté en ws://localhost:7880';
                updateVoiceChatPanel();
            }).catch(function(err) {
                addDebugLog('error', 'VOICE_PANEL', 'Error conectando a sala de prueba: ' + (err && err.message ? err.message : err));
                if (text) text.textContent = 'Error: ' + (err && err.message ? err.message : 'No se pudo conectar al servidor LiveKit');
                updateVoiceChatPanel();
            });
        };

        ['LiveKitConnected', 'LiveKitDisconnected', 'LiveKitReconnecting', 'LiveKitReconnected', 'LiveKitError'].forEach(function(ev) {
            window.addEventListener('livekit:' + ev, function() {
                if (ev === 'LiveKitReconnecting') {
                    setVoiceConnectionState('reconnecting');
                    var t = document.getElementById('voice-status-text');
                    if (t) t.textContent = 'Reconectando...';
                    var d = document.getElementById('voice-status-dot');
                    if (d) d.className = 'dot connecting';
                }
                if (ev === 'LiveKitDisconnected' || ev === 'LiveKitError') setVoiceConnectionState('disconnected');
                if (ev === 'LiveKitReconnected') setVoiceConnectionState('connected');
                updateVoiceChatPanel();
                if (ev === 'LiveKitConnected' && window.LiveKitManager && window.LiveKitManager.publishAudio) {
                    window.LiveKitManager.publishAudio().then(function() { updateVoiceChatPanel(); }).catch(function() { updateVoiceChatPanel(); });
                }
            });
        });
        setInterval(updateVoiceChatPanel, 2000);
        updateVoiceChatPanel();
    </script>
    <script>
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

            // Extraer token y sesión de la URL
            function getQueryParameter(name) {
                const url = window.location.href;
                name = name.replace(/[\[\]]/g, '\\$&');
                const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
                const results = regex.exec(url);
                if (!results) return null;
                if (!results[2]) return '';
                return decodeURIComponent(results[2].replace(/\+/g, ' '));
            }

            const token = getQueryParameter('token');
            let sessionId = getQueryParameter('session');

            addDebugLog('phase', 'UNITY', 'Parámetros de entrada extraídos', {
                hasToken: !!token,
                sessionId: sessionId
            });
                // DEBUG: Imprimir URL completa y parámetros
                console.log("[ENTRY] URL completa: " + window.location.href);
                console.log("[ENTRY] URL search: " + window.location.search);
                console.log("[ENTRY] Parámetro 'token': ", token ? "ENCONTRADO" : "NO ENCONTRADO");
                console.log("[ENTRY] Parámetro 'session': ", sessionId ? "ENCONTRADO (" + sessionId + ")" : "NO ENCONTRADO");

                // Alternativamente, si falla getQueryParameter, intentar parsearlo manualmente
                if (!sessionId && window.location.search) {
                    const params = new URLSearchParams(window.location.search);
                    sessionId = params.get('session') || null;
                    console.log("[ENTRY] SessionId recuperado con URLSearchParams: " + sessionId);
                }

                // Si hay token, obtener los datos de entrada (sessionId puede estar en el token si no está en URL)
                if (token) {
                addDebugLog('phase', 'UNITY', 'Obteniendo datos de sesión desde Laravel...');
                    console.log("[DEBUG] Llamando a /api/unity-entry-info con token:", token);
                
                fetch('/api/unity-entry-info?token=' + encodeURIComponent(token), {
                        credentials: 'same-origin',
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        addDebugLog('phase', 'UNITY', 'Datos de sesión obtenidos', {
                            userName: data.data.user.name,
                            roleName: data.data.role.nombre,
                            sessionId: data.data.session.id
                        });

                        const sessionPayload = {
                            user: data.data.user,
                            session: data.data.session,
                            role: data.data.role
                        };

                        // Intentar enviar inmediatamente si Unity ya está listo
                        const trySendToUnity = () => {
                            if (unityInstance && unityInstance.SendMessage) {
                                console.log("Enviando datos de sesión a Unity...");
                                window.sendToUnity(sessionPayload);
                                return true;
                            }
                            return false;
                        };

                        if (!trySendToUnity()) {
                            // Reintentar varias veces hasta que Unity esté listo
                            let retries = 0;
                            const maxRetries = 20;
                            const intervalId = setInterval(() => {
                                retries++;
                                if (trySendToUnity() || retries >= maxRetries) {
                                    clearInterval(intervalId);
                                    if (retries >= maxRetries) {
                                        console.warn("Unity no estuvo listo para recibir datos a tiempo.");
                                    }
                                }
                            }, 250);
                        }
                    } else {
                        addDebugLog('error', 'UNITY', 'Error obteniendo datos de sesión', data);
                    }
                })
                .catch(error => {
                    addDebugLog('error', 'UNITY', 'Error fetching session data', { error: error.message });
                    console.error("Error obteniendo datos de sesión:", error);
                });
            } else {
                addDebugLog('warning', 'UNITY', 'No hay token o sesión en la URL');
                // Si no hay token, usar configuración de servidor si existe
                if (unityInstance && unityInstance.Module) {
                    unityInstance.Module.onRuntimeInitialized = function() {
                        console.log("Runtime de Unity inicializado");
                    };
                }
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
