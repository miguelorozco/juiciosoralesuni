using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;
using TMPro;
using JuiciosSimulator.API;

namespace JuiciosSimulator.Session
{
    /// <summary>
    /// Gestor de sesiones de juicios orales
    /// Maneja la conexión a sesiones específicas y asignación de roles
    /// </summary>
    public class SessionManager : MonoBehaviour
    {
        [Header("UI Elements")]
        public GameObject sessionSelectionPanel;
        public GameObject sessionInfoPanel;
        public GameObject roleAssignmentPanel;
        public TMP_InputField sessionCodeInput;
        public Button joinSessionButton;
        public Button refreshSessionsButton;

        [Header("Session Info UI")]
        public TextMeshProUGUI sessionNameText;
        public TextMeshProUGUI sessionDescriptionText;
        public TextMeshProUGUI instructorNameText;
        public TextMeshProUGUI sessionStatusText;
        public TextMeshProUGUI assignedRoleText;
        public TextMeshProUGUI participantsCountText;

        [Header("Role Assignment UI")]
        public TextMeshProUGUI roleNameText;
        public TextMeshProUGUI roleDescriptionText;
        public Button confirmRoleButton;
        public Button rejectRoleButton;

        [Header("Configuration")]
        public bool autoJoinOnLogin = false;
        public float sessionRefreshInterval = 30f;

        // Current session data
        private SesionData currentSession;
        private AsignacionRolData currentRoleAssignment;
        private bool isWaitingForRoleConfirmation = false;

        // Events
        public static event System.Action<SesionData> OnSessionJoined;
        public static event System.Action<AsignacionRolData> OnRoleAssigned;
        public static event System.Action<string> OnSessionError;
        public static event System.Action OnSessionLeft;

        private void Start()
        {
            SetupUI();
            SubscribeToEvents();

            if (autoJoinOnLogin)
            {
                StartCoroutine(WaitForLoginAndJoin());
            }
        }

        private void OnDestroy()
        {
            UnsubscribeFromEvents();
        }

        #region Setup

        private void SetupUI()
        {
            // Setup buttons
            joinSessionButton.onClick.AddListener(OnJoinSessionClicked);
            refreshSessionsButton.onClick.AddListener(OnRefreshSessionsClicked);
            confirmRoleButton.onClick.AddListener(OnConfirmRoleClicked);
            rejectRoleButton.onClick.AddListener(OnRejectRoleClicked);

            // Initial UI state
            sessionSelectionPanel.SetActive(true);
            sessionInfoPanel.SetActive(false);
            roleAssignmentPanel.SetActive(false);
        }

        private void SubscribeToEvents()
        {
            LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
            LaravelAPI.OnError += OnLaravelError;
        }

        private void UnsubscribeFromEvents()
        {
            LaravelAPI.OnUserLoggedIn -= OnUserLoggedIn;
            LaravelAPI.OnError -= OnLaravelError;
        }

        #endregion

        #region Session Management

        /// <summary>
        /// Join a session by code
        /// </summary>
        public void JoinSessionByCode(string sessionCode)
        {
            if (string.IsNullOrEmpty(sessionCode))
            {
                ShowError("Código de sesión inválido");
                return;
            }

            StartCoroutine(JoinSessionCoroutine(sessionCode));
        }

        private IEnumerator JoinSessionCoroutine(string sessionCode)
        {
            // Get session info
            yield return StartCoroutine(GetSessionInfo(sessionCode));

            if (currentSession != null)
            {
                // Check if user has role assignment
                yield return StartCoroutine(GetUserRoleAssignment());

                if (currentRoleAssignment != null)
                {
                    // User has role, show role assignment panel
                    ShowRoleAssignment();
                }
                else
                {
                    // User doesn't have role, show error
                    ShowError("No tienes un rol asignado en esta sesión");
                }
            }
        }

