<?php
include 'includes/db.php';

// Este script responde en formato JSON (datos puros)
header('Content-Type: application/json');

if (isset($_GET['id_producto'])) {
    $id = $_GET['id_producto'];

    // LA LÓGICA MAESTRA: FEFO
    // Buscamos el lote con fecha_caducidad más próxima que tenga stock
    $sql = "SELECT 
                i.id_inventario,
                i.cantidad,
                l.codigo_lote_proveedor,
                l.fecha_caducidad,
                l.codigo_barras_generado,
                u.codigo_ubicacion
            FROM inventario i
            JOIN lotes l ON i.id_lote = l.id_lote
            JOIN ubicaciones u ON i.id_ubicacion = u.id_ubicacion
            WHERE l.id_producto = '$id' 
            AND i.cantidad > 0 
            AND u.tipo = 'RACK' 
            ORDER BY l.fecha_caducidad ASC 
            LIMIT 1";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(["error" => "No hay stock disponible en Racks"]);
    }
}
?>