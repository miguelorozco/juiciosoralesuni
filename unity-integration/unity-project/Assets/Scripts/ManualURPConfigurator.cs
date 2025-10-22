using UnityEngine;
using UnityEngine.Rendering;
using UnityEngine.Rendering.Universal;

namespace JuiciosSimulator.Fixes
{
    /// <summary>
    /// Script de configuración manual para Unity Editor
    /// Usa este script para configurar manualmente el URP y corregir errores
    /// </summary>
    public class ManualURPConfigurator : MonoBehaviour
    {
        [Header("Manual Configuration")]
        [SerializeField] private UniversalRenderPipelineAsset urpAsset;
        [SerializeField] private VolumeProfile defaultVolumeProfile;
        
        [Header("Quality Settings")]
        [SerializeField] private int targetFrameRate = 60;
        [SerializeField] private int vSyncCount = 0;
        [SerializeField] private int antiAliasing = 0;
        
        [Header("Job System")]
        [SerializeField] private int jobWorkerCount = 4;
        
        [Header("Debug")]
        [SerializeField] private bool showDebugInfo = true;
        
        /// <summary>
        /// Configura el URP manualmente
        /// </summary>
        [ContextMenu("Configurar URP")]
        public void ConfigureURP()
        {
            try
            {
                // 1. Configurar Graphics Settings
                if (urpAsset != null)
                {
                    GraphicsSettings.renderPipelineAsset = urpAsset;
                    Debug.Log("ManualURPConfigurator: URP Asset configurado");
                }
                else
                {
                    Debug.LogWarning("ManualURPConfigurator: URP Asset no asignado");
                }
                
                // 2. Configurar Quality Settings
                ConfigureQualitySettings();
                
                // 3. Configurar Job System
                ConfigureJobSystem();
                
                // 4. Configurar Post-Processing
                ConfigurePostProcessing();
                
                if (showDebugInfo)
                {
                    Debug.Log("ManualURPConfigurator: Configuración completada");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"ManualURPConfigurator: Error en configuración: {e.Message}");
            }
        }
        
        /// <summary>
        /// Configura Quality Settings
        /// </summary>
        private void ConfigureQualitySettings()
        {
            try
            {
                QualitySettings.vSyncCount = vSyncCount;
                QualitySettings.antiAliasing = antiAliasing;
                
                if (Application.isPlaying)
                {
                    Application.targetFrameRate = targetFrameRate;
                }
                
                Debug.Log($"ManualURPConfigurator: Quality Settings configurados - VSync: {vSyncCount}, AA: {antiAliasing}");
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"ManualURPConfigurator: Error en Quality Settings: {e.Message}");
            }
        }
        
        /// <summary>
        /// Configura Job System
        /// </summary>
        private void ConfigureJobSystem()
        {
            try
            {
                var workers = Mathf.Min(jobWorkerCount, System.Environment.ProcessorCount);
                Unity.Jobs.JobWorkerCount.SetWorkerCount(workers);
                
                Debug.Log($"ManualURPConfigurator: Job System configurado con {workers} workers");
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"ManualURPConfigurator: Error en Job System: {e.Message}");
            }
        }
        
        /// <summary>
        /// Configura Post-Processing
        /// </summary>
        private void ConfigurePostProcessing()
        {
            try
            {
                // Buscar Volumes globales
                var volumes = FindObjectsOfType<Volume>();
                int configuredVolumes = 0;
                
                foreach (var volume in volumes)
                {
                    if (volume == null) continue;
                    
                    // Si no tiene profile y tenemos uno por defecto, asignarlo
                    if (volume.profile == null && defaultVolumeProfile != null)
                    {
                        volume.profile = defaultVolumeProfile;
                        configuredVolumes++;
                    }
                }
                
                Debug.Log($"ManualURPConfigurator: {configuredVolumes} volumes configurados");
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"ManualURPConfigurator: Error en Post-Processing: {e.Message}");
            }
        }
        
        /// <summary>
        /// Verifica la configuración actual
        /// </summary>
        [ContextMenu("Verificar Configuración")]
        public void VerifyConfiguration()
        {
            try
            {
                var currentURP = GraphicsSettings.currentRenderPipeline;
                var workerCount = Unity.Jobs.JobWorkerCount.GetWorkerCount();
                var volumes = FindObjectsOfType<Volume>();
                
                Debug.Log($"ManualURPConfigurator: Verificación - URP: {(currentURP != null ? "Configurado" : "No configurado")}, Workers: {workerCount}, Volumes: {volumes.Length}");
                
                // Verificar cada volume
                foreach (var volume in volumes)
                {
                    if (volume != null)
                    {
                        var status = volume.profile != null ? "Válido" : "Sin profile";
                        Debug.Log($"ManualURPConfigurator: Volume '{volume.name}' - {status}");
                    }
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"ManualURPConfigurator: Error en verificación: {e.Message}");
            }
        }
        
        /// <summary>
        /// Resetea la configuración a valores por defecto
        /// </summary>
        [ContextMenu("Resetear Configuración")]
        public void ResetConfiguration()
        {
            try
            {
                QualitySettings.vSyncCount = 1;
                QualitySettings.antiAliasing = 2;
                Application.targetFrameRate = -1;
                
                var defaultWorkers = System.Environment.ProcessorCount;
                Unity.Jobs.JobWorkerCount.SetWorkerCount(defaultWorkers);
                
                Debug.Log("ManualURPConfigurator: Configuración reseteada a valores por defecto");
            }
            catch (System.Exception e)
            {
                Debug.LogError($"ManualURPConfigurator: Error al resetear: {e.Message}");
            }
        }
    }
}
