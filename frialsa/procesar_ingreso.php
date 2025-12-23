<?php
include 'includes/db.php';

// Recibir datos del formulario
$id_producto = $_POST['id_producto'];
$temperatura = $_POST['temperatura'];
$estado_calidad = $_POST['estado_calidad'];
$lote_prov = $_POST['lote_proveedor'];
$fecha_prod = $_POST['fecha_prod'];
$fecha_venc = $_POST['fecha_venc'];

// 1. Insertar en RECEPCIÓN / CALIDAD (Siempre se registra el evento)
$sql_recepcion = "INSERT INTO recepciones_calidad (id_producto, temperatura_medida, estado_calidad, id_usuario_calidad) 
                  VALUES ('$id_producto', '$temperatura', '$estado_calidad', 1)"; // 1 es el ID usuario admin por defecto

if ($conn->query($sql_recepcion) === TRUE) {
    $id_recepcion = $conn->insert_id; // Obtenemos el ID generado

    // 2. Si NO fue rechazado, creamos el LOTE y el INVENTARIO
    if ($estado_calidad != 'RECHAZADO') {
        
        // Generar código de barras único ficticio
        $codigo_barras = "BAR-" . time() . rand(100,999);

        // Crear Lote
        $sql_lote = "INSERT INTO lotes (id_producto, id_recepcion, codigo_lote_proveedor, fecha_produccion, fecha_caducidad, codigo_barras_generado)
                     VALUES ('$id_producto', '$id_recepcion', '$lote_prov', '$fecha_prod', '$fecha_venc', '$codigo_barras')";
        
        if ($conn->query($sql_lote) === TRUE) {
            $id_lote = $conn->insert_id;

            // Buscar ID de ubicación "PISO-REC-01" (Asegúrate que exista en tu BD, si no usa id 1)
            // En el script anterior insertamos 'PISO-REC-01' como ID 1
            $id_ubicacion_inicial = 1; 

            // Insertar Inventario (Ponerlo en Piso)
            $sql_inv = "INSERT INTO inventario (id_lote, id_ubicacion, cantidad, unidad_medida)
                        VALUES ('$id_lote', '$id_ubicacion_inicial', 100, 'Cajas')"; // Cantidad default 100 para demo
            
            $conn->query($sql_inv);
        }
    }

    // Redirigir al Dashboard con éxito
    echo "<script>
            alert('Ingreso registrado correctamente. Estado: $estado_calidad');
            window.location.href='index.php';
          </script>";

} else {
    echo "Error: " . $sql_recepcion . "<br>" . $conn->error;
}
?>