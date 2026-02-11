using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;
using TMPro;
using JuiciosSimulator.API;

/// <summary>
/// UI del panel de diálogo: se suscribe a DialogoManager y actualiza textos, botones de respuesta y envía decisiones.
/// Asignar referencias en el Inspector o deja que las resuelva por nombre bajo el panel.
/// </summary>
public class DialogoUIController : MonoBehaviour
{
    [Header("Referencias (opcional; si están vacías se buscan por nombre)")]
    [Tooltip("Panel raíz del diálogo (se oculta si no hay diálogo activo)")]
    public GameObject panelRoot;
    [Tooltip("Título del nodo actual")]
    public TMP_Text tituloNodo;
    [Tooltip("Contenido del nodo actual")]
    public TMP_Text contenidoNodo;
    [Tooltip("Rol que está hablando")]
    public TMP_Text rolHablando;
    [Tooltip("Progreso (ej. 0.5)")]
    public TMP_Text progreso;
    [Tooltip("Tiempo transcurrido")]
    public TMP_Text tiempo;
    [Tooltip("Mensaje Tu turno / Esperando a...")]
    public TMP_Text mensajeTurno;
    [Tooltip("Contenedor donde se crean los botones de respuesta")]
    public Transform contenedorRespuestas;
    [Tooltip("Prefab del botón de respuesta (debe tener Button y un hijo TMP_Text)")]
    public GameObject botonRespuestaPrefab;
    [Tooltip("Botón para iniciar el diálogo")]
    public Button botonIniciarDialogo;
    [Tooltip("Mensaje de error o estado final")]
    public TMP_Text mensajeErrorOFinal;
    [Tooltip("Opcional: muestra Sesión ID y Usuario ID (ej. 'Sesión: 1 | Usuario: 3')")]
    public TMP_Text textoSesionUsuario;

    private DialogoManager _dialogoManager;

    private void Awake()
    {
        _dialogoManager = FindFirstObjectByType<DialogoManager>();
        if (_dialogoManager == null)
        {
            Debug.LogError("DialogoUIController: DialogoManager no encontrado en la escena.");
            return;
        }

        ResolverReferencias();
    }

    private void ResolverReferencias()
    {
        if (panelRoot == null) panelRoot = transform.Find("PanelDialogo")?.gameObject ?? gameObject;
        if (tituloNodo == null) tituloNodo = panelRoot?.transform.Find("TituloNodo")?.GetComponent<TMP_Text>();
        if (contenidoNodo == null) contenidoNodo = panelRoot?.transform.Find("ContenidoNodo")?.GetComponent<TMP_Text>();
        if (rolHablando == null) rolHablando = panelRoot?.transform.Find("RolHablando")?.GetComponent<TMP_Text>();
        if (progreso == null) progreso = panelRoot?.transform.Find("Progreso")?.GetComponent<TMP_Text>();
        if (tiempo == null) tiempo = panelRoot?.transform.Find("Tiempo")?.GetComponent<TMP_Text>();
        if (mensajeTurno == null) mensajeTurno = panelRoot?.transform.Find("MensajeTurno")?.GetComponent<TMP_Text>();
        if (contenedorRespuestas == null) contenedorRespuestas = panelRoot?.transform.Find("ContenedorRespuestas");
        if (mensajeErrorOFinal == null) mensajeErrorOFinal = panelRoot?.transform.Find("MensajeError")?.GetComponent<TMP_Text>();
        if (textoSesionUsuario == null) textoSesionUsuario = panelRoot?.transform.Find("TextoSesionUsuario")?.GetComponent<TMP_Text>();
        if (botonIniciarDialogo == null) botonIniciarDialogo = panelRoot?.transform.Find("BotonIniciarDialogo")?.GetComponent<Button>();
        if (botonRespuestaPrefab == null && contenedorRespuestas != null)
        {
            var canvas = contenedorRespuestas.GetComponentInParent<Canvas>();
            if (canvas != null)
            {
                var btn = canvas.transform.Find("BotonRespuesta");
                if (btn != null) botonRespuestaPrefab = btn.gameObject;
            }
        }
    }

    private void Start()
    {
        if (_dialogoManager == null) return;

        DialogoManager.OnEstadoActualizado += OnEstadoActualizado;
        DialogoManager.OnRespuestasDisponibles += OnRespuestasDisponibles;
        DialogoManager.OnError += OnError;
        DialogoManager.OnDialogoFinalizado += OnDialogoFinalizado;

        if (botonIniciarDialogo != null)
            botonIniciarDialogo.onClick.AddListener(OnClickIniciarDialogo);

        // Mostrar el canvas de diálogos desde el inicio con mensaje "Inicio." y estado
        if (panelRoot != null) panelRoot.SetActive(true);
        ActualizarTituloConInfoDialogo(null);
        SetText(contenidoNodo, "Inicio.");
        SetText(rolHablando, "");
        SetText(progreso, "");
        SetText(tiempo, "");
        SetText(mensajeTurno, "Cargando estado...");
        SetText(mensajeErrorOFinal, "");
        ActualizarTextoSesionUsuario();

        _dialogoManager.RefrescarEstado();
    }

