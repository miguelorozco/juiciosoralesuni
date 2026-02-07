using UnityEngine;

namespace JuiciosSimulator.API
{
    /// <summary>
    /// Configuración del puente Unity-Laravel (API).
    /// La URL base y el token pueden asignarse desde código o desde WebGL (p. ej. via SendMessage desde JS).
    /// </summary>
    public static class UnityBridgeConfig
    {
        private static string _baseUrl = "http://localhost:8000/api";
        private static string _token = "";

        /// <summary>URL base del API (ej. http://localhost:8000/api).</summary>
        public static string BaseUrl
        {
            get => _baseUrl?.TrimEnd('/') ?? "";
            set => _baseUrl = value?.TrimEnd('/') ?? "";
        }

        /// <summary>Token JWT para autenticación (Bearer). En WebGL suele inyectarse desde la página.</summary>
        public static string Token
        {
            get => _token ?? "";
            set => _token = value ?? "";
        }

        /// <summary>Indica si hay token configurado.</summary>
        public static bool HasToken => !string.IsNullOrEmpty(_token);

        /// <summary>
        /// Establece la URL base. Pensado para ser llamado desde JavaScript en WebGL.
        /// </summary>
        public static void SetBaseUrl(string url)
        {
            BaseUrl = url;
        }

        /// <summary>
        /// Establece el token de autenticación. Pensado para ser llamado desde JavaScript en WebGL.
        /// </summary>
        public static void SetToken(string token)
        {
            Token = token;
        }

        /// <summary>Devuelve la URL completa para un path (path puede empezar con / o no).</summary>
        public static string GetFullUrl(string path)
        {
            path = path?.Trim() ?? "";
            if (path.StartsWith("/")) return BaseUrl + path;
            return string.IsNullOrEmpty(BaseUrl) ? path : BaseUrl + "/" + path;
        }
    }
}
