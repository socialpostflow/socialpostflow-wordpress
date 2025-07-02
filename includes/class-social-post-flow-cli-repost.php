<?php
/**
 * Repost CLI class.
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Defines the Repost WP-CLI Command
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 1.0.0
 */
class Social_Post_Flow_CLI_Repost {

	/**
	 * Reposts Posts, Pages and Custom Post Types to the API
	 * based on the status settings at Plugin and Post level.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $args           Array of positional arguments (not used).
	 * @param   array $assoc_args     Array of associative arguments.
	 */
	public function __invoke( $args, $assoc_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Get Plugin Instance.
		$plugin = Social_Post_Flow::get_instance();

		$plugin->get_class( 'log' )->add_to_debug_log( $plugin->plugin->displayName . ': WP-CLI: Repost: Started' );

		// Get Arguments.
		$post_types = ( isset( $assoc_args['post_types'] ) ? explode( ',', $assoc_args['post_types'] ) : false );
		$test_mode  = ( isset( $assoc_args['test_mode'] ) ? true : false );

		$plugin->get_class( 'log' )->add_to_debug_log( $plugin->plugin->displayName . ': WP-CLI: Repost: Post Types: ' . print_r( $post_types, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		$plugin->get_class( 'log' )->add_to_debug_log( $plugin->plugin->displayName . ': WP-CLI: Repost: Test Mode: ' . $test_mode );

		// Run Repost.
		$plugin->get_class( 'repost' )->run( $post_types, $test_mode );

		$plugin->get_class( 'log' )->add_to_debug_log( $plugin->plugin->displayName . ': WP-CLI: Repost: Stopped' );

	}

}
