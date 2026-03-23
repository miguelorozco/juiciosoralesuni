using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using JuiciosSimulator.API;

/// <summary>
/// Driver del bucle de diálogo Laravel/Unity: obtiene estado, muestra respuestas cuando es mi turno y envía decisiones.
/// No incluye UI; suscribirse a los eventos o leer el estado actual para enlazar con tu UI.
/// Requiere UnityApiClient en escena y UnityBridgeConfig.Token asignado.
/// </summary>
public class DialogoManager : MonoBehaviour
{
    [Header("Configuración")]
    [Tooltip("ID de la sesión de juicio. Por defecto -1; se rellena con LaravelSessionData al entrar desde la web. En editor puedes poner un ID para pruebas.")]
    public int sesionJuicioId = -1;
    [Tooltip("ID del usuario actual. Por defecto -1; se rellena con LaravelSessionData al entrar desde la web. En editor puedes poner un ID para pruebas.")]
    public int usuarioId = -1;
    [Tooltip("Intervalo de polling del estado (segundos). 0 = no hacer polling automático")]
    public float pollingInterval = 2f;
    [Tooltip("Si true, al iniciar hace un refresh inmediato del estado")]
    public bool refreshOnStart = true;

    [Header("Estado actual (solo lectura en runtime)")]
    [SerializeField] private bool _dialogoActivo;
    [SerializeField] private string _estadoSesionDialogo;
    [SerializeField] private int _miRolId = -1;
    [SerializeField] private bool _esMiTurno;

    private DialogoEstado _estadoActual;
    private List<RespuestaUsuario> _respuestasActuales = new List<RespuestaUsuario>();
    private float _tiempoInicioRespuesta;
    private Coroutine _pollingCoroutine;
    private int _estadoRequestVersion;
    private int _ultimoEstadoProcesado;
    private int _respuestasRequestVersion;
    private int _ultimoRespuestasProcesadas;
    private bool _estadoRequestEnCurso;
    private bool _respuestasRequestEnCurso;
    private bool _navegadorEnFoco = true;
    private int _nodoIdDeRespuestasActuales = -1;
    private int _respuestaPendienteId = -1;
    private string _decisionPendienteTexto = "";
    private int _decisionPendienteTiempo;
    private bool _decisionPendienteEsReintento;

    /// <summary>Estado actual del diálogo (null si no hay o no se ha cargado).</summary>
    public DialogoEstado EstadoActual => _estadoActual;

    /// <summary>Diálogo activo según última respuesta del servidor.</summary>
    public bool DialogoActivo => _dialogoActivo;

    /// <summary>Es el turno del usuario actual (rol del nodo = mi rol).</summary>
    public bool EsMiTurno => _esMiTurno;

    /// <summary>True si puede actuar en este nodo (su turno o instructor que puede avanzar por otro rol).</summary>
    public bool PuedoActuar => _esMiTurno || (_estadoActual?.puede_actuar ?? false);

    /// <summary>Respuestas disponibles en este momento (solo si es mi turno).</summary>
    public IReadOnlyList<RespuestaUsuario> RespuestasActuales => _respuestasActuales;

    /// <summary>ID del rol del usuario actual en esta sesión (-1 si no conocido).</summary>
    public int MiRolId => _miRolId;

    public static event Action<DialogoEstado> OnEstadoActualizado;
    public static event Action<List<RespuestaUsuario>> OnRespuestasDisponibles;
    public static event Action<string> OnError;
    public static event Action<string> OnInfo;
    public static event Action<bool> OnDialogoFinalizado;

    private void Start()
    {
        if (UnityApiClient.Instance == null)
        {
            Debug.LogError("DialogoManager: UnityApiClient no encontrado en la escena.");
            return;
        }

        if (LaravelSessionData.HasData)
        {
            sesionJuicioId = LaravelSessionData.SessionId;
            usuarioId = LaravelSessionData.UserId;
        }

        if (sesionJuicioId < 0 || usuarioId < 0)
        {
            Debug.LogWarning("DialogoManager: sesión o usuario no configurados (usa la web para entrar o asigna IDs en el inspector para pruebas). No se hará polling ni refresh.");
            return;
        }

        if (refreshOnStart)
            RefrescarEstado();

        if (pollingInterval > 0f)
            _pollingCoroutine = StartCoroutine(PollingCoroutine());
    }

    private void OnDestroy()
    {
        if (_pollingCoroutine != null)
            StopCoroutine(_pollingCoroutine);
    }

    private IEnumerator PollingCoroutine()
    {
        var wait = new WaitForSeconds(pollingInterval);
        while (true)
        {
            yield return wait;
            RefrescarEstado();
        }
    }

