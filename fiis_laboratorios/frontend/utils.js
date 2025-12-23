// /frontend/utils.js
function showToast(message, type = 'info') {
    // Crear contenedor si no existe
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    // Iconos según tipo
    let icon = 'fa-info-circle';
    if (type === 'success') icon = 'fa-check-circle';
    if (type === 'error') icon = 'fa-exclamation-triangle';

    // Crear el Toast
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<i class="fas ${icon}"></i> <span>${message}</span>`;

    container.appendChild(toast);

    // Auto-eliminar a los 3 segundos
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease-out forwards';
        toast.addEventListener('animationend', () => toast.remove());
    }, 3000);
}

// --- NUEVA FUNCIÓN: CERRAR SESIÓN SEGURA ---
function logout() {
    // 1. Limpiar TODA la memoria de sesión (Borrón y cuenta nueva)
    sessionStorage.clear();
    
    // 2. Opcional: Limpiar localStorage si alguna vez lo usamos
    localStorage.clear();

    // 3. Redirigir al Login
    window.location.href = 'index.html';
}