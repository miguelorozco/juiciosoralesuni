using UnityEngine;

/// <summary>
/// Almacena los datos de sesión/usuario/rol recibidos desde Laravel (JavaScript → ReceiveLaravelData).
/// Los usa PlayerIdentity para mostrar el rol correcto y DialogoManager para sesión y usuario.
/// </summary>
public static class LaravelSessionData
{
    public static int UserId { get; private set; } = -1;
    public static int SessionId { get; private set; } = -1;
    public static int RoleId { get; private set; } = -1;
    public static string RoleNombre { get; private set; } = "";

    public static bool HasData => UserId >= 0 && SessionId >= 0;

    public static void Set(int userId, int sessionId, int roleId, string roleNombre)
    {
        UserId = userId;
        SessionId = sessionId;
        RoleId = roleId;
        RoleNombre = roleNombre ?? "";
    }

    public static void Clear()
    {
        UserId = -1;
        SessionId = -1;
        RoleId = -1;
        RoleNombre = "";
    }
}
