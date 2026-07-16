<?php
/**
 * Bulk Publish CLI class.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Defines the Bulk Publish WP-CLI Command
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */
class Social_Post_Flow_CLI_Bulk_Publish {

	/**
	 * Reposts Posts, Pages and Custom Post Types to the API
	 * based on the status settings at Plugin and Post level.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $args           Array of positional arguments (not used).
	 * @param   array $assoc_args     Array of associative arguments.
	 */
	public function __invoke( $args, $assoc_args ) {

		// Get Plugin Instance.
		$plugin = Social_Post_Flow::get_instance();

		$plugin->get_class( 'log' )->add_to_debug_log( $plugin->plugin->displayName . ': WP-CLI: Bulk Publish: Started' );

		// Get Post Type.
		$post_type = ( isset( $assoc_args['post_type'] ) ? $assoc_args['post_type'] : false );

		// Get Search Parameters.
		$params = array(
			'post_ids'   => ( isset( $assoc_args['ids'] ) ? explode( ',', $assoc_args['ids'] ) : false ),
			'start_date' => ( isset( $assoc_args['start_date'] ) ? $assoc_args['start_date'] : false ),
			'end_date'   => ( isset( $assoc_args['end_date'] ) ? $assoc_args['end_date'] : false ),
			'authors'    => ( isset( $assoc_args['authors'] ) ? explode( ',', $assoc_args['authors'] ) : false ),
			'meta'       => false,
			's'          => ( isset( $assoc_args['s'] ) ? $assoc_args['s'] : false ),
			'taxonomies' => false,
			'orderby'    => ( isset( $assoc_args['orderby'] ) ? $assoc_args['orderby'] : false ),
			'order'      => ( isset( $assoc_args['order_by'] ) ? $assoc_args['order'] : false ),
		);

		// Get Taxonomy Search Parameters for all registered Taxonomies.
		foreach ( get_taxonomies() as $taxonomy ) {
			if ( ! isset( $assoc_args[ $taxonomy ] ) ) {
				continue;
			}

			if ( ! is_array( $params['taxonomies'] ) ) {
				$params['taxonomies'] = array();
			}

			$params['taxonomies'][ $taxonomy ] = explode( ',', $assoc_args[ $taxonomy ] );
		}

		// Get Meta Search Parameters.
		if ( isset( $assoc_args['meta_key'] ) && ! empty( $assoc_args['meta_key'] ) ) {
			$params['meta'] = array(
				array(
					'key'     => ( isset( $assoc_args['meta_key'] ) ? $assoc_args['meta_key'] : false ),
					'value'   => ( isset( $assoc_args['meta_value'] ) ? $assoc_args['meta_value'] : false ),
					'compare' => ( isset( $assoc_args['meta_compare'] ) ? $assoc_args['meta_compare'] : false ),
				),
			);
		}

		// Get Test Mode Flag.
		$test_mode = ( isset( $assoc_args['test_mode'] ) ? true : false );

		$plugin->get_class( 'log' )->add_to_debug_log( $plugin->plugin->displayName . ': WP-CLI: Bulk Publish: Post Type: ' . $post_type );
		$plugin->get_class( 'log' )->add_to_debug_log( $plugin->plugin->displayName . ': WP-CLI: Bulk Publish: Test Mode: ' . $test_mode );
		$plugin->get_class( 'log' )->add_to_debug_log( $plugin->plugin->displayName . ': WP-CLI: Bulk Publish: Parameters: ' . print_r( $params, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions

		// Run Bulk Publish.
		$plugin->get_class( 'bulk_publish' )->run( $post_type, $params, $test_mode );

		$plugin->get_class( 'log' )->add_to_debug_log( $plugin->plugin->displayName . ': WP-CLI: Bulk Publish: Stopped' );

	}

}
