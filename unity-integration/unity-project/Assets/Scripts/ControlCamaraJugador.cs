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
            if (listener != null)
            {
                listener.enabled = true;
                // Deshabilitar todos los demás AudioListeners
                DisableOtherAudioListeners(listener);
            }
        }
        else
        {
            camara.enabled = false;
            AudioListener listener = camara.GetComponent<AudioListener>();
            if (listener != null) listener.enabled = false;
        }
    }

    /// <summary>
    /// Deshabilita todos los AudioListeners excepto el proporcionado.
    /// </summary>
    private void DisableOtherAudioListeners(AudioListener keepActive)
    {
        AudioListener[] allListeners = FindObjectsOfType<AudioListener>();
        
        foreach (AudioListener listener in allListeners)
        {
            if (listener != keepActive && listener.enabled)
            {
                listener.enabled = false;
                Debug.Log($"[ControlCamaraJugador] AudioListener deshabilitado en: {listener.gameObject.name}");
            }
        }
    }
}
