<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
 
// Avisos por día (últimos 7 días)
$avisos_por_dia = $conn->query("
    SELECT DATE(fecha_envio) as dia, COUNT(*) as total
    FROM avisos_log
    WHERE fecha_envio >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(fecha_envio)
    ORDER BY dia ASC
");
 
// Socios que más avisos recibieron
$mas_avisos = $conn->query("
    SELECT c.nombre, c.plan, c.estado, COUNT(a.id) as total_avisos
    FROM avisos_log a
    JOIN clientes c ON a.cliente_id = c.id
    GROUP BY a.cliente_id
    ORDER BY total_avisos DESC
    LIMIT 5
");
 
// Total avisos
$total_avisos = $conn->query("SELECT COUNT(*) as t FROM avisos_log")->fetch_assoc()['t'];
$avisos_hoy   = $conn->query("SELECT COUNT(*) as t FROM avisos_log WHERE DATE(fecha_envio) = CURDATE()")->fetch_assoc()['t'];
$avisos_semana= $conn->query("SELECT COUNT(*) as t FROM avisos_log WHERE fecha_envio >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['t'];
 
// Distribución de planes
$planes = $conn->query("SELECT plan, COUNT(*) as total FROM clientes GROUP BY plan");
 
// Datos para el gráfico de barras
$dias_labels = [];
$dias_data   = [];
$tmp = $avisos_por_dia;
while ($r = $tmp->fetch_assoc()) {
    $dias_labels[] = date('d/m', strtotime($r['dia']));
    $dias_data[]   = (int)$r['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymFlow — Estadísticas</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
:root {
  --bg:#0a0a0a; --card:#141414; --card2:#1a1a1a;
  --border:#222; --green:#00ff88; --green-s:rgba(0,255,136,.1);
  --red:#ff3333; --yellow:#ffcc00; --text:#f0f0f0; --muted:#555;
}
body { background:var(--bg); color:var(--text); font-family:'Segoe UI',sans-serif; }
nav { background:var(--card); border-bottom:1px solid var(--border); padding:1rem 2rem; display:flex; align-items:center; justify-content:space-between; }
.logo { font-size:1.3rem; font-weight:900; letter-spacing:2px; color:var(--green); }
.logo span { color:var(--text); }
.nav-links a { color:var(--muted); font-size:.8rem; text-decoration:none; margin-left:1.5rem; text-transform:uppercase; letter-spacing:1px; transition:color .15s; }
.nav-links a:hover { color:var(--green); }
main { max-width:1000px; margin:0 auto; padding:2rem; }
h1 { font-size:1.6rem; font-weight:800; margin-bottom:.25rem; }
.sub { color:var(--muted); font-size:.85rem; margin-bottom:2rem; }
 
.stats { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:2rem; }
.stat { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:1.25rem 1.5rem; }
.stat-num { font-size:2.5rem; font-weight:900; color:var(--green); line-height:1; }
.stat-label { font-size:.68rem; color:var(--muted); text-transform:uppercase; letter-spacing:1px; margin-top:.25rem; }
 
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem; }
.panel { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:1.5rem; }
.panel-title { font-size:.72rem; text-transform:uppercase; letter-spacing:1.5px; color:var(--muted); font-weight:700; margin-bottom:1.25rem; }
 
/* GRÁFICO DE BARRAS */
.chart { display:flex; align-items:flex-end; gap:8px; height:120px; margin-bottom:.5rem; }
.bar-wrap { flex:1; display:flex; flex-direction:column; align-items:center; gap:5px; height:100%; justify-content:flex-end; }
.bar { width:100%; background:var(--green); border-radius:4px 4px 0 0; min-height:4px; transition:height .5s ease; box-shadow:0 0 8px rgba(0,255,136,.3); }
.bar-val { font-size:.7rem; color:var(--green); font-weight:700; }
.bar-label { font-size:.65rem; color:var(--muted); }
.chart-labels { display:flex; gap:8px; }
.no-data { color:var(--muted); font-size:.85rem; text-align:center; padding:2rem; }
 
/* TABLA TOP */
.top-item { display:flex; align-items:center; gap:.875rem; padding:.75rem 0; border-bottom:1px solid var(--border); }
.top-item:last-child { border-bottom:none; }
.top-rank { width:28px; height:28px; border-radius:50%; background:var(--card2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700; color:var(--muted); flex-shrink:0; }
.top-rank.gold { background:rgba(255,204,0,.15); border-color:#ffcc00; color:#ffcc00; }
.top-nombre { font-size:.88rem; font-weight:600; }
.top-detalle { font-size:.72rem; color:var(--muted); margin-top:2px; }
.top-count { font-size:1.2rem; font-weight:800; color:var(--red); margin-left:auto; }
 
/* PLANES */
.plan-item { margin-bottom:1rem; }
.plan-label { display:flex; justify-content:space-between; font-size:.8rem; margin-bottom:5px; }
.plan-bar { height:6px; background:var(--card2); border-radius:3px; overflow:hidden; }
.plan-fill { height:100%; border-radius:3px; background:var(--green); box-shadow:0 0 6px rgba(0,255,136,.3); }
 
@media(max-width:700px) { .grid-2 { grid-template-columns:1fr; } .stats { grid-template-columns:1fr 1fr; } }
</style>
</head>
<body>
 
<nav>
  <div class="logo">GYM<span>FLOW</span></div>
  <div class="nav-links">
    <a href="automatizacion.php">← Panel</a>
    <a href="renovar.php">Renovar</a>
    <a href="exportar.php">📥 Exportar CSV</a>
    <a href="logout.php">Salir</a>
  </div>
</nav>
 
<main>
  <h1>📊 Estadísticas</h1>
  <p class="sub">Historial de actividad del sistema · <?= date('d/m/Y') ?></p>
 
  <div class="stats">
    <div class="stat">
      <div class="stat-num"><?= $total_avisos ?></div>
      <div class="stat-label">Avisos enviados (total)</div>
    </div>
    <div class="stat">
      <div class="stat-num"><?= $avisos_semana ?></div>
      <div class="stat-label">Esta semana</div>
    </div>
    <div class="stat">
      <div class="stat-num"><?= $avisos_hoy ?></div>
      <div class="stat-label">Hoy</div>
    </div>
  </div>
 
  <div class="grid-2">
    <div class="panel">
      <div class="panel-title">📈 Avisos enviados — últimos 7 días</div>
      <?php if (empty($dias_data)): ?>
        <div class="no-data">Todavía no hay avisos registrados.</div>
      <?php else:
        $max = max($dias_data) ?: 1;
      ?>
      <div class="chart">
        <?php foreach ($dias_data as $i => $val): ?>
        <div class="bar-wrap">
          <div class="bar-val"><?= $val ?></div>
          <div class="bar" style="height:<?= round(($val/$max)*100) ?>%"></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="chart-labels">
        <?php foreach ($dias_labels as $l): ?>
          <div style="flex:1;text-align:center;font-size:.65rem;color:var(--muted)"><?= $l ?></div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
 
    <div class="panel">
      <div class="panel-title">📦 Distribución de planes</div>
      <?php
      $total_s = $conn->query("SELECT COUNT(*) as t FROM clientes")->fetch_assoc()['t'];
      $planes->data_seek(0);
      while ($p = $planes->fetch_assoc()):
        $pct = $total_s > 0 ? round(($p['total']/$total_s)*100) : 0;
      ?>
      <div class="plan-item">
        <div class="plan-label">
          <span><?= ucfirst($p['plan']) ?></span>
          <span style="color:var(--green)"><?= $p['total'] ?> socios (<?= $pct ?>%)</span>
        </div>
        <div class="plan-bar"><div class="plan-fill" style="width:<?= $pct ?>%"></div></div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
 
  <div class="panel">
    <div class="panel-title">🔴 Socios con más avisos recibidos</div>
    <?php if ($mas_avisos->num_rows === 0): ?>
      <div class="no-data">Sin datos todavía.</div>
    <?php else:
      $pos = 1;
      while ($r = $mas_avisos->fetch_assoc()): ?>
      <div class="top-item">
        <div class="top-rank <?= $pos === 1 ? 'gold' : '' ?>"><?= $pos ?></div>
        <div style="flex:1">
          <div class="top-nombre"><?= htmlspecialchars($r['nombre']) ?></div>
          <div class="top-detalle">Plan <?= ucfirst($r['plan']) ?> · Estado: <?= ucfirst($r['estado']) ?></div>
        </div>
        <div class="top-count"><?= $r['total_avisos'] ?> avisos</div>
      </div>
      <?php $pos++; endwhile; ?>
    <?php endif; ?>
  </div>
</main>
</body>
</html>