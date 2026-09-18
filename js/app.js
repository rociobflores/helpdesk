const API = {
    tickets: (action, params = '') => `api/tickets.php?action=${action}${params}`,
    agentes: (action) => `api/agentes.php?action=${action}`,
    clientes: (action) => `api/clientes.php?action=${action}`,
    usuarios: (action) => `api/usuarios.php?action=${action}`
};

async function get(url) {
    const res = await fetch(url);
    return res.json();
}

async function post(url, data) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return res.json();
}

function toast(msg, tipo = 'success') {
    let t = document.getElementById('toast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'toast';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.className = `toast toast-${tipo}`;
    t.style.opacity = '1';
    setTimeout(() => t.style.opacity = '0', 3000);
}

const views = {
    dashboard: { title: 'Dashboard', subtitle: 'Resumen general del sistema' },
    tickets: { title: 'Tickets', subtitle: 'Gestión de tickets de soporte' },
    clientes: { title: 'Clientes', subtitle: 'Lista de clientes registrados' },
    agentes: { title: 'Agentes', subtitle: 'Gestión del equipo de soporte' }
};

function cambiarVista(vista) {
    document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    const viewEl = document.getElementById(`view-${vista}`);
    if (!viewEl) return;
    viewEl.classList.add('active');
    const navEl = document.querySelector(`[data-view="${vista}"]`);
    if (navEl) navEl.classList.add('active');
    document.getElementById('view-title').textContent = views[vista]?.title || vista;
    document.getElementById('view-subtitle').textContent = views[vista]?.subtitle || '';
    if (vista === 'dashboard') cargarDashboard();
    if (vista === 'tickets') cargarTickets();
    if (vista === 'clientes') cargarClientes();
    if (vista === 'agentes') cargarAgentes();
}

function badgePrioridad(p) {
    const labels = { critica: 'Crítica', alta: 'Alta', media: 'Media', baja: 'Baja' };
    return `<span class="badge badge-${p}">${labels[p] || p}</span>`;
}

function badgeEstado(e) {
    const labels = { abierto: 'Abierto', en_progreso: 'En progreso', esperando_cliente: 'Esp. cliente', resuelto: 'Resuelto', cerrado: 'Cerrado' };
    return `<span class="badge badge-${e}">${labels[e] || e}</span>`;
}

function formatFecha(f) {
    return new Date(f).toLocaleDateString('es-AR', { day: '2-digit', month: 'short', year: 'numeric' });
}

function diasAbierto(fecha) {
    const dias = Math.floor((new Date() - new Date(fecha)) / (1000 * 60 * 60 * 24));
    if (dias === 0) return '<span style="color:var(--baja)">Hoy</span>';
    if (dias === 1) return '<span style="color:var(--baja)">1 día</span>';
    if (dias <= 3) return `<span style="color:var(--media)">${dias} días</span>`;
    return `<span style="color:var(--critica)">${dias} días</span>`;
}

async function cargarDashboard() {
    const stats = await get(API.tickets('estadisticas'));
    document.getElementById('stat-total').textContent = stats.total;
    document.getElementById('stat-abiertos').textContent = stats.abiertos;
    document.getElementById('stat-progreso').textContent = stats.en_progreso;
    document.getElementById('stat-resueltos').textContent = stats.resueltos;
    document.getElementById('stat-criticos').textContent = stats.criticos;

    const tickets = await get(API.tickets('listar'));
    renderTablaTickets(tickets.slice(0, 5), 'tickets-recientes');

    const ctx1 = document.getElementById('grafico-estados')?.getContext('2d');
    const ctx2 = document.getElementById('grafico-prioridades')?.getContext('2d');

    if (window.graficoEstados) window.graficoEstados.destroy();
    if (window.graficoPrioridades) window.graficoPrioridades.destroy();

    if (ctx1) {
        window.graficoEstados = new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Abiertos', 'En progreso', 'Esp. cliente', 'Resueltos', 'Cerrados'],
                datasets: [{
                    data: [
                        tickets.filter(t => t.estado === 'abierto').length,
                        tickets.filter(t => t.estado === 'en_progreso').length,
                        tickets.filter(t => t.estado === 'resuelto').length,
                        tickets.filter(t => t.estado === 'cerrado').length,
                    ],
                    backgroundColor: ['#3b82f6','#8b5cf6','#f59e0b','#22c55e','#64748b'],
                    borderWidth: 0
                }]
            },
            options: { plugins: { legend: { labels: { color: '#e2e8f0', font: { size: 11 } } } } }
        });
    }

    if (ctx2) {
        window.graficoPrioridades = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['Crítica', 'Alta', 'Media', 'Baja'],
                datasets: [{
                    label: 'Tickets',
                    data: [
                        tickets.filter(t => t.prioridad === 'critica').length,
                        tickets.filter(t => t.prioridad === 'alta').length,
                        tickets.filter(t => t.prioridad === 'media').length,
                        tickets.filter(t => t.prioridad === 'baja').length,
                    ],
                    backgroundColor: ['#ef4444','#f97316','#eab308','#22c55e'],
                    borderRadius: 6, borderWidth: 0
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { color: '#64748b' }, grid: { color: '#2a3044' } },
                    x: { ticks: { color: '#64748b' }, grid: { display: false } }
                }
            }
        });
    }
}

