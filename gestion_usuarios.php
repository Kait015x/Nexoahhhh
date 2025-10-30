<?php
require_once 'conexion.php';
verificarAuth();
soloAdmin(); // Solo administradores pueden acceder

// Procesar crear usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    $departamento = $_POST['departamento'];
    $telefono = $_POST['telefono'];

    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, departamento, telefono, estado) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$nombre, $email, $password, $rol, $departamento, $telefono]);
    
    registrarActividad($_SESSION['usuario_id'], 'Creó usuario: ' . $nombre, 'create');
    
    header('Location: gestion_usuarios.php?success=1');
    exit();
}

// Procesar eliminar usuario
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    // No permitir eliminar el propio usuario
    if ($id != $_SESSION['usuario_id']) {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        
        registrarActividad($_SESSION['usuario_id'], 'Eliminó usuario ID: ' . $id, 'delete');
        
        header('Location: gestion_usuarios.php?success=2');
        exit();
    }
}

// Procesar cambiar estado
if (isset($_GET['cambiar_estado'])) {
    $id = $_GET['cambiar_estado'];
    $nuevo_estado = $_GET['estado'];
    
    $stmt = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $id]);
    
    registrarActividad($_SESSION['usuario_id'], 'Cambió estado de usuario ID: ' . $id . ' a ' . $nuevo_estado, 'update');
    
    header('Location: gestion_usuarios.php?success=3');
    exit();
}

// Obtener usuarios REALES
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY fecha_registro DESC")->fetchAll();

