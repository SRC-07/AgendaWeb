<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Tarea;

class ChatController extends Controller
{
    public function ask(Request $request)
    {
        $request->validate(['pregunta' => 'required|string']);

        $tareas = Auth::user()->tareas()->where('estado', '!=', 'completada')->get();
        $contexto = "Eres un asistente de agenda. Si el usuario pide crear una tarea, responde ÚNICAMENTE con este JSON: {\"accion\": \"crear_tarea\", \"titulo\": \"nombre\", \"prioridad\": \"alta/media/baja\"}. Si no, responde normal. Tareas actuales: ";
        foreach ($tareas as $tarea) { $contexto .= "- " . $tarea->titulo . ". "; }

        try {
            $response = Http::timeout(60)->post(env('OLLAMA_URL'), [
                'model' => 'llama3',
                'prompt' => $contexto . " Usuario dice: " . $request->pregunta,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $textoIA = $response->json()['response'];
                $data = json_decode($textoIA, true);

                if (is_array($data) && isset($data['accion']) && $data['accion'] === 'crear_tarea') {
                    $nuevaTarea = Tarea::create([
                        'user_id' => Auth::id(),
                        'titulo' => $data['titulo'],
                        'prioridad' => $data['prioridad'] ?? 'media',
                        'estado' => 'pendiente',
                        'fecha_vencimiento' => now()->addDay()
                    ]);

                    return response()->json([
                        'respuesta' => "¡Listo! He anotado '" . $nuevaTarea->titulo . "' en tu agenda.",
                        'nueva_tarea' => [
                            'id' => $nuevaTarea->id,
                            'titulo' => $nuevaTarea->titulo,
                            'prioridad' => $nuevaTarea->prioridad,
                            'estado' => 'pendiente',
                            'fecha' => $nuevaTarea->fecha_vencimiento->format('d/m/Y'),
                            'url_edit' => route('tareas.edit', $nuevaTarea->id)
                        ]
                    ]);
                }

                return response()->json(['respuesta' => $textoIA]);
            }
            return response()->json(['respuesta' => 'Error en Ollama'], 500);
        } catch (\Exception $e) {
            return response()->json(['respuesta' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}