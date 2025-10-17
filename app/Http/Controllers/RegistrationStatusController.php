<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RegistrationStatusController extends Controller
{
    /**
     * Get the current registration status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus()
    {
        $isEnabled = config('app.allow_new_user', true);
        
        return response()->json([
            'success' => true,
            'data' => [
                'registration_enabled' => $isEnabled,
                'message' => $isEnabled 
                    ? 'El registro de nuevos usuarios está habilitado' 
                    : 'El registro de nuevos usuarios no está habilitado'
            ]
        ]);
    }
}
