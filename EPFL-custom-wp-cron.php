<?php
/**
 * Plugin Name: WordPress custom wp-cron
 * Description: Unschedule and block unwanted cron events
 * Version: 0.1
 * Author: ISAS-FSD <isas-fsd@groupes.epfl.ch>
 * License: Copyright (c) 2025 Ecole Polytechnique Federale de Lausanne, Switzerland
 **/

// Unschedule all events attached to unwanted hooks
add_action('init', function () {
    $unwanted_hooks = [
        'wp_version_check',
        'wp_update_plugins',
        'wp_update_themes',
    ];

    foreach ($unwanted_hooks as $hook) {
        error_log("YY: wp_clear_scheduled_hook for: " . $hook);
        $result = wp_clear_scheduled_hook($hook);

        if (is_wp_error($result)) {
            error_log("YY: Error clearing hook: " . $result->get_error_message());
        } elseif ($result === false) {
            error_log("YY: Failed to clear hook, returned false.");
        } elseif ($result === 0) {
            error_log("YY: No events found.");
        } else {
            error_log("YY: Cleared {$result} event(s).");
        }
    }
});

// Prevent from being pre scheduled
add_filter('pre_schedule_event', function ($pre, $event) {
    $unwanted_hooks = [
        'wp_version_check',
        'wp_update_plugins',
        'wp_update_themes',
    ];

    error_log("YY: pre_schedule_event_fired ");
    error_log("→ hook: " . $event->hook);

    if (in_array($event->hook, $unwanted_hooks, true)) {
        error_log("Block schedule hook: " . $event->hook);
        return false;
    }

    return $pre;
}, 10, 2);

// Prevent from being pre rescheduled
add_filter('pre_reschedule_event', function ($pre, $event) {
    $unwanted_hooks = [
        'wp_version_check',
        'wp_update_plugins',
        'wp_update_themes',
    ];

    error_log("YY: pre_reschedule_event_fired ");
    error_log("→ hook: " . $event->hook);

    if (in_array($event->hook, $unwanted_hooks, true)) {
        error_log("Block reschedule hook: " . $event->hook);
        return false;
    }

    return $pre;
}, 10, 2);
