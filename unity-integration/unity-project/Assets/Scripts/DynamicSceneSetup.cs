using System.Collections.Generic;
using UnityEngine;
using JuiciosSimulator.Characters;
using JuiciosSimulator.API;
using JuiciosSimulator.Dialogue;
using System.Linq;
using JuiciosSimulator.Utils;

namespace JuiciosSimulator.Scene
{
    /// <summary>
    /// Configurador dinámico de escena basado en datos de la base de datos
    /// Reemplaza OXXOSceneSetup para funcionar con cualquier diálogo/sesión
    /// </summary>
    public class DynamicSceneSetup : MonoBehaviour
    {
        [Header("Configuración de Posiciones")]
        [Tooltip("Posiciones de spawn para personajes (se asignan automáticamente según el orden de los roles)")]
        public List<Transform> characterPositions = new List<Transform>();

        [Header("Prefabs de Personajes")]
        [Tooltip("Prefab genérico para personajes (se usa para todos los roles)")]
        public GameObject genericCharacterPrefab;
        
        [Tooltip("Prefabs específicos por rol (opcional, si no se define usa el genérico)")]
        public List<RolePrefabMapping> rolePrefabMappings = new List<RolePrefabMapping>();

        [Header("Configuración de Diálogos")]
        public Transform dialogueSpawnPoint;
        public float dialogueHeight = 2.5f;
        public float dialogueDistance = 3f;

        [Header("Referencias")]
        public SceneDialogueConfigurator sceneConfigurator;
        public DialogueManager dialogueManager;

        [Header("Configuración")]
        [Tooltip("Si está activado, la escena se configurará automáticamente. Si está desactivado, debe llamarse SetupScene() manualmente.")]
        public bool autoSetup = true;

        // Flag para prevenir múltiples inicializaciones
        private bool isSetup = false;
        private bool isSubscribedToEvents = false; // Flag para prevenir múltiples suscripciones

        // Datos cargados desde la base de datos
        private DialogueData currentDialogueData;
        private SessionData currentSessionData;

        private void Start()
        {
            // Solo ejecutar si auto-setup está habilitado y no se ha configurado ya
            if (autoSetup && !isSetup)
            {
                // Esperar varios frames para asegurar que la escena esté completamente cargada
                StartCoroutine(InitializeAfterFrame());
            }
            else if (!autoSetup)
            {
                Debug.Log("[DynamicSceneSetup] Auto-setup deshabilitado. Llama a SetupScene() manualmente cuando los datos estén disponibles.");
            }
        }

        private System.Collections.IEnumerator InitializeAfterFrame()
        {
            // ESPERAR a que LaravelAPI esté completamente inicializado
            float timeout = 5f;
            float elapsed = 0f;
            
            while (!LaravelAPI.IsInitialized && elapsed < timeout)
            {
                yield return null;
                elapsed += Time.deltaTime;
            }

            // Esperar varios frames adicionales para que Unity termine de inicializar todos los scripts
            yield return new WaitForEndOfFrame();
            yield return new WaitForEndOfFrame();
            yield return new WaitForEndOfFrame();

            // Intentar obtener datos de LaravelAPI
            var laravelAPI = LaravelAPI.Instance;
            if (laravelAPI != null)
            {
                // Suscribirse a eventos para recibir datos cuando estén disponibles (solo una vez)
                if (!isSubscribedToEvents)
                {
                    LaravelAPI.OnDialogueDataReceived += OnDialogueDataReceived;
                    LaravelAPI.OnActiveSessionReceived += OnActiveSessionReceived;
                    isSubscribedToEvents = true;
                }

                // Esperar otro frame antes de verificar datos existentes
                yield return new WaitForEndOfFrame();

                // Si ya hay datos disponibles, configurar (pero solo una vez)
                if (!isSetup)
                {
                    if (laravelAPI.currentDialogueData != null && laravelAPI.currentSessionData != null)
                    {
                        // Tenemos ambos datos, configurar inmediatamente
                        SetupScene(laravelAPI.currentDialogueData, laravelAPI.currentSessionData);
                    }
                    else if (laravelAPI.currentDialogueData != null)
                    {
                        // Solo tenemos diálogo, guardar y esperar sesión
                        currentDialogueData = laravelAPI.currentDialogueData;
                    }
                    else if (laravelAPI.currentSessionData != null)
                    {
                        // Solo tenemos sesión, guardar y esperar diálogo
                        currentSessionData = laravelAPI.currentSessionData;
                    }
                }
            }
            else
            {
                Debug.LogWarning("[DynamicSceneSetup] LaravelAPI.Instance no está disponible. La escena se configurará cuando los datos estén disponibles.");
            }
        }

