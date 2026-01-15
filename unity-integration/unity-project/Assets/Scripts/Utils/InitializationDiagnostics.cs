using UnityEngine;
using System.Collections;
using System.Collections.Generic;
using System.Text;
using JuiciosSimulator.API;
using JuiciosSimulator;
using JuiciosSimulator.Dialogue;
using JuiciosSimulator.Integration;

namespace JuiciosSimulator.Utils
{
    /// <summary>
    /// Script de diagnóstico para identificar problemas de inicialización y recursión
    /// </summary>
    public class InitializationDiagnostics : MonoBehaviour
    {
        [Header("Configuración")]
        public bool enableDiagnostics = true;
        public bool logToHTML = true;

        private static InitializationDiagnostics Instance;
        private Dictionary<string, int> initializationCounts = new Dictionary<string, int>();
        private Dictionary<string, float> initializationTimes = new Dictionary<string, float>();
        private List<string> initializationOrder = new List<string>();

        private void Awake()
        {
            if (Instance == null)
            {
                Instance = this;
                DontDestroyOnLoad(gameObject);
                // NO hacer logging en Awake - esperar a que Unity esté cargado
            }
            else
            {
                Destroy(gameObject);
                return;
            }
        }

        private void Start()
        {
            if (!enableDiagnostics) return;

            // Retrasar el diagnóstico para evitar problemas con el runtime de Unity WebGL
            StartCoroutine(StartDiagnosticsDelayed());
        }

        private IEnumerator StartDiagnosticsDelayed()
        {
            // Esperar varios frames para que Unity WebGL esté completamente cargado
            yield return null; // Frame 1
            yield return null; // Frame 2
            yield return null; // Frame 3
            yield return new WaitForEndOfFrame(); // Frame completo
            
            // Ahora iniciar el diagnóstico
            LogDiagnostic("InitializationDiagnostics", "Start", "Diagnóstico inicializado");
            StartCoroutine(DiagnosticCoroutine());
        }

        private IEnumerator DiagnosticCoroutine()
        {
            // Esperar un frame para que otros scripts se inicialicen
            yield return null;
            yield return null;
            yield return null;

            // Diagnosticar scripts críticos
            DiagnoseScripts();
            
            // Esperar más y diagnosticar de nuevo
            yield return new WaitForSeconds(1f);
            DiagnoseScripts();
        }

        private void DiagnoseScripts()
        {
            LogDiagnostic("DIAGNOSTIC", "=== INICIANDO DIAGNÓSTICO ===", "");

            // Verificar LaravelAPI
            var laravelAPIs = FindObjectsOfType<LaravelAPI>();
            LogDiagnostic("LaravelAPI", $"Instancias encontradas: {laravelAPIs.Length}", 
                laravelAPIs.Length > 1 ? "⚠️ MÚLTIPLES INSTANCIAS DETECTADAS!" : "OK");

            if (laravelAPIs.Length > 0)
            {
                var api = laravelAPIs[0];
                LogDiagnostic("LaravelAPI", $"IsInitialized: {LaravelAPI.IsInitialized}", "");
                LogDiagnostic("LaravelAPI", $"Instance == null: {LaravelAPI.Instance == null}", "");
                LogDiagnostic("LaravelAPI", $"isGettingActiveSession: {GetPrivateField(api, "isGettingActiveSession")}", "");
                LogDiagnostic("LaravelAPI", $"isGettingSessionDialogue: {GetPrivateField(api, "isGettingSessionDialogue")}", "");
            }

            // Verificar GameInitializer
            var gameInitializers = FindObjectsOfType<GameInitializer>();
            LogDiagnostic("GameInitializer", $"Instancias encontradas: {gameInitializers.Length}", 
                gameInitializers.Length > 1 ? "⚠️ MÚLTIPLES INSTANCIAS DETECTADAS!" : "OK");

            // Verificar LaravelUnityEntryManager
            var entryManagers = FindObjectsOfType<LaravelUnityEntryManager>();
            LogDiagnostic("LaravelUnityEntryManager", $"Instancias encontradas: {entryManagers.Length}", 
                entryManagers.Length > 1 ? "⚠️ MÚLTIPLES INSTANCIAS DETECTADAS!" : "OK");

            // Verificar DialogueManager
            var dialogueManagers = FindObjectsOfType<DialogueManager>();
            LogDiagnostic("DialogueManager", $"Instancias encontradas: {dialogueManagers.Length}", 
                dialogueManagers.Length > 1 ? "⚠️ MÚLTIPLES INSTANCIAS DETECTADAS!" : "OK");

            // Verificar GestionRedJugador
            var gestionRed = FindObjectsOfType<GestionRedJugador>();
            LogDiagnostic("GestionRedJugador", $"Instancias encontradas: {gestionRed.Length}", 
                gestionRed.Length > 1 ? "⚠️ MÚLTIPLES INSTANCIAS DETECTADAS!" : "OK");

            // Verificar UnityLaravelIntegration
            var integrations = FindObjectsOfType<UnityLaravelIntegration>();
            LogDiagnostic("UnityLaravelIntegration", $"Instancias encontradas: {integrations.Length}", 
                integrations.Length > 1 ? "⚠️ MÚLTIPLES INSTANCIAS DETECTADAS!" : "OK");

            // Contar suscripciones a eventos usando reflexión
            int activeSessionSubscribers = GetEventSubscriberCount(typeof(LaravelAPI), "OnActiveSessionReceived");
            LogDiagnostic("EVENTOS", $"OnActiveSessionReceived suscriptores: {activeSessionSubscribers}", 
                activeSessionSubscribers > 5 ? "⚠️ DEMASIADAS SUSCRIPCIONES!" : "OK");

            int dialogueDataSubscribers = GetEventSubscriberCount(typeof(LaravelAPI), "OnDialogueDataReceived");
            LogDiagnostic("EVENTOS", $"OnDialogueDataReceived suscriptores: {dialogueDataSubscribers}", 
                dialogueDataSubscribers > 5 ? "⚠️ DEMASIADAS SUSCRIPCIONES!" : "OK");

            LogDiagnostic("DIAGNOSTIC", "=== FIN DIAGNÓSTICO ===", "");
        }

