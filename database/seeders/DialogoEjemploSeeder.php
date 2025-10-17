<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Dialogo;
use App\Models\NodoDialogo;
use App\Models\RespuestaDialogo;
use App\Models\RolDisponible;
use App\Models\User;

class DialogoEjemploSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles básicos si no existen
        $roles = [
            ['nombre' => 'Juez', 'descripcion' => 'Juez del tribunal', 'color' => '#dc3545', 'icono' => 'gavel'],
            ['nombre' => 'Fiscal', 'descripcion' => 'Fiscal del ministerio público', 'color' => '#007bff', 'icono' => 'balance-scale'],
            ['nombre' => 'Defensor', 'descripcion' => 'Defensor público', 'color' => '#28a745', 'icono' => 'shield-alt'],
            ['nombre' => 'Acusado', 'descripcion' => 'Persona acusada', 'color' => '#ffc107', 'icono' => 'user'],
            ['nombre' => 'Testigo', 'descripcion' => 'Testigo del caso', 'color' => '#6c757d', 'icono' => 'eye'],
        ];

        $rolesCreados = [];
        foreach ($roles as $rolData) {
            $rol = RolDisponible::firstOrCreate(
                ['nombre' => $rolData['nombre']],
                $rolData
            );
            $rolesCreados[$rolData['nombre']] = $rol;
        }

        // Obtener un usuario admin para crear el diálogo
        $admin = User::where('tipo', 'admin')->first();
        if (!$admin) {
            $admin = User::first();
        }

        // Crear diálogo de ejemplo
        $dialogo = Dialogo::create([
            'nombre' => 'Juicio Civil - Incumplimiento de Contrato',
            'descripcion' => 'Simulación de juicio civil sobre incumplimiento de contrato de servicios',
            'creado_por' => $admin->id,
            'publico' => true,
            'estado' => 'activo',
            'configuracion' => [
                'duracion_estimada' => 60,
                'nivel_dificultad' => 'medio',
                'variables_iniciales' => [
                    'monto_contrato' => 50000,
                    'dias_retraso' => 30,
                    'evidencia_presentada' => false,
                ]
            ]
        ]);

        // Crear nodos del diálogo
        $nodos = [
            [
                'titulo' => 'Apertura del Juicio',
                'contenido' => 'Buenos días, damas y caballeros. Este tribunal está reunido para conocer el caso de incumplimiento de contrato presentado por la demandante contra la demandada. Procederemos con la apertura del juicio.',
                'instrucciones' => 'El juez debe declarar abierto el juicio y explicar el procedimiento',
                'tipo' => 'inicio',
                'es_inicial' => true,
                'rol' => 'Juez',
                'orden' => 1,
            ],
            [
                'titulo' => 'Exposición del Fiscal',
                'contenido' => 'Señor juez, el ministerio público presenta evidencia de que la demandada incumplió el contrato de servicios por un monto de $50,000 pesos, con un retraso de 30 días en la entrega.',
                'instrucciones' => 'El fiscal debe presentar los argumentos principales del caso',
                'tipo' => 'desarrollo',
                'rol' => 'Fiscal',
                'orden' => 2,
            ],
            [
                'titulo' => 'Respuesta del Defensor',
                'contenido' => 'Señor juez, la defensa argumenta que el retraso fue debido a circunstancias extraordinarias fuera del control de la demandada, específicamente problemas en la cadena de suministro.',
                'instrucciones' => 'El defensor debe presentar la defensa del acusado',
                'tipo' => 'desarrollo',
                'rol' => 'Defensor',
                'orden' => 3,
            ],
            [
                'titulo' => 'Declaración del Acusado',
                'contenido' => 'Señor juez, quiero declarar que efectivamente hubo un retraso, pero fue debido a circunstancias imprevistas. Estamos dispuestos a llegar a un acuerdo.',
                'instrucciones' => 'El acusado debe decidir cómo responder',
                'tipo' => 'decision',
                'rol' => 'Acusado',
                'orden' => 4,
            ],
            [
                'titulo' => 'Testimonio del Testigo',
                'contenido' => 'Señor juez, puedo confirmar que la empresa demandada tenía problemas de suministro durante el período en cuestión.',
                'instrucciones' => 'El testigo debe decidir qué tan favorable será su testimonio',
                'tipo' => 'decision',
                'rol' => 'Testigo',
                'orden' => 5,
            ],
            [
                'titulo' => 'Sentencia del Juez',
                'contenido' => 'Después de escuchar todas las partes, este tribunal dicta sentencia considerando las circunstancias del caso.',
                'instrucciones' => 'El juez debe decidir la sentencia final',
                'tipo' => 'final',
                'es_final' => true,
                'rol' => 'Juez',
                'orden' => 6,
            ],
        ];

        $nodosCreados = [];
        foreach ($nodos as $nodoData) {
            $rol = $nodosCreados[$nodoData['rol']] ?? $rolesCreados[$nodoData['rol']];
            unset($nodoData['rol']);
            
            $nodo = $dialogo->nodos()->create(array_merge($nodoData, [
                'rol_id' => $rol->id,
                'condiciones' => [],
                'metadata' => [],
            ]));
            
            $nodosCreados[$nodoData['titulo']] = $nodo;
        }

        // Crear respuestas para los nodos de decisión
        $respuestas = [
            // Respuestas para "Declaración del Acusado"
            [
                'nodo' => 'Declaración del Acusado',
                'respuestas' => [
                    [
                        'texto' => 'Admitir responsabilidad y pedir clemencia',
                        'descripcion' => 'El acusado admite el incumplimiento y pide consideración',
                        'nodo_siguiente' => 'Sentencia del Juez',
                        'puntuacion' => 5,
                        'color' => '#dc3545',
                        'consecuencias' => [
                            ['tipo' => 'set', 'variable' => 'admitio_culpa', 'valor' => true],
                            ['tipo' => 'set', 'variable' => 'pidio_clemencia', 'valor' => true],
                        ]
                    ],
                    [
                        'texto' => 'Negar responsabilidad y argumentar fuerza mayor',
                        'descripcion' => 'El acusado niega responsabilidad alegando circunstancias extraordinarias',
                        'nodo_siguiente' => 'Testimonio del Testigo',
                        'puntuacion' => 8,
                        'color' => '#ffc107',
                        'consecuencias' => [
                            ['tipo' => 'set', 'variable' => 'niega_responsabilidad', 'valor' => true],
                            ['tipo' => 'set', 'variable' => 'alega_fuerza_mayor', 'valor' => true],
                        ]
                    ],
                    [
                        'texto' => 'Proponer acuerdo extrajudicial',
                        'descripcion' => 'El acusado propone llegar a un acuerdo fuera del tribunal',
                        'nodo_siguiente' => 'Sentencia del Juez',
                        'puntuacion' => 10,
                        'color' => '#28a745',
                        'consecuencias' => [
                            ['tipo' => 'set', 'variable' => 'propone_acuerdo', 'valor' => true],
                            ['tipo' => 'set', 'variable' => 'disposicion_negociar', 'valor' => true],
                        ]
                    ],
                ]
            ],
            // Respuestas para "Testimonio del Testigo"
            [
                'nodo' => 'Testimonio del Testigo',
                'respuestas' => [
                    [
                        'texto' => 'Testimonio favorable al acusado',
                        'descripcion' => 'El testigo confirma los problemas de suministro',
                        'nodo_siguiente' => 'Sentencia del Juez',
                        'puntuacion' => 7,
                        'color' => '#28a745',
                        'consecuencias' => [
                            ['tipo' => 'set', 'variable' => 'testimonio_favorable', 'valor' => true],
                            ['tipo' => 'set', 'variable' => 'evidencia_problemas', 'valor' => true],
                        ]
                    ],
                    [
                        'texto' => 'Testimonio neutral',
                        'descripcion' => 'El testigo no puede confirmar ni negar los problemas',
                        'nodo_siguiente' => 'Sentencia del Juez',
                        'puntuacion' => 5,
                        'color' => '#6c757d',
                        'consecuencias' => [
                            ['tipo' => 'set', 'variable' => 'testimonio_neutral', 'valor' => true],
                        ]
                    ],
                    [
                        'texto' => 'Testimonio desfavorable',
                        'descripcion' => 'El testigo cuestiona la veracidad de los problemas alegados',
                        'nodo_siguiente' => 'Sentencia del Juez',
                        'puntuacion' => 2,
                        'color' => '#dc3545',
                        'consecuencias' => [
                            ['tipo' => 'set', 'variable' => 'testimonio_desfavorable', 'valor' => true],
                            ['tipo' => 'set', 'variable' => 'cuestiona_veracidad', 'valor' => true],
                        ]
                    ],
                ]
            ],
            // Respuestas para "Sentencia del Juez"
            [
                'nodo' => 'Sentencia del Juez',
                'respuestas' => [
                    [
                        'texto' => 'Sentencia favorable al demandante',
                        'descripcion' => 'El juez falla a favor del demandante con indemnización completa',
                        'nodo_siguiente' => null,
                        'puntuacion' => 3,
                        'color' => '#dc3545',
                        'consecuencias' => [
                            ['tipo' => 'set', 'variable' => 'sentencia_favorable_demandante', 'valor' => true],
                            ['tipo' => 'set', 'variable' => 'indemnizacion_completa', 'valor' => 50000],
                        ]
                    ],
                    [
                        'texto' => 'Sentencia parcial',
                        'descripcion' => 'El juez ordena indemnización parcial considerando las circunstancias',
                        'nodo_siguiente' => null,
                        'puntuacion' => 6,
                        'color' => '#ffc107',
                        'consecuencias' => [
                            ['tipo' => 'set', 'variable' => 'sentencia_parcial', 'valor' => true],
                            ['tipo' => 'set', 'variable' => 'indemnizacion_parcial', 'valor' => 25000],
                        ]
                    ],
                    [
                        'texto' => 'Sentencia favorable al demandado',
                        'descripcion' => 'El juez absuelve al demandado por fuerza mayor',
                        'nodo_siguiente' => null,
                        'puntuacion' => 9,
                        'color' => '#28a745',
                        'consecuencias' => [
                            ['tipo' => 'set', 'variable' => 'sentencia_favorable_demandado', 'valor' => true],
                            ['tipo' => 'set', 'variable' => 'absuelto', 'valor' => true],
                        ]
                    ],
                ]
            ],
        ];

        foreach ($respuestas as $grupoRespuestas) {
            $nodo = $nodosCreados[$grupoRespuestas['nodo']];
            
            foreach ($grupoRespuestas['respuestas'] as $respuestaData) {
                $nodoSiguiente = $respuestaData['nodo_siguiente'] ? 
                    $nodosCreados[$respuestaData['nodo_siguiente']] : null;
                
                unset($respuestaData['nodo_siguiente']);
                
                $nodo->respuestas()->create(array_merge($respuestaData, [
                    'nodo_siguiente_id' => $nodoSiguiente?->id,
                    'condiciones' => [],
                    'orden' => 0, // Se calculará automáticamente
                ]));
            }
        }

        $this->command->info('Diálogo de ejemplo creado exitosamente: ' . $dialogo->nombre);
    }
}
