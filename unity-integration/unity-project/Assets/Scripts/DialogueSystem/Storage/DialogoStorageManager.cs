using System;
using System.Collections;
using System.Collections.Generic;
using System.IO;
using UnityEngine;
using DialogueSystem.Data;
using JuiciosSimulator.API;

namespace DialogueSystem.Storage
{
    /// <summary>
    /// Singleton que gestiona el almacenamiento de diálogos.
    /// Maneja ScriptableObjects, JSON y sincronización con Laravel.
    /// </summary>
    public class DialogoStorageManager : MonoBehaviour
    {
        [Header("Configuración")]
        /// <summary>
        /// Ruta base para ScriptableObjects (relativa a Assets).
        /// </summary>
        public string scriptableObjectsPath = "DialogueSystem/Data/ScriptableObjects";

        /// <summary>
        /// Ruta base para archivos JSON (relativa a Assets).
        /// </summary>
        public string jsonPath = "DialogueSystem/Data/JSON";

        /// <summary>
        /// Ruta base para recursos runtime (relativa a Resources).
        /// </summary>
        public string resourcesPath = "DialogueSystem/Data";

        /// <summary>
        /// Habilitar cache local de diálogos cargados.
        /// </summary>
        public bool enableCache = true;

        /// <summary>
        /// Tiempo de vida del cache en segundos (0 = sin expiración).
        /// </summary>
        public float cacheLifetime = 3600f; // 1 hora

        // Singleton
        public static DialogoStorageManager Instance { get; private set; }

        // Cache de diálogos cargados
        private Dictionary<int, CachedDialogo> cache = new Dictionary<int, CachedDialogo>();

        // Referencia a LaravelAPI para sincronización
        private LaravelAPI laravelAPI;

        // Eventos
        public static event Action<DialogoData> OnDialogoLoaded;
        public static event Action<DialogoData> OnDialogoSaved;
        public static event Action<int> OnDialogoImported;
        public static event Action<string> OnError;

        private void Awake()
        {
            if (Instance == null)
            {
                Instance = this;
                DontDestroyOnLoad(gameObject);
            }
            else
            {
                Destroy(gameObject);
            }
        }

        private void Start()
        {
            // Obtener referencia a LaravelAPI
            laravelAPI = LaravelAPI.Instance;
            if (laravelAPI == null)
            {
                laravelAPI = FindObjectOfType<LaravelAPI>();
            }

            // Crear estructura de carpetas si no existe
            CreateDirectoryStructure();
        }

        private void Update()
        {
            // Limpiar cache expirado
            if (enableCache && cacheLifetime > 0)
            {
                CleanExpiredCache();
            }
        }

        #region Estructura de Carpetas

        /// <summary>
        /// Crea la estructura de carpetas necesaria.
        /// </summary>
        private void CreateDirectoryStructure()
        {
            // Nota: En runtime, solo podemos crear carpetas en Application.persistentDataPath
            // Las carpetas de Assets solo se pueden crear en el editor
            #if UNITY_EDITOR
            string assetsPath = Application.dataPath;
            string soPath = Path.Combine(assetsPath, scriptableObjectsPath);
            string jsonPathFull = Path.Combine(assetsPath, jsonPath);

            if (!Directory.Exists(soPath))
            {
                Directory.CreateDirectory(soPath);
                UnityEditor.AssetDatabase.Refresh();
            }

            if (!Directory.Exists(jsonPathFull))
            {
                Directory.CreateDirectory(jsonPathFull);
                UnityEditor.AssetDatabase.Refresh();
            }
            #endif
        }

        #endregion

        #region Guardar Diálogos

        /// <summary>
        /// Guarda un diálogo como ScriptableObject (solo en editor).
        /// </summary>
        public void GuardarDialogo(DialogoData dialogo, string nombreArchivo = null)
        {
            if (dialogo == null)
            {
                OnError?.Invoke("No se puede guardar un diálogo null.");
                return;
            }

            #if UNITY_EDITOR
            if (string.IsNullOrEmpty(nombreArchivo))
            {
                nombreArchivo = $"Dialogo_{dialogo.id}_{dialogo.nombre.Replace(" ", "_")}.asset";
            }

            string path = $"Assets/{scriptableObjectsPath}/{nombreArchivo}";
            UnityEditor.AssetDatabase.CreateAsset(dialogo, path);
            UnityEditor.AssetDatabase.SaveAssets();
            UnityEditor.AssetDatabase.Refresh();

            // Actualizar cache
            if (enableCache)
            {
                UpdateCache(dialogo);
            }

            OnDialogoSaved?.Invoke(dialogo);
            Debug.Log($"Diálogo guardado: {path}");
            #else
            Debug.LogWarning("Guardar como ScriptableObject solo está disponible en el editor.");
            #endif
        }

