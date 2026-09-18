<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
$usuario = $_SESSION['usuario'];
$es_admin = $usuario['rol'] === 'admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShieldDesk — Panel de Soporte</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <span>🛡️</span>
        <span>ShieldDesk</span>
    </div>
    <nav class="sidebar-nav">
        <a href="#" class="nav-item active" data-view="dashboard">
            <i class="fas fa-chart-bar"></i> Dashboard
        </a>
        <a href="#" class="nav-item" data-view="tickets">
            <i class="fas fa-ticket-alt"></i> Tickets
        </a>
        <a href="#" class="nav-item" data-view="clientes">
            <i class="fas fa-building"></i> Clientes
        </a>
        <?php if ($es_admin): ?>
        <a href="#" class="nav-item" data-view="agentes">
            <i class="fas fa-users"></i> Agentes
        </a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-user" id="sidebar-user-btn">
        <div class="user-avatar"><?= htmlspecialchars($usuario['avatar']) ?></div>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($usuario['nombre']) ?></span>
            <span class="user-rol"><?= htmlspecialchars($usuario['rol']) ?></span>
        </div>
        <i class="fas fa-chevron-up" id="user-chevron"></i>
    </div>
    <div class="user-menu" id="user-menu">
        <a href="#" class="user-menu-item" onclick="abrirPerfil()">
            <i class="fas fa-user"></i> Mi perfil
        </a>
        <a href="logout.php" class="user-menu-item danger">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </div>
</aside>

<main class="main">
    <header class="main-header">
        <div class="header-left">
            <h1 id="view-title">Dashboard</h1>
            <span id="view-subtitle">Resumen general del sistema</span>
        </div>
        <div class="header-right">
            <button class="btn-nuevo" id="btnNuevoTicket">
                <i class="fas fa-plus"></i> Nuevo Ticket
            </button>
        </div>
    </header>

    <section id="view-dashboard" class="view active">
        <div class="greeting">
            <span class="greeting-icon"><?= htmlspecialchars($usuario['avatar']) ?></span>
            <div>
                <h2 class="greeting-title">Buen día, <?= htmlspecialchars(explode(' ', $usuario['nombre'])[0]) ?>!</h2>
                <p class="greeting-sub"><?= ucfirst($usuario['rol']) ?> · ShieldDesk</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="stat-info">
                    <p class="stat-num" id="stat-total">-</p>
                    <p class="stat-label">Total tickets</p>
                </div>
            </div>
            <div class="stat-card abiertos">
                <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
                <div class="stat-info">
                    <p class="stat-num" id="stat-abiertos">-</p>
                    <p class="stat-label">Abiertos</p>
                </div>
            </div>
            <div class="stat-card progreso">
                <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                <div class="stat-info">
                    <p class="stat-num" id="stat-progreso">-</p>
                    <p class="stat-label">En progreso</p>
                </div>
            </div>
            <div class="stat-card resueltos">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <p class="stat-num" id="stat-resueltos">-</p>
                    <p class="stat-label">Resueltos</p>
                </div>
            </div>
            <div class="stat-card criticos">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-info">
                    <p class="stat-num" id="stat-criticos">-</p>
                    <p class="stat-label">Críticos</p>
                </div>
            </div>
        </div>

        <div class="dashboard-charts">
            <div class="chart-card">
                <h3>Tickets por estado</h3>
                <canvas id="grafico-estados"></canvas>
            </div>
            <div class="chart-card">
                <h3>Tickets por prioridad</h3>
                <canvas id="grafico-prioridades"></canvas>
            </div>
        </div>

        <div class="dashboard-tickets">
            <h2>Tickets recientes</h2>
            <div id="tickets-recientes"></div>
        </div>
    </section>

    <section id="view-tickets" class="view">
        <div class="filtros">
            <input type="text" id="buscador" placeholder="Buscar ticket...">
            <select id="filtro-estado">
                <option value="">Todos los estados</option>
                <option value="abierto">Abierto</option>
                <option value="en_progreso">En progreso</option>
                <option value="resuelto">Resuelto</option>
                <option value="cerrado">Cerrado</option>
            </select>
            <select id="filtro-prioridad">
                <option value="">Todas las prioridades</option>
                <option value="critica">Crítica</option>
                <option value="alta">Alta</option>
                <option value="media">Media</option>
                <option value="baja">Baja</option>
            </select>
            <input type="date" id="filtro-fecha-desde" title="Desde">
            <input type="date" id="filtro-fecha-hasta" title="Hasta">
        </div>
        <div id="tickets-lista"></div>
    </section>

    <section id="view-clientes" class="view">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
            <h2 style="font-size:1rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;">Lista de clientes</h2>
            <button class="btn-nuevo" onclick="abrirModalNuevoCliente()">
                <i class="fas fa-plus"></i> Nuevo Cliente
            </button>
        </div>
        <div id="clientes-lista"></div>
    </section>

    <?php if ($es_admin): ?>
    <section id="view-agentes" class="view">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
            <h2 style="font-size:1rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;">Gestión de agentes</h2>
            <button class="btn-nuevo" onclick="abrirModalNuevoAgente()">
                <i class="fas fa-plus"></i> Nuevo Agente
            </button>
        </div>
        <div id="agentes-lista"></div>
    </section>
    <?php endif; ?>

