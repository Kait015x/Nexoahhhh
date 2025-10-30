<?php
require_once 'conexion.php';
verificarAuth();

// Obtener datos del usuario actual
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $departamento = $_POST['departamento'];

    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, departamento = ? WHERE id = ?");
    $stmt->execute([$nombre, $email, $telefono, $departamento, $_SESSION['usuario_id']]);
    
    // Actualizar sesión
    $_SESSION['usuario_nombre'] = $nombre;
    $_SESSION['usuario_email'] = $email;
    
    registrarActividad($_SESSION['usuario_id'], 'Actualizó perfil de usuario', 'update');
    
    header('Location: user_profile.php?success=1');
    exit();
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'];
    $nueva_password = $_POST['nueva_password'];
    $confirmar_password = $_POST['confirmar_password'];

    // Verificar contraseña actual
    if (password_verify($password_actual, $usuario['password'])) {
        if ($nueva_password === $confirmar_password) {
            if (strlen($nueva_password) >= 8) {
                $nueva_password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                $stmt->execute([$nueva_password_hash, $_SESSION['usuario_id']]);
                
                registrarActividad($_SESSION['usuario_id'], 'Cambió contraseña', 'update');
                
                header('Location: user_profile.php?success=2');
                exit();
            } else {
                $error_password = "La nueva contraseña debe tener al menos 8 caracteres";
            }
        } else {
            $error_password = "Las contraseñas no coinciden";
        }
    } else {
        $error_password = "La contraseña actual es incorrecta";
    }
}