        /// <summary>
        /// Guarda un diálogo como JSON en el sistema de archivos persistente.
        /// </summary>
        public void GuardarDialogoJSON(DialogoData dialogo, string nombreArchivo = null)
        {
            if (dialogo == null)
            {
                OnError?.Invoke("No se puede guardar un diálogo null.");
                return;
            }

            if (string.IsNullOrEmpty(nombreArchivo))
            {
                nombreArchivo = $"Dialogo_{dialogo.id}_{dialogo.nombre.Replace(" ", "_")}.json";
            }

            string json = ExportarAJSON(dialogo);
            string path = Path.Combine(Application.persistentDataPath, jsonPath, nombreArchivo);

            // Crear directorio si no existe
            string directory = Path.GetDirectoryName(path);
            if (!Directory.Exists(directory))
            {
                Directory.CreateDirectory(directory);
            }

            File.WriteAllText(path, json);

            // Actualizar cache
            if (enableCache)
            {
                UpdateCache(dialogo);
            }

            OnDialogoSaved?.Invoke(dialogo);
            Debug.Log($"Diálogo guardado como JSON: {path}");
        }

        #endregion

        #region Cargar Diálogos

        /// <summary>
        /// Carga un diálogo desde ScriptableObject (solo en editor).
        /// </summary>
        public DialogoData CargarDialogo(string dialogoId)
        {
            // Intentar cargar desde cache primero
            if (enableCache && int.TryParse(dialogoId, out int id))
            {
                if (cache.ContainsKey(id) && !IsCacheExpired(cache[id]))
                {
                    return cache[id].dialogo;
                }
            }

            #if UNITY_EDITOR
            string[] guids = UnityEditor.AssetDatabase.FindAssets($"t:{nameof(DialogoData)}");
            
            foreach (string guid in guids)
            {
                string path = UnityEditor.AssetDatabase.GUIDToAssetPath(guid);
                DialogoData dialogo = UnityEditor.AssetDatabase.LoadAssetAtPath<DialogoData>(path);
                
                if (dialogo != null && dialogo.id.ToString() == dialogoId)
                {
                    if (enableCache)
                    {
                        UpdateCache(dialogo);
                    }
                    
                    OnDialogoLoaded?.Invoke(dialogo);
                    return dialogo;
                }
            }
            #endif

            OnError?.Invoke($"No se encontró el diálogo con ID: {dialogoId}");
            return null;
        }

        /// <summary>
        /// Carga un diálogo desde un archivo JSON.
        /// </summary>
        public DialogoData CargarDesdeJSON(string jsonPath)
        {
            if (string.IsNullOrEmpty(jsonPath))
            {
                OnError?.Invoke("La ruta del JSON no puede estar vacía.");
                return null;
            }

            // Si es una ruta relativa, buscar en persistentDataPath
            string fullPath = jsonPath;
            if (!Path.IsPathRooted(jsonPath))
            {
                fullPath = Path.Combine(Application.persistentDataPath, jsonPath);
            }

            if (!File.Exists(fullPath))
            {
                OnError?.Invoke($"No se encontró el archivo JSON: {fullPath}");
                return null;
            }

            try
            {
                string json = File.ReadAllText(fullPath);
                DialogoData dialogo = ImportarDesdeJSON(json);

                if (dialogo != null && enableCache)
                {
                    UpdateCache(dialogo);
                }

                OnDialogoLoaded?.Invoke(dialogo);
                return dialogo;
            }
            catch (Exception e)
            {
                OnError?.Invoke($"Error al cargar JSON: {e.Message}");
                return null;
            }
        }