    /// <summary>Obtiene el estado actual del diálogo del servidor.</summary>
    public void RefrescarEstado()
    {
        if (UnityApiClient.Instance == null || sesionJuicioId < 0 || usuarioId < 0) return;

        int requestVersion = ++_estadoRequestVersion;
        _estadoRequestEnCurso = true;
        UnityApiClient.Instance.GetDialogoEstado(sesionJuicioId, response => OnDialogoEstadoReceived(requestVersion, response));
    }

    private void OnDialogoEstadoReceived(int requestVersion, APIResponse<DialogoEstado> response)
    {
        if (requestVersion < _estadoRequestVersion || requestVersion <= _ultimoEstadoProcesado)
            return;

        _ultimoEstadoProcesado = requestVersion;
        _estadoRequestEnCurso = false;

        if (!response.success)
        {
            _dialogoActivo = false;
            _estadoActual = response.data;
            _nodoIdDeRespuestasActuales = -1;
            OnError?.Invoke(response.message ?? "Error al obtener estado");
            if (_estadoActual != null)
                OnEstadoActualizado?.Invoke(_estadoActual);
            return;
        }

        _estadoActual = response.data;
        _dialogoActivo = _estadoActual != null && _estadoActual.dialogo_activo;
        _estadoSesionDialogo = _estadoActual?.estado ?? "";
        if (!_dialogoActivo)
            _nodoIdDeRespuestasActuales = -1;
        _miRolId = -1;
        _esMiTurno = false;

#if UNITY_EDITOR || DEVELOPMENT_BUILD
        if (_estadoActual != null)
            UnityApiTypesValidator.ValidateDialogoEstado(_estadoActual, logWarnings: true);
#endif

        if (_estadoActual?.participantes != null)
        {
            foreach (var p in _estadoActual.participantes)
            {
                if (p.usuario_id == usuarioId)
                {
                    _miRolId = p.rol != null ? p.rol.id : -1;
                    _esMiTurno = p.es_turno;
                    break;
                }
            }
        }

        OnEstadoActualizado?.Invoke(_estadoActual);

        if (_estadoActual?.estado == "finalizado" || (_estadoActual?.nodo_actual?.es_final ?? false))
            OnDialogoFinalizado?.Invoke(true);

        bool puedeActuar = _esMiTurno || (_estadoActual?.puede_actuar ?? false);
        if (_navegadorEnFoco && puedeActuar && _dialogoActivo)
        {
            UnityDebugLog.ToLaravel("dialogo_solicitar_respuestas", "Solicitando respuestas (Tu turno o instructor)", new System.Collections.Generic.Dictionary<string, object> {
                { "sesion_id", sesionJuicioId },
                { "usuario_id", usuarioId },
                { "es_mi_turno", _esMiTurno },
                { "puede_actuar", _estadoActual?.puede_actuar ?? false },
            });
            CargarRespuestasUsuario();
        }
        else
            _respuestasActuales.Clear();
    }

    private void CargarRespuestasUsuario()
    {
        if (UnityApiClient.Instance == null || sesionJuicioId < 0 || usuarioId < 0) return;

        int requestVersion = ++_respuestasRequestVersion;
        _respuestasRequestEnCurso = true;
        UnityApiClient.Instance.GetRespuestasUsuario(sesionJuicioId, usuarioId, response => OnRespuestasReceived(requestVersion, response));
    }

    private void OnRespuestasReceived(int requestVersion, APIResponse<RespuestasResponse> response)
    {
        if (requestVersion < _respuestasRequestVersion || requestVersion <= _ultimoRespuestasProcesadas)
            return;

        _ultimoRespuestasProcesadas = requestVersion;
        _respuestasRequestEnCurso = false;
        _respuestasActuales.Clear();
        _nodoIdDeRespuestasActuales = -1;

        if (!response.success || response.data == null)
        {
            UnityDebugLog.ToLaravel("dialogo_respuestas_error", "API respuestas falló o data null. Revisar laravel.log para 'Unity obtenerRespuestasUsuario: excepción' o 'Unity respuestas-usuario:'. Mensaje servidor: " + (response.message ?? "(vacío)"), new System.Collections.Generic.Dictionary<string, object> {
                { "success", response.success },
                { "message", response.message ?? "" },
            });
            return;
        }

        bool disponibles = response.data.respuestas_disponibles;
        var lista = response.data.respuestas;
        int count = lista != null ? lista.Count : 0;

        UnityDebugLog.ToLaravel("dialogo_respuestas_recibidas", disponibles ? (count > 0 ? "Mostrando " + count + " opciones" : "Mostrando botón Continuar") : "No hay opciones (no es turno)", new System.Collections.Generic.Dictionary<string, object> {
                { "respuestas_disponibles", disponibles },
                { "count", count },
            });

#if UNITY_EDITOR || DEVELOPMENT_BUILD
        UnityApiTypesValidator.ValidateRespuestasResponse(response.data, logWarnings: true);
#endif

        if (!_navegadorEnFoco || !disponibles)
            return;

        _nodoIdDeRespuestasActuales = response.data.nodo_actual != null
            ? response.data.nodo_actual.id
            : (_estadoActual?.nodo_actual?.id ?? -1);

        if (lista != null)
            _respuestasActuales.AddRange(lista);
        OnRespuestasDisponibles?.Invoke(_respuestasActuales);
        IntentarDecisionPendiente();
    }

