using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;
using TMPro;
using JuiciosSimulator.API;
using System.Linq;

namespace JuiciosSimulator.Dialogue
{
    /// <summary>
    /// Manager principal para el sistema de diálogos interactivos
    /// </summary>
    public class DialogueManager : MonoBehaviour
    {
        [Header("Configuración")]
        public Canvas dialogueCanvas;
        public Transform dialoguePosition;
        public float dialogueDistance = 3f;
        public float dialogueHeight = 2f;

        [Header("UI Elements")]
        public TextMeshProUGUI characterNameText;
        public TextMeshProUGUI dialogueText;
        public GameObject optionsPanel;
        public Button continueButton;
        public Button[] optionButtons;
        public Image roleIcon;
        public TextMeshProUGUI roleNameText;

        [Header("Notificaciones")]
        public GameObject notificationPanel;
        public TextMeshProUGUI notificationText;
        public Image notificationIcon;

        [Header("Configuración Visual")]
        public Color currentUserColor = Color.green;
        public Color otherUserColor = Color.gray;
        public float dialogueSpeed = 0.05f;

        [Header("Configuración de Avatares Automáticos")]
        public GameObject autoAvatarPrefab;
        public Transform[] avatarSpawnPoints;
        public float autoPlayerDelay = 15f; // Tiempo de espera para avatar automático
        public float autoOptionDelay = 3f; // Tiempo para seleccionar opción automáticamente

        // Estado actual
        private DialogueNode currentNode;
        private RoleFlow currentRoleFlow;
        private DialogueData currentDialogueData;
        private bool isWaitingForUserInput;
        private bool isDialogueActive;

        // Sistema de avatares automáticos
        private Dictionary<string, GameObject> autoAvatars = new Dictionary<string, GameObject>();
        private Dictionary<string, bool> playersInRoom = new Dictionary<string, bool>();
        private Coroutine autoPlayerCoroutine;

        // Referencias
        private Camera mainCamera;
        private LaravelAPI laravelAPI;

        // Eventos
        public static event Action<DialogueNode> OnDialogueStarted;
        public static event Action OnDialogueEnded;
        public static event Action<int> OnOptionSelected;
        public static event Action<string> OnDialogueError;

        // Singleton
        public static DialogueManager Instance { get; private set; }

        private void Awake()
        {
            if (Instance == null)
            {
                Instance = this;
                DontDestroyOnLoad(gameObject);
            }
            else
            {
                Destroy(gameObject);
            }
        }

        private void Start()
        {
            // Retrasar inicialización para esperar a LaravelAPI
            StartCoroutine(InitializeDelayed());
        }

        private System.Collections.IEnumerator InitializeDelayed()
        {
            // ESPERAR a que LaravelAPI esté completamente inicializado
            float timeout = 5f;
            float elapsed = 0f;
            
            while (!LaravelAPI.IsInitialized && elapsed < timeout)
            {
                yield return null;
                elapsed += Time.deltaTime;
            }

            // Esperar varios frames adicionales
            yield return new WaitForEndOfFrame();
            yield return new WaitForEndOfFrame();

            InitializeDialogueSystem();
        }

        private void InitializeDialogueSystem()
        {
            // Obtener referencias
            mainCamera = Camera.main;
            laravelAPI = LaravelAPI.Instance;

            // Configurar canvas
            if (dialogueCanvas == null)
            {
                dialogueCanvas = GetComponent<Canvas>();
            }

            // Configurar canvas como World Space
            dialogueCanvas.renderMode = RenderMode.WorldSpace;
            dialogueCanvas.worldCamera = mainCamera;

            // Suscribirse a eventos
            SubscribeToEvents();

            // Configurar botones
            SetupButtons();

            // Ocultar elementos inicialmente
            HideDialogueUI();

            Debug.Log("Sistema de diálogos inicializado");
        }

        private bool isSubscribedToEvents = false; // Flag para prevenir múltiples suscripciones

