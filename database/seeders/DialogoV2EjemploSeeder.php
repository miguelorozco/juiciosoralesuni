<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DialogoV2;
use App\Models\NodoDialogoV2;
use App\Models\RespuestaDialogoV2;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Seeder para crear un diálogo de ejemplo usando el sistema v2
 */
class DialogoV2EjemploSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->crearDialogoEjemplo();
        });
    }

    private function crearDialogoEjemplo()
    {
        // Obtener el primer administrador o crear uno si no existe
        $admin = User::where('tipo', 'admin')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin',
                'apellido' => 'Sistema',
                'email' => 'admin@test.com',
                'password' => bcrypt('password'),
                'tipo' => 'admin',
                'activo' => true,
                'email_verified_at' => now(),
            ]);
        }

        // Crear el diálogo principal
        $dialogo = DialogoV2::create([
            'nombre' => 'Diálogo de Ejemplo - Juicio Penal Simple',
            'descripcion' => 'Un diálogo de ejemplo para probar el sistema v2. Incluye un caso penal simple con inicio, desarrollo, decisiones y final.',
            'creado_por' => $admin->id,
            'publico' => true,
            'estado' => 'activo',
            'version' => '1.0.0',
            'configuracion' => [
                'tipo' => 'penal',
                'complejidad' => 'baja',
                'duracion_estimada' => '30 minutos',
            ],
            'metadata_unity' => [
                'scene' => 'sala_principal',
                'background_music' => 'courtroom_ambient.mp3',
            ],
        ]);

        // Crear nodos del diálogo
        $nodos = [];

        // Nodo 1: Inicio
        $nodos['inicio'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'tipo' => 'inicio',
            'titulo' => 'Inicio del Juicio',
            'contenido' => 'Bienvenidos al juicio penal. El juez da inicio a la audiencia y presenta el caso.',
            'menu_text' => 'Inicio del juicio',
            'posicion_x' => 100,
            'posicion_y' => 100,
            'es_inicial' => true,
            'es_final' => false,
            'instrucciones' => 'El juez debe presentar el caso y explicar el procedimiento.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Nodo 2: Desarrollo - Presentación del Fiscal
        $nodos['fiscal'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'tipo' => 'desarrollo',
            'titulo' => 'Presentación del Fiscal',
            'contenido' => 'El fiscal presenta los cargos contra el acusado y expone la evidencia inicial.',
            'menu_text' => 'Fiscal presenta cargos',
            'posicion_x' => 400,
            'posicion_y' => 100,
            'es_inicial' => false,
            'es_final' => false,
            'instrucciones' => 'El fiscal debe presentar los cargos de manera clara y profesional.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Nodo 3: Decisión - Estrategia de Defensa
        $nodos['decision'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'tipo' => 'decision',
            'titulo' => 'Estrategia de Defensa',
            'contenido' => 'El abogado defensor debe elegir su estrategia de defensa.',
            'menu_text' => 'Elegir estrategia',
            'posicion_x' => 700,
            'posicion_y' => 100,
            'es_inicial' => false,
            'es_final' => false,
            'instrucciones' => 'El defensor debe elegir entre diferentes estrategias de defensa.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Nodo 4: Desarrollo - Defensa por Inocencia
        $nodos['defensa_inocencia'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'tipo' => 'desarrollo',
            'titulo' => 'Defensa por Inocencia',
            'contenido' => 'El defensor argumenta que su cliente es inocente y presenta pruebas de coartada.',
            'menu_text' => 'Defensa por inocencia',
            'posicion_x' => 1000,
            'posicion_y' => 50,
            'es_inicial' => false,
            'es_final' => false,
            'instrucciones' => 'El defensor debe presentar pruebas sólidas de inocencia.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Nodo 5: Desarrollo - Defensa por Atenuantes
        $nodos['defensa_atenuantes'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'tipo' => 'desarrollo',
            'titulo' => 'Defensa por Atenuantes',
            'contenido' => 'El defensor acepta la culpabilidad pero argumenta circunstancias atenuantes.',
            'menu_text' => 'Defensa por atenuantes',
            'posicion_x' => 1000,
            'posicion_y' => 200,
            'es_inicial' => false,
            'es_final' => false,
            'instrucciones' => 'El defensor debe presentar circunstancias que atenúen la responsabilidad.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Nodo 6: Final - Absolución
        $nodos['absolucion'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'tipo' => 'final',
            'titulo' => 'Absolución',
            'contenido' => 'El juez absuelve al acusado por falta de pruebas suficientes.',
            'menu_text' => 'Absolución',
            'posicion_x' => 1300,
            'posicion_y' => 50,
            'es_inicial' => false,
            'es_final' => true,
            'instrucciones' => 'El juez debe fundamentar la absolución.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Nodo 7: Final - Condena
        $nodos['condena'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'tipo' => 'final',
            'titulo' => 'Condena',
            'contenido' => 'El juez condena al acusado y dicta sentencia considerando las circunstancias atenuantes.',
            'menu_text' => 'Condena',
            'posicion_x' => 1300,
            'posicion_y' => 200,
            'es_inicial' => false,
            'es_final' => true,
            'instrucciones' => 'El juez debe fundamentar la condena y dictar sentencia.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Crear respuestas/conexiones entre nodos

        // De inicio a fiscal
        RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodos['inicio']->id,
            'nodo_siguiente_id' => $nodos['fiscal']->id,
            'texto' => 'Continuar con la presentación del fiscal',
            'orden' => 1,
            'puntuacion' => 0,
            'color' => '#007bff',
            'requiere_usuario_registrado' => false,
            'es_opcion_por_defecto' => true,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // De fiscal a decisión
        RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodos['fiscal']->id,
            'nodo_siguiente_id' => $nodos['decision']->id,
            'texto' => 'Pasar a la defensa',
            'orden' => 1,
            'puntuacion' => 0,
            'color' => '#007bff',
            'requiere_usuario_registrado' => false,
            'es_opcion_por_defecto' => true,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // De decisión a defensa por inocencia
        RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodos['decision']->id,
            'nodo_siguiente_id' => $nodos['defensa_inocencia']->id,
            'texto' => 'Defender la inocencia del acusado',
            'orden' => 1,
            'puntuacion' => 10,
            'color' => '#28a745',
            'requiere_usuario_registrado' => true,
            'es_opcion_por_defecto' => false,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [
                'variables' => [
                    'estrategia' => 'inocencia',
                    'puntos' => 10,
                ],
            ],
        ]);

        // De decisión a defensa por atenuantes
        RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodos['decision']->id,
            'nodo_siguiente_id' => $nodos['defensa_atenuantes']->id,
            'texto' => 'Aceptar culpabilidad con atenuantes',
            'orden' => 2,
            'puntuacion' => 5,
            'color' => '#ffc107',
            'requiere_usuario_registrado' => true,
            'es_opcion_por_defecto' => false,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [
                'variables' => [
                    'estrategia' => 'atenuantes',
                    'puntos' => 5,
                ],
            ],
        ]);

        // De defensa por inocencia a absolución
        RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodos['defensa_inocencia']->id,
            'nodo_siguiente_id' => $nodos['absolucion']->id,
            'texto' => 'Continuar hacia la sentencia',
            'orden' => 1,
            'puntuacion' => 0,
            'color' => '#007bff',
            'requiere_usuario_registrado' => false,
            'es_opcion_por_defecto' => true,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // De defensa por atenuantes a condena
        RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodos['defensa_atenuantes']->id,
            'nodo_siguiente_id' => $nodos['condena']->id,
            'texto' => 'Continuar hacia la sentencia',
            'orden' => 1,
            'puntuacion' => 0,
            'color' => '#007bff',
            'requiere_usuario_registrado' => false,
            'es_opcion_por_defecto' => true,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        $this->command->info("✅ Diálogo de ejemplo creado exitosamente!");
        $this->command->info("📝 ID del diálogo: {$dialogo->id}");
        $this->command->info("📝 Nombre: {$dialogo->nombre}");
        $this->command->info("🔗 URL del editor: /dialogos-v2/{$dialogo->id}/editor");
        $this->command->info("📊 Nodos creados: " . count($nodos));
        $this->command->info("🔗 Respuestas creadas: 6");
    }
}
