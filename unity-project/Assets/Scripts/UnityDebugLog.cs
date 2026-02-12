using UnityEngine;
using JuiciosSimulator.API;

/// <summary>
/// Envía eventos de debug a Laravel para verlos en storage/logs/laravel.log con prefijo [Unity debug].
/// Usar para depurar asignación de rol, user id, clics en botones, etc.
/// </summary>
public static class UnityDebugLog
{
    public static void ToLaravel(string eventName, string message, object data = null)
    {
        var client = UnityApiClient.Instance;
        if (client != null)
            client.SendDebugLog(eventName, message, data);
        else
            Debug.Log($"[Unity debug] {eventName}: {message}");
    }
}
