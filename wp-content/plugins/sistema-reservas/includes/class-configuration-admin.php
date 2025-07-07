<?php
/**
 * Clase para gestionar la configuración del sistema de reservas
 * Archivo: wp-content/plugins/sistema-reservas/includes/class-configuration-admin.php
 */
class ReservasConfigurationAdmin {
    
    public function __construct() {
        // Hooks AJAX para configuración
        add_action('wp_ajax_get_configuration', array($this, 'get_configuration'));
        add_action('wp_ajax_save_configuration', array($this, 'save_configuration'));
        
        // Hook para activación del plugin (crear tabla)
        add_action('init', array($this, 'maybe_create_table'));
    }

    /**
     * Crear tabla de configuración si no existe
     */
    public function maybe_create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'reservas_configuration';
        
        // Verificar si la tabla existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            $this->create_configuration_table();
        }
    }

    /**
     * Crear tabla de configuración
     */
    private function create_configuration_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'reservas_configuration';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            config_key varchar(100) NOT NULL UNIQUE,
            config_value longtext,
            config_group varchar(50) DEFAULT 'general',
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY config_key (config_key),
            KEY config_group (config_group)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Crear configuración por defecto
        $this->create_default_configuration();
    }

    /**
     * Crear configuración por defecto
     */
    private function create_default_configuration() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'reservas_configuration';
        
        $default_configs = array(
            // Precios por defecto
            array(
                'config_key' => 'precio_adulto_defecto',
                'config_value' => '10.00',
                'config_group' => 'precios',
                'description' => 'Precio por defecto para adultos'
            ),
            array(
                'config_key' => 'precio_nino_defecto',
                'config_value' => '5.00',
                'config_group' => 'precios',
                'description' => 'Precio por defecto para niños (5-12 años)'
            ),
            array(
                'config_key' => 'precio_residente_defecto',
                'config_value' => '5.00',
                'config_group' => 'precios',
                'description' => 'Precio por defecto para residentes'
            ),
            
            // Configuración de servicios
            array(
                'config_key' => 'plazas_defecto',
                'config_value' => '50',
                'config_group' => 'servicios',
                'description' => 'Número de plazas por defecto por autobús'
            ),
            array(
                'config_key' => 'hora_vuelta_estandar',
                'config_value' => '13:30',
                'config_group' => 'servicios',
                'description' => 'Hora de vuelta estándar'
            ),
            array(
                'config_key' => 'dias_anticipacion_minima',
                'config_value' => '1',
                'config_group' => 'servicios',
                'description' => 'Días de anticipación mínima para reservar'
            ),
            
            // Notificaciones
            array(
                'config_key' => 'email_confirmacion_activo',
                'config_value' => '1',
                'config_group' => 'notificaciones',
                'description' => 'Activar email de confirmación automático'
            ),
            array(
                'config_key' => 'email_recordatorio_activo',
                'config_value' => '0',
                'config_group' => 'notificaciones',
                'description' => 'Activar recordatorios antes del viaje'
            ),
            array(
                'config_key' => 'horas_recordatorio',
                'config_value' => '24',
                'config_group' => 'notificaciones',
                'description' => 'Horas antes del viaje para enviar recordatorio'
            ),
            array(
                'config_key' => 'email_remitente',
                'config_value' => get_option('admin_email'),
                'config_group' => 'notificaciones',
                'description' => 'Email remitente para notificaciones'
            ),
            array(
                'config_key' => 'nombre_remitente',
                'config_value' => get_bloginfo('name'),
                'config_group' => 'notificaciones',
                'description' => 'Nombre del remitente para notificaciones'
            ),
            
            // Personalización
            array(
                'config_key' => 'nombre_empresa',
                'config_value' => get_bloginfo('name'),
                'config_group' => 'personalizacion',
                'description' => 'Nombre de la empresa'
            ),
            array(
                'config_key' => 'color_primario',
                'config_value' => '#EFCF4B',
                'config_group' => 'personalizacion',
                'description' => 'Color primario del sistema'
            ),
            array(
                'config_key' => 'color_secundario',
                'config_value' => '#E74C3C',
                'config_group' => 'personalizacion',
                'description' => 'Color secundario del sistema'
            ),
            array(
                'config_key' => 'texto_reserva_exitosa',
                'config_value' => '¡Tu reserva ha sido confirmada! Recibirás un email con los detalles.',
                'config_group' => 'personalizacion',
                'description' => 'Mensaje mostrado tras reserva exitosa'
            ),
            
            // General
            array(
                'config_key' => 'zona_horaria',
                'config_value' => 'Europe/Madrid',
                'config_group' => 'general',
                'description' => 'Zona horaria del sistema'
            ),
            array(
                'config_key' => 'moneda',
                'config_value' => 'EUR',
                'config_group' => 'general',
                'description' => 'Moneda utilizada'
            ),
            array(
                'config_key' => 'simbolo_moneda',
                'config_value' => '€',
                'config_group' => 'general',
                'description' => 'Símbolo de la moneda'
            ),
            array(
                'config_key' => 'idioma',
                'config_value' => 'es_ES',
                'config_group' => 'general',
                'description' => 'Idioma del sistema'
            )
        );
        
        foreach ($default_configs as $config) {
            // Verificar si ya existe
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE config_key = %s",
                $config['config_key']
            ));
            
            if ($existing == 0) {
                $wpdb->insert($table_name, $config);
            }
        }
    }

    /**
     * Obtener toda la configuración
     */
    public function get_configuration() {
        if (!wp_verify_nonce($_POST['nonce'], 'reservas_nonce')) {
            wp_send_json_error('Error de seguridad');
        }

        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['reservas_user']) || $_SESSION['reservas_user']['role'] !== 'super_admin') {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'reservas_configuration';

        $configs = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY config_group, config_key"
        );

        // Organizar por grupos
        $grouped_configs = array();
        foreach ($configs as $config) {
            if (!isset($grouped_configs[$config->config_group])) {
                $grouped_configs[$config->config_group] = array();
            }
            $grouped_configs[$config->config_group][$config->config_key] = array(
                'value' => $config->config_value,
                'description' => $config->description
            );
        }

        wp_send_json_success($grouped_configs);
    }

    /**
     * Guardar configuración
     */
    public function save_configuration() {
        if (!wp_verify_nonce($_POST['nonce'], 'reservas_nonce')) {
            wp_send_json_error('Error de seguridad');
        }

        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['reservas_user']) || $_SESSION['reservas_user']['role'] !== 'super_admin') {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'reservas_configuration';

        // Obtener datos del formulario
        $configs_to_save = array();
        
        // Precios
        if (isset($_POST['precio_adulto_defecto'])) {
            $configs_to_save['precio_adulto_defecto'] = floatval($_POST['precio_adulto_defecto']);
        }
        if (isset($_POST['precio_nino_defecto'])) {
            $configs_to_save['precio_nino_defecto'] = floatval($_POST['precio_nino_defecto']);
        }
        if (isset($_POST['precio_residente_defecto'])) {
            $configs_to_save['precio_residente_defecto'] = floatval($_POST['precio_residente_defecto']);
        }
        
        // Servicios
        if (isset($_POST['plazas_defecto'])) {
            $configs_to_save['plazas_defecto'] = intval($_POST['plazas_defecto']);
        }
        if (isset($_POST['hora_vuelta_estandar'])) {
            $configs_to_save['hora_vuelta_estandar'] = sanitize_text_field($_POST['hora_vuelta_estandar']);
        }
        if (isset($_POST['dias_anticipacion_minima'])) {
            $configs_to_save['dias_anticipacion_minima'] = intval($_POST['dias_anticipacion_minima']);
        }
        
        // Notificaciones
        if (isset($_POST['email_confirmacion_activo'])) {
            $configs_to_save['email_confirmacion_activo'] = $_POST['email_confirmacion_activo'] ? 1 : 0;
        }
        if (isset($_POST['email_recordatorio_activo'])) {
            $configs_to_save['email_recordatorio_activo'] = $_POST['email_recordatorio_activo'] ? 1 : 0;
        }
        if (isset($_POST['horas_recordatorio'])) {
            $configs_to_save['horas_recordatorio'] = intval($_POST['horas_recordatorio']);
        }
        if (isset($_POST['email_remitente'])) {
            $configs_to_save['email_remitente'] = sanitize_email($_POST['email_remitente']);
        }
        if (isset($_POST['nombre_remitente'])) {
            $configs_to_save['nombre_remitente'] = sanitize_text_field($_POST['nombre_remitente']);
        }
        
        // Personalización
        if (isset($_POST['nombre_empresa'])) {
            $configs_to_save['nombre_empresa'] = sanitize_text_field($_POST['nombre_empresa']);
        }
        if (isset($_POST['color_primario'])) {
            $configs_to_save['color_primario'] = sanitize_text_field($_POST['color_primario']);
        }
        if (isset($_POST['color_secundario'])) {
            $configs_to_save['color_secundario'] = sanitize_text_field($_POST['color_secundario']);
        }
        if (isset($_POST['texto_reserva_exitosa'])) {
            $configs_to_save['texto_reserva_exitosa'] = sanitize_textarea_field($_POST['texto_reserva_exitosa']);
        }
        
        // General
        if (isset($_POST['zona_horaria'])) {
            $configs_to_save['zona_horaria'] = sanitize_text_field($_POST['zona_horaria']);
        }
        if (isset($_POST['moneda'])) {
            $configs_to_save['moneda'] = sanitize_text_field($_POST['moneda']);
        }
        if (isset($_POST['simbolo_moneda'])) {
            $configs_to_save['simbolo_moneda'] = sanitize_text_field($_POST['simbolo_moneda']);
        }
        if (isset($_POST['idioma'])) {
            $configs_to_save['idioma'] = sanitize_text_field($_POST['idioma']);
        }

        // Guardar cada configuración
        $saved_count = 0;
        $errors = array();

        foreach ($configs_to_save as $key => $value) {
            $result = $wpdb->update(
                $table_name,
                array('config_value' => $value),
                array('config_key' => $key)
            );

            if ($result !== false) {
                $saved_count++;
            } else {
                $errors[] = "Error guardando $key: " . $wpdb->last_error;
            }
        }

        if (count($errors) > 0) {
            wp_send_json_error('Errores al guardar: ' . implode(', ', $errors));
        }

        wp_send_json_success("Configuración guardada correctamente. $saved_count elementos actualizados.");
    }

    /**
     * Método estático para obtener un valor de configuración
     */
    public static function get_config($key, $default = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'reservas_configuration';
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT config_value FROM $table_name WHERE config_key = %s",
            $key
        ));
        
        return $value !== null ? $value : $default;
    }

    /**
     * Método estático para establecer un valor de configuración
     */
    public static function set_config($key, $value, $group = 'general', $description = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'reservas_configuration';
        
        // Intentar actualizar primero
        $updated = $wpdb->update(
            $table_name,
            array('config_value' => $value),
            array('config_key' => $key)
        );
        
        // Si no se actualizó nada, insertar
        if ($updated === 0) {
            $wpdb->insert(
                $table_name,
                array(
                    'config_key' => $key,
                    'config_value' => $value,
                    'config_group' => $group,
                    'description' => $description
                )
            );
        }
        
        return true;
    }
}

