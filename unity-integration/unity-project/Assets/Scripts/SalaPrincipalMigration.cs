using UnityEngine;
using UnityEngine.UI;
using TMPro;
using JuiciosSimulator.Integration;
using JuiciosSimulator.Session;
using JuiciosSimulator.UI;

namespace JuiciosSimulator.Migration
{
    /// <summary>
    /// Script de migración para reemplazar la selección manual de roles con integración de Laravel
    /// Este script ayuda a migrar la escena SalaPrincipal de selección manual a asignación automática
    /// </summary>
    public class SalaPrincipalMigration : MonoBehaviour
    {
        [Header("Migration Instructions")]
        [TextArea(10, 20)]
        public string migrationInstructions = @"
MIGRACIÓN DE SALA PRINCIPAL - SELECCIÓN MANUAL A LARAVEL INTEGRATION

PASOS PARA MIGRAR LA ESCENA:

1. ELIMINAR COMPONENTES ANTIGUOS:
   - Eliminar RoleSelectionUI del Canvas
   - Eliminar GestionRedJugador del GameObject principal
   - Eliminar el Canvas de selección de roles completo

2. AGREGAR COMPONENTES NUEVOS:
   - Agregar EnhancedNetworkManager al GameObject principal
   - Agregar RoleInfoUI al Canvas (si quieres mostrar info del rol)
   - Agregar SessionManager si no existe

3. CONFIGURAR ENHANCED NETWORK MANAGER:
   - Asignar SessionManager en el campo sessionManager
   - Configurar spawnPosition y spawnRotation
   - Configurar UI references (loadingPanel, loadingText, statusText)

4. CONFIGURAR ROLE INFO UI (OPCIONAL):
   - Asignar SessionManager
   - Configurar todos los campos de UI (TextMeshProUGUI, Image, Button)
   - Configurar colores y descripciones de roles

5. CONFIGURAR SESSION MANAGER:
   - Asegurar que esté configurado correctamente
   - Verificar que tenga acceso a la API de Laravel

6. PROBAR LA MIGRACIÓN:
   - Ejecutar la escena
   - Verificar que se conecte a Laravel
   - Verificar que obtenga el rol automáticamente
   - Verificar que se conecte a Photon con el rol asignado

NOTAS IMPORTANTES:
- El chat de voz seguirá funcionando igual
- Los roles ahora vienen de Laravel, no de selección manual
- La sala de Photon se crea basada en la sesión de Laravel
- El usuario ya no necesita seleccionar rol, se asigna automáticamente
";

        [Header("Migration Status")]
        public bool migrationCompleted = false;
        public bool oldComponentsRemoved = false;
        public bool newComponentsAdded = false;
        public bool configurationCompleted = false;

        [Header("Debug")]
        public bool showDebugLogs = true;

        void Start()
        {
            if (showDebugLogs)
            {
                Debug.Log("SalaPrincipalMigration: Script de migración cargado");
                Debug.Log("Revisa las instrucciones en el Inspector para completar la migración");
            }
        }

        /// <summary>
        /// Verifica el estado de la migración
        /// </summary>
        [ContextMenu("Verificar Estado de Migración")]
        public void CheckMigrationStatus()
        {
            try
            {
                Debug.Log("=== VERIFICACIÓN DE MIGRACIÓN SALA PRINCIPAL ===");

                // Verificar componentes antiguos
                CheckOldComponents();

                // Verificar componentes nuevos
                CheckNewComponents();

                // Verificar configuración
                CheckConfiguration();

                Debug.Log("=== FIN DE VERIFICACIÓN ===");
            }
            catch (System.Exception e)
            {
                Debug.LogError($"SalaPrincipalMigration: Error verificando migración: {e.Message}");
            }
        }

        /// <summary>
        /// Verifica si los componentes antiguos han sido eliminados
        /// </summary>
        private void CheckOldComponents()
        {
            Debug.Log("--- VERIFICANDO COMPONENTES ANTIGUOS ---");

            // Buscar RoleSelectionUI
            var roleSelectionUI = FindObjectOfType<RoleSelectionUI>();
            if (roleSelectionUI != null)
            {
                Debug.LogWarning("❌ RoleSelectionUI encontrado - DEBE SER ELIMINADO");
                oldComponentsRemoved = false;
            }
            else
            {
                Debug.Log("✅ RoleSelectionUI no encontrado - OK");
            }

            // Buscar GestionRedJugador
            var gestionRedJugador = FindObjectOfType<GestionRedJugador>();
            if (gestionRedJugador != null)
            {
                Debug.LogWarning("❌ GestionRedJugador encontrado - DEBE SER ELIMINADO");
                oldComponentsRemoved = false;
            }
            else
            {
                Debug.Log("✅ GestionRedJugador no encontrado - OK");
            }

            // Buscar Canvas de selección de roles
            var canvases = FindObjectsOfType<Canvas>();
            bool foundRoleSelectionCanvas = false;

            foreach (var canvas in canvases)
            {
                if (canvas.name.Contains("Role") || canvas.name.Contains("Selection"))
                {
                    foundRoleSelectionCanvas = true;
                    Debug.LogWarning($"❌ Canvas de selección encontrado: {canvas.name} - DEBE SER ELIMINADO");
                }
            }

            if (!foundRoleSelectionCanvas)
            {
                Debug.Log("✅ No se encontraron Canvas de selección de roles - OK");
            }
        }

