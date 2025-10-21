using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using System;
using Newtonsoft.Json;

namespace JuiciosSimulator.Audio
{
    /// <summary>
    /// Integración de audio compartido Unity con Laravel
    /// Este script se integra con tu AudioManager existente
    /// </summary>
    public class AudioIntegration : MonoBehaviour
    {
        [Header("Configuración de Audio")]
        public bool spatialAudio = true;
        public float maxDistance = 10f;
        public float volumeMultiplier = 1f;
        public bool echoCancellation = true;
        public bool noiseSuppression = true;
        
        [Header("Referencias")]
        public AudioManager audioManager; // Tu AudioManager existente
        public AudioSource localAudioSource; // AudioSource local
        public AudioSource[] remoteAudioSources; // AudioSources de otros jugadores
        
        [Header("Estado del Audio")]
        public bool isMicrophoneActive = false;
        public bool isAudioEnabled = true;
        public float currentVolume = 1f;
        
        // Eventos
        public static event Action<bool> OnMicrophoneStateChanged;
        public static event Action<float> OnVolumeChanged;
        public static event Action<AudioData> OnAudioDataReceived;
        public static event Action<AudioData> OnAudioDataSent;
        
        // Singleton
        public static AudioIntegration Instance { get; private set; }
        
        private void Awake()
        {
            if (Instance == null)
            {
                Instance = this;
                DontDestroyOnLoad(gameObject);
            }
            else
            {
                Destroy(gameObject);
            }
        }
        
        private void Start()
        {
            // Suscribirse a eventos de Laravel
            LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
            LaravelAPI.OnError += OnError;
            
            // Suscribirse a eventos de RoomIntegration
            RoomIntegration.OnPlayerJoined += OnPlayerJoined;
            RoomIntegration.OnPlayerLeft += OnPlayerLeft;
            
            // Configurar audio inicial
            ConfigureAudio();
        }
        
        private void OnDestroy()
        {
            LaravelAPI.OnUserLoggedIn -= OnUserLoggedIn;
            LaravelAPI.OnError -= OnError;
            RoomIntegration.OnPlayerJoined -= OnPlayerJoined;
            RoomIntegration.OnPlayerLeft -= OnPlayerLeft;
        }
        
        #region Configuración de Audio
        
        /// <summary>
        /// Configurar audio según la configuración de Laravel
        /// </summary>
        public void ConfigureAudio()
        {
            if (audioManager != null)
            {
                // Aplicar configuración de audio
                // audioManager.SetSpatialAudio(spatialAudio);
                // audioManager.SetMaxDistance(maxDistance);
                // audioManager.SetVolumeMultiplier(volumeMultiplier);
                // audioManager.SetEchoCancellation(echoCancellation);
                // audioManager.SetNoiseSuppression(noiseSuppression);
            }
            
            // Configurar AudioSource local
            if (localAudioSource != null)
            {
                localAudioSource.spatialBlend = spatialAudio ? 1f : 0f;
                localAudioSource.maxDistance = maxDistance;
                localAudioSource.volume = currentVolume * volumeMultiplier;
            }
        }
        
        /// <summary>
        /// Actualizar configuración de audio desde Laravel
        /// </summary>
        public void UpdateAudioConfiguration(Dictionary<string, object> audioConfig)
        {
            if (audioConfig.ContainsKey("spatial_audio"))
                spatialAudio = Convert.ToBoolean(audioConfig["spatial_audio"]);
            
            if (audioConfig.ContainsKey("max_distance"))
                maxDistance = Convert.ToSingle(audioConfig["max_distance"]);
            
            if (audioConfig.ContainsKey("volume_multiplier"))
                volumeMultiplier = Convert.ToSingle(audioConfig["volume_multiplier"]);
            
            if (audioConfig.ContainsKey("echo_cancellation"))
                echoCancellation = Convert.ToBoolean(audioConfig["echo_cancellation"]);
            
            if (audioConfig.ContainsKey("noise_suppression"))
                noiseSuppression = Convert.ToBoolean(audioConfig["noise_suppression"]);
            
            ConfigureAudio();
        }
        
        #endregion
        
        #region Control de Micrófono
        
        /// <summary>
        /// Activar/desactivar micrófono
        /// </summary>
        public void SetMicrophoneActive(bool active)
        {
            if (isMicrophoneActive == active) return;
            
            isMicrophoneActive = active;
            
            // Integrar con tu AudioManager
            if (audioManager != null)
            {
                // audioManager.SetMicrophoneActive(active);
            }
            
            // Notificar a Laravel
            NotifyMicrophoneState(active);
            
            OnMicrophoneStateChanged?.Invoke(active);
            Debug.Log($"Micrófono {(active ? "activado" : "desactivado")}");
        }
        
