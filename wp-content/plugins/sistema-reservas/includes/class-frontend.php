<?php
class ReservasFrontend
{

    public function __construct()
    {
        add_shortcode('reservas_formulario', array($this, 'render_booking_form'));
            add_shortcode('reservas_detalles', array($this, 'render_details_form')); // ← AÑADIR ESTA LÍNEA

        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        // AJAX hooks para el frontend
        add_action('wp_ajax_get_available_services', array($this, 'get_available_services'));
        add_action('wp_ajax_nopriv_get_available_services', array($this, 'get_available_services'));
        add_action('wp_ajax_calculate_price', array($this, 'calculate_price'));
        add_action('wp_ajax_nopriv_calculate_price', array($this, 'calculate_price'));
    }

    public function enqueue_frontend_assets()
    {
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

    public function render_booking_form()
    {
        ob_start();
?>
        <div id="reservas-formulario" class="reservas-booking-container">
            <!-- Paso 1: Seleccionar fecha/hora Y personas juntos -->
            <div class="booking-step" id="step-1">
                <div class="booking-steps-grid">
                    <!-- Columna izquierda: Calendario -->
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

                    <!-- Columna derecha: Selección de personas -->
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
            </div>

            <!-- Paso 2: Completar reserva (botón) -->
            <div class="booking-step" id="step-2">
                <div class="step-card complete-booking">
                    <h3>3. COMPLETA RESERVA</h3>
                    <button type="button" class="complete-btn" onclick="proceedToDetails()">
                        Continuar
                    </button>
                </div>
            </div>

            <!-- Navegación -->
            <div class="booking-navigation">
                <button type="button" id="btn-siguiente" onclick="nextStep()" disabled>
                    Siguiente →
                </button>
            </div>
        </div>
<?php
        return ob_get_clean();
    }

    public function get_available_services()
    {
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

    public function calculate_price()
    {
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



    public function render_details_form() {
    ob_start();
    ?>
    <div id="reservas-detalles" class="reservas-details-container">
        <!-- Detalles de la reserva -->
        <div class="details-summary">
            <h2>DETALLES DE LA RESERVA</h2>
            <div class="details-grid">
                <div class="details-section">
                    <h3>FECHAS Y HORAS</h3>
                    <div class="detail-row">
                        <span class="label">FECHA AUTOBÚS IDA:</span>
                        <span class="value" id="fecha-ida">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">HORA AUTOBÚS IDA:</span>
                        <span class="value" id="hora-ida">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">FECHA AUTOBÚS VUELTA:</span>
                        <span class="value" id="fecha-vuelta">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">HORA AUTOBÚS VUELTA:</span>
                        <span class="value" id="hora-vuelta">-</span>
                    </div>
                </div>
                
                <div class="details-section">
                    <h3>BILLETES Y/O PERSONAS</h3>
                    <div class="detail-row">
                        <span class="label">NÚMERO DE ADULTOS:</span>
                        <span class="value" id="num-adultos">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">NÚMERO DE RESIDENTES:</span>
                        <span class="value" id="num-residentes">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">NÚMERO DE NIÑOS (5/12 AÑOS):</span>
                        <span class="value" id="num-ninos-5-12">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">NÚMERO DE NIÑOS (-5 AÑOS):</span>
                        <span class="value" id="num-ninos-menores">-</span>
                    </div>
                </div>
                
                <div class="details-section">
                    <h3>PRECIOS</h3>
                    <div class="detail-row">
                        <span class="label">IMPORTE BASE:</span>
                        <span class="value" id="importe-base">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">DESCUENTO RESIDENTES:</span>
                        <span class="value" id="descuento-residentes">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">DESCUENTO MENORES:</span>
                        <span class="value" id="descuento-menores">-0€</span>
                    </div>
                    <div class="detail-row total-row">
                        <span class="label">TOTAL RESERVA:</span>
                        <span class="value total-price" id="total-reserva">-</span>
                    </div>
                </div>
            </div>
            
            <div class="confirm-section">
                <button type="button" class="confirm-btn" id="confirm-info-btn">
                    CONFIRMAR INFORMACIÓN ☺
                </button>
            </div>
        </div>
        
        <!-- Formularios de datos -->
        <div class="forms-section" id="forms-section" style="display: none;">
            <div class="forms-grid">
                <div class="form-card">
                    <h3>DATOS PERSONALES</h3>
                    <form id="personal-data-form">
                        <div class="form-group">
                            <input type="text" name="nombre" placeholder="NOMBRE" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="apellidos" placeholder="APELLIDOS" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="EMAIL" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" name="telefono" placeholder="MÓVIL O TELÉFONO" required>
                        </div>
                    </form>
                </div>
                
                <div class="form-card">
                    <h3>DATOS BANCARIOS</h3>
                    <form id="payment-data-form">
                        <div class="form-group">
                            <input type="text" name="numero_tarjeta" placeholder="NÚMERO DE TARJETA" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="cvv" placeholder="CVV" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="fecha_caducidad" placeholder="FECHA DE CADUCIDAD" required>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="final-buttons">
                <button type="button" class="back-btn" onclick="goBackToBooking()">
                    ← VOLVER A MODIFICAR RESERVA
                </button>
                <button type="button" class="process-btn" onclick="processReservation()">
                    PROCESAR RESERVA
                </button>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Cargar datos de la reserva desde sessionStorage
        loadReservationData();
        
        // Confirmar información
        $('#confirm-info-btn').on('click', function() {
            $('.details-summary').hide();
            $('#forms-section').show();
        });
    });
    
    function loadReservationData() {
        const data = JSON.parse(sessionStorage.getItem('reservationData') || '{}');
        
        if (Object.keys(data).length === 0) {
            alert('No hay datos de reserva. Redirigiendo al formulario...');
            window.history.back();
            return;
        }
        
        // Rellenar los campos
        $('#fecha-ida').text(data.fecha || '-');
        $('#hora-ida').text('10:00'); // Esto se puede obtener del servicio
        $('#fecha-vuelta').text(data.fecha || '-');
        $('#hora-vuelta').text('13:30'); // Esto se puede obtener del servicio
        
        $('#num-adultos').text(data.adultos || 0);
        $('#num-residentes').text(data.residentes || 0);
        $('#num-ninos-5-12').text(data.ninos_5_12 || 0);
        $('#num-ninos-menores').text(data.ninos_menores || 0);
        
        $('#importe-base').text('35€'); // Calcular
        $('#descuento-residentes').text('-10€'); // Calcular
        $('#total-reserva').text(data.total_price || '0€');
    }
    
    function goBackToBooking() {
        window.history.back();
    }
    
    function processReservation() {
        // Validar formularios
        const nombre = $('[name="nombre"]').val();
        const apellidos = $('[name="apellidos"]').val();
        const email = $('[name="email"]').val();
        const telefono = $('[name="telefono"]').val();
        
        if (!nombre || !apellidos || !email || !telefono) {
            alert('Por favor, completa todos los campos de datos personales.');
            return;
        }
        
        // Aquí procesarías la reserva
        alert('Reserva procesada correctamente! (Función en desarrollo)');
        
        // Limpiar sessionStorage
        sessionStorage.removeItem('reservationData');
        
        // Redirigir a página de confirmación o inicio
        window.location.href = '/';
    }
    </script>
    <?php
    return ob_get_clean();
}
}


