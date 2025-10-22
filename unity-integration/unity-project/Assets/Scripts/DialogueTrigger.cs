using System.Collections;
using UnityEngine;
using JuiciosSimulator.API;
using JuiciosSimulator.Dialogue;

namespace JuiciosSimulator.Characters
{
    /// <summary>
    /// Trigger para manejar la interacción de diálogos en personajes
    /// </summary>
    public class DialogueTrigger : MonoBehaviour
    {
        [Header("Configuración del Personaje")]
        public string characterName;
        public int roleId;
        public Color roleColor = Color.white;
        public string roleIcon = "person";

        [Header("Posicionamiento")]
        public Transform dialogueSpawnPoint;
        public float interactionRadius = 2f;

        [Header("Configuración Visual")]
        public GameObject roleIndicator;
        public GameObject interactionPrompt;
        public Material currentUserMaterial;
        public Material otherUserMaterial;

        [Header("Referencias")]
        public Renderer characterRenderer;
        public Animator characterAnimator;

        // Estado
        private bool isCurrentUserRole;
        private bool isInteractable;
        private bool isDialogueActive;

        // Referencias
        private LaravelAPI laravelAPI;
        private DialogueManager dialogueManager;

        private void Start()
        {
            InitializeCharacter();
        }

        private void InitializeCharacter()
        {
            // Obtener referencias
            laravelAPI = LaravelAPI.Instance;
            dialogueManager = DialogueManager.Instance;

            // Verificar si este es el rol del usuario actual
            CheckUserRole();

            // Configurar indicadores visuales
            SetupVisualIndicators();

            // Configurar animaciones
            SetupAnimations();

            Debug.Log($"Personaje inicializado: {characterName} (Rol ID: {roleId})");
        }

        private void CheckUserRole()
        {
            if (laravelAPI?.currentSessionData?.role != null)
            {
                isCurrentUserRole = laravelAPI.currentSessionData.role.id == roleId;
                Debug.Log($"Rol del usuario: {isCurrentUserRole} para {characterName}");
            }
            else
            {
                isCurrentUserRole = false;
                Debug.LogWarning("No hay datos de sesión disponibles");
            }
        }

        private void SetupVisualIndicators()
        {
            // Configurar indicador de rol
            if (roleIndicator != null)
            {
                roleIndicator.SetActive(true);

                // Cambiar color según si es el usuario actual
                var indicatorRenderer = roleIndicator.GetComponent<Renderer>();
                if (indicatorRenderer != null)
                {
                    indicatorRenderer.material = isCurrentUserRole ? currentUserMaterial : otherUserMaterial;
                }
            }

            // Configurar prompt de interacción
            if (interactionPrompt != null)
            {
                interactionPrompt.SetActive(false);
            }

            // Configurar material del personaje
            if (characterRenderer != null)
            {
                // Aplicar material según el rol
                var materials = characterRenderer.materials;
                if (materials.Length > 0)
                {
                    materials[0] = isCurrentUserRole ? currentUserMaterial : otherUserMaterial;
                    characterRenderer.materials = materials;
                }
            }
        }

        private void SetupAnimations()
        {
            if (characterAnimator != null)
            {
                // Configurar animaciones según el rol
                if (isCurrentUserRole)
                {
                    characterAnimator.SetBool("IsCurrentUser", true);
                }
                else
                {
                    characterAnimator.SetBool("IsCurrentUser", false);
                }
            }
        }

        #region Interacción

        /// <summary>
        /// Activar diálogo para este personaje
        /// </summary>
        public void ActivateDialogue()
        {
            if (dialogueManager == null)
            {
                Debug.LogError("DialogueManager no encontrado");
                return;
            }

            // Configurar posición del diálogo
            if (dialogueSpawnPoint != null)
            {
                dialogueManager.dialoguePosition = dialogueSpawnPoint;
            }
            else
            {
                dialogueManager.dialoguePosition = transform;
            }

            // Mostrar notificación si no es el usuario actual
            if (!isCurrentUserRole)
            {
                dialogueManager.ShowRoleTurnNotification(characterName, "Es su turno de hablar");
            }

            // Iniciar secuencia de diálogos
            dialogueManager.StartDialogueSequence();

            isDialogueActive = true;

            // Reproducir animación de hablar
            if (characterAnimator != null)
            {
                characterAnimator.SetTrigger("StartTalking");
            }

            Debug.Log($"Diálogo activado para {characterName}");
        }

        /// <summary>
        /// Desactivar diálogo
        /// </summary>
        public void DeactivateDialogue()
        {
            isDialogueActive = false;

            // Reproducir animación de parar de hablar
            if (characterAnimator != null)
            {
                characterAnimator.SetTrigger("StopTalking");
            }

            Debug.Log($"Diálogo desactivado para {characterName}");
        }

        #endregion

        #region Detección de Proximidad

        private void OnTriggerEnter(Collider other)
        {
            if (other.CompareTag("Player"))
            {
                ShowInteractionPrompt();
            }
        }

        private void OnTriggerExit(Collider other)
        {
            if (other.CompareTag("Player"))
            {
                HideInteractionPrompt();
            }
        }

        private void ShowInteractionPrompt()
        {
            if (interactionPrompt != null)
            {
                interactionPrompt.SetActive(true);
            }

            isInteractable = true;
        }

        private void HideInteractionPrompt()
        {
            if (interactionPrompt != null)
            {
                interactionPrompt.SetActive(false);
            }

            isInteractable = false;
        }

        #endregion

        #region Métodos Públicos

        /// <summary>
        /// Verificar si este es el rol del usuario actual
        /// </summary>
        public bool IsCurrentUserRole()
        {
            return isCurrentUserRole;
        }

        /// <summary>
        /// Obtener información del personaje
        /// </summary>
        public CharacterInfo GetCharacterInfo()
        {
            return new CharacterInfo
            {
                name = characterName,
                roleId = roleId,
                roleColor = roleColor,
                roleIcon = roleIcon,
                isCurrentUser = isCurrentUserRole,
                isInteractable = isInteractable,
                isDialogueActive = isDialogueActive
            };
        }

        /// <summary>
        /// Actualizar estado del rol
        /// </summary>
        public void UpdateRoleStatus()
        {
            CheckUserRole();
            SetupVisualIndicators();
            SetupAnimations();
        }

        #endregion

        #region Debug

        private void OnDrawGizmosSelected()
        {
            // Dibujar radio de interacción
            Gizmos.color = isCurrentUserRole ? Color.green : Color.yellow;
            Gizmos.DrawWireSphere(transform.position, interactionRadius);

            // Dibujar punto de spawn del diálogo
            if (dialogueSpawnPoint != null)
            {
                Gizmos.color = Color.blue;
                Gizmos.DrawWireCube(dialogueSpawnPoint.position, Vector3.one * 0.5f);
            }
        }

        #endregion
    }

    /// <summary>
    /// Estructura de información del personaje
    /// </summary>
    [System.Serializable]
    public class CharacterInfo
    {
        public string name;
        public int roleId;
        public Color roleColor;
        public string roleIcon;
        public bool isCurrentUser;
        public bool isInteractable;
        public bool isDialogueActive;
    }
}