</main>

<div id="modal-overlay" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modal-titulo">Detalle del Ticket</h2>
            <button onclick="cerrarModal()">✕</button>
        </div>
        <div class="modal-body" id="modal-body"></div>
    </div>
</div>

<div id="modal-nuevo-overlay" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2>Nuevo Ticket</h2>
            <button onclick="cerrarModalNuevo()">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-grupo">
                <label>Título</label>
                <input type="text" id="nuevo-titulo" placeholder="Descripción breve del problema">
            </div>
            <div class="form-grupo">
                <label>Descripción</label>
                <textarea id="nuevo-descripcion" placeholder="Detallá el problema..."></textarea>
            </div>
            <div class="form-fila">
                <div class="form-grupo">
                    <label>Prioridad</label>
                    <select id="nuevo-prioridad">
                        <option value="baja">Baja</option>
                        <option value="media" selected>Media</option>
                        <option value="alta">Alta</option>
                        <option value="critica">Crítica</option>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Categoría</label>
                    <input type="text" id="nuevo-categoria" placeholder="Ej: Red, Hardware, Software">
                </div>
            </div>
            <div class="form-fila">
                <div class="form-grupo">
                    <label>Cliente</label>
                    <select id="nuevo-cliente"></select>
                </div>
                <div class="form-grupo">
                    <label>Agente</label>
                    <select id="nuevo-agente">
                        <option value="">Sin asignar</option>
                    </select>
                </div>
            </div>
            <button class="btn-nuevo" onclick="crearTicket()">
                <i class="fas fa-plus"></i> Crear Ticket
            </button>
        </div>
    </div>
</div>

<div id="modal-perfil-overlay" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2>Mi Perfil</h2>
            <button onclick="cerrarModalPerfil()">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-grupo">
                <label>Nombre</label>
                <input type="text" id="perfil-nombre">
            </div>
            <div class="form-grupo">
                <label>Email</label>
                <input type="email" id="perfil-email">
            </div>
            <button class="btn-nuevo" onclick="guardarPerfil()" style="margin-bottom:1.5rem;">
                <i class="fas fa-save"></i> Guardar cambios
            </button>
            <hr style="border-color:var(--border);margin-bottom:1.5rem;">
            <h3 style="font-size:0.85rem;margin-bottom:1rem;color:var(--text-muted);">Cambiar contraseña</h3>
            <div class="form-grupo">
                <label>Contraseña actual</label>
                <input type="password" id="perfil-pass-actual" placeholder="••••••••">
            </div>
            <div class="form-grupo">
                <label>Nueva contraseña</label>
                <input type="password" id="perfil-pass-nuevo" placeholder="••••••••">
            </div>
            <button class="btn-nuevo" onclick="cambiarPassword()">
                <i class="fas fa-lock"></i> Cambiar contraseña
            </button>
        </div>
    </div>
</div>

<div id="modal-cliente-overlay" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2>Nuevo Cliente</h2>
            <button onclick="cerrarModalCliente()">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-fila">
                <div class="form-grupo">
                    <label>Nombre</label>
                    <input type="text" id="cliente-nombre" placeholder="Nombre completo">
                </div>
                <div class="form-grupo">
                    <label>Empresa</label>
                    <input type="text" id="cliente-empresa" placeholder="Nombre de la empresa">
                </div>
            </div>
            <div class="form-fila">
                <div class="form-grupo">
                    <label>Email</label>
                    <input type="email" id="cliente-email" placeholder="email@empresa.com">
                </div>
                <div class="form-grupo">
                    <label>Teléfono</label>
                    <input type="text" id="cliente-telefono" placeholder="+54 11 1234 5678">
                </div>
            </div>
            <button class="btn-nuevo" onclick="crearCliente()">
                <i class="fas fa-plus"></i> Crear Cliente
            </button>
        </div>
    </div>
</div>

<?php if ($es_admin): ?>
<div id="modal-agente-overlay" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2>Nuevo Agente</h2>
            <button onclick="cerrarModalAgente()">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-fila">
                <div class="form-grupo">
                    <label>Nombre</label>
                    <input type="text" id="agente-nombre" placeholder="Nombre completo">
                </div>
                <div class="form-grupo">
                    <label>Email</label>
                    <input type="email" id="agente-email" placeholder="email@shielddesk.com">
                </div>
            </div>
            <div class="form-fila">
                <div class="form-grupo">
                    <label>Contraseña</label>
                    <input type="password" id="agente-password" placeholder="••••••••">
                </div>
                <div class="form-grupo">
                    <label>Rol</label>
                    <select id="agente-rol">
                        <option value="agente">Agente</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <button class="btn-nuevo" onclick="crearAgente()">
                <i class="fas fa-plus"></i> Crear Agente
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    const USUARIO_ACTUAL = <?= json_encode($usuario) ?>;
    const ES_ADMIN = <?= $es_admin ? 'true' : 'false' ?>;
</script>
<script src="js/app.js"></script>
</body>
</html>