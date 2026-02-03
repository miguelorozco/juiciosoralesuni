using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.Networking;
using System.Text;
using JuiciosSimulator.Utils;

namespace JuiciosSimulator.API
{

    public class LaravelAPI : MonoBehaviour
    {
        [Header("Configuración de API")]
        public string baseURL = "http://localhost:8000/api";
        public string unityVersion = "2022.3.15f1";
        public string unityPlatform = "WebGL";
        public string deviceId = "UNITY_DEVICE_001";

        [Header("Debug")]
        public bool enableDebugLogging = true;
        public bool checkServerStatusOnStart = true;

        [Header("Autenticación")]
        public string authToken = "";
        public UserData currentUser;
        public SessionData currentSessionData;
        public DialogueData currentDialogueData;

        [Header("Sesión Actual")]
        public int currentSesionId = 0;
        public bool isConnected = false;

        // Flags para prevenir llamadas recursivas
        private bool isGettingActiveSession = false;
        private bool isGettingSessionDialogue = false;
        private static bool hasInitialized = false;
        private static int? lastProcessedSessionId = null;

        // Singleton
        public static LaravelAPI Instance { get; private set; }
        public static bool IsInitialized { get; private set; } = false;

        // Eventos
        public static event Action<bool> OnConnectionStatusChanged;
        public static event Action<UserData> OnUserLoggedIn;
        public static event Action OnLogout;
        public static event Action<string> OnError;
        public static event Action<DialogoEstado> OnDialogoUpdated;
        public static event Action<List<RespuestaUsuario>> OnRespuestasReceived;
        public static event Action<SessionData> OnActiveSessionReceived;
        public static event Action<DialogueData> OnDialogueDataReceived;

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
                return;
            }
        }

        private void Start()
        {
            StartCoroutine(InitializeLaravelAPIDelayed());
            FetchRoleColorsFromBackend();
        }

        private IEnumerator InitializeLaravelAPIDelayed()
        {
            yield return null;
            yield return null;
            yield return null;
            yield return new WaitForEndOfFrame();
            InitializeLaravelAPI();
        }

        private void InitializeLaravelAPI()
        {
            if (hasInitialized)
            {
                IsInitialized = true;
                return;
            }
            hasInitialized = true;
            IsInitialized = true;
            if (enableDebugLogging)
            {
                DebugLogger.SetEnabled(true);
                DebugLogger.SetShowInBrowser(true);
            }
            if (checkServerStatusOnStart)
            {
                StartCoroutine(CheckServerStatus());
            }
        }

        public void FetchRoleColorsFromBackend()
        {
            StartCoroutine(FetchRoleColorsCoroutine());
        }

        private IEnumerator FetchRoleColorsCoroutine()
        {
            string url = $"{baseURL}/roles/status";
            using (UnityWebRequest www = UnityWebRequest.Get(url))
            {
                yield return www.SendWebRequest();
                if (www.result != UnityWebRequest.Result.Success)
                {
                    Debug.LogError($"[LaravelAPI] Error obteniendo colores de roles: {www.error}");
                }
                else
                {
                    var json = www.downloadHandler.text;
                    var parsed = JsonUtility.FromJson<RoleStatusResponse>(json);
                    if (parsed != null && parsed.roles != null)
                    {
                        var colorDict = new Dictionary<string, string>();
                        foreach (var kvp in parsed.roles)
                        {
                            colorDict[kvp.Key] = kvp.Value.color;
                        }
                        RoleColorManager.UpdateColorsFromApi(colorDict);
                        Debug.Log("[LaravelAPI] Colores de roles actualizados dinámicamente");
                    }
                }
            }
        }

        public void ReleaseRoleToBackend(string role, int actorNumber)
        {
            StartCoroutine(ReleaseRoleCoroutine(role, actorNumber));
        }

        private IEnumerator ReleaseRoleCoroutine(string role, int actorNumber)
        {
            string url = $"{baseURL}/roles/release";
            WWWForm form = new WWWForm();
            form.AddField("role", role);
            form.AddField("user_id", actorNumber);
            using (UnityWebRequest www = UnityWebRequest.Post(url, form))
            {
                yield return www.SendWebRequest();
                if (www.result != UnityWebRequest.Result.Success)
                {
                    Debug.LogError($"[LaravelAPI] Error liberando rol en backend: {www.error}");
                }
                else
                {
                    Debug.Log($"[LaravelAPI] Rol '{role}' liberado en backend");
                }
            }
        }

        public void Login(string email, string password) { StartCoroutine(LoginCoroutine(email, password)); }
        public void Logout() { authToken = null; currentUser = null; OnLogout?.Invoke(); }
        public void GetActiveSession() { StartCoroutine(GetActiveSessionCoroutine()); }
        public void GetSessionDialogue(int sessionId) { StartCoroutine(GetSessionDialogueCoroutine(sessionId)); }
        public void GetDialogoEstado(int sesionId) { StartCoroutine(GetDialogoEstadoCoroutine(sesionId)); }
        public void GetRespuestasUsuario(int sesionId, int usuarioId) { StartCoroutine(GetRespuestasUsuarioCoroutine(sesionId, usuarioId)); }
        public void EnviarDecision(int sesionId, int usuarioId, int respuestaId, string decisionTexto, int tiempoRespuesta) { StartCoroutine(EnviarDecisionCoroutine(sesionId, usuarioId, respuestaId, decisionTexto, tiempoRespuesta)); }
        public void JoinRoom(string roomId) { StartCoroutine(JoinRoomCoroutine(roomId)); }
        
        public IEnumerator CheckServerStatus()
        {
            using (UnityWebRequest request = UnityWebRequest.Get($"{baseURL}/unity/auth/status"))
            {
                yield return request.SendWebRequest();
                if (request.result == UnityWebRequest.Result.Success)
                {
                    Debug.Log("Servidor disponible");
                }
            }
        }

        private IEnumerator LoginCoroutine(string email, string password) { yield break; }
        private IEnumerator GetActiveSessionCoroutine() { yield break; }
        private IEnumerator GetSessionDialogueCoroutine(int sessionId) { yield break; }
        private IEnumerator GetDialogoEstadoCoroutine(int sesionId) { yield break; }
        private IEnumerator GetRespuestasUsuarioCoroutine(int sesionId, int usuarioId) { yield break; }
        private IEnumerator EnviarDecisionCoroutine(int sesionId, int usuarioId, int respuestaId, string decisionTexto, int tiempoRespuesta) { yield break; }
        private IEnumerator JoinRoomCoroutine(string roomId) { yield break; }
    }

    // Clases de datos
    [Serializable]
    public class RoleStatusResponse { public bool success; public Dictionary<string, RoleInfo> roles; }
    [Serializable]
    public class RoleInfo { public int id; public string nombre; public string color; public string descripcion; public int orden; public string ocupado_por; public string icono; }
    [Serializable]
    public class APIResponse<T> { public bool success; public string message; public T data; }
    [Serializable]
    public class LoginRequest { public string email; public string password; public string unity_version; public string unity_platform; public string device_id; public Dictionary<string, object> session_data; }
    [Serializable]
    public class LoginResponse { public string token; public string token_type; public int expires_in; public UserData user; public Dictionary<string, object> unity_info; public string server_time; }
    [Serializable]
    public class UserData { public int id; public string name; public string apellido; public string email; public string tipo; public bool activo; public Dictionary<string, object> configuracion; }
    [Serializable]
    public class ServerStatus { public string server_status; public string api_version; public bool unity_support; public string server_time; public string timezone; public Dictionary<string, bool> features; }
    [Serializable]
    public class DialogoEstado { public bool dialogo_activo; public string estado; public NodoActual nodo_actual; public List<Participante> participantes; public float progreso; public int tiempo_transcurrido; public Dictionary<string, object> variables; }
    [Serializable]
    public class NodoActual { public int id; public string titulo; public string contenido; public RolHablando rol_hablando; public string tipo; public bool es_final; }
    [Serializable]
    public class RolHablando { public int id; public string nombre; public string color; public string icono; }
    [Serializable]
    public class Participante { public int usuario_id; public string nombre; public RolParticipante rol; public bool es_turno; }
    [Serializable]
    public class RolParticipante { public int id; public string nombre; public string color; public string icono; }
    [Serializable]
    public class RespuestaUsuario { public int id; public string texto; public int nodo_dialogo_id; public int orden; }
    [Serializable]
    public class DecisionRequest { public int usuario_id; public int respuesta_id; public string texto_decision; public int tiempo_respuesta; }
    [Serializable]
    public class UnityEvent { public int id; public string type; public EventData data; public int sesion_id; public string timestamp; public string priority; }
    [Serializable]
    public class EventData { public int usuario_id; public string estado; public Dictionary<string, object> decision; public string tipo; }
    [Serializable]
    public class CreateRoomRequest { public string nombre; public int sesion_juicio_id; public int max_participantes; public Dictionary<string, object> configuracion; public Dictionary<string, object> audio_config; }
    [Serializable]
    public class RoomData { public string room_id; public string nombre; public int max_participantes; public string estado; public Dictionary<string, object> configuracion; public Dictionary<string, object> audio_config; public string fecha_creacion; public int participantes_conectados; public List<Participante> participantes; }
    [Serializable]
    public class SessionData { public SessionInfo session; public RoleInfo role; public AssignmentInfo assignment; public string server_time; }
    [Serializable]
    public class SessionInfo { public int id; public string nombre; public string descripcion; public string estado; public string fecha_inicio; public string fecha_fin; public Dictionary<string, object> configuracion; public InstructorInfo instructor; }
    [Serializable]
    public class InstructorInfo { public int id; public string name; public string email; }
    [Serializable]
    public class AssignmentInfo { public int id; public bool confirmado; public string notas; public string fecha_asignacion; }
    [Serializable]
    public class DialogueData { public DialogueInfo dialogue; public SessionInfo session_info; public UserRoleInfo user_role; public string server_time; }
    [Serializable]
    public class DialogueInfo { public int id; public string nombre; public string descripcion; public List<RoleFlow> roles; }
    [Serializable]
    public class RoleFlow { public int id; public string nombre; public string descripcion; public string color; public string icono; public bool requerido; public List<FlowInfo> flujos; }
    [Serializable]
    public class FlowInfo { public int id; public List<DialogueNode> dialogos; }
    [Serializable]
    public class DialogueNode { public int id; public string titulo; public string contenido; public string tipo; public int posicion; public List<DialogueOption> opciones; }
    [Serializable]
    public class DialogueOption { public int id; public string letra; public string texto; public int puntuacion; public Dictionary<string, object> consecuencias; }
    [Serializable]
    public class UserRoleInfo { public int id; public string nombre; }
}
// ...existing code...
