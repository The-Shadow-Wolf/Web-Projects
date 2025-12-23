<?php
// /api/reportes.php
// VERSIÓN 2.0 - Con Filtros de Fecha

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config/database.php';

$response = ['success' => false];

// 1. OBTENER FECHAS DEL FILTRO (O USAR DEFAULT)
$fecha_inicio = $_GET['inicio'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_fin = $_GET['fin'] ?? date('Y-m-t');       // Último día del mes actual

// Validar formato (simple)
if (!$fecha_inicio) $fecha_inicio = date('Y-m-01');
if (!$fecha_fin) $fecha_fin = date('Y-m-t');

try {
    // --- REPORTE 1: OCUPACIÓN POR LABORATORIO ---
    // Contamos cuántas reservas confirmadas hubo en el rango
    $sql_ocupacion = "
        SELECT l.nombre_lab, COUNT(r.reserva_id) as total_reservas
        FROM reservas r
        JOIN equipos e ON r.equipo_id = e.equipo_id
        JOIN laboratorios l ON e.laboratorio_id = l.laboratorio_id
        WHERE r.estado_reserva = 'confirmada'
          AND r.fecha_reserva BETWEEN '$fecha_inicio' AND '$fecha_fin'
        GROUP BY l.nombre_lab
    ";
    
    $res_ocupacion = $conn->query($sql_ocupacion);
    $data_ocupacion = [];
    while($row = $res_ocupacion->fetch_assoc()) {
        $data_ocupacion[] = $row;
    }

    // --- REPORTE 2: TASA DE ASISTENCIA ---
    // Comparamos Total Reservas vs. Cuántos tuvieron 'hora_ingreso_real' (Asistieron)
    $sql_asistencia = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN hora_ingreso_real IS NOT NULL THEN 1 ELSE 0 END) as asistieron,
            SUM(CASE WHEN hora_ingreso_real IS NULL THEN 1 ELSE 0 END) as faltaron
        FROM reservas
        WHERE estado_reserva = 'confirmada'
          AND fecha_reserva BETWEEN '$fecha_inicio' AND '$fecha_fin'
          AND fecha_reserva < CURDATE() -- Solo contamos días pasados
    ";

    $res_asistencia = $conn->query($sql_asistencia);
    $data_asistencia = $res_asistencia->fetch_assoc();

    // --- REPORTE 3: LISTADO DETALLADO (Para Exportar a Excel) ---
    // Traemos la data cruda para generar el CSV en el frontend
    $sql_detalle = "
        SELECT r.fecha_reserva, r.hora_inicio, r.hora_fin, l.nombre_lab, u.codigo_unac, 
               CASE WHEN r.hora_ingreso_real IS NOT NULL THEN 'ASISTIÓ' ELSE 'FALTO' END as estado_asistencia
        FROM reservas r
        JOIN usuarios u ON r.usuario_id = u.usuario_id
        JOIN equipos e ON r.equipo_id = e.equipo_id
        JOIN laboratorios l ON e.laboratorio_id = l.laboratorio_id
        WHERE r.estado_reserva = 'confirmada'
          AND r.fecha_reserva BETWEEN '$fecha_inicio' AND '$fecha_fin'
        ORDER BY r.fecha_reserva DESC, r.hora_inicio ASC
    ";
    
    $res_detalle = $conn->query($sql_detalle);
    $data_detalle = [];
    while($row = $res_detalle->fetch_assoc()) {
        $data_detalle[] = $row;
    }

    $response['success'] = true;
    $response['rango'] = ['inicio' => $fecha_inicio, 'fin' => $fecha_fin];
    $response['ocupacion'] = $data_ocupacion;
    $response['asistencia'] = $data_asistencia;
    $response['detalle'] = $data_detalle; // Nueva data para exportar

} catch (Exception $e) {
    $response['message'] = "Error BD: " . $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>