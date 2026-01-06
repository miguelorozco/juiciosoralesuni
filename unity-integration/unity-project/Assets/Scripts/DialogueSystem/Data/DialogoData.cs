using System;
using System.Collections.Generic;
using System.Linq;
using UnityEngine;

namespace DialogueSystem.Data
{
    /// <summary>
    /// ScriptableObject que representa un diálogo completo.
    /// Corresponde a `dialogos_v2` en la base de datos.
    /// Puede ser creado desde el editor o cargado desde la API de Laravel.
    /// </summary>
    [CreateAssetMenu(fileName = "NuevoDialogo", menuName = "Dialogue System/Dialogo Data", order = 1)]
    public class DialogoData : ScriptableObject
    {
        [Header("Información Básica")]
        /// <summary>
        /// ID único del diálogo en la base de datos.
        /// </summary>
        public int id;

        /// <summary>
        /// Nombre del diálogo.
        /// </summary>
        public string nombre;

        /// <summary>
        /// Descripción del diálogo.
        /// </summary>
        [TextArea(3, 5)]
        public string descripcion;

        /// <summary>
        /// Versión del diálogo.
        /// </summary>
        public string version = "1.0.0";

        /// <summary>
        /// Fecha de creación del diálogo.
        /// </summary>
        public DateTime fechaCreacion;

        [Header("Configuración")]
        /// <summary>
        /// Indica si el diálogo es público.
        /// </summary>
        public bool publico = false;

        /// <summary>
        /// Estado del diálogo (borrador, activo, archivado).
        /// </summary>
        public string estado = "borrador";

        /// <summary>
        /// Configuración adicional (JSON deserializado).
        /// </summary>
        public Dictionary<string, object> configuracion = new Dictionary<string, object>();

        /// <summary>
        /// Metadata específica de Unity (JSON deserializado).
        /// </summary>
        public Dictionary<string, object> metadataUnity = new Dictionary<string, object>();

        [Header("Estructura del Diálogo")]
        /// <summary>
        /// Lista de nodos del diálogo.
        /// </summary>
        public List<NodoDialogo> nodos = new List<NodoDialogo>();

        /// <summary>
        /// Lista de conexiones entre nodos (para visualización en editor).
        /// </summary>
        public List<ConexionDialogo> conexiones = new List<ConexionDialogo>();

        /// <summary>
        /// Constructor por defecto.
        /// </summary>
        public DialogoData()
        {
            nodos = new List<NodoDialogo>();
            conexiones = new List<ConexionDialogo>();
            configuracion = new Dictionary<string, object>();
            metadataUnity = new Dictionary<string, object>();
            fechaCreacion = DateTime.Now;
        }

        /// <summary>
        /// Obtiene el nodo inicial del diálogo.
        /// </summary>
        /// <returns>El nodo inicial, o null si no existe.</returns>
        public NodoDialogo GetNodoInicial()
        {
            return nodos.FirstOrDefault(n => n.esInicial);
        }

        /// <summary>
        /// Obtiene todos los nodos finales del diálogo.
        /// </summary>
        /// <returns>Lista de nodos finales.</returns>
        public List<NodoDialogo> GetNodosFinales()
        {
            return nodos.Where(n => n.esFinal).ToList();
        }

        /// <summary>
        /// Obtiene un nodo por su ID.
        /// </summary>
        /// <param name="nodoId">ID del nodo.</param>
        /// <returns>El nodo encontrado, o null si no existe.</returns>
        public NodoDialogo GetNodoPorId(int nodoId)
        {
            return nodos.FirstOrDefault(n => n.id == nodoId);
        }

        /// <summary>
        /// Obtiene una respuesta por su ID.
        /// </summary>
        /// <param name="respuestaId">ID de la respuesta.</param>
        /// <returns>La respuesta encontrada, o null si no existe.</returns>
        public RespuestaDialogo GetRespuestaPorId(int respuestaId)
        {
            foreach (var nodo in nodos)
            {
                var respuesta = nodo.respuestas.FirstOrDefault(r => r.id == respuestaId);
                if (respuesta != null)
                {
                    return respuesta;
                }
            }
            return null;
        }

