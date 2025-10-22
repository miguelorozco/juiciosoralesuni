<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla principal de diálogos (escenarios)
        Schema::create('panel_dialogo_escenarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['civil', 'penal', 'laboral', 'administrativo'])->default('penal');
            $table->enum('estado', ['borrador', 'activo', 'archivado'])->default('borrador');
            $table->boolean('publico')->default(false);
            $table->json('configuracion')->nullable(); // Configuraciones generales del escenario
            $table->foreignId('creado_por')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['estado', 'publico']);
            $table->index('tipo');
        });

        // Tabla de roles disponibles para cada escenario
        Schema::create('panel_dialogo_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escenario_id')->constrained('panel_dialogo_escenarios')->onDelete('cascade');
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->string('color', 7)->default('#007bff'); // Color hexadecimal
            $table->string('icono', 50)->default('bi-person-fill'); // Icono Bootstrap
            $table->boolean('requerido')->default(true); // Si el rol es obligatorio
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0); // Orden de aparición
            $table->json('configuracion')->nullable(); // Configuraciones específicas del rol
            $table->timestamps();
            
            $table->index(['escenario_id', 'activo']);
            $table->index('orden');
        });

        // Tabla de flujos por rol (secuencia de diálogos)
        Schema::create('panel_dialogo_flujos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escenario_id')->constrained('panel_dialogo_escenarios')->onDelete('cascade');
            $table->foreignId('rol_id')->constrained('panel_dialogo_roles')->onDelete('cascade');
            $table->string('nombre', 200); // Nombre del flujo (ej: "Flujo Principal del Juez")
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0); // Orden del flujo dentro del rol
            $table->json('configuracion')->nullable(); // Configuraciones del flujo
            $table->timestamps();
            
            $table->index(['escenario_id', 'rol_id']);
            $table->index('orden');
        });

        // Tabla de diálogos (nodos en el flujo)
        Schema::create('panel_dialogo_dialogos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flujo_id')->constrained('panel_dialogo_flujos')->onDelete('cascade');
            $table->string('titulo', 200);
            $table->text('contenido');
            $table->enum('tipo', ['automatico', 'decision', 'final'])->default('automatico');
            $table->boolean('es_inicial')->default(false); // Si es el primer diálogo del flujo
            $table->boolean('es_final')->default(false); // Si es un diálogo final
            $table->integer('orden')->default(0); // Orden dentro del flujo
            $table->json('posicion')->nullable(); // Posición en el editor (x, y)
            $table->json('configuracion')->nullable(); // Configuraciones específicas del diálogo
            $table->timestamps();
            
            $table->index(['flujo_id', 'orden']);
            $table->index('tipo');
        });

        // Tabla de opciones de respuesta (para diálogos de decisión)
        Schema::create('panel_dialogo_opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dialogo_id')->constrained('panel_dialogo_dialogos')->onDelete('cascade');
            $table->string('texto', 500);
            $table->text('descripcion')->nullable();
            $table->string('letra', 1); // A, B, C, etc.
            $table->string('color', 7)->default('#007bff');
            $table->integer('puntuacion')->default(0);
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0); // Orden de aparición
            $table->json('configuracion')->nullable(); // Configuraciones específicas de la opción
            $table->timestamps();
            
            $table->index(['dialogo_id', 'orden']);
            $table->index('letra');
        });

        // Tabla de conexiones entre diálogos (ramificaciones)
        Schema::create('panel_dialogo_conexiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escenario_id')->constrained('panel_dialogo_escenarios')->onDelete('cascade');
            $table->foreignId('dialogo_origen_id')->constrained('panel_dialogo_dialogos')->onDelete('cascade');
            $table->foreignId('dialogo_destino_id')->constrained('panel_dialogo_dialogos')->onDelete('cascade');
            $table->foreignId('opcion_id')->nullable()->constrained('panel_dialogo_opciones')->onDelete('cascade');
            $table->string('tipo', 50)->default('directa'); // directa, condicional, aleatoria
            $table->json('condiciones')->nullable(); // Condiciones para que se active la conexión
            $table->json('consecuencias')->nullable(); // Consecuencias de tomar esta conexión
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index(['escenario_id', 'dialogo_origen_id']);
            $table->index(['escenario_id', 'dialogo_destino_id']);
            $table->index('tipo');
        });

        // Tabla de sesiones de diálogo (instancias de ejecución)
        Schema::create('panel_dialogo_sesiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escenario_id')->constrained('panel_dialogo_escenarios')->onDelete('cascade');
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['programada', 'iniciada', 'en_curso', 'pausada', 'finalizada', 'cancelada'])->default('programada');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->datetime('fecha_inicio')->nullable();
            $table->datetime('fecha_fin')->nullable();
            $table->json('configuracion')->nullable(); // Configuraciones de la sesión
            $table->json('estado_actual')->nullable(); // Estado actual de la sesión
            $table->timestamps();
            
            $table->index(['escenario_id', 'estado']);
            $table->index('instructor_id');
        });

        // Tabla de asignaciones de roles en sesiones
        Schema::create('panel_dialogo_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->constrained('panel_dialogo_sesiones')->onDelete('cascade');
            $table->foreignId('rol_id')->constrained('panel_dialogo_roles')->onDelete('cascade');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('tipo_asignacion', ['manual', 'automatica'])->default('manual');
            $table->foreignId('asignado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('activo')->default(true);
            $table->json('configuracion')->nullable(); // Configuraciones específicas de la asignación
            $table->timestamps();
            
            $table->index(['sesion_id', 'rol_id']);
            $table->index('usuario_id');
        });

        // Tabla de decisiones tomadas en sesiones
        Schema::create('panel_dialogo_decisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->constrained('panel_dialogo_sesiones')->onDelete('cascade');
            $table->foreignId('dialogo_id')->constrained('panel_dialogo_dialogos')->onDelete('cascade');
            $table->foreignId('opcion_id')->nullable()->constrained('panel_dialogo_opciones')->onDelete('set null');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('rol_id')->constrained('panel_dialogo_roles')->onDelete('cascade');
            $table->text('decision_texto')->nullable(); // Texto de la decisión tomada
            $table->json('consecuencias')->nullable(); // Consecuencias de la decisión
            $table->datetime('fecha_decision');
            $table->timestamps();
            
            $table->index(['sesion_id', 'dialogo_id']);
            $table->index('usuario_id');
            $table->index('fecha_decision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panel_dialogo_decisiones');
        Schema::dropIfExists('panel_dialogo_asignaciones');
        Schema::dropIfExists('panel_dialogo_sesiones');
        Schema::dropIfExists('panel_dialogo_conexiones');
        Schema::dropIfExists('panel_dialogo_opciones');
        Schema::dropIfExists('panel_dialogo_dialogos');
        Schema::dropIfExists('panel_dialogo_flujos');
        Schema::dropIfExists('panel_dialogo_roles');
        Schema::dropIfExists('panel_dialogo_escenarios');
    }
};