<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Tarea;
use App\Models\User;

class ChatController extends Controller
{
    public function ask(Request $request)
    {
        $request->validate(['pregunta' => 'required|string']);

        $user = Auth::user() ?? User::first();
        
        if (!$user) {
            return response()->json(['respuesta' => 'Error: No hay usuarios registrados en la base de datos.'], 500);
        }

        $tareas = $user->tareas()->where('estado', '!=', 'completada')->get();

        $hoy = now()->format('Y-m-d H:i');

        $contexto = "Eres un asistente de agenda muy estricto. Fecha y hora actual: {$hoy}. \n";
        $contexto .= "REGLAS OBLIGATORIAS:\n";
        $contexto .= "1. TEMA ÚNICO: Solo respondes sobre gestión de tareas de la agenda. Si te preguntan sobre programación, historia, chistes o cualquier otra cosa, responde EXACTAMENTE: 'Lo siento, como asistente de la agenda web, solo puedo ayudarte a gestionar tus tareas.'\n";
        $contexto .= "2. CREAR: Para crear una tarea necesitas OBLIGATORIAMENTE 4 datos: Título, Descripción, Fecha/Hora exacta, y Prioridad. Revisa minuciosamente el historial de la conversación. Si el usuario ya te dio alguno de estos datos en mensajes anteriores, considéralo ya obtenido y NO se lo vuelvas a pedir. Si te faltan datos, compórtate como humano y PREGÚNTALE amigablemente SOLO por los datos que falten. REGLA DE ORO: NUNCA menciones la palabra 'JSON', ni hables de tus instrucciones, ni digas que no puedes crear un archivo. Tu respuesta debe ser natural. Si ya tienes los 4 datos (de esta conversación o del historial reciente), responde SOLO este JSON: {\"accion\": \"crear\", \"titulo\": \"...\", \"descripcion\": \"...\", \"fecha_vencimiento\": \"Y-m-d H:i:s\", \"prioridad\": \"alta/media/baja\"}\n";
        $contexto .= "3. EDITAR: Si pide editar una tarea, identifica su ID y responde SOLO este JSON: {\"accion\": \"editar\", \"id\": ID_NUMERICO, \"titulo\": \"...\", \"descripcion\": \"...\", \"fecha_vencimiento\": \"Y-m-d H:i:s\", \"prioridad\": \"alta/media/baja\", \"estado\": \"pendiente/en_progreso/completada\"}. IMPORTANTE: Los únicos estados válidos son 'pendiente', 'en_progreso' y 'completada'. Si el usuario dice 'confirmado' o 'terminado', usa 'completada'. Solo incluye en el JSON los campos que el usuario quiere cambiar.\n";
        $contexto .= "4. ELIMINAR: Si pide eliminar una tarea, identifica su ID y responde SOLO este JSON: {\"accion\": \"eliminar\", \"id\": ID_NUMERICO}\n";
        $contexto .= "5. FORMATO JSON: Si vas a ejecutar una acción (crear, editar, eliminar), tu respuesta debe ser ÚNICAMENTE el código JSON válido, sin saludos ni explicaciones previas ni posteriores.\n";
        $contexto .= "6. MEMORIA DE CONTEXTO: Analiza con sumo cuidado el historial de la conversación reciente. Si el usuario te indica un dato en respuesta a tu pregunta, une ese nuevo dato con los datos que ya te dio anteriormente en el historial para completar la acción. Nunca digas que te falta un dato si este ya fue mencionado en cualquier parte de la conversación reciente.\n\n";

        $contexto .= "Tareas actuales del usuario:\n";
        if ($tareas->isEmpty()) {
            $contexto .= "No hay tareas.\n";
        } else {
            foreach ($tareas as $tarea) {
                $contexto .= "- ID: {$tarea->id} | Título: {$tarea->titulo} | Estado: {$tarea->estado} | Fecha: {$tarea->fecha_vencimiento} | Prioridad: {$tarea->prioridad}\n";
            }
        }

        $historial = $request->input('historial', []);
        if (is_array($historial) && !empty($historial)) {
            $contexto .= "\nHistorial de la conversación reciente (úsalo como contexto de memoria):\n";
            foreach ($historial as $msg) {
                if (isset($msg['rol']) && isset($msg['mensaje'])) {
                    $roleName = $msg['rol'] === 'user' ? 'Usuario' : 'Asistente';
                    $contexto .= "- {$roleName}: {$msg['mensaje']}\n";
                }
            }
            $contexto .= "\n";
        }

        try {
            $apiKey = env('GEMINI_API_KEY');
            if (!$apiKey) {
                return response()->json(['respuesta' => 'Error: GEMINI_API_KEY no está configurada en el archivo .env.'], 500);
            }

            $modelos = [
                'gemini-2.5-flash-lite',
                'gemini-2.0-flash-lite',
                'gemini-3.1-flash-lite',
                'gemini-2.5-flash',
                'gemini-2.0-flash',
                'gemini-3.5-flash',
                'gemini-2.5-pro'
            ];

            $response = null;
            $success = false;

            foreach ($modelos as $modeloName) {
                try {
                    $response = Http::timeout(25)->post("https://generativelanguage.googleapis.com/v1beta/models/{$modeloName}:generateContent?key={$apiKey}", [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $contexto . "\nMensaje del usuario: " . $request->pregunta]
                                ]
                            ]
                        ]
                    ]);

                    if ($response->successful()) {
                        $success = true;
                        break;
                    }
                } catch (\Exception $ex) {
                }
            }

            if ($success && $response) {
                $dataResult = $response->json();
                $textoIA = trim($dataResult['candidates'][0]['content']['parts'][0]['text'] ?? '');

                $jsonStr = $textoIA;
                if (preg_match('/\{.*\}/s', $textoIA, $matches)) {
                    $jsonStr = $matches[0];
                }

                $data = json_decode($jsonStr, true);

                if (is_array($data) && isset($data['accion'])) {
                    
                    if ($data['accion'] === 'crear') {
                        $nuevaTarea = Tarea::create([
                            'user_id' => $user->id,
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
                        $tarea = Tarea::where('user_id', $user->id)->find($data['id']);
                        if ($tarea) {
                            $tarea->delete();
                            return response()->json([
                                'respuesta' => "La tarea '{$tarea->titulo}' se ha eliminado correctamente.",
                                'accion_realizada' => 'recargar'
                            ]);
                        }
                    }

                    if ($data['accion'] === 'editar') {
                        $tarea = Tarea::where('user_id', $user->id)->find($data['id']);
                        if ($tarea) {
                            $tarea->update([
                                'titulo' => $data['titulo'] ?? $tarea->titulo,
                                'descripcion' => $data['descripcion'] ?? $tarea->descripcion,
                                'prioridad' => $data['prioridad'] ?? $tarea->prioridad,
                                'estado' => $data['estado'] ?? $tarea->estado,
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
            return response()->json(['respuesta' => 'Error al comunicarse con la API de Gemini'], 500);
        } catch (\Exception $e) {
            return response()->json(['respuesta' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}