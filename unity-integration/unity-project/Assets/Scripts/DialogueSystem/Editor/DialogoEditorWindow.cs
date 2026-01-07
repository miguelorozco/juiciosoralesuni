using System;
using System.Collections.Generic;
using System.Linq;
using UnityEditor;
using UnityEngine;
using DialogueSystem.Data;
using DialogueSystem.Storage;

namespace DialogueSystem.Editor
{
    /// <summary>
    /// Ventana principal del editor de diálogos.
    /// Permite crear, editar y gestionar diálogos de forma visual.
    /// </summary>
    public class DialogoEditorWindow : EditorWindow
    {
        #region Constantes

        private const float PANEL_LEFT_WIDTH = 250f;
        private const float PANEL_RIGHT_WIDTH = 300f;
        private const float TOOLBAR_HEIGHT = 30f;
        private const float GRID_SIZE = 200f;
        private const float MIN_ZOOM = 0.1f;
        private const float MAX_ZOOM = 2.0f;
        private const float DEFAULT_ZOOM = 1.0f;

        #endregion

        #region Variables Privadas

        // Diálogo actual
        private DialogoData dialogoActual;
        private int dialogoActualId = 0;
        private bool dialogoModificado = false;

        // Lista de diálogos
        private List<DialogoData> dialogosDisponibles = new List<DialogoData>();
        private Vector2 scrollListaDialogos = Vector2.zero;
        private string busquedaDialogos = "";

        // Canvas y navegación
        private Vector2 canvasOffset = Vector2.zero;
        private float zoom = DEFAULT_ZOOM;
        private bool mostrarGrid = true;
        private bool snapToGrid = true;

        // Selección
        private NodoDialogo nodoSeleccionado = null;
        private RespuestaDialogo respuestaSeleccionada = null;
        private List<NodoDialogo> nodosSeleccionados = new List<NodoDialogo>();

        // Drag & Drop
        private bool isDraggingNode = false;
        private bool isDraggingCanvas = false;
        private bool isCreatingConnection = false;
        private Vector2 dragStartPosition;
        private NodoDialogo nodoOrigenConexion = null;

        // Propiedades del panel derecho
        private Vector2 scrollPropiedades = Vector2.zero;

        // Estilos
        private GUIStyle nodoStyle;
        private GUIStyle nodoInicialStyle;
        private GUIStyle nodoFinalStyle;
        private GUIStyle nodoDecisionStyle;
        private bool estilosInicializados = false;

        #endregion

        #region Menú y Apertura

        [MenuItem("Tools/Sistema de Diálogos/Editor", false, 1)]
        public static void OpenDialogoEditor()
        {
            var window = GetWindow<DialogoEditorWindow>("Editor de Diálogos");
            window.minSize = new Vector2(800, 600);
            window.Show();
        }

        private void OnEnable()
        {
            // Cargar diálogos disponibles
            CargarDialogosDisponibles();
        }

        #endregion

        #region OnGUI Principal

        private void OnGUI()
        {
            InicializarEstilos();

            // Toolbar superior
            DibujarToolbar();

            // Layout principal: Panel izquierdo | Canvas | Panel derecho
            EditorGUILayout.BeginHorizontal();

            // Panel izquierdo - Lista de diálogos
            DibujarPanelListaDialogos();

            // Panel central - Canvas del editor
            DibujarCanvas();

            // Panel derecho - Propiedades
            DibujarPanelPropiedades();

            EditorGUILayout.EndHorizontal();

            // Manejar eventos del mouse
            ManejarEventosMouse();

            // Actualizar si hay cambios
            if (GUI.changed)
            {
                dialogoModificado = true;
                Repaint();
            }
        }

        #endregion

        #region Toolbar

