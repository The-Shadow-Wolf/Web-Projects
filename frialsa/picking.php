<?php 
include 'includes/security.php'; // Primero verificamos seguridad
include 'includes/db.php';       // Luego conectamos BD
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Picking & Salidas - Frialsa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <nav class="navbar navbar-dark bg-dark py-3 mb-4 shadow">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="fa-solid fa-arrow-left"></i> Volver</a>
            <span class="text-success fw-bold"><i class="fa-solid fa-dolly-flatbed"></i> Picking (Salidas)</span>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-custom p-4 border-success border-top border-4">
                    <h4 class="mb-4 text-white"><i class="fa-solid fa-box-open"></i> Orden de Salida (FEFO)</h4>
                    
                    <form action="procesar_salida.php" method="POST">
                        
                        <div class="mb-4">
                            <label class="form-label text-info">1. ¿Qué producto solicita el cliente?</label>
                            <select class="form-select bg-dark text-light border-secondary p-3" id="selectProducto" name="id_producto" required onchange="buscarMejorLote()">
                                <option value="" selected disabled>Seleccione producto...</option>
                                <?php
                                $sql = "SELECT * FROM productos";
                                $res = $conn->query($sql);
                                while($p = $res->fetch_assoc()){
                                    echo "<option value='".$p['id_producto']."'>".$p['nombre']."</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div id="areaSugerencia" style="display:none;" class="p-3 mb-4 bg-dark border border-secondary rounded">
                            <h6 class="text-warning mb-3"><i class="fa-solid fa-robot"></i> El sistema sugiere retirar:</h6>
                            
                            <div class="row text-center">
                                <div class="col-6 mb-2">
                                    <small class="text-secondary">Ubicación</small>
                                    <h3 class="text-white fw-bold" id="txtUbicacion">---</h3>
                                </div>
                                <div class="col-6 mb-2">
                                    <small class="text-secondary">Lote (Vence Primero)</small>
                                    <h4 class="text-white" id="txtLote">---</h4>
                                </div>
                            </div>
                            
                            <div class="alert alert-dark border-warning d-flex align-items-center" role="alert">
                                <i class="fa-solid fa-calendar-xmark text-warning me-3 fa-2x"></i>
                                <div>
                                    Vence el: <strong id="txtVencimiento" class="text-white">---</strong>
                                </div>
                            </div>

                            <input type="hidden" name="id_inventario" id="inputInventarioID">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Escaneo Código de Barras</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-secondary border-secondary text-white"><i class="fa-solid fa-barcode"></i></span>
                                    <input type="text" class="form-control bg-dark text-light border-secondary" id="inputScan" placeholder="Escanear para validar...">
                                </div>
                                <small id="msgValidacion" class="mt-1 d-block"></small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cantidad a Retirar</label>
                                <input type="number" name="cantidad_retirar" class="form-control bg-dark text-light border-secondary fw-bold" min="1" required>
                                <small class="text-secondary">Disponible: <span id="txtDisponible">0</span></small>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-success btn-lg fw-bold">
                                <i class="fa-solid fa-check-circle"></i> Confirmar Salida
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Lógica AJAX para buscar el lote FEFO
        async function buscarMejorLote() {
            const idProd = document.getElementById('selectProducto').value;
            const area = document.getElementById('areaSugerencia');
            
            // Llamamos a nuestra API invisible
            const response = await fetch(`api_buscar_fefo.php?id_producto=${idProd}`);
            const data = await response.json();

            if (data.error) {
                alert(data.error);
                area.style.display = 'none';
            } else {
                area.style.display = 'block';
                // Llenamos los datos en pantalla
                document.getElementById('txtUbicacion').innerText = data.codigo_ubicacion;
                document.getElementById('txtLote').innerText = data.codigo_lote_proveedor;
                document.getElementById('txtVencimiento').innerText = data.fecha_caducidad;
                document.getElementById('txtDisponible').innerText = data.cantidad;
                
                // Guardamos el ID real del inventario para procesarlo
                document.getElementById('inputInventarioID').value = data.id_inventario;

                // Guardamos el código de barras correcto para validar luego
                document.getElementById('inputScan').dataset.codigoCorrecto = data.codigo_barras_generado;
            }
        }

        // Simulación de Validación de Pistola
        document.getElementById('inputScan').addEventListener('input', function(e) {
            const codigoEscaneado = e.target.value;
            const codigoCorrecto = e.target.dataset.codigoCorrecto;
            const msg = document.getElementById('msgValidacion');

            if(codigoEscaneado === codigoCorrecto) {
                msg.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check"></i> Lote Correcto</span>';
                e.target.classList.add('is-valid');
                e.target.classList.remove('is-invalid');
            } else {
                msg.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-xmark"></i> Lote Incorrecto</span>';
                e.target.classList.add('is-invalid');
            }
        });
    </script>
</body>
</html>