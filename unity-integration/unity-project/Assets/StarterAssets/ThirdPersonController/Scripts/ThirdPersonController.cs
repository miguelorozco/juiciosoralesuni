 using UnityEngine;
#if ENABLE_INPUT_SYSTEM 
using UnityEngine.InputSystem;
#endif

/* Note: animations are called via the controller for both the character and capsule using animator null checks
 */

namespace StarterAssets
{
    [RequireComponent(typeof(CharacterController))]
#if ENABLE_INPUT_SYSTEM 
    [RequireComponent(typeof(PlayerInput))]
#endif
    public class ThirdPersonController : MonoBehaviour
    {
        [Header("Player")]
        [Tooltip("Move speed of the character in m/s")]
        public float MoveSpeed = 2.0f;

        [Tooltip("Sprint speed of the character in m/s")]
        public float SprintSpeed = 5.335f;

        [Tooltip("How fast the character turns to face movement direction")]
        [Range(0.0f, 0.3f)]
        public float RotationSmoothTime = 0.12f;

        [Tooltip("Acceleration and deceleration")]
        public float SpeedChangeRate = 10.0f;

        public AudioClip LandingAudioClip;
        public AudioClip[] FootstepAudioClips;
        [Range(0, 1)] public float FootstepAudioVolume = 0.5f;

        [Space(10)]
        [Tooltip("The height the player can jump")]
        public float JumpHeight = 1.2f;

        [Tooltip("The character uses its own gravity value. The engine default is -9.81f")]
        public float Gravity = -15.0f;

        [Space(10)]
        [Tooltip("Time required to pass before being able to jump again. Set to 0f to instantly jump again")]
        public float JumpTimeout = 0.50f;

        [Tooltip("Time required to pass before entering the fall state. Useful for walking down stairs")]
        public float FallTimeout = 0.15f;

        [Header("Player Grounded")]
        [Tooltip("If the character is grounded or not. Not part of the CharacterController built in grounded check")]
        public bool Grounded = true;

        [Tooltip("Useful for rough ground")]
        public float GroundedOffset = -0.14f;

        [Tooltip("The radius of the grounded check. Should match the radius of the CharacterController")]
        public float GroundedRadius = 0.28f;

        [Tooltip("What layers the character uses as ground")]
        public LayerMask GroundLayers;

        [Header("Cinemachine")]
        [Tooltip("The follow target set in the Cinemachine Virtual Camera that the camera will follow")]
        public GameObject CinemachineCameraTarget;

        [Tooltip("How far in degrees can you move the camera up")]
        public float TopClamp = 70.0f;

        [Tooltip("How far in degrees can you move the camera down")]
        public float BottomClamp = -30.0f;

        [Tooltip("Additional degress to override the camera. Useful for fine tuning camera position when locked")]
        public float CameraAngleOverride = 0.0f;

        [Tooltip("For locking the camera position on all axis")]
        public bool LockCameraPosition = false;

        // cinemachine
        private float _cinemachineTargetYaw;
        private float _cinemachineTargetPitch;

        // player
        private float _speed;
        private float _animationBlend;
        private float _targetRotation = 0.0f;
        private float _rotationVelocity;
        private float _verticalVelocity;
        private float _terminalVelocity = 53.0f;

        // timeout deltatime
        private float _jumpTimeoutDelta;
        private float _fallTimeoutDelta;

        // animation IDs
        private int _animIDSpeed;
        private int _animIDGrounded;
        private int _animIDJump;
        private int _animIDFreeFall;
        private int _animIDMotionSpeed;

#if ENABLE_INPUT_SYSTEM 
        private PlayerInput _playerInput;
#endif
        private Animator _animator;
        private CharacterController _controller;
        private StarterAssetsInputs _input;
        private GameObject _mainCamera;

        private const float _threshold = 0.01f;

        private bool _hasAnimator;

        private bool IsCurrentDeviceMouse
        {
            get
            {
#if ENABLE_INPUT_SYSTEM
                return _playerInput.currentControlScheme == "KeyboardMouse";
#else
				return false;
#endif
            }
        }


        private void Awake()
        {
            // get a reference to our main camera
            if (_mainCamera == null)
            {
                _mainCamera = GameObject.FindGameObjectWithTag("MainCamera");
            }
        }