        private void SubscribeToEvents()
        {
            // Prevenir múltiples suscripciones
            if (isSubscribedToEvents)
            {
                Debug.LogWarning("[DialogueManager] Ya está suscrito a eventos. Ignorando suscripción duplicada.");
                return;
            }

            isSubscribedToEvents = true;

            if (laravelAPI != null)
            {
                LaravelAPI.OnDialogueDataReceived += OnDialogueDataReceived;
                LaravelAPI.OnError += HandleDialogueError;
            }
        }

        private void SetupButtons()
        {
            // Configurar botón de continuar
            if (continueButton != null)
            {
                continueButton.onClick.AddListener(ContinueDialogue);
            }

            // Configurar botones de opciones
            if (optionButtons != null)
            {
                for (int i = 0; i < optionButtons.Length; i++)
                {
                    int index = i; // Capturar índice para el closure
                    optionButtons[i].onClick.AddListener(() => SelectOption(index));
                }
            }
        }

        #region Configuración de Diálogo

        private bool isSettingUpDialogue = false; // Flag para prevenir recursión en SetupDialogueSystem

        /// <summary>
        /// Configurar el sistema con los datos del diálogo
        /// </summary>
        public void SetupDialogueSystem(DialogueData dialogueData)
        {
            // Prevenir recursión
            if (isSettingUpDialogue)
            {
                Debug.LogWarning("[DialogueManager] SetupDialogueSystem ya está en progreso. Ignorando llamada duplicada.");
                return;
            }

            // Prevenir configuración del mismo diálogo múltiples veces
            if (currentDialogueData != null && dialogueData != null && 
                currentDialogueData.dialogue != null && dialogueData.dialogue != null &&
                currentDialogueData.dialogue.id == dialogueData.dialogue.id)
            {
                Debug.LogWarning($"[DialogueManager] Diálogo {dialogueData.dialogue.id} ya está configurado. Ignorando llamada duplicada.");
                return;
            }

            isSettingUpDialogue = true;

            try
            {
                currentDialogueData = dialogueData;

            // Encontrar el flujo del rol del usuario
            var userRole = dialogueData.user_role;
            var roleFlow = dialogueData.dialogue.roles
                .FirstOrDefault(r => r.id == userRole.id);

            if (roleFlow != null)
            {
                currentRoleFlow = roleFlow;
                Debug.Log($"Sistema configurado para rol: {roleFlow.nombre}");

                // Mostrar información del rol del usuario
                ShowUserRoleInfo(roleFlow);
            }
            else
            {
                Debug.LogError($"No se encontró el flujo para el rol: {userRole.nombre}");
            }
            }
            finally
            {
                isSettingUpDialogue = false;
            }
        }

        /// <summary>
        /// Mostrar información del rol del usuario
        /// </summary>
        private void ShowUserRoleInfo(RoleFlow roleFlow)
        {
            if (roleNameText != null)
            {
                roleNameText.text = roleFlow.nombre;
            }

            if (roleIcon != null)
            {
                // Configurar color del rol
                roleIcon.color = GetRoleColor(roleFlow.color);
            }
        }

        #endregion

        #region Mostrar Diálogos

        /// <summary>
        /// Mostrar un diálogo específico
        /// </summary>
        public void ShowDialogue(DialogueNode dialogue)
        {
            if (dialogue == null)
            {
                Debug.LogError("Intento de mostrar diálogo nulo");
                return;
            }

            currentNode = dialogue;
            isDialogueActive = true;

            // Posicionar canvas cerca del personaje
            PositionDialogueCanvas();

            // Mostrar información del diálogo
            DisplayDialogueContent(dialogue);

            // Configurar interacción según el tipo
            ConfigureDialogueInteraction(dialogue);

            // Invocar evento
            OnDialogueStarted?.Invoke(dialogue);

            Debug.Log($"Mostrando diálogo: {dialogue.titulo}");
        }

        /// <summary>
        /// Posicionar el canvas del diálogo
        /// </summary>
        private void PositionDialogueCanvas()
        {
            if (dialoguePosition != null)
            {
                // Posicionar cerca del personaje
                Vector3 targetPosition = dialoguePosition.position + Vector3.up * dialogueHeight;
                dialogueCanvas.transform.position = targetPosition;

                // Orientar hacia la cámara
                Vector3 lookDirection = mainCamera.transform.position - dialogueCanvas.transform.position;
                lookDirection.y = 0; // Mantener horizontal
                dialogueCanvas.transform.rotation = Quaternion.LookRotation(lookDirection);
            }
        }

