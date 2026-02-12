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
                UnityDebugLog.ToLaravel("identity_data_rejected", "JSON incompleto: falta user, session o role", new System.Collections.Generic.Dictionary<string, object> {
                    { "has_user", payload?.user != null },
                    { "has_session", payload?.session != null },
                    { "has_role", payload?.role != null }
                });
                return;
            }

            int userId = payload.user.id;
            int sessionId = payload.session.id;
            int roleId = payload.role.id;
            string roleNombre = payload.role.nombre ?? "";

            LaravelSessionData.Set(userId, sessionId, roleId, roleNombre);
            Debug.Log($"[LaravelUnityEntryManager] Datos recibidos: usuario={userId}, sesión={sessionId}, rol={roleId} ({roleNombre})");
            UnityDebugLog.ToLaravel("identity_data_received", "ReceiveLaravelData: user/session/role asignados en LaravelSessionData", new System.Collections.Generic.Dictionary<string, object> {
                { "user_id", userId },
                { "session_id", sessionId },
                { "role_id", roleId },
                { "role_nombre", roleNombre ?? "" }
            });

            // Marcar al usuario como confirmado en la sesión para que el API de diálogo lo incluya
            // en participantes y muestre "Tu turno" cuando corresponda (evita "Esperando a Juez" siendo el Juez).
            if (UnityApiClient.Instance != null)
                UnityApiClient.Instance.SesionesConfirmarRol(sessionId, _ => { });
            else
                StartCoroutine(ConfirmarRolCuandoApiEsteLista(sessionId));

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
            UnityDebugLog.ToLaravel("identity_data_error", "Excepción al parsear ReceiveLaravelData", new System.Collections.Generic.Dictionary<string, object> {
                { "error", e.Message }
            });
        }
    }

    /// <summary>Busca el jugador local (PhotonView.IsMine) y actualiza su PlayerIdentity y RoleLabel.</summary>
    private void AplicarIdentidadALocalPlayer()
    {
        var identities = FindObjectsOfType<PlayerIdentity>(true);
        bool localActualizado = false;
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

            Debug.Log($"[LaravelUnityEntryManager] PlayerIdentity actualizado: rol={identity.nombreRol} (id={identity.rolId}), usuarioId={identity.usuarioId}");
            UnityDebugLog.ToLaravel("identity_applied", "PlayerIdentity y RoleLabel actualizados en jugador local", new System.Collections.Generic.Dictionary<string, object> {
                { "role_id", identity.rolId },
                { "role_nombre", identity.nombreRol ?? "" },
                { "user_id", identity.usuarioId }
            });
            localActualizado = true;
            break;
        }

        if (!localActualizado)
        {
            var reason = identities.Length == 0 ? "no_player_identity_in_scene" : "no_local_player_is_mine";
            if (identities.Length == 0)
                Debug.LogWarning("[LaravelUnityEntryManager] No hay ningún PlayerIdentity en la escena. ¿El prefab del jugador tiene el componente?");
            else
                Debug.LogWarning("[LaravelUnityEntryManager] Ningún PlayerIdentity es el jugador local (IsMine). Se reintentará en 0.3s, 0.8s, 1.5s, 3s y 5s.");
            UnityDebugLog.ToLaravel("identity_not_applied", reason, new System.Collections.Generic.Dictionary<string, object> {
                { "identities_found", identities.Length },
                { "laravel_has_data", LaravelSessionData.HasData },
                { "laravel_user_id", LaravelSessionData.UserId },
                { "laravel_role_id", LaravelSessionData.RoleId }
            });
        }

        var dialogoManager = FindObjectOfType<DialogoManager>();
        if (dialogoManager != null)
        {
            dialogoManager.sesionJuicioId = LaravelSessionData.SessionId;
            dialogoManager.usuarioId = LaravelSessionData.UserId;
            Debug.Log($"[LaravelUnityEntryManager] DialogoManager actualizado: sesion={LaravelSessionData.SessionId}, usuarioId={LaravelSessionData.UserId}");
            UnityDebugLog.ToLaravel("dialogo_manager_updated", "DialogoManager sesion/usuario asignados", new System.Collections.Generic.Dictionary<string, object> {
                { "session_id", LaravelSessionData.SessionId },
                { "user_id", LaravelSessionData.UserId }
            });
            dialogoManager.RefrescarEstado();
        }
    }

    /// <summary>Llama a confirmar-rol cuando UnityApiClient esté disponible (por si llega antes que el API bridge).</summary>
    private IEnumerator ConfirmarRolCuandoApiEsteLista(int sessionId)
    {
        for (int i = 0; i < 20; i++)
        {
            yield return new WaitForSeconds(0.5f);
            if (UnityApiClient.Instance == null) continue;
            UnityApiClient.Instance.SesionesConfirmarRol(sessionId, _ => { });
            yield break;
        }
    }

    /// <summary>Reintenta aplicar identidad cuando el jugador se instancia (después de Photon).</summary>
    private IEnumerator ReintentarAplicarIdentidadCuandoJugadorAparezca()
    {
        if (!LaravelSessionData.HasData) yield break;

        float[] delays = { 0.3f, 0.8f, 1.5f, 3f, 5f, 7f, 10f, 15f };
        foreach (float d in delays)
        {
            yield return new WaitForSeconds(d);
            if (!LaravelSessionData.HasData) yield break;
            AplicarIdentidadALocalPlayer();
        }
    }
}