        /// <summary>
        /// Agrega un nodo al diálogo.
        /// </summary>
        public void AgregarNodo(NodoDialogo nodo)
        {
            if (nodo == null)
            {
                Debug.LogError("No se puede agregar un nodo null al diálogo.");
                return;
            }

            if (nodo.dialogoId != id)
            {
                nodo.dialogoId = id;
            }

            if (!nodos.Contains(nodo))
            {
                nodos.Add(nodo);
            }
        }

        /// <summary>
        /// Remueve un nodo del diálogo.
        /// </summary>
        public void RemoverNodo(NodoDialogo nodo)
        {
            if (nodos.Remove(nodo))
            {
                // Remover también las conexiones relacionadas
                conexiones.RemoveAll(c => c.nodoOrigenId == nodo.id || c.nodoDestinoId == nodo.id);
            }
        }

        /// <summary>
        /// Agrega una conexión entre nodos.
        /// </summary>
        public void AgregarConexion(ConexionDialogo conexion)
        {
            if (conexion == null)
            {
                Debug.LogError("No se puede agregar una conexión null al diálogo.");
                return;
            }

            if (!conexiones.Contains(conexion))
            {
                conexiones.Add(conexion);
            }
        }

        /// <summary>
        /// Remueve una conexión del diálogo.
        /// </summary>
        public void RemoverConexion(ConexionDialogo conexion)
        {
            conexiones.Remove(conexion);
        }

        /// <summary>
        /// Valida la estructura del grafo de diálogo.
        /// </summary>
        /// <returns>Lista de errores encontrados (vacía si no hay errores).</returns>
        public List<string> ValidarEstructura()
        {
            var errores = new List<string>();

            // Verificar que hay al menos un nodo
            if (nodos.Count == 0)
            {
                errores.Add("El diálogo debe tener al menos un nodo.");
                return errores;
            }

            // Verificar que hay exactamente un nodo inicial
            var nodosIniciales = nodos.Where(n => n.esInicial).ToList();
            if (nodosIniciales.Count == 0)
            {
                errores.Add("El diálogo debe tener al menos un nodo inicial.");
            }
            else if (nodosIniciales.Count > 1)
            {
                errores.Add($"El diálogo tiene {nodosIniciales.Count} nodos iniciales. Debe tener exactamente uno.");
            }

            // Verificar que hay al menos un nodo final
            if (nodos.Where(n => n.esFinal).Count() == 0)
            {
                errores.Add("El diálogo debe tener al menos un nodo final.");
            }

            // Verificar que todos los nodos tienen IDs únicos
            var ids = nodos.Select(n => n.id).ToList();
            var idsDuplicados = ids.GroupBy(id => id).Where(g => g.Count() > 1).Select(g => g.Key).ToList();
            if (idsDuplicados.Count > 0)
            {
                errores.Add($"Hay nodos con IDs duplicados: {string.Join(", ", idsDuplicados)}");
            }

            // Verificar que todas las respuestas apuntan a nodos válidos
            foreach (var nodo in nodos)
            {
                foreach (var respuesta in nodo.respuestas)
                {
                    if (respuesta.nodoOrigenId != nodo.id)
                    {
                        errores.Add($"La respuesta {respuesta.id} del nodo {nodo.id} tiene nodoOrigenId incorrecto ({respuesta.nodoOrigenId}).");
                    }

                    if (GetNodoPorId(respuesta.nodoDestinoId) == null)
                    {
                        errores.Add($"La respuesta {respuesta.id} apunta a un nodo destino inexistente ({respuesta.nodoDestinoId}).");
                    }
                }
            }

            // Verificar que todas las conexiones apuntan a nodos válidos
            foreach (var conexion in conexiones)
            {
                if (GetNodoPorId(conexion.nodoOrigenId) == null)
                {
                    errores.Add($"La conexión apunta a un nodo origen inexistente ({conexion.nodoOrigenId}).");
                }

                if (GetNodoPorId(conexion.nodoDestinoId) == null)
                {
                    errores.Add($"La conexión apunta a un nodo destino inexistente ({conexion.nodoDestinoId}).");
                }
            }

            return errores;
        }