        private void Start()
        {
            _cinemachineTargetYaw = CinemachineCameraTarget.transform.rotation.eulerAngles.y;
            
            _hasAnimator = TryGetComponent(out _animator);
            _controller = GetComponent<CharacterController>();
            
            // CRÍTICO: Verificar si este ThirdPersonController pertenece al jugador local ANTES de obtener StarterAssetsInputs
            var parentPlayerController = GetComponentInParent<PlayerController>();
            bool isLocalPlayer = parentPlayerController != null && parentPlayerController.IsLocalPlayer();
            
            // CRÍTICO: Si NO es el jugador local, DESHABILITAR completamente este ThirdPersonController
            if (!isLocalPlayer)
            {
                Debug.LogWarning($"[ThirdPersonController] ⚠️ Este ThirdPersonController pertenece a un jugador REMOTO ({gameObject.name}). DESHABILITANDO completamente.");
                enabled = false;
                return; // Salir inmediatamente sin inicializar nada
            }
            
            Debug.Log($"[ThirdPersonController] ✅ Este ThirdPersonController pertenece al jugador LOCAL ({gameObject.name}). Inicializando...");
            
            // CRÍTICO: Obtener StarterAssetsInputs SOLO del mismo GameObject o de sus hijos DIRECTOS
            // NUNCA usar FindObjectsOfType o buscar en otros GameObjects
            _input = GetComponent<StarterAssetsInputs>();
            if (_input == null)
            {
                // Buscar SOLO en los hijos directos de este GameObject
                _input = GetComponentInChildren<StarterAssetsInputs>(false); // false = solo hijos directos
            }
            
            // Si aún no se encontró, buscar en toda la jerarquía de hijos (pero solo de este GameObject)
            if (_input == null)
            {
                _input = GetComponentInChildren<StarterAssetsInputs>(true);
            }
            
            // DEBUG: Log detallado sobre qué StarterAssetsInputs se obtuvo
            if (_input != null)
            {
                Debug.Log($"[ThirdPersonController] ✅ Start() - StarterAssetsInputs encontrado en GameObject: {gameObject.name}");
                Debug.Log($"[ThirdPersonController]   - StarterAssetsInputs GameObject: {_input.gameObject.name}");
                Debug.Log($"[ThirdPersonController]   - StarterAssetsInputs position: {_input.transform.position}");
                Debug.Log($"[ThirdPersonController]   - StarterAssetsInputs enabled: {_input.enabled}");
                
                // CRÍTICO: Verificar que el StarterAssetsInputs pertenezca a este GameObject o sus hijos
                bool belongsToThisObject = _input.transform.IsChildOf(transform) || _input.gameObject == gameObject;
                if (!belongsToThisObject)
                {
                    Debug.LogError($"[ThirdPersonController] ❌ CRÍTICO: StarterAssetsInputs NO pertenece a este GameObject!");
                    Debug.LogError($"[ThirdPersonController]   - StarterAssetsInputs: {_input.gameObject.name}");
                    Debug.LogError($"[ThirdPersonController]   - GameObject esperado: {gameObject.name}");
                    _input = null; // Invalidar la referencia incorrecta
                }
                
                // Verificar el parent del ThirdPersonController para ver a qué PlayerController pertenece
                if (parentPlayerController != null)
                {
                    Debug.Log($"[ThirdPersonController]   - Parent PlayerController: {parentPlayerController.gameObject.name} (IsLocalPlayer: {parentPlayerController.IsLocalPlayer()})");
                    var parentAvatar = parentPlayerController.GetCurrentAvatar();
                    Debug.Log($"[ThirdPersonController]   - Parent Avatar: {(parentAvatar != null ? parentAvatar.name : "NULL")}");
                    
                    // CRÍTICO: Verificar que el StarterAssetsInputs pertenezca al avatar del parent PlayerController
                    if (parentAvatar != null && _input != null)
                    {
                        bool belongsToAvatar = _input.transform.IsChildOf(parentAvatar.transform) || _input.gameObject == parentAvatar;
                        if (!belongsToAvatar)
                        {
                            Debug.LogError($"[ThirdPersonController] ❌ CRÍTICO: StarterAssetsInputs NO pertenece al avatar del parent PlayerController!");
                            Debug.LogError($"[ThirdPersonController]   - StarterAssetsInputs: {_input.gameObject.name}");
                            Debug.LogError($"[ThirdPersonController]   - Avatar esperado: {parentAvatar.name}");
                            
                            // Buscar el StarterAssetsInputs correcto en el avatar
                            var correctInput = parentAvatar.GetComponentInChildren<StarterAssetsInputs>(true);
                            if (correctInput != null)
                            {
                                Debug.Log($"[ThirdPersonController] ✅ Encontrado StarterAssetsInputs correcto en avatar: {correctInput.gameObject.name}");
                                _input = correctInput;
                            }
                            else
                            {
                                Debug.LogError($"[ThirdPersonController] ❌ NO se encontró StarterAssetsInputs en el avatar correcto!");
                                _input = null; // Invalidar la referencia incorrecta
                            }
                        }
                        else
                        {
                            Debug.Log($"[ThirdPersonController] ✅ StarterAssetsInputs pertenece al avatar correcto");
                        }
                    }
                }
            }
            else
            {
                Debug.LogError($"[ThirdPersonController] ❌ Start() - StarterAssetsInputs NO encontrado en GameObject: {gameObject.name}");
                Debug.LogError($"[ThirdPersonController] 🔍 Este ThirdPersonController NO funcionará sin StarterAssetsInputs!");
            }
            
#if ENABLE_INPUT_SYSTEM 
            _playerInput = GetComponent<PlayerInput>();
#else
			Debug.LogError( "Starter Assets package is missing dependencies. Please use Tools/Starter Assets/Reinstall Dependencies to fix it");
#endif

            AssignAnimationIDs();

            // reset our timeouts on start
            _jumpTimeoutDelta = JumpTimeout;
            _fallTimeoutDelta = FallTimeout;
        }

