<?php
/*
 * Plugin Name: EPFL Functions
 * Plugin URI:
 * Description: Must-use plugin for the EPFL website.
 * Version: 1.2.2
 * Author: wwp-admin@epfl.ch
 */


add_filter('robots_txt', 'get_robots_txt');

/**
 * Returns the content of robots.txt. We override the one
 * coming by default to add wp-login.php.
 */
function get_robots_txt($original) {

    $text = "User-agent: *
Disallow: /wp-login.php
Disallow: /wp-admin/
";

    return $text;
}

/*
 * File Upload Security

 * Sources:
 * http://www.geekpress.fr/wordpress/astuce/suppression-accents-media-1903/
 * https://gist.github.com/herewithme/7704370

 * See also Ticket #22363
 * https://core.trac.wordpress.org/ticket/22363
 * and #24661 - remove_accents is not removing combining accents
 * https://core.trac.wordpress.org/ticket/24661
*/

add_filter( 'sanitize_file_name', 'remove_accents', 10, 1 );
add_filter( 'sanitize_file_name_chars', 'sanitize_file_name_chars', 10, 1 );

function sanitize_file_name_chars( $special_chars = array() ) {
	$special_chars = array_merge( array( '’', '‘', '“', '”', '«', '»', '‹', '›', '—', 'æ', 'œ', '€','é','à','ç','ä','ö','ü','ï','û','ô','è' ), $special_chars );
	return $special_chars;
}


/*--------------------------------------------------------------

 # REST API

--------------------------------------------------------------*/

/*
 * Disable display list of users from /wp-json/wp/v2/users/
 */
add_filter( 'rest_endpoints', function( $endpoints ){
        if ( isset( $endpoints['/wp/v2/users'] ) ) {
                    unset( $endpoints['/wp/v2/users'] );
                    }
            if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
                    unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
                    }
            return $endpoints;
});


/*--------------------------------------------------------------

 # Content improvements

--------------------------------------------------------------*/

/*
 * Remove empty <p> tags
 */

add_filter( 'the_content', 'remove_empty_p', 20, 1 );
function remove_empty_p( $content ){
// clean up p tags around block elements
$content = preg_replace( array(
  '#<p>\s*<(div|aside|section|article|header|footer)#',
  '#</(div|aside|section|article|header|footer)>\s*</p>#',
  '#</(div|aside|section|article|header|footer)>\s*<br ?/?>#',
  '#<(div|aside|section|article|header|footer)(.*?)>\s*</p>#',
  '#<p>\s*</(div|aside|section|article|header|footer)#',
  ), array(
  '<$1',
  '</$1>',
  '</$1>',
  '<$1$2>',
  '</$1',
  ), $content );

return preg_replace('#<p>(\s|&nbsp;)*+(<br\s*/*>)*(\s|&nbsp;)*</p>#i', '', $content);
}


/*--------------------------------------------------------------

 # Gallery improvements

--------------------------------------------------------------*/

/*
 * Add the title of an image to it's anchor in WP galleries
 */

function add_title_attachment_link($link, $id = null) {
	$id = intval( $id );
	$_post = get_post( $id );
	$post_title = esc_attr( $_post->post_title );
	return str_replace('<a href', '<a title="'. $post_title .'" href', $link);
}
add_filter('wp_get_attachment_link', 'add_title_attachment_link', 10, 2);

/*
 * Link to large instead of full size images in galleries
 * http://oikos.org.uk/2011/09/tech-notes-using-resized-images-in-wordpress-galleries-and-lightboxes/
 */

function oikos_get_attachment_link_filter( $content, $post_id, $size, $permalink ) {

    // Only do this if we're getting the file URL
    if (! $permalink) {
        // This returns an array of (url, width, height)
        $image = wp_get_attachment_image_src( $post_id, 'large' );
        $new_content = preg_replace('/href=\'(.*?)\'/', 'href=\'' . $image[0] . '\'', $content );
        return $new_content;
    } else {
        return $content;
    }
}

add_filter('wp_get_attachment_link', 'oikos_get_attachment_link_filter', 10, 4);


/*--------------------------------------------------------------

 # Custom post types

--------------------------------------------------------------*/



