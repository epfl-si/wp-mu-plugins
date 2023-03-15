<?php
/*
Plugin Name: “WPLastPageChanges”
Description: Gives the name of the last user who modified a given page.
Version: 0.0.1
Author: Jérôme Cosandey
Author URI: https://github.com/saphirevert
*/

function getLastChange( $data ){
    global $wpdb;
    $url = $data->get_param( 'url' );
    $postId = url_to_postid($url);
    $sql = $wpdb->prepare( "SELECT wp_users.user_login AS username, post_modified AS last_modified FROM `wp_posts` 
                            LEFT JOIN wp_users ON wp_users.ID = wp_posts.post_author
                            WHERE post_parent=$postId AND post_status!='publish' ORDER BY wp_posts.post_modified DESC LIMIT 1;");
    $results = $wpdb->get_results( $sql );
    return $results;
}

function getLastRevisions( $data ){
    global $wpdb;
    $name = $data->get_param( 'name' );
    $name = $name ? $name : '';
    $limit = $data->get_param( 'limit' );
    $limit = $limit ? $limit : 5;
    $sql = $wpdb->prepare( "SELECT wp_posts.post_title, wp_users.user_login AS username, post_modified AS last_modified FROM `wp_posts` 
                            LEFT JOIN wp_users ON wp_users.ID = wp_posts.post_author 
                            WHERE wp_posts.post_title LIKE '%$name%' ORDER BY wp_posts.post_modified DESC LIMIT $limit;");
    $results = $wpdb->get_results( $sql );
    // return $wpdb->print_error(); 
    return $results;
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'wp/v2', '/lastmodified', array(
    'methods' => 'GET',
    'callback' => 'getLastChange',
  ) );
} );