        private void Update()
        {
            // DEBUG: Log cada frame cuando hay input
            bool hasInput = _input != null && _input.move.magnitude > 0.01f;
            
            // CRÍTICO: Verificar que este ThirdPersonController pertenezca al jugador local
            // Buscar PlayerController en el padre (el avatar puede ser hijo del PlayerController)
            var parentPlayerController = GetComponentInParent<PlayerController>();
            
            // Si no se encuentra en el parent, buscar en toda la jerarquía hacia arriba
            if (parentPlayerController == null)
            {
                Transform current = transform.parent;
                while (current != null && parentPlayerController == null)
                {
                    parentPlayerController = current.GetComponent<PlayerController>();
                    current = current.parent;
                }
            }
            
            // DEBUG: Simplificar para debugging - como solo Player_Juez está activo, siempre asumir que es local
            // Esto permite que el movimiento funcione sin depender de la jerarquía de PlayerController
            bool isLocalPlayer = true; // Por defecto, asumir que es local para debugging
            
            // Solo verificar PlayerController si existe, pero no bloquear si no existe
            if (parentPlayerController != null)
            {
                isLocalPlayer = parentPlayerController.IsLocalPlayer();
                if (!isLocalPlayer && Time.frameCount % 120 == 0)
                {
                    Debug.LogWarning($"[ThirdPersonController] ⚠️ PlayerController dice que NO es local, pero continuando para debugging.");
                }
            }
            else
            {
                // Si no hay PlayerController padre, asumir que es local (solo hay un player activo)
                if (Time.frameCount % 120 == 0)
                {
                    Debug.Log($"[ThirdPersonController] ℹ️ No se encontró PlayerController padre. Asumiendo que es jugador local (solo Player_Juez está activo).");
                }
            }
            
            // FORZAR que siempre sea local para debugging cuando solo hay un player
            isLocalPlayer = true;
            
            if (hasInput || Time.frameCount % 60 == 0)
            {
                Debug.Log($"[ThirdPersonController] 🔍 Update() ejecutándose | GameObject: {gameObject.name}");
                Debug.Log($"[ThirdPersonController]   - parentPlayerController: {(parentPlayerController != null ? parentPlayerController.gameObject.name : "NULL")}");
                Debug.Log($"[ThirdPersonController]   - isLocalPlayer: {isLocalPlayer}");
                Debug.Log($"[ThirdPersonController]   - enabled: {enabled}");
                Debug.Log($"[ThirdPersonController]   - _input: {(_input != null ? _input.gameObject.name : "NULL")}");
                Debug.Log($"[ThirdPersonController]   - _controller: {(_controller != null ? "OK" : "NULL")}");
                if (_input != null)
                {
                    Debug.Log($"[ThirdPersonController]   - _input.move: {_input.move}");
                    Debug.Log($"[ThirdPersonController]   - _input.enabled: {_input.enabled}");
                }
                if (_controller != null)
                {
                    Debug.Log($"[ThirdPersonController]   - _controller.enabled: {_controller.enabled}");
                    Debug.Log($"[ThirdPersonController]   - _controller.velocity: {_controller.velocity}");
                }
            }
            
            // CRÍTICO: Si NO es el jugador local, NO ejecutar nada y deshabilitar este componente
            if (!isLocalPlayer)
            {
                if (enabled)
                {
                    Debug.LogWarning($"[ThirdPersonController] ⚠️ Este ThirdPersonController pertenece a un jugador REMOTO ({gameObject.name}). DESHABILITANDO.");
                    enabled = false;
                }
                return; // Salir inmediatamente sin ejecutar nada
            }
            
            // CRÍTICO: Verificar que _input pertenezca al avatar correcto
            if (_input != null)
            {
                if (parentPlayerController != null)
                {
                    var parentAvatar = parentPlayerController.GetCurrentAvatar();
                    if (parentAvatar != null)
                    {
                        bool belongsToAvatar = _input.transform.IsChildOf(parentAvatar.transform) || _input.gameObject == parentAvatar;
                        if (!belongsToAvatar)
                        {
                            // Buscar el StarterAssetsInputs correcto en el avatar
                            var correctInput = parentAvatar.GetComponentInChildren<StarterAssetsInputs>(true);
                            if (correctInput != null && correctInput != _input)
                            {
                                Debug.LogWarning($"[ThirdPersonController] ⚠️ CRÍTICO: StarterAssetsInputs incorrecto detectado en Update(). Corrigiendo...");
                                Debug.LogWarning($"[ThirdPersonController]   - StarterAssetsInputs actual: {_input.gameObject.name}");
                                Debug.LogWarning($"[ThirdPersonController]   - StarterAssetsInputs correcto: {correctInput.gameObject.name}");
                                Debug.LogWarning($"[ThirdPersonController]   - Avatar esperado: {parentAvatar.name}");
                                _input = correctInput;
                            }
                        }
                    }
                    else
                    {
                        Debug.LogError($"[ThirdPersonController] ❌ CRÍTICO: Avatar es NULL para PlayerController: {parentPlayerController.gameObject.name}");
                        return; // No ejecutar movimiento si no hay avatar
                    }
                }
            }
            
            // CRÍTICO: Si _input es NULL, intentar obtenerlo de nuevo
            if (_input == null)
            {
                Debug.LogWarning("[ThirdPersonController] ⚠️ _input es NULL en Update(). Intentando obtenerlo de nuevo...");
                _input = GetComponent<StarterAssetsInputs>();
                if (_input == null)
                {
                    _input = GetComponentInChildren<StarterAssetsInputs>(true);
                }
                if (_input == null)
                {
                    Debug.LogError("[ThirdPersonController] ❌ CRÍTICO: _input (StarterAssetsInputs) es NULL en Update() después de reintento. El movimiento no funcionará.");
                    return;
                }
                else
                {
                    Debug.Log($"[ThirdPersonController] ✅ StarterAssetsInputs encontrado en Update(): {_input.gameObject.name}");
                }
            }
            
            _hasAnimator = TryGetComponent(out _animator);

            JumpAndGravity();
            GroundedCheck();
            Move();
        }

