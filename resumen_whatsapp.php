<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/nav.php';

$hoy          = date('Y-m-d');
$total        = $conn->query("SELECT COUNT(*) as t FROM clientes")->fetch_assoc()['t'];
$activos      = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='activo'")->fetch_assoc()['t'];
$por_vencer   = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='por_vencer'")->fetch_assoc()['t'];
$vencidos     = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='vencido'")->fetch_assoc()['t'];

// Nombre del gym desde config
$nombre_gym = 'GymFlow';
$r = $conn->query("SELECT valor FROM configuracion WHERE clave='nombre_gym' LIMIT 1");
if ($r && $r->num_rows > 0) $nombre_gym = $r->fetch_assoc()['valor'];

// Detalle de vencidos
$detalle_vencidos = $conn->query("
    SELECT nombre, plan, DATEDIFF('$hoy', fecha_vencimiento) as dias
    FROM clientes WHERE estado='vencido'
    ORDER BY dias DESC LIMIT 10
");
$detalle_por_vencer = $conn->query("
    SELECT nombre, plan, DATEDIFF(fecha_vencimiento, '$hoy') as dias
    FROM clientes WHERE estado='por_vencer'
    ORDER BY dias ASC LIMIT 10
");

// Generar texto del resumen
$dias_semana = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
$dia_actual  = $dias_semana[date('l')];
$fecha_hoy   = date('d/m/Y');

$resumen  = "📋 *Resumen {$nombre_gym} — {$dia_actual} {$fecha_hoy}*\n\n";
if ($vencidos > 0) {
    $resumen .= "🔴 *Vencidos ({$vencidos}):*\n";
    $detalle_vencidos->data_seek(0);
    while ($r = $detalle_vencidos->fetch_assoc()) {
        $d = $r['dias']; $txt = $d===0?'hoy':($d===1?'hace 1 día':"hace {$d} días");
        $resumen .= "   • {$r['nombre']} — venció {$txt}\n";
    }
    $resumen .= "\n";
}
if ($por_vencer > 0) {
    $resumen .= "🟡 *Por vencer ({$por_vencer}):*\n";
    $detalle_por_vencer->data_seek(0);
    while ($r = $detalle_por_vencer->fetch_assoc()) {
        $d = $r['dias']; $txt = $d===0?'hoy':($d===1?'mañana':"en {$d} días");
        $resumen .= "   • {$r['nombre']} — vence {$txt}\n";
    }
    $resumen .= "\n";
}
$resumen .= "🟢 *Al día: {$activos} socios*\n";
$resumen .= "👥 *Total: {$total} socios registrados*\n";
$resumen .= "\n_Generado por GymFlow AI_";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymFlow AI — Resumen WhatsApp</title>
<link rel="stylesheet" href="css/premium.css">
</head>
<body>
<div class="app-layout">
  <?php navBar('resumen'); ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <div class="topbar-title">💬 Resumen para WhatsApp</div>
        <div class="topbar-sub">Copiá y pegalo directo en WhatsApp</div>
      </div>
    </div>

    <div class="page">
      <div class="grid-2-wide">

        <!-- PREVIEW -->
        <div class="card">
          <div class="card-title">Vista previa del mensaje</div>

          <!-- Burbuja estilo WhatsApp -->
          <div style="background:#0a1a10;border:1px solid rgba(37,211,102,.15);border-radius:14px;padding:1.5rem;margin-bottom:1.25rem">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid rgba(37,211,102,.1)">
              <div style="width:38px;height:38px;border-radius:50%;background:rgba(37,211,102,.2);border:1.5px solid #25D366;display:flex;align-items:center;justify-content:center;font-size:.9rem">🏋️</div>
              <div>
                <div style="font-size:.85rem;font-weight:600;color:#f0f0f0"><?= htmlspecialchars($nombre_gym) ?></div>
                <div style="font-size:.68rem;color:#25D366">En línea</div>
              </div>
            </div>
            <div id="msgPreview" style="background:#1a2e1a;border-radius:4px 12px 12px 12px;padding:1rem 1.125rem;font-size:.85rem;line-height:1.8;color:#e0e0e0;white-space:pre-line;border:1px solid rgba(37,211,102,.1)"><?= nl2br(htmlspecialchars($resumen)) ?></div>
            <div style="font-size:.65rem;color:#555;margin-top:.5rem;text-align:right">Generado automáticamente · ✓✓</div>
          </div>

          <div style="display:flex;gap:.75rem;flex-wrap:wrap">
            <button class="btn btn-primary btn-lg" onclick="copiar()">📋 Copiar mensaje</button>
            <?php
              $tel_dueño = '';
              $rd = $conn->query("SELECT valor FROM configuracion WHERE clave='telefono_dueño' LIMIT 1");
              if ($rd && $rd->num_rows) $tel_dueño = $rd->fetch_assoc()['valor'];
              if ($tel_dueño):
                $link_wa = "https://wa.me/{$tel_dueño}?text=" . urlencode($resumen);
            ?>
            <a href="<?= $link_wa ?>" target="_blank" class="btn btn-wa btn-lg">📲 Enviar al dueño por WhatsApp</a>
            <?php endif; ?>
          </div>
          <div id="copiado" style="display:none" class="alert alert-success mt-2">✅ ¡Mensaje copiado! Pegalo en WhatsApp.</div>
        </div>

        <!-- STATS RÁPIDAS -->
        <div style="display:flex;flex-direction:column;gap:1rem">
          <div class="stat-card red">
            <div class="stat-icon">🔴</div>
            <div class="stat-num"><?= $vencidos ?></div>
            <div class="stat-label">Vencidos hoy</div>
          </div>
          <div class="stat-card yellow">
            <div class="stat-icon">⏰</div>
            <div class="stat-num"><?= $por_vencer ?></div>
            <div class="stat-label">Por vencer</div>
          </div>
          <div class="stat-card green">
            <div class="stat-icon">✅</div>
            <div class="stat-num"><?= $activos ?></div>
            <div class="stat-label">Al día</div>
          </div>
          <div class="card">
            <div class="card-title">Acciones rápidas</div>
            <div style="display:flex;flex-direction:column;gap:.5rem">
              <a href="avisos.php"       class="btn btn-outline">📲 Ver avisos individuales</a>
              <a href="cron_estados.php" class="btn btn-outline">⚡ Ejecutar automatización</a>
              <a href="exportar.php"     class="btn btn-outline">📥 Exportar CSV</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const texto = <?= json_encode($resumen) ?>;
function copiar() {
  navigator.clipboard.writeText(texto).then(() => {
    document.getElementById('copiado').style.display = 'flex';
    setTimeout(() => document.getElementById('copiado').style.display = 'none', 3000);
  });
}
</script>
</body>
</html>
