<?php
/**
 * Image class.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Determines optimal image sizes and aspect ratios for each
 * social networks, detects if such sizes are registered
 * in WordPress and (where possible) resizes and crops
 * images.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */
class Social_Post_Flow_Image {

	/**
	 * Helper method to retrieve Image Options
	 *
	 * @since   1.0.0
	 *
	 * @param   bool   $network    Network (false = defaults).
	 * @param   string $post_type  Post Type.
	 * @return  array              Image Options
	 */
	public function get_status_image_options( $network = false, $post_type = false ) {

		// If a Post Type has been specified, get its featured_image label.
		$label = __( 'Feat. Image', 'social-post-flow' );
		if ( $post_type !== false && $post_type !== 'bulk' ) {
			$post_type_object = get_post_type_object( $post_type );
			$label            = $post_type_object->labels->featured_image;
		}

		// Build featured image options.
		$options = array(
			'featured_image' => array(
				'label'             => $label,
				'additional_images' => true,
				'text_to_image'     => false,
			),
			'text_to_image'  => array(
				'label'             => __( 'Text to Image', 'social-post-flow' ),
				'additional_images' => false,
				'text_to_image'     => true,
			),
		);

		/**
		 * Defines the available Featured Image select dropdown options on a status, depending
		 * on the Plugin and Social Network the status message is for.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $options    Featured Image Dropdown Options.
		 * @param   string  $network    Social Network.
		 * @param   string  $post_type  Post Type.
		 */
		$options = apply_filters( 'social_post_flow_get_status_image_options', $options, $network, $post_type );

		// Return filtered results.
		return $options;

	}

	/**
	 * Helper method to retrieve Additional Image Options that are supported by the given network and post type.
	 *
	 * @since   1.0.0
	 *
	 * @param   bool   $network    Network (false = defaults).
	 * @param   string $post_type  Post Type.
	 * @return  array              Image Options
	 */
	public function get_status_image_options_supporting_additional_images( $network = false, $post_type = false ) {

		return array_filter(
			$this->get_status_image_options( $network, $post_type ),
			function ( $option ) {
				return $option['additional_images'];
			}
		);

	}

	/**
	 * Helper method to retrieve Text to Image Options that are supported by the given network and post type.
	 *
	 * @since   1.0.0
	 *
	 * @param   bool   $network    Network (false = defaults).
	 * @param   string $post_type  Post Type.
	 * @return  array              Image Options
	 */
	public function get_status_image_options_supporting_text_to_image( $network = false, $post_type = false ) {

		return array_filter(
			$this->get_status_image_options( $network, $post_type ),
			function ( $option ) {
				return $option['text_to_image'];
			}
		);

	}

	/**
	 * Helper method to retrieve Additional Image Options
	 *
	 * @since   1.0.0
	 *
	 * @param   bool   $network    Network (false = defaults).
	 * @param   string $post_type  Post Type.
	 * @return  array              Image Options
	 */
	public function get_status_additional_image_options( $network = false, $post_type = false ) {

		// If a Post Type has been specified, get its featured_image label.
		$label = __( 'Post', 'social-post-flow' );
		if ( $post_type !== false && $post_type !== 'bulk' ) {
			$post_type_object = get_post_type_object( $post_type );
			$label            = $post_type_object->labels->featured_image;
		}

		$options = array(
			''              => __( 'None', 'social-post-flow' ),
			'post_settings' => __( 'Specified in Post settings', 'social-post-flow' ),
			'post_content'  => sprintf(
				/* translators: Translated name for a Post Type's Featured Image (e.g. for WooCommerce, might be "Product image") */
				__( 'Auto populate from %s content', 'social-post-flow' ),
				$label
			),
		);

		/**
		 * Defines the available Additional Images select dropdown options on a status.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $options    Featured Image Dropdown Options.
		 * @param   string  $network    Social Network.
		 * @param   string  $post_type  Post Type.
		 */
		$options = apply_filters( 'social_post_flow_get_status_additional_image_options', $options, $network, $post_type );

		// Return filtered results.
		return $options;

	}