        private void DibujarToolbar()
        {
            EditorGUILayout.BeginHorizontal(EditorStyles.toolbar, GUILayout.Height(TOOLBAR_HEIGHT));

            // Botón Nuevo
            if (GUILayout.Button("Nuevo", EditorStyles.toolbarButton, GUILayout.Width(60)))
            {
                CrearNuevoDialogo();
            }

            // Botón Abrir
            if (GUILayout.Button("Abrir", EditorStyles.toolbarButton, GUILayout.Width(60)))
            {
                AbrirDialogo();
            }

            // Botón Guardar
            GUI.enabled = dialogoActual != null && dialogoModificado;
            if (GUILayout.Button("Guardar", EditorStyles.toolbarButton, GUILayout.Width(60)))
            {
                GuardarDialogo();
            }
            GUI.enabled = true;

            // Separador
            GUILayout.Space(10);

            // Botón Exportar
            GUI.enabled = dialogoActual != null;
            if (GUILayout.Button("Exportar JSON", EditorStyles.toolbarButton, GUILayout.Width(100)))
            {
                ExportarJSON();
            }
            GUI.enabled = true;

            // Botón Importar
            if (GUILayout.Button("Importar JSON", EditorStyles.toolbarButton, GUILayout.Width(100)))
            {
                ImportarJSON();
            }

            // Separador
            GUILayout.Space(10);

            // Botón Sincronizar con Laravel
            GUI.enabled = dialogoActual != null;
            if (GUILayout.Button("Sincronizar Laravel", EditorStyles.toolbarButton, GUILayout.Width(130)))
            {
                SincronizarConLaravel();
            }
            GUI.enabled = true;

            // Espacio flexible
            GUILayout.FlexibleSpace();

            // Indicador de modificado
            if (dialogoModificado && dialogoActual != null)
            {
                GUILayout.Label("*", EditorStyles.boldLabel);
            }

            // Zoom
            GUILayout.Label("Zoom:", EditorStyles.miniLabel);
            zoom = EditorGUILayout.Slider(zoom, MIN_ZOOM, MAX_ZOOM, GUILayout.Width(100));

            // Grid toggle
            mostrarGrid = GUILayout.Toggle(mostrarGrid, "Grid", EditorStyles.toolbarButton, GUILayout.Width(50));
            snapToGrid = GUILayout.Toggle(snapToGrid, "Snap", EditorStyles.toolbarButton, GUILayout.Width(50));

            EditorGUILayout.EndHorizontal();
        }

        #endregion

        #region Panel Lista de Diálogos

        private void DibujarPanelListaDialogos()
        {
            EditorGUILayout.BeginVertical(GUILayout.Width(PANEL_LEFT_WIDTH));

            // Título
            EditorGUILayout.LabelField("Diálogos", EditorStyles.boldLabel);

            // Búsqueda
            busquedaDialogos = EditorGUILayout.TextField("Buscar:", busquedaDialogos);

            // Botones de acción
            EditorGUILayout.BeginHorizontal();
            if (GUILayout.Button("Nuevo", GUILayout.Height(25)))
            {
                CrearNuevoDialogo();
            }
            if (GUILayout.Button("Refrescar", GUILayout.Height(25)))
            {
                CargarDialogosDisponibles();
            }
            EditorGUILayout.EndHorizontal();

            // Lista de diálogos
            scrollListaDialogos = EditorGUILayout.BeginScrollView(scrollListaDialogos);

            var dialogosFiltrados = dialogosDisponibles;
            if (!string.IsNullOrEmpty(busquedaDialogos))
            {
                dialogosFiltrados = dialogosDisponibles.Where(d =>
                    d.nombre.ToLower().Contains(busquedaDialogos.ToLower()) ||
                    d.descripcion.ToLower().Contains(busquedaDialogos.ToLower())
                ).ToList();
            }

            foreach (var dialogo in dialogosFiltrados)
            {
                EditorGUILayout.BeginHorizontal();

                // Botón para seleccionar diálogo
                bool isSelected = dialogoActual != null && dialogoActual.id == dialogo.id;
                if (GUILayout.Button(dialogo.nombre, isSelected ? EditorStyles.miniButtonMid : EditorStyles.miniButton, GUILayout.Height(20)))
                {
                    AbrirDialogo(dialogo);
                }

                // Indicador de modificado
                if (isSelected && dialogoModificado)
                {
                    GUILayout.Label("*", EditorStyles.boldLabel, GUILayout.Width(10));
                }

                EditorGUILayout.EndHorizontal();
            }

            EditorGUILayout.EndScrollView();

            EditorGUILayout.EndVertical();
        }

        #endregion

        #region Canvas

