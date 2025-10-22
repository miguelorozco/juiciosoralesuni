using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using JuiciosSimulator.API;
using JuiciosSimulator.Session;

namespace JuiciosSimulator.Realtime
{
    /// <summary>
    /// Gestor de sincronización en tiempo real
    /// Maneja la sincronización de diálogos, participantes y estado de la sesión
    /// </summary>
    public class RealtimeSyncManager : MonoBehaviour
    {
        [Header("Configuration")]
        public float syncInterval = 2f;
        public float heartbeatInterval = 30f;
        public int maxRetries = 3;
        public float retryDelay = 5f;
        
        [Header("Debug")]
        public bool enableDebugLogs = true;
        public bool showSyncStatus = true;
        
        // References
        private LaravelAPI laravelAPI;
        private SessionManager sessionManager;
        
        // State
        private bool isSyncing = false;
        private bool isConnected = false;
        private int currentRetries = 0;
        private float lastSyncTime = 0f;
        private float lastHeartbeatTime = 0f;
        
        // Cached data
        private DialogoEstado lastDialogState;
        private List<Participante> lastParticipants;
        private SesionData lastSessionData;
        
        // Events
        public static event System.Action<DialogoEstado> OnDialogStateChanged;
        public static event System.Action<List<Participante>> OnParticipantsChanged;
        public static event System.Action<SesionData> OnSessionDataChanged;
        public static event System.Action<bool> OnConnectionStatusChanged;
        public static event System.Action<string> OnSyncError;
        
        private void Start()
        {
            InitializeReferences();
            SubscribeToEvents();
            StartCoroutine(SyncLoop());
            StartCoroutine(HeartbeatLoop());
        }
        
        private void OnDestroy()
        {
            UnsubscribeFromEvents();
        }
        
        #region Initialization
        
        private void InitializeReferences()
        {
            laravelAPI = LaravelAPI.Instance;
            sessionManager = FindObjectOfType<SessionManager>();
        }
        
        private void SubscribeToEvents()
        {
            SessionManager.OnSessionJoined += OnSessionJoined;
            SessionManager.OnSessionLeft += OnSessionLeft;
            LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
            LaravelAPI.OnError += OnLaravelError;
        }
        
        private void UnsubscribeFromEvents()
        {
            SessionManager.OnSessionJoined -= OnSessionJoined;
            SessionManager.OnSessionLeft -= OnSessionLeft;
            LaravelAPI.OnUserLoggedIn -= OnUserLoggedIn;
            LaravelAPI.OnError -= OnLaravelError;
        }
        
        #endregion
        
        #region Sync Loop
        
        private IEnumerator SyncLoop()
        {
            while (true)
            {
                yield return new WaitForSeconds(syncInterval);
                
                if (ShouldSync())
                {
                    yield return StartCoroutine(PerformSync());
                }
            }
        }
        
        private bool ShouldSync()
        {
            return isConnected && 
                   sessionManager != null && 
                   sessionManager.IsInSession() && 
                   !isSyncing;
        }
        
        private IEnumerator PerformSync()
        {
            isSyncing = true;
            
            try
            {
                // Sync dialog state
                yield return StartCoroutine(SyncDialogState());
                
                // Sync participants
                yield return StartCoroutine(SyncParticipants());
                
                // Sync session data
                yield return StartCoroutine(SyncSessionData());
                
                // Reset retry counter on success
                currentRetries = 0;
                lastSyncTime = Time.time;
                
                if (enableDebugLogs)
                {
                    Debug.Log("Sync completed successfully");
                }
            }
            catch (System.Exception e)
            {
                HandleSyncError($"Sync failed: {e.Message}");
            }
            finally
            {
                isSyncing = false;
            }
        }
        
        #endregion
        
        #region Dialog State Sync
        
        private IEnumerator SyncDialogState()
        {
            if (sessionManager == null || !sessionManager.IsInSession()) yield break;
            
            var session = sessionManager.GetCurrentSession();
            if (session == null) yield break;
            
            using (var request = new UnityEngine.Networking.UnityWebRequest($"{laravelAPI.baseURL}/api/unity/sesion/{session.id}/dialogo-estado", "GET"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {laravelAPI.authToken}");
                request.SetRequestHeader("X-Unity-Version", Application.unityVersion);
                request.SetRequestHeader("X-Unity-Platform", Application.platform.ToString());
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityEngine.Networking.UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<DialogoEstado>>(request.downloadHandler.text);
                    if (response.success && response.data != null)
                    {
                        var newDialogState = response.data;
                        
                        // Check if dialog state has changed
                        if (HasDialogStateChanged(newDialogState))
                        {
                            lastDialogState = newDialogState;
                            OnDialogStateChanged?.Invoke(newDialogState);
                            
                            if (enableDebugLogs)
                            {
                                Debug.Log($"Dialog state updated: {newDialogState.estado}");
                            }
                        }
                    }
                }
                else
                {
                    HandleSyncError($"Failed to sync dialog state: {request.error}");
                }
            }
        }
        
