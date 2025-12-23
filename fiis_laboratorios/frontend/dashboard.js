// /frontend/dashboard.js
// VERSIÓN 5.0 - Modales, Filtros y Lógica de Estados Completa

const API_LABORATORIOS = '../api/laboratorios.php';
const API_RESERVAS = '../api/reservas.php';

// ... Constantes API ...

// --- LÓGICA DE MODO OSCURO ---
const toggleBtn = document.getElementById('theme-toggle');
// Verificar si ya había guardado el tema antes
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
    if(toggleBtn) toggleBtn.textContent = '☀️';
}

if(toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        let currentTheme = document.documentElement.getAttribute('data-theme');
        let newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme); // Guardar en memoria del navegador
        toggleBtn.textContent = newTheme === 'dark' ? '☀️' : '🌙';
    });
}

// --- SALUDO PERSONALIZADO E INTELIGENTE ---
// --- SALUDO PERSONALIZADO (CORREGIDO PARA VISIBILIDAD) ---
function mostrarSaludo() {
    const hora = new Date().getHours();
    let saludo = "Hola";
    if (hora >= 5 && hora < 12) saludo = "Buenos días ☀️";
    else if (hora >= 12 && hora < 18) saludo = "Buenas tardes 🌤️";
    else saludo = "Buenas noches 🌙";

    // 1. Recuperar datos
    const nombreGuardado = sessionStorage.getItem('nombre_usuario');
    const rolId = sessionStorage.getItem('rol_id');

    // 2. Definir etiqueta y nombre
    let nombreMostrar = nombreGuardado || 'Usuario';
    let etiquetaRol = 'Estudiante FIIS';
    let claseRol = 'badge-estudiante';

    if (rolId === '2') { // ADMIN
        if (!nombreGuardado) nombreMostrar = 'Administrador';
        etiquetaRol = 'Administrador del Sistema';
        claseRol = 'badge-admin';
    }

    // 3. Inyectar en el HTML
    const titulo = document.getElementById('saludo-usuario');
    if (titulo) {
        // CORRECCIÓN AQUÍ: Cambiamos el color a BLANCO y negrita para que se vea sobre el fondo azul
        titulo.innerHTML = `${saludo}, <span style="color:#ffffff; font-weight:900; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">${nombreMostrar}</span>`;
        
        // Agregar la etiqueta (Badge)
        const contenedorPadre = titulo.parentElement;
        
        // Limpiar etiqueta previa si existe
        const etiquetaExistente = contenedorPadre.querySelector('.badge-rol-sistema');
        if (etiquetaExistente) etiquetaExistente.remove();

        const divRol = document.createElement('div');
        divRol.className = `badge-rol-sistema ${claseRol}`;
        divRol.innerHTML = `<i class="fas fa-user-tag"></i> ${etiquetaRol}`;
        
        contenedorPadre.appendChild(divRol);
    }
}



// Ejecutar saludo al iniciar
mostrarSaludo();

let laboratoriosData = [];
let calendarioEventos = [];
let mi_usuario_id = null;
let mi_rol_id = null;
let reservaIdACancelar = null; // Para el modal

document.addEventListener('DOMContentLoaded', () => {
    
    mi_usuario_id = sessionStorage.getItem('usuario_id');
    mi_rol_id = sessionStorage.getItem('rol_id');

    if (!mi_usuario_id) {
        alert('Debe iniciar sesión.');
        window.location.href = 'index.html';
        return;
    }

    // Inyección Botón Admin
    if (mi_rol_id === '2') { 
        const adminContainer = document.getElementById('admin-controls');
        if (adminContainer) {
            const btnAdmin = document.createElement('button');
            btnAdmin.textContent = 'Ir al Panel Admin';
            btnAdmin.className = 'btn-admin-nav';
            btnAdmin.onclick = () => window.location.href = 'admin.html';
            adminContainer.appendChild(btnAdmin);
        }
    }
    
    // Cargar datos
    recargarTodo();
    
    // Configurar Eventos UI
    configurarFormulario();
    
    // Configurar Filtro de "Mis Reservas"
    const filtroMisReservas = document.getElementById('filtro-estado-reserva');
    if (filtroMisReservas) {
        filtroMisReservas.addEventListener('change', renderizarMisReservas);
    }

    // Configurar Modal de Cancelación
    const btnConfirmCancel = document.getElementById('btn-confirm-action');
    if (btnConfirmCancel) {
        btnConfirmCancel.addEventListener('click', ejecutarCancelacion);
    }
});

function mostrarSkeleton() {
    const mapaGrid = document.getElementById('mapa-maquinas-grid');
    // Generamos 20 cajitas grises pulsantes
    mapaGrid.innerHTML = Array(20).fill('<div class="pc skeleton"></div>').join('');
}

