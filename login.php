<?php
session_start();
if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/db.php';
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if ($usuario && password_verify($password, $usuario['password'])) {
        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email'],
            'rol' => $usuario['rol'],
            'avatar' => $usuario['avatar']
        ];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Email o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShieldDesk — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
 
</head>
<body>
<div class="login-wrap">
    <div class="login-logo">
        <span class="icon">🛡️</span>
        <h1>ShieldDesk</h1>
        <p>Plataforma de soporte en ciberseguridad</p>
    </div>
    <div class="login-card">
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-grupo">
                <label>Email</label>
                <input type="email" name="email" placeholder="rocio@shielddesk.com" required>
            </div>
            <div class="form-grupo">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="123456" required>
            </div>
            <button type="submit" class="btn-login">Ingresar</button>
        </form>
    </div>
    <p class="login-footer">© 2026 ShieldDesk</p>
</div>
</body>
</html>