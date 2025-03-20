<?php
/*
* Plugin Name: EPFL custom editor role menu
* Plugin URI:
* Description: Must-use plugin for the EPFL website.
* Version: 1.3.0
* Author: wwp-admin@epfl.ch
 */



require (WPMU_PLUGIN_DIR.'/epfl-custom-editor-menu/epfl-custom-editor-menu.php');


function remove_redirection_from_tools_menu_for_editor() {
    $user = wp_get_current_user();
    if (!in_array('administrator', $user->roles)) {
        remove_submenu_page('tools.php', 'redirection.php');
    }
}

add_action('admin_menu', 'remove_redirection_from_tools_menu_for_editor', 999);
