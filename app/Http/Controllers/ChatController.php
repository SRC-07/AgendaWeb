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

        $hoy = now()->format('Y-m-d H:i');

        $contexto = "Eres un asistente de agenda muy estricto. Fecha y hora actual: {$hoy}. \n";
        $contexto .= "REGLAS OBLIGATORIAS:\n";
        $contexto .= "1. TEMA ÚNICO: Solo respondes sobre gestión de tareas de la agenda. Si te preguntan sobre programación, historia, chistes o cualquier otra cosa, responde EXACTAMENTE: 'Lo siento, como asistente de la agenda web, solo puedo ayudarte a gestionar tus tareas.'\n";
        $contexto .= "2. CREAR: Para crear una tarea necesitas OBLIGATORIAMENTE 4 datos: Título, Descripción, Fecha/Hora exacta, y Prioridad. Si el usuario NO te da los 4 datos en su mensaje, NO crees el JSON, compórtate como humano y PREGÚNTALE los datos que faltan. NUNCA inventes datos. Si ya tienes los 4 datos, responde SOLO este JSON: {\"accion\": \"crear\", \"titulo\": \"...\", \"descripcion\": \"...\", \"fecha_vencimiento\": \"Y-m-d H:i:s\", \"prioridad\": \"alta/media/baja\"}\n";
        $contexto .= "3. EDITAR: Si pide editar una tarea, identifica su ID y responde SOLO este JSON: {\"accion\": \"editar\", \"id\": ID_NUMERICO, \"titulo\": \"...\", \"descripcion\": \"...\", \"fecha_vencimiento\": \"Y-m-d H:i:s\", \"prioridad\": \"alta/media/baja\"} (solo incluye los campos que el usuario quiere cambiar).\n";
        $contexto .= "4. ELIMINAR: Si pide eliminar una tarea, identifica su ID y responde SOLO este JSON: {\"accion\": \"eliminar\", \"id\": ID_NUMERICO}\n";
        $contexto .= "5. FORMATO JSON: Si vas a ejecutar una acción (crear, editar, eliminar), tu respuesta debe ser ÚNICAMENTE el código JSON válido, sin saludos, ni explicaciones previas ni posteriores.\n\n";

        $contexto .= "Tareas actuales del usuario:\n";
        if ($tareas->isEmpty()) {
            $contexto .= "No hay tareas.\n";
        } else {
            foreach ($tareas as $tarea) {
                $contexto .= "- ID: {$tarea->id} | Título: {$tarea->titulo} | Fecha: {$tarea->fecha_vencimiento} | Prioridad: {$tarea->prioridad}\n";
            }
        }

        try {
            $response = Http::timeout(60)->post(env('OLLAMA_URL'), [
                'model' => 'llama3',
                'prompt' => $contexto . " Mensaje del usuario: " . $request->pregunta,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $textoIA = trim($response->json()['response']);

                $jsonStr = $textoIA;
                if (preg_match('/\{.*\}/s', $textoIA, $matches)) {
                    $jsonStr = $matches[0];
                }

                $data = json_decode($jsonStr, true);

                if (is_array($data) && isset($data['accion'])) {
                    
                    if ($data['accion'] === 'crear') {
                        $nuevaTarea = Tarea::create([
                            'user_id' => Auth::id(),
                            'titulo' => $data['titulo'],
                            'descripcion' => $data['descripcion'] ?? 'Sin descripción',
                            'prioridad' => $data['prioridad'] ?? 'media',
                            'estado' => 'pendiente',
                            'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null
                        ]);
                        return response()->json([
                            'respuesta' => "¡Listo! He creado la tarea '{$nuevaTarea->titulo}' con los detalles indicados.",
                            'accion_realizada' => 'crear',
                            'nueva_tarea' => [
                                'titulo' => $nuevaTarea->titulo,
                                'prioridad' => $nuevaTarea->prioridad,
                                'fecha' => \Carbon\Carbon::parse($nuevaTarea->fecha_vencimiento)->format('d/m/Y H:i'),
                                'url_edit' => route('tareas.edit', $nuevaTarea->id)
                            ]
                        ]);
                    }

                    if ($data['accion'] === 'eliminar') {
                        $tarea = Tarea::where('user_id', Auth::id())->find($data['id']);
                        if ($tarea) {
                            $tarea->delete();
                            return response()->json([
                                'respuesta' => "La tarea '{$tarea->titulo}' se ha eliminado correctamente.",
                                'accion_realizada' => 'recargar'
                            ]);
                        }
                    }

                    if ($data['accion'] === 'editar') {
                        $tarea = Tarea::where('user_id', Auth::id())->find($data['id']);
                        if ($tarea) {
                            $tarea->update([
                                'titulo' => $data['titulo'] ?? $tarea->titulo,
                                'descripcion' => $data['descripcion'] ?? $tarea->descripcion,
                                'prioridad' => $data['prioridad'] ?? $tarea->prioridad,
                                'fecha_vencimiento' => $data['fecha_vencimiento'] ?? $tarea->fecha_vencimiento
                            ]);
                            return response()->json([
                                'respuesta' => "La tarea '{$tarea->titulo}' se ha editado correctamente.",
                                'accion_realizada' => 'recargar'
                            ]);
                        }
                    }
                }

                return response()->json(['respuesta' => $textoIA]);
            }
            return response()->json(['respuesta' => 'Error en Ollama'], 500);
        } catch (\Exception $e) {
            return response()->json(['respuesta' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}