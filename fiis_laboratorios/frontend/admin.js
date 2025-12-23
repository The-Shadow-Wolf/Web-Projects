// /frontend/admin.js
// VERSIÓN 4.0 - Modal Elegante + Fix de Fecha + Prevención Doble Click

const API_BLOQUEOS = '../api/bloqueos.php';
const API_LABORATORIOS = '../api/laboratorios.php';
const API_RESERVAS = '../api/reservas.php';
const API_ACCESO = '../api/control_acceso.php';
const API_GESTION = '../api/gestion_labs.php';
const API_REPORTES = '../api/reportes.php';

// --- LÓGICA DE MODO OSCURO (ADMIN) ---
const toggleBtnAdmin = document.getElementById('theme-toggle-admin');

// 1. Aplicar tema guardado al cargar
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
    if(toggleBtnAdmin) toggleBtnAdmin.textContent = '☀️';
}

// 2. Evento Click
if(toggleBtnAdmin) {
    toggleBtnAdmin.addEventListener('click', () => {
        let currentTheme = document.documentElement.getAttribute('data-theme');
        let newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        toggleBtnAdmin.textContent = newTheme === 'dark' ? '☀️' : '🌙';
    });
}

let mi_usuario_id = null;
let mi_rol_id = null;
let calendarioEventos = [];
let bloqueoIdAEliminar = null; // Variable temporal para el modal

document.addEventListener('DOMContentLoaded', () => {
    
    mi_usuario_id = sessionStorage.getItem('usuario_id');
    mi_rol_id = sessionStorage.getItem('rol_id');
    
    if (mi_rol_id !== '2') {
        alert('Acceso Denegado.');
        window.location.href = 'dashboard.html'; 
        return;
    }

    cargarDatosIniciales();
    cargarLaboratoriosParaFormulario();

    const formBloqueo = document.getElementById('form-nuevo-bloqueo');
    if (formBloqueo) {
        formBloqueo.addEventListener('submit', manejarCrearBloqueo);
    }

    // Configurar botón de confirmación del modal
    document.getElementById('btn-confirm-action').addEventListener('click', ejecutarEliminacion);

    // Formulario Crear Lab
const formLab = document.getElementById('form-crear-lab');
if (formLab) {
    formLab.addEventListener('submit', manejarCrearLab);
}

});

// --- CREAR BLOQUEO (Corregido) ---
function manejarCrearBloqueo(event) {
    event.preventDefault();
    
    const btnSubmit = event.target.querySelector('button[type="submit"]');
    const textoOriginal = btnSubmit.textContent;
    
    // 1. Deshabilitar botón para evitar múltiples clics
    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Creando...';

    const lab = document.getElementById('laboratorio').value;
    const fecha = document.getElementById('fecha_bloqueo').value;
    const horaIni = document.getElementById('hora_inicio_bloqueo').value;
    const horaFin = document.getElementById('hora_fin_bloqueo').value;
    const motivo = document.getElementById('motivo').value;

    if (!lab || !fecha || !horaIni || !horaFin || !motivo) {
        showToast('Complete todos los campos', 'error');
        btnSubmit.disabled = false;
        btnSubmit.textContent = textoOriginal;
        return;
    }

    const datosBloqueo = {
        laboratorio_id: lab,
        admin_id: mi_usuario_id,
        fecha_inicio: `${fecha} ${horaIni}`,
        fecha_fin: `${fecha} ${horaFin}`,
        motivo: motivo
    };

    fetch(API_BLOQUEOS, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datosBloqueo)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast(result.message, 'success');
            document.getElementById('form-nuevo-bloqueo').reset();
            cargarDatosIniciales(); // Refrescar lista
        } else {
            showToast(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error de conexión.', 'error');
    })
    .finally(() => {
        // 2. Rehabilitar botón siempre
        btnSubmit.disabled = false;
        btnSubmit.textContent = textoOriginal;
    });
}