function recargarTodo() {

    mostrarSkeleton();
    Promise.all([
        
        fetch(API_RESERVAS).then(res => res.json()),
        fetch(API_LABORATORIOS).then(res => res.json())
    ])
    .then(([resultEventos, resultLabs]) => {
        if (resultEventos.success) {
            calendarioEventos = resultEventos.data;
            renderizarCalendarioGeneral();
            renderizarMisReservas();
            renderizarMapaMaquinas(); // Redibujar mapa si había selección
        }
        if (resultLabs.success) {
            laboratoriosData = resultLabs.data;
            popularDropdownLaboratorios();
        }
    })
    .catch(error => console.error('Error cargando datos:', error));
}

function configurarFormulario() {
    const selectLab = document.getElementById('laboratorio');
    const form = document.getElementById('form-nueva-reserva');
    const fechaInput = document.getElementById('fecha');
    const horaInicioInput = document.getElementById('hora_inicio');
    const horaFinInput = document.getElementById('hora_fin');

    [selectLab, fechaInput, horaInicioInput, horaFinInput].forEach(el => {
        el.addEventListener('change', renderizarMapaMaquinas);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault(); 
        const datosReserva = {
            accion: 'crear', 
            usuario_id: mi_usuario_id,
            equipo_id: document.getElementById('equipo_id_seleccionado').value,
            fecha: fechaInput.value,
            hora_inicio: horaInicioInput.value,
            hora_fin: horaFinInput.value
        };

        if (!datosReserva.equipo_id) return showToast('Seleccione una PC del mapa.', 'warning');
        if (!datosReserva.fecha) return showToast('Seleccione una fecha.', 'warning');
        
        enviarDatosReserva(datosReserva);
    });
}

// --- LÓGICA DE CANCELACIÓN CON MODAL ---
function solicitarCancelacion(reservaId) {
    reservaIdACancelar = reservaId;
    document.getElementById('confirm-modal').classList.add('active');
}

function ejecutarCancelacion() {
    if (!reservaIdACancelar) return;
    
    // Cerrar modal
    document.getElementById('confirm-modal').classList.remove('active');
    
    // Enviar petición
    enviarDatosReserva({
        accion: 'cancelar',
        reserva_id: reservaIdACancelar,
        usuario_id: mi_usuario_id
    });
    
    reservaIdACancelar = null;
}

function enviarDatosReserva(datos) {
    // Guardar valores temporales
    const fechaVal = document.getElementById('fecha').value;
    const horaInicioVal = document.getElementById('hora_inicio').value;
    const horaFinVal = document.getElementById('hora_fin').value;
mostrarSkeleton();
    fetch(API_RESERVAS, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast(result.message, 'success');
            
            if (datos.accion === 'crear') {
                document.getElementById('form-nueva-reserva').reset();
                document.getElementById('equipo_id_seleccionado').value = '';
            }

            // Recargar datos
            mostrarSkeleton();
            fetch(API_RESERVAS).then(res => res.json()).then(resultEventos => {
                calendarioEventos = resultEventos.data;
                renderizarCalendarioGeneral(); 
                renderizarMisReservas();
                
                // Restaurar para el mapa
                document.getElementById('fecha').value = fechaVal;
                document.getElementById('hora_inicio').value = horaInicioVal;
                document.getElementById('hora_fin').value = horaFinVal;
                renderizarMapaMaquinas();

                if (datos.accion === 'crear') { // Limpiar si fue éxito de creación
                    document.getElementById('fecha').value = '';
                }
            });
        } else {
            showToast(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error POST:', error);
        showToast('Error de conexión.', 'error');
    });
}

