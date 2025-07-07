<?php
class ReservasFrontend
{

    public function __construct()
    {
        add_shortcode('reservas_formulario', array($this, 'render_booking_form'));
        add_shortcode('reservas_detalles', array($this, 'render_details_form'));

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

        // Cargar assets para formulario de reserva
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

        // Cargar assets para página de detalles
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'reservas_detalles')) {
            wp_add_inline_style('wp-block-library', $this->get_details_css());
        }
    }

    private function get_details_css()
    {
        return '
/* Estilos para la página de detalles de reserva */
.reservas-details-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #F5F5F5;
    min-height: 100vh;
}

.details-summary h2 {
    background: #8B4513;
    color: white;
    text-align: center;
    margin: 0 0 20px 0;
    padding: 20px;
    font-size: 24px;
    font-weight: bold;
    letter-spacing: 1px;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 0;
    background: white;
    border: 2px solid #ddd;
    margin-bottom: 20px;
}

.details-section {
    padding: 20px;
    border-right: 1px solid #ddd;
}

.details-section:last-child {
    border-right: none;
}

.details-section h3 {
    background: #E8E8E8;
    color: #666;
    text-align: center;
    margin: -20px -20px 20px -20px;
    padding: 12px;
    font-size: 16px;
    font-weight: bold;
    border-bottom: 1px solid #ddd;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding: 8px 0;
}

.detail-row .label {
    color: #8B4513;
    font-weight: bold;
    font-size: 14px;
    flex: 1;
}

.detail-row .value {
    color: #333;
    font-weight: bold;
    font-size: 14px;
    text-align: right;
    min-width: 80px;
}

.total-row {
    border-top: 2px solid #ddd;
    margin-top: 15px;
    padding-top: 15px;
}

.total-row .label {
    font-size: 16px;
    color: #000;
}

.total-row .value {
    font-size: 18px;
    color: #E74C3C;
    font-weight: bold;
}

.confirm-section {
    text-align: center;
    margin: 30px 0;
}

.confirm-btn {
    background: #F4D03F;
    border: none;
    padding: 15px 40px;
    border-radius: 25px;
    font-size: 16px;
    font-weight: bold;
    color: #333;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.confirm-btn:hover {
    background: #F1C40F;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.forms-section {
    margin-top: 30px;
}

.forms-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.form-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.form-card h3 {
    background: #E74C3C;
    color: white;
    text-align: center;
    margin: 0;
    padding: 15px;
    font-size: 16px;
    font-weight: bold;
}

.form-card form {
    padding: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group input::placeholder {
    color: #999;
    font-weight: normal;
}

.form-group input:focus {
    outline: none;
    border-color: #E74C3C;
    box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.1);
}

.final-buttons {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    margin-top: 30px;
}

.back-btn {
    background: #6C757D;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.3s;
    flex: 1;
}

.back-btn:hover {
    background: #5A6268;
}

.process-btn {
    background: #28A745;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s;
    flex: 1;
}

.process-btn:hover {
    background: #218838;
}

