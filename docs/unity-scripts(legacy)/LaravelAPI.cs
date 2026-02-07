using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.Networking;
using System.Text;
using Newtonsoft.Json;

namespace JuiciosSimulator.API
{
    /// <summary>
    /// Clase principal para comunicación con la API de Laravel
    /// </summary>
    public class LaravelAPI : MonoBehaviour
    {
        [Header("Configuración de API")]
        public string baseURL = "http://localhost:8000/api";
        public string unityVersion = "2022.3.15f1";
        public string unityPlatform = "WindowsPlayer";
        public string deviceId = "UNITY_DEVICE_123";
        
        [Header("Autenticación")]
        public string authToken = "";
        public UserData currentUser;
        
        [Header("Sesión Actual")]
        public int currentSesionId = 0;
        public bool isConnected = false;
        
        // Eventos
        public static event Action<bool> OnConnectionStatusChanged;
        public static event Action<UserData> OnUserLoggedIn;
        public static event Action<string> OnError;
        public static event Action<DialogoEstado> OnDialogoUpdated;
        public static event Action<List<RespuestaUsuario>> OnRespuestasReceived;
        
        // Singleton
        public static LaravelAPI Instance { get; private set; }
        
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
            // Verificar estado del servidor al iniciar
            StartCoroutine(CheckServerStatus());
        }
        
        #region Autenticación
        
        /// <summary>
        /// Login del usuario en la API
        /// </summary>
        public void Login(string email, string password)
        {
            StartCoroutine(LoginCoroutine(email, password));
        }
        
