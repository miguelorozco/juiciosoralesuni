using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using JuiciosSimulator.API;
using JuiciosSimulator.Session;
using JuiciosSimulator.UI;
using JuiciosSimulator.Realtime;
using JuiciosSimulator.Network;

namespace JuiciosSimulator.Core
{
    /// <summary>
    /// Gestor principal mejorado del juego
    /// Integra todos los componentes del sistema de juicios orales
    /// </summary>
    public class EnhancedGameManager : MonoBehaviour
    {
        [Header("Component References")]
        public LaravelAPI laravelAPI;
        public SessionManager sessionManager;
        public EnhancedDialogoUI dialogoUI;
        public RealtimeSyncManager realtimeSync;
        public EnhancedPhotonIntegration photonIntegration;

        [Header("Configuration")]
        public bool autoInitialize = true;
        public bool enableDebugMode = true;
        public float initializationTimeout = 30f;

        [Header("Debug UI")]
        public bool showDebugPanel = true;
        public bool showSystemStatus = true;

        // State
        private bool isInitialized = false;
        private bool isInitializing = false;
        private GameState currentState = GameState.Initializing;
        private float initializationStartTime;

        // Events
        public static event System.Action<GameState> OnGameStateChanged;
        public static event System.Action<bool> OnInitializationComplete;
        public static event System.Action<string> OnSystemError;

        private void Start()
        {
            if (autoInitialize)
            {
                StartCoroutine(InitializeSystem());
            }
        }

        private void OnDestroy()
        {
            // Cleanup
            if (isInitialized)
            {
                ShutdownSystem();
            }
        }

        #region Initialization

        private IEnumerator InitializeSystem()
        {
            if (isInitializing || isInitialized)
            {
                yield break;
            }

            isInitializing = true;
            initializationStartTime = Time.time;
            SetGameState(GameState.Initializing);

            Debug.Log("=== ENHANCED GAME MANAGER INITIALIZATION ===");

            // Step 1: Initialize Laravel API
            yield return StartCoroutine(InitializeLaravelAPI());

            // Step 2: Initialize Session Manager
            yield return StartCoroutine(InitializeSessionManager());

            // Step 3: Initialize UI Components
            yield return StartCoroutine(InitializeUIComponents());

            // Step 4: Initialize Realtime Sync
            yield return StartCoroutine(InitializeRealtimeSync());

            // Step 5: Initialize Photon Integration
            yield return StartCoroutine(InitializePhotonIntegration());

            // Step 6: Setup Event Subscriptions
            SetupEventSubscriptions();

            // Step 7: Finalize Initialization
            FinalizeInitialization();

            Debug.Log("=== INITIALIZATION COMPLETE ===");

            isInitializing = false;
        }

        private IEnumerator InitializeLaravelAPI()
        {
            Debug.Log("Initializing Laravel API...");

            if (laravelAPI == null)
            {
                laravelAPI = FindObjectOfType<LaravelAPI>();
                if (laravelAPI == null)
                {
                    laravelAPI = new GameObject("LaravelAPI").AddComponent<LaravelAPI>();
                }
            }

            // Wait for Laravel API to be ready
            float timeout = 10f;
            float startTime = Time.time;

            while (!laravelAPI.isConnected && (Time.time - startTime) < timeout)
            {
                yield return new WaitForSeconds(0.5f);
            }

            if (!laravelAPI.isConnected)
            {
                throw new System.Exception("Laravel API connection timeout");
            }

            Debug.Log("Laravel API initialized successfully");
        }

        private IEnumerator InitializeSessionManager()
        {
            Debug.Log("Initializing Session Manager...");

            if (sessionManager == null)
            {
                sessionManager = FindObjectOfType<SessionManager>();
                if (sessionManager == null)
                {
                    sessionManager = new GameObject("SessionManager").AddComponent<SessionManager>();
                }
            }

            Debug.Log("Session Manager initialized successfully");
            yield return null;
        }

        private IEnumerator InitializeUIComponents()
        {
            Debug.Log("Initializing UI Components...");

            if (dialogoUI == null)
            {
                dialogoUI = FindObjectOfType<EnhancedDialogoUI>();
                if (dialogoUI == null)
                {
                    dialogoUI = new GameObject("EnhancedDialogoUI").AddComponent<EnhancedDialogoUI>();
                }
            }

            Debug.Log("UI Components initialized successfully");
            yield return null;
        }

