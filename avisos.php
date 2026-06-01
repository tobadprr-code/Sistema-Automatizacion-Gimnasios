<?php
require_once 'includes/db.php';

// Traer socios vencidos y por vencer
$result = $conn->query("
    SELECT * FROM clientes 
    WHERE estado IN ('vencido', 'por_vencer')
    ORDER BY estado DESC, fecha_vencimiento ASC
");

$total_vencidos  = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='vencido'")->fetch_assoc()['t'];
$total_por_vencer = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='por_vencer'")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>GymFlow — Avisos</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#0d0d0d; color:#f0f0f0; font-family:'Segoe UI',sans-serif; padding:2rem; }
h1 { font-size:1.8rem; font-weight:800; margin-bottom:.25rem; }
.sub { color:#666; font-size:.9rem; margin-bottom:2rem; }

.stats { display:flex; gap:1rem; margin-bottom:2rem; flex-wrap:wrap; }
.stat { background:#161616; border:1px solid #222; border-radius:12px; padding:1.25rem 1.75rem; }
.stat-num { font-size:2.5rem; font-weight:900; line-height:1; }
.stat-label { font-size:.7rem; color:#666; text-transform:uppercase; letter-spacing:1px; margin-top:.25rem; }
.red  { color:#ff3333; }
.yellow { color:#ffcc00; }

.card { background:#161616; border:1px solid #222; border-radius:14px; padding:1.5rem; margin-bottom:1rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.card:hover { border-color:#333; }
.card.vencido  { border-left:3px solid #ff3333; }
.card.por_vencer { border-left:3px solid #ffcc00; }

.info-nombre { font-weight:700; font-size:1rem; margin-bottom:.2rem; }
.info-detalle { font-size:.78rem; color:#666; }
.info-detalle span { margin-right:1rem; }

.badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:.7rem; font-weight:700; letter-spacing:.5px; margin-bottom:.5rem; }
.badge.vencido   { background:rgba(255,51,51,.12); color:#ff3333; border:1px solid rgba(255,51,51,.3); }
.badge.por_vencer { background:rgba(255,204,0,.1); color:#ffcc00; border:1px solid rgba(255,204,0,.3); }

.btn-wa { display:inline-flex; align-items:center; gap:8px; background:#25D366; color:#fff; font-weight:700; font-size:.85rem; padding:.7rem 1.5rem; border-radius:8px; text-decoration:none; transition:background .15s; white-space:nowrap; }
.btn-wa:hover { background:#1cb254; }

.empty { text-align:center; padding:4rem; color:#444; }
.empty .icon { font-size:3rem; margin-bottom:1rem; }

.volver { display:inline-block; margin-bottom:1.5rem; color:#555; font-size:.85rem; text-decoration:none; }
.volver:hover { color:#f0f0f0; }

/* Botón ejecutar automatización */
.btn-auto { background:#1a1a1a; border:1px solid #333; color:#f0f0f0; padding:.75rem 1.5rem; border-radius:8px; cursor:pointer; font-size:.85rem; font-weight:600; text-decoration:none; display:inline-block; margin-bottom:2rem; transition:border-color .15s; }
.btn-auto:hover { border-color:#00ff88; color:#00ff88; }
</style>
</head>
<body>

<a href="automatizacion.php" class="volver">← Volver al panel</a>

<h1>📋 Avisos del día</h1>
<p class="sub">Socios que necesitan recordatorio hoy · <?= date('d/m/Y') ?></p>

<a href="cron_estados.php" class="btn-auto">⚡ Ejecutar automatización</a>

<div class="stats">
  <div class="stat">
    <div class="stat-num red"><?= $total_vencidos ?></div>
    <div class="stat-label">🔴 Vencidos</div>
  </div>
  <div class="stat">
    <div class="stat-num yellow"><?= $total_por_vencer ?></div>
    <div class="stat-label">🟡 Por vencer</div>
  </div>
</div>

<?php if ($result->num_rows === 0): ?>
  <div class="empty">
    <div class="icon">✅</div>
    <p>No hay socios con pagos pendientes hoy.</p>
  </div>
<?php else: ?>
  <?php while ($s = $result->fetch_assoc()):
    $nombre   = $s['nombre'];
    $primer_nombre = explode(' ', $nombre)[0];
    $telefono = $s['telefono'];
    $estado   = $s['estado'];
    $vence    = date('d/m/Y', strtotime($s['fecha_vencimiento']));

    // Mensaje automático según estado
    if ($estado === 'vencido') {
      $msg = "Hola {$primer_nombre} 👋 Te recordamos que tu membresía del gimnasio está vencida. Cuando puedas, comunicate con nosotros para renovarla. ¡Gracias! 💪";
      $label = "Vencido";
    } else {
      $msg = "Hola {$primer_nombre} 👋 Te avisamos que tu membresía vence pronto. ¡Renovála para seguir entrenando sin interrupciones! 💪";
      $label = "Por vencer";
    }

    $link_wa = "https://wa.me/{$telefono}?text=" . urlencode($msg);

    // Guardar aviso en historial
    $msg_escaped = $conn->real_escape_string($msg);
    $conn->query("
        INSERT INTO avisos_log (cliente_id, tipo_aviso, mensaje)
        VALUES ({$s['id']}, '{$estado}', '{$msg_escaped}')
    ");
  ?>
  <div class="card <?= $estado ?>">
    <div>
      <span class="badge <?= $estado ?>"><?= $label ?></span>
      <div class="info-nombre"><?= htmlspecialchars($nombre) ?></div>
      <div class="info-detalle">
        <span>📞 <?= $telefono ?></span>
        <span>📦 <?= ucfirst($s['plan']) ?></span>
        <span>📅 Vence: <?= $vence ?></span>
      </div>
    </div>
    <a class="btn-wa" href="<?= $link_wa ?>" target="_blank">
      📲 Enviar aviso
    </a>
  </div>
  <?php endwhile; ?>
<?php endif; ?>

</body>
</html>