// --- RENDERIZADO "MIS RESERVAS" (CON FILTROS) ---
function renderizarMisReservas() {
    const listaMisReservas = document.getElementById('lista-mis-reservas');
    const filtro = document.getElementById('filtro-estado-reserva').value; // 'todos', 'activos', 'completados'
    
    if (!listaMisReservas) return;
    listaMisReservas.innerHTML = '';
    
    const misReservas = calendarioEventos.filter(evento => 
        evento.tipo === 'reserva' &&
        String(evento.usuario_id) === String(mi_usuario_id) &&
        evento.estado_reserva === 'confirmada' // Solo mostramos confirmadas (no canceladas históricas para no ensuciar)
    );
    
    if (misReservas.length > 0) {
        let contadorVisible = 0;

        misReservas.forEach(reserva => {
            // Lógica de Estado
            let estado = 'pendiente'; // default
            if (reserva.hora_ingreso_real && reserva.hora_salida_real) estado = 'completado';
            else if (reserva.hora_ingreso_real) estado = 'curso';

            // Lógica de Filtro
            if (filtro === 'activos' && estado === 'completado') return; // Saltar
            if (filtro === 'completados' && estado !== 'completado') return; // Saltar

            contadorVisible++;
            const li = document.createElement('li');
            
            let badgeHtml = '';
            let btnHtml = '';

            if (estado === 'completado') {
                li.style.borderLeftColor = 'var(--text-muted)'; // Gris
                badgeHtml = `<span class="badge badge-success">Completado</span>`;
                // NO BOTÓN CANCELAR
            } else if (estado === 'curso') {
                li.style.borderLeftColor = 'var(--info)'; // Azul
                badgeHtml = `<span class="badge badge-info">En Curso</span>`;
                // NO BOTÓN CANCELAR (Ya entró)
            } else {
                li.style.borderLeftColor = 'var(--success)'; // Verde
                badgeHtml = `<span class="badge badge-warning">Pendiente</span>`;
                // SÍ BOTÓN CANCELAR
                btnHtml = `<button class="btn-danger" style="width:auto; padding:5px 10px; font-size:0.8rem;" onclick="solicitarCancelacion(${reserva.id})">Cancelar</button>`;
            }
            
            li.innerHTML = `
                <div style="display:flex; justify-content:space-between; width:100%; align-items:center;">
                    <div>
                        <b>${reserva.nombre_lab} (PC-${reserva.numero_equipo})</b><br>
                        ${reserva.fecha} (${reserva.hora_inicio} - ${reserva.hora_fin})<br>
                        ${badgeHtml}
                    </div>
                    <div>${btnHtml}</div>
                </div>
            `;
            
            listaMisReservas.appendChild(li);
        });

        if (contadorVisible === 0) {
            listaMisReservas.innerHTML = '<li style="background:transparent; border:none; padding:0; color:#999;">No hay reservas en esta categoría.</li>';
        }

    } else {
        listaMisReservas.innerHTML = '<li style="background:transparent; border:none; padding:0; color:#999;">No tienes reservas activas.</li>';
    }
}

// --- RENDERIZADO CALENDARIO GENERAL (Solo HOY) ---
function renderizarCalendarioGeneral() {
    const listaReservas = document.getElementById('lista-reservas');
    if (!listaReservas) return;
    listaReservas.innerHTML = ''; 

    const hoy = new Date().toLocaleDateString('en-CA');

    const eventosGenerales = calendarioEventos.filter(e => 
        e.fecha === hoy && 
        (e.tipo === 'bloqueo' || (e.tipo === 'reserva' && e.estado_reserva === 'confirmada'))
    );

    if (eventosGenerales.length > 0) {
        eventosGenerales.forEach(evento => {
            const li = document.createElement('li');
            if (evento.tipo === 'bloqueo') {
                li.classList.add('bloqueo');
                li.innerHTML = `<div><span class="badge badge-danger">CLASE</span> <b>${evento.nombre_lab}</b><br>${evento.motivo}</div><small>${evento.hora_inicio} - ${evento.hora_fin}</small>`;
            } else {
                li.innerHTML = `<div><span class="badge badge-info">RESERVA</span> <b>${evento.nombre_lab}</b><br>PC-${evento.numero_equipo}</div><small>${evento.hora_inicio} - ${evento.hora_fin}</small>`;
            }
            listaReservas.appendChild(li);
        });
    } else {
        listaReservas.innerHTML = '<li style="background:transparent; border:none; padding:0; color:#999;">Laboratorios libres el resto del día.</li>';
    }
}

// --- MAPA y DROPDOWN (Sin cambios mayores) ---
function popularDropdownLaboratorios() {
    const selectLab = document.getElementById('laboratorio');
    if (!selectLab) return;
    selectLab.innerHTML = '<option value="">Seleccione un laboratorio</option>';
    laboratoriosData.forEach(lab => {
        const option = document.createElement('option');
        option.value = lab.laboratorio_id;
        option.textContent = `${lab.nombre_lab} (${lab.equipos.length} PCs)`;
        selectLab.appendChild(option);
    });
}

// --- Reemplaza tu función renderizarMapaMaquinas actual con esta ---

