using System.Collections.Generic;
using UnityEngine;
using JuiciosSimulator.Characters;
using JuiciosSimulator.API;
using JuiciosSimulator.Dialogue;

namespace JuiciosSimulator.Scene
{
    /// <summary>
    /// Configurador específico para la escena del caso OXXO
    /// </summary>
    public class OXXOSceneSetup : MonoBehaviour
    {
        [Header("Configuración de Personajes OXXO")]
        public Transform juezPosition;
        public Transform fiscalPosition;
        public Transform defensorPosition;
        public Transform testigoPosition;
        public Transform acusadoPosition;

        [Header("Prefabs de Personajes")]
        public GameObject juezPrefab;
        public GameObject fiscalPrefab;
        public GameObject defensorPrefab;
        public GameObject testigoPrefab;
        public GameObject acusadoPrefab;

        [Header("Configuración de Diálogos")]
        public Transform dialogueSpawnPoint;
        public float dialogueHeight = 2.5f;

        [Header("Referencias")]
        public SceneDialogueConfigurator sceneConfigurator;
        public DialogueManager dialogueManager;

        // IDs de roles del diálogo OXXO
        private readonly Dictionary<string, int> roleIds = new Dictionary<string, int>
        {
            {"Juez", 1},
            {"Fiscal", 2},
            {"Defensor", 3},
            {"Testigo", 4},
            {"Acusado", 5}
        };

        // Colores de roles
        private readonly Dictionary<string, Color> roleColors = new Dictionary<string, Color>
        {
            {"Juez", new Color(0.8f, 0.2f, 0.2f)},      // Rojo oscuro
            {"Fiscal", new Color(0.2f, 0.2f, 0.8f)},    // Azul
            {"Defensor", new Color(0.2f, 0.8f, 0.2f)},  // Verde
            {"Testigo", new Color(0.8f, 0.8f, 0.2f)},   // Amarillo
            {"Acusado", new Color(0.5f, 0.5f, 0.5f)}    // Gris
        };

        private void Start()
        {
            SetupOXXOScene();
        }

        public void SetupOXXOScene()
        {
            Debug.Log("Configurando escena del caso OXXO...");

            // Crear personajes
            CreateCharacters();

            // Configurar sistema de diálogos
            ConfigureDialogueSystem();

            // Configurar escena
            ConfigureScene();

            Debug.Log("Escena OXXO configurada exitosamente");
        }

        private void CreateCharacters()
        {
            var characterSetups = new List<CharacterSetup>();

            // Crear Juez
            if (juezPrefab != null && juezPosition != null)
            {
                var juez = Instantiate(juezPrefab, juezPosition.position, juezPosition.rotation);
                juez.name = "Juez";

                var juezSetup = new CharacterSetup
                {
                    characterName = "Juez",
                    roleId = roleIds["Juez"],
                    roleColor = roleColors["Juez"],
                    roleIcon = "gavel",
                    characterObject = juez,
                    dialogueSpawnPoint = juezPosition
                };

                characterSetups.Add(juezSetup);
                Debug.Log("Juez creado");
            }

            // Crear Fiscal
            if (fiscalPrefab != null && fiscalPosition != null)
            {
                var fiscal = Instantiate(fiscalPrefab, fiscalPosition.position, fiscalPosition.rotation);
                fiscal.name = "Fiscal";

                var fiscalSetup = new CharacterSetup
                {
                    characterName = "Fiscal",
                    roleId = roleIds["Fiscal"],
                    roleColor = roleColors["Fiscal"],
                    roleIcon = "briefcase",
                    characterObject = fiscal,
                    dialogueSpawnPoint = fiscalPosition
                };

                characterSetups.Add(fiscalSetup);
                Debug.Log("Fiscal creado");
            }

            // Crear Defensor
            if (defensorPrefab != null && defensorPosition != null)
            {
                var defensor = Instantiate(defensorPrefab, defensorPosition.position, defensorPosition.rotation);
                defensor.name = "Defensor";

                var defensorSetup = new CharacterSetup
                {
                    characterName = "Defensor",
                    roleId = roleIds["Defensor"],
                    roleColor = roleColors["Defensor"],
                    roleIcon = "shield",
                    characterObject = defensor,
                    dialogueSpawnPoint = defensorPosition
                };

                characterSetups.Add(defensorSetup);
                Debug.Log("Defensor creado");
            }

            // Crear Testigo
            if (testigoPrefab != null && testigoPosition != null)
            {
                var testigo = Instantiate(testigoPrefab, testigoPosition.position, testigoPosition.rotation);
                testigo.name = "Testigo";

                var testigoSetup = new CharacterSetup
                {
                    characterName = "Testigo",
                    roleId = roleIds["Testigo"],
                    roleColor = roleColors["Testigo"],
                    roleIcon = "person",
                    characterObject = testigo,
                    dialogueSpawnPoint = testigoPosition
                };

                characterSetups.Add(testigoSetup);
                Debug.Log("Testigo creado");
            }

            // Crear Acusado
            if (acusadoPrefab != null && acusadoPosition != null)
            {
                var acusado = Instantiate(acusadoPrefab, acusadoPosition.position, acusadoPosition.rotation);
                acusado.name = "Acusado";

                var acusadoSetup = new CharacterSetup
                {
                    characterName = "Acusado",
                    roleId = roleIds["Acusado"],
                    roleColor = roleColors["Acusado"],
                    roleIcon = "person-badge",
                    characterObject = acusado,
                    dialogueSpawnPoint = acusadoPosition
                };

                characterSetups.Add(acusadoSetup);
                Debug.Log("Acusado creado");
            }

            // Configurar escena con los personajes
            if (sceneConfigurator != null)
            {
                sceneConfigurator.characters = characterSetups;
            }
        }

