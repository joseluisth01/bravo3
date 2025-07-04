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

        // Registrar reglas de reescritura
        $this->add_rewrite_rules();

        // Añadir query vars
        add_filter('query_vars', array($this, 'add_query_vars'));

        // Manejar template redirect
        add_action('template_redirect', array($this, 'template_redirect'));

        add_action('wp_ajax_get_calendar_data', array($this, 'get_calendar_data'));
        add_action('wp_ajax_save_service', array($this, 'save_service'));
        add_action('wp_ajax_delete_service', array($this, 'delete_service'));
        add_action('wp_ajax_get_service_details', array($this, 'get_service_details'));
        add_action('wp_ajax_bulk_add_services', array($this, 'bulk_add_services'));
    }


    public function get_calendar_data()
    {
        // Limpiar cualquier output buffer
        if (ob_get_level()) {
            ob_clean();
        }

        // Headers para JSON
        header('Content-Type: application/json');

        try {
            // Verificar nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'reservas_nonce')) {
                wp_send_json_error('Error de seguridad');
                exit;
            }

            // Verificar sesión
            if (!session_id()) {
                session_start();
            }

            if (!isset($_SESSION['reservas_user'])) {
                wp_send_json_error('Usuario no logueado');
                exit;
            }

            // Obtener datos reales de la base de datos
            global $wpdb;
            $table_name = $wpdb->prefix . 'reservas_servicios';

            $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
            $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

            // Calcular primer y último día del mes
            $first_day = sprintf('%04d-%02d-01', $year, $month);
            $last_day = date('Y-m-t', strtotime($first_day));

            // Consultar servicios del mes
            $servicios = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name 
                WHERE fecha BETWEEN %s AND %s 
                AND status = 'active'
                ORDER BY fecha, hora",
                $first_day,
                $last_day
            ));

            // Organizar por fecha
            $calendar_data = array();
            foreach ($servicios as $servicio) {
                if (!isset($calendar_data[$servicio->fecha])) {
                    $calendar_data[$servicio->fecha] = array();
                }

                $calendar_data[$servicio->fecha][] = array(
                    'id' => $servicio->id,
                    'hora' => substr($servicio->hora, 0, 5), // Formato HH:MM
                    'plazas_totales' => $servicio->plazas_totales,
                    'plazas_disponibles' => $servicio->plazas_disponibles,
                    'precio_adulto' => $servicio->precio_adulto,
                    'precio_nino' => $servicio->precio_nino,
                    'precio_residente' => $servicio->precio_residente
                );
            }

            wp_send_json_success($calendar_data);
            exit;
        } catch (Exception $e) {
            error_log('ERROR EXCEPTION: ' . $e->getMessage());
            wp_send_json_error('Error: ' . $e->getMessage());
            exit;
        }
    }

    // Método para guardar un servicio
    public function save_service()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'reservas_nonce')) {
            wp_die('Error de seguridad');
        }

        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['reservas_user']) || $_SESSION['reservas_user']['role'] !== 'super_admin') {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'reservas_servicios';

        $fecha = sanitize_text_field($_POST['fecha']);
        $hora = sanitize_text_field($_POST['hora']);
        $plazas_totales = intval($_POST['plazas_totales']);
        $precio_adulto = floatval($_POST['precio_adulto']);
        $precio_nino = floatval($_POST['precio_nino']);
        $precio_residente = floatval($_POST['precio_residente']);
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;

        // Validar que no exista ya un servicio en esa fecha y hora
        if ($service_id == 0) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE fecha = %s AND hora = %s",
                $fecha,
                $hora
            ));

            if ($existing > 0) {
                wp_send_json_error('Ya existe un servicio en esa fecha y hora');
            }
        }

        $data = array(
            'fecha' => $fecha,
            'hora' => $hora,
            'plazas_totales' => $plazas_totales,
            'plazas_disponibles' => $plazas_totales,
            'precio_adulto' => $precio_adulto,
            'precio_nino' => $precio_nino,
            'precio_residente' => $precio_residente,
            'status' => 'active'
        );

        if ($service_id > 0) {
            // Actualizar
            $result = $wpdb->update($table_name, $data, array('id' => $service_id));
        } else {
            // Insertar
            $result = $wpdb->insert($table_name, $data);
        }

        if ($result !== false) {
            wp_send_json_success('Servicio guardado correctamente');
        } else {
            wp_send_json_error('Error al guardar el servicio: ' . $wpdb->last_error);
        }
    }

    // Método para eliminar un servicio
    public function delete_service()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'reservas_nonce')) {
            wp_die('Error de seguridad');
        }

        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['reservas_user']) || $_SESSION['reservas_user']['role'] !== 'super_admin') {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'reservas_servicios';

        $service_id = intval($_POST['service_id']);

        // Por ahora eliminar directamente, luego implementaremos la verificación de reservas
        $result = $wpdb->delete($table_name, array('id' => $service_id));

        if ($result !== false) {
            wp_send_json_success('Servicio eliminado correctamente');
        } else {
            wp_send_json_error('Error al eliminar el servicio');
        }
    }

    // Método para obtener detalles de un servicio
    public function get_service_details()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'reservas_nonce')) {
            wp_die('Error de seguridad');
        }

        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['reservas_user']) || $_SESSION['reservas_user']['role'] !== 'super_admin') {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'reservas_servicios';

        $service_id = intval($_POST['service_id']);

        $servicio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $service_id
        ));

        if ($servicio) {
            wp_send_json_success($servicio);
        } else {
            wp_send_json_error('Servicio no encontrado');
        }
    }

    // Método para añadir servicios masivamente
    public function bulk_add_services()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'reservas_nonce')) {
            wp_die('Error de seguridad');
        }

        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['reservas_user']) || $_SESSION['reservas_user']['role'] !== 'super_admin') {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'reservas_servicios';

        $fecha_inicio = sanitize_text_field($_POST['fecha_inicio']);
        $fecha_fin = sanitize_text_field($_POST['fecha_fin']);
        $horarios = json_decode(stripslashes($_POST['horarios']), true);
        $plazas_totales = intval($_POST['plazas_totales']);
        $precio_adulto = floatval($_POST['precio_adulto']);
        $precio_nino = floatval($_POST['precio_nino']);
        $precio_residente = floatval($_POST['precio_residente']);
        $dias_semana = isset($_POST['dias_semana']) ? $_POST['dias_semana'] : array();

        $fecha_actual = strtotime($fecha_inicio);
        $fecha_limite = strtotime($fecha_fin);
        $servicios_creados = 0;
        $servicios_existentes = 0;
        $errores = 0;

        while ($fecha_actual <= $fecha_limite) {
            $fecha_str = date('Y-m-d', $fecha_actual);
            $dia_semana = date('w', $fecha_actual); // 0=domingo, 1=lunes, etc.

            // Verificar si este día de la semana está seleccionado
            if (empty($dias_semana) || in_array($dia_semana, $dias_semana)) {

                foreach ($horarios as $horario) {
                    $hora = sanitize_text_field($horario['hora']);

                    // Verificar si ya existe
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_name WHERE fecha = %s AND hora = %s",
                        $fecha_str,
                        $hora
                    ));

                    if ($existing == 0) {
                        $result = $wpdb->insert($table_name, array(
                            'fecha' => $fecha_str,
                            'hora' => $hora,
                            'plazas_totales' => $plazas_totales,
                            'plazas_disponibles' => $plazas_totales,
                            'precio_adulto' => $precio_adulto,
                            'precio_nino' => $precio_nino,
                            'precio_residente' => $precio_residente,
                            'status' => 'active'
                        ));

                        if ($result !== false) {
                            $servicios_creados++;
                        } else {
                            $errores++;
                            error_log("Error insertando servicio: " . $wpdb->last_error);
                        }
                    } else {
                        $servicios_existentes++;
                    }
                }
            }

            $fecha_actual = strtotime('+1 day', $fecha_actual);
        }

        $mensaje = "Se crearon $servicios_creados servicios.";
        if ($servicios_existentes > 0) {
            $mensaje .= " $servicios_existentes ya existían.";
        }
        if ($errores > 0) {
            $mensaje .= " Hubo $errores errores.";
        }

        wp_send_json_success(array(
            'creados' => $servicios_creados,
            'existentes' => $servicios_existentes,
            'errores' => $errores,
            'mensaje' => $mensaje
        ));
    }

    private function load_dependencies()
    {
        $files = array(
            'includes/class-database.php',
            'includes/class-auth.php',
            'includes/class-admin.php',
        );

        foreach ($files as $file) {
            $path = RESERVAS_PLUGIN_PATH . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }

        // Inicializar solo lo esencial
        if (class_exists('ReservasAuth')) {
            new ReservasAuth();
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
            $this->handle_logout();
        }

        if ($page === 'login') {
            $this->show_login();
            exit;
        }

        if ($page === 'dashboard') {
            $this->show_dashboard();
            exit;
        }
    }

    private function handle_logout()
    {
        if (!session_id()) {
            session_start();
        }
        session_destroy();
        wp_redirect(home_url('/reservas-login/?logout=success'));
        exit;
    }

    private function show_login()
    {
        // Procesar login si se envió el formulario
        if ($_POST && isset($_POST['username']) && isset($_POST['password'])) {
            $this->process_login();
        }

?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>

        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Sistema de Reservas - Login</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    margin: 0;
                    padding: 20px;
                    background: #f1f1f1;
                    color: #333;
                }

                .login-container {
                    max-width: 400px;
                    margin: 100px auto;
                    padding: 30px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                .login-container h2 {
                    text-align: center;
                    margin-bottom: 30px;
                    color: #23282d;
                }

                .form-group {
                    margin-bottom: 20px;
                }

                .form-group label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 600;
                    color: #555;
                }

                .form-group input[type="text"],
                .form-group input[type="password"] {
                    width: 100%;
                    padding: 12px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    box-sizing: border-box;
                    font-size: 16px;
                }

                .form-group input:focus {
                    outline: none;
                    border-color: #0073aa;
                    box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
                }

                .btn-login {
                    width: 100%;
                    padding: 12px;
                    background: #0073aa;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 16px;
                    font-weight: 600;
                }

                .btn-login:hover {
                    background: #005a87;
                }

                .info-box {
                    margin-top: 20px;
                    padding: 15px;
                    background: #f0f0f1;
                    border-radius: 4px;
                    border-left: 4px solid #0073aa;
                }

                .info-box p {
                    margin: 5px 0;
                    font-size: 14px;
                }

                .error {
                    background: #fbeaea;
                    border-left: 4px solid #d63638;
                    padding: 12px;
                    margin: 15px 0;
                    border-radius: 4px;
                    color: #d63638;
                }

                .success {
                    background: #edfaed;
                    border-left: 4px solid #00a32a;
                    padding: 12px;
                    margin: 15px 0;
                    border-radius: 4px;
                    color: #00a32a;
                }
            </style>
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
                    <div class="success">
                        Login correcto. Redirigiendo...
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
                    <div class="success">
                        Sesión cerrada correctamente.
                    </div>
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

    private function process_login()
    {
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

    private function get_error_message($error)
    {
        switch ($error) {
            case 'invalid':
                return 'Usuario o contraseña incorrectos.';
            case 'access':
                return 'Debes iniciar sesión para acceder.';
            default:
                return 'Error desconocido.';
        }
    }

    // REEMPLAZA la parte del dashboard donde está el script por esto:

    private function show_dashboard()
    {
        // Verificar si el usuario está logueado
        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['reservas_user'])) {
            wp_redirect(home_url('/reservas-login/?error=access'));
            exit;
        }

        $user = $_SESSION['reservas_user'];
        $nonce = wp_create_nonce('reservas_nonce');
        $ajax_url = admin_url('admin-ajax.php');

    ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>

        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Sistema de Reservas - Dashboard</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    margin: 0;
                    background: #f1f1f1;
                    color: #333;
                }

                .dashboard-header {
                    background: #23282d;
                    color: white;
                    padding: 15px 20px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .dashboard-header h1 {
                    margin: 0;
                    font-size: 24px;
                }

                .user-info {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }

                .user-role {
                    background: #0073aa;
                    padding: 4px 8px;
                    border-radius: 3px;
                    font-size: 12px;
                    text-transform: uppercase;
                }

                .btn-logout {
                    background: #d63638;
                    color: white;
                    padding: 8px 15px;
                    text-decoration: none;
                    border-radius: 4px;
                    font-size: 14px;
                }

                .btn-logout:hover {
                    background: #b32d2e;
                }

                .dashboard-content {
                    max-width: 1200px;
                    margin: 20px auto;
                    padding: 0 20px;
                }

                .welcome-card {
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    margin-bottom: 20px;
                }

                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }

                .stat-card {
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    text-align: center;
                }

                .stat-number {
                    font-size: 32px;
                    font-weight: bold;
                    color: #0073aa;
                    margin: 10px 0;
                }

                .status-active {
                    color: #00a32a;
                    font-weight: bold;
                }

                .next-steps {
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    margin-top: 20px;
                }

                .next-steps ul {
                    list-style-type: none;
                    padding: 0;
                }

                .next-steps li {
                    padding: 10px 0;
                    border-bottom: 1px solid #eee;
                }

                .next-steps li:before {
                    content: "▶ ";
                    color: #0073aa;
                    font-weight: bold;
                }

                .menu-actions {
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    margin-top: 20px;
                }

                .menu-actions h3 {
                    margin-top: 0;
                    color: #23282d;
                }

                .action-buttons {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 15px;
                }

                .action-btn {
                    display: block;
                    padding: 15px;
                    background: #0073aa;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    text-align: center;
                    font-weight: 600;
                    transition: background 0.3s;
                    border: none;
                    cursor: pointer;
                }

                .action-btn:hover {
                    background: #005a87;
                    color: white;
                }

                .action-btn:visited {
                    color: white;
                }

                /* Estilos para el calendario */
                .calendar-management {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    padding: 20px;
                    background: #f1f1f1;
                    min-height: 100vh;
                }

                .calendar-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 30px;
                    padding: 20px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }

                .calendar-header h1 {
                    margin: 0;
                    color: #23282d;
                }

                .calendar-actions {
                    display: flex;
                    gap: 10px;
                }

                .btn-primary,
                .btn-secondary {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 600;
                }

                .btn-primary {
                    background: #0073aa;
                    color: white;
                }

                .btn-secondary {
                    background: #6c757d;
                    color: white;
                }

                .calendar-controls {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    gap: 20px;
                    margin-bottom: 20px;
                    padding: 15px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }

                .calendar-controls button {
                    padding: 8px 16px;
                    background: #0073aa;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                }

                .calendar-controls button:hover {
                    background: #005a87;
                }

                #currentMonth {
                    font-size: 18px;
                    font-weight: bold;
                    color: #23282d;
                }

                .calendar-grid {
                    display: grid;
                    grid-template-columns: repeat(7, 1fr);
                    gap: 1px;
                    background: #ddd;
                    border-radius: 8px;
                    overflow: hidden;
                }

                .calendar-header-day {
                    background: #23282d;
                    color: white;
                    padding: 15px;
                    text-align: center;
                    font-weight: bold;
                }

                .calendar-day {
                    background: white;
                    min-height: 120px;
                    padding: 10px;
                    position: relative;
                    cursor: pointer;
                    transition: background 0.2s;
                }

                .calendar-day:hover {
                    background: #f8f9fa;
                }

                .calendar-day.other-month {
                    background: #f8f9fa;
                    color: #999;
                }

                .calendar-day.today {
                    background: #e3f2fd;
                    border: 2px solid #0073aa;
                }

                .day-number {
                    font-weight: bold;
                    margin-bottom: 5px;
                }

                .service-item {
                    background: #0073aa;
                    color: white;
                    padding: 2px 6px;
                    margin: 2px 0;
                    border-radius: 3px;
                    font-size: 11px;
                    cursor: pointer;
                }

                .service-item:hover {
                    background: #005a87;
                }

                .loading {
                    text-align: center;
                    padding: 40px;
                    color: #666;
                }

                /* Modales */
                .modal {
                    display: none;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                }

                .modal-content {
                    background-color: white;
                    margin: 5% auto;
                    padding: 20px;
                    border-radius: 8px;
                    width: 90%;
                    max-width: 600px;
                    position: relative;
                }

                .close {
                    position: absolute;
                    top: 10px;
                    right: 15px;
                    font-size: 24px;
                    cursor: pointer;
                }

                .form-group {
                    margin-bottom: 15px;
                }

                .form-group label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 600;
                }

                .form-group input,
                .form-group select {
                    width: 100%;
                    padding: 8px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    box-sizing: border-box;
                }

                .form-row {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                }

                .form-actions {
                    display: flex;
                    gap: 10px;
                    margin-top: 20px;
                }

                .horarios-list {
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 10px;
                    max-height: 200px;
                    overflow-y: auto;
                }

                .horario-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 5px 0;
                    border-bottom: 1px solid #eee;
                }

                .horario-item:last-child {
                    border-bottom: none;
                }

                .btn-small {
                    padding: 4px 8px;
                    font-size: 12px;
                    border: none;
                    border-radius: 3px;
                    cursor: pointer;
                }

                .btn-danger {
                    background: #d63638;
                    color: white;
                }

                .dias-semana {
                    display: grid;
                    grid-template-columns: repeat(7, 1fr);
                    gap: 5px;
                    margin-top: 10px;
                }

                .dia-checkbox {
                    display: flex;
                    align-items: center;
                    gap: 5px;
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

            <script>
                // Variables globales
                const ajaxUrl = '<?php echo $ajax_url; ?>';
                const nonce = '<?php echo $nonce; ?>';
                let currentDate = new Date();
                let servicesData = {};
                let bulkHorarios = [];

                function loadCalendarSection() {
                    document.body.innerHTML = `
                <div class="calendar-management">
                    <div class="calendar-header">
                        <h1>Gestión de Calendario</h1>
                        <div class="calendar-actions">
                            <button class="btn-primary" onclick="showBulkAddModal()">➕ Añadir Múltiples Servicios</button>
                            <button class="btn-secondary" onclick="goBackToDashboard()">← Volver al Dashboard</button>
                        </div>
                    </div>
                    
                    <div class="calendar-controls">
                        <button onclick="changeMonth(-1)">← Mes Anterior</button>
                        <span id="currentMonth"></span>
                        <button onclick="changeMonth(1)">Siguiente Mes →</button>
                    </div>
                    
                    <div id="calendar-container">
                        <div class="loading">Cargando calendario...</div>
                    </div>
                </div>
            `;

                    // Inicializar el calendario
                    initCalendar();
                }

                function initCalendar() {
                    updateCalendarDisplay();
                    loadCalendarData();
                }

                function updateCalendarDisplay() {
                    const monthNames = [
                        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
                    ];

                    document.getElementById('currentMonth').textContent =
                        monthNames[currentDate.getMonth()] + ' ' + currentDate.getFullYear();
                }

                function changeMonth(direction) {
                    currentDate.setMonth(currentDate.getMonth() + direction);
                    updateCalendarDisplay();
                    loadCalendarData();
                }

                function loadCalendarData() {
                    console.log('Iniciando carga de calendario');

                    const formData = new FormData();
                    formData.append('action', 'get_calendar_data');
                    formData.append('month', currentDate.getMonth() + 1);
                    formData.append('year', currentDate.getFullYear());
                    formData.append('nonce', nonce);

                    fetch(ajaxUrl, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            console.log('Response status:', response.status);
                            console.log('Response headers:', response.headers);
                            return response.text(); // Cambiar a .text() para ver el contenido raw
                        })
                        .then(text => {
                            console.log('Raw response:', text);

                            try {
                                const data = JSON.parse(text);
                                console.log('Parsed JSON:', data);

                                if (data.success) {
                                    servicesData = data.data;
                                    renderCalendar();
                                } else {
                                    alert('Error del servidor: ' + data.data);
                                }
                            } catch (e) {
                                console.error('Error parsing JSON:', e);
                                console.error('Raw text that failed to parse:', text);
                                alert('Error: respuesta no es JSON válido. Ver consola para detalles.');
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            alert('Error de conexión: ' + error.message);
                        });
                }

                function renderCalendar() {
                    const year = currentDate.getFullYear();
                    const month = currentDate.getMonth();

                    const firstDay = new Date(year, month, 1);
                    const lastDay = new Date(year, month + 1, 0);
                    let firstDayOfWeek = firstDay.getDay();
                    firstDayOfWeek = (firstDayOfWeek + 6) % 7;

                    const daysInMonth = lastDay.getDate();

                    const dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];


                    let calendarHTML = '<div class="calendar-grid">';

                    // Encabezados de días
                    dayNames.forEach(day => {
                        calendarHTML += `<div class="calendar-header-day">${day}</div>`;
                    });

                    for (let i = 0; i < firstDayOfWeek; i++) {
                        const dayNum = new Date(year, month, -firstDayOfWeek + i + 1).getDate();
                        calendarHTML += `<div class="calendar-day other-month">
        <div class="day-number">${dayNum}</div>
    </div>`;
                    }

                    // Días del mes actual
                    for (let day = 1; day <= daysInMonth; day++) {
                        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                        const isToday = dateStr === new Date().toISOString().split('T')[0];
                        const todayClass = isToday ? ' today' : '';

                        let servicesHTML = '';
                        if (servicesData[dateStr]) {
                            servicesData[dateStr].forEach(service => {
                                servicesHTML += `<div class="service-item" onclick="editService(${service.id})">${service.hora}</div>`;
                            });
                        }

                        calendarHTML += `<div class="calendar-day${todayClass}" onclick="addService('${dateStr}')">
                    <div class="day-number">${day}</div>
                    ${servicesHTML}
                </div>`;
                    }

                    calendarHTML += '</div>';

                    // Modales
                    calendarHTML += `
                <!-- Modal Añadir/Editar Servicio -->
                <div id="serviceModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeServiceModal()">&times;</span>
                        <h3 id="serviceModalTitle">Añadir Servicio</h3>
                        <form id="serviceForm">
                            <input type="hidden" id="serviceId" name="service_id">
                            <div class="form-group">
                                <label for="serviceFecha">Fecha:</label>
                                <input type="date" id="serviceFecha" name="fecha" required>
                            </div>
                            <div class="form-group">
                                <label for="serviceHora">Hora:</label>
                                <input type="time" id="serviceHora" name="hora" required>
                            </div>
                            <div class="form-group">
                                <label for="servicePlazas">Plazas Totales:</label>
                                <input type="number" id="servicePlazas" name="plazas_totales" min="1" max="100" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="precioAdulto">Precio Adulto (€):</label>
                                    <input type="number" id="precioAdulto" name="precio_adulto" step="0.01" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="precioNino">Precio Niño (€):</label>
                                    <input type="number" id="precioNino" name="precio_nino" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="precioResidente">Precio Residente (€):</label>
                                <input type="number" id="precioResidente" name="precio_residente" step="0.01" min="0" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Guardar Servicio</button>
                                <button type="button" class="btn-secondary" onclick="closeServiceModal()">Cancelar</button>
                                <button type="button" id="deleteServiceBtn" class="btn-danger" onclick="deleteService()" style="display: none;">Eliminar</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Modal Añadir Múltiples Servicios -->
                <div id="bulkAddModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeBulkAddModal()">&times;</span>
                        <h3>Añadir Múltiples Servicios</h3>
                        <form id="bulkAddForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="bulkFechaInicio">Fecha Inicio:</label>
                                    <input type="date" id="bulkFechaInicio" name="fecha_inicio" required>
                                </div>
                                <div class="form-group">
                                    <label for="bulkFechaFin">Fecha Fin:</label>
                                    <input type="date" id="bulkFechaFin" name="fecha_fin" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Días de la semana:</label>
                                <div class="dias-semana">
                                    <div class="dia-checkbox">
                                        <input type="checkbox" id="dom" name="dias_semana[]" value="0">
                                        <label for="dom">Dom</label>
                                    </div>
                                    <div class="dia-checkbox">
                                        <input type="checkbox" id="lun" name="dias_semana[]" value="1">
                                        <label for="lun">Lun</label>
                                    </div>
                                    <div class="dia-checkbox">
                                        <input type="checkbox" id="mar" name="dias_semana[]" value="2">
                                        <label for="mar">Mar</label>
                                    </div>
                                    <div class="dia-checkbox">
                                        <input type="checkbox" id="mie" name="dias_semana[]" value="3">
                                        <label for="mie">Mié</label>
                                    </div>
                                    <div class="dia-checkbox">
                                        <input type="checkbox" id="jue" name="dias_semana[]" value="4">
                                        <label for="jue">Jue</label>
                                    </div>
                                    <div class="dia-checkbox">
                                        <input type="checkbox" id="vie" name="dias_semana[]" value="5">
                                        <label for="vie">Vie</label>
                                    </div>
                                    <div class="dia-checkbox">
                                        <input type="checkbox" id="sab" name="dias_semana[]" value="6">
                                        <label for="sab">Sáb</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Horarios:</label>
                                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                    <input type="time" id="nuevoHorario" placeholder="Hora">
                                    <button type="button" class="btn-primary" onclick="addHorario()">Añadir</button>
                                </div>
                                <div id="horariosList" class="horarios-list">
                                    <!-- Los horarios se añadirán aquí -->
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="bulkPlazas">Plazas por Servicio:</label>
                                <input type="number" id="bulkPlazas" name="plazas_totales" min="1" max="100" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="bulkPrecioAdulto">Precio Adulto (€):</label>
                                    <input type="number" id="bulkPrecioAdulto" name="precio_adulto" step="0.01" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="bulkPrecioNino">Precio Niño (€):</label>
                                    <input type="number" id="bulkPrecioNino" name="precio_nino" step="0.01" min="0" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="bulkPrecioResidente">Precio Residente (€):</label>
                                <input type="number" id="bulkPrecioResidente" name="precio_residente" step="0.01" min="0" required>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Crear Servicios</button>
                                <button type="button" class="btn-secondary" onclick="closeBulkAddModal()">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

                    document.getElementById('calendar-container').innerHTML = calendarHTML;

                    // Inicializar eventos de los modales
                    initModalEvents();
                }

                function initModalEvents() {
                    // Formulario de servicio individual
                    document.getElementById('serviceForm').addEventListener('submit', function(e) {
                        e.preventDefault();
                        saveService();
                    });

                    // Formulario de servicios masivos
                    document.getElementById('bulkAddForm').addEventListener('submit', function(e) {
                        e.preventDefault();
                        saveBulkServices();
                    });
                }

                function addService(fecha) {
                    document.getElementById('serviceModalTitle').textContent = 'Añadir Servicio';
                    document.getElementById('serviceForm').reset();
                    document.getElementById('serviceId').value = '';
                    document.getElementById('serviceFecha').value = fecha;
                    document.getElementById('deleteServiceBtn').style.display = 'none';

                    // Valores por defecto
                    document.getElementById('servicePlazas').value = 50;
                    document.getElementById('precioAdulto').value = 10.00;
                    document.getElementById('precioNino').value = 5.00;
                    document.getElementById('precioResidente').value = 5.00;

                    document.getElementById('serviceModal').style.display = 'block';
                }

                function editService(serviceId) {
                    const formData = new FormData();
                    formData.append('action', 'get_service_details');
                    formData.append('service_id', serviceId);
                    formData.append('nonce', nonce);

                    fetch(ajaxUrl, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const service = data.data;
                                document.getElementById('serviceModalTitle').textContent = 'Editar Servicio';
                                document.getElementById('serviceId').value = service.id;
                                document.getElementById('serviceFecha').value = service.fecha;
                                document.getElementById('serviceHora').value = service.hora;
                                document.getElementById('servicePlazas').value = service.plazas_totales;
                                document.getElementById('precioAdulto').value = service.precio_adulto;
                                document.getElementById('precioNino').value = service.precio_nino;
                                document.getElementById('precioResidente').value = service.precio_residente;
                                document.getElementById('deleteServiceBtn').style.display = 'block';

                                document.getElementById('serviceModal').style.display = 'block';
                            } else {
                                alert('Error al cargar el servicio: ' + data.data);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error de conexión');
                        });
                }

                function saveService() {
                    const formData = new FormData(document.getElementById('serviceForm'));
                    formData.append('action', 'save_service');
                    formData.append('nonce', nonce);

                    fetch(ajaxUrl, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Servicio guardado correctamente');
                                closeServiceModal();
                                loadCalendarData();
                            } else {
                                alert('Error: ' + data.data);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error de conexión');
                        });
                }

                function deleteService() {
                    if (!confirm('¿Estás seguro de que quieres eliminar este servicio?')) {
                        return;
                    }

                    const serviceId = document.getElementById('serviceId').value;
                    const formData = new FormData();
                    formData.append('action', 'delete_service');
                    formData.append('service_id', serviceId);
                    formData.append('nonce', nonce);

                    fetch(ajaxUrl, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Servicio eliminado correctamente');
                                closeServiceModal();
                                loadCalendarData();
                            } else {
                                alert('Error: ' + data.data);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error de conexión');
                        });
                }

                function closeServiceModal() {
                    document.getElementById('serviceModal').style.display = 'none';
                }

                function showBulkAddModal() {
                    document.getElementById('bulkAddForm').reset();
                    bulkHorarios = [];
                    updateHorariosList();

                    // Valores por defecto
                    document.getElementById('bulkPlazas').value = 50;
                    document.getElementById('bulkPrecioAdulto').value = 10.00;
                    document.getElementById('bulkPrecioNino').value = 5.00;
                    document.getElementById('bulkPrecioResidente').value = 5.00;

                    document.getElementById('bulkAddModal').style.display = 'block';
                }

                function closeBulkAddModal() {
                    document.getElementById('bulkAddModal').style.display = 'none';
                }

                function addHorario() {
                    const horarioInput = document.getElementById('nuevoHorario');
                    const horario = horarioInput.value;

                    if (horario && !bulkHorarios.find(h => h.hora === horario)) {
                        bulkHorarios.push({
                            hora: horario
                        });
                        horarioInput.value = '';
                        updateHorariosList();
                    }
                }

                function removeHorario(index) {
                    bulkHorarios.splice(index, 1);
                    updateHorariosList();
                }

                function updateHorariosList() {
                    const container = document.getElementById('horariosList');

                    if (bulkHorarios.length === 0) {
                        container.innerHTML = '<p style="text-align: center; color: #666;">No hay horarios añadidos</p>';
                        return;
                    }

                    let html = '';
                    bulkHorarios.forEach((horario, index) => {
                        html += `
                    <div class="horario-item">
                        <span>${horario.hora}</span>
                        <button type="button" class="btn-small btn-danger" onclick="removeHorario(${index})">Eliminar</button>
                    </div>
                `;
                    });

                    container.innerHTML = html;
                }

                function saveBulkServices() {
                    if (bulkHorarios.length === 0) {
                        alert('Debes añadir al menos un horario');
                        return;
                    }

                    const formData = new FormData(document.getElementById('bulkAddForm'));
                    formData.append('action', 'bulk_add_services');
                    formData.append('horarios', JSON.stringify(bulkHorarios));
                    formData.append('nonce', nonce);

                    // Obtener días de la semana seleccionados
                    const diasSeleccionados = [];
                    document.querySelectorAll('input[name="dias_semana[]"]:checked').forEach(checkbox => {
                        diasSeleccionados.push(checkbox.value);
                    });

                    diasSeleccionados.forEach(dia => {
                        formData.append('dias_semana[]', dia);
                    });

                    fetch(ajaxUrl, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert(data.data.mensaje);
                                closeBulkAddModal();
                                loadCalendarData();
                            } else {
                                alert('Error: ' + data.data);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error de conexión');
                        });
                }

                function goBackToDashboard() {
                    location.reload();
                }
            </script>
        </body>

        </html>
    <?php
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

        // Crear usuario super admin inicial
        $this->create_super_admin();
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
