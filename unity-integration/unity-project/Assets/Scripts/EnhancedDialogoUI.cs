using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;
using TMPro;
using JuiciosSimulator.API;
using JuiciosSimulator.Session;

namespace JuiciosSimulator.UI
{
    /// <summary>
    /// UI mejorada para el sistema de diálogos ramificados
    /// Integra con SessionManager para manejo de sesiones y roles
    /// </summary>
    public class EnhancedDialogoUI : MonoBehaviour
    {
        [Header("UI Panels")]
        public GameObject sessionSelectionPanel;
        public GameObject dialogoPanel;
        public GameObject waitingPanel;
        public GameObject roleInfoPanel;
        
        [Header("Session Selection UI")]
        public TMP_InputField sessionCodeInput;
        public Button joinSessionButton;
        public Button refreshSessionsButton;
        public TextMeshProUGUI sessionStatusText;
        
        [Header("Role Info UI")]
        public TextMeshProUGUI roleNameText;
        public TextMeshProUGUI roleDescriptionText;
        public TextMeshProUGUI roleColorIndicator;
        public Button readyButton;
        
        [Header("Dialog UI")]
        public TextMeshProUGUI dialogoTitleText;
        public TextMeshProUGUI dialogoContentText;
        public TextMeshProUGUI currentSpeakerText;
        public TextMeshProUGUI turnIndicatorText;
        public Transform respuestasContainer;
        public GameObject respuestaButtonPrefab;
        public Button enviarDecisionButton;
        public Button skipTurnButton;
        
        [Header("Dialog History")]
        public ScrollRect dialogHistoryScrollRect;
        public Transform dialogHistoryContainer;
        public GameObject dialogHistoryItemPrefab;
        public Button showHistoryButton;
        public Button hideHistoryButton;
        
        [Header("Participant List")]
        public ScrollRect participantsScrollRect;
        public Transform participantsContainer;
        public GameObject participantItemPrefab;
        public Button showParticipantsButton;
        public Button hideParticipantsButton;
        
        [Header("Configuration")]
        public float autoRefreshInterval = 5f;
        public bool showDialogHistory = true;
        public bool showParticipantsList = true;
        
        // References
        private SessionManager sessionManager;
        private LaravelAPI laravelAPI;
        
        // Current state
        private SesionData currentSession;
        private AsignacionRolData currentRole;
        private DialogoEstado currentDialogState;
        private List<RespuestaUsuario> currentResponses = new List<RespuestaUsuario>();
        private List<GameObject> responseButtons = new List<GameObject>();
        private List<GameObject> dialogHistoryItems = new List<GameObject>();
        private List<GameObject> participantItems = new List<GameObject>();
        
        // UI State
        private bool isMyTurn = false;
        private int selectedResponseIndex = -1;
        private bool isWaitingForTurn = false;
        
        // Events
        public static event System.Action<DialogoEstado> OnDialogStateChanged;
        public static event System.Action<bool> OnTurnChanged;
        public static event System.Action<RespuestaUsuario> OnResponseSelected;
        
        private void Start()
        {
            InitializeReferences();
            SetupUI();
            SubscribeToEvents();
            StartCoroutine(AutoRefreshDialog());
        }
        
        private void OnDestroy()
        {
            UnsubscribeFromEvents();
        }
        
        #region Initialization
        
        private void InitializeReferences()
        {
            sessionManager = FindObjectOfType<SessionManager>();
            laravelAPI = LaravelAPI.Instance;
        }
        
        private void SetupUI()
        {
            // Setup buttons
            joinSessionButton.onClick.AddListener(OnJoinSessionClicked);
            refreshSessionsButton.onClick.AddListener(OnRefreshSessionsClicked);
            readyButton.onClick.AddListener(OnReadyClicked);
            enviarDecisionButton.onClick.AddListener(OnEnviarDecisionClicked);
            skipTurnButton.onClick.AddListener(OnSkipTurnClicked);
            showHistoryButton.onClick.AddListener(OnShowHistoryClicked);
            hideHistoryButton.onClick.AddListener(OnHideHistoryClicked);
            showParticipantsButton.onClick.AddListener(OnShowParticipantsClicked);
            hideParticipantsButton.onClick.AddListener(OnHideParticipantsClicked);
            
            // Initial UI state
            ShowSessionSelection();
        }
        
