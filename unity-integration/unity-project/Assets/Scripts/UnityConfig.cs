using UnityEngine;
using System;
using Photon.Pun;

namespace JuiciosSimulator.Config
{
    /// <summary>
    /// Configuración centralizada para Unity
    /// </summary>
    [CreateAssetMenu(fileName = "UnityConfig", menuName = "Juicios Simulator/Unity Config")]
    public class UnityConfig : ScriptableObject
    {
        [Header("Configuración de API")]
        [Tooltip("URL base de la API de Laravel")]
        public string apiBaseURL = "http://localhost:8000/api";

        [Tooltip("Versión de Unity")]
        public string unityVersion = "2022.3.15f1";

        [Tooltip("Plataforma de Unity")]
        public string unityPlatform = "WebGL";

        [Header("Configuración de Photon")]
        [Tooltip("App ID de Photon PUN2")]
        public string photonAppId = "YOUR_PHOTON_APP_ID";

        [Tooltip("Región de Photon")]
        public string photonRegion = "us";

        [Header("Configuración de PeerJS")]
        [Tooltip("Servidor PeerJS principal")]
        public string peerjsHost = "juiciosorales.site";

        [Tooltip("Puerto PeerJS")]
        public int peerjsPort = 443;

        [Tooltip("Usar HTTPS para PeerJS")]
        public bool peerjsSecure = true;

        [Header("Configuración de Audio")]
        [Tooltip("Echo cancellation")]
        public bool echoCancellation = true;

        [Tooltip("Noise suppression")]
        public bool noiseSuppression = true;

        [Tooltip("Auto gain control")]
        public bool autoGainControl = true;

        [Tooltip("Sample rate")]
        public int sampleRate = 44100;

        [Tooltip("Channel count")]
        public int channelCount = 1;

        [Tooltip("Latencia de audio")]
        public float audioLatency = 0.01f;

        [Header("Configuración de Sala")]
        [Tooltip("Máximo de jugadores por sala")]
        public int maxPlayersPerRoom = 20;

        [Tooltip("Tiempo de timeout para conexiones")]
        public float connectionTimeout = 30f;

        [Header("Configuración de Debug")]
        [Tooltip("Mostrar logs de debug")]
        public bool showDebugLogs = true;

        [Tooltip("Mostrar panel de debug en UI")]
        public bool showDebugPanel = true;

        [Tooltip("Log level")]
        public LogLevel logLevel = LogLevel.Info;

        public enum LogLevel
        {
            None = 0,
            Error = 1,
            Warning = 2,
            Info = 3,
            Debug = 4
        }

        /// <summary>
        /// Obtener configuración de audio para PeerJS
        /// </summary>
        public object GetAudioConfig()
        {
            return new
            {
                echoCancellation = echoCancellation,
                noiseSuppression = noiseSuppression,
                autoGainControl = autoGainControl,
                sampleRate = sampleRate,
                channelCount = channelCount,
                latency = audioLatency
            };
        }

        /// <summary>
        /// Obtener configuración de PeerJS
        /// </summary>
        public object GetPeerJSConfig()
        {
            return new
            {
                host = peerjsHost,
                port = peerjsPort,
                secure = peerjsSecure,
                path = "/peerjs",
                debug = (int)logLevel
            };
        }

        /// <summary>
        /// Obtener configuración de Photon
        /// </summary>
        public object GetPhotonConfig()
        {
            return new
            {
                appId = photonAppId,
                region = photonRegion,
                maxPlayers = maxPlayersPerRoom
            };
        }

        /// <summary>
        /// Validar configuración
        /// </summary>
        public bool ValidateConfig()
        {
            if (string.IsNullOrEmpty(apiBaseURL))
            {
                Debug.LogError("API Base URL no puede estar vacía");
                return false;
            }

            if (string.IsNullOrEmpty(photonAppId) || photonAppId == "YOUR_PHOTON_APP_ID")
            {
                Debug.LogError("Photon App ID no está configurado");
                return false;
            }

            if (string.IsNullOrEmpty(peerjsHost))
            {
                Debug.LogError("PeerJS Host no puede estar vacío");
                return false;
            }

            if (maxPlayersPerRoom <= 0 || maxPlayersPerRoom > 100)
            {
                Debug.LogError("Max Players debe estar entre 1 y 100");
                return false;
            }

            return true;
        }

        /// <summary>
        /// Aplicar configuración a los componentes
        /// </summary>
        public void ApplyConfig()
        {
            if (!ValidateConfig())
            {
                Debug.LogError("Configuración inválida, no se puede aplicar");
                return;
            }

            // Aplicar configuración a LaravelAPI
            var laravelAPI = FindObjectOfType<JuiciosSimulator.API.LaravelAPI>();
            if (laravelAPI != null)
            {
                laravelAPI.baseURL = apiBaseURL;
                laravelAPI.unityVersion = unityVersion;
                laravelAPI.unityPlatform = unityPlatform;
            }

            // Aplicar configuración de Photon
            if (!string.IsNullOrEmpty(photonAppId))
            {
                PhotonNetwork.PhotonServerSettings.AppSettings.AppIdRealtime = photonAppId;
            }

            Debug.Log("Configuración aplicada exitosamente");
        }

        /// <summary>
        /// Resetear a configuración por defecto
        /// </summary>
        [ContextMenu("Reset to Default")]
        public void ResetToDefault()
        {
            apiBaseURL = "http://localhost:8000/api";
            unityVersion = "2022.3.15f1";
            unityPlatform = "WebGL";
            photonAppId = "YOUR_PHOTON_APP_ID";
            photonRegion = "us";
            peerjsHost = "juiciosorales.site";
            peerjsPort = 443;
            peerjsSecure = true;
            echoCancellation = true;
            noiseSuppression = true;
            autoGainControl = true;
            sampleRate = 44100;
            channelCount = 1;
            audioLatency = 0.01f;
            maxPlayersPerRoom = 20;
            connectionTimeout = 30f;
            showDebugLogs = true;
            showDebugPanel = true;
            logLevel = LogLevel.Info;

            Debug.Log("Configuración reseteada a valores por defecto");
        }
    }
}
