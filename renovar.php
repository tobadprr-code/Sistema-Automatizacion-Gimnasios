<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/nav.php';

$alerta = '';
$tipo   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cliente_id'])) {
    $id   = (int)$_POST['cliente_id'];
    $plan = $conn->real_escape_string($_POST['plan']);
    $dias = ['semanal'=>7,'quincenal'=>15,'mensual'=>30][$plan] ?? 30;
    $nueva_fecha = date('Y-m-d', strtotime("+{$dias} days"));

    $conn->query("UPDATE clientes SET plan='{$plan}', fecha_inicio=CURDATE(), fecha_vencimiento='{$nueva_fecha}', estado='activo', ultimo_aviso=NULL, aviso_conteo=0 WHERE id={$id}");
    $conn->query("INSERT INTO avisos_log (cliente_id, tipo_aviso, mensaje) VALUES ({$id}, 'por_vencer', 'Membresía renovada — {$plan} hasta {$nueva_fecha}')");

    $c = $conn->query("SELECT * FROM clientes WHERE id=$id")->fetch_assoc();
    $fn = explode(' ', $c['nombre'])[0];
    $msg_wa = "✅ Hola {$fn}! Tu membresía fue renovada hasta el " . date('d/m/Y', strtotime($nueva_fecha)) . ". ¡A seguir entrenando! 💪";
    $link_wa = "https://wa.me/{$c['telefono']}?text=" . urlencode($msg_wa);

    $alerta = "✅ Membresía de {$c['nombre']} renovada hasta " . date('d/m/Y', strtotime($nueva_fecha));
    $tipo   = 'success';
    $wa_btn = $link_wa;
}

$socios = $conn->query("
    SELECT *, DATEDIFF(CURDATE(), fecha_vencimiento) as dias_diff
    FROM clientes ORDER BY estado DESC, fecha_vencimiento ASC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymFlow AI — Renovar</title>
<link rel="stylesheet" href="css/premium.css">
</head>
<body>
<div class="app-layout">
  <?php navBar('renovar'); ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <div class="topbar-title">🔄 Renovar Membresías</div>
        <div class="topbar-sub">La fecha se calcula automáticamente desde hoy</div>
      </div>
    </div>

    <div class="page">

      <?php if ($alerta): ?>
        <div class="alert alert-<?= $tipo ?> mb-3">
          <?= $alerta ?>
          <?php if (isset($wa_btn)): ?>
            &nbsp;·&nbsp;
            <a href="<?= $wa_btn ?>" target="_blank" class="btn btn-wa" style="padding:.3rem .875rem;font-size:.75rem;margin-left:.5rem">📲 Confirmar por WhatsApp</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php while ($s = $socios->fetch_assoc()):
        $dias = (int)$s['dias_diff'];
        if ($s['estado'] === 'vencido')      { $dias_txt = $dias===0 ? 'Venció hoy' : "Hace {$dias}d";    $badge = 'badge-red';    $bc = 'var(--red)'; }
        elseif ($s['estado'] === 'por_vencer'){ $dias_txt = "Vence en ".abs($dias)."d";                    $badge = 'badge-yellow'; $bc = 'var(--yellow)'; }
        else                                 { $dias_txt = abs($dias)."d restantes";                       $badge = 'badge-green';  $bc = 'var(--primary)'; }
      ?>
      <div style="background:var(--surface);border:1px solid var(--border);border-left:3px solid <?= $bc ?>;border-radius:14px;padding:1.125rem 1.5rem;margin-bottom:.75rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
          <div style="font-weight:700;font-size:.92rem;margin-bottom:.25rem;color:var(--text)"><?= htmlspecialchars($s['nombre']) ?></div>
          <div style="font-size:.72rem;color:var(--muted);display:flex;gap:.875rem;flex-wrap:wrap">
            <span>📞 <?= $s['telefono'] ?></span>
            <span>Plan actual: <?= ucfirst($s['plan']) ?></span>
            <span>📅 <?= date('d/m/Y', strtotime($s['fecha_vencimiento'])) ?></span>
          </div>
        </div>
        <span class="badge <?= $badge ?>"><span class="badge-dot"></span><?= $dias_txt ?></span>
        <form method="POST" style="display:flex;align-items:center;gap:.5rem">
          <input type="hidden" name="cliente_id" value="<?= $s['id'] ?>">
          <select name="plan" class="form-input" style="width:160px;padding:.5rem .875rem">
            <option value="semanal"   <?= $s['plan']==='semanal'   ?'selected':'' ?>>Semanal (7 días)</option>
            <option value="quincenal" <?= $s['plan']==='quincenal' ?'selected':'' ?>>Quincenal (15 días)</option>
            <option value="mensual"   <?= $s['plan']==='mensual'   ?'selected':'' ?>>Mensual (30 días)</option>
          </select>
          <button class="btn btn-primary" type="submit">🔄 Renovar</button>
        </form>
      </div>
      <?php endwhile; ?>

    </div>
  </div>
</div>
</body>
</html>
