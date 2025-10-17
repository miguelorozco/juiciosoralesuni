<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SesionJuicio;

class SesionController extends Controller
{
    public function index()
    {
        return view('sesiones.index');
    }
    
    public function create()
    {
        return view('sesiones.create');
    }
    
    public function show(SesionJuicio $sesion)
    {
        return view('sesiones.activa', compact('sesion'));
    }
    
    public function edit(SesionJuicio $sesion)
    {
        return view('sesiones.edit', compact('sesion'));
    }
}
