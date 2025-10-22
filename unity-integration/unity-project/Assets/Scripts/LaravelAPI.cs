using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.Networking;
using System.Text;

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
        public string unityPlatform = "WebGL";
        public string deviceId = "UNITY_DEVICE_001";

        [Header("Autenticación")]
        public string authToken = "";
        public UserData currentUser;
        public SessionData currentSessionData;

        [Header("Sesión Actual")]
        public int currentSesionId = 0;
        public bool isConnected = false;

        // Eventos
        public static event Action<bool> OnConnectionStatusChanged;
        public static event Action<UserData> OnUserLoggedIn;
        public static event Action OnLogout;
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

            string jsonData = JsonUtility.ToJson(loginData);

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
                    var response = JsonUtility.FromJson<APIResponse<LoginResponse>>(request.downloadHandler.text);

                    if (response.success)
                    {
                        authToken = response.data.token;
                        currentUser = response.data.user;
                        isConnected = true;

                        OnUserLoggedIn?.Invoke(currentUser);
                        OnConnectionStatusChanged?.Invoke(true);

                        Debug.Log($"Login exitoso: {currentUser.name}");
                    }
                    else
                    {
                        OnError?.Invoke(response.message);
                        Debug.LogError($"Error en login: {response.message}");
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
        /// Cerrar sesión del usuario
        /// </summary>
        public void Logout()
        {
            authToken = null;
            currentUser = null;
            OnLogout?.Invoke();
            Debug.Log("Usuario deslogueado");
        }

        /// <summary>
        /// Verificar estado del servidor
        /// </summary>
        public IEnumerator CheckServerStatus()
        {
            using (UnityWebRequest request = UnityWebRequest.Get($"{baseURL}/unity/auth/status"))
            {
                yield return request.SendWebRequest();

                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<ServerStatus>>(request.downloadHandler.text);
                    if (response.success)
                    {
                        Debug.Log($"Servidor Unity disponible: {response.data.server_status}");
                    }
                }
                else
                {
                    Debug.LogError($"Servidor no disponible: {request.error}");
                }
            }
        }

        #endregion

        #region Sesiones Activas

        // Eventos para sesiones activas
        public static event Action<SessionData> OnActiveSessionReceived;
        public static event Action<DialogueData> OnDialogueDataReceived;

        /// <summary>
        /// Obtener sesión activa del usuario autenticado
        /// </summary>
        public void GetActiveSession()
        {
            StartCoroutine(GetActiveSessionCoroutine());
        }

        private IEnumerator GetActiveSessionCoroutine()
        {
            using (UnityWebRequest request = UnityWebRequest.Get($"{baseURL}/unity/auth/session/active"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                request.SetRequestHeader("X-Unity-Version", unityVersion);
                request.SetRequestHeader("X-Unity-Platform", unityPlatform);

                yield return request.SendWebRequest();

                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<SessionData>>(request.downloadHandler.text);

                    if (response.success)
                    {
                        currentSesionId = response.data.session.id;
                        currentSessionData = response.data;
                        OnActiveSessionReceived?.Invoke(response.data);
                        Debug.Log($"Sesión activa obtenida: {response.data.session.nombre}");

                        // Automáticamente cargar el diálogo de la sesión
                        GetSessionDialogue(response.data.session.id);
                    }
                    else
                    {
                        OnError?.Invoke(response.message);
                        Debug.LogError($"Error obteniendo sesión activa: {response.message}");
                    }
                }
                else
                {
                    OnError?.Invoke($"Error obteniendo sesión activa: {request.error}");
                    Debug.LogError($"Error obteniendo sesión activa: {request.error}");
                }
            }
        }

        /// <summary>
        /// Obtener diálogo específico de una sesión
        /// </summary>
        public void GetSessionDialogue(int sessionId)
        {
            StartCoroutine(GetSessionDialogueCoroutine(sessionId));
        }

        private IEnumerator GetSessionDialogueCoroutine(int sessionId)
        {
            using (UnityWebRequest request = UnityWebRequest.Get($"{baseURL}/unity/auth/session/{sessionId}/dialogue"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                request.SetRequestHeader("X-Unity-Version", unityVersion);
                request.SetRequestHeader("X-Unity-Platform", unityPlatform);

                yield return request.SendWebRequest();

                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<DialogueData>>(request.downloadHandler.text);

                    if (response.success)
                    {
                        OnDialogueDataReceived?.Invoke(response.data);
                        Debug.Log($"Diálogo de sesión obtenido: {response.data.dialogue.nombre}");
                    }
                    else
                    {
                        OnError?.Invoke(response.message);
                        Debug.LogError($"Error obteniendo diálogo de sesión: {response.message}");
                    }
                }
                else
                {
                    OnError?.Invoke($"Error obteniendo diálogo de sesión: {request.error}");
                    Debug.LogError($"Error obteniendo diálogo de sesión: {request.error}");
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
            currentSesionId = sesionId;
            StartCoroutine(GetDialogoEstadoCoroutine(sesionId));
        }

        private IEnumerator GetDialogoEstadoCoroutine(int sesionId)
        {
            using (UnityWebRequest request = UnityWebRequest.Get($"{baseURL}/unity/{sesionId}/dialogo-estado"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                request.SetRequestHeader("X-Unity-Version", unityVersion);
                request.SetRequestHeader("X-Unity-Platform", unityPlatform);

                yield return request.SendWebRequest();

                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<DialogoEstado>>(request.downloadHandler.text);

                    if (response.success)
                    {
                        OnDialogoUpdated?.Invoke(response.data);
                    }
                    else
                    {
                        OnError?.Invoke(response.message);
                    }
                }
                else
                {
                    OnError?.Invoke($"Error obteniendo estado del diálogo: {request.error}");
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
            using (UnityWebRequest request = UnityWebRequest.Get($"{baseURL}/unity/{sesionId}/respuestas-usuario/{usuarioId}"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                request.SetRequestHeader("X-Unity-Version", unityVersion);
                request.SetRequestHeader("X-Unity-Platform", unityPlatform);

                yield return request.SendWebRequest();

                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<List<RespuestaUsuario>>>(request.downloadHandler.text);

                    if (response.success)
                    {
                        OnRespuestasReceived?.Invoke(response.data);
                    }
                    else
                    {
                        OnError?.Invoke(response.message);
                    }
                }
                else
                {
                    OnError?.Invoke($"Error obteniendo respuestas: {request.error}");
                }
            }
        }

        /// <summary>
        /// Enviar decisión del usuario
        /// </summary>
        public void EnviarDecision(int sesionId, int usuarioId, int respuestaId, string decisionTexto, int tiempoRespuesta)
        {
            StartCoroutine(EnviarDecisionCoroutine(sesionId, usuarioId, respuestaId, decisionTexto, tiempoRespuesta));
        }

        private IEnumerator EnviarDecisionCoroutine(int sesionId, int usuarioId, int respuestaId, string decisionTexto, int tiempoRespuesta)
        {
            var decisionData = new DecisionRequest
            {
                usuario_id = usuarioId,
                respuesta_id = respuestaId,
                texto_decision = decisionTexto,
                tiempo_respuesta = tiempoRespuesta
            };

            string jsonData = JsonUtility.ToJson(decisionData);

            using (UnityWebRequest request = new UnityWebRequest($"{baseURL}/unity/{sesionId}/enviar-decision", "POST"))
            {
                byte[] bodyRaw = Encoding.UTF8.GetBytes(jsonData);
                request.uploadHandler = new UploadHandlerRaw(bodyRaw);
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Content-Type", "application/json");
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                request.SetRequestHeader("X-Unity-Version", unityVersion);
                request.SetRequestHeader("X-Unity-Platform", unityPlatform);

                yield return request.SendWebRequest();

                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<object>>(request.downloadHandler.text);

                    if (response.success)
                    {
                        Debug.Log("Decisión enviada exitosamente");
                        // Actualizar estado del diálogo después de enviar decisión
                        GetDialogoEstado(sesionId);
                    }
                    else
                    {
                        OnError?.Invoke(response.message);
                    }
                }
                else
                {
                    OnError?.Invoke($"Error enviando decisión: {request.error}");
                }
            }
        }

        #endregion

        #region Tiempo Real

        /// <summary>
        /// Iniciar escucha de eventos en tiempo real
        /// </summary>
        public void StartRealtimeEvents(int sesionId)
        {
            StartCoroutine(RealtimeEventsCoroutine(sesionId));
        }

        private IEnumerator RealtimeEventsCoroutine(int sesionId)
        {
            using (UnityWebRequest request = UnityWebRequest.Get($"{baseURL}/unity/{sesionId}/events"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                request.SetRequestHeader("Accept", "text/event-stream");
                request.SetRequestHeader("Cache-Control", "no-cache");

                yield return request.SendWebRequest();

                if (request.result == UnityWebRequest.Result.Success)
                {
                    ProcessSSEEvents(request.downloadHandler.text);
                }
                else
                {
                    OnError?.Invoke($"Error en eventos en tiempo real: {request.error}");
                }
            }
        }

        /// <summary>
        /// Procesar eventos Server-Sent Events
        /// </summary>
        private void ProcessSSEEvents(string sseData)
        {
            string[] lines = sseData.Split('\n');

            foreach (string line in lines)
            {
                if (line.StartsWith("data: "))
                {
                    string jsonData = line.Substring(6);
                    try
                    {
                        var eventData = JsonUtility.FromJson<UnityEvent>(jsonData);
                        HandleUnityEvent(eventData);
                    }
                    catch (Exception e)
                    {
                        Debug.LogError($"Error procesando evento SSE: {e.Message}");
                    }
                }
            }
        }

        /// <summary>
        /// Manejar eventos de Unity
        /// </summary>
        private void HandleUnityEvent(UnityEvent eventData)
        {
            switch (eventData.type)
            {
                case "dialogo_actualizado":
                    // Actualizar estado del diálogo
                    GetDialogoEstado(currentSesionId);
                    break;

                case "usuario_hablando":
                    // Manejar cambio de estado de habla
                    Debug.Log($"Usuario {eventData.data.usuario_id} está hablando: {eventData.data.estado}");
                    break;

                case "decision_procesada":
                    // Actualizar UI después de procesar decisión
                    GetDialogoEstado(currentSesionId);
                    break;

                case "sesion_finalizada":
                    // Manejar finalización de sesión
                    Debug.Log("Sesión finalizada");
                    break;
            }
        }

        #endregion

        #region Salas Unity

        /// <summary>
        /// Crear sala de Unity
        /// </summary>
        public void CreateRoom(string nombre, int sesionJuicioId, int maxParticipantes = 10)
        {
            StartCoroutine(CreateRoomCoroutine(nombre, sesionJuicioId, maxParticipantes));
        }

        private IEnumerator CreateRoomCoroutine(string nombre, int sesionJuicioId, int maxParticipantes)
        {
            var roomData = new CreateRoomRequest
            {
                nombre = nombre,
                sesion_juicio_id = sesionJuicioId,
                max_participantes = maxParticipantes,
                configuracion = new Dictionary<string, object>(),
                audio_config = new Dictionary<string, object>()
            };

            string jsonData = JsonUtility.ToJson(roomData);

            using (UnityWebRequest request = new UnityWebRequest($"{baseURL}/unity/rooms/create", "POST"))
            {
                byte[] bodyRaw = Encoding.UTF8.GetBytes(jsonData);
                request.uploadHandler = new UploadHandlerRaw(bodyRaw);
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Content-Type", "application/json");
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                request.SetRequestHeader("X-Unity-Version", unityVersion);
                request.SetRequestHeader("X-Unity-Platform", unityPlatform);

                yield return request.SendWebRequest();

                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<RoomData>>(request.downloadHandler.text);

                    if (response.success)
                    {
                        Debug.Log($"Sala creada: {response.data.room_id}");
                    }
                    else
                    {
                        OnError?.Invoke(response.message);
                    }
                }
                else
                {
                    OnError?.Invoke($"Error creando sala: {request.error}");
                }
            }
        }

        /// <summary>
        /// Unirse a sala de Unity
        /// </summary>
        public void JoinRoom(string roomId)
        {
            StartCoroutine(JoinRoomCoroutine(roomId));
        }

        private IEnumerator JoinRoomCoroutine(string roomId)
        {
            using (UnityWebRequest request = UnityWebRequest.Get($"{baseURL}/unity/rooms/{roomId}/join"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {authToken}");
                request.SetRequestHeader("X-Unity-Version", unityVersion);
                request.SetRequestHeader("X-Unity-Platform", unityPlatform);
                request.SetRequestHeader("X-Unity-Device-Id", deviceId);

                yield return request.SendWebRequest();

                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<RoomData>>(request.downloadHandler.text);

                    if (response.success)
                    {
                        Debug.Log($"Unido a sala: {response.data.room_id}");
                    }
                    else
                    {
                        OnError?.Invoke(response.message);
                    }
                }
                else
                {
                    OnError?.Invoke($"Error uniéndose a sala: {request.error}");
                }
            }
        }

        #endregion
    }

    #region Clases de Datos

    [Serializable]
    public class APIResponse<T>
    {
        public bool success;
        public string message;
        public T data;
    }

    [Serializable]
    public class LoginRequest
    {
        public string email;
        public string password;
        public string unity_version;
        public string unity_platform;
        public string device_id;
        public Dictionary<string, object> session_data;
    }

    [Serializable]
    public class LoginResponse
    {
        public string token;
        public string token_type;
        public int expires_in;
        public UserData user;
        public Dictionary<string, object> unity_info;
        public string server_time;
    }

    [Serializable]
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

    [Serializable]
    public class ServerStatus
    {
        public string server_status;
        public string api_version;
        public bool unity_support;
        public string server_time;
        public string timezone;
        public Dictionary<string, bool> features;
    }

    [Serializable]
    public class DialogoEstado
    {
        public bool dialogo_activo;
        public string estado;
        public NodoActual nodo_actual;
        public List<JuiciosSimulator.API.Participante> participantes;
        public float progreso;
        public int tiempo_transcurrido;
        public Dictionary<string, object> variables;
    }

    [Serializable]
    public class NodoActual
    {
        public int id;
        public string titulo;
        public string contenido;
        public RolHablando rol_hablando;
        public string tipo;
        public bool es_final;
    }

    [Serializable]
    public class RolHablando
    {
        public int id;
        public string nombre;
        public string color;
        public string icono;
    }

    [Serializable]
    public class Participante
    {
        public int usuario_id;
        public string nombre;
        public RolParticipante rol;
        public bool es_turno;
    }

    [Serializable]
    public class RolParticipante
    {
        public int id;
        public string nombre;
        public string color;
        public string icono;
    }

    [Serializable]
    public class RespuestaUsuario
    {
        public int id;
        public string texto;
        public int nodo_dialogo_id;
        public int orden;
    }

    [Serializable]
    public class DecisionRequest
    {
        public int usuario_id;
        public int respuesta_id;
        public string texto_decision;
        public int tiempo_respuesta;
    }

    [Serializable]
    public class UnityEvent
    {
        public int id;
        public string type;
        public EventData data;
        public int sesion_id;
        public string timestamp;
        public string priority;
    }

    [Serializable]
    public class EventData
    {
        public int usuario_id;
        public string estado;
        public Dictionary<string, object> decision;
        public string tipo;
    }

    [Serializable]
    public class CreateRoomRequest
    {
        public string nombre;
        public int sesion_juicio_id;
        public int max_participantes;
        public Dictionary<string, object> configuracion;
        public Dictionary<string, object> audio_config;
    }

    [Serializable]
    public class RoomData
    {
        public string room_id;
        public string nombre;
        public int max_participantes;
        public string estado;
        public Dictionary<string, object> configuracion;
        public Dictionary<string, object> audio_config;
        public string fecha_creacion;
        public int participantes_conectados;
        public List<JuiciosSimulator.API.Participante> participantes;
    }

    [Serializable]
    public class SessionData
    {
        public SessionInfo session;
        public RoleInfo role;
        public AssignmentInfo assignment;
        public string server_time;
    }

    [Serializable]
    public class SessionInfo
    {
        public int id;
        public string nombre;
        public string descripcion;
        public string estado;
        public string fecha_inicio;
        public string fecha_fin;
        public Dictionary<string, object> configuracion;
        public InstructorInfo instructor;
    }

    [Serializable]
    public class InstructorInfo
    {
        public int id;
        public string name;
        public string email;
    }

    [Serializable]
    public class RoleInfo
    {
        public int id;
        public string nombre;
        public string descripcion;
        public string color;
        public string icono;
    }

    [Serializable]
    public class AssignmentInfo
    {
        public int id;
        public bool confirmado;
        public string notas;
        public string fecha_asignacion;
    }

    [Serializable]
    public class DialogueData
    {
        public DialogueInfo dialogue;
        public SessionInfo session_info;
        public UserRoleInfo user_role;
        public string server_time;
    }

    [Serializable]
    public class DialogueInfo
    {
        public int id;
        public string nombre;
        public string descripcion;
        public List<RoleFlow> roles;
    }

    [Serializable]
    public class RoleFlow
    {
        public int id;
        public string nombre;
        public string descripcion;
        public string color;
        public string icono;
        public bool requerido;
        public List<FlowInfo> flujos;
    }

    [Serializable]
    public class FlowInfo
    {
        public int id;
        public List<DialogueNode> dialogos;
    }

    [Serializable]
    public class DialogueNode
    {
        public int id;
        public string titulo;
        public string contenido;
        public string tipo;
        public int posicion;
        public List<DialogueOption> opciones;
    }

    [Serializable]
    public class DialogueOption
    {
        public int id;
        public string letra;
        public string texto;
        public int puntuacion;
        public Dictionary<string, object> consecuencias;
    }

    [Serializable]
    public class UserRoleInfo
    {
        public int id;
        public string nombre;
    }

    #endregion
}
