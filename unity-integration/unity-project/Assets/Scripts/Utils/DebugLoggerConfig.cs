using UnityEngine;

namespace JuiciosSimulator.Utils
{
    /// <summary>
    /// Configurador del sistema de logging para debug
    /// Se puede agregar a cualquier GameObject en la escena para controlar el logging
    /// </summary>
    public class DebugLoggerConfig : MonoBehaviour
    {
        [Header("Configuración de Debug")]
        [Tooltip("Activar/desactivar sistema de logging completo")]
        public bool enableDebugLogging = true;

        [Tooltip("Mostrar logs en la consola del navegador (WebGL)")]
        public bool showInBrowser = true;

        [Tooltip("Mostrar logs en la consola de Unity")]
        public bool showInUnity = true;

        [Header("Niveles de Log")]
        [Tooltip("Nivel mínimo de log a mostrar (Debug muestra todo, Error solo errores)")]
        public DebugLogger.LogLevel minLogLevel = DebugLogger.LogLevel.Info;

        [Header("Estadísticas")]
        [Tooltip("Mostrar estadísticas de eventos en consola")]
        public bool showStatsOnStart = false;

        private void Start()
        {
            // Configurar sistema de logging
            DebugLogger.SetEnabled(enableDebugLogging);
            DebugLogger.SetShowInBrowser(showInBrowser);
            DebugLogger.SetMinLogLevel(minLogLevel);

            DebugLogger.LogInfo("DebugLoggerConfig", "Sistema de logging configurado", new {
                enabled = enableDebugLogging,
                showInBrowser,
                showInUnity,
                minLogLevel = minLogLevel.ToString()
            });

            if (showStatsOnStart)
            {
                InvokeRepeating(nameof(ShowStats), 5f, 10f);
            }
        }

        private void ShowStats()
        {
            string stats = DebugLogger.GetEventStats();
            DebugLogger.LogInfo("DebugLoggerConfig", "Estadísticas de eventos", new { stats });
        }

        private void OnDestroy()
        {
            CancelInvoke();
        }

        // Métodos públicos para cambiar configuración en runtime
        public void SetEnabled(bool enabled)
        {
            enableDebugLogging = enabled;
            DebugLogger.SetEnabled(enabled);
        }

        public void SetShowInBrowser(bool show)
        {
            showInBrowser = show;
            DebugLogger.SetShowInBrowser(show);
        }

        public void SetMinLogLevel(DebugLogger.LogLevel level)
        {
            minLogLevel = level;
            DebugLogger.SetMinLogLevel(level);
        }
    }
}

