<?php
/**
 * Administration class.
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Plugin settings screen and JS/CSS.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 1.0.0
 */
class Social_Post_Flow_Admin {

	/**
	 * Holds the success and error messages
	 *
	 * @since   1.0.0
	 *
	 * @var     array
	 */
	public $notices = array(
		'success' => array(),
		'error'   => array(),
	);

	/**
	 * Constructor
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		// Actions.
		add_action( 'social_post_flow_api_get_access_token', array( $this, 'save_oauth_tokens' ), 10, 1 );
		add_action( 'social_post_flow_api_refresh_token', array( $this, 'save_oauth_tokens' ), 10, 1 );
		add_action( 'init', array( $this, 'maybe_set_oauth_nonce' ), 11 );
		add_action( 'init', array( $this, 'maybe_get_access_token' ), 12 );
		add_action( 'init', array( $this, 'maybe_disconnect' ), 13 );
		add_action( 'init', array( $this, 'check_plugin_setup' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_css' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_filter( 'plugin_action_links_social-post-flow-/social-post-flow-.php', array( $this, 'plugin_action_links_settings_page' ) );

	}

	/**
	 * Sets the OAuth nonce cookie, if the Plugin isn't connected to the API.
	 *
	 * @since 1.0.0
	 */
	public function maybe_set_oauth_nonce() {

		// If the Plugin is connected to the API, no need to set a nonce cookie.
		if ( social_post_flow()->get_class( 'validation' )->api_connected() ) {
			return;
		}

		// If a cookie is already set, don't set it again.
		if ( array_key_exists( 'social_post_flow_oauth_nonce', $_COOKIE ) ) {
			return;
		}

		setcookie( 'social_post_flow_oauth_nonce', wp_create_nonce( 'social-post-flow-oauth' ), time() + HOUR_IN_SECONDS, '/' );

	}

	/**
	 * Exchanges the authorization code for an access token, if included in the request.
	 *
	 * @since   1.0.0
	 */
	public function maybe_get_access_token() {

		// Bail if nonce is not set.
		if ( ! isset( $_COOKIE['social_post_flow_oauth_nonce'] ) ) {
			return;
		}

		// Bail if nonce is not valid.
		if ( ! wp_verify_nonce( sanitize_key( $_COOKIE['social_post_flow_oauth_nonce'] ), 'social-post-flow-oauth' ) ) {
			setcookie( 'social_post_flow_oauth_nonce', '', time() - 3600, '/' );
			return;
		}

		// Delete cookie.
		setcookie( 'social_post_flow_oauth_nonce', '', time() - 3600, '/' );

		// If a code is included in the request, exchange it for an access token.
		if ( ! isset( $_REQUEST['code'] ) ) {
			return;
		}

		// Setup notices class.
		social_post_flow()->get_class( 'notices' )->set_key_prefix( 'social_post_flow_' . wp_get_current_user()->ID );

		// Sanitize token.
		$authorization_code = sanitize_text_field( wp_unslash( $_REQUEST['code'] ) );

		// Exchange the authorization code and verifier for an access token.
		$result = social_post_flow()->get_class( 'api' )->get_access_token( $authorization_code );

		// If an error occured, add it to the notices.
		if ( is_wp_error( $result ) ) {
			social_post_flow()->get_class( 'notices' )->add_error_notice( $result->get_error_message() );
			return;
		}

		// Store success message.
		social_post_flow()->get_class( 'notices' )->enable_store();
		social_post_flow()->get_class( 'notices' )->add_success_notice(
			__( 'Thanks! You\'ve connected the Plugin to your Social Post Flow account. Now select profiles below to enable, and define your statuses to start sending Posts to your social media profiles.', 'social-post-flow' )
		);

		// Redirect to Post tab.
		wp_safe_redirect( 'admin.php?page=social-post-flow&tab=post&type=post' );
		die();

	}

