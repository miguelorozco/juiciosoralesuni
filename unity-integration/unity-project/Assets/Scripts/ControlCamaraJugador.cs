using Photon.Pun;
using UnityEngine;

public class ControlCamaraJugador : MonoBehaviourPun
{
    private Camera camara;

    void Start()
    {
        // Busca la cámara hija automáticamente
        camara = GetComponentInChildren<Camera>();

        if (photonView.IsMine)
        {
            camara.enabled = true;
            AudioListener listener = camara.GetComponent<AudioListener>();
            if (listener != null) listener.enabled = true;
        }
        else
        {
            camara.enabled = false;
            AudioListener listener = camara.GetComponent<AudioListener>();
            if (listener != null) listener.enabled = false;
        }
    }
}
