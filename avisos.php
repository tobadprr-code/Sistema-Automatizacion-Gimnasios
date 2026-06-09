<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/nav.php';

$hoy = date('Y-m-d');

// Traer vencidos y por vencer ordenados por urgencia (más días vencido primero)
// Y marcar cuáles ya recibieron aviso esta semana (anti-spam)
$result = $conn->query("
    SELECT *,
      DATEDIFF('$hoy', fecha_vencimiento) as dias_vencido,
      DATEDIFF('$hoy', ultimo_aviso) as dias_desde_aviso
    FROM clientes
    WHERE estado IN ('vencido','por_vencer')
    ORDER BY dias_vencido DESC, estado DESC
");

$total_vencidos   = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='vencido'")->fetch_assoc()['t'];
$total_por_vencer = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='por_vencer'")->fetch_assoc()['t'];
$ya_avisados      = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado IN ('vencido','por_vencer') AND ultimo_aviso IS NOT NULL AND DATEDIFF('$hoy', ultimo_aviso) < 4")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymFlow AI — Avisos</title>
<link rel="stylesheet" href="css/premium.css">
</head>
<body>
<div class="app-layout">
  <?php navBar('avisos'); ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <div class="topbar-title">📲 Avisos del día</div>
        <div class="topbar-sub"><?= date('l d/m/Y') ?> · Ordenados por urgencia</div>
      </div>
      <div class="topbar-right">
        <a href="cron_estados.php" class="btn btn-outline">⚡ Ejecutar automatización</a>
      </div>
    </div>

    <div class="page">

      <!-- STATS -->
      <div class="stats-grid mb-4">
        <div class="stat-card red">
          <div class="stat-icon">🔴</div>
          <div class="stat-num"><?= $total_vencidos ?></div>
          <div class="stat-label">Vencidos</div>
        </div>
        <div class="stat-card yellow">
          <div class="stat-icon">⏰</div>
          <div class="stat-num"><?= $total_por_vencer ?></div>
          <div class="stat-label">Por vencer</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon">📤</div>
          <div class="stat-num"><?= $total_vencidos + $total_por_vencer - $ya_avisados ?></div>
          <div class="stat-label">Listos para avisar</div>
        </div>
        <div class="stat-card purple">
          <div class="stat-icon">🛡️</div>
          <div class="stat-num"><?= $ya_avisados ?></div>
          <div class="stat-label">Anti-spam activo</div>
        </div>
      </div>

      <?php if ($result->num_rows === 0): ?>
        <div class="card" style="text-align:center;padding:4rem">
          <div style="font-size:3rem;margin-bottom:1rem">✅</div>
          <div style="font-size:1rem;font-weight:700;margin-bottom:.5rem">Todo al día</div>
          <div class="text-muted text-sm">No hay socios con pagos pendientes hoy.</div>
        </div>
      <?php else: ?>

        <?php while ($s = $result->fetch_assoc()):
          $fn    = explode(' ', $s['nombre'])[0];
          $estado = $s['estado'];
          $dias_v = (int)$s['dias_vencido'];
          $dias_a = $s['dias_desde_aviso'];
          $puede_avisar = ($s['ultimo_aviso'] === null || (int)$dias_a >= 4);

          // Calcular texto de días
          if ($estado === 'vencido') {
              if ($dias_v === 0) $dias_txt = 'Venció hoy';
              elseif ($dias_v === 1) $dias_txt = 'Vencido hace 1 día';
              else $dias_txt = "Vencido hace {$dias_v} días";
              $msg = "Hola {$fn} 👋 Te recordamos que tu membresía del gimnasio está vencida. Cuando puedas, comunicate con nosotros para renovarla. ¡Gracias! 💪";
          } else {
              $dias_rest = abs($dias_v);
              $dias_txt = $dias_rest <= 1 ? 'Vence mañana' : "Vence en {$dias_rest} días";
              $msg = "Hola {$fn} 👋 Te avisamos que tu membresía vence pronto. ¡Renovála para seguir entrenando sin interrupciones! 💪";
          }

          $link_wa = "https://wa.me/{$s['telefono']}?text=" . urlencode($msg);

          // Guardar en historial si corresponde (anti-spam)
          if ($puede_avisar) {
              $msg_e = $conn->real_escape_string($msg);
              $conn->query("INSERT INTO avisos_log (cliente_id, tipo_aviso, mensaje) VALUES ({$s['id']}, '{$estado}', '{$msg_e}')");
              $conn->query("UPDATE clientes SET ultimo_aviso='$hoy', aviso_conteo=aviso_conteo+1 WHERE id={$s['id']}");
          }

          $border_color = $estado === 'vencido' ? 'var(--red)' : 'var(--yellow)';
        ?>
        <div style="background:var(--surface);border:1px solid var(--border);border-left:3px solid <?= $border_color ?>;border-radius:14px;padding:1.25rem 1.5rem;margin-bottom:.875rem;display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap;transition:border-color .15s">

          <!-- Info -->
          <div style="flex:1;min-width:200px">
            <div style="display:flex;align-items:center;gap:.625rem;margin-bottom:.4rem">
              <span class="badge <?= $estado==='vencido' ? 'badge-red' : 'badge-yellow' ?>">
                <span class="badge-dot"></span>
                <?= $estado==='vencido' ? 'Vencido' : 'Por vencer' ?>
              </span>
              <?php if (!$puede_avisar): ?>
                <span class="badge badge-purple" title="Ya recibió aviso hace <?= $dias_a ?> días">🛡️ Anti-spam</span>
              <?php endif; ?>
            </div>
            <div style="font-weight:700;font-size:.95rem;margin-bottom:.3rem;color:var(--text)"><?= htmlspecialchars($s['nombre']) ?></div>
            <div style="font-size:.75rem;color:var(--muted);display:flex;gap:1rem;flex-wrap:wrap">
              <span>📞 <?= $s['telefono'] ?></span>
              <span>📦 <?= ucfirst($s['plan']) ?></span>
              <span>📅 <?= date('d/m/Y', strtotime($s['fecha_vencimiento'])) ?></span>
              <?php if ($s['ultimo_aviso']): ?>
                <span>💬 Último aviso: <?= date('d/m', strtotime($s['ultimo_aviso'])) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Días counter -->
          <div style="text-align:center;padding:.625rem 1rem;background:var(--surface2);border-radius:10px;border:1px solid var(--border);min-width:100px">
            <div style="font-size:1.6rem;font-weight:900;color:<?= $estado==='vencido' ? 'var(--red)' : 'var(--yellow)' ?>;line-height:1"><?= $dias_v >= 0 ? $dias_v : abs($dias_v) ?></div>
            <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-top:2px"><?= $estado==='vencido' ? 'días vencido' : 'días para vencer' ?></div>
          </div>

          <!-- Acciones -->
          <div style="display:flex;flex-direction:column;gap:.5rem">
            <?php if ($puede_avisar): ?>
              <a href="<?= $link_wa ?>" target="_blank" class="btn btn-wa">📲 Enviar aviso</a>
            <?php else: ?>
              <button class="btn btn-outline" disabled title="Ya avisado hace <?= $dias_a ?> días · esperá <?= 4 - (int)$dias_a ?> día(s) más">🛡️ Ya avisado</button>
            <?php endif; ?>
            <a href="renovar.php" class="btn btn-outline" style="font-size:.75rem;padding:.45rem .875rem">🔄 Renovar</a>
          </div>
        </div>
        <?php endwhile; ?>

      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
