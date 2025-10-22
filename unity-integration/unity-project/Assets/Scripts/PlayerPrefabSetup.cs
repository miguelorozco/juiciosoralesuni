using UnityEngine;
using Photon.Pun;
using TMPro;

[System.Serializable]
public class PlayerPrefabSetup : MonoBehaviour
{
    [Header("Configuración del Prefab")]
    public GameObject playerModel;
    public GameObject roleLabelPrefab;
    public Transform roleLabelSpawnPoint;

    [Header("Configuración de Red")]
    public PhotonView photonView;
    public PlayerController playerController;
    public PlayerInputHandler inputHandler;

    void Start()
    {
        SetupPlayerPrefab();
    }

    void SetupPlayerPrefab()
    {
        // Configurar PhotonView si no existe
        if (photonView == null)
        {
            photonView = GetComponent<PhotonView>();
            if (photonView == null)
            {
                photonView = gameObject.AddComponent<PhotonView>();
            }
        }

        // Configurar PlayerController si no existe
        if (playerController == null)
        {
            playerController = GetComponent<PlayerController>();
            if (playerController == null)
            {
                playerController = gameObject.AddComponent<PlayerController>();
            }
        }

        // Configurar PlayerInputHandler si no existe
        if (inputHandler == null)
        {
            inputHandler = GetComponent<PlayerInputHandler>();
            if (inputHandler == null)
            {
                inputHandler = gameObject.AddComponent<PlayerInputHandler>();
            }
        }

        // Crear modelo del jugador si no existe
        if (playerModel == null)
        {
            CreatePlayerModel();
        }

        // Crear label del rol si no existe
        if (roleLabelPrefab == null)
        {
            CreateRoleLabel();
        }

        // Configurar componentes
        ConfigureComponents();
    }

    void CreatePlayerModel()
    {
        // Crear un cilindro como modelo básico del jugador
        GameObject model = GameObject.CreatePrimitive(PrimitiveType.Cylinder);
        model.name = "PlayerModel";
        model.transform.SetParent(transform);
        model.transform.localPosition = Vector3.zero;
        model.transform.localScale = new Vector3(0.8f, 1.8f, 0.8f);

        // Configurar material
        Renderer renderer = model.GetComponent<Renderer>();
        if (renderer != null)
        {
            Material playerMaterial = new Material(Shader.Find("Standard"));
            playerMaterial.color = Color.blue;
            renderer.material = playerMaterial;
        }

        // Configurar collider
        CapsuleCollider collider = model.GetComponent<CapsuleCollider>();
        if (collider != null)
        {
            collider.isTrigger = false;
            collider.radius = 0.4f;
            collider.height = 1.8f;
        }

        playerModel = model;
    }

    void CreateRoleLabel()
    {
        // Crear canvas para el label del rol
        GameObject canvasObj = new GameObject("RoleCanvas");
        canvasObj.transform.SetParent(transform);
        canvasObj.transform.localPosition = new Vector3(0, 2.5f, 0);

        Canvas canvas = canvasObj.AddComponent<Canvas>();
        canvas.renderMode = RenderMode.WorldSpace;
        canvas.worldCamera = Camera.main;

        // Crear texto del rol
        GameObject textObj = new GameObject("RoleText");
        textObj.transform.SetParent(canvasObj.transform);
        textObj.transform.localPosition = Vector3.zero;

        TextMeshProUGUI roleText = textObj.AddComponent<TextMeshProUGUI>();
        roleText.text = "Rol";
        roleText.fontSize = 24;
        roleText.color = Color.white;
        roleText.alignment = TextAlignmentOptions.Center;

        // Configurar rect transform
        RectTransform rectTransform = textObj.GetComponent<RectTransform>();
        rectTransform.sizeDelta = new Vector2(200, 50);

        roleLabelPrefab = canvasObj;
        roleLabelSpawnPoint = canvasObj.transform;
    }

    void ConfigureComponents()
    {
        // Configurar PlayerController
        if (playerController != null)
        {
            playerController.roleLabel = roleLabelPrefab.GetComponentInChildren<TextMeshProUGUI>();
            playerController.roleCanvas = roleLabelPrefab.GetComponent<Canvas>();
            playerController.avatarPrefab = playerModel;
            playerController.avatarSpawnPoint = transform;
        }

        // Configurar PlayerInputHandler
        if (inputHandler != null)
        {
            inputHandler.playerController = playerController;
        }

        // Configurar PhotonView
        if (photonView != null)
        {
            photonView.ObservedComponents.Add(playerController);
            photonView.ObservedComponents.Add(inputHandler);
        }
    }
}
