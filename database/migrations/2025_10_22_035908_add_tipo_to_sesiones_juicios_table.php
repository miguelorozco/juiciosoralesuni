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
        Schema::table('sesiones_juicios', function (Blueprint $table) {
            $table->enum('tipo', ['civil', 'penal', 'laboral', 'administrativo'])->default('penal')->after('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sesiones_juicios', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
