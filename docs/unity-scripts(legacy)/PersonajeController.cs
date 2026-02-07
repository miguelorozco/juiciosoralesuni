using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.AI;
using System.Linq;

namespace JuiciosSimulator.Characters
{
    /// <summary>
    /// Controlador de personajes para el simulador de juicios
    /// </summary>
    public class PersonajeController : MonoBehaviour
    {
        [Header("Configuración del Personaje")]
        public int usuarioId;
        public int rolId;
        public string nombrePersonaje;
        public Color colorPersonaje = Color.white;
        public bool esActivo = false;
        
        [Header("Componentes")]
        public Animator animator;
        public NavMeshAgent navAgent;
        public Renderer characterRenderer;
        public Light characterLight;
        public ParticleSystem talkingEffect;
        
        [Header("Posiciones")]
        public Transform[] posicionesDisponibles;
        public Transform posicionActual;
        public Transform posicionDestino;
        
        [Header("Animaciones")]
        public string animacionEsperando = "Idle";
        public string animacionHablando = "Talking";
        public string animacionCaminando = "Walking";
        public string animacionPensando = "Thinking";
        
        [Header("Efectos Visuales")]
        public GameObject highlightEffect;
        public GameObject nameTag;
        public TextMesh nameText;
        
        private Vector3 posicionInicial;
        private bool estaHablando = false;
        private bool estaEnMovimiento = false;
        private Coroutine movimientoCoroutine;
        
        private void Start()
        {
            InitializeCharacter();
            SuscribirseEventos();
        }
        
        private void OnDestroy()
        {
            DesuscribirseEventos();
        }
        
        private void InitializeCharacter()
        {
            // Configurar componentes
            if (animator == null)
                animator = GetComponent<Animator>();
            
            if (navAgent == null)
                navAgent = GetComponent<NavMeshAgent>();
            
            if (characterRenderer == null)
                characterRenderer = GetComponentInChildren<Renderer>();
            
            // Guardar posición inicial
            posicionInicial = transform.position;
            
            // Configurar color del personaje
            if (characterRenderer != null)
            {
                characterRenderer.material.color = colorPersonaje;
            }
            
            // Configurar luz del personaje
            if (characterLight != null)
            {
                characterLight.color = colorPersonaje;
                characterLight.enabled = false;
            }
            
            // Configurar nombre
            if (nameTag != null && nameText != null)
            {
                nameText.text = nombrePersonaje;
                nameTag.SetActive(false);
            }
            
            // Estado inicial
            SetEstado("esperando");
        }
        
        private void SuscribirseEventos()
        {
            // Suscribirse a eventos de la API
            LaravelAPI.OnDialogoUpdated += OnDialogoUpdated;
            LaravelAPI.OnRespuestasReceived += OnRespuestasReceived;
        }
        
        private void DesuscribirseEventos()
        {
            // Desuscribirse de eventos
            LaravelAPI.OnDialogoUpdated -= OnDialogoUpdated;
            LaravelAPI.OnRespuestasReceived -= OnRespuestasReceived;
        }
        
        #region Event Handlers
        
        private void OnDialogoUpdated(DialogoEstado estado)
        {
            if (estado.participantes == null) return;
            
            // Buscar este personaje en los participantes
            var participante = estado.participantes.FirstOrDefault(p => p.usuario_id == usuarioId);
            if (participante == null) return;
            
            // Actualizar estado del personaje
            esActivo = participante.es_turno;
            UpdateCharacterState(esActivo, estado.nodo_actual);
        }
        
        private void OnRespuestasReceived(List<RespuestaUsuario> respuestas)
        {
            // Si es nuestro turno y tenemos respuestas, mostrar animación de pensamiento
            if (esActivo && respuestas.Count > 0)
            {
                SetEstado("pensando");
            }
        }
        
        #endregion
        
        #region Character State Management
        
        public void UpdateCharacterState(bool esTurno, NodoActual nodoActual)
        {
            esActivo = esTurno;
            
            if (esTurno)
            {
                SetEstado("hablando");
                MoverAPosicionDestino();
            }
            else
            {
                SetEstado("esperando");
                MoverAPosicionInicial();
            }
            
            // Actualizar efectos visuales
            UpdateVisualEffects();
        }
        
        public void SetEstado(string estado)
        {
            switch (estado.ToLower())
            {
                case "esperando":
                    SetAnimacion(animacionEsperando);
                    estaHablando = false;
                    break;
                    
                case "hablando":
                    SetAnimacion(animacionHablando);
                    estaHablando = true;
                    break;
                    
                case "pensando":
                    SetAnimacion(animacionPensando);
                    estaHablando = false;
                    break;
                    
                case "caminando":
                    SetAnimacion(animacionCaminando);
                    estaHablando = false;
                    break;
            }
        }
        
        private void SetAnimacion(string nombreAnimacion)
        {
            if (animator != null && !string.IsNullOrEmpty(nombreAnimacion))
            {
                animator.Play(nombreAnimacion);
            }
        }
        
        #endregion
        
        #region Movement
        
