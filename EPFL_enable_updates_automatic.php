<?php
/*
 * Plugin Name: EPFL enable all automatic updates.
 * Plugin URI:
 * Description: Must-use plugin for the EPFL website.
 * Version: 0.0.3
 * Author: wwp-admin@epfl.ch
 * */

/* Enable all automatic updates.
 *
 * http://codex.wordpress.org/Configuring_Automatic_Background_Updates
 */

// enable WordPress Core minor updates
add_filter( 'allow_minor_auto_core_updates', '__return_true' );

// enable WordPress Core major updates
add_filter( 'allow_major_auto_core_updates', '__return_false' );

// enable plugins updates
add_filter( 'auto_update_plugin', '__return_true' );
// hide UI to edit the auto update option
add_filter( 'plugins_auto_update_enabled', '__return_false' );

// enable themes updates
add_filter( 'auto_update_theme', '__return_true' );
// hide UI to edit the auto update option
add_filter( 'themes_auto_update_enabled', '__return_false' );

// disable transalations updates
add_filter( 'auto_update_translation', '__return_true' );

?>
