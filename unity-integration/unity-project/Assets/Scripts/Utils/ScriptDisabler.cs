using UnityEngine;
using System.Collections.Generic;
using JuiciosSimulator.API;
using JuiciosSimulator;
using JuiciosSimulator.Dialogue;
using JuiciosSimulator.Integration;
using JuiciosSimulator.Scene;
using JuiciosSimulator.Realtime;
using JuiciosSimulator.Session;

namespace JuiciosSimulator.Utils
{
    /// <summary>
    /// Script para deshabilitar temporalmente otros scripts durante el debugging
    /// </summary>
    public class ScriptDisabler : MonoBehaviour
    {
        [Header("Scripts a Deshabilitar")]
        public bool disableGameInitializer = false;
        public bool disableLaravelUnityEntryManager = false;
        public bool disableGestionRedJugador = false;
        public bool disableUnityLaravelIntegration = false;
        public bool disableDialogueManager = false;
        public bool disableDynamicSceneSetup = false;
        public bool disableRealtimeSyncManager = false;
        public bool disableSessionManager = false;
        public bool disableEnhancedScripts = true; // Deshabilitar scripts "Enhanced" por defecto

        [Header("Auto-Deshabilitar Múltiples Instancias")]
        public bool autoDisableDuplicates = true;

        private void Start()
        {
            if (autoDisableDuplicates)
            {
                DisableDuplicateInstances();
            }

            DisableScripts();
        }

        private void DisableDuplicateInstances()
        {
            // LaravelAPI
            var laravelAPIs = FindObjectsOfType<LaravelAPI>();
            if (laravelAPIs.Length > 1)
            {
                Debug.LogWarning($"[ScriptDisabler] Encontradas {laravelAPIs.Length} instancias de LaravelAPI. Deshabilitando duplicados...");
                for (int i = 1; i < laravelAPIs.Length; i++)
                {
                    laravelAPIs[i].enabled = false;
                    Debug.LogWarning($"[ScriptDisabler] LaravelAPI duplicado #{i} deshabilitado: {laravelAPIs[i].gameObject.name}");
                }
            }

            // GameInitializer
            var gameInitializers = FindObjectsOfType<GameInitializer>();
            if (gameInitializers.Length > 1)
            {
                Debug.LogWarning($"[ScriptDisabler] Encontradas {gameInitializers.Length} instancias de GameInitializer. Deshabilitando duplicados...");
                for (int i = 1; i < gameInitializers.Length; i++)
                {
                    gameInitializers[i].enabled = false;
                    Debug.LogWarning($"[ScriptDisabler] GameInitializer duplicado #{i} deshabilitado: {gameInitializers[i].gameObject.name}");
                }
            }

            // LaravelUnityEntryManager
            var entryManagers = FindObjectsOfType<LaravelUnityEntryManager>();
            if (entryManagers.Length > 1)
            {
                Debug.LogWarning($"[ScriptDisabler] Encontradas {entryManagers.Length} instancias de LaravelUnityEntryManager. Deshabilitando duplicados...");
                for (int i = 1; i < entryManagers.Length; i++)
                {
                    entryManagers[i].enabled = false;
                    Debug.LogWarning($"[ScriptDisabler] LaravelUnityEntryManager duplicado #{i} deshabilitado: {entryManagers[i].gameObject.name}");
                }
            }

            // DialogueManager
            var dialogueManagers = FindObjectsOfType<DialogueManager>();
            if (dialogueManagers.Length > 1)
            {
                Debug.LogWarning($"[ScriptDisabler] Encontradas {dialogueManagers.Length} instancias de DialogueManager. Deshabilitando duplicados...");
                for (int i = 1; i < dialogueManagers.Length; i++)
                {
                    dialogueManagers[i].enabled = false;
                    Debug.LogWarning($"[ScriptDisabler] DialogueManager duplicado #{i} deshabilitado: {dialogueManagers[i].gameObject.name}");
                }
            }
        }

        private void DisableScripts()
        {
            if (disableGameInitializer)
            {
                var scripts = FindObjectsOfType<GameInitializer>();
                foreach (var script in scripts)
                {
                    script.enabled = false;
                    Debug.Log($"[ScriptDisabler] GameInitializer deshabilitado: {script.gameObject.name}");
                }
            }

            if (disableLaravelUnityEntryManager)
            {
                var scripts = FindObjectsOfType<LaravelUnityEntryManager>();
                foreach (var script in scripts)
                {
                    script.enabled = false;
                    Debug.Log($"[ScriptDisabler] LaravelUnityEntryManager deshabilitado: {script.gameObject.name}");
                }
            }

            if (disableGestionRedJugador)
            {
                var scripts = FindObjectsOfType<GestionRedJugador>();
                foreach (var script in scripts)
                {
                    script.enabled = false;
                    Debug.Log($"[ScriptDisabler] GestionRedJugador deshabilitado: {script.gameObject.name}");
                }
            }

            if (disableUnityLaravelIntegration)
            {
                var scripts = FindObjectsOfType<UnityLaravelIntegration>();
                foreach (var script in scripts)
                {
                    script.enabled = false;
                    Debug.Log($"[ScriptDisabler] UnityLaravelIntegration deshabilitado: {script.gameObject.name}");
                }
            }

            if (disableDialogueManager)
            {
                var scripts = FindObjectsOfType<DialogueManager>();
                foreach (var script in scripts)
                {
                    script.enabled = false;
                    Debug.Log($"[ScriptDisabler] DialogueManager deshabilitado: {script.gameObject.name}");
                }
            }

            if (disableDynamicSceneSetup)
            {
                var scripts = FindObjectsOfType<DynamicSceneSetup>();
                foreach (var script in scripts)
                {
                    script.enabled = false;
                    Debug.Log($"[ScriptDisabler] DynamicSceneSetup deshabilitado: {script.gameObject.name}");
                }
            }

            if (disableRealtimeSyncManager)
            {
                var scripts = FindObjectsOfType<RealtimeSyncManager>();
                foreach (var script in scripts)
                {
                    script.enabled = false;
                    Debug.Log($"[ScriptDisabler] RealtimeSyncManager deshabilitado: {script.gameObject.name}");
                }
            }

            if (disableSessionManager)
            {
                var scripts = FindObjectsOfType<SessionManager>();
                foreach (var script in scripts)
                {
                    script.enabled = false;
                    Debug.Log($"[ScriptDisabler] SessionManager deshabilitado: {script.gameObject.name}");
                }
            }

            if (disableEnhancedScripts)
            {
                DisableEnhancedScripts();
            }
        }

        private void DisableEnhancedScripts()
        {
            // Buscar y deshabilitar scripts "Enhanced" que puedan causar conflictos
            var allMonoBehaviours = FindObjectsOfType<MonoBehaviour>();
            
            foreach (var mb in allMonoBehaviours)
            {
                string typeName = mb.GetType().Name;
                if (typeName.Contains("Enhanced") && 
                    (typeName.Contains("Initializer") || 
                     typeName.Contains("Manager") || 
                     typeName.Contains("Integration")))
                {
                    mb.enabled = false;
                    Debug.LogWarning($"[ScriptDisabler] Script Enhanced deshabilitado: {typeName} en {mb.gameObject.name}");
                }
            }
        }
    }
}

