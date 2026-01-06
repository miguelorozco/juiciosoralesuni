using System;
using System.Collections.Generic;
using System.Linq;
using UnityEngine;
using DialogueSystem.Data;

namespace DialogueSystem.Storage
{
    /// <summary>
    /// Convierte entre formatos JSON de Laravel y objetos Unity DialogoData.
    /// </summary>
    public static class DialogoJSONConverter
    {
        /// <summary>
        /// Convierte un DialogoData a formato JSON compatible con Laravel.
        /// </summary>
        public static DialogoJSONWrapper ConvertToJSON(DialogoData dialogo)
        {
            if (dialogo == null)
                return null;

            var wrapper = new DialogoJSONWrapper
            {
                id = dialogo.id,
                nombre = dialogo.nombre,
                descripcion = dialogo.descripcion,
                version = dialogo.version,
                fechaCreacion = dialogo.fechaCreacion.ToString("yyyy-MM-dd HH:mm:ss"),
                publico = dialogo.publico,
                estado = dialogo.estado,
                configuracion = dialogo.configuracion,
                metadata_unity = dialogo.metadataUnity,
                nodos = dialogo.nodos.Select(n => ConvertNodoToJSON(n)).ToList()
            };

            return wrapper;
        }

    /// <summary>
    /// Convierte un JSON de Laravel a DialogoData.
    /// Nota: JsonUtility no soporta Dictionary directamente, por lo que
    /// necesitamos usar un parser más avanzado o manejar los diccionarios manualmente.
    /// </summary>
    public static DialogoData ConvertFromJSON(string json)
    {
        try
        {
            // JsonUtility no soporta Dictionary<string, object> directamente
            // Necesitamos parsear manualmente o usar una librería externa
            // Por ahora, usamos un enfoque simplificado que funciona con campos básicos
            
            // Intentar parsear primero con la versión simple (sin Dictionary)
            // JsonUtility no soporta Dictionary<string, object> directamente
            var simpleWrapper = JsonUtility.FromJson<DialogoJSONWrapperSimple>(json);
            
            if (simpleWrapper != null)
            {
                return ConvertFromLaravelResponseSimple(simpleWrapper);
            }

            // Si falla, intentar con la versión completa (puede fallar si hay Dictionary)
            try
            {
                var wrapper = JsonUtility.FromJson<DialogoJSONWrapper>(json);
                if (wrapper != null)
                {
                    return ConvertFromLaravelResponse(wrapper);
                }
            }
            catch
            {
                // Ignorar error y continuar
            }

            Debug.LogError("No se pudo parsear el JSON. Asegúrate de que el formato sea correcto.");
            return null;
        }
        catch (Exception e)
        {
            Debug.LogError($"Error al convertir JSON: {e.Message}\n{e.StackTrace}");
            return null;
        }
    }

        /// <summary>
        /// Convierte un DialogoJSONWrapper (de Laravel) a DialogoData.
        /// </summary>
        public static DialogoData ConvertFromLaravelResponse(DialogoJSONWrapper wrapper)
        {
            if (wrapper == null)
                return null;

            DialogoData dialogo = ScriptableObject.CreateInstance<DialogoData>();
            dialogo.id = wrapper.id;
            dialogo.nombre = wrapper.nombre;
            dialogo.descripcion = wrapper.descripcion ?? "";
            dialogo.version = wrapper.version ?? "1.0.0";
            
            if (DateTime.TryParse(wrapper.fechaCreacion, out DateTime fecha))
            {
                dialogo.fechaCreacion = fecha;
            }
            else
            {
                dialogo.fechaCreacion = DateTime.Now;
            }

            dialogo.publico = wrapper.publico;
            dialogo.estado = wrapper.estado ?? "borrador";
            dialogo.configuracion = wrapper.configuracion ?? new Dictionary<string, object>();
            dialogo.metadataUnity = wrapper.metadata_unity ?? new Dictionary<string, object>();

            // Convertir nodos
            if (wrapper.nodos != null)
            {
                foreach (var nodoJSON in wrapper.nodos)
                {
                    NodoDialogo nodo = ConvertNodoFromJSON(nodoJSON);
                    if (nodo != null)
                    {
                        dialogo.AgregarNodo(nodo);
                    }
                }
            }

            // Construir conexiones desde respuestas
            dialogo.ConstruirConexionesDesdeRespuestas();

            return dialogo;
        }