        private void DibujarCanvas()
        {
            EditorGUILayout.BeginVertical();

            // Área del canvas
            Rect canvasRect = GUILayoutUtility.GetRect(0, 0, GUILayout.ExpandWidth(true), GUILayout.ExpandHeight(true));

            // Dibujar grid de fondo
            if (mostrarGrid)
            {
                DibujarGrid(canvasRect);
            }

            // Aplicar transformación de zoom y offset
            Matrix4x4 oldMatrix = GUI.matrix;
            GUI.matrix = Matrix4x4.TRS(canvasOffset, Quaternion.identity, Vector3.one * zoom);

            // Dibujar conexiones
            if (dialogoActual != null)
            {
                DibujarConexiones();
            }

            // Dibujar nodos
            if (dialogoActual != null)
            {
                DibujarNodos();
            }

            // Restaurar matriz
            GUI.matrix = oldMatrix;

            // Dibujar línea temporal de conexión
            if (isCreatingConnection && nodoOrigenConexion != null)
            {
                DibujarLineaTemporalConexion();
            }

            EditorGUILayout.EndVertical();
        }

        private void DibujarGrid(Rect canvasRect)
        {
            Handles.BeginGUI();

            Color gridColor = new Color(0.5f, 0.5f, 0.5f, 0.2f);
            Handles.color = gridColor;

            float gridSize = GRID_SIZE * zoom;

            // Líneas verticales
            for (float x = canvasOffset.x % gridSize; x < canvasRect.width; x += gridSize)
            {
                Handles.DrawLine(new Vector3(x, 0), new Vector3(x, canvasRect.height));
            }

            // Líneas horizontales
            for (float y = canvasOffset.y % gridSize; y < canvasRect.height; y += gridSize)
            {
                Handles.DrawLine(new Vector3(0, y), new Vector3(canvasRect.width, y));
            }

            Handles.EndGUI();
        }

        private void DibujarNodos()
        {
            foreach (var nodo in dialogoActual.nodos)
            {
                DibujarNodo(nodo);
            }
        }

        private void DibujarNodo(NodoDialogo nodo)
        {
            Rect nodoRect = new Rect(nodo.posicionX, nodo.posicionY, 200, 100);

            // Determinar estilo según tipo
            GUIStyle estilo = nodoStyle;
            Color colorFondo = Color.white;

            if (nodo.esInicial)
            {
                estilo = nodoInicialStyle;
                colorFondo = Color.green;
            }
            else if (nodo.esFinal)
            {
                estilo = nodoFinalStyle;
                colorFondo = Color.red;
            }
            else if (nodo.tipo == TipoNodo.Decision)
            {
                estilo = nodoDecisionStyle;
                colorFondo = Color.yellow;
            }

            // Color de selección
            if (nodoSeleccionado == nodo || nodosSeleccionados.Contains(nodo))
            {
                colorFondo = Color.cyan;
            }

            // Dibujar fondo del nodo
            EditorGUI.DrawRect(nodoRect, colorFondo);

            // Dibujar contenido del nodo
            GUI.Box(nodoRect, "", estilo);

            // Título
            Rect tituloRect = new Rect(nodoRect.x + 5, nodoRect.y + 5, nodoRect.width - 10, 20);
            GUI.Label(tituloRect, string.IsNullOrEmpty(nodo.titulo) ? $"Nodo {nodo.id}" : nodo.titulo, EditorStyles.boldLabel);

            // Contenido truncado
            Rect contenidoRect = new Rect(nodoRect.x + 5, nodoRect.y + 25, nodoRect.width - 10, 50);
            string contenidoTruncado = string.IsNullOrEmpty(nodo.contenido) ? "(Sin contenido)" : nodo.contenido;
            if (contenidoTruncado.Length > 50)
            {
                contenidoTruncado = contenidoTruncado.Substring(0, 50) + "...";
            }
            GUI.Label(contenidoRect, contenidoTruncado, EditorStyles.wordWrappedLabel);

            // Indicador de tipo
            Rect tipoRect = new Rect(nodoRect.x + 5, nodoRect.y + 75, nodoRect.width - 10, 20);
            GUI.Label(tipoRect, nodo.tipo.ToString(), EditorStyles.miniLabel);
        }

        private void DibujarConexiones()
        {
            Handles.BeginGUI();

            foreach (var nodo in dialogoActual.nodos)
            {
                foreach (var respuesta in nodo.respuestas)
                {
                    NodoDialogo nodoDestino = dialogoActual.GetNodoPorId(respuesta.nodoDestinoId);
                    if (nodoDestino != null)
                    {
                        DibujarConexion(nodo, nodoDestino, respuesta);
                    }
                }
            }

            Handles.EndGUI();
        }