/*--------------------------------------------------------------

 # File upload extension whitelist

--------------------------------------------------------------*/
function epfl_mimetypes($mime_types){

    /* Extensions and Mimes types can be found here:
    https://www.lifewire.com/mime-types-by-content-type-3469108
    */

    $mime_types['ppd'] = 'application/vnd.cups-ppd'; //Adding ppd extension
    $mime_types['tex'] = 'application/x-tex'; //Adding tex extension
    return $mime_types;
}
add_filter('upload_mimes', 'epfl_mimetypes', 1, 1);


/*--------------------------------------------------------------

 # Shortcodes

--------------------------------------------------------------*/

/**
 * Create custom shortcodes
 *
 * @link https://codex.wordpress.org/Shortcode_API
 */

// Désactive wpautop

function remove_wpautop($content) {
  $content = do_shortcode( shortcode_unautop($content) );
  $content = preg_replace( '#^<\/p>|^<br \/>|<p>$#', '', $content );
  return $content;
}

// Publications

function content_publication_list( $atts, $content = null ) {
  $return = '<section class="publications clearfix">';
  $return .= do_shortcode($content);
  $return .= '</section>';
  return $return;
}
add_shortcode('list-publications', 'content_publication_list');

function content_publication( $atts, $content = null ) {
  $return = '<article class="publication clearfix">';
  $return .= do_shortcode($content);
  $return .= '</article>';
  return $return;
}
add_shortcode('publication', 'content_publication');

function links( $atts, $content = null ) {
  $return = '<p class="links">';
  $return .= do_shortcode(remove_wpautop($content));
  $return .= '</p>';
  return $return;
}
add_shortcode('links', 'links');

function faq_item( $atts, $content = null ) {
  $a = shortcode_atts( array(
        'title' => 'Title',
    ), $atts );
  $return = '<section class="faq-item"><h3 class="title faq-title" id="">' . esc_attr( $a['title'] ) . '</h3><div class="content">';
  $return .= do_shortcode($content);
  $return .= '</div></section>';
  return $return;
}
add_shortcode('faq-item', 'faq_item');

function colored_box( $atts, $content = null ) {
  $return = '<section class="colored-box">';
  $return .= do_shortcode($content);
  $return .= '</section>';
  return $return;
}
add_shortcode('colored-box', 'colored_box');


/*--------------------------------------------------------------

 # CloudFlare

--------------------------------------------------------------*/

/* CloudFlare doesn't like the Polylang cookie (or any cookie);
 * however, we still want the homepage to use it (and bypass all
 * caches). */
$current_url = array_key_exists('SCRIPT_URL', $_SERVER)? $_SERVER["SCRIPT_URL"] : "" ;
if ($current_url != "/") {
    define('PLL_COOKIE', false);
}

/*
    If we have 302 redirection on local address, we transform them to 303 to avoid CloudFlare to cache
    them. If we don't do this, we have issues to switch from one language to another (Polylang) because the
    first time we visit the homepage, it does a 302 to default lang homepage and this request is cached in cloudflare
    so it's impossible to switch to the other language
*/
function http_status_change_to_non_cacheable($status, $location) {
      /* We update header to avoid caching when using 302 redirect on local host */
   if($status==302 && strpos($location, $_SERVER['SERVER_NAME'])!==false)
   {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
   }
   return $status;
}
add_filter( 'wp_redirect_status', 'http_status_change_to_non_cacheable', 10, 2);


