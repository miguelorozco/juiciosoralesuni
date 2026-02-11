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

    /// <summary>Estado actual del diálogo (null si no hay o no se ha cargado).</summary>
    public DialogoEstado EstadoActual => _estadoActual;

    /// <summary>Diálogo activo según última respuesta del servidor.</summary>
    public bool DialogoActivo => _dialogoActivo;

    /// <summary>Es el turno del usuario actual (rol del nodo = mi rol).</summary>
    public bool EsMiTurno => _esMiTurno;

    /// <summary>Respuestas disponibles en este momento (solo si es mi turno).</summary>
    public IReadOnlyList<RespuestaUsuario> RespuestasActuales => _respuestasActuales;

    /// <summary>ID del rol del usuario actual en esta sesión (-1 si no conocido).</summary>
    public int MiRolId => _miRolId;

    public static event Action<DialogoEstado> OnEstadoActualizado;
    public static event Action<List<RespuestaUsuario>> OnRespuestasDisponibles;
    public static event Action<string> OnError;
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
        if (UnityApiClient.Instance == null) return;

        UnityApiClient.Instance.GetDialogoEstado(sesionJuicioId, OnDialogoEstadoReceived);
    }

    private void OnDialogoEstadoReceived(APIResponse<DialogoEstado> response)
    {
        if (!response.success)
        {
            _dialogoActivo = false;
            _estadoActual = response.data;
            OnError?.Invoke(response.message ?? "Error al obtener estado");
            if (_estadoActual != null)
                OnEstadoActualizado?.Invoke(_estadoActual);
            return;
        }

        _estadoActual = response.data;
        _dialogoActivo = _estadoActual != null && _estadoActual.dialogo_activo;
        _estadoSesionDialogo = _estadoActual?.estado ?? "";

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

        if (_esMiTurno && _dialogoActivo)
            CargarRespuestasUsuario();
        else
            _respuestasActuales.Clear();
    }

    private void CargarRespuestasUsuario()
    {
        if (UnityApiClient.Instance == null) return;

        UnityApiClient.Instance.GetRespuestasUsuario(sesionJuicioId, usuarioId, OnRespuestasReceived);
    }

    private void OnRespuestasReceived(APIResponse<RespuestasResponse> response)
    {
        _respuestasActuales.Clear();
        if (!response.success || response.data == null)
            return;
        if (!response.data.respuestas_disponibles || response.data.respuestas == null)
            return;
        _respuestasActuales.AddRange(response.data.respuestas);
        OnRespuestasDisponibles?.Invoke(_respuestasActuales);
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

        if (tiempoRespuesta <= 0 && _tiempoInicioRespuesta > 0f)
            tiempoRespuesta = Mathf.RoundToInt(Time.time - _tiempoInicioRespuesta);

        UnityApiClient.Instance.EnviarDecision(
            sesionJuicioId,
            usuarioId,
            respuestaId,
            decisionTexto ?? "",
            tiempoRespuesta,
            OnDecisionEnviada
        );
    }

    private void OnDecisionEnviada(APIResponse<DecisionResponse> response)
    {
        if (!response.success)
        {
            OnError?.Invoke(response.message ?? "Error al enviar decisión");
            return;
        }

        if (response.data?.nuevo_estado?.dialogo_finalizado ?? false)
            OnDialogoFinalizado?.Invoke(true);

        RefrescarEstado();
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

    /// <summary>Notifica al servidor que el usuario está hablando (para sincronizar con otros clientes).</summary>
    public void NotificarHablando(string estado)
    {
        if (UnityApiClient.Instance == null) return;
        UnityApiClient.Instance.NotificarHablando(sesionJuicioId, usuarioId, estado, _ => { });
    }
}