        private void DibujarConexion(NodoDialogo origen, NodoDialogo destino, RespuestaDialogo respuesta)
        {
            Vector2 puntoOrigen = new Vector2(origen.posicionX + 100, origen.posicionY + 50);
            Vector2 puntoDestino = new Vector2(destino.posicionX + 100, destino.posicionY + 50);

            Color colorConexion = respuesta.color;
            if (respuestaSeleccionada == respuesta)
            {
                colorConexion = Color.cyan;
            }

            Handles.color = colorConexion;
            Handles.DrawLine(puntoOrigen, puntoDestino);

            // Dibujar flecha
            Vector2 direccion = (puntoDestino - puntoOrigen).normalized;
            Vector2 flecha1 = puntoDestino - direccion * 15 + new Vector2(-direccion.y, direccion.x) * 5;
            Vector2 flecha2 = puntoDestino - direccion * 15 - new Vector2(-direccion.y, direccion.x) * 5;
            Handles.DrawLine(puntoDestino, flecha1);
            Handles.DrawLine(puntoDestino, flecha2);
        }

        private void DibujarLineaTemporalConexion()
        {
            if (nodoOrigenConexion == null)
                return;

            Handles.BeginGUI();
            Handles.color = Color.yellow;

            Vector2 puntoOrigen = new Vector2(nodoOrigenConexion.posicionX + 100, nodoOrigenConexion.posicionY + 50);
            Vector2 puntoDestino = Event.current.mousePosition;

            Handles.DrawLine(puntoOrigen, puntoDestino);
            Handles.EndGUI();
        }

        #endregion

        #region Panel Propiedades

        private void DibujarPanelPropiedades()
        {
            EditorGUILayout.BeginVertical(GUILayout.Width(PANEL_RIGHT_WIDTH));

            scrollPropiedades = EditorGUILayout.BeginScrollView(scrollPropiedades);

            if (dialogoActual == null)
            {
                EditorGUILayout.HelpBox("No hay diálogo abierto. Crea uno nuevo o abre uno existente.", MessageType.Info);
            }
            else if (nodoSeleccionado != null)
            {
                DibujarPropiedadesNodo();
            }
            else if (respuestaSeleccionada != null)
            {
                DibujarPropiedadesRespuesta();
            }
            else
            {
                DibujarPropiedadesDialogo();
            }

            EditorGUILayout.EndScrollView();
            EditorGUILayout.EndVertical();
        }

        private void DibujarPropiedadesDialogo()
        {
            EditorGUILayout.LabelField("Propiedades del Diálogo", EditorStyles.boldLabel);

            EditorGUI.BeginChangeCheck();

            dialogoActual.nombre = EditorGUILayout.TextField("Nombre:", dialogoActual.nombre);
            dialogoActual.descripcion = EditorGUILayout.TextArea(dialogoActual.descripcion, GUILayout.Height(60));
            dialogoActual.version = EditorGUILayout.TextField("Versión:", dialogoActual.version);
            dialogoActual.publico = EditorGUILayout.Toggle("Público:", dialogoActual.publico);
            dialogoActual.estado = EditorGUILayout.TextField("Estado:", dialogoActual.estado);

            if (EditorGUI.EndChangeCheck())
            {
                dialogoModificado = true;
            }

            EditorGUILayout.Space();

            // Estadísticas
            EditorGUILayout.LabelField("Estadísticas", EditorStyles.boldLabel);
            EditorGUILayout.LabelField($"Nodos: {dialogoActual.GetTotalNodos()}");
            EditorGUILayout.LabelField($"Respuestas: {dialogoActual.GetTotalRespuestas()}");

            EditorGUILayout.Space();

            // Validar estructura
            if (GUILayout.Button("Validar Estructura"))
            {
                ValidarEstructura();
            }
        }