// Estadísticas
$total_usuarios = count($usuarios);
$admin_usuarios = array_filter($usuarios, fn($u) => $u['rol'] == 'admin');
$teacher_usuarios = array_filter($usuarios, fn($u) => $u['rol'] == 'teacher');
$active_usuarios = array_filter($usuarios, fn($u) => $u['estado'] == 'active');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - EduAdmin Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-background min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-secondary-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-8">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                            </svg>
                        </div>
                        <h1 class="text-xl font-semibold text-secondary-800">EduAdmin Portal</h1>
                    </div>
                    
                    <nav class="hidden md:flex space-x-6">
                        <a href="admin_dashboard.php" class="text-secondary-600 hover:text-primary transition-colors duration-200 flex items-center space-x-2">
                            <i class="fas fa-tachometer-alt text-sm"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="gestion_avisos.php" class="text-secondary-600 hover:text-primary transition-colors duration-200 flex items-center space-x-2">
                            <i class="fas fa-bullhorn text-sm"></i>
                            <span>Avisos</span>
                        </a>
                        <a href="gestion_informes.php" class="text-secondary-600 hover:text-primary transition-colors duration-200 flex items-center space-x-2">
                            <i class="fas fa-file-alt text-sm"></i>
                            <span>Informes</span>
                        </a>
                        <a href="gestion_usuarios.php" class="text-primary border-b-2 border-primary flex items-center space-x-2">
                            <i class="fas fa-users text-sm"></i>
                            <span>Usuarios</span>
                        </a>
                    </nav>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-3">
                        <img src="https://images.unsplash.com/photo-1689351355812-05c02e711724" alt="Foto de perfil" class="w-8 h-8 rounded-full object-cover">
                        <div class="hidden sm:block">
                            <p class="text-sm font-medium text-secondary-800"><?php echo $_SESSION['usuario_nombre']; ?></p>
                            <p class="text-xs text-secondary-500">Administrador</p>
                        </div>
                    </div>
                    <a href="logout.php" class="text-secondary-400 hover:text-secondary-600 transition-colors duration-200">
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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-3xl font-semibold text-secondary-800 mb-2">Gestión de Usuarios</h2>
                    <p class="text-secondary-600">Administra cuentas de usuarios y controla el acceso al sistema</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <button onclick="abrirModalCrear()" class="btn-primary flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Crear Usuario</span>
                    </button>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 p-4 bg-success-50 border border-success-200 rounded-md">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-success-600 mr-2"></i>
                <span class="text-sm text-success-600">
                    <?php 
                    switch($_GET['success']) {
                        case 1: echo 'Usuario creado exitosamente'; break;
                        case 2: echo 'Usuario eliminado exitosamente'; break;
                        case 3: echo 'Estado de usuario actualizado'; break;
                    }
                    ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-primary-50 rounded-lg">
                        <i class="fas fa-users text-primary text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-600">Total Usuarios</p>
                        <p class="text-2xl font-semibold text-secondary-800"><?php echo $total_usuarios; ?></p>
                    </div>
                </div>
            </div>
            <div class="card p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-success-50 rounded-lg">
                        <i class="fas fa-user-check text-success-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-600">Activos</p>
                        <p class="text-2xl font-semibold text-secondary-800"><?php echo count($active_usuarios); ?></p>
                    </div>
                </div>
            </div>
            <div class="card p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-accent-50 rounded-lg">
                        <i class="fas fa-chalkboard-teacher text-accent-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-600">Profesores</p>
                        <p class="text-2xl font-semibold text-secondary-800"><?php echo count($teacher_usuarios); ?></p>
                    </div>
                </div>
            </div>
            <div class="card p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-warning-50 rounded-lg">
                        <i class="fas fa-user-cog text-warning-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-600">Administradores</p>
                        <p class="text-2xl font-semibold text-secondary-800"><?php echo count($admin_usuarios); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-secondary-200">
                    <thead class="bg-secondary-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Usuario</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Rol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Departamento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Último Acceso</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-secondary-200">
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr class="hover:bg-secondary-50 transition-colors duration-200">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e" alt="Foto de perfil" class="w-10 h-10 rounded-full object-cover mr-3">
                                    <div>
                                        <p class="text-sm font-medium text-secondary-800"><?php echo $usuario['nombre']; ?></p>
                                        <p class="text-sm text-secondary-500"><?php echo $usuario['email']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $usuario['rol'] == 'admin' ? 'bg-warning-100 text-warning-800' : 'bg-accent-100 text-accent-800'; ?>">
                                    <i class="fas <?php echo $usuario['rol'] == 'admin' ? 'fa-user-cog' : 'fa-chalkboard-teacher'; ?> mr-1"></i>
                                    <?php echo $usuario['rol'] == 'admin' ? 'Administrador' : 'Profesor'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php echo $usuario['estado'] == 'active' ? 'bg-success-100 text-success-800' : 
                                           ($usuario['estado'] == 'suspended' ? 'bg-error-100 text-error-800' : 'bg-warning-100 text-warning-800'); ?>">
                                    <div class="w-2 h-2 rounded-full <?php echo $usuario['estado'] == 'active' ? 'bg-success-500' : 
                                           ($usuario['estado'] == 'suspended' ? 'bg-error-500' : 'bg-warning-500'); ?> mr-2"></div>
                                    <?php echo $usuario['estado'] == 'active' ? 'Activo' : 
                                           ($usuario['estado'] == 'suspended' ? 'Suspendido' : 'Pendiente'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-secondary-600">
                                <?php echo $usuario['departamento'] ?: 'No asignado'; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-secondary-600">
                                <?php echo $usuario['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'Nunca'; ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                    <button onclick="cambiarEstado(<?php echo $usuario['id']; ?>, '<?php echo $usuario['estado'] == 'active' ? 'suspended' : 'active'; ?>')" 
                                            class="text-<?php echo $usuario['estado'] == 'active' ? 'warning' : 'success'; ?>-600 hover:text-<?php echo $usuario['estado'] == 'active' ? 'warning' : 'success'; ?>-800"
                                            title="<?php echo $usuario['estado'] == 'active' ? 'Suspender' : 'Activar'; ?>">
                                        <i class="fas <?php echo $usuario['estado'] == 'active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                    </button>
                                    <button onclick="eliminarUsuario(<?php echo $usuario['id']; ?>)" 
                                            class="text-error-600 hover:text-error-800" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <span class="text-secondary-400 text-xs">Usuario actual</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Crear Usuario -->
    <div id="modalCrear" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-secondary-800">Crear Usuario</h3>
                    <button onclick="cerrarModalCrear()" class="text-secondary-400 hover:text-secondary-600 transition-colors duration-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="crear_usuario" value="1">
                    
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-secondary-700 mb-2">
                            <i class="fas fa-user mr-2 text-primary"></i>Nombre Completo
                        </label>
                        <input type="text" id="nombre" name="nombre" class="form-input" placeholder="Ej: Ana García López" required>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-secondary-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-primary"></i>Correo Electrónico
                        </label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="usuario@institucion.edu.es" required>
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-secondary-700 mb-2">
                            <i class="fas fa-lock mr-2 text-primary"></i>Contraseña
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password" class="form-input pr-10" placeholder="Mínimo 8 caracteres" required>
                            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-secondary-400 hover:text-secondary-600 transition-colors">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <label for="rol" class="block text-sm font-medium text-secondary-700 mb-2">
                            <i class="fas fa-user-tag mr-2 text-primary"></i>Rol
                        </label>
                        <select id="rol" name="rol" class="form-input" required>
                            <option value="admin">Administrador</option>
                            <option value="teacher" selected>Profesor</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="departamento" class="block text-sm font-medium text-secondary-700 mb-2">
                            <i class="fas fa-building mr-2 text-primary"></i>Departamento
                        </label>
                        <select id="departamento" name="departamento" class="form-input">
                            <option value="">Seleccionar departamento...</option>
                            <option value="Administración">Administración</option>
                            <option value="Matemáticas">Matemáticas</option>
                            <option value="Ciencias">Ciencias</option>
                            <option value="Literatura">Literatura</option>
                            <option value="Historia">Historia</option>
                            <option value="Tecnología">Tecnología</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-secondary-700 mb-2">
                            <i class="fas fa-phone mr-2 text-primary"></i>Teléfono
                        </label>
                        <input type="tel" id="telefono" name="telefono" class="form-input" placeholder="+34 612 345 678">
                    </div>
                    
                    <div class="flex items-center justify-end space-x-3 pt-6 border-t border-secondary-200">
                        <button type="button" onclick="cerrarModalCrear()" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary flex items-center space-x-2">
                            <i class="fas fa-save"></i>
                            <span>Crear Usuario</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirModalCrear() {
            document.getElementById('modalCrear').classList.remove('hidden');
        }
        
        function cerrarModalCrear() {
            document.getElementById('modalCrear').classList.add('hidden');
        }
        
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
        
        function eliminarUsuario(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
                window.location.href = 'gestion_usuarios.php?eliminar=' + id;
            }
        }
        
        function cambiarEstado(id, nuevoEstado) {
            const accion = nuevoEstado === 'active' ? 'activar' : 'suspender';
            if (confirm(`¿Estás seguro de que deseas ${accion} este usuario?`)) {
                window.location.href = `gestion_usuarios.php?cambiar_estado=${id}&estado=${nuevoEstado}`;
            }
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