    /// <summary>Envía la decisión elegida. Llamar cuando el usuario seleccione una respuesta.</summary>
    /// <param name="respuestaId">ID de la respuesta elegida.</param>
    /// <param name="decisionTexto">Texto adicional opcional.</param>
    /// <param name="tiempoRespuesta">Segundos desde que se mostraron las opciones (0 para calcular desde último refresh).</param>
    public void EnviarDecision(int respuestaId, string decisionTexto = "", int tiempoRespuesta = 0)
    {
        if (UnityApiClient.Instance == null)
        {
            OnError?.Invoke("UnityApiClient no disponible");
            return;
        }

        if (!_dialogoActivo || _estadoActual?.nodo_actual == null)
        {
            ProgramarDecisionPendiente(respuestaId, decisionTexto, tiempoRespuesta, false, "Sincronizando diálogo: el nodo cambió antes de enviar la decisión.");
            return;
        }

        if (tiempoRespuesta <= 0 && _tiempoInicioRespuesta > 0f)
            tiempoRespuesta = Mathf.RoundToInt(Time.time - _tiempoInicioRespuesta);

        int nodoActualId = _estadoActual.nodo_actual.id;
        if (_nodoIdDeRespuestasActuales > 0 && _nodoIdDeRespuestasActuales != nodoActualId)
        {
            ProgramarDecisionPendiente(respuestaId, decisionTexto, tiempoRespuesta, false, "Sincronizando diálogo: las opciones visibles ya no corresponden al nodo actual.");
            return;
        }

        EnviarDecisionInterna(respuestaId, decisionTexto, tiempoRespuesta, false);
    }

    private void EnviarDecisionInterna(int respuestaId, string decisionTexto, int tiempoRespuesta, bool esReintento)
    {
        if (UnityApiClient.Instance == null || _estadoActual?.nodo_actual == null)
            return;

        int nodoActualId = _estadoActual.nodo_actual.id;
        UnityApiClient.Instance.EnviarDecision(
            sesionJuicioId,
            usuarioId,
            respuestaId,
            nodoActualId,
            decisionTexto ?? "",
            tiempoRespuesta,
            response => OnDecisionEnviada(response, respuestaId, decisionTexto ?? "", tiempoRespuesta, esReintento)
        );
    }

    private void OnDecisionEnviada(APIResponse<DecisionResponse> response, int respuestaId, string decisionTexto, int tiempoRespuesta, bool esReintento)
    {
        if (!response.success)
        {
            if (!esReintento && EsErrorDeSincronizacion(response.message))
            {
                ProgramarDecisionPendiente(respuestaId, decisionTexto, tiempoRespuesta, true, "Sincronizando diálogo para reintentar la decisión.");
                return;
            }

            LimpiarDecisionPendiente();
            OnError?.Invoke(response.message ?? "Error al enviar decisión");
            RefrescarEstado();
            return;
        }

        LimpiarDecisionPendiente();
        if (response.data?.nuevo_estado?.dialogo_finalizado ?? false)
            OnDialogoFinalizado?.Invoke(true);

        RefrescarEstado();
    }

    private void ProgramarDecisionPendiente(int respuestaId, string decisionTexto, int tiempoRespuesta, bool esReintento, string mensaje)
    {
        if (esReintento && _decisionPendienteEsReintento)
        {
            LimpiarDecisionPendiente();
            OnInfo?.Invoke("El diálogo se sincronizó, pero la opción elegida ya no pudo reenviarse automáticamente. Elige una nueva respuesta.");
            RefrescarEstado();
            return;
        }

        _respuestaPendienteId = respuestaId;
        _decisionPendienteTexto = decisionTexto ?? "";
        _decisionPendienteTiempo = tiempoRespuesta;
        _decisionPendienteEsReintento = esReintento;
        OnInfo?.Invoke(mensaje);
        RefrescarEstado();
    }