        private void DibujarPropiedadesNodo()
        {
            EditorGUILayout.LabelField("Propiedades del Nodo", EditorStyles.boldLabel);

            EditorGUI.BeginChangeCheck();

            nodoSeleccionado.titulo = EditorGUILayout.TextField("Título:", nodoSeleccionado.titulo);
            nodoSeleccionado.contenido = EditorGUILayout.TextArea(nodoSeleccionado.contenido, GUILayout.Height(80));
            nodoSeleccionado.tipo = (TipoNodo)EditorGUILayout.EnumPopup("Tipo:", nodoSeleccionado.tipo);
            nodoSeleccionado.menuText = EditorGUILayout.TextField("Menu Text:", nodoSeleccionado.menuText);
            nodoSeleccionado.instrucciones = EditorGUILayout.TextArea(nodoSeleccionado.instrucciones, GUILayout.Height(40));

            EditorGUILayout.Space();
            nodoSeleccionado.esInicial = EditorGUILayout.Toggle("Es Inicial:", nodoSeleccionado.esInicial);
            nodoSeleccionado.esFinal = EditorGUILayout.Toggle("Es Final:", nodoSeleccionado.esFinal);
            nodoSeleccionado.activo = EditorGUILayout.Toggle("Activo:", nodoSeleccionado.activo);

            EditorGUILayout.Space();
            nodoSeleccionado.rolAsignadoId = EditorGUILayout.IntField("Rol ID:", nodoSeleccionado.rolAsignadoId ?? 0);
            nodoSeleccionado.conversantId = EditorGUILayout.IntField("Conversant ID:", nodoSeleccionado.conversantId ?? 0);

            if (EditorGUI.EndChangeCheck())
            {
                dialogoModificado = true;
            }

            EditorGUILayout.Space();

            // Botón eliminar
            if (GUILayout.Button("Eliminar Nodo", GUILayout.Height(30)))
            {
                EliminarNodo(nodoSeleccionado);
            }
        }

        private void DibujarPropiedadesRespuesta()
        {
            EditorGUILayout.LabelField("Propiedades de la Respuesta", EditorStyles.boldLabel);

            EditorGUI.BeginChangeCheck();

            respuestaSeleccionada.texto = EditorGUILayout.TextArea(respuestaSeleccionada.texto, GUILayout.Height(60));
            respuestaSeleccionada.orden = EditorGUILayout.IntField("Orden:", respuestaSeleccionada.orden);
            respuestaSeleccionada.puntuacion = EditorGUILayout.IntField("Puntuación:", respuestaSeleccionada.puntuacion);
            respuestaSeleccionada.color = EditorGUILayout.ColorField("Color:", respuestaSeleccionada.color);

            EditorGUILayout.Space();
            respuestaSeleccionada.requiereUsuarioRegistrado = EditorGUILayout.Toggle("Requiere Usuario Registrado:", respuestaSeleccionada.requiereUsuarioRegistrado);
            respuestaSeleccionada.esOpcionPorDefecto = EditorGUILayout.Toggle("Opción por Defecto:", respuestaSeleccionada.esOpcionPorDefecto);

            if (EditorGUI.EndChangeCheck())
            {
                dialogoModificado = true;
            }

            EditorGUILayout.Space();

            // Botón eliminar
            if (GUILayout.Button("Eliminar Respuesta", GUILayout.Height(30)))
            {
                EliminarRespuesta(respuestaSeleccionada);
            }
        }

        #endregion

        #region Eventos Mouse

        private void ManejarEventosMouse()
        {
            Event e = Event.current;
            Vector2 mousePos = e.mousePosition;

            // Convertir posición del mouse a coordenadas del canvas
            Vector2 canvasMousePos = (mousePos - canvasOffset) / zoom;

            switch (e.type)
            {
                case EventType.MouseDown:
                    if (e.button == 0) // Click izquierdo
                    {
                        ManejarClickIzquierdo(canvasMousePos);
                    }
                    else if (e.button == 1) // Click derecho
                    {
                        ManejarClickDerecho(canvasMousePos);
                    }
                    else if (e.button == 2) // Click medio
                    {
                        isDraggingCanvas = true;
                        dragStartPosition = mousePos;
                    }
                    break;

                case EventType.MouseDrag:
                    if (isDraggingNode && nodoSeleccionado != null)
                    {
                        MoverNodo(nodoSeleccionado, canvasMousePos);
                    }
                    else if (isDraggingCanvas)
                    {
                        canvasOffset += e.delta;
                    }
                    break;

                case EventType.MouseUp:
                    if (e.button == 0)
                    {
                        if (isCreatingConnection)
                        {
                            FinalizarConexion(canvasMousePos);
                        }
                        isDraggingNode = false;
                    }
                    else if (e.button == 2)
                    {
                        isDraggingCanvas = false;
                    }
                    break;

                case EventType.ScrollWheel:
                    // Zoom con rueda del mouse
                    float zoomDelta = -e.delta.y * 0.01f;
                    zoom = Mathf.Clamp(zoom + zoomDelta, MIN_ZOOM, MAX_ZOOM);
                    e.Use();
                    break;
            }
        }