        /// <summary>
        /// Configurar escena con datos del diálogo
        /// </summary>
        public void SetupScene(DialogueData dialogueData, SessionData sessionData = null)
        {
            DebugLogger.LogMethodEntry("DynamicSceneSetup.SetupScene", isSetup);

            // Prevenir múltiples ejecuciones
            if (isSetup)
            {
                DebugLogger.LogWarning("DynamicSceneSetup", "La escena ya ha sido configurada. Ignorando llamada duplicada.");
                Debug.LogWarning("[DynamicSceneSetup] La escena ya ha sido configurada. Ignorando llamada duplicada.");
                return;
            }

            if (dialogueData == null || dialogueData.dialogue == null)
            {
                DebugLogger.LogError("DynamicSceneSetup", "DialogueData es null o inválido. No se puede configurar la escena.");
                Debug.LogError("[DynamicSceneSetup] DialogueData es null o inválido. No se puede configurar la escena.");
                return;
            }

            isSetup = true;
            currentDialogueData = dialogueData;
            currentSessionData = sessionData;

            DebugLogger.LogPhase("DynamicSceneSetup", $"Configurando escena para diálogo: {dialogueData.dialogue.nombre}", new {
                dialogueId = dialogueData.dialogue.id,
                dialogueName = dialogueData.dialogue.nombre,
                rolesCount = dialogueData.dialogue.roles?.Count ?? 0,
                hasSessionData = sessionData != null
            });
            Debug.Log($"Configurando escena dinámicamente para diálogo: {dialogueData.dialogue.nombre}");

            // Crear personajes basados en los roles del diálogo
            CreateCharactersFromDialogueData();

            // Configurar sistema de diálogos
            ConfigureDialogueSystem();

            // Configurar escena
            ConfigureScene();

            Debug.Log("Escena configurada exitosamente con datos dinámicos");
        }

        private int dialogueDataReceivedCount = 0; // Contador para detectar múltiples llamadas
        private int sessionDataReceivedCount = 0; // Contador para detectar múltiples llamadas

        private void OnDialogueDataReceived(DialogueData dialogueData)
        {
            // Prevenir procesamiento si ya está configurado
            if (isSetup)
            {
                Debug.LogWarning("[DynamicSceneSetup] Escena ya configurada. Ignorando OnDialogueDataReceived.");
                return;
            }

            // Validar datos
            if (dialogueData == null || dialogueData.dialogue == null)
            {
                Debug.LogError("[DynamicSceneSetup] DialogueData inválido recibido.");
                return;
            }

            // Prevenir procesamiento del mismo diálogo múltiples veces
            if (currentDialogueData != null && 
                currentDialogueData.dialogue != null && 
                dialogueData.dialogue.id == currentDialogueData.dialogue.id)
            {
                Debug.LogWarning($"[DynamicSceneSetup] Diálogo {dialogueData.dialogue.id} ya recibido. Ignorando evento duplicado.");
                return;
            }

            dialogueDataReceivedCount++;
            if (dialogueDataReceivedCount > 1)
            {
                Debug.LogWarning($"[DynamicSceneSetup] OnDialogueDataReceived llamado {dialogueDataReceivedCount} veces. Posible recursión.");
            }

            currentDialogueData = dialogueData;
            
            // Si ya tenemos datos de sesión, configurar inmediatamente
            if (currentSessionData != null)
            {
                SetupScene(dialogueData, currentSessionData);
            }
        }