// --- SISTEMA DE MODAL PARA ELIMINAR ---
function confirmarEliminacion(bloqueoId) {
    bloqueoIdAEliminar = bloqueoId; // Guardamos el ID
    const modal = document.getElementById('confirm-modal');
    modal.classList.add('active'); // Mostrar modal
}

function cerrarModal() {
    document.getElementById('confirm-modal').classList.remove('active');
    bloqueoIdAEliminar = null;
}

function ejecutarEliminacion() {
    if (!bloqueoIdAEliminar) return;

    fetch(API_BLOQUEOS, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            accion: 'eliminar',
            bloqueo_id: bloqueoIdAEliminar
        })
    })
    .then(response => response.json())
    .then(result => {
        cerrarModal(); // Cerrar modal primero
        if (result.success) {
            showToast('Bloqueo eliminado.', 'success');
            cargarDatosIniciales(); // Refrescar lista
        } else {
            showToast(result.message, 'error');
        }
    })
    .catch(error => {
        cerrarModal();
        console.error('Error:', error);
        showToast('Error al eliminar.', 'error');
    });
}

// Variable global para almacenar la fecha actual que estamos viendo
let fechaVisualizacion = new Date().toISOString().split('T')[0]; // "YYYY-MM-DD" hoy

// --- CARGA DE DATOS (ACTUALIZADO) ---
function cargarDatosIniciales() {
    // 1. Cargar Bloqueos
    fetch(API_BLOQUEOS)
        .then(res => res.json())
        .then(result => renderizarListaBloqueos(result))
        .catch(err => console.error(err));

    // 2. Cargar Reservas
    fetch(API_RESERVAS)
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                calendarioEventos = result.data;
                renderizarControlAcceso();
            }
        });

    // Cargar Reservas (usando la fecha global)
    cargarReservasPorFecha(fechaVisualizacion);

    // 3. ¡NUEVO! Cargar Gestión de Infraestructura (HU-06)
    cargarGestionLaboratorios();

    cargarReportes(); // Nueva función
}

// NUEVA FUNCIÓN: Carga datos del servidor para una fecha específica
function cargarReservasPorFecha(fecha) {
    console.log("Cargando datos del servidor para:", fecha);
    
    // Llamamos al API con el parámetro GET
    fetch(`${API_RESERVAS}?fecha=${fecha}`) 
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                calendarioEventos = result.data; // Guardamos los datos NUEVOS en la variable global
                renderizarControlAcceso();       // Redibujamos la tabla
            }
        })
        .catch(err => console.error("Error cargando historial:", err));
}

// --- RENDERIZADO BLOQUEOS (Corregido para evitar crash) ---
function renderizarListaBloqueos(result) {
    const lista = document.getElementById('lista-bloqueos');
    if (!lista) return;
    lista.innerHTML = ''; 

    if (result.success && result.data.length > 0) {
        result.data.forEach(bloqueo => {
            const li = document.createElement('li');
            li.style.display = 'flex';
            li.style.justifyContent = 'space-between';
            li.style.alignItems = 'center';
            
            // Procesamiento de fecha seguro
            let fechaStr = bloqueo.fecha_inicio;
            let horaIniStr = "00:00";
            let horaFinStr = "00:00";

            try {
                // Intentamos parsear seguro
                const fechaObj = new Date(bloqueo.fecha_inicio);
                const fechaFinObj = new Date(bloqueo.fecha_fin);
                
                // Formato local simple
                fechaStr = fechaObj.toLocaleDateString();
                horaIniStr = fechaObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                horaFinStr = fechaFinObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            } catch (e) {
                console.error("Error parseando fecha", e);
            }

            const infoDiv = document.createElement('div');
            infoDiv.innerHTML = `
                <div><span class="badge badge-danger">BLOQUEO</span> <b>${bloqueo.nombre_lab}</b></div>
                <div style="font-size:0.9rem; margin-top:2px;">${bloqueo.motivo}</div>
                <small style="color:#666;">${fechaStr} | ${horaIniStr} - ${horaFinStr}</small>
            `;

            const btnDelete = document.createElement('button');
            btnDelete.innerHTML = '<i class="fas fa-trash"></i>';
            btnDelete.className = 'btn-danger';
            btnDelete.style.width = 'auto';
            btnDelete.style.padding = '8px 12px';
            btnDelete.onclick = () => confirmarEliminacion(bloqueo.bloqueo_id); // Usamos el nuevo modal

            li.appendChild(infoDiv);
            li.appendChild(btnDelete);
            lista.appendChild(li);
        });
    } else {
        lista.innerHTML = '<li style="background:transparent; box-shadow:none;">No hay bloqueos activos.</li>';
    }
}