        private void ManejarClickIzquierdo(Vector2 canvasMousePos)
        {
            // Buscar nodo clickeado
            NodoDialogo nodoClickeado = null;
            foreach (var nodo in dialogoActual?.nodos ?? new List<NodoDialogo>())
            {
                Rect nodoRect = new Rect(nodo.posicionX, nodo.posicionY, 200, 100);
                if (nodoRect.Contains(canvasMousePos))
                {
                    nodoClickeado = nodo;
                    break;
                }
            }

            if (nodoClickeado != null)
            {
                // Seleccionar nodo
                if (Event.current.control || Event.current.command)
                {
                    // Multi-selección
                    if (nodosSeleccionados.Contains(nodoClickeado))
                    {
                        nodosSeleccionados.Remove(nodoClickeado);
                    }
                    else
                    {
                        nodosSeleccionados.Add(nodoClickeado);
                    }
                }
                else
                {
                    nodoSeleccionado = nodoClickeado;
                    nodosSeleccionados.Clear();
                    nodosSeleccionados.Add(nodoClickeado);
                    respuestaSeleccionada = null;
                }

                isDraggingNode = true;
                dialogoModificado = true;
            }
            else
            {
                // Deseleccionar
                nodoSeleccionado = null;
                respuestaSeleccionada = null;
                nodosSeleccionados.Clear();
            }
        }

        private void ManejarClickDerecho(Vector2 canvasMousePos)
        {
            GenericMenu menu = new GenericMenu();

            menu.AddItem(new GUIContent("Crear Nodo/Inicio"), false, () => CrearNodo(TipoNodo.Inicio, canvasMousePos));
            menu.AddItem(new GUIContent("Crear Nodo/Desarrollo"), false, () => CrearNodo(TipoNodo.Desarrollo, canvasMousePos));
            menu.AddItem(new GUIContent("Crear Nodo/Decisión"), false, () => CrearNodo(TipoNodo.Decision, canvasMousePos));
            menu.AddItem(new GUIContent("Crear Nodo/Final"), false, () => CrearNodo(TipoNodo.Final, canvasMousePos));

            if (nodoSeleccionado != null)
            {
                menu.AddSeparator("");
                menu.AddItem(new GUIContent("Crear Conexión"), false, () => IniciarConexion(nodoSeleccionado));
                menu.AddItem(new GUIContent("Eliminar Nodo"), false, () => EliminarNodo(nodoSeleccionado));
            }

            menu.ShowAsContext();
        }

        #endregion

        #region Funciones de Diálogo

        private void CargarDialogosDisponibles()
        {
            dialogosDisponibles.Clear();

            #if UNITY_EDITOR
            // Buscar ScriptableObjects de diálogos
            string[] guids = AssetDatabase.FindAssets($"t:{nameof(DialogoData)}");
            foreach (string guid in guids)
            {
                string path = AssetDatabase.GUIDToAssetPath(guid);
                DialogoData dialogo = AssetDatabase.LoadAssetAtPath<DialogoData>(path);
                if (dialogo != null)
                {
                    dialogosDisponibles.Add(dialogo);
                }
            }
            #endif
        }

        private void CrearNuevoDialogo()
        {
            DialogoData nuevoDialogo = ScriptableObject.CreateInstance<DialogoData>();
            nuevoDialogo.nombre = "Nuevo Diálogo";
            nuevoDialogo.descripcion = "";
            nuevoDialogo.version = "1.0.0";
            nuevoDialogo.fechaCreacion = DateTime.Now;
            nuevoDialogo.estado = "borrador";
            nuevoDialogo.publico = false;

            AbrirDialogo(nuevoDialogo);
        }

