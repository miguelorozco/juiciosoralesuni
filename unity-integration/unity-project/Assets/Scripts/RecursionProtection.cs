using UnityEngine;

namespace JuiciosSimulator.Utils
{
    /// <summary>
    /// Utilidad para prevenir recursión infinita durante la inicialización
    /// </summary>
    public static class RecursionProtection
    {
        private static bool isInitializing = false;
        private static int initializationDepth = 0;
        private const int MAX_DEPTH = 10;

        /// <summary>
        /// Verificar si se puede ejecutar una inicialización
        /// </summary>
        public static bool CanInitialize(string context)
        {
            if (isInitializing && initializationDepth >= MAX_DEPTH)
            {
                Debug.LogError($"[RecursionProtection] ⚠️ Profundidad máxima alcanzada ({initializationDepth}) en contexto: {context}. Posible recursión infinita detectada.");
                return false;
            }

            return true;
        }

        /// <summary>
        /// Iniciar bloque de inicialización
        /// </summary>
        public static void BeginInitialization(string context)
        {
            if (!isInitializing)
            {
                isInitializing = true;
                initializationDepth = 0;
                Debug.Log($"[RecursionProtection] Iniciando inicialización: {context}");
            }
            else
            {
                initializationDepth++;
                Debug.LogWarning($"[RecursionProtection] Profundidad de inicialización: {initializationDepth} en contexto: {context}");
            }
        }

        /// <summary>
        /// Finalizar bloque de inicialización
        /// </summary>
        public static void EndInitialization(string context)
        {
            if (initializationDepth > 0)
            {
                initializationDepth--;
            }
            else
            {
                isInitializing = false;
                Debug.Log($"[RecursionProtection] Finalizada inicialización: {context}");
            }
        }

        /// <summary>
        /// Resetear protección (usar con cuidado)
        /// </summary>
        public static void Reset()
        {
            isInitializing = false;
            initializationDepth = 0;
            Debug.Log("[RecursionProtection] Protección reseteada");
        }
    }
}

