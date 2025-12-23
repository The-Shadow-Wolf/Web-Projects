<?php
include 'includes/db.php';

$id_inventario = $_POST['id_inventario'];
$id_nueva_ubicacion = $_POST['id_nueva_ubicacion'];

// Actualizamos la ubicación en la tabla inventario
$sql = "UPDATE inventario SET id_ubicacion = '$id_nueva_ubicacion' WHERE id_inventario = '$id_inventario'";

if ($conn->query($sql) === TRUE) {
    // Éxito: volvemos a la lista
    header("Location: ubicacion.php");
} else {
    echo "Error actualizando registro: " . $conn->error;
}
?>