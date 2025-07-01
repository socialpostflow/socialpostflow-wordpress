<?php
/**
 * Image class.
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Determines optimal image sizes and aspect ratios for each
 * social networks, detects if such sizes are registered
 * in WordPress and (where possible) resizes and crops
 * images.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 1.0.0
 */
class Social_Post_Flow_Image {

	/**
	 * Constructor
	 *
	 * @since   4.6.6
	 */
	public function __construct() {

		// Load WordPress image libraries.
		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

	}

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
	 * Determines if "Use OpenGraph Settings" is an option available for the Status Image dropdown
	 *
	 * @since   4.2.0
	 *
	 * @return  bool    Supports OpenGraph
	 */
	public function supports_opengraph() {

		$featured_image_options = $this->get_status_image_options();

		if ( isset( $featured_image_options[0] ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Determines if the WordPress installations has a Plugin installed that outputs
	 * OpenGraph metadata
	 *
	 * @since   4.4.0
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
	 * @since   4.6.6
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
		 * @since   4.6.6
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
	 * Returns the image for the given Attachment ID, resized to meet the provided aspect ratio requirements.
	 *
	 * @since   4.6.6
	 *
	 * @param   int    $image_id                           Image ID.
	 * @param   string $source                             Source Image ID was derived from (plugin, featured_image, post_content, text_to_image).
	 * @param   array  $size                               getimagesize() result.
	 * @param   float  $aspect_ratio                       Image's current aspect ratio.
	 * @param   float  $required_aspect_ratio_landscape    Image's required aspect ratio, if the image is landscape.
	 * @param   float  $required_aspect_ratio_portrait     Image's required aspect ratio, if the image is portrait.
	 * @return  WP_Error|array                              Image ID, Image URLs, Source
	 */
	private function get_resized_image_sources( $image_id, $source, $size, $aspect_ratio, $required_aspect_ratio_landscape = 1.91, $required_aspect_ratio_portrait = 0.8 ) {

		if ( $aspect_ratio > 1 ) {
			// Original image is landscape.
			$width  = $size[1] * $required_aspect_ratio_landscape;
			$height = $size[1];

			// If the resulting width is greater than the original, cropping won't give us the required aspect ratio.
			// Instead, pad the left and right of the image to achieve the required aspect ratio.
			if ( $width > $size[0] ) {
				$resized_image_id = $this->pad( $image_id, $width, $height );
			} else {
				$resized_image_id = $this->crop( $image_id, $width, $height );
			}
		} else {
			// Origina image is portrait.
			$width  = $size[0];
			$height = $size[0] / $required_aspect_ratio_portrait;

			// If the resulting height is greater than the original, cropping won't give us the required aspect ratio.
			// Instead, pad the top and bottom of the image to achieve the required aspect ratio.
			if ( $height > $size[1] ) {
				$resized_image_id = $this->pad( $image_id, $width, $height );
			} else {
				$resized_image_id = $this->crop( $image_id, $width, $height );
			}
		}

		// Bail if an error occured.
		if ( is_wp_error( $resized_image_id ) ) {
			return $resized_image_id;
		}

		// Return Resized Image.
		return $this->get_image_source_by_size( $resized_image_id, $source, 'full' );

	}

	/**
	 * Crops the given image to the given width and height.
	 *
	 * @since   5.0.0
	 *
	 * @param   int   $image_id   Image ID.
	 * @param   float $width      Required Width.
	 * @param   float $height     Required Height.
	 * @return  WP_Error|int                Error | Resized Image ID.
	 */
	private function crop( $image_id, $width, $height ) {

		// Get image.
		$image_path_and_file = get_attached_file( $image_id );

		// Just return the original image ID if we couldn't get the image path and file.
		if ( empty( $image_path_and_file ) || ! file_exists( $image_path_and_file ) ) {
			return $image_id;
		}

		// Load image into image editor.
		$image = wp_get_image_editor( $image_path_and_file );

		// Bail if an error occured.
		if ( is_wp_error( $image ) ) {
			return $image;
		}

		// Resize image, using cropping.
		$resize_result = $image->resize( $width, $height, true );

		// Bail if an error occured.
		if ( is_wp_error( $resize_result ) ) {
			return $resize_result;
		}

		// Save to temporary file on disk.
		$resized_image = $image->save( get_temp_dir() . 'social-post-flow-resized-' . bin2hex( random_bytes( 5 ) ) );

		// Bail if an error occured.
		if ( is_wp_error( $resized_image ) ) {
			return $resized_image;
		}

		// Upload to Media Library, returning the result.
		return social_post_flow()->get_class( 'media_library' )->upload_local_image( $resized_image['path'] );

	}

	/**
	 * Pads the given image to the give width and height.
	 *
	 * @since   5.0.0
	 *
	 * @param   int   $image_id   Image ID.
	 * @param   float $width      Required Width.
	 * @param   float $height     Required Height.
	 * @return  WP_Error|int                Error | Resized Image ID.
	 */
	private function pad( $image_id, $width, $height ) {

		// Get image.
		$image_path_and_file = get_attached_file( $image_id );

		// Just return the original image ID if we couldn't get the image path and file.
		if ( empty( $image_path_and_file ) || ! file_exists( $image_path_and_file ) ) {
			return $image_id;
		}

		// Load image into image editor.
		$image = wp_get_image_editor( $image_path_and_file );

		// Bail if an error occured.
		if ( is_wp_error( $image ) ) {
			return $image;
		}

		// Load image into our extended editor class, depending on the class
		// WordPress originally used.
		switch ( get_class( $image ) ) {
			case 'WP_Image_Editor_GD':
				$image  = new Social_Post_Flow_Image_GD( $image_path_and_file );
				$loaded = $image->load();
				break;

			case 'WP_Image_Editor_Imagick':
				$image  = new Social_Post_Flow_Image_Imagick( $image_path_and_file );
				$loaded = $image->load();
				break;
		}

		// Bail if an error occured.
		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		// Resize image, using padding.
		$resize_result = $image->pad( $width, $height );

		// Bail if an error occured.
		if ( is_wp_error( $resize_result ) ) {
			return $resize_result;
		}

		// Save to temporary file on disk.
		$resized_image = $image->save( get_temp_dir() . 'social-post-flow-resized-' . bin2hex( random_bytes( 5 ) ) );

		// Bail if an error occured.
		if ( is_wp_error( $resized_image ) ) {
			return $resized_image;
		}

		// Upload to Media Library, returning the result.
		return social_post_flow()->get_class( 'media_library' )->upload_local_image( $resized_image['path'] );

	}

	/**
	 * Returns an array comprising of the image ID, image URL and alt text for the requested size, thumbnail size
	 * and the source of the image.
	 *
	 * @since   4.6.6
	 *
	 * @param   int    $image_id   Image ID.
	 * @param   string $source     Source Image ID was derived from (plugin, featured_image, post_content, text_to_image).
	 * @param   string $size       WordPress Registered Image Size to return the image as.
	 * @return  array              Image
	 */
	public function get_image_source_by_size( $image_id, $source, $size = 'large' ) {

		// Get image at requested size.
		$image = wp_get_attachment_image_src( $image_id, $size );

		// Return URLs only.
		return array(
			'image'    => ( is_array( $image ) ? strtok( $image[0], '?' ) : false ), // Strip query parameters that might break some APIs.
			'alt_text' => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
		);

	}

	/**
	 * Defines the optimal image sizes for each social network.
	 *
	 * @since   4.6.6
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
		);

		/**
		 * Defines the optimal image sizes for each social network.
		 *
		 * @since   4.2.0
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
	 * @since   4.6.6
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
		 * @since   3.7.9
		 *
		 * @param   array   $plugins    Plugins
		 */
		$plugins = apply_filters( 'social_post_flow_get_opengraph_seo_plugins', $plugins );

		// Return filtered results.
		return $plugins;

	}

}
