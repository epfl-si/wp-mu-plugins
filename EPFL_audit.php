<?php
/**
 * EPFL Audit
 *
 * @package     EPFLAudit
 * @author      ISAS-FSD
 * @copyright   Copyright (c) 2025, EPFL
 * @license     GPL-2.0-or-later
 *
 * @wordpress-mu-plugins
 * Must Use Plugin Name: EPFL Audit
 * Plugin URI:  https://github.com/epfl-si/wp-mu-plugins
 * Description: Write audit logs for wp-admin
 * Version:     1.0.0
 * Author:      ISAS-FSD
 * Author URI:  https://go.epfl.ch/idev-fsd
 * Text Domain: EPFL-Audit
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 */

function save_page_function( $post_id, $post, $update ) {
	if ( wp_is_post_autosave( $post_id ) or wp_is_post_revision( $post_id ) or !$update) {
		return;
	}

	$user = wp_get_current_user();
	if (in_array($post->post_type, ['post', 'page'])) {
		error_log($post->post_type . ' "' . get_the_title($post_id) . '" (' . get_page_link($post_id) . ') was updated by ' . $user->user_email . ' (' . $user->user_nicename . ') for site ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	} elseif ($post->post_type == 'nav_menu_item') {
		$menu_item = wp_setup_nav_menu_item( get_post( $post_id ) );
		error_log($post->post_type . ' "' . $post_id . ' - ' . $menu_item->title . '" (' . $menu_item->url . ') was updated by ' . $user->user_email . ' (' . $user->user_nicename . ') for site ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	}
}

function delete_page_function( $post_id ) {
	$post = get_post($post_id);
	if ( wp_is_post_revision( $post_id ) ){
		return;
	}
	$user = wp_get_current_user();
	if (in_array($post->post_type, ['post', 'page'])) {
		error_log($post->post_type . ' "' . get_the_title($post_id) . '" (' . get_page_link($post_id) . ') was deleted by ' . $user->user_email . ' (' . $user->user_nicename . ') for site ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	} elseif ($post->post_type == 'nav_menu_item') {
		error_log($post->post_type . ' ' . $post_id . ') was deleted by ' . $user->user_email . ' (' . $user->user_nicename . ') for site ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	}

}

function create_nav_menu_function( $menu_id, $action ) {
	$user = wp_get_current_user();
	error_log('Menu ' . $menu_id . ') was ' . $action . ' by ' . $user->user_email . ' (' . $user->user_nicename . ') for site ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
}

add_action( 'save_post', 'save_page_function', 10, 3 );
add_action( 'delete_post', 'delete_page_function', 10 );

add_action( 'wp_create_nav_menu', function( $menu_id ) {
	$action = 'created';
	create_nav_menu_function( $menu_id, $action );
}, 10 );
add_action( 'wp_update_nav_menu', function( $menu_id ) {
	$action = 'updated';
	create_nav_menu_function( $menu_id, $action );
}, 10 );
add_action( 'wp_delete_nav_menu', function( $menu_id ) {
	$action = 'deleted';
	create_nav_menu_function( $menu_id, $action );
}, 10 );

// TODO users, wpform, redirections, options