        /// <summary>
        /// Mostrar contenido del diálogo
        /// </summary>
        private void DisplayDialogueContent(DialogueNode dialogue)
        {
            // Mostrar nombre del personaje
            if (characterNameText != null)
            {
                characterNameText.text = GetCharacterName(dialogue);
            }

            // Mostrar texto del diálogo
            if (dialogueText != null)
            {
                StartCoroutine(TypeDialogueText(dialogue.contenido));
            }
        }

        /// <summary>
        /// Efecto de escritura del texto
        /// </summary>
        private IEnumerator TypeDialogueText(string text)
        {
            dialogueText.text = "";

            foreach (char c in text)
            {
                dialogueText.text += c;
                yield return new WaitForSeconds(dialogueSpeed);
            }
        }

        /// <summary>
        /// Configurar interacción según el tipo de diálogo
        /// </summary>
        private void ConfigureDialogueInteraction(DialogueNode dialogue)
        {
            switch (dialogue.tipo)
            {
                case "automatic":
                    ShowAutomaticDialogue();
                    break;

                case "decision":
                    ShowDecisionDialogue();
                    break;

                case "final":
                    ShowFinalDialogue();
                    break;

                default:
                    Debug.LogWarning($"Tipo de diálogo desconocido: {dialogue.tipo}");
                    ShowAutomaticDialogue();
                    break;
            }
        }

        #endregion

        #region Tipos de Diálogo

        /// <summary>
        /// Mostrar diálogo automático
        /// </summary>
        private void ShowAutomaticDialogue()
        {
            continueButton.gameObject.SetActive(true);
            optionsPanel.SetActive(false);

            // Solo el dueño del rol puede continuar
            if (IsCurrentUserRole())
            {
                continueButton.interactable = true;
                continueButton.GetComponentInChildren<TextMeshProUGUI>().text = "Continuar";
            }
            else
            {
                continueButton.interactable = false;
                continueButton.GetComponentInChildren<TextMeshProUGUI>().text = "Esperando...";
            }
        }

        /// <summary>
        /// Mostrar diálogo con opciones
        /// </summary>
        private void ShowDecisionDialogue()
        {
            continueButton.gameObject.SetActive(false);
            optionsPanel.SetActive(true);

            // Configurar botones de opciones
            for (int i = 0; i < currentNode.opciones.Count && i < optionButtons.Length; i++)
            {
                var option = currentNode.opciones[i];
                var button = optionButtons[i];

                button.gameObject.SetActive(true);
                button.GetComponentInChildren<TextMeshProUGUI>().text = $"{option.letra}. {option.texto}";

                if (IsCurrentUserRole())
                {
                    button.interactable = true;
                }
                else
                {
                    button.interactable = false;
                }
            }

            // Ocultar botones no utilizados
            for (int i = currentNode.opciones.Count; i < optionButtons.Length; i++)
            {
                optionButtons[i].gameObject.SetActive(false);
            }
        }

        /// <summary>
        /// Mostrar diálogo final
        /// </summary>
        private void ShowFinalDialogue()
        {
            continueButton.gameObject.SetActive(true);
            optionsPanel.SetActive(false);

            continueButton.interactable = true;
            continueButton.GetComponentInChildren<TextMeshProUGUI>().text = "Finalizar";
        }

        #endregion

        #region Interacciones del Usuario

        /// <summary>
        /// Continuar diálogo automático
        /// </summary>
        public void ContinueDialogue()
        {
            if (!IsCurrentUserRole())
            {
                Debug.LogWarning("Usuario no autorizado para continuar diálogo");
                return;
            }

            // Enviar decisión a Laravel
            SendDecisionToLaravel(0, "Continuar");

            // Ocultar UI
            HideDialogueUI();
        }

