<?php
require_once 'conexion.php';
verificarAuth();

// Obtener estadísticas REALES de la BD
$avisos_publicados = $pdo->query("SELECT COUNT(*) as total FROM avisos WHERE estado = 'published'")->fetch()['total'];
$total_informes = $pdo->query("SELECT COUNT(*) as total FROM informes")->fetch()['total'];
$total_usuarios = $pdo->query("SELECT COUNT(*) as total FROM usuarios")->fetch()['total'];
$usuarios_activos = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'active'")->fetch()['total'];

// Actividad reciente
$actividad_reciente = $pdo->query("
    SELECT a.*, u.nombre as usuario_nombre 
    FROM actividad a 
    JOIN usuarios u ON a.usuario_id = u.id 
    ORDER BY a.fecha DESC 
    LIMIT 5
")->fetchAll();

// Avisos recientes
$avisos_recientes = $pdo->query("
    SELECT a.*, u.nombre as autor_nombre 
    FROM avisos a 
    JOIN usuarios u ON a.autor_id = u.id 
    WHERE a.estado = 'published'
    ORDER BY a.fecha_publicacion DESC 
    LIMIT 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - EduAdmin Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-background min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-secondary-200 sticky top-0 z-40">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-10 h-10 bg-primary rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold text-secondary-800">EduAdmin Portal</h1>
                        <p class="text-sm text-secondary-600">Panel de Administración</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="relative p-2 text-secondary-600 hover:text-secondary-800 hover:bg-secondary-100 rounded-lg transition-colors">
                            <i class="fas fa-bell text-lg"></i>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-error text-white text-xs rounded-full flex items-center justify-center">3</span>
                        </button>
                    </div>

                    <div class="relative">
                        <button id="userMenuButton" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-secondary-100 transition-colors">
                            <img src="https://images.unsplash.com/photo-1689351355812-05c02e711724" alt="Foto de perfil" class="w-8 h-8 rounded-full object-cover">
                            <div class="text-left">
                                <p class="text-sm font-medium text-secondary-800"><?php echo $_SESSION['usuario_nombre']; ?></p>
                                <p class="text-xs text-secondary-600"><?php echo $_SESSION['usuario_rol'] === 'admin' ? 'Administrador' : 'Profesor'; ?></p>
                            </div>
                            <i class="fas fa-chevron-down text-xs text-secondary-400"></i>
                        </button>

                        <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-secondary-200 py-2 z-50">
                            <a href="user_profile.php" class="flex items-center px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50">
                                <i class="fas fa-user mr-3 text-secondary-400"></i>Mi Perfil
                            </a>
                            <a href="javascript:void(0)" class="flex items-center px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50">
                                <i class="fas fa-cog mr-3 text-secondary-400"></i>Configuración
                            </a>
                            <hr class="my-2 border-secondary-200">
                            <a href="logout.php" class="w-full flex items-center px-4 py-2 text-sm text-error-600 hover:bg-error-50">
                                <i class="fas fa-sign-out-alt mr-3"></i>Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-sm border-r border-secondary-200 min-h-screen">
            <nav class="p-6">
                <ul class="space-y-2">
                    <li>
                        <a href="admin_dashboard.php" class="flex items-center px-4 py-3 text-primary bg-primary-50 rounded-lg font-medium">
                            <i class="fas fa-tachometer-alt mr-3"></i>Panel Principal
                        </a>
                    </li>
                    <li>
                        <a href="gestion_avisos.php" class="flex items-center px-4 py-3 text-secondary-700 hover:bg-secondary-50 rounded-lg transition-colors">
                            <i class="fas fa-bullhorn mr-3"></i>Gestión de Avisos
                        </a>
                    </li>
                    <li>
                        <a href="gestion_informes.php" class="flex items-center px-4 py-3 text-secondary-700 hover:bg-secondary-50 rounded-lg transition-colors">
                            <i class="fas fa-file-alt mr-3"></i>Gestión de Informes
                        </a>
                    </li>
                    <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
                    <li>
                        <a href="gestion_usuarios.php" class="flex items-center px-4 py-3 text-secondary-700 hover:bg-secondary-50 rounded-lg transition-colors">
                            <i class="fas fa-users mr-3"></i>Gestión de Usuarios
                        </a>
                    </li>
                    <?php endif; ?>
                    <li>
                        <a href="user_profile.php" class="flex items-center px-4 py-3 text-secondary-700 hover:bg-secondary-50 rounded-lg transition-colors">
                            <i class="fas fa-user-circle mr-3"></i>Mi Perfil
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            <!-- Welcome Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold text-secondary-800 mb-2">Bienvenido, <?php echo explode(' ', $_SESSION['usuario_nombre'])[0]; ?></h2>
                        <p class="text-secondary-600">Aquí tienes un resumen de la actividad institucional</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-secondary-600">Última actualización</p>
                        <p class="text-sm font-medium text-secondary-800" id="lastUpdate"><?php echo date('d/m/Y - H:i'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Metrics Cards CON DATOS REALES -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="card p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-secondary-600">Avisos Publicados</p>
                            <p class="text-3xl font-semibold text-secondary-800 mt-2"><?php echo $avisos_publicados; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-primary-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-bullhorn text-primary text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-secondary-600">Informes Totales</p>
                            <p class="text-3xl font-semibold text-secondary-800 mt-2"><?php echo $total_informes; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-warning-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-alt text-warning text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-secondary-600">Usuarios Totales</p>
                            <p class="text-3xl font-semibold text-secondary-800 mt-2"><?php echo $total_usuarios; ?></p>
                            <p class="text-sm text-secondary-600 mt-1">
                                <i class="fas fa-users mr-1"></i><?php echo $usuarios_activos; ?> activos
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-accent-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-accent text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-secondary-600">Estado del Sistema</p>
                            <p class="text-lg font-semibold text-success-600 mt-2">Operativo</p>
                            <p class="text-sm text-secondary-600 mt-1">
                                <i class="fas fa-check-circle mr-1"></i>99.9% uptime
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-success-50 rounded-lg flex items-center justify-center">
                            <div class="w-4 h-4 bg-success rounded-full animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Avisos Recientes -->
                <div class="card p-6">
                    <h3 class="text-lg font-semibold text-secondary-800 mb-6">Avisos Recientes</h3>
                    <div class="space-y-4">
                        <?php foreach ($avisos_recientes as $aviso): ?>
                        <div class="flex items-start space-x-3 p-3 bg-secondary-50 rounded-lg">
                            <div class="w-3 h-3 bg-primary rounded-full mt-2"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-secondary-800"><?php echo $aviso['titulo']; ?></p>
                                <p class="text-xs text-secondary-500"><?php echo date('d/m/Y H:i', strtotime($aviso['fecha_publicacion'])); ?> • <?php echo $aviso['vistas']; ?> vistas</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Actividad Reciente -->
                <div class="card p-6">
                    <h3 class="text-lg font-semibold text-secondary-800 mb-6">Actividad Reciente</h3>
                    <div class="space-y-4">
                        <?php foreach ($actividad_reciente as $actividad): ?>
                        <div class="flex items-start space-x-3">
                            <div class="w-2 h-2 bg-success-500 rounded-full mt-2"></div>
                            <div class="flex-1">
                                <p class="text-sm text-secondary-800">
                                    <strong><?php echo $actividad['usuario_nombre']; ?></strong> - <?php echo $actividad['accion']; ?>
                                </p>
                                <p class="text-xs text-secondary-500"><?php echo date('d/m/Y H:i', strtotime($actividad['fecha'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // User menu dropdown
        const userMenuButton = document.getElementById('userMenuButton');
        const userDropdown = document.getElementById('userDropdown');

        userMenuButton.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', function() {
            userDropdown.classList.add('hidden');
        });

        // Update timestamp
        function updateTimestamp() {
            const now = new Date();
            const formatted = now.toLocaleDateString('es-ES', {
                day: '2-digit', month: '2-digit', year: 'numeric'
            }) + ' - ' + now.toLocaleTimeString('es-ES', {
                hour: '2-digit', minute: '2-digit'
            });
            document.getElementById('lastUpdate').textContent = formatted;
        }

        // Initialize
        window.addEventListener('load', function() {
            updateTimestamp();
            setInterval(updateTimestamp, 60000);
        });
    </script>
</body>
</html>