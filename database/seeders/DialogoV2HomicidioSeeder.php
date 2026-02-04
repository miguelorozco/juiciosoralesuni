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
 * Seeder del caso "Homicidio en Bar La Herradura" para diálogos v2.
 * 30+ nodos con decisiones de 3 opciones, usando únicamente los 20 roles existentes.
 */
class DialogoV2HomicidioSeeder extends Seeder
{
    private array $rolIds = [];
    private ?DialogoV2 $dialogo = null;
    private array $nodos = [];

    public function run(): void
    {
        DB::transaction(function () {
            $this->cargarRoles();
            $this->crearDialogo();
            $this->crearNodos();
            $this->crearConexiones();
        });

        $this->command->info("✅ Diálogo 'Homicidio en Bar La Herradura' creado con " . count($this->nodos) . " nodos.");
    }

    private function cargarRoles(): void
    {
        // Usar SOLO los 20 roles existentes en la base de datos
        $rolesExistentes = [
            'Juez', 'Fiscal', 'Defensa', 'Testigo1', 'Testigo2',
            'Policía1', 'Policía2', 'Psicólogo', 'Acusado', 'Secretario',
            'Abogado1', 'Abogado2', 'Perito1', 'Perito2', 'Víctima',
            'Acusador', 'Periodista', 'Público1', 'Público2', 'Observador'
        ];

        foreach ($rolesExistentes as $nombre) {
            $rol = RolDisponible::where('nombre', $nombre)->first();
            if ($rol) {
                $this->rolIds[$nombre] = $rol->id;
            }
        }

        if (count($this->rolIds) < 20) {
            $this->command->warn("⚠️ Solo se encontraron " . count($this->rolIds) . " roles de 20.");
        }
    }

    private function crearDialogo(): void
    {
        $admin = User::where('tipo', 'admin')->first();

        $this->dialogo = DialogoV2::create([
            'nombre' => 'Homicidio en Bar La Herradura',
            'descripcion' => 'Juicio oral por homicidio doloso ocurrido en un bar. Caso complejo con múltiples testigos, evidencia forense y decisiones estratégicas.',
            'creado_por' => $admin?->id,
            'publico' => true,
            'estado' => 'activo',
            'version' => '1.0.0',
            'configuracion' => [
                'tipo' => 'penal',
                'complejidad' => 'alta',
                'duracion_estimada' => '90 minutos',
                'delito' => 'Homicidio doloso',
            ],
            'metadata_unity' => [
                'scene' => 'sala_audiencias_penal',
                'ambiente' => 'formal',
            ],
        ]);
    }

