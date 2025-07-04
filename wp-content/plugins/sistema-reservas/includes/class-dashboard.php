<?php
class ReservasDashboard {
    
    public function __construct() {
        // Inicializar hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));
    }
    
    public function enqueue_dashboard_assets() {
        // Solo cargar en nuestras páginas
        $page = get_query_var('reservas_page');
        if ($page === 'login' || $page === 'dashboard') {
            wp_enqueue_style(
                'reservas-dashboard-style',
                RESERVAS_PLUGIN_URL . 'assets/css/admin-style.css',
                array(),
                '1.0.0'
            );
            
            wp_enqueue_script(
                'reservas-dashboard-script',
                RESERVAS_PLUGIN_URL . 'assets/js/dashboard-script.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            // Pasar variables a JavaScript
            wp_localize_script('reservas-dashboard-script', 'reservasAjax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('reservas_nonce')
            ));
        }
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
        
        include RESERVAS_PLUGIN_PATH . 'templates/login-template.php';
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
        
        include RESERVAS_PLUGIN_PATH . 'templates/dashboard-template.php';
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