        private void LateUpdate()
        {
            // CRÍTICO: Verificar que _input no sea NULL antes de llamar a CameraRotation
            if (_input == null)
            {
                // Intentar obtenerlo de nuevo
                _input = GetComponent<StarterAssetsInputs>();
                if (_input == null)
                {
                    _input = GetComponentInChildren<StarterAssetsInputs>(true);
                }
                if (_input == null)
                {
                    return; // Salir si no se puede encontrar
                }
            }
            
            CameraRotation();
        }

        private void AssignAnimationIDs()
        {
            _animIDSpeed = Animator.StringToHash("Speed");
            _animIDGrounded = Animator.StringToHash("Grounded");
            _animIDJump = Animator.StringToHash("Jump");
            _animIDFreeFall = Animator.StringToHash("FreeFall");
            _animIDMotionSpeed = Animator.StringToHash("MotionSpeed");
        }

        private void GroundedCheck()
        {
            // set sphere position, with offset
            Vector3 spherePosition = new Vector3(transform.position.x, transform.position.y - GroundedOffset,
                transform.position.z);
            Grounded = Physics.CheckSphere(spherePosition, GroundedRadius, GroundLayers,
                QueryTriggerInteraction.Ignore);

            // update animator if using character
            if (_hasAnimator)
            {
                _animator.SetBool(_animIDGrounded, Grounded);
            }
        }