	/**
	 * Disconnects the Plugin from the API, if the user clicks the disconnect button.
	 *
	 * @since 1.0.0
	 */
	public function maybe_disconnect() {

		// Bail if nonce is not set.
		if ( ! array_key_exists( 'social-post-flow-oauth-disconnect', $_REQUEST ) ) {
			return;
		}

		// Bail if nonce is not valid.
		if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['social-post-flow-oauth-disconnect'] ), 'social-post-flow-oauth-disconnect' ) ) {
			return;
		}

		// Delete tokens.
		social_post_flow()->get_class( 'settings' )->delete_tokens();

		// Redirect to settings page.
		wp_safe_redirect( add_query_arg( array( 'page' => 'social-post-flow' ), admin_url( 'admin.php' ) ) );
		die();

	}


	/**
	 * Saves the OAuth tokens to the Plugin settings, whenever
	 * the authorization code is exchanged for an access token,
	 * or an existing access token is refreshed.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $tokens  OAuth Tokens.
	 */
	public function save_oauth_tokens( $tokens ) {

		social_post_flow()->get_class( 'settings' )->update_tokens( $tokens['access_token'], $tokens['refresh_token'], time() + $tokens['expires_in'] );

	}

	/**
	 * Checks that the oAuth authorization flow has been completed, and that
	 * at least one Post Type with one Social Media account has been enabled.
	 *
	 * Displays a dismissible WordPress notification if this has not been done.
	 *
	 * @since   1.0.0
	 */
	public function check_plugin_setup() {

		// Show an error if cURL hasn't been installed.
		if ( ! function_exists( 'curl_init' ) ) {
			social_post_flow()->get_class( 'notices' )->add_error_notice(
				__( 'Social Post Flow requires the PHP cURL extension to be installed and enabled by your web host.', 'social-post-flow' )
			);
		}

		// Check the API is connected.
		if ( ! social_post_flow()->get_class( 'validation' )->api_connected() ) {
			// Don't display the notice if this request is for the settings auth screen.
			$screen = social_post_flow()->get_class( 'screen' )->get_current_screen();
			if ( $screen['screen'] === 'settings' && $screen['section'] === 'auth' ) {
				return;
			}

			// Display the notice.
			social_post_flow()->get_class( 'notices' )->add_error_notice(
				sprintf(
					'%1$s <a href="%2$s">%3$s</a>',
					esc_html__( 'Social Post Flow needs to be authorized before you can start sending Posts.', 'social-post-flow' ),
					admin_url( 'admin.php?page=social-post-flow' ),
					esc_html__( 'Click here to Authorize.', 'social-post-flow' )
				)
			);
		}

	}

	/**
	 * Checks the transient to see if any admin notices need to be output now.
	 *
	 * @since   1.0.0
	 */
	public function admin_notices() {

		// Output notices.
		social_post_flow()->get_class( 'notices' )->set_key_prefix( 'social_post_flow_' . wp_get_current_user()->ID );
		social_post_flow()->get_class( 'notices' )->output_notices();

	}

	/**
	 * Register and enqueue any JS and CSS for the WordPress Administration
	 *
	 * @since 1.0.0
	 */
	public function admin_scripts_css() {

		global $id, $post;

		// Get current screen.
		$screen = social_post_flow()->get_class( 'screen' )->get_current_screen();

		// CSS - always load.
		wp_enqueue_style( 'social-post-flow', SOCIAL_POST_FLOW_PLUGIN_URL . 'assets/css/admin.css', array(), SOCIAL_POST_FLOW_PLUGIN_VERSION );

		// Define CSS variables for design.
		wp_register_style( 'social-post-flow-vars', false, array(), SOCIAL_POST_FLOW_PLUGIN_VERSION );
		wp_enqueue_style( 'social-post-flow-vars' );
		wp_add_inline_style(
			'social-post-flow-vars',
			trim(
				':root {
			--wpzinc-logo: url(\'' . esc_attr( social_post_flow()->plugin->logo ) . '\');
			--wpzinc-header-background-color: ' . esc_attr( social_post_flow()->plugin->header_background_color ) . ';
			--wpzinc-header-primary-text-color: ' . esc_attr( social_post_flow()->plugin->header_primary_text_color ) . ';
			--wpzinc-header-secondary-text-color: ' . esc_attr( social_post_flow()->plugin->header_secondary_text_color ) . ';
			--wpzinc-plugin-display-name: "Social Post Flow ";
		}'
			)
		);

		// Don't load anything else if we're not on a Plugin or Post screen.
		if ( ! $screen['screen'] ) {
			return;
		}

		// Determine whether to load minified versions of JS.
		$minified = social_post_flow()->dashboard->should_load_minified_js();

		// Define JS and localization.
		wp_register_script( 'social-post-flow-bulk-publish', SOCIAL_POST_FLOW_PLUGIN_URL . 'assets/js/' . ( $minified ? 'min/' : '' ) . 'bulk-publish' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), SOCIAL_POST_FLOW_PLUGIN_VERSION, true );
		wp_register_script( 'social-post-flow-log', SOCIAL_POST_FLOW_PLUGIN_URL . 'assets/js/' . ( $minified ? 'min/' : '' ) . 'log' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), SOCIAL_POST_FLOW_PLUGIN_VERSION, true );
		wp_register_script( 'social-post-flow-quick-edit', SOCIAL_POST_FLOW_PLUGIN_URL . 'assets/js/' . ( $minified ? 'min/' : '' ) . 'quick-edit' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), SOCIAL_POST_FLOW_PLUGIN_VERSION, true );
		wp_register_script( 'social-post-flow-settings', SOCIAL_POST_FLOW_PLUGIN_URL . 'assets/js/' . ( $minified ? 'min/' : '' ) . 'settings' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery', 'wp-color-picker' ), SOCIAL_POST_FLOW_PLUGIN_VERSION, true );
		wp_register_script( 'social-post-flow-statuses', SOCIAL_POST_FLOW_PLUGIN_URL . 'assets/js/' . ( $minified ? 'min/' : '' ) . 'statuses' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), SOCIAL_POST_FLOW_PLUGIN_VERSION, true );

		// Define localization for statuses.
		$localization = array(
			'ajax'                     => admin_url( 'admin-ajax.php' ),

			'character_count_action'   => 'social_post_flow_character_count',
			'character_count_metabox'  => '#social-post-flow-override',
			'character_count_nonce'    => wp_create_nonce( 'social-post-flow-character-count' ),

			'clear_log_nonce'          => wp_create_nonce( 'social-post-flow-clear-log' ),
			'clear_log_completed'      => __( 'No log entries exist, or no status updates have been sent to Social Post Flow.', 'social-post-flow' ),

			'get_log_nonce'            => wp_create_nonce( 'social-post-flow-get-log' ),

			'delete_condition_message' => __( 'Are you sure you want to delete this condition?', 'social-post-flow' ),
			'delete_status_message'    => __( 'Are you sure you want to delete this status?', 'social-post-flow' ),

			'get_status_row_action'    => 'social_post_flow_get_status_row',
			'get_status_row_nonce'     => wp_create_nonce( 'social-post-flow-get-status-row' ),

			'post_id'                  => ( isset( $post->ID ) ? $post->ID : (int) $id ),

			// Plugin specific Status Form Container and Status Form, so statuses.js knows where to look for the form
			// relative to this Plugin.
			'plugin_name'              => 'social-post-flow',
			'status_form_container'    => '#social-post-flow-status-form-container',
			'status_form'              => '#social-post-flow-status-form',

			// Search Nonces.
			'search_authors_nonce'     => wp_create_nonce( 'social-post-flow-search-authors' ),
			'search_roles_nonce'       => wp_create_nonce( 'social-post-flow-search-roles' ),
			'search_terms_nonce'       => wp_create_nonce( 'social-post-flow-search-terms' ),

			// status.js appends profile service to this e.g. twitter,facebook.
			'usernames_search_action'  => 'social_post_flow_usernames_search_',
		);

		// If here, we're on a Plugin or Post screen.
		// Conditionally load scripts and styles depending on which section of the Plugin we're loading.
		switch ( $screen['screen'] ) {
			/**
			 * Post
			 */
			case 'post':
				switch ( $screen['section'] ) {
					/**
					 * WP_List_Table
					 */
					case 'wp_list_table':
						break;

					/**
					 * Add/Edit
					 */
					case 'edit':
						// JS.
						wp_enqueue_script( 'wpzinc-admin-autocomplete' );
						wp_enqueue_script( 'wpzinc-admin-autosize' );
						wp_enqueue_script( 'wpzinc-admin-conditional' );
						wp_enqueue_media();
						wp_enqueue_script( 'wpzinc-admin-media-library' );
						wp_enqueue_script( 'wpzinc-admin-modal' );
						wp_enqueue_script( 'wpzinc-admin-selectize' );
						wp_enqueue_script( 'wpzinc-admin-tables' );
						wp_enqueue_script( 'wpzinc-admin-tabs' );
						wp_enqueue_script( 'wpzinc-admin' );
						wp_enqueue_script( 'jquery-ui-sortable' );

						// Plugin JS.
						wp_enqueue_script( 'social-post-flow-log' );
						wp_enqueue_script( 'social-post-flow-statuses' );

						// Add Action and Nonce to allow AJAX saving.
						$localization['post_type']              = $post->post_type;
						$localization['prompt_unsaved_changes'] = false;
						$localization['save_statuses_action']   = 'social_post_flow_save_statuses_post';
						$localization['save_statuses_modal']    = array(
							'title'         => __( 'Saving', 'social-post-flow' ),
							'title_success' => __( 'Saved!', 'social-post-flow' ),
						);
						$localization['save_statuses_nonce']    = wp_create_nonce( 'social-post-flow-save-statuses-post' );

						// Localize.
						wp_localize_script( 'social-post-flow-log', 'social_post_flow', $localization );
						wp_localize_script( 'social-post-flow-statuses', 'social_post_flow', $localization );

						// Localize Autocomplete.
						wp_localize_script( 'wpzinc-admin-autocomplete', 'wpzinc_autocomplete', $this->get_autocomplete_configuration( $localization['post_type'] ) );

						// CSS.
						wp_enqueue_style( 'wpzinc-admin-selectize' );
						break;
				}
				break;

			/**
			 * Settings
			 */
			case 'settings':
				// WordPress CSS.
				wp_enqueue_style( 'wp-color-picker' );

				// JS.
				wp_enqueue_script( 'wpzinc-admin-conditional' );
				wp_enqueue_media();
				wp_enqueue_script( 'wpzinc-admin-media-library' );
				wp_enqueue_script( 'wpzinc-admin-tables' );
				wp_enqueue_script( 'wpzinc-admin-tabs' );
				wp_enqueue_script( 'wpzinc-admin' );

				// Plugin JS.
				wp_enqueue_script( 'social-post-flow-settings' );

				switch ( $screen['section'] ) {
					/**
					 * General
					 */
					case 'auth':
						// JS.
						wp_enqueue_script( 'wpzinc-admin-modal' );

						// Add Repost Test Action and Nonce.
						$localization['repost_test_action'] = 'social_post_flow_repost_test';
						$localization['repost_test_modal']  = array(
							'title'         => __( 'Testing', 'social-post-flow' ),
							'title_success' => __( 'Finished', 'social-post-flow' ),
						);
						$localization['repost_test_nonce']  = wp_create_nonce( 'social-post-flow-repost-test' );

						// Localize.
						wp_localize_script( 'social-post-flow-settings', 'social_post_flow', $localization );
						break;

					/**
					 * Post Type
					 */
					default:
						// JS.
						wp_enqueue_script( 'wpzinc-admin-autocomplete' );
						wp_enqueue_script( 'wpzinc-admin-autosize' );
						wp_enqueue_script( 'wpzinc-admin-modal' );
						wp_enqueue_script( 'wpzinc-admin-selectize' );
						wp_enqueue_script( 'jquery-ui-sortable' );

						// Plugin JS.
						wp_enqueue_script( 'social-post-flow-statuses' );

						// Add Twitter Username Save Action and Nonce.
						$localization['username_save_twitter_action'] = 'social_post_flow_username_save_twitter';
						$localization['username_save_twitter_nonce']  = wp_create_nonce( 'social-post-flow-username-save-twitter' );

						// Localize.
						wp_localize_script( 'social-post-flow-settings', 'social_post_flow', $localization );

						// Add Post Type, Action and Nonce to allow AJAX saving.
						$localization['post_type']              = $this->get_post_type_tab();
						$localization['prompt_unsaved_changes'] = true;
						$localization['save_statuses_action']   = 'social_post_flow_save_statuses';
						$localization['save_statuses_modal']    = array(
							'title'         => __( 'Saving', 'social-post-flow' ),
							'title_success' => __( 'Saved!', 'social-post-flow' ),
						);
						$localization['save_statuses_nonce']    = wp_create_nonce( 'social-post-flow-save-statuses' );

						// Localize Statuses.
						wp_localize_script( 'social-post-flow-statuses', 'social_post_flow', $localization );

						// Localize Autocomplete.
						wp_localize_script( 'wpzinc-admin-autocomplete', 'wpzinc_autocomplete', $this->get_autocomplete_configuration( $localization['post_type'] ) );

						// CSS.
						wp_enqueue_style( 'wpzinc-admin-selectize' );
						break;
				}
				break;

			/**
			 * Bulk Publish
			 */
			case 'bulk_publish':
				// JS.
				wp_enqueue_script( 'wpzinc-admin-selectize' );
				wp_enqueue_script( 'jquery-ui-progressbar' );
				wp_enqueue_script( 'jquery-ui-sortable' );

				// Plugin JS.
				wp_enqueue_script( 'wpzinc-admin-synchronous-ajax' );
				wp_enqueue_script( 'wpzinc-admin-tables' );
				wp_enqueue_script( 'social-post-flow-bulk-publish' );
				wp_enqueue_script( 'social-post-flow-statuses' );

				// Localization.
				wp_localize_script( 'social-post-flow-statuses', 'social_post_flow', $localization );

				// CSS.
				wp_enqueue_style( 'wpzinc-admin-selectize' );
				break;

			/**
			 * Log
			 */
			case 'log':
				// Plugin JS.
				wp_enqueue_script( 'social-post-flow-log' );

				// Localize.
				wp_localize_script( 'social-post-flow-log', 'social_post_flow', $localization );
				break;
		}

	}

	/**
	 * Returns configuration for tribute.js autocomplete instances for Tags, Facebook Pages and Twitter Username mentions.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $post_type  Post Type.
	 * @return  array               Javascript  Autocomplete Configuration
	 */
	private function get_autocomplete_configuration( $post_type ) {

		$autocomplete_configuration = array(
			// Tags.
			array(
				'fields'   => array(
					'textarea.text',
					'input.url',
					'textarea.text-to-image',

					// Pinterest.
					'input#pinterest_title',
					'input#pinterest_source_url',

					// Google Business.
					'input#googlebusiness_title',
					'input#googlebusiness_code',
					'input#googlebusiness_terms',
				),
				'triggers' => array(
					// Tags.
					array(
						'trigger' => '{',
						'values'  => social_post_flow()->get_class( 'common' )->get_tags_flat( $post_type ),
					),
				),
			),

			// Facebook Autocomplete mentions.
			array(
				'fields'   => array(
					'div.facebook textarea.text',
				),
				'triggers' => array(
					// Usernames.
					array(
						'trigger'           => '@',
						'url'               => admin_url( 'admin-ajax.php' ),
						'method'            => 'POST',
						'action'            => 'social_post_flow_usernames_search_facebook',
						'nonce'             => wp_create_nonce( 'social-post-flow-usernames-search-facebook' ),
						'menuShowMinLength' => 3,
					),
				),
			),
		);

		/**
		 * Defines configuration for tribute.js autocomplete instances for Tags, Facebook Pages and Twitter Username mentions.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $autocomplete_configuration     Javascript  Autocomplete Configuration.
		 * @param   string  $post_type                      Post Type.
		 */
		$autocomplete_configuration = apply_filters( 'social_post_flow_admin_get_autocomplete_configuration', $autocomplete_configuration );

		// Return.
		return $autocomplete_configuration;

	}

	/**
	 * Add the Plugin to the WordPress Administration Menu
	 *
	 * @since   1.0.0
	 */
	public function admin_menu() {

		// Define the minimum capability required to access settings.
		$minimum_capability = 'manage_options';

		/**
		 * Defines the minimum capability required to access the Plugin's
		 * Menu and Sub Menus
		 *
		 * @since   1.0.0
		 *
		 * @param   string  $capability     Minimum Required Capability.
		 * @return  string                  Minimum Required Capability
		 */
		$minimum_capability = apply_filters( 'social_post_flow_admin_admin_menu_minimum_capability', $minimum_capability );

		/**
		 * Add settings menus and sub menus for the Plugin's settings.
		 *
		 * @since   1.0.0
		 *
		 * @param   string  $minimum_capability     Minimum capability required.
		 */
		do_action( 'social_post_flow_admin_admin_menu', $minimum_capability );

	}

	/**
	 * Define links to display below the Plugin Name on the WP_List_Table at in the Plugins screen.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $links      Links.
	 * @return  array               Links
	 */
	public function plugin_action_links_settings_page( $links ) {

		// Bail if user access doesn't permit access to settings.
		if ( ! social_post_flow()->get_class( 'access' )->can_access( 'show_menu_settings' ) ) {
			return $links;
		}

		// Add link to Plugin settings screen.
		$links['settings'] = sprintf(
			'<a href="%s">%s</a>',
			add_query_arg(
				array(
					'page' => 'social-post-flow-settings',
				),
				admin_url( 'admin.php' )
			),
			__( 'Settings', 'social-post-flow' )
		);

		// Return.
		return $links;

	}

	/**
	 * Outputs the Settings Screen
	 *
	 * @since   1.0.0
	 */
	public function settings_screen() {

		// Setup notices class.
		social_post_flow()->get_class( 'notices' )->set_key_prefix( 'social_post_flow_' . wp_get_current_user()->ID );

		// Maybe save settings.
		$result = $this->save_settings();
		if ( is_wp_error( $result ) ) {
			// Error notice.
			social_post_flow()->get_class( 'notices' )->add_error_notice( $result->get_error_message() );
		} elseif ( $result === true ) {
			// Success notice.
			social_post_flow()->get_class( 'notices' )->add_success_notice( __( 'Settings saved successfully.', 'social-post-flow' ) );
		}

		// If the Plugin isn't connected to the API, show the screen to do this now.
		if ( ! social_post_flow()->get_class( 'validation' )->api_connected() ) {
			$this->auth_screen();
			return;
		}

		// Authentication.
		$access_token = social_post_flow()->get_class( 'settings' )->get_access_token();
		if ( ! empty( $access_token ) ) {
			social_post_flow()->get_class( 'api' )->set_tokens( $access_token );
		}

		// Profiles.
		$profiles = social_post_flow()->get_class( 'api' )->profiles( true, social_post_flow()->get_class( 'common' )->get_transient_expiration_time() );
		if ( is_wp_error( $profiles ) ) {
			social_post_flow()->get_class( 'notices' )->add_error_notice( $profiles->get_error_message() );
		}

		// Get Settings Tab and Post Type we're managing settings for.
		$tab                 = $this->get_tab( $profiles );
		$post_type           = $this->get_post_type_tab();
		$disable_save_button = false;

		// Post Types.
		$post_types_public = social_post_flow()->get_class( 'common' )->get_post_types();
		$post_types        = social_post_flow()->get_class( 'common' )->maybe_remove_post_types_by_role(
			$post_types_public,
			wp_get_current_user()->roles[0]
		);

		// Depending on the screen we're on, load specific options.
		switch ( $tab ) {
			/**
			 * Settings
			 */
			case 'auth':
				// Disconnect URL.
				$disconnect_url = add_query_arg(
					array(
						'page' => 'social-post-flow',
						'social-post-flow-oauth-disconnect' => wp_create_nonce( 'social-post-flow-oauth-disconnect' ),
					),
					admin_url( 'admin.php' )
				);

				// General Settings.
				$override_options = social_post_flow()->get_class( 'common' )->get_override_options();

				// Text to Image Settings.
				$fonts = social_post_flow()->get_class( 'common' )->get_fonts();

				// Log Settings.
				$log_levels = social_post_flow()->get_class( 'log' )->get_level_options();

				// Repost Settings.
				$repost_event_next_scheduled = social_post_flow()->get_class( 'cron' )->get_repost_event_next_scheduled( 'dS F Y, H:i:s' );
				$repost_schedule             = $this->get_setting( '', 'repost_time' );
				$repost_days                 = array_keys( social_post_flow()->get_class( 'common' )->get_days() );

				// Roles.
				$roles = social_post_flow()->get_class( 'common' )->get_user_roles();

				// Documentation URL.
				$documentation_url = 'https://www.socialpostflow.com/documentation/wordpress/authentication-settings/';
				break;

			/**
			 * No Profiles
			 */
			case 'profiles-missing':
				// Disable Save button, as there are no settings displayed to save.
				$disable_save_button = true;

				// Documentation URL.
				$documentation_url = 'https://www.socialpostflow.com/documentation/wordpress/status-settings/';
				break;

			/**
			 * Profiles Error
			 */
			case 'profiles-error':
				// Disable Save button, as there are no settings displayed to save.
				$disable_save_button = true;

				// Documentation URL.
				$documentation_url = 'https://www.socialpostflow.com/documentation/wordpress/status-settings/';
				break;

			/**
			 * Post Type
			 */
			default:
				// Run profiles through role restriction.
				$profiles = social_post_flow()->get_class( 'common' )->maybe_remove_profiles_by_role( $profiles, wp_get_current_user()->roles[0] );

				// Get original statuses that will be stored in a hidden field so they are preserved if the screen is saved
				// with no changes that trigger an update to the hidden field.
				$original_statuses = social_post_flow()->get_class( 'settings' )->get_settings( $post_type );

				// Get some other information.
				$post_type_object  = get_post_type_object( $post_type );
				$actions_plural    = social_post_flow()->get_class( 'common' )->get_post_actions_past_tense();
				$post_actions      = social_post_flow()->get_class( 'common' )->get_post_actions();
				$documentation_url = 'https://www.socialpostflow.com/documentation/wordpress/status-settings/';
				$is_post_screen    = false; // Disables the 'specific' schedule option, which can only be used on individual Per-Post Settings.

				// Check if this Post Type is enabled.
				if ( ! social_post_flow()->get_class( 'settings' )->is_post_type_enabled( $post_type ) ) {
					social_post_flow()->get_class( 'notices' )->add_warning_notice(
						sprintf(
							'%1$s <a href="%2$s" target="_blank">%3$s</a>',
							sprintf(
								/* translators: %1$s: Post Type */
								__( 'To send %1$s to Social Post Flow, at least one action on the Defaults tab must be enabled with a status defined, and at least one social media profile must be enabled below by clicking the applicable profile name and ticking the "Account Enabled" box.', 'social-post-flow' ),
								$post_type_object->label
							),
							$documentation_url,
							__( 'See Documentation', 'social-post-flow' )
						)
					);
				}
				break;
		}

		// Load View.
		include_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/settings.php';

		// Add footer action to output overlay modal markup.
		add_action( 'admin_footer', array( $this, 'output_modal' ) );

	}

	/**
	 * Outputs the auth screen, allowing the user to begin the process of connecting the Plugin
	 * to the API, without showing other settings.
	 *
	 * @since   1.0.0
	 */
	public function auth_screen() {

		$admin_url = add_query_arg(
			array(
				'page' => 'social-post-flow',
			),
			admin_url( 'admin.php' )
		);
		$oauth_url = social_post_flow()->get_class( 'api' )->get_oauth_url( $admin_url );

		// Load View.
		include_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/settings-auth-required.php';

	}

	/**
	 * Outputs the hidden Javascript Modal and Overlay in the Footer
	 *
	 * @since   1.0.0
	 */
	public function output_modal() {

		// Load view.
		require_once SOCIAL_POST_FLOW_PLUGIN_PATH . '_modules/dashboard/views/modal.php';

	}

	/**
	 * Outputs the Bulk Publish Screen
	 *
	 * @since   1.0.0
	 */
	public function bulk_publish_screen() {

		// Setup notices class.
		social_post_flow()->get_class( 'notices' )->set_key_prefix( 'social_post_flow_' . wp_get_current_user()->ID );

		// Set access and refresh tokens.
		social_post_flow()->get_class( 'api' )->set_tokens(
			social_post_flow()->get_class( 'settings' )->get_access_token()
		);

		// Get Profiles.
		$profiles = social_post_flow()->get_class( 'api' )->profiles( true, social_post_flow()->get_class( 'common' )->get_transient_expiration_time() );
		if ( is_wp_error( $profiles ) ) {
			// Set error notice.
			social_post_flow()->get_class( 'notices' )->add_error_notice( $profiles->get_error_message() );

			// Load view.
			include_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/bulk-publish-error.php';
			return;
		}

		// Get Post Types.
		$post_types = social_post_flow()->get_class( 'common' )->get_post_types();

		// Get URL parameters.
		$stage     = $this->get_bulk_publish_stage();
		$post_type = $this->get_post_type_tab();
		if ( empty( $post_type ) ) {
			$post_type = 'post';
		}
		$tags                              = social_post_flow()->get_class( 'common' )->get_tags( $post_type );
		$taxonomies                        = social_post_flow()->get_class( 'common' )->get_taxonomies( $post_type );
		$authors                           = social_post_flow()->get_class( 'common' )->get_authors();
		$comparison_operators              = social_post_flow()->get_class( 'common' )->get_comparison_operators();
		$custom_field_comparison_operators = social_post_flow()->get_class( 'common' )->get_custom_field_comparison_operators();
		$orderby                           = social_post_flow()->get_class( 'common' )->get_order_by();
		$order                             = social_post_flow()->get_class( 'common' )->get_order();

		// Get some additional data, depending on which stage we're on.
		switch ( $stage ) {
			/**
			 * Select Posts
			 */
			case 1:
				// Missing nonce.
				if ( ! isset( $_REQUEST['social_post_flow_nonce'] ) ) {
					social_post_flow()->get_class( 'notices' )->add_error_notice( __( 'Nonce field is missing.', 'social-post-flow' ) );

					// Load view.
					include_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/bulk-publish-error.php';
					return;
				}

				// Invalid nonce.
				if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['social_post_flow_nonce'] ), 'social-post-flow' ) ) {
					// Set error notice.
					social_post_flow()->get_class( 'notices' )->add_error_notice( __( 'Invalid nonce specified.', 'social-post-flow' ) );

					// Load view.
					include_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/bulk-publish-error.php';
					return;
				}

				// Build Search Params.
				$params = array(
					'start_date' => ( isset( $_POST['social-post-flow']['start_date'] ) && ! empty( $_POST['social-post-flow']['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['social-post-flow']['start_date'] ) ) : false ),
					'end_date'   => ( isset( $_POST['social-post-flow']['end_date'] ) && ! empty( $_POST['social-post-flow']['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['social-post-flow']['end_date'] ) ) : false ),
					'authors'    => ( isset( $_POST['social-post-flow']['authors'] ) && ! empty( $_POST['social-post-flow']['authors'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_POST['social-post-flow']['authors'] ) ) ) : false ),
					'meta'       => false,
					's'          => ( isset( $_POST['social-post-flow']['s'] ) && ! empty( $_POST['social-post-flow']['s'] ) ? sanitize_text_field( wp_unslash( $_POST['social-post-flow']['s'] ) ) : false ),
					'taxonomies' => false,
					'orderby'    => ( isset( $_POST['social-post-flow']['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['social-post-flow']['orderby'] ) ) : false ),
					'order'      => ( isset( $_POST['social-post-flow']['order'] ) ? sanitize_text_field( wp_unslash( $_POST['social-post-flow']['order'] ) ) : false ),
				);

				// If the URL request includes Post IDs, we've come from a WP_List_Table
				// Use these IDs.
				if ( isset( $_REQUEST['post_ids'] ) ) {
					$post_ids = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['post_ids'] ) ) );
					foreach ( $post_ids as $key => $post_id ) {
						$post_ids[ $key ] = absint( $post_id );
					}

					$params['post_ids'] = $post_ids;
				}

				// Build Taxonomy Search Params.
				$taxonomies = array();

				if ( isset( $_POST['social-post-flow']['taxonomies'] ) ) {
					foreach ( wp_unslash( $_POST['social-post-flow']['taxonomies'] ) as $taxonomy => $terms ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						// Ignore if no Terms.
						if ( empty( $terms ) ) {
							continue;
						}

						$taxonomies[ $taxonomy ] = explode( ',', $terms );
					}
				}
				if ( ! empty( $taxonomies ) ) {
					$params['taxonomies'] = $taxonomies;
				}

				// Build Meta Search Params.
				$meta = array();
				if ( isset( $_POST['social-post-flow']['meta']['key'] ) ) {
					foreach ( wp_unslash( $_POST['social-post-flow']['meta']['key'] ) as $index => $meta_key ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						// Ignore if the key is blank.
						if ( empty( wp_unslash( $_POST['social-post-flow']['meta']['key'][ $index ] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							continue;
						}
						if ( empty( wp_unslash( $_POST['social-post-flow']['meta']['value'][ $index ] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							continue;
						}
						if ( empty( wp_unslash( $_POST['social-post-flow']['meta']['compare'][ $index ] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							continue;
						}

						// Add meta condition.
						$meta[] = array(
							'key'     => sanitize_text_field( wp_unslash( $_POST['social-post-flow']['meta']['key'][ $index ] ) ),
							'value'   => sanitize_text_field( wp_unslash( $_POST['social-post-flow']['meta']['value'][ $index ] ) ),
							'compare' => sanitize_text_field( wp_unslash( $_POST['social-post-flow']['meta']['compare'][ $index ] ) ),
						);
					}
				}
				if ( ! empty( $meta ) ) {
					$params['meta'] = $meta;
				}

				// Get Post IDs.
				$post_ids = social_post_flow()->get_class( 'bulk_publish' )->get_post_ids( $post_type, $params );

				// Bail if no Post IDs found.
				if ( ! count( $post_ids ) ) {
					// Revert back a stage with an error notice.
					social_post_flow()->get_class( 'notices' )->add_error_notice(
						sprintf(
							/* translators: Post Type Plural Name */
							__( 'No %s found matching the given Bulk Publish conditions. Please adjust / remove conditions as necessary.', 'social-post-flow' ),
							$post_types[ $post_type ]->labels->name
						)
					);
					$stage = 0;
				}
				break;

			/**
			 * Publish
			 */
			case '2':
				// Missing nonce.
				if ( ! isset( $_POST['social_post_flow_nonce'] ) ) {
					social_post_flow()->get_class( 'notices' )->add_error_notice( __( 'Nonce field is missing.', 'social-post-flow' ) );

					// Load view.
					include_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/bulk-publish-error.php';
					return;
				}

				// Invalid nonce.
				if ( ! wp_verify_nonce( sanitize_key( $_POST['social_post_flow_nonce'] ), 'social-post-flow' ) ) {
					// Set error notice.
					social_post_flow()->get_class( 'notices' )->add_error_notice( __( 'Invalid nonce specified. Settings NOT saved.', 'social-post-flow' ) );

					// Load view.
					include_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/bulk-publish-error.php';
					return;
				}

				// Check at least one Post has been selected.
				if ( ! isset( $_POST['social-post-flow']['posts'] ) || count( $_POST['social-post-flow']['posts'] ) === 0 ) {
					// Revert back a stage with an error message.
					social_post_flow()->get_class( 'notices' )->add_error_notice(
						sprintf(
							/* translators: %1$s: Post Type Singular Name */
							__( 'Please select at least one %1$s to publish to Social Post Flow.', 'social-post-flow' ),
							$post_types[ $post_type ]->labels->singular_name
						)
					);
					$stage = 1;

					// Get Posts and Post IDs.
					if ( isset( $_POST['post_ids'] ) ) {
						$post_ids = explode( ',', sanitize_text_field( wp_unslash( $_POST['post_ids'] ) ) );
						$posts    = new WP_Query(
							array(
								'post__in' => $post_ids,
							)
						);
					}
					break;
				}

				// If here, one or more Post(s) were selected.
				// Get Posts and Post IDs.
				$post_ids = wp_unslash( $_POST['social-post-flow']['posts'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				// Localize Bulk Publish script.
				wp_localize_script(
					'social-post-flow-bulk-publish',
					'social_post_flow_bulk_publish',
					array(
						'ajax'               => admin_url( 'admin-ajax.php' ),
						'action'             => 'social_post_flow_bulk_publish',
						'nonce'              => wp_create_nonce( 'social-post-flow-bulk-publish' ),
						'post_ids'           => array_values( $post_ids ),
						'number_of_requests' => absint( count( $post_ids ) ),
						'finished'           => __( 'Finished.', 'social-post-flow' ),
					)
				);
				break;

		}

		// Load View.
		include_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/bulk-publish.php';

	}

	/**
	 * Determines which stage of the Bulk Publish process the user is on.
	 *
	 * Takes into account whether the user is bulk publishing from a WP_List_Table or the Plugin's
	 * Bulk Publish option
	 *
	 * @since   1.0.0
	 *
	 * @return  int     Stage
	 */
	private function get_bulk_publish_stage() {

		if ( isset( $_REQUEST['stage'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return absint( $_REQUEST['stage'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		// If Post IDs are specified in the URL request, we've been redirected from a WP_List_Table.
		if ( isset( $_REQUEST['post_ids'] ) && ! empty( $_REQUEST['post_ids'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return 1;
		}

		return 0;

	}

	/**
	 * Outputs the Log Screen
	 *
	 * @since   1.0.0
	 */
	public function log_screen() {

		// Init table.
		$table = new Social_Post_Flow_Log_Table();
		$table->prepare_items();

		// Load View.
		include_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/log.php';

	}

	/**
	 * Helper method to get the setting value from the plugin settings
	 *
	 * @since   1.0.0
	 *
	 * @param   string $type            Setting Type.
	 * @param   string $key             Setting Key.
	 * @param   mixed  $default_value   Default Value if Setting does not exist.
	 * @return  mixed                   Value
	 */
	public function get_setting( $type = '', $key = '', $default_value = '' ) {

		// Post Type Setting or Bulk Setting.
		if ( post_type_exists( $type ) ) {
			return social_post_flow()->get_class( 'settings' )->get_setting( $type, $key, $default_value );
		}

		// Access token.
		if ( $key === 'access_token' ) {
			return social_post_flow()->get_class( 'settings' )->get_access_token();
		}

		// Depending on the type, return settings / options.
		switch ( $type ) {
			case 'text_to_image':
			case 'log':
			case 'hide_meta_box_by_roles':
			case 'roles':
			case 'custom_tags':
			case 'repost':
				return social_post_flow()->get_class( 'settings' )->get_setting( $type, $key, $default_value );

			default:
				return social_post_flow()->get_class( 'settings' )->get_option( $key, $default_value );
		}

	}

	/**
	 * Helper method to save settings
	 *
	 * @since   1.0.0
	 *
	 * @return  mixed   WP_Error | bool
	 */
	public function save_settings() {

		// Check if a POST request was made.
		if ( ! isset( $_POST['submit'] ) ) {
			return false;
		}

		// Missing nonce.
		if ( ! isset( $_POST['social_post_flow_nonce'] ) ) {
			return new WP_Error(
				'social_post_flow_admin_save_settings_error',
				__( 'Nonce field is missing. Settings NOT saved.', 'social-post-flow' )
			);
		}

		// Invalid nonce.
		if ( ! wp_verify_nonce( sanitize_key( $_POST['social_post_flow_nonce'] ), 'social-post-flow' ) ) {
			return new WP_Error(
				'social_post_flow_admin_save_settings_error',
				__( 'Invalid nonce specified. Settings NOT saved.', 'social-post-flow' )
			);
		}

		// Get URL parameters.
		$tab       = $this->get_tab();
		$post_type = $this->get_post_type_tab();

		switch ( $tab ) {
			/**
			 * Authentication
			 */
			case 'auth':
				// oAuth settings are now handled by this class' oauth() function.
				// Save other Settings.

				// General Settings.
				social_post_flow()->get_class( 'settings' )->update_option( 'test_mode', ( isset( $_POST['test_mode'] ) ? 1 : 0 ) );
				social_post_flow()->get_class( 'settings' )->update_option( 'cron', ( isset( $_POST['cron'] ) ? 1 : 0 ) );
				social_post_flow()->get_class( 'settings' )->update_option( 'cron_delay', ( isset( $_POST['cron_delay'] ) ? absint( $_POST['cron_delay'] ) : 30 ) );
				social_post_flow()->get_class( 'settings' )->update_option( 'override', ( isset( $_POST['override'] ) ? sanitize_text_field( wp_unslash( $_POST['override'] ) ) : 0 ) );

				// Image Settings.
				social_post_flow()->get_class( 'settings' )->update_option( 'text_to_image', ( isset( $_POST['text_to_image'] ) ? wp_unslash( $_POST['text_to_image'] ) : array() ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				// Log Settings.
				// Always force errors.
				$log = isset( $_POST['log'] ) ? wp_unslash( $_POST['log'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( ! isset( $log['log_level'] ) ) {
					$log['log_level'] = array(
						'error',
					);
				} else {
					// 'Error' is disabled on the form and not sent if another option is chosen.
					// We always want errors to be logged so add it to the log levels now.
					$log['log_level'][] = 'error';
				}
				social_post_flow()->get_class( 'settings' )->update_option( 'log', $log );

				// Repost Settings.
				social_post_flow()->get_class( 'settings' )->update_option( 'repost_disable_cron', ( isset( $_POST['repost_disable_cron'] ) ? 1 : 0 ) );
				social_post_flow()->get_class( 'settings' )->update_option(
					'repost_time',
					( isset( $_POST['repost_time'] ) ? wp_unslash( $_POST['repost_time'] ) : array( // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						'mon' => array( 0 ),
						'tue' => array( 0 ),
						'wed' => array( 0 ),
						'thu' => array( 0 ),
						'fri' => array( 0 ),
						'sat' => array( 0 ),
						'sun' => array( 0 ),
					) )
				);
				social_post_flow()->get_class( 'settings' )->update_option( 'repost', ( isset( $_POST['repost'] ) ? wp_unslash( $_POST['repost'] ) : '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				// User Access.
				social_post_flow()->get_class( 'settings' )->update_option( 'hide_meta_box_by_roles', ( isset( $_POST['hide_meta_box_by_roles'] ) ? wp_unslash( $_POST['hide_meta_box_by_roles'] ) : array() ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				social_post_flow()->get_class( 'settings' )->update_option( 'restrict_post_types', ( isset( $_POST['restrict_post_types'] ) ? 1 : 0 ) );
				social_post_flow()->get_class( 'settings' )->update_option( 'restrict_roles', ( isset( $_POST['restrict_roles'] ) ? 1 : 0 ) );
				social_post_flow()->get_class( 'settings' )->update_option( 'roles', ( isset( $_POST['roles'] ) ? wp_unslash( $_POST['roles'] ) : array() ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				// Custom Tags.
				social_post_flow()->get_class( 'settings' )->update_option( 'custom_tags', ( isset( $_POST['custom_tags'] ) ? wp_unslash( $_POST['custom_tags'] ) : '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				// Reschedule CRON events.
				social_post_flow()->get_class( 'cron' )->reschedule_log_cleanup_event();
				social_post_flow()->get_class( 'cron' )->reschedule_repost_event();

				// Done.
				return true;

			/**
			 * Post Type
			 */
			default:
				if ( ! isset( $_POST['social-post-flow']['statuses'] ) ) {
					return new WP_Error(
						'social_post_flow_admin_save_settings_error',
						__( 'Statuses field is missing. Settings NOT saved.', 'social-post-flow' )
					);
				}

				// Unslash and decode JSON field.
				$settings = json_decode( wp_unslash( $_POST['social-post-flow']['statuses'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				// Save Settings for this Post Type.
				return social_post_flow()->get_class( 'settings' )->update_settings( $post_type, $settings );
		}

	}

	/**
	 * Returns the settings tab that the user has selected.
	 *
	 * @since   1.0.0
	 *
	 * @param   mixed $profiles   API Profiles (false|WP_Error|array).
	 * @return  string  Tab
	 */
	private function get_tab( $profiles = false ) {

		$tab = ( isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'auth' ); // phpcs:ignore WordPress.Security.NonceVerification

		// If we're on the Settings tab, return.
		if ( $tab === 'auth' ) {
			return $tab;
		}

		// If Profiles are an error, show error.
		if ( is_wp_error( $profiles ) ) {
			return 'profiles-error';
		}

		// If no Profiles exist, show error.
		if ( is_array( $profiles ) && ! count( $profiles ) ) {
			return 'profiles-missing';
		}

		// Return tab.
		return $tab;

	}

	/**
	 * Returns the Post Type tab that the user has selected.
	 *
	 * @since   1.0.0
	 *
	 * @return  string  Tab
	 */
	private function get_post_type_tab() {

		return ( isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification

	}

}
