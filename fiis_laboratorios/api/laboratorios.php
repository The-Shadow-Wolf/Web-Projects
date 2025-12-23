<?php
// /api/laboratorios.php
// Este script devuelve todos los laboratorios y sus equipos

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // (CORS)

require_once 'config/database.php';

$response = ['success' => false, 'data' => []];

// Consulta para traer los laboratorios y los equipos anidados
$sql = "
    SELECT 
        l.laboratorio_id, 
        l.nombre_lab, 
        l.ubicacion,
        e.equipo_id,
        e.numero_equipo,
        e.estado_equipo
    FROM laboratorios l
    LEFT JOIN equipos e ON l.laboratorio_id = e.laboratorio_id
    ORDER BY l.nombre_lab, e.numero_equipo;
";

$result = $conn->query($sql);

if ($result) {
    $laboratorios = [];
    while ($row = $result->fetch_assoc()) {
        $lab_id = $row['laboratorio_id'];
        
        // Si el laboratorio no está en el array, lo agregamos
        if (!isset($laboratorios[$lab_id])) {
            $laboratorios[$lab_id] = [
                'laboratorio_id' => $lab_id,
                'nombre_lab' => $row['nombre_lab'],
                'ubicacion' => $row['ubicacion'],
                'equipos' => []
            ];
        }

        // Si el laboratorio tiene equipos, los agregamos
        if ($row['equipo_id'] != null) {
            $laboratorios[$lab_id]['equipos'][] = [
                'equipo_id' => $row['equipo_id'],
                'numero_equipo' => $row['numero_equipo'],
                'estado_equipo' => $row['estado_equipo']
            ];
        }
    }
    
    // Re-indexar el array para que sea una lista
    $response['success'] = true;
    $response['data'] = array_values($laboratorios);

} else {
    $response['message'] = 'Error en la consulta: ' . $conn->error;
}

$conn->close();
echo json_encode($response);
?>