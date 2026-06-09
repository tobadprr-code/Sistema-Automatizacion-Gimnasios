<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/nav.php';

$total        = $conn->query("SELECT COUNT(*) as t FROM clientes")->fetch_assoc()['t'];
$activos      = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='activo'")->fetch_assoc()['t'];
$vencidos     = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='vencido'")->fetch_assoc()['t'];
$por_vencer   = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='por_vencer'")->fetch_assoc()['t'];
$total_avisos = $conn->query("SELECT COUNT(*) as t FROM avisos_log")->fetch_assoc()['t'];
$avisos_hoy   = $conn->query("SELECT COUNT(*) as t FROM avisos_log WHERE DATE(fecha_envio)=CURDATE()")->fetch_assoc()['t'];
$avisos_sem   = $conn->query("SELECT COUNT(*) as t FROM avisos_log WHERE fecha_envio>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetch_assoc()['t'];

// Retención
$retencion = $total > 0 ? round(($activos / $total) * 100) : 0;

// Avisos últimos 7 días para gráfico
$dias_data = []; $dias_labels = [];
$tmp = $conn->query("SELECT DATE(fecha_envio) as d, COUNT(*) as t FROM avisos_log WHERE fecha_envio>=DATE_SUB(NOW(),INTERVAL 7 DAY) GROUP BY DATE(fecha_envio) ORDER BY d ASC");
while ($r = $tmp->fetch_assoc()) { $dias_labels[] = date('d/m', strtotime($r['d'])); $dias_data[] = (int)$r['t']; }

// Distribución planes
$planes_data = []; $planes_labels = [];
$tmp2 = $conn->query("SELECT plan, COUNT(*) as t FROM clientes GROUP BY plan");
while ($r = $tmp2->fetch_assoc()) { $planes_labels[] = ucfirst($r['plan']); $planes_data[] = (int)$r['t']; }

// Top deudores
$top = $conn->query("SELECT c.nombre, c.plan, c.estado, COUNT(a.id) as tot FROM avisos_log a JOIN clientes c ON a.cliente_id=c.id GROUP BY a.cliente_id ORDER BY tot DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymFlow AI — Estadísticas</title>
<link rel="stylesheet" href="css/premium.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">
  <?php navBar('estadisticas'); ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <div class="topbar-title">📈 Estadísticas</div>
        <div class="topbar-sub">Historial y análisis del sistema · <?= date('d/m/Y') ?></div>
      </div>
      <div class="topbar-right">
        <a href="exportar.php" class="btn btn-outline">📥 Exportar CSV</a>
      </div>
    </div>

    <div class="page">

      <!-- STATS -->
      <div class="stats-grid mb-4">
        <div class="stat-card green">
          <div class="stat-icon">📊</div>
          <div class="stat-num"><?= $total_avisos ?></div>
          <div class="stat-label">Avisos totales</div>
        </div>
        <div class="stat-card purple">
          <div class="stat-icon">📤</div>
          <div class="stat-num"><?= $avisos_sem ?></div>
          <div class="stat-label">Esta semana</div>
        </div>
        <div class="stat-card yellow">
          <div class="stat-icon">📅</div>
          <div class="stat-num"><?= $avisos_hoy ?></div>
          <div class="stat-label">Hoy</div>
        </div>
        <div class="stat-card <?= $retencion >= 70 ? 'green' : ($retencion >= 50 ? 'yellow' : 'red') ?>">
          <div class="stat-icon">💎</div>
          <div class="stat-num"><?= $retencion ?>%</div>
          <div class="stat-label">Retención</div>
        </div>
      </div>

      <div class="grid-2 mb-3">

        <!-- GRÁFICO AVISOS -->
        <div class="card">
          <div class="card-title">📈 Avisos últimos 7 días</div>
          <?php if (empty($dias_data)): ?>
            <div style="text-align:center;padding:3rem;color:var(--muted);font-size:.82rem">Sin datos todavía</div>
          <?php else: ?>
            <canvas id="chartAvisos" height="180"></canvas>
          <?php endif; ?>
        </div>

        <!-- GRÁFICO PLANES -->
        <div class="card">
          <div class="card-title">📦 Distribución de planes</div>
          <?php if (empty($planes_data)): ?>
            <div style="text-align:center;padding:3rem;color:var(--muted);font-size:.82rem">Sin datos</div>
          <?php else: ?>
            <canvas id="chartPlanes" height="180"></canvas>
          <?php endif; ?>
        </div>
      </div>

      <!-- ESTADO GENERAL -->
      <div class="card mb-3">
        <div class="card-title">🏋️ Estado general del gym</div>
        <div style="margin-bottom:1rem">
          <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:.4rem">
            <span>Al día</span><span style="color:var(--primary);font-weight:700"><?= $activos ?> socios (<?= $total>0?round($activos/$total*100):0 ?>%)</span>
          </div>
          <div class="prog-bar mb-2"><div class="prog-fill green" style="width:<?= $total>0?round($activos/$total*100):0 ?>%"></div></div>
        </div>
        <div style="margin-bottom:1rem">
          <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:.4rem">
            <span>Por vencer</span><span style="color:var(--yellow);font-weight:700"><?= $por_vencer ?> socios (<?= $total>0?round($por_vencer/$total*100):0 ?>%)</span>
          </div>
          <div class="prog-bar mb-2"><div class="prog-fill yellow" style="width:<?= $total>0?round($por_vencer/$total*100):0 ?>%"></div></div>
        </div>
        <div>
          <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:.4rem">
            <span>Vencidos</span><span style="color:var(--red);font-weight:700"><?= $vencidos ?> socios (<?= $total>0?round($vencidos/$total*100):0 ?>%)</span>
          </div>
          <div class="prog-bar"><div class="prog-fill red" style="width:<?= $total>0?round($vencidos/$total*100):0 ?>%"></div></div>
        </div>
      </div>

      <!-- TOP DEUDORES -->
      <div class="card">
        <div class="card-title">🔴 Socios con más avisos recibidos</div>
        <?php if ($top->num_rows === 0): ?>
          <div style="text-align:center;padding:2rem;color:var(--muted);font-size:.82rem">Sin datos todavía.</div>
        <?php else:
          $pos = 1; while ($r = $top->fetch_assoc()): ?>
          <div style="display:flex;align-items:center;gap:.875rem;padding:.75rem 0;border-bottom:1px solid var(--border)">
            <div style="width:30px;height:30px;border-radius:50%;background:<?= $pos===1?'rgba(245,158,11,.15)':'var(--surface2)' ?>;border:1px solid <?= $pos===1?'var(--yellow)':'var(--border)' ?>;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:<?= $pos===1?'var(--yellow)':'var(--muted)' ?>;flex-shrink:0"><?= $pos ?></div>
            <div style="flex:1">
              <div style="font-size:.88rem;font-weight:600;color:var(--text)"><?= htmlspecialchars($r['nombre']) ?></div>
              <div style="font-size:.72rem;color:var(--muted)"><?= ucfirst($r['plan']) ?> · <?= ucfirst($r['estado']) ?></div>
            </div>
            <div style="font-size:1.3rem;font-weight:800;color:var(--red)"><?= $r['tot'] ?></div>
            <div style="font-size:.65rem;color:var(--muted)">avisos</div>
          </div>
          <?php $pos++; endwhile; endif; ?>
      </div>

    </div>
  </div>
</div>

<script>
const chartOpts = {
  responsive:true,
  plugins:{ legend:{ display:false }, tooltip:{ backgroundColor:'#0d0d18', titleColor:'#e8e8f2', bodyColor:'#a0a0c0', borderColor:'#1e1e32', borderWidth:1 } },
  scales:{
    x:{ grid:{ color:'#1e1e32' }, ticks:{ color:'#52527a', font:{ size:11 } } },
    y:{ grid:{ color:'#1e1e32' }, ticks:{ color:'#52527a', font:{ size:11 } }, beginAtZero:true }
  }
};
<?php if (!empty($dias_data)): ?>
new Chart(document.getElementById('chartAvisos'), {
  type:'bar',
  data:{
    labels: <?= json_encode($dias_labels) ?>,
    datasets:[{ data: <?= json_encode($dias_data) ?>, backgroundColor:'rgba(0,200,150,.25)', borderColor:'#00c896', borderWidth:2, borderRadius:6 }]
  },
  options: chartOpts
});
<?php endif; ?>
<?php if (!empty($planes_data)): ?>
new Chart(document.getElementById('chartPlanes'), {
  type:'doughnut',
  data:{
    labels: <?= json_encode($planes_labels) ?>,
    datasets:[{ data: <?= json_encode($planes_data) ?>, backgroundColor:['rgba(0,200,150,.7)','rgba(124,111,205,.7)','rgba(245,158,11,.7)'], borderColor:'#0d0d18', borderWidth:3 }]
  },
  options:{
    responsive:true, cutout:'65%',
    plugins:{ legend:{ position:'bottom', labels:{ color:'#a0a0c0', font:{size:12}, padding:16 } }, tooltip:{ backgroundColor:'#0d0d18', titleColor:'#e8e8f2', bodyColor:'#a0a0c0', borderColor:'#1e1e32', borderWidth:1 } }
  }
});
<?php endif; ?>
</script>
</body>
</html>
