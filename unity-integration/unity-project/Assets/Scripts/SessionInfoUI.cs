using UnityEngine;
using UnityEngine.UI;
using TMPro;
using JuiciosSimulator.API;

namespace JuiciosSimulator.UI
{
    /// <summary>
    /// UI para mostrar información de la sesión actual
    /// </summary>
    public class SessionInfoUI : MonoBehaviour
    {
        [Header("UI Elements")]
        public TextMeshProUGUI sessionNameText;
        public TextMeshProUGUI roleNameText;
        public TextMeshProUGUI statusText;
        public Image roleIcon;

        [Header("Configuración")]
        public bool showDebugInfo = true;

        private SessionData currentSessionData;

        void Start()
        {
            // Suscribirse a eventos de LaravelAPI
            LaravelAPI.OnActiveSessionReceived += OnActiveSessionReceived;

            // Inicializar UI
            UpdateUI();
        }

        void OnDestroy()
        {
            // Desuscribirse de eventos
            LaravelAPI.OnActiveSessionReceived -= OnActiveSessionReceived;
        }

        private void OnActiveSessionReceived(SessionData sessionData)
        {
            currentSessionData = sessionData;
            UpdateUI();
        }

        private void UpdateUI()
        {
            if (currentSessionData != null)
            {
                // Actualizar información de la sesión
                if (sessionNameText != null)
                {
                    sessionNameText.text = $"Sesión: {currentSessionData.session.nombre}";
                }

                // Actualizar información del rol
                if (roleNameText != null)
                {
                    roleNameText.text = $"Rol: {currentSessionData.role.nombre}";
                }

                // Actualizar estado
                if (statusText != null)
                {
                    statusText.text = $"Estado: {currentSessionData.session.estado}";
                }

                // Actualizar icono del rol
                if (roleIcon != null)
                {
                    // Configurar color del rol
                    Color roleColor = GetRoleColor(currentSessionData.role.color);
                    roleIcon.color = roleColor;
                }
            }
            else
            {
                // Mostrar información por defecto
                if (sessionNameText != null)
                {
                    sessionNameText.text = "Sesión: No disponible";
                }

                if (roleNameText != null)
                {
                    roleNameText.text = "Rol: No asignado";
                }

                if (statusText != null)
                {
                    statusText.text = "Estado: Desconectado";
                }
            }
        }

        private Color GetRoleColor(string colorHex)
        {
            if (ColorUtility.TryParseHtmlString(colorHex, out Color color))
            {
                return color;
            }
            return Color.white;
        }

        public void ToggleVisibility()
        {
            gameObject.SetActive(!gameObject.activeSelf);
        }

        public void ShowSessionInfo()
        {
            gameObject.SetActive(true);
        }

        public void HideSessionInfo()
        {
            gameObject.SetActive(false);
        }

        public string GetSessionStatus()
        {
            if (currentSessionData != null)
            {
                return $"Sesión: {currentSessionData.session.nombre}\n" +
                       $"Rol: {currentSessionData.role.nombre}\n" +
                       $"Estado: {currentSessionData.session.estado}";
            }
            return "No hay sesión activa";
        }
    }
}