let todosLosTickets = [];

async function cargarTickets() {
    todosLosTickets = await get(API.tickets('listar'));
    renderTablaTickets(todosLosTickets, 'tickets-lista');
}

function renderTablaTickets(tickets, contenedorId) {
    const cont = document.getElementById(contenedorId);
    if (!cont) return;
    if (!tickets.length) {
        cont.innerHTML = '<p style="color:var(--text-muted);padding:2rem;text-align:center;">No hay tickets.</p>';
        return;
    }
    cont.innerHTML = `
        <div class="tickets-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Título</th><th>Cliente</th>
                        <th>Prioridad</th><th>Estado</th><th>Agente</th>
                        <th>Tiempo abierto</th><th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    ${tickets.map(t => `
                        <tr>
                            <td style="color:var(--text-muted);font-size:0.75rem;">#${t.id}</td>
                            <td onclick="verTicket(${t.id})" style="cursor:pointer;">${t.titulo}</td>
                            <td>
                                <div style="font-size:0.85rem;">${t.cliente_nombre || '-'}</div>
                                <div style="font-size:0.72rem;color:var(--text-muted);">${t.empresa || ''}</div>
                            </td>
                            <td>${badgePrioridad(t.prioridad)}</td>
                            <td>${badgeEstado(t.estado)}</td>
                            <td style="font-size:0.82rem;color:var(--text-muted);">${t.agente_nombre || '<span style="color:#ef4444">Sin asignar</span>'}</td>
                            <td>${diasAbierto(t.created_at)}</td>
                            <td>
                                <button class="btn-eliminar" onclick="eliminarTicket(${t.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function aplicarFiltros() {
    const q = document.getElementById('buscador').value.toLowerCase();
    const estado = document.getElementById('filtro-estado').value;
    const prioridad = document.getElementById('filtro-prioridad').value;
    const desde = document.getElementById('filtro-fecha-desde').value;
    const hasta = document.getElementById('filtro-fecha-hasta').value;

    const filtrados = todosLosTickets.filter(t => {
        const matchQ = !q || t.titulo.toLowerCase().includes(q) || (t.cliente_nombre || '').toLowerCase().includes(q);
        const matchEstado = !estado || t.estado === estado;
        const matchPrioridad = !prioridad || t.prioridad === prioridad;
        const fecha = new Date(t.created_at);
        const matchDesde = !desde || fecha >= new Date(desde);
        const matchHasta = !hasta || fecha <= new Date(hasta + 'T23:59:59');
        return matchQ && matchEstado && matchPrioridad && matchDesde && matchHasta;
    });

    renderTablaTickets(filtrados, 'tickets-lista');
}

async function eliminarTicket(id) {
    if (!confirm('¿Eliminar este ticket? Esta acción no se puede deshacer.')) return;
    await post(API.tickets('eliminar'), { id });
    toast('Ticket eliminado');
    cargarTickets();
    cargarDashboard();
}

