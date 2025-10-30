<?php
require_once 'conexion.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['username'];
    $password = $_POST['password'];
    $rol = $_POST['role'];

    // Buscar usuario en BD
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND rol = ? AND estado = 'active'");
    $stmt->execute([$email, $rol]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($password, $usuario['password'])) {
        // Iniciar sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_rol'] = $usuario['rol'];
        $_SESSION['usuario_departamento'] = $usuario['departamento'];
        
        // Actualizar último acceso
        $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?")->execute([$usuario['id']]);
        
        // Registrar actividad
        registrarActividad($usuario['id'], 'Inicio de sesión', 'login', 'Acceso al sistema');
        
        header('Location: admin_dashboard.php');
        exit();
    } else {
        $error = "Credenciales incorrectas o usuario inactivo";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - EduAdmin Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-primary-50 to-accent-50 min-h-screen">
    <div class="absolute inset-0 opacity-5">
        <svg class="w-full h-full" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                    <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5"/>
                </pattern>
            </defs>
            <rect width="100" height="100" fill="url(#grid)" />
        </svg>
    </div>

    <div class="relative min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary rounded-xl mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-semibold text-secondary-800 mb-2">EduAdmin Portal</h1>
                <p class="text-secondary-600">Sistema de Gestión Educativa</p>
            </div>

            <div class="card p-8 shadow-lg animate-fade-in">
                <div class="mb-6">
                    <h2 class="text-2xl font-semibold text-secondary-800 mb-2">Iniciar Sesión</h2>
                    <p class="text-secondary-600">Accede a tu cuenta institucional</p>
                </div>

                <form id="loginForm" method="POST" class="space-y-6">
                    <div>
                        <label for="role" class="block text-sm font-medium text-secondary-700 mb-2">
                            <i class="fas fa-user-tag mr-2 text-primary"></i>Tipo de Usuario
                        </label>
                        <select id="role" name="role" class="form-input" required>
                            <option value="">Seleccionar rol...</option>
                            <option value="admin">Administrador</option>
                            <option value="teacher">Profesor</option>
                        </select>
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-secondary-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-primary"></i>Correo Electrónico
                        </label>
                        <input type="email" id="username" name="username" class="form-input" placeholder="usuario@institucion.edu.es" required>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-secondary-700 mb-2">
                            <i class="fas fa-lock mr-2 text-primary"></i>Contraseña
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password" class="form-input pr-10" placeholder="Introduce tu contraseña" required>
                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-secondary-400 hover:text-secondary-600 transition-colors">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <?php if ($error): ?>
                    <div id="loginError" class="p-4 bg-error-50 border border-error-200 rounded-md">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-error-600 mr-2"></i>
                            <span class="text-sm text-error-600"><?php echo $error; ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="w-full btn-primary py-3 text-base font-medium flex items-center justify-center space-x-2 transition-all duration-200 hover:shadow-md">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Iniciar Sesión</span>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-secondary-600">
                        ¿Problemas para acceder? 
                        <a href="javascript:void(0)" class="text-primary hover:text-primary-700 transition-colors">Contacta con soporte</a>
                    </p>
                </div>
            </div>

            <div class="mt-8 text-center">
                <p class="text-sm text-secondary-500">
                    <strong>Credenciales de prueba:</strong><br>
                    Admin: admin@eduadmin.es / password<br>
                    Profesor: profesor@eduadmin.es / password
                </p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
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
        });

        // Real-time validation
        document.getElementById('username').addEventListener('input', function() {
            if (this.value && this.value.includes('@')) {
                document.getElementById('usernameError')?.classList.add('hidden');
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            if (this.value) {
                document.getElementById('passwordError')?.classList.add('hidden');
            }
        });
    </script>
</body>
</html>