        private void OnActiveSessionReceived(SessionData sessionData)
        {
            // Prevenir procesamiento si ya está configurado
            if (isSetup)
            {
                Debug.LogWarning("[DynamicSceneSetup] Escena ya configurada. Ignorando OnActiveSessionReceived.");
                return;
            }

            // Validar datos
            if (sessionData == null || sessionData.session == null)
            {
                Debug.LogError("[DynamicSceneSetup] SessionData inválido recibido.");
                return;
            }

            // Prevenir procesamiento de la misma sesión múltiples veces
            if (currentSessionData != null && 
                currentSessionData.session != null && 
                sessionData.session.id == currentSessionData.session.id)
            {
                Debug.LogWarning($"[DynamicSceneSetup] Sesión {sessionData.session.id} ya recibida. Ignorando evento duplicado.");
                return;
            }

            sessionDataReceivedCount++;
            if (sessionDataReceivedCount > 1)
            {
                Debug.LogWarning($"[DynamicSceneSetup] OnActiveSessionReceived llamado {sessionDataReceivedCount} veces. Posible recursión.");
            }

            currentSessionData = sessionData;
            
            // Si ya tenemos datos de diálogo, configurar inmediatamente
            if (currentDialogueData != null)
            {
                SetupScene(currentDialogueData, sessionData);
            }
        }

        /// <summary>
        /// Crear personajes basados en los roles del diálogo
        /// </summary>
        private void CreateCharactersFromDialogueData()
        {
            if (currentDialogueData == null || currentDialogueData.dialogue == null)
            {
                Debug.LogError("[DynamicSceneSetup] No hay datos de diálogo disponibles para crear personajes.");
                return;
            }

            var characterSetups = new List<CharacterSetup>();
            var roles = currentDialogueData.dialogue.roles;

            if (roles == null || roles.Count == 0)
            {
                Debug.LogWarning("[DynamicSceneSetup] No hay roles definidos en el diálogo.");
                return;
            }

            Debug.Log($"Creando {roles.Count} personajes basados en roles del diálogo...");

            for (int i = 0; i < roles.Count; i++)
            {
                var role = roles[i];
                
                // Obtener posición (usar la posición del índice o una posición por defecto)
                Transform position = GetCharacterPosition(i);

                // Obtener prefab para este rol
                GameObject prefab = GetPrefabForRole(role.id, role.nombre);

                if (prefab == null)
                {
                    Debug.LogWarning($"[DynamicSceneSetup] No hay prefab disponible para el rol {role.nombre}. Saltando...");
                    continue;
                }

                if (position == null)
                {
                    Debug.LogWarning($"[DynamicSceneSetup] No hay posición disponible para el rol {role.nombre}. Saltando...");
                    continue;
                }

                // Instanciar personaje
                var character = Instantiate(prefab, position.position, position.rotation);
                character.name = $"Character_{role.nombre}";

                // Convertir color de string a Color
                Color roleColor = ParseColor(role.color);

                var characterSetup = new CharacterSetup
                {
                    characterName = role.nombre,
                    roleId = role.id,
                    roleColor = roleColor,
                    roleIcon = role.icono ?? "person",
                    characterObject = character,
                    dialogueSpawnPoint = position
                };

                characterSetups.Add(characterSetup);
                Debug.Log($"Personaje creado: {role.nombre} (ID: {role.id})");
            }

            // Configurar escena con los personajes
            if (sceneConfigurator != null)
            {
                sceneConfigurator.characters = characterSetups;
                Debug.Log($"Personajes configurados en SceneDialogueConfigurator: {characterSetups.Count}");
            }
            else
            {
                Debug.LogWarning("[DynamicSceneSetup] SceneDialogueConfigurator no está asignado. Los personajes no se configurarán.");
            }
        }

