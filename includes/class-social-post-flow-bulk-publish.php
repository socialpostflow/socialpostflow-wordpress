<?php
/**
 * Bulk Publish class.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Bulk Publish screens.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */
class Social_Post_Flow_Bulk_Publish {

	/**
	 * Fetches matching Posts, Pages and/or Custom Post Types that are eligible
	 * to be bulk published to the API.
	 *
	 * If Post(s) are found, they are sent to the API, using the status settings
	 * as defined in the 'bulk_publish' section of the Plugin.
	 *
	 * Where this function is invoked from the WP-CLI, verbose logging is also
	 * output in the console.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $post_type      Post Type.
	 * @param   array  $params         Search Parameters.
	 * @param   bool   $test_mode      Test Mode (don't send to API).
	 */
	public function run( $post_type = false, $params = false, $test_mode = false ) {

		// Check a valid access token exists.
		$access_token = social_post_flow()->get_class( 'settings' )->get_access_token();
		if ( ! $access_token ) {
			return new WP_Error(
				'social_post_flow_no_access_token',
				__( 'The Plugin has not been authorized with Social Post Flow. Go to Social Post Flow > Settings to setup the plugin.', 'social-post-flow' )
			);
		}

		// Get all public Post Types.
		$post_types = social_post_flow()->get_class( 'common' )->get_post_types();

		// Check if Logging is enabled.
		$log_enabled = social_post_flow()->get_class( 'log' )->is_enabled();

		// Fetch Post IDs.
		$post_ids = $this->get_post_ids( $post_type, $params );

		// Skip if no Post IDs found.
		if ( ! $post_ids || count( $post_ids ) === 0 ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::error(
					sprintf(
						/* translators: Post Type Plural Name */
						__( 'No %s found matching the given Bulk Publish conditions. Please adjust / remove conditions as necessary.', 'social-post-flow' ),
						( isset( $post_types[ $post_type ]->labels->name ) ? $post_types[ $post_type ]->labels->name : __( 'Posts', 'social-post-flow' ) )
					)
				);
			}

			return new WP_Error(
				sprintf(
					/* translators: Post Type Plural Name */
					__( 'No %s found matching the given Bulk Publish conditions. Please adjust / remove conditions as necessary.', 'social-post-flow' ),
					( isset( $post_types[ $post_type ]->labels->name ) ? $post_types[ $post_type ]->labels->name : __( 'Posts', 'social-post-flow' ) )
				)
			);
		}

		// Iterate through Post IDs.
		foreach ( $post_ids as $post_id ) {
			// Bulk Publish.
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::log( '---' );
				WP_CLI::log( 'Post #' . $post_id . ': Bulk Publishing' );
			}

