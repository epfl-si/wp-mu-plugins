<?php

namespace WP_Operator_Status_Plugins;

add_filter("wp_operator_status", '\WP_Operator_Status_Plugins\report_plugins');

function report_plugins ($status) {
  $status["plugins"] = [];
  foreach (get_option( 'active_plugins', array() ) as $plugin) {
    $status["plugins"][$plugin] = new stdClass;
  }
  return $status;
}