        private void CameraRotation()
        {
            // CRÍTICO: Verificar que _input no sea NULL
            if (_input == null)
            {
                return; // Salir si no hay input
            }
            
            // if there is an input and camera position is not fixed
            if (_input.look.sqrMagnitude >= _threshold && !LockCameraPosition)
            {
                //Don't multiply mouse input by Time.deltaTime;
                float deltaTimeMultiplier = IsCurrentDeviceMouse ? 1.0f : Time.deltaTime;

                _cinemachineTargetYaw += _input.look.x * deltaTimeMultiplier;
                _cinemachineTargetPitch += _input.look.y * deltaTimeMultiplier;
            }

            // clamp our rotations so our values are limited 360 degrees
            _cinemachineTargetYaw = ClampAngle(_cinemachineTargetYaw, float.MinValue, float.MaxValue);
            _cinemachineTargetPitch = ClampAngle(_cinemachineTargetPitch, BottomClamp, TopClamp);

            // Cinemachine will follow this target
            CinemachineCameraTarget.transform.rotation = Quaternion.Euler(_cinemachineTargetPitch + CameraAngleOverride,
                _cinemachineTargetYaw, 0.0f);
        }

        private static int moveCallCount = 0;
        private static int lastLoggedFrame = -1;
        
        private void Move()
        {
            moveCallCount++;
            
            // DEBUG: Log detallado cada frame cuando hay input, o cada 30 frames sin input
            bool hasInput = _input != null && _input.move.magnitude > 0.01f;
            bool shouldLog = hasInput || (Time.frameCount % 30 == 0);
            if (shouldLog && Time.frameCount != lastLoggedFrame)
            {
                lastLoggedFrame = Time.frameCount;
                Debug.Log($"[ThirdPersonController] 🔍 MOVE() LLAMADO #{moveCallCount} | GameObject: {gameObject.name}");
                Debug.Log($"[ThirdPersonController]   - _input: {(_input != null ? "OK" : "NULL")}");
                Debug.Log($"[ThirdPersonController]   - _input.move: {(_input != null ? _input.move.ToString() : "N/A")}");
                Debug.Log($"[ThirdPersonController]   - _controller: {(_controller != null ? "OK" : "NULL")}");
                Debug.Log($"[ThirdPersonController]   - _controller.enabled: {(_controller != null ? _controller.enabled.ToString() : "N/A")}");
                Debug.Log($"[ThirdPersonController]   - _controller.isGrounded: {(_controller != null ? _controller.isGrounded.ToString() : "N/A")}");
                Debug.Log($"[ThirdPersonController]   - _controller.velocity: {(_controller != null ? _controller.velocity.ToString() : "N/A")}");
                Debug.Log($"[ThirdPersonController]   - transform.position: {transform.position}");
                Debug.Log($"[ThirdPersonController]   - MoveSpeed: {MoveSpeed}, SprintSpeed: {SprintSpeed}");
            }
            
            if (_input == null)
            {
                if (shouldLog && Time.frameCount != lastLoggedFrame)
                {
                    Debug.LogError("[ThirdPersonController] ❌ CRÍTICO: _input es NULL en Move()!");
                }
                return;
            }
            
            if (_controller == null)
            {
                if (shouldLog && Time.frameCount != lastLoggedFrame)
                {
                    Debug.LogError("[ThirdPersonController] ❌ CRÍTICO: _controller es NULL en Move()!");
                }
                return;
            }
            
            if (!_controller.enabled)
            {
                if (shouldLog && Time.frameCount != lastLoggedFrame)
                {
                    Debug.LogError("[ThirdPersonController] ❌ CRÍTICO: _controller está DESHABILITADO!");
                }
                return;
            }
            
            // set target speed based on move speed, sprint speed and if sprint is pressed
            float targetSpeed = _input.sprint ? SprintSpeed : MoveSpeed;

            // a simplistic acceleration and deceleration designed to be easy to remove, replace, or iterate upon

            // note: Vector2's == operator uses approximation so is not floating point error prone, and is cheaper than magnitude
            // if there is no input, set the target speed to 0
            if (_input.move == Vector2.zero) targetSpeed = 0.0f;

            // a reference to the players current horizontal velocity
            float currentHorizontalSpeed = new Vector3(_controller.velocity.x, 0.0f, _controller.velocity.z).magnitude;

            float speedOffset = 0.1f;
            float inputMagnitude = _input.analogMovement ? _input.move.magnitude : 1f;

            // accelerate or decelerate to target speed
            if (currentHorizontalSpeed < targetSpeed - speedOffset ||
                currentHorizontalSpeed > targetSpeed + speedOffset)
            {
                // creates curved result rather than a linear one giving a more organic speed change
                // note T in Lerp is clamped, so we don't need to clamp our speed
                _speed = Mathf.Lerp(currentHorizontalSpeed, targetSpeed * inputMagnitude,
                    Time.deltaTime * SpeedChangeRate);

                // round speed to 3 decimal places
                _speed = Mathf.Round(_speed * 1000f) / 1000f;
            }
            else
            {
                _speed = targetSpeed;
            }

            _animationBlend = Mathf.Lerp(_animationBlend, targetSpeed, Time.deltaTime * SpeedChangeRate);
            if (_animationBlend < 0.01f) _animationBlend = 0f;

            // normalise input direction
            Vector3 inputDirection = new Vector3(_input.move.x, 0.0f, _input.move.y).normalized;

            // note: Vector2's != operator uses approximation so is not floating point error prone, and is cheaper than magnitude
            // if there is a move input rotate player when the player is moving
            if (_input.move != Vector2.zero)
            {
                float mainCamY = (_mainCamera != null) ? _mainCamera.transform.eulerAngles.y : 0f;
                _targetRotation = Mathf.Atan2(inputDirection.x, inputDirection.z) * Mathf.Rad2Deg +
                                  mainCamY;
                float rotation = Mathf.SmoothDampAngle(transform.eulerAngles.y, _targetRotation, ref _rotationVelocity,
                    RotationSmoothTime);

                // rotate to face input direction relative to camera position
                transform.rotation = Quaternion.Euler(0.0f, rotation, 0.0f);
            }


            Vector3 targetDirection = Quaternion.Euler(0.0f, _targetRotation, 0.0f) * Vector3.forward;
            Vector3 moveVector = targetDirection.normalized * (_speed * Time.deltaTime) +
                             new Vector3(0.0f, _verticalVelocity, 0.0f) * Time.deltaTime;
            
            // DEBUG: Log antes de mover
            Vector3 positionBefore = transform.position;
            if (shouldLog && Time.frameCount != lastLoggedFrame && moveVector.magnitude > 0.001f)
            {
                Debug.Log($"[ThirdPersonController] 🏃 ANTES DE MOVE: position={positionBefore}, moveVector={moveVector}, _speed={_speed}, targetSpeed={targetSpeed}");
            }

            // move the player
            _controller.Move(moveVector);
            
            // DEBUG: Log después de mover
            if (shouldLog && Time.frameCount != lastLoggedFrame && moveVector.magnitude > 0.001f)
            {
                Vector3 positionAfter = transform.position;
                Vector3 positionDelta = positionAfter - positionBefore;
                float distanceMoved = Vector3.Distance(positionAfter, positionBefore);
                Debug.Log($"[ThirdPersonController] 🏃 DESPUÉS DE MOVE: position={positionAfter}, delta={positionDelta}, distanceMoved={distanceMoved}, velocity={_controller.velocity}");
                
                if (distanceMoved < 0.001f)
                {
                    Debug.LogWarning("[ThirdPersonController] ⚠️ ADVERTENCIA: La posición NO cambió después de Move()! El CharacterController puede estar bloqueado.");
                }
            }

            // update animator if using character
            if (_hasAnimator)
            {
                _animator.SetFloat(_animIDSpeed, _animationBlend);
                _animator.SetFloat(_animIDMotionSpeed, inputMagnitude);
            }
        }