        private void SubscribeToEvents()
        {
            // Session events
            SessionManager.OnSessionJoined += OnSessionJoined;
            SessionManager.OnRoleAssigned += OnRoleAssigned;
            SessionManager.OnSessionError += OnSessionError;
            SessionManager.OnSessionLeft += OnSessionLeft;
            
            // Laravel API events
            LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
            LaravelAPI.OnDialogoUpdated += OnDialogoUpdated;
            LaravelAPI.OnRespuestasReceived += OnRespuestasReceived;
            LaravelAPI.OnError += OnLaravelError;
        }
        
        private void UnsubscribeFromEvents()
        {
            // Session events
            SessionManager.OnSessionJoined -= OnSessionJoined;
            SessionManager.OnRoleAssigned -= OnRoleAssigned;
            SessionManager.OnSessionError -= OnSessionError;
            SessionManager.OnSessionLeft -= OnSessionLeft;
            
            // Laravel API events
            LaravelAPI.OnUserLoggedIn -= OnUserLoggedIn;
            LaravelAPI.OnDialogoUpdated -= OnDialogoUpdated;
            LaravelAPI.OnRespuestasReceived -= OnRespuestasReceived;
            LaravelAPI.OnError -= OnLaravelError;
        }
        
        #endregion
        
        #region UI State Management
        
        private void ShowSessionSelection()
        {
            sessionSelectionPanel.SetActive(true);
            dialogoPanel.SetActive(false);
            waitingPanel.SetActive(false);
            roleInfoPanel.SetActive(false);
        }
        
        private void ShowRoleInfo()
        {
            if (currentRole == null) return;
            
            roleNameText.text = currentRole.rol.nombre;
            roleDescriptionText.text = currentRole.rol.descripcion;
            
            // Set role color
            if (ColorUtility.TryParseHtmlString($"#{currentRole.rol.color}", out Color roleColor))
            {
                roleColorIndicator.color = roleColor;
            }
            
            roleInfoPanel.SetActive(true);
            sessionSelectionPanel.SetActive(false);
        }
        
        private void ShowDialogPanel()
        {
            dialogoPanel.SetActive(true);
            roleInfoPanel.SetActive(false);
            waitingPanel.SetActive(false);
        }
        
        private void ShowWaitingPanel()
        {
            waitingPanel.SetActive(true);
            dialogoPanel.SetActive(false);
        }
        
        #endregion
        
        #region Session Management
        
        private void OnSessionJoined(SesionData session)
        {
            currentSession = session;
            Debug.Log($"Sesión unida: {session.nombre}");
        }
        
        private void OnRoleAssigned(AsignacionRolData role)
        {
            currentRole = role;
            ShowRoleInfo();
            Debug.Log($"Rol asignado: {role.rol.nombre}");
        }
        
        private void OnSessionError(string error)
        {
            ShowError($"Error de sesión: {error}");
        }
        
        private void OnSessionLeft()
        {
            currentSession = null;
            currentRole = null;
            ShowSessionSelection();
        }
        
        #endregion
        
        #region Dialog Management
        
        private void OnDialogoUpdated(DialogoEstado estado)
        {
            currentDialogState = estado;
            OnDialogStateChanged?.Invoke(estado);
            
            if (!estado.dialogo_activo)
            {
                ShowWaitingDialog();
                return;
            }
            
            UpdateDialogContent(estado);
            CheckIfMyTurn(estado);
        }
        
        private void UpdateDialogContent(DialogoEstado estado)
        {
            dialogoTitleText.text = estado.nodo_actual.titulo;
            dialogoContentText.text = estado.nodo_actual.contenido;
            currentSpeakerText.text = $"Habla: {estado.nodo_actual.rol_hablando.nombre}";
            
            // Set speaker color
            if (ColorUtility.TryParseHtmlString($"#{estado.nodo_actual.rol_hablando.color}", out Color speakerColor))
            {
                currentSpeakerText.color = speakerColor;
            }
            
            // Update turn indicator
            UpdateTurnIndicator(estado);
        }
        
        private void CheckIfMyTurn(DialogoEstado estado)
        {
            if (currentRole == null) return;
            
            var participante = estado.participantes.Find(p => p.usuario_id == currentRole.usuario_id);
            bool wasMyTurn = isMyTurn;
            isMyTurn = participante != null && participante.es_turno;
            
            if (isMyTurn != wasMyTurn)
            {
                OnTurnChanged?.Invoke(isMyTurn);
            }
            
            if (isMyTurn)
            {
                ShowDialogPanel();
                GetAvailableResponses();
            }
            else
            {
                ShowWaitingPanel();
                ClearResponses();
            }
        }
        
