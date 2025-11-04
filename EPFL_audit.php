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

add_action( 'admin_init', 'wpforms_export_function' );
add_action( 'admin_init', 'wpforms_data_list_function' );
add_action( 'admin_init', 'wpform_data_read_function' );
add_action( 'admin_init', 'wpforms_data_edit_function' );
add_action( 'admin_init', 'wpforms_handle_entry_action' );
add_action( 'admin_init', 'wpform_data_read_payment_function' );
add_action( 'admin_init', 'wpform_data_delete_payment_function' );
add_action( 'wpforms_process_complete', 'wpform_data_submit_details', 10, 4 );

function simple_history_function($insert_id) {
	if (!isset($insert_id['_message_key'])) return;
	$payload = array(
		'log_id'   => $insert_id,
		'site_url' => get_site_url()
	);
	callOPDo(json_encode($payload), $payload['log_id']['_message_key']);
}

function wpforms_export_function() {
	if (
		isset( $_GET['page'], $_GET['view'], $_GET['action'] ) &&
		$_GET['page'] === 'wpforms-tools' &&
		$_GET['view'] === 'export'
	) {
		if ($_GET['action'] === 'wpforms_tools_entries_export_download' && isset($_GET['request_id'])) {
			$action = 'wpform_export_all';
			$request_id = sanitize_text_field($_GET['request_id']);
			$request_data = get_option( '_wpforms_transient_wpforms-tools-entries-export-request-' . $request_id );
			$log_entry = sprintf(
				"All entries has been exported for form %s (ID: %d)",
				$request_data['form_data']['settings']['form_title'],
				$request_data['form_data']['id']
			);
		} else if ($_GET['action'] === 'wpforms_tools_single_entry_export_download') {
			$action = 'wpform_export_single_entry';
			$log_entry = sprintf(
				"Entry has been exported for form %s (ID: %d): %s",
				get_the_title( $_GET['form'] ),
				$_GET['form'],
				json_encode(wpforms()->entry->get( $_GET['entry_id'] ))
			);
		}
		apply_filters('simple_history_log',substr($log_entry,0, 250) . ' ...');
		callOPDo( $log_entry, $action);
	}
}

function wpforms_data_list_function() {
	if (
		isset( $_GET['page'], $_GET['view'], $_GET['form_id'] ) &&
		$_GET['page'] === 'wpforms-entries' &&
		$_GET['view'] === 'list' && !isset($_GET['action'])
	) {
		$type = 'entries';
		if ($_GET['type'] === 'payment') {
			$type = 'payments';
		}
		$log_entry = sprintf(
			"All %s has been read for form '%s' (ID: %d)",
			$type,
			get_the_title( $_GET['form_id'] ),
			$_GET['form_id']
		);
		apply_filters('simple_history_log',$log_entry);
		callOPDo( $log_entry, 'wpform_data_list_' . $type);
	}
}

function wpform_data_read_function() {
	if (
		isset( $_GET['page'], $_GET['view'], $_GET['entry_id'] ) &&
		$_GET['page'] === 'wpforms-entries' &&
		($_GET['view'] === 'edit' || $_GET['view'] === 'details' || $_GET['view'] === 'print')
	) {
		$entry = wpforms()->entry->get( $_GET['entry_id'] );
		write_entry_log($entry, 'wpform_data_' . $_GET['view'] . '_details');
	}
}

function wpform_data_read_payment_function() {
	if (
		isset( $_GET['page'], $_GET['view'], $_GET['payment_id'] ) &&
		$_GET['page'] === 'wpforms-payments' &&
		($_GET['view'] === 'payment')
	) {
		$payment = wpforms()->payment->get( $_GET['payment_id']);
		$entry = wpforms()->entry->get( $payment->entry_id );
		write_entry_log( $entry,'wpform_data_read_details_payment');
	}
}

function wpform_data_delete_payment_function() {
	if (
		isset( $_GET['page'], $_GET['action'], $_GET['payment_id'] ) &&
		$_GET['page'] === 'wpforms-payments' &&
		($_GET['action'] === 'delete')
	) {
		$payment = wpforms()->payment->get( $_GET['payment_id']);
		$entry = wpforms()->entry->get( $payment->entry_id );
		write_entry_log( $entry,'wpform_data_delete_details_payment');
	}
}

function wpforms_handle_entry_action() {
	if (empty($_GET['page']) || $_GET['page'] !== 'wpforms-entries') {
		return;
	}

	$view       = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '';
	$action     = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'read';
	$status     = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
	$type       = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
	$entry_id   = isset($_GET['entry_id']) ? absint($_GET['entry_id']) : 0;
	$payment_id = isset($_GET['payment_id']) ? absint($_GET['payment_id']) : 0;

	if (!$entry_id && !$payment_id) {
		return;
	}

	$entry   = $entry_id ? wpforms()->entry->get($entry_id) : null;
	$payment = $payment_id ? wpforms()->payment->get($payment_id) : null;

	if ($payment && !$entry && !empty($payment->entry_id)) {
		$entry = wpforms()->entry->get($payment->entry_id);
	}
	if ($entry && !empty($entry->payment_id) && !$payment) {
		$payment = wpforms()->payment->get($entry->payment_id);
	}
	if ($entry && $payment) {
		$entry->payment = $payment;
	}

	$log_key = 'wpform_data_' . str_replace('_', '-', $action) . '_details_' . $type;
	if ($entry && $log_key) {
		write_entry_log($entry, $log_key);
	}
}

function wpforms_data_edit_function() {
	if (
		isset( $_POST['action'], $_POST['wpforms'] ) &&
		$_POST['action'] === 'wpforms_submit' && isset($_POST['wpforms']['entry_id'])
	) {
		write_entry_log($_POST['wpforms'], 'wpform_data_edit_details');
	}
}

function wpform_data_submit_details( $fields, $entry, $form_data, $entry_id ) {
	$entry['entry_id'] = $entry_id;
	write_entry_log($entry, 'wpform_data_submit_details');
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
		'details' => 'read'
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

function callOPDo($payload, $action) {
	$user = wp_get_current_user();
	$url = getenv('OPDO_URL');

	$data = [
		"@timestamp" => (new DateTime())->format(DateTime::ATOM),
		"crudt" => $action,
		"handled_id" => $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		"handler_id" => $user->user_email,
		"source" => 'wordpress',
		"payload" => $payload
	];

	error_log(substr(var_export($data, true), 0, 1024));

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
