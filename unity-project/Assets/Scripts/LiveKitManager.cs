using UnityEngine;

/// <summary>
/// Recibe eventos del chat de voz LiveKit desde JavaScript (game.blade.php).
/// Debe existir un GameObject con nombre exacto "LiveKitManager" en la escena para que
/// SendMessage('LiveKitManager', 'OnLiveKitEvent', json) desde la página funcione.
/// </summary>
public class LiveKitManager : MonoBehaviour
{
    [Header("Debug")]
    [Tooltip("Registrar en consola los eventos recibidos desde JS")]
    public bool logEvents;

    /// <summary>
    /// Llamado por SendMessage desde JavaScript con JSON: { "event": string, "data": object }
    /// </summary>
    public void OnLiveKitEvent(string json)
    {
        if (string.IsNullOrEmpty(json)) return;
        if (logEvents) Debug.Log("[LiveKitManager] OnLiveKitEvent: " + json);
        // Aquí se puede reaccionar a eventos (conectado, participante hablando, etc.)
    }
}
