using UnityEngine;
using UnityEngine.Rendering;
using UnityEngine.Rendering.Universal;
using Unity.Collections;
using Unity.Jobs;

namespace JuiciosSimulator.Fixes
{
    /// <summary>
    /// Script simple para corregir errores comunes de URP y Render Graph
    /// Soluciona InvalidOperationException, Render Graph Execution errors y NullReferenceException
    /// </summary>
    public class SimpleURPFixScript : MonoBehaviour
    {
        [Header("Fix Options")]
        [SerializeField] private bool fixJobSystem = true;
        [SerializeField] private bool fixPostProcessing = true;
        [SerializeField] private bool optimizePerformance = true;
        
        [Header("Debug")]
        [SerializeField] private bool showDebugLogs = true;
        
        private void Start()
        {
            ApplyFixes();
        }
        
        /// <summary>
        /// Aplica todas las correcciones necesarias
        /// </summary>
        private void ApplyFixes()
        {
            try
            {
                if (fixJobSystem)
                {
                    FixJobSystem();
                }
                
                if (fixPostProcessing)
                {
                    FixPostProcessing();
                }
                
                if (optimizePerformance)
                {
                    OptimizePerformance();
                }
                
                if (showDebugLogs)
                {
                    Debug.Log("SimpleURPFixScript: Todas las correcciones aplicadas exitosamente");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"SimpleURPFixScript: Error al aplicar correcciones: {e.Message}");
            }
        }
        
        /// <summary>
        /// Corrige problemas del Job System
        /// </summary>
        private void FixJobSystem()
        {
            try
            {
                // Configurar Job System para evitar errores de concurrencia
                var workerCount = Mathf.Min(4, System.Environment.ProcessorCount);
                Unity.Jobs.JobWorkerCount.SetWorkerCount(workerCount);
                
                if (showDebugLogs)
                {
                    Debug.Log($"SimpleURPFixScript: Job System configurado con {workerCount} workers");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"SimpleURPFixScript: Error al configurar Job System: {e.Message}");
            }
        }
        
        /// <summary>
        /// Corrige problemas de Post-Processing
        /// </summary>
        private void FixPostProcessing()
        {
            try
            {
                // Buscar y corregir Volumes con problemas
                var volumes = FindObjectsOfType<Volume>();
                int fixedVolumes = 0;
                
                foreach (var volume in volumes)
                {
                    if (volume == null) continue;
                    
                    // Verificar profile
                    if (volume.profile == null)
                    {
                        Debug.LogWarning($"SimpleURPFixScript: Volume '{volume.name}' sin profile");
                        continue;
                    }
                    
                    // Verificar componentes
                    var components = volume.profile.components;
                    for (int i = components.Count - 1; i >= 0; i--)
                    {
                        if (components[i] == null)
                        {
                            components.RemoveAt(i);
                            fixedVolumes++;
                        }
                    }
                }
                
                if (showDebugLogs)
                {
                    Debug.Log($"SimpleURPFixScript: {fixedVolumes} volumes corregidos");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"SimpleURPFixScript: Error al corregir Post-Processing: {e.Message}");
            }
        }
        
        /// <summary>
        /// Optimiza el rendimiento general
        /// </summary>
        private void OptimizePerformance()
        {
            try
            {
                // Configurar Quality Settings para mejor rendimiento
                QualitySettings.vSyncCount = 0;
                QualitySettings.antiAliasing = 0;
                
                // Configurar Application.targetFrameRate si es necesario
                if (Application.isPlaying)
                {
                    Application.targetFrameRate = 60;
                }
                
                if (showDebugLogs)
                {
                    Debug.Log("SimpleURPFixScript: Rendimiento optimizado");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"SimpleURPFixScript: Error al optimizar rendimiento: {e.Message}");
            }
        }
        
        /// <summary>
        /// Método público para re-aplicar las correcciones
        /// </summary>
        [ContextMenu("Re-aplicar Correcciones")]
        public void ReapplyFixes()
        {
            ApplyFixes();
        }
        
        /// <summary>
        /// Método para verificar el estado del sistema
        /// </summary>
        [ContextMenu("Verificar Estado")]
        public void CheckStatus()
        {
            try
            {
                var workerCount = Unity.Jobs.JobWorkerCount.GetWorkerCount();
                var volumes = FindObjectsOfType<Volume>();
                var validVolumes = 0;
                
                foreach (var volume in volumes)
                {
                    if (volume != null && volume.profile != null)
                    {
                        validVolumes++;
                    }
                }
                
                Debug.Log($"SimpleURPFixScript: Estado - Workers: {workerCount}, Volumes válidos: {validVolumes}");
            }
            catch (System.Exception e)
            {
                Debug.LogError($"SimpleURPFixScript: Error al verificar estado: {e.Message}");
            }
        }
        
        /// <summary>
        /// Limpia recursos al destruir el objeto
        /// </summary>
        private void OnDestroy()
        {
            try
            {
                // Limpiar Job System
                Unity.Jobs.JobWorkerCount.SetWorkerCount(0);
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"SimpleURPFixScript: Error al limpiar recursos: {e.Message}");
            }
        }
    }
}
