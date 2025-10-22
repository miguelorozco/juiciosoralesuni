<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PanelDialogoEscenario;
use App\Models\PanelDialogoRol;
use App\Models\PanelDialogoFlujo;
use App\Models\PanelDialogoDialogo;
use App\Models\PanelDialogoOpcion;
use App\Models\PanelDialogoConexion;
use Illuminate\Support\Facades\DB;

class DialogoRoboOXXOCompletoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->crearEscenarioCompleto();
        });
    }

    private function crearEscenarioCompleto()
    {
        // Crear el escenario principal
        $escenario = PanelDialogoEscenario::create([
            'nombre' => 'Robo Violento a OXXO - Caso Complejo',
            'descripcion' => 'Caso penal detallado sobre el robo violento a una tienda OXXO en la Colonia Centro. El cajero Carlos Mendoza resultó herido con un cuchillo. Incluye múltiples testigos, evidencia forense, diferentes estrategias de defensa y decisiones judiciales complejas.',
            'creado_por' => 1,
            'estado' => 'activo',
            'configuracion' => [
                'tipo' => 'penal',
                'publico' => true,
                'complejidad' => 'alta',
                'duracion_estimada' => '180 minutos',
                'roles_requeridos' => 6,
                'roles_opcionales' => 4,
                'dialogos_totales' => 120,
                'decisiones_complejas' => 25
            ]
        ]);

        // Crear roles del escenario
        $roles = $this->crearRolesCompletos($escenario);
        
        // Crear flujos para cada rol
        $flujos = $this->crearFlujosCompletos($escenario, $roles);
        
        // Crear diálogos detallados para cada flujo
        $dialogos = $this->crearDialogosCompletos($flujos);
        
        // Crear opciones específicas para cada decisión
        $opciones = $this->crearOpcionesEspecificas($dialogos);
        
        // Crear conexiones complejas entre diálogos
        $this->crearConexionesComplejas($escenario, $dialogos, $opciones);
        
        echo "✅ Escenario 'Robo Violento a OXXO' creado exitosamente con " . count($dialogos, COUNT_RECURSIVE) . " diálogos totales\n";
    }

    private function crearRolesCompletos($escenario)
    {
        $roles = [
            [
                'nombre' => 'Juez María Elena Rodríguez',
                'descripcion' => 'Jueza con 15 años de experiencia en casos penales. Conocida por su imparcialidad y atención al detalle.',
                'requerido' => true,
                'configuracion' => ['color' => '#8B4513', 'icono' => 'bi-gavel', 'experiencia' => '15 años']
            ],
            [
                'nombre' => 'Fiscal Alejandro Morales',
                'descripcion' => 'Fiscal especializado en delitos violentos. Persistente y meticuloso en la presentación de casos.',
                'requerido' => true,
                'configuracion' => ['color' => '#DC143C', 'icono' => 'bi-shield-check', 'especialidad' => 'delitos violentos']
            ],
            [
                'nombre' => 'Abogado Defensor Roberto Silva',
                'descripcion' => 'Defensor público experimentado. Conocido por su creatividad en estrategias de defensa.',
                'requerido' => true,
                'configuracion' => ['color' => '#4169E1', 'icono' => 'bi-shield', 'experiencia' => '12 años']
            ],
            [
                'nombre' => 'Acusado Juan Carlos Pérez',
                'descripcion' => 'Hombre de 28 años, padre soltero con antecedentes menores. Trabaja como albañil.',
                'requerido' => true,
                'configuracion' => ['color' => '#FF6347', 'icono' => 'bi-person', 'edad' => 28, 'ocupacion' => 'albañil']
            ],
            [
                'nombre' => 'Cajero Carlos Mendoza',
                'descripcion' => 'Empleado de OXXO de 35 años, padre de familia. Resultó herido durante el robo.',
                'requerido' => true,
                'configuracion' => ['color' => '#32CD32', 'icono' => 'bi-person-heart', 'edad' => 35, 'estado' => 'herido']
            ],
            [
                'nombre' => 'Testigo Ana Patricia López',
                'descripcion' => 'Cliente frecuente de la tienda, maestra de primaria. Presenció el robo desde afuera.',
                'requerido' => true,
                'configuracion' => ['color' => '#FFD700', 'icono' => 'bi-eye', 'ocupacion' => 'maestra']
            ],
            [
                'nombre' => 'Perito Forense Dr. Miguel Torres',
                'descripcion' => 'Especialista en medicina forense con 20 años de experiencia. Analizó la evidencia del caso.',
                'requerido' => false,
                'configuracion' => ['color' => '#9370DB', 'icono' => 'bi-clipboard-data', 'especialidad' => 'medicina forense']
            ],
            [
                'nombre' => 'Detective José Luis Ramírez',
                'descripcion' => 'Detective de la Policía Municipal con 10 años de experiencia. Dirigió la investigación.',
                'requerido' => false,
                'configuracion' => ['color' => '#2F4F4F', 'icono' => 'bi-shield-fill-check', 'rango' => 'detective']
            ],
            [
                'nombre' => 'Secretario de Acuerdos Lic. Carmen Vega',
                'descripcion' => 'Secretaria judicial con 8 años de experiencia. Registra todas las actuaciones del juicio.',
                'requerido' => false,
                'configuracion' => ['color' => '#696969', 'icono' => 'bi-journal-text', 'experiencia' => '8 años']
            ],
            [
                'nombre' => 'Público en General',
                'descripcion' => 'Familiares del cajero, vecinos del acusado y ciudadanos interesados en el caso.',
                'requerido' => false,
                'configuracion' => ['color' => '#D3D3D3', 'icono' => 'bi-people', 'composicion' => 'mixta']
            ]
        ];

        $rolesCreados = [];
        foreach ($roles as $index => $rolData) {
            $rol = PanelDialogoRol::create([
                'escenario_id' => $escenario->id,
                'nombre' => $rolData['nombre'],
                'descripcion' => $rolData['descripcion'],
                'requerido' => $rolData['requerido'],
                'configuracion' => $rolData['configuracion'],
                'orden' => $index
            ]);
            $rolesCreados[$rolData['nombre']] = $rol;
        }

        return $rolesCreados;
    }

    private function crearFlujosCompletos($escenario, $roles)
    {
        $flujos = [];
        
        foreach ($roles as $nombreRol => $rol) {
            $flujo = PanelDialogoFlujo::create([
                'escenario_id' => $escenario->id,
                'rol_id' => $rol->id,
                'nombre' => "Flujo Completo de {$nombreRol}",
                'descripcion' => "Secuencia detallada de diálogos para el rol {$nombreRol} en el caso del robo a OXXO",
                'configuracion' => ['orden' => 0, 'activo' => true, 'complejidad' => 'alta']
            ]);
            $flujos[$nombreRol] = $flujo;
        }

        return $flujos;
    }

    private function crearDialogosCompletos($flujos)
    {
        $dialogos = [];
        
        // Crear diálogos detallados para cada rol
        foreach ($flujos as $nombreRol => $flujo) {
            $dialogos[$nombreRol] = $this->crearDialogosDetalladosPorRol($flujo, $nombreRol);
        }

        return $dialogos;
    }

    private function crearDialogosDetalladosPorRol($flujo, $nombreRol)
    {
        $dialogos = [];
        
        switch ($nombreRol) {
            case 'Juez María Elena Rodríguez':
                $dialogos = $this->crearDialogosJuezCompleto($flujo);
                break;
            case 'Fiscal Alejandro Morales':
                $dialogos = $this->crearDialogosFiscalCompleto($flujo);
                break;
            case 'Abogado Defensor Roberto Silva':
                $dialogos = $this->crearDialogosDefensorCompleto($flujo);
                break;
            case 'Acusado Juan Carlos Pérez':
                $dialogos = $this->crearDialogosAcusadoCompleto($flujo);
                break;
            case 'Cajero Carlos Mendoza':
                $dialogos = $this->crearDialogosCajeroCompleto($flujo);
                break;
            case 'Testigo Ana Patricia López':
                $dialogos = $this->crearDialogosTestigoCompleto($flujo);
                break;
            case 'Perito Forense Dr. Miguel Torres':
                $dialogos = $this->crearDialogosPeritoCompleto($flujo);
                break;
            case 'Detective José Luis Ramírez':
                $dialogos = $this->crearDialogosDetectiveCompleto($flujo);
                break;
            case 'Secretario de Acuerdos Lic. Carmen Vega':
                $dialogos = $this->crearDialogosSecretarioCompleto($flujo);
                break;
            case 'Público en General':
                $dialogos = $this->crearDialogosPublicoCompleto($flujo);
                break;
        }

        return $dialogos;
    }

    private function crearDialogosJuezCompleto($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // 1. Apertura formal del juicio
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Apertura Formal del Juicio',
            'contenido' => 'Se abre la audiencia del caso penal número 1234/2024, por el delito de robo con violencia en agravio de la tienda OXXO ubicada en la Colonia Centro, ocurrido el día 15 de marzo de 2024. Solicito que todas las partes se identifiquen formalmente.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 100, 'y' => 50, 'momento' => 'inicio']
        ]);

        // 2. Decisión sobre admisión de pruebas
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Admisión de Pruebas del Ministerio Público',
            'contenido' => 'El Ministerio Público presenta las siguientes pruebas: 1) Declaración del cajero víctima Carlos Mendoza, 2) Testimonio de la testigo ocular Ana Patricia López, 3) Evidencia forense del cuchillo con huellas dactilares, 4) Grabaciones de cámaras de seguridad, 5) Informe médico de las lesiones. ¿Admite estas pruebas para su valoración?',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 300, 'y' => 50, 'momento' => 'admisión_pruebas']
        ]);

        // 3. Decisión sobre declaración del acusado
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Derecho a Declarar del Acusado',
            'contenido' => 'Se le informa al acusado Juan Carlos Pérez su derecho constitucional a declarar o mantenerse en silencio. Si decide declarar, sus palabras podrán ser usadas en su contra. ¿Permite que declare en este momento o prefiere que se reserve su derecho?',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 500, 'y' => 50, 'momento' => 'declaración_acusado']
        ]);

        // 4. Decisión sobre medidas cautelares
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Medidas Cautelares',
            'contenido' => 'Considerando la gravedad del delito de robo con violencia, las lesiones causadas al cajero y el riesgo de fuga del acusado, ¿Dicta prisión preventiva oficiosa o permite que el defensor solicite medidas alternativas como arraigo domiciliario?',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 700, 'y' => 50, 'momento' => 'medidas_cautelares']
        ]);

        // 5. Decisión sobre admisión de pruebas de la defensa
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Admisión de Pruebas de la Defensa',
            'contenido' => 'La defensa presenta las siguientes pruebas: 1) Testimonio de la madre del acusado como coartada, 2) Recibo del supermercado del día del robo, 3) Testimonio de vecinos sobre el carácter del acusado, 4) Informe psicológico del acusado. ¿Admite estas pruebas para su valoración?',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 900, 'y' => 50, 'momento' => 'pruebas_defensa']
        ]);

        // 6. Decisión sobre culpabilidad
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Determinación de Culpabilidad',
            'contenido' => 'Después de analizar todas las pruebas, declaraciones y alegatos de las partes, debe determinar si el acusado Juan Carlos Pérez es culpable del delito de robo con violencia en agravio de Carlos Mendoza. ¿Cuál es su decisión basada en la evidencia presentada?',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 1100, 'y' => 50, 'momento' => 'culpabilidad']
        ]);

        // 7. Decisión sobre atenuantes
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Consideración de Atenuantes',
            'contenido' => 'Si determina culpabilidad, debe considerar si existen atenuantes: 1) Primera vez que comete delito, 2) Necesidad económica extrema, 3) Arrepentimiento del acusado, 4) Colaboración con la investigación. ¿Reconoce alguna de estas circunstancias?',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 1300, 'y' => 50, 'momento' => 'atenuantes']
        ]);

        // 8. Decisión sobre reparación del daño
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Reparación del Daño',
            'contenido' => 'El cajero Carlos Mendoza solicita reparación del daño por $50,000 pesos por gastos médicos, días de trabajo perdidos y daño moral. ¿Ordena el pago de esta cantidad o considera una cantidad diferente?',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 1500, 'y' => 50, 'momento' => 'reparación_daño']
        ]);

        // Diálogos finales
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Sentencia Condenatoria Completa',
            'contenido' => 'Se condena al acusado Juan Carlos Pérez a 8 años de prisión por robo con violencia, más el pago de $50,000 pesos por reparación del daño. Se reconocen atenuantes que reducen la pena. La sentencia es firme y ejecutoria.',
            'tipo' => 'final',
            'es_inicial' => false,
            'es_final' => true,
            'orden' => $orden++,
            'posicion' => ['x' => 1700, 'y' => 50, 'momento' => 'sentencia_condenatoria']
        ]);

        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Absolución por Falta de Pruebas',
            'contenido' => 'Se absuelve al acusado Juan Carlos Pérez por falta de pruebas suficientes que demuestren su culpabilidad más allá de toda duda razonable. Se ordena su inmediata libertad y el pago de $10,000 pesos por daños y perjuicios por detención indebida.',
            'tipo' => 'final',
            'es_inicial' => false,
            'es_final' => true,
            'orden' => $orden++,
            'posicion' => ['x' => 1700, 'y' => 150, 'momento' => 'absolución']
        ]);

        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Suspensión del Proceso',
            'contenido' => 'Se suspende el proceso penal por falta de elementos para continuar. Se ordena la libertad del acusado bajo caución de $20,000 pesos y se programa nueva audiencia para cuando se reúnan más elementos probatorios.',
            'tipo' => 'final',
            'es_inicial' => false,
            'es_final' => true,
            'orden' => $orden++,
            'posicion' => ['x' => 1700, 'y' => 250, 'momento' => 'suspensión']
        ]);

        return $dialogos;
    }

    private function crearDialogosFiscalCompleto($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // 1. Alegatos de apertura
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Alegatos de Apertura del Ministerio Público',
            'contenido' => 'Señor Juez, el Ministerio Público acusa a Juan Carlos Pérez del delito de robo con violencia en agravio de Carlos Mendoza, empleado de la tienda OXXO. Presentaremos pruebas contundentes que demuestran su culpabilidad más allá de toda duda razonable.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 100, 'y' => 200, 'momento' => 'apertura']
        ]);

        // 2. Interrogatorio al cajero víctima
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Interrogatorio al Cajero Víctima',
            'contenido' => 'Señor Carlos Mendoza, ¿puede describir exactamente lo que ocurrió el día 15 de marzo a las 8:30 PM? ¿Reconoce al acusado Juan Carlos Pérez como la persona que lo amenazó con el cuchillo y le causó las heridas?',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 300, 'y' => 200, 'momento' => 'interrogatorio_cajero']
        ]);

        // 3. Interrogatorio al testigo ocular
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Interrogatorio al Testigo Ocular',
            'contenido' => 'Señora Ana Patricia López, usted declaró haber visto al acusado salir corriendo de la tienda con una bolsa en la mano. ¿Puede confirmar que era la misma persona que vio entrar minutos antes? ¿Está segura de su identificación?',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 500, 'y' => 200, 'momento' => 'interrogatorio_testigo']
        ]);

        // 4. Presentación de evidencia forense
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Presentación de Evidencia Forense',
            'contenido' => 'Señor Juez, presentamos el cuchillo encontrado en el lugar del robo con huellas dactilares que coinciden con el acusado, las grabaciones de las cámaras de seguridad que muestran el momento del robo, y el informe médico que confirma las heridas del cajero.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 700, 'y' => 200, 'momento' => 'evidencia_forense']
        ]);

        // 5. Interrogatorio al detective
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Interrogatorio al Detective',
            'contenido' => 'Detective Ramírez, ¿puede explicar cómo llegó al acusado como sospechoso? ¿Qué evidencia encontró en su domicilio que lo vincula con el robo? ¿El acusado tiene antecedentes penales?',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 900, 'y' => 200, 'momento' => 'interrogatorio_detective']
        ]);

        // 6. Interrogatorio al perito forense
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Interrogatorio al Perito Forense',
            'contenido' => 'Dr. Torres, ¿puede explicar los resultados del análisis de ADN en el cuchillo? ¿Las huellas dactilares coinciden con el acusado? ¿Las heridas del cajero son consistentes con el uso de este cuchillo?',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 1100, 'y' => 200, 'momento' => 'interrogatorio_perito']
        ]);

        // 7. Alegatos de clausura
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Alegatos de Clausura del Ministerio Público',
            'contenido' => 'Señor Juez, las pruebas son contundentes y consistentes. El acusado Juan Carlos Pérez cometió robo con violencia, causó lesiones al cajero Carlos Mendoza y debe ser condenado conforme a la ley. Solicito una sentencia ejemplar.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 1300, 'y' => 200, 'momento' => 'clausura']
        ]);

        return $dialogos;
    }

    private function crearDialogosDefensorCompleto($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // 1. Alegatos de apertura de la defensa
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Alegatos de Apertura de la Defensa',
            'contenido' => 'Señor Juez, mi cliente Juan Carlos Pérez es inocente. Presentaremos pruebas que demuestran que fue víctima de una identificación errónea, que tiene coartada sólida y que las pruebas de la fiscalía son insuficientes.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 100, 'y' => 350, 'momento' => 'apertura_defensa']
        ]);

        // 2. Decisión sobre estrategia de defensa
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Estrategia de Defensa',
            'contenido' => '¿Qué estrategia de defensa utilizará? 1) Demostrar coartada con testigos, 2) Cuestionar la identificación del cajero y testigo, 3) Atacar la evidencia forense, 4) Negociar un acuerdo de culpabilidad con pena reducida.',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 300, 'y' => 350, 'momento' => 'estrategia']
        ]);

        // 3. Contra-interrogatorio al cajero
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Contra-interrogatorio al Cajero',
            'contenido' => 'Señor Mendoza, ¿está absolutamente seguro de que mi cliente es la persona que lo amenazó? ¿No podría haber sido otra persona de características similares? ¿Cuánto tiempo duró el incidente? ¿Estaba nervioso o asustado?',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 500, 'y' => 350, 'momento' => 'contra_interrogatorio_cajero']
        ]);

        // 4. Contra-interrogatorio al testigo
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Contra-interrogatorio al Testigo',
            'contenido' => 'Señora López, ¿desde qué distancia vio a la persona salir corriendo? ¿Había buena iluminación? ¿Cuánto tiempo duró la observación? ¿Podría haber confundido a mi cliente con otra persona?',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 700, 'y' => 350, 'momento' => 'contra_interrogatorio_testigo']
        ]);

        // 5. Presentación de coartada
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Presentación de Coartada',
            'contenido' => 'Señor Juez, presentamos el testimonio de la señora María Pérez, madre de mi cliente, quien confirma que estaba en casa con él a la hora del robo. También presentamos el recibo del supermercado donde compró ese día.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 900, 'y' => 350, 'momento' => 'coartada']
        ]);

        // 6. Cuestionamiento de evidencia forense
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Cuestionamiento de Evidencia Forense',
            'contenido' => 'Dr. Torres, ¿puede garantizar que las huellas dactilares no fueron contaminadas durante el manejo de la evidencia? ¿Las pruebas de ADN son concluyentes o podrían ser de otra persona? ¿Se realizaron todas las pruebas necesarias?',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 1100, 'y' => 350, 'momento' => 'cuestionamiento_forense']
        ]);

        // 7. Alegatos de defensa
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Alegatos de Clausura de la Defensa',
            'contenido' => 'Señor Juez, las pruebas de la fiscalía son insuficientes y contradictorias. Mi cliente tiene coartada sólida, la identificación es dudosa y la evidencia forense es cuestionable. Solicito su absolución por falta de pruebas.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 1300, 'y' => 350, 'momento' => 'alegatos_defensa']
        ]);

        return $dialogos;
    }

    private function crearDialogosAcusadoCompleto($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // 1. Declaración inicial del acusado
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Declaración Inicial del Acusado',
            'contenido' => 'Señor Juez, yo no robé esa tienda. Estoy siendo acusado injustamente. Soy un padre soltero que trabaja como albañil para mantener a mi hijo. No tengo antecedentes penales y puedo demostrar mi inocencia.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 100, 'y' => 500, 'momento' => 'declaración_inicial']
        ]);

        // 2. Decisión sobre declarar
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Decisión de Declarar',
            'contenido' => '¿Qué decide hacer? 1) Declarar su inocencia y presentar coartada detallada, 2) Mantenerse en silencio y dejar que la defensa actúe, 3) Confesar el delito pero explicar las circunstancias, 4) Culpar a otra persona conocida.',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 300, 'y' => 500, 'momento' => 'decisión_declarar']
        ]);

        // 3. Presentación de coartada detallada
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Presentación de Coartada Detallada',
            'contenido' => 'Señor Juez, el día del robo yo estaba en mi casa con mi madre cuidando a mi hijo que estaba enfermo. Mi madre puede confirmarlo. También tengo el recibo del supermercado donde compré medicinas para mi hijo a las 7:30 PM.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 500, 'y' => 500, 'momento' => 'coartada_detallada']
        ]);

        // 4. Confesión con circunstancias
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Confesión con Circunstancias',
            'contenido' => 'Señor Juez, confieso que cometí el robo, pero fue porque mi hijo estaba muy enfermo y necesitaba dinero urgente para sus medicinas. No quería hacer daño a nadie, solo necesitaba el dinero. Me arrepiento de lo que hice.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 500, 'y' => 600, 'momento' => 'confesión']
        ]);

        // 5. Culpar a otra persona
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Culpar a Otra Persona',
            'contenido' => 'Señor Juez, yo no fui quien robó la tienda. Fue mi primo Roberto quien me pidió que le prestara mi ropa para un trabajo. Él es quien cometió el robo y me está culpando a mí para salvarse.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 500, 'y' => 700, 'momento' => 'culpar_otro']
        ]);

        // 6. Explicación de antecedentes
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Explicación de Antecedentes',
            'contenido' => 'Señor Juez, es cierto que tengo antecedentes menores por robo de comida cuando era joven, pero eso fue hace 10 años. Desde entonces he trabajado honestamente y soy padre responsable. No volvería a cometer un delito.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 700, 'y' => 500, 'momento' => 'antecedentes']
        ]);

        return $dialogos;
    }

    private function crearDialogosCajeroCompleto($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // 1. Declaración inicial del cajero
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Declaración Inicial del Cajero',
            'contenido' => 'Señor Juez, el día 15 de marzo a las 8:30 PM estaba trabajando en la caja de la tienda OXXO cuando entró un hombre con gorra azul y me amenazó con un cuchillo. Me hirió en el brazo izquierdo y se llevó aproximadamente $3,000 pesos.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 100, 'y' => 650, 'momento' => 'declaración_inicial']
        ]);

        // 2. Descripción detallada del agresor
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Descripción Detallada del Agresor',
            'contenido' => 'El hombre era de estatura media, aproximadamente 1.70 metros, moreno, de complexión delgada. Llevaba una gorra azul de los Yankees, una camiseta blanca y jeans. Tenía un tatuaje de una cruz en el brazo derecho. Estoy seguro de que es el acusado.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 300, 'y' => 650, 'momento' => 'descripción_agresor']
        ]);

        // 3. Secuencia detallada de eventos
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Secuencia Detallada de Eventos',
            'contenido' => 'Primero me pidió cambio para un billete de $100, luego sacó el cuchillo de debajo de su camisa y me dijo que le diera todo el dinero de la caja. Cuando me resistí, me cortó el brazo. Se llevó el dinero y salió corriendo hacia la calle principal.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 500, 'y' => 650, 'momento' => 'secuencia_eventos']
        ]);

        // 4. Impacto emocional y físico
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Impacto Emocional y Físico',
            'contenido' => 'Este incidente me ha afectado mucho. Tengo miedo de trabajar en la tienda, no puedo dormir bien y necesito terapia psicológica. También perdí 15 días de trabajo por las heridas y gasté $5,000 pesos en medicinas.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 700, 'y' => 650, 'momento' => 'impacto']
        ]);

        // 5. Identificación en rueda de reconocimiento
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Identificación en Rueda de Reconocimiento',
            'contenido' => 'Cuando la policía me mostró las fotos en la rueda de reconocimiento, identifiqué inmediatamente al acusado como la persona que me amenazó. Estoy 100% seguro de que es él. No hay duda en mi mente.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 900, 'y' => 650, 'momento' => 'identificación']
        ]);

        // 6. Solicitud de reparación del daño
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Solicitud de Reparación del Daño',
            'contenido' => 'Señor Juez, solicito que el acusado me pague $50,000 pesos por reparación del daño: $5,000 por gastos médicos, $15,000 por días de trabajo perdidos, $20,000 por daño moral y $10,000 por terapia psicológica.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 1100, 'y' => 650, 'momento' => 'reparación_daño']
        ]);

        return $dialogos;
    }

    private function crearDialogosTestigoCompleto($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // 1. Declaración inicial del testigo
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Declaración Inicial del Testigo',
            'contenido' => 'Señor Juez, yo soy Ana Patricia López, maestra de primaria. El día del robo estaba comprando en la tienda OXXO cuando escuché gritos y vi a un hombre salir corriendo con una bolsa en la mano.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 100, 'y' => 800, 'momento' => 'declaración_inicial']
        ]);

        // 2. Descripción del sospechoso
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Descripción del Sospechoso',
            'contenido' => 'El hombre que vi salir corriendo era de estatura media, moreno, llevaba una gorra azul y una camiseta blanca. Corrió muy rápido hacia la calle principal y se subió a un taxi que lo esperaba.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 300, 'y' => 800, 'momento' => 'descripción_sospechoso']
        ]);

        // 3. Identificación en rueda de reconocimiento
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Identificación en Rueda de Reconocimiento',
            'contenido' => 'Cuando la policía me mostró las fotos, identifiqué al acusado como la persona que vi salir corriendo. Estoy bastante segura de que es él, aunque solo lo vi por unos segundos.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 500, 'y' => 800, 'momento' => 'identificación']
        ]);

        // 4. Detalles adicionales
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Detalles Adicionales',
            'contenido' => 'Recuerdo que el taxi era amarillo y tenía el número 1234. El conductor parecía estar esperando al hombre. También vi que el cajero salió corriendo detrás del hombre gritando "¡Ladrón!".',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 700, 'y' => 800, 'momento' => 'detalles_adicionales']
        ]);

        // 5. Dudas sobre la identificación
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Dudas sobre la Identificación',
            'contenido' => 'Señor Juez, debo ser honesta. Solo vi al hombre por unos segundos y estaba corriendo. La iluminación no era perfecta. Aunque creo que es el acusado, no puedo estar 100% segura.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 900, 'y' => 800, 'momento' => 'dudas']
        ]);

        return $dialogos;
    }

    private function crearDialogosPeritoCompleto($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // 1. Informe forense inicial
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Informe Forense Inicial',
            'contenido' => 'Señor Juez, como perito forense, analicé la evidencia del caso. El cuchillo encontrado en el lugar tiene huellas dactilares que coinciden con el acusado Juan Carlos Pérez en un 99.7% de probabilidad.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 100, 'y' => 950, 'momento' => 'informe_inicial']
        ]);

        // 2. Análisis de heridas
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Análisis de Heridas del Cajero',
            'contenido' => 'Las heridas del cajero Carlos Mendoza son consistentes con un corte producido por un cuchillo de cocina de 15 cm de hoja. La profundidad de 3 cm indica que fue hecho con fuerza considerable, probablemente en defensa propia.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 300, 'y' => 950, 'momento' => 'análisis_heridas']
        ]);

        // 3. Evidencia de ADN
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Evidencia de ADN',
            'contenido' => 'Encontramos restos de sangre en el cuchillo que coinciden con el ADN del cajero Carlos Mendoza. También hay huellas dactilares del acusado en el mango del cuchillo. La evidencia es concluyente.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 500, 'y' => 950, 'momento' => 'evidencia_adn']
        ]);

        // 4. Análisis de ropa
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Análisis de Ropa Encontrada',
            'contenido' => 'La ropa encontrada en el domicilio del acusado tiene restos de sangre que coinciden con el ADN del cajero. La camiseta blanca tiene una mancha de sangre de 5 cm de diámetro en el área del pecho.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 700, 'y' => 950, 'momento' => 'análisis_ropa']
        ]);

        // 5. Conclusiones del perito
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Conclusiones del Perito',
            'contenido' => 'Basándome en el análisis forense, puedo concluir que el acusado Juan Carlos Pérez estuvo en contacto con el cuchillo usado en el robo y con la sangre del cajero. La evidencia física es contundente.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 900, 'y' => 950, 'momento' => 'conclusiones']
        ]);

        return $dialogos;
    }

    private function crearDialogosDetectiveCompleto($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // 1. Informe policial inicial
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Informe Policial Inicial',
            'contenido' => 'Señor Juez, como detective de la Policía Municipal, llegué al lugar del robo 15 minutos después de recibir la llamada. El cajero Carlos Mendoza estaba herido y nos dio una descripción detallada del agresor.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 100, 'y' => 1100, 'momento' => 'informe_inicial']
        ]);

        // 2. Proceso de investigación
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Proceso de Investigación',
            'contenido' => 'Basándome en la descripción del cajero y el testigo, busqué en la base de datos de antecedentes y encontré al acusado Juan Carlos Pérez que tenía características similares y antecedentes por robo menor.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 300, 'y' => 1100, 'momento' => 'investigación']
        ]);

        // 3. Detención del acusado
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Detención del Acusado',
            'contenido' => 'Detuve al acusado en su domicilio ubicado en la Colonia Centro, a 3 cuadras de la tienda OXXO. Al momento de la detención, estaba nervioso y negó haber cometido el robo.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 500, 'y' => 1100, 'momento' => 'detención']
        ]);

        // 4. Evidencia encontrada
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Evidencia Encontrada en el Domicilio',
            'contenido' => 'En el domicilio del acusado encontramos ropa similar a la descrita por el cajero y el testigo: una gorra azul de los Yankees, una camiseta blanca manchada de sangre, y el cuchillo usado en el robo.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 700, 'y' => 1100, 'momento' => 'evidencia']
        ]);

        // 5. Declaración del acusado
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Declaración del Acusado en Detención',
            'contenido' => 'Durante el interrogatorio, el acusado negó haber cometido el robo pero no pudo explicar por qué tenía la ropa manchada de sangre ni el cuchillo. Dijo que estaba en casa con su madre.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 900, 'y' => 1100, 'momento' => 'declaración_detención']
        ]);

        return $dialogos;
    }

    private function crearDialogosSecretarioCompleto($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // 1. Registro de apertura
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Registro de Apertura de Audiencia',
            'contenido' => 'Se registra la apertura de la audiencia del caso penal 1234/2024. Presentes: Jueza María Elena Rodríguez, Fiscal Alejandro Morales, Abogado Defensor Roberto Silva, Acusado Juan Carlos Pérez y todas las partes.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 100, 'y' => 1250, 'momento' => 'apertura']
        ]);

        // 2. Registro de declaraciones
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Registro de Declaraciones',
            'contenido' => 'Se registran las declaraciones del cajero víctima Carlos Mendoza, testigo ocular Ana Patricia López, perito forense Dr. Miguel Torres y detective José Luis Ramírez. Todas las declaraciones quedan asentadas en el expediente.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 300, 'y' => 1250, 'momento' => 'declaraciones']
        ]);

        // 3. Registro de pruebas
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Registro de Pruebas Admitidas',
            'contenido' => 'Se registran las pruebas admitidas: cuchillo con huellas dactilares, grabaciones de cámaras de seguridad, informe médico de lesiones, evidencia de ADN, ropa del acusado y testimonios de testigos.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 500, 'y' => 1250, 'momento' => 'pruebas']
        ]);

        // 4. Registro de sentencia
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Registro de Sentencia',
            'contenido' => 'Se registra la sentencia dictada por la Jueza María Elena Rodríguez. La sentencia queda asentada en el expediente y se notifica a todas las partes conforme a la ley.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 700, 'y' => 1250, 'momento' => 'sentencia']
        ]);

        return $dialogos;
    }

    private function crearDialogosPublicoCompleto($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // 1. Reacción inicial del público
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Reacción Inicial del Público',
            'contenido' => 'El público presente en la audiencia murmura cuando se presentan las pruebas. Los familiares del cajero Carlos Mendoza muestran apoyo y piden justicia, mientras que los del acusado Juan Carlos Pérez claman por su inocencia.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 100, 'y' => 1400, 'momento' => 'reacción_inicial']
        ]);

        // 2. Presión social durante el juicio
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Presión Social Durante el Juicio',
            'contenido' => 'Los familiares del cajero piden justicia y una sentencia ejemplar, mientras que los del acusado argumentan que es un padre responsable que no podría haber cometido tal delito. El ambiente en la sala es tenso.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 300, 'y' => 1400, 'momento' => 'presión_social']
        ]);

        // 3. Reacción a la evidencia
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Reacción a la Evidencia',
            'contenido' => 'Cuando se presenta la evidencia forense, el público se conmociona. Los familiares del cajero lloran al ver las fotos de las heridas, mientras que los del acusado se muestran incrédulos ante las pruebas.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 500, 'y' => 1400, 'momento' => 'reacción_evidencia']
        ]);

        // 4. Reacción a la sentencia
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Reacción a la Sentencia',
            'contenido' => 'Al escuchar la sentencia, los familiares del cajero expresan alivio y satisfacción, mientras que los del acusado muestran desesperación y lloran. El público en general parece satisfecho con la decisión judicial.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'posicion' => ['x' => 700, 'y' => 1400, 'momento' => 'reacción_sentencia']
        ]);

        return $dialogos;
    }

    private function crearOpcionesEspecificas($dialogos)
    {
        $opciones = [];
        
        // Crear opciones específicas para cada diálogo de decisión
        foreach ($dialogos as $rol => $dialogosRol) {
            foreach ($dialogosRol as $dialogo) {
                if ($dialogo->tipo === 'decision') {
                    $opcionesDialogo = $this->crearOpcionesEspecificasParaDialogo($dialogo);
                    $opciones = array_merge($opciones, $opcionesDialogo);
                }
            }
        }

        return $opciones;
    }

    private function crearOpcionesEspecificasParaDialogo($dialogo)
    {
        $opciones = [];
        
        // Opciones específicas basadas en el título del diálogo
        $opcionesData = $this->obtenerOpcionesPorTitulo($dialogo->titulo);
        
        foreach ($opcionesData as $opcionData) {
            $opcion = PanelDialogoOpcion::create([
                'dialogo_id' => $dialogo->id,
                'texto' => $opcionData['texto'],
                'descripcion' => $opcionData['descripcion'],
                'letra' => chr(65 + $opcionData['orden']), // A, B, C
                'orden' => $opcionData['orden'],
                'color' => $opcionData['color'],
                'puntuacion' => $opcionData['puntuacion_impacto'],
                'configuracion' => $opcionData['consecuencias']
            ]);
            $opciones[] = $opcion;
        }

        return $opciones;
    }

    private function obtenerOpcionesPorTitulo($titulo)
    {
        $opcionesPorTitulo = [
            'Admisión de Pruebas del Ministerio Público' => [
                [
                    'texto' => 'Admitir todas las pruebas',
                    'descripcion' => 'Se admiten todas las pruebas presentadas por el Ministerio Público',
                    'orden' => 0,
                    'color' => '#28a745',
                    'puntuacion_impacto' => 3,
                    'consecuencias' => ['pruebas_admitidas' => 'todas', 'impacto' => 'alto']
                ],
                [
                    'texto' => 'Admitir solo pruebas directas',
                    'descripcion' => 'Se admiten solo las pruebas directas, se excluyen las indirectas',
                    'orden' => 1,
                    'color' => '#ffc107',
                    'puntuacion_impacto' => 2,
                    'consecuencias' => ['pruebas_admitidas' => 'parciales', 'impacto' => 'medio']
                ],
                [
                    'texto' => 'Rechazar pruebas por defectos',
                    'descripcion' => 'Se rechazan las pruebas por defectos en el proceso',
                    'orden' => 2,
                    'color' => '#dc3545',
                    'puntuacion_impacto' => 1,
                    'consecuencias' => ['pruebas_admitidas' => 'ninguna', 'impacto' => 'bajo']
                ]
            ],
            'Derecho a Declarar del Acusado' => [
                [
                    'texto' => 'Permitir declaración inmediata',
                    'descripcion' => 'Se permite que el acusado declare en este momento',
                    'orden' => 0,
                    'color' => '#28a745',
                    'puntuacion_impacto' => 2,
                    'consecuencias' => ['declaración' => 'inmediata', 'impacto' => 'medio']
                ],
                [
                    'texto' => 'Reservar derecho a declarar',
                    'descripcion' => 'Se reserva el derecho del acusado a declarar',
                    'orden' => 1,
                    'color' => '#ffc107',
                    'puntuacion_impacto' => 1,
                    'consecuencias' => ['declaración' => 'reservada', 'impacto' => 'bajo']
                ],
                [
                    'texto' => 'Solicitar declaración posterior',
                    'descripcion' => 'Se programa la declaración para después',
                    'orden' => 2,
                    'color' => '#17a2b8',
                    'puntuacion_impacto' => 1,
                    'consecuencias' => ['declaración' => 'posterior', 'impacto' => 'bajo']
                ]
            ],
            'Medidas Cautelares' => [
                [
                    'texto' => 'Dictar prisión preventiva',
                    'descripcion' => 'Se dicta prisión preventiva oficiosa',
                    'orden' => 0,
                    'color' => '#dc3545',
                    'puntuacion_impacto' => 3,
                    'consecuencias' => ['medida' => 'prisión', 'impacto' => 'alto']
                ],
                [
                    'texto' => 'Arraigo domiciliario',
                    'descripcion' => 'Se ordena arraigo domiciliario',
                    'orden' => 1,
                    'color' => '#ffc107',
                    'puntuacion_impacto' => 2,
                    'consecuencias' => ['medida' => 'arraigo', 'impacto' => 'medio']
                ],
                [
                    'texto' => 'Libertad bajo caución',
                    'descripcion' => 'Se otorga libertad bajo caución',
                    'orden' => 2,
                    'color' => '#28a745',
                    'puntuacion_impacto' => 1,
                    'consecuencias' => ['medida' => 'caución', 'impacto' => 'bajo']
                ]
            ],
            'Determinación de Culpabilidad' => [
                [
                    'texto' => 'Declarar culpable',
                    'descripcion' => 'Se declara culpable al acusado',
                    'orden' => 0,
                    'color' => '#dc3545',
                    'puntuacion_impacto' => 3,
                    'consecuencias' => ['culpabilidad' => 'culpable', 'impacto' => 'alto']
                ],
                [
                    'texto' => 'Absolver por falta de pruebas',
                    'descripcion' => 'Se absuelve por falta de pruebas suficientes',
                    'orden' => 1,
                    'color' => '#28a745',
                    'puntuacion_impacto' => 1,
                    'consecuencias' => ['culpabilidad' => 'absuelto', 'impacto' => 'bajo']
                ],
                [
                    'texto' => 'Suspender proceso',
                    'descripcion' => 'Se suspende el proceso por falta de elementos',
                    'orden' => 2,
                    'color' => '#ffc107',
                    'puntuacion_impacto' => 2,
                    'consecuencias' => ['culpabilidad' => 'suspendido', 'impacto' => 'medio']
                ]
            ],
            'Estrategia de Defensa' => [
                [
                    'texto' => 'Demostrar coartada',
                    'descripcion' => 'Se presenta coartada con testigos',
                    'orden' => 0,
                    'color' => '#28a745',
                    'puntuacion_impacto' => 2,
                    'consecuencias' => ['estrategia' => 'coartada', 'impacto' => 'medio']
                ],
                [
                    'texto' => 'Cuestionar identificación',
                    'descripcion' => 'Se cuestiona la identificación de testigos',
                    'orden' => 1,
                    'color' => '#ffc107',
                    'puntuacion_impacto' => 2,
                    'consecuencias' => ['estrategia' => 'identificación', 'impacto' => 'medio']
                ],
                [
                    'texto' => 'Atacar evidencia forense',
                    'descripcion' => 'Se ataca la evidencia forense',
                    'orden' => 2,
                    'color' => '#17a2b8',
                    'puntuacion_impacto' => 3,
                    'consecuencias' => ['estrategia' => 'forense', 'impacto' => 'alto']
                ]
            ],
            'Decisión de Declarar' => [
                [
                    'texto' => 'Declarar inocencia',
                    'descripcion' => 'Se declara inocente y presenta coartada',
                    'orden' => 0,
                    'color' => '#28a745',
                    'puntuacion_impacto' => 2,
                    'consecuencias' => ['declaración' => 'inocencia', 'impacto' => 'medio']
                ],
                [
                    'texto' => 'Mantenerse en silencio',
                    'descripcion' => 'Se mantiene en silencio',
                    'orden' => 1,
                    'color' => '#6c757d',
                    'puntuacion_impacto' => 1,
                    'consecuencias' => ['declaración' => 'silencio', 'impacto' => 'bajo']
                ],
                [
                    'texto' => 'Confesar con circunstancias',
                    'descripcion' => 'Se confiesa pero explica circunstancias',
                    'orden' => 2,
                    'color' => '#dc3545',
                    'puntuacion_impacto' => 3,
                    'consecuencias' => ['declaración' => 'confesión', 'impacto' => 'alto']
                ]
            ]
        ];

        return $opcionesPorTitulo[$titulo] ?? [
            [
                'texto' => 'Opción A',
                'descripcion' => 'Primera opción disponible',
                'orden' => 0,
                'color' => '#28a745',
                'puntuacion_impacto' => 1,
                'consecuencias' => ['impacto' => 1]
            ],
            [
                'texto' => 'Opción B',
                'descripcion' => 'Segunda opción disponible',
                'orden' => 1,
                'color' => '#ffc107',
                'puntuacion_impacto' => 2,
                'consecuencias' => ['impacto' => 2]
            ],
            [
                'texto' => 'Opción C',
                'descripcion' => 'Tercera opción disponible',
                'orden' => 2,
                'color' => '#fd7e14',
                'puntuacion_impacto' => 3,
                'consecuencias' => ['impacto' => 3]
            ]
        ];
    }

    private function crearConexionesComplejas($escenario, $dialogos, $opciones)
    {
        // Crear conexiones básicas entre diálogos del mismo rol
        foreach ($dialogos as $rol => $dialogosRol) {
            for ($i = 0; $i < count($dialogosRol) - 1; $i++) {
                $dialogoActual = $dialogosRol[$i];
                $dialogoSiguiente = $dialogosRol[$i + 1];
                
                PanelDialogoConexion::create([
                    'escenario_id' => $escenario->id,
                    'dialogo_origen_id' => $dialogoActual->id,
                    'dialogo_destino_id' => $dialogoSiguiente->id,
                    'tipo' => 'directa',
                    'activo' => true,
                    'condiciones' => ['conexion_automatica' => true, 'secuencia' => $i]
                ]);
            }
        }

        // Crear conexiones entre roles (interacciones)
        $this->crearConexionesEntreRoles($escenario, $dialogos);
    }

    private function crearConexionesEntreRoles($escenario, $dialogos)
    {
        // Conexiones específicas entre roles
        $conexionesEntreRoles = [
            // Juez puede influir en otros roles
            ['origen' => 'Juez María Elena Rodríguez', 'destino' => 'Fiscal Alejandro Morales', 'momento' => 'admisión_pruebas'],
            ['origen' => 'Juez María Elena Rodríguez', 'destino' => 'Abogado Defensor Roberto Silva', 'momento' => 'medidas_cautelares'],
            
            // Fiscal interroga a testigos
            ['origen' => 'Fiscal Alejandro Morales', 'destino' => 'Cajero Carlos Mendoza', 'momento' => 'interrogatorio_cajero'],
            ['origen' => 'Fiscal Alejandro Morales', 'destino' => 'Testigo Ana Patricia López', 'momento' => 'interrogatorio_testigo'],
            
            // Defensor contra-interroga
            ['origen' => 'Abogado Defensor Roberto Silva', 'destino' => 'Cajero Carlos Mendoza', 'momento' => 'contra_interrogatorio_cajero'],
            ['origen' => 'Abogado Defensor Roberto Silva', 'destino' => 'Testigo Ana Patricia López', 'momento' => 'contra_interrogatorio_testigo'],
        ];

        foreach ($conexionesEntreRoles as $conexion) {
            $dialogoOrigen = $this->buscarDialogoPorMomento($dialogos[$conexion['origen']], $conexion['momento']);
            $dialogoDestino = $this->buscarDialogoPorMomento($dialogos[$conexion['destino']], $conexion['momento']);
            
            if ($dialogoOrigen && $dialogoDestino) {
                PanelDialogoConexion::create([
                    'escenario_id' => $escenario->id,
                    'dialogo_origen_id' => $dialogoOrigen->id,
                    'dialogo_destino_id' => $dialogoDestino->id,
                    'tipo' => 'directa',
                    'activo' => true,
                    'condiciones' => ['conexion_interaccion' => true, 'momento' => $conexion['momento']]
                ]);
            }
        }
    }

    private function buscarDialogoPorMomento($dialogos, $momento)
    {
        foreach ($dialogos as $dialogo) {
            if (isset($dialogo->posicion['momento']) && $dialogo->posicion['momento'] === $momento) {
                return $dialogo;
            }
        }
        return null;
    }
}
