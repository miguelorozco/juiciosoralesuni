using Photon.Pun;
using Photon.Realtime;
using UnityEngine;
using ExitGames.Client.Photon;

public class GestionRedJugador : MonoBehaviourPunCallbacks
{
    public GameObject rolSelectionPanel;
    public Camera uiCamara;

    void Start()
    {
        ConnectToPhoton();
    }

    void ConnectToPhoton()
    {
        if (!PhotonNetwork.IsConnected)
        {
            Debug.Log("Conectando a Photon...");
            PhotonNetwork.ConnectUsingSettings();
        }
    }

    public override void OnConnectedToMaster()
    {
        Debug.Log("Conectado al Master Server. Entrando al lobby...");
        PhotonNetwork.JoinLobby(); // NECESARIO
    }

    public override void OnJoinedLobby()
    {
        Debug.Log("Unido al Lobby. Esperando la selección de un rol.");

        rolSelectionPanel.SetActive(true);
        if (uiCamara != null)
        {
            uiCamara.enabled = true;
        }

        // Llama al método para inicializar la UI de roles
        if (rolSelectionPanel != null)
        {
            rolSelectionPanel.GetComponent<RoleSelectionUI>().InitializeUI();
        }
        //PhotonNetwork.JoinRandomRoom();
    }

    public void JoinRoom()
    {
        Debug.Log("Uniendo a la sala aleatoria...");
        PhotonNetwork.JoinRandomRoom();
    }

    public override void OnJoinRandomFailed(short returnCode, string message)
    {
        Debug.Log("No se pudo unir a una sala existente. Creando nueva sala...");
        RoomOptions roomOptions = new RoomOptions { MaxPlayers = 20 };
        string roomName = "Sala_" + Random.Range(1000, 9999);
        PhotonNetwork.CreateRoom(roomName, roomOptions, TypedLobby.Default);

    }

    public override void OnJoinedRoom()
    {
        Debug.Log("Unido a una sala. Instanciando jugador...");

        // Desactivar el panel de selección de rol
        if (rolSelectionPanel != null)
        {
            rolSelectionPanel.SetActive(false);
        }

        // Desactivar la camara de UI
        if (uiCamara != null)
        {
            uiCamara.enabled = false;
        }

        // 1. Obtener el rol que el jugador seleccionó en el lobby.
        object selectedRoleObject;
        if (PhotonNetwork.LocalPlayer.CustomProperties.TryGetValue("Rol", out selectedRoleObject))
        {
            string selectedRole = selectedRoleObject.ToString();

            // 2. Actualizar las propiedades de la sala con el rol usado.
            string[] usedRoles = GetUsedRoles();

            var newUsed = new string[usedRoles.Length + 1];
            usedRoles.CopyTo(newUsed, 0);
            newUsed[newUsed.Length - 1] = selectedRole;

            Hashtable roomProps = new Hashtable() { { "UsedRoles", newUsed } };
            PhotonNetwork.CurrentRoom.SetCustomProperties(roomProps);
        }

        // Instanciar el jugador
        Vector3 spawnPosition = new Vector3(-0.06f, 4.8f, -16.0f);
        Quaternion spawnRotation = Quaternion.Euler(0, 180, 0);
        PhotonNetwork.Instantiate("Player", spawnPosition, spawnRotation);

#if UNITY_WEBGL && !UNITY_EDITOR
    string roomId = PhotonNetwork.CurrentRoom.Name;
    int actorId = PhotonNetwork.LocalPlayer.ActorNumber;

    // Llamar a la función JavaScript con el RoomID y el número de jugador
    Application.ExternalCall("initVoiceCall", roomId, actorId);
#endif
    }

    // Obtener los roles usados en la sala
    string[] GetUsedRoles()
    {
        if (PhotonNetwork.CurrentRoom == null) return new string[0];

        object used;
        if (PhotonNetwork.CurrentRoom.CustomProperties.TryGetValue("UsedRoles", out used))
        {
            return (string[])used;
        }
        return new string[0];
    }





    // Unity -> JavaScript (Enviar PeerID)
    public void OnVoiceReady(string myPeerId)
    {
        Debug.Log("Mi PeerJS ID es: " + myPeerId);

        // Puedes compartirlo con los demás vía RPC o guardar localmente
        photonView.RPC("RecibirPeerId", RpcTarget.Others, myPeerId);
    }

    [PunRPC]
    public void RecibirPeerId(string peerId)
    {
#if UNITY_WEBGL && !UNITY_EDITOR
    Application.ExternalCall("callPeer", peerId);
#endif
    }


}