    /// <summary>Actualiza el título del panel con sesión, usuario e info del diálogo (debug).</summary>
    private void ActualizarTituloConInfoDialogo(DialogoEstado estado)
    {
        if (tituloNodo == null || _dialogoManager == null) return;
        var sb = new System.Text.StringBuilder();
        sb.Append("Estado diálogos (Sesión ").Append(_dialogoManager.sesionJuicioId)
          .Append(", Usuario ").Append(_dialogoManager.usuarioId).Append(")");
        if (estado != null)
        {
            if (estado.dialogo_activo && !string.IsNullOrEmpty(estado.dialogo_nombre))
                sb.Append(" · Diálogo: ").Append(estado.dialogo_nombre).Append(" (ID: ").Append(estado.dialogo_id).Append(")");
            else if (!string.IsNullOrEmpty(estado.dialogo_configurado_nombre))
                sb.Append(" · Configurado: ").Append(estado.dialogo_configurado_nombre).Append(" (ID: ").Append(estado.dialogo_configurado_id).Append(", no activo)");
            else
                sb.Append(" · Sin diálogo configurado");
        }
        tituloNodo.text = sb.ToString();
    }

    private void ActualizarTextoSesionUsuario()
    {
        if (textoSesionUsuario == null || _dialogoManager == null) return;
        textoSesionUsuario.text = $"Sesión: {_dialogoManager.sesionJuicioId} | Usuario: {_dialogoManager.usuarioId}";
    }

    private void OnDestroy()
    {
        DialogoManager.OnEstadoActualizado -= OnEstadoActualizado;
        DialogoManager.OnRespuestasDisponibles -= OnRespuestasDisponibles;
        DialogoManager.OnError -= OnError;
        DialogoManager.OnDialogoFinalizado -= OnDialogoFinalizado;
    }

    private void OnEstadoActualizado(DialogoEstado estado)
    {
        ActualizarTextoSesionUsuario();
        ActualizarTituloConInfoDialogo(estado);
        if (panelRoot != null)
            panelRoot.SetActive(true);

        if (estado == null || !estado.dialogo_activo)
        {
            bool sinAsignar = estado != null && estado.dialogo_configurado_id == 0 && string.IsNullOrEmpty(estado.dialogo_configurado_nombre);
            SetText(contenidoNodo, sinAsignar
                ? "Esta sesión no tiene un diálogo asignado.\n\nVe a la web → Sesiones → Editar esta sesión → elige \"Diálogo a utilizar\" y guarda. Luego recarga Unity."
                : "No hay diálogo activo.");
            SetText(rolHablando, "");
            SetText(progreso, "");
            SetText(tiempo, "");
            SetText(mensajeTurno, "");
            LimpiarRespuestas();
            return;
        }

        var nodo = estado.nodo_actual;
        var rol = nodo?.rol_hablando;
        SetText(tituloNodo, nodo?.titulo ?? "");
        SetText(contenidoNodo, nodo?.contenido ?? "");
        SetText(rolHablando, rol != null ? rol.nombre : "");
        float p = estado.progreso ?? 0f;
        SetText(progreso, p > 0 ? $"{p:P0}" : "");
        SetText(tiempo, estado.tiempo_transcurrido > 0 ? $"{estado.tiempo_transcurrido}s" : "");
        SetText(mensajeErrorOFinal, "");

        if (_dialogoManager.EsMiTurno)
            SetText(mensajeTurno, "Tu turno");
        else
            SetText(mensajeTurno, rol != null ? $"Esperando a {rol.nombre}..." : "Esperando...");

        if (!_dialogoManager.EsMiTurno)
            LimpiarRespuestas();
    }

    private void OnRespuestasDisponibles(List<RespuestaUsuario> respuestas)
    {
        _dialogoManager.MarcarInicioRespuesta();
        LimpiarRespuestas();
        if (contenedorRespuestas == null || botonRespuestaPrefab == null || respuestas == null) return;

        foreach (var r in respuestas)
        {
            var resp = r;
            var btnGo = Instantiate(botonRespuestaPrefab, contenedorRespuestas);
            btnGo.SetActive(true);
            var btn = btnGo.GetComponent<Button>();
            var label = btnGo.GetComponentInChildren<TMP_Text>();
            if (label != null) label.text = resp.texto ?? "";
            if (btn != null)
                btn.onClick.AddListener(() => EnviarDecision(resp.id));
        }
    }

    private void EnviarDecision(int respuestaId)
    {
        if (_dialogoManager != null)
            _dialogoManager.EnviarDecision(respuestaId, "", 0);
        LimpiarRespuestas();
    }

    private void OnError(string mensaje)
    {
        ActualizarTextoSesionUsuario();
        SetText(mensajeErrorOFinal, mensaje ?? "Error");
    }

    private void OnDialogoFinalizado(bool _)
    {
        SetText(mensajeTurno, "Diálogo finalizado.");
        LimpiarRespuestas();
    }

    private void OnClickIniciarDialogo()
    {
        if (_dialogoManager == null) return;
        _dialogoManager.IniciarDialogo((ok, msg) =>
        {
            SetText(mensajeErrorOFinal, ok ? "Diálogo iniciado." : (msg ?? "Error"));
        });
    }

    private void LimpiarRespuestas()
    {
        if (contenedorRespuestas == null) return;
        for (int i = contenedorRespuestas.childCount - 1; i >= 0; i--)
        {
            var child = contenedorRespuestas.GetChild(i);
            if (child.gameObject.activeSelf && child.GetComponent<Button>() != null)
                Destroy(child.gameObject);
        }
    }

    private static void SetText(TMP_Text tmp, string text)
    {
        if (tmp != null) tmp.text = text ?? "";
    }
}
