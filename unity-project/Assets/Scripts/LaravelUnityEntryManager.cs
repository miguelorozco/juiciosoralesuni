using System.Collections;
using UnityEngine;
using Photon.Pun;
using JuiciosSimulator.API;

/// <summary>
/// Recibe los datos de sesión/usuario/rol enviados por JavaScript (game.blade.php)
/// cuando el usuario entra con token. Debe existir un GameObject con este nombre en la escena
/// para que SendMessage('LaravelUnityEntryManager', 'ReceiveLaravelData', json) funcione.
/// </summary>
public class LaravelUnityEntryManager : MonoBehaviour
{
    [System.Serializable]
    public class UserPayload
    {
        public int id;
        public string name;
        public string email;
    }

    [System.Serializable]
    public class SessionPayloadInner
    {
        public int id;
        public string nombre;
        public string estado;
    }

    [System.Serializable]
    public class RolePayload
    {
        public int id;
        public string nombre;
        public string descripcion;
    }

    [System.Serializable]
    public class SessionPayload
    {
        public UserPayload user;
        public SessionPayloadInner session;
        public RolePayload role;
        /// <summary>Token para llamadas API (Bearer). Enviado desde la página WebGL.</summary>
        public string token;
        /// <summary>URL base del API (ej. http://localhost/api). Mismo origen que la página, sin puerto.</summary>
        public string baseUrl;
    }

    private void Awake()
    {
        if (GameObject.Find("LiveKitManager") == null)
        {
            var go = new GameObject("LiveKitManager");
            go.AddComponent<LiveKitManager>();
            DontDestroyOnLoad(go);
        }
    }

    /// <summary>Llamado por SendMessage desde JavaScript con el JSON de sesión/usuario/rol.</summary>
    public void ReceiveLaravelData(string jsonData)
    {
        if (string.IsNullOrEmpty(jsonData))
        {
            Debug.LogWarning("[LaravelUnityEntryManager] ReceiveLaravelData recibió JSON vacío.");
            return;
        }

        try
        {
            var payload = JsonUtility.FromJson<SessionPayload>(jsonData);
            if (payload?.user == null || payload.session == null || payload.role == null)
            {
                Debug.LogWarning("[LaravelUnityEntryManager] JSON incompleto (falta user/session/role).");
                return;
            }

            int userId = payload.user.id;
            int sessionId = payload.session.id;
            int roleId = payload.role.id;
            string roleNombre = payload.role.nombre ?? "";

            LaravelSessionData.Set(userId, sessionId, roleId, roleNombre);
            Debug.Log($"[LaravelUnityEntryManager] Datos recibidos: usuario={userId}, sesión={sessionId}, rol={roleId} ({roleNombre})");

            if (!string.IsNullOrEmpty(payload.token))
            {
                UnityBridgeConfig.SetToken(payload.token);
                Debug.Log("[LaravelUnityEntryManager] Token de API configurado para UnityApiClient.");
            }

            if (!string.IsNullOrEmpty(payload.baseUrl))
            {
                UnityBridgeConfig.SetBaseUrl(payload.baseUrl);
                Debug.Log("[LaravelUnityEntryManager] BaseUrl de API configurada: " + payload.baseUrl);
            }

            AplicarIdentidadALocalPlayer();
            StartCoroutine(ReintentarAplicarIdentidadCuandoJugadorAparezca());
        }
        catch (System.Exception e)
        {
            Debug.LogError($"[LaravelUnityEntryManager] Error parseando JSON: {e.Message}");
        }
    }

    /// <summary>Busca el jugador local (PhotonView.IsMine) y actualiza su PlayerIdentity y RoleLabel.</summary>
    private void AplicarIdentidadALocalPlayer()
    {
        var identities = FindObjectsOfType<PlayerIdentity>();
        foreach (var identity in identities)
        {
            var pv = identity.GetComponentInParent<PhotonView>();
            if (pv == null || !pv.IsMine) continue;

            identity.rolId = LaravelSessionData.RoleId;
            identity.nombreRol = LaravelSessionData.RoleNombre;
            identity.usuarioId = LaravelSessionData.UserId;

            var roleLabel = identity.GetComponentInChildren<RoleLabel>(true);
            if (roleLabel != null)
                roleLabel.ActualizarTexto();

            Debug.Log($"[LaravelUnityEntryManager] PlayerIdentity actualizado: rol={identity.nombreRol}, usuarioId={identity.usuarioId}");
            break;
        }

        var dialogoManager = FindObjectOfType<DialogoManager>();
        if (dialogoManager != null)
        {
            dialogoManager.sesionJuicioId = LaravelSessionData.SessionId;
            dialogoManager.usuarioId = LaravelSessionData.UserId;
            Debug.Log($"[LaravelUnityEntryManager] DialogoManager actualizado: sesion={LaravelSessionData.SessionId}, usuarioId={LaravelSessionData.UserId}");
            dialogoManager.RefrescarEstado();
        }
    }

    /// <summary>Reintenta aplicar identidad cuando el jugador se instancia (después de Photon).</summary>
    private IEnumerator ReintentarAplicarIdentidadCuandoJugadorAparezca()
    {
        if (!LaravelSessionData.HasData) yield break;

        float[] delays = { 0.5f, 1.5f, 3f };
        foreach (float d in delays)
        {
            yield return new WaitForSeconds(d);
            if (!LaravelSessionData.HasData) yield break;
            AplicarIdentidadALocalPlayer();
        }
    }
}
