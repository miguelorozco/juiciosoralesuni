using UnityEngine;
using Photon.Pun;

/// <summary>
/// Identidad del jugador en la sesión: rol asignado para el sistema de diálogos y otros sistemas.
/// Se rellena desde Laravel al entrar con token (LaravelUnityEntryManager) o en Start desde LaravelSessionData.
/// </summary>
public class PlayerIdentity : MonoBehaviour
{
    [Header("Rol asignado")]
    [Tooltip("ID del rol en el backend (coincide con el rol de la sesión de juicio)")]
    public int rolId = -1;

    [Tooltip("Nombre del rol para mostrar (opcional)")]
    public string nombreRol = "";

    /// <summary>ID del usuario en el backend (para diálogos/sesión). -1 si no asignado.</summary>
    [Header("Usuario (opcional)")]
    [Tooltip("ID del usuario en el backend. Si es -1, DialogoManager puede usar el suyo.")]
    public int usuarioId = -1;

    public bool TieneRolAsignado => rolId >= 0;

    private void Start()
    {
        var pv = GetComponentInParent<PhotonView>();
        if (pv == null || !pv.IsMine) return;
        if (!LaravelSessionData.HasData) return;
        if (usuarioId >= 0) return;

        rolId = LaravelSessionData.RoleId;
        nombreRol = LaravelSessionData.RoleNombre;
        usuarioId = LaravelSessionData.UserId;

        var roleLabel = GetComponentInChildren<RoleLabel>(true);
        if (roleLabel != null)
            roleLabel.ActualizarTexto();
    }
}
