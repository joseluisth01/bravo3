<?php
/**
 * Single Product title
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/title.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://woo.com/document/template-structure/
 * @package    WooCommerce\Templates
 * @version    1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if(get_field("subtitulo_producto")){ ?><div class="subtitle_product"><?php echo get_field("subtitulo_producto"); ?></div><?php }
the_title( '<h1 class="product_title entry-title">', '</h1>' );
echo '<div class="bg-3"></div>';
echo '<div class="bg-2"></div>';