			// Attempt to Bulk Publish the Post.
			$results = social_post_flow()->get_class( 'publish' )->publish( $post_id, 'bulk_publish', $test_mode );
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
								'action'         => 'bulk_publish',
								'request_sent'   => gmdate( 'Y-m-d H:i:s' ),
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
		}

		// Done.
		return true;

	}

	/**
	 * Returns all published Posts for the given Post Type, based on the given
	 * query arguments
	 *
	 * @since   1.0.0
	 *
	 * @param   string $post_type  Post Type.
	 * @param   array  $params     Search Parameters.
	 *              $post_ids   array   Post IDs.
	 *              $start_date string  Published Start Date (yyyy-mm-dd).
	 *              $end_date   string  Published End Date (yyyy-mm-dd).
	 *              $authors    array   Author IDs.
	 *              $meta       array   Post Meta.
	 *              $s          string  Search.
	 *              $taxonomies array   Taxonomies and Terms.
	 *              $orderby    string  Order By.
	 *              $order      string  Order (asc|desc).
	 * @return  array               Post IDs. Use false where not specifying a parameter
	 */
	public function get_post_ids( $post_type, $params ) {

		// Define default params.
		$defaults = array(
			'post_ids'   => false,
			'start_date' => false,
			'end_date'   => false,
			'authors'    => false,
			'meta'       => false,
			's'          => false,
			'taxonomies' => false,
			'orderby'    => false,
			'order'      => false,
		);

		// Merge defaults with params.
		$params = array_merge( $defaults, $params );

		// Define default WP_Query args.
		$args = array(
			'post_type'              => ( ! $post_type ? array( 'any' ) : $post_type ),
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_term_meta_cache' => false,
		);

		// Post IDs.
		if ( ! empty( $params['post_ids'] ) ) {
			$post_ids = array();
			foreach ( $params['post_ids'] as $key => $post_id ) {
				$post_ids[ $key ] = absint( $post_id );
			}

			$args['post__in'] = $post_ids;
		}

		// Dates.
		if ( ! empty( $params['start_date'] ) || ! empty( $params['end_date'] ) ) {
			$date_query = array(
				'inclusive' => true,
			);

			// Start Date.
			if ( ! empty( $params['start_date'] ) ) {
				$date_query['after'] = array(
					'year'  => gmdate( 'Y', strtotime( $params['start_date'] ) ),
					'month' => gmdate( 'm', strtotime( $params['start_date'] ) ),
					'day'   => gmdate( 'd', strtotime( $params['start_date'] ) ),
				);
			}

			// End Date.
			if ( ! empty( $params['end_date'] ) ) {
				$date_query['before'] = array(
					'year'  => gmdate( 'Y', strtotime( $params['end_date'] ) ),
					'month' => gmdate( 'm', strtotime( $params['end_date'] ) ),
					'day'   => gmdate( 'd', strtotime( $params['end_date'] ) ),
				);
			}

			// Add date query to WP_Query args.
			$args['date_query'] = array(
				$date_query,
			);
		}

		// Authors.
		if ( ! empty( $params['authors'] ) ) {
			$author_ids = array();
			foreach ( $params['authors'] as $author_id ) {
				$author_ids[] = absint( $author_id );
			}
			$args['author__in'] = $author_ids;
		}

		// Meta.
		if ( ! empty( $params['meta'] ) ) {
			$args['meta_query'] = $params['meta'];
		}

		// Search.
		if ( ! empty( $params['s'] ) ) {
			$args['s'] = $params['s'];
		}

		// Taxonomies.
		if ( ! empty( $params['taxonomies'] ) ) {
			$tax_query = array();
			foreach ( $params['taxonomies'] as $taxonomy => $terms ) {
				// Skip if terms are empty.
				if ( empty( $terms ) ) {
					continue;
				}

				// Convert terms to array if not an array.
				if ( ! is_array( $terms ) ) {
					$terms = explode( ',', $terms );
				}

				// Build term ids.
				$term_ids = array();
				foreach ( $terms as $term_id ) {
					$term_ids[] = absint( $term_id );
				}

				// Add to taxonomy query.
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'id',
					'terms'    => $term_ids,
				);
			}

			// If the tax query isn't empty, add it to the WP_Query args.
			if ( ! empty( $tax_query ) ) {
				$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'relation' => 'OR',
					$tax_query,
				);
			}
		}

		// Order By.
		if ( ! empty( $params['orderby'] ) ) {
			$args['orderby'] = $params['orderby'];
		}

		// Order.
		if ( ! empty( $params['order'] ) ) {
			$args['order'] = $params['order'];
		}

		/**
		 * Filters WP_Query arguments for fetching Post IDs for a given Post Type,
		 * that are then checked to see if reposting is required.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $args       WP_Query Arguments.
		 * @param   string  $post_type  Post Type.
		 * @param   array   $params     Search Parameters.
		 */
		$args = apply_filters( 'social_post_flow_bulk_publish_get_post_ids', $args, $post_type, $params );

		// Run query.
		$posts = new WP_Query( $args );

		// Return results.
		return $posts->posts;

	}

}
