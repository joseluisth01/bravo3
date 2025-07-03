<?php

/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined('ABSPATH') || exit;

get_header('shop');
?>
<?php

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action('woocommerce_before_main_content');

?>

<div class="woocommerce-products-header">
	<?php if (apply_filters('woocommerce_show_page_title', true)): ?>
		<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
	<?php endif; ?>

	<?php
	/**
	 * Hook: woocommerce_archive_description.
	 *
	 * @hooked woocommerce_taxonomy_archive_description - 10
	 * @hooked woocommerce_product_archive_description - 10
	 */
	do_action('woocommerce_archive_description');
	?>
</div>
<div class="container">
	<div class="row responsivetiendadiv" style="justify-content: space-between;">
		<div class="col-12 col-md-3 lista_categorias">
			<?php
			echo do_shortcode('[yith_wcan_filters slug="default-preset"]');
			if (is_active_sidebar('sidebar-categorias')) {
				dynamic_sidebar('sidebar-categorias');
			}
			?>
			<?php
			// Insertar botón de restablecer filtros justo después del div .lista_categorias
			$current_url = $_SERVER['REQUEST_URI'];
			$show_reset = (strpos($current_url, 'yith_wcan=1') !== false); // Comprobar si hay filtros aplicados
			?>

			<div class="reset-filters-button <?php echo $show_reset ? 'visible' : ''; ?>">
				<a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="reset-btn">
					Restablecer filtros
				</a>
			</div>
		</div>
		<div class="productostienda col-12 col-md-9" style="width:80%">
			<?php
			if (woocommerce_product_loop()) {

				/**
				 * Hook: woocommerce_before_shop_loop.
				 *
				 * @hooked woocommerce_output_all_notices - 10
				 * @hooked woocommerce_result_count - 20
				 * @hooked woocommerce_catalog_ordering - 30
				 */


				woocommerce_product_loop_start();

				if (wc_get_loop_prop('total')) {
					while (have_posts()) {
						the_post();

						/**
						 * Hook: woocommerce_shop_loop.
						 */
						do_action('woocommerce_shop_loop');

						wc_get_template_part('content', 'product');
					}
				}

				woocommerce_product_loop_end();

				woocommerce_product_loop_start();

				/**
				 * Hook: woocommerce_after_shop_loop.
				 *
				 * @hooked woocommerce_pagination - 10
				 */
				do_action('woocommerce_after_shop_loop');
			} else {
				/**
				 * Hook: woocommerce_no_products_found.
				 *
				 * @hooked wc_no_products_found - 10
				 */
				do_action('woocommerce_no_products_found');
			}
			?>
		</div>
	</div>
</div>
<div class="taller-bisuteria-container" style="position: relative; max-width: 100%; height: auto; overflow: hidden;">
	<!-- Imagen de fondo (sin overlay) -->
	<img src="/indomitaselection/wp-content/uploads/2025/03/Imagen-6.png" alt="Taller de Bisutería"
		style="width: 100%; height: auto; display: block;">

	<!-- Contenido de texto centrado -->
	<div
		style="position: absolute; top: 50%; left: 0; right: 0; transform: translateY(-50%); text-align: center; color: white;">
		<h2
			style="text-shadow: 2px 2px 4px #000000; font-size: 60px !important; margin-bottom: 20px !important; color:white !important;">
			Taller de Bisutería Local</h2>
		<p
			style="text-shadow: 2px 2px 4px #000000; !important; font-size: 20px !important; margin-bottom: 30px !important; font-family: 'MontserratAlternates-Medium' !important; color:white !important; text-align: center !important;">
			Servicio Exclusivo en Córdoba: Taller de Alta Bisutería (Solo en Tienda)</p>

		<!-- Botón "más información" -->
		<!-- Botón "más información" -->
<a href="<?php echo esc_url(get_permalink(get_page_by_path('contacto'))); ?>" style="">MÁS INFORMACIÓN</a>
	</div>
</div>
<?php
/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action('woocommerce_after_main_content');

/**
 * Hook: woocommerce_sidebar.
 *
 * @hooked woocommerce_get_sidebar - 10
 */


get_footer('shop');
