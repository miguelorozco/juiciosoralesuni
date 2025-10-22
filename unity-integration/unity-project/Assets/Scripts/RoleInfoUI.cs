using UnityEngine;
using UnityEngine.UI;
using TMPro;
using JuiciosSimulator.Session;
using JuiciosSimulator.API;

namespace JuiciosSimulator.UI
{
    /// <summary>
    /// UI para mostrar información del rol asignado desde Laravel
    /// Reemplaza la selección manual de roles con información automática
    /// </summary>
    public class RoleInfoUI : MonoBehaviour
    {
        [Header("Role Information")]
        public TextMeshProUGUI roleNameText;
        public TextMeshProUGUI roleDescriptionText;
        public TextMeshProUGUI sessionInfoText;
        public TextMeshProUGUI participantInfoText;

        [Header("Visual Elements")]
        public Image roleColorIndicator;
        public GameObject roleIcon;
        public Button readyButton;
        public Button leaveSessionButton;

        [Header("Status")]
        public TextMeshProUGUI statusText;
        public GameObject loadingIndicator;

        [Header("Session Integration")]
        public SessionManager sessionManager;

        [Header("Debug")]
        public bool showDebugLogs = true;

        private string currentRole;
        private SessionData currentSession;

        void Start()
        {
            InitializeRoleUI();
        }

        /// <summary>
        /// Inicializa la UI de información de rol
        /// </summary>
        private void InitializeRoleUI()
        {
            try
            {
                // Buscar SessionManager si no está asignado
                if (sessionManager == null)
                {
                    sessionManager = FindObjectOfType<SessionManager>();
                }

                if (sessionManager == null)
                {
                    Debug.LogError("RoleInfoUI: SessionManager no encontrado");
                    ShowError("Error: SessionManager no encontrado");
                    return;
                }

                // Suscribirse a eventos
                SubscribeToSessionEvents();

                // Configurar botones
                SetupButtons();

                // Mostrar estado inicial
                ShowLoadingState("Esperando asignación de rol...");

                if (showDebugLogs)
                {
                    Debug.Log("RoleInfoUI: Inicializado correctamente");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"RoleInfoUI: Error en inicialización: {e.Message}");
                ShowError($"Error de inicialización: {e.Message}");
            }
        }

        /// <summary>
        /// Se suscribe a los eventos del SessionManager
        /// </summary>
        private void SubscribeToSessionEvents()
        {
            if (sessionManager != null)
            {
                // TODO: Implementar eventos del SessionManager
                // SessionManager.OnSessionJoined += OnSessionJoined;
                // SessionManager.OnRoleAssigned += OnRoleAssigned;
                // SessionManager.OnSessionError += OnSessionError;
                // SessionManager.OnParticipantsUpdated += OnParticipantsUpdated;
            }
        }

        /// <summary>
        /// Se desuscribe de los eventos del SessionManager
        /// </summary>
        private void UnsubscribeFromSessionEvents()
        {
            if (sessionManager != null)
            {
                // TODO: Implementar eventos del SessionManager
                // SessionManager.OnSessionJoined -= OnSessionJoined;
                // SessionManager.OnRoleAssigned -= OnRoleAssigned;
                // SessionManager.OnSessionError -= OnSessionError;
                // SessionManager.OnParticipantsUpdated -= OnParticipantsUpdated;
            }
        }

        /// <summary>
        /// Configura los botones de la UI
        /// </summary>
        private void SetupButtons()
        {
            if (readyButton != null)
            {
                readyButton.onClick.AddListener(OnReadyButtonClicked);
                readyButton.interactable = false;
            }

            if (leaveSessionButton != null)
            {
                leaveSessionButton.onClick.AddListener(OnLeaveSessionButtonClicked);
            }
        }

        /// <summary>
        /// Callback cuando se une a una sesión
        /// </summary>
        private void OnSessionJoined(SessionData session)
        {
            try
            {
                currentSession = session;
                UpdateSessionInfo();

                if (showDebugLogs)
                {
                    Debug.Log($"RoleInfoUI: Sesión unida - {session.session.nombre}");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"RoleInfoUI: Error en OnSessionJoined: {e.Message}");
            }
        }

