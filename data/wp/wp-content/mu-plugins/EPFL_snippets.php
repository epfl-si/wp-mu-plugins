<?php

/**
 * Plugin Name: EPFL snippets
 * Description: display snippets, an image with a title, subtitle, description and image.
 * @version: 1.0
 * @copyright: Copyright (c) 2018 Ecole Polytechnique Federale de Lausanne, Switzerland
 */

declare( strict_types = 1 );

/**
 * Helper to debug the code
 * @param $var: variable to display
 */
function epfl_snippets_debug( $var ) {
    print "<pre>";
    var_dump( $var );
    print "</pre>";
}

/**
 * Build the html
 *
 * @param $title: the title
 * @param $subtitle: the subtitle
 * @param $description: the description
 * @param $image: the image
 * @param $big_image: the big image (optional)
 * @param $enable_zoom: if true the image can be zoomed
 * @return string the snippets html
 */
function epfl_snippets_build_html( string $title, string $subtitle, string $description, string $image, string $big_image, , string $enable_zoom ): string
{
    $html  = '<div class="snippets">';
    $html .= '  <div class="snippets-title">' . $title . '</div>';
    $html .= '  <div class="snippets-subtitle">' . $subtitle . '</div>';
    $html .= '  <div class="snippets-description">' . $description . '</div>';
    $html .= '  <div class="snippets-image"><img src="' . esc_attr($image) . '"/></div>';        
    $html .= '</div">';
    
    return $html;
}

/**
 * Execute the shortcode
 *
 * @attributes: array of all input parameters
 * @content: the content of the shortcode. In our case the content is empty
 * @return html of shortcode
 */
function epfl_snippets_process_shortcode( $attributes, string $content = null ): string
{
    // get parameters
    $atts = shortcode_atts(array(
        'title'        => '',
        'subtitle'     => '',
        'description'  => '',
        'image'        => '',
        'big_image'    => '',
        'enable_zoom'  => '',
    ), $attributes);
    
    // sanitize parameters
    $title       = sanitize_text_field($atts['title']);
    $subtitle    = sanitize_text_field($atts['subtitle']);
    $description = sanitize_text_field($atts['description']);
    $image       = sanitize_text_field($atts['image']);
    $big_image   = sanitize_text_field($atts['big_image']);
    $enable_zoom = sanitize_text_field($atts['enable_zoom']);

    return epfl_snippets_build_html( $title, $subtitle, $description, $image, $big_image, $enable_zoom );
}

add_shortcode( 'epfl_snippets', 'epfl_snippets_process_shortcode' );

?>
