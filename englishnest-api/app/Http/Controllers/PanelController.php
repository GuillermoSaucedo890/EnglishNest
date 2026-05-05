<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class PanelController extends Controller
{
    public function index()
    {
        $usuario = Auth::user();

        return view('panel.index', compact('usuario'));
    }
}