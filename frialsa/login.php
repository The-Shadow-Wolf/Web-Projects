<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Frialsa WMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.9)), url('https://source.unsplash.com/random/1920x1080/?warehouse,logistics');
            background-size: cover;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background-color: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>

    <div class="card card-custom login-card p-4 shadow-lg border-0">
        <div class="card-body text-center">
            
            <div class="mb-4">
                <img src="assets/img/logo-frialsa.png" alt="Frialsa Logo" class="img-fluid logo-white mb-3">
                <br>
                <small class="text-secondary fw-bold letter-spacing-1">SISTEMA WMS - ACCESO</small>
            </div>

            <?php 
            if(isset($_GET['error'])) {
                echo '<div class="alert alert-danger py-2 text-small"><i class="fa-solid fa-circle-exclamation"></i> Credenciales incorrectas</div>';
            }
            ?>

            <form action="auth.php" method="POST">
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="emailInput" name="correo" placeholder=" " required>
                    <label for="emailInput" class="text-dark">Correo Electrónico</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="passInput" name="password" placeholder=" " required>
                    <label for="passInput" class="text-dark">Contraseña</label>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-info btn-lg fw-bold text-dark">
                        INICIAR SESIÓN <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </form>

            <div class="mt-4 text-secondary text-small">
                <small>&copy; 2025 Frialsa Logística</small>
            </div>
        </div>
    </div>

</body>
</html>