<?php
class ReservasDashboard {
    
    public function __construct() {
        // Inicializar hooks
        // add_action('wp_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));
        // add_action('admin_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));
    }
    
    
    public function handle_logout() {
        if (!session_id()) {
            session_start();
        }
        session_destroy();
        wp_redirect(home_url('/reservas-login/?logout=success'));
        exit;
    }
    
    public function show_login() {
        // Procesar login si se envió el formulario
        if ($_POST && isset($_POST['username']) && isset($_POST['password'])) {
            $this->process_login();
        }
        
        $this->render_login_page();
    }
    
    public function show_dashboard() {
        // Verificar si el usuario está logueado
        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['reservas_user'])) {
            wp_redirect(home_url('/reservas-login/?error=access'));
            exit;
        }
        
        $this->render_dashboard_page();
    }


private function render_login_page() {
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sistema de Reservas - Login</title>
        <link rel="stylesheet" href="<?php echo RESERVAS_PLUGIN_URL; ?>assets/css/admin-style.css">
    </head>
    <body>
        <div class="login-container">
            <h2>Sistema de Reservas</h2>

            <?php if (isset($_GET['error'])): ?>
                <div class="error">
                    <?php echo $this->get_error_message($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="success">Login correcto. Redirigiendo...</div>
            <?php endif; ?>

            <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
                <div class="success">Sesión cerrada correctamente.</div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="form-group">
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">Iniciar Sesión</button>
            </form>

            <div class="info-box">
                <p><strong>Usuario inicial:</strong> superadmin</p>
                <p><strong>Contraseña inicial:</strong> admin123</p>
                <p><em>Cambia estas credenciales después del primer acceso</em></p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

private function render_dashboard_page() {
    $user = $_SESSION['reservas_user'];
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sistema de Reservas - Dashboard</title>
        <link rel="stylesheet" href="<?php echo RESERVAS_PLUGIN_URL; ?>assets/css/admin-style.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="<?php echo RESERVAS_PLUGIN_URL; ?>assets/js/dashboard-script.js"></script>
        <script>
            // Variables globales para JavaScript
            const reservasAjax = {
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('reservas_nonce'); ?>'
            };
        </script>
        <style>
            /* Estilos adicionales para la gestión de descuentos */
            .discounts-management {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                padding: 20px;
                background: #f1f1f1;
                min-height: 100vh;
            }

            .discounts-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
                padding: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .discounts-header h1 {
                margin: 0;
                color: #23282d;
            }

            .discounts-actions {
                display: flex;
                gap: 10px;
            }

            .current-rules-section {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .current-rules-section h3 {
                margin-top: 0;
                color: #23282d;
                border-bottom: 2px solid #0073aa;
                padding-bottom: 10px;
            }

            .rules-table table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }

            .rules-table th,
            .rules-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }

            .rules-table th {
                background: #f8f9fa;
                font-weight: bold;
                color: #333;
            }

            .rules-table tr:hover {
                background: #f8f9fa;
            }

            .no-rules {
                text-align: center;
                padding: 40px;
                color: #666;
            }

            .no-rules p {
                font-size: 16px;
                margin-bottom: 20px;
            }

            .btn-edit,
            .btn-delete {
                padding: 6px 12px;
                margin: 0 2px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                text-decoration: none;
                display: inline-block;
            }

            .btn-edit {
                background: #0073aa;
                color: white;
            }

            .btn-edit:hover {
                background: #005a87;
            }

            .btn-delete {
                background: #d63638;
                color: white;
            }

            .btn-delete:hover {
                background: #b32d2e;
            }

            .btn-danger {
                background: #d63638;
                color: white;
            }

            .btn-danger:hover {
                background: #b32d2e;
            }

            /* Estilos para el formulario de descuentos */
            #discountModal .modal-content {
                max-width: 600px;
            }

            #discountModal .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }

            #discountModal .form-group {
                margin-bottom: 15px;
            }

            #discountModal .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
                color: #333;
            }

            #discountModal .form-group input,
            #discountModal .form-group select,
            #discountModal .form-group textarea {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
                box-sizing: border-box;
            }

            #discountModal .form-group input:focus,
            #discountModal .form-group select:focus,
            #discountModal .form-group textarea:focus {
                outline: none;
                border-color: #0073aa;
                box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
            }

            #discountModal .form-group input[type="checkbox"] {
                width: auto;
                margin-right: 8px;
            }

            #discountModal .form-actions {
                display: flex;
                gap: 10px;
                margin-top: 20px;
                justify-content: flex-start;
            }

            @media (max-width: 768px) {
                .discounts-header {
                    flex-direction: column;
                    gap: 15px;
                    text-align: center;
                }

                .discounts-actions {
                    justify-content: center;
                }

                #discountModal .form-row {
                    grid-template-columns: 1fr;
                }

                .rules-table {
                    overflow-x: auto;
                }
            }
        </style>
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
                    <button class="action-btn" onclick="loadCalendarSection()">📅 Gestionar Calendario</button>
                    <button class="action-btn" onclick="loadDiscountsConfigSection()">💰 Configurar Descuentos</button>
                    <button class="action-btn" onclick="loadConfigurationSection()">⚙️ Configuración</button>
                    <button class="action-btn" onclick="alert('Función en desarrollo')">🏢 Gestionar Agencias</button>
                    <button class="action-btn" onclick="alert('Función en desarrollo')">📊 Informes</button>
                </div>
                </div>
            <?php endif; ?>

            <div class="next-steps">
                <h3>Próximos Pasos de Desarrollo</h3>
                <ul>
                    <li>Implementar gestión de usuarios completa</li>
                    <li>Crear sistema de calendario y horarios ✅</li>
                    <li>Configurar descuentos automáticos ✅</li>
                    <li>Desarrollar sistema de reservas</li>
                    <li>Integrar métodos de pago</li>
                    <li>Crear generación de PDFs y códigos QR</li>
                    <li>Implementar sistema de informes</li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
}

    
    private function process_login() {
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];

        global $wpdb;
        $table_name = $wpdb->prefix . 'reservas_users';

        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE username = %s AND status = 'active'",
            $username
        ));

        if ($user && password_verify($password, $user->password)) {
            // Iniciar sesión
            if (!session_id()) {
                session_start();
            }

            $_SESSION['reservas_user'] = array(
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role
            );

            // Redireccionar al dashboard
            wp_redirect(home_url('/reservas-admin/?success=1'));
            exit;
        } else {
            wp_redirect(home_url('/reservas-login/?error=invalid'));
            exit;
        }
    }
    
    public function get_error_message($error) {
        switch ($error) {
            case 'invalid':
                return 'Usuario o contraseña incorrectos.';
            case 'access':
                return 'Debes iniciar sesión para acceder.';
            default:
                return 'Error desconocido.';
        }
    }
}