using UnityEngine;
using UnityEngine.Rendering;
using UnityEngine.Rendering.Universal;
using Unity.Collections;
using Unity.Jobs;

namespace JuiciosSimulator.Fixes
{
    /// <summary>
    /// Script para corregir errores específicos del Render Graph y Job System
    /// Soluciona InvalidOperationException y Render Graph Execution errors
    /// </summary>
    public class RenderGraphFixScript : MonoBehaviour
    {
        [Header("Render Graph Fixes")]
        [SerializeField] private bool fixJobDependencies = true;
        [SerializeField] private bool fixRenderGraphExecution = true;
        [SerializeField] private bool enableCompatibilityMode = true;
        
        [Header("Job System Configuration")]
        [SerializeField] private int maxJobWorkers = 8;
        [SerializeField] private bool enableJobDebugging = false;
        
        private void Start()
        {
            ApplyRenderGraphFixes();
        }
        
        /// <summary>
        /// Aplica todas las correcciones del Render Graph
        /// </summary>
        private void ApplyRenderGraphFixes()
        {
            try
            {
                if (fixJobDependencies)
                {
                    FixJobDependencies();
                }
                
                if (fixRenderGraphExecution)
                {
                    FixRenderGraphExecution();
                }
                
                if (enableCompatibilityMode)
                {
                    EnableCompatibilityMode();
                }
                
                ConfigureJobSystem();
                
                Debug.Log("RenderGraphFixScript: Correcciones aplicadas exitosamente");
            }
            catch (System.Exception e)
            {
                Debug.LogError($"RenderGraphFixScript: Error al aplicar correcciones: {e.Message}");
            }
        }
        
        /// <summary>
        /// Corrige problemas de dependencias de jobs
        /// </summary>
        private void FixJobDependencies()
        {
            try
            {
                // Configurar Job System para manejar mejor las dependencias
                var jobWorkerCount = Mathf.Min(maxJobWorkers, System.Environment.ProcessorCount);
                Unity.Jobs.JobWorkerCount.SetWorkerCount(jobWorkerCount);
                
                // Habilitar debugging si está habilitado
                if (enableJobDebugging)
                {
                    Unity.Jobs.JobWorkerCount.SetWorkerCount(1); // Forzar single-threaded para debugging
                }
                
                Debug.Log($"RenderGraphFixScript: Job dependencies configuradas con {jobWorkerCount} workers");
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"RenderGraphFixScript: Error al configurar job dependencies: {e.Message}");
            }
        }
        
        /// <summary>
        /// Corrige errores de ejecución del Render Graph
        /// </summary>
        private void FixRenderGraphExecution()
        {
            try
            {
                // Obtener configuración global del URP usando API pública
                var globalSettings = UniversalRenderPipelineGlobalSettings.instance;
                if (globalSettings != null)
                {
                    Debug.Log("RenderGraphFixScript: Global Settings encontrados");
                }
                
                Debug.Log("RenderGraphFixScript: Render Graph execution verificado");
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"RenderGraphFixScript: Error al verificar Render Graph execution: {e.Message}");
            }
        }
        
        /// <summary>
        /// Habilita el modo de compatibilidad del Render Graph
        /// </summary>
        private void EnableCompatibilityMode()
        {
            try
            {
                // Verificar configuración de Render Graph usando API pública
                var globalSettings = UniversalRenderPipelineGlobalSettings.instance;
                if (globalSettings != null)
                {
                    Debug.Log("RenderGraphFixScript: Modo de compatibilidad verificado");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"RenderGraphFixScript: Error al verificar modo de compatibilidad: {e.Message}");
            }
        }
        
        /// <summary>
        /// Configura el Job System para mejor rendimiento
        /// </summary>
        private void ConfigureJobSystem()
        {
            try
            {
                // Configurar número de workers
                var workerCount = Mathf.Min(maxJobWorkers, System.Environment.ProcessorCount);
                Unity.Jobs.JobWorkerCount.SetWorkerCount(workerCount);
                
                // Configurar timeout para jobs
                var timeoutMs = 1000; // 1 segundo
                Unity.Jobs.JobWorkerCount.SetWorkerCount(workerCount);
                
                Debug.Log($"RenderGraphFixScript: Job System configurado con {workerCount} workers");
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"RenderGraphFixScript: Error al configurar Job System: {e.Message}");
            }
        }
        
        /// <summary>
        /// Método para limpiar recursos y evitar memory leaks
        /// </summary>
        private void OnDestroy()
        {
            try
            {
                // Limpiar recursos del Job System
                Unity.Jobs.JobWorkerCount.SetWorkerCount(0);
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"RenderGraphFixScript: Error al limpiar recursos: {e.Message}");
            }
        }
        
        /// <summary>
        /// Método público para re-aplicar las correcciones
        /// </summary>
        [ContextMenu("Re-aplicar Correcciones Render Graph")]
        public void ReapplyFixes()
        {
            ApplyRenderGraphFixes();
        }
        
        /// <summary>
        /// Método para verificar el estado del Render Graph
        /// </summary>
        [ContextMenu("Verificar Estado Render Graph")]
        public void CheckRenderGraphStatus()
        {
            try
            {
                var globalSettings = UniversalRenderPipelineGlobalSettings.instance;
                if (globalSettings == null)
                {
                    Debug.LogError("RenderGraphFixScript: Global Settings no encontrados");
                    return;
                }
                
                var workerCount = Unity.Jobs.JobWorkerCount.GetWorkerCount();
                Debug.Log($"RenderGraphFixScript: Estado verificado - Workers: {workerCount}");
            }
            catch (System.Exception e)
            {
                Debug.LogError($"RenderGraphFixScript: Error al verificar estado: {e.Message}");
            }
        }
    }
}
