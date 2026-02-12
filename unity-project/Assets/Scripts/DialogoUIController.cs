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
        AcomodarLabelTiempoSesion();
    }

    /// <summary>Formatea segundos totales como horas:minutos:segundos (ej. 0:00:00, 1:23:45).</summary>
    private static string FormatearTiempoSesion(float segundosTotales)
    {
        if (float.IsNaN(segundosTotales) || segundosTotales < 0) return "0:00:00";
        int s = Mathf.FloorToInt(segundosTotales);
        int h = s / 3600;
        int m = (s % 3600) / 60;
        int sec = s % 60;
        return $"{h}:{m:D2}:{sec:D2}";
    }

    /// <summary>Coloca el label de tiempo en la esquina superior derecha del panel, por encima del resto.</summary>
    private void AcomodarLabelTiempoSesion()
    {
        if (tiempo == null || panelRoot == null) return;
        var rect = tiempo.GetComponent<RectTransform>();
        if (rect == null) return;
        tiempo.transform.SetParent(panelRoot.transform, true);
        tiempo.transform.SetAsLastSibling();
        rect.anchorMin = new Vector2(1f, 1f);
        rect.anchorMax = new Vector2(1f, 1f);
        rect.pivot = new Vector2(1f, 1f);
        rect.anchoredPosition = new Vector2(-12f, -12f);
        rect.sizeDelta = new Vector2(180f, 28f);
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
        if (botonIniciarDialogo != null)
        {
            var btnLabel = botonIniciarDialogo.GetComponentInChildren<TMP_Text>(true);
            if (btnLabel != null) btnLabel.text = "Iniciar diálogo";
        }
        if (botonRespuestaPrefab == null && contenedorRespuestas != null)
        {
            var canvas = contenedorRespuestas.GetComponentInParent<Canvas>();
            if (canvas != null)
            {
                var btn = canvas.transform.Find("BotonRespuesta");
                if (btn != null) botonRespuestaPrefab = btn.gameObject;
            }
            if (botonRespuestaPrefab == null && panelRoot != null)
            {
                var btn = panelRoot.transform.Find("BotonRespuesta");
                if (btn != null) botonRespuestaPrefab = btn.gameObject;
            }
            if (botonRespuestaPrefab == null && Resources.Load<GameObject>("BotonRespuesta") != null)
                botonRespuestaPrefab = Resources.Load<GameObject>("BotonRespuesta");
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
            // Sin diálogo activo: mostrar botón para poder iniciar (si hay diálogo configurado)
            if (botonIniciarDialogo != null) botonIniciarDialogo.gameObject.SetActive(true);
            bool sinAsignar = estado != null && estado.dialogo_configurado_id == 0 && string.IsNullOrEmpty(estado.dialogo_configurado_nombre);
            SetText(contenidoNodo, sinAsignar
                ? "Esta sesión no tiene un diálogo asignado.\n\nVe a la web → Sesiones → Editar esta sesión → elige \"Diálogo a utilizar\" y guarda. Luego recarga Unity."
                : "No hay diálogo activo. Pulsa \"Iniciar diálogo\" para comenzar.");
            SetText(rolHablando, "");
            SetText(progreso, "");
            SetText(tiempo, "");
            SetText(mensajeTurno, "");
            LimpiarRespuestas();
            return;
        }

        // Diálogo activo: ocultar "Iniciar diálogo" solo cuando ya está en curso o pausado (según API Laravel)
        bool dialogoYaIniciado = estado.estado == "en_curso" || estado.estado == "pausado";
        if (botonIniciarDialogo != null)
            botonIniciarDialogo.gameObject.SetActive(!dialogoYaIniciado);

        var nodo = estado.nodo_actual;
        var rol = nodo?.rol_hablando;
        SetText(tituloNodo, !string.IsNullOrEmpty(nodo?.titulo) ? nodo.titulo : "Diálogo en curso");
        SetText(contenidoNodo, !string.IsNullOrEmpty(nodo?.contenido) ? nodo.contenido : "(Cargando nodo...)");
        SetText(rolHablando, rol != null ? rol.nombre : "");
        float p = estado.progreso ?? 0f;
        SetText(progreso, p > 0 ? $"{p:P0}" : "");
        SetText(tiempo, FormatearTiempoSesion(estado.tiempo_transcurrido));
        SetText(mensajeErrorOFinal, "");

        if (_dialogoManager.EsMiTurno)
            SetText(mensajeTurno, "Tu turno");
        else if (_dialogoManager.PuedoActuar)
            SetText(mensajeTurno, "Puedes avanzar (instructor)");
        else
            SetText(mensajeTurno, rol != null ? $"Esperando a {rol.nombre}..." : "Esperando...");

        if (!_dialogoManager.PuedoActuar)
            LimpiarRespuestas();
    }

    private void OnRespuestasDisponibles(List<RespuestaUsuario> respuestas)
    {
        int count = respuestas != null ? respuestas.Count : 0;
        UnityDebugLog.ToLaravel("dialogo_respuestas_ui", "OnRespuestasDisponibles recibido", new Dictionary<string, object> { { "count", count } });

        _dialogoManager.MarcarInicioRespuesta();
        LimpiarRespuestas();
        if (contenedorRespuestas == null)
        {
            Debug.LogWarning("DialogoUIController: ContenedorRespuestas no asignado. Asigna el objeto ContenedorRespuestas en el panel de diálogo.");
            UnityDebugLog.ToLaravel("dialogo_ui_error", "ContenedorRespuestas no asignado", null);
            return;
        }
        ResolverReferencias();
        if (botonRespuestaPrefab == null)
        {
            Debug.LogWarning("DialogoUIController: Prefab de botón de respuesta no encontrado. Asegúrate de tener un objeto 'BotonRespuesta' bajo Canvas o PanelDialogo, o un prefab en Resources/BotonRespuesta.");
            UnityDebugLog.ToLaravel("dialogo_ui_error", "Prefab BotonRespuesta no encontrado", null);
            return;
        }
        if (contenedorRespuestas.gameObject.activeSelf == false)
            contenedorRespuestas.gameObject.SetActive(true);

        // Si no hay opciones (nodo narrativo), mostrar un solo botón "Continuar" que avanza al siguiente nodo
        if (respuestas == null || respuestas.Count == 0)
        {
            UnityDebugLog.ToLaravel("dialogo_boton_aparece", "Aparece botón: Continuar", new Dictionary<string, object> { { "tipo", "continuar" } });
            CrearBotonRespuesta("Continuar", () =>
            {
                UnityDebugLog.ToLaravel("dialogo_boton_presionado", "Presiono el botón: Continuar", new Dictionary<string, object> { { "boton", "Continuar" } });
                if (_dialogoManager != null)
                {
                    _dialogoManager.AvanzarNodo((ok, msg) =>
                    {
                        if (!ok && !string.IsNullOrEmpty(msg))
                            SetText(mensajeErrorOFinal, msg);
                        else
                            SetText(mensajeErrorOFinal, "");
                    });
                }
                LimpiarRespuestas();
            });
            RebuildLayoutContenedor();
            return;
        }

        for (int i = 0; i < respuestas.Count; i++)
        {
            var resp = respuestas[i];
            string texto = resp.texto ?? ("Opción " + (i + 1));
            UnityDebugLog.ToLaravel("dialogo_boton_aparece", "Aparece botón opción " + (i + 1) + ": " + texto, new Dictionary<string, object> { { "indice", i + 1 }, { "texto", texto }, { "respuesta_id", resp.id } });
            var respCopy = resp;
            CrearBotonRespuesta(texto, () =>
            {
                UnityDebugLog.ToLaravel("dialogo_boton_presionado", "Presiono el botón: " + (respCopy.texto ?? "opción"), new Dictionary<string, object> { { "texto", respCopy.texto }, { "respuesta_id", respCopy.id } });
                EnviarDecision(respCopy.id);
            });
        }
        RebuildLayoutContenedor();
    }

    private void RebuildLayoutContenedor()
    {
        if (contenedorRespuestas == null) return;
        var rect = contenedorRespuestas as RectTransform;
        if (rect != null)
            LayoutRebuilder.ForceRebuildLayoutImmediate(rect);
    }

    /// <summary>Crea un botón en el contenedor de respuestas con texto y acción. Usado para opciones y para "Continuar".</summary>
    private void CrearBotonRespuesta(string texto, UnityEngine.Events.UnityAction onClick)
    {
        if (contenedorRespuestas == null || botonRespuestaPrefab == null) return;

        var btnGo = Instantiate(botonRespuestaPrefab, contenedorRespuestas);
        btnGo.SetActive(true);

        var rect = btnGo.GetComponent<RectTransform>();
        if (rect != null)
        {
            rect.anchorMin = new Vector2(0f, 1f);
            rect.anchorMax = new Vector2(1f, 1f);
            rect.pivot = new Vector2(0.5f, 1f);
            rect.offsetMin = new Vector2(0f, 0f);
            rect.offsetMax = new Vector2(0f, 0f);
        }

        var layout = btnGo.GetComponent<LayoutElement>();
        if (layout == null) layout = btnGo.AddComponent<LayoutElement>();
        layout.minHeight = 44f;
        layout.preferredHeight = -1f;
        layout.flexibleHeight = 0f;
        layout.preferredWidth = -1f;
        layout.flexibleWidth = 1f;

        var btn = btnGo.GetComponent<Button>();
        if (btn != null)
        {
            btn.onClick.RemoveAllListeners();
            if (onClick != null) btn.onClick.AddListener(onClick);
            var colors = btn.colors;
            colors.highlightedColor = new Color(0.85f, 0.9f, 1f);
            colors.pressedColor = new Color(0.75f, 0.82f, 0.95f);
            colors.selectedColor = new Color(0.9f, 0.93f, 1f);
            btn.colors = colors;
        }

        var label = btnGo.GetComponentInChildren<TMP_Text>(true);
        if (label != null)
        {
            label.text = texto;
            label.enableWordWrapping = true;
            label.overflowMode = TextOverflowModes.Overflow;
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
        UnityDebugLog.ToLaravel("button_clicked", "IniciarDialogo", new System.Collections.Generic.Dictionary<string, object> {
            { "button", "IniciarDialogo" },
            { "session_id", _dialogoManager != null ? _dialogoManager.sesionJuicioId : -1 },
            { "user_id", _dialogoManager != null ? _dialogoManager.usuarioId : -1 }
        });
        if (_dialogoManager == null) return;
        SetText(mensajeErrorOFinal, "Iniciando...");
        _dialogoManager.IniciarDialogo((ok, msg) =>
        {
            SetText(mensajeErrorOFinal, ok ? "Diálogo iniciado. Cargando..." : (msg ?? "Error"));
            if (ok)
                _dialogoManager.RefrescarEstado();
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
