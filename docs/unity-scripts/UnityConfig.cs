using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using System;

namespace JuiciosSimulator.Config
{
    /// <summary>
    /// Configuración central para la integración Unity-Laravel
    /// </summary>
    [CreateAssetMenu(fileName = "UnityConfig", menuName = "Juicios Simulator/Unity Config")]
    public class UnityConfig : ScriptableObject
    {
        [Header("Configuración de API")]
        [Tooltip("URL base de la API de Laravel")]
        public string apiBaseURL = "http://localhost:8000/api";
        
        [Tooltip("Versión de Unity para identificación")]
        public string unityVersion = "2022.3.15f1";
        
        [Tooltip("Plataforma de Unity")]
        public string unityPlatform = "WindowsPlayer";
        
        [Header("Configuración de Sesión")]
        [Tooltip("ID de la sesión de juicio")]
        public int sesionId = 1;
        
        [Tooltip("ID del usuario actual")]
        public int usuarioId = 1;
        
        [Header("Configuración de Red")]
        [Tooltip("Timeout para requests HTTP (segundos)")]
        public float requestTimeout = 30f;
        
        [Tooltip("Intervalo de polling para eventos (segundos)")]
        public float eventPollingInterval = 1f;
        
        [Tooltip("Máximo número de reintentos para requests fallidos")]
        public int maxRetries = 3;
        
        [Header("Configuración de UI")]
        [Tooltip("Tiempo de espera antes de mostrar loading (segundos)")]
        public float loadingDelay = 0.5f;
        
        [Tooltip("Duración de animaciones de transición (segundos)")]
        public float transitionDuration = 0.3f;
        
        [Header("Configuración de Personajes")]
        [Tooltip("Velocidad de movimiento de personajes")]
        public float characterMoveSpeed = 2f;
        
        [Tooltip("Distancia mínima para considerar llegada")]
        public float arrivalDistance = 0.5f;
        
        [Tooltip("Duración de animaciones de personajes")]
        public float characterAnimationDuration = 1f;
        
        [Header("Configuración de Debug")]
        [Tooltip("Habilitar logs detallados")]
        public bool enableDebugLogs = true;
        
        [Tooltip("Mostrar información de red en UI")]
        public bool showNetworkInfo = false;
        
        [Tooltip("Simular latencia de red (segundos)")]
        public float simulatedLatency = 0f;
        
        [Header("Configuración de Audio")]
        [Tooltip("Volumen general del juego")]
        [Range(0f, 1f)]
        public float masterVolume = 1f;
        
        [Tooltip("Volumen de efectos de sonido")]
        [Range(0f, 1f)]
        public float sfxVolume = 0.8f;
        
        [Tooltip("Volumen de música de fondo")]
        [Range(0f, 1f)]
        public float musicVolume = 0.6f;
        
        [Header("Configuración de Calidad")]
        [Tooltip("Calidad de gráficos")]
        public QualityLevel graphicsQuality = QualityLevel.High;
        
        [Tooltip("Resolución objetivo")]
        public Vector2 targetResolution = new Vector2(1920, 1080);
        
        [Tooltip("Habilitar VSync")]
        public bool enableVSync = true;
        
        [Header("Configuración de Tiempo Real")]
        [Tooltip("Habilitar comunicación en tiempo real")]
        public bool enableRealtimeCommunication = true;
        
        [Tooltip("Intervalo de heartbeat (segundos)")]
        public float heartbeatInterval = 30f;
        
        [Tooltip("Timeout de conexión en tiempo real (segundos)")]
        public float realtimeTimeout = 300f;
        
        // Singleton
        private static UnityConfig _instance;
        public static UnityConfig Instance
        {
            get
            {
                if (_instance == null)
                {
                    _instance = Resources.Load<UnityConfig>("UnityConfig");
                    if (_instance == null)
                    {
                        Debug.LogError("UnityConfig no encontrado en Resources. Creando configuración por defecto.");
                        _instance = CreateInstance<UnityConfig>();
                    }
                }
                return _instance;
            }
        }
        
        #region Validation
        
        private void OnValidate()
        {
            // Validar URLs
            if (string.IsNullOrEmpty(apiBaseURL))
            {
                apiBaseURL = "http://localhost:8000/api";
            }
            
            // Validar versiones
            if (string.IsNullOrEmpty(unityVersion))
            {
                unityVersion = Application.unityVersion;
            }
            
            if (string.IsNullOrEmpty(unityPlatform))
            {
                unityPlatform = Application.platform.ToString();
            }
            
            // Validar IDs
            if (sesionId <= 0)
            {
                sesionId = 1;
            }
            
            if (usuarioId <= 0)
            {
                usuarioId = 1;
            }
            
            // Validar timeouts
            if (requestTimeout <= 0)
            {
                requestTimeout = 30f;
            }
            
            if (eventPollingInterval <= 0)
            {
                eventPollingInterval = 1f;
            }
            
            if (maxRetries < 0)
            {
                maxRetries = 3;
            }
        }
        