        private void ConfigureDialogueSystem()
        {
            if (dialogueManager == null)
            {
                dialogueManager = FindObjectOfType<DialogueManager>();
            }

            if (dialogueManager != null)
            {
                // Configurar parámetros específicos para OXXO
                dialogueManager.dialogueHeight = dialogueHeight;
                dialogueManager.dialogueDistance = 3f;

                if (dialogueSpawnPoint != null)
                {
                    dialogueManager.dialoguePosition = dialogueSpawnPoint;
                }

                Debug.Log("Sistema de diálogos configurado para OXXO");
            }
        }

        private void ConfigureScene()
        {
            // Configurar iluminación para el tribunal
            ConfigureLighting();

            // Configurar cámara
            ConfigureCamera();

            // Configurar ambiente
            ConfigureAmbience();
        }

        private void ConfigureLighting()
        {
            // Configurar iluminación del tribunal
            var mainLight = FindObjectOfType<Light>();
            if (mainLight != null)
            {
                mainLight.type = LightType.Directional;
                mainLight.intensity = 1.2f;
                mainLight.color = Color.white;
                mainLight.transform.rotation = Quaternion.Euler(45f, 45f, 0f);
            }

            Debug.Log("Iluminación configurada");
        }

        private void ConfigureCamera()
        {
            var mainCamera = Camera.main;
            if (mainCamera != null)
            {
                // Posicionar cámara para vista del tribunal
                mainCamera.transform.position = new Vector3(0f, 3f, -8f);
                mainCamera.transform.rotation = Quaternion.Euler(15f, 0f, 0f);

                // Configurar campo de visión
                mainCamera.fieldOfView = 60f;
            }

            Debug.Log("Cámara configurada");
        }

        private void ConfigureAmbience()
        {
            // Configurar ambiente del tribunal
            RenderSettings.ambientMode = UnityEngine.Rendering.AmbientMode.Trilight;
            RenderSettings.ambientSkyColor = new Color(0.8f, 0.8f, 1f);
            RenderSettings.ambientEquatorColor = new Color(0.6f, 0.6f, 0.8f);
            RenderSettings.ambientGroundColor = new Color(0.4f, 0.4f, 0.6f);

            Debug.Log("Ambiente configurado");
        }

        #region Métodos Públicos

        /// <summary>
        /// Obtener información de todos los personajes OXXO
        /// </summary>
        public Dictionary<string, JuiciosSimulator.Characters.CharacterInfo> GetOXXOCharacterInfo()
        {
            var characterInfos = new Dictionary<string, JuiciosSimulator.Characters.CharacterInfo>();

            if (sceneConfigurator != null)
            {
                var allInfos = sceneConfigurator.GetAllCharacterInfo();

                foreach (var info in allInfos)
                {
                    characterInfos[info.name] = info;
                }
            }

            return characterInfos;
        }

        /// <summary>
        /// Activar diálogo para un personaje específico
        /// </summary>
        public void ActivateDialogueForCharacter(string characterName)
        {
            if (roleIds.ContainsKey(characterName))
            {
                var roleId = roleIds[characterName];

                if (sceneConfigurator != null)
                {
                    sceneConfigurator.ActivateDialogueForCharacter(roleId);
                }
            }
            else
            {
                Debug.LogWarning($"Personaje no encontrado: {characterName}");
            }
        }

        /// <summary>
        /// Obtener estado de la escena
        /// </summary>
        public string GetSceneStatus()
        {
            string status = "Estado de la Escena OXXO:\n";
            status += $"Personajes creados: {GetOXXOCharacterInfo().Count}\n";
            status += $"Sistema de diálogos: {(dialogueManager != null ? "Activo" : "Inactivo")}\n";
            status += $"Configurador: {(sceneConfigurator != null ? "Activo" : "Inactivo")}\n";

            return status;
        }

        #endregion

        #region Debug

        private void OnDrawGizmos()
        {
            // Dibujar posiciones de personajes
            if (juezPosition != null)
            {
                Gizmos.color = roleColors["Juez"];
                Gizmos.DrawWireCube(juezPosition.position, Vector3.one * 0.5f);
            }

            if (fiscalPosition != null)
            {
                Gizmos.color = roleColors["Fiscal"];
                Gizmos.DrawWireCube(fiscalPosition.position, Vector3.one * 0.5f);
            }

            if (defensorPosition != null)
            {
                Gizmos.color = roleColors["Defensor"];
                Gizmos.DrawWireCube(defensorPosition.position, Vector3.one * 0.5f);
            }

            if (testigoPosition != null)
            {
                Gizmos.color = roleColors["Testigo"];
                Gizmos.DrawWireCube(testigoPosition.position, Vector3.one * 0.5f);
            }

            if (acusadoPosition != null)
            {
                Gizmos.color = roleColors["Acusado"];
                Gizmos.DrawWireCube(acusadoPosition.position, Vector3.one * 0.5f);
            }

            // Dibujar punto de spawn del diálogo
            if (dialogueSpawnPoint != null)
            {
                Gizmos.color = Color.blue;
                Gizmos.DrawWireCube(dialogueSpawnPoint.position, Vector3.one * 0.3f);
            }
        }

        #endregion
    }
}