        /// <summary>
        /// Convierte un DialogoJSONWrapperSimple (de Laravel) a DialogoData.
        /// Versión simplificada que no requiere Dictionary en el JSON.
        /// </summary>
        public static DialogoData ConvertFromLaravelResponseSimple(DialogoJSONWrapperSimple wrapper)
        {
            if (wrapper == null)
                return null;

            DialogoData dialogo = ScriptableObject.CreateInstance<DialogoData>();
            dialogo.id = wrapper.id;
            dialogo.nombre = wrapper.nombre ?? "";
            dialogo.descripcion = wrapper.descripcion ?? "";
            dialogo.version = wrapper.version ?? "1.0.0";
            
            if (DateTime.TryParse(wrapper.fechaCreacion, out DateTime fecha))
            {
                dialogo.fechaCreacion = fecha;
            }
            else
            {
                dialogo.fechaCreacion = DateTime.Now;
            }

            dialogo.publico = wrapper.publico;
            dialogo.estado = wrapper.estado ?? "borrador";
            dialogo.configuracion = new Dictionary<string, object>();
            dialogo.metadataUnity = new Dictionary<string, object>();

            // Convertir nodos
            if (wrapper.nodos != null)
            {
                foreach (var nodoJSON in wrapper.nodos)
                {
                    NodoDialogo nodo = ConvertNodoFromJSONSimple(nodoJSON);
                    if (nodo != null)
                    {
                        dialogo.AgregarNodo(nodo);
                    }
                }
            }

            // Construir conexiones desde respuestas
            dialogo.ConstruirConexionesDesdeRespuestas();

            return dialogo;
        }

        /// <summary>
        /// Convierte un NodoDialogo a formato JSON.
        /// </summary>
        private static NodoJSONWrapper ConvertNodoToJSON(NodoDialogo nodo)
        {
            if (nodo == null)
                return null;

            var wrapper = new NodoJSONWrapper
            {
                id = nodo.id,
                dialogo_id = nodo.dialogoId,
                titulo = nodo.titulo,
                contenido = nodo.contenido,
                tipo = ConvertTipoNodoToString(nodo.tipo),
                menu_text = nodo.menuText,
                instrucciones = nodo.instrucciones,
                rol_id = nodo.rolAsignadoId,
                conversant_id = nodo.conversantId,
                posicion_x = nodo.posicionX,
                posicion_y = nodo.posicionY,
                es_inicial = nodo.esInicial,
                es_final = nodo.esFinal,
                activo = nodo.activo,
                condiciones = nodo.condiciones,
                consecuencias = nodo.consecuencias,
                metadata = nodo.metadata,
                respuestas = nodo.respuestas.Select(r => ConvertRespuestaToJSON(r)).ToList()
            };

            return wrapper;
        }

        /// <summary>
        /// Convierte un NodoJSONWrapper a NodoDialogo (versión simple sin diccionarios).
        /// </summary>
        private static NodoDialogo ConvertNodoFromJSONSimple(NodoJSONWrapperSimple wrapper)
        {
            if (wrapper == null)
                return null;

            var nodo = new NodoDialogo
            {
                id = wrapper.id,
                dialogoId = wrapper.dialogo_id,
                titulo = wrapper.titulo ?? "",
                contenido = wrapper.contenido ?? "",
                tipo = ConvertStringToTipoNodo(wrapper.tipo),
                menuText = wrapper.menu_text ?? "",
                instrucciones = wrapper.instrucciones ?? "",
                rolAsignadoId = wrapper.rol_id,
                conversantId = wrapper.conversant_id,
                posicionX = wrapper.posicion_x,
                posicionY = wrapper.posicion_y,
                esInicial = wrapper.es_inicial,
                esFinal = wrapper.es_final,
                activo = wrapper.activo,
                condiciones = new Dictionary<string, object>(),
                consecuencias = new Dictionary<string, object>(),
                metadata = new Dictionary<string, object>()
            };

            // Convertir respuestas
            if (wrapper.respuestas != null && wrapper.respuestas.Count > 0)
            {
                foreach (var respuestaJSON in wrapper.respuestas)
                {
                    RespuestaDialogo respuesta = ConvertRespuestaFromJSONSimple(respuestaJSON);
                    if (respuesta != null)
                    {
                        nodo.AgregarRespuesta(respuesta);
                    }
                }
            }

            return nodo;
        }

