<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoginAttempt extends Model
{
    use HasFactory;

    protected $table = 'login_attempts';

    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'successful',
        'attempted_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    /**
     * Verificar si una IP está bloqueada
     */
    public static function isIpBlocked($ip, $maxAttempts = 10, $timeWindow = 15)
    {
        $attempts = self::where('ip_address', $ip)
            ->where('successful', false)
            ->where('attempted_at', '>=', now()->subMinutes($timeWindow))
            ->count();

        return $attempts >= $maxAttempts;
    }

    /**
     * Verificar si un email está bloqueado
     */
    public static function isEmailBlocked($email, $maxAttempts = 5, $timeWindow = 15)
    {
        $attempts = self::where('email', $email)
            ->where('successful', false)
            ->where('attempted_at', '>=', now()->subMinutes($timeWindow))
            ->count();

        return $attempts >= $maxAttempts;
    }

    /**
     * Registrar un intento de login
     */
    public static function recordAttempt($email, $ip, $userAgent, $successful = false)
    {
        return self::create([
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'successful' => $successful,
            'attempted_at' => now(),
        ]);
    }

    /**
     * Limpiar intentos antiguos
     */
    public static function cleanupOldAttempts($days = 30)
    {
        return self::where('attempted_at', '<', now()->subDays($days))->delete();
    }
}
