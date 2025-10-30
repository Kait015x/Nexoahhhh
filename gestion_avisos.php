<?php
require_once 'conexion.php';
verificarAuth();

// Procesar crear aviso
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_aviso'])) {
    $titulo = $_POST['titulo'];
    $contenido = $_POST['contenido'];
    $categoria = $_POST['categoria'];
    $prioridad = $_POST['prioridad'];
    $audiencia = $_POST['audiencia'];
    $fecha_publicacion = $_POST['fecha_publicacion'];
    $enviar_notificacion = isset($_POST['enviar_notificacion']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO avisos (titulo, contenido, categoria, prioridad, audiencia, autor_id, estado, fecha_publicacion) VALUES (?, ?, ?, ?, ?, ?, 'published', ?)");
    $stmt->execute([$titulo, $contenido, $categoria, $prioridad, $audiencia, $_SESSION['usuario_id'], $fecha_publicacion]);
    
    registrarActividad($_SESSION['usuario_id'], 'Creó aviso: ' . $titulo, 'create');
    
    header('Location: gestion_avisos.php?success=1');
    exit();
}

// Procesar eliminar aviso
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM avisos WHERE id = ? AND autor_id = ?");
    $stmt->execute([$id, $_SESSION['usuario_id']]);
    
    registrarActividad($_SESSION['usuario_id'], 'Eliminó aviso ID: ' . $id, 'delete');
    
    header('Location: gestion_avisos.php?success=2');
    exit();
}