        /// <summary>
        /// Seleccionar opción
        /// </summary>
        public void SelectOption(int optionIndex)
        {
            if (!IsCurrentUserRole())
            {
                Debug.LogWarning("Usuario no autorizado para seleccionar opción");
                return;
            }

            if (optionIndex < 0 || optionIndex >= currentNode.opciones.Count)
            {
                Debug.LogError($"Índice de opción inválido: {optionIndex}");
                return;
            }

            var selectedOption = currentNode.opciones[optionIndex];

            // Enviar decisión a Laravel
            SendDecisionToLaravel(selectedOption.id, selectedOption.texto);

            // Ocultar UI
            HideDialogueUI();
        }

        /// <summary>
        /// Enviar decisión a Laravel
        /// </summary>
        private void SendDecisionToLaravel(int optionId, string optionText)
        {
            if (laravelAPI != null && currentDialogueData != null)
            {
                // Obtener ID del usuario actual
                int userId = laravelAPI.currentUser?.id ?? 0;

                // Enviar decisión
                laravelAPI.EnviarDecision(
                    currentDialogueData.session_info.id,
                    userId,
                    optionId,
                    optionText,
                    Mathf.RoundToInt(Time.time)
                );

                Debug.Log($"Decisión enviada: {optionText}");
            }
        }

        #endregion

        #region Utilidades

        /// <summary>
        /// Verificar si el usuario actual tiene el rol correcto
        /// </summary>
        private bool IsCurrentUserRole()
        {
            if (laravelAPI?.currentSessionData?.role == null)
                return false;

            return currentRoleFlow != null &&
                   laravelAPI.currentSessionData.role.id == currentRoleFlow.id;
        }

        /// <summary>
        /// Obtener nombre del personaje
        /// </summary>
        private string GetCharacterName(DialogueNode dialogue)
        {
            // Por ahora usar el nombre del rol, después se puede personalizar
            return currentRoleFlow?.nombre ?? "Personaje";
        }

        /// <summary>
        /// Obtener color del rol
        /// </summary>
        private Color GetRoleColor(string colorHex)
        {
            if (ColorUtility.TryParseHtmlString(colorHex, out Color color))
            {
                return color;
            }
            return Color.white;
        }

        /// <summary>
        /// Ocultar UI del diálogo
        /// </summary>
        private void HideDialogueUI()
        {
            dialogueCanvas.gameObject.SetActive(false);
            isDialogueActive = false;
            isWaitingForUserInput = false;
        }

        /// <summary>
        /// Mostrar notificación de turno
        /// </summary>
        public void ShowRoleTurnNotification(string roleName, string action)
        {
            if (notificationPanel != null)
            {
                notificationText.text = $"Es el turno de {roleName}: {action}";
                notificationPanel.SetActive(true);

                // Auto-hide después de 3 segundos
                StartCoroutine(HideNotificationAfterDelay(3f));
            }
        }

        private IEnumerator HideNotificationAfterDelay(float delay)
        {
            yield return new WaitForSeconds(delay);
            if (notificationPanel != null)
            {
                notificationPanel.SetActive(false);
            }
        }

        #endregion

        #region Event Handlers

        private bool hasReceivedDialogue = false; // Flag para prevenir procesamiento múltiple

        private void OnDialogueDataReceived(DialogueData dialogueData)
        {
            // Prevenir procesamiento múltiple del mismo evento
            if (hasReceivedDialogue && currentDialogueData != null && 
                currentDialogueData.dialogue != null && dialogueData != null && 
                dialogueData.dialogue != null && 
                currentDialogueData.dialogue.id == dialogueData.dialogue.id)
            {
                Debug.LogWarning("[DialogueManager] Diálogo ya recibido y procesado. Ignorando evento duplicado.");
                return;
            }

            hasReceivedDialogue = true;
            SetupDialogueSystem(dialogueData);
        }

        private void HandleDialogueError(string error)
        {
            Debug.LogError($"Error en diálogo: {error}");
            // No invocar el evento aquí para evitar recursión
        }

        #endregion

        #region Métodos Públicos