    private function crearNodos(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // FILA 1: INICIO (x=100)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['inicio'] = $this->crearNodo(
            'Juez', 'inicio', 'Apertura del Juicio Oral',
            'El Juez declara abierta la audiencia de juicio oral por el delito de homicidio doloso. Se verifica la presencia de las partes y se informa al acusado de sus derechos constitucionales.',
            100, 300, true, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 2: LECTURA Y FORMALIDADES (x=350)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['lectura_acusacion'] = $this->crearNodo(
            'Secretario', 'desarrollo', 'Lectura de la Acusación',
            'El Secretario da lectura a la acusación formal: El día 15 de marzo, aproximadamente a las 23:45 horas, en el Bar "La Herradura", el acusado Juan Carlos Méndez privó de la vida a Roberto Sánchez Vega mediante múltiples heridas punzocortantes.',
            350, 300, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 3: ALEGATOS DE APERTURA (x=600)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['alegato_fiscal'] = $this->crearNodo(
            'Fiscal', 'desarrollo', 'Alegato de Apertura - Fiscalía',
            'El Ministerio Público presenta su teoría del caso: homicidio premeditado por celos. Anuncia pruebas testimoniales, periciales y videos de seguridad que demostrarán la culpabilidad más allá de duda razonable.',
            600, 200, false, false
        );

        $this->nodos['alegato_defensa'] = $this->crearNodo(
            'Defensa', 'desarrollo', 'Alegato de Apertura - Defensa',
            'La defensa plantea legítima defensa: el acusado fue atacado primero con una botella rota. Presenta su versión de los hechos y anuncia testimonios que corroboran la agresión inicial de la víctima.',
            600, 400, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 4: PRIMERA DECISIÓN ESTRATÉGICA (x=850)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['decision_orden_pruebas'] = $this->crearNodo(
            'Juez', 'decision', 'Orden de Desahogo de Pruebas',
            'El Juez solicita a las partes acordar el orden del desahogo probatorio. ¿Cómo proceder con la presentación de evidencia?',
            850, 300, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 5: RAMAS DE PRUEBAS (x=1100)
        // ═══════════════════════════════════════════════════════════════
        // Rama A: Testimoniales primero
        $this->nodos['testigo_presencial'] = $this->crearNodo(
            'Testigo1', 'desarrollo', 'Testimonio del Mesero',
            'El mesero del bar declara: "Vi cuando el acusado se acercó a la víctima. Discutían por una mujer. El acusado sacó una navaja y lo apuñaló varias veces. La víctima no tenía nada en las manos."',
            1100, 100, false, false
        );

        // Rama B: Periciales primero
        $this->nodos['perito_forense'] = $this->crearNodo(
            'Perito1', 'desarrollo', 'Dictamen Médico Forense',
            'El perito médico presenta: La víctima presentó 7 heridas punzocortantes, 3 de ellas mortales en región torácica. La trayectoria de las heridas indica que el agresor era diestro y atacó de frente.',
            1100, 300, false, false
        );

        // Rama C: Documentales primero
        $this->nodos['video_seguridad'] = $this->crearNodo(
            'Policía1', 'desarrollo', 'Análisis del Video de Seguridad',
            'El oficial presenta el video de las cámaras del bar. Se observa el altercado verbal, un empujón inicial, y el momento en que el acusado extrae un objeto del bolsillo. La calidad del video no permite ver claramente si la víctima atacó primero.',
            1100, 500, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 6: CONTRAINTERROGATORIOS (x=1350)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['contra_testigo'] = $this->crearNodo(
            'Defensa', 'desarrollo', 'Contrainterrogatorio al Mesero',
            'La defensa cuestiona al testigo: "¿A qué distancia se encontraba? ¿Cuántas cervezas había servido esa noche? ¿No es cierto que la víctima era conocido por ser conflictivo?"',
            1350, 100, false, false
        );

        $this->nodos['contra_perito'] = $this->crearNodo(
            'Defensa', 'desarrollo', 'Contrainterrogatorio al Perito',
            'La defensa interroga: "Doctor, ¿las heridas son consistentes con un ataque defensivo? ¿Encontró lesiones de defensa en la víctima? ¿Había alcohol en su sangre?"',
            1350, 300, false, false
        );

        $this->nodos['contra_video'] = $this->crearNodo(
            'Defensa', 'desarrollo', 'Impugnación del Video',
            'La defensa señala: "El video tiene baja resolución y ángulo limitado. No se aprecia claramente quién inició la agresión física. Solicitamos no darle valor probatorio pleno."',
            1350, 500, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 7: SEGUNDA DECISIÓN - TESTIMONIOS ADICIONALES (x=1600)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['decision_testimonios'] = $this->crearNodo(
            'Fiscal', 'decision', 'Estrategia de Testimonios Adicionales',
            'El Fiscal debe decidir qué testigos adicionales presentar para fortalecer su teoría del caso.',
            1600, 300, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 8: TESTIMONIOS OPCIONALES (x=1850)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['testigo_novia'] = $this->crearNodo(
            'Testigo2', 'desarrollo', 'Testimonio de la Ex-novia',
            'María Elena, ex-novia del acusado, declara: "Juan Carlos me acosaba. Cuando empecé a salir con Roberto, me amenazó diciendo que lo iba a matar. Estaba obsesionado."',
            1850, 100, false, false
        );

        $this->nodos['testigo_amigo'] = $this->crearNodo(
            'Público1', 'desarrollo', 'Testimonio del Amigo de la Víctima',
            'Pedro, amigo de Roberto, declara: "Roberto no era violento. Esa noche solo quería hablar con Juan Carlos para que dejara en paz a María Elena. No llevaba armas."',
            1850, 300, false, false
        );

        $this->nodos['policia_arresto'] = $this->crearNodo(
            'Policía2', 'desarrollo', 'Testimonio del Oficial de Arresto',
            'El oficial que arrestó al acusado declara: "Lo encontramos a dos calles del bar, con sangre en la ropa. Dijo: Ya valió, lo maté. Se le aseguró una navaja ensangrentada."',
            1850, 500, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 9: PRUEBAS DE LA DEFENSA (x=2100)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['decision_defensa'] = $this->crearNodo(
            'Defensa', 'decision', 'Estrategia Defensiva',
            'La defensa debe elegir su línea principal de argumentación para el contraataque.',
            2100, 300, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 10: LÍNEAS DEFENSIVAS (x=2350)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['legitima_defensa'] = $this->crearNodo(
            'Acusado', 'desarrollo', 'Declaración del Acusado - Legítima Defensa',
            'El acusado declara: "Roberto me atacó primero con una botella rota. Me cortó el brazo. Saqué la navaja que siempre cargo para defenderme. Solo quería que dejara de golpearme."',
            2350, 100, false, false
        );

        $this->nodos['perito_psicologia'] = $this->crearNodo(
            'Psicólogo', 'desarrollo', 'Dictamen Psicológico',
            'El perito psicólogo de la defensa presenta: "El acusado presenta rasgos de ansiedad y reacción de pánico. Su perfil no es consistente con un agresor premeditado sino con respuesta defensiva extrema."',
            2350, 300, false, false
        );

        $this->nodos['testigo_defensa'] = $this->crearNodo(
            'Público2', 'desarrollo', 'Testigo de Descargo',
            'Un cliente del bar declara: "Vi que Roberto empujó primero al acusado y le rompió una botella en la cabeza. El otro tipo solo se defendió. Roberto siempre buscaba pleito."',
            2350, 500, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 11: RÉPLICAS (x=2600)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['replica_fiscal'] = $this->crearNodo(
            'Fiscal', 'desarrollo', 'Réplica de la Fiscalía',
            'El Fiscal contraargumenta: "La supuesta herida de botella no aparece en el certificado médico del acusado. El video no muestra ninguna botella. Es una fabricación para justificar el asesinato."',
            2600, 200, false, false
        );

        $this->nodos['contrarreplica_defensa'] = $this->crearNodo(
            'Abogado1', 'desarrollo', 'Contrarréplica de la Defensa',
            'El co-defensor argumenta: "El certificado médico se realizó horas después, la herida superficial ya había sanado. El ángulo del video tiene punto ciego. Existe duda razonable."',
            2600, 400, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 12: PERICIAL ADICIONAL (x=2850)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['perito_criminalistica'] = $this->crearNodo(
            'Perito2', 'desarrollo', 'Dictamen Criminalístico',
            'El perito en criminalística presenta reconstrucción de hechos: Las manchas de sangre y posición del cuerpo sugieren que la víctima estaba de pie y de frente al momento del primer ataque.',
            2850, 300, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 13: DECISIÓN CRUCIAL (x=3100)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['decision_juez_prueba'] = $this->crearNodo(
            'Juez', 'decision', 'Valoración de Prueba Superveniente',
            'Se presenta un nuevo testigo que afirma tener video desde otro ángulo. El Juez debe decidir sobre su admisión.',
            3100, 300, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 14: RESULTADO DE DECISIÓN (x=3350)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['video_admitido'] = $this->crearNodo(
            'Observador', 'desarrollo', 'Nuevo Video Admitido',
            'El nuevo video del celular de un cliente muestra claramente que Roberto sacó una botella primero y atacó al acusado. El Fiscal solicita receso para evaluar el impacto.',
            3350, 100, false, false
        );

        $this->nodos['video_rechazado'] = $this->crearNodo(
            'Abogado2', 'desarrollo', 'Prueba Rechazada - Continuación',
            'El Juez rechaza el video por extemporáneo. La defensa protesta y hace constar la violación procesal para futuro amparo.',
            3350, 300, false, false
        );

        $this->nodos['video_diferido'] = $this->crearNodo(
            'Secretario', 'desarrollo', 'Decisión Diferida',
            'El Juez ordena un receso para analizar la autenticidad del video y su cadena de custodia antes de decidir.',
            3350, 500, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 15: ALEGATOS DE CLAUSURA (x=3600)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['alegato_clausura_fiscal'] = $this->crearNodo(
            'Fiscal', 'desarrollo', 'Alegato de Clausura - Fiscalía',
            'El Fiscal concluye: "Se ha demostrado con pruebas contundentes que el acusado, movido por celos enfermizos, privó de la vida a Roberto Sánchez. Solicitamos condena por homicidio calificado."',
            3600, 200, false, false
        );

        $this->nodos['alegato_clausura_defensa'] = $this->crearNodo(
            'Defensa', 'desarrollo', 'Alegato de Clausura - Defensa',
            'La defensa concluye: "Existe duda razonable. Mi cliente actuó en legítima defensa ante una agresión. No hay prueba plena de premeditación. Solicitamos absolución o en subsidio, homicidio simple."',
            3600, 400, false, false
        );

        // ═══════════════════════════════════════════════════════════════
        // FILA 16: DELIBERACIÓN Y FINALES (x=3850-4100)
        // ═══════════════════════════════════════════════════════════════
        $this->nodos['deliberacion'] = $this->crearNodo(
            'Juez', 'decision', 'Deliberación Final',
            'El Juez analiza todas las pruebas y testimonios presentados. Debe emitir sentencia considerando los elementos del tipo penal y las causas de justificación alegadas.',
            3850, 300, false, false
        );

        // FINALES
        $this->nodos['final_culpable_calificado'] = $this->crearNodo(
            'Juez', 'final', 'Sentencia: Culpable de Homicidio Calificado',
            'SENTENCIA: Se declara CULPABLE a Juan Carlos Méndez del delito de HOMICIDIO CALIFICADO con las agravantes de premeditación y ventaja. Se impone pena de 35 años de prisión.',
            4100, 50, false, true
        );

        $this->nodos['final_culpable_simple'] = $this->crearNodo(
            'Juez', 'final', 'Sentencia: Culpable de Homicidio Simple',
            'SENTENCIA: Se declara CULPABLE a Juan Carlos Méndez del delito de HOMICIDIO SIMPLE al no acreditarse la premeditación. Se impone pena de 15 años de prisión.',
            4100, 250, false, true
        );

        $this->nodos['final_absuelto'] = $this->crearNodo(
            'Juez', 'final', 'Sentencia: Absolución por Legítima Defensa',
            'SENTENCIA: Se ABSUELVE a Juan Carlos Méndez al acreditarse la causa de justificación de LEGÍTIMA DEFENSA. Se ordena su inmediata libertad.',
            4100, 450, false, true
        );

        // Nodo adicional para periodista (cobertura)
        $this->nodos['cobertura_medios'] = $this->crearNodo(
            'Periodista', 'desarrollo', 'Cobertura Mediática',
            'El periodista reporta: "Juicio controversial divide opiniones. Familiares de ambas partes presentes. El caso ha generado debate sobre legítima defensa y violencia en establecimientos."',
            2850, 550, false, false
        );

        // Nodo de víctima (familiar)
        $this->nodos['declaracion_familiar'] = $this->crearNodo(
            'Víctima', 'desarrollo', 'Declaración de la Madre de la Víctima',
            'La madre de Roberto declara como víctima indirecta: "Mi hijo no era perfecto, pero no merecía morir. Esa persona le quitó la vida a sangre fría. Exijo justicia."',
            2100, 550, false, false
        );

        // Acusador coadyuvante
        $this->nodos['acusador_coadyuvante'] = $this->crearNodo(
            'Acusador', 'desarrollo', 'Intervención del Acusador Coadyuvante',
            'El abogado de la familia de la víctima interviene: "Nos constituimos como acusadores coadyuvantes. Aportamos prueba documental de amenazas previas vía mensajes de texto."',
            1600, 550, false, false
        );
    }

    private function crearNodo(
        string $rol,
        string $tipo,
        string $titulo,
        string $contenido,
        int $x,
        int $y,
        bool $esInicial,
        bool $esFinal
    ): NodoDialogoV2 {
        return NodoDialogoV2::create([
            'dialogo_id' => $this->dialogo->id,
            'rol_id' => $this->rolIds[$rol] ?? null,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'contenido' => $contenido,
            'menu_text' => mb_substr($titulo, 0, 30, 'UTF-8'),
            'posicion_x' => $x,
            'posicion_y' => $y,
            'es_inicial' => $esInicial,
            'es_final' => $esFinal,
            'instrucciones' => "Nodo de tipo {$tipo} para el rol {$rol}.",
            'activo' => true,
            'condiciones' => [],
            'consecuencias' => [],
        ]);
    }

    private function crearConexiones(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // CONEXIONES LINEALES INICIALES
        // ═══════════════════════════════════════════════════════════════
        $this->conectar('inicio', 'lectura_acusacion', 'Proceder con lectura de acusación');
        $this->conectar('lectura_acusacion', 'alegato_fiscal', 'Dar palabra a la Fiscalía');
        $this->conectar('alegato_fiscal', 'alegato_defensa', 'Turno de la Defensa');
        $this->conectar('alegato_defensa', 'decision_orden_pruebas', 'Iniciar desahogo de pruebas');

        // ═══════════════════════════════════════════════════════════════
        // PRIMERA DECISIÓN: ORDEN DE PRUEBAS (3 opciones)
        // ═══════════════════════════════════════════════════════════════
        $this->conectar('decision_orden_pruebas', 'testigo_presencial', 'Iniciar con testimoniales', 1, '#32CD32');
        $this->conectar('decision_orden_pruebas', 'perito_forense', 'Iniciar con periciales', 2, '#9370DB');
        $this->conectar('decision_orden_pruebas', 'video_seguridad', 'Iniciar con documentales', 3, '#000080');

        // Contrainterrogatorios
        $this->conectar('testigo_presencial', 'contra_testigo', 'Contrainterrogar al testigo');
        $this->conectar('perito_forense', 'contra_perito', 'Contrainterrogar al perito');
        $this->conectar('video_seguridad', 'contra_video', 'Impugnar el video');

        // Convergencia hacia segunda decisión
        $this->conectar('contra_testigo', 'decision_testimonios', 'Continuar con más testigos');
        $this->conectar('contra_perito', 'decision_testimonios', 'Continuar con testimonios');
        $this->conectar('contra_video', 'decision_testimonios', 'Presentar testimonios');

        // ═══════════════════════════════════════════════════════════════
        // SEGUNDA DECISIÓN: TESTIMONIOS ADICIONALES (3 opciones)
        // ═══════════════════════════════════════════════════════════════
        $this->conectar('decision_testimonios', 'testigo_novia', 'Presentar a la ex-novia', 1, '#FF1493');
        $this->conectar('decision_testimonios', 'testigo_amigo', 'Presentar al amigo de la víctima', 2, '#808080');
        $this->conectar('decision_testimonios', 'policia_arresto', 'Presentar al oficial de arresto', 3, '#000080');

        // Desde acusador coadyuvante
        $this->conectar('decision_testimonios', 'acusador_coadyuvante', 'Permitir acusador coadyuvante', 4, '#DC143C');
        $this->conectar('acusador_coadyuvante', 'declaracion_familiar', 'Escuchar a familiares');

        // Convergencia hacia decisión de defensa
        $this->conectar('testigo_novia', 'decision_defensa', 'Turno de la defensa');
        $this->conectar('testigo_amigo', 'decision_defensa', 'Turno de la defensa');
        $this->conectar('policia_arresto', 'decision_defensa', 'Turno de la defensa');
        $this->conectar('declaracion_familiar', 'decision_defensa', 'Turno de la defensa');

        // ═══════════════════════════════════════════════════════════════
        // TERCERA DECISIÓN: ESTRATEGIA DEFENSIVA (3 opciones)
        // ═══════════════════════════════════════════════════════════════
        $this->conectar('decision_defensa', 'legitima_defensa', 'Argumentar legítima defensa', 1, '#FF6347');
        $this->conectar('decision_defensa', 'perito_psicologia', 'Presentar dictamen psicológico', 2, '#9370DB');
        $this->conectar('decision_defensa', 'testigo_defensa', 'Presentar testigo de descargo', 3, '#808080');

        // Réplicas
        $this->conectar('legitima_defensa', 'replica_fiscal', 'Réplica del fiscal');
        $this->conectar('perito_psicologia', 'replica_fiscal', 'Réplica del fiscal');
        $this->conectar('testigo_defensa', 'replica_fiscal', 'Réplica del fiscal');

        $this->conectar('replica_fiscal', 'contrarreplica_defensa', 'Contrarréplica');
        $this->conectar('contrarreplica_defensa', 'perito_criminalistica', 'Prueba pericial adicional');
        
        // Cobertura mediática (rama secundaria)
        $this->conectar('perito_criminalistica', 'cobertura_medios', 'Observar reacción mediática');
        $this->conectar('cobertura_medios', 'decision_juez_prueba', 'Continuar con el juicio');
        
        $this->conectar('perito_criminalistica', 'decision_juez_prueba', 'Aparece nuevo testigo');

        // ═══════════════════════════════════════════════════════════════
        // CUARTA DECISIÓN: PRUEBA SUPERVENIENTE (3 opciones)
        // ═══════════════════════════════════════════════════════════════
        $this->conectar('decision_juez_prueba', 'video_admitido', 'Admitir el nuevo video', 1, '#32CD32');
        $this->conectar('decision_juez_prueba', 'video_rechazado', 'Rechazar por extemporáneo', 2, '#DC143C');
        $this->conectar('decision_juez_prueba', 'video_diferido', 'Diferir decisión para análisis', 3, '#696969');

        // Hacia alegatos de clausura
        $this->conectar('video_admitido', 'alegato_clausura_defensa', 'Alegatos de clausura');
        $this->conectar('video_rechazado', 'alegato_clausura_fiscal', 'Alegatos de clausura');
        $this->conectar('video_diferido', 'alegato_clausura_fiscal', 'Alegatos de clausura');

        $this->conectar('alegato_clausura_fiscal', 'alegato_clausura_defensa', 'Turno de la defensa');
        $this->conectar('alegato_clausura_defensa', 'deliberacion', 'Pasar a deliberación');

        // ═══════════════════════════════════════════════════════════════
        // DECISIÓN FINAL: SENTENCIA (3 opciones)
        // ═══════════════════════════════════════════════════════════════
        $this->conectar('deliberacion', 'final_culpable_calificado', 'Culpable - Homicidio Calificado', 1, '#DC143C');
        $this->conectar('deliberacion', 'final_culpable_simple', 'Culpable - Homicidio Simple', 2, '#FF8C00');
        $this->conectar('deliberacion', 'final_absuelto', 'Absuelto - Legítima Defensa', 3, '#32CD32');
    }

    private function conectar(
        string $origen,
        string $destino,
        string $texto,
        int $orden = 1,
        ?string $color = null
    ): void {
        if (!isset($this->nodos[$origen]) || !isset($this->nodos[$destino])) {
            return;
        }

        RespuestaDialogoV2::create([
            'nodo_padre_id' => $this->nodos[$origen]->id,
            'nodo_siguiente_id' => $this->nodos[$destino]->id,
            'texto' => $texto,
            'orden' => $orden,
            'puntuacion' => 0,
            'color' => $color ?? '#8B4513',
            'requiere_usuario_registrado' => false,
            'es_opcion_por_defecto' => $orden === 1,
            'requiere_rol' => [],
            'condiciones' => [],
            'consecuencias' => [],
        ]);
    }
}