// Obtener avisos REALES
$avisos = $pdo->query("
    SELECT a.*, u.nombre as autor_nombre 
    FROM avisos a 
    JOIN usuarios u ON a.autor_id = u.id 
    ORDER BY a.fecha_creacion DESC
")->fetchAll();

// Estadísticas
$total_avisos = count($avisos);
$avisos_publicados = array_filter($avisos, fn($a) => $a['estado'] == 'published');
$avisos_borrador = array_filter($avisos, fn($a) => $a['estado'] == 'draft');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Avisos - EduAdmin Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body class="bg-background min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-secondary-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-8">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 bg-primary rounded-lg mr-3">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-semibold text-secondary-800">EduAdmin Portal</span>
                    </div>
                    
                    <nav class="hidden md:flex space-x-6">
                        <a href="admin_dashboard.php" class="text-secondary-600 hover:text-primary transition-colors">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <a href="gestion_avisos.php" class="text-primary font-medium">
                            <i class="fas fa-bullhorn mr-2"></i>Avisos
                        </a>
                        <a href="gestion_informes.php" class="text-secondary-600 hover:text-primary transition-colors">
                            <i class="fas fa-file-alt mr-2"></i>Informes
                        </a>
                        <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
                        <a href="gestion_usuarios.php" class="text-secondary-600 hover:text-primary transition-colors">
                            <i class="fas fa-users mr-2"></i>Usuarios
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-3">
                        <img src="https://images.unsplash.com/photo-1689351355812-05c02e711724" alt="Foto de perfil" class="w-8 h-8 rounded-full object-cover">
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-medium text-secondary-800"><?php echo $_SESSION['usuario_nombre']; ?></p>
                            <p class="text-xs text-secondary-500"><?php echo $_SESSION['usuario_rol'] === 'admin' ? 'Administrador' : 'Profesor'; ?></p>
                        </div>
                    </div>
                    <a href="logout.php" class="text-secondary-400 hover:text-secondary-600 transition-colors">
                        <i class="fas fa-sign-out-alt text-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-secondary-800 mb-2">Gestión de Avisos</h1>
                    <p class="text-secondary-600">Crea, publica y gestiona los avisos institucionales</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="abrirModalCrear()" class="btn-primary flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Nuevo Aviso</span>
                    </button>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 p-4 bg-success-50 border border-success-200 rounded-md">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-success-600 mr-2"></i>
                <span class="text-sm text-success-600">
                    <?php echo $_GET['success'] == 1 ? 'Aviso creado exitosamente' : 'Aviso eliminado exitosamente'; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="card p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-primary-50 rounded-lg">
                        <i class="fas fa-bullhorn text-primary text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-600">Total Avisos</p>
                        <p class="text-2xl font-semibold text-secondary-800"><?php echo $total_avisos; ?></p>
                    </div>
                </div>
            </div>
            <div class="card p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-success-50 rounded-lg">
                        <i class="fas fa-eye text-success-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-600">Publicados</p>
                        <p class="text-2xl font-semibold text-secondary-800"><?php echo count($avisos_publicados); ?></p>
                    </div>
                </div>
            </div>
            <div class="card p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-warning-50 rounded-lg">
                        <i class="fas fa-edit text-warning-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-600">Borradores</p>
                        <p class="text-2xl font-semibold text-secondary-800"><?php echo count($avisos_borrador); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avisos Table -->
        <div class="card">
            <div class="p-6 border-b border-secondary-200">
                <h2 class="text-xl font-semibold text-secondary-800">Lista de Avisos</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-secondary-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Título</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Categoría</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Prioridad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Vistas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-secondary-200">
                        <?php foreach ($avisos as $aviso): ?>
                        <tr class="hover:bg-secondary-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-secondary-800"><?php echo $aviso['titulo']; ?></div>
                                <div class="text-sm text-secondary-500">Por: <?php echo $aviso['autor_nombre']; ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                    <?php echo $aviso['categoria']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php echo $aviso['prioridad'] == 'high' ? 'bg-error-100 text-error-800' : 
                                           ($aviso['prioridad'] == 'medium' ? 'bg-warning-100 text-warning-800' : 'bg-secondary-100 text-secondary-800'); ?>">
                                    <?php echo $aviso['prioridad']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php echo $aviso['estado'] == 'published' ? 'bg-success-100 text-success-800' : 
                                           ($aviso['estado'] == 'draft' ? 'bg-warning-100 text-warning-800' : 'bg-secondary-100 text-secondary-800'); ?>">
                                    <?php echo $aviso['estado']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-secondary-500">
                                <?php echo $aviso['fecha_publicacion'] ? date('d/m/Y H:i', strtotime($aviso['fecha_publicacion'])) : 'No publicada'; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-secondary-500">
                                <?php echo $aviso['vistas']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <button onclick="verAviso(<?php echo $aviso['id']; ?>)" class="text-primary hover:text-primary-700 mr-3">Ver</button>
                                <?php if ($aviso['autor_id'] == $_SESSION['usuario_id'] || $_SESSION['usuario_rol'] == 'admin'): ?>
                                <button onclick="editarAviso(<?php echo $aviso['id']; ?>)" class="text-accent hover:text-accent-600 mr-3">Editar</button>
                                <button onclick="eliminarAviso(<?php echo $aviso['id']; ?>)" class="text-error-600 hover:text-error-800">Eliminar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Crear Aviso -->
    <div id="modalCrear" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-secondary-800">Crear Nuevo Aviso</h3>
                <button onclick="cerrarModalCrear()" class="text-secondary-400 hover:text-secondary-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="crear_aviso" value="1">
                
                <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-2">Título del Aviso</label>
                    <input type="text" name="titulo" class="form-input" placeholder="Ingresa el título..." required>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-2">Categoría</label>
                        <select name="categoria" class="form-input" required>
                            <option value="general">General</option>
                            <option value="academic">Académico</option>
                            <option value="administrative">Administrativo</option>
                            <option value="events">Eventos</option>
                            <option value="urgent">Urgente</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-2">Prioridad</label>
                        <select name="prioridad" class="form-input" required>
                            <option value="low">Baja</option>
                            <option value="medium" selected>Media</option>
                            <option value="high">Alta</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-2">Audiencia</label>
                        <select name="audiencia" class="form-input" required>
                            <option value="all">Todos</option>
                            <option value="admin">Administradores</option>
                            <option value="teachers">Profesores</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-2">Contenido</label>
                    <textarea name="contenido" rows="6" class="form-input resize-none" placeholder="Escribe el contenido del aviso..." required></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-2">Fecha de Publicación</label>
                    <input type="datetime-local" name="fecha_publicacion" class="form-input" required>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="enviar_notificacion" name="enviar_notificacion" class="h-4 w-4 text-primary focus:ring-primary-500 border-secondary-300 rounded" checked>
                    <label for="enviar_notificacion" class="ml-2 text-sm text-secondary-700">Enviar notificación por email</label>
                </div>
                
                <div class="flex items-center justify-end space-x-3 pt-4">
                    <button type="button" onclick="cerrarModalCrear()" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Publicar Aviso</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalCrear() {
            document.getElementById('modalCrear').classList.remove('hidden');
            // Set current date/time
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.querySelector('input[name="fecha_publicacion"]').value = now.toISOString().slice(0, 16);
        }
        
        function cerrarModalCrear() {
            document.getElementById('modalCrear').classList.add('hidden');
        }
        
        function eliminarAviso(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este aviso?')) {
                window.location.href = 'gestion_avisos.php?eliminar=' + id;
            }
        }
        
        function verAviso(id) {
            alert('Funcionalidad de ver aviso en desarrollo para ID: ' + id);
        }
        
        function editarAviso(id) {
            alert('Funcionalidad de editar aviso en desarrollo para ID: ' + id);
        }
        
        // Close modal on outside click
        document.getElementById('modalCrear').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalCrear();
            }
        });
    </script>
</body>
</html>