        /// <summary>
        /// Convierte un NodoJSONWrapper a NodoDialogo.
        /// </summary>
        private static NodoDialogo ConvertNodoFromJSON(NodoJSONWrapper wrapper)
        {
            if (wrapper == null)
                return null;

            var nodo = new NodoDialogo
            {
                id = wrapper.id,
                dialogoId = wrapper.dialogo_id,
                titulo = wrapper.titulo,
                contenido = wrapper.contenido,
                tipo = ConvertStringToTipoNodo(wrapper.tipo),
                menuText = wrapper.menu_text,
                instrucciones = wrapper.instrucciones,
                rolAsignadoId = wrapper.rol_id,
                conversantId = wrapper.conversant_id,
                posicionX = wrapper.posicion_x,
                posicionY = wrapper.posicion_y,
                esInicial = wrapper.es_inicial,
                esFinal = wrapper.es_final,
                activo = wrapper.activo,
                condiciones = wrapper.condiciones ?? new Dictionary<string, object>(),
                consecuencias = wrapper.consecuencias ?? new Dictionary<string, object>(),
                metadata = wrapper.metadata ?? new Dictionary<string, object>()
            };

            // Convertir respuestas
            if (wrapper.respuestas != null)
            {
                foreach (var respuestaJSON in wrapper.respuestas)
                {
                    RespuestaDialogo respuesta = ConvertRespuestaFromJSON(respuestaJSON);
                    if (respuesta != null)
                    {
                        nodo.AgregarRespuesta(respuesta);
                    }
                }
            }

            return nodo;
        }

        /// <summary>
        /// Convierte una RespuestaDialogo a formato JSON.
        /// </summary>
        private static RespuestaJSONWrapper ConvertRespuestaToJSON(RespuestaDialogo respuesta)
        {
            if (respuesta == null)
                return null;

            var wrapper = new RespuestaJSONWrapper
            {
                id = respuesta.id,
                nodo_padre_id = respuesta.nodoOrigenId,
                nodo_siguiente_id = respuesta.nodoDestinoId,
                texto = respuesta.texto,
                orden = respuesta.orden,
                color = ColorToHex(respuesta.color),
                puntuacion = respuesta.puntuacion,
                requiere_usuario_registrado = respuesta.requiereUsuarioRegistrado,
                requiere_rol_id = respuesta.requiereRolId,
                es_opcion_por_defecto = respuesta.esOpcionPorDefecto,
                condiciones = respuesta.condiciones,
                consecuencias = respuesta.consecuencias,
                metadata = respuesta.metadata
            };

            return wrapper;
        }

        /// <summary>
        /// Convierte un RespuestaJSONWrapper a RespuestaDialogo (versión simple sin diccionarios).
        /// </summary>
        private static RespuestaDialogo ConvertRespuestaFromJSONSimple(RespuestaJSONWrapperSimple wrapper)
        {
            if (wrapper == null)
                return null;

            var respuesta = new RespuestaDialogo
            {
                id = wrapper.id,
                nodoOrigenId = wrapper.nodo_padre_id,
                nodoDestinoId = wrapper.nodo_siguiente_id,
                texto = wrapper.texto ?? "",
                orden = wrapper.orden,
                color = HexToColor(wrapper.color),
                puntuacion = wrapper.puntuacion,
                requiereUsuarioRegistrado = wrapper.requiere_usuario_registrado,
                requiereRolId = wrapper.requiere_rol_id,
                esOpcionPorDefecto = wrapper.es_opcion_por_defecto,
                condiciones = new Dictionary<string, object>(),
                consecuencias = new Dictionary<string, object>(),
                metadata = new Dictionary<string, object>()
            };

            return respuesta;
        }

        /// <summary>
        /// Convierte un RespuestaJSONWrapper a RespuestaDialogo.
        /// </summary>
        private static RespuestaDialogo ConvertRespuestaFromJSON(RespuestaJSONWrapper wrapper)
        {
            if (wrapper == null)
                return null;

            var respuesta = new RespuestaDialogo
            {
                id = wrapper.id,
                nodoOrigenId = wrapper.nodo_padre_id,
                nodoDestinoId = wrapper.nodo_siguiente_id,
                texto = wrapper.texto,
                orden = wrapper.orden,
                color = HexToColor(wrapper.color),
                puntuacion = wrapper.puntuacion,
                requiereUsuarioRegistrado = wrapper.requiere_usuario_registrado,
                requiereRolId = wrapper.requiere_rol_id,
                esOpcionPorDefecto = wrapper.es_opcion_por_defecto,
                condiciones = wrapper.condiciones ?? new Dictionary<string, object>(),
                consecuencias = wrapper.consecuencias ?? new Dictionary<string, object>(),
                metadata = wrapper.metadata ?? new Dictionary<string, object>()
            };

            return respuesta;
        }

        /// <summary>
        /// Convierte TipoNodo a string (formato Laravel).
        /// </summary>
        private static string ConvertTipoNodoToString(TipoNodo tipo)
        {
            switch (tipo)
            {
                case TipoNodo.Inicio:
                    return "npc";
                case TipoNodo.Desarrollo:
                    return "npc";
                case TipoNodo.Decision:
                    return "pc";
                case TipoNodo.Final:
                    return "npc";
                case TipoNodo.Agrupacion:
                    return "agrupacion";
                default:
                    return "npc";
            }
        }