        /// <summary>
        /// Iniciar secuencia de diálogos
        /// </summary>
        public void StartDialogueSequence()
        {
            if (currentRoleFlow?.flujos != null && currentRoleFlow.flujos.Count > 0)
            {
                var firstFlow = currentRoleFlow.flujos[0];
                if (firstFlow.dialogos != null && firstFlow.dialogos.Count > 0)
                {
                    var firstDialogue = firstFlow.dialogos[0];
                    ShowDialogue(firstDialogue);
                }
            }
        }

        /// <summary>
        /// Obtener estado del sistema
        /// </summary>
        public string GetDialogueStatus()
        {
            string status = "Estado del Sistema de Diálogos:\n";
            status += $"Diálogo activo: {isDialogueActive}\n";
            status += $"Esperando input: {isWaitingForUserInput}\n";
            status += $"Rol actual: {currentRoleFlow?.nombre ?? "Ninguno"}\n";
            status += $"Diálogo actual: {currentNode?.titulo ?? "Ninguno"}\n";

            return status;
        }

        #endregion

        #region Sistema de Avatares Automáticos

        /// <summary>
        /// Notificar que un jugador se unió a la sala
        /// </summary>
        public void OnPlayerJoined(string role)
        {
            playersInRoom[role] = true;

            // Si hay un avatar automático para este rol, removerlo
            if (autoAvatars.ContainsKey(role))
            {
                DestroyAutoAvatar(role);
            }

            Debug.Log($"Jugador con rol {role} se unió a la sala");
        }

        /// <summary>
        /// Notificar que un jugador se desconectó
        /// </summary>
        public void OnPlayerLeft(string role)
        {
            playersInRoom[role] = false;
            Debug.Log($"Jugador con rol {role} se desconectó");
        }

        /// <summary>
        /// Verificar si hay un jugador real para el rol actual
        /// </summary>
        private bool HasRealPlayerForRole(string role)
        {
            return playersInRoom.ContainsKey(role) && playersInRoom[role];
        }

        /// <summary>
        /// Crear avatar automático para un rol
        /// </summary>
        private void CreateAutoAvatar(string role)
        {
            if (autoAvatarPrefab == null)
            {
                Debug.LogWarning("No hay prefab de avatar automático configurado");
                return;
            }

            // Obtener posición de spawn
            Vector3 spawnPosition = GetAvatarSpawnPosition(role);

            // Crear avatar
            GameObject autoAvatar = Instantiate(autoAvatarPrefab, spawnPosition, Quaternion.identity);
            autoAvatars[role] = autoAvatar;

            // Configurar el avatar
            var avatarController = autoAvatar.GetComponent<AvatarController>();
            if (avatarController != null)
            {
                avatarController.SetRole(role, GetRoleColor(GetRoleColorHex(role)));
            }

            Debug.Log($"Avatar automático creado para rol: {role}");
        }

        /// <summary>
        /// Destruir avatar automático
        /// </summary>
        private void DestroyAutoAvatar(string role)
        {
            if (autoAvatars.ContainsKey(role))
            {
                Destroy(autoAvatars[role]);
                autoAvatars.Remove(role);
                Debug.Log($"Avatar automático removido para rol: {role}");
            }
        }

        /// <summary>
        /// Obtener posición de spawn para avatar
        /// </summary>
        private Vector3 GetAvatarSpawnPosition(string role)
        {
            if (avatarSpawnPoints != null && avatarSpawnPoints.Length > 0)
            {
                // Usar posición específica del rol
                switch (role.ToLower())
                {
                    case "juez":
                        return avatarSpawnPoints.Length > 0 ? avatarSpawnPoints[0].position : Vector3.zero;
                    case "fiscal":
                        return avatarSpawnPoints.Length > 1 ? avatarSpawnPoints[1].position : Vector3.left * 3;
                    case "defensor":
                        return avatarSpawnPoints.Length > 2 ? avatarSpawnPoints[2].position : Vector3.right * 3;
                    case "testigo":
                        return avatarSpawnPoints.Length > 3 ? avatarSpawnPoints[3].position : Vector3.forward * 3;
                    case "acusado":
                        return avatarSpawnPoints.Length > 4 ? avatarSpawnPoints[4].position : Vector3.back * 3;
                    default:
                        return avatarSpawnPoints[0].position;
                }
            }

            // Posiciones por defecto
            switch (role.ToLower())
            {
                case "juez":
                    return new Vector3(0, 1, 0);
                case "fiscal":
                    return new Vector3(-3, 1, 0);
                case "defensor":
                    return new Vector3(3, 1, 0);
                case "testigo":
                    return new Vector3(0, 1, 3);
                case "acusado":
                    return new Vector3(0, 1, -3);
                default:
                    return new Vector3(UnityEngine.Random.Range(-5, 5), 1, UnityEngine.Random.Range(-5, 5));
            }
        }

