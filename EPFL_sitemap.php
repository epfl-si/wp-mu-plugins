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
 * EPFL Sitemap is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * EPFL Sitemap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EPFL Sitemap. If not, see <http://www.gnu.org/licenses/>.
 */

function getSitemap() {
  $menu_api_host = getenv('MENU_API_HOST') ?: "menu-api";

  $curl = curl_init('http://' . $menu_api_host . ':3001/getSitemap');
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($curl);
  if (curl_errno($curl)) {
    $error_text = curl_error($curl);
  }
  curl_close($curl);

  if (isset($error_text)) {
    return new WP_Error('curl_error', $error_text, array('status' => 500));
  } elseif ($response === false || trim($response) == '') {
    return new WP_Error('no_data', 'Failed to retrieve data from the API.', array('status' => 500));
  } else {
    $xml = simplexml_load_string($response);
    if ($xml === false) {
      return new WP_Error('invalid_xml', 'Invalid XML received from API', array('status' => 500));
    }
    header('Content-Type: application/xml; charset=UTF-8');
    echo $xml->asXML();
  }
}

add_action( 'parse_request', function( $enabled ) {
  if ( isset( $_SERVER['REQUEST_URI'] ) && $_SERVER['REQUEST_URI'] === '/sitemap.xml' ) {
    getSitemap();
    die();
  }
});