        /// <summary>
        /// Notificar estado del micrófono a Laravel
        /// </summary>
        private void NotifyMicrophoneState(bool active)
        {
            if (string.IsNullOrEmpty(RoomIntegration.Instance?.roomId)) return;
            
            StartCoroutine(NotifyMicrophoneStateCoroutine(active));
        }
        
        private IEnumerator NotifyMicrophoneStateCoroutine(bool active)
        {
            var audioData = new AudioStateRequest
            {
                usuario_id = LaravelAPI.Instance.currentUser?.id ?? 0,
                microfono_activo = active,
                audio_enabled = isAudioEnabled,
                volumen = currentVolume,
                metadata = new Dictionary<string, object>
                {
                    {"timestamp", DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()},
                    {"spatial_audio", spatialAudio},
                    {"max_distance", maxDistance}
                }
            };
            
            string jsonData = JsonConvert.SerializeObject(audioData);
            
            using (UnityWebRequest request = new UnityWebRequest($"{LaravelAPI.Instance.baseURL}/unity/rooms/{RoomIntegration.Instance.roomId}/audio-state", "POST"))
            {
                byte[] bodyRaw = Encoding.UTF8.GetBytes(jsonData);
                request.uploadHandler = new UploadHandlerRaw(bodyRaw);
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Content-Type", "application/json");
                request.SetRequestHeader("Authorization", $"Bearer {LaravelAPI.Instance.authToken}");
                
                yield return request.SendWebRequest();
                
                if (request.result != UnityWebRequest.Result.Success)
                {
                    Debug.LogError($"Error al notificar estado de micrófono: {request.error}");
                }
            }
        }
        
        #endregion
        
        #region Control de Volumen
        
        /// <summary>
        /// Cambiar volumen
        /// </summary>
        public void SetVolume(float volume)
        {
            currentVolume = Mathf.Clamp01(volume);
            
            // Aplicar volumen
            if (localAudioSource != null)
            {
                localAudioSource.volume = currentVolume * volumeMultiplier;
            }
            
            // Integrar con tu AudioManager
            if (audioManager != null)
            {
                // audioManager.SetVolume(currentVolume);
            }
            
            OnVolumeChanged?.Invoke(currentVolume);
        }
        
        /// <summary>
        /// Cambiar volumen de un jugador específico
        /// </summary>
        public void SetPlayerVolume(int usuarioId, float volume)
        {
            // Buscar AudioSource del jugador
            var audioSource = GetPlayerAudioSource(usuarioId);
            if (audioSource != null)
            {
                audioSource.volume = volume;
            }
        }
        
        #endregion
        
        #region Procesamiento de Audio
        
        /// <summary>
        /// Enviar datos de audio a Laravel
        /// </summary>
        public void SendAudioData(float[] audioData, int sampleRate = 44100)
        {
            if (!isMicrophoneActive || !isAudioEnabled) return;
            
            StartCoroutine(SendAudioDataCoroutine(audioData, sampleRate));
        }
        
        private IEnumerator SendAudioDataCoroutine(float[] audioData, int sampleRate)
        {
            var audioRequest = new AudioDataRequest
            {
                usuario_id = LaravelAPI.Instance.currentUser?.id ?? 0,
                audio_data = ConvertToBase64(audioData),
                sample_rate = sampleRate,
                channels = 1,
                timestamp = DateTimeOffset.UtcNow.ToUnixTimeMilliseconds(),
                metadata = new Dictionary<string, object>
                {
                    {"spatial_audio", spatialAudio},
                    {"max_distance", maxDistance},
                    {"echo_cancellation", echoCancellation},
                    {"noise_suppression", noiseSuppression}
                }
            };
            
            string jsonData = JsonConvert.SerializeObject(audioRequest);
            
            using (UnityWebRequest request = new UnityWebRequest($"{LaravelAPI.Instance.baseURL}/unity/rooms/{RoomIntegration.Instance.roomId}/audio-data", "POST"))
            {
                byte[] bodyRaw = Encoding.UTF8.GetBytes(jsonData);
                request.uploadHandler = new UploadHandlerRaw(bodyRaw);
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Content-Type", "application/json");
                request.SetRequestHeader("Authorization", $"Bearer {LaravelAPI.Instance.authToken}");
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityWebRequest.Result.Success)
                {
                    OnAudioDataSent?.Invoke(new AudioData
                    {
                        usuarioId = audioRequest.usuario_id,
                        audioData = audioData,
                        sampleRate = sampleRate,
                        timestamp = audioRequest.timestamp
                    });
                }
                else
                {
                    Debug.LogError($"Error al enviar audio: {request.error}");
                }
            }
        }
        
        /// <summary>
        /// Recibir datos de audio desde Laravel
        /// </summary>
        public void ReceiveAudioData(AudioData audioData)
        {
            // Buscar AudioSource del jugador
            var audioSource = GetPlayerAudioSource(audioData.usuarioId);
            if (audioSource != null)
            {
                // Reproducir audio
                PlayAudioData(audioSource, audioData.audioData, audioData.sampleRate);
            }
            
            OnAudioDataReceived?.Invoke(audioData);
        }
        