        /// <summary>
        /// Callback cuando se asigna un rol
        /// </summary>
        private void OnRoleAssigned(string role)
        {
            try
            {
                currentRole = role;
                UpdateRoleInfo();

                if (readyButton != null)
                {
                    readyButton.interactable = true;
                }

                if (showDebugLogs)
                {
                    Debug.Log($"RoleInfoUI: Rol asignado - {role}");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"RoleInfoUI: Error en OnRoleAssigned: {e.Message}");
            }
        }

        /// <summary>
        /// Callback cuando hay un error en la sesión
        /// </summary>
        private void OnSessionError(string error)
        {
            Debug.LogError($"RoleInfoUI: Error de sesión - {error}");
            ShowError($"Error de sesión: {error}");
        }

        /// <summary>
        /// Callback cuando se actualizan los participantes
        /// </summary>
        private void OnParticipantsUpdated(System.Collections.Generic.List<JuiciosSimulator.Realtime.Participante> participants)
        {
            try
            {
                UpdateParticipantInfo(participants);
            }
            catch (System.Exception e)
            {
                Debug.LogError($"RoleInfoUI: Error actualizando participantes: {e.Message}");
            }
        }

        /// <summary>
        /// Actualiza la información de la sesión
        /// </summary>
        private void UpdateSessionInfo()
        {
            try
            {
                if (currentSession != null && sessionInfoText != null)
                {
                    sessionInfoText.text = $"Sesión: {currentSession.session.nombre}\n" +
                                         $"Instructor: {currentSession.session.instructor.name}\n" +
                                         $"Estado: {currentSession.session.estado}";
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"RoleInfoUI: Error actualizando información de sesión: {e.Message}");
            }
        }

        /// <summary>
        /// Actualiza la información del rol
        /// </summary>
        private void UpdateRoleInfo()
        {
            try
            {
                if (string.IsNullOrEmpty(currentRole))
                {
                    return;
                }

                // Actualizar nombre del rol
                if (roleNameText != null)
                {
                    roleNameText.text = currentRole;
                }

                // Actualizar descripción del rol
                if (roleDescriptionText != null)
                {
                    roleDescriptionText.text = GetRoleDescription(currentRole);
                }

                // Actualizar color del rol
                if (roleColorIndicator != null)
                {
                    roleColorIndicator.color = GetRoleColor(currentRole);
                }

                // Actualizar icono del rol
                UpdateRoleIcon();

                if (showDebugLogs)
                {
                    Debug.Log($"RoleInfoUI: Información del rol '{currentRole}' actualizada");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"RoleInfoUI: Error actualizando información del rol: {e.Message}");
            }
        }

        /// <summary>
        /// Actualiza la información de los participantes
        /// </summary>
        private void UpdateParticipantInfo(System.Collections.Generic.List<JuiciosSimulator.Realtime.Participante> participants)
        {
            try
            {
                if (participantInfoText != null && participants != null)
                {
                    string participantText = $"Participantes ({participants.Count}):\n";

                    foreach (var participant in participants)
                    {
                        string status = participant.es_turno ? "Activo" : "Inactivo";
                        participantText += $"• {participant.nombre} ({participant.rol}) - {status}\n";
                    }

                    participantInfoText.text = participantText;
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"RoleInfoUI: Error actualizando información de participantes: {e.Message}");
            }
        }

        /// <summary>
        /// Obtiene la descripción de un rol
        /// </summary>
        private string GetRoleDescription(string role)
        {
            switch (role.ToLower())
            {
                case "juez":
                    return "Dirige el juicio y toma las decisiones finales";
                case "fiscal":
                    return "Representa la acusación y presenta pruebas";
                case "defensa":
                    return "Representa al acusado y presenta la defensa";
                case "testigo1":
                case "testigo2":
                    return "Proporciona testimonio sobre los hechos";
                case "policía1":
                case "policía2":
                    return "Presenta evidencia policial y testimonio";
                case "psicólogo":
                    return "Proporciona análisis psicológico";
                case "acusado":
                    return "La persona acusada en el juicio";
                case "secretario":
                    return "Registra las actas del juicio";
                case "abogado1":
                case "abogado2":
                    return "Asiste en la representación legal";
                case "perito1":
                case "perito2":
                    return "Proporciona testimonio experto";
                case "víctima":
                    return "La persona afectada por los hechos";
                case "acusador":
                    return "Presenta la acusación formal";
                case "periodista":
                    return "Cubre el juicio para los medios";
                case "público1":
                case "público2":
                    return "Observa el juicio como público";
                case "observador":
                    return "Observa el juicio sin participación activa";
                default:
                    return "Rol asignado para participar en el juicio";
            }
        }