        /// <summary>
        /// Carga un diálogo desde Resources (runtime).
        /// </summary>
        public DialogoData CargarDesdeResources(string nombreRecurso)
        {
            if (string.IsNullOrEmpty(nombreRecurso))
            {
                OnError?.Invoke("El nombre del recurso no puede estar vacío.");
                return null;
            }

            string path = $"{resourcesPath}/{nombreRecurso}";
            TextAsset jsonAsset = Resources.Load<TextAsset>(path);

            if (jsonAsset == null)
            {
                OnError?.Invoke($"No se encontró el recurso: {path}");
                return null;
            }

            DialogoData dialogo = ImportarDesdeJSON(jsonAsset.text);

            if (dialogo != null && enableCache)
            {
                UpdateCache(dialogo);
            }

            OnDialogoLoaded?.Invoke(dialogo);
            return dialogo;
        }

        #endregion

        #region Importar/Exportar JSON

        /// <summary>
        /// Exporta un diálogo a formato JSON.
        /// </summary>
        public string ExportarAJSON(DialogoData dialogo)
        {
            if (dialogo == null)
            {
                OnError?.Invoke("No se puede exportar un diálogo null.");
                return null;
            }

            try
            {
                // Convertir a formato JSON compatible con Laravel
                DialogoJSONWrapper wrapper = DialogoJSONConverter.ConvertToJSON(dialogo);
                return JsonUtility.ToJson(wrapper, true);
            }
            catch (Exception e)
            {
                OnError?.Invoke($"Error al exportar a JSON: {e.Message}");
                return null;
            }
        }

        /// <summary>
        /// Importa un diálogo desde formato JSON.
        /// </summary>
        public DialogoData ImportarDesdeJSON(string json)
        {
            if (string.IsNullOrEmpty(json))
            {
                OnError?.Invoke("El JSON no puede estar vacío.");
                return null;
            }

            try
            {
                // Validar estructura JSON
                if (!ValidarEstructuraJSON(json))
                {
                    OnError?.Invoke("El JSON no tiene una estructura válida.");
                    return null;
                }

                // Convertir desde formato JSON de Laravel
                DialogoData dialogo = DialogoJSONConverter.ConvertFromJSON(json);
                return dialogo;
            }
            catch (Exception e)
            {
                OnError?.Invoke($"Error al importar desde JSON: {e.Message}");
                return null;
            }
        }

        /// <summary>
        /// Valida la estructura básica del JSON.
        /// </summary>
        private bool ValidarEstructuraJSON(string json)
        {
            try
            {
                // Verificar que contiene campos básicos
                return json.Contains("\"id\"") && 
                       json.Contains("\"nombre\"") && 
                       json.Contains("\"nodos\"");
            }
            catch
            {
                return false;
            }
        }

        #endregion

        #region Sincronización con Laravel

        /// <summary>
        /// Importa un diálogo desde Laravel por ID.
        /// </summary>
        public void ImportarDesdeLaravel(int dialogoId, Action<DialogoData> onComplete = null, Action<string> onError = null)
        {
            if (laravelAPI == null)
            {
                string error = "LaravelAPI no está disponible.";
                OnError?.Invoke(error);
                onError?.Invoke(error);
                return;
            }

            StartCoroutine(ImportarDesdeLaravelCoroutine(dialogoId, onComplete, onError));
        }