    private void IntentarDecisionPendiente()
    {
        if (_respuestaPendienteId <= 0)
            return;

        var respuesta = _respuestasActuales.Find(r => r.id == _respuestaPendienteId);
        if (respuesta == null)
        {
            LimpiarDecisionPendiente();
            OnInfo?.Invoke("El diálogo se sincronizó, pero la opción elegida ya no está disponible. Elige una nueva respuesta.");
            return;
        }

        int respuestaId = _respuestaPendienteId;
        string decisionTexto = _decisionPendienteTexto;
        int tiempoRespuesta = _decisionPendienteTiempo;
        bool esReintento = _decisionPendienteEsReintento;

        LimpiarDecisionPendiente();
        OnInfo?.Invoke("Estado sincronizado. Reintentando la decisión.");
        EnviarDecisionInterna(respuestaId, decisionTexto, tiempoRespuesta, esReintento);
    }

    private void LimpiarDecisionPendiente()
    {
        _respuestaPendienteId = -1;
        _decisionPendienteTexto = "";
        _decisionPendienteTiempo = 0;
        _decisionPendienteEsReintento = false;
    }

    private static bool EsErrorDeSincronizacion(string mensaje)
    {
        if (string.IsNullOrEmpty(mensaje))
            return false;

        string normalizado = mensaje.ToLowerInvariant();
        return normalizado.Contains("sincron")
            || normalizado.Contains("nodo actual")
            || normalizado.Contains("cambió antes")
            || normalizado.Contains("corresponde al nodo");
    }

    /// <summary>Avanza al siguiente nodo cuando el actual no tiene opciones (nodo narrativo). Llama al API y refresca estado.</summary>
    public void AvanzarNodo(Action<bool, string> onDone = null)
    {
        if (UnityApiClient.Instance == null)
        {
            onDone?.Invoke(false, "UnityApiClient no disponible");
            return;
        }
        UnityApiClient.Instance.AvanzarNodo(sesionJuicioId, response =>
        {
            if (response.success)
                RefrescarEstado();
            onDone?.Invoke(response.success, response.message ?? "");
        });
    }

    /// <summary>Inicia el diálogo (estado iniciado -> en_curso). Solo instructor/admin.</summary>
    public void IniciarDialogo(Action<bool, string> onDone = null)
    {
        if (UnityApiClient.Instance == null)
        {
            onDone?.Invoke(false, "UnityApiClient no disponible");
            return;
        }

        UnityApiClient.Instance.IniciarDialogo(sesionJuicioId, response =>
        {
            if (response.success)
                RefrescarEstado();
            onDone?.Invoke(response.success, response.message ?? "");
        });
    }

    /// <summary>Llamar cuando se muestren las respuestas al usuario (para medir tiempo de respuesta).</summary>
    public void MarcarInicioRespuesta()
    {
        _tiempoInicioRespuesta = Time.time;
    }

    public void BrowserFocusChanged(string state)
    {
        bool hasFocus = !string.Equals(state, "blur", StringComparison.OrdinalIgnoreCase);
        if (_navegadorEnFoco == hasFocus)
            return;

        _navegadorEnFoco = hasFocus;

        if (_navegadorEnFoco)
        {
            OnInfo?.Invoke("Sincronizando diálogo tras recuperar el foco de la ventana.");
            UnityDebugLog.ToLaravel("dialogo_browser_focus", "Foco del navegador recuperado; sincronizando diálogo", new Dictionary<string, object> {
                { "session_id", sesionJuicioId },
                { "user_id", usuarioId }
            });
            RefrescarEstado();
            return;
        }

        OnInfo?.Invoke("La ventana perdió el foco. Las opciones quedarán en pausa hasta resincronizar.");
        _respuestasActuales.Clear();
        OnRespuestasDisponibles?.Invoke(_respuestasActuales);
    }

    public void BrowserForceRefresh(string reason)
    {
        OnInfo?.Invoke("Sincronizando diálogo desde la interfaz web.");
        UnityDebugLog.ToLaravel("dialogo_browser_refresh", "Refresh forzado desde navegador", new Dictionary<string, object> {
            { "reason", reason ?? "" },
            { "session_id", sesionJuicioId },
            { "user_id", usuarioId }
        });
        RefrescarEstado();
    }

    private void OnApplicationFocus(bool hasFocus)
    {
        _navegadorEnFoco = hasFocus;
        if (hasFocus && !_estadoRequestEnCurso && !_respuestasRequestEnCurso)
            RefrescarEstado();
    }

    private void OnApplicationPause(bool pauseStatus)
    {
        _navegadorEnFoco = !pauseStatus;
        if (!pauseStatus && !_estadoRequestEnCurso && !_respuestasRequestEnCurso)
            RefrescarEstado();
    }

    /// <summary>Notifica al servidor que el usuario está hablando (para sincronizar con otros clientes).</summary>
    public void NotificarHablando(string estado)
    {
        if (UnityApiClient.Instance == null) return;
        UnityApiClient.Instance.NotificarHablando(sesionJuicioId, usuarioId, estado, _ => { });
    }
}
