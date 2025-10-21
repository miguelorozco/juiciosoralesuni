using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;
using TMPro;
using System.Linq;

namespace JuiciosSimulator.UI
{
    /// <summary>
    /// Controlador de UI para el sistema de diálogos
    /// </summary>
    public class DialogoUI : MonoBehaviour
    {
        [Header("UI Elements")]
        public GameObject loginPanel;
        public GameObject dialogoPanel;
        public GameObject respuestasPanel;
        public GameObject loadingPanel;
        
        [Header("Login UI")]
        public TMP_InputField emailInput;
        public TMP_InputField passwordInput;
        public Button loginButton;
        public TextMeshProUGUI loginStatusText;
        
        [Header("Dialogo UI")]
        public TextMeshProUGUI dialogoTitleText;
        public TextMeshProUGUI dialogoContentText;
        public TextMeshProUGUI rolHablandoText;
        public TextMeshProUGUI progresoText;
        public TextMeshProUGUI tiempoText;
        
        [Header("Respuestas UI")]
        public Transform respuestasContainer;
        public GameObject respuestaButtonPrefab;
        public Button enviarButton;
        public TMP_InputField decisionInput;
        
        [Header("Configuración")]
        public int sesionId = 1;
        public int usuarioId = 1;
        
        private List<RespuestaUsuario> respuestasActuales = new List<RespuestaUsuario>();
        private int respuestaSeleccionada = -1;
        private float tiempoInicioRespuesta;
        private bool esperandoRespuesta = false;
        
        private void Start()
        {
            // Suscribirse a eventos de la API
            LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
            LaravelAPI.OnDialogoUpdated += OnDialogoUpdated;
            LaravelAPI.OnRespuestasReceived += OnRespuestasReceived;
            LaravelAPI.OnError += OnError;
            LaravelAPI.OnConnectionStatusChanged += OnConnectionStatusChanged;
            
            // Configurar UI inicial
            SetupUI();
        }
        
        private void OnDestroy()
        {
            // Desuscribirse de eventos
            LaravelAPI.OnUserLoggedIn -= OnUserLoggedIn;
            LaravelAPI.OnDialogoUpdated -= OnDialogoUpdated;
            LaravelAPI.OnRespuestasReceived -= OnRespuestasReceived;
            LaravelAPI.OnError -= OnError;
            LaravelAPI.OnConnectionStatusChanged -= OnConnectionStatusChanged;
        }
        
        private void SetupUI()
        {
            // Configurar botones
            loginButton.onClick.AddListener(OnLoginClicked);
            enviarButton.onClick.AddListener(OnEnviarDecisionClicked);
            
            // Mostrar panel de login inicialmente
            ShowLoginPanel();
        }
        
        #region Login
        
        private void OnLoginClicked()
        {
            string email = emailInput.text;
            string password = passwordInput.text;
            
            if (string.IsNullOrEmpty(email) || string.IsNullOrEmpty(password))
            {
                ShowLoginError("Por favor, ingresa email y contraseña");
                return;
            }
            
            ShowLoading("Iniciando sesión...");
            LaravelAPI.Instance.Login(email, password);
        }
        
        private void OnUserLoggedIn(UserData user)
        {
            HideLoading();
            ShowDialogoPanel();
            UpdateUserInfo(user);
            
            // Iniciar comunicación en tiempo real
            LaravelAPI.Instance.StartRealtimeEvents(sesionId);
            
            // Obtener estado inicial del diálogo
            LaravelAPI.Instance.GetDialogoEstado(sesionId);
        }
        
        private void OnConnectionStatusChanged(bool connected)
        {
            if (!connected)
            {
                ShowLoginPanel();
                ShowLoginError("Conexión perdida. Por favor, inicia sesión nuevamente.");
            }
        }
        
        #endregion
        
        #region Diálogo
        
        private void OnDialogoUpdated(DialogoEstado estado)
        {
            if (!estado.dialogo_activo)
            {
                ShowDialogoError("No hay un diálogo activo en esta sesión");
                return;
            }
            
            UpdateDialogoUI(estado);
            
            // Verificar si es el turno del usuario actual
            var participante = estado.participantes.FirstOrDefault(p => p.usuario_id == usuarioId);
            if (participante != null && participante.es_turno)
            {
                // Es nuestro turno, obtener respuestas
                LaravelAPI.Instance.GetRespuestasUsuario(sesionId, usuarioId);
                esperandoRespuesta = true;
                tiempoInicioRespuesta = Time.time;
            }
            else
            {
                // No es nuestro turno
                ShowEsperandoTurno(participante?.rol?.nombre ?? "Desconocido");
            }
        }
        
        private void UpdateDialogoUI(DialogoEstado estado)
        {
            dialogoTitleText.text = estado.nodo_actual.titulo;
            dialogoContentText.text = estado.nodo_actual.contenido;
            rolHablandoText.text = $"Habla: {estado.nodo_actual.rol_hablando.nombre}";
            progresoText.text = $"Progreso: {estado.progreso:F1}%";
            tiempoText.text = $"Tiempo: {estado.tiempo_transcurrido}s";
            
            // Actualizar color del rol hablando
            if (ColorUtility.TryParseHtmlString($"#{estado.nodo_actual.rol_hablando.color}", out Color rolColor))
            {
                rolHablandoText.color = rolColor;
            }
        }
        
        #endregion
        
        #region Respuestas
        
