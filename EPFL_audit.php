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

	if (in_array($post->post_type, ['post', 'page'])) {
		callOPDo('u',$post->post_type . ' "' . get_the_title($post_id) . '" (' . get_page_link($post_id) . ') was updated');
	} elseif ($post->post_type == 'nav_menu_item') {
		$menu_item = wp_setup_nav_menu_item( get_post( $post_id ) );
		callOPDo('u', $post->post_type . ' "' . $post_id . ' - ' . $menu_item->title . '" (' . $menu_item->url . ') was updated');
	}
}

function delete_page_function( $post_id ) {
	$post = get_post($post_id);
	if ( wp_is_post_revision( $post_id ) ){
		return;
	}
	if (in_array($post->post_type, ['post', 'page'])) {
		callOPDo('d', $post->post_type . ' "' . get_the_title($post_id) . '" (' . get_page_link($post_id) . ') was deleted');
	} elseif ($post->post_type == 'nav_menu_item') {
		callOPDo('d', $post->post_type . ' ' . $post_id . ') was deleted');
	}
}

function nav_menu_function( $menu_id, $action, $action_label ) {
	callOPDo($action, 'Menu ' . $menu_id . ' was ' . $action_label);
}

function user_function( $user_id, $action, $action_label ) {
	callOPDo($action, 'User ' . $user_id . ' was ' . $action_label);
}

function option_function( $option, $value, $action, $old_value ) {
	/*if ($action == 'c') callOPDo($action, "Option added $option = " . maybe_serialize( $value ) );
	else if ($action == 'u') error_log( "Option modified : $option from " . maybe_serialize($old_value) . " to " . maybe_serialize($value) );
	else if ($action == 'd') error_log( "Option deleted : $option" );*/
}

add_action( 'save_post', 'save_page_function', 10, 3 );
add_action( 'delete_post', 'delete_page_function', 10 );
add_action( 'wp_create_nav_menu', function( $menu_id ) {
	nav_menu_function( $menu_id, 'c', 'created' );
}, 10 );
add_action( 'wp_update_nav_menu', function( $menu_id ) {
	nav_menu_function( $menu_id, 'u', 'updated' );
}, 10 );
add_action( 'wp_delete_nav_menu', function( $menu_id ) {
	nav_menu_function( $menu_id, 'd', 'deleted' );
}, 10 );
add_action( 'user_register', function( $user_id ) {
	user_function( $user_id, 'c', 'created' );
}, 10, 1 );
add_action( 'profile_update', function( $user_id ) {
	user_function( $user_id, 'u', 'updated' );
}, 10, 2 );
add_action( 'delete_user', function( $user_id ) {
	user_function( $user_id, 'd', 'deleted' );
}, 10, 3 );
add_action( 'add_option', function( $option, $value ) {
	option_function( $option, $value, 'c', NULL );
}, 10, 2 );
add_action( 'update_option', function( $option, $old_value, $value ) {
	option_function( $option, $value, 'u', $old_value );
}, 10, 3 );
add_action( 'deleted_option', function( $option ) {
	option_function( $option, NULL, 'd', NULL );
} );

// TODO wpform, redirections

function callOPDo($crudt, $description) {
	$user = wp_get_current_user();
	$url = getenv('OPDO_URL');

	$data = [
		"@timestamp" => (new DateTime())->format(DateTime::ATOM),
		"crudt" => $crudt,
		"handled_id" => $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		"handler_id" => $user->user_email,
		"payload" => 'Site: ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '\n' . $description,
		"source" => 'wordpress'
	];

	$ch = curl_init($url);

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //getenv("API_KEY")
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		"Authorization: ApiKey " . getenv('OPDO_API_KEY'),
		"Content-Type: application/json",
		"Accept: application/json"
	]);

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		error_log('ERROR: ' . curl_error($ch));
	} else {
		error_log("Response: " . $response);
	}

	curl_close($ch);
}
