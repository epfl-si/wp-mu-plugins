<?php
  /**
   * Remove the Site Health features (dashboard and menu)
   */

add_action("wp_dashboard_setup", function() {
    global $wp_meta_boxes;
    unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_site_health'] );
});


add_action("admin_menu", function() {
    remove_submenu_page( 'tools.php', 'site-health.php' );
});