function renderizarMapaMaquinas() {
    const labIdSeleccionado = document.getElementById('laboratorio').value;
    const mapaGrid = document.getElementById('mapa-maquinas-grid');
    const inputOculto = document.getElementById('equipo_id_seleccionado');
    
    // Si no hay laboratorio seleccionado, mostramos mensaje y salimos
    if (!labIdSeleccionado) {
        mapaGrid.innerHTML = '<span style="color:var(--text-muted);">Seleccione laboratorio...</span>';
        inputOculto.value = '';
        return;
    }

    // --- 1. APLICAR SKELETON (Efecto de carga visual) ---
    // Si el array de laboratorios aún está vacío (carga inicial) o estamos procesando, mostramos el esqueleto
    if (laboratoriosData.length === 0) {
        mostrarSkeleton();
        return; // Salimos y esperamos a que recargarTodo termine
    }
    // ----------------------------------------------------

    const lab = laboratoriosData.find(l => l.laboratorio_id === labIdSeleccionado);
    if (!lab || !lab.equipos) { mapaGrid.innerHTML = 'No hay equipos.'; return; }
    
    inputOculto.value = ''; // Limpiar selección previa

    const fecha = document.getElementById('fecha').value;
    const hora_inicio = document.getElementById('hora_inicio').value;
    const hora_fin = document.getElementById('hora_fin').value;

    mapaGrid.innerHTML = ''; 
    // Ajustar columnas dinámicamente según cantidad de PCs
    mapaGrid.style.gridTemplateColumns = `repeat(${lab.equipos.length > 20 ? 10 : 8}, 1fr)`;

    // Calcular bloqueo del laboratorio
    const estaLabBloqueado = calendarioEventos.some(evento => {
        if (evento.tipo !== 'bloqueo' || evento.laboratorio_id != labIdSeleccionado || !fecha || !hora_inicio || !hora_fin) return false;
        const dtEvIni = new Date(`${evento.fecha}T${evento.hora_inicio}`);
        const dtEvFin = new Date(`${evento.fecha}T${evento.hora_fin}`);
        const dtSelIni = new Date(`${fecha}T${hora_inicio}`);
        const dtSelFin = new Date(`${fecha}T${hora_fin}`);
        return dtSelIni < dtEvFin && dtSelFin > dtEvIni;
    });
    
    // Dibujar los equipos reales
    lab.equipos.forEach(equipo => {
        const pcDiv = document.createElement('div');
        pcDiv.classList.add('pc');
        pcDiv.textContent = equipo.numero_equipo;
        pcDiv.dataset.equipoId = equipo.equipo_id;

        if (equipo.estado_equipo === 'en_mantenimiento') {
            pcDiv.classList.add('mantenimiento');
            pcDiv.title = 'Mantenimiento';
        } else if (estaLabBloqueado) {
             pcDiv.classList.add('ocupado');
             pcDiv.title = 'Clase en curso';
        } else if (esPCReservada(equipo.equipo_id, fecha, hora_inicio, hora_fin)) {
            pcDiv.classList.add('ocupado');
            pcDiv.title = 'Reservado';
        } else {
            pcDiv.classList.add('disponible');
            pcDiv.title = `Disponible`;
            pcDiv.addEventListener('click', () => {
                const pcs = mapaGrid.querySelectorAll('.pc');
                pcs.forEach(pc => pc.classList.remove('seleccionado'));
                pcDiv.classList.add('seleccionado');
                inputOculto.value = equipo.equipo_id;
            });
        }
        mapaGrid.appendChild(pcDiv);
    });
}

function esPCReservada(equipoId, fechaSel, horaInicioSel, horaFinSel) {
    if (!fechaSel || !horaInicioSel || !horaFinSel) return false;
    const dtSelInicio = new Date(`${fechaSel}T${horaInicioSel}`);
    const dtSelFin = new Date(`${fechaSel}T${horaFinSel}`);

    return calendarioEventos.some(evento => {
        if (evento.tipo === 'reserva' && evento.estado_reserva === 'confirmada' && evento.equipo_id == equipoId && evento.fecha === fechaSel) {
            const dtEvInicio = new Date(`${evento.fecha}T${evento.hora_inicio}`);
            const dtEvFin = new Date(`${evento.fecha}T${evento.hora_fin}`);
            return dtSelInicio < dtEvFin && dtSelFin > dtEvInicio;
        }
        return false;
    });
}

function saludoPersonalizado() {
    const hora = new Date().getHours();
    let saludo = "Hola";
    if (hora < 12) saludo = "Buenos días ☀️";
    else if (hora < 18) saludo = "Buenas tardes 🌤️";
    else saludo = "Buenas noches 🌙";

    // Suponiendo que tienes el nombre en sessionStorage
    const nombre = sessionStorage.getItem('nombre_usuario') || 'Estudiante';
    
    // Inyectar en el HTML
    const header = document.querySelector('h2'); // O donde tengas el título
    header.innerHTML = `${saludo}, <span style="color:var(--primary-color)">${nombre}</span>`;
    
    // Gamificación: Nivel de Confiabilidad (Mockup)
    // Podrías calcular esto real si tienes historial de asistencias
    const divNivel = document.createElement('div');
    divNivel.className = 'badge-nivel';
    divNivel.innerHTML = `🏆 Nivel: <b>Usuario Responsable</b> (98% Asistencia)`;
    header.parentNode.insertBefore(divNivel, header.nextSibling);
}