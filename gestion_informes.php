<?php
require_once 'conexion.php';
verificarAuth();

// Procesar crear informe
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_informe'])) {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $categoria = $_POST['categoria'];
    $estado = $_POST['estado'];
    $prioridad = $_POST['prioridad'];
    $fecha_limite = $_POST['fecha_limite'];

    $stmt = $pdo->prepare("INSERT INTO informes (titulo, descripcion, categoria, estado, prioridad, autor_id, fecha_limite) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$titulo, $descripcion, $categoria, $estado, $prioridad, $_SESSION['usuario_id'], $fecha_limite]);
    
    registrarActividad($_SESSION['usuario_id'], 'Creó informe: ' . $titulo, 'create');
    
    header('Location: gestion_informes.php?success=1');
    exit();
}

// Procesar eliminar informe
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM informes WHERE id = ? AND autor_id = ?");
    $stmt->execute([$id, $_SESSION['usuario_id']]);
    
    registrarActividad($_SESSION['usuario_id'], 'Eliminó informe ID: ' . $id, 'delete');
    
    header('Location: gestion_informes.php?success=2');
    exit();
}

// Obtener informes REALES
$informes = $pdo->query("
    SELECT i.*, u.nombre as autor_nombre 
    FROM informes i 
    JOIN usuarios u ON i.autor_id = u.id 
    ORDER BY i.fecha_creacion DESC
")->fetchAll();

// Estadísticas
$total_informes = count($informes);
$informes_completados = array_filter($informes, fn($i) => $i['estado'] == 'completed');
$informes_progreso = array_filter($informes, fn($i) => $i['estado'] == 'in_progress');
$informes_pendientes = array_filter($informes, fn($i) => $i['estado'] == 'not_started');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Informes - EduAdmin Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-secondary-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-secondary-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-8">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 bg-primary rounded-lg mr-3">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold text-secondary-800">EduAdmin Portal</h1>
                        </div>
                    </div>
                    
                    <nav class="hidden md:flex space-x-6">
                        <a href="admin_dashboard.php" class="text-secondary-600 hover:text-primary transition-colors">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <a href="gestion_avisos.php" class="text-secondary-600 hover:text-primary transition-colors">
                            <i class="fas fa-bullhorn mr-2"></i>Avisos
                        </a>
                        <a href="gestion_informes.php" class="text-primary font-medium">
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
                        <img src="https://images.unsplash.com/photo-1652376924447-ab929125ee31" alt="Foto de perfil" class="w-8 h-8 rounded-full object-cover">
                        <div class="hidden md:block">
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
                    <h1 class="text-3xl font-semibold text-secondary-800 mb-2">Gestión de Informes</h1>
                    <p class="text-secondary-600">Crea, envía y rastrea informes institucionales</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="abrirModalCrear()" class="btn-primary flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Nuevo Informe</span>
                    </button>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 p-4 bg-success-50 border border-success-200 rounded-md">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-success-600 mr-2"></i>
                <span class="text-sm text-success-600">
                    <?php echo $_GET['success'] == 1 ? 'Informe creado exitosamente' : 'Informe eliminado exitosamente'; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-primary-50 rounded-lg">
                        <i class="fas fa-file-alt text-primary text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-600">Total Informes</p>
                        <p class="text-2xl font-semibold text-secondary-800"><?php echo $total_informes; ?></p>
                    </div>
                </div>
            </div>
            <div class="card p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-success-50 rounded-lg">
                        <i class="fas fa-check-circle text-success-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-600">Completados</p>
                        <p class="text-2xl font-semibold text-secondary-800"><?php echo count($informes_completados); ?></p>
                    </div>
                </div>
            </div>
            <div class="card p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-warning-50 rounded-lg">
                        <i class="fas fa-spinner text-warning-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-600">En Progreso</p>
                        <p class="text-2xl font-semibold text-secondary-800"><?php echo count($informes_progreso); ?></p>
                    </div>
                </div>
            </div>
            <div class="card p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-error-50 rounded-lg">
                        <i class="fas fa-clock text-error-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-600">Pendientes</p>
                        <p class="text-2xl font-semibold text-secondary-800"><?php echo count($informes_pendientes); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informes Table -->
        <div class="card">
            <div class="p-6 border-b border-secondary-200">
                <h2 class="text-xl font-semibold text-secondary-800">Lista de Informes</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-secondary-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Título</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Categoría</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Prioridad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Fecha Límite</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Autor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-secondary-200">
                        <?php foreach ($informes as $informe): ?>
                        <tr class="hover:bg-secondary-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-secondary-800"><?php echo $informe['titulo']; ?></div>
                                <div class="text-sm text-secondary-500"><?php echo substr($informe['descripcion'], 0, 60) . '...'; ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                    <?php echo $informe['categoria']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php echo $informe['estado'] == 'completed' ? 'bg-success-100 text-success-800' : 
                                           ($informe['estado'] == 'in_progress' ? 'bg-warning-100 text-warning-800' : 'bg-error-100 text-error-800'); ?>">
                                    <?php echo $informe['estado'] == 'completed' ? 'Completado' : 
                                           ($informe['estado'] == 'in_progress' ? 'En Progreso' : 'No Iniciado'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php echo $informe['prioridad'] == 'urgent' ? 'bg-error-100 text-error-800' : 
                                           ($informe['prioridad'] == 'high' ? 'bg-warning-100 text-warning-800' : 'bg-secondary-100 text-secondary-800'); ?>">
                                    <?php echo $informe['prioridad']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-secondary-500">
                                <?php echo date('d/m/Y', strtotime($informe['fecha_limite'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-secondary-500">
                                <?php echo $informe['autor_nombre']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <button onclick="verInforme(<?php echo $informe['id']; ?>)" class="text-primary hover:text-primary-700 mr-3">Ver</button>
                                <?php if ($informe['autor_id'] == $_SESSION['usuario_id'] || $_SESSION['usuario_rol'] == 'admin'): ?>
                                <button onclick="editarInforme(<?php echo $informe['id']; ?>)" class="text-accent hover:text-accent-600 mr-3">Editar</button>
                                <button onclick="eliminarInforme(<?php echo $informe['id']; ?>)" class="text-error-600 hover:text-error-800">Eliminar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Crear Informe -->
    <div id="modalCrear" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-secondary-800">Crear Nuevo Informe</h3>
                <button onclick="cerrarModalCrear()" class="text-secondary-400 hover:text-secondary-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="crear_informe" value="1">
                
                <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-2">Título del Informe</label>
                    <input type="text" name="titulo" class="form-input" placeholder="Ingresa el título del informe" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-2">Descripción</label>
                    <textarea name="descripcion" rows="4" class="form-input resize-none" placeholder="Describe el contenido y objetivos del informe" required></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-2">Categoría</label>
                        <select name="categoria" class="form-input" required>
                            <option value="academico">Académico</option>
                            <option value="administrativo">Administrativo</option>
                            <option value="financiero">Financiero</option>
                            <option value="recursos_humanos">Recursos Humanos</option>
                            <option value="infraestructura">Infraestructura</option>
                            <option value="tecnologia">Tecnología</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-2">Estado</label>
                        <select name="estado" class="form-input" required>
                            <option value="not_started">No Iniciado</option>
                            <option value="in_progress">En Progreso</option>
                            <option value="completed">Completado</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-2">Prioridad</label>
                        <select name="prioridad" class="form-input" required>
                            <option value="low">Baja</option>
                            <option value="medium">Media</option>
                            <option value="high">Alta</option>
                            <option value="urgent">Urgente</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-2">Fecha Límite</label>
                        <input type="date" name="fecha_limite" class="form-input" required>
                    </div>
                </div>
                
                <div class="flex items-center justify-end space-x-3 pt-4">
                    <button type="button" onclick="cerrarModalCrear()" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Crear Informe</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalCrear() {
            document.getElementById('modalCrear').classList.remove('hidden');
            // Set default date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.querySelector('input[name="fecha_limite"]').value = tomorrow.toISOString().split('T')[0];
        }
        
        function cerrarModalCrear() {
            document.getElementById('modalCrear').classList.add('hidden');
        }
        
        function eliminarInforme(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este informe?')) {
                window.location.href = 'gestion_informes.php?eliminar=' + id;
            }
        }
        
        function verInforme(id) {
            alert('Funcionalidad de ver informe en desarrollo para ID: ' + id);
        }
        
        function editarInforme(id) {
            alert('Funcionalidad de editar informe en desarrollo para ID: ' + id);
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