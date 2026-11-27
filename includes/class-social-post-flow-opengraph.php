<?php
/**
 * OpenGraph class.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

/**
 * Outputs OpenGraph and Twitter Card tags on singular Posts, unless
 * an SEO / OpenGraph Plugin is active that outputs them.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */
class Social_Post_Flow_OpenGraph {

	/**
	 * Holds whether OpenGraph tags should be output, once determined.
	 *
	 * @since   1.4.7
	 *
	 * @var     null|bool
	 */
	private $should_output = null;

	/**
	 * Constructor
	 *
	 * @since   1.4.7
	 */
	public function __construct() {

		add_action( 'wp_head', array( $this, 'output' ), 1 );
		add_filter( 'jetpack_enable_open_graph', array( $this, 'maybe_disable_jetpack_open_graph' ), 99 );

	}

	/**
	 * Returns the available OpenGraph setting options.
	 *
	 * @since   1.4.7
	 *
	 * @return  array
	 */
	public function get_options() {

		$options = array(
			'auto'   => __( 'Auto', 'social-post-flow' ),
			'always' => __( 'Always', 'social-post-flow' ),
			'never'  => __( 'Never', 'social-post-flow' ),
		);

		/**
		 * Defines the available OpenGraph setting options.
		 *
		 * @since   1.4.7
		 *
		 * @param   array   $options    Options.
		 */
		return apply_filters( 'social_post_flow_opengraph_options', $options );

	}

	/**
	 * Returns the OpenGraph setting.
	 *
	 * @since   1.4.7
	 *
	 * @return  string  auto|always|never
	 */
	public function get_setting() {

		$setting = social_post_flow()->get_class( 'settings' )->get_option( 'opengraph', 'auto' );

		// Fallback to auto if an unknown value is stored.
		if ( ! array_key_exists( $setting, $this->get_options() ) ) {
			return 'auto';
		}

		return $setting;

	}

	/**
	 * Returns SEO and OpenGraph Plugins that output OpenGraph tags, keyed by Plugin file.
	 *
	 * @since   1.4.7
	 *
	 * @return  array
	 */
	public function get_plugins() {

		$plugins = array(
			// SEO Plugins.
			'wordpress-seo/wp-seo.php'                    => 'Yoast SEO',
			'wordpress-seo-premium/wp-seo-premium.php'    => 'Yoast SEO Premium',
			'seo-by-rank-math/rank-math.php'              => 'Rank Math',
			'seo-by-rank-math-pro/rank-math-pro.php'      => 'Rank Math Pro',
			'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
			'all-in-one-seo-pack-pro/all_in_one_seo_pack.php' => 'All in One SEO Pro',
			'wp-seopress/seopress.php'                    => 'SEOPress',
			'wp-seopress-pro/seopress-pro.php'            => 'SEOPress Pro',
			'autodescription/autodescription.php'         => 'The SEO Framework',
			'slim-seo/slim-seo.php'                       => 'Slim SEO',
			'surerank/surerank.php'                       => 'SureRank',
			'smartcrawl-seo/wpmu-dev-seo.php'             => 'SmartCrawl',
			'squirrly-seo/squirrly.php'                   => 'Squirrly SEO',
			'wpsso/wpsso.php'                             => 'WPSSO',

			// OpenGraph / Social Plugins, per Jetpack's conflicting plugins list.
			'wonderm00ns-simple-facebook-open-graph-tags/wonderm00n-open-graph.php' => 'Open Graph and Twitter Card Tags',
			'heateor-open-graph-meta-tags/heateor-open-graph-meta-tags.php' => 'Open Graph Meta Tags by Heateor',
			'complete-open-graph/complete-open-graph.php' => 'Complete Open Graph',
			'add-meta-tags/add-meta-tags.php'             => 'Add Meta Tags',
			'opengraph/opengraph.php'                     => 'Open Graph',
			'social-networks-auto-poster-facebook-twitter-g/NextScripts_SNAP.php' => 'NextScripts SNAP',
			'shareaholic/sexy-bookmarks.php'              => 'Shareaholic',
			'nextgen-facebook/nextgen-facebook.php'       => 'NextGEN Facebook',
			'open-graph-protocol-framework/open-graph-protocol-framework.php' => 'Open Graph Protocol Framework',
			'og-tags/og-tags.php'                         => 'OG Tags',
			'wp-facebook-open-graph-protocol/wp-facebook-ogp.php' => 'WP Facebook Open Graph protocol',
			'facebook-thumb-fixer/_facebook-thumb-fixer.php' => 'Facebook Thumb Fixer',
			'open-graph-metabox/open-graph-metabox.php'   => 'Open Graph Metabox',
			'facebook/facebook.php'                       => 'Facebook',
			'facebook-awd/AWD_facebook.php'               => 'Facebook AWD All in One',
			'header-footer/plugin.php'                    => 'Head, Footer and Post Injections',
			'network-publisher/networkpub.php'            => 'Network Publisher',
			'wp-ogp/wp-ogp.php'                           => 'WP-OGP',
			'wp-fb-share-like-button/wp_fb_share-like_widget.php' => 'WP FB Share Like Button',
			'facebook-revised-open-graph-meta-tag/index.php' => 'Facebook Revised Open Graph Meta Tag',
			'facebook-and-digg-thumbnail-generator/facebook-and-digg-thumbnail-generator.php' => 'Facebook and Digg Thumbnail Generator',
			'facebook-meta-tags/facebook-metatags.php'    => 'Facebook Meta Tags',
			'easy-facebook-share-thumbnails/esft.php'     => 'Easy Facebook Share Thumbnail',
			'jetpack-social-meta/jetpack-social-meta.php' => 'Jetpack Social Meta',
			'og-tags-for-facebook/og-tags-for-facebook.php' => 'OG Tags for Facebook',
			'social-sharing-toolkit/social_sharing_toolkit.php' => 'Social Sharing Toolkit',
			'tweet-and-like-it/tweet-and-like-it.php'     => 'Tweet and Like It',
			'snap-social-publish/snap-social-publish.php' => 'SNAP Social Publish',
			'vk-all-in-one-expansion-unit/vkExUnit.php'   => 'VK All in One Expansion Unit',
			'wp-open-graph/wp-open-graph.php'             => 'WP Open Graph',
		);

		/**
		 * Defines SEO and OpenGraph Plugins that output OpenGraph tags. If any are active
		 * and the setting is Auto, Social Post Flow won't output OpenGraph tags.
		 *
		 * @since   1.4.7
		 *
		 * @param   array   $plugins    Plugin names, keyed by Plugin file.
		 */
		return apply_filters( 'social_post_flow_opengraph_plugins', $plugins );

	}

