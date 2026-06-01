<?php
require_once 'includes/db.php';

// Fecha de hoy
$hoy = date('Y-m-d');

// Fecha límite para "por vencer" (3 días desde hoy)
$limite_aviso = date('Y-m-d', strtotime('+3 days'));

// 1. Marcar como VENCIDO
$conn->query("
    UPDATE clientes 
    SET estado = 'vencido'
    WHERE fecha_vencimiento < '$hoy'
    AND estado != 'vencido'
");
$vencidos = $conn->affected_rows;

// 2. Marcar como POR VENCER
$conn->query("
    UPDATE clientes 
    SET estado = 'por_vencer'
    WHERE fecha_vencimiento BETWEEN '$hoy' AND '$limite_aviso'
    AND estado = 'activo'
");
$por_vencer = $conn->affected_rows;

// 3. Marcar como ACTIVO los que están bien
$conn->query("
    UPDATE clientes 
    SET estado = 'activo'
    WHERE fecha_vencimiento > '$limite_aviso'
    AND estado != 'activo'
");
$activos = $conn->affected_rows;

// Resultado
echo "<h2>✅ Automatización ejecutada</h2>";
echo "<p>🔴 Vencidos actualizados: <strong>$vencidos</strong></p>";
echo "<p>🟡 Por vencer actualizados: <strong>$por_vencer</strong></p>";
echo "<p>🟢 Activos confirmados: <strong>$activos</strong></p>";
echo "<p>📅 Fecha analizada: <strong>$hoy</strong></p>";
?>