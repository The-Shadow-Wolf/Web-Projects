<?php include 'includes/db.php'; ?><?php 
include 'includes/security.php'; // Primero verificamos seguridad
include 'includes/db.php';       // Luego conectamos BD
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Operaciones - Montacarga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <nav class="navbar navbar-dark bg-dark py-3 mb-4 shadow">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="fa-solid fa-arrow-left"></i> Volver</a>
            <span class="text-warning fw-bold"><i class="fa-solid fa-dolly"></i> Módulo de Montacargas</span>
        </div>
    </nav>

    <div class="container">
        <h4 class="text-light mb-4">Paletas en Piso (Pendientes de Ubicar)</h4>

        <div class="row">
            <?php
            // Solo mostramos inventario que esté en ubicaciones tipo 'PISO'
            $sql = "SELECT i.id_inventario, p.nombre, l.codigo_lote_proveedor, i.cantidad, u.codigo_ubicacion 
                    FROM inventario i
                    JOIN lotes l ON i.id_lote = l.id_lote
                    JOIN productos p ON l.id_producto = p.id_producto
                    JOIN ubicaciones u ON i.id_ubicacion = u.id_ubicacion
                    WHERE u.tipo = 'PISO'";
            
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card card-custom h-100 border-warning border-start border-4">
                        <div class="card-body">
                            <h5 class="card-title text-info fw-bold"><?php echo $row['nombre']; ?></h5>
                            <p class="card-text text-light mb-1">
                                <i class="fa-solid fa-barcode"></i> Lote: <span class="text-secondary"><?php echo $row['codigo_lote_proveedor']; ?></span>
                            </p>
                            <p class="card-text text-light mb-1">
                                <i class="fa-solid fa-cubes"></i> Cantidad: <span class="fw-bold"><?php echo $row['cantidad']; ?></span>
                            </p>
                            <p class="card-text text-light mb-3">
                                <i class="fa-solid fa-map-pin"></i> Actual: <span class="badge bg-secondary"><?php echo $row['codigo_ubicacion']; ?></span>
                            </p>
                            
                            <button class="btn btn-warning w-100 fw-bold text-dark" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalUbicar" 
                                    onclick="cargarDatos(<?php echo $row['id_inventario']; ?>, '<?php echo $row['nombre']; ?>')">
                                <i class="fa-solid fa-arrow-up-from-bracket"></i> MOVER A RACK
                            </button>
                        </div>
                    </div>
                </div>
            <?php 
                }
            } else {
                echo "<div class='alert alert-success text-center'>¡Todo ordenado! No hay paletas en el piso.</div>";
            }
            ?>
        </div>
    </div>

    <div class="modal fade" id="modalUbicar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card-custom">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title text-light" id="tituloModal">Mover Paleta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="guardar_ubicacion.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_inventario" id="inputIdInventario">
                        
                        <div class="mb-3">
                            <label class="form-label text-light">Seleccione Ubicación Final (Rack)</label>
                            <select class="form-select bg-dark text-light border-secondary" name="id_nueva_ubicacion" required>
                                <option value="" selected disabled>Escanee o seleccione Rack...</option>
                                <?php
                                // Cargar solo ubicaciones tipo RACK que estén disponibles
                                $sql_racks = "SELECT * FROM ubicaciones WHERE tipo = 'RACK'";
                                $res_racks = $conn->query($sql_racks);
                                while($r = $res_racks->fetch_assoc()){
                                    echo "<option value='".$r['id_ubicacion']."'>".$r['codigo_ubicacion']." - ".$r['zona']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold">Confirmar Ubicación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para pasar datos al modal
        function cargarDatos(id, nombre) {
            document.getElementById('inputIdInventario').value = id;
            document.getElementById('tituloModal').innerText = "Ubicando: " + nombre;
        }
    </script>
</body>
</html>