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

add_action( 'simple_history/log/inserted', 'simple_history_function', 10, 2 );

add_action( 'admin_init', 'wpforms_export_all_function' );
add_action( 'admin_init', 'wpforms_export_single_entry_function' );
add_action( 'admin_init', 'wpforms_data_list_function' );
add_action( 'admin_init', 'wpforms_payments_data_list_function' );
add_action( 'admin_init', 'wpform_data_read_function' );
add_action( 'admin_init', 'wpforms_data_delete_function' );
add_action( 'admin_init', 'wpforms_data_actions_from_trash_function' );
add_action( 'admin_init', 'wpforms_data_edit_function' );

function simple_history_function($insert_id) {
	if (!isset($payload['log_id']['_message_key'])) return;
	$payload = array(
		'log_id'   => $insert_id,
		'site_url' => get_site_url()
	);
	callOPDo(json_encode($payload), $payload['log_id']['_message_key']);
}

function wpforms_export_all_function() {
	if (
		isset( $_GET['page'], $_GET['view'], $_GET['action'], $_GET['request_id'] ) &&
		$_GET['page'] === 'wpforms-tools' &&
		$_GET['view'] === 'export' &&
		$_GET['action'] === 'wpforms_tools_entries_export_download'
	) {
		$request_id = sanitize_text_field($_GET['request_id']);
		$request_data = get_option( '_wpforms_transient_wpforms-tools-entries-export-request-' . $request_id );
		$log_entry = sprintf(
			"All entries has been exported for form %s (ID: %d)",
			$request_data['form_data']['settings']['form_title'],
			$request_data['form_data']['id']
		);
		apply_filters('simple_history_log',$log_entry);
		callOPDo( $log_entry, 'wpform_export_all');
	}
}

function wpforms_export_single_entry_function() {
	if (
		isset( $_GET['page'], $_GET['view'], $_GET['action'], $_GET['request_id'] ) &&
		$_GET['page'] === 'wpforms-tools' &&
		$_GET['view'] === 'export' &&
		$_GET['action'] === 'wpforms_tools_single_entry_export_download'
	) {
		$log_entry = sprintf(
			"Entry has been exported for form %s (ID: %d): %s",
			get_the_title( $_GET['form'] ),
			$_GET['form'],
			json_encode(wpforms()->entry->get( $_GET['entry_id'] ))
		);
		apply_filters('simple_history_log',substr($log_entry,0, 250) . ' ...');
		callOPDo( $log_entry, 'wpform_export_single_entry');
	}
}

function wpforms_data_list_function() {
	if (
		isset( $_GET['page'], $_GET['view'], $_GET['form_id'] ) &&
		$_GET['page'] === 'wpforms-entries' &&
		$_GET['view'] === 'list' && !isset($_GET['action']) && (!isset($_GET['type']) || $_GET['type'] !== 'payment')
	) {
		$log_entry = sprintf(
			"All entries has been read for form '%s' (ID: %d)",
			get_the_title( $_GET['form_id'] ),
			$_GET['form_id']
		);
		apply_filters('simple_history_log',$log_entry);
		callOPDo( $log_entry, 'wpform_data_list');
	}
}

function wpforms_payments_data_list_function() {
	if (
		isset( $_GET['page'], $_GET['view'], $_GET['form_id'], $_GET['type'] ) &&
		$_GET['page'] === 'wpforms-entries' &&
		$_GET['view'] === 'list' && !isset($_GET['action']) && $_GET['type'] === 'payment'
	) {
		$log_entry = sprintf(
			"All payments has been read for form '%s' (ID: %d)",
			get_the_title( $_GET['form_id'] ),
			$_GET['form_id']
		);
		apply_filters('simple_history_log',$log_entry);
		callOPDo( $log_entry, 'wpform_data_list_payment');
	}
}