async function verTicket(id) {
    const t = await get(API.tickets('detalle', `&id=${id}`));
    document.getElementById('modal-titulo').textContent = `Ticket #${t.id}`;
    const agentes = await get(API.agentes('listar'));
    const optsAgentes = agentes.map(a => `<option value="${a.id}" ${t.id_agente == a.id ? 'selected' : ''}>${a.nombre}</option>`).join('');

    document.getElementById('modal-body').innerHTML = `
        <div class="ticket-detalle-grid">
            <div class="ticket-detalle-item"><label>Título</label><p>${t.titulo}</p></div>
            <div class="ticket-detalle-item"><label>Estado</label><p>${badgeEstado(t.estado)}</p></div>
            <div class="ticket-detalle-item"><label>Prioridad</label><p>${badgePrioridad(t.prioridad)}</p></div>
            <div class="ticket-detalle-item"><label>Categoría</label><p><span class="badge" style="background:rgba(108,99,255,0.2);color:var(--primary-light);">${t.categoria || '-'}</span></p></div>
            <div class="ticket-detalle-item"><label>Cliente</label><p>${t.cliente_nombre || '-'} <span style="color:var(--text-muted);font-size:0.8rem;">(${t.empresa || ''})</span></p></div>
            <div class="ticket-detalle-item"><label>Creado</label><p>${formatFecha(t.created_at)} · ${diasAbierto(t.created_at)}</p></div>
        </div>
        <div class="form-grupo"><label>Descripción</label><p style="font-size:0.9rem;line-height:1.6;color:var(--text);">${t.descripcion}</p></div>
        <div class="form-fila">
            <div class="form-grupo">
                <label>Cambiar estado</label>
                <select id="cambiar-estado-sel" onchange="cambiarEstado(${t.id}, this.value)">
                    <option value="abierto" ${t.estado=='abierto'?'selected':''}>Abierto</option>
                    <option value="en_progreso" ${t.estado=='en_progreso'?'selected':''}>En progreso</option>
                    <option value="resuelto" ${t.estado=='resuelto'?'selected':''}>Resuelto</option>
                    <option value="cerrado" ${t.estado=='cerrado'?'selected':''}>Cerrado</option>
                </select>
            </div>
            <div class="form-grupo">
                <label>Asignar agente</label>
                <select onchange="asignarAgente(${t.id}, this.value)">
                    <option value="">Sin asignar</option>${optsAgentes}
                </select>
            </div>
        </div>
        <div>
            <label style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;">Historial</label>
            <div class="comentarios-lista" style="margin-top:0.5rem;">
                ${t.comentarios?.length ? t.comentarios.map(c => `
                    <div class="comentario ${c.tipo === 'interno' ? 'interno' : ''}">
                        <div style="display:flex;justify-content:space-between;">
                            <span class="comentario-autor">${c.autor} ${c.tipo === 'interno' ? '🔒' : ''}</span>
                            <span class="comentario-fecha">${formatFecha(c.created_at)}</span>
                        </div>
                        <p class="comentario-msg">${c.mensaje}</p>
                    </div>
                `).join('') : '<p style="color:var(--text-muted);font-size:0.85rem;">Sin comentarios aún.</p>'}
            </div>
        </div>
        <div class="form-grupo"><label>Agregar comentario</label><textarea id="nuevo-comentario" placeholder="Escribí tu comentario..."></textarea></div>
        <div class="form-fila">
            <div class="form-grupo">
                <label>Tipo</label>
                <select id="tipo-comentario">
                    <option value="publico">Público</option>
                    <option value="interno">Interno 🔒</option>
                </select>
            </div>
            <div style="display:flex;align-items:flex-end;">
                <button class="btn-nuevo" onclick="agregarComentario(${t.id})">
                    <i class="fas fa-paper-plane"></i> Enviar
                </button>
            </div>
        </div>
    `;
    document.getElementById('modal-overlay').classList.add('open');
}

function cerrarModal() { document.getElementById('modal-overlay').classList.remove('open'); }

async function cambiarEstado(id, estado) {
    await post(API.tickets('actualizar_estado'), { id, estado });
    toast('Estado actualizado');
    cargarDashboard();
}

async function asignarAgente(id, id_agente) {
    await post(API.tickets('asignar_agente'), { id, id_agente });
    toast('Agente asignado');
}

async function agregarComentario(id_ticket) {
    const mensaje = document.getElementById('nuevo-comentario').value.trim();
    const tipo = document.getElementById('tipo-comentario').value;
    if (!mensaje) return;
    await post(API.tickets('agregar_comentario'), { id_ticket, autor: USUARIO_ACTUAL.nombre, mensaje, tipo });
    verTicket(id_ticket);
}

