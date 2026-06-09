<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$filtro = $_GET['filtro'] ?? 'todos';
$allowed = ['todos','activo','por_vencer','vencido'];
if (!in_array($filtro, $allowed)) $filtro = 'todos';

$where = $filtro !== 'todos' ? "WHERE estado='{$filtro}'" : '';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="gymflow_' . $filtro . '_' . date('d-m-Y') . '.csv"');
$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($out, ['ID','Nombre','Teléfono','Plan','Días que asiste','Día de pago','Fecha inicio','Fecha vencimiento','Estado','Días vencido/restantes','Avisos enviados','Fecha registro'], ';');

$socios = $conn->query("SELECT * FROM clientes {$where} ORDER BY estado DESC, fecha_vencimiento ASC");
while ($s = $socios->fetch_assoc()) {
    $hoy  = new DateTime();
    $vence= new DateTime($s['fecha_vencimiento']);
    $diff = (int)$hoy->diff($vence)->days;
    if ($s['estado']==='vencido')      $dl = "Vencido hace {$diff} días";
    elseif ($s['estado']==='por_vencer') $dl = "Vence en {$diff} días";
    else                                $dl = "{$diff} días restantes";
    fputcsv($out, [
        $s['id'], $s['nombre'], $s['telefono'], ucfirst($s['plan']),
        $s['dias_asiste'], $s['dia_pago'],
        date('d/m/Y', strtotime($s['fecha_inicio'])),
        date('d/m/Y', strtotime($s['fecha_vencimiento'])),
        ucfirst($s['estado']), $dl,
        $s['aviso_conteo'] ?? 0,
        date('d/m/Y H:i', strtotime($s['fecha_registro']))
    ], ';');
}
fclose($out); exit;
