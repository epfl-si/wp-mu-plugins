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

/*
add_action( 'admin_init', 'wpforms_export_all_function' );

function wpforms_export_all_function() {
	if (
		isset( $_GET['page'], $_GET['view'], $_GET['action'] ) &&
		$_GET['page'] === 'wpforms-tools' &&
		$_GET['view'] === 'export' &&
		$_GET['action'] === 'wpforms_tools_entries_export_download'
	) {
		//https://wordpress.localhost/wp-admin/admin.php?page=wpforms-tools&view=export&action=wpforms_tools_entries_export_download&nonce=066e6b1030&request_id=6f691964cbf467fe643478e8f0631620
		error_log("TEST {$_GET['page']} - {$_GET['view']} - {$_GET['action']}");
		$form_id = isset( $_GET['form'] ) ? intval( $_GET['form'] ) : 0;
		$form = wpforms()->form->get( $form_id );
		if ( ! empty( $form ) ) {
			$form_data = wpforms_decode( $form->post_content );
			$form_title = isset( $form_data['settings']['form_title'] ) ? $form_data['settings']['form_title'] : '';
			$log_entry = sprintf(
				"ALL entries has been exported for form %s (ID: %d)",
				$form_title,
				$form_id
			);
			error_log( $log_entry );
		}
	}
}*/

add_action( 'simple_history/log/inserted', 'callOPDo', 10, 2 );

// TODO wpform, redirections

function callOPDo($insert_id, $context) {
	// Locally
	if (!getenv('OPDO_URL')) return;

	$payload = array(
		'log_id'   => $insert_id,
		'message'  => isset( $context['message'] ) ? $context['message'] : '',
		'context'  => $context,
		'site_url' => get_site_url(),
		'timestamp' => current_time( 'mysql' ),
	);

	$user = wp_get_current_user();
	$url = getenv('OPDO_URL');

	$data = [
		"@timestamp" => (new DateTime())->format(DateTime::ATOM),
		"crudt" => $payload['log_id']['_message_key'],
		"handled_id" => $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		"handler_id" => $user->user_email,
		"payload" => var_export($payload, true),
		"source" => 'wordpress'
	];
	error_log(var_export($data, true));

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
