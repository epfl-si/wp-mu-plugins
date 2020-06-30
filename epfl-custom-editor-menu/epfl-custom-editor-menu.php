<?php

// For Gutenberg only, limit and remove some elements
function add_gutenberg_custom_editor_menu() {
	wp_enqueue_script(
		'wp-gutenberg-epfl-custom-editor-menu',
		content_url() . '/mu-plugins/epfl-custom-editor-menu/epfl-custom-editor-menu.js',
		array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element' )
	);

	// If we're in the admin section and user is not an administrator,
	if(is_admin() && !current_user_can('administrator') )
	{
		// We add a script to hide "Page Attributes" panel on the editor right side
		wp_enqueue_script(
			'wp-gutenberg-epfl-custom-editor-menu-editor',
			content_url() . '/mu-plugins/epfl-custom-editor-menu/epfl-custom-editor-menu-editor.js',
			array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element' )
		);
	}
}


// Gutenberg is on ?
if (function_exists( 'register_block_type' ) ) {
	add_action( 'enqueue_block_editor_assets', 'add_gutenberg_custom_editor_menu' );
	
}

?>