// ... (Resto de funciones: renderizarControlAcceso, registrarMovimiento, etc. siguen igual que antes) ...


// --- FUNCION MEJORADA: CONTROL DE ACCESO CON FECHA ---
function renderizarControlAcceso() {
    const listaReservasHoy = document.getElementById('lista-reservas-hoy');
    if (!listaReservasHoy) return;
    
    // 1. LEER LA FECHA SELECCIONADA DEL INPUT
    const inputFecha = document.getElementById('fecha-control');
    let fechaSeleccionada = inputFecha.value;

    // Si el input está vacío (primera carga), ponemos la fecha de hoy
    if (!fechaSeleccionada) {
        const fechaObj = new Date();
        const anio = fechaObj.getFullYear();
        const mes = String(fechaObj.getMonth() + 1).padStart(2, '0');
        const dia = String(fechaObj.getDate()).padStart(2, '0');
        fechaSeleccionada = `${anio}-${mes}-${dia}`;
        inputFecha.value = fechaSeleccionada; // Actualizamos el input visualmente
    }

    // Actualizar el título para que diga la fecha
    const titulo = document.querySelector('#control-acceso-view h2');
    if(titulo) titulo.innerHTML = `Control de Acceso <span style="font-weight:normal; font-size:1rem; color:#666;">(${fechaSeleccionada})</span>`;

    // 2. FILTRAR POR LA FECHA SELECCIONADA
    const reservasDelDia = calendarioEventos.filter(evento => 
        evento.tipo === 'reserva' &&
        evento.fecha === fechaSeleccionada &&
        evento.estado_reserva === 'confirmada'
    );
    
    // Leer el filtro de estado (Pendiente/En Lab...)
    const filtro = document.getElementById('filtro-acceso-admin') ? document.getElementById('filtro-acceso-admin').value : 'todos';
    listaReservasHoy.innerHTML = '';

    if (reservasDelDia.length === 0) {
        listaReservasHoy.innerHTML = '<li style="background:transparent; box-shadow:none; text-align:center; color:#888;">No hay reservas para esta fecha.</li>';
        return;
    }

    // --- DATOS PARA LÓGICA TEMPORAL ---
    // Calculamos "Hoy" real para comparar si la fecha seleccionada es Pasado, Presente o Futuro
    const ahora = new Date();
    const hoyReal = nowToStringDate(ahora); // (YYYY-MM-DD)
    const horaActual = nowToString(ahora);  // (HH:MM:SS)

    let contadorVisibles = 0;

    reservasDelDia.forEach(reserva => {
        const li = document.createElement('li');
        
        // Info de la reserva
        const info = document.createElement('div');
        info.className = 'reserva-info';
        info.innerHTML = 
            `<b>${reserva.nombre_lab} (PC-${reserva.numero_equipo})</b><br>` +
            `<span style="font-size:0.85rem; color:#666;">${reserva.hora_inicio} - ${reserva.hora_fin} | ${reserva.codigo_unac}</span>`;
        li.appendChild(info);

        const estado = document.createElement('div');
        const botones = document.createElement('div');
        botones.className = 'reserva-botones';
        
        // Botones (se habilitarán/deshabilitarán según lógica)
        const btnIngreso = document.createElement('button');
        btnIngreso.textContent = 'Ingreso';
        btnIngreso.className = 'btn-success';
        btnIngreso.style.marginRight = '5px';
        btnIngreso.onclick = () => registrarMovimiento(reserva.id, 'ingreso');

        const btnSalida = document.createElement('button');
        btnSalida.textContent = 'Salida';
        btnSalida.className = 'btn-warning';
        btnSalida.onclick = () => registrarMovimiento(reserva.id, 'salida');

        // --- LÓGICA DE ESTADOS (Presente, Pasado y Futuro) ---
        let estadoActual = 'pendientes';

        if (reserva.hora_ingreso_real && reserva.hora_salida_real) {
            estadoActual = 'completados';
        } else if (reserva.hora_ingreso_real) {
            estadoActual = 'en_lab';
        } else {
            // Si NO ha ingresado, verificamos si "ya fue" (Vencido)
            
            if (fechaSeleccionada < hoyReal) {
                // CASO 1: Estamos mirando el PASADO (Ayer o antes)
                // Si no entró, es falta automática.
                estadoActual = 'vencido'; 
            } else if (fechaSeleccionada > hoyReal) {
                // CASO 2: Estamos mirando el FUTURO (Mañana)
                // Todo es pendiente, nada vence todavía.
                estadoActual = 'pendientes';
            } else {
                // CASO 3: Estamos mirando HOY (Presente)
                // Usamos la hora para ver si ya se pasó su turno
                if (horaActual > reserva.hora_fin) {
                    estadoActual = 'vencido';
                }
            }
        }

        // --- FILTRADO VISUAL ---
        if (filtro !== 'todos') {
            // Nota: Mostramos 'vencido' dentro de la categoría 'pendientes' para que el admin vea quién faltó
            if (filtro === 'pendientes' && (estadoActual !== 'pendientes' && estadoActual !== 'vencido')) return;
            if (filtro === 'en_lab' && estadoActual !== 'en_lab') return;
            if (filtro === 'completados' && estadoActual !== 'completados') return;
        }

        contadorVisibles++;

        // --- RENDERIZADO VISUAL ---
        if (estadoActual === 'completados') {
            estado.innerHTML = `<span class="badge badge-success">Completado</span>`;
            deshabilitarBtn(btnIngreso); deshabilitarBtn(btnSalida);

        } else if (estadoActual === 'en_lab') {
            estado.innerHTML = `<span class="badge badge-info">En Lab</span>`;
            deshabilitarBtn(btnIngreso); btnSalida.disabled = false;

        } else if (estadoActual === 'vencido') {
            estado.innerHTML = `<span class="badge" style="background:#dc3545; color:white;">No Asistió</span>`;
            // Bloqueamos botones porque ya pasó el turno o el día
            deshabilitarBtn(btnIngreso); deshabilitarBtn(btnSalida);

        } else {
            estado.innerHTML = `<span class="badge badge-warning">Pendiente</span>`;
            // Solo habilitamos ingreso si es HOY. Si es Futuro, bloqueamos para no marcar por error.
            if(fechaSeleccionada > hoyReal) {
                 btnIngreso.disabled = true; 
                 btnIngreso.title = "Solo se puede registrar el día de la reserva";
            } else {
                 btnIngreso.disabled = false;
            }
            deshabilitarBtn(btnSalida);
        }
        
        li.appendChild(estado);
        botones.appendChild(btnIngreso);
        botones.appendChild(btnSalida);
        li.appendChild(botones);

        listaReservasHoy.appendChild(li);
    });
    
    if (contadorVisibles === 0) {
        listaReservasHoy.innerHTML = '<li style="background:transparent; box-shadow:none; text-align:center;">No hay reservas con este filtro.</li>';
    }
}

