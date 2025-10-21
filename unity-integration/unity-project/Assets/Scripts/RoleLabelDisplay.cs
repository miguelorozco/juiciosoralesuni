using UnityEngine;
using TMPro;
using Photon.Pun;
using Photon.Realtime;
using ExitGames.Client.Photon;

public class RoleLabelDisplay : MonoBehaviourPunCallbacks
{
    public TextMeshPro labelRole;

    void Start()
    {
        UpdateRoleLabel();

    }


    void UpdateRoleLabel()
    {
        object rol;
        if (photonView.Owner.CustomProperties.TryGetValue("Rol", out rol))
        {
            Debug.Log("Rol asignado: " + rol.ToString());
            labelRole.text = rol.ToString();
        }
    }

    public override void OnPlayerPropertiesUpdate(Player targetPlayer, Hashtable changedProps)
    {
        if (targetPlayer == photonView.Owner && changedProps.ContainsKey("Rol"))
        {
            UpdateRoleLabel();
        }
    }
}