        /// <summary>
        /// Obtiene el color asociado a un rol
        /// </summary>
        private Color GetRoleColor(string role)
        {
            switch (role.ToLower())
            {
                case "juez":
                    return new Color(0.8f, 0.2f, 0.2f); // Rojo oscuro
                case "fiscal":
                    return new Color(0.2f, 0.2f, 0.8f); // Azul
                case "defensa":
                    return new Color(0.2f, 0.8f, 0.2f); // Verde
                case "testigo1":
                case "testigo2":
                    return new Color(0.8f, 0.8f, 0.2f); // Amarillo
                case "policía1":
                case "policía2":
                    return new Color(0.5f, 0.5f, 0.5f); // Gris
                case "psicólogo":
                    return new Color(0.8f, 0.2f, 0.8f); // Magenta
                case "acusado":
                    return new Color(0.2f, 0.2f, 0.2f); // Negro
                case "secretario":
                    return new Color(0.6f, 0.6f, 0.6f); // Gris claro
                default:
                    return new Color(0.5f, 0.5f, 0.5f); // Gris por defecto
            }
        }

        /// <summary>
        /// Actualiza el icono del rol
        /// </summary>
        private void UpdateRoleIcon()
        {
            // Aquí podrías cambiar el icono basado en el rol
            // Por ahora solo activamos/desactivamos el objeto
            if (roleIcon != null)
            {
                roleIcon.SetActive(!string.IsNullOrEmpty(currentRole));
            }
        }

        /// <summary>
        /// Muestra el estado de carga
        /// </summary>
        private void ShowLoadingState(string message)
        {
            if (statusText != null)
            {
                statusText.text = message;
            }

            if (loadingIndicator != null)
            {
                loadingIndicator.SetActive(true);
            }
        }

        /// <summary>
        /// Muestra un error
        /// </summary>
        private void ShowError(string error)
        {
            Debug.LogError($"RoleInfoUI: {error}");

            if (statusText != null)
            {
                statusText.text = $"Error: {error}";
            }

            if (loadingIndicator != null)
            {
                loadingIndicator.SetActive(false);
            }
        }

        /// <summary>
        /// Callback del botón Ready
        /// </summary>
        private void OnReadyButtonClicked()
        {
            try
            {
                if (showDebugLogs)
                {
                    Debug.Log($"RoleInfoUI: Usuario listo con rol '{currentRole}'");
                }

                // Notificar que el usuario está listo
                if (sessionManager != null)
                {
                    // TODO: Implementar NotifyUserReady en SessionManager
                    // sessionManager.NotifyUserReady();
                }

                // Ocultar la UI de rol
                gameObject.SetActive(false);
            }
            catch (System.Exception e)
            {
                Debug.LogError($"RoleInfoUI: Error en OnReadyButtonClicked: {e.Message}");
            }
        }

        /// <summary>
        /// Callback del botón Leave Session
        /// </summary>
        private void OnLeaveSessionButtonClicked()
        {
            try
            {
                if (showDebugLogs)
                {
                    Debug.Log("RoleInfoUI: Usuario abandonando sesión");
                }

                // Abandonar la sesión
                if (sessionManager != null)
                {
                    sessionManager.LeaveSession();
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"RoleInfoUI: Error en OnLeaveSessionButtonClicked: {e.Message}");
            }
        }

        /// <summary>
        /// Método público para actualizar manualmente la información
        /// </summary>
        [ContextMenu("Actualizar Información")]
        public void RefreshRoleInfo()
        {
            if (currentSession != null)
            {
                UpdateSessionInfo();
            }

            if (!string.IsNullOrEmpty(currentRole))
            {
                UpdateRoleInfo();
            }
        }

        /// <summary>
        /// Método público para verificar el estado
        /// </summary>
        [ContextMenu("Verificar Estado")]
        public void CheckStatus()
        {
            Debug.Log($"RoleInfoUI: Estado - Rol: {currentRole}, Sesión: {(currentSession != null ? currentSession.session.nombre : "Ninguna")}");
        }

        /// <summary>
        /// Limpia recursos al destruir el objeto
        /// </summary>
        private void OnDestroy()
        {
            UnsubscribeFromSessionEvents();
        }
    }
}
