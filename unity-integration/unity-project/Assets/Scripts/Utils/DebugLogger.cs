using System;
using System.Collections.Generic;
using UnityEngine;

namespace JuiciosSimulator.Utils
{
    /// <summary>
    /// Sistema de logging detallado para debug que muestra información en la consola del navegador
    /// </summary>
    public static class DebugLogger
    {
        private static bool isEnabled = true; // Bandera para activar/desactivar
        private static bool showInBrowser = true; // Mostrar en consola del navegador (WebGL)
        private static bool showInUnity = true; // Mostrar en consola de Unity
        private static LogLevel minLogLevel = LogLevel.Info; // Nivel mínimo de log
        
        private static Dictionary<string, int> eventCounts = new Dictionary<string, int>();
        private static Dictionary<string, float> eventTimestamps = new Dictionary<string, float>();

        public enum LogLevel
        {
            Debug = 0,
            Info = 1,
            Warning = 2,
            Error = 3
        }

        /// <summary>
        /// Activar o desactivar el sistema de logging
        /// </summary>
        public static void SetEnabled(bool enabled)
        {
            isEnabled = enabled;
            LogInfo("DebugLogger", $"Sistema de logging {(enabled ? "activado" : "desactivado")}");
        }

        /// <summary>
        /// Configurar si se muestra en el navegador
        /// </summary>
        public static void SetShowInBrowser(bool show)
        {
            showInBrowser = show;
        }

        /// <summary>
        /// Configurar nivel mínimo de log
        /// </summary>
        public static void SetMinLogLevel(LogLevel level)
        {
            minLogLevel = level;
        }

        /// <summary>
        /// Log de información general
        /// </summary>
        public static void LogInfo(string phase, string message, object data = null)
        {
            Log(LogLevel.Info, phase, message, data);
        }

        /// <summary>
        /// Log de debug detallado
        /// </summary>
        public static void LogDebug(string phase, string message, object data = null)
        {
            Log(LogLevel.Debug, phase, message, data);
        }

        /// <summary>
        /// Log de advertencia
        /// </summary>
        public static void LogWarning(string phase, string message, object data = null)
        {
            Log(LogLevel.Warning, phase, message, data);
        }

        /// <summary>
        /// Log de error
        /// </summary>
        public static void LogError(string phase, string message, object data = null)
        {
            Log(LogLevel.Error, phase, message, data);
        }

        /// <summary>
        /// Log de evento (con contador)
        /// </summary>
        public static void LogEvent(string eventName, string message = "", object data = null)
        {
            if (!isEnabled) return;

            // Contar eventos
            if (!eventCounts.ContainsKey(eventName))
            {
                eventCounts[eventName] = 0;
            }
            eventCounts[eventName]++;

            // Registrar timestamp
            eventTimestamps[eventName] = Time.time;

            string fullMessage = $"[EVENT #{eventCounts[eventName]}] {eventName}";
            if (!string.IsNullOrEmpty(message))
            {
                fullMessage += $" - {message}";
            }

            Log(LogLevel.Info, "EVENT", fullMessage, data);
            
            // También enviar directamente a la ventana HTML
            LogEventToBrowser(eventName, fullMessage, data);
        }

        /// <summary>
        /// Log de fase de carga
        /// </summary>
        public static void LogPhase(string phaseName, string status, object data = null)
        {
            if (!isEnabled) return;

            string message = $"[PHASE] {phaseName}] {status}";
            Log(LogLevel.Info, "PHASE", message, data);
            
            // También enviar directamente a la ventana HTML
            LogPhaseToBrowser(phaseName, status, data);
        }

        /// <summary>
        /// Log de llamada a API
        /// </summary>
        public static void LogAPI(string method, string url, string status = "INITIATED", object data = null)
        {
            if (!isEnabled) return;

            string message = $"[API {method}] {url} - {status}";
            Log(LogLevel.Info, "API", message, data);
            
            // También enviar directamente a la ventana HTML
            LogAPIToBrowser(method, url, status, data);
        }

