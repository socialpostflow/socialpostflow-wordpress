<?php
/**
 * Publish class
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Handles publishing status(es) to the scheduling service
 * based on the Post and Plugin settings, when a Post's
 * status is transitioned.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 * @version 1.0.0
 */
class Social_Post_Flow_Publish {

	/**
	 * Holds all supported Tags and their Post data replacements.
	 *
	 * @since   1.0.0
	 *
	 * @var     array
	 */
	private $all_possible_searches_replacements = false;

	/**
	 * Holds searches and replacements for status messages.
	 *
	 * @since   1.0.0
	 *
	 * @var     array
	 */
	private $searches_replacements = false;

	/**
	 * Constructor
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		// Actions.
		add_action( 'wp_loaded', array( $this, 'register_publish_hooks' ), 1 );
		add_action( 'social-post-flow', array( $this, 'publish' ), 1, 2 );

	}

	/**
	 * Registers publish hooks against all public Post Types,
	 *
	 * @since   1.0.0
	 */
	public function register_publish_hooks() {

		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );

	}

	/**
	 * Fired when a Post's status transitions.  Called by WordPress when wp_insert_post() is called,
	 * and wp_insert_post() is called by WordPress and the REST API whenever creating or updating a Post.
	 *
	 * @since   1.0.0
	 *
	 * @param   string  $new_status     New Status.
	 * @param   string  $old_status     Old Status.
	 * @param   WP_Post $post           Post.
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {

		// Bail if the Post Type isn't public.
		// This prevents the rest of this routine running on e.g. ACF Free, when saving Fields (which results in Field loss).
		$post_types = array_keys( social_post_flow()->get_class( 'common' )->get_post_types() );
		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return;
		}

		// New Post Screen loading.
		// Draft saved.
		if ( $new_status === 'auto-draft' || $new_status === 'draft' || $new_status === 'inherit' || $new_status === 'trash' ) {
			return;
		}

		// Remove actions registered by this Plugin.
		// This ensures that when Page Builders call publish or update events via AJAX, we don't run this multiple times.
		remove_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );
		remove_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_publish' ), 10 );
		remove_action( 'wp_insert_post', array( $this, 'wp_insert_post_update' ), 999 );
		remove_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_update' ), 10 );

		/**
		 * = REST API =
		 * If this is a REST API Request, we can't use the wp_insert_post action, because the metadata
		 * is *not* included in the call to wp_insert_post().  Instead, we must use a late REST API action
		 * that gives the REST API time to save metadata.
		 * Note that the meta being supplied in the REST API Request must be registered with WordPress using
		 * register_meta()
		 *
		 * = Gutenberg =
		 * If Gutenberg is being used on the given Post Type, two requests are sent:
		 * - a REST API request, comprising of Post Data and Metadata registered in Gutenberg,
		 * - a standard request, comprising of Post Metadata registered outside of Gutenberg (i.e. add_meta_box() data)
		 * The second request will be seen by transition_post_status() as an update.
		 * Therefore, we set a meta flag on the first Gutenberg REST API request to defer publishing the status until
		 * the second, standard request - at which point, all Post metadata will be available to the Plugin.
		 *
		 * = Classic Editor =
		 * Metadata is included in the call to wp_insert_post(), meaning that it's saved to the Post before we use it.
		 */

		social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Post ID: #' . $post->ID );

		// If transitioning from future to publish, this is a scheduled Post being published by WordPress Cron.
		// We don't need to know whether it's a Gutenberg, Classic Editor or REST API request.
		if ( $old_status === 'future' && $new_status === 'publish' ) {
			social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Scheduled Post being published by WordPress' );

			add_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );

			// Don't need to do anything else, so exit.
			return;
		}

		// Flag to determine if the current Post is a Gutenberg Post or Rest API Request.
		$is_gutenberg_request = $this->is_gutenberg_request();
		$is_rest_api_request  = $this->is_rest_api_request();
		social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Gutenberg Post: ' . ( $is_gutenberg_request ? 'Yes' : 'No' ) );
		social_post_flow()->get_class( 'log' )->add_to_debug_log( 'REST API Request: ' . ( $is_rest_api_request ? 'Yes' : 'No' ) );

		// If a previous request flagged that an 'update' request should be treated as a publish request (i.e.
		// we're using Gutenberg and request to post.php was made after the REST API), do this now.
		$needs_publishing = get_post_meta( $post->ID, 'social_post_flow_needs_publishing', true );
		if ( $needs_publishing ) {
			// If "Use WP Cron" is enabled, we've already scheduled an event to perform
			// the publish action in Gutenberg's first request. Just delete the flag.
			if ( social_post_flow()->get_class( 'settings' )->get_option( 'cron', false ) ) {
				social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Use WP Cron enabled, so event already scheduled.' );
				return delete_post_meta( $post->ID, 'social_post_flow_needs_publishing' );
			}

			social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Needs Publishing' );

			// Run Publish Status Action now.
			delete_post_meta( $post->ID, 'social_post_flow_needs_publishing' );
			add_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );

			// Don't need to do anything else, so exit.
			return;
		}

		// If a previous request flagged that an update request be deferred (i.e.
		// we're using Gutenberg and request to post.php was made after the REST API), do this now.
		$needs_updating = get_post_meta( $post->ID, 'social_post_flow_needs_updating', true );
		if ( $needs_updating ) {
			// If "Use WP Cron" is enabled, we've already scheduled an event to perform
			// the publish action in Gutenberg's first request. Just delete the flag.
			if ( social_post_flow()->get_class( 'settings' )->get_option( 'cron', false ) ) {
				social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Use WP Cron enabled, so event already scheduled.' );
				return delete_post_meta( $post->ID, 'social_post_flow_needs_updating' );
			}

			social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Needs Updating' );

			// Run Publish Status Action now.
			delete_post_meta( $post->ID, 'social_post_flow_needs_updating' );
			add_action( 'wp_insert_post', array( $this, 'wp_insert_post_update' ), 999 );

			// Don't need to do anything else, so exit.
			return;
		}

		// Publish.
		if ( $new_status === 'publish' && $new_status !== $old_status ) {
			/**
			 * Gutenberg Editor REST API Request
			 * - Non-Gutenberg metaboxes are POSTed via a second, separate request to post.php, which appears
			 * as an 'update'.  Define a meta key that we'll check on the separate request later.
			 */
			if ( $is_gutenberg_request ) {
				social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Defer Publish' );

				update_post_meta( $post->ID, 'social_post_flow_needs_publishing', 1 );

				// If "Use WP Cron" is enabled, schedule the publish() cron event now and exit.
				// Hooking schedule_publish() to wp_insert_post results in wp_schedule_single_event
				// stating it scheduled the event, however the event never gets scheduled when using
				// Gutenberg.  This is likely due to the second Gutenberg request not having the required
				// permissions to actually schedule an event in the WordPress Cron.
				if ( social_post_flow()->get_class( 'settings' )->get_option( 'cron', false ) ) {
					// Don't need to include $test_mode, as Social_Post_Flow_Cron::publish()
					// checks for Test Mode when the event runs.
					return $this->schedule_publish( $post->ID, 'publish' );
				}

				// Don't need to do anything else, so exit.
				return;
			}

			/**
			 * REST API
			 */
			if ( $is_rest_api_request ) {
				social_post_flow()->get_class( 'log' )->add_to_debug_log( 'REST API: Publish' );
				add_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_publish' ), 10, 1 );

				// Don't need to do anything else, so exit.
				return;
			}

			/**
			 * Classic Editor
			 */
			social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Classic Editor: Publish' );
			add_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );

			// Don't need to do anything else, so exit.
			return;
		}

		// Update.
		if ( $new_status === 'publish' && $old_status === 'publish' ) {
			/**
			 * Gutenberg Editor REST API Request
			 * - Non-Gutenberg metaboxes are POSTed via a second, separate request to post.php, which appears
			 * as an 'update'.  Define a meta key that we'll check on the separate request later.
			 */
			if ( $is_gutenberg_request ) {
				social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Defer Update' );

				update_post_meta( $post->ID, 'social_post_flow_needs_updating', 1 );

				// If "Use WP Cron" is enabled, schedule the publish() cron event now and exit.
				// Hooking schedule_publish() to wp_insert_post results in wp_schedule_single_event
				// stating it scheduled the event, however the event never gets scheduled when using
				// Gutenberg.  This is likely due to the second Gutenberg request not having the required
				// permissions to actually schedule an event in the WordPress Cron.
				if ( social_post_flow()->get_class( 'settings' )->get_option( 'cron', false ) ) {
					// Don't need to include $test_mode, as Social_Post_Flow_Cron::publish()
					// checks for Test Mode when the event runs.
					return $this->schedule_publish( $post->ID, 'update' );
				}

				// Don't need to do anything else, so exit.
				return;
			}

			/**
			 * REST API
			 */
			if ( $is_rest_api_request ) {
				social_post_flow()->get_class( 'log' )->add_to_debug_log( 'REST API: Update' );
				add_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_update' ), 10, 1 );

				// Don't need to do anything else, so exit.
				return;
			}

			/**
			 * Classic Editor
			 */
			social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Classic Editor: Update' );
			add_action( 'wp_insert_post', array( $this, 'wp_insert_post_update' ), 999 );

			// Don't need to do anything else, so exit.
			return;
		}

	}

	/**
	 * Helper function to determine if the request is a Gutenberg REST API request.
	 *
	 * @since   1.0.0
	 *
	 * @return  bool    Is Gutenberg REST API Request
	 */
	private function is_gutenberg_request() {

		if ( ! defined( 'REST_REQUEST' ) ) {
			return false;
		}

		if ( ! REST_REQUEST ) {
			return false;
		}

		// Gutenberg requests are REST API requests, but include a _locale key.
		// 'True' REST API requests do not include this key.
		if ( ! filter_has_var( INPUT_POST, '_locale' ) && ! filter_has_var( INPUT_GET, '_locale' ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Helper function to determine if the request is a REST API request.
	 *
	 * @since   1.0.0
	 *
	 * @return  bool    Is REST API Request
	 */
	private function is_rest_api_request() {

		if ( ! defined( 'REST_REQUEST' ) ) {
			return false;
		}

		if ( ! REST_REQUEST ) {
			return false;
		}

		// Gutenberg requests are REST API requests, but include a _locale key.
		// 'True' REST API requests do not include this key.
		if ( filter_has_var( INPUT_POST, '_locale' ) || filter_has_var( INPUT_GET, '_locale' ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Helper function to determine if the Post contains Gutenberg Content.
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post   Post.
	 * @return  bool                Post Content contains Gutenberg Block Markup
	 */
	private function is_gutenberg_post_content( $post ) {

		if ( strpos( $post->post_content, '<!-- wp:' ) !== false ) {
			return true;
		}

		return false;

	}

	/**
	 * Called when a Post has been Published via the REST API.
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post           Post.
	 */
	public function rest_api_post_publish( $post ) {

		$this->wp_insert_post_publish( $post->ID );

	}

	/**
	 * Called when a Post has been Published via the REST API
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post           Post.
	 */
	public function rest_api_post_update( $post ) {

		$this->wp_insert_post_update( $post->ID );

	}

	/**
	 * Called when a Post has been Published
	 *
	 * @since   1.0.0
	 *
	 * @param   int $post_id    Post ID.
	 */
	public function wp_insert_post_publish( $post_id ) {

		// Get Test Mode Flag and Use WP Cron Flag.
		$test_mode   = social_post_flow()->get_class( 'settings' )->get_option( 'test_mode', false );
		$use_wp_cron = social_post_flow()->get_class( 'settings' )->get_option( 'cron', false );

		// If "Use WP Cron" is enabled, schedule the publish() event and exit.
		if ( $use_wp_cron ) {
			// Don't need to include $test_mode, as Social_Post_Flow_Cron::publish()
			// checks for Test Mode when the event runs.
			return $this->schedule_publish( $post_id, 'publish' );
		}

		// Call main function to publish status(es) to social media.
		$results = $this->publish( $post_id, 'publish', $test_mode );

		// If no result, bail.
		if ( ! isset( $results ) ) {
			return;
		}

		// If no errors, return.
		if ( ! is_wp_error( $results ) ) {
			return;
		}

		// If logging is disabled, return.
		$log_enabled = social_post_flow()->get_class( 'log' )->is_enabled();
		if ( ! $log_enabled ) {
			return;
		}

		// The result is a single warning caught before any statuses were sent to the API.
		// Add the warning to the log so that the user can see why no statuses were sent to API.
		social_post_flow()->get_class( 'log' )->add(
			$post_id,
			array(
				'action'         => 'publish',
				'request_sent'   => gmdate( 'Y-m-d H:i:s' ),
				'result'         => 'warning',
				'result_message' => $results->get_error_message(),
			)
		);

	}

	/**
	 * Called when a Post has been Updated
	 *
	 * @since   1.0.0
	 *
	 * @param   int $post_id    Post ID.
	 */
	public function wp_insert_post_update( $post_id ) {

		// If a status was last sent within 5 seconds, don't send it again.
		// Prevents Page Builders that trigger wp_update_post() multiple times on Publish or Update from
		// causing statuses to send multiple times.
		$last_sent = get_post_meta( $post_id, '_social_post_flow_last_sent', true );
		if ( ! empty( $last_sent ) ) {
			$difference = ( current_time( 'timestamp' ) - $last_sent ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
			if ( $difference < 5 ) {
				return;
			}
		}

		// Get Test Mode Flag and Use WP Cron Flag.
		$test_mode   = social_post_flow()->get_class( 'settings' )->get_option( 'test_mode', false );
		$use_wp_cron = social_post_flow()->get_class( 'settings' )->get_option( 'cron', false );

		// If "Use WP Cron" is enabled, schedule the publish() event and exit.
		if ( $use_wp_cron ) {
			// Don't need to include $test_mode, as Social_Post_Flow_Cron::publish()
			// checks for Test Mode when the event runs.
			return $this->schedule_publish( $post_id, 'update' );
		}

		// Call main function to publish status(es) to social media.
		$results = $this->publish( $post_id, 'update', $test_mode );

		// If no result, bail.
		if ( ! isset( $results ) ) {
			return;
		}

		// If no errors, return.
		if ( ! is_wp_error( $results ) ) {
			return;
		}

		// If logging is disabled, return.
		$log_enabled = social_post_flow()->get_class( 'log' )->is_enabled();
		if ( ! $log_enabled ) {
			return;
		}

		// The result is a single error caught before any statuses were sent to the API.
		// Add the error to the log so that the user can see why no statuses were sent to API.
		social_post_flow()->get_class( 'log' )->add(
			$post_id,
			array(
				'action'         => 'update',
				'request_sent'   => gmdate( 'Y-m-d H:i:s' ),
				'result'         => 'warning',
				'result_message' => $results->get_error_message(),
			)
		);

	}

	/**
	 * Called when any Page, Post or CPT is published or updated and Use WP Cron
	 * is enabled, which makes wp_insert_post_publish() and wp_insert_post_update()
	 * call this function.
	 *
	 * See Social_Post_Flow_Cron::publish(), which is fired when the event runs,
	 * and checks the Plugin's Test Mode to determine whether to send or log the status(es).
	 *
	 * @since   1.0.0
	 *
	 * @param   int    $post_id                Post ID.
	 * @param   string $action                 Action (publish|update).
	 * @return  mixed                               WP_Error | API Results array
	 */
	public function schedule_publish( $post_id, $action ) {

		social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Social Post Flow: schedule_publish(): Post ID: #' . $post_id );
		social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Social Post Flow: schedule_publish(): Action: ' . $action );

		// Get settings, validating the Post and Action.
		$settings = $this->validate( $post_id, $action );

		// If an error occured, bail.
		if ( is_wp_error( $settings ) ) {
			social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Social Post Flow: schedule_publish(): Settings Error: ' . $settings->get_error_message() );
			return $settings;
		}

		// If settings are false, we're not sending this Post, so there's no need to schedule an event.
		if ( ! $settings ) {
			social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Social Post Flow: schedule_publish(): Settings are blank, no event needs to be scheduled' );
			return false;
		}

		// Define the number of seconds before the scheduled event should run,
		// relative to the current time.
		$delay = social_post_flow()->get_class( 'settings' )->get_option( 'cron_delay', 30 );

		/**
		 * Define the number of seconds before the scheduled event should run,
		 * relative to the current time.
		 *
		 * @since   1.0.0
		 *
		 * @param   int     $delay      Delay (in seconds).
		 * @param   int     $post_id    Post ID.
		 * @param   string. $action     Action (publish|update).
		 */
		$delay = apply_filters( 'social_post_flow_publish_schedule_publish_delay', $delay, $post_id, $action );

		// Define schedule time and arguments for the event.
		$schedule_time = ( time() + $delay );
		$schedule_args = array(
			$post_id,
			$action,
		);

		// If an event is already scheduled for this Post ID and action, delete it.
		$existing_scheduled_time = wp_next_scheduled( 'social_post_flow_publish_cron', $schedule_args );
		if ( $existing_scheduled_time ) {
			// Unschedule event.
			wp_unschedule_event(
				$existing_scheduled_time,
				'social_post_flow_publish_cron',
				$schedule_args
			);

			// Delete pending entries from the log.
			social_post_flow()->get_class( 'log' )->delete_pending_by_post_id_and_action( $post_id, $action );
		}

		// Schedule registered action.
		$event = wp_schedule_single_event(
			$schedule_time,
			'social_post_flow_publish_cron',
			$schedule_args
		);

		// Bail if an error occured scheduling.
		if ( is_wp_error( $event ) ) {
			social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Social Post Flow: schedule_publish(): Event Error: ' . $event->get_error_message() );
			return $event;
		}

		// Add single log entry.
		$logs = array(
			array(
				'action'         => $action,
				'request_sent'   => gmdate( 'Y-m-d H:i:s', $schedule_time ),
				'profile_id'     => false,
				'profile_name'   => false,
				'result'         => 'pending',
				'result_message' => __( 'Status added to WordPress Cron for sending to Social Post Flow. Check the Post\'s Log after the "Request Sent" date and time to confirm that the status has been added to Social Post Flow.', 'social-post-flow' ),
				'status_text'    => false,
			),
		);

		// Save log, if enabled.
		$log_enabled = social_post_flow()->get_class( 'log' )->is_enabled();
		if ( $log_enabled ) {
			foreach ( $logs as $log ) {
				social_post_flow()->get_class( 'log' )->add( $post_id, $log );
			}
		}

		// Return log results that are scheduled to be sent to the API via CRON.
		return $logs;

	}

	/**
	 * Main function. Called when any Page, Post or CPT is published, updated, reposted
	 * or bulk published.
	 *
	 * @since   1.0.0
	 *
	 * @param   int    $post_id                Post ID.
	 * @param   string $action                 Action (publish|update|repost|bulk_publish).
	 * @param   bool   $test_mode              Test Mode (won't send to API).
	 * @return  mixed                               WP_Error | API Results array
	 */
	public function publish( $post_id, $action, $test_mode = false ) {

		social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Social Post Flow: publish(): Post ID: #' . $post_id );
		social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Social Post Flow: publish(): Action: ' . $action );
		social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Social Post Flow: publish(): Test Mode: ' . ( $test_mode ? 'Yes' : 'No' ) );

		// Get settings, validating the Post and Action.
		$settings = $this->validate( $post_id, $action );

		// If an error occured, bail.
		if ( is_wp_error( $settings ) ) {
			social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Social Post Flow: publish(): Settings Error: ' . $settings->get_error_message() );
			return $settings;
		}

		// If settings are false, we're not sending this Post, so there's no need to schedule an event.
		if ( ! $settings ) {
			return false;
		}

		// Get post.
		$post = get_post( $post_id );

		// Clear any cached data that we have stored in this class.
		$this->clear_search_replacements();

		// Check a valid access token exists.
		$access_token = social_post_flow()->get_class( 'settings' )->get_access_token();
		if ( ! $access_token ) {
			return new WP_Error(
				'social_post_flow_no_access_token',
				__( 'The Plugin has not been authorized with Social Post Flow. Go to Social Post Flow > Settings to setup the plugin.', 'social-post-flow' )
			);
		}

		// Setup API.
		social_post_flow()->get_class( 'api' )->set_tokens( $access_token );

		// Get Profiles.
		$profiles = social_post_flow()->get_class( 'api' )->profiles( false, social_post_flow()->get_class( 'common' )->get_transient_expiration_time() );

		// Bail if the Profiles could not be fetched.
		if ( is_wp_error( $profiles ) ) {
			social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Social Post Flow: publish(): Profiles Error: ' . $profiles->get_error_message() );
			return $profiles;
		}

		// Array for storing statuses we'll send to the API.
		$statuses = array();

		// Run profiles and settings through role restriction, based on the Post's Author.
		$author = get_user_by( 'id', $post->post_author );
		if ( $author !== false ) {
			$profiles = social_post_flow()->get_class( 'common' )->maybe_remove_profiles_by_role( $profiles, $author->roles[0] );
			$settings = social_post_flow()->get_class( 'common' )->maybe_remove_profiles_by_role( $settings, $author->roles[0] );
		}

		// Iterate through each social media profile.
		foreach ( $settings as $profile_id => $profile_settings ) {

			// Skip some setting keys that aren't related to profiles.
			if ( in_array( $profile_id, array( 'featured_image', 'additional_images', 'override' ), true ) ) {
				continue;
			}

			// Get detailed settings from Post or Plugin.
			switch ( social_post_flow()->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[override]', 0 ) ) {
				case '1':
					// Use Post Settings.
					$profile_enabled  = social_post_flow()->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[' . $profile_id . '][enabled]', 0 );
					$profile_override = social_post_flow()->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[' . $profile_id . '][override]', 0 );

					// Use Override Settings.
					if ( $profile_override ) {
						$action_enabled  = social_post_flow()->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[' . $profile_id . '][' . $action . '][enabled]', 0 );
						$status_settings = social_post_flow()->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[' . $profile_id . '][' . $action . '][status]', array() );
					} else {
						$action_enabled  = social_post_flow()->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[default][' . $action . '][enabled]', 0 );
						$status_settings = social_post_flow()->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[default][' . $action . '][status]', array() );
					}
					break;

				case '0':
					// Use Plugin Settings.
					$profile_enabled  = social_post_flow()->get_class( 'settings' )->get_setting( $post->post_type, '[' . $profile_id . '][enabled]', 0 );
					$profile_override = social_post_flow()->get_class( 'settings' )->get_setting( $post->post_type, '[' . $profile_id . '][override]', 0 );

					// Use Override Settings.
					if ( $profile_override ) {
						$action_enabled  = social_post_flow()->get_class( 'settings' )->get_setting( $post->post_type, '[' . $profile_id . '][' . $action . '][enabled]', 0 );
						$status_settings = social_post_flow()->get_class( 'settings' )->get_setting( $post->post_type, '[' . $profile_id . '][' . $action . '][status]', array() );
					} else {
						$action_enabled  = social_post_flow()->get_class( 'settings' )->get_setting( $post->post_type, '[default][' . $action . '][enabled]', 0 );
						$status_settings = social_post_flow()->get_class( 'settings' )->get_setting( $post->post_type, '[default][' . $action . '][status]', array() );
					}
					break;
			}

			// Check if this profile is enabled.
			if ( ! $profile_enabled ) {
				continue;
			}

			// Check if this profile's action is enabled.
			if ( ! $action_enabled ) {
				continue;
			}

			// Determine which social media service this profile ID belongs to.
			foreach ( $profiles as $profile ) {
				if ( (int) $profile['id'] === (int) $profile_id ) {
					$service = $profile['provider'];
					break;
				}
			}

			// Iterate through each Status.
			foreach ( $status_settings as $index => $status ) {
				// If this Status has Post Title, Excerpt or Content conditions enabled, check they are met.
				$conditions_met = $this->check_post_conditions( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// If this Status has Date conditions enabled, check they are met.
				$conditions_met = $this->check_date_conditions( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// If this Status has Taxonomy conditions enabled, check they are met.
				$conditions_met = $this->check_taxonomy_conditions( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// If this Status has Custom Field Conditions, check these Custom Field Conditions are met.
				$conditions_met = $this->check_custom_field_conditions( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// If this Status has Author conditions enabled, check they are met.
				$conditions_met = $this->check_author_condition( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// If this Status has Author Role conditions enabled, check they are met.
				$conditions_met = $this->check_author_role_condition( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// If this Status has Author Custom Field conditions enabled, check these Author Custom Field Conditions are met.
				$conditions_met = $this->check_author_custom_field_conditions( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// Built in conditions are met.
				$conditions_met = true;

				/**
				 * Process condition settings for Integrations / Third Party Plugins
				 *
				 * @since   1.0.0
				 *
				 * @param   array       $status         Status
				 * @param   WP_Post     $post           WordPress Post
				 * @param   string      $profile_id     Social Media Profile ID.
				 * @param   string      $service        Social Media Service.
				 * @param   string      $action         Action (publish|update|repost|bulk_publish).
				 */
				$conditions_met = apply_filters( 'social_post_flow_publish_status_conditions_met', $conditions_met, $status, $post, $profile_id, $service, $action );
				if ( ! $conditions_met ) {
					continue;
				}

				// If here, the status either has no conditions, or the conditions found are met.
				// Add the status to our array for it to be sent to the API.
				$status = $this->build_args( $post, $profile_id, $service, $status, $action );

				// If the status built is a WP_Error, something went wrong with e.g. the image.
				// Include the error object and the profile ID, so the error is logged.
				if ( is_wp_error( $status ) ) {
					$status = array(
						'profile_ids' => array( $profile_id ),
						'error'       => $status,
					);
				}

				// Add status to array of statuses.
				$statuses[] = $status;
			}
		}

		social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Social Post Flow: publish(): Statuses: ' . print_r( $statuses, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions

		// Check if any statuses exist.
		// If not, exit.
		if ( count( $statuses ) === 0 ) {
			// Fetch Post Type object and Settings URL.
			$post_type_object = get_post_type_object( $post->post_type );
			$plugin_url       = admin_url( 'admin.php?page=social-post-flow&tab=post&type=' . $post->post_type );
			$post_url         = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

			// Return an error, depending on why no statuses were found.
			if ( isset( $conditions_met ) && ! $conditions_met ) {
				$error = new WP_Error(
					'social_post_flow_no_statuses_conditions',
					sprintf(
						/* translators: %1$s: Post Type Name, Singular, %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %3$s: Action (Publish, Update, Repost, Bulk Publish), %4$s, %5$s, %6$s: Post Type Name, Singular, %7$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %8$s: Plugin URL, %9$s: Plugin Name, %10$s: Post Type Name, Singular, %11$s: Action (Publish, Update, Repost, Bulk Publish) */
						__( 'Status(es) exist for sending this %1$s to %2$s when you %3$s a %4$s, but no status was sent because the %5$s did not meet the status conditions. If you want this %6$s to be sent to %7$s, navigate to <a href="%8$s" target="_blank">%9$s > Settings > %10$s Tab > %11$s Action Tab</a>, ensuring that no Conditions are set on the defined statuses.', 'social-post-flow' ),
						$post_type_object->labels->singular_name,
						__( 'Social Post Flow', 'social-post-flow' ),
						ucwords( str_replace( '_', ' ', $action ) ),
						$post_type_object->labels->singular_name,
						$post_type_object->labels->singular_name,
						$post_type_object->labels->singular_name,
						__( 'Social Post Flow', 'social-post-flow' ),
						$plugin_url,
						__( 'Social Post Flow', 'social-post-flow' ),
						$post_type_object->labels->name,
						ucwords( str_replace( '_', ' ', $action ) )
					)
				);

				social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Social Post Flow: publish(): Statuses Error: ' . $error->get_error_message() );

				return $error;
			} else {
				if ( social_post_flow()->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[override]', 0 ) ) {
					// Post's Manual Settings don't permit sending to API.
					$error = new WP_Error(
						'social_post_flow_no_statuses_enabled',
						sprintf(
							/* translators: %1$s: Post Type Name, Singular, %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %3$s: Action (Publish, Update, Repost, Bulk Publish), %4$s, %5$s, %6$s: Post Type Name, Singular, %7$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %8$s: Plugin URL, %9$s: Plugin Name, %10$s: Post Type Name, Singular, %11$s: Action (Publish, Update, Repost, Bulk Publish) */
							__( 'No %1$s Settings are defined for sending this %2$s to %3$s when you %4$s. To send statuses to %5$s on %6$s, <a href="%7$s" target="_blank">Edit the Post</a>, navigate to %8$s > Defaults > %9$s Action Tab, tick "Enabled" and also enable at least one social media profile.', 'social-post-flow' ),
							$post_type_object->labels->singular_name,
							$post_type_object->labels->singular_name,
							__( 'Social Post Flow', 'social-post-flow' ),
							ucwords( str_replace( '_', ' ', $action ) ),
							__( 'Social Post Flow', 'social-post-flow' ),
							ucwords( str_replace( '_', ' ', $action ) ),
							$post_url,
							__( 'Social Post Flow', 'social-post-flow' ),
							ucwords( str_replace( '_', ' ', $action ) )
						)
					);
				} else {
					$error = new WP_Error(
						'social_post_flow_no_statuses_enabled',
						sprintf(
							/* translators: %1$s: Post Type Name, Singular, %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %3$s: Action (Publish, Update, Repost, Bulk Publish), %4$s, %5$s, %6$s: Post Type Name, Singular, %7$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %8$s: Plugin URL, %9$s: Plugin Name, %10$s: Post Type Name, Singular, %11$s: Action (Publish, Update, Repost, Bulk Publish) */
							__( 'No Plugin Settings are defined for sending %1$s to %2$s when you %3$s a %4$s. To send statuses to %5$s on %6$s, navigate to <a href="%7$s" target="_blank">%8$s > Settings > %9$s Tab > %10$s Action Tab</a>, tick "Enabled", and also enable at least one social media profile.', 'social-post-flow' ),
							$post_type_object->labels->name,
							__( 'Social Post Flow', 'social-post-flow' ),
							ucwords( str_replace( '_', ' ', $action ) ),
							$post_type_object->labels->singular_name,
							__( 'Social Post Flow', 'social-post-flow' ),
							ucwords( str_replace( '_', ' ', $action ) ),
							$plugin_url,
							__( 'Social Post Flow', 'social-post-flow' ),
							$post_type_object->labels->name,
							ucwords( str_replace( '_', ' ', $action ) )
						)
					);
				}

				social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Social Post Flow: publish(): Statuses Error: ' . $error->get_error_message() );

				return $error;
			}
		}

		/**
		 * Determine the statuses to send, just before they're sent. Statuses can be added, edited
		 * and/or deleted as necessary here.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $statuses   Statuses to be sent to social media.
		 * @param   int     $post_id    Post ID.
		 * @param   string  $action     Action (publish, update, repost).
		 */
		$statuses = apply_filters( 'social_post_flow_publish_statuses', $statuses, $post_id, $action );

		// Debugging.
		social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Statuses: ' . print_r( $statuses, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions

		// Send status messages to the API.
		$results = $this->send( $statuses, $post_id, $action, $profiles, $test_mode );

		// If no results, we're finished.
		if ( empty( $results ) || count( $results ) === 0 ) {
			return false;
		}

		return $results;

	}

	/**
	 * Performs pre-publish and pre-schedule publish validation checks, including
	 * - if the action is supported
	 * - if the Post exists
	 * - if the Post Type's supported
	 * - whether the Post override disables sending statuses
	 *
	 * @since   1.0.0
	 *
	 * @param   int    $post_id                Post ID.
	 * @param   string $action                 Action (publish|update).
	 * @return  WP_Error|bool|array
	 */
	private function validate( $post_id, $action ) {

		// Bail if the action isn't supported.
		$supported_actions = array_keys( social_post_flow()->get_class( 'common' )->get_post_actions() );
		if ( ! in_array( $action, $supported_actions, true ) ) {
			return new WP_Error(
				'wp_to_social_post_flow_publish_invalid_action',
				sprintf(
					/* translators: Action */
					__( 'The %s action is not supported.', 'social-post-flow' ),
					$action
				)
			);
		}

		// Get Post.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'no_post',
				sprintf(
					/* translators: Post ID */
					__( 'No WordPress Post could be found for Post ID %s', 'social-post-flow' ),
					$post_id
				)
			);
		}

		// Bail if the Post Type isn't supported.
		// This prevents non-public Post Types sending status(es) where Post Level Default = Post using Manual Settings
		// and this non-public Post Type has been created by copying metadata from a public Post Type that specifies.
		// Post-specific status settings.
		$supported_post_types = array_keys( social_post_flow()->get_class( 'common' )->get_post_types() );
		if ( ! in_array( get_post_type( $post ), $supported_post_types, true ) ) {
			return false;
		}

		/**
		 * If a draft or new Post is published, this function is always called before Social_Post_Flow::save_post()
		 * We can't control this, therefore we need to save the Post's plugin settings first, before checking them -
		 * otherwise we would be looking at an old copy of the Post's settings (if any exist).
		 */
		if ( in_array( $action, array( 'publish', 'update' ), true ) ) {
			social_post_flow()->get_class( 'post' )->save_post( $post_id );
		}

		// Get Settings from either this Post or the Plugin's Settings, depending
		// on the Post's override setting.
		switch ( social_post_flow()->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[override]', 0 ) ) {
			case '1':
				// Use Post Settings.
				return social_post_flow()->get_class( 'post' )->get_settings( $post->ID );

			case '0':
				// Use Plugin Settings.
				return social_post_flow()->get_class( 'settings' )->get_settings( get_post_type( $post ) );

			case '-1':
				// Do not Post.
				return false;

		}

		// Shouldn't ever reach here, but if we do, something went wrong.
		return false;

	}

	/**
	 * Checks whether the Post Title meets the status' Post Conditions, if any,
	 * to determine if the status can be sent to the API.
	 *
	 * @since   1.0.0
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_post_conditions( $status, $post ) {

		// Define Post Keys to test.
		$post_keys = array(
			'post_title',
			'post_excerpt',
			'post_content',
		);

		foreach ( $post_keys as $post_key ) {
			// Skip if no key or comparison is defined.
			if ( ! isset( $status[ $post_key ] ) ) {
				continue;
			}
			if ( ! is_array( $status[ $post_key ] ) ) {
				continue;
			}
			if ( ! isset( $status[ $post_key ]['compare'] ) ) {
				continue;
			}
			if ( empty( $status[ $post_key ]['compare'] ) ) {
				continue;
			}

			// Get the Post Key's value.
			switch ( $post_key ) {
				case 'post_title':
					$post_value = $this->get_title( $post );
					break;

				case 'post_excerpt':
					$post_value = $this->get_excerpt( $post, false );
					break;

				case 'post_content':
					$post_value = $this->get_content( $post );
					break;
			}

			// Test condition.
			$condition_passed = $this->condition_passed( $status[ $post_key ]['compare'], 'post', $post->ID, $post_value, $status[ $post_key ]['value'], false );
			if ( ! $condition_passed ) {
				return false;
			}
		}

		// If here, conditions met.
		return true;

	}

	/**
	 * Checks whether the Post meets the status' Date Conditions, if any,
	 * to determine if the status can be sent to the API
	 *
	 * @since   1.0.0
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_date_conditions( $status, $post ) {

		// Conditions met if no start or end date specified.
		if ( ! isset( $status['start_date'] ) ) {
			return true;
		}
		if ( ! isset( $status['end_date'] ) ) {
			return true;
		}

		// Conditions met if start or end date are blank.
		if ( in_array( '', $status['start_date'], true ) ) {
			return true;
		}
		if ( in_array( '', $status['end_date'], true ) ) {
			return true;
		}

		// Fetch the Post Date, changing the year to this year.
		$post_date = new DateTime( $post->post_date );
		$post_date = strtotime( gmdate( 'Y' ) . '-' . $post_date->format( 'm-d' ) );

		// Define the start and end dates.
		$start_date = strtotime( gmdate( 'Y' ) . '-' . $status['start_date']['month'] . '-' . $status['start_date']['day'] );
		$end_date   = strtotime( gmdate( 'Y' ) . '-' . $status['end_date']['month'] . '-' . $status['end_date']['day'] );

		// Check if the Post's Date falls within the start and end date.
		if ( $post_date >= $start_date && $post_date <= $end_date ) {
			return true;
		}

		// Conditions not met.
		return false;

	}

	/**
	 * Checks whether the Post meets the status' Taxonomy Conditions, if any,
	 * to determine if the status can be sent to the API
	 *
	 * @since   1.0.0
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_taxonomy_conditions( $status, $post ) {

		// Conditions met if no Taxonomy Conditions are specified.
		if ( ! isset( $status['conditions'] ) ) {
			return true;
		}
		if ( empty( $status['conditions'] ) ) {
			return true;
		}
		if ( ! is_array( $status['conditions'] ) ) {
			return true;
		}
		if ( ! count( array_filter( $status['conditions'] ) ) ) {
			return true;
		}

		foreach ( $status['conditions'] as $taxonomy => $method ) {
			// Skip if no method is defined; this means no condition is set.
			if ( empty( $method ) ) {
				continue;
			}

			// Skip if no terms defined; we can't test a condition with no terms.
			if ( ! isset( $status['terms'][ $taxonomy ] ) ) {
				continue;
			}
			if ( empty( $status['terms'][ $taxonomy ] ) ) {
				continue;
			}
			if ( ! is_array( $status['terms'][ $taxonomy ] ) ) {
				continue;
			}
			if ( ! count( array_filter( $status['terms'][ $taxonomy ] ) ) ) {
				continue;
			}

			// Fetch Post Term IDs.
			$post_term_ids = wp_get_post_terms(
				$post->ID,
				$taxonomy,
				array(
					'fields' => 'ids',
				)
			);

			// Skip if an error occured (e.g. a Taxonomy Condition was set for a Taxonomy
			// that no longer exists).
			if ( is_wp_error( $post_term_ids ) ) {
				continue;
			}

			// Fetch Condition Term IDs.
			$condition_term_ids = $status['terms'][ $taxonomy ];

			// Depending on the condition method, determine whether the status
			// should be sent to the API.
			switch ( $method ) {
				/**
				 * Post must include ANY one of the condition terms
				 */
				case 'include_any':
					foreach ( $condition_term_ids as $condition_term_id ) {
						// If the Condition Term ID is in this Post, condition met.
						if ( in_array( (int) $condition_term_id, $post_term_ids, true ) ) {
							break 2;
						}
					}

					// If here, condition not met.
					return false;

				/**
				 * Post must include ALL of the condition terms
				 */
				case 'include_all':
					foreach ( $condition_term_ids as $condition_term_id ) {
						// If the Condition Term ID is not in this Post, condition not met.
						if ( ! in_array( (int) $condition_term_id, $post_term_ids, true ) ) {
							return false;
						}
					}
					break;

				/**
				 * Post must not have ANY one of the condition terms
				 */
				case 'exclude_any':
					foreach ( $condition_term_ids as $condition_term_id ) {
						// If the Condition Term ID is in this Post, condition not met.
						if ( in_array( (int) $condition_term_id, $post_term_ids, true ) ) {
							return false;
						}
					}
					break;
			}
		}

		// If here, conditions met.
		return true;

	}

	/**
	 * Checks whether the Post meets the status' Custom Field Conditions, if any,
	 * to determine if the status can be sent to the API.
	 *
	 * @since   1.0.0
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_custom_field_conditions( $status, $post ) {

		// Conditions met if no Custom Field Conditions are specified.
		if ( ! isset( $status['custom_fields'] ) ) {
			return true;
		}

		// Conditions met is Custom Field Conditions are not an array.
		if ( ! is_array( $status['custom_fields'] ) ) {
			return true;
		}

		// Conditions met if no Custom Field Conditions in the array.
		if ( ! count( $status['custom_fields'] ) ) {
			return true;
		}

		foreach ( $status['custom_fields'] as $custom_field ) {
			// Skip if no key or comparison is defined.
			if ( empty( $custom_field['key'] ) ) {
				continue;
			}
			if ( empty( $custom_field['compare'] ) ) {
				continue;
			}

			// Get the Post's meta value.
			$post_meta_value = get_post_meta( $post->ID, $custom_field['key'], true );

			// Test condition.
			$condition_passed = $this->condition_passed( $custom_field['compare'], 'post', $post->ID, $post_meta_value, $custom_field['value'], $custom_field['key'] );
			if ( ! $condition_passed ) {
				return false;
			}
		}

		// If here, conditions met.
		return true;

	}

	/**
	 * Checks whether the Post meets the status' Author Condition, if any,
	 * to determine if the status can be sent to the API.
	 *
	 * @since   1.0.0
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_author_condition( $status, $post ) {

		// Conditions met if no Authors are specified.
		if ( ! isset( $status['authors'] ) ) {
			return true;
		}
		if ( ! is_array( $status['authors'] ) ) {
			return true;
		}

		// Remove empty Authors.
		$status['authors'] = array_filter( $status['authors'] );

		// Conditions met if no Authors are specified after filtering the array.
		if ( ! count( $status['authors'] ) ) {
			return true;
		}

		// Test condition.
		switch ( $status['authors_compare'] ) {
			/**
			 * Not Equals
			 */
			case '!=':
				// Condition fails if the Post Author is in the array of Authors.
				if ( in_array( $post->post_author, $status['authors'], true ) ) {
					return false;
				}
				break;

			/**
			 * Equals
			 */
			case '=':
			default:
				// Condition fails if the Post Author is not in the array of Authors.
				if ( ! in_array( $post->post_author, $status['authors'], true ) ) {
					return false;
				}
				break;
		}

		// If here, condition passes.
		return true;

	}

	/**
	 * Checks whether the Post meets the status' Author Role Condition, if any,
	 * to determine if the status can be sent to the API.
	 *
	 * @since   1.0.0
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_author_role_condition( $status, $post ) {

		// Conditions met if no Roles are specified.
		if ( ! isset( $status['authors_roles'] ) ) {
			return true;
		}
		if ( ! is_array( $status['authors_roles'] ) ) {
			return true;
		}

		// Remove empty Roles.
		$status['authors_roles'] = array_filter( $status['authors_roles'] );

		// Conditions met if no Roles are specified after filtering the array.
		if ( ! count( $status['authors_roles'] ) ) {
			return true;
		}

		// Get Author's Role(s).
		$author_metadata = get_userdata( $post->post_author );

		// Test condition.
		switch ( $status['authors_roles_compare'] ) {
			/**
			 * Not Equals
			 */
			case '!=':
				// Condition fails if any one of the Post Author's Role(s) exists in the array of Author Roles.
				foreach ( $author_metadata->roles as $role ) {
					if ( in_array( $role, $status['authors_roles'], true ) ) {
						return false;
					}
				}

				// If here, none of the Post Author's Role(s) exist in the array of Author Roles, so the condition passes.
				return true;

			/**
			 * Equals
			 */
			case '=':
			default:
				// Condition passes if any one of Post Author's Role(s) exists in the array of Author Roles.
				foreach ( $author_metadata->roles as $role ) {
					if ( in_array( $role, $status['authors_roles'], true ) ) {
						return true;
					}
				}

				// If here, none of the Post Author's Role(s) exist in the array of Author Roles, so the condition fails.
				return false;
		}

	}

	/**
	 * Checks whether the Post meets the status' Author's Custom Field Conditions, if any,
	 * to determine if the status can be sent to the API.
	 *
	 * @since   1.0.0
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_author_custom_field_conditions( $status, $post ) {

		// Conditions met if no Author Custom Field Conditions are specified.
		if ( ! isset( $status['authors_custom_fields'] ) ) {
			return true;
		}

		// Conditions met if Author Custom Field Conditions are not an array.
		if ( ! is_array( $status['authors_custom_fields'] ) ) {
			return true;
		}

		// Conditions met if no Author Custom Field Conditions in the array.
		if ( ! count( $status['authors_custom_fields'] ) ) {
			return true;
		}

		foreach ( $status['authors_custom_fields'] as $custom_field ) {
			// Skip if no key or comparison is defined.
			if ( empty( $custom_field['key'] ) ) {
				continue;
			}
			if ( empty( $custom_field['compare'] ) ) {
				continue;
			}

			// Get the Post Author's meta value.
			$user_meta_value = get_user_meta( $post->post_author, $custom_field['key'], true );

			// Test condition.
			$condition_passed = $this->condition_passed( $custom_field['compare'], 'user', $post->post_author, $user_meta_value, $custom_field['value'], $custom_field['key'] );

			if ( ! $condition_passed ) {
				return false;
			}
		}

		// If here, conditions met.
		return true;

	}

	/**
	 * Determines if the conditional query passes or fails.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $comparison         Comparison Method.
	 * @param   string $type               Type (post|user).
	 * @param   int    $id                 Post or Author ID.
	 * @param   string $value              Post Value.
	 * @param   string $condition_value    Condition Value.
	 * @param   mixed  $condition_key      Condition Key (false | string).
	 * @return  bool                        Condition Passed
	 */
	private function condition_passed( $comparison, $type, $id, $value, $condition_value, $condition_key = false ) {

		switch ( $comparison ) {
			case '=':
				if ( $value == $condition_value ) {  // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					return true;
				}

				return false;

			case '!=':
				if ( $value != $condition_value ) {  // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
					return true;
				}

				return false;

			case '>':
				if ( $value > $condition_value ) {
					return true;
				}

				return false;

			case '>=':
				if ( $value >= $condition_value ) {
					return true;
				}

				return false;

			case '<':
				if ( $value < $condition_value ) {
					return true;
				}

				return false;

			case '<=':
				if ( $value <= $condition_value ) {
					return true;
				}

				return false;

			case 'IN':
				return in_array( $value, explode( ',', $condition_value ) ); // phpcs:ignore WordPress.PHP.StrictInArray

			case 'NOT IN':
				return ! in_array( $value, explode( ',', $condition_value ) );  // phpcs:ignore WordPress.PHP.StrictInArray

			case 'LIKE':
				if ( stripos( $value, $condition_value ) !== false ) {
					return true;
				}

				return false;

			case 'NOT LIKE':
				if ( stripos( $value, $condition_value ) === false ) {
					return true;
				}

				return false;

			case 'EMPTY':
				if ( empty( $value ) ) {
					return true;
				}

				return false;

			case 'NOT EMPTY':
				if ( ! empty( $value ) ) {
					return true;
				}

				return false;

			case 'EXISTS':
				if ( metadata_exists( $type, $id, $condition_key ) ) {
					return true;
				}

				return false;

			case 'NOT EXISTS':
				if ( ! metadata_exists( $type, $id, $condition_key ) ) {
					return true;
				}

				return false;

			default:
				/**
				 * Determine if a statuses meta conditionals have been met, where the conditional
				 * is not a plugin standard option.
				 *
				 * @since   1.0.0
				 *
				 * @param   bool    $condition_passed   Condition Passed.
				 * @param   string  $comparison         Comparison Method.
				 * @param   string  $type               Type (post|user).
				 * @param   int     $id                 Post or Author ID.
				 * @param   string  $post_value         Post Value.
				 * @param   string  $condition_value    Condition Value.
				 * @param   mixed   $condition_key      Condition Key (false | string).
				 */
				return apply_filters( 'social_post_flow_publish_condition_passed', true, $comparison, $type, $id, $value, $condition_value, $condition_key = false );
		} // switch.

	}

	/**
	 * Helper method to build arguments and create a status via the API
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post                       Post.
	 * @param   string  $profile_id                 Profile ID.
	 * @param   string  $service                    Service.
	 * @param   array   $status                     Status Settings.
	 * @param   string  $action                     Action (publish|update|repost|bulk_publish).
	 * @return  WP_Error|array
	 */
	private function build_args( $post, $profile_id, $service, $status, $action ) {

		// For some services, the post_type may need to be changed to a supported post type.
		// This might happen if e.g. only defaults are set, and per-profile settings are not defined.
		switch ( $service ) {
			/**
			 * Instagram:
			 * - If `image` or `story` is not specified, default to `image`.
			 */
			case 'instagram':
				if ( ! in_array( $status['post_type'], array( 'image', 'story' ), true ) ) {
					$status['post_type'] = 'image';
				}
				break;

			/**
			 * TikTok:
			 * - If `image` is not specified, default to `image`.
			 */
			case 'tiktok':
				if ( ! in_array( $status['post_type'], array( 'image' ), true ) ) {
					$status['post_type'] = 'image';
				}
				break;

			/**
			 * Pinterest: Change post type to `pin`.
			 */
			case 'pinterest':
				$status['post_type'] = 'pin';
				break;

			/**
			 * Google: Change post type to `google`.
			 */
			case 'google':
				$status['post_type'] = 'google';
				break;
		}

		// Build API compatible arguments.
		$args = array(
			'post_type'   => $status['post_type'],
			'profile_ids' => array( $profile_id ),
			'text'        => $this->parse_text( $post, $status['text'], ( $service === 'instagram' ? true : false ) ),
		);

		// First Comment.
		switch ( $service ) {
			case 'mastodon':
			case 'tiktok':
			case 'telegram':
			case 'google':
				// First comment is not supported for these services.
				break;

			default:
				// First comment is supported for these services.
				// Pinterest: uses this for the description field on the Pin.
				$args['first_comment'] = $this->parse_text( $post, $status['first_comment'], ( $service === 'instagram' ? true : false ) );
				break;
		}

		// URL.
		switch ( $args['post_type'] ) {
			/**
			 * Link
			 */
			case 'link':
				$args['url'] = $this->parse_text( $post, $status['url'] );

				// If the URL is empty, use the Post's URL, as a URL is required.
				if ( empty( $args['url'] ) ) {
					$args['url'] = $this->get_permalink( $post );
				}
				break;

			/**
			 * Pinterest
			 * Google
			 */
			case 'pin':
			case 'google':
				// Get URL.
				$url = $this->parse_text( $post, $status['url'] );

				// If URL is empty, don't include it in the args.
				if ( empty( $url ) ) {
					break;
				}

				// Add URL to args.
				$args['url'] = $url;
				break;

			/**
			 * IG Story
			 * - Remove first comment.
			 */
			case 'story':
				unset( $args['first_comment'] );
				break;
		}

		// Image(s).
		switch ( $args['post_type'] ) {
			case 'pin':
			case 'google':
			case 'story':
			case 'image':
				switch ( $status['image'] ) {
					/**
					 * Featured, Additional or Content Image
					 */
					case 'featured_image':
						// Plugin's First (Featured) Image, Post's Featured Image or Post Content's First Image.
						$image = $this->get_post_image( $post );

						// If the image is a WP_Error object, log it and return.
						if ( is_wp_error( $image ) ) {
							social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Image Error: ' . $image->get_error_message() );
							return $image;
						}

						// Add image to media_urls.
						$args['media_urls'] = array( $image );

						// Additional Images.
						$additional_images = $this->get_additional_images( $post, $service, $status );

						if ( $additional_images !== false ) {
							$args['media_urls'] = array_merge( $args['media_urls'], $additional_images );
						}
						break;

					case 'text_to_image':
						$text_to_image = $this->parse_text( $post, $status['text_to_image'], true );

						// Generate Image from Text.
						$image = $this->get_text_to_image( $text_to_image, $service, $profile_id, $post->ID, $status, $status['post_type'] );

						// If the image is a WP_Error object, log it and return.
						if ( is_wp_error( $image ) ) {
							social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Image Error: ' . $image->get_error_message() );
							return $image;
						}

						// Add image to media_urls.
						$args['media_urls'] = array( $image );
						break;

					default:
						$image = false;

						/**
						 * Allows third party integrations to define the image to use for the status.
						 *
						 * @since   1.0.0
						 *
						 * @param   false       $image                      Image.
						 * @param   int         $image_setting              Image Setting.
						 * @param   WP_Post     $post                       WordPress Post.
						 * @param   string      $profile_id                 Social Media Profile ID.
						 * @param   string      $service                    Social Media Service.
						 * @param   array       $status                     Parsed Status Message Settings.
						 * @param   string      $action                     Action (publish|update|repost|bulk_publish).
						 */
						$image = apply_filters( 'social_post_flow_publish_build_args_image', $image, $status['image'], $post, $profile_id, $service, $status, $action );

						// If the image is a WP_Error object, log it and return.
						if ( is_wp_error( $image ) ) {
							social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Image Error: ' . $image->get_error_message() );
							return $image;
						}

						// Add image to media_urls.
						$args['media_urls'] = array( $image );

						// Additional Images.
						$additional_images = $this->get_additional_images( $post, $service, $status );

						if ( $additional_images !== false ) {
							$args['media_urls'] = array_merge( $args['media_urls'], $additional_images );
						}
						break;

				}
		}

		// Scheduling.
		switch ( $status['schedule'] ) {
			case 'queue_end':
			case 'queue_start':
			case 'immediate':
				$args['schedule_type'] = $status['schedule'];
				break;

			/**
			 * Custom Time
			 */
			case 'custom':
				// Check days, hours, minutes are set.
				if ( empty( $status['days'] ) ) {
					$status['days'] = 0;
				}
				if ( empty( $status['hours'] ) ) {
					$status['hours'] = 0;
				}
				if ( empty( $status['minutes'] ) ) {
					$status['minutes'] = 0;
				}

				// Define the Post Date, depending on the action.
				switch ( $action ) {
					case 'publish':
						$post_date = $post->post_date_gmt;
						break;

					case 'update':
						$post_date = $post->post_modified_gmt;
						break;

					case 'repost':
					case 'bulk_publish':
						$post_date = gmdate( 'Y-m-d H:i:s' );
						break;
				}

				// Add days, hours and minutes.
				$timestamp = strtotime( '+' . $status['days'] . ' days ' . $status['hours'] . ' hours ' . $status['minutes'] . ' minutes', strtotime( $post_date ) );

				// No need to adjust for UTC here, as the date we're using is already UTC/GMT.
				$args['schedule_type'] = 'scheduled';
				$args['scheduled_at']  = gmdate( 'Y-m-d H:i:s', $timestamp );
				break;

			/**
			 * Custom Time (Relative Format)
			 */
			case 'custom_relative':
				// Define the Post Date, depending on the action.
				switch ( $action ) {
					case 'publish':
						$post_date = $post->post_date_gmt;
						break;

					case 'update':
						$post_date = $post->post_modified_gmt;
						break;

					case 'repost':
					case 'bulk_publish':
						$post_date = gmdate( 'Y-m-d H:i:s' );
						break;
				}

				// Define scheduled date and time based on the Relative Format.
				switch ( $status['schedule_relative_day'] ) {
					case 'today':
					case 'tomorrow':
						$timestamp = strtotime( $status['schedule_relative_day'] . ' ' . $status['schedule_relative_time'] );
						break;

					default:
						$timestamp = strtotime( 'next ' . $status['schedule_relative_day'] . ' ' . $status['schedule_relative_time'] );
						break;
				}

				// No need to adjust for UTC here, as the date we're using is already UTC/GMT.
				$args['schedule_type'] = 'scheduled';
				$args['scheduled_at']  = gmdate( 'Y-m-d H:i:s', $timestamp );
				break;

			case 'custom_field':
				// Check days, hours, minutes are set.
				if ( empty( $status['days'] ) ) {
					$status['days'] = 0;
				}
				if ( empty( $status['hours'] ) ) {
					$status['hours'] = 0;
				}
				if ( empty( $status['minutes'] ) ) {
					$status['minutes'] = 0;
				}

				// Fetch the Post's Meta Value based on the given Custom Field Key.
				$post_date = get_post_meta( $post->ID, $status['schedule_custom_field_name'], true );

				// If the post date is numeric, it's most likely a timestamp
				// Convert it to a date and time.
				if ( is_numeric( $post_date ) ) {
					$post_date = gmdate( 'Y-m-d H:i:s', $post_date );
				}

				// Get adjusted date and time.
				$date_time = social_post_flow()->get_class( 'date' )->adjust_date_time(
					$post_date,
					$status['schedule_custom_field_relation'],
					$status['days'],
					$status['hours'],
					$status['minutes']
				);

				// Return UTC date and time.
				$args['schedule_type'] = 'scheduled';
				$args['scheduled_at']  = social_post_flow()->get_class( 'date' )->get_utc_date_time( $date_time );
				break;

			/**
			 * Specific Date and Time
			 */
			case 'specific':
				/**
				 * The datetime that we send via the API must be in UTC, so that the social media service can then apply
				 * its timezone offset as defined by the user account's settings.
				 *
				 * For example, 2018-09-01 13:00:00 in a UTC+1 timezone will be sent as 2018-09-01 12:00:00, and scheduled as
				 * 2018-09-01 13:00:00, because the social media services' timezone will add an hour back to the scheduled
				 * datetime.
				 */
				$args['schedule_type'] = 'scheduled';
				$args['scheduled_at']  = social_post_flow()->get_class( 'date' )->get_utc_date_time( gmdate( 'Y-m-d H:i:s', strtotime( $status['schedule_specific'] ) ) );
				break;

			default:
				$scheduled_at = false;

				/**
				 * Allows integrations to define when the status should be scheduled for publication
				 *
				 * @since   1.0.0
				 *
				 * @param   string  $scheduled_at   Schedule Status (yyyy-mm-dd hh:mm:ss format).
				 * @param   array   $status         Status.
				 * @param   WP_Post $post           WordPress Post.
				 */
				$scheduled_at = apply_filters( 'social_post_flow_publish_builds_args_schedule_' . $status['schedule'], $scheduled_at, $status, $post );

				// Ignore if no scheduled_at defined.
				if ( ! $scheduled_at ) {
					break;
				}

				$args['schedule_type'] = 'scheduled';
				$args['scheduled_at']  = $scheduled_at;
				break;

		}

		/**
		 * Determine the standardised arguments array to send via the API for a status message's settings.
		 *
		 * @since   1.0.0
		 *
		 * @param   array       $args                       API standardised arguments.
		 * @param   WP_Post     $post                       WordPress Post.
		 * @param   string      $profile_id                 Social Media Profile ID.
		 * @param   string      $service                    Social Media Service.
		 * @param   array       $status                     Parsed Status Message Settings.
		 * @param   string      $action                     Action (publish|update|repost|bulk_publish).
		 */
		$args = apply_filters( 'social_post_flow_publish_build_args', $args, $post, $profile_id, $service, $status, $action );

		// Return args.
		return $args;

	}

	/**
	 * Attempts to fetch the primary Post's Image, in the following order:
	 * - Plugin's First (Featured) Image
	 * - Post's Featured Image
	 * - Post's Content's First Image
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post       Post ID.
	 * @return  bool|array
	 */
	private function get_post_image( $post ) {

		// Plugin's First (Featured) Image.
		$image_id = social_post_flow()->get_class( 'post' )->get_setting_by_post_id( $post->ID, 'featured_image' );
		if ( $image_id > 0 ) {
			return social_post_flow()->get_class( 'image' )->get_image_source_by_size( $image_id, 'plugin', 'large' );
		}

		// Featured Image.
		$image_id = get_post_thumbnail_id( $post->ID );
		if ( $image_id > 0 ) {
			return social_post_flow()->get_class( 'image' )->get_image_source_by_size( $image_id, 'featured_image', 'large' );
		}

		// Content's First Image.
		$images = $this->get_images_from_post_content( $post );
		if ( count( $images ) ) {
			// Return first image found.
			return $images[0];
		}

		// If here, no image was found in the Post.
		return false;

	}

	/**
	 * Attempts to fetch the non-primary (additional) Pos Images, in the following order:
	 * - Plugin's Additional Images
	 * - Post's Content's Images
	 *
	 * Duplicates are automatically removed, and the number of images returned is based
	 * on the number supported by the given Social Media Service.
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post       Post.
	 * @param   string  $service    Service.
	 * @param   array   $status     Status Settings.
	 * @return  bool|array
	 */
	private function get_additional_images( $post, $service, $status ) {

		// Get the status' additional images limit, if defined.
		$status_additional_images_limit = ( array_key_exists( 'image_additional_limit', $status ) && ! empty( $status['image_additional_limit'] ) ? ( absint( $status['image_additional_limit'] ) - 1 ) : 9 );

		// Determine the maximum number of additional images that are supported, depending on the service.
		switch ( $service ) {
			case 'facebook':
			case 'instagram':
			case 'threads':
			case 'tiktok':
				// 9 additional images (10 total).
				$additional_images_limit = min( $status_additional_images_limit, 9 );
				break;

			case 'linkedin':
				// 8 additional images (9 total).
				$additional_images_limit = min( $status_additional_images_limit, 8 );
				break;

			case 'x':
			case 'twitter':
			case 'mastodon':
			case 'bluesky':
				// 3 additional images (4 total).
				$additional_images_limit = min( $status_additional_images_limit, 3 );
				break;

			case 'google':
			case 'pinterest':
			case 'telegram':
			default:
				// A network that does not support additional images.
				return false;
		}

		// Assume no additional images.
		$images = false;

		// Depending on the status' image setting, fetch additional images.
		switch ( $status['post_type'] ) {
			/**
			 * Text
			 * Link
			 * Pin
			 * Google
			 * Story
			 */
			case 'text':
			case 'link':
			case 'pin':
			case 'google':
			case 'story':
				// No additional images supported.
				break;

			/**
			 * Image
			 * Integrations (e.g. ACF)
			 */
			case 'image':
				switch ( $status['image_additional'] ) {
					case '':
						// No additional images.
						break;

					case 'post_settings':
						// Fetch additional images specified in Post's settings only.
						$images = $this->get_images_from_post_settings( $post, $service );
						break;

					case 'post_content':
						// Fetch additional images specified in the Post's settings
						// and from the Post's content.
						$images = array_merge(
							$this->get_images_from_post_settings( $post, $service ),
							$this->get_images_from_post_content( $post, $service )
						);
						break;

					default:
						/**
						 * Allows third party integrations to define the additional images to use for the status.
						 *
						 * @since   1.0.0
						 *
						 * @param   bool|array  $images                     Images.
						 * @param   int         $additional_images_source   Additional Images Source.
						 * @param   WP_Post     $post                       Post.
						 * @param   string      $service                    Service.
						 * @param   array       $status                     Status Settings.
						 */
						$images = apply_filters( 'social_post_flow_publish_get_additional_images', $images, $status['image_additional'], $post, $service, $status );
						break;
				}
				break;
		}

		// If no images were found, bail.
		if ( ! $images ) {
			return false;
		}

		// Remove duplicate images.
		$images = array_unique( $images );

		// Re-key the array to a zero based index.
		$images = array_values( $images );

		// Limit the number of additional images based on the social network.
		$images = array_slice( $images, 0, $additional_images_limit );

		// Return.
		return $images;

	}

	/**
	 * Get all images defined in the Plugin's "Featured and Additional Images" section
	 * on a Post.
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post       WordPress Post.
	 * @return  array
	 */
	private function get_images_from_post_settings( $post ) {

		$images = array();

		// Fetch additional images specified in Post's settings.
		$additional_images = social_post_flow()->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[additional_images]', false );
		if ( ! $additional_images ) {
			return $images;
		}

		foreach ( $additional_images as $additional_image_id ) {
			// Get additional image.
			$additional_image = social_post_flow()->get_class( 'image' )->get_image_source_by_size( $additional_image_id, 'additional_image', 'large' );

			// Skip if not found.
			if ( is_wp_error( $additional_image ) ) {
				// Log error.
				social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Additional Image #' . $additional_image_id . ' Error: ' . $additional_image->get_error_message() );
				continue;
			}

			// Add additional image to array.
			$images[] = $additional_image;
		}

		return $images;

	}

	/**
	 * Get images from the Post's content.
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post       WordPress Post.
	 * @return  array
	 */
	private function get_images_from_post_content( $post ) {

		// Wrap content in <html>, <head> and <body> tags with an UTF-8 Content-Type meta tag.
		// Forcibly tell DOMDocument that this HTML uses the UTF-8 charset.
		// <meta charset="utf-8"> isn't enough, as DOMDocument still interprets the HTML as ISO-8859, which breaks character encoding
		// Use of mb_convert_encoding() with HTML-ENTITIES is deprecated in PHP 8.2, so we have to use this method.
		// If we don't, special characters render incorrectly.
		$text = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . apply_filters( 'the_content', $post->post_content ) . '</body></html>';

		// Load the HTML into a DOMDocument.
		libxml_use_internal_errors( true );
		$html = new DOMDocument();
		$html->loadHTML( $text );

		// Load DOMDocument into XPath.
		$xpath = new DOMXPath( $html );

		// Extract images from the Post's content.
		$images     = $xpath->query( '//img[@src]' );
		$image_urls = array();
		foreach ( $images as $image ) {
			$image_urls[] = $image->getAttribute( 'src' );
		}

		// If no images were found, return a blank array.
		if ( ! count( $image_urls ) ) {
			return array();
		}

		// Iterate through images, building array of image IDs.
		$image_ids = array();
		foreach ( $image_urls as $image_url ) {
			// Remove query parameters from the image URL.
			$image_url = strtok( $image_url, '?' );

			// Remove any size suffix from the image URL.
			$image_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif|webp|avif))/i', '', $image_url );

			// Attempt to get the image ID by the image URL, as this allows us to build a detailed image object
			// including width, height and other attributes that some networks may require.
			$image_id = attachment_url_to_postid( esc_url( $image_url ) );
			if ( $image_id ) {
				$image_ids[] = $image_id;
				continue;
			}
		}

		// If no image IDs could be established, we can't reliably return an image from the Post's content.
		if ( ! count( $image_ids ) ) {
			return array();
		}

		// Iterate through the image IDs, building an array of data for each image.
		$content_images = array();
		foreach ( $image_ids as $image_id ) {
			// Get image.
			$image = social_post_flow()->get_class( 'image' )->get_image_source_by_size( $image_id, 'content', 'large' );

			// Skip if not found.
			if ( is_wp_error( $image ) ) {
				// Log error.
				social_post_flow()->get_class( 'log' )->add_to_debug_log( 'Content Image #' . $image_id . ' Error: ' . $image->get_error_message() );
				continue;
			}

			// Add content image to array.
			$content_images[] = $image;
		}

		return $content_images;

	}

	/**
	 * Generates an image from the given text, Social Media Service and Plugin Settings
	 *
	 * @since   1.0.0
	 *
	 * @param   string      $text       Text.
	 * @param   string      $service    Social Media Service.
	 * @param   string      $profile_id Social Media Profile ID.
	 * @param   int         $post_id    Post ID.
	 * @param   array       $status     Status.
	 * @param   bool|string $format     Status format (for example, 'story' or 'post' for Instagram).
	 * @return  mixed                       false | array
	 */
	private function get_text_to_image( $text, $service, $profile_id, $post_id, $status, $format = false ) {

		// Get Text to Image Settings.
		$settings = social_post_flow()->get_class( 'settings' )->get_option( 'text_to_image' );

		// Define settings from Plugin's Text to Image Settings.
		$background_image_type = ( array_key_exists( 'type', $settings ) && array_key_exists( $profile_id, $settings['type'] ) ? $settings['type'][ $profile_id ] : 'background_image' );
		$background_image_id   = ( isset( $settings['background_image'] ) && isset( $settings['background_image'][ $profile_id ] ) && ! empty( $settings['background_image'][ $profile_id ] ) ? $settings['background_image'][ $profile_id ] : false );

		// If the status defines Text to Image settings, use the status settings instead.
		if ( array_key_exists( 'text_to_image_type', $status ) ) {
			if ( ! empty( $status['text_to_image_type'] ) ) {
				$background_image_type = $status['text_to_image_type'];
			}
		}
		if ( array_key_exists( 'text_to_image_background_image', $status ) ) {
			if ( ! empty( $status['text_to_image_background_image'] ) ) {
				$background_image_id = $status['text_to_image_background_image'];
			}
		}

		// Setup Text to Image.
		if ( extension_loaded( 'imagick' ) ) {
			$text_to_image = new Social_Post_Flow_Text_To_Image_Imagick();
		} else {
			$text_to_image = new Social_Post_Flow_Text_To_Image_GD();
		}

		// Determine whehther to use a featured image, background image or color as the background.
		switch ( $background_image_type ) {
			case 'featured':
				// If the Post has a Featured Image, use it.
				$image_id = get_post_thumbnail_id( $post_id );

				// Skip if no featured image.
				if ( ! $image_id ) {
					break;
				}

				// Load Image.
				$dimensions = $text_to_image->load( $image_id );
				break;

			case 'background_image':
			default:
				// Skip if no background image.
				if ( ! $background_image_id ) {
					break;
				}

				// Load Image.
				$dimensions = $text_to_image->load( $background_image_id );
				break;
		}

		// If no image loaded, use the background color instead.
		if ( ! isset( $dimensions ) ) {
			// Get required dimensions for this Social Media Service.
			$dimensions = social_post_flow()->get_class( 'image' )->get_social_media_image_size( $service, $format );

			// Create Image using Background Color.
			$text_to_image->create(
				$dimensions[0],
				$dimensions[1],
				( isset( $settings['background_color'] ) && ! empty( $settings['background_color'] ) ? $settings['background_color'] : '#e7e7e7' )
			);
		}

		// Bail if an error occured.
		if ( is_wp_error( $dimensions ) ) {
			return $dimensions;
		}

		// Get Font.
		$font = SOCIAL_POST_FLOW_PLUGIN_PATH . 'assets/fonts/OpenSans-Regular.ttf';
		if ( isset( $settings['font'] ) ) {
			if ( ! $settings['font'] ) {
				// Custom Font.
				$font = get_attached_file( $settings['font_custom'] );
			} else {
				// Plugin Font.
				$font = SOCIAL_POST_FLOW_PLUGIN_PATH . 'assets/fonts/' . $settings['font'] . '.ttf';
			}
		}

		// Add Text.
		$text_to_image->add_text(
			$text,
			$font,
			( isset( $settings['text_size'] ) ? $settings['text_size'] : 90 ),
			( isset( $settings['text_color'] ) ? $settings['text_color'] : '#000000' ),
			( isset( $settings['text_background_color'] ) ? $settings['text_background_color'] : false ),
			$dimensions[0],
			$dimensions[1],
			50
		);

		// Save to temporary file on disk.
		$image = $text_to_image->save_tmp();

		// Upload to Media Library.
		$image_id = social_post_flow()->get_class( 'media_library' )->upload_local_image( $image, $post_id, false, $text, $text, $text );

		// Bail if we couldn't upload to the Media Library.
		if ( is_wp_error( $image_id ) ) {
			return $image_id;
		}

		// Return Text to Image.
		return social_post_flow()->get_class( 'image' )->get_image_source_by_size( $image_id, 'text_to_image', 'large' );

	}

	/**
	 * Populates the status message by replacing tags with Post/Author data
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post               Post.
	 * @param   string  $message            Status Message to parse.
	 * @param   bool    $strip_urls         Whether to strip URLs from the status message.
	 * @return  string                      Parsed Status Message
	 */
	public function parse_text( $post, $message, $strip_urls = false ) {

		// Get Author.
		$author = get_user_by( 'id', $post->post_author );

		// If we haven't yet populated the searches and replacements for this Post, do so now.
		if ( ! $this->all_possible_searches_replacements ) {
			$this->all_possible_searches_replacements = $this->register_all_possible_searches_replacements( $post, $author );
		}

		// If no searches and replacements are defined, we can't parse anything.
		if ( ! $this->all_possible_searches_replacements || count( $this->all_possible_searches_replacements ) === 0 ) {
			return $message;
		}

		// Extract all of the tags in the message.
		preg_match_all( '|{(.+?)}|', $message, $matches );

		// If no tags exist in the message, there's nothing to parse.
		if ( ! is_array( $matches ) ) {
			return $message;
		}
		if ( count( $matches[0] ) === 0 ) {
			return $message;
		}

		// Define return text.
		$text = $message;

		// Iterate through matches, adding them to the search / replacement array.
		foreach ( $matches[1] as $index => $tag ) {
			// Clean up some vars.
			unset( $tag_params, $transformation, $replacement );

			// Define some default attributes for this tag.
			$tag_params = $this->get_default_tag_params( $matches[0][ $index ], $tag );

			// If we already have a replacement for this exact tag (i.e. from a previous status message),
			// we don't need to define the replacement again.
			if ( isset( $this->searches_replacements[ $tag_params['tag_with_braces'] ] ) ) {
				continue;
			}

			// Backward compatibility for word, sentence and character limit tags
			// Store them in the tag parameter's transformations array.
			if ( preg_match( '/(.*?)\((.*?)_words\)/', $tag_params['tag'], $word_limit_matches ) ) {
				$tag_params['tag'] = $word_limit_matches[1];
				$transformation    = array(
					'transformation' => 'words',
					'arguments'      => array(
						absint( $word_limit_matches[2] ),
					),
				);
			} elseif ( preg_match( '/(.*?)\((.*?)_sentences\)/', $tag_params['tag'], $sentence_limit_matches ) ) {
				$tag_params['tag'] = $sentence_limit_matches[1];
				$transformation    = array(
					'transformation' => 'sentences',
					'arguments'      => array(
						absint( $sentence_limit_matches[2] ),
					),
				);
			} elseif ( preg_match( '/(.*?)\((.*?)\)/', $tag_params['tag'], $character_limit_matches ) ) {
				$tag_params['tag'] = $character_limit_matches[1];
				$transformation    = array(
					'transformation' => 'characters',
					'arguments'      => array(
						absint( $character_limit_matches[2] ),
					),
				);
			}
			if ( isset( $transformation ) ) {
				if ( is_array( $tag_params['transformations'] ) ) {
					$tag_params['transformations'][] = $transformation;
				} else {
					$tag_params['transformations'] = array( $transformation );
				}
			}

			// If this Tag is a Custom Field, register it now.
			if ( preg_match( '/^custom_field_(.*)$/', $tag_params['tag'], $custom_field_matches ) ) {
				$this->register_post_meta_search_replacement( $tag_params['tag'], $custom_field_matches[1], $post );
			}

			// If this Tag is an Author Field, register it now.
			if ( preg_match( '/^author_field_(.*)$/', $tag_params['tag'], $custom_field_matches ) ) {
				$this->register_author_meta_search_replacement( $tag_params['tag'], $custom_field_matches[1], $author );
			}

			// If this Tag is a Taxonomy Tag, fetch some parameters that may be included in the tag.
			if ( preg_match( '/^taxonomy_(.*)_name$/', $tag_params['tag'], $taxonomy_matches ) ) {
				// Taxonomy with Name Format.
				$tag_params['tag']                  = 'taxonomy_' . $taxonomy_matches[1];
				$tag_params['taxonomy']             = $taxonomy_matches[1];
				$tag_params['taxonomy_term_format'] = 'name';

				if ( $tag_params['transformations'] ) {
					foreach ( $tag_params['transformations'] as $transformation ) {
						if ( ! is_numeric( $transformation['transformation'] ) ) {
							continue;
						}

						$tag_params['taxonomy_term_limit'] = $transformation['transformation'];
						break;
					}
				}
			} elseif ( preg_match( '/^taxonomy_(.*)_hashtag_retain_case$/', $tag_params['tag'], $taxonomy_matches ) ) {
				// Taxonomy with Hashtag, Retain Case Format.
				$tag_params['tag']                  = 'taxonomy_' . $taxonomy_matches[1];
				$tag_params['taxonomy']             = $taxonomy_matches[1];
				$tag_params['taxonomy_term_format'] = 'hashtag_retain_case';

				if ( $tag_params['transformations'] ) {
					foreach ( $tag_params['transformations'] as $transformation ) {
						if ( ! is_numeric( $transformation['transformation'] ) ) {
							continue;
						}

						$tag_params['taxonomy_term_limit'] = $transformation['transformation'];
						break;
					}
				}
			} elseif ( preg_match( '/^taxonomy_(.*)_hashtag_underscore$/', $tag_params['tag'], $taxonomy_matches ) ) {
				// Taxonomy with Hashtag, Underscore Spaces.
				$tag_params['tag']                  = 'taxonomy_' . $taxonomy_matches[1];
				$tag_params['taxonomy']             = $taxonomy_matches[1];
				$tag_params['taxonomy_term_format'] = 'hashtag_underscore';

				if ( $tag_params['transformations'] ) {
					foreach ( $tag_params['transformations'] as $transformation ) {
						if ( ! is_numeric( $transformation['transformation'] ) ) {
							continue;
						}

						$tag_params['taxonomy_term_limit'] = $transformation['transformation'];
						break;
					}
				}
			} elseif ( preg_match( '/^taxonomy_(.*?)$/', $tag_params['tag'], $taxonomy_matches ) ) {
				// Taxonomy with Hashtag Format.
				$tag_params['taxonomy'] = str_replace( 'taxonomy_', '', $tag_params['tag'] );

				if ( $tag_params['transformations'] ) {
					foreach ( $tag_params['transformations'] as $transformation ) {
						if ( ! is_numeric( $transformation['transformation'] ) ) {
							continue;
						}

						$tag_params['taxonomy_term_limit'] = $transformation['transformation'];
						break;
					}
				}
			}

			// Fetch possible tag replacement value.
			$replacement = ( isset( $this->all_possible_searches_replacements[ $tag_params['tag'] ] ) ? $this->all_possible_searches_replacements[ $tag_params['tag'] ] : '' );

			// If this is a taxonomy replacement, replace according to the tag parameters.
			if ( $tag_params['taxonomy'] !== false ) {
				// Define a string to hold the list of terms.
				$term_names = '';

				// Iterate through terms, building string.
				foreach ( $replacement as $term_index => $term ) {
					// If there's a term limit and this term exceeds it, exit the loop.
					if ( $tag_params['taxonomy_term_limit'] > 0 && $term_index + 1 > $tag_params['taxonomy_term_limit'] ) {
						break;
					}

					// Depending on the tag, build the output now.
					switch ( $tag_params['taxonomy_term_format'] ) {
						/**
						 * Name
						 * e.g. Bathroom Installations --> Bathroom Installations
						 */
						case 'name':
							$term_name = $term->name;

							/**
							 * Defines the Taxonomy Term Name to replace the status template tag.
							 *
							 * @since   1.0.0
							 *
							 * @param   string      $term_name                          Term Name.
							 * @param   string      $tag_params['taxonomy_term_format'] Term Format.
							 * @param   WP_Term     $term                               Term.
							 * @param   string      $tag_params['taxonomy']             Taxonomy.
							 * @param   string      $text                               Status Text.
							 */
							$term_name = apply_filters( 'social_post_flow_publish_parse_text_term_name', $term_name, $tag_params['taxonomy_term_format'], $term, $tag_params['taxonomy'], $text );
							break;

						/**
						 * Hashtag, retaining case
						 * e.g. Bathroom Installations --> #BathroomInstallations
						 */
						case 'hashtag_retain_case':
							// Decode HTML.
							$term_name = str_replace( ' ', '', html_entity_decode( $term->name ) );

							// Remove anything that isn't alphanumeric or an underscore, to ensure the whole hashtag is linked
							// when posted to social media and not broken by e.g. a full stop.
							$term_name = '#' . preg_replace( '/[^\p{L}\p{N}\p{M}_]+/u', '', $term_name );

							/**
							 * Defines the Taxonomy Term Hashtag to replace the status template tag.
							 *
							 * @since   1.0.0
							 *
							 * @param   string      $term_name                          Term Name.
							 * @param   string      $tag_params['taxonomy_term_format'] Term Format.
							 * @param   WP_Term     $term                               Term.
							 * @param   string      $tag_params['taxonomy']             Taxonomy.
							 * @param   string      $text                               Status Text.
							 */
							$term_name = apply_filters( 'social_post_flow_publish_parse_text_term_hashtag_retain_case', $term_name, $tag_params['taxonomy_term_format'], $term, $tag_params['taxonomy'], $text );
							break;

						/**
						 * Hashtag, underscore
						 * e.g. Bathroom Installations --> #bathroom_installations
						 */
						case 'hashtag_underscore':
							// Lowercase and decode HTML.
							$term_name = strtolower( str_replace( ' ', '_', html_entity_decode( $term->name ) ) );

							// Remove anything that isn't alphanumeric or an underscore, to ensure the whole hashtag is linked
							// when posted to social media and not broken by e.g. a full stop.
							$term_name = '#' . preg_replace( '/[^\p{L}\p{N}\p{M}_]+/u', '', $term_name );

							/**
							 * Defines the Taxonomy Term Hashtag to replace the status template tag.
							 *
							 * @since   1.0.0
							 *
							 * @param   string      $term_name                          Term Name.
							 * @param   string      $tag_params['taxonomy_term_format'] Term Format.
							 * @param   WP_Term     $term                               Term.
							 * @param   string      $tag_params['taxonomy']             Taxonomy.
							 * @param   string      $text                               Status Text.
							 */
							$term_name = apply_filters( 'social_post_flow_publish_parse_text_term_hashtag_underscore', $term_name, $tag_params['taxonomy_term_format'], $term, $tag_params['taxonomy'], $text );
							break;

						/**
						 * Hashtag
						 * e.g. Bathroom Installations --> #bathroominstallations
						 */
						case 'hashtag':
						default:
							// Lowercase and decode HTML.
							$term_name = strtolower( str_replace( ' ', '', html_entity_decode( $term->name ) ) );

							// Remove anything that isn't alphanumeric or an underscore, to ensure the whole hashtag is linked
							// when posted to social media and not broken by e.g. a full stop.
							$term_name = '#' . preg_replace( '/[^\p{L}\p{N}\p{M}_]+/u', '', $term_name );

							/**
							 * Defines the Taxonomy Term Hashtag to replace the status template tag.
							 *
							 * @since   1.0.0
							 *
							 * @param   string      $term_name                          Term Name.
							 * @param   string      $tag_params['taxonomy_term_format'] Term Format.
							 * @param   WP_Term     $term                               Term.
							 * @param   string      $tag_params['taxonomy']             Taxonomy.
							 * @param   string      $text                               Status Text.
							 */
							$term_name = apply_filters( 'social_post_flow_publish_parse_text_term_hashtag', $term_name, $tag_params['taxonomy_term_format'], $term, $tag_params['taxonomy'], $text );
							break;
					}

					/**
					 * Backward compat filter to define the Taxonomy Term Name to replace the status template tag.
					 * _publish_parse_text_term_name and _publish_parse_text_term_hashtag should be used instead.
					 *
					 * @since   1.0.0
					 *
					 * @param   string      $term_name                              Term Name.
					 * @param   string      $term->name                             Term Name.
					 * @param   string      $tag_params['taxonomy']                 Taxonomy.
					 * @param   string      $text                                   Status Text.
					 * @param   string      $tag_params['taxonomy_term_format']     Term Format.
					 */
					$term_name = apply_filters( 'social_post_flow_term', $term_name, $term->name, $tag_params['taxonomy'], $text, $tag_params['taxonomy_term_format'] );

					// Add term to term names string.
					$term_names .= $term_name . ' ';
				}

				// Finally, replace the array of terms with the string of formatted terms.
				$replacement = trim( $term_names );
			}

			// Trim replacement.
			$replacement = trim( $replacement );

			// Apply Transformations.
			if ( $tag_params['transformations'] ) {
				foreach ( $tag_params['transformations'] as $transformation ) {
					$replacement = $this->apply_text_transformation(
						$tag_params['tag'],
						$transformation['transformation'],
						$replacement,
						$transformation['arguments']
					);
				}
			}

			// Add the search and replacement to the array.
			$this->searches_replacements[ $tag_params['tag_with_braces'] ] = $replacement;

		} // Close foreach tag match in text.

		// Search and Replace.
		$text = str_replace( array_keys( $this->searches_replacements ), $this->searches_replacements, $text );

		// Execute any shortcodes in the text now.
		$text = do_shortcode( $text );

		// Convert to plain text.
		$text = $this->convert_to_plain_text( $text, true, $strip_urls );

		/**
		 * Filters the parsed status message text on a status.
		 *
		 * @since   1.0.0
		 *
		 * @param   string      $text                                       Parsed Text, no Tags.
		 * @param   string      $message                                    Unparsed Text with Tags.
		 * @param   array       $this->searches_replacements                Specific Tag Search and Replacements for the given Text.
		 * @param   array       $this->all_possible_searches_replacements   All Registered Tag Search and Replacements.
		 * @param   WP_Post     $post                                       WordPress Post.
		 * @param   WP_User     $author                                     WordPress User (Author).
		 */
		$text = apply_filters( 'social_post_flow_publish_parse_text', $text, $message, $this->searches_replacements, $this->all_possible_searches_replacements, $post, $author );

		return $text;

	}

	/**
	 * Returns default tag parameters for the given tag e.g. {title:transformation(args)} or {title}.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $tag_with_braces    Tag with Braces e.g. {title:transformation(args)} or {title}.
	 * @param   string $tag                Tag without Braces e.g. title:transformation(args) or title.
	 * @return  array                       Tag Parameters
	 * */
	private function get_default_tag_params( $tag_with_braces, $tag ) {

		// Define array of tag parameters to be populated.
		$tag_params = array(
			'tag_with_braces'      => $tag_with_braces,    // Original tag with braces, including transformations.
			'tag'                  => $tag,                // No braces, no transformations.
			'transformations'      => false,
			'taxonomy'             => false,
			'taxonomy_term_limit'  => false,
			'taxonomy_term_format' => false,
		);

		// If no transformations exist, return.
		if ( strpos( $tag, ':' ) === false ) {
			return $tag_params;
		}

		// Extract transformations.
		$tag_params['transformations'] = explode( ':', substr( $tag_params['tag'], strpos( $tag_params['tag'], ':' ) + 1 ) );

		// Remove transformations from tag.
		$tag_params['tag'] = substr( $tag_params['tag'], 0, strpos( $tag_params['tag'], ':' ) );

		// Iterate through transformations to see if arguments are attached.
		foreach ( $tag_params['transformations'] as $index => $transformation ) {
			// If no arguments exist for this transformation, update the array structure and continue.
			if ( strpos( $transformation, '(' ) === false ) {
				$tag_params['transformations'][ $index ] = array(
					'transformation' => $transformation,
					'arguments'      => false,
				);
				continue;
			}

			// Extract arguments.
			$arguments = explode( '(', substr( $transformation, strpos( $transformation, '(' ) + 1 ) );
			foreach ( $arguments as $a_index => $argument ) {
				$arguments[ $a_index ] = str_replace( ')', '', $argument );
			}

			// Remove arguments from transformation.
			$transformation = substr( $transformation, 0, strpos( $transformation, '(' ) );

			// Update array structure.
			$tag_params['transformations'][ $index ] = array(
				'transformation' => $transformation,
				'arguments'      => $arguments,
			);
		}

		// Return.
		return $tag_params;

	}

	/**
	 * Applies a transformation to the given value
	 *
	 * @since   1.0.0
	 *
	 * @param   string $tag                        Tag e.g. title, date.
	 * @param   string $transformation             Transformation.
	 * @param   string $value                      Value.
	 * @param   mixed  $transformation_arguments   false | array of arguments to apply to the transformation e.g. character limit, date format.
	 * @return  string                              Transformed Value
	 */
	private function apply_text_transformation( $tag, $transformation, $value, $transformation_arguments = false ) {

		switch ( $transformation ) {
			/**
			 * Uppercase
			 */
			case 'uppercase_all':
			case 'uppercase':
				// Use i18n compatible method if available.
				if ( function_exists( 'mb_convert_case' ) ) {
					return mb_convert_case( $value, MB_CASE_UPPER );
				}

				// Fallback to basic version which doesn't support i18n.
				return strtoupper( $value );

			/**
			 * Lowercase
			 */
			case 'lowercase_all':
			case 'lowercase':
				// Use i18n compatible method if available.
				if ( function_exists( 'mb_convert_case' ) ) {
					return mb_convert_case( $value, MB_CASE_LOWER );
				}

				// Fallback to basic version which doesn't support i18n.
				return strtolower( $value );

			/**
			 * Upperchase first character
			 */
			case 'uppercase_first_character':
				// Use i18n compatible method if available.
				if ( function_exists( 'mb_strtoupper' ) ) {
					return mb_strtoupper( mb_substr( $value, 0, 1 ) ) . mb_substr( $value, 1 );
				}

				// Fallback to basic version which doesn't support i18n.
				return ucfirst( $value );

			/**
			 * Uppercase first character of each word
			 */
			case 'uppercase_first_character_words':
				// Use i18n compatible method if available.
				if ( function_exists( 'mb_convert_case' ) ) {
					return mb_convert_case( $value, MB_CASE_TITLE );
				}

				// Fallback to basic version which doesn't support i18n.
				return ucwords( $value );

			/**
			 * First Word
			 */
			case 'first_word':
				$term_parts = explode( ' ', $value );
				return $term_parts[0];

			/**
			 * Last Word
			 */
			case 'last_word':
				$term_parts = explode( ' ', $value );
				return $term_parts[ count( $term_parts ) - 1 ];

			/**
			 * URL
			 */
			case 'url':
				return sanitize_title( $value );

			/**
			 * URL, Underscore
			 */
			case 'url_underscore':
				return str_replace( '-', '_', sanitize_title( $value ) );

			/**
			 * URL, Encode to RFC 3986
			 */
			case 'url_encode':
				return rawurlencode( $value );

			/**
			 * Date
			 */
			case 'date':
				// Don't attempt to format the date if no format is given.
				if ( ! $transformation_arguments ) {
					return $value;
				}

				// Don't attempt to format the date if the value isn't a date/time.
				$timestamp = strtotime( $value );
				if ( $timestamp === false ) {
					return $value;
				}

				return date_i18n( $transformation_arguments[0], $timestamp );

			/**
			 * Word Limit
			 */
			case 'words':
				// Don't attempt to apply limit if the tag doesn't support it.
				if ( ! $this->can_apply_character_limit_to_tag( $tag ) ) {
					return $value;
				}

				// Don't attempt to apply limit if no limit is given.
				if ( ! $transformation_arguments ) {
					return $value;
				}

				return $this->apply_word_limit( $value, $transformation_arguments[0] );

			/**
			 * Sentence Limit
			 */
			case 'sentences':
				// Don't attempt to apply limit if the tag doesn't support it.
				if ( ! $this->can_apply_character_limit_to_tag( $tag ) ) {
					return $value;
				}

				// Don't attempt to apply limit if no limit is given.
				if ( ! $transformation_arguments ) {
					return $value;
				}

				return $this->apply_sentence_limit( $value, $transformation_arguments[0] );

			/**
			 * Character Limit
			 */
			case 'characters':
				// Don't attempt to apply limit if the tag doesn't support it.
				if ( ! $this->can_apply_character_limit_to_tag( $tag ) ) {
					return $value;
				}

				// Don't attempt to apply limit if no limit is given.
				if ( ! $transformation_arguments ) {
					return $value;
				}

				return $this->apply_character_limit( $value, $transformation_arguments[0] );

			/**
			 * Other Transformations
			 */
			default:
				/**
				 * Applies the given transformation to the given value
				 *
				 * @since   1.0.0
				 *
				 * @param   string  $value              Value.
				 * @param   string  $transformation     Transformation.
				 */
				$value = apply_filters( 'social_post_flow_publish_apply_text_transformation', $value, $transformation );

				return $value;
		}

	}

	/**
	 * Returns an array comprising of all supported tags and their Post / Author / Taxonomy data replacements.
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post       WordPress Post.
	 * @param   WP_User $author     WordPress User (Author of the Post).
	 * @return  array                   Search / Replacement Key / Value pairs
	 */
	private function register_all_possible_searches_replacements( $post, $author ) {

		// Start with no searches or replacements.
		$searches_replacements = array();

		// Register Post Tags and Replacements.
		$searches_replacements = $this->register_post_searches_replacements( $searches_replacements, $post );

		// Register Post Author Tags and Replacements.
		$searches_replacements = $this->register_author_searches_replacements( $searches_replacements, $author );

		// Register Taxonomy Tags and Replacements.
		// Add Taxonomies.
		$taxonomies = get_object_taxonomies( $post->post_type, 'names' );
		if ( count( $taxonomies ) > 0 ) {
			$searches_replacements = $this->register_taxonomy_searches_replacements( $searches_replacements, $post, $taxonomies );
		}

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported.
		 *
		 * @since   1.0.0
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   WP_User     $author                 WordPress User (Author of the Post).
		 */
		$searches_replacements = apply_filters( 'social_post_flow_publish_get_all_possible_searches_replacements', $searches_replacements, $post, $author );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Registers status message tags and their data replacements for the given Post.
	 *
	 * @since   1.0.0
	 *
	 * @param   array   $searches_replacements  Registered Supported Tags and their Replacements.
	 * @param   WP_Post $post                   WordPress Post.
	 * @return  array                           Registered Supported Tags and their Replacements
	 */
	private function register_post_searches_replacements( $searches_replacements, $post ) {

		// Check Plugin Settings to see if the excerpt should fallback to the content if no
		// Excerpt defined.
		$excerpt_fallback = ( social_post_flow()->get_class( 'settings' )->get_option( 'disable_excerpt_fallback', false ) ? false : true );

		$searches_replacements['sitename']         = get_bloginfo( 'name' );
		$searches_replacements['title']            = $this->get_title( $post );
		$searches_replacements['excerpt']          = $this->get_excerpt( $post, $excerpt_fallback );
		$searches_replacements['content']          = $this->get_content( $post );
		$searches_replacements['content_more_tag'] = $this->get_content( $post, true );
		$searches_replacements['date']             = $this->get_date( $post );
		$searches_replacements['url']              = $this->get_permalink( $post );
		$searches_replacements['url_short']        = $this->get_short_permalink( $post );
		$searches_replacements['id']               = absint( $post->ID );

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for the given Post.
		 *
		 * @since   1.0.0
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 */
		$searches_replacements = apply_filters( 'social_post_flow_publish_register_post_searches_replacements', $searches_replacements, $post );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Registers status message tags and their data replacements for the given Post Author.
	 *
	 * @since   1.0.0
	 *
	 * @param   array   $searches_replacements  Registered Supported Tags and their Replacements.
	 * @param   WP_User $author                 WordPress Author.
	 * @return  array                           Registered Supported Tags and their Replacements
	 */
	private function register_author_searches_replacements( $searches_replacements, $author ) {

		// If author isn't specified, return blank replacements.
		if ( ! $author ) {
			$searches_replacements['author']               = '';
			$searches_replacements['author_user_login']    = '';
			$searches_replacements['author_user_nicename'] = '';
			$searches_replacements['author_user_email']    = '';
			$searches_replacements['author_user_url']      = '';
			$searches_replacements['author_display_name']  = '';
		} else {
			$searches_replacements['author']               = $author->display_name;
			$searches_replacements['author_user_login']    = $author->user_login;
			$searches_replacements['author_user_nicename'] = $author->user_nicename;
			$searches_replacements['author_user_email']    = $author->user_email;
			$searches_replacements['author_user_url']      = $author->user_url;
			$searches_replacements['author_display_name']  = $author->display_name;
		}

		/**
		 * Registers any additional status message tags, and their Author data replacements, that are supported
		 * for the given Post Author.
		 *
		 * @since   1.0.0
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_User     $author                 WordPress Post Author.
		 */
		$searches_replacements = apply_filters( 'social_post_flow_publish_register_author_searches_replacements', $searches_replacements, $author );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Registers status message tags and their data replacements for the given Post Taxonomies.
	 *
	 * @since   1.0.0
	 *
	 * @param   array   $searches_replacements  Registered Supported Tags and their Replacements.
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   array   $taxonomies             Post Taxonomies.
	 * @return  array   $searches_replacements  Registered Supported Tags and their Replacements.
	 */
	private function register_taxonomy_searches_replacements( $searches_replacements, $post, $taxonomies ) {

		foreach ( $taxonomies as $taxonomy ) {
			$searches_replacements[ 'taxonomy_' . $taxonomy ] = wp_get_post_terms( $post->ID, $taxonomy );
		}

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for the given Post.
		 *
		 * @since   1.0.0
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   array       $taxonomies             Post Taxonomies.
		 */
		$searches_replacements = apply_filters( 'social_post_flow_publish_register_post_searches_replacements', $searches_replacements, $post, $taxonomies );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Adds a search and replacement to the existing array of possible searches
	 * and replacements for Post Meta / Custom Field.
	 *
	 * @since   1.0.0
	 *
	 * @param   string  $tag        Tag.
	 * @param   string  $meta_key   Meta Key.
	 * @param   WP_Post $post       WordPress Post.
	 */
	private function register_post_meta_search_replacement( $tag, $meta_key, $post ) {

		// Bail if the search / replacement already exists.
		if ( isset( $this->all_possible_searches_replacements[ $tag ] ) ) {
			return;
		}

		// Extract just the meta key, in case the tag included square brackets to fetch
		// the post meta array value.
		$meta_key_only = ( strpos( $meta_key, '[' ) !== false ? substr( $meta_key, 0, strpos( $meta_key, '[' ) ) : $meta_key );

		// Fetch post meta.
		$value = get_post_meta( $post->ID, $meta_key_only, true );

		// If the meta value is a string, add it to the search/replace array and return.
		if ( is_string( $value ) ) {
			// If JSON doesn't validate, it's just a string.
			if ( is_null( json_decode( $value ) ) ) {
				$this->all_possible_searches_replacements[ $tag ] = $value;
				return;
			}

			// Convert value from JSON string to array.
			$value = json_decode( $value, true );
		}

		// $value is an array.
		// Extract the string from the array and register it as the replacement for the tag.
		$this->all_possible_searches_replacements[ $tag ] = $this->get_array_value_by_query_string( $meta_key, $value );

	}

	/**
	 * Returns the given array value as a string, by the query string.
	 *
	 * If the value of the full array hierarchy of keys isn't a string,
	 * nothing will be returned
	 *
	 * @since   1.0.0
	 *
	 * @param   string $query_string   Query string (e.g. my-meta-key[key][sub-key]).
	 * @param   array  $value          Array.
	 * @return  string
	 */
	private function get_array_value_by_query_string( $query_string, $value ) {

		// Extract the array keys e.g. my-meta-key[key][another-key].
		preg_match_all( '/\[([^\]]*)\]/', $query_string, $matches );

		// Iterate through the requested array key hierarchy.
		foreach ( $matches[1] as $key ) {
			// If the meta value is an object, convert it to an array.
			if ( is_object( $value ) ) {
				$value = json_decode( wp_json_encode( $value ), true );
			}

			// If this key does not exist in the post meta array, bail.
			if ( ! array_key_exists( $key, $value ) ) {
				return '';
			}

			// Update the value.
			$value = $value[ $key ];
		}

		// If the 'final' value is still an array, bail.
		if ( is_array( $value ) ) {
			return '';
		}

		// Return string.
		return $value;

	}

	/**
	 * Adds a search and replacement to the existing array of possible searches
	 * and replacements for Author Meta / Custom Field.
	 *
	 * @since   1.0.0
	 *
	 * @param   string  $tag        Tag.
	 * @param   string  $meta_key   Meta Key.
	 * @param   WP_User $user       WordPress User.
	 */
	private function register_author_meta_search_replacement( $tag, $meta_key, $user ) {

		// Bail if the search / replacement already exists.
		if ( isset( $this->all_possible_searches_replacements[ $tag ] ) ) {
			return;
		}

		$this->all_possible_searches_replacements[ $tag ] = get_user_meta( $user->ID, $meta_key, true );

	}

	/**
	 * Safely generate a title, stripping tags and shortcodes, and applying filters so that
	 * third party plugins (such as translation plugins) can determine the final output.
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post               WordPress Post.
	 * @return  string                          Title
	 */
	private function get_title( $post ) {

		// Define title.
		$title = $this->convert_to_plain_text( get_the_title( $post ), false );

		/**
		 * Filters the dynamic {title} replacement, when a Post's status is being built.
		 *
		 * @since   3.7.3
		 *
		 * @param   string      $title      Post Title.
		 * @param   WP_Post     $post       WordPress Post.
		 */
		$title = apply_filters( 'social_post_flow_publish_get_title', $title, $post );

		// Return.
		return $title;

	}

	/**
	 * Safely generate an excerpt, stripping tags, shortcodes, falling back
	 * to the content if the Post Type doesn't have excerpt support, and applying filters so that
	 * third party plugins (such as translation plugins) can determine the final output.
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post      WordPress Post.
	 * @param   bool    $fallback  Use Content if no Excerpt exists.
	 * @return  string             Excerpt
	 */
	private function get_excerpt( $post, $fallback = true ) {

		// Fetch excerpt.
		if ( empty( $post->post_excerpt ) ) {
			if ( $fallback ) {
				$excerpt = $post->post_content;
			} else {
				$excerpt = $post->post_excerpt;
			}
		} else {
			// Remove some third party Plugin filters that wrongly output content that we don't want in a status.
			remove_filter( 'get_the_excerpt', 'powerpress_content' );

			$excerpt = apply_filters( 'get_the_excerpt', $post->post_excerpt, $post );
		}

		// Convert to plain text.
		$excerpt = $this->convert_to_plain_text( $excerpt, false );

		/**
		 * Filters the dynamic {excerpt} replacement, when a Post's status is being built.
		 *
		 * @since   3.7.3
		 *
		 * @param   string      $excerpt    Post Excerpt.
		 * @param   WP_Post     $post       WordPress Post.
		 * @param   bool        $fallback   Use Content if no Excerpt exists.
		 */
		$excerpt = apply_filters( 'social_post_flow_publish_get_excerpt', $excerpt, $post, $fallback );

		// Return.
		return $excerpt;

	}

	/**
	 * Safely generate a title, stripping tags and shortcodes, and applying filters so that
	 * third party plugins (such as translation plugins) can determine the final output.
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post               WordPress Post.
	 * @param   bool    $to_more_tag        Only return content up to the <!-- more --> tag.
	 * @return  string                          Content
	 */
	private function get_content( $post, $to_more_tag = false ) {

		// Fetch content.
		// get_the_content() only works for WordPress 5.2+, which added the $post param.
		if ( $to_more_tag ) {
			$extended = get_extended( $post->post_content );

			if ( isset( $extended['main'] ) && ! empty( $extended['main'] ) ) {
				$content = $extended['main'];
			} else {
				// Fallback to the Post Content.
				$content = $post->post_content;
			}
		} else {
			$content = $post->post_content;
		}

		// Strip shortcodes.
		$content = strip_shortcodes( $content );

		// Remove the wpautop filter, as this converts double newlines into <p> tags.
		// In turn, <p> tags are correctly discarded later on in this function, as social networks don't support HTML.
		// However, this results in separation between paragraphs going from two newlines to one newline.
		// Some social media services further drop a single newline, meaning paragraphs become one long block of text, which isn't
		// intended.
		remove_filter( 'the_content', 'wpautop' );

		// Remove some third party Plugin filters that wrongly output content that we don't want in a status.
		remove_filter( 'the_content', 'powerpress_content' );

		// Apply filters to get true output.
		$content = apply_filters( 'the_content', $content );

		// Restore wpautop that we just removed.
		add_filter( 'the_content', 'wpautop' );

		// If the content originates from Gutenberg, remove double newlines and convert breaklines
		// into newlines.
		$is_gutenberg_request_content = $this->is_gutenberg_post_content( $post );
		if ( $is_gutenberg_request_content ) {
			// Remove double newlines, which may occur due to using Gutenberg blocks.
			// (blocks are separated with HTML comments, stripped using apply_filters( 'the_content' ), which results in double, or even triple, breaklines).
			$content = preg_replace( '/(?:(?:\r\n|\r|\n)\s*){2}/s', "\n\n", $content );

			// Convert <br> and <br /> into newlines.
			$content = preg_replace( '/<br(\s+)?\/?>/i', "\n", $content );
		}

		// Convert to plain text.
		$content = $this->convert_to_plain_text( $content );

		/**
		 * Filters the dynamic {content} replacement, when a Post's status is being built.
		 *
		 * @since   1.0.0
		 *
		 * @param   string      $content                    Post Content.
		 * @param   WP_Post     $post                       WordPress Post.
		 * @param   bool        $is_gutenberg_request_content  Is Gutenberg Post Content.
		 */
		$content = apply_filters( 'social_post_flow_publish_get_content', $content, $post, $is_gutenberg_request_content );

		// Return.
		return $content;

	}

	/**
	 * Returns the date in the locale specified in WordPress.
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post   WordPress Post.
	 * @return  string              Date
	 */
	private function get_date( $post ) {

		$date = date_i18n( get_option( 'date_format' ), strtotime( $post->post_date ) );

		/*
		 * Filters the dynamic {date} replacement, when a Post's status is being built.
		 *
		 * @since   1.0.0
		 *
		 * @param   string      $date                       Date.
		 * @param   WP_Post     $post                       WordPress Post.
		 */
		$date = apply_filters( 'social_post_flow_publish_get_date', $date, $post );

		// Return.
		return $date;

	}

	/**
	 * Returns the Permalink, including or excluding a trailing slash, depending on the Plugin settings.
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post               WordPress Post.
	 * @return  string                          WordPress Post Permalink
	 */
	private function get_permalink( $post ) {

		$url = rtrim( get_permalink( $post->ID ), '/' );

		/**
		 * Filters the Post's Permalink, including or excluding a trailing slash, depending on the Plugin settings
		 *
		 * @since   1.0.0
		 *
		 * @param   string      $url                            WordPress Post Permalink.
		 * @param   WP_Post     $post                           WordPress Post.
		 */
		$url = apply_filters( 'social_post_flow_publish_get_permalink', $url, $post );

		// Return.
		return $url;

	}

	/**
	 * Returns the Short Permalink
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post               WordPress Post.
	 * @return  string                          WordPress Post Permalink
	 */
	private function get_short_permalink( $post ) {

		// Define short permalink e.g http://yoursite.com/?p=1.
		$url = rtrim( get_bloginfo( 'url' ), '/' ) . '/?p=' . $post->ID;

		/**
		 * Filters the Post's Permalink, including or excluding a trailing slash, depending on the Plugin settings
		 *
		 * @since   1.0.0
		 *
		 * @param   string      $url                            WordPress Post Permalink.
		 * @param   WP_Post     $post                           WordPress Post.
		 */
		$url = apply_filters( 'social_post_flow_publish_get_short_permalink', $url, $post );

		// Return.
		return $url;

	}

	/**
	 * Converts the given string (which is typically HTML from a WordPress Post or Post Meta Field)
	 * to plain text, by performing several functions:
	 * - stripping shortcodes (if shortcodes need processing, do so before calling this function)
	 * - removing all inline style elements and their contents,
	 * - stripping HTML tags, excluding <br>, <br />, <a>, <li>
	 * - decoding HTML entities to avoid encoding issues on status output
	 * - converting <br> and <br /> to newlines
	 * - removing double spaces
	 * - trimming the final result of any leading or trailing spaces
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text                           Text.
	 * @param   bool   $convert_links_to_inline        true: Convert e.g. `<a href="http://foo.com">text</a>` to `text (http://foo.com)`.
	 *                                                 false: Convert e.g. `<a href="http://foo.com">text</a>` to `text`.
	 * @param   bool   $strip_urls                     Whether to strip URLs from the text.
	 * @return  string                                 Text
	 */
	private function convert_to_plain_text( $text, $convert_links_to_inline = true, $strip_urls = false ) {

		// Strip any shortcodes still remaining.
		// If shortcodes need to be processed, they should be processed before calling this function.
		$text = strip_shortcodes( $text );

		// Wrap content in <html>, <head> and <body> tags with an UTF-8 Content-Type meta tag.
		// Forcibly tell DOMDocument that this HTML uses the UTF-8 charset.
		// <meta charset="utf-8"> isn't enough, as DOMDocument still interprets the HTML as ISO-8859, which breaks character encoding
		// Use of mb_convert_encoding() with HTML-ENTITIES is deprecated in PHP 8.2, so we have to use this method.
		// If we don't, special characters render incorrectly.
		$text = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $text . '</body></html>';

		// Load the HTML into a DOMDocument.
		libxml_use_internal_errors( true );
		$html = new DOMDocument();
		$html->loadHTML( $text );

		// Load DOMDocument into XPath.
		$xpath = new DOMXPath( $html );

		// Remove inline style tags and their contents.
		foreach ( $xpath->query( '//style' ) as $node ) {
			$node->parentNode->removeChild( $node ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		// Fetch revised HTML.
		$text = $html->saveHTML();

		// Remove HTML, except breaklines, links and unordered list items.
		$retain_tags = ( $strip_urls ? '<br><li>' : '<br><a><li>' );
		$text        = strip_tags( $text, $retain_tags );

		// Decode excerpt to avoid encoding issues on status output.
		$text = html_entity_decode( $text );

		// Convert <br> and <br /> into newlines.
		$text = preg_replace( '/<br(\s+)?\/?>/i', "\n", $text );

		// Convert <a> to text and inline link.
		if ( $convert_links_to_inline ) {
			// Extract the text from the link, and add the link in brackets after the text.
			$text = preg_replace( '/<a[^>]+href=\"(.*?)\"[^>]*>(.*?)<\/a>/i', '$2 ($1)', $text );
		} else {
			// Just extract the text from the link and output it.
			$text = preg_replace( '/<a[^>]+href=\"(.*?)\"[^>]*>(.*?)<\/a>/i', '$2', $text );
		}

		// If URLs are to be stripped, remove them.
		if ( $strip_urls ) {
			$text = preg_replace( '/https?:\/\/[^\s]+/', '', $text );
		}

		// Convert <li> to hyphenated.
		$text = preg_replace( '/<li[^>]*>(.*?)<\/li>/i', '- $1', $text );

		// Remove double spaces, but retain newlines and accented characters.
		$text = preg_replace( '/[ ]{2,}/', ' ', $text );

		// Remove tabs.
		$text = str_replace( "\t", '', $text );

		// Finally, trim the text.
		$text = trim( $text );

		// Return.
		return $text;

	}

	/**
	 * Returns a flag denoting whether a character limit can safely be applied
	 * to the given tag.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $tag    Tag.
	 * @return  bool            Can apply character limit
	 */
	private function can_apply_character_limit_to_tag( $tag ) {

		// Get Tags.
		$tags = social_post_flow()->get_class( 'common' )->get_tags_excluded_from_character_limit();

		// If the tag is in the array of tags excluded from character limits, we
		// cannot apply a character limit to this tag.
		if ( in_array( $tag, $tags, true ) ) {
			return false;
		}

		// Can apply character limit to tag.
		return true;

	}

	/**
	 * Applies the given word limit to the given text
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text          Text.
	 * @param   int    $word_limit    Word Limit.
	 * @return  string                 Text
	 */
	private function apply_word_limit( $text, $word_limit = 0 ) {

		// Store original text.
		$original_text = $text;

		// Bail if the word limit is zero or false.
		if ( ! $word_limit || $word_limit === 0 ) {
			return $text;
		}

		// Limit text.
		$text = wp_trim_words( $text, $word_limit, '' );

		/**
		 * Applies the given word limit to the given text.
		 *
		 * @since   1.0.0
		 *
		 * @param   string      $text               Text, with word limit applied.
		 * @param   int         $word_limit         Sentence Limit.
		 * @param   string      $original_text      Original Text, with no limit applied.
		 */
		$text = apply_filters( 'social_post_flow_publish_apply_word_limit', $text, $word_limit, $original_text );

		return $text;

	}

	/**
	 * Applies the given sentence limit to the given text
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text                Text.
	 * @param   int    $sentence_limit      Sentence Limit.
	 * @param   int    $min_sentence_length Minimum Sentence Length.
	 * @return  string
	 */
	public function apply_sentence_limit( $text, $sentence_limit = 0, $min_sentence_length = 5 ) {

		// Store original text.
		$original_text = $text;

		// Bail if the sentence limit is zero or false.
		if ( ! $sentence_limit || $sentence_limit === 0 ) {
			return $text;
		}

		// Build array of sentences.
		$parts = preg_split( '/(?<=[.?!])\s+(?=[a-z])/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE );

		// Iterate through the array, adding sentences to the array until we hit the sentence limit.
		// Sentences do not count towards the limit if they are shorter than the minimum sentence length.
		// This ensures abbreviations do not count towards the limit.
		$sentences      = array();
		$sentence_count = 0;
		foreach ( $parts as $index => $sentence ) {
			// If we've hit the sentence limit, stop.
			if ( $sentence_count >= $sentence_limit ) {
				break;
			}

			// Trim the sentence, adding it to the array.
			$sentences[ $index ] = trim( $sentence );

			// If the sentence is longer than the minimum sentence length, count this as a sentence.
			if ( mb_strlen( $sentences[ $index ] ) > $min_sentence_length ) {
				++$sentence_count;
			}
		}

		// Implode into text, with a space between each sentence, trimming the array results to avoid double spacing.
		$text = implode( ' ', array_map( 'trim', $sentences ) );

		/**
		 * Applies the given sentence limit to the given text.
		 *
		 * @since   1.0.0
		 *
		 * @param   string      $text               Text, with word limit applied.
		 * @param   int         $sentence_limit     Sentence Limit.
		 * @param   string      $original_text      Original Text, with no limit applied.
		 */
		$text = apply_filters( 'social_post_flow_publish_apply_sentence_limit', $text, $sentence_limit, $original_text );

		// Return.
		return $text;

	}

	/**
	 * Applies the given character limit to the given text
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text               Text.
	 * @param   int    $character_limit    Character Limit.
	 * @return  string                      Text
	 */
	private function apply_character_limit( $text, $character_limit = 0 ) {

		// Bail if the character limit is zero or false.
		if ( ! $character_limit || $character_limit === 0 ) {
			return $text;
		}

		// Bail if the content isn't longer than the character limit.
		if ( strlen( $text ) <= $character_limit ) {
			return $text;
		}

		// Limit text.
		// Use mb_substr so that emojis don't break, which would result in text not being saved
		// by the social network when the status is sent.
		$text = mb_substr( $text, 0, $character_limit );

		/**
		 * Filters the character limited text.
		 *
		 * @since   1.0.0
		 *
		 * @param   string      $text               Text, with character limit applied.
		 * @param   int         $character_limit    Character Limit used.
		 */
		$text = apply_filters( 'social_post_flow_publish_apply_character_limit', $text, $character_limit );

		// Return.
		return $text;

	}

	/**
	 * Helper method to iterate through statuses, sending each via a separate API call
	 * to the API.
	 *
	 * @since   1.0.0
	 *
	 * @param   array  $statuses   Statuses.
	 * @param   int    $post_id    Post ID.
	 * @param   string $action     Action.
	 * @param   array  $profiles   All Enabled Profiles.
	 * @param   bool   $test_mode  Test Mode (won't send to API).
	 * @return  array               API Result for each status
	 */
	public function send( $statuses, $post_id, $action, $profiles, $test_mode = false ) {

		// Assume no errors.
		$errors = false;

		// Setup API.
		social_post_flow()->get_class( 'api' )->set_tokens(
			social_post_flow()->get_class( 'settings' )->get_access_token()
		);

		// Setup logging.
		$logs        = array();
		$log_error   = array();
		$log_enabled = social_post_flow()->get_class( 'log' )->is_enabled();

		foreach ( $statuses as $index => $status ) {
			// If the status is a WP_Error, something went wrong in building the status to be sent.
			// Log the error and continue to the next status.
			if ( isset( $status['error'] ) && is_wp_error( $status['error'] ) ) {
				// Error.
				$errors      = true;
				$logs[]      = array(
					'action'         => $action,
					'request_sent'   => gmdate( 'Y-m-d H:i:s' ),
					'profile_id'     => $status['profile_ids'][0],
					'profile_name'   => $profiles[ $status['profile_ids'][0] ]['provider'] . ': ' . $profiles[ $status['profile_ids'][0] ]['profile_name'],
					'result'         => 'error',
					'result_message' => sprintf(
						/* translators: %1$s: Plugin Error string, %2$s: Error message from Plugin */
						'%1$s: %2$s',
						__( 'Plugin Error', 'social-post-flow' ),
						$status['error']->get_error_message()
					),
					'status_text'    => false,
				);
				$log_error[] = ( $profiles[ $status['profile_ids'][0] ]['provider'] . ': ' . $profiles[ $status['profile_ids'][0] ]['profile_name'] . ': ' . $status['error']->get_error_message() );
				continue;
			}

			// If this is a test, add to the log array only.
			if ( $test_mode ) {
				$logs[] = array(
					'action'              => $action,
					'request_sent'        => gmdate( 'Y-m-d H:i:s' ),
					'profile_id'          => $status['profile_ids'][0],
					'profile_name'        => $profiles[ $status['profile_ids'][0] ]['provider'] . ': ' . $profiles[ $status['profile_ids'][0] ]['profile_name'],
					'result'              => 'test',
					'result_message'      => '',
					'status_text'         => $status['text'],
					'status_created_at'   => gmdate( 'Y-m-d H:i:s', strtotime( 'now' ) ),
					'status_scheduled_at' => ( isset( $status['scheduled_at'] ) ? $status['scheduled_at'] : '' ),
				);

				continue;
			}

			// Send request.
			$result = social_post_flow()->get_class( 'api' )->create_post( $status );

			// Store result in log array.
			if ( is_wp_error( $result ) ) {
				// Error.
				$errors      = true;
				$logs[]      = array(
					'action'         => $action,
					'request_sent'   => gmdate( 'Y-m-d H:i:s' ),
					'profile_id'     => $status['profile_ids'][0],
					'profile_name'   => $profiles[ $status['profile_ids'][0] ]['provider'] . ': ' . $profiles[ $status['profile_ids'][0] ]['profile_name'],
					'result'         => 'error',
					'result_message' => $result->get_error_message(),
					'status_text'    => $status['text'],
				);
				$log_error[] = ( $profiles[ $status['profile_ids'][0] ]['provider'] . ': ' . $profiles[ $status['profile_ids'][0] ]['profile_name'] . ': ' . $result->get_error_message() );
			} else {
				// OK.
				// Iterate through the results (as more than one post may have been sent in this request, as the API always returns a collection).
				foreach ( $result['data'] as $status_result ) {
					$logs[] = array(
						'action'              => $action,
						'request_sent'        => gmdate( 'Y-m-d H:i:s' ),
						'profile_id'          => $status_result['social_profile_id'],
						'profile_name'        => $status_result['provider'] . ': ' . $status_result['profile_name'],
						'result'              => 'success',
						'result_message'      => $status_result['status'],
						'status_text'         => $status_result['text'],
						'status_created_at'   => $status_result['created_at'],
						'status_scheduled_at' => $status_result['scheduled_at'],
					);
				}
			}
		}

		// Set the last sent timestamp, which we may use to prevent duplicate statuses.
		update_post_meta( $post_id, '_social_post_flow_last_sent', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp

		// If we're reposting, update the last reposted date against the Post.
		// We do this here to ensure the Post isn't reposting again where e.g. one profile status worked + one profile status failed,
		// which would be deemed a failure.
		if ( $action === 'repost' && ! $test_mode ) {
			social_post_flow()->get_class( 'repost' )->update_last_reposted_date( $post_id );
		}

		// If no errors were reported, set a meta key to show a success message.
		// This triggers admin_notices() to tell the user what happened.
		if ( ! $errors ) {
			// Only set a success message if test mode is disabled.
			if ( ! $test_mode ) {
				update_post_meta( $post_id, '_social_post_flow_success', 1 );
			}
			delete_post_meta( $post_id, '_social_post_flow_error' );
			delete_post_meta( $post_id, '_social_post_flow_errors' );

			// Request that the user review the plugin. Notification displayed later,
			// can be called multiple times and won't re-display the notification if dismissed.
			social_post_flow()->dashboard->request_review();
		} else {
			update_post_meta( $post_id, '_social_post_flow_success', 0 );
			update_post_meta( $post_id, '_social_post_flow_error', 1 );
			update_post_meta( $post_id, '_social_post_flow_errors', $log_error );
		}

		// Save the log, if logging is enabled.
		if ( $log_enabled ) {
			foreach ( $logs as $log ) {
				social_post_flow()->get_class( 'log' )->add( $post_id, $log );
			}
		}

		// Return log results.
		return $logs;

	}

	/**
	 * Clears any searches and replacements stored in this class.
	 *
	 * @since   1.0.0
	 */
	private function clear_search_replacements() {

		$this->all_possible_searches_replacements = array();
		$this->searches_replacements              = array();

	}

}