// Helper para obtener fecha YYYY-MM-DD
function nowToStringDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function deshabilitarBtn(btn) {
    btn.disabled = true;
    btn.style.backgroundColor = '#e9ecef';
    btn.style.color = '#adb5bd';
    btn.style.boxShadow = 'none';
    btn.style.cursor = 'not-allowed';
}

function deshabilitarBtn(btn) {
    btn.disabled = true;
    btn.style.backgroundColor = '#e9ecef';
    btn.style.color = '#adb5bd';
    btn.style.boxShadow = 'none';
    btn.style.cursor = 'not-allowed';
}

function registrarMovimiento(reservaId, accion) {
    const datos = { reserva_id: reservaId, accion: accion };
    fetch(API_ACCESO, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast(result.message, 'success');
            cargarDatosIniciales();
        } else {
            showToast(result.message, 'error');
        }
    })
    .catch(error => { console.error('Error:', error); showToast('Error de conexión.', 'error'); });
}

function cargarLaboratoriosParaFormulario() {
    const selectLab = document.getElementById('laboratorio');
    if(!selectLab) return;
    fetch(API_LABORATORIOS)
        .then(response => response.json())
        .then(result => {
            selectLab.innerHTML = '<option value="">Seleccione un laboratorio</option>';
            if (result.success) {
                result.data.forEach(lab => {
                    const option = document.createElement('option');
                    option.value = lab.laboratorio_id;
                    option.textContent = `${lab.nombre_lab}`;
                    selectLab.appendChild(option);
                });
            }
        });
}

