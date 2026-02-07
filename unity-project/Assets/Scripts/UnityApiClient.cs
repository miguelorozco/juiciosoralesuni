using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
using UnityEngine;
using UnityEngine.Networking;
using Newtonsoft.Json;

namespace JuiciosSimulator.API
{
    /// <summary>
    /// Cliente del API Laravel para Unity (puente Unity–Laravel).
    /// Usa <see cref="UnityBridgeConfig"/> para BaseUrl y Token.
    /// Colgar en un GameObject de la escena (p. ej. persistente con DontDestroyOnLoad).
    /// </summary>
    public class UnityApiClient : MonoBehaviour
    {
        public static UnityApiClient Instance { get; private set; }

        [Header("Opcional: sobreescribe UnityBridgeConfig.BaseUrl en Start")]
        [Tooltip("Si está vacío, se usa UnityBridgeConfig.BaseUrl")]
        public string overrideBaseUrl = "";

        [Header("Debug")]
        public bool logRequests;

        private void Awake()
        {
            if (Instance != null && Instance != this)
            {
                Destroy(gameObject);
                return;
            }
            Instance = this;
            DontDestroyOnLoad(gameObject);
        }

        private void Start()
        {
            if (!string.IsNullOrEmpty(overrideBaseUrl))
                UnityBridgeConfig.BaseUrl = overrideBaseUrl;
        }

        private string BaseUrl => UnityBridgeConfig.BaseUrl;
        private string Token => UnityBridgeConfig.Token;

        private void ApplyAuth(UnityWebRequest req)
        {
            if (!string.IsNullOrEmpty(Token))
                req.SetRequestHeader("Authorization", "Bearer " + Token);
            req.SetRequestHeader("Accept", "application/json");
            req.SetRequestHeader("Content-Type", "application/json");
            req.SetRequestHeader("X-Unity-Version", Application.unityVersion);
            req.SetRequestHeader("X-Unity-Platform", Application.platform.ToString());
        }

        private string FullUrl(string path) => UnityBridgeConfig.GetFullUrl(path);

        #region Auth

        /// <summary>GET /api/unity/auth/status</summary>
        public void GetAuthStatus(Action<APIResponse<ServerStatus>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/auth/status"), null, onDone));
        }

        /// <summary>POST /api/unity/auth/login</summary>
        public void Login(string email, string password, Action<APIResponse<LoginResponse>> onDone)
        {
            var body = new LoginRequest
            {
                email = email,
                password = password,
                unity_version = Application.unityVersion,
                unity_platform = Application.platform.ToString(),
                device_id = SystemInfo.deviceUniqueIdentifier,
                session_data = new Dictionary<string, object>
                {
                    { "platform", Application.platform.ToString() },
                    { "device_model", SystemInfo.deviceModel },
                    { "device_name", SystemInfo.deviceName }
                }
            };
            StartCoroutine(PostJson(FullUrl("unity/auth/login"), body, onDone));
        }

        /// <summary>POST /api/unity/auth/logout (requiere token)</summary>
        public void Logout(Action<APIResponse<object>> onDone)
        {
            StartCoroutine(PostJson(FullUrl("unity/auth/logout"), (object)null, onDone));
        }

        /// <summary>GET /api/unity/auth/me (requiere token)</summary>
        public void Me(Action<APIResponse<UserData>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/auth/me"), null, onDone));
        }

        /// <summary>GET /api/unity/auth/session/active (requiere token)</summary>
        public void GetActiveSession(Action<APIResponse<object>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/auth/session/active"), null, onDone));
        }

        #endregion

        #region Sesiones

        /// <summary>GET /api/unity/sesiones/buscar-por-codigo/{codigo}</summary>
        public void SesionesBuscarPorCodigo(string codigo, Action<APIResponse<SesionData>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/sesiones/buscar-por-codigo/" + Uri.EscapeDataString(codigo)), null, onDone));
        }

        /// <summary>GET /api/unity/sesiones/{id}/mi-rol</summary>
        public void SesionesMiRol(int sesionId, Action<APIResponse<object>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/sesiones/" + sesionId + "/mi-rol"), null, onDone));
        }

        /// <summary>POST /api/unity/sesiones/{id}/confirmar-rol</summary>
        public void SesionesConfirmarRol(int sesionId, Action<APIResponse<object>> onDone)
        {
            StartCoroutine(PostJson(FullUrl("unity/sesiones/" + sesionId + "/confirmar-rol"), new { }, onDone));
        }

        /// <summary>GET /api/unity/sesiones/disponibles</summary>
        public void SesionesDisponibles(Action<APIResponse<List<SesionData>>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/sesiones/disponibles"), null, onDone));
        }

