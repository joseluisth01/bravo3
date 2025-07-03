<?php
/**
 * Storefront engine room
 *
 * @package storefront
 */

/**
 * Assign the Storefront version to a var
 */
$theme              = wp_get_theme( 'storefront' );
$storefront_version = $theme['Version'];

/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 980; /* pixels */
}

$storefront = (object) array(
	'version'    => $storefront_version,

	/**
	 * Initialize all the things.
	 */
	'main'       => require 'inc/class-storefront.php',
	'customizer' => require 'inc/customizer/class-storefront-customizer.php',
);

require 'inc/storefront-functions.php';
require 'inc/storefront-template-hooks.php';
require 'inc/storefront-template-functions.php';
require 'inc/wordpress-shims.php';

if ( class_exists( 'Jetpack' ) ) {
	$storefront->jetpack = require 'inc/jetpack/class-storefront-jetpack.php';
}

if ( storefront_is_woocommerce_activated() ) {
	$storefront->woocommerce            = require 'inc/woocommerce/class-storefront-woocommerce.php';
	$storefront->woocommerce_customizer = require 'inc/woocommerce/class-storefront-woocommerce-customizer.php';

	require 'inc/woocommerce/class-storefront-woocommerce-adjacent-products.php';

	require 'inc/woocommerce/storefront-woocommerce-template-hooks.php';
	require 'inc/woocommerce/storefront-woocommerce-template-functions.php';
	require 'inc/woocommerce/storefront-woocommerce-functions.php';
}

if ( is_admin() ) {
	$storefront->admin = require 'inc/admin/class-storefront-admin.php';

	require 'inc/admin/class-storefront-plugin-install.php';
}

/**
 * NUX
 * Only load if wp version is 4.7.3 or above because of this issue;
 * https://core.trac.wordpress.org/ticket/39610?cversion=1&cnum_hist=2
 */
if ( version_compare( get_bloginfo( 'version' ), '4.7.3', '>=' ) && ( is_admin() || is_customize_preview() ) ) {
	require 'inc/nux/class-storefront-nux-admin.php';
	require 'inc/nux/class-storefront-nux-guided-tour.php';
	require 'inc/nux/class-storefront-nux-starter-content.php';
}

/**
 * Note: Do not add any custom code here. Please use a custom plugin so that your customizations aren't lost during updates.
 * https://github.com/woocommerce/theme-customisations
 */


/**
 * Modifica el formato de fecha de expiración de tarjeta a mm/aa y verifica el envío
 */
function modificar_formato_fecha_expiracion() {
    // Solo ejecutar en la página de pago
    if (!is_checkout()) {
        return;
    }
    
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Esperar a que los elementos de pago se carguen completamente
        var checkExist = setInterval(function() {
            if ($('#paycomet_card_month').length && $('#paycomet_card_year').length) {
                clearInterval(checkExist);
                
                // Modificar opciones del select de mes
                $('#paycomet_card_month option').each(function() {
                    var mesValor = $(this).val();
                    
                    // Saltar la opción de placeholder
                    if (mesValor === 'Mes' || mesValor === '') {
                        return;
                    }
                    
                    // Cambiar texto de "01 - Enero" a solo "01"
                    $(this).text(mesValor);
                });
                
                // Modificar opciones del select de año
                $('#paycomet_card_year option').each(function() {
                    var añoValor = $(this).val();
                    
                    // Saltar la opción de placeholder
                    if (añoValor === 'Año' || añoValor === '') {
                        return;
                    }
                    
                    // Cambiar año completo (ej. "2026") a formato corto (ej. "26")
                    var añoCorto = añoValor.substr(2, 2);
                    $(this).text(añoCorto);
                });
                
                // Añadir un span entre los selects para mostrar el formato mm/aa
                if ($('.paycomet-date-separator').length === 0) {
                    $('#paycomet_card_month').after('<span class="paycomet-date-separator" style="margin: 0 5px;">/</span>');
                }
                
                // Monitorear la presentación del formulario para depurar
                $('form#checkout').on('submit', function() {
                    console.log('Mes seleccionado: ' + $('#paycomet_card_month').val());
                    console.log('Año seleccionado: ' + $('#paycomet_card_year').val());
                });
                
                // Monitorear el envío de datos de PAYCOMET específicamente
                if (typeof PAYCOMET !== 'undefined') {
                    var originalSubmit = PAYCOMET.submitForm;
                    PAYCOMET.submitForm = function() {
                        console.log('PAYCOMET enviando datos de fecha:');
                        console.log('Mes: ' + $('[data-paycomet="dateMonth"]').val());
                        console.log('Año: ' + $('[data-paycomet="dateYear"]').val());
                        return originalSubmit.apply(this, arguments);
                    };
                }
                
                // También capturar el momento en que se presiona el botón de pago
                $('#place_order').on('click', function() {
                    console.log('Datos en el momento de pago:');
                    console.log('Mes: ' + $('#paycomet_card_month').val());
                    console.log('Año: ' + $('#paycomet_card_year').val());
                });
                
                // Para el caso específico de PAYCOMET con JetIframe
                $('#jetiframe-button').on('click', function() {
                    console.log('Datos al validar JetIframe:');
                    console.log('Mes: ' + $('#paycomet_card_month').val());
                    console.log('Año: ' + $('#paycomet_card_year').val());
                });
            }
        }, 100); // Verificar cada 100ms
    });
    </script>
    <?php
}
add_action('wp_footer', 'modificar_formato_fecha_expiracion');