using UnityEngine;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine.SceneManagement;
using JuiciosSimulator.API;
using JuiciosSimulator.Utils;

namespace JuiciosSimulator.Editor
{
    /// <summary>
    /// Script de editor para configurar automáticamente la escena mínima de prueba
    /// </summary>
    public class SetupMinimalScene : EditorWindow
    {
        [MenuItem("Juicios Simulator/Setup Minimal Test Scene")]
        public static void ShowWindow()
        {
            GetWindow<SetupMinimalScene>("Setup Minimal Scene");
        }

        private void OnGUI()
        {
            GUILayout.Label("Configurar Escena Mínima de Prueba", EditorStyles.boldLabel);
            GUILayout.Space(10);

            GUILayout.Label("Este script configurará la escena MinimalTestScene:", EditorStyles.boldLabel);
            GUILayout.Space(5);
            
            bool includeDiagnostics = GUILayout.Toggle(true, "Incluir InitializationDiagnostics");
            
            GUILayout.Space(10);
            GUILayout.Label("Componentes a crear:");
            GUILayout.Label("• LaravelAPI");
            if (includeDiagnostics)
            {
                GUILayout.Label("• InitializationDiagnostics");
            }
            GUILayout.Space(10);

            if (GUILayout.Button("Configurar Escena Mínima", GUILayout.Height(30)))
            {
                SetupScene(includeDiagnostics);
            }

            GUILayout.Space(10);
            GUILayout.Label("Nota: Esto eliminará todos los GameObjects existentes", EditorStyles.helpBox);
        }

        private static bool includeDiagnosticsInScene = true;

        private static void SetupScene(bool includeDiagnostics = true)
        {
            includeDiagnosticsInScene = includeDiagnostics;

            // Cargar o crear la escena
            string scenePath = "Assets/Scenes/MinimalTestScene.unity";
            UnityEngine.SceneManagement.Scene scene = EditorSceneManager.OpenScene(scenePath, OpenSceneMode.Single);

            if (scene.IsValid())
            {
                Debug.Log("Escena MinimalTestScene cargada");
            }
            else
            {
                // Crear nueva escena si no existe
                scene = EditorSceneManager.NewScene(NewSceneSetup.DefaultGameObjects, NewSceneMode.Single);
                Debug.Log("Nueva escena creada");
            }

            // Eliminar todos los GameObjects existentes (excepto Main Camera y Directional Light si existen)
            GameObject[] allObjects = FindObjectsOfType<GameObject>();
            foreach (GameObject obj in allObjects)
            {
                // Mantener la cámara y la luz por si acaso, pero podemos eliminarlas también
                if (obj.name != "Main Camera" && obj.name != "Directional Light")
                {
                    DestroyImmediate(obj);
                }
            }

            // Crear GameObject "LaravelAPI"
            GameObject laravelAPIObj = new GameObject("LaravelAPI");
            LaravelAPI laravelAPI = laravelAPIObj.AddComponent<LaravelAPI>();
            
            // Configurar LaravelAPI
            SerializedObject laravelAPISerialized = new SerializedObject(laravelAPI);
            laravelAPISerialized.FindProperty("baseURL").stringValue = "http://localhost:8000/api";
            laravelAPISerialized.FindProperty("enableDebugLogging").boolValue = true;
            laravelAPISerialized.ApplyModifiedProperties();

            Debug.Log("✅ GameObject 'LaravelAPI' creado y configurado");

            GameObject selectedObj = laravelAPIObj;
            string componentsList = "• LaravelAPI";

            // Crear GameObject "InitializationDiagnostics" solo si se solicita
            if (includeDiagnostics)
            {
                GameObject diagnosticsObj = new GameObject("InitializationDiagnostics");
                InitializationDiagnostics diagnostics = diagnosticsObj.AddComponent<InitializationDiagnostics>();
                
                // Configurar InitializationDiagnostics
                SerializedObject diagnosticsSerialized = new SerializedObject(diagnostics);
                diagnosticsSerialized.FindProperty("enableDiagnostics").boolValue = true;
                diagnosticsSerialized.FindProperty("logToHTML").boolValue = true;
                diagnosticsSerialized.ApplyModifiedProperties();

                Debug.Log("✅ GameObject 'InitializationDiagnostics' creado y configurado");
                componentsList += "\n• InitializationDiagnostics";
            }

            // Guardar la escena
            EditorSceneManager.SaveScene(scene, scenePath);
            Debug.Log($"✅ Escena guardada en: {scenePath}");

            // Mostrar mensaje de éxito
            EditorUtility.DisplayDialog("Escena Configurada", 
                $"La escena mínima ha sido configurada exitosamente con:\n{componentsList}\n\n" +
                "La escena está lista para probar.", 
                "OK");

            // Seleccionar el primer GameObject en la jerarquía
            Selection.activeGameObject = selectedObj;
        }
    }
}


