<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PanelDialogoEscenario;
use App\Models\PanelDialogoRol;
use App\Models\PanelDialogoFlujo;
use App\Models\PanelDialogoDialogo;
use App\Models\PanelDialogoOpcion;
use App\Models\PanelDialogoConexion;
use App\Models\User;

class PanelDialogoEscenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el primer usuario admin
        $admin = User::where('tipo', 'admin')->first();
        if (!$admin) {
            $admin = User::first();
        }

        // Crear escenario de juicio penal
        $escenario = PanelDialogoEscenario::create([
            'nombre' => 'Juicio Penal: Robo a Tienda en Colonia Popular',
            'descripcion' => 'Simulación de juicio penal por robo a tienda en una colonia popular de la Ciudad de México. Incluye testigo, víctima y múltiples estrategias de defensa.',
            'tipo' => 'penal',
            'estado' => 'activo',
            'publico' => true,
            'configuracion' => [
                'duracion_estimada' => '45 minutos',
                'nivel_dificultad' => 'medio',
                'objetivos_educativos' => [
                    'Conocer el procedimiento penal',
                    'Practicar técnicas de interrogatorio',
                    'Desarrollar estrategias de defensa'
                ]
            ],
            'creado_por' => $admin->id
        ]);

        // Crear roles
        $roles = [
            [
                'nombre' => 'Juez',
                'descripcion' => 'Magistrado que preside el juicio y toma las decisiones finales',
                'color' => '#007bff',
                'icono' => 'bi-gavel',
                'requerido' => true,
                'orden' => 1
            ],
            [
                'nombre' => 'Fiscal',
                'descripcion' => 'Representante del Ministerio Público que acusa al imputado',
                'color' => '#dc3545',
                'icono' => 'bi-shield-check',
                'requerido' => true,
                'orden' => 2
            ],
            [
                'nombre' => 'Defensor',
                'descripcion' => 'Abogado defensor del imputado',
                'color' => '#28a745',
                'icono' => 'bi-shield-exclamation',
                'requerido' => true,
                'orden' => 3
            ],
            [
                'nombre' => 'Imputado',
                'descripcion' => 'Persona acusada del delito',
                'color' => '#6c757d',
                'icono' => 'bi-person',
                'requerido' => true,
                'orden' => 4
            ],
            [
                'nombre' => 'Víctima',
                'descripcion' => 'Dueño de la tienda que fue robada',
                'color' => '#fd7e14',
                'icono' => 'bi-person-badge',
                'requerido' => true,
                'orden' => 5
            ],
            [
                'nombre' => 'Testigo',
                'descripcion' => 'Testigo presencial del robo',
                'color' => '#20c997',
                'icono' => 'bi-person-check',
                'requerido' => true,
                'orden' => 6
            ]
        ];

        $rolesCreados = [];
        foreach ($roles as $rolData) {
            $rol = $escenario->roles()->create($rolData);
            $rolesCreados[$rolData['nombre']] = $rol;

            // Crear flujo principal para cada rol
            $flujo = $rol->flujos()->create([
                'escenario_id' => $escenario->id,
                'nombre' => 'Flujo Principal de ' . $rol->nombre,
                'descripcion' => 'Secuencia principal de diálogos para el rol ' . $rol->nombre,
                'orden' => 0,
                'activo' => true
            ]);

            // Crear diálogos específicos por rol
            $this->crearDialogosPorRol($flujo, $rolData['nombre']);
        }

        // Crear conexiones entre diálogos
        $this->crearConexionesEntreDialogos($escenario, $rolesCreados);
    }

    private function crearDialogosPorRol($flujo, $nombreRol)
    {
        $dialogos = [];

        switch ($nombreRol) {
            case 'Juez':
                $dialogos = [
                    [
                        'titulo' => 'Apertura del Juicio',
                        'contenido' => 'Se abre la audiencia del caso de robo a tienda. Procederemos con la lectura de cargos.',
                        'tipo' => 'automatico',
                        'es_inicial' => true,
                        'orden' => 0,
                        'posicion' => ['x' => 100, 'y' => 100]
                    ],
                    [
                        'titulo' => 'Escuchar Argumentos',
                        'contenido' => 'Proceda con sus argumentos iniciales. Fiscal, presente su caso.',
                        'tipo' => 'automatico',
                        'orden' => 1,
                        'posicion' => ['x' => 100, 'y' => 250]
                    ],
                    [
                        'titulo' => 'Decisión Final',
                        'contenido' => 'Después de escuchar todos los argumentos y evidencia, ¿cuál es su decisión?',
                        'tipo' => 'decision',
                        'es_final' => true,
                        'orden' => 2,
                        'posicion' => ['x' => 100, 'y' => 400]
                    ]
                ];
                break;

            case 'Fiscal':
                $dialogos = [
                    [
                        'titulo' => 'Presentación de Cargos',
                        'contenido' => 'Se presentan los cargos contra el imputado por el delito de robo agravado.',
                        'tipo' => 'automatico',
                        'es_inicial' => true,
                        'orden' => 0,
                        'posicion' => ['x' => 400, 'y' => 100]
                    ],
                    [
                        'titulo' => 'Estrategia de Acusación',
                        'contenido' => '¿Qué estrategia utilizará para probar la culpabilidad?',
                        'tipo' => 'decision',
                        'orden' => 1,
                        'posicion' => ['x' => 400, 'y' => 250]
                    ],
                    [
                        'titulo' => 'Interrogatorio a Testigos',
                        'contenido' => 'Procederé a interrogar a los testigos para establecer los hechos.',
                        'tipo' => 'automatico',
                        'orden' => 2,
                        'posicion' => ['x' => 400, 'y' => 400]
                    ]
                ];
                break;

            case 'Defensor':
                $dialogos = [
                    [
                        'titulo' => 'Defensa del Cliente',
                        'contenido' => 'En defensa de mi cliente, presentaré argumentos que demuestren su inocencia.',
                        'tipo' => 'automatico',
                        'es_inicial' => true,
                        'orden' => 0,
                        'posicion' => ['x' => 700, 'y' => 100]
                    ],
                    [
                        'titulo' => 'Estrategia de Defensa',
                        'contenido' => '¿Qué estrategia utilizará para defender al imputado?',
                        'tipo' => 'decision',
                        'orden' => 1,
                        'posicion' => ['x' => 700, 'y' => 250]
                    ],
                    [
                        'titulo' => 'Contrainterrogatorio',
                        'contenido' => 'Procederé a contrainterrogar a los testigos para cuestionar su credibilidad.',
                        'tipo' => 'automatico',
                        'orden' => 2,
                        'posicion' => ['x' => 700, 'y' => 400]
                    ]
                ];
                break;

            case 'Imputado':
                $dialogos = [
                    [
                        'titulo' => 'Declaración Inicial',
                        'contenido' => 'Declaro mi inocencia ante este tribunal.',
                        'tipo' => 'automatico',
                        'es_inicial' => true,
                        'orden' => 0,
                        'posicion' => ['x' => 1000, 'y' => 100]
                    ],
                    [
                        'titulo' => 'Respuesta a Acusaciones',
                        'contenido' => '¿Cómo responderá a las acusaciones del fiscal?',
                        'tipo' => 'decision',
                        'orden' => 1,
                        'posicion' => ['x' => 1000, 'y' => 250]
                    ]
                ];
                break;

            case 'Víctima':
                $dialogos = [
                    [
                        'titulo' => 'Testimonio de la Víctima',
                        'contenido' => 'Relataré los hechos tal como ocurrieron el día del robo.',
                        'tipo' => 'automatico',
                        'es_inicial' => true,
                        'orden' => 0,
                        'posicion' => ['x' => 1300, 'y' => 100]
                    ],
                    [
                        'titulo' => 'Descripción del Hecho',
                        'contenido' => '¿Cómo describirá el robo que presenció?',
                        'tipo' => 'decision',
                        'orden' => 1,
                        'posicion' => ['x' => 1300, 'y' => 250]
                    ]
                ];
                break;

            case 'Testigo':
                $dialogos = [
                    [
                        'titulo' => 'Testimonio del Testigo',
                        'contenido' => 'Presencié el robo y puedo describir lo que vi.',
                        'tipo' => 'automatico',
                        'es_inicial' => true,
                        'orden' => 0,
                        'posicion' => ['x' => 1600, 'y' => 100]
                    ],
                    [
                        'titulo' => 'Descripción del Suceso',
                        'contenido' => '¿Qué detalles proporcionará sobre el robo?',
                        'tipo' => 'decision',
                        'orden' => 1,
                        'posicion' => ['x' => 1600, 'y' => 250]
                    ]
                ];
                break;
        }

        foreach ($dialogos as $dialogoData) {
            $dialogo = $flujo->dialogos()->create($dialogoData);

            // Crear opciones para diálogos de decisión
            if ($dialogoData['tipo'] === 'decision') {
                $this->crearOpcionesParaDialogo($dialogo, $nombreRol);
            }
        }
    }

    private function crearOpcionesParaDialogo($dialogo, $nombreRol)
    {
        $opciones = [];

        switch ($nombreRol) {
            case 'Juez':
                $opciones = [
                    ['texto' => 'Absolver al acusado', 'letra' => 'A', 'color' => '#28a745'],
                    ['texto' => 'Condenar al acusado', 'letra' => 'B', 'color' => '#dc3545'],
                    ['texto' => 'Suspender audiencia', 'letra' => 'C', 'color' => '#ffc107']
                ];
                break;

            case 'Fiscal':
                $opciones = [
                    ['texto' => 'Presentar evidencia directa', 'letra' => 'A', 'color' => '#28a745'],
                    ['texto' => 'Interrogar testigos', 'letra' => 'B', 'color' => '#ffc107'],
                    ['texto' => 'Solicitar peritaje', 'letra' => 'C', 'color' => '#fd7e14']
                ];
                break;

            case 'Defensor':
                $opciones = [
                    ['texto' => 'Negar los hechos', 'letra' => 'A', 'color' => '#28a745'],
                    ['texto' => 'Alegar atenuantes', 'letra' => 'B', 'color' => '#ffc107'],
                    ['texto' => 'Solicitar absolución', 'letra' => 'C', 'color' => '#fd7e14']
                ];
                break;

            case 'Imputado':
                $opciones = [
                    ['texto' => 'Mantener inocencia', 'letra' => 'A', 'color' => '#28a745'],
                    ['texto' => 'Admitir parcialmente', 'letra' => 'B', 'color' => '#ffc107'],
                    ['texto' => 'Solicitar clemencia', 'letra' => 'C', 'color' => '#fd7e14']
                ];
                break;

            case 'Víctima':
                $opciones = [
                    ['texto' => 'Descripción detallada', 'letra' => 'A', 'color' => '#28a745'],
                    ['texto' => 'Enfoque en pérdidas', 'letra' => 'B', 'color' => '#ffc107'],
                    ['texto' => 'Testimonio emocional', 'letra' => 'C', 'color' => '#fd7e14']
                ];
                break;

            case 'Testigo':
                $opciones = [
                    ['texto' => 'Descripción objetiva', 'letra' => 'A', 'color' => '#28a745'],
                    ['texto' => 'Detalles específicos', 'letra' => 'B', 'color' => '#ffc107'],
                    ['texto' => 'Testimonio circunstancial', 'letra' => 'C', 'color' => '#fd7e14']
                ];
                break;
        }

        foreach ($opciones as $index => $opcionData) {
            $dialogo->opciones()->create([
                'texto' => $opcionData['texto'],
                'descripcion' => 'Opción ' . $opcionData['letra'] . ' para ' . $nombreRol,
                'letra' => $opcionData['letra'],
                'color' => $opcionData['color'],
                'puntuacion' => ($index + 1) * 10,
                'orden' => $index + 1,
                'activo' => true
            ]);
        }
    }

    private function crearConexionesEntreDialogos($escenario, $rolesCreados)
    {
        // Esta función se puede expandir para crear conexiones más complejas
        // Por ahora, solo creamos conexiones básicas dentro de cada flujo
        
        foreach ($rolesCreados as $rol) {
            $flujo = $rol->flujos()->first();
            $dialogos = $flujo->dialogos()->orderBy('orden')->get();
            
            for ($i = 0; $i < $dialogos->count() - 1; $i++) {
                $dialogoActual = $dialogos[$i];
                $dialogoSiguiente = $dialogos[$i + 1];
                
                if ($dialogoActual->tipo === 'decision') {
                    // Para diálogos de decisión, conectar cada opción al siguiente diálogo
                    foreach ($dialogoActual->opciones as $opcion) {
                        $escenario->conexiones()->create([
                            'dialogo_origen_id' => $dialogoActual->id,
                            'dialogo_destino_id' => $dialogoSiguiente->id,
                            'opcion_id' => $opcion->id,
                            'tipo' => 'directa',
                            'activo' => true
                        ]);
                    }
                } else {
                    // Para diálogos automáticos, conexión directa
                    $escenario->conexiones()->create([
                        'dialogo_origen_id' => $dialogoActual->id,
                        'dialogo_destino_id' => $dialogoSiguiente->id,
                        'tipo' => 'directa',
                        'activo' => true
                    ]);
                }
            }
        }
    }
}