function allow_svg_in_tinymce( $init ) {
    /* Code taken from here : https://gist.github.com/Kelderic/f092abf13d5373f245f90ab42e7f885d and
    'script' tag has been removed for security reasons */

	$svgElemList = array(
		'a',
		'altGlyph',
		'altGlyphDef',
		'altGlyphItem',
		'animate',
		'animateColor',
		'animateMotion',
		'animateTransform',
		'circle',
		'clipPath',
		'color-profile',
		'cursor',
		'defs',
		'desc',
		'ellipse',
		'feBlend',
		'feColorMatrix',
		'feComponentTransfer',
		'feComposite',
		'feConvolveMatrix',
		'feDiffuseLighting',
		'feDisplacementMap',
		'deDistantLight',
		'feFlood',
		'feFuncA',
		'feFuncB',
		'feFuncG',
		'feFuncR',
		'feGaussianBlur',
		'feImage',
		'feMerge',
		'feMergeNode',
		'feMorphology',
		'feOffset',
		'fePointLight',
		'feSpecularLighting',
		'feSpotLight',
		'feTile',
		'feTurbulance',
		'filter',
		'font',
		'font-face',
		'font-face-format',
		'font-face-name',
		'font-face-src',
		'font-face-url',
		'foreignObject',
		'g',
		'glyph',
		'glyphRef',
		'hkern',
		'image',
		'line',
		'lineGradient',
		'marker',
		'mask',
		'metadata',
		'missing-glyph',
		'pmath',
		'path',
		'pattern',
		'polygon',
		'polyline',
		'radialGradient',
		'rect',
		'set',
		'source',
		'stop',
		'style',
		'svg',
		'switch',
		'symbol',
		'text',
		'textPath',
		'time',
		'title',
		'tref',
		'tspan',
		'use',
		'view',
        'vkern'
	);

	// extended_valid_elements is the list of elements that TinyMCE allows. This checks
	// to make sure it exists, and then implodes the SVG element list and adds it. The
	// format of each element is 'element[attributes]'. The array is imploded, and turns
	// into something like '...svg[*],path[*]...'

	if ( !isset( $init['extended_valid_elements'] ) ) {
	    $init['extended_valid_elements'] = "";
	}
	else
	{
		$init['extended_valid_elements'] .= ",";
	}
	$init['extended_valid_elements'] .= implode('[*],',$svgElemList).'[*]';


	// return value
	return $init;
}
add_filter('tiny_mce_before_init', 'allow_svg_in_tinymce');


/*
    Add tags present in HTML styleguide (https://epfl-si.github.io/elements/#/) to allow users to use them directly
    in "Text Editor"
*/
function epfl_2018_add_allowed_tags($tags)
{

    /* We extend needed attributes */
    $tags['button']['data-toggle'] = true;
    $tags['button']['data-target'] = true;
    $tags['button']['data-dismiss'] = true;
    $tags['button']['data-content'] = true;
    $tags['button']['aria-expanded'] = true;
    $tags['button']['aria-controls'] = true;
    $tags['button']['aria-label'] = true;
    $tags['button']['aria-haspopup'] = true;
    $tags['button']['aria-hidden'] = true;

    $tags['span']['aria-hidden'] = true;
    $tags['span']['aria-label'] = true;
    $tags['span']['itemprop'] = true;
    $tags['span']['content'] = true;

    $tags['div']['aria-expanded'] = true;
    $tags['div']['aria-labelledby'] = true;
    $tags['div']['itemprop'] = true;
    $tags['div']['itemscope'] = true;
    $tags['div']['itemtype'] = true;

    $tags['a']['data-toggle'] = true;
    $tags['a']['aria-hidden'] = true;
    $tags['a']['aria-controls'] = true;
    $tags['a']['aria-selected'] = true;
    $tags['a']['aria-label'] = true;
    $tags['a']['aria-haspopup'] = true;
    $tags['a']['aria-expanded'] = true;
    $tags['a']['aria-describedby'] = true;
    $tags['a']['tabindex'] = true;
    $tags['a']['accesskey'] = true;
    $tags['a']['itemprop'] = true;
    $tags['a']['data-page-id'] = true;

    $tags['table']['data-tablesaw-mode'] = true;

    $tags['img']['aria-labelledby'] = true;

    $tags['figure']['itemprop'] = true;
    $tags['figure']['itemscope'] = true;
    $tags['figure']['itemtype'] = true;

    $tags['strong']['itemprop'] = true;

    $tags['p']['itemprop'] = true;
    $tags['p']['itemscope'] = true;
    $tags['p']['itemtype'] = true;

    $tags['nav']['aria-label'] = true;
    $tags['nav']['aria-labelledby'] = true;
    $tags['nav']['aria-describedby'] = true;

    $tags['li']['aria-current'] = true;

    $tags['ul']['aria-hidden'] = true;

    /* Some tags are not present in WordPress 4.9.8 so we add them if necessary. Code is done to be compatible if
    tags are added in a future WordPress version */

    if(!array_key_exists('svg', $tags)) $tags['svg'] = [];
    $tags['svg']['class'] = true;
    $tags['svg']['aria-hidden'] = true;

    if(!array_key_exists('use', $tags)) $tags['use'] = [];
    $tags['use']['xlink:href'] = true;

    if(!array_key_exists('time', $tags)) $tags['time'] = [];
    $tags['time']['datetime'] = true;

    if(!array_key_exists('source', $tags)) $tags['source'] = [];
    $tags['source']['media'] = true;
    $tags['source']['srcset'] = true;


    if(!array_key_exists('picture', $tags)) $tags['picture'] = [];


    return $tags;
}
add_filter('wp_kses_allowed_html', 'epfl_2018_add_allowed_tags');


