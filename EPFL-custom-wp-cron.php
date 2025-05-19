<?php
/**
 * Plugin Name: WordPress custom wp cron
 * Description: Unschedule and block unwanted cron events
 * Version: 0.1
 * Author: ISAS-FSD <isas-fsd@groupes.epfl.ch>
 * License: Copyright (c) 2025 Ecole Polytechnique Federale de Lausanne, Switzerland
 **/

namespace WP_CustomWPCron;

function get_unwanted_hooks() {
    return [
        'check_plugin_updates-wp-media-folder',
        'mainwp_child_cron_plugin_health_check_daily',
        'mainwp_child_cron_theme_health_check_daily',
        'wp_version_check',
        'wp_update_plugins',
        'wp_update_themes',
    ];
}

// Prevent from being pre scheduled
add_filter('pre_schedule_event', function ($pre, $event) {
    if (in_array($event->hook, get_unwanted_hooks(), true)) {
        return false;
    }
    return $pre;
}, 10, 2);

// Prevent from being pre rescheduled
add_filter('pre_reschedule_event', function ($pre, $event) {
    if (in_array($event->hook, get_unwanted_hooks(), true)) {
        return false;
    }
    return $pre;
}, 10, 2);


////////////////////////////////////////////////////////////////////////
/**
 * clear_unwanted_hooks: the wp cron hook for clearing unwanted hooks
 */
add_action('clear_unwanted_hooks', '\WP_CustomWPCron\clear_unwanted_hooks');

if (PHP_SAPI === 'cli') {
    if (!wp_next_scheduled('clear_unwanted_hooks')) {
        wp_schedule_event(time(), 'hourly', "clear_unwanted_hooks");
    }
}

function clear_unwanted_hooks() {
    foreach (get_unwanted_hooks() as $hook) {
        if (wp_next_scheduled($hook)) {
            $result = wp_clear_scheduled_hook($hook);
        }
    }
}