        private IEnumerator ImportarDesdeLaravelCoroutine(int dialogoId, Action<DialogoData> onComplete, Action<string> onError)
        {
            // Construir URL de la API
            string url = $"{laravelAPI.baseURL}/dialogos/{dialogoId}";

            using (UnityEngine.Networking.UnityWebRequest request = UnityEngine.Networking.UnityWebRequest.Get(url))
            {
                // Agregar headers de autenticación si hay token
                if (!string.IsNullOrEmpty(laravelAPI.authToken))
                {
                    request.SetRequestHeader("Authorization", $"Bearer {laravelAPI.authToken}");
                }

                request.SetRequestHeader("Content-Type", "application/json");
                request.SetRequestHeader("Accept", "application/json");

                yield return request.SendWebRequest();

                if (request.result == UnityEngine.Networking.UnityWebRequest.Result.Success)
                {
                    try
                    {
                        // La respuesta de Laravel viene como:
                        // { "success": true, "data": { ...datos del diálogo... }, "message": "..." }
                        // Necesitamos extraer el objeto "data" del JSON
                        string jsonResponse = request.downloadHandler.text;
                        
                        // Parsear la respuesta completa
                        var response = JsonUtility.FromJson<LaravelAPIResponseSimple>(jsonResponse);

                        if (response.success)
                        {
                            // Extraer el JSON del campo "data" y convertirlo a DialogoData
                            // Como JsonUtility no puede parsear el objeto data directamente si tiene Dictionary,
                            // usamos el método ConvertFromJSON que maneja esto
                            DialogoData dialogo = DialogoJSONConverter.ConvertFromJSON(jsonResponse);
                            
                            // Si el método anterior no funciona, intentar extraer solo el campo "data"
                            if (dialogo == null)
                            {
                                // Intentar parsear solo el campo data del JSON
                                // Esto requiere un poco de manipulación manual del JSON
                                int dataStart = jsonResponse.IndexOf("\"data\":");
                                if (dataStart > 0)
                                {
                                    // Extraer el objeto data del JSON
                                    // Nota: Esta es una solución simple, puede necesitar mejorarse
                                    string dataJson = ExtractDataFromResponse(jsonResponse);
                                    if (!string.IsNullOrEmpty(dataJson))
                                    {
                                        dialogo = DialogoJSONConverter.ConvertFromJSON(dataJson);
                                    }
                                }
                            }

                            // Actualizar cache
                            if (enableCache)
                            {
                                UpdateCache(dialogo);
                            }

                            OnDialogoImported?.Invoke(dialogoId);
                            OnDialogoLoaded?.Invoke(dialogo);
                            onComplete?.Invoke(dialogo);
                        }
                        else
                        {
                            string error = response.message ?? "Error desconocido al importar diálogo.";
                            OnError?.Invoke(error);
                            onError?.Invoke(error);
                        }
                    }
                    catch (Exception e)
                    {
                        string error = $"Error al parsear respuesta: {e.Message}";
                        OnError?.Invoke(error);
                        onError?.Invoke(error);
                    }
                }
                else
                {
                    string error = $"Error de conexión: {request.error}";
                    OnError?.Invoke(error);
                    onError?.Invoke(error);
                }
            }
        }

        /// <summary>
        /// Sincroniza un diálogo con Laravel (envía cambios al servidor).
        /// </summary>
        public void SincronizarConLaravel(DialogoData dialogo, Action<bool> onComplete = null, Action<string> onError = null)
        {
            if (dialogo == null)
            {
                string error = "No se puede sincronizar un diálogo null.";
                OnError?.Invoke(error);
                onError?.Invoke(error);
                return;
            }

            if (laravelAPI == null)
            {
                string error = "LaravelAPI no está disponible.";
                OnError?.Invoke(error);
                onError?.Invoke(error);
                return;
            }

            StartCoroutine(SincronizarConLaravelCoroutine(dialogo, onComplete, onError));
        }

        private IEnumerator SincronizarConLaravelCoroutine(DialogoData dialogo, Action<bool> onComplete, Action<string> onError)
        {
            // Convertir a formato JSON de Laravel
            DialogoJSONWrapper wrapper = DialogoJSONConverter.ConvertToJSON(dialogo);
            string json = JsonUtility.ToJson(wrapper);

            // Determinar si es crear o actualizar
            bool isUpdate = dialogo.id > 0;
            string method = isUpdate ? "PUT" : "POST";
            string url = isUpdate 
                ? $"{laravelAPI.baseURL}/dialogos/{dialogo.id}"
                : $"{laravelAPI.baseURL}/dialogos";

            using (UnityEngine.Networking.UnityWebRequest request = new UnityEngine.Networking.UnityWebRequest(url, method))
            {
                byte[] bodyRaw = System.Text.Encoding.UTF8.GetBytes(json);
                request.uploadHandler = new UnityEngine.Networking.UploadHandlerRaw(bodyRaw);
                request.downloadHandler = new UnityEngine.Networking.DownloadHandlerBuffer();
                request.SetRequestHeader("Content-Type", "application/json");
                request.SetRequestHeader("Accept", "application/json");

                if (!string.IsNullOrEmpty(laravelAPI.authToken))
                {
                    request.SetRequestHeader("Authorization", $"Bearer {laravelAPI.authToken}");
                }

                yield return request.SendWebRequest();

                if (request.result == UnityEngine.Networking.UnityWebRequest.Result.Success)
                {
                    try
                    {
                        var response = JsonUtility.FromJson<LaravelAPIResponse<DialogoJSONWrapper>>(request.downloadHandler.text);

                        if (response.success)
                        {
                            // Actualizar diálogo con datos del servidor
                            DialogoData updatedDialogo = DialogoJSONConverter.ConvertFromLaravelResponse(response.data);
                            
                            // Copiar datos actualizados
                            dialogo.id = updatedDialogo.id;
                            dialogo.version = updatedDialogo.version;
                            dialogo.fechaCreacion = updatedDialogo.fechaCreacion;

                            // Actualizar cache
                            if (enableCache)
                            {
                                UpdateCache(dialogo);
                            }

                            OnDialogoSaved?.Invoke(dialogo);
                            onComplete?.Invoke(true);
                        }
                        else
                        {
                            string error = response.message ?? "Error desconocido al sincronizar.";
                            OnError?.Invoke(error);
                            onError?.Invoke(error);
                            onComplete?.Invoke(false);
                        }
                    }
                    catch (Exception e)
                    {
                        string error = $"Error al parsear respuesta: {e.Message}";
                        OnError?.Invoke(error);
                        onError?.Invoke(error);
                        onComplete?.Invoke(false);
                    }
                }
                else
                {
                    string error = $"Error de conexión: {request.error}";
                    OnError?.Invoke(error);
                    onError?.Invoke(error);
                    onComplete?.Invoke(false);
                }
            }
        }