        /// <summary>
        /// Obtener posición para un personaje según su índice
        /// </summary>
        private Transform GetCharacterPosition(int index)
        {
            if (characterPositions != null && index < characterPositions.Count && characterPositions[index] != null)
            {
                return characterPositions[index];
            }

            // Si no hay posiciones definidas, crear posiciones en círculo
            if (characterPositions == null || characterPositions.Count == 0)
            {
                Debug.LogWarning("[DynamicSceneSetup] No hay posiciones definidas. Creando posiciones por defecto...");
                CreateDefaultPositions();
                
                if (index < characterPositions.Count)
                {
                    return characterPositions[index];
                }
            }

            // Fallback: posición en el origen
            Debug.LogWarning($"[DynamicSceneSetup] No hay posición para índice {index}. Usando posición por defecto.");
            return transform;
        }

        /// <summary>
        /// Crear posiciones por defecto en círculo
        /// </summary>
        private void CreateDefaultPositions()
        {
            if (currentDialogueData == null || currentDialogueData.dialogue == null)
                return;

            int roleCount = currentDialogueData.dialogue.roles?.Count ?? 0;
            if (roleCount == 0)
                return;

            characterPositions = new List<Transform>();
            float radius = 3f;
            float angleStep = 360f / roleCount;

            for (int i = 0; i < roleCount; i++)
            {
                float angle = i * angleStep * Mathf.Deg2Rad;
                Vector3 position = new Vector3(
                    Mathf.Cos(angle) * radius,
                    0f,
                    Mathf.Sin(angle) * radius
                );

                GameObject positionObj = new GameObject($"Position_{i}");
                positionObj.transform.SetParent(transform);
                positionObj.transform.position = position;
                characterPositions.Add(positionObj.transform);
            }
        }

        /// <summary>
        /// Obtener prefab para un rol específico
        /// </summary>
        private GameObject GetPrefabForRole(int roleId, string roleName)
        {
            // Buscar prefab específico en los mapeos
            var mapping = rolePrefabMappings?.FirstOrDefault(m => m.roleId == roleId || m.roleName == roleName);
            if (mapping != null && mapping.prefab != null)
            {
                return mapping.prefab;
            }

            // Usar prefab genérico
            if (genericCharacterPrefab != null)
            {
                return genericCharacterPrefab;
            }

            Debug.LogError($"[DynamicSceneSetup] No hay prefab disponible para el rol {roleName} (ID: {roleId}).");
            return null;
        }

        /// <summary>
        /// Convertir string de color a Color de Unity
        /// </summary>
        private Color ParseColor(string colorString)
        {
            if (string.IsNullOrEmpty(colorString))
                return Color.white;

            // Intentar parsear como hex (#RRGGBB o #RRGGBBAA)
            if (colorString.StartsWith("#"))
            {
                colorString = colorString.Substring(1);
            }

            if (colorString.Length == 6 || colorString.Length == 8)
            {
                if (ColorUtility.TryParseHtmlString($"#{colorString}", out Color color))
                {
                    return color;
                }
            }

            // Fallback: colores predefinidos por nombre
            switch (colorString.ToLower())
            {
                case "rojo": case "red": return Color.red;
                case "azul": case "blue": return Color.blue;
                case "verde": case "green": return Color.green;
                case "amarillo": case "yellow": return Color.yellow;
                case "naranja": case "orange": return new Color(1f, 0.5f, 0f);
                default: return Color.white;
            }
        }

        private bool isConfiguringDialogue = false; // Flag para prevenir recursión

