<?php
class ReservasFrontend {
    
    public function __construct() {
        add_shortcode('reservas_formulario', array($this, 'render_booking_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // AJAX hooks para el frontend
        add_action('wp_ajax_get_available_services', array($this, 'get_available_services'));
        add_action('wp_ajax_nopriv_get_available_services', array($this, 'get_available_services'));
        add_action('wp_ajax_calculate_price', array($this, 'calculate_price'));
        add_action('wp_ajax_nopriv_calculate_price', array($this, 'calculate_price'));
    }
    
public function enqueue_frontend_assets() {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'reservas_formulario')) {
        wp_enqueue_style(
            'reservas-frontend-style',
            RESERVAS_PLUGIN_URL . 'assets/css/frontend-style.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'reservas-frontend-script',
            RESERVAS_PLUGIN_URL . 'assets/js/frontend-script.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('reservas-frontend-script', 'reservasAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reservas_nonce')
        ));
    }
}
    
    public function render_booking_form() {
        ob_start();
        ?>
        <div id="reservas-formulario" class="reservas-booking-container">
            <!-- Paso 1: Seleccionar fecha y hora -->
            <div class="booking-step" id="step-1">
                <div class="step-card">
                    <h3>1. ELIGE EL DÍA Y LA HORA</h3>
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <button type="button" id="prev-month">‹</button>
                            <span id="current-month-year"></span>
                            <button type="button" id="next-month">›</button>
                        </div>
                        <div class="calendar-grid" id="calendar-grid">
                            <!-- El calendario se generará aquí -->
                        </div>
                        <div class="calendar-legend">
                            <span class="legend-item">
                                <span class="legend-color no-disponible"></span>
                                Día No Disponible
                            </span>
                            <span class="legend-item">
                                <span class="legend-color seleccion"></span>
                                Selección
                            </span>
                            <span class="legend-item">
                                <span class="legend-color oferta"></span>
                                Día con Oferta
                            </span>
                        </div>
                        <div class="horarios-section">
                            <label>HORARIOS</label>
                            <select id="horarios-select" disabled>
                                <option value="">Selecciona primero una fecha</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paso 2: Seleccionar personas -->
            <div class="booking-step" id="step-2" style="display: none;">
                <div class="step-card">
                    <h3>2. SELECCIONA LAS PERSONAS</h3>
                    <div class="persons-grid">
                        <div class="person-selector">
                            <label>ADULTOS</label>
                            <select id="adultos">
                                <option value="0">0</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                        
                        <div class="person-selector">
                            <label>ADULTOS RESIDENTES</label>
                            <select id="residentes">
                                <option value="0">0</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                        
                        <div class="person-selector">
                            <label>NIÑOS (5/12 AÑOS)</label>
                            <select id="ninos-5-12">
                                <option value="0">0</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                        
                        <div class="person-selector">
                            <label>NIÑOS (-5 AÑOS)</label>
                            <select id="ninos-menores">
                                <option value="0">0</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="price-summary">
                        <div class="price-row">
                            <span>ADULTOS: <span id="price-adultos">10€</span></span>
                            <span>NIÑOS (DE 5 A 12 AÑOS): <span id="price-ninos">5€</span></span>
                        </div>
                        <div class="price-notes">
                            <p>*NIÑOS (Menores de 5 años): 0€ (viajan gratis).</p>
                            <p>*RESIDENTES en Córdoba: 50% de descuento.</p>
                            <p>*Los RESIDENTES deben llevar un documento que lo acredite y presentarlo en persona.</p>
                            <p>*En reservas de más de 10 personas se aplica DESCUENTO POR GRUPO.</p>
                        </div>
                        <div class="total-price">
                            <span class="discount">DESCUENTOS: <span id="total-discount">-10€</span></span>
                            <span class="total">TOTAL: <span id="total-price">25€</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paso 3: Completar reserva -->
            <div class="booking-step" id="step-3" style="display: none;">
                <div class="step-card complete-booking">
                    <h3>3. COMPLETA RESERVA</h3>
                    <button type="button" class="complete-btn" onclick="proceedToPayment()">
                        Proceder al Pago
                    </button>
                </div>
            </div>

            <!-- Navegación -->
            <div class="booking-navigation">
                <button type="button" id="btn-anterior" onclick="previousStep()" style="display: none;">
                    ← Anterior
                </button>
                <button type="button" id="btn-siguiente" onclick="nextStep()" disabled>
                    Siguiente →
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function get_available_services() {
        if (!wp_verify_nonce($_POST['nonce'], 'reservas_nonce')) {
            wp_die('Error de seguridad');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservas_servicios';
        
        $month = intval($_POST['month']);
        $year = intval($_POST['year']);
        
        // Calcular primer y último día del mes
        $first_day = sprintf('%04d-%02d-01', $year, $month);
        $last_day = date('Y-m-t', strtotime($first_day));
        
        // Consultar servicios del mes
        $servicios = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE fecha BETWEEN %s AND %s 
            AND status = 'active'
            AND plazas_disponibles > 0
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
                'hora' => substr($servicio->hora, 0, 5),
                'plazas_disponibles' => $servicio->plazas_disponibles,
                'precio_adulto' => $servicio->precio_adulto,
                'precio_nino' => $servicio->precio_nino,
                'precio_residente' => $servicio->precio_residente
            );
        }
        
        wp_send_json_success($calendar_data);
    }
    
    public function calculate_price() {
        if (!wp_verify_nonce($_POST['nonce'], 'reservas_nonce')) {
            wp_die('Error de seguridad');
        }
        
        $service_id = intval($_POST['service_id']);
        $adultos = intval($_POST['adultos']);
        $residentes = intval($_POST['residentes']);
        $ninos_5_12 = intval($_POST['ninos_5_12']);
        $ninos_menores = intval($_POST['ninos_menores']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservas_servicios';
        
        $servicio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $service_id
        ));
        
        if (!$servicio) {
            wp_send_json_error('Servicio no encontrado');
        }
        
        $total_personas = $adultos + $residentes + $ninos_5_12;
        $precio_base = 0;
        $descuento = 0;
        
        // Calcular precio base
        $precio_base += $adultos * $servicio->precio_adulto;
        $precio_base += $ninos_5_12 * $servicio->precio_nino;
        $precio_base += $residentes * $servicio->precio_residente;
        
        // Descuento por grupo (más de 10 personas)
        if ($total_personas > 10) {
            $descuento = $precio_base * 0.15; // 15% descuento
        }
        
        $total = $precio_base - $descuento;
        
        wp_send_json_success(array(
            'precio_base' => $precio_base,
            'descuento' => $descuento,
            'total' => $total,
            'precio_adulto' => $servicio->precio_adulto,
            'precio_nino' => $servicio->precio_nino,
            'precio_residente' => $servicio->precio_residente
        ));
    }
}