@media (max-width: 768px) {
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .details-section {
        border-right: none;
        border-bottom: 1px solid #ddd;
    }
    
    .details-section:last-child {
        border-bottom: none;
    }
    
    .forms-grid {
        grid-template-columns: 1fr;
    }
    
    .final-buttons {
        flex-direction: column;
    }
    
    .detail-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .detail-row .value {
        text-align: left;
    }
}
        ';
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
                                <input type="number" id="adultos" min="0" max="999" value="0" class="person-input">
                            </div>

                            <div class="person-selector">
                                <label>ADULTOS RESIDENTES</label>
                                <input type="number" id="residentes" min="0" max="999" value="0" class="person-input">
                            </div>

                            <div class="person-selector">
                                <label>NIÑOS (5/12 AÑOS)</label>
                                <input type="number" id="ninos-5-12" min="0" max="999" value="0" class="person-input">
                            </div>

                            <div class="person-selector">
                                <label>NIÑOS (-5 AÑOS)</label>
                                <input type="number" id="ninos-menores" min="0" max="999" value="0" class="person-input">
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

                            <!-- Mensaje de descuento por grupo -->
                            <div id="discount-message" class="discount-message">
                                <span id="discount-text">Descuento del 15% por grupo numeroso</span>
                            </div>

                            <div class="total-price">
                                <div class="discount-row" id="discount-row" style="display: none;">
                                    <span class="discount">DESCUENTOS: <span id="total-discount">-10€</span></span>
                                </div>
                                <div class="total-row">
                                    <span class="total">TOTAL: <span id="total-price">25€</span></span>
                                </div>
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

        // ACTUALIZADO: Incluir los nuevos campos de descuento en la consulta
        $servicios = $wpdb->get_results($wpdb->prepare(
            "SELECT id, fecha, hora, plazas_disponibles, precio_adulto, precio_nino, precio_residente, 
                    tiene_descuento, porcentaje_descuento
            FROM $table_name 
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
                'precio_residente' => $servicio->precio_residente,
                'tiene_descuento' => $servicio->tiene_descuento,
                'porcentaje_descuento' => $servicio->porcentaje_descuento
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

        // ACTUALIZADO: Incluir campos de descuento en la consulta
        $servicio = $wpdb->get_row($wpdb->prepare(
            "SELECT *, tiene_descuento, porcentaje_descuento FROM $table_name WHERE id = %d",
            $service_id
        ));

        if (!$servicio) {
            wp_send_json_error('Servicio no encontrado');
        }

        // Calcular precio base (sin descuentos)
        $precio_base = 0;
        $descuento_total = 0;

        // Adultos normales (precio completo)
        $precio_base += $adultos * $servicio->precio_adulto;

        // Adultos residentes (precio base como adulto normal)
        $precio_base += $residentes * $servicio->precio_adulto;
        $descuento_residentes = $residentes * ($servicio->precio_adulto - $servicio->precio_residente);
        $descuento_total += $descuento_residentes;

        // Niños 5-12 años (precio base como adulto)
        $precio_base += $ninos_5_12 * $servicio->precio_adulto;
        $descuento_ninos = $ninos_5_12 * ($servicio->precio_adulto - $servicio->precio_nino);
        $descuento_total += $descuento_ninos;

        // Calcular total de personas que ocupan plaza
        $total_personas_con_plaza = $adultos + $residentes + $ninos_5_12;

        // Calcular descuento por grupo usando las reglas configuradas
        $descuento_grupo = 0;
        $regla_aplicada = null;

        if ($total_personas_con_plaza > 0) {
            if (!class_exists('ReservasDiscountsAdmin')) {
                require_once RESERVAS_PLUGIN_PATH . 'includes/class-discounts-admin.php';
            }

            $subtotal = $precio_base - $descuento_total;

            $discount_info = ReservasDiscountsAdmin::calculate_discount(
                $total_personas_con_plaza, 
                $subtotal, 
                'total'
            );

            if ($discount_info['discount_applied']) {
                $descuento_grupo = $discount_info['discount_amount'];
                $descuento_total += $descuento_grupo;
                $regla_aplicada = array(
                    'rule_name' => $discount_info['rule_name'],
                    'discount_percentage' => $discount_info['discount_percentage'],
                    'minimum_persons' => $discount_info['minimum_persons']
                );
            }
        }

        // NUEVO: Aplicar descuento específico del servicio si existe
        $descuento_servicio = 0;
        if ($servicio->tiene_descuento && floatval($servicio->porcentaje_descuento) > 0) {
            $subtotal_actual = $precio_base - $descuento_total;
            $descuento_servicio = ($subtotal_actual * floatval($servicio->porcentaje_descuento)) / 100;
            $descuento_total += $descuento_servicio;
        }

        // Calcular total final
        $total = $precio_base - $descuento_total;

        // Asegurar que el total no sea negativo
        if ($total < 0) {
            $total = 0;
        }

        $response_data = array(
            'precio_base' => round($precio_base, 2),
            'descuento' => round($descuento_total, 2),
            'descuento_residentes' => round($descuento_residentes, 2),
            'descuento_ninos' => round($descuento_ninos, 2),
            'descuento_grupo' => round($descuento_grupo, 2),
            'descuento_servicio' => round($descuento_servicio, 2), // NUEVO
            'total' => round($total, 2),
            'precio_adulto' => $servicio->precio_adulto,
            'precio_nino' => $servicio->precio_nino,
            'precio_residente' => $servicio->precio_residente,
            'total_personas_con_plaza' => $total_personas_con_plaza,
            'regla_descuento_aplicada' => $regla_aplicada,
            'servicio_con_descuento' => array( // NUEVO
                'tiene_descuento' => $servicio->tiene_descuento,
                'porcentaje_descuento' => $servicio->porcentaje_descuento
            )
        );

        wp_send_json_success($response_data);
    }

    public function render_details_form()
    {
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
                        <!-- AÑADIR ESTA NUEVA FILA -->
                        <div class="detail-row" id="descuento-grupo-row" style="display: none;">
                            <span class="label">DESCUENTO GRUPO:</span>
                            <span class="value" id="descuento-grupo-detalle">-0€</span>
                        </div>
                        <div class="detail-row total-row">
                            <span class="label">TOTAL RESERVA:</span>
                            <span class="value total-price" id="total-reserva">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de datos personales directamente debajo -->
            <div class="personal-data-section">
                <div class="form-card-single">
                    <h3>DATOS PERSONALES</h3>
                    <form id="personal-data-form">
                        <div class="form-row">
                            <div class="form-group">
                                <input type="text" name="nombre" placeholder="NOMBRE" required>
                            </div>
                            <div class="form-group">
                                <input type="text" name="apellidos" placeholder="APELLIDOS" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <input type="email" name="email" placeholder="EMAIL" required>
                            </div>
                            <div class="form-group">
                                <input type="tel" name="telefono" placeholder="MÓVIL O TELÉFONO" required>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Botones finales -->
            <div class="final-buttons">
                <button type="button" class="back-btn" onclick="goBackToBooking()">
                    ← VOLVER A MODIFICAR RESERVA
                </button>
                <button type="button" class="process-btn" onclick="processReservation()">
                    PROCESAR RESERVA
                </button>
            </div>
        </div>

        <script>
            <?php echo $this->get_details_page_script(); ?>
        </script>

        <style>
            <?php echo $this->get_details_css(); ?>

            /* Estilos adicionales para la nueva estructura */
            .personal-data-section {
                margin: 20px 0;
            }

            .form-card-single {
                background: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                max-width: 600px;
                margin: 0 auto;
            }

            .form-card-single h3 {
                background: #E74C3C;
                color: white;
                text-align: center;
                margin: 0;
                padding: 15px;
                font-size: 16px;
                font-weight: bold;
            }

            .form-card-single form {
                padding: 20px;
            }

            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin-bottom: 15px;
            }

            .person-input {
    width: 100%;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    background: white;
    text-align: center;
    font-weight: bold;
}

.person-input:focus {
    outline: none;
    border-color: #F4D03F;
    box-shadow: 0 0 0 3px rgba(244, 208, 63, 0.2);
}

.person-input::-webkit-outer-spin-button,
.person-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.person-input[type=number] {
    -moz-appearance: textfield;
}

            .form-group {
                margin-bottom: 0;
            }

            .form-group input {
                width: 100%;
                padding: 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
                box-sizing: border-box;
            }

            .form-group input::placeholder {
                color: #999;
                font-weight: normal;
            }

            .form-group input:focus {
                outline: none;
                border-color: #E74C3C;
                box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.1);
            }

            /* Responsive para formularios */
            @media (max-width: 768px) {
                .form-row {
                    grid-template-columns: 1fr;
                }
            }
        </style>