        #endregion

        #region Cache

        /// <summary>
        /// Actualiza el cache con un diálogo.
        /// </summary>
        private void UpdateCache(DialogoData dialogo)
        {
            if (dialogo == null || dialogo.id == 0)
                return;

            cache[dialogo.id] = new CachedDialogo
            {
                dialogo = dialogo,
                timestamp = Time.time
            };
        }

        /// <summary>
        /// Verifica si el cache está expirado.
        /// </summary>
        private bool IsCacheExpired(CachedDialogo cached)
        {
            if (cacheLifetime <= 0)
                return false;

            return (Time.time - cached.timestamp) > cacheLifetime;
        }

        /// <summary>
        /// Limpia el cache expirado.
        /// </summary>
        private void CleanExpiredCache()
        {
            List<int> keysToRemove = new List<int>();

            foreach (var kvp in cache)
            {
                if (IsCacheExpired(kvp.Value))
                {
                    keysToRemove.Add(kvp.Key);
                }
            }

            foreach (int key in keysToRemove)
            {
                cache.Remove(key);
            }
        }

        /// <summary>
        /// Limpia todo el cache.
        /// </summary>
        public void ClearCache()
        {
            cache.Clear();
        }

        /// <summary>
        /// Obtiene un diálogo del cache.
        /// </summary>
        public DialogoData GetFromCache(int dialogoId)
        {
            if (cache.ContainsKey(dialogoId) && !IsCacheExpired(cache[dialogoId]))
            {
                return cache[dialogoId].dialogo;
            }
            return null;
        }

        #endregion

        #region Clases Auxiliares

        [Serializable]
        private class CachedDialogo
        {
            public DialogoData dialogo;
            public float timestamp;
        }

        [Serializable]
        private class LaravelAPIResponse<T>
        {
            public bool success;
            public string message;
            public T data;
        }

        [Serializable]
        private class LaravelAPIResponseSimple
        {
            public bool success;
            public string message;
        }

        /// <summary>
        /// Extrae el objeto "data" del JSON de respuesta de Laravel.
        /// </summary>
        private string ExtractDataFromResponse(string jsonResponse)
        {
            try
            {
                // Buscar el inicio del objeto "data"
                int dataStart = jsonResponse.IndexOf("\"data\":");
                if (dataStart < 0)
                    return null;

                // Encontrar el inicio del objeto (después de "data":)
                dataStart = jsonResponse.IndexOf('{', dataStart);
                if (dataStart < 0)
                    return null;

                // Encontrar el final del objeto (contando llaves)
                int braceCount = 0;
                int dataEnd = dataStart;
                bool inString = false;

                for (int i = dataStart; i < jsonResponse.Length; i++)
                {
                    char c = jsonResponse[i];
                    
                    if (c == '"' && (i == 0 || jsonResponse[i - 1] != '\\'))
                    {
                        inString = !inString;
                    }
                    else if (!inString)
                    {
                        if (c == '{')
                            braceCount++;
                        else if (c == '}')
                        {
                            braceCount--;
                            if (braceCount == 0)
                            {
                                dataEnd = i + 1;
                                break;
                            }
                        }
                    }
                }

                if (dataEnd > dataStart)
                {
                    return jsonResponse.Substring(dataStart, dataEnd - dataStart);
                }
            }
            catch
            {
                // Si falla, retornar null
            }

            return null;
        }

        #endregion
    }
}