        private IEnumerator GetSessionInfo(string sessionCode)
        {
            using (var request = new UnityEngine.Networking.UnityWebRequest($"{LaravelAPI.Instance.baseURL}/api/unity/sesiones/buscar-por-codigo/{sessionCode}", "GET"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {LaravelAPI.Instance.authToken}");
                request.SetRequestHeader("X-Unity-Version", Application.unityVersion);
                request.SetRequestHeader("X-Unity-Platform", Application.platform.ToString());

                yield return request.SendWebRequest();

                if (request.result == UnityEngine.Networking.UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<SesionData>>(request.downloadHandler.text);
                    if (response.success)
                    {
                        currentSession = response.data;
                        ShowSessionInfo();
                    }
                    else
                    {
                        ShowError(response.message);
                    }
                }
                else
                {
                    ShowError($"Error al obtener información de la sesión: {request.error}");
                }
            }
        }

        private IEnumerator GetUserRoleAssignment()
        {
            if (currentSession == null) yield break;

            using (var request = new UnityEngine.Networking.UnityWebRequest($"{LaravelAPI.Instance.baseURL}/api/unity/sesiones/{currentSession.id}/mi-rol", "GET"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {LaravelAPI.Instance.authToken}");
                request.SetRequestHeader("X-Unity-Version", Application.unityVersion);
                request.SetRequestHeader("X-Unity-Platform", Application.platform.ToString());

                yield return request.SendWebRequest();

                if (request.result == UnityEngine.Networking.UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<AsignacionRolData>>(request.downloadHandler.text);
                    if (response.success)
                    {
                        currentRoleAssignment = response.data;
                    }
                }
            }
        }

        #endregion

        #region UI Management

        private void ShowSessionInfo()
        {
            if (currentSession == null) return;

            sessionNameText.text = currentSession.nombre;
            sessionDescriptionText.text = currentSession.descripcion;
            instructorNameText.text = $"Instructor: {currentSession.instructor.name}";
            sessionStatusText.text = $"Estado: {currentSession.estado}";
            participantsCountText.text = $"Participantes: {currentSession.participantes_count}/{currentSession.max_participantes}";

            sessionSelectionPanel.SetActive(false);
            sessionInfoPanel.SetActive(true);
        }

        private void ShowRoleAssignment()
        {
            if (currentRoleAssignment == null) return;

            roleNameText.text = currentRoleAssignment.rol.nombre;
            roleDescriptionText.text = currentRoleAssignment.rol.descripcion;

            roleAssignmentPanel.SetActive(true);
            isWaitingForRoleConfirmation = true;
        }

        private void HideRoleAssignment()
        {
            roleAssignmentPanel.SetActive(false);
            isWaitingForRoleConfirmation = false;
        }

        #endregion

        #region Button Handlers

        private void OnJoinSessionClicked()
        {
            string sessionCode = sessionCodeInput.text.Trim();
            JoinSessionByCode(sessionCode);
        }

        private void OnRefreshSessionsClicked()
        {
            // Refresh available sessions
            StartCoroutine(RefreshAvailableSessions());
        }

        private void OnConfirmRoleClicked()
        {
            if (currentRoleAssignment != null)
            {
                StartCoroutine(ConfirmRoleAssignment());
            }
        }

        private void OnRejectRoleClicked()
        {
            HideRoleAssignment();
            ShowError("Has rechazado el rol asignado. Contacta al instructor.");
        }

        #endregion

        #region Role Assignment

        private IEnumerator ConfirmRoleAssignment()
        {
            if (currentRoleAssignment == null) yield break;

            using (var request = new UnityEngine.Networking.UnityWebRequest($"{LaravelAPI.Instance.baseURL}/api/unity/sesiones/{currentSession.id}/confirmar-rol", "POST"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {LaravelAPI.Instance.authToken}");
                request.SetRequestHeader("Content-Type", "application/json");

                string jsonData = JsonUtility.ToJson(new { confirmado = true });
                request.uploadHandler = new UnityEngine.Networking.UploadHandlerRaw(System.Text.Encoding.UTF8.GetBytes(jsonData));
                request.downloadHandler = new UnityEngine.Networking.DownloadHandlerBuffer();

                yield return request.SendWebRequest();

                if (request.result == UnityEngine.Networking.UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<AsignacionRolData>>(request.downloadHandler.text);
                    if (response.success)
                    {
                        currentRoleAssignment = response.data;
                        HideRoleAssignment();
                        OnRoleAssigned?.Invoke(currentRoleAssignment);
                        OnSessionJoined?.Invoke(currentSession);

                        // Update UI with confirmed role
                        assignedRoleText.text = $"Rol: {currentRoleAssignment.rol.nombre}";
                    }
                    else
                    {
                        ShowError(response.message);
                    }
                }
                else
                {
                    ShowError($"Error al confirmar rol: {request.error}");
                }
            }
        }

        #endregion

        #region Session Refresh

        private IEnumerator RefreshAvailableSessions()
        {
            using (var request = new UnityEngine.Networking.UnityWebRequest($"{LaravelAPI.Instance.baseURL}/api/unity/sesiones/disponibles", "GET"))
            {
                request.SetRequestHeader("Authorization", $"Bearer {LaravelAPI.Instance.authToken}");

                yield return request.SendWebRequest();

                if (request.result == UnityEngine.Networking.UnityWebRequest.Result.Success)
                {
                    var response = JsonUtility.FromJson<APIResponse<List<SesionData>>>(request.downloadHandler.text);
                    if (response.success)
                    {
                        // Update UI with available sessions
                        Debug.Log($"Sesiones disponibles: {response.data.Count}");
                    }
                }
            }
        }

        #endregion

        #region Event Handlers

        private void OnUserLoggedIn(UserData user)
        {
            Debug.Log($"Usuario logueado: {user.name}");

            if (autoJoinOnLogin)
            {
                StartCoroutine(WaitForLoginAndJoin());
            }
        }

        private void OnLaravelError(string error)
        {
            ShowError($"Error de Laravel: {error}");
        }

        #endregion

        #region Auto Join

        private IEnumerator WaitForLoginAndJoin()
        {
            // Wait for user to be logged in
            while (!LaravelAPI.Instance.isConnected)
            {
                yield return new WaitForSeconds(0.5f);
            }

            // Try to join session from URL parameters or saved data
            string sessionCode = GetSessionCodeFromURL();
            if (!string.IsNullOrEmpty(sessionCode))
            {
                JoinSessionByCode(sessionCode);
            }
        }

        private string GetSessionCodeFromURL()
        {
            // Try to get session code from URL parameters
            // This would work in WebGL builds
            return PlayerPrefs.GetString("SessionCode", "");
        }

        #endregion

        #region Utilities

        private void ShowError(string message)
        {
            Debug.LogError($"SessionManager Error: {message}");
            OnSessionError?.Invoke(message);
        }

        public void LeaveSession()
        {
            currentSession = null;
            currentRoleAssignment = null;

            sessionSelectionPanel.SetActive(true);
            sessionInfoPanel.SetActive(false);
            roleAssignmentPanel.SetActive(false);

            OnSessionLeft?.Invoke();
        }

        #endregion

        #region Public Methods

        public SesionData GetCurrentSession()
        {
            return currentSession;
        }

        public AsignacionRolData GetCurrentRoleAssignment()
        {
            return currentRoleAssignment;
        }

        public bool IsInSession()
        {
            return currentSession != null && currentRoleAssignment != null;
        }

        public void SetSessionCode(string code)
        {
            sessionCodeInput.text = code;
        }

        #endregion
    }

    #region Data Classes

    [System.Serializable]
    public class SesionData
    {
        public int id;
        public string nombre;
        public string descripcion;
        public string estado;
        public string fecha_inicio;
        public string fecha_fin;
        public int max_participantes;
        public int participantes_count;
        public UserData instructor;
        public PlantillaData plantilla;
        public string unity_room_id;
        public Dictionary<string, object> configuracion;
    }

    [System.Serializable]
    public class AsignacionRolData
    {
        public int id;
        public int sesion_id;
        public int usuario_id;
        public int rol_id;
        public bool confirmado;
        public string fecha_asignacion;
        public string notas;
        public RolData rol;
        public UserData usuario;
    }

    [System.Serializable]
    public class RolData
    {
        public int id;
        public string nombre;
        public string descripcion;
        public string color;
        public bool activo;
    }

    [System.Serializable]
    public class PlantillaData
    {
        public int id;
        public string nombre;
        public string descripcion;
        public bool publica;
        public string fecha_creacion;
        public Dictionary<string, object> configuracion;
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