        private void JumpAndGravity()
        {
            if (Grounded)
            {
                // reset the fall timeout timer
                _fallTimeoutDelta = FallTimeout;

                // update animator if using character
                if (_hasAnimator)
                {
                    _animator.SetBool(_animIDJump, false);
                    _animator.SetBool(_animIDFreeFall, false);
                }

                // stop our velocity dropping infinitely when grounded
                if (_verticalVelocity < 0.0f)
                {
                    _verticalVelocity = -2f;
                }

                // Jump
                if (_input.jump && _jumpTimeoutDelta <= 0.0f)
                {
                    // the square root of H * -2 * G = how much velocity needed to reach desired height
                    _verticalVelocity = Mathf.Sqrt(JumpHeight * -2f * Gravity);

                    // update animator if using character
                    if (_hasAnimator)
                    {
                        _animator.SetBool(_animIDJump, true);
                    }
                }

                // jump timeout
                if (_jumpTimeoutDelta >= 0.0f)
                {
                    _jumpTimeoutDelta -= Time.deltaTime;
                }
            }
            else
            {
                // reset the jump timeout timer
                _jumpTimeoutDelta = JumpTimeout;

                // fall timeout
                if (_fallTimeoutDelta >= 0.0f)
                {
                    _fallTimeoutDelta -= Time.deltaTime;
                }
                else
                {
                    // update animator if using character
                    if (_hasAnimator)
                    {
                        _animator.SetBool(_animIDFreeFall, true);
                    }
                }

                // if we are not grounded, do not jump
                _input.jump = false;
            }

            // apply gravity over time if under terminal (multiply by delta time twice to linearly speed up over time)
            if (_verticalVelocity < _terminalVelocity)
            {
                _verticalVelocity += Gravity * Time.deltaTime;
            }
        }