        public void MoverAPosicionDestino()
        {
            if (posicionDestino != null && navAgent != null)
            {
                MoverA(posicionDestino.position);
            }
        }
        
        public void MoverAPosicionInicial()
        {
            MoverA(posicionInicial);
        }
        
        public void MoverA(Vector3 destino)
        {
            if (navAgent != null)
            {
                navAgent.SetDestination(destino);
                estaEnMovimiento = true;
                SetEstado("caminando");
                
                // Iniciar corrutina para verificar llegada
                if (movimientoCoroutine != null)
                {
                    StopCoroutine(movimientoCoroutine);
                }
                movimientoCoroutine = StartCoroutine(VerificarLlegada());
            }
        }
        
        private IEnumerator VerificarLlegada()
        {
            while (estaEnMovimiento && navAgent != null)
            {
                if (!navAgent.pathPending && navAgent.remainingDistance < 0.5f)
                {
                    estaEnMovimiento = false;
                    SetEstado(esActivo ? "hablando" : "esperando");
                    break;
                }
                yield return new WaitForSeconds(0.1f);
            }
        }
        
        #endregion
        
        #region Visual Effects
        
        private void UpdateVisualEffects()
        {
            // Actualizar luz del personaje
            if (characterLight != null)
            {
                characterLight.enabled = esActivo;
                if (esActivo)
                {
                    characterLight.color = colorPersonaje;
                }
            }
            
            // Actualizar efecto de resaltado
            if (highlightEffect != null)
            {
                highlightEffect.SetActive(esActivo);
            }
            
            // Actualizar nombre
            if (nameTag != null)
            {
                nameTag.SetActive(esActivo);
            }
            
            // Actualizar efecto de habla
            if (talkingEffect != null)
            {
                if (estaHablando && esActivo)
                {
                    if (!talkingEffect.isPlaying)
                    {
                        talkingEffect.Play();
                    }
                }
                else
                {
                    if (talkingEffect.isPlaying)
                    {
                        talkingEffect.Stop();
                    }
                }
            }
        }
        
        public void SetColor(Color nuevoColor)
        {
            colorPersonaje = nuevoColor;
            
            if (characterRenderer != null)
            {
                characterRenderer.material.color = nuevoColor;
            }
            
            if (characterLight != null)
            {
                characterLight.color = nuevoColor;
            }
        }
        
        public void SetNombre(string nuevoNombre)
        {
            nombrePersonaje = nuevoNombre;
            
            if (nameText != null)
            {
                nameText.text = nuevoNombre;
            }
        }
        
        #endregion
        
        #region Public Methods
        
        /// <summary>
        /// Configurar información del personaje
        /// </summary>
        public void ConfigurarPersonaje(int usuarioId, int rolId, string nombre, Color color)
        {
            this.usuarioId = usuarioId;
            this.rolId = rolId;
            this.nombrePersonaje = nombre;
            this.colorPersonaje = color;
            
            SetNombre(nombre);
            SetColor(color);
        }
        
        /// <summary>
        /// Obtener información del personaje
        /// </summary>
        public PersonajeInfo GetPersonajeInfo()
        {
            return new PersonajeInfo
            {
                usuarioId = this.usuarioId,
                rolId = this.rolId,
                nombre = this.nombrePersonaje,
                color = this.colorPersonaje,
                esActivo = this.esActivo,
                estaHablando = this.estaHablando,
                posicion = transform.position,
                rotacion = transform.rotation
            };
        }
        
        /// <summary>
        /// Reproducir animación específica
        /// </summary>
        public void PlayAnimacion(string nombreAnimacion, float duracion = 0f)
        {
            SetAnimacion(nombreAnimacion);
            
            if (duracion > 0f)
            {
                StartCoroutine(VolverAEstadoAnterior(duracion));
            }
        }
        
        private IEnumerator VolverAEstadoAnterior(float duracion)
        {
            yield return new WaitForSeconds(duracion);
            SetEstado(esActivo ? "hablando" : "esperando");
        }
        
        #endregion
        
        #region Debug
        
        private void OnDrawGizmos()
        {
            // Dibujar posición inicial
            Gizmos.color = Color.green;
            Gizmos.DrawWireSphere(posicionInicial, 0.5f);
            
            // Dibujar posición destino
            if (posicionDestino != null)
            {
                Gizmos.color = Color.red;
                Gizmos.DrawWireSphere(posicionDestino.position, 0.5f);
            }
            
            // Dibujar ruta del NavMesh
            if (navAgent != null && navAgent.hasPath)
            {
                Gizmos.color = Color.blue;
                Vector3[] path = navAgent.path.corners;
                for (int i = 0; i < path.Length - 1; i++)
                {
                    Gizmos.DrawLine(path[i], path[i + 1]);
                }
            }
        }
        
        #endregion
    }
    
    [System.Serializable]
    public class PersonajeInfo
    {
        public int usuarioId;
        public int rolId;
        public string nombre;
        public Color color;
        public bool esActivo;
        public bool estaHablando;
        public Vector3 posicion;
        public Quaternion rotacion;
    }
}

