<?php
/**
 * Result Count
 *
 * @package WooCommerce\Templates
 * @version 3.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="woocommerce-result-pagination">
    <p class="woocommerce-result-count">
        <?php
        global $wp_query;
        
        $total = $wp_query->found_posts;
        $showing = min($wp_query->post_count, get_query_var('posts_per_page'));
        
        echo 'Mostrando ' . $showing . ' de ' . $total . ' artículos';
        ?>
    </p>
    
    <div class="woocommerce-custom-pagination">
        <?php
        // Obtener la paginación personalizada
        $total_pages = $wp_query->max_num_pages;
        $current_page = max(1, get_query_var('paged'));
        
        if ($total_pages > 1) {
            echo '<div class="pagination-numbers">';
            
            // Mostrar número de página actual y total
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == $current_page) {
                    echo '<span class="page-number current">' . $i . '</span>';
                } else {
                    echo '<a href="' . esc_url(get_pagenum_link($i)) . '" class="page-number">' . $i . '</a>';
                }
            }
            
            // Botón "Siguiente" solo si no estamos en la última página
            if ($current_page < $total_pages) {
                echo '<a href="' . esc_url(get_pagenum_link($current_page + 1)) . '" class="next-page">Siguiente <span>&rsaquo;</span></a>';
            }
            
            echo '</div>';
        }
        ?>
    </div>
</div>

<style>
    .woocommerce-result-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-top: 1px solid #e2e2e2;
        margin-bottom: 20px;
        width: 100%;
    }
    
    .woocommerce-result-count {
        margin: 0;
        font-size: 14px;
    }
    
    .woocommerce-custom-pagination {
        display: flex;
        align-items: center;
    }
    
    .pagination-numbers {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .page-number {
        display: inline-block;
        width: 30px;
        height: 30px;
        line-height: 30px;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 50%;
        text-decoration: none;
        color: #333;
    }
    
    .page-number.current {
        background-color: #f8f8f8;
        font-weight: bold;
    }
    
    .next-page {
        margin-left: 10px;
        text-decoration: none;
        color: #333;
        font-weight: normal;
    }
    
    /* Ocultar la paginación original de WooCommerce */
    .woocommerce-pagination {
        display: none !important;
    }
</style>