// Obtener actividad reciente del usuario
$actividad_reciente = $pdo->prepare("
    SELECT * FROM actividad 
    WHERE usuario_id = ? 
    ORDER BY fecha DESC 
    LIMIT 5
");
$actividad_reciente->execute([$_SESSION['usuario_id']]);
$actividad = $actividad_reciente->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario - EduAdmin Portal</title>
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
                    <h2 class="text-3xl font-semibold text-secondary-800">Perfil de Usuario</h2>
                    <p class="text-secondary-600 mt-1">Gestiona tu información personal y preferencias de la cuenta</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-success-100 text-success-800">
                        <i class="fas fa-circle text-success-500 mr-2 text-xs"></i>
                        Perfil Activo
                    </span>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 p-4 bg-success-50 border border-success-200 rounded-md">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-success-600 mr-2"></i>
                <span class="text-sm text-success-600">
                    <?php echo $_GET['success'] == 1 ? 'Perfil actualizado exitosamente' : 'Contraseña cambiada exitosamente'; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Image and Quick Info -->
            <div class="lg:col-span-1">
                <div class="card p-6 mb-6">
                    <div class="text-center">
                        <div class="relative inline-block">
                            <img src="https://images.unsplash.com/photo-1652841236281-576b8cacee20" alt="Foto de perfil" class="w-32 h-32 rounded-full object-cover mx-auto border-4 border-white shadow-lg">
                        </div>
                        <h3 class="text-xl font-semibold text-secondary-800 mt-4"><?php echo $usuario['nombre']; ?></h3>
                        <p class="text-secondary-600"><?php echo $usuario['rol'] === 'admin' ? 'Administrador' : 'Profesor'; ?></p>
                        <p class="text-sm text-secondary-500 mt-1"><?php echo $usuario['email']; ?></p>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-secondary-200">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-secondary-600">Último acceso:</span>
                                <span class="text-secondary-800 font-medium">
                                    <?php echo $usuario['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'Nunca'; ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-secondary-600">Miembro desde:</span>
                                <span class="text-secondary-800 font-medium">
                                    <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-secondary-600">Departamento:</span>
                                <span class="text-secondary-800 font-medium">
                                    <?php echo $usuario['departamento'] ?: 'No asignado'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Summary -->
                <div class="card p-6">
                    <h4 class="text-lg font-semibold text-secondary-800 mb-4">
                        <i class="fas fa-chart-line text-primary mr-2"></i>
                        Actividad Reciente
                    </h4>
                    <div class="space-y-4">
                        <?php foreach ($actividad as $act): ?>
                        <div class="flex items-start space-x-3">
                            <div class="w-2 h-2 bg-success-500 rounded-full mt-2"></div>
                            <div class="flex-1">
                                <p class="text-sm text-secondary-800"><?php echo $act['accion']; ?></p>
                                <p class="text-xs text-secondary-500"><?php echo date('d/m/Y H:i', strtotime($act['fecha'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Profile Form and Settings -->
            <div class="lg:col-span-2">
                <!-- Tabs Navigation -->
                <div class="card mb-6">
                    <div class="border-b border-secondary-200">
                        <nav class="flex space-x-8 px-6">
                            <button class="tab-button active py-4 px-1 border-b-2 font-medium text-sm transition-colors" data-tab="personal">
                                <i class="fas fa-user mr-2"></i>Información Personal
                            </button>
                            <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm transition-colors" data-tab="security">
                                <i class="fas fa-shield-alt mr-2"></i>Seguridad
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Personal Information Tab -->
                <div id="personalTab" class="tab-content">
                    <div class="card p-6">
                        <h3 class="text-xl font-semibold text-secondary-800 mb-6">Información Personal</h3>
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="actualizar_perfil" value="1">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-2">
                                        <i class="fas fa-user mr-2 text-primary"></i>Nombre Completo
                                    </label>
                                    <input type="text" name="nombre" value="<?php echo $usuario['nombre']; ?>" class="form-input" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-2">
                                        <i class="fas fa-envelope mr-2 text-primary"></i>Correo Electrónico
                                    </label>
                                    <input type="email" name="email" value="<?php echo $usuario['email']; ?>" class="form-input" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-2">
                                        <i class="fas fa-phone mr-2 text-primary"></i>Teléfono
                                    </label>
                                    <input type="tel" name="telefono" value="<?php echo $usuario['telefono']; ?>" class="form-input">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-2">
                                        <i class="fas fa-building mr-2 text-primary"></i>Departamento
                                    </label>
                                    <select name="departamento" class="form-input">
                                        <option value="">Seleccionar departamento...</option>
                                        <option value="Administración" <?php echo $usuario['departamento'] == 'Administración' ? 'selected' : ''; ?>>Administración</option>
                                        <option value="Matemáticas" <?php echo $usuario['departamento'] == 'Matemáticas' ? 'selected' : ''; ?>>Matemáticas</option>
                                        <option value="Ciencias" <?php echo $usuario['departamento'] == 'Ciencias' ? 'selected' : ''; ?>>Ciencias</option>
                                        <option value="Literatura" <?php echo $usuario['departamento'] == 'Literatura' ? 'selected' : ''; ?>>Literatura</option>
                                        <option value="Historia" <?php echo $usuario['departamento'] == 'Historia' ? 'selected' : ''; ?>>Historia</option>
                                        <option value="Tecnología" <?php echo $usuario['departamento'] == 'Tecnología' ? 'selected' : ''; ?>>Tecnología</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-4 pt-6">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security Tab -->
                <div id="securityTab" class="tab-content hidden">
                    <div class="space-y-6">
                        <!-- Change Password -->
                        <div class="card p-6">
                            <h3 class="text-xl font-semibold text-secondary-800 mb-6">Cambiar Contraseña</h3>
                            <form method="POST" class="space-y-6">
                                <input type="hidden" name="cambiar_password" value="1">
                                
                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-2">
                                        <i class="fas fa-lock mr-2 text-primary"></i>Contraseña Actual
                                    </label>
                                    <input type="password" name="password_actual" class="form-input" required>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-secondary-700 mb-2">
                                            <i class="fas fa-key mr-2 text-primary"></i>Nueva Contraseña
                                        </label>
                                        <input type="password" name="nueva_password" class="form-input" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-secondary-700 mb-2">
                                            <i class="fas fa-check mr-2 text-primary"></i>Confirmar Contraseña
                                        </label>
                                        <input type="password" name="confirmar_password" class="form-input" required>
                                    </div>
                                </div>
                                
                                <?php if (isset($error_password)): ?>
                                <div class="p-4 bg-error-50 border border-error-200 rounded-md">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle text-error-600 mr-2"></i>
                                        <span class="text-sm text-error-600"><?php echo $error_password; ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-2">
                                    <div class="text-xs text-secondary-500">
                                        <p>La contraseña debe contener:</p>
                                        <ul class="list-disc list-inside mt-1 space-y-1">
                                            <li>Al menos 8 caracteres</li>
                                            <li>Una letra mayúscula</li>
                                            <li>Una letra minúscula</li>
                                            <li>Un número</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="submit" class="btn-primary">
                                        <i class="fas fa-save mr-2"></i>Actualizar Contraseña
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Tab functionality
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabName = button.getAttribute('data-tab');
                
                // Remove active class from all buttons and contents
                tabButtons.forEach(btn => {
                    btn.classList.remove('active', 'border-primary', 'text-primary');
                    btn.classList.add('border-transparent', 'text-secondary-500', 'hover:text-secondary-700', 'hover:border-secondary-300');
                });
                tabContents.forEach(content => content.classList.add('hidden'));
                
                // Add active class to clicked button and show corresponding content
                button.classList.add('active', 'border-primary', 'text-primary');
                button.classList.remove('border-transparent', 'text-secondary-500', 'hover:text-secondary-700', 'hover:border-secondary-300');
                document.getElementById(tabName + 'Tab').classList.remove('hidden');
            });
        });
    </script>
</body>
</html>