        private void ConfigureDialogueSystem()
        {
            // Prevenir recursión
            if (isConfiguringDialogue)
            {
                Debug.LogWarning("[DynamicSceneSetup] ConfigureDialogueSystem ya está en progreso. Ignorando llamada duplicada.");
                return;
            }

            isConfiguringDialogue = true;

            try
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

                    // Configurar el sistema con los datos del diálogo (solo si hay datos nuevos)
                    if (currentDialogueData != null)
                    {
                        dialogueManager.SetupDialogueSystem(currentDialogueData);
                    }

                    Debug.Log("Sistema de diálogos configurado dinámicamente");
                }
            }
            finally
            {
                isConfiguringDialogue = false;
            }
        }

        private void ConfigureScene()
        {
            // Configurar iluminación
            ConfigureLighting();

            // Configurar cámara
            ConfigureCamera();

            // Configurar ambiente
            ConfigureAmbience();
        }

        private void ConfigureLighting()
        {
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
                // Posicionar cámara para vista general
                mainCamera.transform.position = new Vector3(0f, 3f, -8f);
                mainCamera.transform.rotation = Quaternion.Euler(15f, 0f, 0f);
                mainCamera.fieldOfView = 60f;
            }

            Debug.Log("Cámara configurada");
        }

        private void ConfigureAmbience()
        {
            RenderSettings.ambientMode = UnityEngine.Rendering.AmbientMode.Trilight;
            RenderSettings.ambientSkyColor = new Color(0.8f, 0.8f, 1f);
            RenderSettings.ambientEquatorColor = new Color(0.6f, 0.6f, 0.8f);
            RenderSettings.ambientGroundColor = new Color(0.4f, 0.4f, 0.6f);

            Debug.Log("Ambiente configurado");
        }

        #region Métodos Públicos

        /// <summary>
        /// Obtener información de todos los personajes creados dinámicamente
        /// </summary>
        public Dictionary<string, JuiciosSimulator.Characters.CharacterInfo> GetCharacterInfo()
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
        /// Obtener estado de la escena
        /// </summary>
        public string GetSceneStatus()
        {
            string status = "Estado de la Escena Dinámica:\n";
            status += $"Personajes creados: {GetCharacterInfo().Count}\n";
            status += $"Sistema de diálogos: {(dialogueManager != null ? "Activo" : "Inactivo")}\n";
            status += $"Configurador: {(sceneConfigurator != null ? "Activo" : "Inactivo")}\n";
            
            if (currentDialogueData != null)
            {
                status += $"Diálogo: {currentDialogueData.dialogue?.nombre ?? "N/A"}\n";
                status += $"Roles: {currentDialogueData.dialogue?.roles?.Count ?? 0}\n";
            }

            return status;
        }

        #endregion

        #region Debug

        private void OnDrawGizmos()
        {
            // Dibujar posiciones de personajes
            if (characterPositions != null)
            {
                for (int i = 0; i < characterPositions.Count; i++)
                {
                    if (characterPositions[i] != null)
                    {
                        Gizmos.color = Color.cyan;
                        Gizmos.DrawWireCube(characterPositions[i].position, Vector3.one * 0.5f);
                    }
                }
            }

            // Dibujar punto de spawn del diálogo
            if (dialogueSpawnPoint != null)
            {
                Gizmos.color = Color.blue;
                Gizmos.DrawWireCube(dialogueSpawnPoint.position, Vector3.one * 0.3f);
            }
        }

        #endregion

        private void OnDestroy()
        {
            // Desuscribirse de eventos solo si estaban suscritos
            if (isSubscribedToEvents)
            {
                LaravelAPI.OnDialogueDataReceived -= OnDialogueDataReceived;
                LaravelAPI.OnActiveSessionReceived -= OnActiveSessionReceived;
                isSubscribedToEvents = false;
            }
        }
    }

    /// <summary>
    /// Mapeo de prefab por rol (opcional)
    /// </summary>
    [System.Serializable]
    public class RolePrefabMapping
    {
        public int roleId;
        public string roleName;
        public GameObject prefab;
    }
}

