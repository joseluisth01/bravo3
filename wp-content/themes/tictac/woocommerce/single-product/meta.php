<?php
defined( 'ABSPATH' ) || exit;

global $product;

// Obtener las categorías del producto como objetos
$categories = get_the_terms( $product->get_id(), 'product_cat' );

if ( $categories && ! is_wp_error( $categories ) ) {
    echo '<div class="custom-product-categories">';

    // Recorrer cada categoría y mostrar como enlace o en un div separado
    foreach ( $categories as $category ) {
        // Crear el enlace de la categoría
        $category_link = get_term_link( $category->term_id, 'product_cat' );

        // Mostrar cada categoría en su propio div con enlace
        echo '<div class="custom-category-item">';
        echo '<a href="' . esc_url( $category_link ) . '">' . esc_html( $category->name ) . '</a>';
        echo '</div>';
    }

    echo '</div>';
}
