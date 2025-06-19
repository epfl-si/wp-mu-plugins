<?php

namespace WP_Operator_Status_Plugins;

use stdClass;

add_filter('wp_operator_status', '\WP_Operator_Status_Plugins\report_plugins');
add_filter('wp_operator_status', '\WP_Operator_Status_Plugins\report_languages');
add_filter('wp_operator_status', '\WP_Operator_Status_Plugins\report_tagline');
add_filter('wp_operator_status', '\WP_Operator_Status_Plugins\report_title');
add_filter('wp_operator_status', '\WP_Operator_Status_Plugins\report_unit');

function report_plugins ($status) {
	$status['plugins'] = [];
	foreach (get_option( 'active_plugins', array() ) as $plugin) {
		$status['plugins'][dirname($plugin)] = new stdClass;
	}
	return $status;
}

function report_languages ($status) {
	$status['languages'] = [];
	if (function_exists('pll_languages_list')) {
		$languages = pll_languages_list( ['fields' => 'locale'] );

		foreach ($languages as $lang) {
			$status['languages'][] = $lang;
		}
	}
	return $status;
}

function report_tagline ($status) {
	$status['tagline'] = get_bloginfo('description');
	return $status;
}

function report_title ($status) {
	$status['title'] = get_bloginfo('title');
	return $status;
}

function report_unit ($status) {
	$status['unit'] = get_option('plugin:epfl_accred:unit_id');
	return $status;
}
