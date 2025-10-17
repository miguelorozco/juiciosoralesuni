<?php

/**
 * @OA\Info(
 *     title="Simulador de Juicios Orales Mexicanos API",
 *     version="1.0.0",
 *     description="API REST para el sistema de simulación de juicios orales mexicanos desarrollado para la Universidad de Celaya",
 *     @OA\Contact(
 *         email="admin@juiciosorales.site",
 *         name="Equipo de Desarrollo"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Servidor de Desarrollo"
 * )
 * 
 * @OA\Server(
 *     url="https://api.juiciosorales.site",
 *     description="Servidor de Producción"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Autenticación JWT. Incluye el token en el header Authorization: Bearer {token}"
 * )
 * 
 * @OA\Tag(
 *     name="Autenticación",
 *     description="Endpoints para autenticación y gestión de usuarios"
 * )
 * 
 * @OA\Tag(
 *     name="Roles",
 *     description="Gestión de roles disponibles en el sistema"
 * )
 * 
 * @OA\Tag(
 *     name="Plantillas",
 *     description="Gestión de plantillas de sesiones de juicios"
 * )
 * 
 * @OA\Tag(
 *     name="Sesiones",
 *     description="Gestión de sesiones de juicios orales"
 * )
 * 
 * @OA\Tag(
 *     name="Asignaciones",
 *     description="Gestión de asignaciones de roles en sesiones"
 * )
 * 
 * @OA\Tag(
 *     name="Configuraciones",
 *     description="Configuraciones del sistema"
 * )
 * 
 * @OA\Tag(
 *     name="Usuarios",
 *     description="Administración de usuarios (solo admin)"
 * )
 * 
 * @OA\Tag(
 *     name="Estadísticas",
 *     description="Reportes y estadísticas del sistema"
 * )
 * 
 * @OA\Tag(
 *     name="Unity",
 *     description="Integración con Unity 3D"
 * )
 * 
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object"),
 *     @OA\Property(property="message", type="string", example="Operación exitosa")
 * )
 * 
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Descripción del error"),
 *     @OA\Property(property="errors", type="object", description="Errores de validación")
 * )
 * 
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *         @OA\Property(property="first_page_url", type="string"),
 *         @OA\Property(property="from", type="integer"),
 *         @OA\Property(property="last_page", type="integer"),
 *         @OA\Property(property="last_page_url", type="string"),
 *         @OA\Property(property="links", type="array", @OA\Items(type="object")),
 *         @OA\Property(property="next_page_url", type="string"),
 *         @OA\Property(property="path", type="string"),
 *         @OA\Property(property="per_page", type="integer"),
 *         @OA\Property(property="prev_page_url", type="string"),
 *         @OA\Property(property="to", type="integer"),
 *         @OA\Property(property="total", type="integer")
 *     ),
 *     @OA\Property(property="message", type="string", example="Datos obtenidos exitosamente")
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Juan"),
 *     @OA\Property(property="apellido", type="string", example="Pérez"),
 *     @OA\Property(property="email", type="string", example="juan.perez@ejemplo.com"),
 *     @OA\Property(property="tipo", type="string", enum={"admin", "instructor", "alumno"}, example="instructor"),
 *     @OA\Property(property="activo", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="RolDisponible",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Juez"),
 *     @OA\Property(property="descripcion", type="string", example="Preside el juicio y toma decisiones"),
 *     @OA\Property(property="color", type="string", example="#8B4513"),
 *     @OA\Property(property="icono", type="string", example="gavel"),
 *     @OA\Property(property="activo", type="boolean", example=true),
 *     @OA\Property(property="orden", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="PlantillaSesion",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Plantilla Civil"),
 *     @OA\Property(property="descripcion", type="string", example="Plantilla para juicios civiles"),
 *     @OA\Property(property="creado_por", type="integer", example=1),
 *     @OA\Property(property="publica", type="boolean", example=true),
 *     @OA\Property(property="fecha_creacion", type="string", format="date-time"),
 *     @OA\Property(property="configuracion", type="object"),
 *     @OA\Property(property="creador", ref="#/components/schemas/User"),
 *     @OA\Property(property="asignaciones", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="SesionJuicio",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Sesión Civil 001"),
 *     @OA\Property(property="descripcion", type="string", example="Primera sesión civil"),
 *     @OA\Property(property="instructor_id", type="integer", example=1),
 *     @OA\Property(property="plantilla_id", type="integer", example=1),
 *     @OA\Property(property="estado", type="string", enum={"programada", "en_curso", "finalizada", "cancelada"}, example="programada"),
 *     @OA\Property(property="fecha_creacion", type="string", format="date-time"),
 *     @OA\Property(property="fecha_inicio", type="string", format="date-time"),
 *     @OA\Property(property="fecha_fin", type="string", format="date-time"),
 *     @OA\Property(property="max_participantes", type="integer", example=20),
 *     @OA\Property(property="configuracion", type="object"),
 *     @OA\Property(property="unity_room_id", type="string", example="sesion_1_1234567890"),
 *     @OA\Property(property="instructor", ref="#/components/schemas/User"),
 *     @OA\Property(property="plantilla", ref="#/components/schemas/PlantillaSesion"),
 *     @OA\Property(property="asignaciones", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="AsignacionRol",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="sesion_id", type="integer", example=1),
 *     @OA\Property(property="usuario_id", type="integer", example=2),
 *     @OA\Property(property="rol_id", type="integer", example=1),
 *     @OA\Property(property="asignado_por", type="integer", example=1),
 *     @OA\Property(property="fecha_asignacion", type="string", format="date-time"),
 *     @OA\Property(property="confirmado", type="boolean", example=false),
 *     @OA\Property(property="notas", type="string", example="Participante principal"),
 *     @OA\Property(property="sesion", ref="#/components/schemas/SesionJuicio"),
 *     @OA\Property(property="usuario", ref="#/components/schemas/User"),
 *     @OA\Property(property="rol", ref="#/components/schemas/RolDisponible"),
 *     @OA\Property(property="asignadoPor", ref="#/components/schemas/User"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="ConfiguracionSistema",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="clave", type="string", example="sistema_nombre"),
 *     @OA\Property(property="valor", type="string", example="Simulador de Juicios Orales"),
 *     @OA\Property(property="descripcion", type="string", example="Nombre del sistema"),
 *     @OA\Property(property="tipo", type="string", enum={"string", "number", "boolean", "json"}, example="string"),
 *     @OA\Property(property="actualizado_por", type="integer", example=1),
 *     @OA\Property(property="fecha_actualizacion", type="string", format="date-time"),
 *     @OA\Property(property="actualizadoPor", ref="#/components/schemas/User"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="LoginRequest",
 *     type="object",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="admin@juiciosorales.site"),
 *     @OA\Property(property="password", type="string", format="password", example="password")
 * )
 * 
 * @OA\Schema(
 *     schema="RegisterRequest",
 *     type="object",
 *     required={"name", "apellido", "email", "password", "tipo"},
 *     @OA\Property(property="name", type="string", example="Juan"),
 *     @OA\Property(property="apellido", type="string", example="Pérez"),
 *     @OA\Property(property="email", type="string", format="email", example="juan.perez@ejemplo.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password123"),
 *     @OA\Property(property="tipo", type="string", enum={"admin", "instructor", "alumno"}, example="instructor")
 * )
 * 
 * @OA\Schema(
 *     schema="StoreRolRequest",
 *     type="object",
 *     required={"nombre"},
 *     @OA\Property(property="nombre", type="string", example="Juez", description="Nombre del rol"),
 *     @OA\Property(property="descripcion", type="string", example="Preside el juicio y toma decisiones"),
 *     @OA\Property(property="color", type="string", example="#8B4513", description="Color hexadecimal"),
 *     @OA\Property(property="icono", type="string", example="gavel"),
 *     @OA\Property(property="activo", type="boolean", example=true),
 *     @OA\Property(property="orden", type="integer", example=1)
 * )
 * 
 * @OA\Schema(
 *     schema="StorePlantillaRequest",
 *     type="object",
 *     required={"nombre"},
 *     @OA\Property(property="nombre", type="string", example="Plantilla Civil"),
 *     @OA\Property(property="descripcion", type="string", example="Plantilla para juicios civiles"),
 *     @OA\Property(property="publica", type="boolean", example=true),
 *     @OA\Property(property="configuracion", type="object"),
 *     @OA\Property(property="roles", type="array", @OA\Items(
 *         @OA\Property(property="rol_id", type="integer", example=1),
 *         @OA\Property(property="usuario_id", type="integer", example=2),
 *         @OA\Property(property="orden", type="integer", example=1)
 *     ))
 * )
 * 
 * @OA\Schema(
 *     schema="StoreSesionRequest",
 *     type="object",
 *     required={"nombre"},
 *     @OA\Property(property="nombre", type="string", example="Sesión Civil 001"),
 *     @OA\Property(property="descripcion", type="string", example="Primera sesión civil"),
 *     @OA\Property(property="plantilla_id", type="integer", example=1),
 *     @OA\Property(property="max_participantes", type="integer", example=20),
 *     @OA\Property(property="configuracion", type="object"),
 *     @OA\Property(property="participantes", type="array", @OA\Items(
 *         @OA\Property(property="usuario_id", type="integer", example=2),
 *         @OA\Property(property="rol_id", type="integer", example=1),
 *         @OA\Property(property="notas", type="string", example="Participante principal")
 *     ))
 * )
 * 
 * @OA\Schema(
 *     schema="StoreAsignacionRequest",
 *     type="object",
 *     required={"sesion_id", "usuario_id", "rol_id"},
 *     @OA\Property(property="sesion_id", type="integer", example=1),
 *     @OA\Property(property="usuario_id", type="integer", example=2),
 *     @OA\Property(property="rol_id", type="integer", example=1),
 *     @OA\Property(property="notas", type="string", example="Participante principal")
 * )
 * 
 * @OA\Schema(
 *     schema="StoreConfiguracionRequest",
 *     type="object",
 *     required={"clave", "valor", "tipo"},
 *     @OA\Property(property="clave", type="string", example="sistema_nombre"),
 *     @OA\Property(property="valor", type="string", example="Simulador de Juicios Orales"),
 *     @OA\Property(property="descripcion", type="string", example="Nombre del sistema"),
 *     @OA\Property(property="tipo", type="string", enum={"string", "number", "boolean", "json"}, example="string")
 * )
 * 
 * @OA\Schema(
 *     schema="ReordenarRolesRequest",
 *     type="object",
 *     required={"roles"},
 *     @OA\Property(property="roles", type="array", @OA\Items(
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="orden", type="integer", example=1)
 *     ))
 * )
 * 
 * @OA\Schema(
 *     schema="AgregarParticipanteRequest",
 *     type="object",
 *     required={"usuario_id", "rol_id"},
 *     @OA\Property(property="usuario_id", type="integer", example=2),
 *     @OA\Property(property="rol_id", type="integer", example=1),
 *     @OA\Property(property="notas", type="string", example="Participante principal")
 * )
 * 
 * @OA\Schema(
 *     schema="CambiarRolRequest",
 *     type="object",
 *     required={"rol_id"},
 *     @OA\Property(property="rol_id", type="integer", example=3)
 * )
 * 
 * @OA\Schema(
 *     schema="ActualizarNotasRequest",
 *     type="object",
 *     @OA\Property(property="notas", type="string", example="Nuevas notas del participante")
 * )
 * 
 * @OA\Schema(
 *     schema="EstablecerConfiguracionRequest",
 *     type="object",
 *     required={"clave", "valor", "tipo"},
 *     @OA\Property(property="clave", type="string", example="max_participantes"),
 *     @OA\Property(property="valor", type="string", example="20"),
 *     @OA\Property(property="descripcion", type="string", example="Máximo de participantes por sesión"),
 *     @OA\Property(property="tipo", type="string", enum={"string", "number", "boolean", "json"}, example="number")
 * )
 * 
 * @OA\Schema(
 *     schema="ActualizarMultiplesConfiguracionesRequest",
 *     type="object",
 *     required={"configuraciones"},
 *     @OA\Property(property="configuraciones", type="array", @OA\Items(
 *         @OA\Property(property="clave", type="string", example="sistema_nombre"),
 *         @OA\Property(property="valor", type="string", example="Simulador de Juicios Orales"),
 *         @OA\Property(property="tipo", type="string", enum={"string", "number", "boolean", "json"}, example="string"),
 *         @OA\Property(property="descripcion", type="string", example="Nombre del sistema")
 *     ))
 * )
 * 
 * @OA\Schema(
 *     schema="LoginResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
 *         @OA\Property(property="token_type", type="string", example="bearer"),
 *         @OA\Property(property="expires_in", type="integer", example=3600),
 *         @OA\Property(property="user", ref="#/components/schemas/User")
 *     ),
 *     @OA\Property(property="message", type="string", example="Login exitoso")
 * )
 * 
 * @OA\Schema(
 *     schema="ErrorValidation",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="El nombre debe tener al menos 2 caracteres. (and 3 more errors)"),
 *     @OA\Property(property="errors", type="object",
 *         @OA\Property(property="nombre", type="array", @OA\Items(type="string", example="El nombre debe tener al menos 2 caracteres.")),
 *         @OA\Property(property="descripcion", type="array", @OA\Items(type="string", example="La descripción debe tener al menos 10 caracteres.")),
 *         @OA\Property(property="color", type="array", @OA\Items(type="string", example="El color debe ser un código hexadecimal válido (ej: #FF0000).")),
 *         @OA\Property(property="activo", type="array", @OA\Items(type="string", example="El estado activo debe ser verdadero o falso."))
 *     )
 * )
 */
