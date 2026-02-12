using UnityEngine;
using Photon.Pun;

/// <summary>
/// Identidad del jugador en la sesión: rol asignado para el sistema de diálogos y otros sistemas.
/// Se rellena desde Laravel al entrar con token (LaravelUnityEntryManager) o en Start desde LaravelSessionData.
/// En jugadores REMOTOS, rol y nombre se reciben por Photon (OnPhotonSerializeView) para que los demás vean el label correcto.
/// </summary>
public class PlayerIdentity : MonoBehaviour, IPunObservable
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
        SincronizarDesdeLaravelSiSoyLocal();
    }

    private void Update()
    {
        // Si el rol aún no se asignó pero ya hay datos de Laravel (p. ej. llegaron después del spawn), aplicar
        if (!TieneRolAsignado && LaravelSessionData.HasData)
            SincronizarDesdeLaravelSiSoyLocal();
    }

    /// <summary>Rellena rol/usuario desde LaravelSessionData si este objeto es el jugador local (PhotonView.IsMine).</summary>
    private void SincronizarDesdeLaravelSiSoyLocal()
    {
        var pv = GetComponentInParent<PhotonView>();
        if (pv == null || !pv.IsMine) return;
        if (!LaravelSessionData.HasData) return;
        if (rolId >= 0 && usuarioId >= 0) return;

        rolId = LaravelSessionData.RoleId;
        nombreRol = LaravelSessionData.RoleNombre;
        usuarioId = LaravelSessionData.UserId;

        UnityDebugLog.ToLaravel("player_identity_sync", "PlayerIdentity rellenado desde LaravelSessionData (Start/Update)", new System.Collections.Generic.Dictionary<string, object> {
            { "role_id", rolId },
            { "role_nombre", nombreRol ?? "" },
            { "user_id", usuarioId }
        });

        var roleLabel = GetComponentInChildren<RoleLabel>(true);
        if (roleLabel != null)
            roleLabel.ActualizarTexto();
    }

    /// <summary>Sync del rol para que los otros jugadores vean el nombre correcto encima del personaje (no "Invitado").</summary>
    public void OnPhotonSerializeView(PhotonStream stream, PhotonMessageInfo info)
    {
        if (stream.IsWriting)
        {
            stream.SendNext(rolId);
            stream.SendNext(nombreRol ?? "");
        }
        else
        {
            rolId = (int)stream.ReceiveNext();
            nombreRol = (string)stream.ReceiveNext();
            var roleLabel = GetComponentInChildren<RoleLabel>(true);
            if (roleLabel != null)
                roleLabel.ActualizarTexto();
        }
    }
}
