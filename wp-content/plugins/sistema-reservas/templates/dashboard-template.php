<?php
$user = $_SESSION['reservas_user'];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Reservas - Dashboard</title>
</head>
<body>
    <div class="dashboard-header">
        <h1>Sistema de Reservas</h1>
        <div class="user-info">
            <span>Bienvenido, <?php echo esc_html($user['username']); ?></span>
            <span class="user-role"><?php echo esc_html($user['role']); ?></span>
            <a href="<?php echo home_url('/reservas-login/?logout=1'); ?>" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="welcome-card">
            <h2>Dashboard Principal</h2>
            <p class="status-active">✅ El sistema está funcionando correctamente</p>
            <p>Has iniciado sesión correctamente en el sistema de reservas.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Estado del Sistema</h3>
                <div class="stat-number">✓</div>
                <p>Operativo</p>
            </div>
            <div class="stat-card">
                <h3>Tu Rol</h3>
                <div class="stat-number"><?php echo strtoupper($user['role']); ?></div>
                <p>Nivel de acceso</p>
            </div>
            <div class="stat-card">
                <h3>Versión</h3>
                <div class="stat-number">1.0</div>
                <p>Sistema base</p>
            </div>
        </div>

        <?php if ($user['role'] === 'super_admin'): ?>
            <div class="menu-actions">
                <h3>Acciones Disponibles</h3>
                <div class="action-buttons">
                    <button class="action-btn" onclick="alert('Función en desarrollo')">👥 Gestionar Usuarios</button>
                    <button class="action-btn" onclick="loadCalendarSection()">📅 Gestionar Calendario</button>
                    <button class="action-btn" onclick="alert('Función en desarrollo')">🎫 Ver Reservas</button>
                    <button class="action-btn" onclick="alert('Función en desarrollo')">⚙️ Configuración</button>
                    <button class="action-btn" onclick="alert('Función en desarrollo')">📊 Informes</button>
                    <button class="action-btn" onclick="alert('Función en desarrollo')">🏢 Gestionar Agencias</button>
                </div>
            </div>
        <?php endif; ?>

        <div class="next-steps">
            <h3>Próximos Pasos de Desarrollo</h3>
            <ul>
                <li>Implementar gestión de usuarios completa</li>
                <li>Crear sistema de calendario y horarios</li>
                <li>Desarrollar sistema de reservas</li>
                <li>Integrar métodos de pago</li>
                <li>Crear generación de PDFs y códigos QR</li>
                <li>Implementar sistema de informes</li>
            </ul>
        </div>
    </div>
</body>
</html>