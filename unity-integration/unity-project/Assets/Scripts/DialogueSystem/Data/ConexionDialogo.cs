using System;
using System.Collections.Generic;
using UnityEngine;

namespace DialogueSystem.Data
{
    /// <summary>
    /// Representa una conexión visual entre nodos en el editor.
    /// Usado para dibujar líneas curvas entre nodos.
    /// Corresponde a la relación entre nodos a través de respuestas.
    /// </summary>
    [Serializable]
    public class ConexionDialogo
    {
        [Header("Identificación")]
        /// <summary>
        /// ID del nodo origen.
        /// </summary>
        public int nodoOrigenId;

        /// <summary>
        /// ID del nodo destino.
        /// </summary>
        public int nodoDestinoId;

        /// <summary>
        /// ID de la respuesta que crea esta conexión.
        /// </summary>
        public int respuestaId;

        [Header("Visualización")]
        /// <summary>
        /// Puntos intermedios para dibujar una línea curva (opcional).
        /// </summary>
        public List<Vector2> puntosIntermedios = new List<Vector2>();

        /// <summary>
        /// Color de la conexión.
        /// </summary>
        public Color color = Color.white;

        /// <summary>
        /// Ancho de la línea de conexión.
        /// </summary>
        public float ancho = 2f;

        /// <summary>
        /// Constructor por defecto.
        /// </summary>
        public ConexionDialogo()
        {
            puntosIntermedios = new List<Vector2>();
        }

        /// <summary>
        /// Constructor con parámetros básicos.
        /// </summary>
        public ConexionDialogo(int nodoOrigenId, int nodoDestinoId, int respuestaId)
        {
            this.nodoOrigenId = nodoOrigenId;
            this.nodoDestinoId = nodoDestinoId;
            this.respuestaId = respuestaId;
            this.color = Color.white;
            this.ancho = 2f;
            this.puntosIntermedios = new List<Vector2>();
        }

        /// <summary>
        /// Agrega un punto intermedio para crear una línea curva.
        /// </summary>
        public void AgregarPuntoIntermedio(Vector2 punto)
        {
            puntosIntermedios.Add(punto);
        }

        /// <summary>
        /// Limpia todos los puntos intermedios.
        /// </summary>
        public void LimpiarPuntosIntermedios()
        {
            puntosIntermedios.Clear();
        }
    }
}