        private void AbrirDialogo(DialogoData dialogo = null)
        {
            if (dialogo == null)
            {
                // Abrir diálogo desde archivo
                string path = EditorUtility.OpenFilePanel("Abrir Diálogo", "Assets", "asset");
                if (!string.IsNullOrEmpty(path))
                {
                    #if UNITY_EDITOR
                    dialogo = AssetDatabase.LoadAssetAtPath<DialogoData>(path);
                    #endif
                }
            }

            if (dialogo != null)
            {
                dialogoActual = dialogo;
                dialogoActualId = dialogo.id;
                dialogoModificado = false;
                nodoSeleccionado = null;
                respuestaSeleccionada = null;
                nodosSeleccionados.Clear();
            }
        }

        private void GuardarDialogo()
        {
            if (dialogoActual == null)
                return;

            #if UNITY_EDITOR
            if (AssetDatabase.Contains(dialogoActual))
            {
                EditorUtility.SetDirty(dialogoActual);
                AssetDatabase.SaveAssets();
            }
            else
            {
                string path = EditorUtility.SaveFilePanelInProject("Guardar Diálogo", dialogoActual.nombre, "asset", "Guardar diálogo");
                if (!string.IsNullOrEmpty(path))
                {
                    AssetDatabase.CreateAsset(dialogoActual, path);
                    AssetDatabase.SaveAssets();
                }
            }

            dialogoModificado = false;
            CargarDialogosDisponibles();
            #endif
        }

        private void ExportarJSON()
        {
            if (dialogoActual == null)
                return;

            if (DialogoStorageManager.Instance != null)
            {
                string json = DialogoStorageManager.Instance.ExportarAJSON(dialogoActual);
                string path = EditorUtility.SaveFilePanel("Exportar JSON", "", dialogoActual.nombre, "json");
                if (!string.IsNullOrEmpty(path))
                {
                    System.IO.File.WriteAllText(path, json);
                    EditorUtility.DisplayDialog("Éxito", "Diálogo exportado correctamente.", "OK");
                }
            }
        }

        private void ImportarJSON()
        {
            string path = EditorUtility.OpenFilePanel("Importar JSON", "", "json");
            if (!string.IsNullOrEmpty(path))
            {
                if (DialogoStorageManager.Instance != null)
                {
                    DialogoData dialogo = DialogoStorageManager.Instance.CargarDesdeJSON(path);
                    if (dialogo != null)
                    {
                        AbrirDialogo(dialogo);
                        EditorUtility.DisplayDialog("Éxito", "Diálogo importado correctamente.", "OK");
                    }
                    else
                    {
                        EditorUtility.DisplayDialog("Error", "No se pudo importar el diálogo.", "OK");
                    }
                }
            }
        }

        private void SincronizarConLaravel()
        {
            if (dialogoActual == null || DialogoStorageManager.Instance == null)
                return;

            EditorUtility.DisplayDialog("Sincronización", "Funcionalidad de sincronización con Laravel en desarrollo.", "OK");
            // TODO: Implementar sincronización con Laravel
        }

        private void ValidarEstructura()
        {
            if (dialogoActual == null)
                return;

            var errores = dialogoActual.ValidarEstructura();
            if (errores.Count == 0)
            {
                EditorUtility.DisplayDialog("Validación", "El diálogo es válido.", "OK");
            }
            else
            {
                string mensaje = "Errores encontrados:\n\n" + string.Join("\n", errores);
                EditorUtility.DisplayDialog("Validación", mensaje, "OK");
            }
        }

        #endregion

        #region Funciones de Nodos

        private void CrearNodo(TipoNodo tipo, Vector2 posicion)
        {
            if (dialogoActual == null)
                return;

            // Aplicar snap a grid si está activado
            if (snapToGrid)
            {
                posicion.x = Mathf.Round(posicion.x / GRID_SIZE) * GRID_SIZE;
                posicion.y = Mathf.Round(posicion.y / GRID_SIZE) * GRID_SIZE;
            }

            NodoDialogo nuevoNodo = new NodoDialogo
            {
                id = GetNextNodoId(),
                dialogoId = dialogoActual.id,
                tipo = tipo,
                contenido = "",
                titulo = $"Nodo {tipo}",
                posicionX = (int)posicion.x,
                posicionY = (int)posicion.y,
                esInicial = tipo == TipoNodo.Inicio,
                esFinal = tipo == TipoNodo.Final,
                activo = true
            };

            dialogoActual.AgregarNodo(nuevoNodo);
            nodoSeleccionado = nuevoNodo;
            dialogoModificado = true;
        }

