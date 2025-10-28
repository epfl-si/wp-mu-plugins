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
	$user = get_user_by('id', $user_id);
	callOPDo($action, 'User ' . $user->nickname . ' was ' . $action_label);
}

function option_function( $option, $value, $action, $old_value ) {
	if (!str_starts_with($option, '_transient')) {
		if ($action == 'c') callOPDo($action, "Option added $option = " . maybe_serialize($value));
		else if ($action == 'u') callOPDo($action, "Option modified : $option from " . maybe_serialize($old_value) . " to " . maybe_serialize($value));
		else if ($action == 'd') callOPDo($action, "Option deleted : $option");
	}
}

function wpforms_function( $post_id, $post, $update ) {
	if ( wp_is_post_revision( $post_id ) ) return;
	$log_entry = sprintf(
		"Form %s (ID %d) has been updated\n",
		$post->post_title,
		$post_id
	);
	callOPDo('u', $log_entry);
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
add_action( 'save_post_wpforms', 'wpforms_function', 10, 3 );
add_action( 'wpforms_process_complete', 'my_wpforms_logger', 10, 4 );
function my_wpforms_logger( $fields, $entry, $form_data, $entry_id ) {
	$log_data = array(
		'form_id'   => $form_data['id'],
		'form_name' => $form_data['settings']['form_title'],
		'entry_id'  => $entry_id,
		'fields'    => $fields,
		'user_ip'   => $_SERVER['REMOTE_ADDR'],
		'user_id'   => get_current_user_id(),
		'timestamp' => current_time('mysql'),
	);
	$log_file = WP_CONTENT_DIR . '/wpforms_log.txt';
	file_put_contents( $log_file, print_r($log_data, true) . "\n", FILE_APPEND );
	if ( defined('WP_DEBUG') && WP_DEBUG ) {
		error_log( print_r($log_data, true) );
	}
}
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
	error_log(var_export($data, true));

	$ch = curl_init($url);

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