        private void OnRespuestasReceived(List<RespuestaUsuario> respuestas)
        {
            respuestasActuales = respuestas;
            ShowRespuestas(respuestas);
        }
        
        private void ShowRespuestas(List<RespuestaUsuario> respuestas)
        {
            // Limpiar respuestas anteriores
            foreach (Transform child in respuestasContainer)
            {
                Destroy(child.gameObject);
            }
            
            // Crear botones para cada respuesta
            for (int i = 0; i < respuestas.Count; i++)
            {
                int index = i; // Capturar índice para el closure
                RespuestaUsuario respuesta = respuestas[i];
                
                GameObject buttonObj = Instantiate(respuestaButtonPrefab, respuestasContainer);
                Button button = buttonObj.GetComponent<Button>();
                TextMeshProUGUI buttonText = buttonObj.GetComponentInChildren<TextMeshProUGUI>();
                
                buttonText.text = respuesta.texto;
                
                // Configurar color de la respuesta
                if (ColorUtility.TryParseHtmlString($"#{respuesta.color}", out Color respuestaColor))
                {
                    button.image.color = respuestaColor;
                }
                
                // Configurar click del botón
                button.onClick.AddListener(() => OnRespuestaSelected(index, respuesta));
            }
            
            // Mostrar panel de respuestas
            respuestasPanel.SetActive(true);
        }
        
        private void OnRespuestaSelected(int index, RespuestaUsuario respuesta)
        {
            respuestaSeleccionada = index;
            
            // Resaltar respuesta seleccionada
            for (int i = 0; i < respuestasContainer.childCount; i++)
            {
                Button button = respuestasContainer.GetChild(i).GetComponent<Button>();
                ColorBlock colors = button.colors;
                
                if (i == index)
                {
                    colors.normalColor = Color.yellow;
                }
                else
                {
                    colors.normalColor = Color.white;
                }
                
                button.colors = colors;
            }
            
            // Habilitar botón de envío
            enviarButton.interactable = true;
        }
        
        private void OnEnviarDecisionClicked()
        {
            if (respuestaSeleccionada < 0 || respuestaSeleccionada >= respuestasActuales.Count)
            {
                ShowError("Por favor, selecciona una respuesta");
                return;
            }
            
            RespuestaUsuario respuesta = respuestasActuales[respuestaSeleccionada];
            string decisionTexto = decisionInput.text;
            int tiempoRespuesta = Mathf.RoundToInt(Time.time - tiempoInicioRespuesta);
            
            // Notificar que el usuario está hablando
            LaravelAPI.Instance.NotificarHablando(sesionId, usuarioId, "hablando");
            
            // Enviar decisión
            LaravelAPI.Instance.EnviarDecision(sesionId, usuarioId, respuesta.id, decisionTexto, tiempoRespuesta);
            
            // Limpiar selección
            respuestaSeleccionada = -1;
            decisionInput.text = "";
            respuestasPanel.SetActive(false);
            esperandoRespuesta = false;
            
            ShowLoading("Enviando decisión...");
        }
        
        #endregion
        
        #region UI Helpers
        
        private void ShowLoginPanel()
        {
            loginPanel.SetActive(true);
            dialogoPanel.SetActive(false);
            respuestasPanel.SetActive(false);
            loadingPanel.SetActive(false);
        }
        
        private void ShowDialogoPanel()
        {
            loginPanel.SetActive(false);
            dialogoPanel.SetActive(true);
            respuestasPanel.SetActive(false);
            loadingPanel.SetActive(false);
        }
        
        private void ShowLoading(string message)
        {
            loadingPanel.SetActive(true);
            if (loadingPanel.GetComponentInChildren<TextMeshProUGUI>())
            {
                loadingPanel.GetComponentInChildren<TextMeshProUGUI>().text = message;
            }
        }
        
        private void HideLoading()
        {
            loadingPanel.SetActive(false);
        }
        
        private void ShowLoginError(string message)
        {
            loginStatusText.text = message;
            loginStatusText.color = Color.red;
        }
        
        private void ShowDialogoError(string message)
        {
            dialogoContentText.text = message;
            dialogoContentText.color = Color.red;
        }
        
        private void ShowEsperandoTurno(string rolActual)
        {
            dialogoContentText.text = $"Esperando turno... Actualmente habla: {rolActual}";
            dialogoContentText.color = Color.yellow;
        }
        
        private void ShowError(string message)
        {
            Debug.LogError(message);
            // Aquí podrías mostrar un popup de error
        }
        
        private void UpdateUserInfo(UserData user)
        {
            // Actualizar información del usuario en la UI si es necesario
            Debug.Log($"Usuario conectado: {user.name} {user.apellido} ({user.tipo})");
        }
        
        #endregion
        
        #region Event Handlers
        
        private void OnError(string errorMessage)
        {
            HideLoading();
            ShowError(errorMessage);
        }
        
        #endregion
        
        #region Public Methods
        
        /// <summary>
        /// Configurar IDs de sesión y usuario
        /// </summary>
        public void SetSessionInfo(int sesionId, int usuarioId)
        {
            this.sesionId = sesionId;
            this.usuarioId = usuarioId;
        }
        
        /// <summary>
        /// Actualizar estado del diálogo manualmente
        /// </summary>
        public void RefreshDialogo()
        {
            if (LaravelAPI.Instance.isConnected)
            {
                LaravelAPI.Instance.GetDialogoEstado(sesionId);
            }
        }
        
        #endregion
    }
}