async function abrirModalNuevo() {
    const clientes = await get(API.clientes('listar'));
    const agentes = await get(API.agentes('listar'));
    document.getElementById('nuevo-cliente').innerHTML = clientes.map(c => `<option value="${c.id}">${c.nombre} (${c.empresa})</option>`).join('');
    document.getElementById('nuevo-agente').innerHTML = `<option value="">Sin asignar</option>` + agentes.map(a => `<option value="${a.id}">${a.nombre}</option>`).join('');
    document.getElementById('modal-nuevo-overlay').classList.add('open');
}

function cerrarModalNuevo() { document.getElementById('modal-nuevo-overlay').classList.remove('open'); }

async function crearTicket() {
    const data = {
        titulo: document.getElementById('nuevo-titulo').value.trim(),
        descripcion: document.getElementById('nuevo-descripcion').value.trim(),
        prioridad: document.getElementById('nuevo-prioridad').value,
        categoria: document.getElementById('nuevo-categoria').value.trim(),
        id_cliente: document.getElementById('nuevo-cliente').value,
        id_agente: document.getElementById('nuevo-agente').value || null
    };
    if (!data.titulo || !data.descripcion) { toast('Completá título y descripción.', 'error'); return; }
    await post(API.tickets('crear'), data);
    cerrarModalNuevo();
    toast('Ticket creado');
    cargarDashboard();
    cambiarVista('tickets');
}

