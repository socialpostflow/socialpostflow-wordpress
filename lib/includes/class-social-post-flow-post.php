<?php
/**
 * Post class
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Registers status settings on Posts as a metabox.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 3.0.0
 */
class Social_Post_Flow_Post {

	/**
	 * Holds the base class object.
	 *
	 * @since 3.2.0
	 *
	 * @var object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   3.0.0
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct() {

		

		// Admin Notices.
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// Post Metabox.
		add_action( 'admin_menu', array( $this, 'admin_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 1 );

	}

	/**
	 * Outputs a notice if the user is editing a Post, which has a meta key indicating
	 * that status(es) were published to the API.
	 *
	 * @since   3.0.0
	 */
	public function admin_notices() {

		// Check we can get the current screen the user is viewing.
		$screen = get_current_screen();
		if ( ! $screen || ! isset( $screen->base ) || ! isset( $screen->parent_base ) ) {
			return;
		}

		// Check we are on a Post based screen (includes Pages + CPTs).
		if ( $screen->base !== 'post' ) {
			return;
		}

		// Check we are editing a Post, Page or CPT.
		if ( $screen->parent_base !== 'edit' ) {
			return;
		}

		// Check we have a Post ID.
		if ( ! isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$post_id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification

		// Check if this Post has a success or error meta key set by this plugin.
		$success = get_post_meta( $post_id, '_' . 'social_post_flow_success', true );
		$error   = get_post_meta( $post_id, '_' . 'social_post_flow_error', true );
		$errors  = get_post_meta( $post_id, '_' . 'social_post_flow_errors', true );

		// Check for success.
		if ( $success ) {
			// Show notice and clear meta key, so we don't display this notice again.
			delete_post_meta( $post_id, '_' . 'social_post_flow_success' );
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					echo esc_html(
						sprintf(
						/* translators: %1$s: Plugin Name, %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
							__( '%1$s: Post successfully added to %2$s.', 'social-post-flow' ),
							$this->base->plugin->displayName,
							$this->base->plugin->account
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		// Check for error.
		if ( $error ) {
			// Show notice and clear meta key, so we don't display this notice again.
			delete_post_meta( $post_id, '_' . 'social_post_flow_error' );
			delete_post_meta( $post_id, '_' . 'social_post_flow_errors' );
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php
					echo esc_html(
						sprintf(
						/* translators: %1$s: Plugin Name, %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
							__( '%1$s: Some status(es) could not be sent to %2$s', 'social-post-flow' ),
							$this->base->plugin->displayName,
							$this->base->plugin->account
						)
					);
					?>
					<br />
					<?php
					foreach ( $errors as $error ) {
						echo esc_html( $error ) . '<br />';
					}
					?>
				</p>
			</div>
			<?php
		}

	}

	/**
	 * Adds Metaboxes to Post Edit Screens
	 *
	 * @since   3.0.0
	 */
	public function admin_meta_boxes() {

		// Check if we need to hide the meta box by the logged in User's role.
		$hide_meta_box = social_post_flow()->get_class( 'settings' )->get_setting( 'hide_meta_box_by_roles', '[' . wp_get_current_user()->roles[0] . ']' );

		// Bail if we're hiding the meta boxes for the logged in User's role.
		if ( $hide_meta_box ) {
			return;
		}

		// Get Post Types.
		$post_types = social_post_flow()->get_class( 'common' )->maybe_remove_post_types_by_role(
			social_post_flow()->get_class( 'common' )->get_post_types(),
			wp_get_current_user()->roles[0]
		);

		// Determine the title for the Featured Image Meta Box, depending on whether the Plugin supports additional images.
		if ( $this->base->supports( 'additional_images' ) ) {
			$title = sprintf(
				/* translators: Plugin Name */
				__( '%s: Featured and Additional Images', 'social-post-flow' ),
				$this->base->plugin->displayName
			);
		} else {
			$title = sprintf(
				/* translators: Plugin Name */
				__( '%s: Featured Image', 'social-post-flow' ),
				$this->base->plugin->displayName
			);
		}

		// Add meta boxes for each Post Type.
		foreach ( $post_types as $post_type => $post_type_obj ) {
			// Additional Images.
			add_meta_box( 'social-post-flow-image', $title, array( $this, 'meta_image' ), $post_type, 'side', 'low' );

			// Status Settings.
			add_meta_box( 'social-post-flow', $this->base->plugin->displayName, array( $this, 'meta_settings' ), $post_type, 'normal', 'low' );
		}

	}

	/**
	 * Outputs settings to allow the user to override default settings for publishing to the API
	 *
	 * @since   3.0.0
	 *
	 * @param   WP_Post $post   Post.
	 */
	public function meta_settings( $post ) {

		// Get override settings.
		$override_default = social_post_flow()->get_class( 'settings' )->get_option( 'override', '0' );
		$override         = $this->get_setting_by_post_id( $post->ID, '[override]', $override_default );
		?>
		<div class="wpzinc-option ignore-nth-child">
			<p>
				<select name="<?php echo esc_attr( 'social-post-flow' ); ?>[override]" size="1" data-conditional="<?php echo esc_attr( 'social-post-flow' ); ?>-override" data-conditional-value="1">
					<?php
					foreach ( social_post_flow()->get_class( 'common' )->get_override_options() as $value => $label ) {
						?>
						<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $override, $value ); ?>>
							<?php echo esc_attr( $label ); ?>
						</option>
						<?php
					}
					?>
				</select>
				<?php
				// Include plugin nonce field.
				wp_nonce_field( 'social-post-flow', 'social_post_flow_nonce' );
				?>
			</p>
		</div>

		<?php
		// Get URL Parameters.
		$tab            = 'post';
		$post_type      = $post->post_type;
		$is_post_screen = true; // Enables the 'specific' schedule option, which can only be used on individual Per-Post Settings.

		// Authentication.
		social_post_flow()->get_class( 'api' )->set_tokens(
			social_post_flow()->get_class( 'settings' )->get_access_token(),
			social_post_flow()->get_class( 'settings' )->get_refresh_token(),
			social_post_flow()->get_class( 'settings' )->get_token_expires()
		);

		// Get Profiles.
		$profiles = social_post_flow()->get_class( 'api' )->profiles( false, social_post_flow()->get_class( 'common' )->get_transient_expiration_time() );

		// Run profiles through role restriction.
		if ( ! is_wp_error( $profiles ) ) {
			$profiles = social_post_flow()->get_class( 'common' )->maybe_remove_profiles_by_role( $profiles, wp_get_current_user()->roles[0] );
		}

		// Get some other information.
		$post_type_object = get_post_type_object( $post_type );
		$actions_plural   = social_post_flow()->get_class( 'common' )->get_post_actions_past_tense();
		$post_actions     = social_post_flow()->get_class( 'common' )->get_post_actions();

		// Get original statuses that will be stored in a hidden field so they are preserved if the screen is saved
		// with no changes that trigger an update to the hidden field.
		// Check if the override value exists.
		if ( $this->has_post_level_settings( $post->ID ) ) {
			// Use the Post Level Settings.
			$original_statuses = $this->get_settings( $post->ID );
		} else {
			// Use the Post Type Settings.
			$original_statuses = social_post_flow()->get_class( 'settings' )->get_settings( $post_type );
		}
		?>
		<div id="<?php echo esc_attr( 'social-post-flow' ); ?>-override" class="wpzinc-option">
			<?php
			// Load Post Settings View (Tabs + Statuses for each Profile).
			require $this->base->plugin->folder . 'lib/views/settings-post.php';
			?>
		</div>
		<div class="wpzinc-option">
			<div class="full">
				<button class="<?php echo esc_attr( 'social-post-flow' ); ?>-save-post-statuses button button-primary">
					<?php esc_html_e( 'Save', 'social-post-flow' ); ?>
				</button>
			</div>
		</div>
		<?php

		// Add footer action to output overlay modal markup.
		add_action( 'admin_footer', array( $this, 'output_modal' ) );

	}

	/**
	 * Outputs the Featured Image Meta Box
	 *
	 * @since   3.2.6
	 *
	 * @param   WP_Post $post   Post.
	 */
	public function meta_image( $post ) {

		// Fetch images assigned to this Post for use in status(es).
		$images = $this->get_post_images( $post->ID );

		// Get post type object.
		$post_type_object = get_post_type_object( $post->post_type );

		// Check if "Use OpenGraph Settings" is available.
		$supports_opengraph = social_post_flow()->get_class( 'image' )->supports_opengraph();

		// Render view.
		require $this->base->plugin->folder . 'lib/views/settings-post-image.php';

	}

	/**
	 * Returns an array of images that may have been chosen for use when status(es)
	 * are sent to the social network for this Post.
	 *
	 * @since   3.7.8
	 *
	 * @param   int $post_id    Post ID.
	 * @return  array               Images
	 */
	private function get_post_images( $post_id ) {

		// If additional images are supported by the calling Plugin, allow 10 images
		// in total to be defined.  Otherwise, only allow a single Featured Image.
		$supported_images_total = ( $this->base->supports( 'additional_images' ) ? 10 : 1 );

		// Fetch existing images that might have been assigned to this Post.
		$images = array();
		for ( $i = 0; $i < $supported_images_total; $i++ ) {
			switch ( $i ) {
				case 0:
					// For backward compat, the first image is stored in the featured_image key
					// This ensures that any installations < 3.7.8 that have a Plugin Featured Image
					// set will honor this setting on a status update / repost.
					$images[ $i ] = array(
						'id'            => $this->get_setting_by_post_id( $post_id, 'featured_image', false ),
						'thumbnail_url' => false,
					);
					break;

				default:
					// Additional images are stored in the additional_images key.
					$images[ $i ] = array(
						'id'            => $this->get_setting_by_post_id( $post_id, '[additional_images][' . ( $i - 1 ) . ']', false ),
						'thumbnail_url' => false,
					);
					break;
			}
		}

		// Iterate through the images, fetching their thumbnails if an image ID is specified.
		foreach ( $images as $i => $image ) {
			if ( ! $image['id'] ) {
				continue;
			}

			// Get attachment.
			$attachment = wp_get_attachment_image_src( $image['id'], 'thumbnail' );

			// Skip if attachment didn't return anything.
			if ( ! $attachment ) {
				continue;
			}

			// Add thumbnail URL to array.
			$images[ $i ]['thumbnail_url'] = $attachment[0];
		}

		return $images;

	}

	/**
	 * Retrieves a setting from the Post meta, falling back to the Settings data
	 * if this Post has never been saved before (this allows Settings to act as defaults
	 * for new Posts).
	 *
	 * Safely checks if the key(s) exist before returning the default
	 * or the value.
	 *
	 * This function exists so that views/ files, which call $this->get_setting() in both Post
	 * and Setting contexts, works correctly, meaning we don't need to duplicate our views.
	 *
	 * @since   3.0.0
	 *
	 * @param   string $post_type       Post Type.
	 * @param   string $key             Setting key value to retrieve.
	 * @param   string $default_value   Default Value.
	 * @return  string                  Value/Default Value
	 */
	public function get_setting( $post_type = '', $key = '', $default_value = '' ) {

		// Get Post ID.
		global $post;
		$post_id = $post->ID;

		// Check if the override value exists.
		$has_post_level_settings = $this->has_post_level_settings( $post_id );
		if ( ! $has_post_level_settings ) {
			// No settings exist for this Post - populate form with defaults.
			return social_post_flow()->get_class( 'settings' )->get_setting( $post_type, $key, $default_value );
		}

		// If here, the Post has Settings, so fetch data from the Post.
		return $this->get_setting_by_post_id( $post_id, $key, $default_value );

	}

	/**
	 * Determines if the given Post ID has Post level settings defined.
	 *
	 * @since   3.7.8
	 *
	 * @param   int $post_id    Post ID.
	 * @return  bool                Has Post Level Settings
	 */
	public function has_post_level_settings( $post_id ) {

		// The 'default' key will exist if Post level settings have been saved,
		// which happens when the User has (at some point) enabled the Override option.
		return $this->get_setting_by_post_id( $post_id, '[default]', false );

	}

	/**
	 * Retrieves a setting from the Post meta by a Post ID.
	 *
	 * Safely checks if the key(s) exist before returning the default
	 * or the value.
	 *
	 * @since   3.0.3
	 *
	 * @param   mixed  $post_id         Post ID.
	 * @param   string $key             Setting key value to retrieve.
	 * @param   string $default_value   Default Value.
	 * @return  string                  Value/Default Value
	 */
	public function get_setting_by_post_id( $post_id, $key, $default_value = '' ) {

		// Get settings.
		$settings = $this->get_settings( $post_id );

		// Convert string to keys.
		$keys = explode( '][', $key );

		foreach ( $keys as $count => $key ) {
			// Cleanup key.
			$key = trim( $key, '[]' );

			// Check if key exists.
			if ( ! isset( $settings[ $key ] ) ) {
				return $default_value;
			}

			// Key exists - make settings the value (which could be an array or the final value)
			// of this key.
			$settings = $settings[ $key ];
		}

		// If here, setting exists.
		// This will be a non-array value.
		return $settings;

	}

	/**
	 * Returns the settings for the given Post.
	 *
	 * @since   3.0.0
	 *
	 * @param   int $post_id    Post ID.
	 * @return  array               Settings
	 */
	public function get_settings( $post_id ) {

		// Get current settings.
		$settings = get_post_meta( $post_id, 'social-post-flow', true );

		/**
		 * Filters Status Settings for a specific Post.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $settings   Post Settings.
		 * @param   int     $post_id    Post ID.
		 */
		$settings = apply_filters( 'social_post_flow_get_post_meta', $settings, $post_id );

		// Return result.
		return $settings;

	}

	/**
	 * Save Post-specific Plugin Settings
	 *
	 * @since   3.0.9
	 *
	 * @param   int $post_id Post ID.
	 * @return  bool            Success
	 */
	public function save_post( $post_id ) {

		// Missing nonce.
		if ( ! isset( $_POST[ 'social_post_flow_nonce' ] ) ) {
			return false;
		}

		// Invalid nonce.
		if ( ! wp_verify_nonce( sanitize_key( $_POST[ 'social_post_flow_nonce' ] ), 'social-post-flow' ) ) {
			return false;
		}

		// Bail if no settings are being saved.
		if ( ! isset( $_POST[ 'social-post-flow' ] ) ) {
			return true;
		}

		// Bail if the Post Type isn't supported.
		// This prevents non-public Post Types saving Post-specific settings where Post Level Default = Post using Manual Settings.
		$supported_post_types = array_keys( social_post_flow()->get_class( 'common' )->get_post_types() );
		if ( ! in_array( get_post_type( $post_id ), $supported_post_types, true ) ) {
			return true;
		}

		// Save.
		return $this->save_settings( $post_id, $_POST[ 'social-post-flow' ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

	}

	/**
	 * Saves status settings for the specific Post.
	 *
	 * @since   4.4.1
	 *
	 * @param   int   $post_id    Post ID.
	 * @param   array $settings   Status Settings.
	 */
	public function save_settings( $post_id, $settings ) {

		// If override is not enabled, just save the override parameter and featured / additional
		// image information.
		if ( isset( $settings['override'] ) && $settings['override'] != '1' ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
			// Define settings.
			$post_settings = array(
				'override' => $settings['override'],
			);

			if ( isset( $settings['additional_images'] ) && ! empty( $settings['additional_images'] ) && array_filter( $settings['additional_images'] ) ) {
				// For backward compat., save the first additional image as the featured_image.
				$post_settings['featured_image'] = $settings['additional_images'][0];

				// Remove first image from additional images array and rekey.
				unset( $settings['additional_images'][0] );

				if ( count( $settings['additional_images'] ) ) {
					$post_settings['additional_images'] = array_values( $settings['additional_images'] );
				} else {
					$post_settings['additional_images'] = array();
				}
			}

			// Save.
			update_post_meta( $post_id, 'social-post-flow', $post_settings );

			// Return.
			return true;
		}

		// If here, we're overriding settings for this Post.
		if ( isset( $settings['statuses'] ) ) {
			// Unslash and decode JSON.
			$post_settings = json_decode( wp_unslash( $settings['statuses'] ), true );

			// Add other settings in now.
			// We don't use array_merge() to merge the statuses to the Post Settings, because
			// Profile IDs that are numerical i.e. Hootsuite Profiles will result in a broken array structure
			// where the Profile settings are keyed from zero, rather than keyed with the Profile ID.

			// Override.
			$post_settings['override'] = $settings['override'];

			if ( isset( $settings['additional_images'] ) && ! empty( $settings['additional_images'] ) && array_filter( $settings['additional_images'] ) ) {
				// For backward compat., save the first additional image as the featured_image.
				$post_settings['featured_image'] = $settings['additional_images'][0];

				// Remove first image from additional images array and rekey.
				unset( $settings['additional_images'][0] );

				if ( count( $settings['additional_images'] ) ) {
					$post_settings['additional_images'] = array_values( $settings['additional_images'] );
				} else {
					$post_settings['additional_images'] = array();
				}
			}

			// Save.
			update_post_meta( $post_id, 'social-post-flow', $post_settings );

			// Return.
			return true;
		}

		return true;

	}

	/**
	 * Outputs the hidden Javascript Modal and Overlay in the Footer
	 *
	 * @since   4.4.1
	 */
	public function output_modal() {

		// Load view.
		require_once $this->base->plugin->folder . '_modules/dashboard/views/modal.php';

	}

}
