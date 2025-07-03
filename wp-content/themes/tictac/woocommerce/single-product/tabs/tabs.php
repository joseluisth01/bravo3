<?php
/**
 * Single Product tabs as accordion
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/tabs/tabs.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woo.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter tabs and allow third parties to add their own.
 *
 * Each tab is an array containing title, callback and priority.
 *
 * @see woocommerce_default_product_tabs()
 */
$product_tabs = apply_filters( 'woocommerce_product_tabs', array() );

function galeria_producto()
{
	global $product;
	$galeria = get_field("galeria_producto",$product->get_id());
	if ($galeria) {
?>
		<section class="splide galeria_slider_child container" aria-label="Galería">
			<div class="splide__arrows custom_arrows">
				<button class="splide__arrow splide__arrow--prev">
					<img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/arrow.gif" alt="">
				</button>
				<button class="splide__arrow splide__arrow--next">
					<img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/arrow.gif" alt="">
				</button>
			</div>
			<div class="splide__track">
				<ul class="splide__list">
					<?php foreach ($galeria as $image) { ?>
						<li class="splide__slide">
							<img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" />
						</li>
					<?php } ?>
				</ul>
			</div>
		</section>
<?php
	}
}
//galeria_producto();
if ( ! empty( $product_tabs ) ) : ?>

	<div class="woocommerce-tabs wc-accordion-wrapper">
		<?php 
        // Iniciar todos los tabs cerrados
        foreach ( $product_tabs as $key => $product_tab ) : 
        ?>
			<div class="accordion-item">
                <div class="accordion-header" id="accordion-header-<?php echo esc_attr( $key ); ?>">
                    <h3>
                        <button class="accordion-button collapsed" type="button" data-bs-target="#accordion-content-<?php echo esc_attr( $key ); ?>" aria-expanded="false" aria-controls="accordion-content-<?php echo esc_attr( $key ); ?>">
                            <?php echo wp_kses_post( apply_filters( 'woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key ) ); ?>
                            <span class="accordion-icon"></span>
                        </button>
                    </h3>
                </div>
                <div id="accordion-content-<?php echo esc_attr( $key ); ?>" class="accordion-collapse collapse" aria-labelledby="accordion-header-<?php echo esc_attr( $key ); ?>">
                    <div class="accordion-body">
                        <?php
                        if ( isset( $product_tab['callback'] ) ) {
                            call_user_func( $product_tab['callback'], $key, $product_tab );
                        }
                        ?>
                    </div>
                </div>
            </div>
		<?php 
        endforeach; 
        ?>

		<?php do_action( 'woocommerce_product_after_tabs' ); ?>
	</div>

    <style>
        /* Estilos para el acordeón */
        .wc-accordion-wrapper {
            padding: 0px !important;
        }
        
        .accordion-item {
            border-bottom: 1px solid #e5e5e5;
        }
        
        .accordion-header h3{
            margin: 0px !important;
        }
        
		.woocommerce-product-attributes-item__value{
			display: flex;
			align-items: center;
			justify-content: center;
		}
		
		.woocommerce-Reviews-title{
			display: none;
		}
		
		.woocommerce-product-attributes-item__label{
			font-family: 'MontserratAlternates-Medium' !important;
			color: black !important;
			font-size:20px !important;
		}
		
		#respond{
			padding: 0px !important;
		}
        
        .accordion-header h3 button{
            font-family: 'MontserratAlternates-Medium';
			font-weight: 500;
			font-size: 20px;
			line-height: 100%;
			letter-spacing: 5%;
			color: black !important;
			font-style: normal !important;
        }
        
        .accordion-button {
            background: none;
            width: 100%;
            text-align: left;
            padding: 1em 0;
            font-weight: 500;
            font-size: 1rem;
            color: #333;
            position: relative;
            border: none;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .accordion-button:focus {
            outline: none;
        }
        
        /* Nuevo estilo para el icono + y - */
        .accordion-icon {
            width: 16px;
            height: 16px;
            position: relative;
            display: inline-block;
        }
        
        /* Línea horizontal (siempre presente) */
        .accordion-icon:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #333;
            transform: translateY(-50%);
        }
        
        /* Línea vertical (solo visible cuando está colapsado - +) */
        .accordion-icon:after {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            width: 2px;
            height: 100%;
            background-color: #333;
            transform: translateX(-50%);
            transition: opacity 0.3s ease;
        }
        
        /* Cuando está expandido, la línea vertical desaparece (símbolo -) */
        .accordion-button:not(.collapsed) .accordion-icon:after {
            opacity: 0;
        }
        
        .accordion-body {
            padding: 0 0 1.5em 0;
        }
        
        /* Animación suave para el acordeón */
        .accordion-collapse {
            transition: height 0.35s ease;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Función para manejar el comportamiento del acordeón
        $('.accordion-button').on('click', function(e) {
            e.preventDefault();
            
            // Obtener el contenido asociado a este botón
            var target = $(this).data('bs-target');
            var $target = $(target);
            
            // Verificar si está expandido
            var isExpanded = $(this).attr('aria-expanded') === 'true';
            
            // Si ya está expandido, lo cerraremos
            if (isExpanded) {
                $(this).addClass('collapsed').attr('aria-expanded', 'false');
                $target.removeClass('show');
            } else {
                // Si está cerrado, cerramos todos y abrimos este
                $('.accordion-button').addClass('collapsed').attr('aria-expanded', 'false');
                $('.accordion-collapse').removeClass('show');
                
                // Luego abrimos el seleccionado
                $(this).removeClass('collapsed').attr('aria-expanded', 'true');
                $target.addClass('show');
            }
            
            return false;
        });
    });
    </script>

<?php endif; ?>