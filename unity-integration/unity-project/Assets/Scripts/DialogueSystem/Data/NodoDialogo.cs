using System;
using System.Collections.Generic;
using UnityEngine;

namespace DialogueSystem.Data
{
    /// <summary>
    /// Representa un nodo en el grafo de diálogo.
    /// Corresponde a `nodos_dialogo_v2` en la base de datos.
    /// </summary>
    [Serializable]
    public class NodoDialogo
    {
        [Header("Identificación")]
        /// <summary>
        /// ID único del nodo en la base de datos.
        /// </summary>
        public int id;

        /// <summary>
        /// ID del diálogo al que pertenece este nodo.
        /// </summary>
        public int dialogoId;

        [Header("Contenido")]
        /// <summary>
        /// Título del nodo (opcional, para organización).
        /// </summary>
        public string titulo;

        /// <summary>
        /// Contenido principal del nodo (texto del diálogo).
        /// </summary>
        [TextArea(3, 10)]
        public string contenido;

        /// <summary>
        /// Tipo de nodo (Inicio, Desarrollo, Decision, Final, Agrupacion).
        /// </summary>
        public TipoNodo tipo;

        /// <summary>
        /// Texto para mostrar en el menú de respuestas (si aplica).
        /// Alineado con Pixel Crushers MenuText.
        /// </summary>
        public string menuText;

        /// <summary>
        /// Instrucciones adicionales para el nodo (opcional).
        /// </summary>
        [TextArea(2, 5)]
        public string instrucciones;

        [Header("Asignación de Roles")]
        /// <summary>
        /// ID del rol asignado a este nodo (quien habla).
        /// </summary>
        public int? rolAsignadoId = null;

        /// <summary>
        /// ID del conversant (quien escucha).
        /// Alineado con Pixel Crushers ConversantID.
        /// </summary>
        public int? conversantId = null;

        [Header("Posición en Editor")]
        /// <summary>
        /// Posición X del nodo en el editor visual.
        /// </summary>
        public int posicionX;

        /// <summary>
        /// Posición Y del nodo en el editor visual.
        /// </summary>
        public int posicionY;

        /// <summary>
        /// Posición como Vector2 (helper property).
        /// </summary>
        public Vector2 posicion
        {
            get => new Vector2(posicionX, posicionY);
            set
            {
                posicionX = (int)value.x;
                posicionY = (int)value.y;
            }
        }

        [Header("Estado")]
        /// <summary>
        /// Indica si este es el nodo inicial del diálogo.
        /// </summary>
        public bool esInicial = false;

        /// <summary>
        /// Indica si este es un nodo final del diálogo.
        /// </summary>
        public bool esFinal = false;

        /// <summary>
        /// Indica si el nodo está activo.
        /// </summary>
        public bool activo = true;

        [Header("Lógica")]
        /// <summary>
        /// Condiciones para mostrar este nodo (JSON deserializado).
        /// </summary>
        public Dictionary<string, object> condiciones = new Dictionary<string, object>();

        /// <summary>
        /// Consecuencias al llegar a este nodo (JSON deserializado).
        /// </summary>
        public Dictionary<string, object> consecuencias = new Dictionary<string, object>();

        /// <summary>
        /// Metadata adicional (JSON deserializado).
        /// Puede incluir: sequence, userScript, fields, etc.
        /// </summary>
        public Dictionary<string, object> metadata = new Dictionary<string, object>();

        [Header("Respuestas")]
        /// <summary>
        /// Lista de respuestas disponibles desde este nodo.
        /// </summary>
        public List<RespuestaDialogo> respuestas = new List<RespuestaDialogo>();

        /// <summary>
        /// Constructor por defecto.
        /// </summary>
        public NodoDialogo()
        {
            condiciones = new Dictionary<string, object>();
            consecuencias = new Dictionary<string, object>();
            metadata = new Dictionary<string, object>();
            respuestas = new List<RespuestaDialogo>();
        }

        /// <summary>
        /// Constructor con parámetros básicos.
        /// </summary>
        public NodoDialogo(int id, int dialogoId, TipoNodo tipo, string contenido, int posicionX = 0, int posicionY = 0)
        {
            this.id = id;
            this.dialogoId = dialogoId;
            this.tipo = tipo;
            this.contenido = contenido;
            this.posicionX = posicionX;
            this.posicionY = posicionY;
            this.esInicial = false;
            this.esFinal = false;
            this.activo = true;
            this.condiciones = new Dictionary<string, object>();
            this.consecuencias = new Dictionary<string, object>();
            this.metadata = new Dictionary<string, object>();
            this.respuestas = new List<RespuestaDialogo>();
        }

        /// <summary>
        /// Obtiene las respuestas disponibles para el usuario actual.
        /// </summary>
        /// <param name="usuarioRegistrado">Si el usuario está registrado.</param>
        /// <param name="rolId">ID del rol del usuario.</param>
        /// <param name="variables">Variables de sesión.</param>
        /// <returns>Lista de respuestas disponibles.</returns>
        public List<RespuestaDialogo> ObtenerRespuestasDisponibles(bool usuarioRegistrado, int? rolId, Dictionary<string, object> variables = null)
        {
            var disponibles = new List<RespuestaDialogo>();

            foreach (var respuesta in respuestas)
            {
                if (respuesta.EstaDisponible(usuarioRegistrado, rolId, variables))
                {
                    disponibles.Add(respuesta);
                }
            }

            // Si no hay respuestas disponibles y el usuario no está registrado,
            // buscar opción por defecto
            if (disponibles.Count == 0 && !usuarioRegistrado)
            {
                foreach (var respuesta in respuestas)
                {
                    if (respuesta.esOpcionPorDefecto)
                    {
                        disponibles.Add(respuesta);
                        break;
                    }
                }
            }

            // Ordenar por orden
            disponibles.Sort((a, b) => a.orden.CompareTo(b.orden));

            return disponibles;
        }

        /// <summary>
        /// Agrega una respuesta a este nodo.
        /// </summary>
        public void AgregarRespuesta(RespuestaDialogo respuesta)
        {
            if (respuesta.nodoOrigenId != id)
            {
                Debug.LogWarning($"La respuesta tiene nodoOrigenId {respuesta.nodoOrigenId} pero se está agregando al nodo {id}");
                respuesta.nodoOrigenId = id;
            }

            if (!respuestas.Contains(respuesta))
            {
                respuestas.Add(respuesta);
            }
        }

        /// <summary>
        /// Remueve una respuesta de este nodo.
        /// </summary>
        public void RemoverRespuesta(RespuestaDialogo respuesta)
        {
            respuestas.Remove(respuesta);
        }

        /// <summary>
        /// Actualiza la posición del nodo.
        /// </summary>
        public void ActualizarPosicion(int x, int y)
        {
            posicionX = x;
            posicionY = y;
        }

        /// <summary>
        /// Verifica si este nodo tiene respuestas disponibles.
        /// </summary>
        public bool TieneRespuestas(bool usuarioRegistrado, int? rolId, Dictionary<string, object> variables = null)
        {
            return ObtenerRespuestasDisponibles(usuarioRegistrado, rolId, variables).Count > 0;
        }
    }
}