        private void UpdateTurnIndicator(DialogoEstado estado)
        {
            if (isMyTurn)
            {
                turnIndicatorText.text = "Es tu turno";
                turnIndicatorText.color = Color.green;
            }
            else
            {
                var currentSpeaker = estado.nodo_actual.rol_hablando;
                turnIndicatorText.text = $"Turno de: {currentSpeaker.nombre}";
                turnIndicatorText.color = Color.white;
            }
        }
        
        private void ShowWaitingDialog()
        {
            dialogoTitleText.text = "Esperando...";
            dialogoContentText.text = "El diálogo aún no ha comenzado. Espera a que el instructor inicie la sesión.";
            currentSpeakerText.text = "";
            turnIndicatorText.text = "Esperando inicio";
            ClearResponses();
        }
        
        #endregion
        
        #region Response Management
        
        private void GetAvailableResponses()
        {
            if (currentSession == null || currentRole == null) return;
            
            laravelAPI.GetRespuestasUsuario(currentSession.id, currentRole.usuario_id);
        }
        
        private void OnRespuestasReceived(List<RespuestaUsuario> respuestas)
        {
            currentResponses = respuestas;
            ShowResponses(respuestas);
        }
        
        private void ShowResponses(List<RespuestaUsuario> respuestas)
        {
            ClearResponses();
            
            for (int i = 0; i < respuestas.Count; i++)
            {
                RespuestaUsuario respuesta = respuestas[i];
                
                GameObject buttonObj = Instantiate(respuestaButtonPrefab, respuestasContainer);
                Button button = buttonObj.GetComponent<Button>();
                TextMeshProUGUI buttonText = buttonObj.GetComponentInChildren<TextMeshProUGUI>();
                
                buttonText.text = respuesta.texto;
                
                int index = i;
                button.onClick.AddListener(() => OnResponseSelected(index));
                
                responseButtons.Add(buttonObj);
            }
            
            // Enable/disable send button
            enviarDecisionButton.interactable = false;
        }
        
        private void OnResponseSelected(int index)
        {
            selectedResponseIndex = index;
            OnResponseSelected?.Invoke(currentResponses[index]);
            
            // Highlight selected response
            for (int i = 0; i < responseButtons.Count; i++)
            {
                Button button = responseButtons[i].GetComponent<Button>();
                ColorBlock colors = button.colors;
                colors.normalColor = (i == index) ? Color.yellow : Color.white;
                button.colors = colors;
            }
            
            // Enable send button
            enviarDecisionButton.interactable = true;
        }
        
        private void ClearResponses()
        {
            foreach (GameObject button in responseButtons)
            {
                if (button != null)
                {
                    Destroy(button);
                }
            }
            responseButtons.Clear();
            currentResponses.Clear();
            selectedResponseIndex = -1;
            enviarDecisionButton.interactable = false;
        }
        
        #endregion
        
        #region Decision Management
        
        private void OnEnviarDecisionClicked()
        {
            if (selectedResponseIndex < 0 || selectedResponseIndex >= currentResponses.Count)
            {
                ShowError("Por favor selecciona una respuesta");
                return;
            }
            
            if (currentSession == null || currentRole == null) return;
            
            RespuestaUsuario respuesta = currentResponses[selectedResponseIndex];
            
            // Send decision
            laravelAPI.EnviarDecision(
                currentSession.id,
                currentRole.usuario_id,
                respuesta.id,
                respuesta.texto,
                CalculateResponseTime()
            );
            
            // Add to dialog history
            AddToDialogHistory($"Tú: {respuesta.texto}");
            
            // Clear selection
            selectedResponseIndex = -1;
            ClearResponses();
        }
        
        private void OnSkipTurnClicked()
        {
            if (currentSession == null || currentRole == null) return;
            
            // Send skip decision
            laravelAPI.EnviarDecision(
                currentSession.id,
                currentRole.usuario_id,
                0, // Skip response ID
                "Paso mi turno",
                CalculateResponseTime()
            );
            
            AddToDialogHistory("Tú: Pasé mi turno");
            ClearResponses();
        }
        
        private float CalculateResponseTime()
        {
            // Calculate time since dialog started
            if (currentDialogState != null)
            {
                return currentDialogState.tiempo_transcurrido;
            }
            return 0f;
        }
        
        #endregion
        
        #region Dialog History
        
