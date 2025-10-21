using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;
using TMPro;
using JuiciosSimulator.API;

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
        public TMP_InputField emailInput;
        public TMP_InputField passwordInput;
        public Button loginButton;
        
        [Header("Diálogo UI")]
        public TextMeshProUGUI dialogoTitleText;
        public TextMeshProUGUI dialogoContentText;
        public TextMeshProUGUI rolHablandoText;
        public Transform respuestasContainer;
        public GameObject respuestaButtonPrefab;
        
        [Header("Configuración")]
        public int sesionId = 1;
        public int usuarioId = 1;
        
        private List<GameObject> respuestaButtons = new List<GameObject>();
        private List<RespuestaUsuario> respuestasActuales = new List<RespuestaUsuario>();
        private int respuestaSeleccionada = -1;
        
        private void Start()
        {
            // Configurar eventos
            LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
            LaravelAPI.OnDialogoUpdated += OnDialogoUpdated;
            LaravelAPI.OnRespuestasReceived += OnRespuestasReceived;
            LaravelAPI.OnError += OnError;
            
            // Configurar UI inicial
            loginPanel.SetActive(true);
            dialogoPanel.SetActive(false);
            
            // Configurar botón de login
            loginButton.onClick.AddListener(OnLoginClicked);
        }
        
        private void OnDestroy()
        {
            // Desuscribirse de eventos
            LaravelAPI.OnUserLoggedIn -= OnUserLoggedIn;
            LaravelAPI.OnDialogoUpdated -= OnDialogoUpdated;
            LaravelAPI.OnRespuestasReceived -= OnRespuestasReceived;
            LaravelAPI.OnError -= OnError;
        }
        
        #region Login
        
        private void OnLoginClicked()
        {
            string email = emailInput.text;
            string password = passwordInput.text;
            
            if (string.IsNullOrEmpty(email) || string.IsNullOrEmpty(password))
            {
                ShowError("Por favor ingresa email y contraseña");
                return;
            }
            
            // Realizar login
            LaravelAPI.Instance.Login(email, password);
        }
        
        private void OnUserLoggedIn(UserData user)
        {
            // Ocultar panel de login y mostrar panel de diálogo
            loginPanel.SetActive(false);
            dialogoPanel.SetActive(true);
            
            // Actualizar ID de usuario
            usuarioId = user.id;
            
            // Obtener estado del diálogo
            LaravelAPI.Instance.GetDialogoEstado(sesionId);
            
            Debug.Log($"Usuario logueado: {user.name} (ID: {user.id})");
        }
        
        #endregion
        
        #region Diálogo
        
        private void OnDialogoUpdated(DialogoEstado estado)
        {
            if (!estado.dialogo_activo)
            {
                dialogoTitleText.text = "No hay diálogo activo";
                dialogoContentText.text = "Esperando que se inicie un diálogo...";
                rolHablandoText.text = "";
                ClearRespuestas();
                return;
            }
            
            // Actualizar información del diálogo
            dialogoTitleText.text = estado.nodo_actual.titulo;
            dialogoContentText.text = estado.nodo_actual.contenido;
            rolHablandoText.text = $"Habla: {estado.nodo_actual.rol_hablando.nombre}";
            
            // Actualizar color del rol hablando
            if (ColorUtility.TryParseHtmlString($"#{estado.nodo_actual.rol_hablando.color}", out Color rolColor))
            {
                rolHablandoText.color = rolColor;
            }
            
            // Verificar si es el turno del usuario actual
            var participante = estado.participantes.Find(p => p.usuario_id == usuarioId);
            if (participante != null && participante.es_turno)
            {
                // Es nuestro turno, obtener respuestas
                LaravelAPI.Instance.GetRespuestasUsuario(sesionId, usuarioId);
            }
            else
            {
                // No es nuestro turno, limpiar respuestas
                ClearRespuestas();
            }
        }
        
        private void OnRespuestasReceived(List<RespuestaUsuario> respuestas)
        {
            respuestasActuales = respuestas;
            ShowRespuestas(respuestas);
        }
        
        private void ShowRespuestas(List<RespuestaUsuario> respuestas)
        {
            // Limpiar respuestas anteriores
            ClearRespuestas();
            
            // Crear botones para cada respuesta
            for (int i = 0; i < respuestas.Count; i++)
            {
                RespuestaUsuario respuesta = respuestas[i];
                
                GameObject buttonObj = Instantiate(respuestaButtonPrefab, respuestasContainer);
                Button button = buttonObj.GetComponent<Button>();
                TextMeshProUGUI buttonText = buttonObj.GetComponentInChildren<TextMeshProUGUI>();
                
                buttonText.text = respuesta.texto;
                
                int index = i; // Capturar índice para el closure
                button.onClick.AddListener(() => OnRespuestaSelected(index));
                
                respuestaButtons.Add(buttonObj);
            }
        }
        
        private void OnRespuestaSelected(int index)
        {
            respuestaSeleccionada = index;
            
            // Resaltar botón seleccionado
            for (int i = 0; i < respuestaButtons.Count; i++)
            {
                Button button = respuestaButtons[i].GetComponent<Button>();
                ColorBlock colors = button.colors;
                colors.normalColor = (i == index) ? Color.yellow : Color.white;
                button.colors = colors;
            }
        }
        
        private void ClearRespuestas()
        {
            foreach (GameObject button in respuestaButtons)
            {
                if (button != null)
                {
                    Destroy(button);
                }
            }
            respuestaButtons.Clear();
            respuestasActuales.Clear();
            respuestaSeleccionada = -1;
        }
        
        #endregion
        
        #region Envío de Decisiones
        
        public void OnEnviarDecisionClicked()
        {
            if (respuestaSeleccionada < 0 || respuestaSeleccionada >= respuestasActuales.Count)
            {
                ShowError("Por favor selecciona una respuesta");
                return;
            }
            
            RespuestaUsuario respuesta = respuestasActuales[respuestaSeleccionada];
            
            // Enviar decisión
            LaravelAPI.Instance.EnviarDecision(
                sesionId, 
                usuarioId, 
                respuesta.id, 
                respuesta.texto, 
                0 // Tiempo de respuesta (se puede calcular)
            );
            
            // Limpiar selección
            respuestaSeleccionada = -1;
        }
        
        #endregion
        
        #region Utilidades
        
        private void OnError(string error)
        {
            ShowError(error);
        }
        
        private void ShowError(string message)
        {
            Debug.LogError($"Error en DialogoUI: {message}");
            // Aquí podrías mostrar un popup de error o actualizar un texto de error en la UI
        }
        
        #endregion
        
        #region Métodos Públicos para Testing
        
        public void SetSesionId(int id)
        {
            sesionId = id;
        }
        
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
