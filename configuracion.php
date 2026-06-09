<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/nav.php';

// Crear tabla si no existe
$conn->query("CREATE TABLE IF NOT EXISTS configuracion (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(50) NOT NULL UNIQUE,
  valor TEXT DEFAULT NULL
)");

// Valores por defecto
$defaults = [
  'nombre_gym'     => 'Mi Gimnasio',
  'telefono_dueño' => '5493764000000',
  'dias_aviso'     => '3',
  'msg_vencido'    => 'Hola {nombre} 👋 Te recordamos que tu membresía está vencida. Comunicate con nosotros para renovarla. ¡Gracias! 💪',
  'msg_por_vencer' => 'Hola {nombre} 👋 Tu membresía vence pronto. ¡Renovála para seguir entrenando sin interrupciones! 💪',
  'msg_renovacion' => '✅ Hola {nombre}! Tu membresía fue renovada hasta el {fecha}. ¡A entrenar! 💪',
];
foreach ($defaults as $k => $v) {
  $kk = $conn->real_escape_string($k);
  $vv = $conn->real_escape_string($v);
  $conn->query("INSERT IGNORE INTO configuracion (clave, valor) VALUES ('{$kk}','{$vv}')");
}

$alerta = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($_POST as $k => $v) {
    $kk = $conn->real_escape_string($k);
    $vv = $conn->real_escape_string($v);
    $conn->query("INSERT INTO configuracion (clave,valor) VALUES ('{$kk}','{$vv}') ON DUPLICATE KEY UPDATE valor='{$vv}'");
  }
  $alerta = 'Configuración guardada correctamente.';
}

// Leer config
$config = [];
$res = $conn->query("SELECT clave, valor FROM configuracion");
while ($r = $res->fetch_assoc()) $config[$r['clave']] = $r['valor'];
function cfg($key, $config) { return htmlspecialchars($config[$key] ?? ''); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymFlow AI — Configuración</title>
<link rel="stylesheet" href="css/premium.css">
</head>
<body>
<div class="app-layout">
  <?php navBar('config'); ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <div class="topbar-title">⚙️ Configuración</div>
        <div class="topbar-sub">Personalizá el sistema para tu gimnasio</div>
      </div>
    </div>

    <div class="page">
      <?php if ($alerta): ?>
        <div class="alert alert-success mb-3">✅ <?= $alerta ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="grid-2 mb-3">

          <!-- INFO DEL GYM -->
          <div class="card">
            <div class="card-title">🏋️ Información del gym</div>
            <div class="form-group">
              <label class="form-label">Nombre del gimnasio</label>
              <input class="form-input" type="text" name="nombre_gym" value="<?= cfg('nombre_gym',$config) ?>" placeholder="PowerGym">
            </div>
            <div class="form-group">
              <label class="form-label">Teléfono del dueño (con código de país)</label>
              <input class="form-input" type="text" name="telefono_dueño" value="<?= cfg('telefono_dueño',$config) ?>" placeholder="5493764000000">
              <div style="font-size:.68rem;color:var(--muted);margin-top:.35rem">Formato: 549 + código de área + número. Ej: 5493764123456</div>
            </div>
            <div class="form-group">
              <label class="form-label">Días de anticipación para avisar</label>
              <select class="form-input" name="dias_aviso">
                <?php foreach([1,2,3,4,5,7] as $d): ?>
                  <option value="<?= $d ?>" <?= ($config['dias_aviso']??'3')==$d?'selected':'' ?>><?= $d ?> día<?= $d>1?'s':'' ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- MENSAJES -->
          <div class="card">
            <div class="card-title">💬 Mensajes automáticos</div>
            <div style="font-size:.72rem;color:var(--muted);margin-bottom:1rem">Usá <strong style="color:var(--primary)">{nombre}</strong> para el nombre del socio y <strong style="color:var(--primary)">{fecha}</strong> para la fecha.</div>
            <div class="form-group">
              <label class="form-label">Mensaje — membresía vencida</label>
              <textarea class="form-input" name="msg_vencido" rows="3" style="resize:vertical"><?= cfg('msg_vencido',$config) ?></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Mensaje — por vencer</label>
              <textarea class="form-input" name="msg_por_vencer" rows="3" style="resize:vertical"><?= cfg('msg_por_vencer',$config) ?></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Mensaje — confirmación de renovación</label>
              <textarea class="form-input" name="msg_renovacion" rows="3" style="resize:vertical"><?= cfg('msg_renovacion',$config) ?></textarea>
            </div>
          </div>
        </div>

        <button class="btn btn-primary btn-lg" type="submit">💾 Guardar configuración</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