        #endregion

        #region Diálogo (sesion)

        /// <summary>POST /api/unity/sesion/{sesionJuicio}/iniciar-dialogo (solo instructor/admin)</summary>
        public void IniciarDialogo(int sesionJuicio, Action<APIResponse<object>> onDone)
        {
            StartCoroutine(PostJson(FullUrl("unity/sesion/" + sesionJuicio + "/iniciar-dialogo"), null, onDone));
        }

        /// <summary>GET /api/unity/sesion/{sesionJuicio}/dialogo-estado</summary>
        public void GetDialogoEstado(int sesionJuicio, Action<APIResponse<DialogoEstado>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/sesion/" + sesionJuicio + "/dialogo-estado"), null, onDone));
        }

        /// <summary>GET /api/unity/sesion/{sesionJuicio}/respuestas-usuario/{usuario}</summary>
        public void GetRespuestasUsuario(int sesionJuicio, int usuarioId, Action<APIResponse<RespuestasResponse>> onDone)
        {
            StartCoroutine(GetJson(FullUrl($"unity/sesion/{sesionJuicio}/respuestas-usuario/{usuarioId}"), null, onDone));
        }

        /// <summary>POST /api/unity/sesion/{sesionJuicio}/enviar-decision</summary>
        public void EnviarDecision(int sesionJuicio, int usuarioId, int respuestaId, string decisionTexto, int tiempoRespuesta, Action<APIResponse<DecisionResponse>> onDone)
        {
            var body = new DecisionRequest
            {
                usuario_id = usuarioId,
                respuesta_id = respuestaId,
                decision_texto = decisionTexto ?? "",
                tiempo_respuesta = tiempoRespuesta,
                metadata = new Dictionary<string, object>
                {
                    { "unity_timestamp", DateTimeOffset.UtcNow.ToUnixTimeMilliseconds() },
                    { "unity_platform", Application.platform.ToString() }
                }
            };
            StartCoroutine(PostJson(FullUrl("unity/sesion/" + sesionJuicio + "/enviar-decision"), body, onDone));
        }

        /// <summary>POST /api/unity/sesion/{sesionJuicio}/notificar-hablando</summary>
        public void NotificarHablando(int sesionJuicio, int usuarioId, string estado, Action<APIResponse<object>> onDone)
        {
            var body = new HablandoRequest
            {
                usuario_id = usuarioId,
                estado = estado ?? "hablando",
                metadata = new Dictionary<string, object> { { "unity_timestamp", DateTimeOffset.UtcNow.ToUnixTimeMilliseconds() } }
            };
            StartCoroutine(PostJson(FullUrl("unity/sesion/" + sesionJuicio + "/notificar-hablando"), body, onDone));
        }

        /// <summary>GET /api/unity/sesion/{sesionJuicio}/movimientos-personajes</summary>
        public void GetMovimientosPersonajes(int sesionJuicio, Action<APIResponse<object>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/sesion/" + sesionJuicio + "/movimientos-personajes"), null, onDone));
        }

        #endregion

        #region Realtime

        /// <summary>POST /api/unity/sesion/{sesionJuicio}/broadcast</summary>
        public void BroadcastEvent(int sesionJuicio, string eventType, object payload, Action<APIResponse<object>> onDone)
        {
            var body = new BroadcastEventRequest { event_type = eventType, payload = payload, metadata = new Dictionary<string, object>() };
            StartCoroutine(PostJson(FullUrl("unity/sesion/" + sesionJuicio + "/broadcast"), body, onDone));
        }

        /// <summary>GET /api/unity/sesion/{sesionJuicio}/events/history</summary>
        public void GetEventHistory(int sesionJuicio, Action<APIResponse<object>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/sesion/" + sesionJuicio + "/events/history"), null, onDone));
        }

        #endregion

        #region Rooms

        /// <summary>POST /api/unity/rooms/create</summary>
        public void RoomsCreate(string nombre, int sesionJuicioId, int maxParticipantes, Action<APIResponse<RoomCreateResponse>> onDone)
        {
            var body = new RoomCreateRequest
            {
                nombre = nombre,
                sesion_juicio_id = sesionJuicioId,
                max_participantes = maxParticipantes > 0 ? maxParticipantes : 20
            };
            StartCoroutine(PostJson(FullUrl("unity/rooms/create"), body, onDone));
        }

        /// <summary>GET /api/unity/rooms/{roomId}/join</summary>
        public void RoomsJoin(string roomId, Action<APIResponse<object>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/rooms/" + Uri.EscapeDataString(roomId) + "/join"), null, onDone));
        }

