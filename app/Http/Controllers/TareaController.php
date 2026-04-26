<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TareaController extends Controller
{
    public function index(Request $request)
    {
        $query = Auth::user()->tareas();

        if ($request->filled('buscar')) {
            $query->where('titulo', 'like', '%' . $request->buscar . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        $tareas = $query->orderBy('created_at', 'desc')->get();

        return view('tareas.index', compact('tareas'));
    }

    public function create()
    {
        return view('tareas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'prioridad' => 'required|in:baja,media,alta',
            'fecha_vencimiento' => 'nullable|date',
        ]);

        Auth::user()->tareas()->create($request->all());

        return redirect()->route('tareas.index');
    }

    public function edit(Tarea $tarea)
    {
        if ($tarea->user_id !== Auth::id()) {
            abort(403);
        }
        return view('tareas.edit', compact('tarea'));
    }

    public function update(Request $request, Tarea $tarea)
    {
        if ($tarea->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|in:pendiente,en_progreso,completada',
            'prioridad' => 'required|in:baja,media,alta',
            'fecha_vencimiento' => 'nullable|date',
        ]);

        $tarea->update($request->all());

        return redirect()->route('tareas.index');
    }

    public function destroy(Tarea $tarea)
    {
        if ($tarea->user_id !== Auth::id()) {
            abort(403);
        }
        $tarea->delete();
        return redirect()->route('tareas.index');
    }
}