<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LiveKit - Sala de Prueba</title>
    <script src="https://cdn.jsdelivr.net/npm/livekit-client@2.11.0/dist/livekit-client.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: #fff;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #4CAF50;
        }
        
        .subtitle {
            text-align: center;
            color: #888;
            margin-bottom: 30px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .card h2 {
            margin-bottom: 16px;
            font-size: 18px;
            color: #4CAF50;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        label {
            display: block;
            margin-bottom: 6px;
            color: #aaa;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: #fff;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45a049;
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        .btn-danger:hover {
            background: #d32f2f;
        }
        
        .btn-secondary {
            background: #2196F3;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #1976D2;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .status.disconnected {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.5);
        }
        
        .status.connected {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
        }
        
        .status.connecting {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.5);
        }
        
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .status.disconnected .status-dot { background: #f44336; }
        .status.connected .status-dot { background: #4CAF50; }
        .status.connecting .status-dot { background: #FFC107; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .participants {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .participant {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .participant.speaking {
            border-color: #4CAF50;
            box-shadow: 0 0 20px rgba(76, 175, 80, 0.3);
        }
        
        .participant.local {
            border-color: #2196F3;
        }
        
        .participant-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 12px;
        }
        
        .participant-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .participant-status {
            font-size: 12px;
            color: #888;
        }
        
        .participant-status.muted {
            color: #f44336;
        }
        
        .audio-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .mic-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 20px;
            font-size: 14px;
        }
        
        .mic-indicator.active {
            background: rgba(76, 175, 80, 0.3);
            color: #4CAF50;
        }
        
        .mic-indicator.muted {
            background: rgba(244, 67, 54, 0.3);
            color: #f44336;
        }
        
        .logs {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            padding: 16px;
            max-height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        
        .log-entry {
            padding: 4px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-entry .time {
            color: #666;
            margin-right: 8px;
        }
        
        .log-entry.info { color: #4CAF50; }
        .log-entry.error { color: #f44336; }
        .log-entry.warning { color: #FFC107; }
        .log-entry.event { color: #2196F3; }
        
        .server-info {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }

        .volume-meter {
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .volume-meter-fill {
            height: 100%;
            background: #4CAF50;
            width: 0%;
            transition: width 0.1s;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎙️ LiveKit Voice Chat</h1>
        <p class="subtitle">Sala de prueba para comunicación de voz en tiempo real</p>
        
        <!-- Connection Status -->
        <div class="card">
            <div id="status" class="status disconnected">
                <div class="status-dot"></div>
                <span id="status-text">Desconectado</span>
            </div>
            
            <!-- Connection Form -->
            <div id="connect-form">
                <div class="form-group">
                    <label for="room-name">Nombre de la Sala</label>
                    <input type="text" id="room-name" value="sala-prueba" placeholder="Ej: sala-prueba">
                </div>
                <div class="form-group">
                    <label for="user-name">Tu Nombre</label>
                    <input type="text" id="user-name" value="" placeholder="Ej: Juan">
                </div>
                <button class="btn btn-primary" id="btn-connect" onclick="connectToRoom()">
                    🔗 Conectar a la Sala
                </button>
            </div>
            
            <!-- Connected Controls -->
            <div id="connected-controls" style="display: none;">
                <div class="audio-controls">
                    <button class="btn btn-secondary" id="btn-mic" onclick="toggleMicrophone()">
                        🎤 Activar Micrófono
                    </button>
                    <button class="btn btn-primary" id="btn-enable-audio" onclick="enableAudioPlayback()">
                        🔊 Habilitar Audio
                    </button>
                    <button class="btn btn-danger" onclick="disconnectFromRoom()">
                        🚪 Salir de la Sala
                    </button>
                </div>
                <div class="mic-indicator muted" id="mic-status">
                    <span>🎤</span>
                    <span id="mic-status-text">Micrófono apagado</span>
                </div>
            </div>
            
            <div class="server-info">
                Servidor: <code>{{ config('livekit.host', 'ws://localhost:7880') }}</code>
            </div>
        </div>
        
        <!-- Participants -->
        <div class="card">
            <h2>👥 Participantes en la Sala</h2>
            <div id="participants" class="participants">
                <p style="color: #666; grid-column: 1/-1;">Conecta a una sala para ver participantes...</p>
            </div>
        </div>
        
        <!-- Logs -->
        <div class="card">
            <h2>📋 Registro de Eventos</h2>
            <div id="logs" class="logs">
                <div class="log-entry info">
                    <span class="time">[--:--:--]</span>
                    Sistema listo. Ingresa tu nombre y conecta a una sala.
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // LiveKit Room instance
        let room = null;
        let localParticipant = null;
        let isMicEnabled = false;
        
        // DOM Elements
        const statusEl = document.getElementById('status');
        const statusTextEl = document.getElementById('status-text');
        const connectFormEl = document.getElementById('connect-form');
        const connectedControlsEl = document.getElementById('connected-controls');
        const participantsEl = document.getElementById('participants');
        const logsEl = document.getElementById('logs');
        const micStatusEl = document.getElementById('mic-status');
        const micStatusTextEl = document.getElementById('mic-status-text');
        const btnMicEl = document.getElementById('btn-mic');
        const btnConnectEl = document.getElementById('btn-connect');
        
        // Set random default name
        document.getElementById('user-name').value = 'Usuario_' + Math.floor(Math.random() * 1000);
        
        // Logging function
        function log(message, type = 'info') {
            const time = new Date().toLocaleTimeString();
            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;
            entry.innerHTML = `<span class="time">[${time}]</span> ${message}`;
            logsEl.appendChild(entry);
            logsEl.scrollTop = logsEl.scrollHeight;
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
        
        // Update connection status UI
        function setStatus(status, text) {
            statusEl.className = `status ${status}`;
            statusTextEl.textContent = text;
        }
        
        // Get token from Laravel backend
        async function getToken(roomName, userName) {
            try {
                const response = await fetch('/api/livekit/token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        room_name: roomName,
                        participant_name: userName,
                        participant_identity: 'user_' + Date.now()
                    })
                });
                
                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Error obteniendo token');
                }
                
                return await response.json();
            } catch (error) {
                throw error;
            }
        }
        
        // Connect to room
        async function connectToRoom() {
            const roomName = document.getElementById('room-name').value.trim();
            const userName = document.getElementById('user-name').value.trim();
            
            if (!roomName || !userName) {
                log('Por favor ingresa nombre de sala y tu nombre', 'error');
                return;
            }
            
            try {
                setStatus('connecting', 'Conectando...');
                btnConnectEl.disabled = true;
                log(`Solicitando acceso a sala "${roomName}"...`);
                
                // Get token from backend
                const tokenData = await getToken(roomName, userName);
                log('Token obtenido correctamente', 'info');
                
                // Create room instance
                room = new LivekitClient.Room({
                    adaptiveStream: true,
                    dynacast: true,
                    audioCaptureDefaults: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    }
                });
                
                // Setup event listeners
                setupRoomEvents();
                
                // Connect
                log(`Conectando a ${tokenData.url}...`);
                await room.connect(tokenData.url, tokenData.token);
                
                localParticipant = room.localParticipant;
                
                setStatus('connected', `Conectado a "${roomName}"`);
                connectFormEl.style.display = 'none';
                connectedControlsEl.style.display = 'block';
                
                log(`✅ Conectado como "${userName}"`, 'info');
                
                // Handle existing participants in the room
                handleExistingParticipants();
                updateParticipantsList();
                
                // Check if audio playback is allowed
                if (!room.canPlaybackAudio) {
                    log('⚠️ Haz clic en "Habilitar Audio" para escuchar a otros participantes', 'warning');
                }
                
            } catch (error) {
                setStatus('disconnected', 'Error de conexión');
                btnConnectEl.disabled = false;
                log(`❌ Error: ${error.message}`, 'error');
            }
        }
        
        // Attach audio track to an audio element
        function attachAudioTrack(track, participantIdentity) {
            // Remove existing audio element if any
            const existingEl = document.getElementById(`audio-${participantIdentity}`);
            if (existingEl) {
                existingEl.remove();
            }
            
            const audioEl = document.createElement('audio');
            audioEl.id = `audio-${participantIdentity}`;
            audioEl.autoplay = true;
            audioEl.playsInline = true;
            
            // Attach the track
            track.attach(audioEl);
            document.body.appendChild(audioEl);
            
            // Try to play (handle autoplay restrictions)
            audioEl.play().catch(e => {
                log(`⚠️ Audio bloqueado para ${participantIdentity}. Haz clic en la página para habilitar.`, 'warning');
            });
            
            log(`🔊 Audio adjuntado para ${participantIdentity}`, 'info');
        }
        
        // Handle existing participants when joining a room
        function handleExistingParticipants() {
            room.remoteParticipants.forEach((participant, identity) => {
                log(`👤 Participante existente: ${identity}`, 'event');
                
                // Subscribe to their existing audio tracks
                participant.audioTrackPublications.forEach((publication) => {
                    if (publication.track && publication.isSubscribed) {
                        log(`🔊 Suscribiendo a audio existente de ${identity}`, 'event');
                        attachAudioTrack(publication.track, identity);
                    } else if (publication.trackSid) {
                        log(`📡 Track de audio pendiente de ${identity}`, 'info');
                    }
                });
            });
        }
        
        // Setup room event listeners
        function setupRoomEvents() {
            room.on(LivekitClient.RoomEvent.ParticipantConnected, (participant) => {
                log(`👤 ${participant.identity} se unió a la sala`, 'event');
                updateParticipantsList();
            });
            
            room.on(LivekitClient.RoomEvent.ParticipantDisconnected, (participant) => {
                log(`👤 ${participant.identity} salió de la sala`, 'event');
                // Remove their audio element
                const audioEl = document.getElementById(`audio-${participant.identity}`);
                if (audioEl) audioEl.remove();
                updateParticipantsList();
            });
            
            room.on(LivekitClient.RoomEvent.TrackSubscribed, (track, publication, participant) => {
                log(`📡 Track suscrito: ${track.kind} de ${participant.identity}`, 'event');
                if (track.kind === 'audio') {
                    attachAudioTrack(track, participant.identity);
                }
                updateParticipantsList();
            });
            
            room.on(LivekitClient.RoomEvent.TrackUnsubscribed, (track, publication, participant) => {
                if (track.kind === 'audio') {
                    log(`🔇 ${participant.identity} dejó de transmitir audio`, 'event');
                    const audioEl = document.getElementById(`audio-${participant.identity}`);
                    if (audioEl) audioEl.remove();
                }
                updateParticipantsList();
            });
            
            // Important: Handle track published by remote participants
            room.on(LivekitClient.RoomEvent.TrackPublished, (publication, participant) => {
                log(`📢 ${participant.identity} publicó track: ${publication.kind}`, 'event');
                updateParticipantsList();
            });
            
            room.on(LivekitClient.RoomEvent.LocalTrackPublished, (publication) => {
                if (publication.kind === 'audio') {
                    log('🎤 Tu micrófono está transmitiendo', 'info');
                }
            });
            
            room.on(LivekitClient.RoomEvent.ActiveSpeakersChanged, (speakers) => {
                // Clear all speaking indicators
                document.querySelectorAll('.participant.speaking').forEach(el => {
                    el.classList.remove('speaking');
                });
                // Add speaking indicator to active speakers
                speakers.forEach(speaker => {
                    const el = document.getElementById(`participant-${speaker.identity}`);
                    if (el) el.classList.add('speaking');
                });
            });
            
            room.on(LivekitClient.RoomEvent.Disconnected, (reason) => {
                log(`🚪 Desconectado: ${reason}`, 'warning');
                handleDisconnect();
            });
            
            room.on(LivekitClient.RoomEvent.Reconnecting, () => {
                log('🔄 Reconectando...', 'warning');
                setStatus('connecting', 'Reconectando...');
            });
            
            room.on(LivekitClient.RoomEvent.Reconnected, () => {
                log('✅ Reconectado', 'info');
                setStatus('connected', 'Conectado');
            });
            
            // Handle audio playback issues (autoplay policy)
            room.on(LivekitClient.RoomEvent.AudioPlaybackStatusChanged, () => {
                if (!room.canPlaybackAudio) {
                    log('⚠️ El navegador bloqueó el audio. Haz clic en "Habilitar Audio".', 'warning');
                }
            });
        }
        
        // Toggle microphone
        async function toggleMicrophone() {
            if (!room || !localParticipant) return;
            
            try {
                isMicEnabled = !isMicEnabled;
                await localParticipant.setMicrophoneEnabled(isMicEnabled);
                
                if (isMicEnabled) {
                    btnMicEl.textContent = '🔇 Silenciar Micrófono';
                    btnMicEl.className = 'btn btn-danger';
                    micStatusEl.className = 'mic-indicator active';
                    micStatusTextEl.textContent = 'Micrófono activo';
                    log('🎤 Micrófono activado', 'info');
                } else {
                    btnMicEl.textContent = '🎤 Activar Micrófono';
                    btnMicEl.className = 'btn btn-secondary';
                    micStatusEl.className = 'mic-indicator muted';
                    micStatusTextEl.textContent = 'Micrófono apagado';
                    log('🔇 Micrófono silenciado', 'info');
                }
                
                updateParticipantsList();
            } catch (error) {
                log(`❌ Error con micrófono: ${error.message}`, 'error');
            }
        }
        
        // Disconnect from room
        async function disconnectFromRoom() {
            if (room) {
                log('Desconectando de la sala...', 'info');
                await room.disconnect();
            }
            handleDisconnect();
        }
        
        // Handle disconnect
        function handleDisconnect() {
            room = null;
            localParticipant = null;
            isMicEnabled = false;
            
            setStatus('disconnected', 'Desconectado');
            connectFormEl.style.display = 'block';
            connectedControlsEl.style.display = 'none';
            btnConnectEl.disabled = false;
            btnMicEl.textContent = '🎤 Activar Micrófono';
            btnMicEl.className = 'btn btn-secondary';
            micStatusEl.className = 'mic-indicator muted';
            micStatusTextEl.textContent = 'Micrófono apagado';
            
            participantsEl.innerHTML = '<p style="color: #666; grid-column: 1/-1;">Conecta a una sala para ver participantes...</p>';
            
            // Remove all audio elements
            document.querySelectorAll('audio[id^="audio-"]').forEach(el => el.remove());
        }
        
        // Update participants list
        function updateParticipantsList() {
            if (!room) return;
            
            let html = '';
            
            // Local participant
            const localP = room.localParticipant;
            if (localP) {
                const initial = localP.name ? localP.name.charAt(0).toUpperCase() : '?';
                const micStatus = localP.isMicrophoneEnabled ? 'Transmitiendo' : 'Silenciado';
                const micClass = localP.isMicrophoneEnabled ? '' : 'muted';
                
                html += `
                    <div class="participant local" id="participant-${localP.identity}">
                        <div class="participant-avatar">${initial}</div>
                        <div class="participant-name">${localP.name || localP.identity} (Tú)</div>
                        <div class="participant-status ${micClass}">🎤 ${micStatus}</div>
                        <div class="volume-meter"><div class="volume-meter-fill" id="volume-${localP.identity}"></div></div>
                    </div>
                `;
            }
            
            // Remote participants
            room.remoteParticipants.forEach((participant, identity) => {
                const initial = participant.name ? participant.name.charAt(0).toUpperCase() : '?';
                const hasAudio = Array.from(participant.audioTrackPublications.values()).some(pub => pub.isSubscribed);
                const audioStatus = hasAudio ? 'Transmitiendo' : 'Sin audio';
                const audioClass = hasAudio ? '' : 'muted';
                const speakingClass = participant.isSpeaking ? 'speaking' : '';
                
                html += `
                    <div class="participant ${speakingClass}" id="participant-${identity}">
                        <div class="participant-avatar">${initial}</div>
                        <div class="participant-name">${participant.name || identity}</div>
                        <div class="participant-status ${audioClass}">🔊 ${audioStatus}</div>
                        <div class="volume-meter"><div class="volume-meter-fill" id="volume-${identity}"></div></div>
                    </div>
                `;
            });
            
            if (!html) {
                html = '<p style="color: #666; grid-column: 1/-1;">No hay participantes en la sala</p>';
            }
            
            participantsEl.innerHTML = html;
        }
        
        // Enable audio playback (for browsers that block autoplay)
        async function enableAudioPlayback() {
            if (!room) return;
            
            try {
                await room.startAudio();
                log('✅ Audio habilitado correctamente', 'info');
                document.getElementById('btn-enable-audio').style.display = 'none';
                
                // Re-attach all audio tracks
                room.remoteParticipants.forEach((participant, identity) => {
                    participant.audioTrackPublications.forEach((publication) => {
                        if (publication.track && publication.isSubscribed) {
                            attachAudioTrack(publication.track, identity);
                        }
                    });
                });
            } catch (error) {
                log(`❌ Error habilitando audio: ${error.message}`, 'error');
            }
        }
        
        // Click anywhere to enable audio (fallback)
        document.addEventListener('click', async () => {
            if (room && !room.canPlaybackAudio) {
                try {
                    await room.startAudio();
                    log('✅ Audio habilitado por interacción', 'info');
                } catch (e) {
                    // Ignore
                }
            }
        }, { once: true });
        
        // Initialize
        log('Sistema inicializado. LiveKit Client v2.11.0', 'info');
        log('💡 Tip: Todos los usuarios deben activar su micrófono para transmitir', 'info');
    </script>
</body>
</html>
