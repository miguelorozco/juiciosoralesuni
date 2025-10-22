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

class DialogoRoboOXXOSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->crearEscenario();
        });
    }

    private function crearEscenario()
    {
        // Crear el escenario principal
        $escenario = PanelDialogoEscenario::create([
            'nombre' => 'Robo a Tienda OXXO - Colonia Centro',
            'descripcion' => 'Caso penal complejo sobre el robo violento a una tienda OXXO donde el cajero resultó herido. Incluye múltiples testigos, evidencia forense y diferentes estrategias de defensa.',
            'creado_por' => 1, // Admin
            'estado' => 'activo',
            'configuracion' => [
                'tipo' => 'penal',
                'publico' => true,
                'complejidad' => 'alta',
                'duracion_estimada' => '120 minutos',
                'roles_requeridos' => 6,
                'roles_opcionales' => 4
            ]
        ]);

        // Crear roles del escenario
        $roles = $this->crearRoles($escenario);
        
        // Crear flujos para cada rol
        $flujos = $this->crearFlujos($escenario, $roles);
        
        // Crear diálogos para cada flujo
        $dialogos = $this->crearDialogos($flujos);
        
        // Crear opciones de decisión
        $opciones = $this->crearOpciones($dialogos);
        
        // Crear conexiones entre diálogos
        $this->crearConexiones($escenario, $dialogos, $opciones);
        
        echo "✅ Escenario 'Robo a Tienda OXXO' creado exitosamente con " . count($dialogos) . " diálogos\n";
    }

    private function crearRoles($escenario)
    {
        $roles = [
            [
                'nombre' => 'Juez',
                'descripcion' => 'Preside el juicio y toma decisiones finales sobre la culpabilidad',
                'es_principal' => true,
                'configuracion' => ['color' => '#8B4513', 'icono' => 'bi-gavel']
            ],
            [
                'nombre' => 'Fiscal',
                'descripcion' => 'Representa al Ministerio Público, busca probar la culpabilidad del acusado',
                'es_principal' => true,
                'configuracion' => ['color' => '#DC143C', 'icono' => 'bi-shield-check']
            ],
            [
                'nombre' => 'Abogado Defensor',
                'descripcion' => 'Defiende al acusado, busca demostrar su inocencia o atenuar la pena',
                'es_principal' => true,
                'configuracion' => ['color' => '#4169E1', 'icono' => 'bi-shield']
            ],
            [
                'nombre' => 'Acusado',
                'descripcion' => 'Persona acusada del robo, puede declarar o mantenerse en silencio',
                'es_principal' => true,
                'configuracion' => ['color' => '#FF6347', 'icono' => 'bi-person']
            ],
            [
                'nombre' => 'Cajero/Víctima',
                'descripcion' => 'Empleado de OXXO que fue víctima del robo y resultó herido',
                'es_principal' => true,
                'configuracion' => ['color' => '#32CD32', 'icono' => 'bi-person-heart']
            ],
            [
                'nombre' => 'Testigo Ocular',
                'descripcion' => 'Cliente que presenció el robo desde afuera de la tienda',
                'es_principal' => true,
                'configuracion' => ['color' => '#FFD700', 'icono' => 'bi-eye']
            ],
            [
                'nombre' => 'Perito Forense',
                'descripcion' => 'Especialista que analiza la evidencia física del caso',
                'es_principal' => false,
                'configuracion' => ['color' => '#9370DB', 'icono' => 'bi-clipboard-data']
            ],
            [
                'nombre' => 'Policía Investigador',
                'descripcion' => 'Agente que realizó la investigación inicial del caso',
                'es_principal' => false,
                'configuracion' => ['color' => '#2F4F4F', 'icono' => 'bi-shield-fill-check']
            ],
            [
                'nombre' => 'Secretario de Acuerdos',
                'descripcion' => 'Funcionario que registra las declaraciones y decisiones del juicio',
                'es_principal' => false,
                'configuracion' => ['color' => '#696969', 'icono' => 'bi-journal-text']
            ],
            [
                'nombre' => 'Público',
                'descripcion' => 'Observadores del juicio, pueden influir en el ambiente',
                'es_principal' => false,
                'configuracion' => ['color' => '#D3D3D3', 'icono' => 'bi-people']
            ]
        ];

        $rolesCreados = [];
        foreach ($roles as $index => $rolData) {
            $rol = PanelDialogoRol::create([
                'escenario_id' => $escenario->id,
                'nombre' => $rolData['nombre'],
                'descripcion' => $rolData['descripcion'],
                'es_principal' => $rolData['es_principal'],
                'configuracion' => $rolData['configuracion'],
                'orden' => $index
            ]);
            $rolesCreados[$rolData['nombre']] = $rol;
        }

        return $rolesCreados;
    }

    private function crearFlujos($escenario, $roles)
    {
        $flujos = [];
        
        foreach ($roles as $nombreRol => $rol) {
            $flujo = PanelDialogoFlujo::create([
                'escenario_id' => $escenario->id,
                'rol_id' => $rol->id,
                'nombre' => "Flujo Principal de {$nombreRol}",
                'descripcion' => "Secuencia de diálogos para el rol {$nombreRol}",
                'configuracion' => ['orden' => 0, 'activo' => true]
            ]);
            $flujos[$nombreRol] = $flujo;
        }

        return $flujos;
    }

    private function crearDialogos($flujos)
    {
        $dialogos = [];
        
        // Crear diálogos para cada rol
        foreach ($flujos as $nombreRol => $flujo) {
            $dialogos[$nombreRol] = $this->crearDialogosPorRol($flujo, $nombreRol);
        }

        return $dialogos;
    }

    private function crearDialogosPorRol($flujo, $nombreRol)
    {
        $dialogos = [];
        
        switch ($nombreRol) {
            case 'Juez':
                $dialogos = $this->crearDialogosJuez($flujo);
                break;
            case 'Fiscal':
                $dialogos = $this->crearDialogosFiscal($flujo);
                break;
            case 'Abogado Defensor':
                $dialogos = $this->crearDialogosDefensor($flujo);
                break;
            case 'Acusado':
                $dialogos = $this->crearDialogosAcusado($flujo);
                break;
            case 'Cajero/Víctima':
                $dialogos = $this->crearDialogosCajero($flujo);
                break;
            case 'Testigo Ocular':
                $dialogos = $this->crearDialogosTestigo($flujo);
                break;
            case 'Perito Forense':
                $dialogos = $this->crearDialogosPerito($flujo);
                break;
            case 'Policía Investigador':
                $dialogos = $this->crearDialogosPolicia($flujo);
                break;
            case 'Secretario de Acuerdos':
                $dialogos = $this->crearDialogosSecretario($flujo);
                break;
            case 'Público':
                $dialogos = $this->crearDialogosPublico($flujo);
                break;
        }

        return $dialogos;
    }

    private function crearDialogosJuez($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // Diálogo inicial
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Apertura del Juicio',
            'contenido' => 'Se abre la audiencia del caso penal número 1234/2024, por el delito de robo con violencia en agravio de la tienda OXXO ubicada en la Colonia Centro. Solicito que se identifiquen todas las partes.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 100, 'y' => 50]
        ]);

        // Diálogo de decisión sobre admisión de pruebas
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Admisión de Pruebas',
            'contenido' => 'El Ministerio Público presenta las siguientes pruebas: 1) Declaración del cajero víctima, 2) Testimonio del testigo ocular, 3) Evidencia forense del cuchillo, 4) Grabaciones de cámaras de seguridad. ¿Admite estas pruebas?',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 300, 'y' => 50]
        ]);

        // Diálogo de decisión sobre declaración del acusado
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Declaración del Acusado',
            'contenido' => 'Se le informa al acusado su derecho a declarar o mantenerse en silencio. ¿Permite que declare en este momento o prefiere que se reserve su derecho?',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 500, 'y' => 50]
        ]);

        // Diálogo de decisión sobre medidas cautelares
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Medidas Cautelares',
            'contenido' => 'Considerando la gravedad del delito y el riesgo de fuga, ¿Dicta prisión preventiva oficiosa o permite que el defensor solicite medidas alternativas?',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 700, 'y' => 50]
        ]);

        // Diálogo de decisión sobre culpabilidad
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Sentencia de Culpabilidad',
            'contenido' => 'Después de analizar todas las pruebas y declaraciones, debe determinar si el acusado es culpable del delito de robo con violencia. ¿Cuál es su decisión?',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 900, 'y' => 50]
        ]);

        // Diálogos finales
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Sentencia Condenatoria',
            'contenido' => 'Se condena al acusado a 8 años de prisión por robo con violencia, más el pago de daños y perjuicios por $50,000 pesos. La sentencia es firme.',
            'tipo' => 'final',
            'es_inicial' => false,
            'es_final' => true,
            'orden' => $orden++,
            'metadata' => ['x' => 1100, 'y' => 50]
        ]);

        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Absolución',
            'contenido' => 'Se absuelve al acusado por falta de pruebas suficientes. Se ordena su inmediata libertad y el pago de daños y perjuicios por detención indebida.',
            'tipo' => 'final',
            'es_inicial' => false,
            'es_final' => true,
            'orden' => $orden++,
            'metadata' => ['x' => 1100, 'y' => 150]
        ]);

        return $dialogos;
    }

    private function crearDialogosFiscal($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // Diálogo inicial
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Alegatos de Apertura',
            'contenido' => 'Señor Juez, el Ministerio Público acusa a Juan Pérez del delito de robo con violencia. Presentaremos pruebas contundentes que demuestran su culpabilidad.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 100, 'y' => 200]
        ]);

        // Diálogo de interrogatorio al cajero
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Interrogatorio al Cajero',
            'contenido' => 'Señor cajero, ¿puede describir exactamente lo que ocurrió el día del robo? ¿Reconoce al acusado como la persona que lo amenazó con el cuchillo?',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 300, 'y' => 200]
        ]);

        // Diálogo de interrogatorio al testigo
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Interrogatorio al Testigo',
            'contenido' => 'Señor testigo, usted declaró haber visto al acusado salir corriendo de la tienda. ¿Puede confirmar que era la misma persona que vio entrar minutos antes?',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 500, 'y' => 200]
        ]);

        // Diálogo de presentación de evidencia
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Presentación de Evidencia',
            'contenido' => 'Señor Juez, presentamos el cuchillo encontrado en el lugar, las grabaciones de las cámaras de seguridad y el informe forense que confirma las heridas del cajero.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 700, 'y' => 200]
        ]);

        // Diálogo de alegatos de clausura
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Alegatos de Clausura',
            'contenido' => 'Señor Juez, las pruebas son contundentes. El acusado cometió robo con violencia, causó lesiones al cajero y debe ser condenado conforme a la ley.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 900, 'y' => 200]
        ]);

        return $dialogos;
    }

    private function crearDialogosDefensor($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // Diálogo inicial
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Defensa del Acusado',
            'contenido' => 'Señor Juez, mi cliente Juan Pérez es inocente. Presentaremos pruebas que demuestran que fue víctima de una identificación errónea y que tiene coartada.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 100, 'y' => 350]
        ]);

        // Diálogo de estrategia de defensa
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Estrategia de Defensa',
            'contenido' => '¿Qué estrategia de defensa utilizará? 1) Demostrar coartada, 2) Cuestionar la identificación, 3) Atacar la evidencia forense, 4) Negociar un acuerdo.',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 300, 'y' => 350]
        ]);

        // Diálogo de contra-interrogatorio al cajero
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Contra-interrogatorio al Cajero',
            'contenido' => 'Señor cajero, ¿está seguro de que mi cliente es la persona que lo amenazó? ¿No podría haber sido otra persona de características similares?',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 500, 'y' => 350]
        ]);

        // Diálogo de presentación de coartada
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Presentación de Coartada',
            'contenido' => 'Señor Juez, presentamos el testimonio de la señora María González, vecina de mi cliente, quien confirma que estaba en su casa a la hora del robo.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 700, 'y' => 350]
        ]);

        // Diálogo de alegatos de defensa
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Alegatos de Defensa',
            'contenido' => 'Señor Juez, las pruebas de la fiscalía son insuficientes. Mi cliente tiene coartada y la identificación es dudosa. Solicito su absolución.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 900, 'y' => 350]
        ]);

        return $dialogos;
    }

    private function crearDialogosAcusado($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // Diálogo inicial
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Declaración del Acusado',
            'contenido' => 'Señor Juez, yo no robé esa tienda. Estoy siendo acusado injustamente. Tengo una coartada y puedo demostrar mi inocencia.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 100, 'y' => 500]
        ]);

        // Diálogo de decisión sobre declarar
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Decisión de Declarar',
            'contenido' => '¿Qué decide hacer? 1) Declarar su inocencia y presentar coartada, 2) Mantenerse en silencio, 3) Confesar el delito, 4) Culpar a otra persona.',
            'tipo' => 'decision',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 300, 'y' => 500]
        ]);

        // Diálogo de presentación de coartada
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Presentación de Coartada',
            'contenido' => 'Señor Juez, el día del robo yo estaba en mi casa con mi madre. Ella puede confirmarlo. También tengo el recibo del supermercado donde compré ese día.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 500, 'y' => 500]
        ]);

        // Diálogo de confesión
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Confesión del Acusado',
            'contenido' => 'Señor Juez, confieso que cometí el robo, pero fue porque necesitaba dinero para la medicina de mi hijo enfermo. No quería hacer daño a nadie.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 500, 'y' => 600]
        ]);

        return $dialogos;
    }

    private function crearDialogosCajero($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // Diálogo inicial
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Declaración del Cajero',
            'contenido' => 'Señor Juez, el día del robo estaba trabajando en la caja cuando entró un hombre con gorra y me amenazó con un cuchillo. Me hirió en el brazo y se llevó el dinero.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 100, 'y' => 650]
        ]);

        // Diálogo de descripción del agresor
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Descripción del Agresor',
            'contenido' => 'El hombre era de estatura media, moreno, llevaba una gorra azul y una camiseta blanca. Tenía un tatuaje en el brazo derecho. Estoy seguro de que es el acusado.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 300, 'y' => 650]
        ]);

        // Diálogo de secuencia de eventos
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Secuencia de Eventos',
            'contenido' => 'Primero me pidió cambio, luego sacó el cuchillo y me dijo que le diera todo el dinero. Cuando me resistí, me cortó el brazo. Se llevó aproximadamente $3,000 pesos.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 500, 'y' => 650]
        ]);

        // Diálogo de impacto emocional
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Impacto Emocional',
            'contenido' => 'Este incidente me ha afectado mucho. Tengo miedo de trabajar en la tienda y necesito terapia psicológica. También perdí días de trabajo por las heridas.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 700, 'y' => 650]
        ]);

        return $dialogos;
    }

    private function crearDialogosTestigo($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // Diálogo inicial
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Declaración del Testigo',
            'contenido' => 'Señor Juez, yo estaba comprando en la tienda cuando ocurrió el robo. Vi a un hombre salir corriendo con una bolsa en la mano.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 100, 'y' => 800]
        ]);

        // Diálogo de identificación
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Identificación del Sospechoso',
            'contenido' => 'Cuando la policía me mostró las fotos, identifiqué al acusado como la persona que vi salir corriendo. Estoy bastante seguro de que es él.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 300, 'y' => 800]
        ]);

        // Diálogo de detalles adicionales
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Detalles Adicionales',
            'contenido' => 'El hombre llevaba una gorra azul y una camiseta blanca. Corrió hacia la calle principal y se subió a un taxi que lo esperaba.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 500, 'y' => 800]
        ]);

        return $dialogos;
    }

    private function crearDialogosPerito($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // Diálogo inicial
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Informe Forense',
            'contenido' => 'Señor Juez, como perito forense, analicé la evidencia del caso. El cuchillo encontrado tiene huellas dactilares que coinciden con el acusado.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 100, 'y' => 950]
        ]);

        // Diálogo de análisis de heridas
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Análisis de Heridas',
            'contenido' => 'Las heridas del cajero son consistentes con un corte producido por un cuchillo de cocina. La profundidad indica que fue hecho con fuerza considerable.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 300, 'y' => 950]
        ]);

        // Diálogo de evidencia de ADN
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Evidencia de ADN',
            'contenido' => 'Encontramos restos de sangre en el cuchillo que coinciden con el ADN del cajero. También hay huellas dactilares del acusado en el mango.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 500, 'y' => 950]
        ]);

        return $dialogos;
    }

    private function crearDialogosPolicia($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // Diálogo inicial
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Informe Policial',
            'contenido' => 'Señor Juez, como policía investigador, llegué al lugar 15 minutos después del robo. El cajero estaba herido y nos dio la descripción del agresor.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 100, 'y' => 1100]
        ]);

        // Diálogo de investigación
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Proceso de Investigación',
            'contenido' => 'Basándome en la descripción, busqué en la base de datos y encontré al acusado que tenía antecedentes similares. Lo detuve en su domicilio.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 300, 'y' => 1100]
        ]);

        // Diálogo de evidencia encontrada
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Evidencia Encontrada',
            'contenido' => 'En el domicilio del acusado encontramos ropa similar a la descrita por el cajero y el testigo. También encontramos el cuchillo usado en el robo.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 500, 'y' => 1100]
        ]);

        return $dialogos;
    }

    private function crearDialogosSecretario($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // Diálogo inicial
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Registro de la Audiencia',
            'contenido' => 'Se registra la apertura de la audiencia del caso penal 1234/2024. Presentes: Juez, Fiscal, Defensor, Acusado y todas las partes.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 100, 'y' => 1250]
        ]);

        // Diálogo de registro de declaraciones
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Registro de Declaraciones',
            'contenido' => 'Se registran las declaraciones del cajero víctima, testigo ocular, perito forense y policía investigador. Todas las declaraciones quedan asentadas.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 300, 'y' => 1250]
        ]);

        return $dialogos;
    }

    private function crearDialogosPublico($flujo)
    {
        $dialogos = [];
        $orden = 0;

        // Diálogo inicial
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Reacción del Público',
            'contenido' => 'El público presente en la audiencia murmura cuando se presentan las pruebas. Algunos muestran apoyo al cajero víctima, otros dudan de la culpabilidad del acusado.',
            'tipo' => 'automatico',
            'es_inicial' => true,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 100, 'y' => 1400]
        ]);

        // Diálogo de presión social
        $dialogos[] = PanelDialogoDialogo::create([
            'flujo_id' => $flujo->id,
            'titulo' => 'Presión Social',
            'contenido' => 'Los familiares del cajero piden justicia, mientras que los del acusado claman por su inocencia. El ambiente en la sala es tenso.',
            'tipo' => 'automatico',
            'es_inicial' => false,
            'es_final' => false,
            'orden' => $orden++,
            'metadata' => ['x' => 300, 'y' => 1400]
        ]);

        return $dialogos;
    }

    private function crearOpciones($dialogos)
    {
        $opciones = [];
        
        // Crear opciones para diálogos de decisión
        foreach ($dialogos as $rol => $dialogosRol) {
            foreach ($dialogosRol as $dialogo) {
                if ($dialogo->tipo === 'decision') {
                    $opcionesDialogo = $this->crearOpcionesParaDialogo($dialogo);
                    $opciones = array_merge($opciones, $opcionesDialogo);
                }
            }
        }

        return $opciones;
    }

    private function crearOpcionesParaDialogo($dialogo)
    {
        $opciones = [];
        
        // Opciones genéricas para diálogos de decisión
        $opcionesData = [
            [
                'texto' => 'Opción A',
                'descripcion' => 'Primera opción disponible',
                'orden' => 0,
                'color' => '#28a745',
                'puntuacion_impacto' => 1
            ],
            [
                'texto' => 'Opción B', 
                'descripcion' => 'Segunda opción disponible',
                'orden' => 1,
                'color' => '#ffc107',
                'puntuacion_impacto' => 2
            ],
            [
                'texto' => 'Opción C',
                'descripcion' => 'Tercera opción disponible', 
                'orden' => 2,
                'color' => '#fd7e14',
                'puntuacion_impacto' => 3
            ]
        ];

        foreach ($opcionesData as $opcionData) {
            $opcion = PanelDialogoOpcion::create([
                'dialogo_id' => $dialogo->id,
                'texto' => $opcionData['texto'],
                'descripcion' => $opcionData['descripcion'],
                'orden' => $opcionData['orden'],
                'color' => $opcionData['color'],
                'puntuacion_impacto' => $opcionData['puntuacion_impacto'],
                'consecuencias' => ['impacto' => $opcionData['puntuacion_impacto']]
            ]);
            $opciones[] = $opcion;
        }

        return $opciones;
    }

    private function crearConexiones($escenario, $dialogos, $opciones)
    {
        // Crear conexiones básicas entre diálogos
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
                    'metadata' => ['conexion_automatica' => true]
                ]);
            }
        }
    }
}