async function cargarClientes() {
    const clientes = await get(API.clientes('listar'));
    const cont = document.getElementById('clientes-lista');
    if (!clientes.length) { cont.innerHTML = '<p style="color:var(--text-muted);padding:2rem;text-align:center;">No hay clientes.</p>'; return; }
    cont.innerHTML = `
        <div class="tickets-table">
            <table>
                <thead><tr><th>Nombre</th><th>Empresa</th><th>Email</th><th>Teléfono</th><th>Registrado</th></tr></thead>
                <tbody>
                    ${clientes.map(c => `
                        <tr>
                            <td>${c.nombre}</td>
                            <td>${c.empresa || '-'}</td>
                            <td style="color:var(--primary-light);">${c.email}</td>
                            <td style="color:var(--text-muted);">${c.telefono || '-'}</td>
                            <td style="color:var(--text-muted);font-size:0.75rem;">${formatFecha(c.created_at)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function abrirModalNuevoCliente() { document.getElementById('modal-cliente-overlay').classList.add('open'); }
function cerrarModalCliente() { document.getElementById('modal-cliente-overlay').classList.remove('open'); }

async function crearCliente() {
    const data = {
        nombre: document.getElementById('cliente-nombre').value.trim(),
        empresa: document.getElementById('cliente-empresa').value.trim(),
        email: document.getElementById('cliente-email').value.trim(),
        telefono: document.getElementById('cliente-telefono').value.trim()
    };
    if (!data.nombre || !data.email) { toast('Completá nombre y email.', 'error'); return; }
    await post(API.clientes('crear'), data);
    cerrarModalCliente();
    toast('Cliente creado');
    cargarClientes();
}

async function cargarAgentes() {
    const agentes = await get(API.usuarios('listar'));
    const cont = document.getElementById('agentes-lista');
    if (!cont) return;
    cont.innerHTML = `
        <div class="agentes-grid">
            ${agentes.map(a => `
                <div class="agente-card">
                    <div class="agente-avatar"><i class="fas fa-user-shield"></i></div>
                    <h3>${a.nombre}</h3>
                    <p>${a.email}</p>
                    <span class="agente-rol">${a.rol}</span>
                    ${a.id != USUARIO_ACTUAL.id ? `
                    <button class="btn-eliminar" style="margin-top:0.75rem;width:100%;" onclick="eliminarAgente(${a.id})">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>` : '<p style="font-size:0.7rem;color:var(--primary-light);margin-top:0.5rem;">← Sos vos</p>'}
                </div>
            `).join('')}
        </div>
    `;
}

function abrirModalNuevoAgente() { document.getElementById('modal-agente-overlay').classList.add('open'); }
function cerrarModalAgente() { document.getElementById('modal-agente-overlay').classList.remove('open'); }

async function crearAgente() {
    const data = {
        nombre: document.getElementById('agente-nombre').value.trim(),
        email: document.getElementById('agente-email').value.trim(),
        password: document.getElementById('agente-password').value,
        rol: document.getElementById('agente-rol').value
    };
    if (!data.nombre || !data.email || !data.password) { toast('Completá todos los campos.', 'error'); return; }
    const res = await post(API.usuarios('crear'), data);
    if (res.error) { toast(res.error, 'error'); return; }
    cerrarModalAgente();
    toast('Agente creado');
    cargarAgentes();
}

async function eliminarAgente(id) {
    if (!confirm('¿Eliminar este agente?')) return;
    const res = await post(API.usuarios('eliminar'), { id });
    if (res.error) { toast(res.error, 'error'); return; }
    toast('Agente eliminado');
    cargarAgentes();
}

async function abrirPerfil() {
    const perfil = await get(API.usuarios('perfil'));
    document.getElementById('perfil-nombre').value = perfil.nombre;
    document.getElementById('perfil-email').value = perfil.email;
    document.getElementById('modal-perfil-overlay').classList.add('open');
    cerrarMenuUsuario();
}

function cerrarModalPerfil() { document.getElementById('modal-perfil-overlay').classList.remove('open'); }

async function guardarPerfil() {
    const data = {
        nombre: document.getElementById('perfil-nombre').value.trim(),
        email: document.getElementById('perfil-email').value.trim()
    };
    await post(API.usuarios('actualizar_perfil'), data);
    toast('Perfil actualizado');
    document.querySelector('.user-name').textContent = data.nombre;
}

async function cambiarPassword() {
    const data = {
        password_actual: document.getElementById('perfil-pass-actual').value,
        password_nuevo: document.getElementById('perfil-pass-nuevo').value
    };
    if (!data.password_actual || !data.password_nuevo) { toast('Completá ambas contraseñas.', 'error'); return; }
    const res = await post(API.usuarios('cambiar_password'), data);
    if (res.error) { toast(res.error, 'error'); return; }
    toast('Contraseña actualizada');
    document.getElementById('perfil-pass-actual').value = '';
    document.getElementById('perfil-pass-nuevo').value = '';
}

function toggleMenuUsuario() {
    const menu = document.getElementById('user-menu');
    const chevron = document.getElementById('user-chevron');
    menu.classList.toggle('open');
    chevron.style.transform = menu.classList.contains('open') ? 'rotate(180deg)' : '';
}

function cerrarMenuUsuario() {
    document.getElementById('user-menu').classList.remove('open');
    document.getElementById('user-chevron').style.transform = '';
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            cambiarVista(item.dataset.view);
        });
    });

    document.getElementById('sidebar-user-btn').addEventListener('click', toggleMenuUsuario);

    document.addEventListener('click', (e) => {
        const menu = document.getElementById('user-menu');
        const btn = document.getElementById('sidebar-user-btn');
        if (!menu.contains(e.target) && !btn.contains(e.target)) {
            cerrarMenuUsuario();
        }
    });

    document.getElementById('modal-overlay').addEventListener('click', function(e) { if (e.target === this) cerrarModal(); });
    document.getElementById('modal-nuevo-overlay').addEventListener('click', function(e) { if (e.target === this) cerrarModalNuevo(); });
    document.getElementById('modal-perfil-overlay').addEventListener('click', function(e) { if (e.target === this) cerrarModalPerfil(); });
    document.getElementById('modal-cliente-overlay').addEventListener('click', function(e) { if (e.target === this) cerrarModalCliente(); });

    const modalAgente = document.getElementById('modal-agente-overlay');
    if (modalAgente) modalAgente.addEventListener('click', function(e) { if (e.target === this) cerrarModalAgente(); });

    document.getElementById('btnNuevoTicket').addEventListener('click', abrirModalNuevo);
    document.getElementById('buscador')?.addEventListener('input', aplicarFiltros);
    document.getElementById('filtro-estado')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filtro-prioridad')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filtro-fecha-desde')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filtro-fecha-hasta')?.addEventListener('change', aplicarFiltros);

    cargarDashboard();
});
async function eliminarTicket(id) {
    if (!confirm('¿Eliminar este ticket? Esta acción no se puede deshacer.')) return;
    await post(API.tickets('eliminar'), { id });
    toast('Ticket eliminado');
    todosLosTickets = todosLosTickets.filter(t => t.id != id);
    renderTablaTickets(todosLosTickets, 'tickets-lista');
    cargarDashboard();
}