        /// <summary>POST /api/unity/rooms/{roomId}/leave</summary>
        public void RoomsLeave(string roomId, Action<APIResponse<object>> onDone)
        {
            StartCoroutine(PostJson(FullUrl("unity/rooms/" + Uri.EscapeDataString(roomId) + "/leave"), null, onDone));
        }

        /// <summary>GET /api/unity/rooms/{roomId}/state</summary>
        public void RoomsGetState(string roomId, Action<APIResponse<object>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/rooms/" + Uri.EscapeDataString(roomId) + "/state"), null, onDone));
        }

        /// <summary>POST /api/unity/rooms/{roomId}/sync-player</summary>
        public void RoomsSyncPlayer(string roomId, int usuarioId, string nombre, string rolNombre, Action<APIResponse<object>> onDone)
        {
            var body = new SyncPlayerRequest
            {
                usuario_id = usuarioId,
                nombre = nombre,
                rol_nombre = rolNombre,
                position = new Dictionary<string, object>(),
                estado = new Dictionary<string, object>()
            };
            StartCoroutine(PostJson(FullUrl("unity/rooms/" + Uri.EscapeDataString(roomId) + "/sync-player"), body, onDone));
        }

        /// <summary>POST /api/unity/rooms/{roomId}/audio-state</summary>
        public void RoomsAudioState(string roomId, int usuarioId, bool muted, bool speaking, Action<APIResponse<object>> onDone)
        {
            var body = new AudioStateRequest { usuario_id = usuarioId, muted = muted, speaking = speaking, metadata = new Dictionary<string, object>() };
            StartCoroutine(PostJson(FullUrl("unity/rooms/" + Uri.EscapeDataString(roomId) + "/audio-state"), body, onDone));
        }

        /// <summary>GET /api/unity/rooms/{roomId}/events</summary>
        public void RoomsGetEvents(string roomId, Action<APIResponse<object>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/rooms/" + Uri.EscapeDataString(roomId) + "/events"), null, onDone));
        }

        /// <summary>POST /api/unity/rooms/{roomId}/close</summary>
        public void RoomsClose(string roomId, Action<APIResponse<object>> onDone)
        {
            StartCoroutine(PostJson(FullUrl("unity/rooms/" + Uri.EscapeDataString(roomId) + "/close"), null, onDone));
        }

        /// <summary>GET /api/unity/rooms/{roomId}/livekit-token</summary>
        public void RoomsGetLiveKitToken(string roomId, Action<APIResponse<object>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/rooms/" + Uri.EscapeDataString(roomId) + "/livekit-token"), null, onDone));
        }

        /// <summary>GET /api/unity/rooms/{roomId}/livekit-status</summary>
        public void RoomsGetLiveKitStatus(string roomId, Action<APIResponse<object>> onDone)
        {
            StartCoroutine(GetJson(FullUrl("unity/rooms/" + Uri.EscapeDataString(roomId) + "/livekit-status"), null, onDone));
        }

        #endregion

        #region Helpers HTTP

        private IEnumerator GetJson<T>(string url, object _, Action<APIResponse<T>> onDone)
        {
            if (logRequests) Debug.Log("[UnityApiClient] GET " + url);
            using (var req = UnityWebRequest.Get(url))
            {
                ApplyAuth(req);
                yield return req.SendWebRequest();
                HandleResponse(req, onDone);
            }
        }

        private IEnumerator PostJson<T>(string url, object body, Action<APIResponse<T>> onDone)
        {
            if (logRequests) Debug.Log("[UnityApiClient] POST " + url);
            string json = body != null ? JsonConvert.SerializeObject(body) : "{}";
            byte[] raw = Encoding.UTF8.GetBytes(json);
            using (var req = new UnityWebRequest(url, "POST", new DownloadHandlerBuffer(), new UploadHandlerRaw(raw)))
            {
                ApplyAuth(req);
                yield return req.SendWebRequest();
                HandleResponse(req, onDone);
            }
        }

        private void HandleResponse<T>(UnityWebRequest req, Action<APIResponse<T>> onDone)
        {
            try
            {
                string text = req.downloadHandler?.text ?? "";
                if (req.result != UnityWebRequest.Result.Success)
                {
                    var err = new APIResponse<T> { success = false, message = req.error + (string.IsNullOrEmpty(text) ? "" : " | " + text) };
                    onDone?.Invoke(err);
                    return;
                }
                var response = JsonConvert.DeserializeObject<APIResponse<T>>(text);
                onDone?.Invoke(response ?? new APIResponse<T> { success = false, message = "Respuesta no válida" });
            }
            catch (Exception e)
            {
                onDone?.Invoke(new APIResponse<T> { success = false, message = e.Message });
            }
        }

        #endregion
    }
}