<?php
        return ob_get_clean();
    }

    // Nuevo método para obtener el script de la página de detalles
    private function get_details_page_script()
    {
        return '
jQuery(document).ready(function($) {
    console.log("=== PÁGINA DE DETALLES CARGADA ===");
    
    // Cargar datos de la reserva desde sessionStorage
    loadReservationData();
});

function loadReservationData() {
    console.log("=== INICIANDO CARGA DE DATOS ===");
    
    try {
        // Verificar si sessionStorage está disponible
        if (typeof(Storage) === "undefined") {
            console.error("SessionStorage no está disponible en este navegador");
            alert("Tu navegador no soporta sessionStorage. Por favor, usa un navegador más moderno.");
            return;
        }
        
        // Intentar obtener los datos
        const dataString = sessionStorage.getItem("reservationData");
        console.log("Datos en sessionStorage (string):", dataString);
        
        if (!dataString || dataString === "null" || dataString === "undefined") {
            console.error("No hay datos en sessionStorage");
            alert("No hay datos de reserva. Redirigiendo al formulario...");
            // Intentar volver a la página anterior
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Redireccionar a la página principal o formulario
                window.location.href = "/";
            }
            return;
        }
        
        // Intentar parsear los datos JSON
        let data;
        try {
            data = JSON.parse(dataString);
            console.log("Datos parseados exitosamente:", data);
        } catch (parseError) {
            console.error("Error parseando JSON:", parseError);
            console.error("String que falló:", dataString);
            alert("Error en los datos de reserva. Por favor, vuelve a hacer la reserva.");
            window.history.back();
            return;
        }
        
        // Verificar que los datos tienen la estructura esperada
        if (!data || typeof data !== "object") {
            console.error("Datos no válidos:", data);
            alert("Datos de reserva no válidos. Por favor, vuelve a hacer la reserva.");
            window.history.back();
            return;
        }
        
        // Verificar campos críticos
        const requiredFields = ["fecha", "service_id", "hora_ida"];
        const missingFields = requiredFields.filter(field => !data[field]);
        
        if (missingFields.length > 0) {
            console.error("Campos requeridos faltantes:", missingFields);
            alert(`Faltan datos críticos: ${missingFields.join(", ")}. Por favor, vuelve a hacer la reserva.`);
            window.history.back();
            return;
        }
        
        console.log("Validación exitosa. Rellenando formulario...");
        
        // Rellenar los datos en la página
        fillReservationDetails(data);
        
    } catch (error) {
        console.error("Error general en loadReservationData:", error);
        alert("Error cargando los datos de la reserva: " + error.message);
    }
}