function wpform_data_read_function() {
	if (
		isset( $_GET['page'], $_GET['view'], $_GET['entry_id'] ) &&
		$_GET['page'] === 'wpforms-entries' &&
		($_GET['view'] === 'edit' || $_GET['view'] === 'details' || $_GET['view'] === 'print')
	) {
		write_entry_log(wpforms()->entry->get( $_GET['entry_id'] ),
			$_GET['view'] === 'print' ? 'wpform_data_print_details' : 'wpform_data_read_details');
	}
}

function wpforms_data_delete_function() {
	if (
		isset( $_GET['page'], $_GET['view'], $_GET['action'], $_GET['entry_id'] ) &&
		$_GET['page'] === 'wpforms-entries' &&
		($_GET['view'] === 'list' || $_GET['view'] === 'payment') &&
		($_GET['action'] === 'trash' || $_GET['action'] === 'delete')
	) {
		if (isset($_GET['entry_id'])) {
			$entry = wpforms()->entry->get( $_GET['entry_id']);
			$entry->payment = wpforms()->payment->get( $entry->payment_id );
			write_entry_log($entry,
			(isset($_GET['type']) && $_GET['type'] === 'payment') ? 'wpform_data_delete_details_payment' : 'wpform_data_delete_details');
		} else if (isset($_GET['payment_id'])) {
			$payment = wpforms()->payment->get( $_GET['payment_id'] );
			$entry = wpforms()->entry->get( $payment->entry_id );
			$entry->payment = $payment;
			write_entry_log($entry, 'wpform_data_delete_details_payment');
		}
	}
}

function wpforms_data_actions_from_trash_function() {
	if (
		isset( $_GET['page'], $_GET['view'], $_GET['action'], $_GET['entry_id'], $_GET['status'] ) &&
		$_GET['page'] === 'wpforms-entries' &&
		$_GET['view'] === 'list' && ($_GET['status'] === 'trash' || $_GET['status'] === 'spam')
	) {
		write_entry_log(wpforms()->entry->get( $_GET['entry_id'] ), 'wpform_data_' . str_replace('_', '-', $_GET['action']) . '_details');
	}
}

function wpforms_data_edit_function() {
	if (
		isset( $_POST['action'] ) &&
		$_POST['action'] === 'wpforms_submit'
	) {
		write_entry_log($_POST['wpforms'], 'wpform_data_edit_details');
	}
}

function write_entry_log($entry, $action) {
	$form_id = 0;
	if ( is_object( $entry ) && isset( $entry->id ) ) {
		$form_id = $entry->id;
	} elseif ( is_array( $entry ) && isset( $entry['id'] ) ) {
		$form_id = $entry['id'];
	} elseif ( is_object( $entry ) && isset( $entry->form_id ) ) {
		$form_id = $entry->form_id;
	}
	$parts = explode('_', $action);
	$verb = $parts[2];
	$past_map = [
		'read'   => 'read',
		'delete' => 'deleted',
		'restore' => 'restored',
		'spam' => 'marked as spam',
		'mark-not-spam' => 'marked as not a spam',
	];
	$log_entry = sprintf(
		"WPForm entry has been %s for form '%s' (ID: %d): %s",
		$past_map[$verb] ?? $verb . 'ed',
		get_the_title( $form_id ),
		$form_id,
		json_encode($entry)
	);
	apply_filters('simple_history_log',substr($log_entry,0, 250) . ' ...');
	callOPDo( $log_entry, $action);
}

// TODO redirections
function callOPDo($payload, $action) {
	$user = wp_get_current_user();
	$url = getenv('OPDO_URL');

	$data = [
		"@timestamp" => (new DateTime())->format(DateTime::ATOM),
		"crudt" => $action,
		"handled_id" => $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		"handler_id" => $user->user_email,
		"payload" => $payload,
		"source" => 'wordpress'
	];

	error_log(var_export($data, true));

	// Locally
	if (!getenv('OPDO_URL')) return;

	$ch = curl_init($url);

	curl_setopt($ch, CURLOPT_CAINFO, "/usr/local/share/ca-certificates/opdo-ca.crt");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