/*
    Deregister all styles which are not necessary for visitor pages

    Based on information found here (section "Disable Plugin Stylesheets in WordPress"):
    https://www.wpbeginner.com/wp-tutorials/how-wordpress-plugins-affect-your-sites-load-time/

    But there's a mistake in the procedure. The CSS ids cannot be used directly to do the job, you have
    to remove the "-css" at the end because it is automatically added by WordPress but the initial
    name used to register style. And to deregister, you have to use the name used to register it
*/
function epfl_deregister_visitor_styles()
{
    if(!is_admin())
    {

        wp_dequeue_style( 'varnish_http_purge' );
        wp_deregister_style( 'varnish_http_purge' );

        wp_dequeue_style( 'wpmf-material-design-iconic-font.min' );
        wp_deregister_style( 'wpmf-material-design-iconic-font.min' );
    }
}
add_action( 'wp_enqueue_scripts', 'epfl_deregister_visitor_styles', 100 );

/*--------------------------------------------------------------

 # Optimize REST pages crawling

--------------------------------------------------------------*/

/**
 * This class unallow fetching all pages (per_page = 100) with the content field,
 * as it is too intensive for most cases.
 * The main initiator of this need was to fixes the loading of the combox Parent Page in Gutenberg
 * See https://github.com/WordPress/gutenberg/issues/13991
 */
class WP_REST_Posts_Controller_Limiter extends \WP_REST_Posts_Controller {
    public function get_fields_for_response( $request ) {
        $fields = parent::get_fields_for_response( $request);

        if (isset($request['context']) && isset($request['per_page']) &&
            $request['context'] == 'edit' && $request['per_page'] >= 100) {
            # Don't generate content when we are listing a lot of pages
            if (($key = array_search('content', $fields)) !== false) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }
}

function no_content_for_all_pages_crawling_trough_api($args, $post_type){
    # set our custom class for pages
    if ($post_type == 'page'){
        $args['rest_controller_class'] = __NAMESPACE__ . '\WP_REST_Posts_Controller_Limiter';
    }

    return $args;
}

add_filter('register_post_type_args', __NAMESPACE__ . '\no_content_for_all_pages_crawling_trough_api', 10, 2);

# Enlighter plugin show is "About page" everytime an update is done
# this fix this information that we don't want to show to the user
add_filter( 'option_enlighter-activation-redirect', function( $value ) {
    return '';
});

# Deactivate the "Install wizard" for WP Media folder
add_filter('wpmf_user_can', function( $default_value, $action ) {
    if ($action === 'first_install_plugin') {
        return False;
    } else {
        return $default_value;
    }
  }, 10, 2);

/**
 * As we use the pdf viewer plugin and it force the usage of the default "old" jquery from Wordpress on all pages
 * https://github.com/audrasjb/pdf-viewer-block/blob/6bc718b251a6623f2fe0cb68ca37c8037d35c884/public/public.php#L24
 * Make the enqueue with our jquery
 */
function remove_old_jquery_for_pdf_viewer() {

    # if 'pdf-viewer-block-scripts' is here (the one with the jquery dep)
    if (wp_script_is('pdf-viewer-block-scripts') && wp_script_is('epfl-js-jquery') ){
        global $wp_scripts;

        if (isset($wp_scripts->registered['pdf-viewer-block-scripts'])) {
            foreach ($wp_scripts->registered['pdf-viewer-block-scripts']->deps as &$dep) {
                if ($dep === 'jquery') {
                    $dep = 'epfl-js-jquery';
                }
            }
        }
    }
}

add_action( 'wp_enqueue_scripts', 'remove_old_jquery_for_pdf_viewer', 9999);

/**
 * Disable the REST API for unlogged users.
 *
 * For the correct operation of the menu, the following entry points are always accessible:
 * /wp-json/epfl/v1/languages
 * /wp-json/epfl/v1/menus/top
 * /wp-json/wp/v2/epfl-external-menu
 */
function disable_rest_api_for_unlogged_users($access) {
    if (is_user_logged_in()) { return $access; }
    if (strpos($_SERVER['REQUEST_URI'], 'wp-json/epfl') !== false) { return $access; }
    if (strpos($_SERVER['REQUEST_URI'], 'epfl-external-menu') !== false) { return $access; }
    // This is guaranteed (by Kubernetes) to be traffic coming from the same namespace
    // only.
    if ($_SERVER['SERVER_PORT'] == 8443) { return $access; }

    return new WP_Error(
        'rest_cannot_access',
        __('Only authenticated users can access the REST API.', 'disable-json-api'),
        array('status' => rest_authorization_required_code() ));
}

add_filter( 'rest_authentication_errors', 'disable_rest_api_for_unlogged_users' );

function do_bypass_rest_api_auth( $user_id ) {

    global $bypass_rest_api_auth;
    $bypass_rest_api_auth = false;

    // https://wordpress.stackexchange.com/a/131816
    $is_admin = array_intersect( array('administrator'), (array) get_user_by('id', $user_id)->roles );

    // user has admin capabilities
    if ( $is_admin ) {
        $bypass_rest_api_auth = true;
        return get_user_by('id', $user_id)->ID;
    }

    // user is not logged in, but request is cluster internal: force admin user
    if ( $_SERVER['SERVER_PORT'] == 8443 ) {
        $bypass_rest_api_auth = true;
        return get_user_by('login', 'admin')->ID;
    }

    // every other case just continue with user id
    return $user_id;
}
add_filter( 'determine_current_user', 'do_bypass_rest_api_auth', 20 );

function json_basic_auth_error( $error ) {
    // Passthrough other errors
    if ( ! empty( $error ) ) {
        return $error;
    }
    global $bypass_rest_api_auth;
    return $bypass_rest_api_auth;
}
add_filter( 'rest_authentication_errors', 'json_basic_auth_error' );

/**
 * Adds meta tags for social network
 */
function social_network_meta_tags()
{
    global $wp;
    ?>
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?php the_title(); ?>" />
    <?php if ( get_the_excerpt() ) : ?>
    <meta property="og:description" content="<?php the_excerpt(); ?>" />
    <?php endif; ?>
    <meta property="og:url" content="<?php echo  "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" />
    <?php if ( get_the_post_thumbnail() ) :
    $image_data_wh = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'large' );
    ?>
    <meta property="og:image:width" content="<?php echo $image_data_wh[1]; ?>" />
    <meta property="og:image:height" content="<?php echo $image_data_wh[2]; ?>" />
    <meta property="og:image" content="<?php echo get_the_post_thumbnail_url( get_the_ID(), 'large' ); ?>" />
    <?php endif; ?>

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php the_title(); ?>" />
    <?php if ( get_the_excerpt() ) : ?>
    <meta name="twitter:description" content="<?php the_excerpt(); ?>" />
    <?php endif; ?>
    <?php if ( get_the_post_thumbnail() ) : ?>
    <meta name="twitter:image" content="<?php echo get_the_post_thumbnail_url( get_the_ID(), 'large' ); ?>" />
    <?php endif; ?>

