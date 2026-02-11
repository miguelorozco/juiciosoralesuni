using UnityEngine;
using TMPro;
using Photon.Pun;

/// <summary>
/// Muestra encima de la cabeza del jugador el rol asignado (desde PlayerIdentity) o "Invitado" si no hay rol.
/// Solo visible para OTROS usuarios: el dueño del personaje no ve su propio label.
/// Debe estar en un GameObject con Canvas (World Space) y un hijo con TextMeshProUGUI.
/// </summary>
[RequireComponent(typeof(Canvas))]
public class RoleLabel : MonoBehaviour
{
    [Tooltip("Altura sobre la cabeza (si no se usa punto de anclaje)")]
    public float alturaSobreCabeza = 2.2f;

    [Tooltip("Si está activo, el dueño del personaje también ve el label (útil para pruebas en solitario). Si no, solo otros jugadores lo ven.")]
    public bool mostrarTambienAlPropietario;

    private Canvas _canvas;
    private TMP_Text _label;
    private PlayerIdentity _identity;

    private void Awake()
    {
        var photonView = GetComponentInParent<PhotonView>();
        if (photonView != null && photonView.IsMine && !mostrarTambienAlPropietario)
        {
            gameObject.SetActive(false);
            return;
        }

        _canvas = GetComponent<Canvas>();
        _label = GetComponentInChildren<TMP_Text>(true);

        if (_canvas != null)
        {
            _canvas.renderMode = RenderMode.WorldSpace;
            _canvas.worldCamera = Camera.main;
            var rt = transform as RectTransform;
            if (rt != null)
                rt.localScale = new Vector3(0.01f, 0.01f, 0.01f);
        }
    }

    private void Start()
    {
        if (!gameObject.activeInHierarchy) return;

        _identity = GetComponentInParent<PlayerIdentity>();
        ActualizarTexto();
    }

    private void LateUpdate()
    {
        if (_canvas != null && Camera.main != null && _canvas.worldCamera != Camera.main)
            _canvas.worldCamera = Camera.main;
    }

    /// <summary>Actualiza el texto del label según PlayerIdentity (rol o "Invitado").</summary>
    public void ActualizarTexto()
    {
        if (_label == null) return;

        string texto = "Invitado";
        if (_identity != null)
        {
            if (!string.IsNullOrEmpty(_identity.nombreRol))
                texto = _identity.nombreRol;
            else if (_identity.TieneRolAsignado)
                texto = "Rol " + _identity.rolId;
        }

        _label.text = texto;
    }
}
