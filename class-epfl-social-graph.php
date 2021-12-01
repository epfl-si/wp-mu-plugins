<?php
/**
 * EPFL Social Graph
 *
 * This is a must use plugin that handle the generation of the social graph
 * meta elements in the page, based on the page content.
 * It generate the open graph data (see https://ogp.me/ for details) with some
 * attempt to make it relevant to current page. For example, if the Gutenberg
 * block 'epfl/hero' is used, its data will be used.
 * Twitter card elements are also generated, but are quite simpler than open
 * graph.
 * By default, fallbacks to bloginfo (title, tagname).
 *
 * Developer notes:
 *   - Open Graph can be validated on
 *     https://developers.facebook.com/docs/sharing/webmasters/
 *   - Twitter Card can be validated on https://cards-dev.twitter.com/validator
 *   - Telegram will also use theses elements, it is a good tests. Chat with
 *     the user `@WebpageBot` to refresh previews.
 *
 * @TODO
 *   - learn how to use https://instantview.telegram.org/
 *   - implement news Twitter card element, such as `twitter:site`
 *
 * @link              https://www.eplf.ch
 * @since             1.0.0
 * @package           EFPL-MU-plugins
 *
 * @wordpress-plugin
 * Plugin Name:       EPFL Social Graph
 * Plugin URI:        https://github.com/epfl-si/wp-mu-plugins
 * Description:       Define open graph and twitter metadata to enhance sharing
 * Version:           1.0.0
 * Author:            EPFL IDEV-FSD
 * Author URI:        https://go.epfl.ch/idev-fsd
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       EFPL-MU-plugins
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
if ( ! defined( 'EPFL_SOCIAL_GRAPH_VERSION' ) ) {
	define( 'EPFL_SOCIAL_GRAPH_VERSION', '1.0.0' );
}

/**
 * EPFL_Social_Graph Class Doc Comment
 *
 * @category Class
 * @package  EFPL-MU-plugins
 * @author   Nicolas Borboën
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class EPFL_Social_Graph {

	const EPFL_SOCIAL_GRAPH_DEFAULT_IMAGE      = 'https://actu.epfl.ch/image/92055/1108x622.jpg';
	const EPFL_SOCIAL_GRAPH_DEFAULT_IMAGE_SIZE = array( 1106, 622 );
	const EPFL_SOCIAL_GRAPH_DEFAULT_IMAGE_TYPE = 'image/jpeg';
	const EPFL_SOCIAL_GRAPH_DEFAULT_IMAGE_ALT  = 'EPFL Totem in place Cosandey';
	const EPFL_SOCIAL_GRAPH_DEFAULT_TYPE       = 'website';
	const EPFL_SOCIAL_GRAPH_DEFAULT_SITE_NAME  = 'EPFL';

	/**
	 * Social graph type
	 *
	 * @var string $epfl_sg_type, type of the og
	 */
	public string $epfl_sg_type;

	/**
	 * Social graph site_name
	 *
	 * @var string $epfl_sg_site_name, title
	 */
	public string $epfl_sg_site_name;

	/**
	 * Social graph title
	 *
	 * @var string $epfl_sg_title
	 */
	public string $epfl_sg_title;

	/**
	 * Social graph description
	 *
	 * @var string $epfl_sg_description
	 */
	public string $epfl_sg_description;

	/**
	 * Social graph page URL
	 *
	 * @var string $epfl_sg_url
	 */
	public string $epfl_sg_url;

	/**
	 * Social graph image details (URL, alt, size, ...)
	 *
	 * @var array $epfl_sg_image
	 */
	public array $epfl_sg_image;

	/**
	 * Social graph video details
	 *
	 * @var array $epfl_sg_video
	 */
	public array $epfl_sg_video;

	/**
	 * Social graph locale
	 *
	 * @var string $epfl_sg_locale
	 */
	public string $epfl_sg_locale;

	/**
	 * Social graph alternate locales
	 *
	 * @var array $epfl_sg_locales
	 */
	public array $epfl_sg_locales;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'epfl_sg_initialize' ) );
	}

	/**
	 * Get the type for social graph
	 *
	 * The type of your object, e.g., "video.movie". Depending on the type you
	 * specify, other properties may also be required.
	 *
	 * @return string type
	 */
	private function get_the_type_for_social_graph() {
		$this->epfl_sg_type = wp_strip_all_tags( self::EPFL_SOCIAL_GRAPH_DEFAULT_TYPE );
		return $this->epfl_sg_type;
	}

	/**
	 * Get the site name for social graph
	 *
	 * If your object is part of a larger web site, the name which should be
	 * displayed for the overall site.
	 *
	 * @return string site_name
	 */
	private function get_the_site_name_for_social_graph() {
		$this->epfl_sg_site_name = wp_strip_all_tags( self::EPFL_SOCIAL_GRAPH_DEFAULT_SITE_NAME );
		return $this->epfl_sg_site_name;
	}

	/**
	 * Get the title for social graph
	 *
	 * The title of your object as it should appear within the graph.
	 * Fallback to blog's title if the title is not defined.
	 *
	 * @return string title
	 */
	private function get_the_title_for_social_graph() {
		// Use the title of epfl/hero block if defined.
		if ( ! empty( $this->{'epfl/hero'}['title'] ) ) {
			$this->epfl_sg_title = wp_strip_all_tags( html_entity_decode( $this->{'epfl/hero'}['title'] ) );
		} else {
			// Fallback to either the page's title of the site's title.
			$this->epfl_sg_title = wp_strip_all_tags( html_entity_decode( get_the_title() ? get_the_title() : get_bloginfo( 'title' ) ) );
		}
		return $this->epfl_sg_title;
	}

	/**
	 * Get the description for social graph
	 *
	 * A one to two sentence description of your object.
	 * Fallback to blog's title if the title is not defined.
	 *
	 * @return string description
	 */
	private function get_the_description_for_social_graph() {
		// Use the text of epfl/hero block if defined.
		if ( ! empty( $this->{'epfl/hero'}['text'] ) ) {
			$this->epfl_sg_description = wp_strip_all_tags( html_entity_decode( $this->{'epfl/hero'}['text'] ) );
		} else {
			// Fallback to either the page's excerpt of the site's tagline.
			$this->epfl_sg_description = wp_strip_all_tags( html_entity_decode( get_the_excerpt() ? get_the_excerpt() : get_bloginfo( 'tagline' ) ) );
		}
		return $this->epfl_sg_description;
	}

	/**
	 * Get the page link for social graph
	 *
	 * The canonical URL of your object that will be used as its permanent ID in
	 * the graph.
	 *
	 * @return string page link
	 */
	private function get_the_page_link_for_social_graph() {
		// Note get_permalink() or get_page_link() won't return the exact URL,
		// but adding the query string is debatable.
		global $wp;
		$home_url = home_url();
		// With query string: add_query_arg( $wp->query_vars, home_url( $wp->request ) ).
		if ( isset( $wp ) && $wp->request ) {
			$home_url = home_url( $wp->request );
		}
		// Ensure it contains a final slash.
		$this->epfl_sg_url = esc_url_raw( rtrim( $home_url, '/' ) . '/' );
		return $this->epfl_sg_url;
	}

	/**
	 * Get image addition info (alt, type, size)
	 *
	 * @return array $this->epfl_sg_image
	 */
	private function get_image_additonal_info() {
		if ( self::EPFL_SOCIAL_GRAPH_DEFAULT_IMAGE === $this->epfl_sg_image ) {
			// In the case default image is used, hard set the data.
			$this->epfl_sg_image['og:image:width']  = self::EPFL_SOCIAL_GRAPH_DEFAULT_IMAGE_SIZE[0];
			$this->epfl_sg_image['og:image:height'] = self::EPFL_SOCIAL_GRAPH_DEFAULT_IMAGE_SIZE[1];
			$this->epfl_sg_image['og:image:type']   = self::EPFL_SOCIAL_GRAPH_DEFAULT_IMAGE_TYPE;
			$this->epfl_sg_image['og:image:alt']    = self::EPFL_SOCIAL_GRAPH_DEFAULT_IMAGE_ALT;
		} else {
			// Attempt to get the attachement details.
			$image_attachement_id = attachment_url_to_postid( $this->epfl_sg_image['og:image'] );
			if ( is_numeric( $image_attachement_id ) ) {
				$image_metadata = wp_get_attachment_metadata( $image_attachement_id, 'large' );
				if ( ! empty( $image_metadata ) ) {
					$this->epfl_sg_image['og:image:width']  = $image_metadata['width'];
					$this->epfl_sg_image['og:image:height'] = $image_metadata['height'];
				}
				$this->epfl_sg_image['og:image:alt']  = wp_strip_all_tags( html_entity_decode( get_post_meta( $image_attachement_id, '_wp_attachment_image_alt', true ) ) );
				$this->epfl_sg_image['og:image:type'] = get_post_mime_type( $image_attachement_id );
				// Note: information lile title (post_title), caption (post_exerpt) or
				// description (post_content) can be fetched with
				// get_post( $image_attachement_id ).
			}
		}
		// In case that the epfl/hero's description is set, overwrite the image alt.
		if ( ! empty( $this->{'epfl/hero'}['description'] ) ) {
			$this->epfl_sg_image['og:image:alt'] = wp_strip_all_tags( html_entity_decode( $this->{'epfl/hero'}['description'] ) );
		}

		return $this->epfl_sg_image;
	}

	/**
	 * Get the image for social graph
	 *
	 * An image URL which should represent your object within the graph.
	 * Fallback to a nice image.
	 *
	 * @return array $this->epfl_sg_image
	 */
	private function get_the_image_for_social_graph() {
		// Do we have a featured image ?
		if ( get_the_post_thumbnail() ) {
			$this->epfl_sg_image['og:image'] = esc_url_raw( get_the_post_thumbnail_url( get_the_ID() ) );
		} elseif ( ! empty( $this->{'epfl/hero'}['imageUrl'] ) ) {
			// Use the EPFL Hero block's image if defined.
			$this->epfl_sg_image['og:image'] = esc_url_raw( $this->{'epfl/hero'}['imageUrl'] );
		} else {
			// Fallback to a nice image.
			$this->epfl_sg_image['og:image'] = esc_url_raw( self::EPFL_SOCIAL_GRAPH_DEFAULT_IMAGE );
		}

		if ( 'https' === wp_parse_url( $this->epfl_sg_image['og:image'], PHP_URL_SCHEME ) ) {
			$this->epfl_sg_image['og:image:secure_url'] = $this->epfl_sg_image['og:image'];
		}

		// Get additional info: size, type, alt.
		self::get_image_additonal_info();

		return $this->epfl_sg_image;
	}

	/**
	 * Get the video for social graph
	 *
	 * A URL to a video file that complements this object.
	 *
	 * @return string video url
	 */
	private function get_the_video_for_social_graph() {
		// Use the video of epfl/hero block if defined.
		if ( ! empty( $this->{'epfl/hero'}['videoUrl'] ) ) {
			$this->epfl_sg_video['og:video']     = esc_url_raw( $this->{'epfl/hero'}['videoUrl'] );
			$this->epfl_sg_video['og:video:alt'] = wp_strip_all_tags( html_entity_decode( $this->{'epfl/hero'}['description'] ) );
		} else {
			$this->epfl_sg_video = array();
		}
		return $this->epfl_sg_video;
	}

	/**
	 * Get the page locale for social graph
	 *
	 * The locale these tags are marked up in. Of the format language_TERRITORY.
	 * OG default is en_US.
	 *
	 * @return string page locale
	 */
	private function get_the_locale_for_social_graph() {
		if ( function_exists( 'pll_current_language' ) ) {
			$this->epfl_sg_locale = pll_current_language( 'locale' );
		} elseif ( get_locale() ) {
			$this->epfl_sg_locale = get_locale();
		} else {
			$this->epfl_sg_locale = get_bloginfo( 'language' );
		}
		return $this->epfl_sg_locale;
	}

	/**
	 * Get the page locales for social graph
	 *
	 * An array of other locales this page is available in.
	 *
	 * @return string page locale
	 */
	private function get_the_locales_for_social_graph() {
		if ( empty( $this->epfl_sg_locale ) ) {
			$this->get_the_locale_for_social_graph();
		}
		if ( function_exists( 'pll_languages_list' ) ) {
			$languages_locales = pll_languages_list( array( 'fields' => 'locale' ) );
			$key               = array_search( $this->epfl_sg_locale, $languages_locales, true );
			if ( false !== $key ) {
				unset( $languages_locales[ $key ] );
			}
			$this->epfl_sg_locales = $languages_locales;
		} else {
			$this->epfl_sg_locales = false;
		}
		return $this->epfl_sg_locales;
	}

	/**
	 * Generate the open graph HTML elements
	 */
	private function generate_open_graph_meta_elements() {
		$og                        = array();
		$og['og:type']             = $this->epfl_sg_type;
		$og['og:site_name']        = $this->epfl_sg_site_name;
		$og['og:title']            = $this->epfl_sg_title;
		$og['og:description']      = $this->epfl_sg_description;
		$og['og:url']              = $this->epfl_sg_url;
		$og['og:image']            = $this->epfl_sg_image;
		$og['og:video']            = $this->epfl_sg_video;
		$og['og:locale']           = $this->epfl_sg_locale;
		$og['og:locale:alternate'] = $this->epfl_sg_locales;
		$og['fb:app_id']           = '966242223397117'; // this is the default.

		$this->og_meta_html  = '';
		$this->og_meta_html .= "\n\t\t<!-- Open Graph / Facebook -->";
		foreach ( $og as $ogkey => $ogval ) {
			if ( ! empty( $ogval ) ) {
				if ( is_array( $ogval ) ) {
					foreach ( $ogval as $subkey => $subval ) {
						$subkey              = ( 'og:locale:alternate' === $ogkey ) ? $ogkey : $subkey;
						$this->og_meta_html .= "\n\t\t" . wp_sprintf( '<meta property="%s" content="%s" />', $subkey, $subval );
					}
				} else {
					$this->og_meta_html .= "\n\t\t" . wp_sprintf( '<meta property="%s" content="%s" />', $ogkey, $ogval );
				}
			}
		}
		return $this->og_meta_html;
	}

	/**
	 * Generate the Twitter card HTML elements
	 */
	private function generate_twitter_meta_elements() {
		// @TODO look into <meta name="twitter:site" content="@nytimesbits" />
		$this->twitter_meta_html  = "\n";
		$this->twitter_meta_html .= "\n\t\t<!-- Twitter Card -->";
		$this->twitter_meta_html .= "\n\t\t<meta name=\"twitter:card\" content=\"summary_large_image\" />";
		$this->twitter_meta_html .= "\n\t\t" . wp_sprintf( '<meta name="twitter:title" content="%s" />', $this->epfl_sg_title );
		$this->twitter_meta_html .= "\n\t\t" . wp_sprintf( '<meta name="twitter:description" content="%s" />', $this->epfl_sg_description );
		$this->twitter_meta_html .= "\n\t\t" . wp_sprintf( '<meta name="twitter:image" content="%s" />', $this->epfl_sg_image['og:image'] ) . "\n";
		return $this->twitter_meta_html;
	}

	/**
	 * Adds meta tags for social network
	 * https://github.com/Biblicomentarios/biblicomentarios/blob/d4dbcc5fcd73e6b03a672bcc5ca2b0affb560dab/app/public/wp-content/plugins/statically/inc/statically_og.class.php
	 * language, image, valid description
	 * https://ogp.me/
	 */
	public function epfl_sg_initialize() {
		self::get_the_type_for_social_graph();
		self::get_the_site_name_for_social_graph();
		self::get_specific_gutenberg_block_infos();
		self::get_the_title_for_social_graph();
		self::get_the_description_for_social_graph();
		self::get_the_page_link_for_social_graph();
		self::get_the_image_for_social_graph();
		self::get_the_video_for_social_graph();
		self::get_the_locale_for_social_graph();
		self::get_the_locales_for_social_graph();
		$allowed_html = array(
			'meta' => array(
				'name'     => array(),
				'property' => array(),
				'content'  => array(),
			),
		);
		echo wp_kses( $this->generate_open_graph_meta_elements(), $allowed_html );
		echo wp_kses( $this->generate_twitter_meta_elements(), $allowed_html );
	}

	/**
	 * Get specific gutenberg block infos
	 *
	 * @param string $block_name Type of block to search for.
	 * @param string $attribute Name of a specific attribute to return.
	 *
	 * @return string|array either a specific attribute or an array of attributes.
	 */
	private function get_specific_gutenberg_block_infos( $block_name = 'epfl/hero', $attribute = null ) {
		global $post;
		$this->{ $block_name } = array();
		if ( isset( $post ) && has_block( $block_name, $post->post_content ) ) {
			// get all 'epfl/hero' blocks of the page.
			$blocks = array_filter(
				parse_blocks( $post->post_content ),
				function( $block ) use ( $block_name ) {
					return $block_name === $block['blockName'];
				}
			);
			$blocks = array_values( $blocks ); // reindex the array.
			// Just deal with the first one, just in case more than one is allowed.
			$this->{$block_name} = $blocks[0]['attrs'];
			if ( ! empty( $attribute ) ) {
				if ( ! empty( $this->{$block_name}[ $attribute ] ) ) {
					return $this->{ $block_name }[ $attribute ];
				}
			} else {
				return $this->{ $block_name };
			}
		}
		return null;
	}

}

// Load the plugins only for the frontend.
if ( ! is_admin() ) {
	new EPFL_Social_Graph();
}
