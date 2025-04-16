<?php
/**
 * Repost class
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Handles publishing status(es) to the scheduling service
 * based on the Plugin's repost settings.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 3.7.2
 */
class Social_Post_Flow_Repost {

	/**
	 * Holds the base class object.
	 *
	 * @since   3.7.2
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds the meta key used to store the last reposted date
	 *
	 * @since   3.8.2
	 *
	 * @var     string
	 */
	public $meta_key = '_wp_to_social_pro_last_reposted';

	/**
	 * Constructor
	 *
	 * @since   3.7.2
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct() {

		

	}

	/**
	 * Checks the Plugin settings to confirm that
	 * - At least one Repost Time is specified, and
	 * - Disable Repost Cron is not enabled
	 *
	 * @since   4.1.8
	 *
	 * @return  bool    Repost Settings Configured
	 */
	public function repost_configured() {

		// Repost via Cron is disabled in Plugin Settings.
		if ( social_post_flow()->get_class( 'settings' )->get_option( 'repost_disable_cron', 0 ) ) {
			return false;
		}

		// Fetch Repost Days and Times.
		$repost_days_times = social_post_flow()->get_class( 'settings' )->get_option( 'repost_time', 0 );

		// If no Repost Days or Times are specified, reposting isn't configured.
		if ( ! $repost_days_times ) {
			return false;
		}

		// Iterate through Repost Days and Times to check if any exist.
		foreach ( $repost_days_times as $day => $times ) {
			foreach ( $times as $time ) {
				// If time isn't zero, Repost is configured.
				if ( $time !== '0' ) {
					return true;
				}
			}
		}

		// Repost isn't configured.
		return false;

	}

	/**
	 * Fetches matching Posts, Pages and/or Custom Post Types that are eligible
	 * to be reposted to the API.
	 *
	 * If Post(s) are found, they are sent to the API, using the status settings
	 * as defined in the 'repost' section of the Plugin.
	 *
	 * Where this function is invoked from the WP-CLI, verbose logging is also
	 * output in the console.
	 *
	 * @since   3.7.2
	 *
	 * @param   mixed $post_types     Limit to the given Post Types (false = all public Post Types).
	 * @param   bool  $test_mode      Test Mode (don't send to API).
	 */
	public function run( $post_types = false, $test_mode = false ) {

		// Check a valid access token exists.
		$access_token = social_post_flow()->get_class( 'settings' )->get_access_token();
		if ( ! $access_token ) {
			social_post_flow()->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Stopping, as Plugin not authorized with ' . $this->base->plugin->account );
			return new WP_Error(
				'no_access_token',
				sprintf(
					/* translators: %1$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %2$s: Plugin Name */
					__( 'The Plugin has not been authorized with %1$s! Go to %2$s > Settings to setup the plugin.', 'social-post-flow' ),
					$this->base->plugin->account,
					$this->base->plugin->displayName
				)
			);
		}

		// Get all public Post Types.
		if ( ! $post_types ) {
			$post_types = array_keys( social_post_flow()->get_class( 'common' )->get_post_types() );
		}

		// If no public Post Types exist, bail.
		if ( ! $post_types || count( $post_types ) === 0 ) {
			social_post_flow()->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Stopping, as no public Post Types exist' );
			return new WP_Error( 'no_public_post_types', __( 'No public Post Types exist', 'social-post-flow' ) );
		}

		// Check if Logging is enabled.
		$log_enabled = social_post_flow()->get_class( 'log' )->is_enabled();

		// Iterate through each Post Type.
		foreach ( $post_types as $post_type ) {

			social_post_flow()->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Post Type = ' . $post_type );

			// Get limit for this Post Type.
			$posted_count = 0;
			$limit        = social_post_flow()->get_class( 'settings' )->get_setting( 'repost', '[' . $post_type . '][limit]', 3 );

			// Fetch Post IDs.
			$post_ids = $this->get_post_ids( $post_type );

			// Skip if no Post IDs found.
			if ( ! $post_ids || count( $post_ids ) === 0 ) {
				social_post_flow()->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): No Posts were found for the ' . $post_type . ' post type. Skipping...' );
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					WP_CLI::log( 'No Posts were found for the ' . $post_type . ' post type. Skipping...' );
				}
				continue;
			}

			// Iterate through Post IDs.
			foreach ( $post_ids as $post_id ) {
				// If we've hit the limit, bail.
				if ( $posted_count >= $limit ) {
					social_post_flow()->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Limit of ' . $limit . ' reached for the ' . $post_type . ' post type. Skipping...' );
					if ( defined( 'WP_CLI' ) && WP_CLI ) {
						WP_CLI::log( 'Limit of ' . $limit . ' reached for the ' . $post_type . ' post type. Skipping...' );
					}
					break;
				}

				// Repost.
				social_post_flow()->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Post #' . $post_id . ': Reposting' );
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					WP_CLI::log( '---' );
					WP_CLI::log( 'Post #' . $post_id . ': Reposting' );
				}

