using System.Collections.Generic;
using UnityEngine;

public static class RoleColorManager
{
    // Diccionario de colores por rol (debe ser sincronizado desde Laravel)
    public static Dictionary<string, Color> RoleColors = new Dictionary<string, Color>
    {
        {"Juez", HexToColor("#8B4513")},
        {"Fiscal", HexToColor("#DC143C")},
        {"Defensa", HexToColor("#4169E1")},
        {"Testigo1", HexToColor("#32CD32")},
        {"Testigo2", HexToColor("#32CD32")},
        {"Policía1", HexToColor("#000080")},
        {"Policía2", HexToColor("#000080")},
        {"Psicólogo", HexToColor("#9370DB")},
        {"Acusado", HexToColor("#FF6347")},
        {"Secretario", HexToColor("#696969")},
        {"Abogado1", HexToColor("#4169E1")},
        {"Abogado2", HexToColor("#4169E1")},
        {"Perito1", HexToColor("#9370DB")},
        {"Perito2", HexToColor("#9370DB")},
        {"Victima", HexToColor("#FF1493")},
        {"Acusador", HexToColor("#FF8C00")},
        {"Periodista", HexToColor("#FFD700")},
        {"Publico1", HexToColor("#808080")},
        {"Publico2", HexToColor("#808080")},
        {"Observador", HexToColor("#708090")}
    };

    /// <summary>
    /// Actualiza el diccionario de colores desde la respuesta de la API de Laravel
    /// </summary>
    public static void UpdateColorsFromApi(Dictionary<string, string> apiRoleColors)
    {
        foreach (var kvp in apiRoleColors)
        {
            RoleColors[kvp.Key] = HexToColor(kvp.Value);
        }
    }

    public static Color GetColorForRole(string role)
    {
        if (RoleColors.TryGetValue(role, out var color))
            return color;
        return Color.white;
    }

    public static Color HexToColor(string hex)
    {
        Color color;
        if (ColorUtility.TryParseHtmlString(hex, out color))
            return color;
        return Color.white;
    }
}