<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Agenda IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('tareas.index') }}">
            <i class="fas fa-book-open me-2"></i>Mi Agenda IA
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white fw-semibold" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i> {{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="profileDropdown">
                        <li class="dropdown-header">
                            <h6 class="mb-0 text-dark">{{ Auth::user()->name }}</h6>
                            <small class="text-muted">{{ Auth::user()->email }}</small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 class="text-primary fw-bold">Mis Tareas</h1>
            <p class="text-muted">Gestiona tus actividades diarias, {{ Auth::user()->name }}.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('tareas.create') }}" class="btn btn-primary btn-lg shadow-sm">
                <i class="fas fa-plus me-2"></i>Nueva Tarea
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-white rounded">
            <form action="{{ route('tareas.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="buscar" class="form-label fw-semibold">Buscar</label>
                    <input type="text" class="form-control" id="buscar" name="buscar" placeholder="Título de la tarea..." value="{{ request('buscar') }}">
                </div>
                <div class="col-md-3">
                    <label for="estado" class="form-label fw-semibold">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="en_progreso" {{ request('estado') == 'en_progreso' ? 'selected' : '' }}>En Progreso</option>
                        <option value="completada" {{ request('estado') == 'completada' ? 'selected' : '' }}>Completada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="prioridad" class="form-label fw-semibold">Prioridad</label>
                    <select class="form-select" id="prioridad" name="prioridad">
                        <option value="">Todas</option>
                        <option value="baja" {{ request('prioridad') == 'baja' ? 'selected' : '' }}>Baja</option>
                        <option value="media" {{ request('prioridad') == 'media' ? 'selected' : '' }}>Media</option>
                        <option value="alta" {{ request('prioridad') == 'alta' ? 'selected' : '' }}>Alta</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100 fw-bold">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            @if($tareas->isEmpty())
                <div class="p-5 text-center">
                    <i class="fas fa-clipboard-list fa-3x text-light mb-3"></i>
                    <p class="text-muted">No se encontraron tareas o aún no has registrado ninguna.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Título</th>
                                <th>Estado</th>
                                <th>Prioridad</th>
                                <th>Vencimiento</th>
                                <th class="text-center pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tareas as $tarea)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $tarea->titulo }}</td>
                                <td>
                                    <span class="badge rounded-pill bg-{{ $tarea->estado == 'completada' ? 'success' : ($tarea->estado == 'en_progreso' ? 'warning text-dark' : 'secondary') }}">
                                        {{ ucfirst(str_replace('_', ' ', $tarea->estado)) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $tarea->prioridad == 'alta' ? 'danger' : ($tarea->prioridad == 'media' ? 'primary' : 'info') }}">
                                        {{ ucfirst($tarea->prioridad) }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $tarea->fecha_vencimiento ? \Carbon\Carbon::parse($tarea->fecha_vencimiento)->format('d/m/Y') : 'Sin fecha' }}</td>
                                <td class="text-center pe-4">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('tareas.edit', $tarea->id) }}" class="btn btn-sm btn-outline-primary border-0 shadow-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('tareas.destroy', $tarea->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta tarea?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 shadow-sm" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<div id="chat-bubble" style="position: fixed; bottom: 20px; right: 20px; cursor: pointer; background: #0d6efd; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,0,0,0.3); z-index: 1000; font-weight: bold; transition: transform 0.2s;">
    <i class="fas fa-robot fa-lg"></i>
</div>

<div id="chat-window" style="display: none; position: fixed; bottom: 90px; right: 20px; width: 350px; height: 450px; background: white; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.2); z-index: 1000; flex-direction: column; overflow: hidden; border: 1px solid #dee2e6;">
    <div style="background: #0d6efd; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 16px;"><i class="fas fa-robot me-2"></i>Asistente IA</h3>
        <button onclick="toggleChat()" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">&times;</button>
    </div>

    <div id="chat-messages" style="flex-grow: 1; padding: 15px; overflow-y: auto; background: #f8f9fa; display: flex; flex-direction: column; gap: 10px;">
        <div style="background: #e9ecef; padding: 10px; border-radius: 10px; align-self: flex-start; max-width: 80%; font-size: 14px;">
            ¡Hola {{ Auth::user()->name }}! Soy tu asistente. ¿Qué deseas saber sobre tus tareas?
        </div>
    </div>

    <div style="padding: 15px; border-top: 1px solid #dee2e6; display: flex; gap: 10px;">
        <input type="text" id="user-input" class="form-control form-control-sm" placeholder="Escribe tu duda aquí...">
        <button onclick="sendMessage()" class="btn btn-primary btn-sm px-3">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
    function toggleChat() {
        const windowChat = document.getElementById('chat-window');
        windowChat.style.display = (windowChat.style.display === 'none' || windowChat.style.display === '') ? 'flex' : 'none';
    }

    document.getElementById('chat-bubble').onclick = toggleChat;

    async function sendMessage() {
    const input = document.getElementById('user-input');
    const messagesContainer = document.getElementById('chat-messages');
    const texto = input.value.trim();

    if (texto === "") return;

    messagesContainer.innerHTML += `<div style="background: #0d6efd; color: white; padding: 10px; border-radius: 10px; align-self: flex-end; max-width: 80%; font-size: 14px;">${texto}</div>`;
    input.value = "";
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    try {
        const response = await fetch("{{ route('chat.ask') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ pregunta: texto })
        });

        const data = await response.json();

        messagesContainer.innerHTML += `<div style="background: #e9ecef; padding: 10px; border-radius: 10px; align-self: flex-start; max-width: 80%; font-size: 14px;">${data.respuesta}</div>`;
        
        if (data.nueva_tarea) {
            const tableBody = document.querySelector('table tbody');
            const colorPrioridad = data.nueva_tarea.prioridad === 'alta' ? 'danger' : (data.nueva_tarea.prioridad === 'media' ? 'primary' : 'info');
            
            const nuevaFila = `
                <tr>
                    <td class="ps-4 fw-bold text-dark">${data.nueva_tarea.titulo}</td>
                    <td><span class="badge rounded-pill bg-secondary">Pendiente</span></td>
                    <td><span class="badge bg-${colorPrioridad}">${data.nueva_tarea.prioridad.charAt(0).toUpperCase() + data.nueva_tarea.prioridad.slice(1)}</span></td>
                    <td class="text-muted">${data.nueva_tarea.fecha}</td>
                    <td class="text-center pe-4">
                        <div class="d-flex justify-content-center gap-2">
                            <a href="${data.nueva_tarea.url_edit}" class="btn btn-sm btn-outline-primary border-0 shadow-sm"><i class="fas fa-edit"></i></a>
                            <small class="text-muted">Recarga para borrar</small>
                        </div>
                    </td>
                </tr>`;
            
            tableBody.insertAdjacentHTML('afterbegin', nuevaFila);
        }

        messagesContainer.scrollTop = messagesContainer.scrollHeight;

    } catch (error) {
        messagesContainer.innerHTML += `<div style="background: #f8d7da; color: #842029; padding: 10px; border-radius: 10px; align-self: flex-start; font-size: 12px;">Error al conectar con la IA local.</div>`;
    }
}

    document.getElementById('user-input').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') sendMessage();
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>