        /// <summary>
        /// Construye las conexiones basándose en las respuestas de los nodos.
        /// </summary>
        public void ConstruirConexionesDesdeRespuestas()
        {
            conexiones.Clear();

            foreach (var nodo in nodos)
            {
                foreach (var respuesta in nodo.respuestas)
                {
                    var conexion = new ConexionDialogo(nodo.id, respuesta.nodoDestinoId, respuesta.id)
                    {
                        color = respuesta.color
                    };
                    conexiones.Add(conexion);
                }
            }
        }

        /// <summary>
        /// Obtiene el total de nodos en el diálogo.
        /// </summary>
        public int GetTotalNodos()
        {
            return nodos.Count;
        }

        /// <summary>
        /// Obtiene el total de respuestas en el diálogo.
        /// </summary>
        public int GetTotalRespuestas()
        {
            return nodos.Sum(n => n.respuestas.Count);
        }

        /// <summary>
        /// Limpia todos los nodos y conexiones del diálogo.
        /// </summary>
        public void Limpiar()
        {
            nodos.Clear();
            conexiones.Clear();
        }

        /// <summary>
        /// Crea una copia del diálogo (útil para edición sin afectar el original).
        /// </summary>
        public DialogoData CrearCopia()
        {
            var copia = ScriptableObject.CreateInstance<DialogoData>();
            copia.id = id;
            copia.nombre = nombre + " (Copia)";
            copia.descripcion = descripcion;
            copia.version = version;
            copia.fechaCreacion = fechaCreacion;
            copia.publico = publico;
            copia.estado = estado;

            // Copiar configuración
            copia.configuracion = new Dictionary<string, object>(configuracion);
            copia.metadataUnity = new Dictionary<string, object>(metadataUnity);

            // Copiar nodos (deep copy)
            foreach (var nodo in nodos)
            {
                var nodoCopia = new NodoDialogo
                {
                    id = nodo.id,
                    dialogoId = nodo.dialogoId,
                    titulo = nodo.titulo,
                    contenido = nodo.contenido,
                    tipo = nodo.tipo,
                    menuText = nodo.menuText,
                    instrucciones = nodo.instrucciones,
                    rolAsignadoId = nodo.rolAsignadoId,
                    conversantId = nodo.conversantId,
                    posicionX = nodo.posicionX,
                    posicionY = nodo.posicionY,
                    esInicial = nodo.esInicial,
                    esFinal = nodo.esFinal,
                    activo = nodo.activo,
                    condiciones = new Dictionary<string, object>(nodo.condiciones),
                    consecuencias = new Dictionary<string, object>(nodo.consecuencias),
                    metadata = new Dictionary<string, object>(nodo.metadata)
                };

                // Copiar respuestas
                foreach (var respuesta in nodo.respuestas)
                {
                    var respuestaCopia = new RespuestaDialogo
                    {
                        id = respuesta.id,
                        nodoOrigenId = respuesta.nodoOrigenId,
                        nodoDestinoId = respuesta.nodoDestinoId,
                        texto = respuesta.texto,
                        orden = respuesta.orden,
                        color = respuesta.color,
                        puntuacion = respuesta.puntuacion,
                        requiereUsuarioRegistrado = respuesta.requiereUsuarioRegistrado,
                        requiereRolId = respuesta.requiereRolId,
                        esOpcionPorDefecto = respuesta.esOpcionPorDefecto,
                        condiciones = new Dictionary<string, object>(respuesta.condiciones),
                        consecuencias = new Dictionary<string, object>(respuesta.consecuencias),
                        metadata = new Dictionary<string, object>(respuesta.metadata)
                    };
                    nodoCopia.respuestas.Add(respuestaCopia);
                }

                copia.nodos.Add(nodoCopia);
            }

            // Construir conexiones
            copia.ConstruirConexionesDesdeRespuestas();

            return copia;
        }
    }
}