        private void AddToDialogHistory(string message)
        {
            if (!showDialogHistory) return;
            
            GameObject historyItem = Instantiate(dialogHistoryItemPrefab, dialogHistoryContainer);
            TextMeshProUGUI historyText = historyItem.GetComponent<TextMeshProUGUI>();
            historyText.text = $"[{System.DateTime.Now:HH:mm:ss}] {message}";
            
            dialogHistoryItems.Add(historyItem);
            
            // Scroll to bottom
            StartCoroutine(ScrollToBottom());
        }
        
        private IEnumerator ScrollToBottom()
        {
            yield return new WaitForEndOfFrame();
            dialogHistoryScrollRect.verticalNormalizedPosition = 0f;
        }
        
        private void OnShowHistoryClicked()
        {
            dialogHistoryScrollRect.gameObject.SetActive(true);
            showHistoryButton.gameObject.SetActive(false);
            hideHistoryButton.gameObject.SetActive(true);
        }
        
        private void OnHideHistoryClicked()
        {
            dialogHistoryScrollRect.gameObject.SetActive(false);
            showHistoryButton.gameObject.SetActive(true);
            hideHistoryButton.gameObject.SetActive(false);
        }
        
        #endregion
        
        #region Participants List
        
        private void UpdateParticipantsList(DialogoEstado estado)
        {
            if (!showParticipantsList) return;
            
            // Clear existing items
            foreach (GameObject item in participantItems)
            {
                if (item != null)
                {
                    Destroy(item);
                }
            }
            participantItems.Clear();
            
            // Create participant items
            foreach (var participante in estado.participantes)
            {
                GameObject participantItem = Instantiate(participantItemPrefab, participantsContainer);
                TextMeshProUGUI participantText = participantItem.GetComponent<TextMeshProUGUI>();
                
                string status = participante.es_turno ? " (Su turno)" : "";
                participantText.text = $"{participante.rol.nombre}: {participante.usuario.nombre}{status}";
                
                // Set color based on role
                if (ColorUtility.TryParseHtmlString($"#{participante.rol.color}", out Color roleColor))
                {
                    participantText.color = roleColor;
                }
                
                participantItems.Add(participantItem);
            }
        }
        
        private void OnShowParticipantsClicked()
        {
            participantsScrollRect.gameObject.SetActive(true);
            showParticipantsButton.gameObject.SetActive(false);
            hideParticipantsButton.gameObject.SetActive(true);
        }
        
        private void OnHideParticipantsClicked()
        {
            participantsScrollRect.gameObject.SetActive(false);
            showParticipantsButton.gameObject.SetActive(true);
            hideParticipantsButton.gameObject.SetActive(false);
        }
        
        #endregion
        
        #region Auto Refresh
        
        private IEnumerator AutoRefreshDialog()
        {
            while (true)
            {
                yield return new WaitForSeconds(autoRefreshInterval);
                
                if (currentSession != null && laravelAPI.isConnected)
                {
                    laravelAPI.GetDialogoEstado(currentSession.id);
                }
            }
        }
        
        #endregion
        
        #region Button Handlers
        
        private void OnJoinSessionClicked()
        {
            string sessionCode = sessionCodeInput.text.Trim();
            if (sessionManager != null)
            {
                sessionManager.JoinSessionByCode(sessionCode);
            }
        }
        
        private void OnRefreshSessionsClicked()
        {
            // Refresh available sessions
            if (sessionManager != null)
            {
                // This would call a method to refresh sessions
                Debug.Log("Refrescando sesiones disponibles...");
            }
        }
        
        private void OnReadyClicked()
        {
            ShowDialogPanel();
        }
        
        #endregion
        
        #region Event Handlers
        
        private void OnUserLoggedIn(UserData user)
        {
            Debug.Log($"Usuario logueado: {user.name}");
        }
        
        private void OnLaravelError(string error)
        {
            ShowError($"Error de Laravel: {error}");
        }
        
        #endregion
        
        #region Utilities
        
        private void ShowError(string message)
        {
            Debug.LogError($"EnhancedDialogoUI Error: {message}");
            sessionStatusText.text = $"Error: {message}";
        }
        
        #endregion
        
        #region Public Methods
        
        public void SetSessionCode(string code)
        {
            sessionCodeInput.text = code;
        }
        
        public void RefreshDialog()
        {
            if (currentSession != null)
            {
                laravelAPI.GetDialogoEstado(currentSession.id);
            }
        }
        
        public bool IsInSession()
        {
            return currentSession != null && currentRole != null;
        }
        
        public bool IsMyTurn()
        {
            return isMyTurn;
        }
        
        #endregion
    }
}