        private bool HasDialogStateChanged(DialogoEstado newState)
        {
            if (lastDialogState == null) return true;
            
            return lastDialogState.estado != newState.estado ||
                   lastDialogState.dialogo_activo != newState.dialogo_activo ||
                   lastDialogState.nodo_actual.id != newState.nodo_actual.id ||
                   lastDialogState.participantes.Count != newState.participantes.Count;
        }
        
        #endregion
        
        #region Participants Sync
        
        private IEnumerator SyncParticipants()
        {
            if (sessionManager == null || !sessionManager.IsInSession()) yield break;
            
            var session = sessionManager.GetCurrentSession();
            if (session == null) yield break;
            
            using (var request = new UnityEngine.Networking.UnityWebRequest($"{laravelAPI.baseURL}/api/unity/sesion/{session.id}/participantes", "GET"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {laravelAPI.authToken}");
                request.SetRequestHeader("X-Unity-Version", Application.unityVersion);
                request.SetRequestHeader("X-Unity-Platform", Application.platform.ToString());
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityEngine.Networking.UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<List<Participante>>>(request.downloadHandler.text);
                    if (response.success && response.data != null)
                    {
                        var newParticipants = response.data;
                        
                        // Check if participants have changed
                        if (HasParticipantsChanged(newParticipants))
                        {
                            lastParticipants = newParticipants;
                            OnParticipantsChanged?.Invoke(newParticipants);
                            
                            if (enableDebugLogs)
                            {
                                Debug.Log($"Participants updated: {newParticipants.Count} participants");
                            }
                        }
                    }
                }
                else
                {
                    HandleSyncError($"Failed to sync participants: {request.error}");
                }
            }
        }
        
        private bool HasParticipantsChanged(List<Participante> newParticipants)
        {
            if (lastParticipants == null) return true;
            if (lastParticipants.Count != newParticipants.Count) return true;
            
            for (int i = 0; i < newParticipants.Count; i++)
            {
                if (lastParticipants[i].usuario_id != newParticipants[i].usuario_id ||
                    lastParticipants[i].es_turno != newParticipants[i].es_turno)
                {
                    return true;
                }
            }
            
            return false;
        }
        
        #endregion
        
        #region Session Data Sync
        
        private IEnumerator SyncSessionData()
        {
            if (sessionManager == null || !sessionManager.IsInSession()) yield break;
            
            var session = sessionManager.GetCurrentSession();
            if (session == null) yield break;
            
            using (var request = new UnityEngine.Networking.UnityWebRequest($"{laravelAPI.baseURL}/api/unity/sesion/{session.id}", "GET"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {laravelAPI.authToken}");
                request.SetRequestHeader("X-Unity-Version", Application.unityVersion);
                request.SetRequestHeader("X-Unity-Platform", Application.platform.ToString());
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityEngine.Networking.UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<SesionData>>(request.downloadHandler.text);
                    if (response.success && response.data != null)
                    {
                        var newSessionData = response.data;
                        
                        // Check if session data has changed
                        if (HasSessionDataChanged(newSessionData))
                        {
                            lastSessionData = newSessionData;
                            OnSessionDataChanged?.Invoke(newSessionData);
                            
                            if (enableDebugLogs)
                            {
                                Debug.Log($"Session data updated: {newSessionData.estado}");
                            }
                        }
                    }
                }
                else
                {
                    HandleSyncError($"Failed to sync session data: {request.error}");
                }
            }
        }
        
        private bool HasSessionDataChanged(SesionData newSessionData)
        {
            if (lastSessionData == null) return true;
            
            return lastSessionData.estado != newSessionData.estado ||
                   lastSessionData.participantes_count != newSessionData.participantes_count;
        }
        
        #endregion
        
        #region Heartbeat
        
        private IEnumerator HeartbeatLoop()
        {
            while (true)
            {
                yield return new WaitForSeconds(heartbeatInterval);
                
                if (isConnected && sessionManager != null && sessionManager.IsInSession())
                {
                    yield return StartCoroutine(SendHeartbeat());
                }
            }
        }
        