        /// <summary>
        /// Reproducir datos de audio
        /// </summary>
        private void PlayAudioData(AudioSource audioSource, float[] audioData, int sampleRate)
        {
            // Convertir datos de audio a AudioClip
            AudioClip clip = AudioClip.Create("RemoteAudio", audioData.Length, 1, sampleRate, false);
            clip.SetData(audioData, 0);
            
            // Reproducir
            audioSource.clip = clip;
            audioSource.Play();
        }
        
        #endregion
        
        #region Gestión de Jugadores
        
        /// <summary>
        /// Obtener AudioSource de un jugador
        /// </summary>
        private AudioSource GetPlayerAudioSource(int usuarioId)
        {
            // Buscar en los AudioSources remotos
            foreach (var audioSource in remoteAudioSources)
            {
                var playerData = audioSource.GetComponent<PlayerData>();
                if (playerData != null && playerData.usuarioId == usuarioId)
                {
                    return audioSource;
                }
            }
            
            return null;
        }
        
        /// <summary>
        /// Configurar AudioSource para un jugador
        /// </summary>
        public void SetupPlayerAudio(int usuarioId, AudioSource audioSource)
        {
            audioSource.spatialBlend = spatialAudio ? 1f : 0f;
            audioSource.maxDistance = maxDistance;
            audioSource.volume = currentVolume;
            audioSource.loop = false;
            audioSource.playOnAwake = false;
        }
        
        #endregion
        
        #region Event Handlers
        
        private void OnUserLoggedIn(UserData user)
        {
            Debug.Log($"Usuario logueado: {user.name}, configurando audio");
            ConfigureAudio();
        }
        
        private void OnPlayerJoined(PlayerData playerData)
        {
            Debug.Log($"Jugador unido: {playerData.nombre}, configurando audio");
            // Configurar audio para el nuevo jugador
        }
        
        private void OnPlayerLeft(int usuarioId)
        {
            Debug.Log($"Jugador salió: {usuarioId}, limpiando audio");
            // Limpiar audio del jugador que salió
        }
        
        private void OnError(string error)
        {
            Debug.LogError($"Error en AudioIntegration: {error}");
        }
        
        #endregion
        
        #region Utilidades
        
        /// <summary>
        /// Convertir array de float a Base64
        /// </summary>
        private string ConvertToBase64(float[] audioData)
        {
            byte[] bytes = new byte[audioData.Length * 4];
            Buffer.BlockCopy(audioData, 0, bytes, 0, bytes.Length);
            return Convert.ToBase64String(bytes);
        }
        
        /// <summary>
        /// Convertir Base64 a array de float
        /// </summary>
        private float[] ConvertFromBase64(string base64Data)
        {
            byte[] bytes = Convert.FromBase64String(base64Data);
            float[] audioData = new float[bytes.Length / 4];
            Buffer.BlockCopy(bytes, 0, audioData, 0, bytes.Length);
            return audioData;
        }
        
        #endregion
        
        #region Public Methods
        
        /// <summary>
        /// Habilitar/deshabilitar audio
        /// </summary>
        public void SetAudioEnabled(bool enabled)
        {
            isAudioEnabled = enabled;
            
            if (audioManager != null)
            {
                // audioManager.SetAudioEnabled(enabled);
            }
        }
        
        /// <summary>
        /// Obtener estado actual del audio
        /// </summary>
        public AudioState GetAudioState()
        {
            return new AudioState
            {
                isMicrophoneActive = this.isMicrophoneActive,
                isAudioEnabled = this.isAudioEnabled,
                currentVolume = this.currentVolume,
                spatialAudio = this.spatialAudio,
                maxDistance = this.maxDistance,
                echoCancellation = this.echoCancellation,
                noiseSuppression = this.noiseSuppression
            };
        }
        
        #endregion
    }
    
    #region Clases de Datos
    
    [System.Serializable]
    public class AudioStateRequest
    {
        public int usuario_id;
        public bool microfono_activo;
        public bool audio_enabled;
        public float volumen;
        public Dictionary<string, object> metadata;
    }
    
    [System.Serializable]
    public class AudioDataRequest
    {
        public int usuario_id;
        public string audio_data;
        public int sample_rate;
        public int channels;
        public long timestamp;
        public Dictionary<string, object> metadata;
    }
    
    [System.Serializable]
    public class AudioData
    {
        public int usuarioId;
        public float[] audioData;
        public int sampleRate;
        public long timestamp;
    }
    
    [System.Serializable]
    public class AudioState
    {
        public bool isMicrophoneActive;
        public bool isAudioEnabled;
        public float currentVolume;
        public bool spatialAudio;
        public float maxDistance;
        public bool echoCancellation;
        public bool noiseSuppression;
    }
    
    #endregion
}

