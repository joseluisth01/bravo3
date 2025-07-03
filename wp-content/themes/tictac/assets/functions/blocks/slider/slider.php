<?php

if (function_exists('acf_add_local_field_group')) :

    acf_add_local_field_group(array(
        'key' => 'group_628e9448399d8',
        'title' => 'Slider full',
        'fields' => array(
            array(
                'key' => 'field_628e944ce6053',
                'label' => 'Slider full',
                'name' => 'slider_full_repeater',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'collapsed' => '',
                'min' => 0,
                'max' => 0,
                'layout' => 'block',
                'button_label' => 'Agregar Fila',
                'rows_per_page' => 20,
                'sub_fields' => array(
                    array(
                        'key' => 'field_628e945be6054',
                        'label' => 'Imagen',
                        'name' => 'imagen_slider',
                        'aria-label' => '',
                        'type' => 'image',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'array',
                        'preview_size' => 'medium',
                        'library' => 'all',
                        'min_width' => '',
                        'min_height' => '',
                        'min_size' => '',
                        'max_width' => '',
                        'max_height' => '',
                        'max_size' => '',
                        'mime_types' => '',
                        'parent_repeater' => 'field_628e944ce6053',
                        'layout' => 'block',
                    ),
                    array(
                        'key' => 'field_62b1d3cf3615a',
                        'label' => 'titulo banner',
                        'name' => 'titulo_banner',
                        'aria-label' => '',
                        'type' => 'wysiwyg',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                        'parent_repeater' => 'field_628e944ce6053',
                        'layout' => 'block',
                    ),
                    array(
                        'key' => 'field_62b1d3d8365b',
                        'label' => 'subtitulo banner',
                        'name' => 'subtitulo_banner',
                        'aria-label' => '',
                        'type' => 'wysiwyg',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                        'delay' => 0,
                        'parent_repeater' => 'field_628e944ce6053',
                        'layout' => 'block',
                    ),
                    array(
                        'key' => 'field_62b1d3e3366c',
                        'label' => 'link banner',
                        'name' => 'link_banner',
                        'aria-label' => '',
                        'type' => 'link',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'array',
                        'parent_repeater' => 'field_628e944ce6053',
                        'layout' => 'block',
                    ),
                ),
            ),
            array(
                'key' => 'field_65f185aba5c4b',
                'label' => 'formulario',
                'name' => 'titulo_formulario',
                'aria-label' => '',
                'type' => 'text',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/slider-full',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ));

endif;

function slider_acf()
{
    acf_register_block_type([
        'name'        => 'Slider full',
        'title'        => __('Slider full', 'tictac'),
        'description'    => __('Usado en la home normalmente', 'tictac'),
        'render_callback'  => 'slider_full',
        'mode'        => 'preview',
        'icon'        => 'star-filled',
        'keywords'      => ['custom', 'slider', 'bloque', 'home'],
    ]);
}

add_action('acf/init', 'slider_acf');

function slider_scripts()
{
    if (!is_admin()) {
        wp_enqueue_style('slider', get_stylesheet_directory_uri() . '/assets/functions/blocks/slider/slider.min.css');
    }
}
add_action('wp_enqueue_scripts', 'slider_scripts');

function slider_full()
{

?>
    <section class="splide slider_full" aria-label="Slider Principal">


        <div class="splide__track">
            <ul class="splide__list">
                <?php
                if (have_rows('slider_full_repeater')) {
                    $counter = 0;
                    while (have_rows('slider_full_repeater')) {
                        the_row();
                        $imagen_slider = get_sub_field('imagen_slider');
                        $titulo_banner = get_sub_field('titulo_banner');
                        $subtitulo_banner = get_sub_field('subtitulo_banner');
                        $link_banner = get_sub_field('link_banner');
                ?>
                        <li class="splide__slide">
                            <img class="<?php echo 'banner-' . $counter; ?>" data-splide-lazy-srcset="<?= $imagen_slider['url']; ?>" alt="<?= $imagen_slider['alt']; ?>">
                            <div class="content">
                                <?php if ($titulo_banner) { ?>
                                    <div class="titulo">
                                        <?= $titulo_banner; ?>
                                    </div>
                                <?php } ?>

                                <?php if ($subtitulo_banner) { ?>
                                    <div class="subtitulo">
                                        <?= $subtitulo_banner; ?>
                                    </div>
                                <?php } ?>
                                <?php if ($link_banner) { ?>
                                    <div class="link">
                                        <a class="btn custom yellow" href="<?= $link_banner['url']; ?>"><?= $link_banner['title']; ?></a>
                                    </div>
                                <?php } ?>
                            </div>
                        </li>
                <?php
                        $counter++;
                    }
                }
                ?>
            </ul>
        </div>

        <div class="splide__arrows">
            <button class="splide__arro splide__arrow--prev">
                <img class="arrow-custom" src="/annica/wp-content/uploads/2024/10/Vector-11.svg" alt="Previous">
            </button>
            <button class="splide__arro splide__arrow--next">
                <img class="arrow-custom" src="/annica/wp-content/uploads/2024/10/Vector-10.svg" alt="Next">
            </button>
        </div>
    </section>

<?php
}
