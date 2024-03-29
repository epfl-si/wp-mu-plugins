<?php
/*
 * Plugin Name: EPFL disable all automatic updates.
 * Plugin URI:
 * Description: Must-use plugin for the EPFL website.
 * Version: 0.0.4
 * Author: wwp-admin@epfl.ch
 * */

/* Disable all automatic updates.
 *
 * http://codex.wordpress.org/Configuring_Automatic_Background_Updates
 */

// disable WordPress Core minor updates
add_filter( 'allow_minor_auto_core_updates', '__return_false' );

// disable WordPress Core major updates
add_filter( 'allow_major_auto_core_updates', '__return_false' );

// disable plugins updates
add_filter( 'auto_update_plugin', '__return_false' );
// hide UI to edit the auto update option
add_filter( 'plugins_auto_update_enabled', '__return_false' );

// disable themes updates
add_filter( 'auto_update_theme', '__return_false' );
// hide UI to edit the auto update option
add_filter( 'themes_auto_update_enabled', '__return_false' );

// disable transalations updates
add_filter( 'auto_update_translation', '__return_false' );

// disable plugin update cues
function epfl_cannot_update_plugins ($allcaps, $caps, $args) {
    unset($allcaps['update_plugins']);
    return $allcaps;
}
add_filter('user_has_cap', 'epfl_cannot_update_plugins', 10, 3);

// disable notifications about new WP version in the dasboard
function epfl_no_update_nag () {
    remove_action('admin_notices', 'update_nag', 3);
}
add_action( 'admin_init', 'epfl_no_update_nag' );

// disable links about updating to a new WP version
function epfl_cannot_update_core ($allcaps, $caps, $args) {
    unset($allcaps['update_core']);
    return $allcaps;
}
add_filter('user_has_cap', 'epfl_cannot_update_core', 10, 3);