    <?php

}

add_action('wp_head','social_network_meta_tags');

use EPFL\Pod\Site;

/**
 * Try loading the assets url from the root site/pod,
 * so the user's browser cache (identified by the url) is used
 */
function rewrite_uri_to_root_in_www_theme_2018($template_dir_uri, $template, $theme_root_uri) {
    if ($template !== "wp-theme-2018") {
        return  $template_dir_uri;
    }
    # Bail out unless epfl-menus is installed (i.e. on www.epfl.ch)
    if (!class_exists('\\EPFL\\Pod\\Site')) {
        # nope, better cancel now
        return $template_dir_uri;
    }

    $wp_symlink_path = dirname(dirname(dirname(get_template_directory()))) . "/wp";
    if (! ($wp_version = readlink($wp_symlink_path))) {
        return $template_dir_uri;
    }

    $path_under_htdocs = Site::this_site()->path_under_htdocs;

    # Rewrite only URLs that point under the theme
    if (empty($path_under_htdocs)) {
        # on root sites, we want to rewrite only the theme path
        $rewrite_from = '/wp-content/themes/';
    } else {
        $rewrite_from = '/' . $path_under_htdocs . '/wp-content/themes/';
    }

    $rewrite_to = $wp_version . '/wp-content/themes/';

    return str_replace($rewrite_from, $rewrite_to, $template_dir_uri);
}

add_filter('template_directory_uri', 'rewrite_uri_to_root_in_www_theme_2018', 10, 3);
add_filter('stylesheet_directory_uri', 'rewrite_uri_to_root_in_www_theme_2018', 10, 3);
