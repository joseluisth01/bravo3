<?php

/**
 * Plugin Name: Sistema de Reservas
 * Description: Sistema completo de reservas para servicios de transporte
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('RESERVAS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RESERVAS_PLUGIN_PATH', plugin_dir_path(__FILE__));

class SistemaReservas
{
    private $dashboard;
    private $calendar_admin;
    private $discounts_admin;

    public function __construct()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
    }

    public function init()
    {
        // Cargar dependencias
        $this->load_dependencies();

        // Inicializar clases
        $this->initialize_classes();

        // Registrar reglas de reescritura
        $this->add_rewrite_rules();

        // Añadir query vars
        add_filter('query_vars', array($this, 'add_query_vars'));

        // Manejar template redirect
        add_action('template_redirect', array($this, 'template_redirect'));
    }

    private function load_dependencies()
    {
        $files = array(
            'includes/class-database.php',
            'includes/class-auth.php',
            'includes/class-admin.php',
            'includes/class-dashboard.php',
            'includes/class-calendar-admin.php',
            'includes/class-discounts-admin.php', // Nueva clase para descuentos
            'includes/class-frontend.php',
        );

        foreach ($files as $file) {
            $path = RESERVAS_PLUGIN_PATH . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    private function initialize_classes()
    {
        // Inicializar clases básicas
        if (class_exists('ReservasAuth')) {
            new ReservasAuth();
        }

        if (class_exists('ReservasDashboard')) {
            $this->dashboard = new ReservasDashboard();
        }

        if (class_exists('ReservasCalendarAdmin')) {
            $this->calendar_admin = new ReservasCalendarAdmin();
        }

        // Inicializar nueva clase de descuentos
        if (class_exists('ReservasDiscountsAdmin')) {
            $this->discounts_admin = new ReservasDiscountsAdmin();
        }

        if (class_exists('ReservasFrontend')) {
            new ReservasFrontend();
        }
    }

    public function add_rewrite_rules()
    {
        add_rewrite_rule('^reservas-login/?$', 'index.php?reservas_page=login', 'top');
        add_rewrite_rule('^reservas-admin/?$', 'index.php?reservas_page=dashboard', 'top');
        add_rewrite_rule('^reservas-admin/([^/]+)/?$', 'index.php?reservas_page=dashboard&reservas_section=$matches[1]', 'top');
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'reservas_page';
        $vars[] = 'reservas_section';
        return $vars;
    }

    public function template_redirect()
    {
        $page = get_query_var('reservas_page');

        // Manejar logout
        if (isset($_GET['logout']) && $_GET['logout'] == '1') {
            if ($this->dashboard) {
                $this->dashboard->handle_logout();
            }
        }

        if ($page === 'login') {
            if ($this->dashboard) {
                $this->dashboard->show_login();
            }
            exit;
        }

        if ($page === 'dashboard') {
            if ($this->dashboard) {
                $this->dashboard->show_dashboard();
            }
            exit;
        }
    }

    public function activate()
    {
        // Crear tablas de base de datos
        $this->create_tables();

        // Flush rewrite rules para activar las nuevas URLs
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        // Limpiar rewrite rules
        flush_rewrite_rules();
    }

    private function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de usuarios
        $table_users = $wpdb->prefix . 'reservas_users';
        $sql_users = "CREATE TABLE $table_users (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            username varchar(50) NOT NULL UNIQUE,
            email varchar(100) NOT NULL UNIQUE,
            password varchar(255) NOT NULL,
            role varchar(20) NOT NULL DEFAULT 'usuario',
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_users);

        // Tabla de servicios
        $table_servicios = $wpdb->prefix . 'reservas_servicios';
        $sql_servicios = "CREATE TABLE $table_servicios (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            fecha date NOT NULL,
            hora time NOT NULL,
            plazas_totales int(11) NOT NULL,
            plazas_disponibles int(11) NOT NULL,
            plazas_bloqueadas int(11) DEFAULT 0,
            precio_adulto decimal(10,2) NOT NULL,
            precio_nino decimal(10,2) NOT NULL,
            precio_residente decimal(10,2) NOT NULL,
            status enum('active', 'inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY fecha_hora (fecha, hora),
            KEY fecha (fecha),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_servicios);

        // Tabla de reglas de descuento
        $table_discounts = $wpdb->prefix . 'reservas_discount_rules';
        $sql_discounts = "CREATE TABLE $table_discounts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            rule_name varchar(100) NOT NULL,
            minimum_persons int(11) NOT NULL,
            discount_percentage decimal(5,2) NOT NULL,
            apply_to enum('total', 'adults_only', 'all_paid') DEFAULT 'total',
            rule_description text,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active),
            KEY minimum_persons (minimum_persons)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_discounts);

        // Crear usuario super admin inicial
        $this->create_super_admin();

        // Crear regla de descuento por defecto
        $this->create_default_discount_rule();
    }

    private function create_super_admin()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'reservas_users';

        // Verificar si ya existe
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE username = %s",
            'superadmin'
        ));

        if ($existing == 0) {
            $wpdb->insert(
                $table_name,
                array(
                    'username' => 'superadmin',
                    'email' => 'admin@' . parse_url(home_url(), PHP_URL_HOST),
                    'password' => password_hash('admin123', PASSWORD_DEFAULT),
                    'role' => 'super_admin',
                    'status' => 'active',
                    'created_at' => current_time('mysql')
                )
            );
        }
    }

    private function create_default_discount_rule()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'reservas_discount_rules';

        // Verificar si ya hay reglas
        $existing_rules = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        if ($existing_rules == 0) {
            $wpdb->insert(
                $table_name,
                array(
                    'rule_name' => 'Descuento Grupo Grande',
                    'minimum_persons' => 10,
                    'discount_percentage' => 15.00,
                    'apply_to' => 'total',
                    'rule_description' => 'Descuento automático para grupos de 10 o más personas',
                    'is_active' => 1
                )
            );
        }
    }
}

// Shortcode para usar en páginas de WordPress (alternativa)
add_shortcode('reservas_login', 'reservas_login_shortcode');

function reservas_login_shortcode()
{
    // Procesar login si se envía el formulario
    if ($_POST && isset($_POST['shortcode_login'])) {
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];

        global $wpdb;
        $table_name = $wpdb->prefix . 'reservas_users';

        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE username = %s AND status = 'active'",
            $username
        ));

        if ($user && password_verify($password, $user->password)) {
            if (!session_id()) {
                session_start();
            }

            $_SESSION['reservas_user'] = array(
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role
            );

            return '<div style="padding: 20px; background: #edfaed; border-left: 4px solid #00a32a; color: #00a32a;">
                        <strong>✅ Login exitoso!</strong> 
                        <br>Ahora puedes ir al <a href="' . home_url('/reservas-admin/') . '">dashboard</a>
                    </div>';
        } else {
            return '<div style="padding: 20px; background: #fbeaea; border-left: 4px solid #d63638; color: #d63638;">
                        <strong>❌ Error:</strong> Usuario o contraseña incorrectos
                    </div>';
        }
    }

    ob_start();
    ?>
    <div style="max-width: 400px; margin: 0 auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="text-align: center; color: #23282d;">Sistema de Reservas - Login</h2>
        <form method="post">
            <input type="hidden" name="shortcode_login" value="1">
            <p>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Usuario:</label>
                <input type="text" name="username" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
            </p>
            <p>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Contraseña:</label>
                <input type="password" name="password" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
            </p>
            <p>
                <input type="submit" value="Iniciar Sesión" style="width: 100%; padding: 12px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
            </p>
        </form>
        <div style="background: #f0f0f1; padding: 15px; margin-top: 15px; border-radius: 4px;">
            <p style="margin: 5px 0; font-size: 14px;"><strong>Usuario:</strong> superadmin</p>
            <p style="margin: 5px 0; font-size: 14px;"><strong>Contraseña:</strong> admin123</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Inicializar el plugin
new SistemaReservas();