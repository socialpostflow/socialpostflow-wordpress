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
	 * Holds the base class object.
	 *
	 * @since   4.6.6
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   4.6.6
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct() {

		

		// Load WordPress image libraries.
		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

	}

	/**
	 * Helper method to retrieve Featured Image Options
	 *
	 * @since   3.4.3
	 *
	 * @param   bool   $network    Network (false = defaults).
	 * @param   string $post_type  Post Type.
	 * @return  array               Featured Image Options
	 */
	public function get_featured_image_options( $network = false, $post_type = false ) {

		// If a Post Type has been specified, get its featured_image label.
		$label = __( 'Feat. Image', 'social-post-flow' );
		if ( $post_type !== false && $post_type !== 'bulk' ) {
			$post_type_object = get_post_type_object( $post_type );
			$label            = $post_type_object->labels->featured_image;
		}

		// Build featured image options, depending on the Plugin.
		switch ( 'social-post-flow' ) {

			case 'wp-to-buffer':
				$options = array(
					-1 => __( 'No Image', 'social-post-flow' ),
					0  => __( 'Use OpenGraph Settings', 'social-post-flow' ),
					2  => sprintf(
						/* translators: Translated name for a Post Type's Featured Image (e.g. for WooCommerce, might be "Product image") */
						__( 'Use %s, not Linked to Post', 'social-post-flow' ),
						$label
					),
				);
				break;

			case 'wp-to-buffer-pro':
				$options = array(
					-1 => __( 'No Image', 'social-post-flow' ),
					0  => __( 'Use OpenGraph Settings', 'social-post-flow' ),
					1  => sprintf(
						/* translators: Translated name for a Post Type's Featured Image (e.g. for WooCommerce, might be "Product image") */
						__( 'Use %s, Linked to Post', 'social-post-flow' ),
						$label
					),
					2  => sprintf(
						/* translators: Translated name for a Post Type's Featured Image (e.g. for WooCommerce, might be "Product image") */
						__( 'Use %s, not Linked to Post', 'social-post-flow' ),
						$label
					),
					3  => __( 'Use Text to Image, Linked to Post', 'social-post-flow' ),
					4  => __( 'Use Text to Image, not Linked to Post', 'social-post-flow' ),
				);
				break;

			case 'wp-to-hootsuite':
				$options = array(
					-1 => __( 'No Image', 'social-post-flow' ),
					2  => sprintf(
						/* translators: Translated name for a Post Type's Featured Image (e.g. for WooCommerce, might be "Product image") */
						__( 'Use %s, not Linked to Post', 'social-post-flow' ),
						$label
					),
				);
				break;

			case 'wp-to-hootsuite-pro':
				$options = array(
					-1 => __( 'No Image', 'social-post-flow' ),
					2  => sprintf(
						/* translators: Translated name for a Post Type's Featured Image (e.g. for WooCommerce, might be "Product image") */
						__( 'Use %s, not Linked to Post', 'social-post-flow' ),
						$label
					),
					4  => __( 'Use Text to Image, not Linked to Post', 'social-post-flow' ),
				);
				break;

			case 'wp-to-socialpilot':
				$options = array(
					0 => __( 'Use OpenGraph Settings', 'social-post-flow' ),
					2 => sprintf(
						/* translators: Translated name for a Post Type's Featured Image (e.g. for WooCommerce, might be "Product image") */
						__( 'Use %s, not Linked to Post', 'social-post-flow' ),
						$label
					),
				);
				break;

			case 'wp-to-socialpilot-pro':
				$options = array(
					0 => __( 'Use OpenGraph Settings', 'social-post-flow' ),
					2 => sprintf(
						/* translators: Translated name for a Post Type's Featured Image (e.g. for WooCommerce, might be "Product image") */
						__( 'Use %s, not Linked to Post', 'social-post-flow' ),
						$label
					),
					4 => __( 'Use Text to Image, not Linked to Post', 'social-post-flow' ),
				);
				break;

		}

		// Depending on the network, remove some options that aren't supported.
		switch ( $network ) {
			/**
			 * Twitter
			 * - Remove "Use Feat. Image, Linked to Post"
			 */
			case 'twitter':
				unset( $options[1], $options[3] );
				break;

			/**
			 * Instagram, Pinterest
			 * - Remove all options excluding "Use Feat. Image, not Linked to Post"
			 */
			case 'instagram':
			case 'pinterest':
				unset( $options[0], $options[1], $options[3] );
				break;
		}

		/**
		 * Defines the available Featured Image select dropdown options on a status, depending
		 * on the Plugin and Social Network the status message is for.
		 *
		 * @since   3.4.3
		 *
		 * @param   array   $options    Featured Image Dropdown Options.
		 * @param   string  $network    Social Network.
		 */
		$options = apply_filters( 'social_post_flow_get_featured_image_options', $options, $network );

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

		$featured_image_options = $this->get_featured_image_options();

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
		$featured_image_options = array_keys( $this->get_featured_image_options() );

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
	 * Returns the image for the given Attachment ID, based on the social media service
	 * the image will be used for.
	 *
	 * If the image isn't a compatible mime type, this function will attempt to convert the image
	 * from e.g. webp --> jpg.
	 *
	 * Checks that the image will meet the aspect ratio requirements for posting to Instagram,
	 * returning a valid image if the large size would fail.
	 *
	 * @since   4.6.6
	 *
	 * @param   int         $image_id   Image ID.
	 * @param   string      $source     Source Image ID was derived from (plugin, featured_image, post_content, text_to_image).
	 * @param   bool|string $service    Social Media Service the image is for. If not defined, just return the large version.
	 * @param   bool|string $format     Status format (for example, 'story' or 'post' for Instagram).
	 * @return  array|WP_Error              Image ID, Image URLs, Source
	 */
	public function get_image_sources( $image_id, $source, $service = false, $format = false ) {

		$image_mime_type = get_post_mime_type( $image_id );

		// If the image is a webp, attempt to convert it to a JPEG and store in the Media Library
		// as webp isn't supported by all social media services.
		// Check that the image source is a supported format i.e. not a webp.
		switch ( $image_mime_type ) {
			/**
			 * Webp
			 */
			case 'image/webp':
				// Don't do anything if the service supports webp and the image isn't for Instagram.
				// If it is for Instagram, we want to convert to a JPEG as we might need to resize/crop
				// later in this function.
				if ( $this->base->supports( 'webp' ) && $service !== 'instagram' ) {
					break;
				}

				// Get image.
				$image_path_and_file = get_attached_file( $image_id );

				// Just return the original image ID if we couldn't get the image path and file.
				if ( empty( $image_path_and_file ) || ! file_exists( $image_path_and_file ) ) {
					return $image_id;
				}

				// Load webp image.
				$image = wp_get_image_editor( $image_path_and_file );

				// Bail if an error occured.
				if ( is_wp_error( $image ) ) {
					return $image;
				}

				// Save to temporary file on disk.
				$converted_image = $image->save( get_temp_dir() . 'wp-to-social-pro-' . $image_id . '-converted-' . bin2hex( random_bytes( 5 ) ) );

				// Bail if an error occured.
				if ( is_wp_error( $converted_image ) ) {
					return $converted_image;
				}

				// Upload to Media Library.
				$converted_image_id = social_post_flow()->get_class( 'media_library' )->upload_local_image( $converted_image['path'] );

				// Bail if an error occured.
				if ( is_wp_error( $converted_image_id ) ) {
					return $converted_image_id;
				}

				// Assign image ID.
				$image_id = $converted_image_id;
				break;

			default:
				/**
				 * Defines the image ID to use as the image or additional image for the status message.
				 * If an image's mime type is not supported by the social media scheduling service, this
				 * filter can be used to convert the image to a supported type, store it in the Media Library
				 * and return the converted image ID.
				 *
				 * This is already performed for webp images.
				 *
				 * @since   4.6.8
				 *
				 * @param   int     $image_id           Image ID.
				 * @param   string  $source             Source Image ID was derived from (plugin, featured_image, post_content, text_to_image).
				 * @param   string  $service            Social Media Service the image is for. If not defined, just return the large version.
				 * @param   string  $image_mime_type    Image MIME Type.
				 */
				$image_id = apply_filters( 'wp_to_social_pro_image_get_images_sources_convert', $image_id, $source, $service, $image_mime_type );
				break;
		}

		switch ( $service ) {

			/**
			 * Instagram
			 */
			case 'instagram':
				// Get image.
				$image_path_and_file = get_attached_file( $image_id );

				// Just return the original image ID if we couldn't get the image path and file.
				if ( empty( $image_path_and_file ) || ! file_exists( $image_path_and_file ) ) {
					return $this->get_image_source_by_size( $image_id, $source, 'large' );
				}

				// Get image aspect ratio.
				$size         = getimagesize( $image_path_and_file );
				$aspect_ratio = $size[0] / $size[1];

				switch ( $format ) {
					/**
					 * Instagram Story
					 */
					case 'story':
						// If the aspect ratio of the image = 0.5625, just return the image.
						if ( $aspect_ratio === 0.5625 ) {
							return $this->get_image_source_by_size( $image_id, $source, 'large' );
						}

						// If here, the image's aspect ratio would result in the image being cropped when Buffer posts to
						// Instagram Stories.
						// Produce a resized copy that meets the required aspect ratio.
						return $this->get_resized_image_sources( $image_id, $source, $size, $aspect_ratio, 0.5625, 0.5625 );

					/**
					 * Instagram Post
					 */
					default:
						// If the aspect ratio of the image falls within the required limits, just return the image.
						if ( $aspect_ratio >= 0.8 && $aspect_ratio <= 1.91 ) {
							return $this->get_image_source_by_size( $image_id, $source, 'large' );
						}

						// If here, the image's aspect ratio would cause posting to Instagram to fail.
						// Produce a resized copy that meets the required aspect ratio.
						return $this->get_resized_image_sources( $image_id, $source, $size, $aspect_ratio, 1.91, 0.8 );
				}
				break;

			/**
			 * Other Social Networks
			 */
			default:
				return $this->get_image_source_by_size( $image_id, $source, 'large' );

		}

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
		$resized_image = $image->save( get_temp_dir() . 'wp-to-social-pro-resized-' . bin2hex( random_bytes( 5 ) ) );

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
		$resized_image = $image->save( get_temp_dir() . 'wp-to-social-pro-resized-' . bin2hex( random_bytes( 5 ) ) );

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
	 * @return  array               Image ID, Image URLs, Source
	 */
	private function get_image_source_by_size( $image_id, $source, $size ) {

		// Get image at requested size.
		$image = wp_get_attachment_image_src( $image_id, $size );

		// Get thumbnail version, which some APIs might use for a small preview.
		$thumbnail = wp_get_attachment_image_src( $image_id, 'thumbnail' );

		// Return URLs only.
		return array(
			'id'        => $image_id,
			'image'     => ( is_array( $image ) ? strtok( $image[0], '?' ) : false ), // Strip query parameters that might break some APIs.
			'thumbnail' => ( is_array( $thumbnail ) ? strtok( $thumbnail[0], '?' ) : false ), // Strip query parameters that might break some APIs.
			'alt_text'  => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
			'source'    => $source,
			'width'     => ( is_array( $image ) ? $image[1] : '' ),
			'height'    => ( is_array( $image ) ? $image[2] : '' ),
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
			'twitter'         => array( 1600, 900 ),
			'pinterest'       => array( 1000, 1500 ), // also 1000 x 1000.
			'instagram'       => array( 1080, 1080 ),
			'instagram_post'  => array( 1080, 1080 ),
			'instagram_story' => array( 900, 1600 ),
			'facebook'        => array( 1200, 630 ),
			'linkedin'        => array( 1200, 627 ),
			'googlebusiness'  => array( 720, 720 ),
			'threads'         => array( 1200, 1200 ),
			'bluesky'         => array( 1280, 1280 ),
			'mastodon'        => array( 1280, 1280 ),
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