// --- FUNCIONES HU-06: GESTIÓN DE LABORATORIOS ---

function manejarCrearLab(event) {
    event.preventDefault();
    const nombre = document.getElementById('nombre_lab_nuevo').value;
    const ubicacion = document.getElementById('ubicacion_lab_nuevo').value;
    const capacidad = document.getElementById('capacidad_lab_nuevo').value;

    if (!nombre || !ubicacion || !capacidad) return showToast('Complete todos los campos', 'error');

    fetch(API_GESTION, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            accion: 'crear_lab',
            nombre: nombre,
            ubicacion: ubicacion,
            capacidad: capacidad
        })
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            showToast(result.message, 'success');
            document.getElementById('form-crear-lab').reset();
            cargarLaboratoriosParaFormulario(); // Actualiza el select de bloqueos
            cargarGestionLaboratorios(); // Actualiza la lista visual
        } else {
            showToast(result.message, 'error');
        }
    });
}

// En frontend/admin.js

function cargarGestionLaboratorios() {
    const contenedor = document.getElementById('lista-labs-gestion');
    if (!contenedor) return;
    
    fetch(API_LABORATORIOS)
        .then(res => res.json())
        .then(result => {
            contenedor.innerHTML = '';
            if (result.success) {
                result.data.forEach(lab => {
                    const item = document.createElement('div');
                    item.className = 'lab-gestion-item';
                    // Estilos de la tarjeta del laboratorio
                    item.style.border = '1px solid #e0e0e0';
                    item.style.padding = '20px';
                    item.style.borderRadius = '12px';
                    item.style.marginBottom = '20px';
                    item.style.background = 'white';
                    item.style.boxShadow = '0 2px 5px rgba(0,0,0,0.05)';

                    // HTML del Item
                    item.innerHTML = `
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; padding-bottom:10px; border-bottom:1px solid #f0f0f0;">
                            <div>
                                <h3 style="margin:0; font-size:1.2rem; color:var(--primary-color); font-weight:700;">${lab.nombre_lab}</h3>
                                <div style="color:#666; font-size:0.9rem; margin-top:4px;">
                                    <i class="fas fa-map-marker-alt"></i> ${lab.ubicacion} &nbsp;|&nbsp; 
                                    <i class="fas fa-desktop"></i> ${lab.equipos.length} Equipos
                                </div>
                            </div>
                            <button class="btn-danger" style="width:auto; padding:8px 15px; font-size:0.85rem; border-radius:8px;" onclick="eliminarLab(${lab.laboratorio_id})">
                                <i class="fas fa-trash-alt"></i> Eliminar Lab
                            </button>
                        </div>
                        
                        <div style="background: #f8f9fa; padding: 8px 12px; border-radius: 6px; font-size: 0.85rem; color: #555; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                            <span>
                                <i class="fas fa-mouse-pointer" style="color:var(--primary-color); margin-right:6px;"></i> 
                                <b>Modo Edición:</b> Haz clic en un equipo para cambiar su estado.
                            </span>
                            <div style="display:flex; gap:15px;">
                                <span style="display:flex; align-items:center;"><span style="width:8px; height:8px; background:#28a745; border-radius:50%; margin-right:5px;"></span>Habilitado</span>
                                <span style="display:flex; align-items:center;"><span style="width:8px; height:8px; background:#6c757d; border-radius:50%; margin-right:5px;"></span>Mantenimiento</span>
                            </div>
                        </div>
                        
                        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(40px, 1fr)); gap:8px;">
                            ${generarGridEquiposAdmin(lab.equipos)}
                        </div>
                    `;
                    contenedor.appendChild(item);
                });
            }
        });
}

