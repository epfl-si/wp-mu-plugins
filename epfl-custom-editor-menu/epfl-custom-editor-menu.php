<?php

// For Gutenberg only, limit and remove some elements
function add_gutenberg_custom_editor_menu() {
	wp_enqueue_script(
		'wp-gutenberg-epfl-custom-editor-menu',
		content_url() . '/mu-plugins/epfl-custom-editor-menu/epfl-custom-editor-menu.js',
		array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element' )
	);

	/**
	 * set custom CSS to be loaded for non admin users
	 */
	if(is_admin() && !current_user_can('administrator') ) {
		wp_enqueue_style(
			'editor-styles.css',
			content_url() . '/mu-plugins/epfl-custom-editor-menu/custom-editor-for-editor.css',
			array('wp-edit-blocks'),
			filemtime(dirname(__FILE__) . '/custom-editor-for-editor.css')
		);
	}
}

// Gutenberg is on ?
if (function_exists( 'register_block_type' ) ) {
	add_action( 'enqueue_block_editor_assets', 'add_gutenberg_custom_editor_menu' );
}

?>
