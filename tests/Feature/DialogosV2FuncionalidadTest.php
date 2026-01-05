<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\DialogoV2;
use App\Models\NodoDialogoV2;
use App\Models\RespuestaDialogoV2;
use App\Models\SesionDialogoV2;
use App\Models\DecisionDialogoV2;
use App\Models\User;
use App\Models\SesionJuicio;
use App\Models\RolDisponible;
use Tests\TestCase;

class DialogosV2FuncionalidadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ejecutar todas las migraciones necesarias
        $this->artisan('migrate')->assertSuccessful();
    }

    /**
     * Test de creación de diálogo v2
     */
    public function test_crear_dialogo_v2(): void
    {
        $user = User::factory()->create(['tipo' => 'instructor']);

        $dialogo = DialogoV2::create([
            'nombre' => 'Test Diálogo v2',
            'descripcion' => 'Descripción del diálogo de prueba',
            'creado_por' => $user->id,
            'publico' => true,
            'estado' => 'borrador',
            'version' => '1.0.0',
            'configuracion' => [
                'tiempo_maximo' => 60,
                'permite_pausa' => true,
            ],
            'metadata_unity' => [
                'scene_name' => 'TestScene',
            ],
        ]);

        $this->assertDatabaseHas('dialogos_v2', [
            'id' => $dialogo->id,
            'nombre' => 'Test Diálogo v2',
            'estado' => 'borrador',
            'version' => '1.0.0',
        ]);

        $this->assertTrue($dialogo->publico);
        $this->assertEquals('1.0.0', $dialogo->version);
        $this->assertIsArray($dialogo->configuracion);
        $this->assertIsArray($dialogo->metadata_unity);
    }

    /**
     * Test de creación de nodos con posiciones directas
     */
    public function test_crear_nodos_con_posiciones_directas(): void
    {
        $user = User::factory()->create(['tipo' => 'instructor']);
        $rol = RolDisponible::factory()->create();

        $dialogo = DialogoV2::create([
            'nombre' => 'Test Diálogo',
            'creado_por' => $user->id,
            'estado' => 'borrador',
            'version' => '1.0.0',
        ]);

        $nodo = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rol->id,
            'titulo' => 'Nodo de Prueba',
            'contenido' => 'Contenido del nodo',
            'tipo' => 'decision',
            'posicion_x' => 100,
            'posicion_y' => 200,
            'es_inicial' => true,
            'es_final' => false,
            'orden' => 1,
            'activo' => true,
        ]);

        $this->assertDatabaseHas('nodos_dialogo_v2', [
            'id' => $nodo->id,
            'posicion_x' => 100,
            'posicion_y' => 200,
        ]);

        $this->assertEquals(100, $nodo->posicion_x);
        $this->assertEquals(200, $nodo->posicion_y);
        $this->assertEquals(['x' => 100, 'y' => 200], $nodo->posicion);
    }

    /**
     * Test de actualización de posición de nodo
     */
    public function test_actualizar_posicion_de_nodo(): void
    {
        $user = User::factory()->create(['tipo' => 'instructor']);
        $rol = RolDisponible::factory()->create();

        $dialogo = DialogoV2::create([
            'nombre' => 'Test Diálogo',
            'creado_por' => $user->id,
            'estado' => 'borrador',
            'version' => '1.0.0',
        ]);

        $nodo = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rol->id,
            'titulo' => 'Nodo',
            'contenido' => 'Contenido del nodo',
            'tipo' => 'decision',
            'posicion_x' => 0,
            'posicion_y' => 0,
            'orden' => 1,
            'activo' => true,
        ]);

        $nodo->actualizarPosicion(150, 250);

        $this->assertEquals(150, $nodo->fresh()->posicion_x);
        $this->assertEquals(250, $nodo->fresh()->posicion_y);
    }

    /**
     * Test de respuestas con usuarios no registrados
     */
    public function test_respuestas_con_usuarios_no_registrados(): void
    {
        $user = User::factory()->create(['tipo' => 'instructor']);
        $rol = RolDisponible::factory()->create();

        $dialogo = DialogoV2::create([
            'nombre' => 'Test Diálogo',
            'creado_por' => $user->id,
            'estado' => 'borrador',
            'version' => '1.0.0',
        ]);

        $nodo = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rol->id,
            'titulo' => 'Nodo',
            'contenido' => 'Contenido del nodo',
            'tipo' => 'decision',
            'posicion_x' => 0,
            'posicion_y' => 0,
            'orden' => 1,
            'activo' => true,
        ]);

        // Respuesta para usuarios registrados
        $respuestaRegistrada = RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodo->id,
            'texto' => 'Respuesta para registrados',
            'puntuacion' => 10,
            'requiere_usuario_registrado' => true,
            'es_opcion_por_defecto' => false,
            'orden' => 1,
            'activo' => true,
        ]);

        // Respuesta para usuarios no registrados (opción por defecto)
        $respuestaNoRegistrada = RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodo->id,
            'texto' => 'Respuesta por defecto',
            'puntuacion' => 5,
            'requiere_usuario_registrado' => false,
            'es_opcion_por_defecto' => true,
            'orden' => 2,
            'activo' => true,
        ]);

        // Obtener respuestas disponibles para usuario no registrado
        $respuestasDisponibles = RespuestaDialogoV2::disponiblesParaUsuario(
            $nodo->id,
            false, // usuario no registrado
            null,  // sin rol
            []      // sin variables
        );

        $this->assertCount(1, $respuestasDisponibles);
        $this->assertEquals($respuestaNoRegistrada->id, $respuestasDisponibles->first()->id);
        $this->assertTrue($respuestasDisponibles->first()->es_opcion_por_defecto);
    }

    /**
     * Test de flujo completo de diálogo
     */
    public function test_flujo_completo_de_dialogo(): void
    {
        $user = User::factory()->create(['tipo' => 'instructor']);
        $rol = RolDisponible::factory()->create();

        // Crear diálogo
        $dialogo = DialogoV2::create([
            'nombre' => 'Test Diálogo Completo',
            'creado_por' => $user->id,
            'estado' => 'activo',
            'version' => '1.0.0',
        ]);

        // Crear nodo inicial
        $nodoInicial = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rol->id,
            'titulo' => 'Nodo Inicial',
            'contenido' => 'Inicio del diálogo',
            'tipo' => 'inicio',
            'posicion_x' => 0,
            'posicion_y' => 0,
            'es_inicial' => true,
            'es_final' => false,
            'orden' => 1,
            'activo' => true,
        ]);

        // Crear nodo final
        $nodoFinal = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rol->id,
            'titulo' => 'Nodo Final',
            'contenido' => 'Fin del diálogo',
            'tipo' => 'final',
            'posicion_x' => 200,
            'posicion_y' => 200,
            'es_inicial' => false,
            'es_final' => true,
            'orden' => 2,
            'activo' => true,
        ]);

        // Crear respuesta que conecta inicial con final
        $respuesta = RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodoInicial->id,
            'nodo_siguiente_id' => $nodoFinal->id,
            'texto' => 'Continuar',
            'puntuacion' => 10,
            'orden' => 1,
            'activo' => true,
        ]);

        // Crear sesión de juicio
        $sesionJuicio = SesionJuicio::factory()->create([
            'instructor_id' => $user->id,
        ]);

        // Crear sesión de diálogo
        $sesionDialogo = SesionDialogoV2::create([
            'sesion_id' => $sesionJuicio->id,
            'dialogo_id' => $dialogo->id,
            'nodo_actual_id' => $nodoInicial->id,
            'estado' => 'iniciado',
            'variables' => [],
            'historial_nodos' => [],
            'audio_habilitado' => false,
        ]);

        // Iniciar diálogo
        $sesionDialogo->iniciar();

        $this->assertEquals('en_curso', $sesionDialogo->fresh()->estado);
        $this->assertEquals($nodoInicial->id, $sesionDialogo->fresh()->nodo_actual_id);

        // Procesar decisión
        $decision = $sesionDialogo->procesarDecision(
            $user->id,
            $rol->id,
            $respuesta->id,
            'Decisión del usuario',
            30 // tiempo de respuesta
        );

        $this->assertNotNull($decision);
        $this->assertInstanceOf(DecisionDialogoV2::class, $decision);
        $this->assertEquals($nodoFinal->id, $sesionDialogo->fresh()->nodo_actual_id);

        // Finalizar diálogo
        $sesionDialogo->finalizar();

        $this->assertEquals('finalizado', $sesionDialogo->fresh()->estado);
        $this->assertNotNull($sesionDialogo->fresh()->fecha_fin);
    }

    /**
     * Test de evaluación del profesor
     */
    public function test_evaluacion_del_profesor(): void
    {
        $user = User::factory()->create(['tipo' => 'instructor']);
        $estudiante = User::factory()->create(['tipo' => 'alumno']);
        $rol = RolDisponible::factory()->create();

        $dialogo = DialogoV2::create([
            'nombre' => 'Test Diálogo',
            'creado_por' => $user->id,
            'estado' => 'activo',
            'version' => '1.0.0',
        ]);

        $nodo = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rol->id,
            'titulo' => 'Nodo',
            'contenido' => 'Contenido del nodo',
            'tipo' => 'decision',
            'posicion_x' => 0,
            'posicion_y' => 0,
            'orden' => 1,
            'activo' => true,
        ]);

        $respuesta = RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodo->id,
            'texto' => 'Respuesta',
            'puntuacion' => 10,
            'orden' => 1,
            'activo' => true,
        ]);

        $sesionJuicio = SesionJuicio::factory()->create();
        $sesionDialogo = SesionDialogoV2::create([
            'sesion_id' => $sesionJuicio->id,
            'dialogo_id' => $dialogo->id,
            'nodo_actual_id' => $nodo->id,
            'estado' => 'en_curso',
            'variables' => [],
            'historial_nodos' => [],
        ]);

        $decision = DecisionDialogoV2::create([
            'sesion_dialogo_id' => $sesionDialogo->id,
            'nodo_dialogo_id' => $nodo->id,
            'respuesta_id' => $respuesta->id,
            'usuario_id' => $estudiante->id,
            'rol_id' => $rol->id,
            'texto_respuesta' => $respuesta->texto,
            'puntuacion_obtenida' => $respuesta->puntuacion,
            'usuario_registrado' => true,
        ]);

        // Evaluar decisión
        $decision->evaluar(
            85, // calificación
            'Buen trabajo, pero puede mejorar',
            'Continúa practicando',
            $user->id
        );

        $this->assertEquals(85, $decision->fresh()->calificacion_profesor);
        $this->assertEquals('evaluado', $decision->fresh()->estado_evaluacion);
        $this->assertEquals($user->id, $decision->fresh()->evaluado_por);
        $this->assertNotNull($decision->fresh()->fecha_evaluacion);
    }

    /**
     * Test de audio MP3 en decisiones
     */
    public function test_audio_mp3_en_decisiones(): void
    {
        $user = User::factory()->create(['tipo' => 'instructor']);
        $rol = RolDisponible::factory()->create();

        $dialogo = DialogoV2::create([
            'nombre' => 'Test Diálogo',
            'creado_por' => $user->id,
            'estado' => 'activo',
            'version' => '1.0.0',
        ]);

        $nodo = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rol->id,
            'titulo' => 'Nodo',
            'contenido' => 'Contenido del nodo',
            'tipo' => 'decision',
            'posicion_x' => 0,
            'posicion_y' => 0,
            'orden' => 1,
            'activo' => true,
        ]);

        $respuesta = RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodo->id,
            'texto' => 'Respuesta',
            'puntuacion' => 10,
            'orden' => 1,
            'activo' => true,
        ]);

        $sesionJuicio = SesionJuicio::factory()->create();
        $sesionDialogo = SesionDialogoV2::create([
            'sesion_id' => $sesionJuicio->id,
            'dialogo_id' => $dialogo->id,
            'nodo_actual_id' => $nodo->id,
            'estado' => 'en_curso',
            'variables' => [],
            'historial_nodos' => [],
        ]);

        $decision = DecisionDialogoV2::create([
            'sesion_dialogo_id' => $sesionDialogo->id,
            'nodo_dialogo_id' => $nodo->id,
            'respuesta_id' => $respuesta->id,
            'usuario_id' => $user->id,
            'rol_id' => $rol->id,
            'texto_respuesta' => $respuesta->texto,
            'puntuacion_obtenida' => $respuesta->puntuacion,
            'usuario_registrado' => true,
        ]);

        // Guardar audio
        $decision->guardarAudio('audios/decisiones/2025/01/decision_1.mp3', 120);

        $this->assertEquals('audios/decisiones/2025/01/decision_1.mp3', $decision->fresh()->audio_mp3);
        $this->assertEquals(120, $decision->fresh()->audio_duracion);
        $this->assertNotNull($decision->fresh()->audio_grabado_en);
        $this->assertFalse($decision->fresh()->audio_procesado);

        // Marcar como procesado
        $decision->marcarAudioComoProcesado();

        $this->assertTrue($decision->fresh()->audio_procesado);
    }

    /**
     * Test de historial de nodos en sesión
     */
    public function test_historial_de_nodos_en_sesion(): void
    {
        $user = User::factory()->create(['tipo' => 'instructor']);
        $rol = RolDisponible::factory()->create();

        $dialogo = DialogoV2::create([
            'nombre' => 'Test Diálogo',
            'creado_por' => $user->id,
            'estado' => 'activo',
            'version' => '1.0.0',
        ]);

        $nodo1 = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rol->id,
            'titulo' => 'Nodo 1',
            'contenido' => 'Contenido del nodo 1',
            'tipo' => 'inicio',
            'posicion_x' => 0,
            'posicion_y' => 0,
            'orden' => 1,
            'activo' => true,
        ]);

        $nodo2 = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rol->id,
            'titulo' => 'Nodo 2',
            'contenido' => 'Contenido del nodo 2',
            'tipo' => 'decision',
            'posicion_x' => 100,
            'posicion_y' => 100,
            'orden' => 2,
            'activo' => true,
        ]);

        $sesionJuicio = SesionJuicio::factory()->create();
        $sesionDialogo = SesionDialogoV2::create([
            'sesion_id' => $sesionJuicio->id,
            'dialogo_id' => $dialogo->id,
            'nodo_actual_id' => $nodo1->id,
            'estado' => 'en_curso',
            'variables' => [],
            'historial_nodos' => [],
        ]);

        // Avanzar a siguiente nodo (esto agregará nodo1 y nodo2 al historial)
        $sesionDialogo->avanzarANodo($nodo2->id, $user->id, $rol->id, 15);

        $historial = $sesionDialogo->obtenerHistorial();
        $nodosVisitados = $sesionDialogo->obtenerNodosVisitados();

        $this->assertCount(2, $historial);
        $this->assertCount(2, $nodosVisitados);
        $this->assertContains($nodo1->id, $nodosVisitados);
        $this->assertContains($nodo2->id, $nodosVisitados);
    }
}