        private IEnumerator InitializeRealtimeSync()
        {
            Debug.Log("Initializing Realtime Sync...");

            if (realtimeSync == null)
            {
                realtimeSync = FindObjectOfType<RealtimeSyncManager>();
                if (realtimeSync == null)
                {
                    realtimeSync = new GameObject("RealtimeSyncManager").AddComponent<RealtimeSyncManager>();
                }
            }

            Debug.Log("Realtime Sync initialized successfully");
            yield return null;
        }

        private IEnumerator InitializePhotonIntegration()
        {
            Debug.Log("Initializing Photon Integration...");

            if (photonIntegration == null)
            {
                photonIntegration = FindObjectOfType<EnhancedPhotonIntegration>();
                if (photonIntegration == null)
                {
                    photonIntegration = new GameObject("EnhancedPhotonIntegration").AddComponent<EnhancedPhotonIntegration>();
                }
            }

            Debug.Log("Photon Integration initialized successfully");
            yield return null;
        }

        private void SetupEventSubscriptions()
        {
            Debug.Log("Setting up event subscriptions...");

            // Session events
            SessionManager.OnSessionJoined += OnSessionJoined;
            SessionManager.OnSessionLeft += OnSessionLeft;
            SessionManager.OnRoleAssigned += OnRoleAssigned;
            SessionManager.OnSessionError += OnSessionError;

            // Dialog events
            EnhancedDialogoUI.OnDialogStateChanged += OnDialogStateChanged;
            EnhancedDialogoUI.OnTurnChanged += OnTurnChanged;
            EnhancedDialogoUI.OnResponseSelected += OnResponseSelected;

            // Realtime events
            RealtimeSyncManager.OnDialogStateChanged += OnRealtimeDialogStateChanged;
            RealtimeSyncManager.OnParticipantsChanged += OnParticipantsChanged;
            RealtimeSyncManager.OnConnectionStatusChanged += OnRealtimeConnectionChanged;
            RealtimeSyncManager.OnSyncError += OnRealtimeSyncError;

            // Photon events
            EnhancedPhotonIntegration.OnPhotonConnectionChanged += OnPhotonConnectionChanged;
            EnhancedPhotonIntegration.OnRoomConnectionChanged += OnRoomConnectionChanged;
            EnhancedPhotonIntegration.OnPlayerJoined += OnPlayerJoined;
            EnhancedPhotonIntegration.OnPlayerLeft += OnPlayerLeft;
            EnhancedPhotonIntegration.OnNetworkError += OnNetworkError;

            Debug.Log("Event subscriptions setup complete");
        }

        private void FinalizeInitialization()
        {
            isInitialized = true;
            SetGameState(GameState.Ready);
            OnInitializationComplete?.Invoke(true);

            Debug.Log("System initialization finalized");
        }

        #endregion

        #region Game State Management

        private void SetGameState(GameState newState)
        {
            if (currentState != newState)
            {
                GameState previousState = currentState;
                currentState = newState;

                Debug.Log($"Game state changed: {previousState} -> {newState}");
                OnGameStateChanged?.Invoke(newState);

                HandleGameStateChange(previousState, newState);
            }
        }

        private void HandleGameStateChange(GameState previousState, GameState newState)
        {
            switch (newState)
            {
                case GameState.Initializing:
                    HandleInitializingState();
                    break;
                case GameState.Ready:
                    HandleReadyState();
                    break;
                case GameState.InSession:
                    HandleInSessionState();
                    break;
                case GameState.InDialog:
                    HandleInDialogState();
                    break;
                case GameState.Error:
                    HandleErrorState();
                    break;
            }
        }

        private void HandleInitializingState()
        {
            // Show loading UI
            Debug.Log("System is initializing...");
        }

        private void HandleReadyState()
        {
            // System is ready, show main menu
            Debug.Log("System is ready");
        }

        private void HandleInSessionState()
        {
            // User is in a session
            Debug.Log("User is in session");
        }

        private void HandleInDialogState()
        {
            // User is participating in a dialog
            Debug.Log("User is in dialog");
        }

        private void HandleErrorState()
        {
            // System error occurred
            Debug.Log("System error occurred");
        }

        #endregion

        #region Event Handlers

        private void OnSessionJoined(SesionData session)
        {
            Debug.Log($"Session joined: {session.nombre}");
            SetGameState(GameState.InSession);
        }

        private void OnSessionLeft()
        {
            Debug.Log("Session left");
            SetGameState(GameState.Ready);
        }

        private void OnRoleAssigned(AsignacionRolData role)
        {
            Debug.Log($"Role assigned: {role.rol.nombre}");
        }

