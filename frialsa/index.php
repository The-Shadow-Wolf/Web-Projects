<?php 
include 'includes/security.php'; // Primero verificamos seguridad
include 'includes/db.php';       // Luego conectamos BD
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frialsa WMS - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 shadow">
        <div class="container-fluid">
            <a class="navbar-brand p-0" href="index.php">
                <img src="assets/img/logo-frialsa.png" alt="Frialsa WMS" class="logo-white">
            </a>
            <div class="d-flex text-light align-items-center">
            <span class="me-3">
                    <i class="fa-solid fa-user-circle"></i> 
                    <?php echo $_SESSION['nombre']; ?> 
                    <small class="text-secondary ms-1">(<?php echo $_SESSION['rol']; ?>)</small>
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger border-0">
                    <i class="fa-solid fa-right-from-bracket"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card card-custom p-3 border-start border-5 border-danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-secondary">Críticos (< 30 días)</h6>
                            <?php 
                                $sql_rojo = "SELECT COUNT(*) as total FROM vista_dashboard_caducidad WHERE color_alerta = 'ROJO' OR color_alerta = 'VENCIDO'";
                                $res_rojo = $conn->query($sql_rojo);
                                $row_rojo = $res_rojo->fetch_assoc();
                            ?>
                            <h2 class="fw-bold text-danger"><?php echo $row_rojo['total']; ?></h2>
                        </div>
                        <i class="fa-solid fa-triangle-exclamation fa-2x text-danger opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card card-custom p-3 border-start border-5 border-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-secondary">Alerta Media (30-90 días)</h6>
                            <?php 
                                $sql_amarillo = "SELECT COUNT(*) as total FROM vista_dashboard_caducidad WHERE color_alerta = 'AMARILLO'";
                                $res_amarillo = $conn->query($sql_amarillo);
                                $row_amarillo = $res_amarillo->fetch_assoc();
                            ?>
                            <h2 class="fw-bold text-warning"><?php echo $row_amarillo['total']; ?></h2>
                        </div>
                        <i class="fa-solid fa-clock fa-2x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom p-3 border-start border-5 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-secondary">Estado Óptimo (> 90 días)</h6>
                            <?php 
                                $sql_verde = "SELECT COUNT(*) as total FROM vista_dashboard_caducidad WHERE color_alerta = 'VERDE'";
                                $res_verde = $conn->query($sql_verde);
                                $row_verde = $res_verde->fetch_assoc();
                            ?>
                            <h2 class="fw-bold text-success"><?php echo $row_verde['total']; ?></h2>
                        </div>
                        <i class="fa-solid fa-boxes-stacked fa-2x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

<div class="row mb-4">
            <h5 class="text-secondary mb-3"><i class="fa-solid fa-gamepad"></i> Operaciones</h5>
            
            <div class="col-md-3">
                <a href="ingreso.php" class="btn btn-outline-info w-100 p-4 shadow h-100 text-start border-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Ingreso</h5>
                            <small class="text-secondary">Recepción & Calidad</small>
                        </div>
                        <i class="fa-solid fa-truck-ramp-box fa-2x"></i>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="ubicacion.php" class="btn btn-outline-warning w-100 p-4 shadow h-100 text-start border-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Ubicación</h5>
                            <small class="text-secondary">Mover a Rack</small>
                        </div>
                        <i class="fa-solid fa-dolly fa-2x"></i>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="picking.php" class="btn btn-outline-success w-100 p-4 shadow h-100 text-start border-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Picking</h5>
                            <small class="text-secondary">Salida FEFO</small>
                        </div>
                        <i class="fa-solid fa-boxes-packing fa-2x"></i>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="reportes.php" class="btn btn-outline-light w-100 p-4 shadow h-100 text-start border-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Reportes</h5>
                            <small class="text-secondary">Imprimir Vencimientos</small>
                        </div>
                        <i class="fa-solid fa-print fa-2x"></i>
                    </div>
                </a>
            </div>
        </div>

        
        <div class="card card-custom p-4">
            <h4 class="mb-4"><i class="fa-solid fa-list-check"></i> Monitor de Caducidad (En tiempo real)</h4>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Lote</th>
                            <th>Ubicación</th>
                            <th>Vencimiento</th>
                            <th>Días Restantes</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM vista_dashboard_caducidad ORDER BY dias_restantes ASC";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                // Lógica de colores para la interfaz
                                $badgeClass = 'bg-secondary';
                                $icon = 'fa-check';
                                
                                if($row['color_alerta'] == 'ROJO') { $badgeClass = 'bg-danger badge-glow'; $icon = 'fa-skull'; }
                                if($row['color_alerta'] == 'VENCIDO') { $badgeClass = 'bg-dark border border-danger text-danger'; $icon = 'fa-ban'; }
                                if($row['color_alerta'] == 'AMARILLO') { $badgeClass = 'bg-warning text-dark'; $icon = 'fa-exclamation'; }
                                if($row['color_alerta'] == 'VERDE') { $badgeClass = 'bg-success'; $icon = 'fa-check-circle'; }
                                
                                echo "<tr>";
                                echo "<td class='fw-bold'>" . $row['Producto'] . "</td>";
                                echo "<td>" . $row['Lote'] . "</td>";
                                echo "<td><span class='badge bg-info text-dark'>" . $row['Ubicacion'] . "</span></td>";
                                echo "<td>" . $row['fecha_caducidad'] . "</td>";
                                echo "<td class='fw-bold'>" . $row['dias_restantes'] . " días</td>";
                                echo "<td><span class='badge rounded-pill $badgeClass'><i class='fa-solid $icon'></i> " . $row['color_alerta'] . "</span></td>";
                                echo "<td><button class='btn btn-sm btn-outline-light'><i class='fa-solid fa-eye'></i> Ver</button></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center text-muted'>No hay inventario registrado</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>