				// Attempt to Repost the Post.
				$results = social_post_flow()->get_class( 'publish' )->publish( $post_id, 'repost', $test_mode );
				if ( is_wp_error( $results ) ) {
					social_post_flow()->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Post #' . $post_id . ': Error: ' . $results->get_error_message() );
				} else {
					social_post_flow()->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Post #' . $post_id . ': Success' );
				}

				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					// If the result is an error, output it and continue.
					if ( is_wp_error( $results ) ) {
						// If logging is enabled, log the warning.
						if ( $log_enabled ) {
							// The result is a single warning caught before any statuses were sent to the API.
							// Add the warning to the log so that the user can see why no statuses were sent to API.
							social_post_flow()->get_class( 'log' )->add(
								$post_id,
								array(
									'action'         => 'repost',
									'request_sent'   => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
									'result'         => 'warning',
									'result_message' => $results->get_error_message(),
								)
							);
						}

						WP_CLI::error( str_replace( '  ', '', wp_strip_all_tags( $results->get_error_message() ) ), false );
						continue;
					}

					// If the results are an array, iterate through them, outputting similar to our log.
					if ( is_array( $results ) && count( $results ) > 0 ) {
						// Output results table.
						WP_CLI\Utils\format_items( 'table', $results, array_keys( $results[0] ) );
					}
				}

				// Append the count, if the post was successful.
				if ( ! is_wp_error( $results ) ) {
					++$posted_count;
				}
			}
		}

	}

	/**
	 * Returns all published Posts for the given Post Type, based on the settings
	 * in the Repost Settings section of the Plugin
	 *
	 * @since   3.7.2
	 *
	 * @param   string $post_type  Post Type.
	 * @return  array               Post IDs
	 */
	private function get_post_ids( $post_type ) {

		// Fetch Repost Settings for the Post Type.
		$settings = array(
			'frequency' => social_post_flow()->get_class( 'settings' )->get_setting( 'repost', '[' . $post_type . '][frequency]', 30 ),
			'min_age'   => social_post_flow()->get_class( 'settings' )->get_setting( 'repost', '[' . $post_type . '][min_age]', 30 ),
			'max_age'   => social_post_flow()->get_class( 'settings' )->get_setting( 'repost', '[' . $post_type . '][max_age]', 0 ),
			'order'     => social_post_flow()->get_class( 'settings' )->get_setting( 'repost', '[' . $post_type . '][order]', 'ASC' ),
			'orderby'   => social_post_flow()->get_class( 'settings' )->get_setting( 'repost', '[' . $post_type . '][orderby]', 'date' ),
		);

		// Cast settings.
		$settings['frequency'] = absint( $settings['frequency'] );
		$settings['min_age']   = absint( $settings['min_age'] );
		$settings['max_age']   = absint( $settings['max_age'] );

		// Build args.
		$args = array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',

			// We deliberately fetch all possible Posts, as status conditions may mean that a Post from this query does not get Reposted.
			// We can keep working through all matching Posts until we've hit the settings limit.
			'posts_per_page'         => -1,

			'meta_query'             => array(
				'relation' => 'AND',

				// Exclude Posts where "Do NOT Post" has been set at Post level.
				// Passing these to publish() won't result in a status error, so reposting
				// thinks these Posts will have been sent successfully when they were (rightly) ignored
				// in the publish() process.
				array(
					'relation' => 'OR',

					// Any Posts that don't have Post-specific settings.
					array(
						'key'     => 'social-post-flow',
						'compare' => 'NOT EXISTS',
					),

					// Any Posts where override is NOT "Do NOT Post".
					array(
						'key'     => 'social-post-flow',
						'value'   => '"override";s:2:"-1";',
						'compare' => 'NOT LIKE',
					),
				),

				// Last Reposted.
				array(
					'relation' => 'OR',

					// Any Posts that don't have the last reposted meta key.
					array(
						'key'     => $this->meta_key,
						'compare' => 'NOT EXISTS',
					),

					// Any Posts that have a last reposted meta key value greater than the frequency date.
					array(
						'key'     => $this->meta_key,
						'value'   => date( 'Y-m-d', strtotime( $settings['frequency'] . 'days ago' ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						'compare' => '<',
					),
				),
			),

			'order'                  => $settings['order'],
			'orderby'                => $settings['orderby'],

			// Performance.
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		// Build Min/Max Post Date Args if supplied.
		if ( $settings['min_age'] > 0 ) {
			if ( ! isset( $args['date_query'] ) ) {
				$args['date_query'] = array();
			}

			$args['date_query'][] = array(
				'column' => 'post_date_gmt',
				'before' => date( 'Y-m-d', strtotime( $settings['min_age'] . ' days ago' ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			);
		}
		if ( $settings['max_age'] > 0 ) {
			if ( ! isset( $args['date_query'] ) ) {
				$args['date_query'] = array();
			}

			$args['date_query'][] = array(
				'column' => 'post_date_gmt',
				'after'  => date( 'Y-m-d', strtotime( $settings['max_age'] . ' days ago' ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			);
		}

		/**
		 * Filters WP_Query arguments for fetching Post IDs for a given Post Type,
		 * that are then checked to see if reposting is required.
		 *
		 * @since   3.7.2
		 *
		 * @param   array   $args       WP_Query Arguments.
		 * @param   string  $post_type  Post Type.
		 */
		$args = apply_filters( 'social_post_flow_repost_get_post_ids', $args, $post_type );

		// Run query.
		$posts = new WP_Query( $args );

		// Return results.
		return $posts->posts;

	}

	/**
	 * Updates the last reposted date on the given Post ID to today's date.
	 *
	 * @since   3.8.2
	 *
	 * @param   int $post_id    Post ID.
	 */
	public function update_last_reposted_date( $post_id ) {

		update_post_meta( $post_id, $this->meta_key, date( 'Y-m-d' ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
	}

}