        /// <summary>
        /// Verifica si los componentes nuevos han sido agregados
        /// </summary>
        private void CheckNewComponents()
        {
            Debug.Log("--- VERIFICANDO COMPONENTES NUEVOS ---");

            // Buscar EnhancedNetworkManager
            var enhancedNetworkManager = FindObjectOfType<EnhancedNetworkManager>();
            if (enhancedNetworkManager != null)
            {
                Debug.Log("✅ EnhancedNetworkManager encontrado - OK");
                newComponentsAdded = true;
            }
            else
            {
                Debug.LogWarning("❌ EnhancedNetworkManager NO encontrado - DEBE SER AGREGADO");
                newComponentsAdded = false;
            }

            // Buscar SessionManager
            var sessionManager = FindObjectOfType<SessionManager>();
            if (sessionManager != null)
            {
                Debug.Log("✅ SessionManager encontrado - OK");
            }
            else
            {
                Debug.LogWarning("❌ SessionManager NO encontrado - DEBE SER AGREGADO");
            }

            // Buscar RoleInfoUI (opcional)
            var roleInfoUI = FindObjectOfType<RoleInfoUI>();
            if (roleInfoUI != null)
            {
                Debug.Log("✅ RoleInfoUI encontrado - OK");
            }
            else
            {
                Debug.Log("ℹ️ RoleInfoUI no encontrado - OPCIONAL");
            }
        }

        /// <summary>
        /// Verifica la configuración de los componentes
        /// </summary>
        private void CheckConfiguration()
        {
            Debug.Log("--- VERIFICANDO CONFIGURACIÓN ---");

            // Verificar EnhancedNetworkManager
            var enhancedNetworkManager = FindObjectOfType<EnhancedNetworkManager>();
            if (enhancedNetworkManager != null)
            {
                if (enhancedNetworkManager.sessionManager != null)
                {
                    Debug.Log("✅ EnhancedNetworkManager.sessionManager configurado - OK");
                }
                else
                {
                    Debug.LogWarning("❌ EnhancedNetworkManager.sessionManager NO configurado");
                }

                if (enhancedNetworkManager.loadingPanel != null)
                {
                    Debug.Log("✅ EnhancedNetworkManager.loadingPanel configurado - OK");
                }
                else
                {
                    Debug.LogWarning("❌ EnhancedNetworkManager.loadingPanel NO configurado");
                }
            }

            // Verificar SessionManager
            var sessionManager = FindObjectOfType<SessionManager>();
            if (sessionManager != null)
            {
                // TODO: Implementar IsInitialized en SessionManager
                // if (sessionManager.IsInitialized)
                // {
                //     Debug.Log("✅ SessionManager inicializado - OK");
                // }
                // else
                // {
                //     Debug.LogWarning("❌ SessionManager NO inicializado");
                // }
                Debug.Log("✅ SessionManager encontrado - OK");
            }
        }

        /// <summary>
        /// Genera un reporte de migración
        /// </summary>
        [ContextMenu("Generar Reporte de Migración")]
        public void GenerateMigrationReport()
        {
            try
            {
                Debug.Log("=== REPORTE DE MIGRACIÓN SALA PRINCIPAL ===");
                Debug.Log($"Fecha: {System.DateTime.Now}");
                Debug.Log($"Migración completada: {(migrationCompleted ? "SÍ" : "NO")}");
                Debug.Log($"Componentes antiguos eliminados: {(oldComponentsRemoved ? "SÍ" : "NO")}");
                Debug.Log($"Componentes nuevos agregados: {(newComponentsAdded ? "SÍ" : "NO")}");
                Debug.Log($"Configuración completada: {(configurationCompleted ? "SÍ" : "NO")}");

                CheckMigrationStatus();

                if (migrationCompleted && oldComponentsRemoved && newComponentsAdded && configurationCompleted)
                {
                    Debug.Log("🎉 MIGRACIÓN COMPLETADA EXITOSAMENTE");
                }
                else
                {
                    Debug.LogWarning("⚠️ MIGRACIÓN INCOMPLETA - Revisa los pasos pendientes");
                }

                Debug.Log("=== FIN DEL REPORTE ===");
            }
            catch (System.Exception e)
            {
                Debug.LogError($"SalaPrincipalMigration: Error generando reporte: {e.Message}");
            }
        }

        /// <summary>
        /// Marca la migración como completada
        /// </summary>
        [ContextMenu("Marcar Migración como Completada")]
        public void MarkMigrationAsCompleted()
        {
            migrationCompleted = true;
            Debug.Log("SalaPrincipalMigration: Migración marcada como completada");
        }

        /// <summary>
        /// Resetea el estado de migración
        /// </summary>
        [ContextMenu("Resetear Estado de Migración")]
        public void ResetMigrationStatus()
        {
            migrationCompleted = false;
            oldComponentsRemoved = false;
            newComponentsAdded = false;
            configurationCompleted = false;
            Debug.Log("SalaPrincipalMigration: Estado de migración reseteado");
        }
    }
}
