<?php
/**
 * EPFL Sitemap
 *
 * @package     EPFLSitemap
 * @author      ISAS-FSD
 * @copyright   Copyright (c) 2025, EPFL
 * @license     GPL-2.0-or-later
 *
 * @wordpress-mu-plugins
 * Must Use Plugin Name: EPFL Sitemap
 * Plugin URI:  https://github.com/epfl-si/wp-mu-plugins
 * Description: Give the sitemap for www.epfl.ch in xml format
 * Version:     1.0.0
 * Author:      ISAS-FSD
 * Author URI:  https://go.epfl.ch/idev-fsd
 * Text Domain: EPFL-Sitemap
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 */

namespace EPFL_sitemap;

function print_sitemap() {
  $menu_api_host = getenv('MENU_API_HOST') ?: "menu-api";

  $curl = curl_init('http://' . $menu_api_host . ':3001/getSitemap');
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($curl);
  if (curl_errno($curl)) {
    $error_text = curl_error($curl);
  }
  curl_close($curl);

  if (isset($error_text)) {
    fatal('curl_error', $error_text);
  } elseif ($response === false || trim($response) == '') {
	  fatal('no_data', 'Failed to retrieve data from the API.');
  } else {
    $xml = simplexml_load_string($response);
    if ($xml === false) {
		fatal('invalid_xml', 'Invalid XML received from API');
    }
    header('Content-Type: application/xml; charset=UTF-8');
    echo $xml->asXML();
  }
}

function fatal ($error_short, $details) {
	$error = "$error_short: $details";
	error_log($error);
	http_response_code(500);
	print($error);
	die();
}

add_action( 'parse_request', function( $enabled ) {
  if ( isset( $_SERVER['REQUEST_URI'] ) && $_SERVER['REQUEST_URI'] === '/sitemap.xml' ) {
      print_sitemap();
    die();
  }
});