function fillReservationDetails(data) {
    console.log("=== RELLENANDO DETALLES ===");
    
    try {
        // Formatear fecha para mostrar
        let fechaFormateada = "-";
        if (data.fecha) {
            try {
                // Crear fecha y formatear para español
                const fechaObj = new Date(data.fecha + "T00:00:00");
                fechaFormateada = fechaObj.toLocaleDateString("es-ES", {
                    weekday: "long",
                    year: "numeric",
                    month: "long",
                    day: "numeric"
                });
            } catch (dateError) {
                console.error("Error formateando fecha:", dateError);
                fechaFormateada = data.fecha; // Usar fecha original si falla el formateo
            }
        }
        
        console.log("Fecha formateada:", fechaFormateada);
        
        // Rellenar fechas y horas
        updateElementText("#fecha-ida", fechaFormateada);
        updateElementText("#hora-ida", data.hora_ida || "-");
        updateElementText("#fecha-vuelta", fechaFormateada);
        updateElementText("#hora-vuelta", "13:30"); // Hora fija de vuelta
        
        // Rellenar personas
        updateElementText("#num-adultos", data.adultos || 0);
        updateElementText("#num-residentes", data.residentes || 0);
        updateElementText("#num-ninos-5-12", data.ninos_5_12 || 0);
        updateElementText("#num-ninos-menores", data.ninos_menores || 0);
        
        // Calcular precios
        const precioAdulto = parseFloat(data.precio_adulto) || 0;
        const precioNino = parseFloat(data.precio_nino) || 0;
        const precioResidente = parseFloat(data.precio_residente) || 0;
        
        const adultos = parseInt(data.adultos) || 0;
        const residentes = parseInt(data.residentes) || 0;
        const ninos_5_12 = parseInt(data.ninos_5_12) || 0; // CORRECCIÓN: Variable definida correctamente
        const ninos_menores = parseInt(data.ninos_menores) || 0;
        
        // CORRECCIÓN: Calcular importes base correctamente
        const importeAdultos = adultos * precioAdulto;
        const importeResidentes = residentes * precioAdulto; // Base como adulto normal
        const importeNinos = ninos_5_12 * precioAdulto; // Base como adulto normal - CORREGIDO
        const importeBase = importeAdultos + importeResidentes + importeNinos;
        
        // CORRECCIÓN: Calcular descuentos correctamente
        // Descuento residentes: diferencia entre precio adulto y precio residente
        const descuentoResidentes = residentes * (precioAdulto - precioResidente);
        
        // CORRECCIÓN: Descuento niños 5-12: diferencia entre precio adulto y precio niño
        const descuentoNinos = ninos_5_12 * (precioAdulto - precioNino);
        
        console.log("Cálculos detallados:", {
            adultos: adultos,
            residentes: residentes, 
            ninos_5_12: ninos_5_12,
            ninos_menores: ninos_menores,
            precioAdulto: precioAdulto,
            precioNino: precioNino,
            precioResidente: precioResidente,
            importeBase: importeBase,
            descuentoResidentes: descuentoResidentes,
            descuentoNinos: descuentoNinos,
            totalPrice: data.total_price
        });
        
        // Rellenar precios
        updateElementText("#importe-base", formatPrice(importeBase));
updateElementText("#descuento-residentes", formatPrice(-descuentoResidentes));
updateElementText("#descuento-menores", formatPrice(-descuentoNinos));
        updateElementText("#total-reserva", formatPrice(data.total_price || "0"));

        if (data.descuento_grupo && parseFloat(data.descuento_grupo) > 0) {
    updateElementText("#descuento-grupo-detalle", formatPrice(-parseFloat(data.descuento_grupo)));
    jQuery("#descuento-grupo-row").show();
    
    // También mostrar información de la regla aplicada si está disponible
    if (data.regla_descuento_aplicada) {
        console.log("Regla de descuento aplicada:", data.regla_descuento_aplicada);
    }
} else {
    jQuery("#descuento-grupo-row").hide();
}

updateElementText("#total-reserva", formatPrice(data.total_price || "0"));
        
        console.log("Detalles rellenados exitosamente");
        
    } catch (error) {
        console.error("Error rellenando detalles:", error);
        alert("Error mostrando los detalles de la reserva: " + error.message);
    }
}

