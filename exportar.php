<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
 
// Headers para descarga de archivo
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="gymflow_socios_' . date('d-m-Y') . '.csv"');
 
$output = fopen('php://output', 'w');
 
// BOM para que Excel muestre bien los caracteres especiales
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
 
// Encabezados de columnas
fputcsv($output, [
    'ID', 'Nombre', 'Teléfono', 'Plan',
    'Días que asiste', 'Día de pago',
    'Fecha inicio', 'Fecha vencimiento',
    'Estado', 'Días vencido / restantes',
    'Fecha registro'
], ';');
 
$socios = $conn->query("SELECT * FROM clientes ORDER BY estado DESC, fecha_vencimiento ASC");
 
while ($s = $socios->fetch_assoc()) {
    $hoy   = new DateTime();
    $vence = new DateTime($s['fecha_vencimiento']);
    $diff  = $hoy->diff($vence);
    $dias  = (int)$diff->days;
 
    if ($s['estado'] === 'vencido') {
        $dias_label = "Vencido hace {$dias} días";
    } elseif ($s['estado'] === 'por_vencer') {
        $dias_label = "Vence en {$dias} días";
    } else {
        $dias_label = "{$dias} días restantes";
    }
 
    fputcsv($output, [
        $s['id'],
        $s['nombre'],
        $s['telefono'],
        ucfirst($s['plan']),
        $s['dias_asiste'],
        $s['dia_pago'],
        date('d/m/Y', strtotime($s['fecha_inicio'])),
        date('d/m/Y', strtotime($s['fecha_vencimiento'])),
        ucfirst($s['estado']),
        $dias_label,
        date('d/m/Y H:i', strtotime($s['fecha_registro']))
    ], ';');
}
 
fclose($output);
exit;