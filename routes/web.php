<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Aquí es donde registramos las rutas de la aplicación.
|
*/

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('tareas.index');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route('tareas.index');
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';

Route::middleware(['auth'])->group(function () {
    Route::resource('tareas', TareaController::class);
    
    Route::post('/chat/ask', [ChatController::class, 'ask'])->name('chat.ask');
});