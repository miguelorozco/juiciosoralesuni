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
        Schema::table('users', function (Blueprint $table) {
            $table->string('apellido', 100)->after('name');
            $table->enum('tipo', ['admin', 'instructor', 'alumno'])->after('apellido');
            $table->boolean('activo')->default(true)->after('tipo');
            $table->foreignId('creado_por')->nullable()->constrained('users')->after('activo');
            $table->timestamp('ultimo_acceso')->nullable()->after('creado_por');
            $table->json('configuracion')->nullable()->after('ultimo_acceso');
            
            $table->index('tipo');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['creado_por']);
            $table->dropIndex(['tipo']);
            $table->dropIndex(['activo']);
            $table->dropColumn([
                'apellido', 
                'tipo', 
                'activo', 
                'creado_por', 
                'ultimo_acceso', 
                'configuracion'
            ]);
        });
    }
};
