<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$hoy          = date('Y-m-d');
$limite_aviso = date('Y-m-d', strtotime('+3 days'));

// 1. Marcar VENCIDO
$conn->query("UPDATE clientes SET estado='vencido'    WHERE fecha_vencimiento < '$hoy' AND estado != 'vencido'");
$vencidos   = $conn->affected_rows;

// 2. Marcar POR VENCER
$conn->query("UPDATE clientes SET estado='por_vencer' WHERE fecha_vencimiento BETWEEN '$hoy' AND '$limite_aviso' AND estado='activo'");
$por_vencer = $conn->affected_rows;

// 3. Confirmar ACTIVOS
$conn->query("UPDATE clientes SET estado='activo'     WHERE fecha_vencimiento > '$limite_aviso' AND estado != 'activo'");
$activos    = $conn->affected_rows;

// ── ANTI-SPAM: detectar quiénes necesitan aviso sin haber recibido uno en 4 días ──
$para_avisar = $conn->query("
    SELECT id, nombre, telefono, plan, estado, fecha_vencimiento,
           ultimo_aviso, aviso_conteo,
           DATEDIFF('$hoy', ultimo_aviso) as dias_desde_aviso
    FROM clientes
    WHERE estado IN ('vencido','por_vencer')
    AND (ultimo_aviso IS NULL OR DATEDIFF('$hoy', ultimo_aviso) >= 4)
")->num_rows;

// Devolver JSON si lo llama automatizacion.php
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'vencidos'    => $vencidos,
        'por_vencer'  => $por_vencer,
        'activos'     => $activos,
        'para_avisar' => $para_avisar,
        'fecha'       => $hoy,
        'ok'          => true
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>GymFlow — Automatización ejecutada</title>
<link rel="stylesheet" href="css/premium.css">
<style>
body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
.box { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:2.5rem; max-width:440px; width:90%; position:relative; overflow:hidden; }
.box::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg,var(--primary),var(--accent)); }
.box h2 { font-size:1.3rem; font-weight:800; margin-bottom:1.5rem; color:var(--primary); }
.row { display:flex; align-items:center; gap:1rem; background:var(--surface2); border-radius:10px; padding:1rem 1.25rem; margin-bottom:.75rem; border:1px solid var(--border); }
.row-num { font-size:2rem; font-weight:900; width:50px; text-align:center; line-height:1; }
.row-label { font-size:.82rem; color:var(--text2); }
</style>
</head>
<body>
<div class="box">
  <h2>⚡ Automatización ejecutada</h2>
  <div class="row"><span class="row-num" style="color:var(--red)"><?= $vencidos ?></span><div class="row-label">🔴 Vencidos actualizados</div></div>
  <div class="row"><span class="row-num" style="color:var(--yellow)"><?= $por_vencer ?></span><div class="row-label">🟡 Por vencer actualizados</div></div>
  <div class="row"><span class="row-num" style="color:var(--primary)"><?= $activos ?></span><div class="row-label">🟢 Activos confirmados</div></div>
  <div class="row"><span class="row-num" style="color:var(--accent)"><?= $para_avisar ?></span><div class="row-label">📲 Socios listos para recibir aviso (sin spam)</div></div>
  <p style="font-size:.75rem;color:var(--muted);margin-top:1rem">📅 <?= date('d/m/Y H:i') ?> · Anti-spam activo — solo avisa si pasaron 4+ días desde el último mensaje</p>
  <a href="automatizacion.php" class="btn btn-primary btn-full" style="margin-top:1.25rem">← Volver al panel</a>
</div>
</body>
</html>
