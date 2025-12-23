<?php
// /api/reservas.php
// VERSIÓN 5.0 - Con Zona Horaria Forzada (Lima)

// --- ¡ESTA LÍNEA ES LA CLAVE! ---
date_default_timezone_set('America/Lima'); 
// -------------------------------

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

// ... (El resto del código sigue igual) ...

$response = ['success' => false];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- LÓGICA GET (MEJORADA PARA HISTORIAL) ---
if ($method === 'GET') {
    $eventos = [];
    
    // 1. Verificamos si el frontend nos pide una fecha específica
    // Si nos manda ?fecha=2025-10-27, filtramos por ese día.
    // Si no, por defecto traemos todo (o podrías limitar al mes actual para no saturar).
    $filtro_fecha_reserva = "";
    $filtro_fecha_bloqueo = "";
    $param_fecha = null;

    if (isset($_GET['fecha'])) {
        $fecha_solicitada = $_GET['fecha'];
        $filtro_fecha_reserva = "WHERE r.fecha_reserva = '$fecha_solicitada'";
        // Para bloqueos, revisamos si la fecha solicitada cae dentro del rango del bloqueo
        $filtro_fecha_bloqueo = "WHERE '$fecha_solicitada' BETWEEN DATE(b.fecha_inicio) AND DATE(b.fecha_fin)";
    } else {
        // Opcional: Si no hay fecha, traer SOLO futuras para no cargar 10 años de historia por gusto
        // $filtro_fecha_reserva = "WHERE r.fecha_reserva >= CURDATE()"; 
        // $filtro_fecha_bloqueo = "WHERE b.fecha_fin >= CURDATE()";
        
        // Pero como quieres ver historial completo si no especificas:
        $filtro_fecha_reserva = ""; 
        $filtro_fecha_bloqueo = "";
    }

    // 2. Consulta de RESERVAS
    $sql_reservas = "
        SELECT 'reserva' as tipo, r.reserva_id as id, e.equipo_id, r.usuario_id, 
               r.fecha_reserva as fecha, r.hora_inicio, r.hora_fin, 
               r.hora_ingreso_real, r.hora_salida_real, 
               u.codigo_unac, e.numero_equipo, l.nombre_lab, 
               'Reserva de usuario' as motivo, r.estado_reserva
        FROM reservas r
        JOIN usuarios u ON r.usuario_id = u.usuario_id
        JOIN equipos e ON r.equipo_id = e.equipo_id
        JOIN laboratorios l ON e.laboratorio_id = l.laboratorio_id
        $filtro_fecha_reserva
    ";
    
    $result_reservas = $conn->query($sql_reservas);
    if ($result_reservas) { 
        while ($row = $result_reservas->fetch_assoc()) { 
            $eventos[] = $row; 
        } 
    }

    // 3. Consulta de BLOQUEOS
    $sql_bloqueos = "
        SELECT 'bloqueo' as tipo, b.bloqueo_id as id, 
               DATE(b.fecha_inicio) as fecha, TIME(b.fecha_inicio) as hora_inicio, TIME(b.fecha_fin) as hora_fin, 
               'N/A' as codigo_unac, 'TODO EL LAB' as numero_equipo, l.nombre_lab, b.motivo, l.laboratorio_id, 
               'confirmada' as estado_reserva, '0' as usuario_id, NULL as hora_ingreso_real, NULL as hora_salida_real
        FROM bloqueoshorario b 
        JOIN laboratorios l ON b.laboratorio_id = l.laboratorio_id 
        $filtro_fecha_bloqueo
    ";

    $result_bloqueos = $conn->query($sql_bloqueos);
    if ($result_bloqueos) { 
        while ($row = $result_bloqueos->fetch_assoc()) { 
            $eventos[] = $row; 
        } 
    }

    // Ordenar por hora
    usort($eventos, function($a, $b) { 
        return $a['hora_inicio'] <=> $b['hora_inicio']; 
    });

    $response['success'] = true; 
    $response['data'] = $eventos;
}