        public static void LogInitialization(string scriptName, string methodName)
        {
            if (Instance != null && Instance.enableDiagnostics)
            {
                Instance.LogDiagnostic(scriptName, methodName, "");
            }
        }

        private void LogDiagnostic(string scriptName, string methodName, string message)
        {
            string key = $"{scriptName}.{methodName}";
            
            if (!initializationCounts.ContainsKey(key))
            {
                initializationCounts[key] = 0;
            }
            initializationCounts[key]++;
            
            if (!initializationTimes.ContainsKey(key))
            {
                initializationTimes[key] = Time.time;
            }

            if (!initializationOrder.Contains(key))
            {
                initializationOrder.Add(key);
            }

            string logMessage = $"[DIAGNOSTIC] {scriptName}.{methodName}() - Llamado {initializationCounts[key]} veces";
            if (!string.IsNullOrEmpty(message))
            {
                logMessage += $" - {message}";
            }

            Debug.Log(logMessage);

            if (logToHTML && Application.platform == RuntimePlatform.WebGLPlayer)
            {
                try
                {
                    Application.ExternalCall("addDebugLog", "DIAGNOSTIC", logMessage, "info");
                }
                catch
                {
                    // Ignorar errores de ExternalCall
                }
            }

            // Alertar si se llama demasiadas veces
            if (initializationCounts[key] > 3)
            {
                string warning = $"⚠️ {scriptName}.{methodName}() llamado {initializationCounts[key]} veces - POSIBLE RECURSIÓN!";
                Debug.LogError(warning);
                if (logToHTML && Application.platform == RuntimePlatform.WebGLPlayer)
                {
                    try
                    {
                        Application.ExternalCall("addDebugLog", "DIAGNOSTIC", warning, "error");
                    }
                    catch { }
                }
            }
        }

        private object GetPrivateField(object obj, string fieldName)
        {
            try
            {
                var field = obj.GetType().GetField(fieldName, 
                    System.Reflection.BindingFlags.NonPublic | System.Reflection.BindingFlags.Instance);
                return field != null ? field.GetValue(obj) : "N/A";
            }
            catch
            {
                return "N/A";
            }
        }

        private int GetEventSubscriberCount(System.Type type, string eventName)
        {
            try
            {
                var field = type.GetField(eventName, 
                    System.Reflection.BindingFlags.Public | System.Reflection.BindingFlags.Static);
                
                if (field != null)
                {
                    var eventDelegate = field.GetValue(null) as System.Delegate;
                    if (eventDelegate != null)
                    {
                        return eventDelegate.GetInvocationList().Length;
                    }
                }
                return 0;
            }
            catch
            {
                return 0;
            }
        }

        private void OnGUI()
        {
            if (!enableDiagnostics) return;

            GUILayout.BeginArea(new Rect(10, 10, 400, 300));
            GUILayout.Box("DIAGNÓSTICO DE INICIALIZACIÓN");
            
            foreach (var kvp in initializationCounts)
            {
                if (kvp.Value > 1)
                {
                    GUILayout.Label($"{kvp.Key}: {kvp.Value} veces", 
                        kvp.Value > 3 ? GUI.skin.box : GUI.skin.label);
                }
            }
            
            GUILayout.EndArea();
        }
    }
}

