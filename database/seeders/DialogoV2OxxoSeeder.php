<?php

namespace Database\Seeders;

use App\Models\DialogoV2;
use App\Models\NodoDialogoV2;
use App\Models\RespuestaDialogoV2;
use App\Models\RolDisponible;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder del caso "Robo OXXO" para el sistema de diálogos v2 (nodos/respuestas).
 */
class DialogoV2OxxoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->crearDialogoOxxo();
        });
    }

    private function crearDialogoOxxo(): void
    {
        // Asegurar usuario creador
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

        // Asegurar roles base con color
        $roles = [
            'Juez' => '#8B4513',
            'Fiscal' => '#DC143C',
            'Defensa' => '#4169E1',
            'Cajero/Víctima' => '#32CD32',
            'Testigo Ocular' => '#FFD700',
            'Policía Investigador' => '#2F4F4F',
        ];

        $rolIds = [];
        foreach ($roles as $nombre => $color) {
            $rol = RolDisponible::firstOrCreate(
                ['nombre' => $nombre],
                [
                    'descripcion' => $nombre,
                    'color' => $color,
                    'icono' => null,
                    'activo' => true,
                    'orden' => 1,
                ]
            );
            $rolIds[$nombre] = $rol->id;
        }

        // Crear diálogo
        $dialogo = DialogoV2::create([
            'nombre' => 'Caso Robo OXXO - V2',
            'descripcion' => 'Versión v2 del caso OXXO con nodos, decisiones y finales.',
            'creado_por' => $admin->id,
            'publico' => true,
            'estado' => 'activo',
            'version' => '1.0.0',
            'configuracion' => [
                'tipo' => 'penal',
                'complejidad' => 'media',
                'duracion_estimada' => '45 minutos',
            ],
            'metadata_unity' => [
                'scene' => 'sala_oxxo_v2',
            ],
        ]);

        // Crear nodos
        $nodos = [];

        // Inicio
        $nodos['inicio'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rolIds['Juez'],
            'tipo' => 'inicio',
            'titulo' => 'Apertura de audiencia',
            'contenido' => 'El juez abre la audiencia por robo a tienda OXXO y verifica asistencia.',
            'menu_text' => 'Inicio audiencia',
            'posicion_x' => 100,
            'posicion_y' => 120,
            'es_inicial' => true,
            'es_final' => false,
            'instrucciones' => 'Dar contexto y derechos procesales.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Relato del cajero
        $nodos['relato_cajero'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rolIds['Cajero/Víctima'],
            'tipo' => 'desarrollo',
            'titulo' => 'Relato del cajero',
            'contenido' => 'El cajero narra el asalto y la agresión sufrida.',
            'menu_text' => 'Relato víctima',
            'posicion_x' => 420,
            'posicion_y' => 80,
            'es_inicial' => false,
            'es_final' => false,
            'instrucciones' => 'Recabar detalles: hora, agresor, arma, lesiones.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Reporte del policía
        $nodos['reporte_policia'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rolIds['Policía Investigador'],
            'tipo' => 'desarrollo',
            'titulo' => 'Reporte policial',
            'contenido' => 'El policía describe aseguramiento de la escena y evidencia.',
            'menu_text' => 'Reporte policía',
            'posicion_x' => 420,
            'posicion_y' => 200,
            'es_inicial' => false,
            'es_final' => false,
            'instrucciones' => 'Describir cadena de custodia y hallazgos.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Decisión del fiscal
        $nodos['decision_fiscal'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rolIds['Fiscal'],
            'tipo' => 'decision',
            'titulo' => 'Estrategia del fiscal',
            'contenido' => 'El fiscal decide cómo fortalecer la imputación.',
            'menu_text' => 'Estrategia fiscal',
            'posicion_x' => 720,
            'posicion_y' => 140,
            'es_inicial' => false,
            'es_final' => false,
            'instrucciones' => 'Elegir línea probatoria prioritaria.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Desarrollo: solicitar video
        $nodos['video'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rolIds['Fiscal'],
            'tipo' => 'desarrollo',
            'titulo' => 'Solicitud de video vigilancia',
            'contenido' => 'Se integra el video de cámaras OXXO para identificar al imputado.',
            'menu_text' => 'Video OXXO',
            'posicion_x' => 1040,
            'posicion_y' => 60,
            'es_inicial' => false,
            'es_final' => false,
            'instrucciones' => 'Presentar video y autenticar cadena de custodia.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Desarrollo: reforzar testigos
        $nodos['testigo'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rolIds['Testigo Ocular'],
            'tipo' => 'desarrollo',
            'titulo' => 'Refuerzo con testigo ocular',
            'contenido' => 'Un cliente identifica al sospechoso y detalla la huida.',
            'menu_text' => 'Testigo ocular',
            'posicion_x' => 1040,
            'posicion_y' => 200,
            'es_inicial' => false,
            'es_final' => false,
            'instrucciones' => 'Contrastar relato con video y reporte policial.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Finales
        $nodos['final_vincula'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rolIds['Juez'],
            'tipo' => 'final',
            'titulo' => 'Vinculación a proceso',
            'contenido' => 'El juez vincula a proceso por datos de prueba suficientes.',
            'menu_text' => 'Vinculación',
            'posicion_x' => 1320,
            'posicion_y' => 80,
            'es_inicial' => false,
            'es_final' => true,
            'instrucciones' => 'Fundamentar vinculación y medidas cautelares.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        $nodos['final_no_vincula'] = NodoDialogoV2::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $rolIds['Juez'],
            'tipo' => 'final',
            'titulo' => 'No vinculación',
            'contenido' => 'El juez no vincula por dudas razonables en identificación.',
            'menu_text' => 'No vinculación',
            'posicion_x' => 1320,
            'posicion_y' => 220,
            'es_inicial' => false,
            'es_final' => true,
            'instrucciones' => 'Emitir resolución y levantar medidas.',
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Conexiones (respuestas)
        RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodos['inicio']->id,
            'nodo_siguiente_id' => $nodos['relato_cajero']->id,
            'texto' => 'Escuchar a la víctima',
            'orden' => 1,
            'puntuacion' => 0,
            'color' => '#8B4513',
            'requiere_usuario_registrado' => false,
            'es_opcion_por_defecto' => true,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodos['relato_cajero']->id,
            'nodo_siguiente_id' => $nodos['reporte_policia']->id,
            'texto' => 'Pedir reporte del policía',
            'orden' => 1,
            'puntuacion' => 0,
            'color' => '#32CD32',
            'requiere_usuario_registrado' => false,
            'es_opcion_por_defecto' => true,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodos['reporte_policia']->id,
            'nodo_siguiente_id' => $nodos['decision_fiscal']->id,
            'texto' => 'Ceder la palabra al fiscal',
            'orden' => 1,
            'puntuacion' => 0,
            'color' => '#2F4F4F',
            'requiere_usuario_registrado' => false,
            'es_opcion_por_defecto' => true,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Decisión (máx 3 salidas; usamos 2)
        RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodos['decision_fiscal']->id,
            'nodo_siguiente_id' => $nodos['video']->id,
            'texto' => 'Priorizar video de cámaras',
            'orden' => 1,
            'puntuacion' => 0,
            'color' => '#DC143C',
            'requiere_usuario_registrado' => false,
            'es_opcion_por_defecto' => true,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodos['decision_fiscal']->id,
            'nodo_siguiente_id' => $nodos['testigo']->id,
            'texto' => 'Reforzar con testigo ocular',
            'orden' => 2,
            'puntuacion' => 0,
            'color' => '#DC143C',
            'requiere_usuario_registrado' => false,
            'es_opcion_por_defecto' => false,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        // Cerramos hacia finales
        RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodos['video']->id,
            'nodo_siguiente_id' => $nodos['final_vincula']->id,
            'texto' => 'Video identifica plenamente',
            'orden' => 1,
            'puntuacion' => 0,
            'color' => '#DC143C',
            'requiere_usuario_registrado' => false,
            'es_opcion_por_defecto' => true,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [],
        ]);

        RespuestaDialogoV2::create([
            'nodo_padre_id' => $nodos['testigo']->id,
            'nodo_siguiente_id' => $nodos['final_no_vincula']->id,
            'texto' => 'Testimonio genera duda',
            'orden' => 1,
            'puntuacion' => 0,
            'color' => '#FFD700',
            'requiere_usuario_registrado' => false,
            'es_opcion_por_defecto' => true,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [],
        ]);
    }
}
