using System;
using System.Collections.Generic;

namespace JuiciosSimulator.API
{
    /// <summary>Respuesta genérica del API Laravel.</summary>
    [Serializable]
    public class APIResponse<T>
    {
        public bool success;
        public string message;
        public T data;
    }

    [Serializable]
    public class LoginRequest
    {
        public string email;
        public string password;
        public string unity_version;
        public string unity_platform;
        public string device_id;
        public Dictionary<string, object> session_data;
    }

    [Serializable]
    public class LoginResponse
    {
        public string token;
        public string token_type;
        public int expires_in;
        public UserData user;
        public Dictionary<string, object> unity_info;
        public string server_time;
    }

    [Serializable]
    public class UserData
    {
        public int id;
        public string name;
        public string apellido;
        public string email;
        public string tipo;
        public bool activo;
        public Dictionary<string, object> configuracion;
    }

    [Serializable]
    public class ServerStatus
    {
        public string server_status;
        public string api_version;
        public bool unity_support;
        public string server_time;
        public string timezone;
        public Dictionary<string, bool> features;
    }

    [Serializable]
    public class DialogoEstado
    {
        public bool dialogo_activo;
        public string estado;
        /// <summary>Nombre del diálogo cuando está activo (debug).</summary>
        public string dialogo_nombre;
        /// <summary>ID del diálogo cuando está activo (debug).</summary>
        public int dialogo_id;
        /// <summary>Nombre del diálogo configurado para la sesión cuando no hay diálogo activo (debug).</summary>
        public string dialogo_configurado_nombre;
        /// <summary>ID del diálogo configurado cuando no hay diálogo activo (debug).</summary>
        public int dialogo_configurado_id;
        public NodoActual nodo_actual;
        public List<Participante> participantes;
        /// <summary>Progreso 0..1. Null si la API no lo envía (se trata como 0).</summary>
        public float? progreso;
        public int tiempo_transcurrido;
        public Dictionary<string, object> variables;
    }

    [Serializable]
    public class NodoActual
    {
        public int id;
        public string titulo;
        public string contenido;
        public RolHablando rol_hablando;
        public string tipo;
        public bool es_final;
    }

    [Serializable]
    public class RolHablando
    {
        public int id;
        public string nombre;
        public string color;
        public string icono;
    }

    [Serializable]
    public class Participante
    {
        public int usuario_id;
        public string nombre;
        public RolHablando rol;
        public bool es_turno;
    }

    [Serializable]
    public class RespuestasResponse
    {
        public bool respuestas_disponibles;
        public List<RespuestaUsuario> respuestas;
        public NodoActual nodo_actual;
        public RolHablando rol_usuario;
    }

    [Serializable]
    public class RespuestaUsuario
    {
        public int id;
        public string texto;
        public string descripcion;
        public string color;
        public int puntuacion;
        public bool tiene_consecuencias;
        public bool es_final;
    }

    [Serializable]
    public class DecisionRequest
    {
        public int usuario_id;
        public int respuesta_id;
        public string decision_texto;
        public int tiempo_respuesta;
        public Dictionary<string, object> metadata;
    }

    [Serializable]
    public class DecisionResponse
    {
        public bool decision_procesada;
        public int decision_id;
        public int puntuacion_obtenida;
        public NuevoEstado nuevo_estado;
    }

    [Serializable]
    public class NuevoEstado
    {
        public NodoActual nodo_actual;
        public float? progreso;
        public bool dialogo_finalizado;
    }

    [Serializable]
    public class HablandoRequest
    {
        public int usuario_id;
        public string estado;
        public Dictionary<string, object> metadata;
    }

    [Serializable]
    public class SesionData
    {
        public int id;
        public string nombre;
        public string descripcion;
        public string estado;
        public string fecha_inicio;
        public string fecha_fin;
        public int max_participantes;
        public int participantes_count;
        public string unity_room_id;
        public Dictionary<string, object> configuracion;
    }

    [Serializable]
    public class RoomCreateRequest
    {
        public string nombre;
        public int sesion_juicio_id;
        public int max_participantes;
        public Dictionary<string, object> configuracion;
        public Dictionary<string, object> audio_config;
    }

    [Serializable]
    public class RoomCreateResponse
    {
        public string room_id;
        public string nombre;
        public string livekit_room_name;
        public string livekit_token;
        public object livekit_config;
    }

    [Serializable]
    public class SyncPlayerRequest
    {
        public int usuario_id;
        public string nombre;
        public string rol_nombre;
        public Dictionary<string, object> position;
        public Dictionary<string, object> estado;
    }

    [Serializable]
    public class AudioStateRequest
    {
        public int usuario_id;
        public bool muted;
        public bool speaking;
        public Dictionary<string, object> metadata;
    }

    [Serializable]
    public class BroadcastEventRequest
    {
        public string event_type;
        public object payload;
        public Dictionary<string, object> metadata;
    }

    [Serializable]
    public class SSEEvent
    {
        public string type;
        public object data;
        public string timestamp;
    }
}
