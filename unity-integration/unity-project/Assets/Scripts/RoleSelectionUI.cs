using UnityEngine;
using UnityEngine.UI;
using Photon.Pun;
using Photon.Realtime;
using ExitGames.Client.Photon;
using TMPro;

public class RoleSelectionUI : MonoBehaviourPunCallbacks
{
    public GameObject roleButtonPrefab;
    public Transform buttonContainer;
    public Button startButton;
    public GameObject rolePanel;

    public GestionRedJugador gestionRedJugador;

    public TextMeshProUGUI labelRole;


    void Start()
    {
        // El botón de iniciar debe estar desactivado al principio
        startButton.interactable = false;
        // Asignamos la acción del botón de iniciar
        startButton.onClick.AddListener(() => gestionRedJugador.JoinRoom());

    }

    public void InitializeUI()
    {
        GenerateRoleButtons();
    }

    void GenerateRoleButtons()
    {
        string[] usedRoles = GetUsedRoles();
        //Listado de Roles disponibles

        foreach (string role in RoleData.Roles)
        {
            GameObject btnObj = Instantiate(roleButtonPrefab, buttonContainer);
            btnObj.GetComponentInChildren<TextMeshProUGUI>().text = role;

            Button btn = btnObj.GetComponent<Button>();
            if (System.Array.Exists(usedRoles, r => r == role))
            {
                btn.interactable = false;
            }
            else
            {
                btn.onClick.AddListener(() => OnRoleSelected(role));

            }
        }
    }

    string[] GetUsedRoles()
    {
        // Esta parte ahora se llama solo después de unirse al lobby,
        // por lo que PhotonNetwork.CurrentRoom no será nulo.
        if (PhotonNetwork.CurrentRoom != null)
        {
            object used;
            if (PhotonNetwork.CurrentRoom.CustomProperties.TryGetValue("UsedRoles", out used))
            {
                return (string[])used;
            }
        }
        return new string[0];
    }

    void OnRoleSelected(string selectedRole)
    {
        // Solo guardar el rol en las propiedades del jugador.
        // Esta información se usará más tarde cuando el jugador entre a la sala.
        Hashtable playerProps = new Hashtable() { { "Rol", selectedRole } };
        PhotonNetwork.LocalPlayer.SetCustomProperties(playerProps);
        labelRole.text = selectedRole;
        // Habilitar el botón de "Iniciar" para que el jugador pueda continuar.
        if (startButton != null)
        {
            startButton.interactable = true;
        }
    }
}


public static class RoleData
{
    public static readonly string[] Roles = new string[]
    {
        "Juez", "Fiscal", "Defensa", "Testigo1", "Testigo2", "Policía1", "Policía2", "Psicólogo", "Acusado", "Secretario",
        "Abogado1", "Abogado2", "Perito1", "Perito2", "Víctima", "Acusador", "Periodista", "Público1", "Público2", "Observador"
    };
}
