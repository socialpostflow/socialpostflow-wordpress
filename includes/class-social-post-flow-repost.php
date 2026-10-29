<?php
/**
 * Repost class
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Handles publishing status(es) to the scheduling service
 * based on the Plugin's repost settings.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */
class Social_Post_Flow_Repost {

	/**
	 * Holds the meta key used to store the last reposted date
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public $meta_key = '_social_post_flow_last_reposted';

	/**
	 * Checks the Plugin settings to confirm that
	 * - At least one Repost Time is specified, and
	 * - Disable Repost Cron is not enabled
	 *
	 * @since   1.0.0
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
	 * @since   1.0.0
	 *
	 * @param   mixed $post_types     Limit to the given Post Types (false = all public Post Types).
	 * @param   bool  $test_mode      Test Mode (don't send to API).
	 */
	public function run( $post_types = false, $test_mode = false ) {

		// Check a valid access token exists.
		$access_token = social_post_flow()->get_class( 'settings' )->get_access_token();
		if ( ! $access_token ) {
			return new WP_Error(
				'social_post_flow_no_access_token',
				__( 'The Plugin has not been authorized with Social Post Flow. Go to Social Post Flow > Settings to setup the plugin.', 'social-post-flow' )
			);
		}

		// Get all public Post Types.
		if ( ! $post_types ) {
			$post_types = array_keys( social_post_flow()->get_class( 'common' )->get_post_types() );
		}

		// If no public Post Types exist, bail.
		if ( ! $post_types || count( $post_types ) === 0 ) {
			social_post_flow()->get_class( 'log' )->add_to_debug_log( social_post_flow()->plugin->displayName . ': repost(): Stopping, as no public Post Types exist' );
			return new WP_Error( 'no_public_post_types', __( 'No public Post Types exist', 'social-post-flow' ) );
		}

		// Check if Logging is enabled.
		$log_enabled = social_post_flow()->get_class( 'log' )->is_enabled();

		// Iterate through each Post Type.
		foreach ( $post_types as $post_type ) {

			social_post_flow()->get_class( 'log' )->add_to_debug_log( social_post_flow()->plugin->displayName . ': repost(): Post Type = ' . $post_type );

			// Get limit for this Post Type.
			$posted_count = 0;
			$limit        = social_post_flow()->get_class( 'settings' )->get_setting( 'repost', '[' . $post_type . '][limit]', 3 );

			// Fetch Post IDs.
			$post_ids = $this->get_post_ids( $post_type );

			// Skip if no Post IDs found.
			if ( ! $post_ids || count( $post_ids ) === 0 ) {
				social_post_flow()->get_class( 'log' )->add_to_debug_log( social_post_flow()->plugin->displayName . ': repost(): No Posts were found for the ' . $post_type . ' post type. Skipping...' );
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					WP_CLI::log( 'No Posts were found for the ' . $post_type . ' post type. Skipping...' );
				}
				continue;
			}

			// Iterate through Post IDs, a batch at a time, until the limit is reached.
			$page = 1;
			while ( ! empty( $post_ids ) ) {
				foreach ( $post_ids as $post_id ) {
					// If we've hit the limit, bail.
					if ( $posted_count >= $limit ) {
						social_post_flow()->get_class( 'log' )->add_to_debug_log( social_post_flow()->plugin->displayName . ': repost(): Limit of ' . $limit . ' reached for the ' . $post_type . ' post type. Skipping...' );
						if ( defined( 'WP_CLI' ) && WP_CLI ) {
							WP_CLI::log( 'Limit of ' . $limit . ' reached for the ' . $post_type . ' post type. Skipping...' );
						}
						break 2;
					}

					// Repost.
					social_post_flow()->get_class( 'log' )->add_to_debug_log( social_post_flow()->plugin->displayName . ': repost(): Post #' . $post_id . ': Reposting' );
					if ( defined( 'WP_CLI' ) && WP_CLI ) {
						WP_CLI::log( '---' );
						WP_CLI::log( 'Post #' . $post_id . ': Reposting' );
					}

					// Attempt to Repost the Post.
					$results = social_post_flow()->get_class( 'publish' )->publish( $post_id, 'repost', $test_mode );
					if ( is_wp_error( $results ) ) {
						social_post_flow()->get_class( 'log' )->add_to_debug_log( social_post_flow()->plugin->displayName . ': repost(): Post #' . $post_id . ': Error: ' . $results->get_error_message() );

						// Log the error caught before statuses were sent to the API e.g. a connection error.
						if ( $log_enabled ) {
							social_post_flow()->get_class( 'log' )->add(
								$post_id,
								array(
									'action'         => 'repost',
									'request_sent'   => gmdate( 'Y-m-d H:i:s' ),
									'result'         => 'warning',
									'result_message' => $results->get_error_message(),
								)
							);
						}

						// Stop, as this error e.g. a connection error will happen for every Post.
						if ( social_post_flow()->get_class( 'publish' )->is_global_error() ) {
							social_post_flow()->get_class( 'log' )->add_to_debug_log( social_post_flow()->plugin->displayName . ': repost(): Stopped, as this error affects every Post' );
							if ( defined( 'WP_CLI' ) && WP_CLI ) {
								WP_CLI::error( str_replace( '  ', '', wp_strip_all_tags( $results->get_error_message() ) ), false );
							}
							return $results;
						}
					} elseif ( ! is_array( $results ) || count( $results ) === 0 ) {
						// Nothing was sent e.g. the Post's settings are "Do NOT Post".
						social_post_flow()->get_class( 'log' )->add_to_debug_log( social_post_flow()->plugin->displayName . ': repost(): Post #' . $post_id . ': Not reposted: ' . social_post_flow()->get_class( 'publish' )->get_skip_reason() );
					} else {
						social_post_flow()->get_class( 'log' )->add_to_debug_log( social_post_flow()->plugin->displayName . ': repost(): Post #' . $post_id . ': Success' );
					}

					if ( defined( 'WP_CLI' ) && WP_CLI ) {
						// If the result is an error, output it and continue.
						if ( is_wp_error( $results ) ) {
							WP_CLI::error( str_replace( '  ', '', wp_strip_all_tags( $results->get_error_message() ) ), false );
							continue;
						}

						// If the results are an array, iterate through them, outputting similar to our log.
						if ( is_array( $results ) && count( $results ) > 0 ) {
							// Output results table.
							WP_CLI\Utils\format_items( 'table', $results, array_keys( $results[0] ) );
						}
					}

					// Append the count, if statuses were sent. Posts that sent nothing don't count towards the limit.
					if ( is_array( $results ) && count( $results ) > 0 ) {
						++$posted_count;
					}
				}

				// Stop if this was the last batch.
				if ( count( $post_ids ) < $this->get_batch_size() ) {
					break;
				}

				// Fetch the next batch.
				++$page;
				$post_ids = $this->get_post_ids( $post_type, $page );
			}
		}

	}

	/**
	 * Returns all published Posts for the given Post Type, based on the settings
	 * in the Repost Settings section of the Plugin
	 *
	 * @since   1.0.0
	 *
	 * @param   string $post_type  Post Type.
	 * @param   int    $page       Batch number.
	 * @return  array               Post IDs
	 */
	private function get_post_ids( $post_type, $page = 1 ) {

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
			'post_type'               => $post_type,
			'post_status'             => 'publish',

			// Fetch in batches; run() pages through until the limit is reached.
			'posts_per_page'          => $this->get_batch_size(),
			'paged'                   => $page,
			'no_found_rows'           => true,
			'ignore_sticky_posts'     => true,

			// Excludes "Do NOT Post" and recently reposted Posts in posts_where().
			// NOT EXISTS subqueries are used instead of a meta_query, as a meta_query's joins on
			// the postmeta table grow with the number of meta rows per Post, which can take minutes
			// and time out on sites with a lot of Post meta.
			'social_post_flow_repost' => gmdate( 'Y-m-d', strtotime( $settings['frequency'] . 'days ago' ) ),

			'order'                   => $settings['order'],
			'orderby'                 => $settings['orderby'],

			// Performance.
			'fields'                  => 'ids',
			'update_post_meta_cache'  => false,
			'update_post_term_cache'  => false,
		);

		// Build Min/Max Post Date Args if supplied.
		if ( $settings['min_age'] > 0 ) {
			if ( ! isset( $args['date_query'] ) ) {
				$args['date_query'] = array();
			}

			$args['date_query'][] = array(
				'column' => 'post_date_gmt',
				'before' => gmdate( 'Y-m-d', strtotime( $settings['min_age'] . ' days ago' ) ),
			);
		}
		if ( $settings['max_age'] > 0 ) {
			if ( ! isset( $args['date_query'] ) ) {
				$args['date_query'] = array();
			}

			$args['date_query'][] = array(
				'column' => 'post_date_gmt',
				'after'  => gmdate( 'Y-m-d', strtotime( $settings['max_age'] . ' days ago' ) ),
			);
		}

		/**
		 * Filters WP_Query arguments for fetching Post IDs for a given Post Type,
		 * that are then checked to see if reposting is required.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $args       WP_Query Arguments.
		 * @param   string  $post_type  Post Type.
		 */
		$args = apply_filters( 'social_post_flow_repost_get_post_ids', $args, $post_type );

		// Run query.
		add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
		$posts = new WP_Query( $args );
		remove_filter( 'posts_where', array( $this, 'posts_where' ), 10 );

		// Return results.
		return $posts->posts;

	}

	/**
	 * Returns the number of Posts fetched per query when reposting.
	 *
	 * @since   1.4.5
	 *
	 * @return  int
	 */
	public function get_batch_size() {

		/**
		 * Number of Posts fetched per query when reposting.
		 *
		 * @since   1.4.5
		 *
		 * @param   int     $batch_size     Batch size.
		 */
		return max( 1, absint( apply_filters( 'social_post_flow_repost_batch_size', 100 ) ) );

	}

	/**
	 * Excludes Posts set to "Do NOT Post", and Posts reposted within the minimum interval,
	 * from the repost selection query.
	 *
	 * @since   1.4.5
	 *
	 * @param   string   $where  WHERE clause.
	 * @param   WP_Query $query  Query.
	 * @return  string
	 */
	public function posts_where( $where, $query ) {

		global $wpdb;

		$frequency_date = $query->get( 'social_post_flow_repost' );
		if ( ! $frequency_date ) {
			return $where;
		}

		$where .= $wpdb->prepare(
			" AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} spf_override WHERE spf_override.post_id = {$wpdb->posts}.ID AND spf_override.meta_key = %s AND spf_override.meta_value LIKE %s )",
			social_post_flow()->plugin->name,
			'%' . $wpdb->esc_like( '"override";s:2:"-1";' ) . '%'
		);
		$where .= $wpdb->prepare(
			" AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} spf_reposted WHERE spf_reposted.post_id = {$wpdb->posts}.ID AND spf_reposted.meta_key = %s AND spf_reposted.meta_value >= %s )",
			$this->meta_key,
			$frequency_date
		);

		return $where;

	}

	/**
	 * Updates the last reposted date on the given Post ID to today's date.
	 *
	 * @since   1.0.0
	 *
	 * @param   int $post_id    Post ID.
	 */
	public function update_last_reposted_date( $post_id ) {

		update_post_meta( $post_id, $this->meta_key, gmdate( 'Y-m-d' ) );

	}

}
