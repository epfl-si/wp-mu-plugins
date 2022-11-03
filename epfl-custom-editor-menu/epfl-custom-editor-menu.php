<?php

/**
 * Limit and/or remove some elements, by CSS or JS, in the editor view in Gutenberg.
 * For the editors only.
  */
function add_gutenberg_custom_editor_menu() {
    /* only for admin */
    if(is_admin() && current_user_can('administrator') ) {
        wp_enqueue_script(
            'epfl-custom-gutenberg-administrator.js',
            content_url() . '/mu-plugins/epfl-custom-editor-menu/epfl-custom-gutenberg-administrator.js',
            array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element' ),
            filemtime(dirname(__FILE__) . '/epfl-custom-gutenberg-administrator.js')
        );
    }

    /* only for non-admin, aka the editors */
    if(is_admin() && !current_user_can('administrator') ) {
        /**
         * Script to remove Gutenberg elements in JS
         */
        wp_enqueue_script(
            'epfl-custom-gutenberg-editor.js',
            content_url() . '/mu-plugins/epfl-custom-editor-menu/epfl-custom-gutenberg-editor.js',
            array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element' ),
            filemtime(dirname(__FILE__) . '/epfl-custom-gutenberg-editor.js')
        );

        /**
         * Custom CSS
         */
        wp_enqueue_style(
            'epfl-custom-gutenberg-editor.css',
            content_url() . '/mu-plugins/epfl-custom-editor-menu/epfl-custom-gutenberg-editor.css',
            array('wp-edit-blocks'),
            filemtime(dirname(__FILE__) . '/epfl-custom-gutenberg-editor.css')
        );
    }

    /**
     * Custom CSS for all roles
     */
    wp_enqueue_style(
            'epfl-custom-gutenberg-all.css',
            content_url() . '/mu-plugins/epfl-custom-editor-menu/epfl-custom-gutenberg-all.css',
            array('wp-edit-blocks'),
            filemtime(dirname(__FILE__) . '/epfl-custom-gutenberg-all.css')
    );
}
if (function_exists( 'register_block_type' ) ) {
    // register_block_type exits, we are in Gutenberg
	add_action( 'enqueue_block_editor_assets', 'add_gutenberg_custom_editor_menu' );
}

/**
 * Hides elements in "pages list", for editors only
 */
function add_custom_pages_list() {
    if (is_admin() && !current_user_can('administrator') ) {
        wp_enqueue_script(
            'custom-pages-list.js',
            content_url() . '/mu-plugins/epfl-custom-editor-menu/custom-pages-list.js',
            array('jquery'),
            filemtime(dirname(__FILE__) . '/custom-pages-list.js')
        );
    }
}
add_action('admin_enqueue_scripts', 'add_custom_pages_list');
