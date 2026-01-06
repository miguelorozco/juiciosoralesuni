using System;
using System.Collections.Generic;
using UnityEngine;

namespace DialogueSystem.Data
{
    /// <summary>
    /// Representa una respuesta/opción del usuario en un diálogo.
    /// Corresponde a `respuestas_dialogo_v2` en la base de datos.
    /// </summary>
    [Serializable]
    public class RespuestaDialogo
    {
        [Header("Identificación")]
        /// <summary>
        /// ID único de la respuesta en la base de datos.
        /// </summary>
        public int id;

        /// <summary>
        /// ID del nodo origen (desde donde sale esta respuesta).
        /// </summary>
        public int nodoOrigenId;

        /// <summary>
        /// ID del nodo destino (hacia donde lleva esta respuesta).
        /// </summary>
        public int nodoDestinoId;

        [Header("Contenido")]
        /// <summary>
        /// Texto de la respuesta que se mostrará al usuario.
        /// </summary>
        [TextArea(2, 4)]
        public string texto;

        /// <summary>
        /// Orden de visualización de la respuesta.
        /// </summary>
        public int orden;

        [Header("Configuración")]
        /// <summary>
        /// Color de la respuesta (para personalización visual).
        /// </summary>
        public Color color = Color.white;

        /// <summary>
        /// Puntuación asociada a esta respuesta (opcional).
        /// </summary>
        public int puntuacion = 0;

        /// <summary>
        /// Indica si esta respuesta requiere que el usuario esté registrado.
        /// </summary>
        public bool requiereUsuarioRegistrado = false;

        /// <summary>
        /// ID del rol requerido para ver esta respuesta (null = cualquier rol).
        /// </summary>
        public int? requiereRolId = null;

        /// <summary>
        /// Indica si esta es la opción por defecto para usuarios no registrados.
        /// </summary>
        public bool esOpcionPorDefecto = false;

        [Header("Lógica")]
        /// <summary>
        /// Condiciones para mostrar esta respuesta (JSON deserializado).
        /// Formato: { "variable": "valor", "operador": "==", ... }
        /// </summary>
        public Dictionary<string, object> condiciones = new Dictionary<string, object>();

        /// <summary>
        /// Consecuencias al seleccionar esta respuesta (JSON deserializado).
        /// Formato: { "accion": "setVariable", "variable": "nombre", "valor": ... }
        /// </summary>
        public Dictionary<string, object> consecuencias = new Dictionary<string, object>();

        /// <summary>
        /// Metadata adicional (JSON deserializado).
        /// </summary>
        public Dictionary<string, object> metadata = new Dictionary<string, object>();

        /// <summary>
        /// Constructor por defecto.
        /// </summary>
        public RespuestaDialogo()
        {
            condiciones = new Dictionary<string, object>();
            consecuencias = new Dictionary<string, object>();
            metadata = new Dictionary<string, object>();
        }

        /// <summary>
        /// Constructor con parámetros básicos.
        /// </summary>
        public RespuestaDialogo(int id, int nodoOrigenId, int nodoDestinoId, string texto, int orden = 0)
        {
            this.id = id;
            this.nodoOrigenId = nodoOrigenId;
            this.nodoDestinoId = nodoDestinoId;
            this.texto = texto;
            this.orden = orden;
            this.color = Color.white;
            this.puntuacion = 0;
            this.requiereUsuarioRegistrado = false;
            this.esOpcionPorDefecto = false;
            this.condiciones = new Dictionary<string, object>();
            this.consecuencias = new Dictionary<string, object>();
            this.metadata = new Dictionary<string, object>();
        }

        /// <summary>
        /// Verifica si esta respuesta está disponible para el usuario actual.
        /// </summary>
        /// <param name="usuarioRegistrado">Si el usuario está registrado.</param>
        /// <param name="rolId">ID del rol del usuario (null si no tiene rol).</param>
        /// <param name="variables">Variables de sesión para evaluar condiciones.</param>
        /// <returns>True si la respuesta está disponible.</returns>
        public bool EstaDisponible(bool usuarioRegistrado, int? rolId, Dictionary<string, object> variables = null)
        {
            // Verificar si requiere usuario registrado
            if (requiereUsuarioRegistrado && !usuarioRegistrado)
            {
                return false;
            }

            // Verificar si requiere un rol específico
            if (requiereRolId.HasValue && rolId != requiereRolId.Value)
            {
                return false;
            }

            // Verificar condiciones (si hay variables)
            if (variables != null && condiciones != null && condiciones.Count > 0)
            {
                // TODO: Implementar evaluación de condiciones
                // Por ahora, si hay condiciones, asumimos que no está disponible
                // hasta que se implemente el evaluador de condiciones
            }

            return true;
        }

        /// <summary>
        /// Obtiene el texto de la respuesta, con formato si es necesario.
        /// </summary>
        public string GetTextoFormateado()
        {
            return texto;
        }
    }
}
