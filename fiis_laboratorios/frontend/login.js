// login.js - Versión Segura y Visual

const API_URL = '../api/login.php'; 

document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();

    // 1. Limpiar mensajes previos
    const errorDiv = document.getElementById('error-message');
    errorDiv.style.display = 'none';
    errorDiv.textContent = '';
    errorDiv.classList.remove('shake'); // Quitar animación previa

    // 2. Capturar datos
    const codigo = document.getElementById('codigo').value.trim();
    const password = document.getElementById('password').value.trim();
    const btnSubmit = document.querySelector('button[type="submit"]');

    if (!codigo || !password) {
        mostrarError('Por favor ingrese usuario y contraseña.');
        return;
    }

    // Efecto de carga en botón
    const textoOriginal = btnSubmit.innerHTML;
    btnSubmit.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Verificando...';
    btnSubmit.disabled = true;

    // 3. Enviar petición
    fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ codigo: codigo, password: password })
    })
    .then(response => {
        if (!response.ok) throw new Error('Error en el servidor');
        return response.json();
    })
    .then(result => {
        if (result.success) {
            // --- LOGIN EXITOSO ---
            // Guardamos datos de sesión
            sessionStorage.setItem('usuario_id', result.data.usuario_id);
            sessionStorage.setItem('nombre_usuario', result.data.nombre);
            sessionStorage.setItem('rol_id', result.data.rol_id);
            sessionStorage.setItem('codigo', result.data.codigo);

            // Redirección según rol
            if (result.data.rol_id == 2) { 
                window.location.href = 'admin.html'; // Admin
            } else {
                window.location.href = 'dashboard.html'; // Estudiante
            }
        } else {
            // --- LOGIN FALLIDO (Seguridad) ---
            // No importa si falló el usuario o la clave, mostramos el mismo error.
            // "Usuario o contraseña incorrectos" es el estándar seguro.
            mostrarError('Usuario o contraseña incorrectos.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('No se pudo conectar con el servidor.');
    })
    .finally(() => {
        // Restaurar botón
        btnSubmit.innerHTML = textoOriginal;
        btnSubmit.disabled = false;
    });
});

function mostrarError(mensaje) {
    const errorDiv = document.getElementById('error-message');
    
    // Inyectar mensaje
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${mensaje}`;
    errorDiv.style.display = 'block';
    
    // Forzar reinicio de animación CSS (Hack del Reflow)
    void errorDiv.offsetWidth; 
    
    // Agregar clase para animar
    errorDiv.classList.add('shake');
}