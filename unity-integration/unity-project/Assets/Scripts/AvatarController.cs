using UnityEngine;
using TMPro;

public class AvatarController : MonoBehaviour
{
    [Header("Configuración del Avatar")]
    public string avatarRole = "";
    public Color roleColor = Color.white;

    [Header("Componentes UI")]
    public TextMeshPro roleLabel;
    public Canvas roleCanvas;

    [Header("Configuración Visual")]
    public Renderer avatarRenderer;
    public Material[] roleMaterials;

    void Start()
    {
        SetupAvatar();
    }

    public void SetRole(string role, Color color)
    {
        avatarRole = role;
        roleColor = color;
        SetupAvatar();
    }

    private void SetupAvatar()
    {
        // Configurar el label del rol
        if (roleLabel != null)
        {
            roleLabel.text = avatarRole;
            roleLabel.color = roleColor;
        }

        // Configurar el material del avatar
        if (avatarRenderer != null && roleMaterials != null)
        {
            Material roleMaterial = GetRoleMaterial(avatarRole);
            if (roleMaterial != null)
            {
                avatarRenderer.material = roleMaterial;
            }
        }

        // Configurar el canvas del rol
        if (roleCanvas != null)
        {
            roleCanvas.worldCamera = Camera.main;
        }
    }

    private Material GetRoleMaterial(string role)
    {
        if (roleMaterials == null) return null;

        switch (role.ToLower())
        {
            case "juez":
                return roleMaterials.Length > 0 ? roleMaterials[0] : null;
            case "fiscal":
                return roleMaterials.Length > 1 ? roleMaterials[1] : null;
            case "defensor":
                return roleMaterials.Length > 2 ? roleMaterials[2] : null;
            case "testigo":
                return roleMaterials.Length > 3 ? roleMaterials[3] : null;
            case "acusado":
                return roleMaterials.Length > 4 ? roleMaterials[4] : null;
            default:
                return roleMaterials.Length > 0 ? roleMaterials[0] : null;
        }
    }

    public string GetRole()
    {
        return avatarRole;
    }

    public Color GetRoleColor()
    {
        return roleColor;
    }
}
