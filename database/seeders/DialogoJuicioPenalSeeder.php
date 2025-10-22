<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Dialogo;
use App\Models\NodoDialogo;
use App\Models\RespuestaDialogo;
use App\Models\RolDisponible;
use App\Models\User;

class DialogoJuicioPenalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el usuario administrador para crear el diálogo
        $admin = User::where('tipo', 'admin')->first();
        if (!$admin) {
            $this->command->error('No se encontró usuario administrador. Ejecuta AdminUserSeeder primero.');
            return;
        }

        // Obtener roles disponibles
        $roles = RolDisponible::all()->keyBy('nombre');
        
        // Crear el diálogo principal
        $dialogo = Dialogo::create([
            'nombre' => 'Juicio Penal: Robo a Tienda en Colonia Popular',
            'descripcion' => 'Simulación de juicio penal por robo a una tienda de abarrotes en una colonia popular de México. Incluye testimonio de víctima y testigo, con múltiples estrategias de defensa.',
            'creado_por' => $admin->id,
            'plantilla_id' => null,
            'publico' => true,
            'estado' => 'activo',
            'configuracion' => json_encode([
                'duracion_estimada' => 45,
                'nivel_dificultad' => 'intermedio',
                'roles_requeridos' => ['Juez', 'Fiscal', 'Defensa', 'Víctima', 'Testigo', 'Acusado'],
                'escenario' => 'Tribunal Penal',
                'tema' => 'Robo',
                'ubicacion' => 'Colonia Popular, México'
            ])
        ]);

        $this->command->info("✅ Diálogo creado: {$dialogo->nombre}");

        // Crear nodos del diálogo
        $nodos = $this->crearNodos($dialogo, $roles);
        
        // Crear respuestas y conexiones
        $this->crearRespuestas($nodos);

        $this->command->info("🎭 Diálogo ramificado creado exitosamente con " . count($nodos) . " nodos");
    }

    /**
     * Crear todos los nodos del diálogo
     */
    private function crearNodos($dialogo, $roles)
    {
        $nodos = [];

        // NODO 1: INICIO - Apertura del juicio
        $nodos['inicio'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Juez']->id ?? null,
            'titulo' => 'Apertura del Juicio',
            'contenido' => 'Se abre la audiencia del juicio penal contra Juan Carlos Mendoza por el delito de robo agravado. El caso se refiere al robo ocurrido el 15 de marzo de 2024 en la tienda "Abarrotes El Barrio" ubicada en la Colonia San Miguel, donde se sustrajeron mercancías por valor de $2,500 pesos mexicanos.',
            'instrucciones' => 'El Juez debe declarar abierta la audiencia y explicar el procedimiento.',
            'orden' => 1,
            'tipo' => 'inicio',
            'es_inicial' => true,
            'es_final' => false,
            'condiciones' => json_encode(['requiere_rol' => 'Juez']),
            'metadata' => json_encode(['ambiente' => 'tribunal', 'tono' => 'formal'])
        ]);

        // NODO 2: Declaración de la Víctima
        $nodos['victima_declaracion'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Víctima']->id ?? null,
            'titulo' => 'Declaración de la Víctima',
            'contenido' => 'Soy María Elena Rodríguez, propietaria de "Abarrotes El Barrio". El día 15 de marzo, alrededor de las 8:30 PM, estaba cerrando mi tienda cuando entró un joven que conocía de vista del barrio. Me pidió un refresco y mientras yo me volteaba, tomó varios productos de los estantes y salió corriendo. Pude ver claramente que era Juan Carlos Mendoza, lo conozco porque vive cerca y a veces compraba en mi tienda.',
            'instrucciones' => 'La víctima debe declarar los hechos tal como los vivió.',
            'orden' => 2,
            'tipo' => 'desarrollo',
            'es_inicial' => false,
            'es_final' => false,
            'condiciones' => json_encode(['requiere_rol' => 'Víctima']),
            'metadata' => json_encode(['emocion' => 'nerviosa', 'confianza' => 'alta'])
        ]);

        // NODO 3: Declaración del Testigo
        $nodos['testigo_declaracion'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Testigo']->id ?? null,
            'titulo' => 'Declaración del Testigo',
            'contenido' => 'Soy Roberto Silva, vecino de la colonia. Ese día estaba caminando por la calle cuando vi a Juan Carlos corriendo con bolsas en las manos. Parecía nervioso y se metió corriendo a su casa. No vi exactamente qué había en las bolsas, pero sí vi que venía de la dirección de la tienda de doña María Elena.',
            'instrucciones' => 'El testigo debe declarar lo que observó sin especular.',
            'orden' => 3,
            'tipo' => 'desarrollo',
            'es_inicial' => false,
            'es_final' => false,
            'condiciones' => json_encode(['requiere_rol' => 'Testigo']),
            'metadata' => json_encode(['confiabilidad' => 'media', 'detalles' => 'limitados'])
        ]);

        // NODO 4: Interrogatorio del Fiscal
        $nodos['fiscal_interrogatorio'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Fiscal']->id ?? null,
            'titulo' => 'Interrogatorio del Fiscal',
            'contenido' => 'Señor Juez, las pruebas son contundentes. Tenemos la declaración de la víctima que identifica claramente al acusado, el testimonio de un testigo que lo vio corriendo con bolsas, y los productos robados fueron encontrados en su domicilio. El acusado cometió robo agravado según el artículo 369 del Código Penal.',
            'instrucciones' => 'El fiscal debe presentar su caso de manera convincente.',
            'orden' => 4,
            'tipo' => 'desarrollo',
            'es_inicial' => false,
            'es_final' => false,
            'condiciones' => json_encode(['requiere_rol' => 'Fiscal']),
            'metadata' => json_encode(['estrategia' => 'directa', 'confianza' => 'alta'])
        ]);

        // NODO 5: DECISIÓN - Estrategias de Defensa
        $nodos['defensa_decision'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Defensa']->id ?? null,
            'titulo' => 'Estrategias de Defensa',
            'contenido' => 'Señor Juez, como abogado defensor de Juan Carlos Mendoza, debo señalar que las pruebas presentadas por el fiscal tienen serias deficiencias. Mi cliente merece una defensa vigorosa y tengo varias estrategias para demostrar su inocencia o reducir la pena.',
            'instrucciones' => 'El abogado defensor debe elegir su estrategia de defensa.',
            'orden' => 5,
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'condiciones' => json_encode(['requiere_rol' => 'Defensa']),
            'metadata' => json_encode(['momento_crucial' => true, 'decisiones' => 5])
        ]);

        // NODOS DE RESULTADO SEGÚN ESTRATEGIA ELEGIDA

        // ESTRATEGIA 1: Error en la identificación
        $nodos['error_identificacion'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Defensa']->id ?? null,
            'titulo' => 'Error en la Identificación',
            'contenido' => 'Señor Juez, la identificación de mi cliente es errónea. La víctima declaró que vio al ladrón "de vista" pero no puede estar segura. Era de noche, había poca iluminación, y el estrés del momento afecta la memoria. Además, el testigo solo vio a alguien corriendo, no puede confirmar que fuera mi cliente.',
            'instrucciones' => 'La defensa cuestiona la identificación del acusado.',
            'orden' => 6,
            'tipo' => 'desarrollo',
            'es_inicial' => false,
            'es_final' => false,
            'condiciones' => json_encode(['estrategia' => 'error_identificacion']),
            'metadata' => json_encode(['probabilidad_exito' => 'media', 'riesgo' => 'alto'])
        ]);

        // ESTRATEGIA 2: Coacción o necesidad
        $nodos['coaccion_necesidad'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Defensa']->id ?? null,
            'titulo' => 'Coacción o Estado de Necesidad',
            'contenido' => 'Señor Juez, mi cliente actuó bajo estado de necesidad extrema. Su familia no tenía qué comer, su madre está enferma y necesita medicamentos. Juan Carlos intentó conseguir trabajo pero no lo contrataron por su edad. Actuó por necesidad, no por malicia.',
            'instrucciones' => 'La defensa argumenta circunstancias atenuantes.',
            'orden' => 7,
            'tipo' => 'desarrollo',
            'es_inicial' => false,
            'es_final' => false,
            'condiciones' => json_encode(['estrategia' => 'coaccion_necesidad']),
            'metadata' => json_encode(['probabilidad_exito' => 'alta', 'humanitaria' => true])
        ]);

        // ESTRATEGIA 3: Falta de pruebas materiales
        $nodos['falta_pruebas'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Defensa']->id ?? null,
            'titulo' => 'Falta de Pruebas Materiales',
            'contenido' => 'Señor Juez, el fiscal no ha presentado pruebas materiales contundentes. No hay huellas dactilares, no hay video de seguridad, no hay testigos que hayan visto el momento exacto del robo. Solo hay testimonios que pueden estar equivocados. Sin pruebas materiales, no se puede condenar a mi cliente.',
            'instrucciones' => 'La defensa cuestiona la solidez de las pruebas.',
            'orden' => 8,
            'tipo' => 'desarrollo',
            'es_inicial' => false,
            'es_final' => false,
            'condiciones' => json_encode(['estrategia' => 'falta_pruebas']),
            'metadata' => json_encode(['probabilidad_exito' => 'media', 'técnica' => true])
        ]);

        // ESTRATEGIA 4: Confesión y arrepentimiento
        $nodos['confesion_arrepentimiento'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Defensa']->id ?? null,
            'titulo' => 'Confesión y Arrepentimiento',
            'contenido' => 'Señor Juez, mi cliente reconoce su error y se arrepiente profundamente. Es un joven de 19 años sin antecedentes penales, que cometió un error de juventud. Está dispuesto a reparar el daño y trabajar para compensar a la víctima. Pido clemencia considerando su edad y su arrepentimiento genuino.',
            'instrucciones' => 'La defensa busca una pena reducida por arrepentimiento.',
            'orden' => 9,
            'tipo' => 'desarrollo',
            'es_inicial' => false,
            'es_final' => false,
            'condiciones' => json_encode(['estrategia' => 'confesion_arrepentimiento']),
            'metadata' => json_encode(['probabilidad_exito' => 'alta', 'reparacion' => true])
        ]);

        // ESTRATEGIA 5: Procedimiento irregular
        $nodos['procedimiento_irregular'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Defensa']->id ?? null,
            'titulo' => 'Procedimiento Irregular',
            'contenido' => 'Señor Juez, debo señalar irregularidades en el procedimiento. Mi cliente fue detenido sin orden judicial, no se le informaron sus derechos, y la búsqueda en su domicilio se realizó sin autorización. Estas violaciones procesales invalidan las pruebas obtenidas.',
            'instrucciones' => 'La defensa cuestiona la legalidad del procedimiento.',
            'orden' => 10,
            'tipo' => 'desarrollo',
            'es_inicial' => false,
            'es_final' => false,
            'condiciones' => json_encode(['estrategia' => 'procedimiento_irregular']),
            'metadata' => json_encode(['probabilidad_exito' => 'baja', 'técnica' => true])
        ]);

        // NODOS FINALES - Sentencias según estrategia

        // SENTENCIA 1: Absolución por error de identificación
        $nodos['absolucion_identificacion'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Juez']->id ?? null,
            'titulo' => 'Sentencia: Absolución por Error de Identificación',
            'contenido' => 'Considerando las dudas razonables sobre la identificación del acusado, la falta de iluminación adecuada y el estrés del momento, este tribunal declara la absolución de Juan Carlos Mendoza por falta de pruebas contundentes. Queda libre.',
            'instrucciones' => 'El juez dicta sentencia absolutoria.',
            'orden' => 11,
            'tipo' => 'final',
            'es_inicial' => false,
            'es_final' => true,
            'condiciones' => json_encode(['resultado' => 'absolucion']),
            'metadata' => json_encode(['sentencia' => 'absolucion', 'duracion' => 'completa'])
        ]);

        // SENTENCIA 2: Pena reducida por necesidad
        $nodos['pena_reducida_necesidad'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Juez']->id ?? null,
            'titulo' => 'Sentencia: Pena Reducida por Estado de Necesidad',
            'contenido' => 'Considerando las circunstancias de necesidad extrema y la falta de antecedentes penales, este tribunal condena a Juan Carlos Mendoza a 6 meses de prisión y 100 horas de servicio comunitario. Deberá reparar el daño a la víctima.',
            'instrucciones' => 'El juez dicta sentencia con pena reducida.',
            'orden' => 12,
            'tipo' => 'final',
            'es_inicial' => false,
            'es_final' => true,
            'condiciones' => json_encode(['resultado' => 'pena_reducida']),
            'metadata' => json_encode(['sentencia' => 'pena_reducida', 'duracion' => '6_meses'])
        ]);

        // SENTENCIA 3: Absolución por falta de pruebas
        $nodos['absolucion_pruebas'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Juez']->id ?? null,
            'titulo' => 'Sentencia: Absolución por Falta de Pruebas',
            'contenido' => 'Considerando que las pruebas presentadas son insuficientes para demostrar la culpabilidad del acusado más allá de toda duda razonable, este tribunal declara la absolución de Juan Carlos Mendoza. Queda libre.',
            'instrucciones' => 'El juez dicta sentencia absolutoria por falta de pruebas.',
            'orden' => 13,
            'tipo' => 'final',
            'es_inicial' => false,
            'es_final' => true,
            'condiciones' => json_encode(['resultado' => 'absolucion']),
            'metadata' => json_encode(['sentencia' => 'absolucion', 'razon' => 'falta_pruebas'])
        ]);

        // SENTENCIA 4: Suspensión condicional por arrepentimiento
        $nodos['suspension_condicional'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Juez']->id ?? null,
            'titulo' => 'Sentencia: Suspensión Condicional',
            'contenido' => 'Considerando el arrepentimiento del acusado, su edad y la falta de antecedentes, este tribunal suspende condicionalmente la ejecución de la pena de 1 año de prisión. Juan Carlos deberá cumplir 200 horas de servicio comunitario y reparar el daño.',
            'instrucciones' => 'El juez dicta suspensión condicional.',
            'orden' => 14,
            'tipo' => 'final',
            'es_inicial' => false,
            'es_final' => true,
            'condiciones' => json_encode(['resultado' => 'suspension']),
            'metadata' => json_encode(['sentencia' => 'suspension', 'condiciones' => 'servicio_comunitario'])
        ]);

        // SENTENCIA 5: Absolución por procedimiento irregular
        $nodos['absolucion_procedimiento'] = NodoDialogo::create([
            'dialogo_id' => $dialogo->id,
            'rol_id' => $roles['Juez']->id ?? null,
            'titulo' => 'Sentencia: Absolución por Procedimiento Irregular',
            'contenido' => 'Considerando las irregularidades procesales señaladas por la defensa, este tribunal declara la absolución de Juan Carlos Mendoza. Las pruebas obtenidas mediante procedimientos irregulares no pueden ser consideradas válidas.',
            'instrucciones' => 'El juez dicta sentencia absolutoria por irregularidades.',
            'orden' => 15,
            'tipo' => 'final',
            'es_inicial' => false,
            'es_final' => true,
            'condiciones' => json_encode(['resultado' => 'absolucion']),
            'metadata' => json_encode(['sentencia' => 'absolucion', 'razon' => 'procedimiento'])
        ]);

        return $nodos;
    }

    /**
     * Crear respuestas y conexiones entre nodos
     */
    private function crearRespuestas($nodos)
    {
        // Respuestas desde el nodo de decisión de defensa
        $respuestasDefensa = [
            [
                'texto' => 'Cuestionar la identificación de la víctima',
                'descripcion' => 'Argumentar que la víctima no puede estar segura de la identidad del ladrón debido a las condiciones de iluminación y estrés.',
                'nodo_siguiente_id' => $nodos['error_identificacion']->id,
                'orden' => 1,
                'puntuacion' => 70,
                'color' => '#ff6b6b',
                'consecuencias' => json_encode(['riesgo' => 'alto', 'probabilidad_exito' => 'media'])
            ],
            [
                'texto' => 'Argumentar estado de necesidad extrema',
                'descripcion' => 'Presentar circunstancias atenuantes como necesidad económica y falta de alternativas.',
                'nodo_siguiente_id' => $nodos['coaccion_necesidad']->id,
                'orden' => 2,
                'puntuacion' => 85,
                'color' => '#4ecdc4',
                'consecuencias' => json_encode(['humanitaria' => true, 'probabilidad_exito' => 'alta'])
            ],
            [
                'texto' => 'Cuestionar la solidez de las pruebas',
                'descripcion' => 'Argumentar que las pruebas presentadas son insuficientes para una condena.',
                'nodo_siguiente_id' => $nodos['falta_pruebas']->id,
                'orden' => 3,
                'puntuacion' => 75,
                'color' => '#45b7d1',
                'consecuencias' => json_encode(['técnica' => true, 'probabilidad_exito' => 'media'])
            ],
            [
                'texto' => 'Buscar pena reducida por arrepentimiento',
                'descripcion' => 'Reconocer el delito pero buscar clemencia por arrepentimiento y reparación del daño.',
                'nodo_siguiente_id' => $nodos['confesion_arrepentimiento']->id,
                'orden' => 4,
                'puntuacion' => 90,
                'color' => '#96ceb4',
                'consecuencias' => json_encode(['reparacion' => true, 'probabilidad_exito' => 'alta'])
            ],
            [
                'texto' => 'Cuestionar irregularidades procesales',
                'descripcion' => 'Argumentar que las pruebas fueron obtenidas mediante procedimientos irregulares.',
                'nodo_siguiente_id' => $nodos['procedimiento_irregular']->id,
                'orden' => 5,
                'puntuacion' => 60,
                'color' => '#feca57',
                'consecuencias' => json_encode(['técnica' => true, 'probabilidad_exito' => 'baja'])
            ]
        ];

        foreach ($respuestasDefensa as $respuesta) {
            RespuestaDialogo::create([
                'nodo_padre_id' => $nodos['defensa_decision']->id,
                'nodo_siguiente_id' => $respuesta['nodo_siguiente_id'],
                'texto' => $respuesta['texto'],
                'descripcion' => $respuesta['descripcion'],
                'orden' => $respuesta['orden'],
                'puntuacion' => $respuesta['puntuacion'],
                'color' => $respuesta['color'],
                'consecuencias' => $respuesta['consecuencias'],
                'activo' => true
            ]);
        }

        // Respuestas desde cada estrategia a su sentencia correspondiente
        $conexionesFinales = [
            ['estrategia' => 'error_identificacion', 'sentencia' => 'absolucion_identificacion'],
            ['estrategia' => 'coaccion_necesidad', 'sentencia' => 'pena_reducida_necesidad'],
            ['estrategia' => 'falta_pruebas', 'sentencia' => 'absolucion_pruebas'],
            ['estrategia' => 'confesion_arrepentimiento', 'sentencia' => 'suspension_condicional'],
            ['estrategia' => 'procedimiento_irregular', 'sentencia' => 'absolucion_procedimiento']
        ];

        foreach ($conexionesFinales as $conexion) {
            RespuestaDialogo::create([
                'nodo_padre_id' => $nodos[$conexion['estrategia']]->id,
                'nodo_siguiente_id' => $nodos[$conexion['sentencia']]->id,
                'texto' => 'Continuar con la estrategia elegida',
                'descripcion' => 'Proceder con la estrategia de defensa seleccionada.',
                'orden' => 1,
                'puntuacion' => 0,
                'color' => '#6c757d',
                'consecuencias' => json_encode(['automatico' => true]),
                'activo' => true
            ]);
        }

        // Respuestas desde el inicio hasta la declaración de la víctima
        RespuestaDialogo::create([
            'nodo_padre_id' => $nodos['inicio']->id,
            'nodo_siguiente_id' => $nodos['victima_declaracion']->id,
            'texto' => 'Proceder con la declaración de la víctima',
            'descripcion' => 'Iniciar el proceso con el testimonio de la víctima.',
            'orden' => 1,
            'puntuacion' => 0,
            'color' => '#6c757d',
            'consecuencias' => json_encode(['secuencial' => true]),
            'activo' => true
        ]);

        // Respuestas secuenciales entre declaraciones
        $secuencias = [
            ['desde' => 'victima_declaracion', 'hacia' => 'testigo_declaracion'],
            ['desde' => 'testigo_declaracion', 'hacia' => 'fiscal_interrogatorio'],
            ['desde' => 'fiscal_interrogatorio', 'hacia' => 'defensa_decision']
        ];

        foreach ($secuencias as $secuencia) {
            RespuestaDialogo::create([
                'nodo_padre_id' => $nodos[$secuencia['desde']]->id,
                'nodo_siguiente_id' => $nodos[$secuencia['hacia']]->id,
                'texto' => 'Continuar con el siguiente paso',
                'descripcion' => 'Proceder con el siguiente elemento del juicio.',
                'orden' => 1,
                'puntuacion' => 0,
                'color' => '#6c757d',
                'consecuencias' => json_encode(['secuencial' => true]),
                'activo' => true
            ]);
        }

        $this->command->info("🔗 Conexiones entre nodos creadas exitosamente");
    }
}