	/**
	 * Returns the name of the first active Plugin that outputs OpenGraph tags.
	 *
	 * @since   1.4.7
	 *
	 * @return  bool|string     Plugin name, or false if none are active.
	 */
	public function get_active_plugin() {

		// is_plugin_active() isn't loaded on the frontend.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( $this->get_plugins() as $file => $name ) {
			if ( is_plugin_active( $file ) ) {
				return $name;
			}
		}

		return false;

	}

	/**
	 * Returns whether Social Post Flow should output OpenGraph tags.
	 *
	 * @since   1.4.7
	 *
	 * @return  bool
	 */
	public function should_output() {

		if ( is_null( $this->should_output ) ) {
			switch ( $this->get_setting() ) {
				case 'always':
					$should_output = true;
					break;

				case 'never':
					$should_output = false;
					break;

				default:
					$should_output = ( $this->get_active_plugin() === false );
					break;
			}

			/**
			 * Determines whether Social Post Flow should output OpenGraph tags.
			 *
			 * @since   1.4.7
			 *
			 * @param   bool    $should_output  Should output OpenGraph tags.
			 * @param   string  $setting        OpenGraph setting (auto, always, never).
			 */
			$this->should_output = (bool) apply_filters( 'social_post_flow_output_opengraph', $should_output, $this->get_setting() );
		}

		return $this->should_output;

	}

	/**
	 * Returns a description of what the OpenGraph setting is doing, for the settings screen.
	 *
	 * @since   1.4.7
	 *
	 * @return  string
	 */
	public function get_status_description() {

		switch ( $this->get_setting() ) {
			case 'always':
				return __( 'Social Post Flow outputs OpenGraph tags. If an SEO plugin also outputs them, Posts will have duplicate tags.', 'social-post-flow' );

			case 'never':
				return __( 'Social Post Flow does not output OpenGraph tags.', 'social-post-flow' );

			default:
				$plugin = $this->get_active_plugin();
				if ( $plugin !== false ) {
					return sprintf(
						/* translators: Plugin name */
						__( '%s detected. It outputs OpenGraph tags, so Social Post Flow won\'t.', 'social-post-flow' ),
						$plugin
					);
				}

				return __( 'No SEO plugin detected. Social Post Flow outputs OpenGraph tags.', 'social-post-flow' );
		}

	}

	/**
	 * Disables Jetpack's OpenGraph tags when Social Post Flow outputs them.
	 *
	 * @since   1.4.7
	 *
	 * @param   bool $enabled    Jetpack OpenGraph enabled.
	 * @return  bool
	 */
	public function maybe_disable_jetpack_open_graph( $enabled ) {

		if ( $this->should_output() ) {
			return false;
		}

		return $enabled;

	}

	/**
	 * Outputs OpenGraph and Twitter Card tags in the <head> of singular Posts.
	 *
	 * @since   1.4.7
	 */
	public function output() {

		if ( ! is_singular() || ! $this->should_output() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( ! array_key_exists( $post->post_type, social_post_flow()->get_class( 'common' )->get_post_types() ) ) {
			return;
		}

		$tags = $this->get_tags( $post );

		echo "\n<!-- Social Post Flow OpenGraph -->\n";
		foreach ( $tags as $property => $content ) {
			// Twitter tags use name, OpenGraph tags use property.
			$attribute = ( strpos( $property, 'twitter:' ) === 0 ? 'name' : 'property' );
			printf( '<meta %s="%s" content="%s" />' . "\n", esc_attr( $attribute ), esc_attr( $property ), esc_attr( $content ) );
		}
		echo "<!-- / Social Post Flow OpenGraph -->\n";

	}

	/**
	 * Returns OpenGraph and Twitter Card tags for the given Post.
	 *
	 * @since   1.4.7
	 *
	 * @param   WP_Post $post   Post.
	 * @return  array           Tag content, keyed by property.
	 */
	public function get_tags( $post ) {

		$tags = array(
			'og:type'                => 'article',
			'og:title'               => wp_strip_all_tags( get_the_title( $post ) ),
			'og:description'         => $this->get_description( $post ),
			'og:url'                 => get_permalink( $post ),
			'og:site_name'           => get_bloginfo( 'name' ),
			'article:published_time' => get_post_time( 'c', true, $post ),
			'article:modified_time'  => get_post_modified_time( 'c', true, $post ),
		);

		$image = $this->get_image( $post );
		if ( $image ) {
			$tags['og:image']        = $image['url'];
			$tags['og:image:width']  = $image['width'];
			$tags['og:image:height'] = $image['height'];
			$tags['og:image:alt']    = $image['alt'];
		}

		$tags['twitter:card'] = ( $image ? 'summary_large_image' : 'summary' );

		/**
		 * Defines the OpenGraph and Twitter Card tags to output for the given Post.
		 *
		 * @since   1.4.7
		 *
		 * @param   array   $tags   Tag content, keyed by property.
		 * @param   WP_Post $post   Post.
		 */
		$tags = apply_filters( 'social_post_flow_opengraph_tags', $tags, $post );

		// Remove empty tags.
		return array_filter(
			$tags,
			function ( $content ) {
				return $content !== '' && $content !== null && $content !== false;
			}
		);

	}

	/**
	 * Returns the Post's excerpt, or a trimmed version of its content.
	 *
	 * @since   1.4.7
	 *
	 * @param   WP_Post $post   Post.
	 * @return  string
	 */
	private function get_description( $post ) {

		// Use raw content to avoid running the_content filters in wp_head.
		$text = ( has_excerpt( $post ) ? $post->post_excerpt : excerpt_remove_blocks( strip_shortcodes( $post->post_content ) ) );

		return wp_trim_words( wp_strip_all_tags( $text, true ), 55, '...' );

	}

	/**
	 * Returns the Post's image: the Plugin's per-Post image, falling back to the Featured Image.
	 *
	 * @since   1.4.7
	 *
	 * @param   WP_Post $post   Post.
	 * @return  bool|array      URL, width, height and alt, or false if no image.
	 */
	private function get_image( $post ) {

		$image_id = absint( social_post_flow()->get_class( 'post' )->get_setting_by_post_id( $post->ID, 'featured_image' ) );
		if ( ! $image_id ) {
			$image_id = get_post_thumbnail_id( $post->ID );
		}

		if ( ! $image_id ) {
			return false;
		}

		$image = wp_get_attachment_image_src( $image_id, 'large' );
		if ( ! is_array( $image ) ) {
			return false;
		}

		return array(
			'url'    => $image[0],
			'width'  => $image[1],
			'height' => $image[2],
			'alt'    => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
		);

	}

}