        /// <summary>
        /// Obtener color hexadecimal del rol
        /// </summary>
        private string GetRoleColorHex(string role)
        {
            switch (role.ToLower())
            {
                case "juez":
                    return "#FF0000";
                case "fiscal":
                    return "#0000FF";
                case "defensor":
                    return "#00FF00";
                case "testigo":
                    return "#FFFF00";
                case "acusado":
                    return "#FF00FF";
                default:
                    return "#FFFFFF";
            }
        }

        /// <summary>
        /// Iniciar sistema de avatar automático
        /// </summary>
        private void StartAutoPlayerSystem(string role)
        {
            if (autoPlayerCoroutine != null)
            {
                StopCoroutine(autoPlayerCoroutine);
            }

            autoPlayerCoroutine = StartCoroutine(AutoPlayerCoroutine(role));
        }

        /// <summary>
        /// Corrutina para manejar avatar automático
        /// </summary>
        private IEnumerator AutoPlayerCoroutine(string role)
        {
            Debug.Log($"Iniciando sistema de avatar automático para rol: {role}");

            // Esperar el tiempo configurado
            yield return new WaitForSeconds(autoPlayerDelay);

            // Verificar si ya hay un jugador real
            if (!HasRealPlayerForRole(role))
            {
                // Crear avatar automático
                CreateAutoAvatar(role);

                // Mostrar notificación
                ShowRoleTurnNotification(role, "Avatar automático activado");

                // Si es un diálogo con opciones, seleccionar automáticamente
                if (currentNode != null && currentNode.tipo == "decision")
                {
                    yield return new WaitForSeconds(autoOptionDelay);
                    SelectRandomOption();
                }
            }
        }

        /// <summary>
        /// Seleccionar opción aleatoria
        /// </summary>
        private void SelectRandomOption()
        {
            if (currentNode != null && currentNode.opciones != null && currentNode.opciones.Count > 0)
            {
                int randomIndex = UnityEngine.Random.Range(0, currentNode.opciones.Count);
                var selectedOption = currentNode.opciones[randomIndex];

                Debug.Log($"Avatar automático seleccionó opción: {selectedOption.texto}");

                // Enviar decisión automática
                SendDecisionToLaravel(selectedOption.id, selectedOption.texto);

                // Ocultar UI
                HideDialogueUI();
            }
        }

        /// <summary>
        /// Verificar si el diálogo puede iniciar
        /// </summary>
        public bool CanStartDialogue()
        {
            if (currentRoleFlow == null) return false;

            // Verificar si hay al menos un jugador real en la sala
            foreach (var player in playersInRoom)
            {
                if (player.Value) return true;
            }

            return false;
        }

        /// <summary>
        /// Iniciar diálogo con verificación de jugadores
        /// </summary>
        public void StartDialogueWithPlayerCheck()
        {
            if (!CanStartDialogue())
            {
                Debug.LogWarning("No hay jugadores en la sala. Esperando...");
                ShowRoleTurnNotification("Sistema", "Esperando jugadores...");
                return;
            }

            StartDialogueSequence();
        }

        #endregion

        private void OnDestroy()
        {
            // Desuscribirse de eventos
            if (laravelAPI != null)
            {
                LaravelAPI.OnDialogueDataReceived -= OnDialogueDataReceived;
                LaravelAPI.OnError -= HandleDialogueError;
            }

            // Limpiar avatares automáticos
            foreach (var avatar in autoAvatars.Values)
            {
                if (avatar != null)
                {
                    Destroy(avatar);
                }
            }
            autoAvatars.Clear();
        }
    }
}
