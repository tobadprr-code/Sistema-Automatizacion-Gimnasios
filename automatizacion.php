<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/nav.php';

$total      = $conn->query("SELECT COUNT(*) as t FROM clientes")->fetch_assoc()['t'];
$activos    = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='activo'")->fetch_assoc()['t'];
$por_vencer = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='por_vencer'")->fetch_assoc()['t'];
$vencidos   = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='vencido'")->fetch_assoc()['t'];
$pendientes_count = $vencidos + $por_vencer;

// Última ejecución
$ultima = $conn->query("SELECT MAX(fecha_envio) as t FROM avisos_log")->fetch_assoc()['t'];
$ultima_txt = $ultima ? date('d/m/Y H:i', strtotime($ultima)) : 'Nunca ejecutado';

// Historial
$historial = $conn->query("
    SELECT a.*, c.nombre, c.plan FROM avisos_log a
    JOIN clientes c ON a.cliente_id = c.id
    ORDER BY a.fecha_envio DESC LIMIT 8
");

// Pendientes
$pendientes = $conn->query("
    SELECT *, DATEDIFF(CURDATE(), fecha_vencimiento) as dias_diff
    FROM clientes WHERE estado IN ('vencido','por_vencer')
    ORDER BY dias_diff DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymFlow AI — Automatización</title>
<link rel="stylesheet" href="css/premium.css">
</head>
<body>
<div class="app-layout">
  <?php navBar('automatizacion'); ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <div class="topbar-title">⚡ Panel de Automatización</div>
        <div class="topbar-sub">Última ejecución: <?= $ultima_txt ?></div>
      </div>
      <div class="topbar-right">
        <a href="resumen_whatsapp.php" class="btn btn-outline">💬 Resumen WA</a>
        <a href="exportar.php" class="btn btn-outline">📥 CSV</a>
        <span class="topbar-badge">LIVE</span>
      </div>
    </div>

    <div class="page">

      <!-- STATS -->
      <div class="stats-grid mb-4">
        <div class="stat-card white">
          <div class="stat-icon">👥</div>
          <div class="stat-num"><?= $total ?></div>
          <div class="stat-label">Total socios</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon">✅</div>
          <div class="stat-num"><?= $activos ?></div>
          <div class="stat-label">Al día</div>
        </div>
        <div class="stat-card yellow">
          <div class="stat-icon">⏰</div>
          <div class="stat-num"><?= $por_vencer ?></div>
          <div class="stat-label">Por vencer</div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon">🔴</div>
          <div class="stat-num"><?= $vencidos ?></div>
          <div class="stat-label">Vencidos</div>
        </div>
      </div>

      <div class="grid-2-wide mb-3">

        <!-- PANEL IZQUIERDO -->
        <div style="display:flex;flex-direction:column;gap:1.25rem">

          <!-- BOTÓN AUTOMATIZACIÓN -->
          <div class="card">
            <div class="card-title"><span class="live"></span>Motor de automatización</div>
            <button class="btn btn-primary btn-full btn-lg mb-2" id="btnAuto" onclick="ejecutar()">
              <span id="btn-icon">⚡</span>
              <span id="btn-txt">EJECUTAR AUTOMATIZACIÓN</span>
            </button>
            <div class="log-box mb-2" id="logBox">
              <div class="log-info">// Sistema listo · <?= date('d/m/Y H:i') ?></div>
              <div class="log-info">// Presioná el botón para analizar membresías...</div>
            </div>
            <div class="prog-bar mb-3"><div class="prog-fill green" id="progFill" style="width:0%"></div></div>

            <!-- RESUMEN COPIABLE -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem">
              <span class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:1px">Resumen del día</span>
              <button class="btn btn-ghost" style="font-size:.72rem;padding:.35rem .75rem" onclick="copiar()">📋 Copiar</button>
            </div>
            <div id="resumenBox" style="background:#030308;border:1px solid var(--border);border-left:2px solid var(--primary);border-radius:8px;padding:.875rem 1rem;font-family:monospace;font-size:.75rem;line-height:1.9;color:var(--text2);white-space:pre-line;display:none;min-height:60px"></div>
          </div>

          <!-- ACCESOS RÁPIDOS -->
          <div class="card">
            <div class="card-title">Accesos rápidos</div>
            <div class="grid-2" style="gap:.6rem">
              <a href="avisos.php"          class="btn btn-wa">📲 Ver avisos WhatsApp</a>
              <a href="renovar.php"         class="btn btn-outline">🔄 Renovar membresías</a>
              <a href="estadisticas.php"    class="btn btn-outline">📈 Estadísticas</a>
              <a href="configuracion.php"   class="btn btn-outline">⚙️ Configuración</a>
            </div>
          </div>
        </div>

        <!-- PANEL DERECHO — PENDIENTES -->
        <div class="card" style="overflow-y:auto;max-height:600px">
          <div class="card-title"><span class="live"></span>Pendientes hoy (<?= $pendientes_count ?>)</div>
          <?php if ($pendientes_count === 0): ?>
            <div style="text-align:center;padding:2rem;color:var(--muted)">
              <div style="font-size:2rem;margin-bottom:.75rem">✅</div>
              <div style="font-size:.85rem">Todo al día</div>
            </div>
          <?php else: ?>
            <?php while ($s = $pendientes->fetch_assoc()):
              $fn  = explode(' ', $s['nombre'])[0];
              $dias = (int)$s['dias_diff'];
              if ($s['estado'] === 'vencido') {
                  $dias_txt = $dias === 0 ? 'Venció hoy' : "Hace {$dias}d";
                  $msg = "Hola {$fn} 👋 Tu membresía está vencida. Comunicate con nosotros para renovarla. ¡Gracias! 💪";
              } else {
                  $dias_rest = abs($dias);
                  $dias_txt = "Vence en {$dias_rest}d";
                  $msg = "Hola {$fn} 👋 Tu membresía vence pronto. ¡Renovála para seguir entrenando! 💪";
              }
              $link = "https://wa.me/{$s['telefono']}?text=" . urlencode($msg);
            ?>
            <div style="display:flex;align-items:center;gap:.875rem;padding:.875rem;background:var(--surface2);border-radius:10px;margin-bottom:.6rem;border:1px solid var(--border);border-left:2px solid <?= $s['estado']==='vencido' ? 'var(--red)' : 'var(--yellow)' ?>">
              <div style="flex:1">
                <div style="font-weight:600;font-size:.88rem;margin-bottom:2px"><?= htmlspecialchars($s['nombre']) ?></div>
                <div style="font-size:.72rem;color:var(--muted)"><?= ucfirst($s['plan']) ?> · <?= $dias_txt ?></div>
              </div>
              <span class="badge <?= $s['estado']==='vencido' ? 'badge-red' : 'badge-yellow' ?>">
                <span class="badge-dot"></span>
                <?= $dias_txt ?>
              </span>
              <a href="<?= $link ?>" target="_blank" class="btn btn-wa" style="padding:.4rem .75rem;font-size:.72rem">📲</a>
            </div>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- HISTORIAL -->
      <div class="card">
        <div class="card-title">📋 Últimos avisos registrados</div>
        <?php if ($historial->num_rows === 0): ?>
          <div style="text-align:center;padding:1.5rem;color:var(--muted);font-size:.82rem">Sin registros todavía. Los avisos aparecen acá cuando usás el botón de WhatsApp.</div>
        <?php else: ?>
          <table>
            <thead><tr>
              <th>Socio</th><th>Plan</th><th>Tipo</th><th>Fecha</th>
            </tr></thead>
            <tbody>
            <?php while ($h = $historial->fetch_assoc()): ?>
            <tr>
              <td style="font-weight:600;color:var(--text)"><?= htmlspecialchars($h['nombre']) ?></td>
              <td><?= ucfirst($h['plan']) ?></td>
              <td>
                <span class="badge <?= $h['tipo_aviso']==='vencido' ? 'badge-red' : 'badge-yellow' ?>">
                  <span class="badge-dot"></span>
                  <?= $h['tipo_aviso']==='vencido' ? 'Vencido' : 'Por vencer' ?>
                </span>
              </td>
              <td><?= date('d/m/Y H:i', strtotime($h['fecha_envio'])) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

    </div><!-- /page -->
  </div><!-- /main-content -->
</div><!-- /app-layout -->

<script>
function log(t, cls, delay) {
  return new Promise(r => setTimeout(() => {
    const b = document.getElementById('logBox');
    const l = document.createElement('div');
    l.className = cls; l.textContent = t;
    b.appendChild(l); b.scrollTop = 9999; r();
  }, delay));
}
async function ejecutar() {
  const btn = document.getElementById('btnAuto');
  const icon = document.getElementById('btn-icon');
  const txt  = document.getElementById('btn-txt');
  const prog = document.getElementById('progFill');
  btn.disabled = true; icon.textContent = '⏳'; txt.textContent = 'Analizando...';
  document.getElementById('logBox').innerHTML = '';
  prog.style.width = '0%';

  await log('> Iniciando GymFlow AI...', 'log-info', 200);
  await log('> Conectando base de datos...', 'log-info', 500);
  prog.style.width = '15%';
  await log('✓ Conexión establecida', 'log-ok', 1000);
  await log(`> Fecha de análisis: <?= date('d/m/Y') ?>`, 'log-info', 1300);
  prog.style.width = '35%';

  const resp = await fetch('cron_estados.php?ajax=1');
  const data = await resp.json();
  prog.style.width = '65%';

  await log('✓ Fechas analizadas correctamente', 'log-ok', 1800);
  if (data.vencidos   > 0) await log(`⚠ ${data.vencidos} socios con membresía vencida`, 'log-bad', 2200);
  if (data.por_vencer > 0) await log(`~ ${data.por_vencer} socios próximos a vencer`, 'log-warn', 2600);
  if (data.activos    > 0) await log(`✓ ${data.activos} socios activos confirmados`, 'log-ok', 3000);
  prog.style.width = '90%';
  await log('> Preparando sistema de avisos...', 'log-info', 3400);
  prog.style.width = '100%';
  await log('✅ AUTOMATIZACIÓN COMPLETADA', 'log-ok', 3800);

  const d = new Date().toLocaleDateString('es-AR');
  let r = `📋 Resumen GymFlow — ${d}\n\n`;
  if (data.vencidos > 0)   r += `🔴 Vencidos:    ${data.vencidos} socios\n`;
  if (data.por_vencer > 0) r += `🟡 Por vencer:  ${data.por_vencer} socios\n`;
  r += `🟢 Al día:      <?= $activos ?> socios\n`;
  r += `\n👥 Total: <?= $total ?> socios registrados`;
  const rb = document.getElementById('resumenBox');
  rb.textContent = r; rb.style.display = 'block';

  setTimeout(() => {
    btn.disabled = false; icon.textContent = '⚡'; txt.textContent = 'VOLVER A EJECUTAR';
    location.reload();
  }, 4500);
}
function copiar() {
  const r = document.getElementById('resumenBox').textContent;
  if (!r.trim()) { alert('Primero ejecutá la automatización.'); return; }
  navigator.clipboard.writeText(r).then(() => {
    const b = document.querySelector('[onclick="copiar()"]');
    b.textContent = '✅ Copiado!';
    setTimeout(() => b.textContent = '📋 Copiar', 2000);
  });
}
</script>
</body>
</html>