        private static float ClampAngle(float lfAngle, float lfMin, float lfMax)
        {
            if (lfAngle < -360f) lfAngle += 360f;
            if (lfAngle > 360f) lfAngle -= 360f;
            return Mathf.Clamp(lfAngle, lfMin, lfMax);
        }

        private void OnDrawGizmosSelected()
        {
            Color transparentGreen = new Color(0.0f, 1.0f, 0.0f, 0.35f);
            Color transparentRed = new Color(1.0f, 0.0f, 0.0f, 0.35f);

            if (Grounded) Gizmos.color = transparentGreen;
            else Gizmos.color = transparentRed;

            // when selected, draw a gizmo in the position of, and matching radius of, the grounded collider
            Gizmos.DrawSphere(
                new Vector3(transform.position.x, transform.position.y - GroundedOffset, transform.position.z),
                GroundedRadius);
        }

        private void OnFootstep(AnimationEvent animationEvent)
        {
            if (animationEvent.animatorClipInfo.weight > 0.5f)
            {
                if (FootstepAudioClips.Length > 0)
                {
                    var index = Random.Range(0, FootstepAudioClips.Length);
                    AudioSource.PlayClipAtPoint(FootstepAudioClips[index], transform.TransformPoint(_controller.center), FootstepAudioVolume);
                }
            }
        }

        private void OnLand(AnimationEvent animationEvent)
        {
            if (animationEvent.animatorClipInfo.weight > 0.5f)
            {
                AudioSource.PlayClipAtPoint(LandingAudioClip, transform.TransformPoint(_controller.center), FootstepAudioVolume);
            }
        }
    }
}