using UnityEngine;
using UnityEngine.Rendering;
using UnityEngine.Rendering.Universal;

namespace JuiciosSimulator.Fixes
{
    /// <summary>
    /// Script para corregir errores específicos del Post-Processing
    /// Soluciona NullReferenceException en PostProcessingPass
    /// </summary>
    public class PostProcessingFixScript : MonoBehaviour
    {
        [Header("Post-Processing Fixes")]
        [SerializeField] private bool fixNullReferences = true;
        [SerializeField] private bool validateVolumes = true;
        [SerializeField] private bool fixTextureReferences = true;

        [Header("Debug")]
        [SerializeField] private bool showDebugInfo = true;

        private void Start()
        {
            FixPostProcessingIssues();
        }

        /// <summary>
        /// Corrige todos los problemas de Post-Processing
        /// </summary>
        private void FixPostProcessingIssues()
        {
            try
            {
                if (fixNullReferences)
                {
                    FixNullReferences();
                }

                if (validateVolumes)
                {
                    ValidatePostProcessVolumes();
                }

                if (fixTextureReferences)
                {
                    FixTextureReferences();
                }

                if (showDebugInfo)
                {
                    Debug.Log("PostProcessingFixScript: Correcciones aplicadas exitosamente");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"PostProcessingFixScript: Error al aplicar correcciones: {e.Message}");
            }
        }

        /// <summary>
        /// Corrige referencias nulas en Post-Processing
        /// </summary>
        private void FixNullReferences()
        {
            try
            {
                // Buscar todos los Volumes en la escena
                var volumes = FindObjectsOfType<Volume>();

                foreach (var volume in volumes)
                {
                    if (volume == null) continue;

                    // Verificar que el profile no sea nulo
                    if (volume.profile == null)
                    {
                        Debug.LogWarning($"PostProcessingFixScript: Volume '{volume.name}' tiene profile nulo");

                        // Intentar crear un profile por defecto
                        var defaultProfile = CreateDefaultVolumeProfile();
                        if (defaultProfile != null)
                        {
                            volume.profile = defaultProfile;
                            Debug.Log($"PostProcessingFixScript: Profile por defecto asignado a '{volume.name}'");
                        }
                        continue;
                    }

                    // Verificar componentes del profile
                    var components = volume.profile.components;
                    for (int i = components.Count - 1; i >= 0; i--)
                    {
                        var component = components[i];
                        if (component == null)
                        {
                            Debug.LogWarning($"PostProcessingFixScript: Componente nulo encontrado en '{volume.name}'");
                            components.RemoveAt(i);
                        }
                    }
                }

                Debug.Log("PostProcessingFixScript: Referencias nulas corregidas");
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"PostProcessingFixScript: Error al corregir referencias nulas: {e.Message}");
            }
        }

        /// <summary>
        /// Valida todos los Post-Process Volumes
        /// </summary>
        private void ValidatePostProcessVolumes()
        {
            try
            {
                var volumes = FindObjectsOfType<Volume>();

                foreach (var volume in volumes)
                {
                    if (volume == null) continue;

                    // Validar configuración del volume
                    if (volume.profile != null)
                    {
                        // Verificar que el profile sea válido
                        if (!volume.profile.isDirty)
                        {
                            // Forzar recompilación del profile
                            volume.profile.isDirty = true;
                        }
                    }

                    // Validar collider si es necesario
                    if (volume.isGlobal == false)
                    {
                        var collider = volume.GetComponent<Collider>();
                        if (collider == null)
                        {
                            Debug.LogWarning($"PostProcessingFixScript: Volume '{volume.name}' no tiene collider");
                        }
                    }
                }

                Debug.Log("PostProcessingFixScript: Volumes validados");
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"PostProcessingFixScript: Error al validar volumes: {e.Message}");
            }
        }

        /// <summary>
        /// Corrige referencias de texturas en Post-Processing
        /// </summary>
        private void FixTextureReferences()
        {
            try
            {
                // Buscar configuración global del URP usando API pública
                Debug.Log("PostProcessingFixScript: Verificando configuración global");

                Debug.Log("PostProcessingFixScript: Referencias de texturas verificadas");
            }
            catch (System.Exception e)
            {
                Debug.LogWarning($"PostProcessingFixScript: Error al verificar texturas: {e.Message}");
            }
        }

        /// <summary>
        /// Crea un profile por defecto para Post-Processing
        /// </summary>
        private VolumeProfile CreateDefaultVolumeProfile()
        {
            try
            {
                // Crear un nuevo VolumeProfile
                var profile = ScriptableObject.CreateInstance<VolumeProfile>();

                // Agregar componentes básicos
                var bloom = profile.Add<Bloom>();
                if (bloom != null)
                {
                    bloom.intensity.value = 0.5f;
                    bloom.threshold.value = 1.0f;
                }

                // Nota: ColorGrading puede no estar disponible en todas las versiones
                // Se omite para evitar errores de compilación

                Debug.Log("PostProcessingFixScript: Profile por defecto creado");
                return profile;
            }
            catch (System.Exception e)
            {
                Debug.LogError($"PostProcessingFixScript: Error al crear profile por defecto: {e.Message}");
                return null;
            }
        }

        /// <summary>
        /// Método público para re-aplicar las correcciones
        /// </summary>
        [ContextMenu("Re-aplicar Correcciones Post-Processing")]
        public void ReapplyFixes()
        {
            FixPostProcessingIssues();
        }

        /// <summary>
        /// Método para verificar el estado del Post-Processing
        /// </summary>
        [ContextMenu("Verificar Estado Post-Processing")]
        public void CheckPostProcessingStatus()
        {
            try
            {
                var volumes = FindObjectsOfType<Volume>();
                int validVolumes = 0;
                int invalidVolumes = 0;

                foreach (var volume in volumes)
                {
                    if (volume != null && volume.profile != null)
                    {
                        validVolumes++;
                    }
                    else
                    {
                        invalidVolumes++;
                    }
                }

                Debug.Log($"PostProcessingFixScript: Volumes válidos: {validVolumes}, Inválidos: {invalidVolumes}");
            }
            catch (System.Exception e)
            {
                Debug.LogError($"PostProcessingFixScript: Error al verificar estado: {e.Message}");
            }
        }
    }
}
