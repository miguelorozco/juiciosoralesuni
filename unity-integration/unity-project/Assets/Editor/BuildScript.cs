using UnityEngine;
using UnityEditor;
using UnityEditor.Build.Reporting;
using System.IO;
using System.Linq;

/// <summary>
/// Script de build para Unity WebGL
/// Se ejecuta desde la línea de comandos con: -executeMethod BuildScript.BuildWebGL -buildPath "ruta"
/// </summary>
public class BuildScript
{
    /// <summary>
    /// Construye el proyecto Unity para WebGL
    /// Unity no puede pasar parámetros directamente a métodos estáticos desde la línea de comandos,
    /// así que leemos el parámetro -buildPath desde los argumentos de la línea de comandos
    /// </summary>
    public static void BuildWebGL()
    {
        // Leer el parámetro -buildPath desde los argumentos de la línea de comandos
        string buildPath = null;
        string[] args = System.Environment.GetCommandLineArgs();
        
        for (int i = 0; i < args.Length; i++)
        {
            if (args[i] == "-buildPath" && i + 1 < args.Length)
            {
                buildPath = args[i + 1];
                break;
            }
        }
        
        // Si no se proporciona una ruta, usar una por defecto
        if (string.IsNullOrEmpty(buildPath))
        {
            buildPath = Path.Combine(Application.dataPath, "..", "build");
        }

        // Normalizar la ruta (convertir rutas relativas a absolutas)
        buildPath = Path.GetFullPath(buildPath);

        Debug.Log($"[BuildScript] Iniciando build WebGL...");
        Debug.Log($"[BuildScript] Ruta de build: {buildPath}");

        // Obtener escenas habilitadas
        string[] enabledScenes = GetEnabledScenes();
        
        if (enabledScenes.Length == 0)
        {
            Debug.LogError("[BuildScript] ERROR: No hay escenas habilitadas en Build Settings!");
            Debug.LogError("[BuildScript] Por favor, habilita al menos una escena en File > Build Settings");
            EditorApplication.Exit(1);
            return;
        }

        Debug.Log($"[BuildScript] Escenas a compilar: {enabledScenes.Length}");
        foreach (string scene in enabledScenes)
        {
            Debug.Log($"[BuildScript]   - {scene}");
        }

        // Crear directorio si no existe
        if (!Directory.Exists(buildPath))
        {
            Directory.CreateDirectory(buildPath);
            Debug.Log($"[BuildScript] Directorio creado: {buildPath}");
        }

        // Configurar opciones de build
        BuildPlayerOptions buildPlayerOptions = new BuildPlayerOptions
        {
            scenes = enabledScenes,
            locationPathName = buildPath,
            target = BuildTarget.WebGL,
            options = BuildOptions.None
        };

        // Ejecutar build
        Debug.Log($"[BuildScript] Compilando proyecto...");
        BuildReport report = BuildPipeline.BuildPlayer(buildPlayerOptions);
        BuildSummary summary = report.summary;

        // Verificar resultado
        if (summary.result == BuildResult.Succeeded)
        {
            Debug.Log($"[BuildScript] Build exitoso!");
            Debug.Log($"[BuildScript] Tamano total: {summary.totalSize / 1024 / 1024} MB");
            Debug.Log($"[BuildScript] Tiempo de build: {summary.totalTime.TotalSeconds:F2} segundos");
            Debug.Log($"[BuildScript] Archivos en: {buildPath}");
            
            // Copiar archivos a storage/unity-build/ para Laravel
            CopyToLaravelStorage(buildPath);
        }
        else if (summary.result == BuildResult.Failed)
        {
            Debug.LogError($"[BuildScript] Build fallo!");
            Debug.LogError($"[BuildScript] Revisa el log para mas detalles");
            EditorApplication.Exit(1);
        }
        else
        {
            Debug.LogWarning($"[BuildScript] Build cancelado o con advertencias");
        }
    }

    /// <summary>
    /// Obtiene todas las escenas habilitadas en Build Settings
    /// </summary>
    private static string[] GetEnabledScenes()
    {
        var scenes = new System.Collections.Generic.List<string>();
        foreach (EditorBuildSettingsScene scene in EditorBuildSettings.scenes)
        {
            if (scene.enabled)
            {
                scenes.Add(scene.path);
            }
        }
        return scenes.ToArray();
    }

    /// <summary>
    /// Copia los archivos del build a storage/unity-build/ para que Laravel los sirva
    /// </summary>
    private static void CopyToLaravelStorage(string buildPath)
    {
        try
        {
            // Ruta de destino en Laravel (ajustar según tu estructura de proyecto)
            // Asumiendo que el proyecto Laravel está en el mismo nivel que unity-integration
            string laravelStoragePath = Path.Combine(Application.dataPath, "..", "..", "..", "storage", "unity-build");
            
            // Normalizar la ruta
            laravelStoragePath = Path.GetFullPath(laravelStoragePath);
            
            Debug.Log($"[BuildScript] Copiando archivos a Laravel storage: {laravelStoragePath}");
            
            // Crear directorio si no existe
            if (!Directory.Exists(laravelStoragePath))
            {
                Directory.CreateDirectory(laravelStoragePath);
                Debug.Log($"[BuildScript] Directorio creado: {laravelStoragePath}");
            }
            
            // Copiar todos los archivos del build
            CopyDirectory(buildPath, laravelStoragePath, true);
            
            Debug.Log($"[BuildScript] ✅ Archivos copiados exitosamente a {laravelStoragePath}");
            Debug.Log($"[BuildScript] 📋 El sistema de logging ya está incluido en el index.html del template");
        }
        catch (System.Exception e)
        {
            Debug.LogWarning($"[BuildScript] ⚠️ No se pudo copiar a Laravel storage: {e.Message}");
            Debug.LogWarning($"[BuildScript] 💡 Copia manualmente los archivos de {buildPath} a storage/unity-build/");
        }
    }

    /// <summary>
    /// Copia un directorio completo recursivamente
    /// </summary>
    private static void CopyDirectory(string sourceDir, string destDir, bool overwrite = true)
    {
        DirectoryInfo dir = new DirectoryInfo(sourceDir);
        
        if (!dir.Exists)
        {
            throw new DirectoryNotFoundException($"Directorio fuente no existe: {sourceDir}");
        }
        
        DirectoryInfo[] dirs = dir.GetDirectories();
        
        // Crear directorio destino si no existe
        if (!Directory.Exists(destDir))
        {
            Directory.CreateDirectory(destDir);
        }
        
        // Copiar archivos
        FileInfo[] files = dir.GetFiles();
        foreach (FileInfo file in files)
        {
            string targetFilePath = Path.Combine(destDir, file.Name);
            file.CopyTo(targetFilePath, overwrite);
        }
        
        // Copiar subdirectorios recursivamente
        foreach (DirectoryInfo subDir in dirs)
        {
            string newDestDir = Path.Combine(destDir, subDir.Name);
            CopyDirectory(subDir.FullName, newDestDir, overwrite);
        }
    }
}

