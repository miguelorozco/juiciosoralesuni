using UnityEngine;

namespace JuiciosSimulator.API
{
    /// <summary>
    /// Valida que los datos recibidos del API Laravel cumplan el contrato de tipos esperado por Unity.
    /// Ver docs/unity-api-types-contract.md. Solo emite logs de advertencia; no corrige datos.
    /// Llamar opcionalmente en desarrollo para detectar incompatibilidades.
    /// </summary>
    public static class UnityApiTypesValidator
    {
        public static bool ValidateDialogoEstado(DialogoEstado estado, bool logWarnings = true)
        {
            if (estado == null) return false;

            bool ok = true;

            // tiempo_transcurrido debe ser un número válido (float); si falló la deserialización podría ser 0 por defecto
            if (float.IsNaN(estado.tiempo_transcurrido) || estado.tiempo_transcurrido < 0f)
            {
                if (logWarnings)
                    Debug.LogWarning($"[UnityApiTypesValidator] DialogoEstado.tiempo_transcurrido inválido: {estado.tiempo_transcurrido}. La API debe enviar un float (segundos).");
                ok = false;
            }

            // Si hay diálogo activo, nodo_actual no debería ser null
            if (estado.dialogo_activo && estado.nodo_actual == null)
            {
                if (logWarnings)
                    Debug.LogWarning("[UnityApiTypesValidator] DialogoEstado.dialogo_activo=true pero nodo_actual es null. Revisar contrato Laravel-Unity.");
                ok = false;
            }

            if (estado.dialogo_activo && estado.nodo_actual != null && estado.nodo_actual.rol_hablando == null)
            {
                if (logWarnings)
                    Debug.LogWarning("[UnityApiTypesValidator] DialogoEstado.nodo_actual.rol_hablando es null con diálogo activo. Revisar contrato.");
                ok = false;
            }

            return ok;
        }

        public static void ValidateRespuestasResponse(RespuestasResponse data, bool logWarnings = true)
        {
            if (data == null) return;

            if (data.respuestas != null)
            {
                for (int i = 0; i < data.respuestas.Count; i++)
                {
                    var r = data.respuestas[i];
                    if (r.id <= 0 && logWarnings)
                        Debug.LogWarning($"[UnityApiTypesValidator] RespuestasResponse.respuestas[{i}].id no válido: {r.id}. La API debe enviar int.");
                }
            }
        }
    }
}