        private void OnSessionError(string error)
        {
            Debug.LogError($"Session error: {error}");
            OnSystemError?.Invoke($"Session error: {error}");
        }

        private void OnDialogStateChanged(DialogoEstado dialogState)
        {
            Debug.Log($"Dialog state changed: {dialogState.estado}");
            SetGameState(GameState.InDialog);
        }

        private void OnTurnChanged(bool isMyTurn)
        {
            Debug.Log($"Turn changed: {isMyTurn}");
        }

        private void OnResponseSelected(RespuestaUsuario response)
        {
            Debug.Log($"Response selected: {response.texto}");
        }

        private void OnRealtimeDialogStateChanged(DialogoEstado dialogState)
        {
            Debug.Log($"Realtime dialog state changed: {dialogState.estado}");
        }

        private void OnParticipantsChanged(List<JuiciosSimulator.Realtime.Participante> participants)
        {
            Debug.Log($"Participants changed: {participants.Count} participants");
        }

        private void OnRealtimeConnectionChanged(bool isConnected)
        {
            Debug.Log($"Realtime connection changed: {isConnected}");
        }

        private void OnRealtimeSyncError(string error)
        {
            Debug.LogError($"Realtime sync error: {error}");
            OnSystemError?.Invoke($"Realtime sync error: {error}");
        }

        private void OnPhotonConnectionChanged(bool isConnected)
        {
            Debug.Log($"Photon connection changed: {isConnected}");
        }

        private void OnRoomConnectionChanged(bool isInRoom)
        {
            Debug.Log($"Room connection changed: {isInRoom}");
        }

        private void OnPlayerJoined(PlayerData player)
        {
            Debug.Log($"Player joined: {player.roleName}");
        }

        private void OnPlayerLeft(PlayerData player)
        {
            Debug.Log($"Player left: {player.roleName}");
        }

        private void OnNetworkError(string error)
        {
            Debug.LogError($"Network error: {error}");
            OnSystemError?.Invoke($"Network error: {error}");
        }

        #endregion

        #region Public Methods

        public void JoinSession(string sessionCode)
        {
            if (sessionManager != null)
            {
                sessionManager.JoinSessionByCode(sessionCode);
            }
        }

        public void LeaveSession()
        {
            if (sessionManager != null)
            {
                sessionManager.LeaveSession();
            }
        }

        public void RefreshSystem()
        {
            if (isInitialized)
            {
                StartCoroutine(InitializeSystem());
            }
        }

        public void ShutdownSystem()
        {
            Debug.Log("Shutting down system...");

            // Disconnect from all services
            if (photonIntegration != null)
            {
                photonIntegration.DisconnectFromPhoton();
            }

            if (sessionManager != null)
            {
                sessionManager.LeaveSession();
            }

            isInitialized = false;
            SetGameState(GameState.Error);
        }

        public GameState GetCurrentState()
        {
            return currentState;
        }

        public bool IsInitialized()
        {
            return isInitialized;
        }

        public bool IsInSession()
        {
            return sessionManager != null && sessionManager.IsInSession();
        }

        #endregion

        #region Debug

        private void OnGUI()
        {
            if (!showDebugPanel) return;

            GUILayout.BeginArea(new Rect(10, 10, 400, 300));
            GUILayout.Label("=== ENHANCED GAME MANAGER DEBUG ===");
            GUILayout.Label($"State: {currentState}");
            GUILayout.Label($"Initialized: {isInitialized}");
            GUILayout.Label($"In Session: {IsInSession()}");

            if (sessionManager != null)
            {
                var session = sessionManager.GetCurrentSession();
                if (session != null)
                {
                    GUILayout.Label($"Session: {session.nombre}");
                }
            }

            if (photonIntegration != null)
            {
                GUILayout.Label($"Photon Connected: {photonIntegration.IsConnectedToPhoton()}");
                GUILayout.Label($"In Room: {photonIntegration.IsInRoom()}");
            }

            if (realtimeSync != null)
            {
                GUILayout.Label($"Realtime Connected: {realtimeSync.IsConnected()}");
            }

            GUILayout.Space(10);

            if (GUILayout.Button("Refresh System"))
            {
                RefreshSystem();
            }

            if (GUILayout.Button("Shutdown System"))
            {
                ShutdownSystem();
            }

            GUILayout.EndArea();
        }

        #endregion
    }

    #region Enums

    public enum GameState
    {
        Initializing,
        Ready,
        InSession,
        InDialog,
        Error
    }

    #endregion
}