// --- LÓGICA POST ---
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // --- CANCELAR ---
    if (isset($data['accion']) && $data['accion'] === 'cancelar') {
        if (!isset($data['reserva_id']) || !isset($data['usuario_id'])) {
            $response['message'] = 'Error: Faltan datos.';
        } else {
            $stmt = $conn->prepare("UPDATE reservas SET estado_reserva = 'cancelada' WHERE reserva_id = ? AND usuario_id = ? AND estado_reserva = 'confirmada'");
            $stmt->bind_param("ii", $data['reserva_id'], $data['usuario_id']);
            if ($stmt->execute()) {
                $response['success'] = ($stmt->affected_rows > 0);
                $response['message'] = ($stmt->affected_rows > 0) ? 'Reserva cancelada.' : 'No se pudo cancelar.';
            } else { $response['message'] = 'Error BD: ' . $stmt->error; }
            $stmt->close();
        }
    
    // --- CREAR RESERVA ---
    } elseif (isset($data['usuario_id'], $data['equipo_id'], $data['fecha'], $data['hora_inicio'], $data['hora_fin'])) {
        
        $usuario_id = $data['usuario_id'];
        $equipo_id = $data['equipo_id'];
        $fecha = $data['fecha'];
        $hora_inicio = $data['hora_inicio'];
        $hora_fin = $data['hora_fin'];

        // --- VALIDACIONES DE NEGOCIO (QA) ---
        date_default_timezone_set('America/Lima'); 
        $ahora = new DateTime();
        $inicio_reserva = new DateTime($fecha . ' ' . $hora_inicio);
        $fin_reserva = new DateTime($fecha . ' ' . $hora_fin);

        // 1. No pasado
        if ($inicio_reserva < $ahora) {
            $response['message'] = 'Error: No puedes reservar en una fecha u hora pasada.';
            $conn->close(); echo json_encode($response); exit;
        }
        // 2. Coherencia (Fin > Inicio)
        if ($fin_reserva <= $inicio_reserva) {
            $response['message'] = 'Error: La hora de fin debe ser mayor a la hora de inicio.';
            $conn->close(); echo json_encode($response); exit;
        }
        // 3. Duración mínima (30 min)
        $duracion_minutos = ($fin_reserva->getTimestamp() - $inicio_reserva->getTimestamp()) / 60;
        if ($duracion_minutos < 30) {
             $response['message'] = 'Error: La reserva debe durar al menos 30 minutos.';
             $conn->close(); echo json_encode($response); exit;
        }

        // --- 4. VALIDACIÓN HORARIO UNIVERSIDAD (8 AM - 10 PM) ---
        $hora_apertura = '08:00';
        $hora_cierre = '22:00';
        
        // Comparamos solo las horas (HH:MM) como strings, funciona bien en formato 24h
        if ($hora_inicio < $hora_apertura || $hora_fin > $hora_cierre || $hora_inicio >= $hora_cierre) {
            $response['message'] = 'Error: El horario de atención de la FIIS es de 08:00 AM a 10:00 PM.';
            $conn->close(); echo json_encode($response); exit;
        }
        // -------------------------------------------------------

        // Validar existencia equipo
        $lab_res = $conn->query("SELECT laboratorio_id FROM equipos WHERE equipo_id = $equipo_id");
        if ($lab_res->num_rows === 0) { $response['message'] = 'Equipo no existe.'; $conn->close(); echo json_encode($response); exit; }
        $laboratorio_id = $lab_res->fetch_assoc()['laboratorio_id'];

        $dt_inicio = $fecha . ' ' . $hora_inicio;
        $dt_fin = $fecha . ' ' . $hora_fin;

        // Validar Bloqueos
        $stmt_block = $conn->prepare("SELECT bloqueo_id FROM bloqueoshorario WHERE laboratorio_id = ? AND ((fecha_inicio < ? AND fecha_fin > ?) OR (fecha_inicio < ? AND fecha_fin > ?) OR (fecha_inicio >= ? AND fecha_fin <= ?))");
        $stmt_block->bind_param("issssss", $laboratorio_id, $dt_fin, $dt_inicio, $dt_inicio, $dt_fin, $dt_inicio, $dt_fin);
        $stmt_block->execute();
        
        if ($stmt_block->get_result()->num_rows > 0) {
            $response['message'] = 'Error: Laboratorio bloqueado por clase.';
        } else {
            // Validar Traslapes
            $stmt_res = $conn->prepare("SELECT reserva_id FROM reservas WHERE equipo_id = ? AND fecha_reserva = ? AND estado_reserva = 'confirmada' AND ((hora_inicio < ? AND hora_fin > ?) OR (hora_inicio < ? AND hora_fin > ?) OR (hora_inicio >= ? AND hora_fin <= ?))");
            $stmt_res->bind_param("isssssss", $equipo_id, $fecha, $hora_fin, $hora_inicio, $hora_inicio, $hora_fin, $hora_inicio, $hora_fin);
            $stmt_res->execute();

            if ($stmt_res->get_result()->num_rows > 0) {
                $response['message'] = 'Error: El equipo ya está reservado.';
            } else {
                // Insertar
                $stmt_ins = $conn->prepare("INSERT INTO reservas (usuario_id, equipo_id, fecha_reserva, hora_inicio, hora_fin, estado_reserva) VALUES (?, ?, ?, ?, ?, 'confirmada')");
                $stmt_ins->bind_param("iisss", $usuario_id, $equipo_id, $fecha, $hora_inicio, $hora_fin);
                if ($stmt_ins->execute()) {
                    $response['success'] = true; $response['message'] = '¡Reserva creada con éxito!';
                } else { $response['message'] = 'Error BD: ' . $stmt_ins->error; }
                $stmt_ins->close();
            }
            $stmt_res->close();
        }
        $stmt_block->close();
    } else { $response['message'] = 'Datos incompletos.'; }
}
$conn->close(); echo json_encode($response);
?>