        private void MoverNodo(NodoDialogo nodo, Vector2 nuevaPosicion)
        {
            if (nodo == null)
                return;

            // Aplicar snap a grid si está activado
            if (snapToGrid)
            {
                nuevaPosicion.x = Mathf.Round(nuevaPosicion.x / GRID_SIZE) * GRID_SIZE;
                nuevaPosicion.y = Mathf.Round(nuevaPosicion.y / GRID_SIZE) * GRID_SIZE;
            }

            nodo.ActualizarPosicion((int)nuevaPosicion.x, (int)nuevaPosicion.y);
            dialogoModificado = true;
        }

        private void EliminarNodo(NodoDialogo nodo)
        {
            if (nodo == null || dialogoActual == null)
                return;

            if (EditorUtility.DisplayDialog("Eliminar Nodo", $"¿Estás seguro de eliminar el nodo '{nodo.titulo}'?", "Sí", "No"))
            {
                dialogoActual.RemoverNodo(nodo);
                if (nodoSeleccionado == nodo)
                {
                    nodoSeleccionado = null;
                }
                nodosSeleccionados.Remove(nodo);
                dialogoModificado = true;
            }
        }

        private int GetNextNodoId()
        {
            if (dialogoActual == null || dialogoActual.nodos.Count == 0)
                return 1;

            return dialogoActual.nodos.Max(n => n.id) + 1;
        }

        #endregion

        #region Funciones de Conexiones

        private void IniciarConexion(NodoDialogo nodoOrigen)
        {
            nodoOrigenConexion = nodoOrigen;
            isCreatingConnection = true;
        }

        private void FinalizarConexion(Vector2 posicionMouse)
        {
            if (nodoOrigenConexion == null)
            {
                isCreatingConnection = false;
                return;
            }

            // Buscar nodo destino
            NodoDialogo nodoDestino = null;
            foreach (var nodo in dialogoActual.nodos)
            {
                Rect nodoRect = new Rect(nodo.posicionX, nodo.posicionY, 200, 100);
                if (nodoRect.Contains(posicionMouse) && nodo != nodoOrigenConexion)
                {
                    nodoDestino = nodo;
                    break;
                }
            }

            if (nodoDestino != null)
            {
                // Crear respuesta
                RespuestaDialogo nuevaRespuesta = new RespuestaDialogo
                {
                    id = GetNextRespuestaId(),
                    nodoOrigenId = nodoOrigenConexion.id,
                    nodoDestinoId = nodoDestino.id,
                    texto = "Nueva respuesta",
                    orden = nodoOrigenConexion.respuestas.Count
                };

                nodoOrigenConexion.AgregarRespuesta(nuevaRespuesta);
                dialogoActual.ConstruirConexionesDesdeRespuestas();
                dialogoModificado = true;
            }

            nodoOrigenConexion = null;
            isCreatingConnection = false;
        }

        private void EliminarRespuesta(RespuestaDialogo respuesta)
        {
            if (respuesta == null || dialogoActual == null)
                return;

            if (EditorUtility.DisplayDialog("Eliminar Respuesta", "¿Estás seguro de eliminar esta respuesta?", "Sí", "No"))
            {
                foreach (var nodo in dialogoActual.nodos)
                {
                    if (nodo.respuestas.Contains(respuesta))
                    {
                        nodo.RemoverRespuesta(respuesta);
                        break;
                    }
                }

                dialogoActual.ConstruirConexionesDesdeRespuestas();
                respuestaSeleccionada = null;
                dialogoModificado = true;
            }
        }

        private int GetNextRespuestaId()
        {
            int maxId = 0;
            foreach (var nodo in dialogoActual.nodos)
            {
                foreach (var respuesta in nodo.respuestas)
                {
                    if (respuesta.id > maxId)
                        maxId = respuesta.id;
                }
            }
            return maxId + 1;
        }

        #endregion

        #region Estilos

        private void InicializarEstilos()
        {
            if (estilosInicializados)
                return;

            nodoStyle = new GUIStyle(GUI.skin.box);
            nodoInicialStyle = new GUIStyle(GUI.skin.box);
            nodoFinalStyle = new GUIStyle(GUI.skin.box);
            nodoDecisionStyle = new GUIStyle(GUI.skin.box);

            estilosInicializados = true;
        }

        #endregion
    }
}
