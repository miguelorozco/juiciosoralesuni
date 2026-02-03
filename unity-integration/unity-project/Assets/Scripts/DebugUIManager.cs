using UnityEngine;
using System.Collections.Generic;
using UnityEngine.Scripting;

/// <summary>
/// Gestor de interfaz de depuración que comunica el estado de Unity con elementos HTML
/// Sincroniza: logs de Unity, estado de conexiones (PeerJS, Photon, API), y estado de micrófono
/// </summary>
public class DebugUIManager : MonoBehaviour
{
    public static DebugUIManager Instance { get; private set; }

    private Queue<LogMessage> logQueue = new Queue<LogMessage>();
    private const int MAX_LOGS = 100;

    [System.Serializable]
    private class LogMessage
    {
        public string level;
        public string category;
        public string message;
        public string timestamp;
    }

    private void Awake()
    {
        if (Instance != null && Instance != this)
        {
            // Evitar destruir el GameObject padre por accidente si este componente
            // está adjunto a un objeto con otras responsabilidades (ej. Player)
            Destroy(this);
            return;
        }
        Instance = this;
        // Asegurarse de pasar el GameObject raíz a DontDestroyOnLoad
        var rootObject = (transform != null) ? transform.root.gameObject : gameObject;
        DontDestroyOnLoad(rootObject);

        // Registrar handler de logs
        Application.logMessageReceived += HandleLog;

        Log("info", "SYSTEM", "DebugUIManager inicializado");
    }

    private void OnDestroy()
    {
        Application.logMessageReceived -= HandleLog;
    }

    /// <summary>
    /// Callback para logs de UnityEngine
    /// </summary>
    private void HandleLog(string logString, string stackTrace, LogType type)
    {
        string level = type switch
        {
            LogType.Error => "error",
            LogType.Exception => "error",
            LogType.Warning => "warning",
            LogType.Log => "info",
            _ => "debug"
        };

        Log(level, "UNITY", logString);

        if (type == LogType.Exception)
        {
            Log("error", "STACK_TRACE", stackTrace);
        }
    }

    /// <summary>
    /// Agregar un log a la ventana de debug HTML
    /// </summary>
    public void Log(string level, string category, string message, object data = null)
    {
        var logMsg = new LogMessage
        {
            level = level,
            category = category,
            message = message,
            timestamp = System.DateTime.Now.ToString("HH:mm:ss.fff")
        };

        logQueue.Enqueue(logMsg);

        if (logQueue.Count > MAX_LOGS)
        {
            logQueue.Dequeue();
        }

        // Enviar a HTML
        SendToHTML(level, category, message, data);
    }

    /// <summary>
    /// Enviar información a la interfaz HTML via SendMessage
    /// </summary>
    private void SendToHTML(string level, string category, string message, object data = null)
    {
        try
        {
            if (Application.platform == RuntimePlatform.WebGLPlayer)
            {
                string dataJson = data != null ? JsonUtility.ToJson(data, true) : "null";
                string jsCall = $"window.unityDebugLog('{level}', '{category}', '{EscapeJSON(message)}', {dataJson})";
                
                // Ejecutar JavaScript en WebGL
                Application.ExternalEval(jsCall);
            }
        }
        catch (System.Exception e)
        {
            Debug.LogError($"Error enviando log a HTML: {e.Message}");
        }
    }

    private string EscapeJSON(string text)
    {
        if (string.IsNullOrEmpty(text)) return "";
        return text
            .Replace("\\", "\\\\")
            .Replace("\"", "\\\"")
            .Replace("\n", "\\n")
            .Replace("\r", "\\r")
            .Replace("\t", "\\t");
    }

