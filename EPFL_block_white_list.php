<?php
/*
* Plugin Name: EPFL block white list
* Plugin URI:
* Description: Must-use plugin for the EPFL website to define allowed blocks coming from Gutenberg or installed plugins.
* Version: 1.0.9
* Author: wwp-admin@epfl.ch
 */

function epfl_allowed_block_types( $allowed_block_types, $block_editor_context ) {
    if ($allowed_block_types === false) {
        // Look like we don't want any post, respect that
        return $allowed_block_types;
    }

    /* List of blocks allowed only in Posts
    NOTES:
    - A block cannot be in both list at the same time.
    - For EPFL blocks allowed in Posts, please have a look a wp-epfl-gutenberg plugin (plugin.php) file*/
    $post_only_blocks = array(
        'core/heading',
        'core/image',
        'core/file',
        'core/list',
        'core/spacer',
        'core/separator',
        'tadv/classic-paragraph');

    $rest_of_allowed_blocks = array(
        'core/classic',
        'core/rss',
        'core/shortcode',
        'core/freeform',
        'enlighter/codeblock',
        // When WPForms is present, this allows to use it in the code block lookup UI (Tested in 5.7)
        'wpforms/form-selector'
    );

    // In all cases post only blocks are allowed
    if (is_array($allowed_block_types)) {
        $allowed_block_types = array_merge($allowed_block_types, $post_only_blocks);
    } else {
        // case when it's the boolean one
        $allowed_block_types = $post_only_blocks;
    }

    // If we're not editing a post, we all rest of allowed blocks.
    if($block_editor_context->post->post_type != 'post')
    {
        $allowed_block_types = array_merge($allowed_block_types, $rest_of_allowed_blocks);
    }

    /* NOTE: Don't do an "array_unique()" to avoid duplicates. For an unknown reason, even if the array content seems to be correctly
        filtered, the result will be that all blocks will be allowed, but ONLY on pages... not on posts... it's like the return of
        "array_unique" function is "different" when the number of elements in the array is more than X ... */
    return $allowed_block_types;
    // return True; // if you want all natifs blocks.
}

// Gutenberg is on ?
if (function_exists( 'register_block_type' ) ) {
    // We register this filter with priority 99 to ensure it will be called after the one (if present) added in Gutenberg plugin to
    // register epfl blocks
	add_filter( 'allowed_block_types_all', 'epfl_allowed_block_types', 99, 2 );
}

add_action( 'admin_init', function() {

	// Disable "Available to Install" block suggestions.
	remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
	remove_action( 'enqueue_block_editor_assets', 'gutenberg_enqueue_block_editor_assets_block_directory' );

} );

add_action( 'init', function() {

	// Disable core block patterns.
	remove_theme_support( 'core-block-patterns' );

} );
