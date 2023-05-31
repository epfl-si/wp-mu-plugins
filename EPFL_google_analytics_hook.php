<?php
/*
 * Plugin Name: EPFL Google Analytics connector
 * Plugin URI:
 * Description: Must-use plugin for the EPFL website.
 * Version: 2.0
 * Author: wwp-admin@epfl.ch
 * */

/*
 * Hook that add the Google Analytics header to all pages
 * By default, add the script for the Id to the Google Tag Manager.
 * Per site, it's possible to add a custom tag. If this is set, add the script for the Id to the Google Analytics Tag
 */

function google_analytics_connector_render() { ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
      new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-PJGBG5R');</script>
<!-- End Google Tag Manager -->
<?php

	$additional_id = get_option('epfl_google_analytics_id');
	if (!empty($additional_id)): ?>
    <!-- Global Site Tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $additional_id; ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag("js", new Date());
      gtag("config", "<?php echo $additional_id; ?>", { "anonymize_ip": true });
    </script>
<?php endif;
}
add_action('wp_head', 'google_analytics_connector_render', 10);

function google_analytics_body_connector_render() {
?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PJGBG5R" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php
}

add_action( 'wp_body_open', 'google_analytics_body_connector_render' );
?>
