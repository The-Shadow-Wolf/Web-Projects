<?php 
include 'includes/security.php'; // Primero verificamos seguridad
include 'includes/db.php';       // Luego conectamos BD
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingreso de Mercadería - Frialsa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <nav class="navbar navbar-dark bg-dark py-3 mb-4 shadow">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="fa-solid fa-arrow-left"></i> Volver al Dashboard</a>
            <span class="text-light">Módulo de Recepción</span>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-custom p-4">
                    <h3 class="mb-4 text-info"><i class="fa-solid fa-truck-ramp-box"></i> Nuevo Ingreso & Calidad</h3>
                    
                    <form action="procesar_ingreso.php" method="POST">
                        
                        <h5 class="text-info border-bottom border-secondary pb-2 mb-4">
                            <i class="fa-solid fa-list-check"></i> 1. Validación de Calidad
                        </h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Producto</label>
                            <select class="form-select bg-dark text-light border-secondary" name="id_producto" id="selectProducto" required>
                                <option value="" selected disabled>Seleccione producto...</option>
                                <?php
                                // Cargamos los productos y sus rangos de temperatura en atributos data-
                                $sql = "SELECT * FROM productos";
                                $res = $conn->query($sql);
                                while($p = $res->fetch_assoc()){
                                    echo "<option value='".$p['id_producto']."' 
                                            data-min='".$p['temp_min']."' 
                                            data-max='".$p['temp_max']."'>
                                            ".$p['nombre']." (Rango: ".$p['temp_min']."° a ".$p['temp_max']."°)
                                          </option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Temperatura Medida (°C)</label>
                                <input type="number" step="0.1" class="form-control bg-dark text-light border-secondary" name="temperatura" id="inputTemp" required placeholder="Ej: -18.5">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado Calculado</label>
                                <div id="resultadoCalidad" class="alert py-2 text-center" style="display:none;">
                                    </div>
                                <input type="hidden" name="estado_calidad" id="inputEstadoHidden">
                            </div>
                        </div>

                        <h6 class="text-secondary border-bottom pb-2 mb-3 mt-4">2. Datos de Lote y Logística</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lote Proveedor</label>
                                <input type="text" class="form-control bg-dark text-light border-secondary" name="lote_proveedor" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código Interno (Generado)</label>
                                <input type="text" class="form-control bg-dark text-secondary border-secondary" value="AUTOGENERADO" disabled>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha Producción</label>
                                <input type="date" class="form-control bg-dark text-light border-secondary" name="fecha_prod" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha Caducidad</label>
                                <input type="date" class="form-control bg-dark text-light border-secondary" name="fecha_venc" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                             <label class="form-label">Ubicación Inicial</label>
                             <input type="text" class="form-control bg-dark text-info border-info fw-bold" value="PISO-TEMP (Recepción)" disabled>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg" id="btnGuardar">
                                <i class="fa-solid fa-save"></i> Registrar Ingreso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const selectProd = document.getElementById('selectProducto');
        const inputTemp = document.getElementById('inputTemp');
        const divResultado = document.getElementById('resultadoCalidad');
        const inputHidden = document.getElementById('inputEstadoHidden');
        const btnGuardar = document.getElementById('btnGuardar');

        function validarTemperatura() {
            if (selectProd.value === "" || inputTemp.value === "") return;

            // Obtener límites del producto seleccionado
            const option = selectProd.options[selectProd.selectedIndex];
            const min = parseFloat(option.getAttribute('data-min'));
            const max = parseFloat(option.getAttribute('data-max'));
            const actual = parseFloat(inputTemp.value);

            divResultado.style.display = 'block';
            
            // Lógica según tu explicación:
            // 1. Si está en rango -> ACEPTADO
            // 2. Si se pasa por 1 o 2 grados -> ADECUACION
            // 3. Si se pasa por más -> RECHAZADO

            if (actual >= min && actual <= max) {
                divResultado.className = 'alert alert-success py-2 text-center fw-bold';
                divResultado.innerHTML = '<i class="fa-solid fa-check"></i> ACEPTADO';
                inputHidden.value = 'ACEPTADO';
                btnGuardar.disabled = false;
            } 
            else if (actual > max && actual <= (max + 2)) {
                divResultado.className = 'alert alert-warning py-2 text-center fw-bold';
                divResultado.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ADECUACIÓN (Enfriar)';
                inputHidden.value = 'ADECUACION';
                btnGuardar.disabled = false;
            } 
            else {
                divResultado.className = 'alert alert-danger py-2 text-center fw-bold';
                divResultado.innerHTML = '<i class="fa-solid fa-ban"></i> RECHAZADO (Quiebre de frío)';
                inputHidden.value = 'RECHAZADO';
                // Opcional: Bloquear botón si es rechazado
                // btnGuardar.disabled = true; 
            }
        }

        selectProd.addEventListener('change', validarTemperatura);
        inputTemp.addEventListener('input', validarTemperatura);
    </script>
</body>
</html>