        /// <summary>
        /// Log de respuesta de API
        /// </summary>
        public static void LogAPIResponse(string url, bool success, string message = "", object data = null)
        {
            if (!isEnabled) return;

            string status = success ? "SUCCESS" : "ERROR";
            string fullMessage = $"[API RESPONSE] {url} - {status}";
            if (!string.IsNullOrEmpty(message))
            {
                fullMessage += $" - {message}";
            }

            Log(success ? LogLevel.Info : LogLevel.Error, "API", fullMessage, data);
        }

        /// <summary>
        /// Log de suscripción a eventos
        /// </summary>
        public static void LogEventSubscription(string eventName, string handlerName, bool subscribing)
        {
            if (!isEnabled) return;

            string action = subscribing ? "SUBSCRIBED" : "UNSUBSCRIBED";
            string message = $"[EVENT {action}] {eventName} -> {handlerName}";
            Log(LogLevel.Debug, "EVENT_SUB", message);
        }

        /// <summary>
        /// Log de invocación de evento
        /// </summary>
        public static void LogEventInvocation(string eventName, int handlerCount, object data = null)
        {
            if (!isEnabled) return;

            string message = $"[EVENT INVOKE] {eventName} -> {handlerCount} handler(s)";
            Log(LogLevel.Debug, "EVENT_INVOKE", message, data);
        }

        /// <summary>
        /// Log de método con protección contra recursión
        /// </summary>
        public static void LogMethodEntry(string methodName, bool isRecursive = false)
        {
            if (!isEnabled) return;

            if (isRecursive)
            {
                LogWarning("RECURSION", $"⚠️ RECURSIVE CALL DETECTED: {methodName}");
            }
            else
            {
                LogDebug("METHOD", $"→ Entering: {methodName}");
            }
        }

        /// <summary>
        /// Log de salida de método
        /// </summary>
        public static void LogMethodExit(string methodName, string result = "")
        {
            if (!isEnabled) return;

            string message = $"← Exiting: {methodName}";
            if (!string.IsNullOrEmpty(result))
            {
                message += $" - {result}";
            }
            LogDebug("METHOD", message);
        }

        /// <summary>
        /// Obtener estadísticas de eventos
        /// </summary>
        public static string GetEventStats()
        {
            if (eventCounts.Count == 0) return "No hay eventos registrados";

            System.Text.StringBuilder stats = new System.Text.StringBuilder();
            stats.AppendLine("=== ESTADÍSTICAS DE EVENTOS ===");
            
            foreach (var kvp in eventCounts)
            {
                float timeSince = eventTimestamps.ContainsKey(kvp.Key) 
                    ? Time.time - eventTimestamps[kvp.Key] 
                    : 0f;
                stats.AppendLine($"{kvp.Key}: {kvp.Value} veces (último: {timeSince:F2}s atrás)");
            }

            return stats.ToString();
        }

        /// <summary>
        /// Limpiar estadísticas
        /// </summary>
        public static void ClearStats()
        {
            eventCounts.Clear();
            eventTimestamps.Clear();
        }

        /// <summary>
        /// Método principal de logging
        /// </summary>
        private static void Log(LogLevel level, string category, string message, object data = null)
        {
            if (!isEnabled || level < minLogLevel) return;

            // Construir mensaje completo
            string timestamp = DateTime.Now.ToString("HH:mm:ss.fff");
            string levelStr = level.ToString().ToUpper();
            string fullMessage = $"[{timestamp}] [{levelStr}] [{category}] {message}";

            // Agregar datos si existen
            if (data != null)
            {
                try
                {
                    string dataJson = JsonUtility.ToJson(data);
                    fullMessage += $"\nData: {dataJson}";
                }
                catch
                {
                    fullMessage += $"\nData: {data}";
                }
            }

            // Log en Unity
            if (showInUnity)
            {
                switch (level)
                {
                    case LogLevel.Debug:
                    case LogLevel.Info:
                        Debug.Log(fullMessage);
                        break;
                    case LogLevel.Warning:
                        Debug.LogWarning(fullMessage);
                        break;
                    case LogLevel.Error:
                        Debug.LogError(fullMessage);
                        break;
                }
            }

            // Log en navegador (WebGL)
            if (showInBrowser)
            {
                LogToBrowser(level, fullMessage);
            }
        }

