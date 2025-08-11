<?php
/*
Plugin Name: EPFL sitemap
Description: Give the sitemap for www.epfl.ch in xml format.
Version: 1.0.0
Author: ISAS-FSD
*/

function getSitemap() {
    $menu_api_host = "menu-api";
    $menu_api_host_from_env = getenv('MENU_API_HOST');
    if ($menu_api_host_from_env !== false && $menu_api_host_from_env !== '') {
        $menu_api_host = $menu_api_host_from_env;
    }
    $url_api = 'http://' . $menu_api_host . ':3001/getSitemap';

    $curl = curl_init($url_api);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        $error_text = curl_error($curl);
    }
    curl_close($curl);

    if (isset($error_text)) {
        error_log( "curl error: {$error_text} at {$url_api}" );
        return NULL;
    } elseif ($response === false || $response == '') {
        error_log( 'Failed to retrieve data from the API.' );
        return NULL;
    } else {
        $xml = simplexml_load_string($response);
        echo $xml->asXML();
        exit; //Avoid wordpress do JSON.encode on the result
    }
};

add_action( 'rest_api_init', function () {
  register_rest_route( 'epfl/v1', '/sitemap', array(
    'methods' => 'GET',
    'callback' => 'getSitemap',
  ));
});
