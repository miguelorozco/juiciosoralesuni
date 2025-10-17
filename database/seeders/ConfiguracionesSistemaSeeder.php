<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracionesSistemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configuraciones = [
            [
                'clave' => 'max_participantes_sesion',
                'valor' => '20',
                'descripcion' => 'Máximo número de participantes por sesión',
                'tipo' => 'number'
            ],
            [
                'clave' => 'calidad_audio_default',
                'valor' => 'normal',
                'descripcion' => 'Calidad de audio por defecto',
                'tipo' => 'string'
            ],
            [
                'clave' => 'tiempo_maximo_grabacion',
                'valor' => '300',
                'descripcion' => 'Tiempo máximo de grabación en segundos',
                'tipo' => 'number'
            ],
            [
                'clave' => 'servicio_transcripcion',
                'valor' => 'google',
                'descripcion' => 'Servicio de transcripción por defecto',
                'tipo' => 'string'
            ],
            [
                'clave' => 'unity_room_prefix',
                'valor' => 'Sala_',
                'descripcion' => 'Prefijo para IDs de salas de Unity',
                'tipo' => 'string'
            ],
            [
                'clave' => 'jwt_ttl',
                'valor' => '1440',
                'descripcion' => 'Tiempo de vida del token JWT en minutos',
                'tipo' => 'number'
            ],
            [
                'clave' => 'jwt_refresh_ttl',
                'valor' => '20160',
                'descripcion' => 'Tiempo de vida del refresh token en minutos',
                'tipo' => 'number'
            ],
            [
                'clave' => 'peerjs_server',
                'valor' => 'juiciosorales.site',
                'descripcion' => 'Servidor PeerJS por defecto',
                'tipo' => 'string'
            ],
            [
                'clave' => 'photon_app_id',
                'valor' => '2ec23c58-5cc4-419d-8214-13abad14a02f',
                'descripcion' => 'ID de aplicación Photon PUN2',
                'tipo' => 'string'
            ],
            [
                'clave' => 'photon_region',
                'valor' => 'us',
                'descripcion' => 'Región de Photon PUN2',
                'tipo' => 'string'
            ]
        ];

        foreach ($configuraciones as $config) {
            DB::table('configuraciones_sistema')->insert(array_merge($config, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}