function generarGridEquiposAdmin(equipos) {
    return equipos.map(eq => {
        const color = eq.estado_equipo === 'en_mantenimiento' ? '#6c757d' : '#28a745';
        const title = eq.estado_equipo === 'en_mantenimiento' ? 'En Mantenimiento (Clic para activar)' : 'Disponible (Clic para mantenimiento)';
        const nuevoEstado = eq.estado_equipo === 'en_mantenimiento' ? 'disponible' : 'en_mantenimiento';
        
        return `<div onclick="cambiarEstadoEquipo(${eq.equipo_id}, '${nuevoEstado}')" 
                     title="${title}"
                     style="
                        height:30px; 
                        background:${color}; 
                        color:white; 
                        display:flex; 
                        align-items:center; 
                        justify-content:center; 
                        border-radius:4px; 
                        font-size:0.7rem; 
                        cursor:pointer;
                        font-weight:bold;">
                    ${eq.numero_equipo}
                </div>`;
    }).join('');
}

function cambiarEstadoEquipo(equipoId, nuevoEstado) {
    fetch(API_GESTION, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            accion: 'estado_equipo',
            equipo_id: equipoId,
            estado: nuevoEstado
        })
    })
    .then(res => res.json())
    .then(result => {
        if(result.success) {
            showToast('Estado actualizado', 'success');
            cargarGestionLaboratorios(); // Refrescar visualmente
        } else {
            showToast('Error al actualizar', 'error');
        }
    });
}

function eliminarLab(labId) {
    if (!confirm('¿Seguro? Esto borrará el laboratorio y sus equipos.')) return;
    
    fetch(API_GESTION, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            accion: 'eliminar_lab',
            laboratorio_id: labId
        })
    })
    .then(res => res.json())
    .then(result => {
        if(result.success) {
            showToast('Laboratorio eliminado', 'success');
            cargarGestionLaboratorios();
            cargarLaboratoriosParaFormulario();
        } else {
            showToast(result.message, 'error');
        }
    });
}

// --- FUNCIONES HU-08: REPORTES Y GRÁFICOS ---

// Variables globales para los gráficos (para poder destruirlos y redibujarlos)
let chartBarras = null;
let chartPie = null;
// Variable global para datos
let datosDetalleReporte = [];

