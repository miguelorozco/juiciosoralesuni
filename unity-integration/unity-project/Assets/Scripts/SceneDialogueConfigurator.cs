using System.Collections.Generic;
using UnityEngine;
using JuiciosSimulator.Characters;
using JuiciosSimulator.API;
using JuiciosSimulator.Dialogue;
using JuiciosSimulator.UI;

namespace JuiciosSimulator.Scene
{
    /// <summary>
    /// Configurador de escena para el sistema de diálogos
    /// </summary>
    public class SceneDialogueConfigurator : MonoBehaviour
    {
        [Header("Configuración de Personajes")]
        public List<CharacterSetup> characters = new List<CharacterSetup>();

        [Header("Configuración de Diálogos")]
        public Transform dialogueSpawnPoint;
        public float dialogueHeight = 2f;
        public float dialogueDistance = 3f;

        [Header("Configuración Visual")]
        public Material currentUserMaterial;
        public Material otherUserMaterial;
        public GameObject roleIndicatorPrefab;
        public GameObject interactionPromptPrefab;

        [Header("Referencias")]
        public DialogueManager dialogueManager;
        public SessionInfoUI sessionInfoUI;

        private void Start()
        {
            ConfigureScene();
        }

        private void ConfigureScene()
        {
            Debug.Log("Configurando escena para sistema de diálogos...");

            // Configurar personajes
            SetupCharacters();

            // Configurar sistema de diálogos
            SetupDialogueSystem();

            // Configurar UI
            SetupUI();

            Debug.Log("Escena configurada exitosamente");
        }

        private void SetupCharacters()
        {
            foreach (var characterSetup in characters)
            {
                if (characterSetup.characterObject != null)
                {
                    // Agregar DialogueTrigger si no existe
                    var dialogueTrigger = characterSetup.characterObject.GetComponent<DialogueTrigger>();
                    if (dialogueTrigger == null)
                    {
                        dialogueTrigger = characterSetup.characterObject.AddComponent<DialogueTrigger>();
                    }

                    // Configurar DialogueTrigger
                    ConfigureDialogueTrigger(dialogueTrigger, characterSetup);

                    Debug.Log($"Personaje configurado: {characterSetup.characterName}");
                }
            }
        }

        private void ConfigureDialogueTrigger(DialogueTrigger trigger, CharacterSetup setup)
        {
            // Configurar propiedades básicas
            trigger.characterName = setup.characterName;
            trigger.roleId = setup.roleId;
            trigger.roleColor = setup.roleColor;
            trigger.roleIcon = setup.roleIcon;

            // Configurar materiales
            trigger.currentUserMaterial = currentUserMaterial;
            trigger.otherUserMaterial = otherUserMaterial;

            // Configurar prefabs
            trigger.roleIndicator = roleIndicatorPrefab;
            trigger.interactionPrompt = interactionPromptPrefab;

            // Configurar punto de spawn del diálogo
            if (setup.dialogueSpawnPoint != null)
            {
                trigger.dialogueSpawnPoint = setup.dialogueSpawnPoint;
            }
            else
            {
                trigger.dialogueSpawnPoint = setup.characterObject.transform;
            }
        }

        private void SetupDialogueSystem()
        {
            if (dialogueManager == null)
            {
                dialogueManager = FindObjectOfType<DialogueManager>();
            }

            if (dialogueManager != null)
            {
                // Configurar parámetros del diálogo
                dialogueManager.dialogueHeight = dialogueHeight;
                dialogueManager.dialogueDistance = dialogueDistance;

                if (dialogueSpawnPoint != null)
                {
                    dialogueManager.dialoguePosition = dialogueSpawnPoint;
                }

                Debug.Log("Sistema de diálogos configurado");
            }
        }

        private void SetupUI()
        {
            if (sessionInfoUI == null)
            {
                sessionInfoUI = FindObjectOfType<SessionInfoUI>();
            }

            if (sessionInfoUI != null)
            {
                Debug.Log("UI de sesión configurada");
            }
        }

        #region Métodos Públicos

        /// <summary>
        /// Obtener información de todos los personajes
        /// </summary>
        public List<JuiciosSimulator.Characters.CharacterInfo> GetAllCharacterInfo()
        {
            var characterInfos = new List<JuiciosSimulator.Characters.CharacterInfo>();

            foreach (var characterSetup in characters)
            {
                if (characterSetup.characterObject != null)
                {
                    var trigger = characterSetup.characterObject.GetComponent<DialogueTrigger>();
                    if (trigger != null)
                    {
                        characterInfos.Add(trigger.GetCharacterInfo());
                    }
                }
            }

            return characterInfos;
        }

        /// <summary>
        /// Obtener personaje por ID de rol
        /// </summary>
        public DialogueTrigger GetCharacterByRoleId(int roleId)
        {
            foreach (var characterSetup in characters)
            {
                if (characterSetup.characterObject != null && characterSetup.roleId == roleId)
                {
                    return characterSetup.characterObject.GetComponent<DialogueTrigger>();
                }
            }

            return null;
        }

        /// <summary>
        /// Activar diálogo para un personaje específico
        /// </summary>
        public void ActivateDialogueForCharacter(int roleId)
        {
            var character = GetCharacterByRoleId(roleId);
            if (character != null)
            {
                character.ActivateDialogue();
            }
            else
            {
                Debug.LogWarning($"No se encontró personaje con rol ID: {roleId}");
            }
        }

        /// <summary>
        /// Actualizar estado de todos los personajes
        /// </summary>
        public void UpdateAllCharacterStates()
        {
            foreach (var characterSetup in characters)
            {
                if (characterSetup.characterObject != null)
                {
                    var trigger = characterSetup.characterObject.GetComponent<DialogueTrigger>();
                    if (trigger != null)
                    {
                        trigger.UpdateRoleStatus();
                    }
                }
            }
        }

        #endregion

        #region Debug

        private void OnDrawGizmos()
        {
            // Dibujar puntos de spawn de diálogo
            if (dialogueSpawnPoint != null)
            {
                Gizmos.color = Color.blue;
                Gizmos.DrawWireCube(dialogueSpawnPoint.position, Vector3.one * 0.5f);
            }

            // Dibujar información de personajes
            foreach (var characterSetup in characters)
            {
                if (characterSetup.characterObject != null)
                {
                    Gizmos.color = characterSetup.roleColor;
                    Gizmos.DrawWireSphere(characterSetup.characterObject.transform.position, 1f);
                }
            }
        }

        #endregion
    }

    /// <summary>
    /// Configuración de un personaje
    /// </summary>
    [System.Serializable]
    public class CharacterSetup
    {
        [Header("Información del Personaje")]
        public string characterName;
        public int roleId;
        public Color roleColor = Color.white;
        public string roleIcon = "person";

        [Header("Referencias")]
        public GameObject characterObject;
        public Transform dialogueSpawnPoint;

        [Header("Configuración Visual")]
        public bool showRoleIndicator = true;
        public bool showInteractionPrompt = true;
    }
}
