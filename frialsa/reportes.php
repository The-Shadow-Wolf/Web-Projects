<?php 
include 'includes/security.php'; // Primero verificamos seguridad
include 'includes/db.php';       // Luego conectamos BD
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Caducidad - Frialsa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Estilos específicos para impresión */
        @media print {
            body { background-color: white !important; color: black !important; }
            .no-print { display: none !important; } /* Ocultar botones al imprimir */
            .card { border: none !important; box-shadow: none !important; }
            .badge { border: 1px solid #000; color: #000 !important; }
        }
        body { background-color: #f8f9fa; } /* Fondo gris claro para vista normal */
    </style>
</head>
<body class="p-5">

    <div class="container bg-white p-5 shadow rounded">
        
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <div>
                <img src="assets/img/logo-frialsa.png" alt="Frialsa Logo" style="height: 70px; width: auto;">
                <h5 class="text-secondary">Reporte de Control de Caducidad</h5>
            </div>
            <div class="text-end">
                <p class="mb-0"><strong>Fecha Emisión:</strong> <?php echo date("d/m/Y H:i"); ?></p>
                <p class="mb-0"><strong>Generado por:</strong> Admin General</p>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4 no-print">
            <a href="index.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Volver</a>
            <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Imprimir / Guardar PDF</button>
        </div>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Producto</th>
                    <th>Lote</th>
                    <th>Ubicación</th>
                    <th>F. Producción</th>
                    <th>F. Vencimiento</th>
                    <th>Días Restantes</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Reutilizamos la vista, pero ordenamos por los más urgentes
                $sql = "SELECT * FROM vista_dashboard_caducidad ORDER BY dias_restantes ASC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        
                        // Lógica visual simple para el papel
                        $estado = $row['color_alerta'];
                        $colorTexto = ($estado == 'ROJO' || $estado == 'VENCIDO') ? 'text-danger fw-bold' : 'text-dark';

                        echo "<tr>";
                        echo "<td>" . $row['Producto'] . "</td>";
                        echo "<td>" . $row['Lote'] . "</td>";
                        echo "<td>" . $row['Ubicacion'] . "</td>";
                        
                        // Consultar fecha prod extra (join rápido manual o traer de vista si la agregas)
                        // Por simplicidad, calculamos la fecha prod aproximada o mostramos solo vencimiento
                        // Aquí solo mostraremos lo que ya trae la vista para no complicar el SQL
                        echo "<td class='text-center'>--</td>"; 
                        
                        echo "<td class='fw-bold'>" . $row['fecha_caducidad'] . "</td>";
                        echo "<td class='$colorTexto'>" . $row['dias_restantes'] . "</td>";
                        echo "<td>" . $estado . "</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>

        <div class="mt-5 border-top pt-3">
            <p class="text-center text-muted text-small">Documento generado automáticamente por Sistema WMS Frialsa.</p>
        </div>
    </div>

</body>
</html>