	/**
	 * Determines if the WordPress installations has a Plugin installed that outputs
	 * OpenGraph metadata
	 *
	 * @since   1.0.0
	 *
	 * @return  bool    Supports OpenGraph
	 */
	public function is_opengraph_plugin_active() {

		// Load function if required.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Fetch OpenGraph supported SEO Plugins and Fetured Image Options.
		$featured_image_options = array_keys( $this->get_status_image_options() );

		// If the Plugin only offers "Use OpenGraph Settings", no need to check for SEO Plugin availability.
		if ( count( $featured_image_options ) === 1 && ! $featured_image_options[0] ) {
			return false;
		}

		foreach ( $this->get_opengraph_seo_plugins() as $seo_plugin ) {
			// If plugin active, use OpenGraph for images.
			if ( is_plugin_active( $seo_plugin ) ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Helper method to retrieve the image size for the given service.
	 *
	 * @since   1.0.0
	 *
	 * @param   string      $service    Social Media Service.
	 * @param   bool|string $format     Status format (for example, 'story' or 'post' for Instagram).
	 * @return  array                   Social Media Image Size
	 */
	public function get_social_media_image_size( $service, $format = false ) {

		// Get image sizes for all social networks.
		$image_sizes = $this->get_social_media_image_sizes();

		// If a format is defined, append it to the $service to produce e.g instagram_story.
		if ( $format ) {
			$key = $service . '_' . $format;
		} else {
			$key = $service;
		}

		// Bail if the service doesn't have an image size defined.
		if ( ! isset( $image_sizes[ $key ] ) ) {
			return false;
		}

		$image_size = $image_sizes[ $key ];

		/**
		 * Defines the image size limit for the given social media service.
		 *
		 * @since   1.0.0
		 *
		 * @param   array       $image_size    Image Size (width, height).
		 * @param   string      $service       Social Media Service.
		 * @param   bool|string $format        Status format (for example, 'story' or 'post' for Instagram).
		 */
		$image_size = apply_filters( 'social_post_flow_get_social_media_image_size', $image_size, $service, $format );

		// Return filtered result.
		return $image_size;

	}

	/**
	 * Returns an array comprising of the image ID, image URL and alt text for the requested size, thumbnail size
	 * and the source of the image.
	 *
	 * @since   1.0.0
	 *
	 * @param   int    $image_id   Image ID.
	 * @param   string $source     Source Image ID was derived from (plugin, featured_image, post_content, text_to_image).
	 * @param   string $size       WordPress Registered Image Size to return the image as.
	 * @return  string             Image URL.
	 */
	public function get_image_source_by_size( $image_id, $source, $size = 'large' ) {

		// Get image at requested size.
		$image = wp_get_attachment_image_src( $image_id, $size );

		// Return URLs only.
		return ( is_array( $image ) ? strtok( $image[0], '?' ) : '' );

	}

	/**
	 * Defines the optimal image sizes for each social network.
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Social Media Image Sizes by Social Media Type.
	 */
	private function get_social_media_image_sizes() {

		// Defines the optimal image sizes for each social network.
		$image_sizes = array(
			'x'               => array( 1600, 900 ),
			'pinterest'       => array( 1000, 1500 ), // also 1000 x 1000.
			'instagram'       => array( 1080, 1080 ),
			'instagram_image' => array( 1080, 1080 ),
			'instagram_story' => array( 900, 1600 ),
			'facebook'        => array( 1200, 630 ),
			'linkedin'        => array( 1200, 627 ),
			'threads'         => array( 1200, 1200 ),
			'tiktok'          => array( 1080, 1920 ),
			'mastodon'        => array( 1200, 675 ),
			'bluesky'         => array( 1200, 675 ),
			'telegram'        => array( 1080, 1080 ),
		);

		/**
		 * Defines the optimal image sizes for each social network.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $image_sizes   Image Sizes
		 */
		$image_sizes = apply_filters( 'social_post_flow_get_social_media_image_sizes', $image_sizes );

		// Return filtered results.
		return $image_sizes;

	}

	/**
	 * Return an array of Plugins that output OpenGraph data
	 * which can be used by this Plugin for sharing the Featured Image
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Plugins
	 */
	private function get_opengraph_seo_plugins() {

		// Define Plugins.
		$plugins = array();

		/**
		 * Defines the Plugins that output OpenGraph metadata on Posts, Pages
		 * and Custom Post Types.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $plugins    Plugins
		 */
		$plugins = apply_filters( 'social_post_flow_get_opengraph_seo_plugins', $plugins );

		// Return filtered results.
		return $plugins;

	}

}