        #endregion
        
        #region Public Methods
        
        /// <summary>
        /// Obtener URL completa para un endpoint
        /// </summary>
        public string GetEndpointURL(string endpoint)
        {
            if (endpoint.StartsWith("/"))
            {
                return apiBaseURL + endpoint;
            }
            return apiBaseURL + "/" + endpoint;
        }
        
        /// <summary>
        /// Obtener headers por defecto para requests
        /// </summary>
        public Dictionary<string, string> GetDefaultHeaders()
        {
            return new Dictionary<string, string>
            {
                {"Content-Type", "application/json"},
                {"X-Unity-Version", unityVersion},
                {"X-Unity-Platform", unityPlatform},
                {"X-Unity-Device-Id", SystemInfo.deviceUniqueIdentifier}
            };
        }
        
        /// <summary>
        /// Verificar si la configuración es válida
        /// </summary>
        public bool IsValid()
        {
            return !string.IsNullOrEmpty(apiBaseURL) &&
                   !string.IsNullOrEmpty(unityVersion) &&
                   !string.IsNullOrEmpty(unityPlatform) &&
                   sesionId > 0 &&
                   usuarioId > 0 &&
                   requestTimeout > 0;
        }
        
        /// <summary>
        /// Obtener información de debug
        /// </summary>
        public string GetDebugInfo()
        {
            return $"Unity Config Debug Info:\n" +
                   $"API Base URL: {apiBaseURL}\n" +
                   $"Unity Version: {unityVersion}\n" +
                   $"Unity Platform: {unityPlatform}\n" +
                   $"Sesión ID: {sesionId}\n" +
                   $"Usuario ID: {usuarioId}\n" +
                   $"Request Timeout: {requestTimeout}s\n" +
                   $"Event Polling Interval: {eventPollingInterval}s\n" +
                   $"Max Retries: {maxRetries}\n" +
                   $"Realtime Communication: {enableRealtimeCommunication}\n" +
                   $"Debug Logs: {enableDebugLogs}\n" +
                   $"Simulated Latency: {simulatedLatency}s";
        }
        
        /// <summary>
        /// Aplicar configuración de calidad
        /// </summary>
        public void ApplyQualitySettings()
        {
            QualitySettings.SetQualityLevel((int)graphicsQuality);
            QualitySettings.vSyncCount = enableVSync ? 1 : 0;
            
            // Aplicar resolución si es posible
            if (targetResolution.x > 0 && targetResolution.y > 0)
            {
                Screen.SetResolution((int)targetResolution.x, (int)targetResolution.y, Screen.fullScreen);
            }
        }
        
        /// <summary>
        /// Aplicar configuración de audio
        /// </summary>
        public void ApplyAudioSettings()
        {
            AudioListener.volume = masterVolume;
            // Aquí podrías configurar volúmenes específicos si tienes AudioMixer
        }
        
        #endregion
        
        #region Editor Helpers
        
#if UNITY_EDITOR
        [ContextMenu("Reset to Defaults")]
        private void ResetToDefaults()
        {
            apiBaseURL = "http://localhost:8000/api";
            unityVersion = Application.unityVersion;
            unityPlatform = Application.platform.ToString();
            sesionId = 1;
            usuarioId = 1;
            requestTimeout = 30f;
            eventPollingInterval = 1f;
            maxRetries = 3;
            loadingDelay = 0.5f;
            transitionDuration = 0.3f;
            characterMoveSpeed = 2f;
            arrivalDistance = 0.5f;
            characterAnimationDuration = 1f;
            enableDebugLogs = true;
            showNetworkInfo = false;
            simulatedLatency = 0f;
            masterVolume = 1f;
            sfxVolume = 0.8f;
            musicVolume = 0.6f;
            graphicsQuality = QualityLevel.High;
            targetResolution = new Vector2(1920, 1080);
            enableVSync = true;
            enableRealtimeCommunication = true;
            heartbeatInterval = 30f;
            realtimeTimeout = 300f;
        }
        
        [ContextMenu("Validate Configuration")]
        private void ValidateConfiguration()
        {
            if (IsValid())
            {
                Debug.Log("Configuración válida");
            }
            else
            {
                Debug.LogError("Configuración inválida. Revisa los valores.");
            }
        }
#endif
        
        #endregion
    }
    
    public enum QualityLevel
    {
        Low = 0,
        Medium = 1,
        High = 2,
        Ultra = 3
    }
}

