<?php
include 'includes/db.php';

$id_inventario = $_POST['id_inventario'];
$cantidad_retirar = $_POST['cantidad_retirar'];

// 1. Verificar stock actual
$sql_check = "SELECT cantidad FROM inventario WHERE id_inventario = '$id_inventario'";
$res = $conn->query($sql_check);
$fila = $res->fetch_assoc();

if ($fila['cantidad'] >= $cantidad_retirar) {
    
    // 2. Restar stock
    $nueva_cantidad = $fila['cantidad'] - $cantidad_retirar;
    
    // Si queda en 0, podríamos borrar la fila, pero mejor dejarla en 0 para histórico
    $sql_update = "UPDATE inventario SET cantidad = '$nueva_cantidad' WHERE id_inventario = '$id_inventario'";
    
    if ($conn->query($sql_update) === TRUE) {
        // Registrar el movimiento en una tabla de auditoría (Opcional, pero recomendado)
        // Por ahora solo redirigimos
        echo "<script>
                alert('Salida registrada correctamente. Stock restante: $nueva_cantidad');
                window.location.href='index.php';
              </script>";
    } else {
        echo "Error actualizando BD: " . $conn->error;
    }

} else {
    echo "<script>
            alert('Error: No hay suficiente stock. Disponible: " . $fila['cantidad'] . "');
            window.history.back();
          </script>";
}
?>