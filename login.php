<?php
session_start();

// Si ya está logueado, redirigir al panel de automatización
if (isset($_SESSION['gymflow_user'])) {
    header('Location: automatizacion.php');
    exit;
}

$error = '';

// Credenciales del gimnasio
define('USER', 'admin');
define('PASS', 'gym2026');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['usuario'] === USER && $_POST['password'] === PASS) {
        $_SESSION['gymflow_user'] = USER;
        header('Location: automatizacion.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymFlow — Ingresar</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
  background:#0a0a0a; color:#f0f0f0;
  font-family:'Segoe UI',sans-serif;
  min-height:100vh; display:flex; align-items:center; justify-content:center;
  background-image: radial-gradient(ellipse 60% 40% at 50% 0%, rgba(0,255,136,.06), transparent);
}
.box {
  background:#141414; border:1px solid #222; border-radius:20px;
  padding:2.5rem; width:100%; max-width:380px;
}
.logo { text-align:center; font-size:2rem; font-weight:900; letter-spacing:3px; color:#00ff88; margin-bottom:.25rem; }
.logo span { color:#f0f0f0; }
.sub { text-align:center; color:#555; font-size:.8rem; margin-bottom:2rem; }
label { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:1px; color:#666; margin-bottom:.4rem; margin-top:1rem; }
input {
  width:100%; background:#0d0d0d; border:1px solid #2a2a2a; border-radius:8px;
  color:#f0f0f0; padding:.875rem 1rem; font-size:.9rem; outline:none; transition:border-color .15s;
}
input:focus { border-color:#00ff88; }
.error { background:rgba(255,51,51,.1); border:1px solid rgba(255,51,51,.3); color:#ff4444; border-radius:8px; padding:.75rem 1rem; font-size:.82rem; margin-top:1rem; }
.btn {
  width:100%; background:#00ff88; color:#000; font-weight:800; font-size:1rem;
  border:none; border-radius:10px; padding:1rem; cursor:pointer; margin-top:1.5rem;
  transition:all .2s; box-shadow:0 0 20px rgba(0,255,136,.2);
}
.btn:hover { box-shadow:0 0 35px rgba(0,255,136,.4); transform:translateY(-1px); }
.hint { text-align:center; color:#444; font-size:.72rem; margin-top:1.25rem; }
</style>
</head>
<body>
<div class="box">
  <div class="logo">GYM<span>FLOW</span></div>
  <p class="sub">Sistema de Gestión de Membresías</p>

  <?php if ($error): ?>
    <div class="error">⚠ <?= $error ?></div>
  <?php endif; ?>

  <form method="POST">
    <label>Usuario</label>
    <input type="text" name="usuario" placeholder="admin" required autofocus>
    <label>Contraseña</label>
    <input type="password" name="password" placeholder="••••••••" required>
    <button class="btn" type="submit">Ingresar →</button>
  </form>

  <p class="hint">Usuario: admin &nbsp;·&nbsp; Contraseña: gym2026</p>
</div>
</body>
</html>