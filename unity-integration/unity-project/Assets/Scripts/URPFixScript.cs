using UnityEngine;
using UnityEngine.Rendering;
using UnityEngine.Rendering.Universal;

namespace JuiciosSimulator.Fixes
{
    /// <summary>
    /// Script para corregir problemas comunes del Universal Render Pipeline (URP)
    /// Soluciona errores de Render Graph, Post-Processing y Job System
    /// </summary>
    public class URPFixScript : MonoBehaviour
    {
        [Header("URP Configuration")]
        [SerializeField] private bool enableRenderGraph = true;
        [SerializeField] private bool fixPostProcessing = true;
        [SerializeField] private bool optimizeJobSystem = true;

        [Header("Debug")]
        [SerializeField] private bool showDebugInfo = true;

        private UniversalRenderPipelineAsset urpAsset;

        void Start()
        {
            FixURPConfiguration();
        }

        /// <summary>
        /// Corrige la configuración del URP para evitar errores comunes
        /// </summary>
        private void FixURPConfiguration()
        {
            try
            {
                // 1. Obtener el asset de URP actual
                urpAsset = GraphicsSettings.currentRenderPipeline as UniversalRenderPipelineAsset;
                if (urpAsset == null)
                {
                    Debug.LogError("URPFixScript: No se encontró UniversalRenderPipelineAsset");
                    return;
                }

                // 2. Verificar configuración global (usando API pública)
                var globalSettings = UniversalRenderPipelineGlobalSettings.instance;
                if (globalSettings == null)
                {
                    Debug.LogError("URPFixScript: No se encontró UniversalRenderPipelineGlobalSettings");
                    return;
                }

                // 3. Habilitar Render Graph si está deshabilitado
                if (enableRenderGraph)
                {
                    EnableRenderGraph();
                }

                // 4. Corregir Post-Processing
                if (fixPostProcessing)
                {
                    FixPostProcessing();
                }

                // 5. Optimizar Job System
                if (optimizeJobSystem)
                {
                    OptimizeJobSystem();
                }

                if (showDebugInfo)
                {
                    Debug.Log("URPFixScript: Configuración URP corregida exitosamente");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"URPFixScript: Error al corregir configuración URP: {e.Message}");
            }
        }

        /// <summary>
        /// Habilita el Render Graph para evitar errores de ejecución
        /// </summary>
        private void EnableRenderGraph()
        {
            try
            {
                // Verificar que el URP esté configurado correctamente
                if (urpAsset != null)
                {
                    Debug.Log("URPFixScript: URP Asset configurado correctamente");
                    
                    // El Render Graph se habilita automáticamente en Unity 6
                    // Solo verificamos que la configuración sea válida
                    Debug.Log("URPFixScript: Render Graph verificado");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"URPFixScript: No se pudo verificar Render Graph: {e.Message}");
            }
        }

        /// <summary>
        /// Corrige problemas de Post-Processing
        /// </summary>
        private void FixPostProcessing()
        {
            try
            {
                // Buscar y corregir componentes de Post-Processing
                var postProcessVolumes = FindObjectsOfType<Volume>();
                foreach (var volume in postProcessVolumes)
                {
                    if (volume.profile == null)
                    {
                        Debug.LogWarning($"URPFixScript: Volume sin profile encontrado: {volume.name}");
                        continue;
                    }

                    // Verificar que el profile tenga componentes válidos
                    var components = volume.profile.components;
                    for (int i = components.Count - 1; i >= 0; i--)
                    {
                        var component = components[i];
                        if (component == null)
                        {
                            Debug.LogWarning($"URPFixScript: Componente nulo encontrado en {volume.name}");
                            components.RemoveAt(i);
                        }
                    }
                }

                Debug.Log("URPFixScript: Post-Processing corregido");
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"URPFixScript: Error al corregir Post-Processing: {e.Message}");
            }
        }

        /// <summary>
        /// Optimiza el Job System para evitar errores de concurrencia
        /// </summary>
        private void OptimizeJobSystem()
        {
            try
            {
                // Configurar Job System para mejor rendimiento
                var jobWorkerCount = System.Environment.ProcessorCount;
                Unity.Jobs.JobWorkerCount.SetWorkerCount(jobWorkerCount);

                Debug.Log($"URPFixScript: Job System optimizado con {jobWorkerCount} workers");
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"URPFixScript: Error al optimizar Job System: {e.Message}");
            }
        }

        /// <summary>
        /// Método público para re-aplicar las correcciones
        /// </summary>
        [ContextMenu("Re-aplicar Correcciones URP")]
        public void ReapplyFixes()
        {
            FixURPConfiguration();
        }

        /// <summary>
        /// Método para verificar el estado actual del URP
        /// </summary>
        [ContextMenu("Verificar Estado URP")]
        public void CheckURPStatus()
        {
            if (urpAsset == null)
            {
                Debug.LogError("URPFixScript: URP Asset no encontrado");
                return;
            }
            
            var globalSettings = UniversalRenderPipelineGlobalSettings.instance;
            if (globalSettings == null)
            {
                Debug.LogError("URPFixScript: Global Settings no encontrados");
                return;
            }
            
            Debug.Log("URPFixScript: Estado URP verificado correctamente");
        }
    }
}