    /// <summary>
    /// Actualizar estado de conexión de PeerJS en HTML
    /// </summary>
    public void UpdatePeerJSStatus(bool connected)
    {
        try
        {
            if (Application.platform == RuntimePlatform.WebGLPlayer)
            {
                string status = connected ? "Connected" : "Disconnected";
                string color = connected ? "#4CAF50" : "#f44336";
                
                Application.ExternalEval($@"
                    const el = document.getElementById('peerjs-status');
                    if (el) {{
                        el.textContent = '{status}';
                        el.style.color = '{color}';
                    }}
                ");
            }
        }
        catch (System.Exception e)
        {
            Debug.LogError($"Error actualizando PeerJS status: {e.Message}");
        }

        Log("info", "PEERJS", connected ? "Connected to PeerJS server" : "Disconnected from PeerJS server");
    }

    /// <summary>
    /// Actualizar estado de conexión de Photon en HTML
    /// </summary>
    public void UpdatePhotonStatus(bool connected)
    {
        try
        {
            if (Application.platform == RuntimePlatform.WebGLPlayer)
            {
                string status = connected ? "Connected" : "Disconnected";
                string color = connected ? "#4CAF50" : "#f44336";
                
                Application.ExternalEval($@"
                    const el = document.getElementById('photon-status');
                    if (el) {{
                        el.textContent = '{status}';
                        el.style.color = '{color}';
                    }}
                ");
            }
        }
        catch (System.Exception e)
        {
            Debug.LogError($"Error actualizando Photon status: {e.Message}");
        }

        Log("info", "PHOTON", connected ? "Connected to Photon server" : "Disconnected from Photon server");
    }

    /// <summary>
    /// Actualizar estado de conexión de Laravel API en HTML
    /// </summary>
    public void UpdateLaravelStatus(bool connected)
    {
        try
        {
            if (Application.platform == RuntimePlatform.WebGLPlayer)
            {
                string status = connected ? "Connected" : "Disconnected";
                string color = connected ? "#4CAF50" : "#f44336";
                
                Application.ExternalEval($@"
                    const el = document.getElementById('laravel-status');
                    if (el) {{
                        el.textContent = '{status}';
                        el.style.color = '{color}';
                    }}
                ");
            }
        }
        catch (System.Exception e)
        {
            Debug.LogError($"Error actualizando Laravel status: {e.Message}");
        }

        Log("info", "LARAVEL", connected ? "Connected to Laravel API" : "Disconnected from Laravel API");
    }

    /// <summary>
    /// Actualizar estado de permisos de micrófono en HTML
    /// </summary>
    public void UpdateMicrophoneStatus(bool permitted, string status = "")
    {
        try
        {
            if (Application.platform == RuntimePlatform.WebGLPlayer)
            {
                string permissionText = permitted ? "Granted" : "Denied";
                string color = permitted ? "#4CAF50" : "#f44336";
                string displayText = permitted ? $"Permission: Granted {status}" : "Permission: Denied";
                
                Application.ExternalEval($@"
                    const el = document.getElementById('mic-permission-status');
                    if (el) {{
                        el.textContent = '{displayText}';
                        el.style.color = '{color}';
                    }}
                ");
            }
        }
        catch (System.Exception e)
        {
            Debug.LogError($"Error actualizando Microphone status: {e.Message}");
        }

        Log("info", "MICROPHONE", $"Microphone permission: {(permitted ? "Granted" : "Denied")} {status}");
    }

    /// <summary>
    /// Actualizar nivel de audio en tiempo real en HTML (0-100)
    /// </summary>
    public void UpdateAudioLevel(float level)
    {
        try
        {
            if (Application.platform == RuntimePlatform.WebGLPlayer)
            {
                // Clampear entre 0 y 100
                float clampedLevel = Mathf.Clamp01(level) * 100f;
                
                Application.ExternalEval($@"
                    const bar = document.getElementById('audio-level');
                    if (bar) {{
                        bar.style.width = '{clampedLevel:F1}%';
                    }}
                ");
            }
        }
        catch (System.Exception e)
        {
            Debug.LogError($"Error actualizando audio level: {e.Message}");
        }
    }

    /// <summary>
    /// Actualizar si se está grabando audio en HTML
    /// </summary>
    public void SetIsRecording(bool recording)
    {
        try
        {
            if (Application.platform == RuntimePlatform.WebGLPlayer)
            {
                string status = recording ? "Yes" : "No";
                
                Application.ExternalEval($@"
                    const el = document.getElementById('is-recording');
                    if (el) {{
                        el.textContent = '{status}';
                        el.style.color = recording ? '#4CAF50' : '#888';
                    }}
                ");
            }
        }
        catch (System.Exception e)
        {
            Debug.LogError($"Error actualizando recording status: {e.Message}");
        }

        Log("debug", "AUDIO", recording ? "Recording started" : "Recording stopped");
    }

    /// <summary>
    /// Log de fase (con marcado visual especial en HTML)
    /// </summary>
    public void LogPhase(string phaseName, string status, object data = null)
    {
        try
        {
            if (Application.platform == RuntimePlatform.WebGLPlayer)
            {
                string dataJson = data != null ? JsonUtility.ToJson(data, true) : "null";
                Application.ExternalEval($"window.unityLogPhase('{phaseName}', '{status}', {dataJson})");
            }
        }
        catch (System.Exception e)
        {
            Debug.LogError($"Error logging phase: {e.Message}");
        }

        Log("phase", "PHASE", $"[{phaseName}] {status}", data);
    }

    /// <summary>
    /// Log de API (con marcado visual especial en HTML)
    /// </summary>
    public void LogAPI(string method, string url, string status, object data = null)
    {
        try
        {
            if (Application.platform == RuntimePlatform.WebGLPlayer)
            {
                string dataJson = data != null ? JsonUtility.ToJson(data, true) : "null";
                Application.ExternalEval($"window.unityLogAPI('{method}', '{url}', '{status}', {dataJson})");
            }
        }
        catch (System.Exception e)
        {
            Debug.LogError($"Error logging API: {e.Message}");
        }

        Log("api", "API", $"[{method}] {url} - {status}", data);
    }

    /// <summary>
    /// Log de evento (con marcado visual especial en HTML)
    /// </summary>
    public void LogEvent(string eventName, string message, object data = null)
    {
        try
        {
            if (Application.platform == RuntimePlatform.WebGLPlayer)
            {
                string dataJson = data != null ? JsonUtility.ToJson(data, true) : "null";
                Application.ExternalEval($"window.unityLogEvent('{eventName}', '{message}', {dataJson})");
            }
        }
        catch (System.Exception e)
        {
            Debug.LogError($"Error logging event: {e.Message}");
        }

        Log("event", "EVENT", $"[{eventName}] {message}", data);
    }
}