        /// <summary>
        /// Enviar log a la consola del navegador
        /// </summary>
        private static void LogToBrowser(LogLevel level, string message)
        {
#if UNITY_WEBGL && !UNITY_EDITOR
            try
            {
                // Intentar usar la función de logging HTML si está disponible
                string jsMethod = level switch
                {
                    LogLevel.Error => "error",
                    LogLevel.Warning => "warning",
                    _ => "info"
                };

                // Escapar comillas y saltos de línea para JavaScript
                string escapedMessage = message
                    .Replace("\\", "\\\\")
                    .Replace("'", "\\'")
                    .Replace("\"", "\\\"")
                    .Replace("\n", "\\n")
                    .Replace("\r", "");

                // Intentar usar unityDebugLog si está disponible (ventana HTML)
                try
                {
                    Application.ExternalCall("unityDebugLog", jsMethod, "UNITY", escapedMessage, "null");
                }
                catch
                {
                    // Si no está disponible, usar console directamente
                    string consoleMethod = level switch
                    {
                        LogLevel.Error => "console.error",
                        LogLevel.Warning => "console.warn",
                        _ => "console.log"
                    };
                    Application.ExternalCall("eval", $"{consoleMethod}('{escapedMessage}');");
                }
            }
            catch (Exception e)
            {
                // Si falla, solo loguear en Unity
                Debug.LogWarning($"[DebugLogger] No se pudo enviar log al navegador: {e.Message}");
            }
#else
            // En editor, solo mostrar en consola de Unity
            // (ya se hizo arriba)
#endif
        }

        /// <summary>
        /// Enviar log de fase directamente a la ventana HTML
        /// </summary>
        public static void LogPhaseToBrowser(string phaseName, string status, object data = null)
        {
#if UNITY_WEBGL && !UNITY_EDITOR
            try
            {
                string dataJson = data != null ? JsonUtility.ToJson(data) : "null";
                Application.ExternalCall("unityLogPhase", phaseName, status, dataJson);
            }
            catch (Exception e)
            {
                Debug.LogWarning($"[DebugLogger] No se pudo enviar log de fase al navegador: {e.Message}");
            }
#endif
        }

        /// <summary>
        /// Enviar log de API directamente a la ventana HTML
        /// </summary>
        public static void LogAPIToBrowser(string method, string url, string status, object data = null)
        {
#if UNITY_WEBGL && !UNITY_EDITOR
            try
            {
                string dataJson = data != null ? JsonUtility.ToJson(data) : "null";
                Application.ExternalCall("unityLogAPI", method, url, status, dataJson);
            }
            catch (Exception e)
            {
                Debug.LogWarning($"[DebugLogger] No se pudo enviar log de API al navegador: {e.Message}");
            }
#endif
        }

        /// <summary>
        /// Enviar log de evento directamente a la ventana HTML
        /// </summary>
        public static void LogEventToBrowser(string eventName, string message, object data = null)
        {
#if UNITY_WEBGL && !UNITY_EDITOR
            try
            {
                string dataJson = data != null ? JsonUtility.ToJson(data) : "null";
                Application.ExternalCall("unityLogEvent", eventName, message, dataJson);
            }
            catch (Exception e)
            {
                Debug.LogWarning($"[DebugLogger] No se pudo enviar log de evento al navegador: {e.Message}");
            }
#endif
        }
    }
}