        /// <summary>
        /// Convierte string (formato Laravel) a TipoNodo.
        /// </summary>
        private static TipoNodo ConvertStringToTipoNodo(string tipo)
        {
            switch (tipo?.ToLower())
            {
                case "npc":
                    return TipoNodo.Desarrollo; // Por defecto, NPC es desarrollo
                case "pc":
                    return TipoNodo.Decision;
                case "agrupacion":
                    return TipoNodo.Agrupacion;
                default:
                    return TipoNodo.Desarrollo;
            }
        }

        /// <summary>
        /// Convierte Color a string hexadecimal.
        /// </summary>
        private static string ColorToHex(Color color)
        {
            return $"#{ColorUtility.ToHtmlStringRGBA(color)}";
        }

        /// <summary>
        /// Convierte string hexadecimal a Color.
        /// </summary>
        private static Color HexToColor(string hex)
        {
            if (string.IsNullOrEmpty(hex))
                return Color.white;

            if (hex.StartsWith("#"))
                hex = hex.Substring(1);

            if (ColorUtility.TryParseHtmlString($"#{hex}", out Color color))
                return color;

            return Color.white;
        }
    }

    #region Clases de Serialización JSON

    /// <summary>
    /// Wrapper para serializar/deserializar diálogos desde Laravel.
    /// </summary>
    [Serializable]
    public class DialogoJSONWrapper
    {
        public int id;
        public string nombre;
        public string descripcion;
        public string version;
        public string fechaCreacion;
        public bool publico;
        public string estado;
        public Dictionary<string, object> configuracion;
        public Dictionary<string, object> metadata_unity;
        public List<NodoJSONWrapper> nodos;
    }

    /// <summary>
    /// Wrapper para serializar/deserializar nodos desde Laravel.
    /// </summary>
    [Serializable]
    public class NodoJSONWrapper
    {
        public int id;
        public int dialogo_id;
        public string titulo;
        public string contenido;
        public string tipo;
        public string menu_text;
        public string instrucciones;
        public int? rol_id;
        public int? conversant_id;
        public int posicion_x;
        public int posicion_y;
        public bool es_inicial;
        public bool es_final;
        public bool activo;
        public Dictionary<string, object> condiciones;
        public Dictionary<string, object> consecuencias;
        public Dictionary<string, object> metadata;
        public List<RespuestaJSONWrapper> respuestas;
    }

    /// <summary>
    /// Wrapper para serializar/deserializar respuestas desde Laravel.
    /// </summary>
    [Serializable]
    public class RespuestaJSONWrapper
    {
        public int id;
        public int nodo_padre_id;
        public int nodo_siguiente_id;
        public string texto;
        public int orden;
        public string color;
        public int puntuacion;
        public bool requiere_usuario_registrado;
        public int? requiere_rol_id;
        public bool es_opcion_por_defecto;
        public Dictionary<string, object> condiciones;
        public Dictionary<string, object> consecuencias;
        public Dictionary<string, object> metadata;
    }

    #endregion

    #region Clases de Serialización JSON Simple (sin Dictionary)

    /// <summary>
    /// Wrapper simple para serializar/deserializar diálogos (sin Dictionary para JsonUtility).
    /// </summary>
    [Serializable]
    public class DialogoJSONWrapperSimple
    {
        public int id;
        public string nombre;
        public string descripcion;
        public string version;
        public string fechaCreacion;
        public bool publico;
        public string estado;
        public List<NodoJSONWrapperSimple> nodos;
    }

    /// <summary>
    /// Wrapper simple para serializar/deserializar nodos (sin Dictionary para JsonUtility).
    /// </summary>
    [Serializable]
    public class NodoJSONWrapperSimple
    {
        public int id;
        public int dialogo_id;
        public string titulo;
        public string contenido;
        public string tipo;
        public string menu_text;
        public string instrucciones;
        public int rol_id;
        public int conversant_id;
        public int posicion_x;
        public int posicion_y;
        public bool es_inicial;
        public bool es_final;
        public bool activo;
        public List<RespuestaJSONWrapperSimple> respuestas;
    }

    /// <summary>
    /// Wrapper simple para serializar/deserializar respuestas (sin Dictionary para JsonUtility).
    /// </summary>
    [Serializable]
    public class RespuestaJSONWrapperSimple
    {
        public int id;
        public int nodo_padre_id;
        public int nodo_siguiente_id;
        public string texto;
        public int orden;
        public string color;
        public int puntuacion;
        public bool requiere_usuario_registrado;
        public int requiere_rol_id;
        public bool es_opcion_por_defecto;
    }

    #endregion
}
