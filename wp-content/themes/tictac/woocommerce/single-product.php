<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header('shop');

// Start the wrapper and content
do_action('woocommerce_before_main_content');
?>

<div class="container single-product-custom">
    <?php while (have_posts()) : the_post(); 
        global $product;
        $product_id = get_the_ID();
    ?>
        <div class="row productosimple mt-5">
            <!-- Columna izquierda: Galería de imágenes -->
            <div class="col-lg-6 col-md-6 col-sm-12 product-images">
                <?php
                // Usar la función estándar de WooCommerce para mostrar imágenes
                woocommerce_show_product_images();
                ?>
            </div>

            <!-- Columna derecha: Información del producto -->
            <div class="col-lg-6 col-md-6 col-sm-12 product-summary">
                <?php
                // Inicio de la miga de pan
                // Obtener las categorías del producto
                $categories = get_the_terms($product_id, 'product_cat');

                // Iniciar el HTML de la miga de pan
                $breadcrumb = '<div class="custom-breadcrumb">';

                // Enlace a la tienda
                $breadcrumb .= '<a href="' . get_permalink(wc_get_page_id('shop')) . '">Tienda</a>';

                // Si hay categorías, mostrar la jerarquía
                if ($categories && !is_wp_error($categories)) {
                    // Organizar categorías por jerarquía
                    $main_category = null;
                    $child_category = null;
                    
                    foreach ($categories as $category) {
                        // Si es una categoría padre
                        if ($category->parent == 0) {
                            $main_category = $category;
                            break;
                        } else {
                            // Si es una subcategoría, guardarla
                            $child_category = $category;
                        }
                    }
                    
                    // Si no encontramos una categoría padre pero tenemos una subcategoría
                    if (!$main_category && $child_category) {
                        // Obtener la categoría padre de la subcategoría
                        $parent_id = $child_category->parent;
                        $parent_category = get_term($parent_id, 'product_cat');
                        
                        if (!is_wp_error($parent_category)) {
                            // Mostrar la categoría padre
                            $breadcrumb .= ' | <a href="' . get_term_link($parent_category) . '">' . esc_html($parent_category->name) . '</a>';
                            
                            // Mostrar la subcategoría
                            $breadcrumb .= ' | <a href="' . get_term_link($child_category) . '">' . esc_html($child_category->name) . '</a>';
                        } else {
                            // Si hay error, mostrar solo la subcategoría
                            $breadcrumb .= ' | <a href="' . get_term_link($child_category) . '">' . esc_html($child_category->name) . '</a>';
                        }
                    } elseif ($main_category) {
                        // Mostrar la categoría principal
                        $breadcrumb .= ' | <a href="' . get_term_link($main_category) . '">' . esc_html($main_category->name) . '</a>';
                        
                        // Si también tenemos una subcategoría, mostrarla
                        if ($child_category && $child_category->parent == $main_category->term_id) {
                            $breadcrumb .= ' | <a href="' . get_term_link($child_category) . '">' . esc_html($child_category->name) . '</a>';
                        }
                    }
                }

                // Añadir el nombre del producto actual (sin enlace)
                $breadcrumb .= ' | <span>' . get_the_title() . '</span>';

                // Cerrar el div de la miga de pan
                $breadcrumb .= '</div>';

                // Mostrar la miga de pan
                echo $breadcrumb;
                ?>
                
                <div class="d-flex justify-content-between divprecios" style="align-items: flex-end; margin-bottom:30px">
                    <?php
                    the_title('<h2 style="margin:0px !important">', '</h2>');
                    woocommerce_template_single_price();
                    ?>
                </div>
                <?php
                // Mostrar descripción larga (contenido)
                the_content();
                ?>
                
                <!-- Formulario oculto para procesar la adición al carrito -->
                <form class="cart hidden-form" style="display:none;" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product_id); ?>">
                    <input type="hidden" name="quantity" value="1" id="hidden-quantity">
                    <?php if ($product->is_type('variable')) : ?>
                        <input type="hidden" name="variation_id" class="variation_id" value="">
                    <?php endif; ?>
                </form>
                
                <!-- Sección de atributos personalizados -->
                <div class="custom-product-attributes">
                    <!-- Primera fila con Cantidad y Talla -->
                    <div class="attributes-row">
                        <!-- Selector de cantidad -->
                        <div class="attribute-row">
                            <div class="attribute-label">Cantidad</div>
                            <div class="attribute-value quantity-selector">
                                <label class="radio-container">
                                    <input type="radio" name="quantity" value="1" checked>
                                    <span class="quantity-minus">-</span>
                                    <span class="quantity-value">1</span>
                                    <span class="quantity-plus">+</span>
                                </label>
                            </div>
                        </div>
                        
                        <?php
                        // Obtener las tallas disponibles
                        $attributes = $product->get_attributes();
                        $has_required_attributes = false;
                        
                        // Verificar si hay atributos que requieren selección
                        if ($product->is_type('variable')) {
                            $has_required_attributes = true;
                            
                            // Selector de talla como SELECT
                            if (isset($attributes['pa_tallas'])) {
                                $tallas = wc_get_product_terms($product_id, 'pa_tallas', array('fields' => 'all'));
                                if (!empty($tallas)) {
                                ?>
                                    <div class="attribute-row">
                                        <div class="attribute-label">Talla</div>
                                        <div class="attribute-value size-selector">
                                            <select name="attribute_pa_tallas" class="custom-select required-attribute">
                                                <option value="">Selecciona una talla</option>
                                                <?php foreach ($tallas as $talla) { ?>
                                                    <option value="<?php echo esc_attr($talla->slug); ?>"><?php echo esc_html($talla->name); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                <?php
                                }
                            }
                        }
                        ?>
                    </div>
                        
                    <?php
                    // Colocar el resto de atributos debajo
                    // Selector de colores
                    if (isset($attributes['pa_color'])) {
                        $colors = wc_get_product_terms($product_id, 'pa_color', array('fields' => 'all'));
                        if (!empty($colors)) {
                        ?>
                            <div class="attribute-row">
                                <div class="attribute-label">Colores</div>
                                <div class="attribute-value color-selector">
                                    <div class="color-options">
                                    <?php 
                                    foreach ($colors as $color) {
                                        // Determinar el color CSS basado en el nombre
                                        $css_color = '';
                                        switch($color->slug) {
                                            case 'rojo': $css_color = '#dd3333'; break;
                                            case 'verde': $css_color = '#81d742'; break;
                                            case 'azul': $css_color = '#1e73be'; break;
                                            case 'rosa': $css_color = '#e61a99'; break;
                                            case 'morado': $css_color = '#8224e3'; break;
                                            default: $css_color = '#eeeeee'; break;
                                        }
                                    ?>
                                        <label class="color-option">
                                            <input type="radio" name="attribute_pa_color" value="<?php echo esc_attr($color->slug); ?>" class="required-attribute">
                                            <span class="color-swatch" style="background-color:<?php echo $css_color; ?>"></span>
                                        </label>
                                    <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php
                        }
                    }
                    
                    // Procesar otros atributos si existen
                    if ($product->is_type('variable')) {
                        foreach ($attributes as $attribute_name => $attribute) {
                            // Saltarse los atributos ya procesados
                            if ($attribute_name == 'pa_tallas' || $attribute_name == 'pa_color') {
                                continue;
                            }
                            
                            if ($attribute->get_variation()) {
                                $attribute_options = $attribute->get_options();
                                $attribute_name_clean = wc_attribute_label($attribute_name);
                                
                                if (!empty($attribute_options)) {
                                    $terms = array();
                                    foreach ($attribute_options as $option) {
                                        $term = get_term($option, $attribute_name);
                                        if ($term) {
                                            $terms[] = $term;
                                        }
                                    }
                                    
                                    if (!empty($terms)) {
                                    ?>
                                        <div class="attribute-row">
                                            <div class="attribute-label"><?php echo esc_html($attribute_name_clean); ?></div>
                                            <div class="attribute-value generic-selector">
                                                <select name="attribute_<?php echo esc_attr($attribute_name); ?>" class="custom-select required-attribute">
                                                    <option value="">Selecciona <?php echo esc_html(strtolower($attribute_name_clean)); ?></option>
                                                    <?php foreach ($terms as $term) { ?>
                                                        <option value="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    <?php
                                    }
                                }
                            }
                        }
                    }
                    ?>
                </div>
                
                <!-- Botones de acción -->
                <div class="product-actions">
                    <button type="button" class="btn btn-dark btn-block add-to-cart-btn <?php echo $has_required_attributes ? 'disabled' : ''; ?>">AÑADIR A LA BOLSA</button>
                    <button type="button" class="btn btn-info btn-block buy-now-btn <?php echo $has_required_attributes ? 'disabled' : ''; ?>">COMPRAR AHORA</button>
                </div>
                
                <?php
                // Tabs en posición oculta
                woocommerce_output_product_data_tabs();
                ?>
            </div>
        </div>
        
        <?php 
        // Productos relacionados
        echo do_shortcode('[related_products per_page="3" columns="3"]'); 
        ?>
        
        <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="buttomadd" style="width:400px; margin:0 auto; display:block; text-align:center">VOLVER A LA TIENDA</a>
    <?php endwhile; // end of the loop. ?>
</div>

<br><br>
<div class="taller-bisuteria-container" style="position: relative; max-width: 100%; height: auto; overflow: hidden;">
	<!-- Imagen de fondo (sin overlay) -->
	<img src="https://indomitaselection.com/wp-content/uploads/2025/03/Imagen-6.png" alt="Taller de Bisuteríaa"
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
<a href="<?php echo esc_url(get_permalink(get_page_by_path(page_path: 'contacto'))); ?>" style="">MÁS INFORMACIÓN</a>
	</div>
</div>

<!-- Agregar estilos personalizados -->
<style>
    /* Estilos para la miga de pan */
    .custom-breadcrumb {
        font-size: 16px;
        margin-bottom: 15px;
        color: black;
        font-family: "MontserratAlternates-Medium", sans-serif;
    }
    
    .custom-breadcrumb a {
        color: black;
        text-decoration: none;
        transition: color 0.3s ease;
        text-transform: uppercase;
    }

    .custom-breadcrumb span {
        text-transform: uppercase;
    }
    
    .custom-breadcrumb a:hover {
        color: #72C4CD;
    }
    
    .custom-breadcrumb span {
        font-weight: 500;
    }

    .custom-product-attributes {
        margin: 30px 0;
    }
    
    /* Nueva clase para la fila que contiene los dos primeros atributos */
    .attributes-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    
    /* Modificación para que los atributos en la primera fila tengan un ancho específico */
    .attributes-row .attribute-row {
        width: 48%; /* Deja un pequeño espacio entre los dos elementos */
    }
    
    .attribute-row {
        margin-bottom: 20px;
        align-items: center;
    }
    
    .attribute-label {
        font-family: 'MontserratAlternates-Medium';
        font-weight: 500;
        font-size: 19px;
        line-height: 100%;
        letter-spacing: 5%;
        color: black;
        margin-bottom: 10px;
    }
    
    .attribute-value {
        flex: 1;
    }
    
    /* Estilos para el selector de cantidad */
    .quantity-selector {
        display: flex;
        align-items: center;
    }
    
    .quantity-selector label {
        display: flex;
        align-items: center;
        border: 1px solid black;
        border-radius: 5px;
        padding: 5px 15px;
        cursor: pointer;
        height: 40px;
    }
    
    .quantity-minus, .quantity-plus {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-family: 'MontserratAlternates-Medium';
        font-size: 20px;
        color: black !important;
    }
    
    .quantity-value {
        margin: 0 10px;
        font-family: 'MontserratAlternates-Medium';
        font-weight: 400;
        font-size: 20px;
        line-height: 100%;
        letter-spacing: 5%;
        text-align: center;
        color: black !important;
    }
    
    /* Estilos para el selector tipo select */
    .custom-select {
        width: 100%;
        padding: 8px 15px;
        border: 1px solid black;
        border-radius: 5px;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='6' fill='none'%3E%3Cpath fill='%23333' d='M0 0h12L6 6 0 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 12px;
        font-size: 16px;
        color: #333;
        height: 40px;
    }
    
    .custom-select:focus {
        outline: none;
        border-color: #5ec8d0;
    }
    
    /* Estilos para el selector de color */
    .color-options {
        display: flex;
        gap: 10px;
    }
    
    .color-option {
        margin: 0;
        cursor: pointer;
    }
    
    .color-option input {
        display: none;
    }
    
    .color-swatch {
        display: block;
        width: 30px;
        height: 30px;
        border-radius: 5px;
        border: 1px solid #e0e0e0;
    }
    
    .color-option input:checked + .color-swatch {
        box-shadow: 0 0 0 2px #333;
    }
    
    /* Estilos para los botones de acción */
    .product-actions {
        margin-top: 30px;
    }
    
    .add-to-cart-btn {
        margin-bottom: 10px;
        text-transform: uppercase;
        padding: 10px;
        width: 100% !important;
        background-color: black;
        color: #72C4CD !important;
        font-family: 'MontserratAlternates-Medium';
        font-weight: 600;
        font-size: 18px !important;
        line-height: 15px;
        letter-spacing: 5%;
        text-align: center;
        box-shadow: 0px 2px 2px 0px #00000080;
        border-radius: 5px !important;
        transition: all 0.3 ease;
        border: 0px !important;
    }
    .add-to-cart-btn:hover {
        background-color: #72C4CD !important;
        color: black !important;
        border: 0px !important;
    }
    
    .buy-now-btn {
        margin-bottom: 10px;
        text-transform: uppercase;
        padding: 10px;
        width: 100% !important;
        background-color: #72C4CD;
        color: black !important;
        font-family: 'MontserratAlternates-Medium';
        font-weight: 600;
        font-size: 18px !important;
        line-height: 15px;
        letter-spacing: 5%;
        text-align: center;
        box-shadow: 0px 2px 2px 0px #00000080;
        border-radius: 5px !important;
        transition: all 0.3 ease;
        border: 0px !important;
    }
    .buy-now-btn:hover {
        background-color: black !important;
        color: #72C4CD !important;
        border: 0px !important;
    }

    .radio-container input{
        display: none;
    }
    
    /* Estilos para botones deshabilitados */
    .add-to-cart-btn.disabled {
        background-color: #6c757d;
        border-color: #6c757d;
        opacity: 0.65;
        cursor: not-allowed;
    }
    
    .buy-now-btn.disabled {
        background-color: #a9d7dc;
        border-color: #a9d7dc;
        opacity: 0.65;
        cursor: not-allowed;
    }
    
    /* Ocultar las tabs por defecto */

    
    /* Ocultar formulario original */
    .hidden-form {
        position: absolute;
        left: -9999px;
        height: 0;
        overflow: hidden;
    }
</style>

<!-- Agregar javascript para la funcionalidad -->
<script>
    jQuery(document).ready(function($) {
        // Verificar si el producto es variable
        var isVariable = <?php echo $product->is_type('variable') ? 'true' : 'false'; ?>;
        var variations = <?php echo $product->is_type('variable') ? json_encode($product->get_available_variations()) : '[]'; ?>;
        
        // Funcionamiento de los botones de cantidad
        $('.quantity-minus').click(function(e) {
            e.preventDefault();
            var value = parseInt($('.quantity-value').text());
            if (value > 1) {
                $('.quantity-value').text(value - 1);
                $('#hidden-quantity').val(value - 1);
            }
        });
        
        $('.quantity-plus').click(function(e) {
            e.preventDefault();
            var value = parseInt($('.quantity-value').text());
            $('.quantity-value').text(value + 1);
            $('#hidden-quantity').val(value + 1);
        });
        
        // Verificar si todos los atributos requeridos están seleccionados
        function checkRequiredAttributes() {
            if (!isVariable) return true; // Si no es variable, no hay requisitos
            
            var allSelected = true;
            var selectedAttributes = {};
            
            // Verificar cada grupo de atributos (tanto radio buttons como selects)
            $('.required-attribute').each(function() {
                var name = $(this).attr('name');
                var value = '';
                
                if ($(this).is('select')) {
                    // Para elementos select
                    value = $(this).val();
                    if (!value || value === '') {
                        allSelected = false;
                    } else {
                        selectedAttributes[name] = value;
                    }
                } else {
                    // Para radio buttons
                    var groupSelected = $('input[name="' + name + '"]:checked').length > 0;
                    if (!groupSelected) {
                        allSelected = false;
                    } else {
                        selectedAttributes[name] = $('input[name="' + name + '"]:checked').val();
                    }
                }
            });
            
            // Si todos los atributos están seleccionados, buscar la variación correcta
            if (allSelected && isVariable) {
                findMatchingVariation(selectedAttributes);
            }
            
            // Actualizar estado de los botones
            if (allSelected) {
                $('.add-to-cart-btn, .buy-now-btn').removeClass('disabled');
            } else {
                $('.add-to-cart-btn, .buy-now-btn').addClass('disabled');
            }
            
            return allSelected;
        }
        
        // Buscar la variación que coincide con los atributos seleccionados
        function findMatchingVariation(selectedAttributes) {
            for (var i = 0; i < variations.length; i++) {
                var variation = variations[i];
                var match = true;
                
                // Comprobar si todos los atributos seleccionados coinciden con esta variación
                for (var attrName in selectedAttributes) {
                    var attrKey = attrName.replace('attribute_', '');
                    if (variation.attributes[attrName] !== selectedAttributes[attrName] && 
                        variation.attributes[attrName] !== '') {
                        match = false;
                        break;
                    }
                }
                
                if (match) {
                    // Actualizar el ID de variación en el formulario oculto
                    $('.hidden-form .variation_id').val(variation.variation_id);
                    return true;
                }
            }
            
            // No se encontró una coincidencia
            $('.hidden-form .variation_id').val('');
            return false;
        }
        
        // Detectar cambios en la selección de atributos (tanto radio buttons como selects)
        $('.required-attribute').change(function() {
            checkRequiredAttributes();
        });
        
        // Funcionamiento del botón añadir al carrito
        $('.add-to-cart-btn').click(function(e) {
            e.preventDefault();
            
            // Verificar si el botón está habilitado
            if ($(this).hasClass('disabled')) {
                return;
            }
            
            // Actualizar la cantidad
            var quantity = parseInt($('.quantity-value').text());
            $('#hidden-quantity').val(quantity);
            
            // Enviar el formulario
            $('.hidden-form').submit();
        });
        
        // Funcionamiento del botón comprar ahora
        $('.buy-now-btn').click(function(e) {
            e.preventDefault();
            
            // Verificar si el botón está habilitado
            if ($(this).hasClass('disabled')) {
                return;
            }
            
            // Actualizar la cantidad
            var quantity = parseInt($('.quantity-value').text());
            $('#hidden-quantity').val(quantity);
            
            // Obtener los datos del formulario para usarlos en la petición AJAX
            var formData = $('.hidden-form').serialize();
            
            // Añadir al carrito vía AJAX y luego redirigir al checkout
            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
                data: formData,
                success: function(response) {
                    if (response.error) {
                        // En caso de error, mostrar el error y no redirigir
                        console.log(response.error);
                    } else {
                        // Redirigir directamente al checkout
                        window.location.href = '<?php echo wc_get_checkout_url(); ?>';
                    }
                },
                error: function() {
                    console.log('Error al añadir al carrito');
                }
            });
        });
        
        // Inicializar estado de los botones al cargar la página
        checkRequiredAttributes();
    });
</script>

<?php
// Close the wrapper
do_action('woocommerce_after_main_content');

get_footer('shop');
?>