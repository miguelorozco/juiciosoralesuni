using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using Photon.Pun;

[DefaultExecutionOrder(-100)]
public class RedesJugador : MonoBehaviour
{
    public MonoBehaviour[] codigosQueIgnorar;


    private PhotonView photonView;

    private void Awake()
    {
        photonView = GetComponent<PhotonView>();
        if (photonView == null || photonView.IsMine)
            return;
        // Deshabilitar cámara y listener del jugador remoto lo antes posible, para que
        // FindGameObjectWithTag("MainCamera") en otros scripts solo encuentre la nuestra.
        var cam = GetComponentInChildren<Camera>(true);
        if (cam != null)
            cam.enabled = false;
        var listener = GetComponentInChildren<AudioListener>(true);
        if (listener != null)
            listener.enabled = false;
    }

    void Start()
    {
        if (photonView == null)
            photonView = GetComponent<PhotonView>();
        if (!photonView.IsMine)
        {
            foreach (var codigo in codigosQueIgnorar)
            {
                if (codigo != null)
                    codigo.enabled = false;
            }
        }
    }

    // Update is called once per frame
    void Update()
    {

    }
}