// Cargar Reportes
function cargarReportes() {
    console.log("Iniciando carga de reportes...");

    // 1. Validar que Chart.js esté cargado
    if (typeof Chart === 'undefined') {
        alert("ERROR CRÍTICO: La librería Chart.js no se ha cargado. Revisa el archivo HTML.");
        return;
    }

    const inicioInput = document.getElementById('rep-inicio');
    const finInput = document.getElementById('rep-fin');
    
    // Auto-rellenar fechas si están vacías
    if (!inicioInput.value) {
        const hoy = new Date();
        const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        inicioInput.value = primerDia.toISOString().split('T')[0];
        finInput.value = hoy.toISOString().split('T')[0];
    }

    const fechaInicio = inicioInput.value;
    const fechaFin = finInput.value;

    // 2. Llamada al API
    fetch(`${API_REPORTES}?inicio=${fechaInicio}&fin=${fechaFin}`)
        .then(res => {
            if (!res.ok) throw new Error(`Error HTTP: ${res.status}`);
            return res.json();
        })
        .then(result => {
            console.log("Datos recibidos del API:", result); // MIRA LA CONSOLA (F12)

            if (result.success) {
                datosDetalleReporte = result.detalle || [];

                // Validar si llegaron datos vacíos
                if (result.ocupacion.length === 0 && result.detalle.length === 0) {
                    console.warn("El API no devolvió datos para este rango de fechas.");
                }

                // --- Actualizar KPIs ---
                const asistieron = parseInt(result.asistencia.asistieron) || 0;
                const faltaron = parseInt(result.asistencia.faltaron) || 0;
                const total = asistieron + faltaron;
                const tasa = total > 0 ? ((asistieron / total) * 100).toFixed(1) : 0;

                document.getElementById('kpi-total').textContent = total;
                document.getElementById('kpi-asistieron').textContent = asistieron;
                document.getElementById('kpi-faltaron').textContent = faltaron;
                document.getElementById('kpi-tasa').textContent = `${tasa}%`;

                // --- Renderizar Gráficos ---
                // Verifica que los contenedores existan antes de dibujar
                if(document.getElementById('chartOcupacion')) {
                    renderizarGraficoBarras(result.ocupacion);
                }
                if(document.getElementById('chartAsistencia')) {
                    renderizarGraficoPie(asistieron, faltaron);
                }

                // --- Renderizar Tabla ---
                if(typeof renderizarTablaReporte === 'function') {
                    renderizarTablaReporte(result.detalle);
                }
            } else {
                console.error("El API respondió success: false", result);
                alert("Error del servidor: " + (result.message || 'Desconocido'));
            }
        })
        .catch(err => {
            console.error("Error fatal cargando reportes:", err);
            alert("Error de conexión. Revisa la consola (F12) para más detalles.");
        });
}

