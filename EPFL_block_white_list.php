<?php
/*
* Plugin Name: EPFL block white list
* Plugin URI:
* Description: Must-use plugin for the EPFL website to define allowed blocks coming from Gutenberg or installed plugins.
* Version: 1.0.3
* Author: wwp-admin@epfl.ch
 */

function epfl_allowed_block_types( $allowed_block_types, $post ) {
    
    /* List of blocks allowed only in Posts
    NOTE: A block cannot be in both list at the same time. */
    $post_only_blocks = array();

    $rest_of_allowed_blocks = array(
        'core/paragraph',
        'core/heading',
        'core/gallery',
        'core/classic',
        'core/rss',
        'core/table',
        'core/spacer',
        'core/separator',
        'core/shortcode',
        'core/freeform',
        'core/list',
        'core/image',
        'core/file',
        'tadv/classic-paragraph',
        'pdf-viewer-block/standard',
    );

    // In all cases post only blocks are allowed
    $allowed_block_types = array_merge($allowed_block_types, $post_only_blocks);

    // If we're not editing a post, we all rest of allowed blocks.
    if($post->post_type != 'post')
    {
        $allowed_block_types = array_merge($allowed_block_types, $rest_of_allowed_blocks);
    }

    // We ensure there's no duplicate
  	return array_unique($allowed_block_types);
    // return True; // if you want all natifs blocks.
}

// Gutenberg is on ?
if (function_exists( 'register_block_type' ) ) {
    // We register this filter with priority 99 to ensure it will be called after the one (if present) added in Gutenberg plugin to
    // register epfl blocks
	add_filter( 'allowed_block_types', 'epfl_allowed_block_types', 99, 2 );
}