<?php
session_start();
if (isset($_SESSION['gymflow_user'])) { header('Location: automatizacion.php'); exit; }

$error = '';
define('USER', 'admin');
define('PASS', 'gym2026');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['usuario'] === USER && $_POST['password'] === PASS) {
        $_SESSION['gymflow_user'] = USER;
        header('Location: automatizacion.php'); exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymFlow AI — Ingresar</title>
<link rel="stylesheet" href="css/premium.css">
<style>
body {
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  background:var(--bg);
  background-image:
    radial-gradient(ellipse 60% 50% at 30% 20%, rgba(0,200,150,.04), transparent),
    radial-gradient(ellipse 50% 40% at 70% 80%, rgba(124,111,205,.04), transparent);
}
.login-card {
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:20px;
  padding:2.5rem;
  width:100%;
  max-width:400px;
  position:relative;
  overflow:hidden;
}
.login-card::before {
  content:'';
  position:absolute;
  top:0; left:0; right:0;
  height:2px;
  background:linear-gradient(90deg, var(--primary), var(--accent));
}
.login-logo {
  text-align:center;
  margin-bottom:2rem;
}
.login-logo .logo-text {
  font-size:1.8rem;
  font-weight:900;
  letter-spacing:3px;
  color:var(--primary);
  text-shadow:0 0 25px var(--primary-g);
}
.login-logo .logo-text span { color:var(--text); }
.login-logo p { color:var(--muted); font-size:.78rem; margin-top:.25rem; letter-spacing:.5px; }
</style>
</head>
<body>
<div class="login-card">
  <div class="login-logo">
    <div class="logo-text">GYM<span>FLOW</span></div>
    <p>Sistema de Gestión Inteligente</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error">⚠ <?= $error ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label class="form-label">Usuario</label>
      <input class="form-input" type="text" name="usuario" placeholder="admin" required autofocus>
    </div>
    <div class="form-group">
      <label class="form-label">Contraseña</label>
      <input class="form-input" type="password" name="password" placeholder="••••••••" required>
    </div>
    <button class="btn btn-primary btn-full btn-lg" type="submit" style="margin-top:.5rem">
      Ingresar →
    </button>
  </form>

  <p style="text-align:center; color:var(--muted); font-size:.68rem; margin-top:1.5rem;">
    admin · gym2026
  </p>
</div>
</body>
</html>