// Renderizar Tabla en Pantalla (para que salga en PDF)
function renderizarTablaReporte(datos) {
    const tbody = document.getElementById('tabla-reporte-body');
    tbody.innerHTML = '';

    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:10px;">No hay datos en este rango.</td></tr>';
        return;
    }

    // Limitamos a 100 filas para no colgar el navegador si hay miles (en Excel sí van todas)
    const datosVisuales = datos.slice(0, 100); 

    datosVisuales.forEach(row => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = "1px solid #eee";
        
        // Color de estado
        let colorEstado = row.estado_asistencia === 'ASISTIÓ' ? 'green' : 'red';
        
        tr.innerHTML = `
            <td style="padding: 8px;">${row.fecha_reserva}</td>
            <td style="padding: 8px;">${row.hora_inicio}</td>
            <td style="padding: 8px;">${row.nombre_lab}</td>
            <td style="padding: 8px;">${row.codigo_unac}</td>
            <td style="padding: 8px; text-align:center;">
                <span style="color:${colorEstado}; font-weight:bold; font-size:0.8rem;">
                    ${row.estado_asistencia}
                </span>
            </td>
        `;
        tbody.appendChild(tr);
    });

    if (datos.length > 100) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td colspan="5" style="text-align:center; padding:10px; color:#666;">... y ${datos.length - 100} registros más (Descargue Excel para ver todo) ...</td>`;
        tbody.appendChild(tr);
    }
}

// Función Excel CORREGIDA
function exportarExcel() {
    if (!datosDetalleReporte || datosDetalleReporte.length === 0) {
        alert("No hay datos para exportar. Seleccione un rango de fechas y filtre primero.");
        return;
    }

    // Cabecera CSV
    let csvContent = "data:text/csv;charset=utf-8,\uFEFF"; // \uFEFF es CLAVE para tildes en Excel
    csvContent += "Fecha,Hora Inicio,Hora Fin,Laboratorio,Codigo Alumno,Estado Asistencia\r\n";

    datosDetalleReporte.forEach(row => {
        // Asegurar que no haya comas en los datos que rompan el CSV
        const lab = row.nombre_lab.replace(/,/g, " "); 
        const fila = `${row.fecha_reserva},${row.hora_inicio},${row.hora_fin},${lab},${row.codigo_unac},${row.estado_asistencia}`;
        csvContent += fila + "\r\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    const nombreArchivo = `Reporte_FIIS_${new Date().toISOString().slice(0,10)}.csv`;
    link.setAttribute("download", nombreArchivo);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// =========================================================
//  FUNCIONES AUXILIARES FALTANTES (PEGAR AL FINAL DE ADMIN.JS)
// =========================================================

// 1. FUNCIONES PARA GRÁFICOS (Chart.js)
// -------------------------------------
function renderizarGraficoBarras(dataOcupacion) {
    const canvas = document.getElementById('chartOcupacion');
    if (!canvas) return; // Protección por si no existe el elemento

    const ctx = canvas.getContext('2d');
    const labels = dataOcupacion.map(d => d.nombre_lab);
    const data = dataOcupacion.map(d => d.total_reservas);

    // Destruir gráfico previo si existe para actualizar
    if (chartBarras) chartBarras.destroy(); 

    chartBarras = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Reservas Confirmadas',
                data: data,
                backgroundColor: '#36A2EB', // Azul
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }, // Ocultar leyenda para barras simples
                title: { display: true, text: 'Reservas por Laboratorio' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
}

function renderizarGraficoPie(asistieron, faltaron) {
    const canvas = document.getElementById('chartAsistencia');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    // Destruir gráfico previo
    if (chartPie) chartPie.destroy();

    // Si no hay datos, evitamos errores visuales
    if (asistieron === 0 && faltaron === 0) return;

    chartPie = new Chart(ctx, {
        type: 'doughnut', // Gráfico de anillo
        data: {
            labels: ['Asistieron', 'Faltaron'],
            datasets: [{
                data: [asistieron, faltaron],
                backgroundColor: ['#28a745', '#dc3545'], // Verde y Rojo
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

// 2. FUNCIONES PARA TABLAS Y EXCEL
// --------------------------------
function renderizarTablaReporte(datos) {
    const tbody = document.getElementById('tabla-reporte-body');
    if (!tbody) return;
    
    tbody.innerHTML = '';

    if (!datos || datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:10px;">No hay datos en este rango.</td></tr>';
        return;
    }

    // Limitamos a 50 filas para no saturar el DOM visualmente (Excel descarga todo)
    const datosVisuales = datos.slice(0, 50); 

    datosVisuales.forEach(row => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = "1px solid #eee";
        
        let colorEstado = row.estado_asistencia === 'ASISTIÓ' ? '#28a745' : '#dc3545';
        
        tr.innerHTML = `
            <td style="padding: 8px;">${row.fecha_reserva}</td>
            <td style="padding: 8px;">${row.hora_inicio}</td>
            <td style="padding: 8px;">${row.nombre_lab}</td>
            <td style="padding: 8px;">${row.codigo_unac}</td>
            <td style="padding: 8px; text-align:center;">
                <span style="color:${colorEstado}; font-weight:bold; font-size:0.8rem;">
                    ${row.estado_asistencia}
                </span>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// 3. FUNCIONES DE TIEMPO (ERROR 'nowToString')
// --------------------------------------------
function nowToString(date) {
    // Convierte un objeto Date a "HH:MM:SS"
    const h = String(date.getHours()).padStart(2, '0');
    const m = String(date.getMinutes()).padStart(2, '0');
    const s = String(date.getSeconds()).padStart(2, '0');
    return `${h}:${m}:${s}`;
}

function nowToStringDate(date) {
    // Convierte un objeto Date a "YYYY-MM-DD"
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

// Efecto visual de números subiendo
function animateValue(id, start, end, duration) {
    const obj = document.getElementById(id);
    if (!obj) return;
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        obj.innerHTML = Math.floor(progress * (end - start) + start);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}