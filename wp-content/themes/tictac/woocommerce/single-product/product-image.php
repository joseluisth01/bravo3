<?php

/**
 * Custom Product Image Template
 *
 * Este archivo sobrescribe la plantilla predeterminada de imágenes del producto de WooCommerce.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $product;

// Obtén el ID de la imagen principal y las imágenes de la galería.
$main_image_id = $product->get_image_id();
$gallery_image_ids = $product->get_gallery_image_ids();
?>

<div class="custom-product-gallery">
    <!-- Contenedor de miniaturas de la galería -->
    <div class="thumbnail-gallery">
        <?php if ($main_image_id): ?>
            <div class="thumbnail-image" onclick="changeMainImage('<?php echo wp_get_attachment_url($main_image_id); ?>')">
                <img src="<?php echo wp_get_attachment_url($main_image_id); ?>" alt="Imagen principal">
            </div>
        <?php endif; ?>

        <?php if ($gallery_image_ids): ?>
            <?php foreach ($gallery_image_ids as $image_id): ?>
                <div class="thumbnail-image" onclick="changeMainImage('<?php echo wp_get_attachment_url($image_id); ?>')">
                    <img src="<?php echo wp_get_attachment_url($image_id); ?>" alt="Miniatura de la galería">
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Imagen principal del producto -->
    <div class="main-image-container" onclick="openFullscreen()">
        <img id="mainProductImage" src="<?php echo wp_get_attachment_url($main_image_id); ?>" alt="Imagen principal del producto" class="main-product-image">
    </div>


    <script>
        function changeMainImage(newImageUrl) {
            document.getElementById("mainProductImage").src = newImageUrl;
        }

        function openFullscreen() {
            const mainImage = document.getElementById("mainProductImage");
            if (mainImage.requestFullscreen) {
                mainImage.requestFullscreen();
            } else if (mainImage.webkitRequestFullscreen) { // Safari
                mainImage.webkitRequestFullscreen();
            } else if (mainImage.msRequestFullscreen) { // IE11
                mainImage.msRequestFullscreen();
            }
        }
    </script>

</div>