// Función auxiliar para actualizar texto de elementos con manejo de errores
function updateElementText(selector, value) {
    try {
        const element = jQuery(selector);
        if (element.length > 0) {
            element.text(value);
            console.log(`Actualizado ${selector} con: ${value}`);
        } else {
            console.warn(`Elemento no encontrado: ${selector}`);
        }
    } catch (error) {
        console.error(`Error actualizando ${selector}:`, error);
    }
}

// Función auxiliar para formatear precios
function formatPrice(price) {
    try {
        const numPrice = parseFloat(price) || 0;
        return numPrice.toFixed(2) + "€";
    } catch (error) {
        console.error("Error formateando precio:", error);
        return "0.00€";
    }
}

function goBackToBooking() {
    console.log("Volviendo a la página anterior");
    
    // Limpiar sessionStorage
    try {
        sessionStorage.removeItem("reservationData");
        console.log("SessionStorage limpiado");
    } catch (error) {
        console.error("Error limpiando sessionStorage:", error);
    }
    
    // Volver a la página anterior
    if (window.history.length > 1) {
        window.history.back();
    } else {
        // Si no hay historial, ir a la página principal
        window.location.href = "/";
    }
}

function processReservation() {
    console.log("=== PROCESANDO RESERVA REAL ===");
    
    // Validar formularios
    const nombre = jQuery("[name=\"nombre\"]").val().trim();
    const apellidos = jQuery("[name=\"apellidos\"]").val().trim();
    const email = jQuery("[name=\"email\"]").val().trim();
    const telefono = jQuery("[name=\"telefono\"]").val().trim();
    
    console.log("Datos del formulario:", { nombre, apellidos, email, telefono });
    
    if (!nombre || !apellidos || !email || !telefono) {
        alert("Por favor, completa todos los campos de datos personales.");
        return;
    }

    let reservationData;
    try {
        const dataString = sessionStorage.getItem("reservationData");
        if (!dataString) {
            alert("Error: No hay datos de reserva. Por favor, vuelve a hacer la reserva.");
            window.history.back();
            return;
        }
        
        reservationData = JSON.parse(dataString);
        console.log("Datos de reserva recuperados:", reservationData);
    } catch (error) {
        console.error("Error parseando datos de reserva:", error);
        alert("Error en los datos de reserva. Por favor, vuelve a hacer la reserva.");
        window.history.back();
        return;
    }

    const processBtn = jQuery(".process-btn");
    const originalText = processBtn.text();
    processBtn.prop("disabled", true).text("Procesando reserva...");
    
    // Preparar datos para enviar
    const formData = new FormData();
    formData.append("action", "process_reservation");
    formData.append("nonce", reservasAjax.nonce);
    
    // Datos personales
    formData.append("nombre", nombre);
    formData.append("apellidos", apellidos);
    formData.append("email", email);
    formData.append("telefono", telefono);
    
    // Datos de reserva
    formData.append("reservation_data", JSON.stringify(reservationData));
    
    // Validar email básico
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert("Por favor, introduce un email válido.");
        return;
    }
    
    // Aquí procesarías la reserva real
    alert("Reserva procesada correctamente!\\n\\n(Función en desarrollo - aquí se integraría con el sistema de pago)");
    
    // Limpiar sessionStorage
    try {
        sessionStorage.removeItem("reservationData");
        console.log("SessionStorage limpiado después de procesar");
    } catch (error) {
        console.error("Error limpiando sessionStorage:", error);
    }
    
    // Redirigir a página de confirmación o inicio
    // window.location.href = "/confirmacion-reserva/";
    window.location.href = "/";
}
    ';
    }
}
