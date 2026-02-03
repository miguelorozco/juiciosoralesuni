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
        // Verificar que el labelRole esté asignado
        if (labelRole == null)
        {
            Debug.LogWarning("[RoleLabelDisplay] labelRole no está asignado en " + gameObject.name);
            // Intentar encontrar el TextMeshPro hijo
            labelRole = GetComponentInChildren<TextMeshPro>();
            if (labelRole == null)
            {
                Debug.LogError("[RoleLabelDisplay] No se encontró TextMeshPro en " + gameObject.name);
                enabled = false; // Desactivar el script si no hay label
                return;
            }
        }

        UpdateRoleLabel();
    }


    void UpdateRoleLabel()
    {
        // Verificar que labelRole esté disponible
        if (labelRole == null)
        {
            return;
        }

        // Primero intentar obtener el rol de las Custom Properties de Photon
        object rol;
        if (photonView != null && photonView.Owner != null && photonView.Owner.CustomProperties.TryGetValue("Rol", out rol))
        {
            Debug.Log("[RoleLabelDisplay] Rol desde Custom Properties: " + rol.ToString());
            labelRole.text = rol.ToString();
        }
        else
        {
            // Si no hay Custom Properties, obtener el rol del nombre del GameObject
            string objectName = gameObject.name;
            if (objectName.StartsWith("Player_"))
            {
                string roleName = objectName.Replace("Player_", "").Replace("(Clone)", "").Trim();
                Debug.Log("[RoleLabelDisplay] Rol desde nombre del GameObject: " + roleName);
                labelRole.text = roleName;
            }
            else
            {
                // Valor por defecto si no se puede determinar
                labelRole.text = "ROLE";
                Debug.LogWarning("[RoleLabelDisplay] No se pudo determinar el rol para: " + objectName);
            }
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