        private IEnumerator SendHeartbeat()
        {
            if (sessionManager == null || !sessionManager.IsInSession()) yield break;
            
            var session = sessionManager.GetCurrentSession();
            if (session == null) yield break;
            
            using (var request = new UnityEngine.Networking.UnityWebRequest($"{laravelAPI.baseURL}/api/unity/sesion/{session.id}/heartbeat", "POST"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {laravelAPI.authToken}");
                request.SetRequestHeader("Content-Type", "application/json");
                
                var heartbeatData = new
                {
                    timestamp = System.DateTime.UtcNow.ToString("yyyy-MM-ddTHH:mm:ssZ"),
                    unity_version = Application.unityVersion,
                    platform = Application.platform.ToString()
                };
                
                string jsonData = JsonUtility.ToJson(heartbeatData);
                request.uploadHandler = new UnityEngine.Networking.UploadHandlerRaw(System.Text.Encoding.UTF8.GetBytes(jsonData));
                request.downloadHandler = new UnityEngine.Networking.DownloadHandlerBuffer();
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityEngine.Networking.UnityWebRequest.Result.Success)
                {
                    lastHeartbeatTime = Time.time;
                    
                    if (enableDebugLogs)
                    {
                        Debug.Log("Heartbeat sent successfully");
                    }
                }
                else
                {
                    HandleSyncError($"Heartbeat failed: {request.error}");
                }
            }
        }
        
        #endregion
        
        #region Error Handling
        
        private void HandleSyncError(string error)
        {
            currentRetries++;
            
            if (enableDebugLogs)
            {
                Debug.LogError($"Sync error (attempt {currentRetries}/{maxRetries}): {error}");
            }
            
            OnSyncError?.Invoke(error);
            
            if (currentRetries >= maxRetries)
            {
                // Max retries reached, try to reconnect
                StartCoroutine(AttemptReconnection());
            }
        }
        
        private IEnumerator AttemptReconnection()
        {
            Debug.Log("Attempting to reconnect...");
            
            // Wait before retrying
            yield return new WaitForSeconds(retryDelay);
            
            // Reset retry counter
            currentRetries = 0;
            
            // Try to reconnect
            if (laravelAPI != null)
            {
                laravelAPI.CheckServerStatus();
            }
        }
        
        #endregion
        
        #region Event Handlers
        
        private void OnSessionJoined(SesionData session)
        {
            isConnected = true;
            OnConnectionStatusChanged?.Invoke(true);
            
            if (enableDebugLogs)
            {
                Debug.Log($"RealtimeSyncManager: Session joined - {session.nombre}");
            }
        }
        
        private void OnSessionLeft()
        {
            isConnected = false;
            OnConnectionStatusChanged?.Invoke(false);
            
            // Clear cached data
            lastDialogState = null;
            lastParticipants = null;
            lastSessionData = null;
            
            if (enableDebugLogs)
            {
                Debug.Log("RealtimeSyncManager: Session left");
            }
        }
        
        private void OnUserLoggedIn(UserData user)
        {
            if (enableDebugLogs)
            {
                Debug.Log($"RealtimeSyncManager: User logged in - {user.name}");
            }
        }
        
        private void OnLaravelError(string error)
        {
            HandleSyncError($"Laravel error: {error}");
        }
        
        #endregion
        
        #region Public Methods
        
        public void ForceSync()
        {
            if (ShouldSync())
            {
                StartCoroutine(PerformSync());
            }
        }
        
        public void SetSyncInterval(float interval)
        {
            syncInterval = Mathf.Max(0.5f, interval);
        }
        
        public bool IsConnected()
        {
            return isConnected;
        }
        
        public float GetLastSyncTime()
        {
            return lastSyncTime;
        }
        
        public int GetRetryCount()
        {
            return currentRetries;
        }
        
        #endregion
        
        #region Debug
        
        private void OnGUI()
        {
            if (!showSyncStatus) return;
            
            GUILayout.BeginArea(new Rect(10, 10, 300, 150));
            GUILayout.Label("=== Realtime Sync Status ===");
            GUILayout.Label($"Connected: {isConnected}");
            GUILayout.Label($"Syncing: {isSyncing}");
            GUILayout.Label($"Last Sync: {Time.time - lastSyncTime:F1}s ago");
            GUILayout.Label($"Retries: {currentRetries}/{maxRetries}");
            
            if (GUILayout.Button("Force Sync"))
            {
                ForceSync();
            }
            
            GUILayout.EndArea();
        }
        
        #endregion
    }
    
    #region Data Classes
    
    [System.Serializable]
    public class Participante
    {
        public int usuario_id;
        public string nombre;
        public string apellido;
        public string email;
        public bool es_turno;
        public bool conectado;
        public RolData rol;
        public UsuarioData usuario;
    }
    
    [System.Serializable]
    public class UsuarioData
    {
        public int id;
        public string nombre;
        public string apellido;
        public string email;
        public string tipo;
        public bool activo;
    }
    
    [System.Serializable]
    public class APIResponse<T>
    {
        public bool success;
        public string message;
        public T data;
        public string error_code;
    }
    
    #endregion
}