        private IEnumerator LoginCoroutine(string email, string password)
        {
            var loginData = new LoginRequest
            {
                email = email,
                password = password,
                unity_version = unityVersion,
                unity_platform = unityPlatform,
                device_id = deviceId,
                session_data = new Dictionary<string, object>
                {
                    {"platform", Application.platform.ToString()},
                    {"version", Application.version},
                    {"device_model", SystemInfo.deviceModel},
                    {"device_name", SystemInfo.deviceName}
                }
            };
            
            string jsonData = JsonConvert.SerializeObject(loginData);
            
            using (UnityWebRequest request = new UnityWebRequest($"{baseURL}/unity/auth/login", "POST"))
            {
                byte[] bodyRaw = Encoding.UTF8.GetBytes(jsonData);
                request.uploadHandler = new UploadHandlerRaw(bodyRaw);
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Content-Type", "application/json");
                request.SetRequestHeader("X-Unity-Version", unityVersion);
                request.SetRequestHeader("X-Unity-Platform", unityPlatform);
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonConvert.DeserializeObject<APIResponse<LoginResponse>>(request.downloadHandler.text);
                    
                    if (response.success)
                    {
                        authToken = response.data.token;
                        currentUser = response.data.user;
                        isConnected = true;
                        
                        OnUserLoggedIn?.Invoke(currentUser);
                        OnConnectionStatusChanged?.Invoke(true);
                        
                        Debug.Log($"Login exitoso: {currentUser.name} {currentUser.apellido}");
                    }
                    else
                    {
                        OnError?.Invoke(response.message);
                        Debug.LogError($"Error de login: {response.message}");
                    }
                }
                else
                {
                    OnError?.Invoke($"Error de conexión: {request.error}");
                    Debug.LogError($"Error de conexión: {request.error}");
                }
            }
        }
        
        /// <summary>
        /// Logout del usuario
        /// </summary>
        public void Logout()
        {
            StartCoroutine(LogoutCoroutine());
        }
        
        private IEnumerator LogoutCoroutine()
        {
            using (UnityWebRequest request = new UnityWebRequest($"{baseURL}/unity/auth/logout", "POST"))
            {
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                
                yield return request.SendWebRequest();
                
                authToken = "";
                currentUser = null;
                isConnected = false;
                
                OnConnectionStatusChanged?.Invoke(false);
                Debug.Log("Logout exitoso");
            }
        }
        
        /// <summary>
        /// Verificar estado del servidor
        /// </summary>
        private IEnumerator CheckServerStatus()
        {
            using (UnityWebRequest request = UnityWebRequest.Get($"{baseURL}/unity/auth/status"))
            {
                yield return request.SendWebRequest();
                
                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonConvert.DeserializeObject<APIResponse<ServerStatus>>(request.downloadHandler.text);
                    if (response.success)
                    {
                        Debug.Log($"Servidor disponible: {response.data.server_status}");
                    }
                }
                else
                {
                    Debug.LogError($"Servidor no disponible: {request.error}");
                }
            }
        }
        
        #endregion
        
        #region Diálogos
        
        /// <summary>
        /// Obtener estado actual del diálogo
        /// </summary>
        public void GetDialogoEstado(int sesionId)
        {
            StartCoroutine(GetDialogoEstadoCoroutine(sesionId));
        }
        
        private IEnumerator GetDialogoEstadoCoroutine(int sesionId)
        {
            using (UnityWebRequest request = UnityWebRequest.Get($"{baseURL}/unity/sesion/{sesionId}/dialogo-estado"))
            {
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                request.SetRequestHeader("X-Unity-Version", unityVersion);
                request.SetRequestHeader("X-Unity-Platform", unityPlatform);
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonConvert.DeserializeObject<APIResponse<DialogoEstado>>(request.downloadHandler.text);
                    
                    if (response.success)
                    {
                        OnDialogoUpdated?.Invoke(response.data);
                        Debug.Log($"Estado del diálogo obtenido: {response.data.estado}");
                    }
                    else
                    {
                        OnError?.Invoke(response.message);
                    }
                }
                else
                {
                    OnError?.Invoke($"Error al obtener estado: {request.error}");
                }
            }
        }
        
        /// <summary>
        /// Obtener respuestas disponibles para el usuario
        /// </summary>
        public void GetRespuestasUsuario(int sesionId, int usuarioId)
        {
            StartCoroutine(GetRespuestasUsuarioCoroutine(sesionId, usuarioId));
        }
        
        private IEnumerator GetRespuestasUsuarioCoroutine(int sesionId, int usuarioId)
        {
            using (UnityWebRequest request = UnityWebRequest.Get($"{baseURL}/unity/sesion/{sesionId}/respuestas-usuario/{usuarioId}"))
            {
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonConvert.DeserializeObject<APIResponse<RespuestasResponse>>(request.downloadHandler.text);
                    
                    if (response.success && response.data.respuestas_disponibles)
                    {
                        OnRespuestasReceived?.Invoke(response.data.respuestas);
                        Debug.Log($"Respuestas obtenidas: {response.data.respuestas.Count}");
                    }
                    else
                    {
                        Debug.Log($"No hay respuestas disponibles: {response.message}");
                    }
                }
                else
                {
                    OnError?.Invoke($"Error al obtener respuestas: {request.error}");
                }
            }
        }
        
        /// <summary>
        /// Enviar decisión del usuario
        /// </summary>
        public void EnviarDecision(int sesionId, int usuarioId, int respuestaId, string decisionTexto = "", int tiempoRespuesta = 0)
        {
            StartCoroutine(EnviarDecisionCoroutine(sesionId, usuarioId, respuestaId, decisionTexto, tiempoRespuesta));
        }
        
        private IEnumerator EnviarDecisionCoroutine(int sesionId, int usuarioId, int respuestaId, string decisionTexto, int tiempoRespuesta)
        {
            var decisionData = new DecisionRequest
            {
                usuario_id = usuarioId,
                respuesta_id = respuestaId,
                decision_texto = decisionTexto,
                tiempo_respuesta = tiempoRespuesta,
                metadata = new Dictionary<string, object>
                {
                    {"unity_timestamp", DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()},
                    {"unity_platform", unityPlatform}
                }
            };
            
            string jsonData = JsonConvert.SerializeObject(decisionData);
            
            using (UnityWebRequest request = new UnityWebRequest($"{baseURL}/unity/sesion/{sesionId}/enviar-decision", "POST"))
            {
                byte[] bodyRaw = Encoding.UTF8.GetBytes(jsonData);
                request.uploadHandler = new UploadHandlerRaw(bodyRaw);
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Content-Type", "application/json");
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonConvert.DeserializeObject<APIResponse<DecisionResponse>>(request.downloadHandler.text);
                    
                    if (response.success)
                    {
                        Debug.Log($"Decisión enviada exitosamente: {response.data.decision_id}");
                        // Actualizar estado del diálogo
                        GetDialogoEstado(sesionId);
                    }
                    else
                    {
                        OnError?.Invoke(response.message);
                    }
                }
                else
                {
                    OnError?.Invoke($"Error al enviar decisión: {request.error}");
                }
            }
        }
        
        /// <summary>
        /// Notificar que el usuario está hablando
        /// </summary>
        public void NotificarHablando(int sesionId, int usuarioId, string estado)
        {
            StartCoroutine(NotificarHablandoCoroutine(sesionId, usuarioId, estado));
        }
        
        private IEnumerator NotificarHablandoCoroutine(int sesionId, int usuarioId, string estado)
        {
            var hablandoData = new HablandoRequest
            {
                usuario_id = usuarioId,
                estado = estado,
                metadata = new Dictionary<string, object>
                {
                    {"unity_timestamp", DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()}
                }
            };
            
            string jsonData = JsonConvert.SerializeObject(hablandoData);
            
            using (UnityWebRequest request = new UnityWebRequest($"{baseURL}/unity/sesion/{sesionId}/notificar-hablando", "POST"))
            {
                byte[] bodyRaw = Encoding.UTF8.GetBytes(jsonData);
                request.uploadHandler = new UploadHandlerRaw(bodyRaw);
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Content-Type", "application/json");
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityWebRequest.Result.Success)
                {
                    Debug.Log($"Estado de habla actualizado: {estado}");
                }
                else
                {
                    Debug.LogError($"Error al notificar habla: {request.error}");
                }
            }
        }
        
        #endregion
        
        #region Comunicación en Tiempo Real
        
        /// <summary>
        /// Iniciar escucha de eventos en tiempo real
        /// </summary>
        public void StartRealtimeEvents(int sesionId)
        {
            StartCoroutine(RealtimeEventsCoroutine(sesionId));
        }
        
        private IEnumerator RealtimeEventsCoroutine(int sesionId)
        {
            using (UnityWebRequest request = UnityWebRequest.Get($"{baseURL}/unity/sesion/{sesionId}/events"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                request.SetRequestHeader("Accept", "text/event-stream");
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityWebRequest.Result.Success)
                {
                    // Procesar eventos SSE
                    ProcessSSEEvents(request.downloadHandler.text);
                }
                else
                {
                    OnError?.Invoke($"Error en eventos en tiempo real: {request.error}");
                }
            }
        }
        
        private void ProcessSSEEvents(string sseData)
        {
            string[] lines = sseData.Split('\n');
            
            for (int i = 0; i < lines.Length; i++)
            {
                if (lines[i].StartsWith("data: "))
                {
                    string jsonData = lines[i].Substring(6);
                    
                    try
                    {
                        var eventData = JsonConvert.DeserializeObject<SSEEvent>(jsonData);
                        ProcessEvent(eventData);
                    }
                    catch (Exception e)
                    {
                        Debug.LogError($"Error al procesar evento SSE: {e.Message}");
                    }
                }
            }
        }
        
        private void ProcessEvent(SSEEvent eventData)
        {
            switch (eventData.type)
            {
                case "dialogo_actualizado":
                    OnDialogoUpdated?.Invoke(JsonConvert.DeserializeObject<DialogoEstado>(eventData.data.ToString()));
                    break;
                    
                case "usuario_hablando":
                    Debug.Log($"Usuario hablando: {eventData.data}");
                    break;
                    
                case "decision_procesada":
                    Debug.Log($"Decisión procesada: {eventData.data}");
                    break;
                    
                case "sesion_finalizada":
                    Debug.Log("Sesión finalizada");
                    break;
                    
                default:
                    Debug.Log($"Evento recibido: {eventData.type}");
                    break;
            }
        }
        
        #endregion
    }
    
    #region Clases de Datos
    
    [System.Serializable]
    public class APIResponse<T>
    {
        public bool success;
        public string message;
        public T data;
    }
    
    [System.Serializable]
    public class LoginRequest
    {
        public string email;
        public string password;
        public string unity_version;
        public string unity_platform;
        public string device_id;
        public Dictionary<string, object> session_data;
    }
    
    [System.Serializable]
    public class LoginResponse
    {
        public string token;
        public string token_type;
        public int expires_in;
        public UserData user;
        public Dictionary<string, object> unity_info;
        public string server_time;
    }
    
    [System.Serializable]
    public class UserData
    {
        public int id;
        public string name;
        public string apellido;
        public string email;
        public string tipo;
        public bool activo;
        public Dictionary<string, object> configuracion;
    }
    
    [System.Serializable]
    public class ServerStatus
    {
        public string server_status;
        public string api_version;
        public bool unity_support;
        public string server_time;
        public string timezone;
        public Dictionary<string, bool> features;
    }
    
    [System.Serializable]
    public class DialogoEstado
    {
        public bool dialogo_activo;
        public string estado;
        public NodoActual nodo_actual;
        public List<Participante> participantes;
        public float progreso;
        public int tiempo_transcurrido;
        public Dictionary<string, object> variables;
    }
    
    [System.Serializable]
    public class NodoActual
    {
        public int id;
        public string titulo;
        public string contenido;
        public RolHablando rol_hablando;
        public string tipo;
        public bool es_final;
    }
    
    [System.Serializable]
    public class RolHablando
    {
        public int id;
        public string nombre;
        public string color;
        public string icono;
    }
    
    [System.Serializable]
    public class Participante
    {
        public int usuario_id;
        public string nombre;
        public RolHablando rol;
        public bool es_turno;
    }
    
    [System.Serializable]
    public class RespuestasResponse
    {
        public bool respuestas_disponibles;
        public List<RespuestaUsuario> respuestas;
        public NodoActual nodo_actual;
        public RolHablando rol_usuario;
    }
    
    [System.Serializable]
    public class RespuestaUsuario
    {
        public int id;
        public string texto;
        public string descripcion;
        public string color;
        public int puntuacion;
        public bool tiene_consecuencias;
        public bool es_final;
    }
    
    [System.Serializable]
    public class DecisionRequest
    {
        public int usuario_id;
        public int respuesta_id;
        public string decision_texto;
        public int tiempo_respuesta;
        public Dictionary<string, object> metadata;
    }
    
    [System.Serializable]
    public class DecisionResponse
    {
        public bool decision_procesada;
        public int decision_id;
        public int puntuacion_obtenida;
        public NuevoEstado nuevo_estado;
    }
    
    [System.Serializable]
    public class NuevoEstado
    {
        public NodoActual nodo_actual;
        public float progreso;
        public bool dialogo_finalizado;
    }
    
    [System.Serializable]
    public class HablandoRequest
    {
        public int usuario_id;
        public string estado;
        public Dictionary<string, object> metadata;
    }
    
    [System.Serializable]
    public class SSEEvent
    {
        public string type;
        public object data;
        public string timestamp;
    }
    
    #endregion
}

